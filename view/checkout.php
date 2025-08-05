<?php
session_start();
require_once __DIR__ . '/../model/database.php';
require_once __DIR__ . '/../model/VoucherHelper.php';

$db   = new database();
$conn = $db->getConnection();
$voucherHelper = new VoucherHelper($conn);

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

// Xử lý voucher
$applied_voucher = null;
$voucher_discount = 0;
$original_total = $total_amount;
$voucher_errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['apply_voucher'])) {
    $voucher_code = strtoupper(trim($_POST['voucher_code'] ?? ''));
    
    if (!empty($voucher_code)) {
        $validation = $voucherHelper->validateVoucher($voucher_code, $user['MAKH'], $total_amount);
        
        if ($validation['valid']) {
            $applied_voucher = $validation['voucher'];
            $voucher_discount = $voucherHelper->calculateDiscount($applied_voucher, $total_amount);
            $total_amount = $original_total - $voucher_discount;
            $_SESSION['applied_voucher'] = [
                'code' => $voucher_code,
                'discount' => $voucher_discount,
                'voucher_data' => $applied_voucher
            ];
        } else {
            $voucher_errors[] = $validation['message'];
        }
    }
}

// Khôi phục voucher từ session nếu có
if (!$applied_voucher && isset($_SESSION['applied_voucher'])) {
    $session_voucher = $_SESSION['applied_voucher'];
    $validation = $voucherHelper->validateVoucher($session_voucher['code'], $user['MAKH'], $original_total);
    
    if ($validation['valid']) {
        $applied_voucher = $validation['voucher'];
        $voucher_discount = $voucherHelper->calculateDiscount($applied_voucher, $original_total);
        $total_amount = $original_total - $voucher_discount;
    } else {
        unset($_SESSION['applied_voucher']);
        $voucher_errors[] = 'Voucher đã hết hạn hoặc không còn hiệu lực';
    }
}

// Xử lý hủy voucher
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_voucher'])) {
    unset($_SESSION['applied_voucher']);
    $applied_voucher = null;
    $voucher_discount = 0;
    $total_amount = $original_total;
}

// Lấy danh sách voucher có thể sử dụng
$available_vouchers = $voucherHelper->getAvailableVouchers($user['MAKH'], $original_total);

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
                INSERT INTO donhang (MADONHANG, MAKH, NGAYDAT, TONGTIEN, TRANGTHAI, HOTEN, SODIENTHOAI, DIACHI, PHUONGTHUCTHANHTOAN, GHICHU, MAVOUCHER, GIAMGIA)
                VALUES (?, ?, NOW(), ?, 'Chờ xử lý', ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $order_code,
                $user['MAKH'],
                $total_amount,
                $fullname,
                $phone,
                $address,
                $payment_method,
                $notes,
                $applied_voucher ? $applied_voucher['MAVOUCHER'] : null,
                $voucher_discount
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

            // Áp dụng voucher nếu có
            if ($applied_voucher) {
                $result = $voucherHelper->applyVoucher(
                    $applied_voucher['MAVOUCHER'], 
                    $user['MAKH'], 
                    $order_code, 
                    $original_total
                );
                
                if (!$result['success']) {
                    // Log error nhưng vẫn cho đặt hàng thành công
                    error_log("Voucher application failed: " . $result['message']);
                }
                
                // Xóa voucher khỏi session
                unset($_SESSION['applied_voucher']);
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

// Tính phí vận chuyển
$shipping_fee = ($applied_voucher && $applied_voucher['LOAIVOUCHER'] === 'freeship') ? 0 : 30000;
$final_total = $total_amount + $shipping_fee;
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Thanh toán - MENSTA</title>
    <link rel="stylesheet" href="/web_3/view/css/style.css"/>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet" />
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        
        body {
            font-family: "Inter", sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px 0;
        }

        .checkout-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .checkout-header {
            text-align: center;
            margin-bottom: 40px;
        }

        .checkout-header h1 {
            color: white;
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 10px;
            text-shadow: 0 2px 4px rgba(0,0,0,0.3);
        }

        .checkout-header p {
            color: rgba(255,255,255,0.9);
            font-size: 1.1rem;
        }

        .checkout-layout {
            display: grid;
            grid-template-columns: 1fr 400px;
            gap: 30px;
            align-items: start;
        }

        .checkout-main {
            display: flex;
            flex-direction: column;
            gap: 25px;
        }

        .card {
            background: white;
            border-radius: 16px;
            padding: 25px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            border: 1px solid rgba(255,255,255,0.2);
        }

        .card-header {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f1f3f4;
        }

        .card-header i {
            font-size: 1.5rem;
            margin-right: 12px;
            color: #667eea;
        }

        .card-header h3 {
            font-size: 1.3rem;
            font-weight: 600;
            color: #2d3748;
        }

        /* Products Section */
        .product-item {
            display: flex;
            padding: 20px;
            background: #f8fafc;
            border-radius: 12px;
            margin-bottom: 15px;
            border: 1px solid #e2e8f0;
            transition: all 0.3s ease;
        }

        .product-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }

        .product-image {
            width: 90px;
            height: 90px;
            border-radius: 12px;
            object-fit: cover;
            margin-right: 20px;
            border: 2px solid #e2e8f0;
        }

        .product-info {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .product-name {
            font-weight: 600;
            font-size: 1.1rem;
            color: #2d3748;
            margin-bottom: 5px;
        }

        .product-details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            font-size: 0.95rem;
        }

        .product-detail-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .product-detail-label {
            color: #64748b;
            font-weight: 500;
        }

        .product-detail-value {
            color: #2d3748;
            font-weight: 600;
        }

        .product-subtotal {
            color: #dc2626;
            font-weight: 700;
            font-size: 1.1rem;
        }

        /* Form Styles */
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group.full-width {
            grid-column: 1 / -1;
        }

        .form-label {
            font-weight: 600;
            color: #374151;
            margin-bottom: 8px;
            font-size: 0.95rem;
        }

        .form-control {
            padding: 12px 16px;
            border: 2px solid #e5e7eb;
            border-radius: 10px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: #fafafa;
        }

        .form-control:focus {
            outline: none;
            border-color: #667eea;
            background: white;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        /* Voucher Section */
        .voucher-section {
            border: 2px dashed #e5e7eb;
            border-radius: 12px;
            padding: 20px;
            background: #fafbfc;
        }

        .voucher-input-group {
            display: flex;
            gap: 12px;
            margin-bottom: 20px;
        }

        .voucher-input {
            flex: 1;
            padding: 14px 18px;
            border: 2px solid #e5e7eb;
            border-radius: 10px;
            font-size: 1rem;
            text-transform: uppercase;
            font-weight: 600;
            letter-spacing: 1px;
        }

        .voucher-apply-btn {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            border: none;
            padding: 14px 24px;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .voucher-apply-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(16, 185, 129, 0.4);
        }

        .voucher-applied {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 20px;
        }

        .voucher-applied-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .voucher-code {
            font-size: 1.2rem;
            font-weight: 700;
            letter-spacing: 1px;
        }

        .voucher-discount {
            font-size: 1.3rem;
            font-weight: 700;
        }

        .voucher-remove-btn {
            background: rgba(255,255,255,0.2);
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 8px;
            font-size: 0.9rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .voucher-remove-btn:hover {
            background: rgba(255,255,255,0.3);
        }

        .available-vouchers-toggle {
            background: none;
            border: none;
            color: #667eea;
            font-weight: 600;
            cursor: pointer;
            text-decoration: underline;
            padding: 0;
            margin-bottom: 15px;
        }

        .voucher-list {
            max-height: 300px;
            overflow-y: auto;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            background: white;
        }

        .voucher-item {
            padding: 16px;
            border-bottom: 1px solid #f1f3f4;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .voucher-item:last-child {
            border-bottom: none;
        }

        .voucher-item:hover {
            background: #f8fafc;
        }

        .voucher-item.disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .voucher-item-code {
            font-weight: 700;
            color: #667eea;
            font-size: 1.1rem;
            margin-bottom: 5px;
        }

        .voucher-item-name {
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 8px;
        }

        .voucher-item-desc {
            color: #64748b;
            font-size: 0.9rem;
            margin-bottom: 10px;
        }

        .voucher-item-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.85rem;
        }

        .voucher-item-condition {
            color: #64748b;
        }

        .voucher-item-savings {
            font-weight: 600;
        }

        .voucher-item-savings.available {
            color: #10b981;
        }

        .voucher-item-savings.unavailable {
            color: #dc2626;
        }

        /* Order Summary */
        .order-summary {
            position: sticky;
            top: 20px;
        }

        .summary-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid #f1f3f4;
        }

        .summary-item:last-child {
            border-bottom: none;
            border-top: 2px solid #e5e7eb;
            padding-top: 15px;
            margin-top: 10px;
        }

        .summary-label {
            color: #64748b;
            font-weight: 500;
        }

        .summary-value {
            font-weight: 600;
            color: #2d3748;
        }

        .summary-discount {
            color: #10b981;
            font-weight: 600;
        }

        .summary-total {
            font-size: 1.4rem;
            font-weight: 700;
            color: #dc2626;
        }

        /* Checkout Button */
        .checkout-btn {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            padding: 18px 30px;
            border-radius: 12px;
            font-size: 1.2rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            width: 100%;
            margin-top: 20px;
        }

        .checkout-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.4);
        }

        .checkout-btn:disabled {
            background: #9ca3af;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        /* Alerts */
        .alert {
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            border: 1px solid transparent;
        }

        .alert-danger {
            background: #fef2f2;
            color: #dc2626;
            border-color: #fecaca;
        }

        .alert-success {
            background: #f0fdf4;
            color: #16a34a;
            border-color: #bbf7d0;
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .checkout-layout {
                grid-template-columns: 1fr;
                gap: 20px;
            }
            
            .order-summary {
                position: static;
                order: -1;
            }
        }

        @media (max-width: 768px) {
            .checkout-container {
                padding: 0 15px;
            }
            
            .checkout-header h1 {
                font-size: 2rem;
            }
            
            .form-grid {
                grid-template-columns: 1fr;
            }
            
            .product-item {
                flex-direction: column;
                text-align: center;
            }
            
            .product-image {
                width: 100%;
                height: 200px;
                margin-right: 0;
                margin-bottom: 15px;
            }
            
            .product-details {
                grid-template-columns: 1fr;
            }
            
            .voucher-input-group {
                flex-direction: column;
            }
        }

        /* Loading States */
        .loading {
            opacity: 0.7;
            pointer-events: none;
        }

        /* Animation */
        .fade-in {
            animation: fadeIn 0.5s ease-in;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>
    <div class="checkout-container">
        <!-- Header -->
        <div class="checkout-header">
            <h1><i class="fas fa-shopping-cart"></i> Thanh toán đơn hàng</h1>
            <p>Vui lòng kiểm tra thông tin và hoàn tất đơn hàng của bạn</p>
        </div>

        <!-- Error Messages -->
        <?php if (isset($errors) && !empty($errors)): ?>
            <div class="alert alert-danger fade-in">
                <i class="fas fa-exclamation-triangle"></i>
                <?php foreach ($errors as $err): ?>
                    <div><?php echo htmlspecialchars($err); ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="checkout-layout">
            <!-- Main Content -->
            <div class="checkout-main">
                <!-- Products Section -->
                <div class="card fade-in">
                    <div class="card-header">
                        <i class="fas fa-box-open"></i>
                        <h3>Sản phẩm đã chọn (<?php echo count($checkout_items); ?> sản phẩm)</h3>
                    </div>
                    <?php foreach ($checkout_items as $item): ?>
                        <div class="product-item">
                            <img src="/web_3/view/uploads/<?= htmlspecialchars($item['hinhanh']) ?>" 
                                 class="product-image"
                                 onerror="this.onerror=null;this.src='/web_3/view/uploads/no-image.jpg'"
                                 alt="<?= htmlspecialchars($item['tensp']) ?>">
                            <div class="product-info">
                                <div class="product-name"><?= htmlspecialchars($item['tensp']) ?></div>
                                <div class="product-details">
                                    <div class="product-detail-item">
                                        <span class="product-detail-label">Màu sắc:</span>
                                        <span class="product-detail-value"><?= htmlspecialchars($item['mausac']) ?></span>
                                    </div>
                                    <div class="product-detail-item">
                                        <span class="product-detail-label">Kích thước:</span>
                                        <span class="product-detail-value"><?= htmlspecialchars($item['kichthuoc']) ?></span>
                                    </div>
                                    <div class="product-detail-item">
                                        <span class="product-detail-label">Đơn giá:</span>
                                        <span class="product-detail-value"><?= number_format($item['final_price']) ?>đ</span>
                                    </div>
                                    <div class="product-detail-item">
                                        <span class="product-detail-label">Số lượng:</span>
                                        <span class="product-detail-value"><?= $item['soluong'] ?></span>
                                    </div>
                                </div>
                                <div class="product-detail-item" style="margin-top: 10px; padding-top: 10px; border-top: 1px solid #e5e7eb;">
                                    <span class="product-detail-label">Thành tiền:</span>
                                    <span class="product-subtotal"><?= number_format($item['subtotal']) ?>đ</span>
                                </div>
                            </div>
                            <input type="hidden" name="selected_items[]" value="<?= htmlspecialchars($item['key']) ?>">
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Customer Information -->
                <div class="card fade-in">
                    <div class="card-header">
                        <i class="fas fa-user"></i>
                        <h3>Thông tin giao hàng</h3>
                    </div>
                    <form id="checkoutForm" method="POST">
                        <!-- Hidden inputs for selected items -->
                        <?php foreach ($checkout_items as $item): ?>
                            <input type="hidden" name="selected_items[]" value="<?= htmlspecialchars($item['key']) ?>">
                        <?php endforeach; ?>
                        
                        <div class="form-grid">
                            <div class="form-group">
                                <label class="form-label">Họ và tên *</label>
                                <input type="text" name="fullname" class="form-control" required
                                       value="<?= htmlspecialchars($_POST['fullname'] ?? $khachhang['TENKH'] ?? '') ?>"
                                       placeholder="VD: Nguyễn Văn A">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Số điện thoại *</label>
                                <input type="tel" name="phone" class="form-control" required
                                       value="<?= htmlspecialchars($_POST['phone'] ?? $khachhang['SDT'] ?? '') ?>"
                                       placeholder="VD: 0901234567">
                            </div>
                            <div class="form-group full-width">
                                <label class="form-label">Địa chỉ giao hàng *</label>
                                <input type="text" name="address" class="form-control" required
                                       value="<?= htmlspecialchars($_POST['address'] ?? $khachhang['DIACHI'] ?? '') ?>"
                                       placeholder="VD: 123 Đường ABC, Phường XYZ, Quận 1, TP.HCM">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Phương thức thanh toán *</label>
                                <select name="payment_method" class="form-control" required>
                                    <option value="">-- Chọn phương thức thanh toán --</option>
                                    <option value="COD" <?= (($_POST['payment_method'] ?? '') === 'COD') ? 'selected' : '' ?>>
                                        💵 Thanh toán khi nhận hàng (COD)
                                    </option>
                                    <option value="Bank" <?= (($_POST['payment_method'] ?? '') === 'Bank') ? 'selected' : '' ?>>
                                        🏦 Chuyển khoản ngân hàng
                                    </option>
                                    <option value="Momo" <?= (($_POST['payment_method'] ?? '') === 'Momo') ? 'selected' : '' ?>>
                                        📱 Ví điện tử MoMo
                                    </option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Ghi chú</label>
                                <input type="text" name="notes" class="form-control"
                                       value="<?= htmlspecialchars($_POST['notes'] ?? '') ?>"
                                       placeholder="Ghi chú thêm cho đơn hàng (tùy chọn)">
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Voucher Section -->
                <div class="card fade-in">
                    <div class="card-header">
                        <i class="fas fa-ticket-alt"></i>
                        <h3>Mã giảm giá</h3>
                    </div>
                    
                    <div class="voucher-section">
                        <?php if (!empty($voucher_errors)): ?>
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-circle"></i>
                                <?php foreach ($voucher_errors as $error): ?>
                                    <div><?php echo htmlspecialchars($error); ?></div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($applied_voucher): ?>
                            <!-- Applied Voucher -->
                            <div class="voucher-applied">
                                <div class="voucher-applied-content">
                                    <div>
                                        <div class="voucher-code">
                                            <i class="fas fa-ticket-alt"></i>
                                            <?php echo htmlspecialchars($applied_voucher['MAVOUCHER']); ?>
                                        </div>
                                        <div style="margin-top: 5px; font-size: 1rem; opacity: 0.9;">
                                            <?php echo htmlspecialchars($applied_voucher['TENVOUCHER']); ?>
                                        </div>
                                    </div>
                                    <div class="voucher-discount">
                                        <i class="fas fa-tags"></i>
                                        -<?php echo number_format($voucher_discount); ?>đ
                                    </div>
                                </div>
                                <form method="POST" style="display: inline;">
                                    <?php foreach ($checkout_items as $item): ?>
                                        <input type="hidden" name="selected_items[]" value="<?= htmlspecialchars($item['key']) ?>">
                                    <?php endforeach; ?>
                                    <button type="submit" name="remove_voucher" class="voucher-remove-btn">
                                        <i class="fas fa-times"></i> Hủy voucher
                                    </button>
                                </form>
                            </div>
                        <?php else: ?>
                            <!-- Voucher Input -->
                            <form method="POST" id="voucherForm">
                                <?php foreach ($checkout_items as $item): ?>
                                    <input type="hidden" name="selected_items[]" value="<?= htmlspecialchars($item['key']) ?>">
                                <?php endforeach; ?>
                                <div class="voucher-input-group">
                                    <input type="text" 
                                           name="voucher_code" 
                                           class="voucher-input"
                                           placeholder="Nhập mã voucher (VD: SAVE20)"
                                           value="<?php echo htmlspecialchars($_POST['voucher_code'] ?? ''); ?>">
                                    <button type="submit" name="apply_voucher" class="voucher-apply-btn">
                                        <i class="fas fa-check"></i> Áp dụng
                                    </button>
                                </div>
                            </form>
                            
                            <!-- Available Vouchers -->
                            <?php if (!empty($available_vouchers)): ?>
                                <button type="button" class="available-vouchers-toggle" onclick="toggleVoucherList()">
                                    <i class="fas fa-gift"></i> 
                                    Xem voucher có thể sử dụng (<?php echo count($available_vouchers); ?> voucher)
                                </button>
                                
                                <div id="voucher-list" style="display: none;">
                                    <div class="voucher-list">
                                        <?php foreach ($available_vouchers as $voucher): ?>
                                            <div class="voucher-item <?php echo $voucher['can_use'] ? '' : 'disabled'; ?>" 
                                                 onclick="selectVoucher('<?php echo htmlspecialchars($voucher['code'] ?? ''); ?>', <?php echo $voucher['can_use'] ? 'true' : 'false'; ?>)">
                                                <div class="voucher-item-code">
                                                    <i class="fas fa-ticket-alt"></i>
                                                    <?php echo htmlspecialchars($voucher['code'] ?? ''); ?>
                                                </div>
                                                <div class="voucher-item-name">
                                                    <?php echo htmlspecialchars($voucher['name'] ?? ''); ?>
                                                </div>
                                                <?php if (isset($voucher['description']) && $voucher['description']): ?>
                                                    <div class="voucher-item-desc">
                                                        <?php echo htmlspecialchars($voucher['description']); ?>
                                                    </div>
                                                <?php endif; ?>
                                                <div class="voucher-item-footer">
                                                    <span class="voucher-item-condition">
                                                        <?php echo $voucher['condition'] ?? ''; ?>
                                                    </span>
                                                    <span class="voucher-item-savings <?php echo $voucher['can_use'] ? 'available' : 'unavailable'; ?>">
                                                        <?php if ($voucher['can_use']): ?>
                                                            <i class="fas fa-arrow-down"></i>
                                                            Tiết kiệm: <?php echo $voucher['formatted_discount'] ?? ''; ?>
                                                        <?php else: ?>
                                                            <i class="fas fa-times-circle"></i>
                                                            Không đủ điều kiện
                                                        <?php endif; ?>
                                                    </span>
                                                </div>
                                                <div style="font-size: 0.8rem; color: #9ca3af; margin-top: 8px;">
                                                    <i class="fas fa-clock"></i>
                                                    <?php echo $voucher['expires'] ?? ''; ?> • <?php echo $voucher['remaining'] ?? ''; ?>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Order Summary Sidebar -->
            <div class="order-summary">
                <div class="card fade-in">
                    <div class="card-header">
                        <i class="fas fa-receipt"></i>
                        <h3>Tóm tắt đơn hàng</h3>
                    </div>
                    
                    <div class="summary-item">
                        <span class="summary-label">
                            <i class="fas fa-shopping-bag"></i>
                            Tạm tính (<?php echo count($checkout_items); ?> sản phẩm):
                        </span>
                        <span class="summary-value"><?php echo number_format($original_total); ?>đ</span>
                    </div>
                    
                    <?php if ($voucher_discount > 0): ?>
                        <div class="summary-item">
                            <span class="summary-label">
                                <i class="fas fa-ticket-alt"></i>
                                Voucher <?php echo htmlspecialchars($applied_voucher['MAVOUCHER']); ?>:
                            </span>
                            <span class="summary-discount">-<?php echo number_format($voucher_discount); ?>đ</span>
                        </div>
                    <?php endif; ?>
                    
                    <div class="summary-item">
                        <span class="summary-label">
                            <i class="fas fa-shipping-fast"></i>
                            Phí vận chuyển:
                        </span>
                        <span class="summary-value">
                            <?php if ($shipping_fee === 0): ?>
                                <span style="color: #10b981; font-weight: 600;">
                                    <i class="fas fa-gift"></i> Miễn phí
                                </span>
                            <?php else: ?>
                                <?php echo number_format($shipping_fee); ?>đ
                            <?php endif; ?>
                        </span>
                    </div>
                    
                    <div class="summary-item">
                        <span class="summary-label summary-total">
                            <i class="fas fa-money-check-alt"></i>
                            Tổng cộng:
                        </span>
                        <span class="summary-total"><?php echo number_format($final_total); ?>đ</span>
                    </div>
                    
                    <button type="button" onclick="submitOrder()" class="checkout-btn">
                        <i class="fas fa-credit-card"></i>
                        Xác nhận đặt hàng
                    </button>
                    
                    <!-- Payment Info -->
                    <div style="margin-top: 20px; padding: 15px; background: #f8fafc; border-radius: 10px; font-size: 0.9rem; color: #64748b;">
                        <div style="display: flex; align-items: center; margin-bottom: 10px;">
                            <i class="fas fa-shield-alt" style="color: #10b981; margin-right: 8px;"></i>
                            <strong>Thanh toán an toàn & bảo mật</strong>
                        </div>
                        <div style="margin-bottom: 5px;">
                            <i class="fas fa-check-circle" style="color: #10b981; margin-right: 5px;"></i>
                            Thông tin được mã hóa SSL
                        </div>
                        <div style="margin-bottom: 5px;">
                            <i class="fas fa-check-circle" style="color: #10b981; margin-right: 5px;"></i>
                            Hỗ trợ đổi trả trong 7 ngày
                        </div>
                        <div>
                            <i class="fas fa-check-circle" style="color: #10b981; margin-right: 5px;"></i>
                            Giao hàng toàn quốc
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Toggle voucher list visibility
        function toggleVoucherList() {
            const voucherList = document.getElementById('voucher-list');
            const toggle = document.querySelector('.available-vouchers-toggle');
            
            if (voucherList.style.display === 'none' || voucherList.style.display === '') {
                voucherList.style.display = 'block';
                voucherList.classList.add('fade-in');
                toggle.innerHTML = '<i class="fas fa-gift"></i> Ẩn danh sách voucher';
            } else {
                voucherList.style.display = 'none';
                toggle.innerHTML = '<i class="fas fa-gift"></i> Xem voucher có thể sử dụng (<?php echo count($available_vouchers); ?> voucher)';
            }
        }

        // Select voucher from list
        function selectVoucher(code, canUse) {
            if (!canUse) {
                showAlert('Voucher này không đủ điều kiện sử dụng cho đơn hàng hiện tại', 'error');
                return;
            }
            
            const voucherInput = document.querySelector('input[name="voucher_code"]');
            if (voucherInput) {
                voucherInput.value = code;
                
                // Submit voucher form
                const voucherForm = document.getElementById('voucherForm');
                const applyButton = voucherForm.querySelector('button[name="apply_voucher"]');
                
                // Add loading state
                applyButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang áp dụng...';
                applyButton.disabled = true;
                
                // Submit form
                setTimeout(() => {
                    applyButton.click();
                }, 500);
            }
        }

        // Submit order function
        function submitOrder() {
            const form = document.getElementById('checkoutForm');
            const button = document.querySelector('.checkout-btn');
            
            // Validate form
            const fullname = form.querySelector('input[name="fullname"]').value.trim();
            const phone = form.querySelector('input[name="phone"]').value.trim();
            const address = form.querySelector('input[name="address"]').value.trim();
            const paymentMethod = form.querySelector('select[name="payment_method"]').value;
            
            if (!fullname || !phone || !address || !paymentMethod) {
                showAlert('Vui lòng điền đầy đủ thông tin bắt buộc!', 'error');
                return;
            }
            
            // Validate phone number
            const phoneRegex = /^[0-9]{10,11}$/;
            if (!phoneRegex.test(phone)) {
                showAlert('Số điện thoại không hợp lệ! Vui lòng nhập 10-11 chữ số.', 'error');
                return;
            }
            
            // Confirm order
            const totalAmount = <?php echo $final_total; ?>;
            const confirmMessage = `Xác nhận đặt hàng với tổng tiền ${totalAmount.toLocaleString('vi-VN')}đ?\n\nThông tin giao hàng:\n• Họ tên: ${fullname}\n• SĐT: ${phone}\n• Địa chỉ: ${address}`;
            
            if (!confirm(confirmMessage)) {
                return;
            }
            
            // Add loading state
            button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang xử lý đơn hàng...';
            button.disabled = true;
            button.classList.add('loading');
            
            // Add place_order input and submit
            const placeOrderInput = document.createElement('input');
            placeOrderInput.type = 'hidden';
            placeOrderInput.name = 'place_order';
            placeOrderInput.value = '1';
            form.appendChild(placeOrderInput);
            
            form.submit();
        }

        // Show alert function
        function showAlert(message, type = 'info') {
            const alertDiv = document.createElement('div');
            alertDiv.className = `alert alert-${type === 'error' ? 'danger' : 'success'} fade-in`;
            alertDiv.innerHTML = `
                <i class="fas fa-${type === 'error' ? 'exclamation-triangle' : 'check-circle'}"></i>
                ${message}
            `;
            
            const container = document.querySelector('.checkout-container');
            container.insertBefore(alertDiv, container.querySelector('.checkout-layout'));
            
            // Auto remove after 5 seconds
            setTimeout(() => {
                alertDiv.remove();
            }, 5000);
        }

        // Enhanced form interactions
        document.addEventListener('DOMContentLoaded', function() {
            // Auto uppercase voucher input
            const voucherInput = document.querySelector('input[name="voucher_code"]');
            if (voucherInput) {
                voucherInput.addEventListener('input', function() {
                    this.value = this.value.toUpperCase();
                });
                
                // Enter key support
                voucherInput.addEventListener('keypress', function(e) {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        const applyButton = document.querySelector('button[name="apply_voucher"]');
                        if (applyButton) {
                            applyButton.click();
                        }
                    }
                });
            }
            
            // Form validation on input
            const requiredInputs = document.querySelectorAll('input[required], select[required]');
            requiredInputs.forEach(input => {
                input.addEventListener('blur', function() {
                    if (!this.value.trim()) {
                        this.style.borderColor = '#dc2626';
                    } else {
                        this.style.borderColor = '#10b981';
                    }
                });
                
                input.addEventListener('input', function() {
                    if (this.value.trim()) {
                        this.style.borderColor = '#10b981';
                    }
                });
            });
            
            // Phone number formatting
            const phoneInput = document.querySelector('input[name="phone"]');
            if (phoneInput) {
                phoneInput.addEventListener('input', function() {
                    // Remove non-digits
                    this.value = this.value.replace(/\D/g, '');
                    
                    // Limit to 11 digits
                    if (this.value.length > 11) {
                        this.value = this.value.substring(0, 11);
                    }
                });
            }
        });

        // Prevent double submission
        let isSubmitting = false;
        document.getElementById('checkoutForm').addEventListener('submit', function(e) {
            if (isSubmitting) {
                e.preventDefault();
                return false;
            }
            isSubmitting = true;
        });
    </script>
</body>
</html>