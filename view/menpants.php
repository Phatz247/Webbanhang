<?php
// view/menpants.php

session_start();
require_once __DIR__ . '/../model/database.php';
$db   = new database();
$conn = $db->getConnection();

// 1) Lấy param lọc
$currentQuan = $_GET['loai_quan'] ?? '';

// 2) Lấy danh sách loại “Quần Nam”
$stmt = $conn->prepare("
  SELECT l.MALOAI, l.TENLOAI
  FROM loaisanpham l
  JOIN danhmuc d ON l.MADM = d.MADM
  WHERE d.TENDM = 'QUẦN NAM'
  ORDER BY l.TENLOAI
");
$stmt->execute();
$quanTypes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Tìm tên loại hiện tại để đặt tiêu đề
$ten_loai = null;
foreach ($quanTypes as $t) {
    if ($t['MALOAI'] === $currentQuan) {
        $ten_loai = $t['TENLOAI'];
        break;
    }
}
$page_title = $ten_loai ?: 'QUẦN NAM';

// 3) Truy vấn sản phẩm chính (is_main = 1) với thông tin khuyến mãi
if ($currentQuan && $ten_loai) {
    $stmt = $conn->prepare("
      SELECT s.*, gs.gia_khuyenmai, gs.giam_phantram
      FROM sanpham s
      LEFT JOIN (
        SELECT sv.GROUPSP,
               MAX(CASE WHEN NOW() BETWEEN ctkm.NGAYBATDAU AND ctkm.NGAYKETTHUC AND ct.gia_khuyenmai > 0 THEN ct.gia_khuyenmai ELSE 0 END) AS gia_khuyenmai,
               MAX(CASE WHEN NOW() BETWEEN ctkm.NGAYBATDAU AND ctkm.NGAYKETTHUC AND ct.giam_phantram > 0 THEN ct.giam_phantram ELSE 0 END) AS giam_phantram
        FROM sanpham sv
        JOIN chitietctkm ct ON ct.MASP = sv.MASP
        JOIN chuongtrinhkhuyenmai ctkm ON ct.MACTKM = ctkm.MACTKM
        WHERE sv.IS_DELETED = 0
        GROUP BY sv.GROUPSP
      ) gs ON gs.GROUPSP = s.GROUPSP
      WHERE s.MALOAI = :maloai
        AND s.is_main = 1
        AND s.IS_DELETED = 0
      ORDER BY s.ID DESC
    ");
    $stmt->execute([':maloai' => $currentQuan]);
} else {
    $stmt = $conn->prepare("
      SELECT s.*, gs.gia_khuyenmai, gs.giam_phantram
      FROM sanpham s
      JOIN loaisanpham l ON s.MALOAI = l.MALOAI
      JOIN danhmuc d       ON l.MADM   = d.MADM
      LEFT JOIN (
        SELECT sv.GROUPSP,
               MAX(CASE WHEN NOW() BETWEEN ctkm.NGAYBATDAU AND ctkm.NGAYKETTHUC AND ct.gia_khuyenmai > 0 THEN ct.gia_khuyenmai ELSE 0 END) AS gia_khuyenmai,
               MAX(CASE WHEN NOW() BETWEEN ctkm.NGAYBATDAU AND ctkm.NGAYKETTHUC AND ct.giam_phantram > 0 THEN ct.giam_phantram ELSE 0 END) AS giam_phantram
        FROM sanpham sv
        JOIN chitietctkm ct ON ct.MASP = sv.MASP
        JOIN chuongtrinhkhuyenmai ctkm ON ct.MACTKM = ctkm.MACTKM
        WHERE sv.IS_DELETED = 0
        GROUP BY sv.GROUPSP
      ) gs ON gs.GROUPSP = s.GROUPSP
      WHERE d.TENDM = 'QUẦN NAM'
        AND s.is_main = 1
        AND s.IS_DELETED = 0
      ORDER BY s.ID DESC
    ");
    $stmt->execute();
}
$mainProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 4) Lọc theo tên để không lặp
$seen = [];
$products = [];
foreach ($mainProducts as $sp) {
    if (!isset($seen[$sp['TENSP']])) {
        $products[]         = $sp;
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
  <style>
    .old-price { text-decoration: line-through; color: #888; margin-right: 5px; }
    .sale-price { color: #d00; font-weight: bold; }
  </style>
</head>
<body>
  <?php include __DIR__ . '/upload/header.php'; ?>

  <main class="main-content">
    <div class="breadcrumb">
      <a href="/web_3/index.php">Trang chủ</a>
      <span> / </span>
      <a href="/web_3/view/menpants.php">Quần Nam</a>
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
          <div class="product-card" onclick="location.href='/web_3/view/product_detail.php?masp=<?= urlencode($sp['MASP']) ?>'">
            <?php if ($sp['gia_khuyenmai'] || $sp['giam_phantram']): ?>
              <span class="badge sale">SALE</span>
            <?php endif; ?>
            <img src="/web_3/view/uploads/<?= htmlspecialchars($sp['HINHANH']) ?>" alt="<?= htmlspecialchars($sp['TENSP']) ?>" />
            <div class="product-title"><?= htmlspecialchars($sp['TENSP']) ?></div>
            <div class="product-meta">Màu: <?= htmlspecialchars($sp['MAUSAC']) ?></div>
            <div class="price">
              <?php if ($sp['gia_khuyenmai'] || $sp['giam_phantram']): ?>
                <span class="old-price"><?= number_format($sp['GIA']) ?> ₫</span>
                <span class="sale-price"><?php
                  echo $sp['gia_khuyenmai']
                    ? number_format($sp['gia_khuyenmai']) . ' ₫'
                    : number_format($sp['GIA'] * (1 - $sp['giam_phantram']/100)) . ' ₫';
                ?></span>
              <?php else: ?>
                <?= number_format($sp['GIA']) ?> ₫
              <?php endif; ?>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </main>

  <?php include __DIR__ . '/upload/footer.php'; ?>
</body>
</html>
