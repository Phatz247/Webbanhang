<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . "/../model/database.php";
$db = new database();
$conn = $db->getConnection();

// Xử lý xóa hóa đơn
if (isset($_GET['delete'])) {
    $mahd = $_GET['delete'];
    // Xoá chi tiết hóa đơn
    $stmt = $conn->prepare("DELETE FROM chitiethoadon WHERE MAHD = ?");
    $stmt->execute([$mahd]);
    // Xoá hóa đơn chính
    $stmt = $conn->prepare("DELETE FROM hoadon WHERE MAHD = ?");
    $stmt->execute([$mahd]);
    $alert = "Đã xóa hóa đơn $mahd!";
}

// Lấy danh sách hóa đơn
$stmt = $conn->prepare("
    SELECT hd.*, kh.TENKH, kh.SDT, kh.EMAIL
    FROM hoadon hd
    LEFT JOIN khachhang kh ON hd.MAKH = kh.MAKH
    ORDER BY hd.NGAYLAP DESC
");
$stmt->execute();
$ds_hoadon = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <title>Quản lý hóa đơn online</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <style>
    .table-section {
      background: #fff; border-radius: 8px; padding: 25px; margin-top: 25px; box-shadow: 0 1px 12px rgba(0,0,0,0.04);
    }
    .status-label {padding: 3px 15px; border-radius: 16px; font-weight:600; font-size:1em; display:inline-block;}
    .status-checked {background: #e3ffe9; color: #28a745;}
    .status-pending {background: #fff3cd; color: #856404;}
    .status-cancel {background: #f8d7da; color: #c82333;}
    .status-delivered {background: #cce5ff; color: #004085;}
    .table thead th { vertical-align: middle;}
    .action-buttons {
      display: flex;
      gap: 10px;
      justify-content: center;
      align-items: stretch;
      width: 100%;
    }
    .action-buttons .btn {
      min-width: 110px;
      padding: 13px 0 11px 0;
      font-size: 15px;
      font-weight: 500;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      border-radius: 8px;
      line-height: 1.1;
    }
    .action-buttons .btn i {
      font-size: 18px;
      margin-bottom: 2px;
    }
    .action-buttons .btn-primary {
      background: #1976d2; border: none;
    }
    .action-buttons .btn-primary:hover {
      background: #0d47a1;
    }
    .action-buttons .btn-danger {
      background: #e53935; border: none;
    }
    .action-buttons .btn-danger:hover {
      background: #b71c1c;
    }
    @media (max-width: 700px) {
      .action-buttons { flex-direction: column; gap:8px;}
    }
  </style>
</head>
<body>
<div class="container py-4">
  <div class="table-section">
    <h3 class="mb-3"><i class="fas fa-file-invoice"></i> Danh sách hóa đơn</h3>
    <?php if (!empty($alert)): ?>
      <div class="alert alert-success"><?= htmlspecialchars($alert) ?></div>
    <?php endif; ?>
    <div class="table-responsive">
      <table class="table table-bordered table-striped align-middle">
        <thead>
          <tr>
            <th>Mã hóa đơn</th>
            <th>Khách hàng</th>
            <th>SĐT</th>
            <th>Ngày lập</th>
            <th>Trạng thái</th>
            <th>Tổng tiền</th>
            <th style="width:220px">Thao tác</th>
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
                <a href="?delete=<?= urlencode($hd['MAHD']) ?>"
                   class="btn btn-danger"
                   onclick="return confirm('Bạn muốn xóa hóa đơn này?');" title="Xóa hóa đơn">
                  <i class="fas fa-trash-alt"></i>
                  <span>Xóa</span>
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
  </div>
</div>
</body>
</html>
