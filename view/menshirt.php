<?php
// view/menshirt.php

session_start();
require_once __DIR__ . '/../model/database.php';
$db   = new database();
$conn = $db->getConnection();

// 1) Lấy param lọc
$currentAo = $_GET['loai_ao'] ?? '';

// 2) Lấy danh sách loại “Áo Nam” để tìm tên loại và tạo tiêu đề trang
$stmt = $conn->prepare("
  SELECT l.MALOAI, l.TENLOAI
  FROM loaisanpham l
  JOIN danhmuc d ON l.MADM = d.MADM
  WHERE d.TENDM = 'ÁO NAM'
  ORDER BY l.TENLOAI
");
$stmt->execute();
$aoTypes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Tìm tên loại hiện tại (nếu có)
$ten_loai = null;
foreach ($aoTypes as $t) {
    if ($t['MALOAI'] === $currentAo) {
        $ten_loai = $t['TENLOAI'];
        break;
    }
}
$page_title = $ten_loai ?: 'ÁO NAM';

// 3) Truy vấn sản phẩm chính (is_main = 1), hoặc theo loại nếu có loai_ao
if ($currentAo && $ten_loai) {
    $stmt = $conn->prepare("
      SELECT s.*
      FROM sanpham s
      WHERE s.MALOAI = :maloai
        AND s.is_main = 1
      ORDER BY s.ID DESC
    ");
    $stmt->execute([':maloai' => $currentAo]);
} else {
    $stmt = $conn->prepare("
      SELECT s.*
      FROM sanpham s
      JOIN loaisanpham l ON s.MALOAI = l.MALOAI
      JOIN danhmuc d       ON l.MADM   = d.MADM
      WHERE d.TENDM = 'ÁO NAM'
        AND s.is_main = 1
      ORDER BY s.ID DESC
    ");
    $stmt->execute();
}
$mainProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 4) Lọc theo tên để chỉ show mỗi sản phẩm 1 lần
$seen = [];
$products = [];
foreach ($mainProducts as $sp) {
    if (!isset($seen[$sp['TENSP']])) {
        $products[]      = $sp;
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
  <!-- header.php sẽ chứa logo, search, dropdown Áo Nam/Quần Nam/Phụ Kiện động -->
  <?php include __DIR__ . '/upload/header.php'; ?>

  <main class="main-content">
    <!-- Breadcrumb động -->
    <div class="breadcrumb">
      <a href="/web_3/index.php">Trang chủ</a>
      <span> / </span>
      <a href="/web_3/view/menshirt.php">Áo Nam</a>
      <?php if ($ten_loai): ?>
        <span> / </span>
        <span><?= htmlspecialchars($ten_loai) ?></span>
      <?php endif; ?>
    </div>

    <!-- Tiêu đề trang -->
    <h1 class="page-title"><?= htmlspecialchars($page_title) ?></h1>

    <!-- Product grid: không còn phần filter-buttons -->
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
