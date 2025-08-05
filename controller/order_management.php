<?php
if (session_status() == PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../model/database.php';
$db = new database();
$conn = $db->getConnection();

error_reporting(E_ALL);
ini_set('display_errors', 1);

$alert = "";

// --- Cập nhật trạng thái nhanh bằng nút ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['set_shipping'])) {
    $madonhang = $_POST['madonhang'];
    $stmt = $conn->prepare("UPDATE donhang SET TRANGTHAI = 'Đang giao hàng' WHERE MADONHANG = ?");
    $stmt->execute([$madonhang]);
    $alert = "Đã chuyển đơn $madonhang sang trạng thái Đang giao hàng.";
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
    $alert = "Đã cập nhật trạng thái đơn hàng!";
}

// --- Xóa đơn hàng ---
if (isset($_GET['delete'])) {
    $madonhang = $_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM chitietdonhang WHERE MADONHANG = ?");
    $stmt->execute([$madonhang]);
    $stmt = $conn->prepare("DELETE FROM donhang WHERE MADONHANG = ?");
    $stmt->execute([$madonhang]);
    $alert = "Đã xóa đơn hàng $madonhang!";
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
.status-pending {color: #ff9800; font-weight:bold;}
.status-processing {color: #2196f3; font-weight:bold;}
.status-shipping {color: #9c27b0; font-weight:bold;}
.status-delivered {color: #2ecc71; font-weight:bold;}
.status-done {color: #111; font-weight:bold;}
.status-completed {color: #009688; font-weight:bold;}
.status-cancel {color: #f44336; font-weight:bold;}
.status-return-request {color: #ff9800; font-weight:bold; background: #fff3e0; padding: 2px 6px; border-radius: 4px;}
.status-returned {color: #795548; font-weight:bold; background: #efebe9; padding: 2px 6px; border-radius: 4px;}
.btn {padding: 6px 18px; border-radius: 6px; background: #1976d2; color: #fff; border: none; cursor:pointer; transition:0.2s;}
.btn-del {background: #e53935;}
.btn-view {background: #26a69a;}
.btn-sm {font-size: 12px; padding: 4px 8px; margin: 1px;}
.btn[disabled], .btn.disabled {background: #bdbdbd; cursor:not-allowed;}
.select-status {padding:2px 6px; border-radius:5px; border:1px solid #bdbdbd; font-size: 11px; width: 120px;}
.action-buttons {display: flex; flex-direction: column; gap: 2px; align-items: flex-start;}
.button-row {display: flex; gap: 3px; align-items: center;}
.alert {padding: 13px; background: #e3ffe9; color: #168e00; border: 1px solid #adf5a5; margin-bottom: 24px; border-radius:8px; font-size: 15px;}
@media (max-width: 900px) {
    .main-wrap-order{padding:14px;}
    .table-order th, .table-order td{padding:7px 3px;}
}
</style>

<div class="main-wrap-order">
    <h2>Quản lý đơn hàng</h2>
    
    <?php if ($returnRequestCount > 0): ?>
        <div style="background: #fff3e0; border: 2px solid #ff9800; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
            <strong style="color: #e65100;">🔔 Có <?= $returnRequestCount ?> yêu cầu hoàn hàng cần xử lý!</strong>
            <p style="margin: 5px 0 0 0; color: #bf360c; font-size: 14px;">
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
                    <td style="color:#c0392b; font-weight:600;"><?= number_format($row['TONGTIEN']) ?>đ</td>
                    <td>
                        <span class="<?= $status_class ?>"><?= htmlspecialchars($status) ?></span>
                        <?php if($status == 'Đang xử lý' && !$is_done): ?>
                            <form method="post" action="?section=donhang" style="display:inline-block; margin-top:3px;">
                                <input type="hidden" name="madonhang" value="<?= htmlspecialchars($row['MADONHANG']) ?>">
                                <button type="submit" name="set_shipping" class="btn btn-sm" style="background:#2196f3; margin-top:2px;">Chuyển sang "Đang giao hàng"</button>
                            </form>
                        <?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars($row['PHUONGTHUCTHANHTOAN']) ?></td>
                    <td>
                        <div class="action-buttons">
                            <a class="btn btn-view btn-sm" href="?section=donhang&view=<?= urlencode($row['MADONHANG']) ?>">Xem</a>
                            
                            <?php if ($row['TRANGTHAI'] == 'Yêu cầu hoàn hàng'): ?>
                                <!-- Trường hợp yêu cầu hoàn hàng: Admin có thể chấp nhận hoặc từ chối -->
                                <div class="button-row">
                                    <form style="display:inline;" method="POST" action="?section=donhang">
                                        <input type="hidden" name="madonhang" value="<?= htmlspecialchars($row['MADONHANG']) ?>">
                                        <input type="hidden" name="trangthai" value="Đã hoàn hàng">
                                        <button type="submit" name="update_status" class="btn btn-sm" style="background: #4caf50;" 
                                                onclick="return confirm('Chấp nhận hoàn hàng?\n- Sẽ hoàn lại tồn kho\n- Hoàn lại voucher (nếu có)')">
                                            ✓ Chấp nhận
                                        </button>
                                    </form>
                                    <form style="display:inline;" method="POST" action="?section=donhang">
                                        <input type="hidden" name="madonhang" value="<?= htmlspecialchars($row['MADONHANG']) ?>">
                                        <input type="hidden" name="trangthai" value="Đã hoàn thành">
                                        <button type="submit" name="update_status" class="btn btn-sm" style="background: #f44336;" 
                                                onclick="return confirm('Từ chối hoàn hàng và giữ trạng thái Đã hoàn thành?')">
                                            ✗ Từ chối
                                        </button>
                                    </form>
                                </div>
                                <a class="btn btn-del btn-sm" href="?section=donhang&delete=<?= urlencode($row['MADONHANG']) ?>" onclick="return confirm('Bạn chắc chắn muốn xóa đơn này?')">Xóa</a>
                                
                            <?php elseif ($row['TRANGTHAI'] == 'Đã hoàn hàng'): ?>
                                <span style="color: #795548; font-weight:bold; font-size: 11px;">Đã hoàn hàng</span>
                                <a class="btn btn-del btn-sm" href="?section=donhang&delete=<?= urlencode($row['MADONHANG']) ?>" onclick="return confirm('Bạn chắc chắn muốn xóa đơn này?')">Xóa</a>
                                
                            <?php elseif ($row['TRANGTHAI'] == 'Đã hoàn thành'): ?>
                                <a class="btn btn-del btn-sm" href="?section=donhang&delete=<?= urlencode($row['MADONHANG']) ?>" onclick="return confirm('Bạn chắc chắn muốn xóa đơn này?')">Xóa</a>
                                <span style="color: #20bfa1; font-weight:bold; font-size: 11px;">Đã hoàn thành</span>
                                
                            <?php else: ?>
                                <a class="btn btn-del btn-sm" href="?section=donhang&delete=<?= urlencode($row['MADONHANG']) ?>" onclick="return confirm('Bạn chắc chắn muốn xóa đơn này?')">Xóa</a>
                                <form style="display:inline;" method="POST" action="?section=donhang">
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
                                    <button type="submit" name="update_status" class="btn btn-sm">Cập nhật</button>
                                </form>
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
