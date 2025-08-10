<?php
// index.php - Thêm CSS footer

require_once __DIR__ . "/model/database.php";
$db   = new database();
$conn = $db->getConnection();

/**
 * Gom trùng sản phẩm khác màu theo TENSP
 */
function uniqueByName(array $items, string $field = 'TENSP'): array {
    $seen = [];
    $out  = [];
    foreach ($items as $it) {
        if (!isset($seen[$it[$field]])) {
            $seen[$it[$field]] = true;
            $out[] = $it;
        }
    }
    return $out;
}

// Query sản phẩm HOT (có thể có sale)
$hotProducts = $conn->query("
    SELECT sp.*, ct.gia_khuyenmai, ct.giam_phantram
    FROM sanpham sp
    LEFT JOIN chitietctkm ct ON sp.MASP = ct.MASP
    LEFT JOIN chuongtrinhkhuyenmai ctkm ON ct.MACTKM = ctkm.MACTKM 
        AND NOW() BETWEEN ctkm.NGAYBATDAU AND ctkm.NGAYKETTHUC
    WHERE sp.hot=1 AND sp.is_main=1 AND sp.IS_DELETED=0
    ORDER BY sp.ID DESC LIMIT 16
")->fetchAll(PDO::FETCH_ASSOC);
$hotProducts = uniqueByName($hotProducts);

// Query sản phẩm MỚI (có thể có sale)
$newProducts = $conn->query("
    SELECT sp.*, ct.gia_khuyenmai, ct.giam_phantram
    FROM sanpham sp
    LEFT JOIN chitietctkm ct ON sp.MASP = ct.MASP
    LEFT JOIN chuongtrinhkhuyenmai ctkm ON ct.MACTKM = ctkm.MACTKM 
        AND NOW() BETWEEN ctkm.NGAYBATDAU AND ctkm.NGAYKETTHUC
    WHERE sp.news=1 AND sp.is_main=1 AND sp.IS_DELETED=0
    ORDER BY sp.ID DESC LIMIT 16
")->fetchAll(PDO::FETCH_ASSOC);
$newProducts = uniqueByName($newProducts);

// Query sản phẩm SALE (chỉ lấy sản phẩm đang nằm trong CTKM hiệu lực)
$saleProducts = $conn->query("
    SELECT sp.*, ct.gia_khuyenmai, ct.giam_phantram
    FROM sanpham sp
    JOIN chitietctkm ct ON sp.MASP = ct.MASP
    JOIN chuongtrinhkhuyenmai ctkm ON ct.MACTKM = ctkm.MACTKM 
        AND NOW() BETWEEN ctkm.NGAYBATDAU AND ctkm.NGAYKETTHUC
    WHERE sp.is_main=1 AND sp.IS_DELETED=0
    ORDER BY sp.ID DESC LIMIT 16
")->fetchAll(PDO::FETCH_ASSOC);
$saleProducts = uniqueByName($saleProducts);

?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Shoppe - Thời trang nam</title>
  <link rel="stylesheet" href="/web_3/view/css/style.css" />
  <link rel="stylesheet" href="/web_3/view/css/product.css" />
  <link rel="stylesheet" href="/web_3/view/css/footer.css" />
  <script src="/web_3/view/js/script.js" defer></script>
  <link rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"
        crossorigin="anonymous" referrerpolicy="no-referrer" />
  <style>
    .old-price { text-decoration: line-through; color: #888; margin-right: 5px;}
    .sale-price { color: #d00; font-weight: bold;}
  </style>
</head>
<body>
  <?php include 'view/upload/header.php'; ?>
  <?php include 'view/upload/carousel.php'; ?>

  <!-- HOT NHẤT -->
  <section class="products-section">
    <h2 class="section-title">THỜI TRANG HOT NHẤT</h2>
    <div class="product-grid">
      <?php foreach ($hotProducts as $sp): ?><div class="product-card" onclick="location.href='/web_3/view/product_detail.php?masp=<?= urlencode($sp['MASP']) ?>'">
          <span class="badge hot">HOT</span>
          <img src="/web_3/view/uploads/<?= htmlspecialchars($sp['HINHANH']) ?>" alt="<?= htmlspecialchars($sp['TENSP']) ?>" />
          <div class="product-title"><?= htmlspecialchars($sp['TENSP']) ?></div>
          <div class="price">
            <?php if ($sp['gia_khuyenmai'] || $sp['giam_phantram']): ?>
              <span class="old-price"><?= number_format($sp['GIA']) ?> ₫</span>
              <span class="sale-price">
                <?php
                  if ($sp['gia_khuyenmai']) {
                    echo number_format($sp['gia_khuyenmai']) . " ₫";
                  } else {
                    echo number_format($sp['GIA'] * (1 - $sp['giam_phantram']/100)) . " ₫";
                  }
                ?>
              </span>
            <?php else: ?>
              <?= number_format($sp['GIA']) ?> ₫
            <?php endif; ?>
          </div>
          <div class="product-meta">Màu: <?= htmlspecialchars($sp['MAUSAC']) ?></div>
        </div>
      <?php endforeach; ?>
    </div>
  </section>

  <!-- MỚI NHẤT -->
  <section class="products-section">
    <h2 class="section-title">THỜI TRANG MỚI NHẤT</h2>
    <div class="product-grid">
      <?php foreach ($newProducts as $sp): ?>
        <div class="product-card" onclick="location.href='/web_3/view/product_detail.php?masp=<?= urlencode($sp['MASP']) ?>'">
          <span class="badge new">NEW</span>
          <img src="/web_3/view/uploads/<?= htmlspecialchars($sp['HINHANH']) ?>" alt="<?= htmlspecialchars($sp['TENSP']) ?>" />
          <div class="product-title"><?= htmlspecialchars($sp['TENSP']) ?></div>
          <div class="price">
            <?php if ($sp['gia_khuyenmai'] || $sp['giam_phantram']): ?>
              <span class="old-price"><?= number_format($sp['GIA']) ?> ₫</span>
              <span class="sale-price">
                <?php
                  if ($sp['gia_khuyenmai']) {
                    echo number_format($sp['gia_khuyenmai']) . " ₫";
                  } else {
                    echo number_format($sp['GIA'] * (1 - $sp['giam_phantram']/100)) . " ₫";
                  }
                ?>
              </span>
            <?php else: ?>
              <?= number_format($sp['GIA']) ?> ₫
            <?php endif; ?>
          </div>
          <div class="product-meta">Màu: <?= htmlspecialchars($sp['MAUSAC']) ?></div>
        </div>
      <?php endforeach; ?>
    </div>
  </section>

  <!-- OUTSALE -->
  <section class="products-section">
    <h2 class="section-title">OUTSALE</h2>
    <div class="product-grid">
      <?php foreach ($saleProducts as $sp): ?>
        <div class="product-card" onclick="location.href='/web_3/view/product_detail.php?masp=<?= urlencode($sp['MASP']) ?>'">
          <span class="badge sale">SALE</span>
<img src="/web_3/view/uploads/<?= htmlspecialchars($sp['HINHANH']) ?>" alt="<?= htmlspecialchars($sp['TENSP']) ?>" />
          <div class="product-title"><?= htmlspecialchars($sp['TENSP']) ?></div>
          <div class="price">
            <span class="old-price"><?= number_format($sp['GIA']) ?> ₫</span>
            <span class="sale-price">
              <?php
                if ($sp['gia_khuyenmai']) {
                  echo number_format($sp['gia_khuyenmai']) . " ₫";
                } else {
                  echo number_format($sp['GIA'] * (1 - $sp['giam_phantram']/100)) . " ₫";
                }
              ?>
            </span>
          </div>
          <div class="product-meta">Màu: <?= htmlspecialchars($sp['MAUSAC']) ?></div>
        </div>
      <?php endforeach; ?>
    </div>
  </section>
      
  <?php include 'view/upload/footer.php'; ?>
</body>
</html>