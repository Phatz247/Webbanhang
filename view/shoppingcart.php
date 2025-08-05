<?php
session_start();
require_once __DIR__ . '/../model/database.php';
$db   = new database();
$conn = $db->getConnection();

if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $key = $_POST['key'] ?? '';
    if (isset($_SESSION['cart'][$key])) {
        $stmt = $conn->prepare("SELECT SOLUONG FROM sanpham WHERE MASP = ? AND KICHTHUOC = ? LIMIT 1");
        $stmt->execute([$_SESSION['cart'][$key]['masp'], $_SESSION['cart'][$key]['kichthuoc']]);
        $sp = $stmt->fetch(PDO::FETCH_ASSOC);
        $maxQty = $sp ? intval($sp['SOLUONG']) : 1;

        if ($_POST['action'] === 'update_quantity') {
            $newQty = max(1, intval($_POST['quantity'] ?? 1));
            if ($newQty > $maxQty) $newQty = $maxQty;
            $_SESSION['cart'][$key]['soluong'] = $newQty;
        } elseif ($_POST['action'] === 'remove_item') {
            unset($_SESSION['cart'][$key]);
        }
    }
    header('Location: shoppingcart.php');
    exit;
}

$cart_items = [];
$total_cart_value = 0;
$auto_select_key = $_SESSION['auto_select_item'] ?? null; // Lấy key sản phẩm cần tích chọn tự động
unset($_SESSION['auto_select_item']); // Xóa session sau khi sử dụng

foreach ($_SESSION['cart'] as $key => $item) {
    $stmt = $conn->prepare("
        SELECT MASP, TENSP, GIA, HINHANH, MAUSAC, KICHTHUOC, SOLUONG
        FROM sanpham
        WHERE MASP = ? AND KICHTHUOC = ?
        LIMIT 1
    ");
    $stmt->execute([$item['masp'], $item['kichthuoc']]);
    $db_data = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($db_data) {
        // Sử dụng giá từ cart (đã tính khuyến mãi) thay vì giá từ database
        $final_price = isset($item['gia_ban']) && $item['gia_ban'] > 0 ? $item['gia_ban'] : $db_data['GIA'];
        $original_price = $db_data['GIA']; // Giá gốc từ DB
        
        // Debug: Log giá hiển thị trong cart
        error_log("Cart display - Product: " . $db_data['TENSP'] . 
                  ", Cart gia_ban: " . ($item['gia_ban'] ?? 'none') .
                  ", DB GIA: " . $db_data['GIA'] . 
                  ", Final price: " . $final_price);
        
        $db_data['soluong'] = $item['soluong'];
        $db_data['key'] = $key;
        $db_data['GIA'] = $final_price; // Giá cuối cùng (sau khuyến mãi)
        $db_data['original_price'] = $original_price; // Giá gốc để hiển thị
        $db_data['has_sale'] = isset($item['has_promotion']) && $item['has_promotion'];
        $db_data['subtotal'] = $final_price * $item['soluong'];
        $db_data['maxQty'] = $db_data['SOLUONG'];
        $cart_items[] = $db_data;
        $total_cart_value += $db_data['subtotal'];
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Giỏ hàng - MENSTA</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet"/>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
  <style>
    body { background: linear-gradient(120deg,#f5f7fa 60%,#e0f7fa 100%); font-family: "Inter", sans-serif; }
    .container { max-width: 900px; margin: 40px auto; padding: 20px; }
    .cart-list { padding: 0; margin: 0; list-style: none; }
    .cart-item-card {
      background: #fff; border-radius: 14px; box-shadow: 0 2px 16px rgba(0,0,0,0.07);
      display: flex; align-items: center; gap: 18px; margin-bottom: 22px; padding: 18px 22px; transition: box-shadow .2s;
    }
    .cart-item-card:hover { box-shadow: 0 4px 24px rgba(39,174,96,0.10);}
    .cart-item-card.auto-selected {
      border: 2px solid #27ae60;
      background: linear-gradient(45deg, #f8fff8, #ffffff);
      animation: highlightPulse 2s ease-in-out;
    }
    @keyframes highlightPulse {
      0% { box-shadow: 0 0 0 0 rgba(39, 174, 96, 0.7); }
      50% { box-shadow: 0 0 0 10px rgba(39, 174, 96, 0); }
      100% { box-shadow: 0 0 0 0 rgba(39, 174, 96, 0); }
    }
    .cart-image { width: 80px; height: 80px; object-fit: cover; border-radius: 10px; border: 1px solid #eee;}
    .cart-info { flex: 1; }
    .cart-title { font-size: 1.1em; font-weight: 500; color: #222; margin-bottom: 4px;}
    .cart-meta { font-size: 14px; color: #666; margin-bottom: 2px;}
    .cart-price { color: #e67e22; font-weight: 500; font-size: 1em;}
    .quantity-form { display: flex; align-items: center; gap: 4px; margin-top: 6px;}
    .quantity-input { width: 44px; text-align: center; border-radius: 6px; border: 1px solid #ddd; font-size: 16px; }
    .btn { padding: 7px 14px; border-radius: 6px; border: none; cursor: pointer; }
    .btn-danger { background: #e74c3c; color: #fff; }
    .btn-plus, .btn-minus { background: #f1f1f1; color: #333; border: 1px solid #ccc; font-size: 18px; }
    .btn-plus:disabled { background: #eee; color: #bbb; cursor: not-allowed; }
    .stock-info { font-size: 13px; color: #888; }
    .subtotal { font-size: 1.1em; font-weight: 500; color: #27ae60; }
    .empty-cart { text-align: center; color: #888; padding: 50px 0; }
    .cart-actions { display: flex; gap: 8px; }
    .cart-footer { display: flex; justify-content: space-between; align-items: center; margin-top: 30px; }
    .btn-checkout { background: linear-gradient(90deg,#27ae60,#16a085); color: #fff; font-weight: bold; font-size: 18px; padding: 12px 38px; border-radius: 8px; border: none; box-shadow: 0 2px 8px rgba(39,174,96,0.08);}
    .btn-checkout:disabled { background: #ccc; cursor: not-allowed; }
    .btn-back { background: #eee; color: #333; font-weight: 500; font-size: 16px; padding: 12px 38px; border-radius: 8px; border: none;}
    @media (max-width: 768px) {
      .cart-item-card { flex-direction: column; align-items: flex-start; gap: 10px; padding: 12px;}
      .cart-image { width: 60px; height: 60px; }
      .cart-footer { flex-direction: column; gap: 12px; }
      .container { padding: 5px; }
    }
  </style>
</head>
<body>
<div class="container">
  <h2 class="mb-4 text-center" style="color:#27ae60;"><i class="fas fa-shopping-cart"></i> Giỏ hàng của bạn</h2>
  
  <?php if ($auto_select_key): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert" style="background: linear-gradient(45deg, #27ae60, #2ecc71); color: white; border: none; border-radius: 10px;">
      <i class="fas fa-check-circle"></i> Sản phẩm vừa mua đã được tự động chọn để thanh toán!
      <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>
  
  <form method="POST" id="cart-form">
    <?php if (empty($cart_items)): ?>
      <div class="empty-cart">
        <i class="fas fa-box-open fa-3x mb-3"></i>
        <div>Giỏ hàng của bạn đang trống!</div>
        <a href="/web_3/index.php" class="btn btn-back mt-3"><i class="fas fa-arrow-left"></i> Quay lại mua sắm</a>
      </div>
    <?php else: ?>
      <ul class="cart-list">
        <?php foreach ($cart_items as $item): ?>
          <li class="cart-item-card <?= ($auto_select_key && $auto_select_key === $item['key']) ? 'auto-selected' : '' ?>">
            <input type="checkbox" name="selected_items[]" value="<?= htmlspecialchars($item['key']) ?>" class="item-checkbox" 
                   <?= ($auto_select_key && $auto_select_key === $item['key']) ? 'checked' : '' ?> style="margin-right:10px;">
            <img src="/web_3/view/uploads/<?= htmlspecialchars($item['HINHANH']) ?>" class="cart-image"
                 onerror="this.onerror=null;this.src='/web_3/view/uploads/no-image.jpg'">
            <div class="cart-info">
              <div class="cart-title"><?= htmlspecialchars($item['TENSP']) ?></div>
              <div class="cart-meta">Màu: <?= htmlspecialchars($item['MAUSAC']) ?> | Size: <?= htmlspecialchars($item['KICHTHUOC']) ?></div>
              <div class="cart-meta">
                Đơn giá: 
                <?php if ($item['has_sale']): ?>
                  <span style="text-decoration: line-through; color: #888; margin-right: 5px;"><?= number_format($item['original_price']) ?>đ</span>
                  <span class="cart-price"><?= number_format($item['GIA']) ?>đ</span>
                  <span style="color: #28a745; font-size: 0.8em; margin-left: 5px;"><i class="fas fa-tag"></i> SALE</span>
                <?php else: ?>
                  <span class="cart-price"><?= number_format($item['GIA']) ?>đ</span>
                <?php endif; ?>
              </div>
              <div class="stock-info">Còn lại: <?= $item['maxQty'] ?> sản phẩm</div>
              <form method="POST" class="quantity-form" action="shoppingcart.php">
                <input type="hidden" name="key" value="<?= htmlspecialchars($item['key']) ?>">
                <button type="submit" name="action" value="update_quantity" class="btn btn-minus"
                  onclick="this.form.quantity.value=Math.max(1,parseInt(this.form.quantity.value)-1);">-</button>
                <input type="text" name="quantity" class="quantity-input" value="<?= $item['soluong'] ?>" readonly>
                <button type="submit" name="action" value="update_quantity" class="btn btn-plus"
                  <?php if ($item['soluong'] >= $item['maxQty']): ?>disabled<?php endif; ?>
                  onclick="this.form.quantity.value=parseInt(this.form.quantity.value)+1;">
                  +
                </button>
              </form>
              <?php if ($item['soluong'] >= $item['maxQty']): ?>
                <div class="stock-info text-danger">Đã đạt số lượng tối đa!</div>
              <?php endif; ?>
            </div>
            <div class="subtotal">
              <?= number_format($item['subtotal']) ?>đ
            </div>
            <form method="POST" action="shoppingcart.php" style="display:inline;">
              <input type="hidden" name="key" value="<?= htmlspecialchars($item['key']) ?>">
              <button type="submit" name="action" value="remove_item" class="btn btn-danger" onclick="return confirm('Xóa sản phẩm này khỏi giỏ hàng?');">
                <i class="fas fa-trash"></i>
              </button>
            </form>
          </li>
        <?php endforeach; ?>
      </ul>
      <div class="cart-footer">
        <div style="font-size:20px; font-weight:bold;">
          Tổng tiền: <span style="color:#e74c3c"><?= number_format($total_cart_value) ?>đ</span>
        </div>
        <div>
          <button type="button" class="btn-back" onclick="window.history.back()">
            <i class="fas fa-arrow-left"></i> Quay lại
          </button>
          <button type="button" id="checkout-btn" class="btn-checkout ms-2" disabled>
            <i class="fas fa-credit-card"></i> Thanh toán
          </button>
        </div>
      </div>
    <?php endif; ?>
  </form>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
  // Chọn tất cả sản phẩm
  const itemCheckboxes = document.querySelectorAll('.item-checkbox');
  const checkoutBtn = document.getElementById('checkout-btn');

  itemCheckboxes.forEach(cb => {
    cb.addEventListener('change', updateCheckoutBtn);
  });

  function updateCheckoutBtn() {
    const count = Array.from(itemCheckboxes).filter(cb => cb.checked).length;
    checkoutBtn.disabled = count === 0;
  }
  
  // Cập nhật trạng thái nút thanh toán ngay khi tải trang
  updateCheckoutBtn();

  // Xử lý nút thanh toán
  if (checkoutBtn) {
    checkoutBtn.addEventListener('click', function() {
      const form = document.createElement('form');
      form.method = 'POST';
      form.action = 'checkout.php';
      document.querySelectorAll('.item-checkbox:checked').forEach(cb => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'selected_items[]';
        input.value = cb.value;
        form.appendChild(input);
      });
      document.body.appendChild(form);
      form.submit();
    });
  }
</script>
</body>
</html>