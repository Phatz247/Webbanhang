<?php
session_start();
require_once __DIR__ . '/../model/database.php';
$db = new database();
$conn = $db->getConnection();

error_reporting(E_ALL);
ini_set('display_errors', 1);

$order = null;
$order_items = [];
$error_message = "";

// --- Xác nhận hoàn thành đơn hàng ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_done']) && !empty($_POST['madonhang'])) {
    $madonhang = $_POST['madonhang'];
    $stmt = $conn->prepare("UPDATE donhang SET is_confirmed = 1, TRANGTHAI = 'Đã hoàn thành' WHERE MADONHANG = ?");
    $stmt->execute([$madonhang]);
    header("Location: ?order_code=" . urlencode($madonhang));
    exit;
}

// --- Xử lý tự động chuyển sang "Đã giao hàng" sau 2 phút ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['auto_done']) && !empty($_POST['madonhang'])) {
    $madonhang = $_POST['madonhang'];
    // Chỉ update nếu trạng thái là "Đang giao hàng" và chưa xác nhận
    $stmt = $conn->prepare("UPDATE donhang SET TRANGTHAI = 'Đã giao hàng' WHERE MADONHANG = ? AND TRANGTHAI = 'Đang giao hàng' AND is_confirmed = 0");
    $stmt->execute([$madonhang]);
    echo "ok";
    exit;
}

// --- Tìm kiếm đơn hàng hoặc load qua GET ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' || isset($_GET['order_code'])) {
    $order_code = trim($_POST['order_code'] ?? $_GET['order_code'] ?? '');
    $phone = trim($_POST['phone'] ?? $_GET['phone'] ?? '');

    if (empty($order_code)) {
        $error_message = 'Vui lòng nhập mã đơn hàng!';
    } else {
        // Lấy thông tin đơn hàng (có cả is_confirmed)
        $query = "
            SELECT 
                dh.MADONHANG, dh.MAKH, dh.NGAYDAT, dh.TONGTIEN, dh.TRANGTHAI,
                dh.HOTEN, dh.SODIENTHOAI, dh.DIACHI, dh.PHUONGTHUCTHANHTOAN,
                dh.GHICHU, dh.is_confirmed,
                kh.TENKH as KHACHHANG_TEN, kh.EMAIL as KHACHHANG_EMAIL
            FROM donhang dh
            LEFT JOIN khachhang kh ON dh.MAKH = kh.MAKH
            WHERE dh.MADONHANG = ?
        ";
        $params = [$order_code];
        if (!empty($phone)) {
            $query .= " AND dh.SODIENTHOAI = ?";
            $params[] = $phone;
        }
        try {
            $stmt = $conn->prepare($query);
            $stmt->execute($params);
            $order = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$order) {
                $error_message = 'Không tìm thấy đơn hàng với thông tin đã nhập!';
            } else {
                // Lấy chi tiết sản phẩm trong đơn hàng
                $items_query = "
                    SELECT 
                        ct.MASP, ct.KICHTHUOC, ct.SOLUONG, ct.GIA, ct.THANHTIEN,
                        COALESCE(sp.TENSP, 'Sản phẩm không tồn tại') as TENSP,
                        COALESCE(sp.HINHANH, 'no-image.jpg') as HINHANH,
                        COALESCE(sp.MAUSAC, 'Không xác định') as MAUSAC
                    FROM chitietdonhang ct
                    LEFT JOIN sanpham sp ON ct.MASP = sp.MASP
                    WHERE ct.MADONHANG = ?
                    ORDER BY ct.MASP
                ";
                $items_stmt = $conn->prepare($items_query);
                $items_stmt->execute([$order_code]);
                $order_items = $items_stmt->fetchAll(PDO::FETCH_ASSOC);
            }
        } catch (PDOException $e) {
            $error_message = 'Lỗi cơ sở dữ liệu: ' . $e->getMessage();
            error_log("Database error in track_order.php: " . $e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tra cứu đơn hàng - MENSTA</title>
    <link rel="stylesheet" href="/web_3/view/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
      * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Inter', sans-serif; background: #f8f9fc; line-height: 1.6; }
        .container { max-width: 900px; margin: 0 auto; padding: 20px; }
        
        .header { background: white; padding: 30px; border-radius: 12px; box-shadow: 0 2px 15px rgba(0,0,0,0.1); margin-bottom: 30px; text-align: center; }
        .header h1 { color: #2c3e50; font-size: 32px; font-weight: 700; margin-bottom: 10px; }
        .header p { color: #7f8c8d; font-size: 16px; }
        
        .search-form { background: white; padding: 30px; border-radius: 12px; box-shadow: 0 2px 15px rgba(0,0,0,0.1); margin-bottom: 30px; }
        .search-form h3 { margin-bottom: 20px; color: #2c3e50; font-size: 20px; }
        .form-row { display: grid; grid-template-columns: 1fr 1fr auto; gap: 15px; align-items: end; }
        .form-group { display: flex; flex-direction: column; }
        .form-label { font-weight: 500; margin-bottom: 8px; color: #555; font-size: 14px; }
        .form-control { padding: 12px 15px; border: 1px solid #ddd; border-radius: 8px; font-size: 15px; }
        .form-control:focus { border-color: #3498db; box-shadow: 0 0 0 2px rgba(52, 152, 219, 0.2); outline: none; }
        .btn { padding: 12px 20px; border: none; border-radius: 8px; cursor: pointer; font-size: 15px; font-weight: 500; display: inline-flex; align-items: center; gap: 10px; }
        .btn-primary { background: #3498db; color: white; }
.btn-primary:hover { background: #2980b9; }
        
        .order-info { background: white; padding: 30px; border-radius: 12px; box-shadow: 0 2px 15px rgba(0,0,0,0.1); margin-bottom: 30px; }
        .order-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; padding-bottom: 20px; border-bottom: 1px solid #eee; }
        .order-code { font-size: 24px; font-weight: 700; color: #2c3e50; }
        .order-status { padding: 8px 16px; border-radius: 20px; font-size: 14px; font-weight: 600; }
        .status-pending { background: #fff3cd; color: #856404; }
        .status-processing { background: #d1ecf1; color: #0c5460; }
        .status-shipping { background: #e2e3f1; color: #383d41; }
        .status-delivered { background: #d4edda; color: #155724; }
        .status-cancelled { background: #f8d7da; color: #721c24; }
        
        .order-timeline { margin: 30px 0; }
        .timeline { position: relative; padding-left: 30px; }
        .timeline::before { content: ''; position: absolute; left: 15px; top: 0; bottom: 0; width: 2px; background: #e9ecef; }
        .timeline-item { position: relative; margin-bottom: 25px; }
        .timeline-item::before { content: ''; position: absolute; left: -23px; top: 5px; width: 16px; height: 16px; border-radius: 50%; background: #e9ecef; border: 3px solid white; }
        .timeline-item.active::before { background: #27ae60; }
.timeline-item.current::before { background: #3498db; animation: pulse 2s infinite; }
        .timeline-content { background: #f8f9fc; padding: 15px 20px; border-radius: 8px; border-left: 3px solid #e9ecef; }
        .timeline-item.active .timeline-content { border-left-color: #27ae60; }
        .timeline-item.current .timeline-content { border-left-color: #3498db; }
        .timeline-title { font-weight: 600; color: #2c3e50; margin-bottom: 5px; }
        .timeline-time { font-size: 13px; color: #7f8c8d; }
        
        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(52, 152, 219, 0.7); }
            70% { box-shadow: 0 0 0 10px rgba(52, 152, 219, 0); }
            100% { box-shadow: 0 0 0 0 rgba(52, 152, 219, 0); }
        }
        
        .order-details { display: grid; grid-template-columns: 1fr 1fr; gap: 30px; margin-bottom: 30px; }
        .detail-section { background: #f8f9fc; padding: 20px; border-radius: 8px; }
        .detail-section h4 { color: #2c3e50; margin-bottom: 15px; font-size: 16px; }
        .detail-item { display: flex; justify-content: space-between; margin-bottom: 10px; font-size: 14px; }
        .detail-label { color: #7f8c8d; }
        .detail-value { font-weight: 500; color: #2c3e50; }
        
        .order-items { background: white; padding: 30px; border-radius: 12px; box-shadow: 0 2px 15px rgba(0,0,0,0.1); }
        .order-items h3 { margin-bottom: 20px; color: #2c3e50; }
.item { display: flex; align-items: center; padding: 15px; background: #f8f9fc; border-radius: 8px; margin-bottom: 15px; }
        .item-image { width: 80px; height: 80px; object-fit: cover; border-radius: 8px; margin-right: 20px; }
        .item-info { flex: 1; }
        .item-name { font-weight: 600; color: #2c3e50; margin-bottom: 5px; }
        .item-details { color: #7f8c8d; font-size: 14px; margin-bottom: 5px; }
        .item-price { display: flex; justify-content: space-between; align-items: center; }
        .price { font-weight: 600; color: #e74c3c; }
        
        .order-summary { background: #f8f9fc; padding: 20px; border-radius: 8px; margin-top: 20px; }
        .summary-row { display: flex; justify-content: space-between; margin-bottom: 10px; }
        .summary-row.total { font-size: 18px; font-weight: 700; color: #2c3e50; border-top: 1px solid #ddd; padding-top: 15px; margin-top: 15px; }
        
        .alert { padding: 15px; border-radius: 8px; margin-bottom: 20px; text-align: center; }
        .alert-danger { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .alert-info { background: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }
        
        .no-order { text-align: center; padding: 60px 20px; color: #7f8c8d; }
        .no-order i { font-size: 48px; margin-bottom: 20px; color: #bdc3c7; }
        
        @media (max-width: 768px) {
            .form-row { grid-template-columns: 1fr; }
            .order-details { grid-template-columns: 1fr; }
.order-header { flex-direction: column; align-items: flex-start; gap: 15px; }
            .item { flex-direction: column; text-align: center; }
            .item-image { margin-right: 0; margin-bottom: 15px; }
        }
        .btn-success {background: #27ae60 !important; align-items: center;}
        .alert-success { background: #e9ffe6; color: #279940; border: 1px solid #a1f7bd; }
  .btn-danger {
    background: #e74c3c !important;
    color: #fff !important;
    border: none;
    padding: 12px 20px;
    border-radius: 8px;
    font-size: 15px;
    font-weight: 500;
    display: inline-flex;
    align-items: center;
    gap: 10px;
    text-decoration: none;
    transition: background 0.2s;
}
.btn-danger:hover {
    background: #c0392b !important;
    color: #fff !important;
}

  </style>
</head>
<body>
<?php if ($order && $order['TRANGTHAI'] == 'Đang giao hàng' && !$order['is_confirmed']): ?>
<script>
    setTimeout(function(){
        fetch('auto_update.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body: 'madonhang=<?= $order['MADONHANG'] ?>&auto_done=1'

})
        .then(res => res.text())
        .then(function(res){
            if (res.trim() === 'ok') location.reload();
        });
    }, 60000);
</script>
<?php endif; ?>
<div class="container">
     <a href="/web_3/view/profile.php?tab=orders#orders" class="btn btn-danger" style="float:right; margin-bottom: 20px;">
        <i class="fas fa-refresh"></i> Quay lại
    </a>
    <div class="header">
        <h1><i class="fas fa-search"></i> Tra cứu đơn hàng</h1>
        <p>Nhập mã đơn hàng để kiểm tra tình trạng giao hàng</p>
    </div>
    <!-- Form tìm kiếm -->
    <div class="search-form">
        <h3><i class="fas fa-magnifying-glass"></i> Thông tin tra cứu</h3>
        <form method="POST" action="">
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Mã đơn hàng <span style="color: red;">*</span></label>
                    <input type="text" name="order_code" class="form-control"
                        placeholder="Ví dụ: DH2024010112345"
                        value="<?= htmlspecialchars($_POST['order_code'] ?? $_GET['order_code'] ?? '') ?>"
                        required>
                </div>
                <div class="form-group">
                    <label class="form-label">Số điện thoại (tùy chọn)</label>
                    <input type="tel" name="phone" class="form-control"
                        placeholder="Nhập số điện thoại đặt hàng"
                        value="<?= htmlspecialchars($_POST['phone'] ?? $_GET['phone'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i> Tra cứu
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- Hiển thị lỗi -->
    <?php if (!empty($error_message)): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-triangle"></i> <?= $error_message; ?>
        </div>
    <?php endif; ?>

    <!-- Thông tin đơn hàng -->
    <?php if ($order): ?>
        <div class="order-info">
            <div class="order-header">
                <div class="order-code">#<?= $order['MADONHANG']; ?></div>
                <div class="order-status <?php
                    switch ($order['TRANGTHAI']) {
                        case 'Chờ xử lý': echo 'status-pending'; break;
                        case 'Đang xử lý': echo 'status-processing'; break;
                        case 'Đang giao hàng': echo 'status-shipping'; break;
                        case 'Đã giao hàng': echo 'status-delivered'; break;
                        case 'Đã hoàn thành': echo 'status-delivered'; break;
                        case 'Đã hủy': echo 'status-cancelled'; break;
                    }
                ?>">
                    <?= $order['TRANGTHAI']; ?>
                </div>
            </div>
            <!-- Timeline -->
            <?php if ($order['TRANGTHAI'] !== 'Đã hủy'): ?>
                <div class="order-timeline">
                    <h4 style="margin-bottom: 20px; color: #2c3e50;">Tiến trình đơn hàng</h4>
                    <div class="timeline">
                        <?php
                        $statuses = ['Chờ xử lý', 'Đang xử lý', 'Đang giao hàng', 'Đã giao hàng', 'Đã hoàn thành'];
                        $currentStep = array_search($order['TRANGTHAI'], $statuses);
                        foreach ($statuses as $index => $status):
                            $isActive = $index <= $currentStep;
                            $isCurrent = $index === $currentStep && !in_array($order['TRANGTHAI'], ['Đã giao hàng', 'Đã hoàn thành']);
                            $class = $isActive ? 'active' : '';
                            if ($isCurrent) $class .= ' current';
                        ?>
                        <div class="timeline-item <?= $class; ?>">
                            <div class="timeline-content">
                                <div class="timeline-title"><?= $status; ?></div>
                                <?php if ($index === 0): ?>
                                    <div class="timeline-time"><?= date('d/m/Y H:i', strtotime($order['NGAYDAT'])); ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Nút xác nhận nhận hàng (chỉ hiện khi ĐÃ GIAO HÀNG và chưa xác nhận) -->
            <?php if ($order['TRANGTHAI'] == 'Đã giao hàng' && !$order['is_confirmed']): ?>
                <form method="post" style="margin: 25px 0 0 0;">
                    <input type="hidden" name="confirm_done" value="1">
                    <input type="hidden" name="madonhang" value="<?= htmlspecialchars($order['MADONHANG']); ?>">
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-check"></i> Xác nhận đã nhận hàng thành công
                    </button>
                </form>
            <?php elseif ($order['TRANGTHAI'] == 'Đã hoàn thành' && $order['is_confirmed']): ?>
                <div class="alert alert-success" style="margin: 25px 0 0 0;">
                    <i class="fas fa-check-circle"></i> Đơn hàng đã hoàn thành! Cảm ơn bạn đã mua hàng!
                </div>
            <?php endif; ?>

            <!-- Chi tiết đơn hàng -->
            <div class="order-details">
                <div class="detail-section">
                    <h4><i class="fas fa-user"></i> Thông tin người nhận</h4>
                    <div class="detail-item">
                        <span class="detail-label">Họ và tên:</span>
                        <span class="detail-value"><?= htmlspecialchars($order['HOTEN']); ?></span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Số điện thoại:</span>
                        <span class="detail-value"><?= htmlspecialchars($order['SODIENTHOAI']); ?></span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Địa chỉ:</span>
                        <span class="detail-value"><?= htmlspecialchars($order['DIACHI']); ?></span>
                    </div>
                </div>
                <div class="detail-section">
                    <h4><i class="fas fa-info-circle"></i> Thông tin đơn hàng</h4>
                    <div class="detail-item">
                        <span class="detail-label">Ngày đặt:</span>
                        <span class="detail-value"><?= date('d/m/Y H:i', strtotime($order['NGAYDAT'])); ?></span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Thanh toán:</span>
                        <span class="detail-value"><?= htmlspecialchars($order['PHUONGTHUCTHANHTOAN']); ?></span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Tổng tiền:</span>
                        <span class="detail-value" style="color: #e74c3c; font-weight: 600;">
                            <?= number_format($order['TONGTIEN']); ?>đ
                        </span>
                    </div>
                    <?php if (!empty($order['GHICHU'])): ?>
                        <div class="detail-item">
                            <span class="detail-label">Ghi chú:</span>
                            <span class="detail-value"><?= htmlspecialchars($order['GHICHU']); ?></span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <!-- Danh sách sản phẩm -->
        <?php if (!empty($order_items)): ?>
            <div class="order-items">
                <h3><i class="fas fa-box"></i> Sản phẩm trong đơn hàng</h3>
                <?php foreach ($order_items as $item): ?>
                <div class="item">
                    <img src="/web_3/view/uploads/<?= htmlspecialchars($item['HINHANH']); ?>"
                        alt="<?= htmlspecialchars($item['TENSP']); ?>" class="item-image"
                        onerror="this.onerror=null; this.src='/web_3/view/uploads/no-image.jpg'">
                    <div class="item-info">
                        <div class="item-name"><?= htmlspecialchars($item['TENSP']); ?></div>
                        <div class="item-details">
                            Màu sắc: <?= htmlspecialchars($item['MAUSAC']); ?> |
                            Size: <?= htmlspecialchars($item['KICHTHUOC']); ?> |
                            Số lượng: <?= number_format($item['SOLUONG']); ?>
                        </div>
                        <div class="item-price">
                            <span>Đơn giá: <?= number_format($item['GIA']); ?>đ</span>
                            <span class="price">Thành tiền: <?= number_format($item['THANHTIEN']); ?>đ</span>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
                <div class="order-summary">
                    <div class="summary-row">
                        <span>Số lượng sản phẩm:</span>
                        <span><?= count($order_items); ?> sản phẩm</span>
                    </div>
                    <div class="summary-row">
                        <span>Tổng số lượng:</span>
                        <span><?= array_sum(array_column($order_items, 'SOLUONG')); ?> cái</span>
                    </div>
                    <div class="summary-row total">
                        <span>Tổng thanh toán:</span>
                        <span><?= number_format($order['TONGTIEN']); ?>đ</span>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    <?php elseif ($_SERVER['REQUEST_METHOD'] === 'POST'): ?>
        <div class="no-order">
            <i class="fas fa-search"></i>
            <h3>Không tìm thấy đơn hàng</h3>
            <p>Vui lòng kiểm tra lại mã đơn hàng và số điện thoại</p>
        </div>
    <?php else: ?>
        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i>
            Nhập mã đơn hàng để tra cứu tình trạng đơn hàng của bạn
        </div>
    <?php endif; ?>
</div>
<script>
    // Auto-focus vào input đầu tiên
    document.addEventListener('DOMContentLoaded', function() {
        var orderCodeInput = document.querySelector('input[name="order_code"]');
        if (orderCodeInput && !orderCodeInput.value) {
            orderCodeInput.focus();
        }
        // Format mã đơn hàng
        orderCodeInput.addEventListener('input', function(e) {
            let value = e.target.value.toUpperCase();
            e.target.value = value;
        });
    });
</script>
</body>
</html>
