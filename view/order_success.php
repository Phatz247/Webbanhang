<?php
session_start();
require_once __DIR__ . '/../model/database.php';
$db = new database();
$conn = $db->getConnection();

// Bật hiển thị lỗi để debug
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Kiểm tra đăng nhập
$user = $_SESSION['user'] ?? null;
if (!$user) {
    header('Location: login.php');
    exit;
}

// Lấy mã đơn hàng từ URL
$order_code = $_GET['order'] ?? '';
if (empty($order_code)) {
    header('Location: profile.php');
    exit;
}

// Lấy thông tin đơn hàng
$stmt = $conn->prepare("
    SELECT dh.*, COUNT(ct.MASP) as SO_SANPHAM
    FROM donhang dh
    LEFT JOIN chitietdonhang ct ON dh.MADONHANG = ct.MADONHANG
    WHERE dh.MADONHANG = ? AND dh.MAKH = ?
    GROUP BY dh.MADONHANG
");
$stmt->execute([$order_code, $user['MAKH']]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

// Nếu không tìm thấy đơn hàng hoặc không phải của user hiện tại
if (!$order) {
    header('Location: profile.php');
    exit;
}

// Lấy chi tiết đơn hàng
$stmt = $conn->prepare("
    SELECT ct.*, sp.TENSP, sp.HINHANH
    FROM chitietdonhang ct
    JOIN sanpham sp ON ct.MASP = sp.MASP
    WHERE ct.MADONHANG = ?
");
$stmt->execute([$order_code]);
$order_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đặt hàng thành công - MENSTA</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background: #f8f9fa; margin: 0; padding: 0; }
        .container { max-width: 800px; margin: 40px auto; padding: 20px; }
        .success-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 30px;
            text-align: center;
            margin-bottom: 30px;
        }
        .success-icon {
            color: #27ae60;
            font-size: 48px;
            margin-bottom: 20px;
        }
        .success-title {
            color: #27ae60;
            font-size: 24px;
            margin-bottom: 10px;
        }
        .success-message {
            color: #666;
            margin-bottom: 20px;
        }
        .order-code {
            background: #f1f8ff;
            color: #2980b9;
            padding: 10px 20px;
            border-radius: 6px;
            font-weight: 600;
            display: inline-block;
            margin-bottom: 20px;
        }
        .btn {
            display: inline-block;
            padding: 12px 20px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 500;
            margin: 5px;
        }
        .btn-primary {
            background: #3498db;
            color: white;
}
        .btn-outline {
            border: 2px solid #3498db;
            color: #3498db;
        }
        .order-details {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 25px;
        }
        .order-details h3 {
            margin: 0 0 20px;
            color: #2c3e50;
            font-size: 18px;
        }
        .order-info {
            margin-bottom: 25px;
            padding-bottom: 20px;
            border-bottom: 1px solid #eee;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            font-size: 15px;
        }
        .info-label {
            color: #7f8c8d;
            font-weight: 500;
        }
        .info-value {
            color: #2c3e50;
            font-weight: 600;
        }
        .product-list {
            margin-bottom: 25px;
        }
        .product-item {
            display: flex;
            align-items: center;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
            margin-bottom: 10px;
        }
        .product-image {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 6px;
            margin-right: 15px;
        }
        .product-info {
            flex: 1;
        }
        .product-name {
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 5px;
        }
        .product-meta {
            font-size: 14px;
            color: #7f8c8d;
        }
        .product-price {
            color: #e74c3c;
            font-weight: 600;
            text-align: right;
        }
        .total-section {
            padding-top: 20px;
            border-top: 2px solid #eee;
        }
        .total-row {
            display: flex;
            justify-content: space-between;
            font-size: 18px;
            font-weight: 700;
            color: #e74c3c;
        }
    </style>
</head>
<body>
    <div class="container">
        <?php if (isset($_SESSION['order_success'])): ?>
        <div class="success-card">
            <i class="fas fa-check-circle success-icon"></i>
            <h1 class="success-title">Đặt hàng thành công!</h1>
            <p class="success-message">Cảm ơn bạn đã đặt hàng. Chúng tôi sẽ xử lý đơn hàng của bạn trong thời gian sớm nhất.</p>
            <div class="order-code">
                Mã đơn hàng: <?php echo htmlspecialchars($order_code); ?>
            </div>
            <div>
                <a href="track_order.php?order_code=<?php echo urlencode($order_code); ?>" class="btn btn-primary">
                    <i class="fas fa-search"></i> Tra cứu đơn hàng này
                </a>
                <a href="profile.php" class="btn btn-outline">
                    <i class="fas fa-user"></i> Xem tất cả đơn hàng
</a>
                <a href="/web_3/index.php" class="btn btn-outline">
                    <i class="fas fa-home"></i> Về trang chủ
                </a>
            </div>
        </div>
        <?php unset($_SESSION['order_success']); endif; ?>

        <div class="order-details">
            <h3><i class="fas fa-info-circle"></i> Chi tiết đơn hàng</h3>
            
            <div class="order-info">
                <div class="info-row">
                    <span class="info-label">Người nhận:</span>
                    <span class="info-value"><?php echo htmlspecialchars($order['HOTEN']); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Số điện thoại:</span>
                    <span class="info-value"><?php echo htmlspecialchars($order['SODIENTHOAI']); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Địa chỉ:</span>
                    <span class="info-value"><?php echo htmlspecialchars($order['DIACHI']); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Phương thức thanh toán:</span>
                    <span class="info-value"><?php echo htmlspecialchars($order['PHUONGTHUCTHANHTOAN']); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Thời gian đặt:</span>
                    <span class="info-value"><?php echo date('d/m/Y H:i', strtotime($order['NGAYDAT'])); ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Trạng thái:</span>
                    <span class="info-value"><?php echo htmlspecialchars($order['TRANGTHAI']); ?></span>
                </div>
            </div>

            <div class="product-list">
                <?php foreach ($order_items as $item): ?>
                <div class="product-item">
                    <img src="/web_3/view/uploads/<?php echo htmlspecialchars($item['HINHANH']); ?>" 
                         alt="<?php echo htmlspecialchars($item['TENSP']); ?>" 
                         class="product-image"
                         onerror="this.src='/web_3/view/uploads/no-image.jpg'">
                    <div class="product-info">
                        <div class="product-name"><?php echo htmlspecialchars($item['TENSP']); ?></div>
                        <div class="product-meta">
                            Size: <?php echo htmlspecialchars($item['KICHTHUOC']); ?> | 
                            Số lượng: <?php echo number_format($item['SOLUONG']); ?>
                        </div>
                    </div>
                    <div class="product-price">
                        <?php echo number_format($item['THANHTIEN']); ?>đ
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
<div class="total-section">
                <div class="total-row">
                    <span>Tổng tiền:</span>
                    <span><?php echo number_format($order['TONGTIEN']); ?>đ</span>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Xóa checkout state từ sessionStorage khi đặt hàng thành công
        document.addEventListener('DOMContentLoaded', function() {
            // Xóa checkout state để không hiển thị thông báo "đơn hàng đang chờ thanh toán"
            sessionStorage.removeItem('checkout_state');
        });
    </script>
</body>
</html>
