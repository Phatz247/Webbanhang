<?php
// view/voucher_api.php
session_start();
require_once __DIR__ . '/../model/database.php';
require_once __DIR__ . '/../model/VoucherHelper.php';

header('Content-Type: application/json');

$db = new database();
$conn = $db->getConnection();
$voucherHelper = new VoucherHelper($conn);

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$response = ['success' => false, 'message' => ''];

try {
    switch ($action) {
        case 'validate_voucher':
            $voucherCode = strtoupper(trim($_POST['voucher_code'] ?? ''));
            $customerId = $_SESSION['user']['MAKH'] ?? '';
            $orderTotal = floatval($_POST['order_total'] ?? 0);
            
            if (empty($voucherCode)) {
                throw new Exception('Vui lòng nhập mã voucher');
            }
            
            if (empty($customerId)) {
                throw new Exception('Vui lòng đăng nhập để sử dụng voucher');
            }
            
            if ($orderTotal <= 0) {
                throw new Exception('Tổng đơn hàng không hợp lệ');
            }
            
            $validation = $voucherHelper->validateVoucher($voucherCode, $customerId, $orderTotal);
            
            if ($validation['valid']) {
                $voucher = $validation['voucher'];
                $discount = $voucherHelper->calculateDiscount($voucher, $orderTotal);
                $newTotal = $orderTotal - $discount;
                
                $response = [
                    'success' => true,
                    'message' => 'Áp dụng voucher thành công!',
                    'voucher' => [
                        'code' => $voucher['MAVOUCHER'],
                        'name' => $voucher['TENVOUCHER'],
                        'type' => $voucher['LOAIVOUCHER'],
                        'discount' => $discount,
                        'new_total' => $newTotal,
                        'formatted_discount' => number_format($discount) . 'đ',
                        'formatted_new_total' => number_format($newTotal) . 'đ'
                    ]
                ];
            } else {
                throw new Exception($validation['message']);
            }
            break;
            
        case 'get_available_vouchers':
            $customerId = $_SESSION['user']['MAKH'] ?? '';
            $orderTotal = floatval($_GET['order_total'] ?? 0);
            
            if (empty($customerId)) {
                throw new Exception('Vui lòng đăng nhập');
            }
            
            $vouchers = $voucherHelper->getAvailableVouchers($customerId, $orderTotal);
            $formattedVouchers = [];
            
            foreach ($vouchers as $voucher) {
                $info = $voucherHelper->formatVoucherInfo($voucher);
                $discount = $voucherHelper->calculateDiscount($voucher, $orderTotal);
                
                $formattedVouchers[] = [
                    'code' => $voucher['MAVOUCHER'],
                    'name' => $voucher['TENVOUCHER'],
                    'description' => $voucher['MOTA'],
                    'type' => $info['type'],
                    'condition' => $info['condition'],
                    'expires' => $info['expires'],
                    'remaining' => $info['remaining'],
                    'can_use' => $voucher['can_use'] == 1,
                    'discount' => $discount,
                    'formatted_discount' => number_format($discount) . 'đ'
                ];
            }
            
            $response = [
                'success' => true,
                'vouchers' => $formattedVouchers
            ];
            break;
            
        case 'remove_voucher':
            // Xóa voucher khỏi session (khi người dùng hủy áp dụng)
            unset($_SESSION['applied_voucher']);
            $response = [
                'success' => true,
                'message' => 'Đã hủy áp dụng voucher'
            ];
            break;
            
        default:
            throw new Exception('Hành động không hợp lệ');
    }
    
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
?>