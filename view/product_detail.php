<?php
// view/product_detail.php

// Khởi động session trước tiên
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../model/database.php';
$db   = new database();
$conn = $db->getConnection();

// 1) Lấy MASP từ query
$masp = $_GET['masp'] ?? '';
if (!$masp) {
    die('Không có sản phẩm.');
}

// 2) Lấy thông tin sản phẩm chính
$stmt = $conn->prepare("SELECT * FROM sanpham WHERE MASP = ? AND IS_DELETED = 0");
$stmt->execute([$masp]);
$sp = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$sp) {
    die('Sản phẩm không tồn tại hoặc đã bị ẩn.');
}

// 3) Xác định danh mục để quyết định size list
switch ($sp['MADM']) {
    case 'DM001':
        $sizes = ['S','M','L','XL'];
        break;
    case 'DM002':
        $sizes = ['28','29','30','31','32','33','34'];
        break;
    default:
        $sizes = [];
}

// 4) Lấy tất cả biến thể cùng GROUPSP để build picker màu
$stmt2 = $conn->prepare("SELECT * FROM sanpham WHERE GROUPSP = ? AND IS_DELETED = 0 ORDER BY MASP");
$stmt2->execute([$sp['GROUPSP']]);
$all = $stmt2->fetchAll(PDO::FETCH_ASSOC);
$list_colors = [];
$seen_colors = [];
foreach ($all as $c) {
    if (!in_array($c['MAUSAC'], $seen_colors, true)) {
        $seen_colors[] = $c['MAUSAC'];
        $list_colors[] = $c;
    }
}

// 5) Tính tồn kho và lấy MASP cho mỗi màu và size
$full_stock = [];
$variation_masp = [];  // Thêm mảng mới để lưu MASP của từng variation
foreach ($list_colors as $c) {
    $color = $c['MAUSAC'];
    $full_stock[$color] = [];
    $variation_masp[$color] = [];
    
    if (empty($sizes)) { // Phụ kiện - dùng Freesize
        $stmt3 = $conn->prepare("
            SELECT MASP, SOLUONG
            FROM sanpham
            WHERE TENSP = ? AND MAUSAC = ? AND IS_DELETED = 0
        ");
        $stmt3->execute([$sp['TENSP'], $color]);
        $row = $stmt3->fetch(PDO::FETCH_ASSOC);
        $full_stock[$color]['Freesize'] = $row ? (int)$row['SOLUONG'] : 0;
        $variation_masp[$color]['Freesize'] = $row ? $row['MASP'] : $sp['MASP']; // Fallback to current MASP
    } else { // Sản phẩm có size
        foreach ($sizes as $sz) {
            $stmt3 = $conn->prepare("
                SELECT MASP, SOLUONG
                FROM sanpham
                WHERE TENSP = ? AND MAUSAC = ? AND KICHTHUOC = ? AND IS_DELETED = 0
            ");
            $stmt3->execute([$sp['TENSP'], $color, $sz]);
            $row = $stmt3->fetch(PDO::FETCH_ASSOC);
            $full_stock[$color][$sz] = $row ? (int)$row['SOLUONG'] : 0;
            $variation_masp[$color][$sz] = $row ? $row['MASP'] : null;
        }
    }
}

// 6) Lấy thông tin SALE cho từng MASP biến thể - CAPA THIỆN TRUY VẤN
$saleMap = [];
foreach ($all as $item) {
    $stmtSale = $conn->prepare("
        SELECT 
            ct.gia_khuyenmai, 
            ct.giam_phantram, 
            ctkm.TENCTKM,
            ctkm.NGAYBATDAU, 
            ctkm.NGAYKETTHUC,
            CASE 
                WHEN ctkm.NGAYBATDAU IS NOT NULL 
                     AND ctkm.NGAYKETTHUC IS NOT NULL 
                     AND NOW() BETWEEN ctkm.NGAYBATDAU AND ctkm.NGAYKETTHUC 
                THEN 1
ELSE 0 
            END as promotion_active
        FROM chitietctkm ct
        LEFT JOIN chuongtrinhkhuyenmai ctkm ON ct.MACTKM = ctkm.MACTKM
        WHERE ct.MASP = ?
        ORDER BY promotion_active DESC, ctkm.NGAYKETTHUC DESC
        LIMIT 1
    ");
    $stmtSale->execute([$item['MASP']]);
    $row = $stmtSale->fetch(PDO::FETCH_ASSOC);
    if ($row) $saleMap[$item['MASP']] = $row;
}

// 7) Hàm tính giá sale giống logic trong order_process
function getPriceInfo($originalPrice, $saleData) {
    $result = [
        'original_price' => (int)$originalPrice,
        'final_price' => (int)$originalPrice,
        'discount_amount' => 0,
        'discount_percent' => 0,
        'has_promotion' => false,
        'promotion_name' => '',
        'promotion_type' => ''
    ];
    
    if ($saleData && $saleData['promotion_active'] == 1) {
        $result['has_promotion'] = true;
        $result['promotion_name'] = $saleData['TENCTKM'] ?? 'Khuyến mãi';
        
        // Ưu tiên giá khuyến mãi cố định trước
        if (!empty($saleData['gia_khuyenmai']) && $saleData['gia_khuyenmai'] > 0) {
            $result['promotion_type'] = 'fixed';
            $result['final_price'] = (int)$saleData['gia_khuyenmai'];
            $result['discount_amount'] = $result['original_price'] - $result['final_price'];
            $result['discount_percent'] = round(($result['discount_amount'] / $result['original_price']) * 100, 1);
            
        } elseif (!empty($saleData['giam_phantram']) && $saleData['giam_phantram'] > 0) {
            $result['promotion_type'] = 'percent';
            $result['discount_percent'] = (float)$saleData['giam_phantram'];
            $result['discount_amount'] = round($result['original_price'] * ($result['discount_percent'] / 100));
            $result['final_price'] = $result['original_price'] - $result['discount_amount'];
        } else {
            $result['has_promotion'] = false;
        }
    }
    
    return $result;
}

// 8) Ảnh chi tiết theo GROUPSP (bảng tiếng Việt: hinhanh_sanpham)
$detailImgs = [];
try {
  $stmtImgs = $conn->prepare("SELECT TENFILE FROM hinhanh_sanpham WHERE GROUPSP = ? ORDER BY THUTU ASC, ID ASC");
  $stmtImgs->execute([$sp['GROUPSP']]);
  $detailImgs = $stmtImgs->fetchAll(PDO::FETCH_COLUMN);
  // Loại trùng theo tên file nếu có
  $detailImgs = array_values(array_unique($detailImgs));
} catch (Exception $e) {
  // table có thể chưa tồn tại, bỏ qua an toàn
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Chi tiết <?= htmlspecialchars($sp['TENSP']) ?> – MENSTA</title>
  <link rel="stylesheet" href="/web_3/view/css/style.css"/>
  <link rel="stylesheet" href="/web_3/view/css/product.css"/>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
  <style>
    .main-container { max-width:900px; margin:40px auto; padding:0 20px; }
    .product-section { display:flex; gap:40px; flex-wrap:wrap; }
    .product-images, .product-info { flex:1; min-width:300px; }
    .main-image img { width:100%; border-radius:8px; }
    .product-images { position: relative; }
    /* Thumbnail gallery bottom-left */
    .thumbs-container {
      position: absolute;
      left: 10px;
      bottom: 10px;
      display: flex;
      gap: 8px;
      background: rgba(255,255,255,0.8);
      padding: 6px;
      border-radius: 8px;
      backdrop-filter: blur(2px);
    }
    .thumbs-container .thumb {
      width: 52px;
      height: 52px;
      object-fit: cover;
      border-radius: 6px;
      border: 2px solid transparent;
      cursor: pointer;
      transition: transform 0.15s ease, border-color 0.15s ease;
    }
    .thumbs-container .thumb:hover { transform: scale(1.05); border-color: #dc3545; }
    .thumbs-container .thumb.selected { border-color: #dc3545; }
  /* Dải thumbnail hình chi tiết riêng (không đổi màu) */
  .thumbs-detail-container { position:absolute; left:10px; bottom:10px; display:flex; gap:8px; background:rgba(255,255,255,0.85); padding:6px; border-radius:8px; }
  .thumbs-detail-container .thumb { width:52px; height:52px; object-fit:cover; border-radius:6px; border:2px solid transparent; cursor:pointer; transition:transform .15s ease, border-color .15s ease; }
  .thumbs-detail-container .thumb:hover { transform:scale(1.05); border-color:#198754; }
    .current-price { font-size:24px; color:#dc3545; margin:10px 0; }
    .old-price { text-decoration:line-through; color:#888; margin-right:8px; }
    .sale-price { color:#d00; font-weight:bold; }
.discount { color:#fff; background:#d00; font-size:15px; padding:1px 8px; border-radius:12px; margin-left:10px;}
    .promotion-name { color:#27ae60; font-size:13px; font-style:italic; margin-top:5px; }
    .option-group { margin-bottom:20px; }
    .option-group label { display:block; font-weight:500; margin-bottom:6px; }
    .size-row { display:flex; align-items:center; gap:10px; }
    .size-guide-btn { background:#27ae60; color:white; border:none; padding:6px 12px; border-radius:4px; cursor:pointer; font-size:12px; }
    .size-guide-btn:hover { background:#229954; }
    select, input[type="number"] { padding:6px; border:1px solid #ddd; border-radius:4px; width:100%; max-width:200px; }
    .action-buttons { display:flex; gap:10px; margin-top:20px; }
    .action-buttons .btn { flex:1; padding:12px; font-size:16px; border:none; border-radius:6px; cursor:pointer; color:#fff; }
    .buy-now-button { background:#dc3545; }
    .order-button   { background:#27ae60; }
    .color-options { display:flex; gap:12px; flex-wrap:wrap; }
    .color-option { cursor:pointer; border:2px solid transparent; border-radius:6px; padding:8px; background:#f8f9fa; text-align:center; }
    .color-option.selected { border-color:#dc3545; background:#fff5f5; }
    .color-option img { width:50px; height:50px; object-fit:cover; border-radius:4px; margin-bottom:4px; }
    
    /* Styles cho khối mô tả sản phẩm */
    .product-description {
      margin-top: 30px;
      padding: 20px;
      background: #f8f9fa;
      border-radius: 10px;
      border-left: 4px solid #27ae60;
    }
    .product-description h3 {
      color: #27ae60;
      font-size: 18px;
      margin-bottom: 15px;
      font-weight: 600;
    }
    .product-description p {
      color: #555;
      line-height: 1.6;
      margin-bottom: 10px;
    }
    .product-description .no-description {
      color: #888;
      font-style: italic;
    }
    
    /* Responsive cho mobile */
    @media (max-width: 768px) {
      .product-section { flex-direction: column; gap: 20px; }
      .main-container { padding: 0 15px; }
      .product-description {
        margin-top: 20px;
        padding: 15px;
      }
      .product-description h3 {
        font-size: 16px;
      }
    }
    
    /* Styles cho Size Guide Modal */
    .size-guide-modal {
      display: none;
      position: fixed;
      z-index: 1000;
      left: 0;
      top: 0;
      width: 100%;
      height: 100%;
      background-color: rgba(0,0,0,0.5);
    }
    .size-guide-content {
      background-color: white;
      margin: 5% auto;
      padding: 20px;
      border-radius: 10px;
      width: 90%;
      max-width: 600px;
      max-height: 80%;
      overflow-y: auto;
    }
    .size-guide-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 20px;
      border-bottom: 2px solid #27ae60;
      padding-bottom: 10px;
    }
    .size-guide-title {
      color: #27ae60;
      font-size: 20px;
      font-weight: 600;
      margin: 0;
    }
    .close-modal {
      background: none;
      border: none;
      font-size: 24px;
      cursor: pointer;
      color: #666;
    }
    .close-modal:hover {
      color: #333;
    }
    .size-table {
      width: 100%;
      border-collapse: collapse;
      margin-bottom: 15px;
    }
    .size-table th,
    .size-table td {
      border: 1px solid #ddd;
      padding: 10px;
      text-align: center;
    }
    .size-table th {
      background-color: #27ae60;
      color: white;
      font-weight: 600;
    }
    .size-table tr:nth-child(even) {
      background-color: #f8f9fa;
    }
    .size-note {
      color: #666;
      font-style: italic;
      margin-top: 15px;
      padding: 10px;
      background-color: #f0f8ff;
      border-left: 4px solid #27ae60;
      border-radius: 4px;
    }
  </style>
</head>
<body>
  <?php include __DIR__ . '/upload/header.php'; ?>

  <div class="main-container">
    <div class="breadcrumb">
      <a href="/web_3/index.php">Trang chủ</a> /
      <a href="/web_3/view/<?= $sp['MADM']=='DM001'?'menshirt':($sp['MADM']=='DM002'?'menpants':'accessory') ?>.php">
        <?= $sp['MADM']=='DM001'?'Áo Nam':($sp['MADM']=='DM002'?'Quần Nam':'Phụ kiện') ?>
      </a> /
      <span><?= htmlspecialchars($sp['TENSP']) ?></span>
    </div>

    <div class="product-section">
      <!-- Ảnh -->
      <div class="product-images">
        <div class="main-image">
          <img id="product-image"
               src="/web_3/view/uploads/<?= htmlspecialchars($sp['HINHANH']) ?>"
               alt="<?= htmlspecialchars($sp['TENSP']) ?>">
        </div>
        <?php if (!empty($detailImgs)): ?>
        <!-- Thumbnail ảnh chi tiết: chỉ xem trước, không thay đổi lựa chọn màu -->
        <div class="thumbs-detail-container" id="detailThumbs">
          <?php foreach ($detailImgs as $f): ?>
            <img class="thumb" src="/web_3/view/uploads/<?= htmlspecialchars($f) ?>" data-img="<?= htmlspecialchars($f) ?>" alt="Chi tiết">
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
        <?php if (empty($detailImgs)): ?>
          <!-- Thumbnail theo màu (hover để xem, click để chọn màu) -->
          <div class="thumbs-container" id="thumbs">
            <?php foreach ($list_colors as $c): ?>
              <img
                class="thumb<?= $c['MASP']==$sp['MASP'] ? ' selected' : '' ?>"
                src="/web_3/view/uploads/<?= htmlspecialchars($c['HINHANH']) ?>"
                alt="<?= htmlspecialchars($c['MAUSAC']) ?>"
                title="<?= htmlspecialchars($c['MAUSAC']) ?>"
                data-masp="<?= htmlspecialchars($c['MASP']) ?>"
                data-img="<?= htmlspecialchars($c['HINHANH']) ?>"
                data-color="<?= htmlspecialchars($c['MAUSAC']) ?>"
              />
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>

      <!-- Thông tin & form -->
      <div class="product-info">
        <h1><?= htmlspecialchars($sp['TENSP']) ?></h1>
        <div class="current-price" id="price-display"><?= number_format($sp['GIA']) ?> ₫</div>
        <div id="promotion-display" class="promotion-name" style="display:none;"></div>
        
        <form id="product-form" method="POST" action="add_to_cart.php">
          <input type="hidden" name="masp"      id="field-masp"    value="<?= htmlspecialchars($sp['MASP']) ?>">
          <input type="hidden" name="tensp"     value="<?= htmlspecialchars($sp['TENSP']) ?>">
          <input type="hidden" name="gia_goc"   id="field-gia-goc" value="<?= (int)$sp['GIA'] ?>">
          <input type="hidden" name="gia_ban"   id="field-gia-ban" value="<?= (int)$sp['GIA'] ?>">
          <input type="hidden" name="hinhanh"   id="field-hinhanh" value="<?= htmlspecialchars($sp['HINHANH']) ?>">
          <input type="hidden" name="mausac"    id="field-mausac"  value="<?= htmlspecialchars($sp['MAUSAC']) ?>">
<input type="hidden" name="promotion_name" id="field-promotion-name" value="">
          <input type="hidden" name="has_promotion" id="field-has-promotion" value="0">

          <!-- Chọn màu -->
          <div class="option-group">
            <label>Màu sắc:</label>
            <div class="color-options" id="color-picker">
              <?php foreach ($list_colors as $c): ?>
                <div class="color-option<?= $c['MASP']==$sp['MASP']?' selected':'' ?>"
                     data-masp="<?= htmlspecialchars($c['MASP']) ?>"
                     data-hinhanh="<?= htmlspecialchars($c['HINHANH']) ?>"
                     data-mausac="<?= htmlspecialchars($c['MAUSAC']) ?>"
                     data-gia="<?= (int)$c['GIA'] ?>">
                  <img src="/web_3/view/uploads/<?= htmlspecialchars($c['HINHANH']) ?>"
                       alt="<?= htmlspecialchars($c['MAUSAC']) ?>">
                  <div><?= htmlspecialchars($c['MAUSAC']) ?></div>
                </div>
              <?php endforeach; ?>
            </div>
          </div>

          <!-- Chọn size (nếu có) -->
          <?php if (!empty($sizes)): ?>
          <div class="option-group">
            <label for="size-select">Kích thước:</label>
            <div class="size-row">
              <select name="kichthuoc" id="size-select" required>
                <option value="" disabled selected>-- Chọn size --</option>
                <?php foreach ($sizes as $sz):
                  $qty = isset($full_stock[$sp['MAUSAC']][$sz]) ? (int)$full_stock[$sp['MAUSAC']][$sz] : 0; ?>
                  <option value="<?= htmlspecialchars($sz) ?>" <?= $qty <= 0 ? 'disabled' : '' ?>>
                    <?= htmlspecialchars($sz) ?><?= $qty <= 0 ? ' (Hết hàng)' : '' ?>
                  </option>
                <?php endforeach; ?>
              </select>
              <button type="button" class="size-guide-btn" onclick="showSizeGuide('<?= $sp['MADM'] ?>')">
                <i class="fas fa-ruler"></i> Hướng dẫn size
              </button>
              <a href="/web_3/view/size.php" target="_blank" style="color: #27ae60; font-size: 12px; text-decoration: none; margin-left: 5px;" title="Xem trang hướng dẫn chi tiết">
                <i class="fas fa-external-link-alt"></i>
              </a>
            </div>
          </div>
          <?php else: ?>
          <!-- Phụ kiện - Freesize ẩn -->
          <input type="hidden" name="kichthuoc" value="Freesize">
          <?php endif; ?>

          <!-- Số lượng -->
          <div class="option-group">
            <label for="qty">Số lượng:</label>
            <input type="number" name="soluong" id="qty" value="1" min="1" required>
            <small id="stock-info" style="color:#666;"></small>
          </div>

          <!-- Nút -->
          <div class="action-buttons">
            <button type="submit" name="buy_now" class="btn buy-now-button" disabled>Mua ngay</button>
            <button type="submit" name="add_cart" class="btn order-button" disabled>
              <i class="fas fa-shopping-cart"></i> Thêm vào giỏ hàng
            </button>
          </div>
        </form>

        <!-- Khối mô tả sản phẩm -->
        <div class="product-description">
          <h3> Mô tả sản phẩm</h3>
          <?php if (!empty($sp['MOTA']) && trim($sp['MOTA'])): ?>
            <p><?= nl2br(htmlspecialchars($sp['MOTA'])) ?></p>
          <?php else: ?>
            <p class="no-description">Chưa có mô tả chi tiết cho sản phẩm này.</p>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

  <!-- Size Guide Modal -->
  <div id="sizeGuideModal" class="size-guide-modal">
    <div class="size-guide-content">
      <div class="size-guide-header">
        <h3 class="size-guide-title" id="sizeGuideTitle">Hướng dẫn chọn size</h3>
        <button class="close-modal" onclick="closeSizeGuide()">&times;</button>
      </div>
      
      <div id="sizeGuideBody">
        <!-- Size guide content will be populated by JavaScript -->
      </div>
      
      <div class="size-note">
        <strong>Lưu ý:</strong> Thông số chỉ mang tính tham khảo, bạn nên thử sản phẩm để chọn size phù hợp nhất.
        <br><br>
        <a href="/web_3/view/size.php" target="_blank" style="color: #27ae60; text-decoration: underline; font-weight: 600;">
          <i class="fas fa-external-link-alt"></i> Xem hướng dẫn chi tiết về MENSTA
        </a>
      </div>
    </div>
  </div>

  <?php include __DIR__ . '/upload/footer.php'; ?>

  <script>
    const fullStock    = <?= json_encode($full_stock) ?>;
    const variationMasp = <?= json_encode($variation_masp) ?>;
    const allSizes     = <?= json_encode($sizes) ?>;
    const saleMap      = <?= json_encode($saleMap) ?>;
    const giaMap       = <?= json_encode(array_column($all, 'GIA', 'MASP')) ?>;
    let currentColor   = '<?= htmlspecialchars($sp['MAUSAC']) ?>';
let stockByColor   = fullStock[currentColor] || {};
    let currentMaspMap = variationMasp[currentColor] || {};
    const imgEl        = document.getElementById('product-image');
    const fieldMasp    = document.getElementById('field-masp');
    const fieldImg     = document.getElementById('field-hinhanh');
    const fieldColor   = document.getElementById('field-mausac');
    const fieldGiaGoc  = document.getElementById('field-gia-goc');
    const fieldGiaBan  = document.getElementById('field-gia-ban');
    const fieldPromotionName = document.getElementById('field-promotion-name');
    const fieldHasPromotion = document.getElementById('field-has-promotion');
    const sizeSelect   = document.getElementById('size-select');
    const qtyInput     = document.getElementById('qty');
    const buyBtn       = document.querySelector('.buy-now-button');
    const cartBtn      = document.querySelector('.order-button');
    const stockInfo    = document.getElementById('stock-info');
    const priceDisplay = document.getElementById('price-display');
    const promotionDisplay = document.getElementById('promotion-display');
  const thumbsContainer = document.getElementById('thumbs');
  const detailThumbs = document.getElementById('detailThumbs');

    // Size guide functionality
    function showSizeGuide(category) {
      const modal = document.getElementById('sizeGuideModal');
      const title = document.getElementById('sizeGuideTitle');
      const body = document.getElementById('sizeGuideBody');
      
      if (category === 'DM001') { // Áo
        title.textContent = 'Bảng size Áo Nam';
        body.innerHTML = `
          <table class="size-table">
            <tr>
              <th>SIZE</th>
              <th>S (45-60kg)</th>
              <th>M (60-75kg)</th>
              <th>L (75-90kg)</th>
              <th>XL (90-105kg)</th>
            </tr>
            <tr>
              <td><strong>VAI</strong></td>
              <td>50cm</td>
              <td>52cm</td>
              <td>54cm</td>
              <td>56cm</td>
            </tr>
            <tr>
              <td><strong>TAY</strong></td>
              <td>22cm</td>
              <td>23cm</td>
              <td>24cm</td>
              <td>25cm</td>
            </tr>
            <tr>
              <td><strong>DÀI</strong></td>
              <td>69cm</td>
              <td>71cm</td>
              <td>73cm</td>
              <td>75cm</td>
            </tr>
            <tr>
              <td><strong>RỘNG</strong></td>
              <td>57cm</td>
              <td>59cm</td>
              <td>61cm</td>
              <td>63cm</td>
            </tr>
          </table>
        `;
      } else if (category === 'DM002') { // Quần
        title.textContent = 'Bảng size Quần Nam';
        body.innerHTML = `
          <table class="size-table">
            <tr>
              <th>SIZE</th>
              <th>CHIỀU CAO</th>
              <th>CÂN NẶNG</th>
            </tr>
            <tr>
              <td><strong>29</strong></td>
              <td>Dưới 1m65</td>
              <td>55kg - 60kg</td>
            </tr>
            <tr>
              <td><strong>30</strong></td>
              <td>1m65 - 1m72</td>
              <td>60kg - 65kg</td>
            </tr>
            <tr>
              <td><strong>31</strong></td>
              <td>1m65 - 1m80</td>
              <td>66kg - 71kg</td>
            </tr>
            <tr>
              <td><strong>32</strong></td>
              <td>1m70 - 1m85</td>
              <td>72kg - 76kg</td>
            </tr>
            <tr>
              <td><strong>34</strong></td>
              <td>1m70 - 1m85</td>
              <td>76kg - 82kg</td>
            </tr>
          </table>
        `;
      }
      
      modal.style.display = 'block';
      document.body.style.overflow = 'hidden'; // Prevent background scrolling
    }

    function closeSizeGuide() {
      const modal = document.getElementById('sizeGuideModal');
      modal.style.display = 'none';
      document.body.style.overflow = 'auto'; // Restore scrolling
    }

    // Close modal when clicking outside
    window.onclick = function(event) {
      const modal = document.getElementById('sizeGuideModal');
      if (event.target === modal) {
        closeSizeGuide();
      }
    }

    // Close modal with Escape key
    document.addEventListener('keydown', function(event) {
      if (event.key === 'Escape') {
        const modal = document.getElementById('sizeGuideModal');
        if (modal.style.display === 'block') {
          closeSizeGuide();
        }
      }
    });

    function isSaleActive(sale) {
        return sale && sale.promotion_active == 1;
    }

    function updatePrice(currentMasp) {
        let gia = giaMap[currentMasp];
        let sale = saleMap[currentMasp];
        
        fieldGiaGoc.value = gia;
        
        if (isSaleActive(sale)) {
            let salePrice = 0;
            let discountPercent = 0;
            
            if (sale.gia_khuyenmai && sale.gia_khuyenmai > 0) {
                salePrice = parseInt(sale.gia_khuyenmai);
                discountPercent = Math.round(((gia - salePrice) / gia) * 100);
            } else if (sale.giam_phantram && sale.giam_phantram > 0) {
                discountPercent = parseFloat(sale.giam_phantram);
                salePrice = gia - Math.round(gia * (discountPercent / 100));
            }
            
            if (salePrice > 0) {
                priceDisplay.innerHTML = `<span class="old-price">${Number(gia).toLocaleString()} ₫</span>
                    <span class="sale-price">${Number(salePrice).toLocaleString()} ₫</span>
                    <span class="discount">-${discountPercent}%</span>`;
                fieldGiaBan.value = salePrice;
                fieldPromotionName.value = sale.TENCTKM || 'Khuyến mãi';
                fieldHasPromotion.value = '1';
                promotionDisplay.textContent = `🏷️ ${sale.TENCTKM || 'Khuyến mãi'}`;
                promotionDisplay.style.display = 'block';
            } else {
                // Sale không hợp lệ
                priceDisplay.innerHTML = `${Number(gia).toLocaleString()} ₫`;
                fieldGiaBan.value = gia;
                fieldPromotionName.value = '';
                fieldHasPromotion.value = '0';
                promotionDisplay.style.display = 'none';
            }
        } else {
priceDisplay.innerHTML = `${Number(gia).toLocaleString()} ₫`;
            fieldGiaBan.value = gia;
            fieldPromotionName.value = '';
            fieldHasPromotion.value = '0';
            promotionDisplay.style.display = 'none';
        }
    }

    function refresh() {
      // Reset form state
      qtyInput.value = 1;
      qtyInput.disabled = true;
      buyBtn.disabled = true;
      cartBtn.disabled = true;
      
      // Kiểm tra xem là phụ kiện hay không
      if (!sizeSelect) { // Đây là phụ kiện
        const max = stockByColor['Freesize'] || 0;
        const accessoryMasp = currentMaspMap['Freesize'];
        
        if (max <= 0 || !accessoryMasp) {
          stockInfo.textContent = 'Sản phẩm đã hết hàng';
          qtyInput.value = 0;
          qtyInput.disabled = true;
          buyBtn.disabled = true;
          cartBtn.disabled = true;
        } else {
          // Cập nhật MASP cho phụ kiện
          fieldMasp.value = accessoryMasp;
          stockInfo.textContent = `Còn ${max} sản phẩm`;
          qtyInput.disabled = false;
          qtyInput.max = max;
          qtyInput.min = 1;
          qtyInput.value = 1;
          buyBtn.disabled = false;
          cartBtn.disabled = false;
        }
      } else if (sizeSelect.value) { // Sản phẩm có size và đã chọn size
        const sz = sizeSelect.value;
        const max = stockByColor[sz] || 0;
        const variationMasp = currentMaspMap[sz] || null;
        
        if (max <= 0 || !variationMasp) {
          stockInfo.textContent = 'Hết hàng';
          qtyInput.value = 0;
        } else {
          // Còn hàng
          stockInfo.textContent = `Còn ${max} sản phẩm`;
          qtyInput.disabled = false;
          qtyInput.max = max;
          qtyInput.min = 1;
          qtyInput.value = 1;
          buyBtn.disabled = false;
          cartBtn.disabled = false;
          // Cập nhật MASP theo variation
          fieldMasp.value = variationMasp;
        }
      } else {
        // Chưa chọn size
        stockInfo.textContent = 'Vui lòng chọn size';
      }
    }

    document.querySelectorAll('#color-picker .color-option').forEach(el=>{
      el.addEventListener('click',()=>{
        document.querySelectorAll('#color-picker .color-option')
                .forEach(i=>i.classList.remove('selected'));
        el.classList.add('selected');

  // Sync selected state on thumbnails
  document.querySelectorAll('#thumbs .thumb').forEach(t => t.classList.remove('selected'));
  const selectedThumb = document.querySelector(`#thumbs .thumb[data-masp="${el.dataset.masp}"]`);
  if (selectedThumb) selectedThumb.classList.add('selected');

        imgEl.src       = '/web_3/view/uploads/'+el.dataset.hinhanh;
        fieldImg.value  = el.dataset.hinhanh;
        fieldColor.value= el.dataset.mausac;
        currentColor    = el.dataset.mausac;
        stockByColor    = fullStock[currentColor] || {};
        currentMaspMap  = variationMasp[currentColor] || {};

        if (sizeSelect) {
          sizeSelect.innerHTML = '<option value="">-- Chọn size --</option>';
          let hasAvailableSize = false;
          
          allSizes.forEach(sz => {
            const q = stockByColor[sz] || 0;
const masp = currentMaspMap[sz];
            const opt = document.createElement('option');
            opt.value = sz;
            opt.disabled = q <= 0 || !masp;
            opt.setAttribute('data-stock', q);
            opt.text = sz + (q <= 0 ? ' (Hết hàng)' : ` (Còn ${q})`);
            sizeSelect.appendChild(opt);
            
            if (q > 0 && masp) hasAvailableSize = true;
          });
          
          // Nếu không có size nào còn hàng
          if (!hasAvailableSize) {
            stockInfo.textContent = 'Tất cả các size đều hết hàng';
            qtyInput.disabled = true;
            buyBtn.disabled = true;
            cartBtn.disabled = true;
          }
          
          sizeSelect.value = '';
        } else {
          // Đây là phụ kiện, cập nhật MASP ngay
          const freesizeMasp = currentMaspMap['Freesize'];
          if (freesizeMasp) {
            fieldMasp.value = freesizeMasp;
          }
        }
        
        refresh();
        if (saleMap) {
          updatePrice(fieldMasp.value);
        }
      });
    });

    // Thumbnail hover to preview, click to select color
    if (thumbsContainer) {
      const revertToSelected = () => {
        // Revert preview to currently selected image when leaving the thumbnail area
        if (fieldImg && fieldImg.value) {
          imgEl.src = '/web_3/view/uploads/' + fieldImg.value;
        }
      };
      thumbsContainer.addEventListener('mouseleave', revertToSelected);
      thumbsContainer.querySelectorAll('.thumb').forEach(thumb => {
        thumb.addEventListener('mouseenter', () => {
          const img = thumb.dataset.img;
          if (img) imgEl.src = '/web_3/view/uploads/' + img;
        });
        thumb.addEventListener('click', () => {
          const masp = thumb.dataset.masp;
          const target = document.querySelector(`#color-picker .color-option[data-masp="${masp}"]`);
          if (target) target.click();
        });
      });
    }

    // Thumbnail ảnh chi tiết: hover để xem trước, click để cố định ảnh chính, không đổi màu/biến thể
    if (detailThumbs) {
      const revertToSelectedMain = () => {
        if (fieldImg && fieldImg.value) {
          imgEl.src = '/web_3/view/uploads/' + fieldImg.value;
        }
      };
      detailThumbs.addEventListener('mouseleave', revertToSelectedMain);
      detailThumbs.querySelectorAll('.thumb').forEach(t => {
        t.addEventListener('mouseenter', () => {
          const img = t.dataset.img;
          if (img) imgEl.src = '/web_3/view/uploads/' + img;
        });
        t.addEventListener('click', () => {
          const img = t.dataset.img;
          if (img) {
            imgEl.src = '/web_3/view/uploads/' + img;
            // Cập nhật fieldImg để khi rời chuột không revert lại
            fieldImg.value = img;
          }
        });
      });
    }

    if (sizeSelect) {
      sizeSelect.addEventListener('change', refresh);
      qtyInput.addEventListener('input',()=> {
        if (+qtyInput.value > +qtyInput.max) qtyInput.value = qtyInput.max;
      });
    }

    window.addEventListener('DOMContentLoaded', function() {
      refresh();
      updatePrice(fieldMasp.value);
    });
  </script>

  <?php // Reviews block for this product
    include __DIR__ . '/components/product_reviews.php';
  ?>
</body>
</html>
