<?php
session_start();
require_once __DIR__ . "/../model/database.php";
$db = new database();
$conn = $db->getConnection();

$alert = "";
$section = $_GET['section'] ?? 'danhmuc';

// Đăng xuất
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: /web_3/view/login.php');
    exit;
}

// Include giao diện đầu trang
include __DIR__ . '/upload/header_admin.php';

// Include phần nội dung
switch($section) {
    case 'danhmuc':
        include __DIR__ . '/../controller/category_management.php';
        break;
    case 'loaisanpham':
        include __DIR__ . '/../controller/ProductType_Management.php';
        break;
    case 'sanpham':
        include __DIR__ . '/../controller/product_management.php';
        break;
    case 'khuyenmai':
        include __DIR__ . '/../controller/promotion_management.php';
        break;
    case 'voucher':
        include __DIR__ . '/../controller/voucher_management.php';
        break;
    case 'taikhoan':
        include __DIR__ . '/../controller/account_management.php';
        break;
    case 'chitiethoadon':
        include __DIR__ . '/../controller/invoice_management.php';
        break;
    case 'donhang':
        include __DIR__ . '/../controller/order_management.php';
        break;
    case 'thethanhvien':
        include __DIR__ . '/../controller/membercard.php';
        break;
    case 'doanhthu':
        include __DIR__ . '/../controller/revenue_management.php';
        break;
    case 'doanhthu':
        include __DIR__ . '/../controller/voucher_management.php';
        break;
    default:
        echo "Chức năng không hợp lệ!";
        break;
}
include __DIR__ . '/upload/footer_admin.php';
?>
