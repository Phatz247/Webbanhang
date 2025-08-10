<?php
session_start();
require_once __DIR__ . '/../model/database.php';
$db   = new database();
$conn = $db->getConnection();

if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $key = $_POST['key'] ?? '';
    if (isset($_SESSION['cart'][$key])) {
        $stmt = $conn->prepare("SELECT SOLUONG FROM sanpham WHERE MASP = ? AND KICHTHUOC = ? AND IS_DELETED = 0 LIMIT 1");
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
$auto_select_key = $_SESSION['auto_select_item'] ?? null;
unset($_SESSION['auto_select_item']);

foreach ($_SESSION['cart'] as $key => $item) {
    $stmt = $conn->prepare("
        SELECT MASP, TENSP, GIA, HINHANH, MAUSAC, KICHTHUOC, SOLUONG
        FROM sanpham
        WHERE MASP = ? AND KICHTHUOC = ? AND IS_DELETED = 0
        LIMIT 1
    ");
    $stmt->execute([$item['masp'], $item['kichthuoc']]);
    $db_data = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($db_data) {
        $final_price = isset($item['gia_ban']) && $item['gia_ban'] > 0 ? $item['gia_ban'] : $db_data['GIA'];
        $original_price = $db_data['GIA'];
        
        $db_data['soluong'] = $item['soluong'];
        $db_data['key'] = $key;
        $db_data['GIA'] = $final_price;
        $db_data['original_price'] = $original_price;
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
    :root {
      --primary-color: #4a90e2;
      --secondary-color: #7b68ee;
      --success-color: #27ae60;
      --danger-color: #e74c3c;
      --warning-color: #f39c12;
      --light-bg: #f8fafc;
      --card-shadow: 0 4px 20px rgba(0,0,0,0.08);
      --hover-shadow: 0 8px 30px rgba(74,144,226,0.15);
      --border-radius: 16px;
    }

    * {
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    body { 
      background: #ffffff;
      font-family: "Inter", -apple-system, BlinkMacSystemFont, sans-serif;
      min-height: 100vh;
      padding: 20px 0;
    }

    .container { 
      max-width: 950px; 
      margin: 0 auto; 
      padding: 30px 20px;
    }

    .page-header {
      background: #ffffff;
      border: 1px solid #e1e8ed;
      border-radius: var(--border-radius);
      padding: 30px;
      margin-bottom: 30px;
      box-shadow: var(--card-shadow);
      text-align: center;
    }

    .page-title {
      background: linear-gradient(135deg, var(--danger-color), #b33b2eff); 
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      font-size: 2.5rem;
      font-weight: 700;
      margin: 0;
      letter-spacing: -0.02em;
    }

    .select-all-section {
      background: #ffffff;
      border: 1px solid #e1e8ed;
      border-radius: var(--border-radius);
      padding: 20px 30px;
      margin-bottom: 25px;
      box-shadow: var(--card-shadow);
      display: flex;
      align-items: center;
      gap: 15px;
    }

    .select-all-checkbox {
      width: 20px;
      height: 20px;
      cursor: pointer;
      accent-color: var(--primary-color);
    }

    .select-all-label {
      font-weight: 600;
      color: #2c3e50;
      margin: 0;
      cursor: pointer;
      user-select: none;
    }

    .cart-list { 
      padding: 0; 
      margin: 0; 
      list-style: none; 
    }

    .cart-item-card {
      background: #ffffff;
      border: 1px solid #e1e8ed;
      border-radius: var(--border-radius);
      box-shadow: var(--card-shadow);
      display: flex;
      align-items: center;
      gap: 20px;
      margin-bottom: 20px;
      padding: 25px;
      position: relative;
      overflow: hidden;
    }

    .cart-item-card::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      height: 3px;
      background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
      transform: scaleX(0);
      transform-origin: left;
      transition: transform 0.3s ease;
    }

    .cart-item-card:hover {
      box-shadow: var(--hover-shadow);
      transform: translateY(-2px);
    }

    .cart-item-card:hover::before {
      transform: scaleX(1);
    }

    .cart-item-card.auto-selected {
      border: 2px solid var(--success-color);
      background: linear-gradient(135deg, rgba(39,174,96,0.05), rgba(255,255,255,0.95));
      animation: highlightPulse 2s ease-in-out;
    }

    @keyframes highlightPulse {
      0% { 
        box-shadow: 0 0 0 0 rgba(39, 174, 96, 0.7);
        transform: scale(1);
      }
      50% { 
        box-shadow: 0 0 0 15px rgba(39, 174, 96, 0);
        transform: scale(1.02);
      }
      100% { 
        box-shadow: 0 0 0 0 rgba(39, 174, 96, 0);
        transform: scale(1);
      }
    }

    .item-checkbox {
      width: 18px;
      height: 18px;
      cursor: pointer;
      accent-color: var(--primary-color);
      margin-right: 5px;
    }

    .cart-image { 
      width: 90px; 
      height: 90px; 
      object-fit: cover; 
      border-radius: 12px; 
      border: 2px solid rgba(255,255,255,0.5);
      box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }

    .cart-info { 
      flex: 1; 
    }

    .cart-title { 
      font-size: 1.15em; 
      font-weight: 600; 
      color: #2c3e50; 
      margin-bottom: 8px;
      line-height: 1.4;
    }

    .cart-meta { 
      font-size: 14px; 
      color: #7f8c8d; 
      margin-bottom: 4px;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .cart-price { 
      color: var(--primary-color); 
      font-weight: 600; 
      font-size: 1.1em;
    }

    .sale-badge {
      background: linear-gradient(135deg, var(--success-color), #2ecc71);
      color: white;
      padding: 2px 8px;
      border-radius: 12px;
      font-size: 0.75em;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }

    .quantity-form { 
      display: flex; 
      align-items: center; 
      gap: 8px; 
      margin-top: 10px;
      background: rgba(248,250,252,0.8);
      padding: 8px 12px;
      border-radius: 12px;
      border: 1px solid rgba(0,0,0,0.05);
    }

    .quantity-input { 
      width: 50px; 
      text-align: center; 
      border-radius: 8px; 
      border: 1px solid #e1e8ed; 
      font-size: 16px;
      font-weight: 600;
      background: white;
      padding: 6px;
    }

    .btn { 
      padding: 8px 16px; 
      border-radius: 10px; 
      border: none; 
      cursor: pointer; 
      font-weight: 600;
      display: inline-flex;
      align-items: center;
      gap: 6px;
      text-decoration: none;
    }

    .btn-danger { 
      background: linear-gradient(135deg, var(--danger-color), #c0392b); 
      color: white;
      box-shadow: 0 4px 12px rgba(231,76,60,0.3);
    }

    .btn-danger:hover {
      transform: translateY(-1px);
      box-shadow: 0 6px 16px rgba(231,76,60,0.4);
    }

    .btn-plus, .btn-minus { 
      background: linear-gradient(135deg, #f8f9fa, #e9ecef);
      color: #495057; 
      border: 1px solid #dee2e6; 
      font-size: 16px;
      width: 36px;
      height: 36px;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 0;
    }

    .btn-plus:hover, .btn-minus:hover {
      background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
      color: white;
      transform: scale(1.05);
    }

    .btn-plus:disabled { 
      background: #f8f9fa; 
      color: #adb5bd; 
      cursor: not-allowed;
      transform: none;
    }

    .stock-info { 
      font-size: 13px; 
      color: #95a5a6;
      margin-top: 4px;
    }

    .subtotal { 
      font-size: 1.3em; 
      font-weight: 700; 
      background: linear-gradient(135deg, var(--success-color), #2ecc71);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      min-width: 120px;
      text-align: right;
    }

    .empty-cart { 
      text-align: center; 
      color: #7f8c8d; 
      padding: 80px 20px;
      background: #ffffff;
      border: 1px solid #e1e8ed;
      border-radius: var(--border-radius);
    }

    .empty-cart i {
      color: #bdc3c7;
      margin-bottom: 20px;
    }

    .cart-footer { 
      background: #ffffff;
      border: 1px solid #e1e8ed;
      border-radius: var(--border-radius);
      padding: 25px 30px;
      margin-top: 30px;
      box-shadow: var(--card-shadow);
      display: flex; 
      justify-content: space-between; 
      align-items: center;
    }

    .total-amount {
      font-size: 1.2em;
      font-weight: 600;
      color: #2c3e50;
    }

    .total-value {
      background: linear-gradient(135deg, var(--danger-color), #c0392b);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      font-size: 1.3em;
    }

    .btn-checkout { 
      background: linear-gradient(135deg, var(--success-color), #2ecc71);
      color: white; 
      font-weight: 700; 
      font-size: 18px; 
      padding: 15px 30px; 
      border-radius: 12px; 
      border: none; 
      box-shadow: 0 6px 20px rgba(39,174,96,0.3);
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }

    .btn-checkout:hover:not(:disabled) {
      transform: translateY(-2px);
      box-shadow: 0 8px 25px rgba(39,174,96,0.4);
    }

    .btn-checkout:disabled { 
      background: linear-gradient(135deg, #bdc3c7, #95a5a6);
      cursor: not-allowed;
      transform: none;
      box-shadow: none;
    }

    .btn-back { 
      background: linear-gradient(135deg, #95a5a6, #7f8c8d);
      color: white; 
      font-weight: 600; 
      font-size: 16px; 
      padding: 15px 30px; 
      border-radius: 12px; 
      border: none;
      box-shadow: 0 4px 12px rgba(149,165,166,0.3);
    }

    .btn-back:hover {
      transform: translateY(-1px);
      box-shadow: 0 6px 16px rgba(149,165,166,0.4);
    }

    .alert-success {
      background: linear-gradient(135deg, var(--success-color), #2ecc71);
      border: none;
      border-radius: var(--border-radius);
      color: white;
      box-shadow: var(--card-shadow);
      backdrop-filter: blur(20px);
    }

    @media (max-width: 768px) {
      .cart-item-card { 
        flex-direction: column; 
        align-items: flex-start; 
        gap: 15px; 
        padding: 20px;
      }
      
      .cart-image { 
        width: 70px; 
        height: 70px; 
        align-self: center;
      }
      
      .cart-footer { 
        flex-direction: column; 
        gap: 20px; 
        text-align: center;
      }
      
      .container { 
        padding: 15px; 
      }

      .page-title {
        font-size: 2rem;
      }

      .select-all-section {
        padding: 15px 20px;
      }
    }

    .cart-actions {
      display: flex;
      gap: 10px;
      align-items: center;
    }
  </style>
</head>
<body>
<div class="container">
  <div class="page-header">
    <h1 class="page-title">
      <i class="fas fa-shopping-cart"></i> Giỏ hàng của bạn
    </h1>
  </div>
  
  <?php if ($auto_select_key): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
      <i class="fas fa-check-circle"></i> Sản phẩm vừa mua đã được tự động chọn để thanh toán!
      <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>
  
  <form method="POST" id="cart-form">
    <?php if (empty($cart_items)): ?>
      <div class="empty-cart">
        <i class="fas fa-box-open fa-4x"></i>
        <h3 style="margin: 20px 0;">Giỏ hàng của bạn đang trống!</h3>
        <p>Hãy khám phá các sản phẩm tuyệt vời của chúng tôi</p>
        <a href="/web_3/index.php" class="btn btn-back mt-3">
          <i class="fas fa-arrow-left"></i> Quay lại mua sắm
        </a>
      </div>
    <?php else: ?>
      <!-- Select All Section -->
      <div class="select-all-section">
        <input type="checkbox" id="select-all" class="select-all-checkbox">
        <label for="select-all" class="select-all-label">
          <i class="fas fa-check-square"></i> Chọn tất cả sản phẩm
        </label>
        <span id="selected-count" class="badge bg-primary ms-2">0 đã chọn</span>
      </div>

      <ul class="cart-list">
        <?php foreach ($cart_items as $item): ?>
          <li class="cart-item-card <?= ($auto_select_key && $auto_select_key === $item['key']) ? 'auto-selected' : '' ?>">
            <input type="checkbox" name="selected_items[]" value="<?= htmlspecialchars($item['key']) ?>" class="item-checkbox" 
                   <?= ($auto_select_key && $auto_select_key === $item['key']) ? 'checked' : '' ?>>
            
            <img src="/web_3/view/uploads/<?= htmlspecialchars($item['HINHANH']) ?>" class="cart-image"
                 onerror="this.onerror=null;this.src='/web_3/view/uploads/no-image.jpg'" alt="<?= htmlspecialchars($item['TENSP']) ?>">
            
            <div class="cart-info">
              <div class="cart-title"><?= htmlspecialchars($item['TENSP']) ?></div>
              
              <div class="cart-meta">
                <span><i class="fas fa-palette"></i> <?= htmlspecialchars($item['MAUSAC']) ?></span>
                <span><i class="fas fa-expand-arrows-alt"></i> <?= htmlspecialchars($item['KICHTHUOC']) ?></span>
              </div>
              
              <div class="cart-meta">
                <span>Đơn giá:</span>
                <?php if ($item['has_sale']): ?>
                  <span style="text-decoration: line-through; color: #95a5a6; margin-right: 8px;">
                    <?= number_format($item['original_price']) ?>đ
                  </span>
                  <span class="cart-price"><?= number_format($item['GIA']) ?>đ</span>
                  <span class="sale-badge">
                    <i class="fas fa-fire"></i> Sale
                  </span>
                <?php else: ?>
                  <span class="cart-price"><?= number_format($item['GIA']) ?>đ</span>
                <?php endif; ?>
              </div>
              
              <div class="stock-info">
                <i class="fas fa-warehouse"></i> Còn lại: <?= $item['maxQty'] ?> sản phẩm
              </div>
              
              <div class="quantity-form">
                <input type="hidden" class="item-key" value="<?= htmlspecialchars($item['key']) ?>">
                <button type="button" class="btn btn-minus quantity-btn" data-action="decrease">
                  <i class="fas fa-minus"></i>
                </button>
                <input type="text" class="quantity-input" value="<?= $item['soluong'] ?>" readonly>
                <button type="button" class="btn btn-plus quantity-btn" data-action="increase"
                  <?php if ($item['soluong'] >= $item['maxQty']): ?>disabled<?php endif; ?>
                  data-max="<?= $item['maxQty'] ?>">
                  <i class="fas fa-plus"></i>
                </button>
              </div>
              
              <?php if ($item['soluong'] >= $item['maxQty']): ?>
                <div class="stock-info text-danger">
                  <i class="fas fa-exclamation-triangle"></i> Đã đạt số lượng tối đa!
                </div>
              <?php endif; ?>
            </div>
            
            <div class="subtotal">
              <?= number_format($item['subtotal']) ?>đ
            </div>
            
            <div class="cart-actions">
              <button type="button" class="btn btn-danger remove-item-btn" 
                      data-key="<?= htmlspecialchars($item['key']) ?>"
                      onclick="return confirm('Xóa sản phẩm này khỏi giỏ hàng?');">
                <i class="fas fa-trash-alt"></i>
              </button>
            </div>
          </li>
        <?php endforeach; ?>
      </ul>
      
      <div class="cart-footer">
        <div class="total-amount">
          Tổng tiền: <span class="total-value"><?= number_format($total_cart_value) ?>đ</span>
        </div>
        <div>
          <button type="button" class="btn-back" onclick="window.history.back()">
            <i class="fas fa-arrow-left"></i> Quay lại
          </button>
          <button type="button" id="checkout-btn" class="btn-checkout ms-3" disabled>
            <i class="fas fa-credit-card"></i> Thanh toán
          </button>
        </div>
      </div>
    <?php endif; ?>
  </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
  // Elements
  const selectAllCheckbox = document.getElementById('select-all');
  const itemCheckboxes = document.querySelectorAll('.item-checkbox');
  const checkoutBtn = document.getElementById('checkout-btn');
  const selectedCountBadge = document.getElementById('selected-count');

  // Event listeners
  if (selectAllCheckbox) {
    selectAllCheckbox.addEventListener('change', function() {
      const isChecked = this.checked;
      itemCheckboxes.forEach(cb => {
        cb.checked = isChecked;
      });
      updateUI();
      updateTotalPrice();
    });
  }

  itemCheckboxes.forEach(cb => {
    cb.addEventListener('change', function() {
      updateSelectAllState();
      updateUI();
      updateTotalPrice();
    });
  });

  // Gọi updateTotalPrice khi trang vừa load để tổng tiền đúng trạng thái tick
  document.addEventListener('DOMContentLoaded', function() {
    updateTotalPrice();
  });

  // Quantity change handlers
  document.addEventListener('click', function(e) {
    if (e.target.closest('.quantity-btn')) {
      const btn = e.target.closest('.quantity-btn');
      const action = btn.dataset.action;
      const quantityForm = btn.closest('.quantity-form');
      const quantityInput = quantityForm.querySelector('.quantity-input');
      const itemKey = quantityForm.querySelector('.item-key').value;
      const maxQty = parseInt(btn.dataset.max) || 999;
      
      let currentQty = parseInt(quantityInput.value);
      let newQty = currentQty;
      
      if (action === 'increase' && currentQty < maxQty) {
        newQty = currentQty + 1;
      } else if (action === 'decrease' && currentQty > 1) {
        newQty = currentQty - 1;
      }
      
      if (newQty !== currentQty) {
        updateQuantity(itemKey, newQty, quantityInput, btn.closest('.cart-item-card'));
      }
    }
  });

  // Remove item handlers
  document.addEventListener('click', function(e) {
    if (e.target.closest('.remove-item-btn')) {
      const btn = e.target.closest('.remove-item-btn');
      const itemKey = btn.dataset.key;
      
      if (confirm('Xóa sản phẩm này khỏi giỏ hàng?')) {
        removeItem(itemKey, btn.closest('.cart-item-card'));
      }
    }
  });

  function updateQuantity(itemKey, newQty, quantityInput, cartCard) {
    const formData = new FormData();
    formData.append('action', 'update_quantity');
    formData.append('key', itemKey);
    formData.append('quantity', newQty);

    // Show loading state
    cartCard.style.opacity = '0.6';
    
    fetch('shoppingcart.php', {
      method: 'POST',
      body: formData
    })
    .then(response => {
      if (response.ok) {
        // Update the quantity input
        quantityInput.value = newQty;
        
        // Update plus button state
        const plusBtn = cartCard.querySelector('[data-action="increase"]');
        const maxQty = parseInt(plusBtn.dataset.max);
        plusBtn.disabled = newQty >= maxQty;
        
        // Update subtotal
        updateSubtotal(cartCard, newQty);
        
        // Update stock warning
        const stockWarning = cartCard.querySelector('.text-danger');
        if (stockWarning) {
          stockWarning.style.display = newQty >= maxQty ? 'block' : 'none';
        }
        
        cartCard.style.opacity = '1';
        
        // Show success feedback
        showFeedback('Đã cập nhật số lượng!', 'success');
      } else {
        throw new Error('Network response was not ok');
      }
    })
    .catch(error => {
      console.error('Error:', error);
      cartCard.style.opacity = '1';
      showFeedback('Có lỗi xảy ra khi cập nhật!', 'error');
    });
  }

  function removeItem(itemKey, cartCard) {
    const formData = new FormData();
    formData.append('action', 'remove_item');
    formData.append('key', itemKey);

    // Show loading state
    cartCard.style.opacity = '0.6';
    
    fetch('shoppingcart.php', {
      method: 'POST',
      body: formData
    })
    .then(response => {
      if (response.ok) {
        // Animate removal
        cartCard.style.transform = 'translateX(-100%)';
        cartCard.style.opacity = '0';
        
        setTimeout(() => {
          cartCard.remove();
          updateTotalPrice();
          
          // Check if cart is empty
          const remainingItems = document.querySelectorAll('.cart-item-card');
          if (remainingItems.length === 0) {
            location.reload();
          }
        }, 300);
        
        showFeedback('Đã xóa sản phẩm khỏi giỏ hàng!', 'success');
      } else {
        throw new Error('Network response was not ok');
      }
    })
    .catch(error => {
      console.error('Error:', error);
      cartCard.style.opacity = '1';
      showFeedback('Có lỗi xảy ra khi xóa sản phẩm!', 'error');
    });
  }

  function updateSubtotal(cartCard, newQty) {
    const priceText = cartCard.querySelector('.cart-price').textContent;
    const price = parseInt(priceText.replace(/[^\d]/g, ''));
    const newSubtotal = price * newQty;
    
    const subtotalElement = cartCard.querySelector('.subtotal');
    subtotalElement.textContent = new Intl.NumberFormat('vi-VN').format(newSubtotal) + 'đ';
    
    updateTotalPrice();
  }

  function updateTotalPrice() {
    let total = 0;
    // Chỉ tính tổng cho các sản phẩm được tick chọn
    document.querySelectorAll('.cart-item-card').forEach(card => {
      const checkbox = card.querySelector('.item-checkbox');
      if (checkbox && checkbox.checked) {
        const subtotalEl = card.querySelector('.subtotal');
        if (subtotalEl) {
          const subtotalText = subtotalEl.textContent;
          const subtotal = parseInt(subtotalText.replace(/[^\d]/g, ''));
          total += subtotal;
        }
      }
    });
    const totalElement = document.querySelector('.total-value');
    if (totalElement) {
      totalElement.textContent = new Intl.NumberFormat('vi-VN').format(total) + 'đ';
    }
  }

  function showFeedback(message, type) {
    // Create feedback element
    const feedback = document.createElement('div');
    feedback.className = `alert alert-${type === 'success' ? 'success' : 'danger'} position-fixed`;
    feedback.style.cssText = `
      top: 20px; 
      right: 20px; 
      z-index: 9999; 
      min-width: 300px;
      opacity: 0;
      transform: translateX(100%);
      transition: all 0.3s ease;
    `;
    feedback.innerHTML = `
      <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i> 
      ${message}
    `;
    
    document.body.appendChild(feedback);
    
    // Animate in
    setTimeout(() => {
      feedback.style.opacity = '1';
      feedback.style.transform = 'translateX(0)';
    }, 100);
    
    // Remove after 3 seconds
    setTimeout(() => {
      feedback.style.opacity = '0';
      feedback.style.transform = 'translateX(100%)';
      setTimeout(() => feedback.remove(), 300);
    }, 3000);
  }

  function updateSelectAllState() {
    if (!selectAllCheckbox) return;
    
    const checkedCount = Array.from(itemCheckboxes).filter(cb => cb.checked).length;
    const totalCount = itemCheckboxes.length;
    
    selectAllCheckbox.checked = checkedCount === totalCount;
    selectAllCheckbox.indeterminate = checkedCount > 0 && checkedCount < totalCount;
  }

  function updateUI() {
    const checkedCount = Array.from(itemCheckboxes).filter(cb => cb.checked).length;
    
    // Update checkout button
    if (checkoutBtn) {
      checkoutBtn.disabled = checkedCount === 0;
    }
    
    // Update selected count badge
    if (selectedCountBadge) {
      selectedCountBadge.textContent = `${checkedCount} đã chọn`;
      selectedCountBadge.className = checkedCount > 0 ? 'badge bg-success ms-2' : 'badge bg-secondary ms-2';
    }
  }

  // Initialize UI state
  updateSelectAllState();
  updateUI();

  // Checkout button handler
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

  // Add smooth scroll effect for auto-selected items
  const autoSelectedItem = document.querySelector('.cart-item-card.auto-selected');
  if (autoSelectedItem) {
    setTimeout(() => {
      autoSelectedItem.scrollIntoView({ 
        behavior: 'smooth', 
        block: 'center' 
      });
    }, 500);
  }
</script>
</body>
</html>