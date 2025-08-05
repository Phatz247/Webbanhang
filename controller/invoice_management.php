<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . "/../model/database.php";
$db = new database();
$conn = $db->getConnection();

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Xử lý lọc hóa đơn
$recent = isset($_GET['recent']) && $_GET['recent'] == '1';
if ($recent) {
    $stmt = $conn->prepare("
        SELECT hd.*, kh.TENKH, kh.SDT, kh.EMAIL
        FROM hoadon hd
        LEFT JOIN khachhang kh ON hd.MAKH = kh.MAKH
        ORDER BY hd.NGAYLAP DESC
        LIMIT 10
    ");
} else {
    $stmt = $conn->prepare("
        SELECT hd.*, kh.TENKH, kh.SDT, kh.EMAIL
        FROM hoadon hd
        LEFT JOIN khachhang kh ON hd.MAKH = kh.MAKH
        ORDER BY hd.NGAYLAP DESC
    ");
}
$stmt->execute();
$ds_hoadon = $stmt->fetchAll(PDO::FETCH_ASSOC);
include_once __DIR__ . '/../view/upload/header_admin.php';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <title>Quản lý hóa đơn online</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <style>
    body { background: #f6f6f6; }
    .table-section {
      background: #fff; border-radius: 16px; padding: 32px; margin-top: 32px; box-shadow: 0 2px 18px rgba(0,0,0,0.07);
      max-width: 1200px; margin-left:auto; margin-right:auto;
    }
    .status-label {padding: 5px 18px; border-radius: 16px; font-weight:600; font-size:1em; display:inline-block;}
    .status-checked {background: #e3ffe9; color: #28a745;}
    .status-pending {background: #fff3cd; color: #856404;}
    .status-cancel {background: #f8d7da; color: #c82333;}
    .status-delivered {background: #cce5ff; color: #004085;}
    .action-buttons {
      display: flex;
      gap: 12px;
      justify-content: center;
      align-items: stretch;
      width: 100%;
    }
    .action-buttons .btn {
      min-width: 120px;
      padding: 13px 0 11px 0;
      font-size: 15px;
      font-weight: 500;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      border-radius: 10px;
      line-height: 1.1;
      box-shadow: 0 2px 8px rgba(0,0,0,0.04);
      transition: background 0.2s;
    }
    .action-buttons .btn-primary {
      background: #1976d2; border: none; color: #fff;
    }
    .action-buttons .btn-primary:hover {
      background: #0d47a1;
    }
    .table thead th { vertical-align: middle; background: #f4f8fb; }
    .filter-bar {
      display: flex;
      gap: 16px;
      margin-bottom: 24px;
      justify-content: flex-end;
      flex-wrap: wrap;
    }
    .filter-bar .btn {
      font-size: 16px;
      padding: 10px 24px;
      border-radius: 8px;
      box-shadow: 0 1px 8px rgba(0,0,0,0.04);
      font-weight: 500;
transition: background 0.2s;
    }
    .filter-bar .btn.active, .filter-bar .btn:active {
      background: #1976d2 !important;
      color: #fff !important;
      border: none;
    }
    @media (max-width: 900px){
      .table-section { padding: 12px 2px; max-width: 100%; }
      .filter-bar { justify-content: center; }
      .action-buttons .btn { min-width: 100px; font-size: 14px;}
    }
  </style>
</head>
<body>
<div class="container py-4">
  <div class="table-section shadow-sm">
    <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap">
      <h3 class="mb-0" style="font-weight:700; color:#1976d2;">
        <i class="fas fa-file-invoice"></i> Danh sách hóa đơn
      </h3>
      <div class="filter-bar">
        <a href="/web_3/controller/invoice_management.php" class="btn btn-outline-primary <?= !$recent ? 'active' : '' ?>">
  <i class="fa-solid fa-list"></i> Hiển thị tất cả
</a>
<a href="/web_3/controller/invoice_management.php?recent=1" class="btn btn-outline-primary <?= $recent ? 'active' : '' ?>">
  <i class="fa-solid fa-clock"></i> Hóa đơn gần đây
</a>
      </div>
    </div>
    <div class="table-responsive">
      <table class="table table-bordered table-hover align-middle">
        <thead>
          <tr>
            <th>Mã hóa đơn</th>
            <th>Khách hàng</th>
            <th>SĐT</th>
            <th>Ngày lập</th>
            <th>Trạng thái</th>
            <th>Tổng tiền</th>
            <th style="width:180px">Thao tác</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach($ds_hoadon as $hd):
            $st = $hd['TRANGTHAI'];
            $cls = '';
            if ($st == 'Đã xác nhận') $cls = 'status-checked';
            elseif ($st == 'Chờ xác nhận') $cls = 'status-pending';
            elseif ($st == 'Đã hủy') $cls = 'status-cancel';
            else $cls = 'status-delivered';
          ?>
          <tr>
            <td><?= htmlspecialchars($hd['MAHD']) ?></td>
            <td><?= htmlspecialchars($hd['TENKH']) ?></td>
            <td><?= htmlspecialchars($hd['SDT']) ?></td>
            <td><?= date('d/m/Y H:i', strtotime($hd['NGAYLAP'])) ?></td>
            <td><span class="status-label <?= $cls ?>"><?= htmlspecialchars($st) ?></span></td>
            <td style="color:#c0392b; font-weight:600;"><?= number_format($hd['TONGTIEN']) ?>đ</td>
            <td>
              <div class="action-buttons">
                <a href="/web_3/controller/invoice_detail.php?id=<?= urlencode($hd['MAHD']) ?>"
                   class="btn btn-primary" title="Xem chi tiết">
                  <i class="fas fa-eye"></i>
                  <span>Xem chi tiết</span>
                </a>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php if(empty($ds_hoadon)): ?>
          <tr><td colspan="7" class="text-center text-muted">Chưa có hóa đơn nào.</td></tr>
          <?php endif; ?>
</tbody>
      </table>
    </div>
    <?php if($recent): ?>
      <div class="mt-3 text-end text-secondary" style="font-size:15px;">
        <i class="fa-solid fa-clock"></i> Hiển thị 10 hóa đơn gần đây nhất
      </div>
    <?php endif; ?>
  </div>
</div>
</body>
</html>
