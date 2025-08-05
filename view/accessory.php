<?php
// view/accessory.php

session_start();
require_once __DIR__ . '/../model/database.php';
$db   = new database();
$conn = $db->getConnection();

// 1) Lấy param lọc
$currentPk = $_GET['loai_pk'] ?? '';

// 2) Lấy danh sách loại “Phụ Kiện”
$stmt = $conn->prepare("
  SELECT l.MALOAI, l.TENLOAI
  FROM loaisanpham l
  JOIN danhmuc d ON l.MADM = d.MADM
  WHERE d.TENDM = 'PHỤ KIỆN'
  ORDER BY l.TENLOAI
");
$stmt->execute();
$pkTypes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Tìm tên loại hiện tại
$ten_loai = null;
foreach ($pkTypes as $t) {
    if ($t['MALOAI'] === $currentPk) {
        $ten_loai = $t['TENLOAI'];
        break;
    }
}
$page_title = $ten_loai ?: 'PHỤ KIỆN';

// 3) Truy vấn sản phẩm chính (is_main = 1)
if ($currentPk && $ten_loai) {
    $stmt = $conn->prepare("
      SELECT s.*
      FROM sanpham s
      WHERE s.MALOAI = :maloai
        AND s.is_main = 1
      ORDER BY s.ID DESC
    ");
    $stmt->execute([':maloai' => $currentPk]);
} else {
    $stmt = $conn->prepare("
      SELECT s.*
      FROM sanpham s
      JOIN loaisanpham l ON s.MALOAI = l.MALOAI
      JOIN danhmuc d       ON l.MADM   = d.MADM
      WHERE d.TENDM = 'PHỤ KIỆN'
        AND s.is_main = 1
      ORDER BY s.ID DESC
    ");
    $stmt->execute();
}
$mainProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 4) Lọc theo tên
$seen = [];
$products = [];
foreach ($mainProducts as $sp) {
    if (!isset($seen[$sp['TENSP']])) {
        $products[]       = $sp;
        $seen[$sp['TENSP']] = true;
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= htmlspecialchars($page_title) ?> – MENSTA</title>
  <link rel="stylesheet" href="/web_3/view/css/style.css">
  <link rel="stylesheet" href="/web_3/view/css/product.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
  <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet" />
</head>
<body>
  <?php include __DIR__ . '/upload/header.php'; ?>

  <main class="main-content">
    <div class="breadcrumb">
      <a href="/web_3/index.php">Trang chủ</a>
      <span> / </span>
      <a href="/web_3/view/accessory.php">Phụ Kiện</a>
      <?php if ($ten_loai): ?>
        <span> / </span>
        <span><?= htmlspecialchars($ten_loai) ?></span>
      <?php endif; ?>
    </div>

    <h1 class="page-title"><?= htmlspecialchars($page_title) ?></h1>

    <div class="product-grid">
      <?php if (empty($products)): ?>
        <p>Không có sản phẩm nào.</p>
      <?php else: ?>
        <?php foreach ($products as $sp): ?>
          <a href="product_detail.php?masp=<?= urlencode($sp['MASP']) ?>" class="product-card">
            <img src="/web_3/view/uploads/<?= htmlspecialchars($sp['HINHANH']) ?>"
                 alt="<?= htmlspecialchars($sp['TENSP']) ?>">
            <div class="product-title"><?= htmlspecialchars($sp['TENSP']) ?></div>
            <div class="product-color">Màu: <?= htmlspecialchars($sp['MAUSAC']) ?></div>
            <div class="price"><?= number_format($sp['GIA']) ?> ₫</div>
          </a>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </main>

  <?php include __DIR__ . '/upload/footer.php'; ?>
</body>
</html>
