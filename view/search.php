<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once '../model/database.php';
$keyword = trim($_GET['keyword'] ?? '');
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <title>Kết quả tìm kiếm</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="/web_3/view/css/header.css?v=20240729">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
  <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet"/>
  <style>
    body { background: #f6f7fa; }
    .product-search-main {
      min-height: 70vh;
      padding: 45px 0 60px 0;
      max-width: 1350px;
      margin: 0 auto;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: flex-start;
    }
    .product-list {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 32px 26px;
      width: 100%;
      justify-content: center;
    }
    @media (max-width: 1200px) {
      .product-list { grid-template-columns: repeat(3, 1fr);}
    }
    @media (max-width: 900px) {
      .product-list { grid-template-columns: repeat(2, 1fr);}
    }
    @media (max-width: 600px) {
      .product-list { grid-template-columns: 1fr;}
    }
    .product-card {
      background: #fff;
      border-radius: 16px;
      box-shadow: 0 3px 16px rgba(0,0,0,0.10);
      overflow: hidden;
      border: 1px solid #f2f2f2;
      display: flex;
      flex-direction: column;
      transition: box-shadow .15s, transform .13s;
      min-width: 220px;
      max-width: 260px;
      margin: 0 auto;
    }
    .product-card:hover {
      box-shadow: 0 7px 28px rgba(0,0,0,0.14);
      transform: translateY(-4px) scale(1.02);
    }
    .product-card img {
      width: 100%;
      height: 220px;
      object-fit: cover;
      background: #f6f6f6;
      border-bottom: 1px solid #eee;
      display: block;
    }
    .card-body {
      flex: 1 1 auto;
      padding: 18px 16px 22px 16px;
      display: flex;
      flex-direction: column;
      justify-content: flex-end;
      text-align: center;
    }
    .card-title {
      font-size: 1rem;
      font-weight: 600;
      min-height: 38px;
      color: #222;
      margin-bottom: 8px;
      line-height: 1.4;
    }
    .product-price {
      color: #e74c3c;
      font-weight: bold;
      font-size: 17px;
      margin-bottom: 10px;
    }
    .btn {
      border-radius: 8px;
      padding: 8px 0;
    }
    .not-found-message {
      font-size: 18px;
      color: #888;
      text-align: center;
      margin: 70px 0 100px 0;
      width: 100%;
    }
  </style>
</head>
<body>
<?php include 'upload/header.php'; ?>

<div class="product-search-main">
  <div class="product-list">
    <?php
    if ($keyword) {
        $db = new Database();
$conn = $db->getConnection();
        $stmt = $conn->prepare("SELECT * FROM sanpham WHERE TENSP LIKE ?");
        $stmt->execute(['%' . $keyword . '%']);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if ($results) {
            foreach ($results as $sp): ?>
                <div class="product-card">
                  <a href="/web_3/view/product_detail.php?masp=<?= $sp['MASP'] ?>">
                    <img src="/web_3/view/img/<?= htmlspecialchars($sp['HINHANH']) ?>" alt="<?= htmlspecialchars($sp['TENSP']) ?>">
                  </a>
                  <div class="card-body">
                    <div class="card-title"><?= htmlspecialchars($sp['TENSP']) ?></div>
                    <div class="product-price"><?= number_format($sp['GIA']) ?>₫</div>
                    <a href="/web_3/view/product_detail.php?masp=<?= $sp['MASP'] ?>" class="btn btn-dark btn-sm w-100">Xem chi tiết</a>
                  </div>
                </div>
            <?php endforeach;
        } else {
            echo "<div class='not-found-message'><i>Không tìm thấy sản phẩm phù hợp.</i></div>";
        }
    } else {
        echo "<div class='not-found-message'><i>Vui lòng nhập từ khóa để tìm kiếm sản phẩm.</i></div>";
    }
    ?>
  </div>
</div>

<?php include 'upload/footer.php'; ?>
</body>
</html>
