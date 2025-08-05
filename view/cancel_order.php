<?php
session_start();
require_once __DIR__ . '/../model/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['username'])) {
    echo json_encode(['success' => false, 'message' => 'Bạn cần đăng nhập để thực hiện thao tác này']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Phương thức không được hỗ trợ']);
    exit;
}

if (!isset($_POST['order_code']) || empty($_POST['order_code'])) {
    echo json_encode(['success' => false, 'message' => 'Mã đơn hàng không hợp lệ']);
    exit;
}

// Lấy loại hành động (cancel hoặc return)
$action = $_POST['action'] ?? 'cancel';

$db = new Database();
$conn = $db->getConnection();

$orderCode = $_POST['order_code'];
$username = $_SESSION['username'];

try {
    // Bắt đầu transaction
    $conn->beginTransaction();
    
    // Lấy thông tin người dùng
    $userStmt = $conn->prepare("SELECT MAKH FROM taikhoan WHERE TENDANGNHAP = ?");
    $userStmt->execute([$username]);
    $userInfo = $userStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$userInfo) {
        throw new Exception('Không tìm thấy thông tin người dùng');
    }
    
    // Kiểm tra đơn hàng có thuộc về user này không
    $orderStmt = $conn->prepare("
        SELECT MADONHANG, TRANGTHAI, MAKH 
        FROM donhang 
        WHERE MADONHANG = ? AND MAKH = ?
    ");
    $orderStmt->execute([$orderCode, $userInfo['MAKH']]);
    $order = $orderStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$order) {
        throw new Exception('Không tìm thấy đơn hàng hoặc đơn hàng không thuộc về bạn');
    }
    
    // Kiểm tra điều kiện dựa trên hành động
    if ($action === 'cancel') {
        // Hủy đơn: chỉ được phép khi đơn hàng "Chờ xử lý"
        if ($order['TRANGTHAI'] !== 'Chờ xử lý') {
            throw new Exception('Chỉ có thể hủy đơn hàng ở trạng thái "Chờ xử lý"');
        }
        $newStatus = 'Đã hủy';
        $successMessage = 'Đơn hàng đã được hủy thành công';
    } elseif ($action === 'return') {
        // Hoàn hàng: chỉ được phép khi đơn hàng "Đã hoàn thành"
        if ($order['TRANGTHAI'] !== 'Đã hoàn thành') {
            throw new Exception('Chỉ có thể hoàn hàng khi đơn hàng đã hoàn thành');
        }
        $newStatus = 'Yêu cầu hoàn hàng';
        $successMessage = 'Yêu cầu hoàn hàng đã được gửi thành công. Admin sẽ xem xét trong 24h.';
    } else {
        throw new Exception('Hành động không hợp lệ');
    }
    
    // Lấy danh sách sản phẩm trong đơn hàng
    $detailStmt = $conn->prepare("
        SELECT MASP, SOLUONG 
        FROM chitietdonhang 
        WHERE MADONHANG = ?
    ");
    $detailStmt->execute([$orderCode]);
    $orderDetails = $detailStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Chỉ hoàn lại tồn kho khi HỦY ĐỚN (không phải hoàn hàng)
    if ($action === 'cancel') {
        // Hoàn lại số lượng tồn kho cho từng sản phẩm
        foreach ($orderDetails as $detail) {
            $updateStockStmt = $conn->prepare("
                UPDATE sanpham 
                SET SOLUONG = SOLUONG + ? 
                WHERE MASP = ?
            ");
            $updateStockStmt->execute([$detail['SOLUONG'], $detail['MASP']]);
        }
        
        // Nếu đơn hàng có sử dụng voucher, hoàn lại số lượng voucher
        $voucherStmt = $conn->prepare("SELECT MAVOUCHER FROM donhang WHERE MADONHANG = ? AND MAVOUCHER IS NOT NULL");
        $voucherStmt->execute([$orderCode]);
        $voucherUsed = $voucherStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($voucherUsed) {
            // Giảm số lượng đã sử dụng của voucher
            $updateVoucherStmt = $conn->prepare("
                UPDATE voucher 
                SET SOLUONGSUDUNG = SOLUONGSUDUNG - 1 
                WHERE MAVOUCHER = ?
            ");
            $updateVoucherStmt->execute([$voucherUsed['MAVOUCHER']]);
            
            // Xóa record trong bảng voucher_usage nếu có
            $deleteUsageStmt = $conn->prepare("
                DELETE FROM voucher_usage 
                WHERE MAVOUCHER = ? AND MAKH = ? AND MADONHANG = ?
            ");
            $deleteUsageStmt->execute([$voucherUsed['MAVOUCHER'], $userInfo['MAKH'], $orderCode]);
        }
    }
    
    // Cập nhật trạng thái đơn hàng
    $updateOrderStmt = $conn->prepare("
        UPDATE donhang 
        SET TRANGTHAI = ? 
        WHERE MADONHANG = ?
    ");
    $updateOrderStmt->execute([$newStatus, $orderCode]);
    
    // Nếu là yêu cầu hoàn hàng, thêm ghi chú lý do (tùy chọn)
    if ($action === 'return' && isset($_POST['return_reason'])) {
        $reason = $_POST['return_reason'];
        $updateReasonStmt = $conn->prepare("
            UPDATE donhang 
            SET GHICHU = CONCAT(COALESCE(GHICHU, ''), '[YÊU CẦU HOÀN HÀNG] Lý do: ', ?) 
            WHERE MADONHANG = ?
        ");
        $updateReasonStmt->execute([$reason, $orderCode]);
    }
    
    // Commit transaction
    $conn->commit();
    
    echo json_encode([
        'success' => true, 
        'message' => $successMessage
    ]);
    
} catch (Exception $e) {
    // Rollback transaction nếu có lỗi
    $conn->rollback();
    
    echo json_encode([
        'success' => false, 
        'message' => $e->getMessage()
    ]);
}
?>
