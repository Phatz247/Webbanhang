<?php
session_start();
require_once __DIR__ . '/../model/database.php';
$db   = new database();
$conn = $db->getConnection();

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Kiểm tra đăng nhập
$user = $_SESSION['user'] ?? null;
if (!$user) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $_SESSION['pending_order'] = $_POST;
    }
    header('Location: login.php?redirect=checkout.php');
    exit;
}

// Lấy thông tin khách hàng từ database
$stmt = $conn->prepare("SELECT TENKH, SDT, DIACHI FROM khachhang WHERE MAKH = ?");
$stmt->execute([$user['MAKH']]);
$khachhang = $stmt->fetch(PDO::FETCH_ASSOC);

// Nếu quay lại từ login và có pending_order, khôi phục dữ liệu POST
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_SESSION['pending_order'])) {
    $_POST = $_SESSION['pending_order'];
    unset($_SESSION['pending_order']);
}

// Kiểm tra có sản phẩm được chọn không
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['selected_items'])) {
    header('Location: shoppingcart.php');
    exit;
}

$selected_keys = (array)$_POST['selected_items'];
if (empty($selected_keys)) {
    header('Location: shoppingcart.php');
    exit;
}

// Lấy thông tin chi tiết các sản phẩm đã chọn từ database (cập nhật giá mới nhất)
$checkout_items = [];
$total_amount = 0;

foreach ($selected_keys as $key) {
    if (!isset($_SESSION['cart'][$key])) continue;
    $cart_item = $_SESSION['cart'][$key];
    $stmt = $conn->prepare("
        SELECT s.MASP, s.TENSP, s.GIA, s.HINHANH, s.MAUSAC, s.KICHTHUOC, s.SOLUONG
        FROM sanpham s
        WHERE s.MASP = ? AND s.KICHTHUOC = ?
        LIMIT 1
    ");
    $stmt->execute([$cart_item['masp'], $cart_item['kichthuoc']]);
    $sp = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$sp) continue;
    if ($sp['SOLUONG'] < $cart_item['soluong']) {
        $_SESSION['checkout_error'] = "Sản phẩm {$sp['TENSP']} (Size: {$sp['KICHTHUOC']}) không đủ hàng!";
        header('Location: shoppingcart.php');
        exit;
    }
    $checkout_item = [
        'key' => $key,
        'masp' => $sp['MASP'],
        'tensp' => $sp['TENSP'],
        'hinhanh' => $sp['HINHANH'],
        'mausac' => $sp['MAUSAC'],
        'kichthuoc' => $sp['KICHTHUOC'],
        'soluong' => $cart_item['soluong'],
        'final_price' => $sp['GIA'],
        'subtotal' => $sp['GIA'] * $cart_item['soluong']
    ];
    $checkout_items[] = $checkout_item;
    $total_amount += $checkout_item['subtotal'];
}

if (empty($checkout_items)) {
    $_SESSION['checkout_error'] = 'Không có sản phẩm hợp lệ để thanh toán!';
    header('Location: shoppingcart.php');
    exit;
}

// Xử lý form submit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    $fullname = trim($_POST['fullname'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $payment_method = $_POST['payment_method'] ?? '';
    $notes = trim($_POST['notes'] ?? '');

    $errors = [];
    if (empty($fullname)) $errors[] = 'Vui lòng nhập họ tên';
    if (empty($phone)) $errors[] = 'Vui lòng nhập số điện thoại';
    if (empty($address)) $errors[] = 'Vui lòng nhập địa chỉ giao hàng';
    if (empty($payment_method)) $errors[] = 'Vui lòng chọn phương thức thanh toán';

    if (empty($errors)) {
        try {
            $conn->beginTransaction();
            
            // Debug logging
            error_log("Starting checkout process for user: " . $user['MAKH']);
            
            $order_code = 'DH' . date('YmdHis') . rand(100, 999);
            
            // Debug: Log order code
            error_log("Generated order code: " . $order_code);
            $stmt = $conn->prepare("
                INSERT INTO donhang (MADONHANG, MAKH, NGAYDAT, TONGTIEN, TRANGTHAI, HOTEN, SODIENTHOAI, DIACHI, PHUONGTHUCTHANHTOAN, GHICHU)
                VALUES (?, ?, NOW(), ?, 'Chờ xử lý', ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $order_code,
                $user['MAKH'],
                $total_amount,
                $fullname,
                $phone,
                $address,
                $payment_method,
                $notes
            ]);
            $madonhang = $order_code;
            if ($madonhang) {
                $stmt = $conn->prepare("SELECT MAKH, TONGTIEN FROM donhang WHERE MADONHANG = ?");
                $stmt->execute([$madonhang]);
                $order = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($order) {
                    $makh = $order['MAKH'];
                    $tongtien = $order['TONGTIEN'];
                    $mahd = 'HD' . date('YmdHis') . uniqid(rand(), true);
                    $mahd = substr($mahd, 0, 20);
                    $stmt = $conn->prepare("INSERT INTO hoadon (MAHD, MAKH, NGAYLAP, TONGTIEN, TRANGTHAI) VALUES (?, ?, NOW(), ?, 'Đã xác nhận')");
                    $stmt->execute([$mahd, $makh, $tongtien]);
                }
            }
            foreach ($checkout_items as $item) {
                // Debug: Log item being processed
                error_log("Processing item: " . json_encode($item));
                
                // Insert vào chi tiết đơn hàng
                $stmt = $conn->prepare("
                    INSERT INTO chitietdonhang (MADONHANG, MASP, KICHTHUOC, SOLUONG, GIA, THANHTIEN)
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                $result = $stmt->execute([
                    $order_code,
                    $item['masp'],
                    $item['kichthuoc'],
                    $item['soluong'],
                    $item['final_price'],
                    $item['subtotal']
                ]);
                
                if (!$result) {
                    throw new Exception("Failed to insert into chitietdonhang for " . $item['masp']);
                }
                
                // Insert vào chi tiết hóa đơn (sử dụng ON DUPLICATE KEY UPDATE để xử lý trùng lặp)
                $stmt = $conn->prepare("
                    INSERT INTO chitiethoadon (MAHD, MASP, SOLUONG, DONGIA)
                    VALUES (?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE
                        SOLUONG = SOLUONG + VALUES(SOLUONG)
                ");
                $stmt->execute([
                    $mahd,
                    $item['masp'],
                    $item['soluong'],
                    $item['final_price']
                ]);

                
                // Trừ số lượng tồn kho
                $stmt = $conn->prepare("
                    UPDATE sanpham 
                    SET SOLUONG = SOLUONG - ? 
                    WHERE MASP = ? AND KICHTHUOC = ? AND SOLUONG >= ?
                ");
                $stmt->execute([
                    $item['soluong'],
                    $item['masp'],
                    $item['kichthuoc'],
                    $item['soluong']
                ]);
                
                unset($_SESSION['cart'][$item['key']]);
            }
            $conn->commit();
            $_SESSION['order_success'] = "Đặt hàng thành công! Mã đơn hàng: {$order_code}";
            header('Location: order_success.php?order=' . $order_code);
            exit;
        } catch (Exception $e) {
            $conn->rollBack();
            
            // Log detailed error information
            error_log("Checkout error: " . $e->getMessage());
            error_log("Error trace: " . $e->getTraceAsString());
            
            $errors[] = 'Có lỗi xảy ra khi đặt hàng: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Thanh toán - MENSTA</title>
  <link rel="stylesheet" href="/web_3/view/css/style.css"/>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
  <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet" />
  <style>
    body { font-family: "Inter", sans-serif; background: #f5f7fa; margin: 0; padding: 0; }
    .container { max-width: 900px; margin: 40px auto; padding: 20px; }
    h2 { text-align: center; color: #111; margin-bottom: 30px; }
    .card { background: white; padding: 25px; border-radius: 10px; box-shadow: 0 0 10px rgba(0,0,0,0.1); margin-bottom: 20px; }
    .checkout-products { margin-bottom: 20px; }
    .checkout-product { display: flex; margin-bottom: 15px; padding: 15px; background: #f8f9fa; border-radius: 8px; border: 1px solid #e9ecef; }
    .checkout-image { width: 80px; height: 80px; object-fit: cover; border-radius: 8px; margin-right: 18px; }
    .checkout-info { display: flex; flex-direction: column; gap: 8px; flex: 1; }
    .checkout-info-row { display: flex; justify-content: space-between; align-items: center; }
    .checkout-info strong { color: #333; min-width: 100px; flex-shrink: 0; }
    .checkout-info .info-value { text-align: right; flex: 1; }
    .total-price { font-size: 18px; font-weight: bold; color: #dc3545; margin-top: 10px; text-align: right; }
    .form-label { font-weight: 500; margin-bottom: 8px; display: block; font-size: 15px; }
    .form-control { width: 100%; padding: 10px; border-radius: 6px; border: 1px solid #ddd; font-size: 15px; box-sizing: border-box; }
    .form-row { display: flex; gap: 15px; margin-bottom: 20px; }
    .form-col { flex: 1; }
    .btn-checkout { background: linear-gradient(90deg,#27ae60,#16a085); color: #fff; font-weight: bold; font-size: 18px; width: 100%; margin-top: 22px; padding: 14px; border-radius: 8px; border: none; box-shadow: 0 2px 8px rgba(39,174,96,0.08);}
    .btn-checkout:disabled { background: #ccc; cursor: not-allowed; }
    .alert-danger { background: #f8d7da; color: #c82333; border: 1px solid #f5c6cb; padding: 10px; border-radius: 6px; margin-bottom: 15px;}
    @media (max-width: 768px) {
      .form-row { flex-direction: column; gap: 0; }
      .checkout-product { flex-direction: column; }
      .checkout-image { width: 100%; height: 200px; margin-right: 0; margin-bottom: 15px; }
    }
  </style>
</head>
<body>
  <div class="container">
    <h2><i class="fas fa-shopping-cart"></i> Thanh toán đơn hàng</h2>
    <?php if (isset($errors) && !empty($errors)): ?>
      <div class="alert alert-danger">
        <?php foreach ($errors as $err) echo htmlspecialchars($err) . '<br>'; ?>
      </div>
    <?php endif; ?>
    <form method="POST" action="">
      <!-- Danh sách sản phẩm -->
      <div class="card checkout-products">
        <h4 class="mb-3"><i class="fas fa-box"></i> Sản phẩm đã chọn</h4>
        <?php foreach ($checkout_items as $item): ?>
          <div class="checkout-product">
            <img src="/web_3/view/uploads/<?= htmlspecialchars($item['hinhanh']) ?>" class="checkout-image"
                 onerror="this.onerror=null;this.src='/web_3/view/uploads/no-image.jpg'">
            <div class="checkout-info">
              <div class="checkout-info-row">
                <strong><?= htmlspecialchars($item['tensp']) ?></strong>
                <span class="info-value"><?= htmlspecialchars($item['mausac']) ?> | <?= htmlspecialchars($item['kichthuoc']) ?></span>
              </div>
              <div class="checkout-info-row">
                <span>Đơn giá:</span>
                <span class="info-value"><?= number_format($item['final_price']) ?>đ</span>
              </div>
              <div class="checkout-info-row">
                <span>Số lượng:</span>
                <span class="info-value"><?= $item['soluong'] ?></span>
              </div>
              <div class="checkout-info-row">
                <span>Thành tiền:</span>
                <span class="info-value" style="color:#dc3545;font-weight:500"><?= number_format($item['subtotal']) ?>đ</span>
              </div>
            </div>
          </div>
          <input type="hidden" name="selected_items[]" value="<?= htmlspecialchars($item['key']) ?>">
        <?php endforeach; ?>
        <div class="total-price">Tổng tiền: <?= number_format($total_amount) ?>đ</div>
      </div>
      <!-- Thông tin giao hàng -->
      <div class="card">
        <h4 class="mb-3"><i class="fas fa-user"></i> Thông tin giao hàng</h4>
        <div class="form-row">
          <div class="form-col">
            <label class="form-label">Họ tên</label>
            <input type="text" name="fullname" class="form-control"
                   value="<?= htmlspecialchars($_POST['fullname'] ?? $khachhang['TENKH'] ?? '') ?>" required>
          </div>
          <div class="form-col">
            <label class="form-label">Số điện thoại</label>
            <input type="text" name="phone" class="form-control"
                   value="<?= htmlspecialchars($_POST['phone'] ?? $khachhang['SDT'] ?? '') ?>" required>
          </div>
        </div>
        <div class="form-row">
          <div class="form-col">
            <label class="form-label">Địa chỉ giao hàng</label>
            <input type="text" name="address" class="form-control"
                   value="<?= htmlspecialchars($_POST['address'] ?? $khachhang['DIACHI'] ?? '') ?>" required>
          </div>
          <div class="form-col">
            <label class="form-label">Ghi chú</label>
            <input type="text" name="notes" class="form-control"
                   value="<?= htmlspecialchars($_POST['notes'] ?? '') ?>" placeholder="Ghi chú thêm (nếu có)">
          </div>
        </div>
      </div>
      <!-- Phương thức thanh toán -->
      <div class="card">
        <h4 class="mb-3"><i class="fas fa-credit-card"></i> Phương thức thanh toán</h4>
        <div class="form-row">
          <div class="form-col">
            <select name="payment_method" class="form-control" required>
              <option value="">-- Chọn phương thức --</option>
              <option value="COD" <?= (($_POST['payment_method'] ?? '') === 'COD') ? 'selected' : '' ?>>Thanh toán khi nhận hàng (COD)</option>
              <option value="Bank">Chuyển khoản ngân hàng</option>
              <option value="Momo">Ví điện tử MoMo</option>
            </select>
          </div>
        </div>
      </div>
      <button type="submit" name="place_order" class="btn-checkout"><i class="fas fa-check"></i> Xác nhận đặt hàng</button>
    </form>
  </div>
  <script>
    document.querySelector('form').addEventListener('submit', function(e) {
      const fullname = document.querySelector('input[name="fullname"]').value.trim();
      const phone = document.querySelector('input[name="phone"]').value.trim();
      const address = document.querySelector('input[name="address"]').value.trim();
      const paymentMethod = document.querySelector('select[name="payment_method"]').value;
      if (!fullname || !phone || !address || !paymentMethod) {
        e.preventDefault();
        alert('Vui lòng điền đầy đủ thông tin bắt buộc!');
        return false;
      }
      const phoneRegex = /^[0-9]{10,11}$/;
      if (!phoneRegex.test(phone)) {
        e.preventDefault();
        alert('Số điện thoại không hợp lệ!');
        return false;
      }
      const totalAmount = '<?= number_format($total_amount) ?>';
      if (!confirm(`Xác nhận đặt hàng với tổng tiền ${totalAmount}đ?`)) {
        e.preventDefault();
        return false;
      }
    });
  </script>
</body>
</html>