<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Quản trị hệ thống</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body { background:#f6f6f6; }
    .sidebar { min-width: 240px; background:#232323; color:#fff; height:100vh; overflow-y: auto; }
    .sidebar .nav-link { color:#fff; }
    .sidebar .nav-link.active, .sidebar .nav-link:hover { background:#1d8cf8; color:#fff; }
    .content { padding: 0 30px 30px 30px; }
    .table img { max-width: 50px; border-radius:5px; }
    .color-image-set { border:1px solid #eee; margin-bottom:10px; border-radius:7px; background:#f9f9f9;}
    .color-image-set .row { margin-top:10px; }
    .alert { margin-top: 20px; transition: opacity 1s ease; }
    .form-section { background: #fff; border-radius: 8px; padding: 20px 25px 25px 25px; box-shadow: 0 1px 12px rgba(0,0,0,0.04);}
    .table-section { background: #fff; border-radius: 8px; padding: 25px; margin-top: 20px; box-shadow: 0 1px 12px rgba(0,0,0,0.04);}
    .badge { font-size: .88em;}
    .size-custom-input input[type="number"] {
      min-width: 60px;
      height: 40px;
      font-size: 16px;
      text-align: center;
      margin-bottom: 5px;
    }
    .size-custom-input .col {
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: flex-end;
    }
    .size-custom-input label {
      font-size: 15px;
      margin-bottom: 4px;
    }
    .alert-fixed {
      position: fixed;
      top: 20px;
      right: 40px;
      min-width: 240px;
      z-index: 9999;
      font-size: 16px;
      padding: 12px 18px;
      border-radius: 8px;
      box-shadow: 0 4px 20px rgba(0,0,0,0.11);
      font-weight: 600;
      letter-spacing: 0.2px;
    }
    .btn-action { min-width: 70px; }
    .d-action { display: flex; gap: 12px; justify-content: center; }
    .delete-mode-toggle { margin-bottom: 15px; }
    .filter-section { background: #f8f9fa; padding: 15px; border-radius: 5px; margin-bottom: 20px; }
    .delete-checkbox-section { background: #fff3cd; padding: 15px; border-radius: 5px; margin-bottom: 15px; border: 1px solid #ffeaa7; }
    .price-original { text-decoration: line-through; color: #6c757d; }
    .price-sale { color: #dc3545; font-weight: bold; }
  </style>
</head>
<body>
<div class="d-flex">
  <div class="sidebar d-flex flex-column p-3">
    <h3 class="mb-4 text-center">Admin Panel</h3>
    <ul class="nav nav-pills flex-column mb-auto">
      <li class="nav-item"><a href="?section=danhmuc" class="nav-link <?= $section=='danhmuc'?'active':'' ?>">Danh mục</a></li>
      <li><a href="?section=loaisanpham" class="nav-link <?= $section=='loaisanpham'?'active':'' ?>">Loại sản phẩm</a></li>
      <li><a href="?section=sanpham" class="nav-link <?= $section=='sanpham'?'active':'' ?>">Sản phẩm</a></li>
      <li><a href="?section=khuyenmai" class="nav-link <?= $section=='khuyenmai'?'active':'' ?>">Khuyến mãi</a></li>
      <li><a href="?section=voucher" class="nav-link <?= $section=='voucher'?'active':'' ?>">Voucher</a></li>
      <li><a href="?section=chitiethoadon" class="nav-link <?= $section=='chitiethoadon'?'active':'' ?>">Hóa Đơn</a></li>
      <li><a href="?section=donhang" class="nav-link <?= $section=='taikhoan'?'active':'' ?>">Đơn hàng</a></li>
      <li><a href="?section=taikhoan" class="nav-link <?= $section=='taikhoan'?'active':'' ?>">Tài khoản</a></li>
      <li><a href="?section=thethanhvien" class="nav-link <?= $section=='thethanhvien'?'active':'' ?>">Thẻ thành viên</a></li>
      <li><a href="?section=doanhthu" class="nav-link <?= $section=='doanhthu'?'active':'' ?>">Doanh thu</a></li>
    </ul>
    <a href="?logout=1" class="btn btn-light mt-4">Đăng Xuất</a>
  </div>
  <div class="content flex-grow-1">
