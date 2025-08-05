<?php
session_start();

// Kiểm tra đăng nhập
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

// Lấy thông tin từ session storage (sẽ được gửi qua POST từ JavaScript)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $checkout_data = json_decode($_POST['checkout_data'] ?? '{}', true);
    
    if (!empty($checkout_data)) {
        // Khôi phục dữ liệu form (không bao gồm selected_items để tránh duplicate)
        $_SESSION['restore_checkout'] = [
            'payment_method' => $checkout_data['payment_method'] ?? '',
            'notes' => $checkout_data['notes'] ?? '',
            'voucher_code' => $checkout_data['voucher_code'] ?? ''
        ];
        
        // Redirect về checkout với thông báo
        header('Location: checkout.php?return=profile&restored=1');
        exit;
    }
}

// Nếu không có dữ liệu, redirect về trang chủ
header('Location: index.php');
exit;
?>
