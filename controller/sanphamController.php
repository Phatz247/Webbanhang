<?php
require_once "../model/SanPham.php";

$sanpham = new SanPham();

// Thêm mới sản phẩm khi có dữ liệu POST từ form
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'] ?? '';
    $price = $_POST['price'] ?? 0;
    $image = $_POST['image'] ?? '';
    $description = $_POST['description'] ?? '';
    $sp = new SanPham(null, $name, $price, $image, $description);
    $sp->insert();
    header("Location: SanPhamController.php"); // reload để hiện danh sách mới
    exit;
}

// Lấy danh sách sản phẩm
$dssp = $sanpham->getAll();

// Load view hiển thị
include "../view/sanpham_list.php";
?>
