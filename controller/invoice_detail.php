<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . "/../model/database.php";
$db = new database();
$conn = $db->getConnection();

$mahd = $_GET['id'] ?? '';
$hoadon = null;
$sanphams = [];

if ($mahd) {
    $stmt = $conn->prepare("
        SELECT hd.*, kh.TENKH, kh.SDT, kh.EMAIL, kh.DIACHI
        FROM hoadon hd
        LEFT JOIN khachhang kh ON hd.MAKH = kh.MAKH
        WHERE hd.MAHD = ?
    ");
    $stmt->execute([$mahd]);
    $hoadon = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($hoadon) {
        $stmt = $conn->prepare("
            SELECT sp.MASP, sp.TENSP, cthd.SOLUONG, cthd.DONGIA, (cthd.SOLUONG * cthd.DONGIA) as THANHTIEN
            FROM chitiethoadon cthd
            LEFT JOIN sanpham sp ON cthd.MASP = sp.MASP
            WHERE cthd.MAHD = ?
        ");
        $stmt->execute([$mahd]);
        $sanphams = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <title>Chi tiết hóa đơn<?= $hoadon ? " #".htmlspecialchars($hoadon['MAHD']) : "" ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    .table-section { background: #fff; border-radius: 8px; padding: 25px; margin-top: 20px; box-shadow: 0 1px 12px rgba(0,0,0,0.04);}
    .hd-title {color: #dc3545;}
    .hd-meta {font-size: 1.1em;}
    .status-label {
      padding: 3px 15px;
      border-radius: 16px;
      font-weight: 600;
      font-size: 1em;
      display: inline-block;
    }
    .status-checked { background: #e3ffe9; color: #28a745;}
    .status-pending { background: #fff3cd; color: #856404;}
    .status-cancel { background: #f8d7da; color: #c82333;}
    .status-delivered { background: #cce5ff; color: #004085;}
  </style>
</head>
<body>
<div class="container py-4">
  <div class="table-section">
    <h4 class="hd-title mb-3">
      🧾 Chi tiết hóa đơn <?= $hoadon ? "#".htmlspecialchars($hoadon['MAHD']) : "" ?>
    </h4>
    <?php if (!$mahd): ?>
      <div class="alert alert-info">Chưa chọn hóa đơn nào.</div>
    <?php elseif (!$hoadon): ?>
      <div class="alert alert-danger">Không tìm thấy mã hóa đơn này trong hệ thống!</div>
    <?php else: ?>
      <div class="row mb-4">
        <div class="col-md-6">
          <div class="hd-meta">
            <strong>Khách hàng:</strong> <?= htmlspecialchars($hoadon['TENKH']) ?><br>
            <strong>SĐT:</strong> <?= htmlspecialchars($hoadon['SDT']) ?><br>
            <strong>Email:</strong> <?= htmlspecialchars($hoadon['EMAIL']) ?><br>
            <strong>Địa chỉ:</strong> <?= htmlspecialchars($hoadon['DIACHI']) ?>
          </div>
        </div>
        <div class="col-md-6">
          <div class="hd-meta">
            <strong>Ngày lập:</strong> <?= date('d/m/Y H:i', strtotime($hoadon['NGAYLAP'])) ?><br>
            <strong>Trạng thái:</strong>
            <?php
              $st = $hoadon['TRANGTHAI'];
$cls = '';
              if ($st == 'Đã xác nhận') $cls = 'status-checked';
              elseif ($st == 'Chờ xác nhận') $cls = 'status-pending';
              elseif ($st == 'Đã hủy') $cls = 'status-cancel';
              else $cls = 'status-delivered';
            ?>
            <span class="status-label <?= $cls ?>"><?= htmlspecialchars($st) ?></span><br>
            <strong>Tổng tiền:</strong>
            <span style="font-size:1.3em;color:#198754"><?= number_format($hoadon['TONGTIEN']) ?>đ</span>
          </div>
        </div>
      </div>
      <h5>Danh sách sản phẩm</h5>
      <div class="table-responsive mb-3">
        <table class="table table-bordered table-striped">
          <thead>
            <tr>
              <th>Mã SP</th>
              <th>Tên sản phẩm</th>
              <th>Số lượng</th>
              <th>Đơn giá</th>
              <th>Thành tiền</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach($sanphams as $sp): ?>
            <tr>
              <td><?= htmlspecialchars($sp['MASP']) ?></td>
              <td><?= htmlspecialchars($sp['TENSP']) ?></td>
              <td><?= $sp['SOLUONG'] ?></td>
              <td><?= number_format($sp['DONGIA']) ?>đ</td>
              <td><?= number_format($sp['THANHTIEN']) ?>đ</td>
            </tr>
            <?php endforeach; ?>
            <?php if(empty($sanphams)): ?>
            <tr><td colspan="5" class="text-center text-muted">Không có sản phẩm nào</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
    <a href="/web_3/controller/invoice_management.php" class="btn btn-secondary">← Quay lại danh sách hóa đơn</a>
  </div>
</div>
</body>
</html>