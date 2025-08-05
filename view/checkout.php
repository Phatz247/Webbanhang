<?php
session_start();
require_once __DIR__ . '/../model/database.php';
require_once __DIR__ . '/../model/VoucherHelper.php';

$db   = new database();
$conn = $db->getConnection();
$voucherHelper = new VoucherHelper($conn);

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Kiểm tra đăng nhập
$user = $_SESSION['user'] ?? null;
if (!$user) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $_SESSION['pending_order'] = $_POST;
    }
    header('Location: login.php?redirect=checkout.php');
    exit;
}

// Lấy thông tin khách hàng từ database
$stmt = $conn->prepare("SELECT TENKH, SDT, DIACHI FROM khachhang WHERE MAKH = ?");
$stmt->execute([$user['MAKH']]);
$khachhang = $stmt->fetch(PDO::FETCH_ASSOC);

// Nếu quay lại từ login và có pending_order, khôi phục dữ liệu POST
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_SESSION['pending_order'])) {
    $_POST = $_SESSION['pending_order'];
    unset($_SESSION['pending_order']);
}

// Khôi phục checkout state từ profile (nếu có)
$restored_from_profile = false;
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_SESSION['restore_checkout'])) {
    $restore_data = $_SESSION['restore_checkout'];
    // Chỉ khôi phục form data, không khôi phục selected_items để tránh duplicate
    $_POST['payment_method'] = $restore_data['payment_method'];
    $_POST['notes'] = $restore_data['notes'];
    if (!empty($restore_data['voucher_code'])) {
        $_POST['voucher_code'] = $restore_data['voucher_code'];
        $_POST['apply_voucher'] = true; // Trigger voucher application
    }
    unset($_SESSION['restore_checkout']);
    $restored_from_profile = true;
    // Set REQUEST_METHOD để xử lý voucher
    $_SERVER['REQUEST_METHOD'] = 'POST';
}

// Kiểm tra có sản phẩm được chọn không (trừ khi quay lại từ profile)
if (!$restored_from_profile && (($_SERVER['REQUEST_METHOD'] !== 'POST') || empty($_POST['selected_items']))) {
    header('Location: shoppingcart.php');
    exit;
}

// Nếu không phải từ profile thì cần selected_items
if (!$restored_from_profile) {
    $selected_keys = (array)$_POST['selected_items'];
    if (empty($selected_keys)) {
        header('Location: shoppingcart.php');
        exit;
    }
} else {
    // Nếu từ profile, lấy tất cả items từ cart
    $selected_keys = array_keys($_SESSION['cart']);
}

// Lấy thông tin chi tiết các sản phẩm đã chọn từ database (cập nhật giá mới nhất)
$checkout_items = [];
$total_amount = 0;

foreach ($selected_keys as $key) {
    if (!isset($_SESSION['cart'][$key])) continue;
    $cart_item = $_SESSION['cart'][$key];
    $stmt = $conn->prepare("
        SELECT s.MASP, s.TENSP, s.GIA, s.HINHANH, s.MAUSAC, s.KICHTHUOC, s.SOLUONG,
               ct.gia_khuyenmai, ct.giam_phantram
        FROM sanpham s
        LEFT JOIN chitietctkm ct ON s.MASP = ct.MASP
        LEFT JOIN chuongtrinhkhuyenmai ctkm ON ct.MACTKM = ctkm.MACTKM 
            AND NOW() BETWEEN ctkm.NGAYBATDAU AND ctkm.NGAYKETTHUC
        WHERE s.MASP = ? AND s.KICHTHUOC = ?
        LIMIT 1
    ");
    $stmt->execute([$cart_item['masp'], $cart_item['kichthuoc']]);
    $sp = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$sp) continue;
    if ($sp['SOLUONG'] < $cart_item['soluong']) {
        $_SESSION['checkout_error'] = "Sản phẩm {$sp['TENSP']} (Size: {$sp['KICHTHUOC']}) không đủ hàng!";
        header('Location: shoppingcart.php');
        exit;
    }
    
    // Tính giá cuối cùng (ưu tiên giá từ cart đã tính khuyến mãi)
    $final_price = $sp['GIA']; // Giá gốc mặc định
    
    // Debug: Log thông tin để kiểm tra
    error_log("Product: " . $sp['TENSP']);
    error_log("Original price from DB: " . $sp['GIA']);
    error_log("Cart gia_ban: " . ($cart_item['gia_ban'] ?? 'none'));
    error_log("DB gia_khuyenmai: " . ($sp['gia_khuyenmai'] ?? 'none'));
    error_log("DB giam_phantram: " . ($sp['giam_phantram'] ?? 'none'));
    
    // Nếu cart có gia_ban (giá đã tính khuyến mãi) thì dùng giá đó
    if (isset($cart_item['gia_ban']) && $cart_item['gia_ban'] > 0) {
        $final_price = $cart_item['gia_ban'];
        error_log("Using cart price: " . $final_price);
    } else {
        // Nếu không có trong cart, tính từ database
        if ($sp['gia_khuyenmai']) {
            $final_price = $sp['gia_khuyenmai']; // Giá khuyến mãi cố định
            error_log("Using DB gia_khuyenmai: " . $final_price);
        } elseif ($sp['giam_phantram']) {
            $final_price = $sp['GIA'] * (1 - $sp['giam_phantram']/100); // Giá giảm theo %
            error_log("Using DB giam_phantram: " . $final_price);
        } else {
            error_log("Using original price: " . $final_price);
        }
    }
    
    // Kiểm tra xem có khuyến mãi hay không
    $has_sale = (isset($cart_item['has_promotion']) && $cart_item['has_promotion']) || 
                ($sp['gia_khuyenmai'] || $sp['giam_phantram']);
    
    $checkout_item = [
        'key' => $key,
        'masp' => $sp['MASP'],
        'tensp' => $sp['TENSP'],
        'hinhanh' => $sp['HINHANH'],
        'mausac' => $sp['MAUSAC'],
        'kichthuoc' => $sp['KICHTHUOC'],
        'soluong' => $cart_item['soluong'],
        'original_price' => $sp['GIA'], // Giá gốc để hiển thị
        'final_price' => $final_price,  // Giá cuối cùng để tính toán
        'subtotal' => $final_price * $cart_item['soluong'],
        'has_sale' => $has_sale
    ];
    $checkout_items[] = $checkout_item;
    $total_amount += $checkout_item['subtotal'];
}

if (empty($checkout_items)) {
    $_SESSION['checkout_error'] = 'Không có sản phẩm hợp lệ để thanh toán!';
    header('Location: shoppingcart.php');
    exit;
}

// Xử lý voucher
$applied_voucher = null;
$voucher_discount = 0;
$original_total = $total_amount;
$voucher_errors = [];

// Reset voucher nếu có action remove
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_voucher'])) {
    unset($_SESSION['applied_voucher']);
    $applied_voucher = null;
    $voucher_discount = 0;
    $total_amount = $original_total;
}

// Apply voucher
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['apply_voucher'])) {
    $voucher_code = strtoupper(trim($_POST['voucher_code'] ?? ''));
    
    if (!empty($voucher_code)) {
        try {
            $validation = $voucherHelper->validateVoucher($voucher_code, $user['MAKH'], $original_total);
            
            if ($validation['valid']) {
                $applied_voucher = $validation['voucher'];
                $voucher_discount = $voucherHelper->calculateDiscount($applied_voucher, $original_total);
                $total_amount = $original_total - $voucher_discount;
                $_SESSION['applied_voucher'] = [
                    'code' => $voucher_code,
                    'discount' => $voucher_discount,
                    'voucher_data' => $applied_voucher
                ];
            } else {
                $voucher_errors[] = $validation['message'];
            }
        } catch (Exception $e) {
            $voucher_errors[] = 'Lỗi khi áp dụng voucher: ' . $e->getMessage();
        }
    } else {
        $voucher_errors[] = 'Vui lòng nhập mã voucher';
    }
}

// Khôi phục voucher từ session nếu có (khi không có action mới)
if (!$applied_voucher && isset($_SESSION['applied_voucher']) && 
    !isset($_POST['apply_voucher']) && !isset($_POST['remove_voucher'])) {
    
    $session_voucher = $_SESSION['applied_voucher'];
    try {
        $validation = $voucherHelper->validateVoucher($session_voucher['code'], $user['MAKH'], $original_total);
        
        if ($validation['valid']) {
            $applied_voucher = $validation['voucher'];
            $voucher_discount = $voucherHelper->calculateDiscount($applied_voucher, $original_total);
            $total_amount = $original_total - $voucher_discount;
        } else {
            unset($_SESSION['applied_voucher']);
            $voucher_errors[] = 'Voucher đã hết hạn hoặc không còn hiệu lực';
        }
    } catch (Exception $e) {
        unset($_SESSION['applied_voucher']);
        $voucher_errors[] = 'Lỗi kiểm tra voucher: ' . $e->getMessage();
    }
}

// Lấy danh sách voucher có thể sử dụng
$available_vouchers = [];
try {
    $available_vouchers = $voucherHelper->getAvailableVouchers($user['MAKH'], $original_total);
} catch (Exception $e) {
    error_log("Error getting available vouchers: " . $e->getMessage());
}

// Xử lý form submit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    $payment_method = $_POST['payment_method'] ?? '';
    $notes = trim($_POST['notes'] ?? '');

    $errors = [];
    
    // Kiểm tra thông tin khách hàng từ database
    if (empty($khachhang['TENKH'])) $errors[] = 'Vui lòng cập nhật họ tên trong trang Profile';
    if (empty($khachhang['SDT'])) $errors[] = 'Vui lòng cập nhật số điện thoại trong trang Profile';  
    if (empty($khachhang['DIACHI'])) $errors[] = 'Vui lòng cập nhật địa chỉ trong trang Profile';
    if (empty($payment_method)) $errors[] = 'Vui lòng chọn phương thức thanh toán';

    if (empty($errors)) {
        // Sử dụng thông tin từ database
        $fullname = $khachhang['TENKH'];
        $phone = $khachhang['SDT'];
        $address = $khachhang['DIACHI'];
        
        try {
            // Bắt đầu transaction
            $conn->beginTransaction();
            
            error_log("Starting checkout process for user: " . $user['MAKH']);
            
            $order_code = 'DH' . date('YmdHis') . rand(100, 999);
            $mahd = 'HD' . date('YmdHis') . rand(100, 999);
            
            error_log("Generated order code: " . $order_code);
            
            // Tạo đơn hàng
            $stmt = $conn->prepare("
                INSERT INTO donhang (MADONHANG, MAKH, NGAYDAT, TONGTIEN, TRANGTHAI, HOTEN, SODIENTHOAI, DIACHI, PHUONGTHUCTHANHTOAN, GHICHU, MAVOUCHER, GIATRIGIAM)
                VALUES (?, ?, NOW(), ?, 'Chờ xử lý', ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $order_code,
                $user['MAKH'],
                $total_amount,
                $fullname,
                $phone,
                $address,
                $payment_method,
                $notes,
                $applied_voucher ? $applied_voucher['MAVOUCHER'] : null,
                $voucher_discount
            ]);
            
            // Tạo hóa đơn
            $stmt = $conn->prepare("INSERT INTO hoadon (MAHD, MAKH, NGAYLAP, TONGTIEN, TRANGTHAI) VALUES (?, ?, NOW(), ?, 'Đã xác nhận')");
            $stmt->execute([$mahd, $user['MAKH'], $total_amount]);
            
            // Xử lý từng sản phẩm
            foreach ($checkout_items as $item) {
                error_log("Processing item: " . json_encode($item));
                
                // Thêm vào chi tiết đơn hàng
                $stmt = $conn->prepare("
                    INSERT INTO chitietdonhang (MADONHANG, MASP, KICHTHUOC, SOLUONG, GIA, THANHTIEN)
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $order_code,
                    $item['masp'],
                    $item['kichthuoc'],
                    $item['soluong'],
                    $item['final_price'],
                    $item['subtotal']
                ]);
                
                // Thêm vào chi tiết hóa đơn
                $stmt = $conn->prepare("
    INSERT INTO chitiethoadon (MAHD, MASP, SOLUONG, DONGIA)
    VALUES (?, ?, ?, ?)
    ON DUPLICATE KEY UPDATE
        SOLUONG = SOLUONG + VALUES(SOLUONG)
");
$stmt->execute([
    $mahd,
    $item['masp'],
    $item['soluong'],
    $item['final_price']
]);


                // Cập nhật số lượng sản phẩm
                $stmt = $conn->prepare("
                    UPDATE sanpham 
                    SET SOLUONG = SOLUONG - ? 
                    WHERE MASP = ? AND KICHTHUOC = ? AND SOLUONG >= ?
                ");
                $stmt->execute([
                    $item['soluong'],
                    $item['masp'],
                    $item['kichthuoc'],
                    $item['soluong']
                ]);
                
                // Xóa sản phẩm khỏi giỏ hàng
                unset($_SESSION['cart'][$item['key']]);
            }

            // Áp dụng voucher nếu có (không dùng VoucherHelper để tránh lỗi)
            if ($applied_voucher) {
                try {
                    $stmt = $conn->prepare("
                        INSERT INTO voucher_usage (MAVOUCHER, MAKH, MADONHANG, NGAYSUDUNG, GIATRISUDUNG)
                        VALUES (?, ?, ?, NOW(), ?)
                    ");
                    $stmt->execute([
                        $applied_voucher['MAVOUCHER'],
                        $user['MAKH'],
                        $order_code,
                        $voucher_discount
                    ]);
                    
                    // Cập nhật số lượng đã sử dụng
                    $stmt = $conn->prepare("
                        UPDATE voucher 
                        SET SOLUONGSUDUNG = SOLUONGSUDUNG + 1 
                        WHERE MAVOUCHER = ?
                    ");
                    $stmt->execute([$applied_voucher['MAVOUCHER']]);
                    
                } catch (Exception $e) {
                    error_log("Voucher application error: " . $e->getMessage());
                }
                
                unset($_SESSION['applied_voucher']);
            }

            // Commit transaction
            $conn->commit();
            
            $_SESSION['order_success'] = "Đặt hàng thành công! Mã đơn hàng: {$order_code}";
            header('Location: order_success.php?order=' . $order_code);
            exit;
            
        } catch (Exception $e) {
            // Rollback chỉ khi có transaction active
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }
            
            error_log("Checkout error: " . $e->getMessage());
            error_log("Error trace: " . $e->getTraceAsString());
            
            $errors[] = 'Có lỗi xảy ra khi đặt hàng: ' . $e->getMessage();
        }
    }
}

// Tính phí vận chuyển
$shipping_fee = ($applied_voucher && $applied_voucher['LOAIVOUCHER'] === 'freeship') ? 0 : 30000;
$final_total = $total_amount + $shipping_fee;
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Thanh toán đơn hàng - MENSTA</title>
    <link rel="stylesheet" href="/web_3/view/css/style.css"/>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet" />
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        
        body {
            font-family: "Inter", sans-serif;
            background-color: #f8f9fa;
            min-height: 100vh;
            padding: 20px 0;
        }

        .checkout-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .checkout-header {
            text-align: center;
            margin-bottom: 30px;
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .checkout-header h1 {
            color: #007bff;
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .checkout-header p {
            color: #6c757d;
            font-size: 1rem;
        }

        .checkout-layout {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .checkout-main {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .section-card {
            background: white;
            border-radius: 8px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            border: 1px solid #e9ecef;
        }

        .section-header {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #e9ecef;
        }

        .section-header i {
            font-size: 1.2rem;
            margin-right: 10px;
            color: #007bff;
        }

        .section-header h3 {
            font-size: 1.1rem;
            font-weight: 600;
            color: #495057;
        }

        /* Products Section */
        .product-item {
            display: flex;
            align-items: center;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
            margin-bottom: 15px;
            border: 1px solid #e9ecef;
        }

        .product-image {
            width: 60px;
            height: 60px;
            border-radius: 6px;
            object-fit: cover;
            margin-right: 15px;
            border: 1px solid #dee2e6;
        }

        .product-info {
            flex: 1;
        }

        .product-name {
            font-weight: 600;
            font-size: 0.95rem;
            color: #495057;
            margin-bottom: 5px;
        }

        .product-details {
            font-size: 0.85rem;
            color: #6c757d;
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }

        .product-price {
            text-align: right;
            font-weight: 600;
            color: #dc3545;
            font-size: 0.95rem;
        }

        /* Form Styles */
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group.full-width {
            grid-column: 1 / -1;
        }

        .form-label {
            font-weight: 600;
            color: #495057;
            margin-bottom: 8px;
            font-size: 0.9rem;
        }

        .form-control {
            padding: 12px 15px;
            border: 1px solid #ced4da;
            border-radius: 6px;
            font-size: 0.95rem;
            transition: all 0.2s ease;
            background: white;
        }

        .form-control:focus {
            outline: none;
            border-color: #007bff;
            box-shadow: 0 0 0 2px rgba(0, 123, 255, 0.15);
        }

        /* Customer Info Display */
        .customer-info-display {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }

        .info-group {
            display: flex;
            margin-bottom: 15px;
            align-items: center;
        }

        .info-group:last-child {
            margin-bottom: 0;
        }

        .info-label {
            font-weight: 600;
            color: #495057;
            width: 140px;
            flex-shrink: 0;
            font-size: 0.9rem;
        }

        .info-value {
            flex: 1;
            color: #212529;
            font-size: 0.95rem;
            padding: 8px 12px;
            background: white;
            border: 1px solid #ced4da;
            border-radius: 4px;
        }

        .edit-profile-btn {
            background: #17a2b8;
            color: white;
            text-decoration: none;
            padding: 8px 15px;
            border-radius: 4px;
            font-size: 0.85rem;
            font-weight: 500;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .edit-profile-btn:hover {
            background: #138496;
            color: white;
            text-decoration: none;
        }

        /* Voucher Section */
        .voucher-section {
            border: 2px dashed #ced4da;
            border-radius: 8px;
            padding: 20px;
            background: #f8f9fa;
        }

        .voucher-input-group {
            display: flex;
            gap: 10px;
            margin-bottom: 15px;
        }

        .voucher-input {
            flex: 1;
            padding: 12px 15px;
            border: 1px solid #ced4da;
            border-radius: 6px;
            font-size: 0.95rem;
            text-transform: uppercase;
            font-weight: 600;
        }

        .voucher-apply-btn {
            background: #28a745;
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            white-space: nowrap;
        }

        .voucher-apply-btn:hover {
            background: #218838;
        }

        .voucher-applied {
            background: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 15px;
            border: 1px solid #c3e6cb;
        }

        .voucher-applied-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }

        .voucher-code {
            font-size: 1rem;
            font-weight: 700;
        }

        .voucher-discount {
            font-size: 1.1rem;
            font-weight: 700;
            color: #dc3545;
        }

        .voucher-remove-btn {
            background: #dc3545;
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 4px;
            font-size: 0.8rem;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .voucher-remove-btn:hover {
            background: #c82333;
        }

        /* Order Summary - now integrated in main layout */

        .summary-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid #e9ecef;
            font-size: 0.95rem;
        }

        .summary-item:last-child {
            border-bottom: none;
            border-top: 2px solid #007bff;
            padding-top: 15px;
            margin-top: 10px;
            font-weight: 700;
            font-size: 1.1rem;
        }

        .summary-label {
            color: #6c757d;
        }

        .summary-value {
            font-weight: 600;
            color: #495057;
        }

        .summary-discount {
            color: #28a745;
            font-weight: 600;
        }

        .summary-total {
            color: #007bff;
            font-weight: 700;
        }

        /* Checkout Button */
        .checkout-btn {
            background: #007bff;
            color: white;
            border: none;
            padding: 15px 25px;
            border-radius: 6px;
            font-size: 1.1rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s ease;
            width: 100%;
            margin-top: 15px;
        }

        .checkout-btn:hover {
            background: #0056b3;
        }

        .checkout-btn:disabled {
            background: #6c757d;
            cursor: not-allowed;
        }

        /* Alerts */
        .alert {
            padding: 12px 15px;
            border-radius: 6px;
            margin-bottom: 20px;
            border: 1px solid transparent;
            font-size: 0.9rem;
        }

        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border-color: #f5c6cb;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border-color: #c3e6cb;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .checkout-container {
                padding: 0 15px;
            }
            
            .form-grid {
                grid-template-columns: 1fr;
            }
            
            .product-item {
                flex-direction: column;
                text-align: center;
            }
            
            .product-image {
                width: 80px;
                height: 80px;
                margin-right: 0;
                margin-bottom: 10px;
            }
            
            .voucher-input-group {
                flex-direction: column;
            }
        }

        .available-vouchers {
            font-size: 0.85rem;
            color: #007bff;
            text-decoration: underline;
            cursor: pointer;
            margin-bottom: 10px;
        }

        .voucher-list {
            max-height: 250px;
            overflow-y: auto;
            border: 1px solid #ced4da;
            border-radius: 6px;
            background: white;
            display: none;
        }

        .voucher-item {
            padding: 12px;
            border-bottom: 1px solid #e9ecef;
            cursor: pointer;
            transition: background 0.2s ease;
        }

        .voucher-item:hover {
            background: #f8f9fa;
        }

        .voucher-item:last-child {
            border-bottom: none;
        }

        .voucher-item-code {
            font-weight: 700;
            color: #007bff;
            font-size: 0.9rem;
        }

        .voucher-item-name {
            font-size: 0.85rem;
            color: #495057;
            margin: 3px 0;
        }

        .voucher-item-desc {
            font-size: 0.8rem;
            color: #6c757d;
        }
    </style>
</head>
<body>
    <div class="checkout-container">
        <!-- Header -->
        <div class="checkout-header">
            <h1><i class="fas fa-shopping-cart"></i> Thanh toán đơn hàng</h1>
            <p>Vui lòng kiểm tra thông tin và hoàn tất đơn hàng của bạn</p>
        </div>

        <!-- Error Messages -->
        <?php if (isset($errors) && !empty($errors)): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle"></i>
                <?php foreach ($errors as $err): ?>
                    <div><?php echo htmlspecialchars($err); ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($voucher_errors)): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i> 
                <?php foreach ($voucher_errors as $error): ?>
                    <div><?php echo htmlspecialchars($error); ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- Return from profile notification -->
        <?php if (isset($_GET['return']) && $_GET['return'] === 'profile'): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> 
                Đã cập nhật thông tin thành công! Vui lòng kiểm tra lại thông tin và hoàn tất đơn hàng.
            </div>
        <?php endif; ?>

        <div class="checkout-layout">
            <!-- Main Content -->
            <div class="checkout-main">
                <!-- Products Section -->
                <div class="section-card">
                    <div class="section-header">
                        <i class="fas fa-box-open"></i>
                        <h3>Sản phẩm đã chọn (<?php echo count($checkout_items); ?> sản phẩm)</h3>
                    </div>
                    <?php foreach ($checkout_items as $item): ?>
                        <div class="product-item">
                            <img src="/web_3/view/uploads/<?= htmlspecialchars($item['hinhanh']) ?>" 
                                 class="product-image"
                                 onerror="this.onerror=null;this.src='/web_3/view/uploads/no-image.jpg'"
                                 alt="<?= htmlspecialchars($item['tensp']) ?>">
                            <div class="product-info">
                                <div class="product-name"><?= htmlspecialchars($item['tensp']) ?></div>
                                <div class="product-details">
                                    <span>Màu: <?= htmlspecialchars($item['mausac']) ?></span>
                                    <span>Size: <?= htmlspecialchars($item['kichthuoc']) ?></span>
                                    <span>SL: <?= $item['soluong'] ?></span>
                                </div>
                            </div>
                            <div class="product-price">
                                <?php if ($item['has_sale']): ?>
                                    <div style="font-size: 0.8rem; color: #888; text-decoration: line-through;">
                                        <?= number_format($item['original_price'] * $item['soluong']) ?>đ
                                    </div>
                                    <div style="color: #dc3545; font-weight: 600;">
                                        <?= number_format($item['subtotal']) ?>đ
                                    </div>
                                    <div style="font-size: 0.75rem; color: #28a745;">
                                        <i class="fas fa-tag"></i> SALE
                                    </div>
                                <?php else: ?>
                                    <?= number_format($item['subtotal']) ?>đ
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Customer Information -->
                <div class="section-card">
                    <div class="section-header">
                        <i class="fas fa-user"></i>
                        <h3>Thông tin giao hàng</h3>
                        <div style="margin-left: auto;">
                            <a href="profile.php?return=checkout" class="edit-profile-btn">
                                <i class="fas fa-edit"></i> Chỉnh sửa thông tin
                            </a>
                        </div>
                    </div>
                    <form id="checkoutForm" method="POST">
                        <!-- Hidden inputs for customer info -->
                        <input type="hidden" name="fullname" value="<?= htmlspecialchars($khachhang['TENKH'] ?? '') ?>">
                        <input type="hidden" name="phone" value="<?= htmlspecialchars($khachhang['SDT'] ?? '') ?>">
                        <input type="hidden" name="address" value="<?= htmlspecialchars($khachhang['DIACHI'] ?? '') ?>">
                        
                        <div class="customer-info-display">
                            <div class="info-group">
                                <label class="info-label">Họ và tên:</label>
                                <div class="info-value"><?= htmlspecialchars($khachhang['TENKH'] ?? 'Chưa cập nhật') ?></div>
                            </div>
                            <div class="info-group">
                                <label class="info-label">Số điện thoại:</label>
                                <div class="info-value"><?= htmlspecialchars($khachhang['SDT'] ?? 'Chưa cập nhật') ?></div>
                            </div>
                            <div class="info-group">
                                <label class="info-label">Địa chỉ giao hàng:</label>
                                <div class="info-value"><?= htmlspecialchars($khachhang['DIACHI'] ?? 'Chưa cập nhật') ?></div>
                            </div>
                        </div>
                        
                        <div class="form-grid" style="margin-top: 20px;">
                            <div class="form-group">
                                <label class="form-label">Phương thức thanh toán *</label>
                                <select name="payment_method" class="form-control" required>
                                    <option value="">-- Chọn phương thức thanh toán --</option>
                                    <option value="COD" <?= (($_POST['payment_method'] ?? '') === 'COD') ? 'selected' : '' ?>>
                                        Thanh toán khi nhận hàng (COD)
                                    </option>
                                    <option value="Bank" <?= (($_POST['payment_method'] ?? '') === 'Bank') ? 'selected' : '' ?>>
                                        Chuyển khoản ngân hàng
                                    </option>
                                    <option value="Momo" <?= (($_POST['payment_method'] ?? '') === 'Momo') ? 'selected' : '' ?>>
                                        Ví điện tử MoMo
                                    </option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Ghi chú</label>
                                <input type="text" name="notes" class="form-control"
                                       value="<?= htmlspecialchars($_POST['notes'] ?? '') ?>"
                                       placeholder="Ghi chú đơn hàng (tùy chọn)">
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Voucher Section -->
                <div class="section-card">
                    <div class="section-header">
                        <i class="fas fa-tag"></i>
                        <h3>Mã giảm giá</h3>
                    </div>
                    
                    <div class="voucher-section">
                        <?php if ($applied_voucher): ?>
                            <!-- Applied Voucher -->
                            <div class="voucher-applied">
                                <div class="voucher-applied-content">
                                    <div>
                                        <div class="voucher-code">
                                            <i class="fas fa-ticket-alt"></i>
                                            <?php echo htmlspecialchars($applied_voucher['MAVOUCHER']); ?>
                                        </div>
                                        <div style="font-size: 0.85rem; margin-top: 3px;">
                                            <?php echo htmlspecialchars($applied_voucher['TENVOUCHER'] ?? 'Voucher giảm giá'); ?>
                                        </div>
                                    </div>
                                    <div class="voucher-discount">
                                        -<?php echo number_format($voucher_discount); ?>đ
                                    </div>
                                </div>
                                <form method="POST" style="display: inline;">
                                    <button type="submit" name="remove_voucher" class="voucher-remove-btn">
                                        <i class="fas fa-times"></i> Hủy voucher
                                    </button>
                                </form>
                            </div>
                        <?php else: ?>
                            <!-- Voucher Input -->
                            <form method="POST" id="voucherForm">
                                <div class="voucher-input-group">
                                    <input type="text" 
                                           name="voucher_code" 
                                           class="voucher-input"
                                           placeholder="Nhập mã voucher"
                                           value="<?php echo htmlspecialchars($_POST['voucher_code'] ?? ''); ?>">
                                    <button type="submit" name="apply_voucher" class="voucher-apply-btn">
                                        Áp dụng
                                    </button>
                                </div>
                            </form>
                            
                            <!-- Available Vouchers -->
                            <?php if (!empty($available_vouchers)): ?>
                                <div class="available-vouchers" onclick="toggleVoucherList()">
                                    <i class="fas fa-gift"></i> 
                                    Chọn voucher có sẵn (<?php echo count($available_vouchers); ?> voucher)
                                </div>
                                
                                <div id="voucher-list" class="voucher-list">
                                    <?php foreach ($available_vouchers as $voucher): ?>
                                        <div class="voucher-item" onclick="selectVoucher('<?php echo htmlspecialchars($voucher['code'] ?? ''); ?>')">
                                            <div class="voucher-item-code">
                                                <?php echo htmlspecialchars($voucher['code'] ?? ''); ?>
                                            </div>
                                            <div class="voucher-item-name">
                                                <?php echo htmlspecialchars($voucher['name'] ?? ''); ?>
                                            </div>
                                            <?php if (isset($voucher['description']) && $voucher['description']): ?>
                                                <div class="voucher-item-desc">
                                                    <?php echo htmlspecialchars($voucher['description']); ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Order Summary (moved to bottom) -->
                <div class="section-card">
                    <div class="section-header">
                        <i class="fas fa-receipt"></i>
                        <h3>Tóm tắt đơn hàng</h3>
                    </div>
                    
                    <div class="summary-item">
                        <span class="summary-label">
                            Tạm tính (<?php echo count($checkout_items); ?> sản phẩm):
                        </span>
                        <span class="summary-value"><?php echo number_format($original_total); ?>đ</span>
                    </div>
                    
                    <?php if ($voucher_discount > 0): ?>
                        <div class="summary-item">
                            <span class="summary-label">
                                Voucher <?php echo htmlspecialchars($applied_voucher['MAVOUCHER']); ?>:
                            </span>
                            <span class="summary-discount">-<?php echo number_format($voucher_discount); ?>đ</span>
                        </div>
                    <?php endif; ?>
                    
                    <div class="summary-item">
                        <span class="summary-label">
                            Phí vận chuyển:
                        </span>
                        <span class="summary-value">
                            <?php if ($shipping_fee === 0): ?>
                                <span style="color: #28a745;">Miễn phí</span>
                            <?php else: ?>
                                <?php echo number_format($shipping_fee); ?>đ
                            <?php endif; ?>
                        </span>
                    </div>
                    
                    <div class="summary-item">
                        <span class="summary-label summary-total">
                            Tổng cộng:
                        </span>
                        <span class="summary-total"><?php echo number_format($final_total); ?>đ</span>
                    </div>
                    
                    <button type="button" onclick="submitOrder()" class="checkout-btn">
                        <i class="fas fa-credit-card"></i>
                        Xác nhận đặt hàng
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Toggle voucher list visibility
        function toggleVoucherList() {
            const voucherList = document.getElementById('voucher-list');
            const toggle = document.querySelector('.available-vouchers');
            
            if (voucherList.style.display === 'none' || voucherList.style.display === '') {
                voucherList.style.display = 'block';
                toggle.innerHTML = '<i class="fas fa-gift"></i> Ẩn danh sách voucher';
            } else {
                voucherList.style.display = 'none';
                toggle.innerHTML = '<i class="fas fa-gift"></i> Chọn voucher có sẵn (<?php echo count($available_vouchers); ?> voucher)';
            }
        }

        // Select voucher from list
        function selectVoucher(code) {
            const voucherInput = document.querySelector('input[name="voucher_code"]');
            if (voucherInput) {
                voucherInput.value = code;
                
                // Submit voucher form
                const voucherForm = document.getElementById('voucherForm');
                const applyButton = voucherForm.querySelector('button[name="apply_voucher"]');
                
                // Add loading state
                applyButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang áp dụng...';
                applyButton.disabled = true;
                
                // Submit form
                setTimeout(() => {
                    // Add selected items to voucher form
                    const selectedItems = <?php echo json_encode(array_column($checkout_items, 'key')); ?>;
                    selectedItems.forEach(key => {
                        const hiddenInput = document.createElement('input');
                        hiddenInput.type = 'hidden';
                        hiddenInput.name = 'selected_items[]';
                        hiddenInput.value = key;
                        voucherForm.appendChild(hiddenInput);
                    });
                    
                    const applyInput = document.createElement('input');
                    applyInput.type = 'hidden';
                    applyInput.name = 'apply_voucher';
                    applyInput.value = '1';
                    voucherForm.appendChild(applyInput);
                    voucherForm.submit();
                }, 300);
            }
        }

        // Submit order function
        function submitOrder() {
            const form = document.getElementById('checkoutForm');
            const button = document.querySelector('.checkout-btn');
            
            // Validate form
            const paymentMethod = form.querySelector('select[name="payment_method"]').value;
            
            if (!paymentMethod) {
                alert('Vui lòng chọn phương thức thanh toán!');
                return;
            }
            
            // Check if customer info is complete
            const fullname = form.querySelector('input[name="fullname"]').value.trim();
            const phone = form.querySelector('input[name="phone"]').value.trim();
            const address = form.querySelector('input[name="address"]').value.trim();
            
            if (!fullname || !phone || !address) {
                alert('Thông tin cá nhân chưa đầy đủ! Vui lòng cập nhật thông tin trong trang Profile trước khi đặt hàng.');
                return;
            }
            
            // Confirm order
            const totalAmount = <?php echo $final_total; ?>;
            const confirmMessage = `Xác nhận đặt hàng với tổng tiền ${totalAmount.toLocaleString('vi-VN')}đ?`;
            
            if (!confirm(confirmMessage)) {
                return;
            }
            
            // Add loading state
            button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang xử lý...';
            button.disabled = true;
            
            // Add selected items to form (only when submitting order)
            const selectedItems = <?php echo json_encode(array_column($checkout_items, 'key')); ?>;
            selectedItems.forEach(key => {
                const hiddenInput = document.createElement('input');
                hiddenInput.type = 'hidden';
                hiddenInput.name = 'selected_items[]';
                hiddenInput.value = key;
                form.appendChild(hiddenInput);
            });
            
            // Add place_order input and submit
            const placeOrderInput = document.createElement('input');
            placeOrderInput.type = 'hidden';
            placeOrderInput.name = 'place_order';
            placeOrderInput.value = '1';
            form.appendChild(placeOrderInput);
            
            // Xóa checkout state trước khi submit
            sessionStorage.removeItem('checkout_state');
            
            form.submit();
        }

        // Enhanced form interactions
        document.addEventListener('DOMContentLoaded', function() {
            // Khôi phục checkout state nếu có
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('restored') === '1') {
                // Hiển thị thông báo khôi phục thành công
                const notification = document.createElement('div');
                notification.className = 'alert alert-success';
                notification.innerHTML = '<i class="fas fa-check-circle"></i> Đã khôi phục thông tin checkout thành công!';
                document.querySelector('.checkout-container').insertBefore(notification, document.querySelector('.checkout-header').nextSibling);
                
                // Tự động ẩn thông báo sau 5 giây
                setTimeout(() => {
                    notification.remove();
                }, 5000);
            }
            
            // Lưu checkout state trước khi chuyển trang
            const editProfileBtn = document.querySelector('.edit-profile-btn');
            if (editProfileBtn) {
                editProfileBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    
                    // Thu thập dữ liệu checkout hiện tại (chỉ lưu voucher và form data, không lưu selected_items)
                    const checkoutData = {
                        payment_method: document.querySelector('select[name="payment_method"]').value,
                        notes: document.querySelector('input[name="notes"]').value,
                        voucher_code: document.querySelector('input[name="voucher_code"]') ? document.querySelector('input[name="voucher_code"]').value : '',
                        timestamp: Date.now()
                    };
                    
                    // Lưu vào sessionStorage
                    sessionStorage.setItem('checkout_state', JSON.stringify(checkoutData));
                    
                    // Chuyển đến trang profile
                    window.location.href = this.href;
                });
            }
            
            // Auto uppercase voucher input
            const voucherInput = document.querySelector('input[name="voucher_code"]');
            if (voucherInput) {
                voucherInput.addEventListener('input', function() {
                    this.value = this.value.toUpperCase();
                });
                
                // Enter key support
                voucherInput.addEventListener('keypress', function(e) {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        const applyButton = document.querySelector('button[name="apply_voucher"]');
                        if (applyButton) {
                            applyButton.click();
                        }
                    }
                });
            }
            
            // Handle voucher form submission
            const voucherForm = document.getElementById('voucherForm');
            if (voucherForm) {
                voucherForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    
                    // Add selected items to form
                    const selectedItems = <?php echo json_encode(array_column($checkout_items, 'key')); ?>;
                    selectedItems.forEach(key => {
                        const hiddenInput = document.createElement('input');
                        hiddenInput.type = 'hidden';
                        hiddenInput.name = 'selected_items[]';
                        hiddenInput.value = key;
                        this.appendChild(hiddenInput);
                    });
                    
                    // Add apply voucher flag
                    const applyInput = document.createElement('input');
                    applyInput.type = 'hidden';
                    applyInput.name = 'apply_voucher';
                    applyInput.value = '1';
                    this.appendChild(applyInput);
                    
                    // Submit form
                    this.submit();
                });
            }
            
            // Handle remove voucher forms
            const removeVoucherForms = document.querySelectorAll('form[style*="inline"]');
            removeVoucherForms.forEach(form => {
                if (form.querySelector('button[name="remove_voucher"]')) {
                    form.addEventListener('submit', function(e) {
                        e.preventDefault();
                        
                        // Add selected items to form
                        const selectedItems = <?php echo json_encode(array_column($checkout_items, 'key')); ?>;
                        selectedItems.forEach(key => {
                            const hiddenInput = document.createElement('input');
                            hiddenInput.type = 'hidden';
                            hiddenInput.name = 'selected_items[]';
                            hiddenInput.value = key;
                            this.appendChild(hiddenInput);
                        });
                        
                        // Submit form
                        this.submit();
                    });
                }
            });
            
            // Form validation on input
            const requiredInputs = document.querySelectorAll('select[required]');
            requiredInputs.forEach(input => {
                input.addEventListener('blur', function() {
                    if (!this.value.trim()) {
                        this.style.borderColor = '#dc3545';
                    } else {
                        this.style.borderColor = '#28a745';
                    }
                });
                
                input.addEventListener('change', function() {
                    if (this.value.trim()) {
                        this.style.borderColor = '#28a745';
                    }
                });
            });
        });

        // Prevent double submission
        let isSubmitting = false;
        document.getElementById('checkoutForm').addEventListener('submit', function(e) {
            if (isSubmitting) {
                e.preventDefault();
                return false;
            }
            isSubmitting = true;
        });
    </script>
</body>
</html>