<?php
if (session_status() == PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../model/database.php';
$db = new database();
$conn = $db->getConnection();

error_reporting(E_ALL);
ini_set('display_errors', 1);

$alert = "";

// Hiển thị thông báo dựa trên tham số URL
if (isset($_GET['updated'])) {
    $alert = "Đã cập nhật trạng thái đơn hàng thành công!";
} elseif (isset($_GET['deleted'])) {
    $alert = "Đã xóa đơn hàng thành công!";
}

// --- Cập nhật trạng thái nhanh bằng nút ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['set_shipping'])) {
    $madonhang = $_POST['madonhang'];
    $stmt = $conn->prepare("UPDATE donhang SET TRANGTHAI = 'Đang giao hàng' WHERE MADONHANG = ?");
    $stmt->execute([$madonhang]);
    // Cập nhật trạng thái giao hàng tương ứng
    $stmt = $conn->prepare("UPDATE giaohang SET TRANGTHAIGH = 'Đang giao', NGAYCAPNHAT = NOW() WHERE MADONHANG = ?");
    $stmt->execute([$madonhang]);
    
    // Redirect sau khi cập nhật (tránh header sau khi đã output)
    echo '<script>window.location.href = "/web_3/view/admin.php?section=donhang&updated=1";</script>';
    exit;
}

// --- Cập nhật trạng thái bằng select box ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $madonhang = $_POST['madonhang'];
    $trangthai = $_POST['trangthai'];
    
    // Nếu admin chấp nhận hoàn hàng, cần hoàn lại tồn kho
    if ($trangthai === 'Đã hoàn hàng') {
        // Lấy chi tiết đơn hàng
        $detailStmt = $conn->prepare("SELECT MASP, SOLUONG FROM chitietdonhang WHERE MADONHANG = ?");
        $detailStmt->execute([$madonhang]);
        $orderDetails = $detailStmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Hoàn lại số lượng tồn kho
        foreach ($orderDetails as $detail) {
            $updateStockStmt = $conn->prepare("UPDATE sanpham SET SOLUONG = SOLUONG + ? WHERE MASP = ?");
            $updateStockStmt->execute([$detail['SOLUONG'], $detail['MASP']]);
        }
        
        // Hoàn lại voucher nếu có
        $voucherStmt = $conn->prepare("SELECT MAVOUCHER FROM donhang WHERE MADONHANG = ? AND MAVOUCHER IS NOT NULL");
        $voucherStmt->execute([$madonhang]);
        $voucherUsed = $voucherStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($voucherUsed) {
            $updateVoucherStmt = $conn->prepare("UPDATE voucher SET SOLUONGSUDUNG = SOLUONGSUDUNG - 1 WHERE MAVOUCHER = ?");
            $updateVoucherStmt->execute([$voucherUsed['MAVOUCHER']]);
        }
    }
    
    $stmt = $conn->prepare("UPDATE donhang SET TRANGTHAI = ? WHERE MADONHANG = ?");
    $stmt->execute([$trangthai, $madonhang]);
    // Đồng bộ bảng giao hàng
    $map = [
        'Chờ xử lý' => 'Chờ xử lý',
        'Đang xử lý' => 'Đang chuẩn bị',
        'Đang giao hàng' => 'Đang giao',
        'Đã giao hàng' => 'Đã giao',
        'Yêu cầu hoàn hàng' => 'Yêu cầu hoàn',
        'Đã hoàn hàng' => 'Đã hoàn',
        'Đã hủy' => 'Đã hủy',
        'Đã hoàn thành' => 'Đã giao',
    ];
    if (isset($map[$trangthai])) {
        $ghStatus = $map[$trangthai];
        $extra = $ghStatus === 'Đã giao' ? ", NGAYGIAO = COALESCE(NGAYGIAO, NOW())" : '';
        $stmt = $conn->prepare("UPDATE giaohang SET TRANGTHAIGH = ?, NGAYCAPNHAT = NOW() $extra WHERE MADONHANG = ?");
        $stmt->execute([$ghStatus, $madonhang]);
    }
    
    // Redirect sau khi cập nhật (tránh header sau khi đã output)
    echo '<script>window.location.href = "/web_3/view/admin.php?section=donhang&updated=1";</script>';
    exit;
}

// --- Xóa đơn hàng ---
if (isset($_GET['delete'])) {
    $madonhang = $_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM chitietdonhang WHERE MADONHANG = ?");
    $stmt->execute([$madonhang]);
    $stmt = $conn->prepare("DELETE FROM donhang WHERE MADONHANG = ?");
    $stmt->execute([$madonhang]);
    
    // Redirect về trang quản lý đơn hàng sau khi xóa (tránh header sau khi đã output)
    echo '<script>window.location.href = "/web_3/view/admin.php?section=donhang&deleted=1";</script>';
    exit;
}

// --- Lấy danh sách đơn hàng ---
$stmt = $conn->prepare("
    SELECT d.*, k.TENKH 
    FROM donhang d 
    LEFT JOIN khachhang k ON d.MAKH = k.MAKH
    ORDER BY 
        CASE WHEN d.TRANGTHAI = 'Yêu cầu hoàn hàng' THEN 0 ELSE 1 END,
        d.NGAYDAT DESC
");
$stmt->execute();
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Đếm số yêu cầu hoàn hàng
$returnRequestCount = count(array_filter($orders, fn($order) => $order['TRANGTHAI'] === 'Yêu cầu hoàn hàng'));

// --- Lấy chi tiết đơn hàng nếu có yêu cầu ---
$order_details = [];
if (isset($_GET['view'])) {
    $madonhang = $_GET['view'];
    $stmt = $conn->prepare("
        SELECT c.*, s.TENSP 
        FROM chitietdonhang c 
        LEFT JOIN sanpham s ON c.MASP = s.MASP
        WHERE c.MADONHANG = ?
    ");
    $stmt->execute([$madonhang]);
    $order_details = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<style>
body {
    background: #f7f7f9;
}
.main-wrap-order {
    max-width:1200px; margin:40px auto 0 auto; background:#fff;
    border-radius:12px; padding:36px 32px; box-shadow:0 4px 16px 0 #0001;
}
h2 {font-size:2.1rem; color:#273042; margin-bottom:28px;}
.table-order {
    width: 100%; border-collapse: collapse; margin-bottom: 25px;
    background: #fff;
    font-size: 15px;
}
.table-order th, .table-order td {
    border: 1px solid #e9ecef; padding: 10px 9px; text-align:left;
}
.table-order th {
    background: #f6f8fa; font-weight:700; color:#343a40;
}
.table-order tr:nth-child(even){background: #fafbfc;}
.status-pending {color: #6c757d; font-weight:bold;}
.status-processing {color: #495057; font-weight:bold;}
.status-shipping {color: #6c757d; font-weight:bold;}
.status-delivered {color: #343a40; font-weight:bold;}
.status-done {color: #212529; font-weight:bold;}
.status-completed {color: #495057; font-weight:bold;}
.status-cancel {color: #dc3545; font-weight:bold;}
.status-return-request {color: #dc3545; font-weight:bold; background: #f8f9fa; padding: 2px 6px; border-radius: 4px; border: 1px solid #dee2e6;}
.status-returned {color: #6c757d; font-weight:bold; background: #f8f9fa; padding: 2px 6px; border-radius: 4px; border: 1px solid #dee2e6;}
.btn {padding: 6px 12px; border-radius: 6px; background: #6c757d; color: #fff; border: none; cursor:pointer; transition:0.2s; text-decoration: none; display: inline-block;}
.btn:hover {opacity: 0.85; transform: translateY(-1px);}
.btn-del {background: #dc3545;}
.btn-view {background: #0d6efd;}
.btn-update {background: #1d8cf8;}
.btn-accept {background: #343a40;}
.btn-reject {background: #dc3545;}
.btn-sm {font-size: 12px; padding: 5px 10px; margin: 1px;}
.btn[disabled], .btn.disabled {background: #adb5bd; cursor:not-allowed; opacity: 0.6;}
.select-status {padding:4px 8px; border-radius:5px; border:1px solid #ced4da; font-size: 12px; width: 110px; margin-right: 3px; background: #fff;}
.action-buttons {display: flex; flex-direction: row; flex-wrap: wrap; gap: 3px; align-items: center; min-width: 200px;}
.button-row {display: flex; gap: 3px; align-items: center;}
.update-row {display: flex; align-items: center; gap: 3px;}
.status-info {margin-right: 5px;}
.status-badge {font-size: 11px; padding: 3px 8px; border-radius: 4px; font-weight: bold; margin-right: 5px;}
.status-badge.status-returned {background: #f8f9fa; color: #6c757d; border: 1px solid #dee2e6;}
.status-badge.status-completed {background: #e9ecef; color: #495057; border: 1px solid #ced4da;}
.alert {padding: 13px; background: #f8f9fa; color: #495057; border: 1px solid #dee2e6; margin-bottom: 24px; border-radius:8px; font-size: 15px;}
@media (max-width: 900px) {
    .main-wrap-order{padding:14px;}
    .table-order th, .table-order td{padding:7px 3px;}
    .action-buttons {flex-direction: column; align-items: flex-start; min-width: 120px;}
    .select-status {width: 90px; font-size: 10px;}
    .btn-sm {font-size: 10px; padding: 3px 6px;}
}
</style>

<div class="main-wrap-order">
    <h2>Quản lý đơn hàng</h2>
    
    <?php if ($returnRequestCount > 0): ?>
        <div style="background: #f8f9fa; border: 2px solid #dc3545; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
            <strong style="color: #dc3545;">⚠ Có <?= $returnRequestCount ?> yêu cầu hoàn hàng cần xử lý!</strong>
            <p style="margin: 5px 0 0 0; color: #6c757d; font-size: 14px;">
                Các đơn hàng yêu cầu hoàn hàng được ưu tiên hiển thị ở đầu danh sách.
            </p>
        </div>
    <?php endif; ?>
    
    <?php if (!empty($alert)): ?>
        <div class="alert"><?= htmlspecialchars($alert) ?></div>
    <?php endif; ?>
    <table class="table-order">
        <thead>
            <tr>
                <th>Mã ĐH</th>
                <th>Khách hàng</th>
                <th>Ngày đặt</th>
                <th>Tổng tiền</th>
                <th>Trạng thái</th>
                <th>Thanh toán</th>
                <th>Thao tác</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($orders as $row): 
                $status = $row['TRANGTHAI'];
                $status_class = '';
                if ($status == 'Chờ xử lý') $status_class = 'status-pending';
                elseif ($status == 'Đang xử lý') $status_class = 'status-processing';
                elseif ($status == 'Đang giao hàng') $status_class = 'status-shipping';
                elseif ($status == 'Đã giao hàng') $status_class = 'status-delivered';
                elseif ($status == 'Đã hoàn thành') $status_class = 'status-completed';
                elseif ($status == 'Đã hủy') $status_class = 'status-cancel';
                elseif ($status == 'Yêu cầu hoàn hàng') $status_class = 'status-return-request';
                elseif ($status == 'Đã hoàn hàng') $status_class = 'status-returned';
                // Đơn đã hoàn thành hay chưa
                $is_done = ($status == 'Đã hoàn thành');
            ?>
                <tr>
                    <td><?= htmlspecialchars($row['MADONHANG']) ?></td>
                    <td><?= htmlspecialchars($row['TENKH'] ?? $row['HOTEN']) ?><br>
<small><?= htmlspecialchars($row['SODIENTHOAI']) ?></small>
                    </td>
                    <td><?= date('d/m/Y H:i', strtotime($row['NGAYDAT'])) ?></td>
                    <td style="color:#dc3545; font-weight:600;"><?= number_format($row['TONGTIEN']) ?>đ</td>
                    <td>
                        <span class="<?= $status_class ?>"><?= htmlspecialchars($status) ?></span>
                    </td>
                    <td><?= htmlspecialchars($row['PHUONGTHUCTHANHTOAN']) ?></td>
                    <td>
                        <div class="action-buttons">
                            <!-- Nút Xem - luôn hiển thị đầu tiên -->
                            <a class="btn btn-view btn-sm" href="?section=donhang&view=<?= urlencode($row['MADONHANG']) ?>">Xem</a>
                            
                            <?php if ($row['TRANGTHAI'] == 'Yêu cầu hoàn hàng'): ?>
                                <!-- Trường hợp yêu cầu hoàn hàng: Admin có thể chấp nhận hoặc từ chối -->
                                <form style="display:inline;" method="POST" action="?section=donhang">
                                    <input type="hidden" name="madonhang" value="<?= htmlspecialchars($row['MADONHANG']) ?>">
                                    <input type="hidden" name="trangthai" value="Đã hoàn hàng">
                                    <button type="submit" name="update_status" class="btn btn-sm btn-accept" 
                                            onclick="return confirm('Chấp nhận hoàn hàng?\n- Sẽ hoàn lại tồn kho\n- Hoàn lại voucher (nếu có)')">
                                        Chấp nhận
                                    </button>
                                </form>
                                <form style="display:inline;" method="POST" action="?section=donhang">
                                    <input type="hidden" name="madonhang" value="<?= htmlspecialchars($row['MADONHANG']) ?>">
                                    <input type="hidden" name="trangthai" value="Đã hoàn thành">
                                    <button type="submit" name="update_status" class="btn btn-sm btn-reject" 
                                            onclick="return confirm('Từ chối hoàn hàng và giữ trạng thái Đã hoàn thành?')">
Từ chối
                                    </button>
                                </form>
                                <a class="btn btn-del btn-sm" href="?section=donhang&delete=<?= urlencode($row['MADONHANG']) ?>" onclick="return confirm('Bạn chắc chắn muốn xóa đơn này?')">Xóa</a>
                                
                            <?php elseif ($row['TRANGTHAI'] == 'Đã hoàn hàng' || $row['TRANGTHAI'] == 'Đã hoàn thành'): ?>
                                <!-- Trạng thái hoàn tất - chỉ có nút xóa -->
                                <span class="status-badge <?= $row['TRANGTHAI'] == 'Đã hoàn hàng' ? 'status-returned' : 'status-completed' ?>">
                                    <?= $row['TRANGTHAI'] == 'Đã hoàn hàng' ? 'Đã hoàn hàng' : 'Hoàn thành' ?>
                                </span>
                                <a class="btn btn-del btn-sm" href="?section=donhang&delete=<?= urlencode($row['MADONHANG']) ?>" onclick="return confirm('Bạn chắc chắn muốn xóa đơn này?')">Xóa</a>
                                
                            <?php else: ?>
                                <!-- Trạng thái bình thường - có thể cập nhật -->
                                <?php if($status == 'Đang xử lý'): ?>
                                <form method="post" action="?section=donhang" style="display:inline-flex; align-items:center; gap:3px;">
                                    <input type="hidden" name="madonhang" value="<?= ($row['MADONHANG']) ?>">
                                    <button type="submit" name="set_shipping" class="btn btn-sm" style="background:#495057;">Chuyển sang "Đang giao hàng"</button>
                                </form>
                                <?php endif; ?>
                                <form style="display:inline-flex; align-items:center; gap:3px;" method="POST" action="?section=donhang">
                                    <input type="hidden" name="madonhang" value="<?= htmlspecialchars($row['MADONHANG']) ?>">
                                    <select name="trangthai" class="select-status">
                                        <option <?= $status=='Chờ xử lý'?'selected':'' ?>>Chờ xử lý</option>
                                        <option <?= $status=='Đang xử lý'?'selected':'' ?>>Đang xử lý</option>
                                        <option <?= $status=='Đang giao hàng'?'selected':'' ?>>Đang giao hàng</option>
                                        <option <?= $status=='Đã giao hàng'?'selected':'' ?>>Đã giao hàng</option>
                                        <option <?= $status=='Đã hoàn thành'?'selected':'' ?>>Đã hoàn thành</option>
                                        <option <?= $status=='Đã hủy'?'selected':'' ?>>Đã hủy</option>
                                        <option <?= $status=='Yêu cầu hoàn hàng'?'selected':'' ?>>Yêu cầu hoàn hàng</option>
                                        <option <?= $status=='Đã hoàn hàng'?'selected':'' ?>>Đã hoàn hàng</option>
                                    </select>
                                    <button type="submit" name="update_status" class="btn btn-sm btn-update">Cập nhật</button>
                                </form>
                                <a class="btn btn-del btn-sm" href="?section=donhang&delete=<?= urlencode($row['MADONHANG']) ?>" onclick="return confirm('Bạn chắc chắn muốn xóa đơn này?')">Xóa</a>
<?php endif; ?>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php if(isset($_GET['view'])): ?>
        <h3 style="margin-top:38px; color:#273042;">Chi tiết đơn hàng <?= htmlspecialchars($madonhang) ?></h3>
        <table class="table-order">
            <thead>
                <tr>
                    <th>Sản phẩm</th>
                    <th>Kích thước</th>
                    <th>Giá</th>
                    <th>Số lượng</th>
                    <th>Thành tiền</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($order_details as $d): ?>
                <tr>
                    <td><?= htmlspecialchars($d['TENSP']) ?> (<?= htmlspecialchars($d['MASP']) ?>)</td>
                    <td><?= htmlspecialchars($d['KICHTHUOC']) ?></td>
                    <td><?= number_format($d['GIA']) ?>đ</td>
                    <td><?= $d['SOLUONG'] ?></td>
                    <td><?= number_format($d['THANHTIEN']) ?>đ</td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
