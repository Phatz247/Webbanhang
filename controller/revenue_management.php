<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . "/../model/database.php";
$db = new database();
$conn = $db->getConnection();

// Tổng doanh thu ONLINE (chỉ đơn đã hoàn thành)
$stmt = $conn->query("SELECT SUM(TONGTIEN) as total_revenue FROM donhang WHERE TRANGTHAI='Đã hoàn thành'");
$row = $stmt->fetch(PDO::FETCH_ASSOC);
$tong_doanh_thu = $row['total_revenue'] ?? 0;

// Doanh thu theo tháng ONLINE
$stmt = $conn->query("
  SELECT DATE_FORMAT(NGAYDAT, '%Y-%m') as thang, SUM(TONGTIEN) as doanh_thu
  FROM donhang
  WHERE TRANGTHAI='Đã hoàn thành'
  GROUP BY thang
  ORDER BY thang DESC
");
$doanhthu_theo_thang = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Đơn hàng hoàn thành gần đây ONLINE
$stmt = $conn->query("
  SELECT MADONHANG, NGAYDAT, TONGTIEN, HOTEN
  FROM donhang
  WHERE TRANGTHAI='Đã hoàn thành'
  ORDER BY NGAYDAT DESC
  LIMIT 30
");
$hoadon_ganday = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Top khách hàng mua nhiều nhất ONLINE
$stmt = $conn->query("
    SELECT kh.MAKH, kh.TENKH, COUNT(dh.MADONHANG) AS so_don, SUM(dh.TONGTIEN) AS tong_chi
    FROM donhang dh
    JOIN khachhang kh ON dh.MAKH = kh.MAKH
    WHERE dh.TRANGTHAI = 'Đã hoàn thành'
    GROUP BY kh.MAKH, kh.TENKH
    ORDER BY so_don DESC, tong_chi DESC
    LIMIT 5
");
$top_khachhang = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Top sản phẩm bán chạy nhất ONLINE
$stmt = $conn->query("
    SELECT sp.MASP, sp.TENSP, SUM(ct.SOLUONG) AS so_luong_ban
    FROM chitietdonhang ct
    JOIN donhang dh ON ct.MADONHANG = dh.MADONHANG
    JOIN sanpham sp ON ct.MASP = sp.MASP
    WHERE dh.TRANGTHAI = 'Đã hoàn thành'
    GROUP BY sp.MASP, sp.TENSP
    ORDER BY so_luong_ban DESC
    LIMIT 5
");
$top_sanpham = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <title>Thống kê doanh thu - Đơn hàng Online</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    .table-section { background: #fff; border-radius: 8px; padding: 25px; margin-top: 20px; box-shadow: 0 1px 12px rgba(0,0,0,0.04);}
    .stat {font-size:1.3em; color:#dc3545;}
    h4, h5 {color: #2d3436;}
  </style>
</head>
<body>
<div class="container py-4">
<div class="table-section">
  <h4>📊 Doanh thu đơn hàng Online (chỉ tính Đã hoàn thành)</h4>
  <div class="mb-4">
    <strong>Tổng doanh thu:</strong>
    <span class="stat"><?= number_format($tong_doanh_thu) ?>đ</span>
  </div>
  <h5>Doanh thu theo tháng</h5>
  <div class="table-responsive mb-4">
    <table class="table table-bordered table-striped">
      <thead>
        <tr>
          <th>Tháng</th>
          <th>Doanh thu</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach($doanhthu_theo_thang as $row): ?>
        <tr>
          <td><?= htmlspecialchars($row['thang']) ?></td>
          <td><?= number_format($row['doanh_thu']) ?>đ</td>
        </tr>
        <?php endforeach; ?>
        <?php if(empty($doanhthu_theo_thang)): ?>
        <tr><td colspan="2" class="text-center text-muted">Chưa có doanh thu</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
  <h5>Đơn hàng hoàn thành gần đây</h5>
  <div class="table-responsive mb-4">
    <table class="table table-sm table-hover">
      <thead>
        <tr>
          <th>Mã đơn</th>
          <th>Ngày hoàn thành</th>
          <th>Tổng tiền</th>
          <th>Khách hàng</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach($hoadon_ganday as $dh): ?>
        <tr>
          <td><?= htmlspecialchars($dh['MADONHANG']) ?></td>
          <td><?= date('d/m/Y H:i', strtotime($dh['NGAYDAT'])) ?></td>
          <td><?= number_format($dh['TONGTIEN']) ?>đ</td>
          <td><?= htmlspecialchars($dh['HOTEN']) ?></td>
        </tr>
        <?php endforeach; ?>
        <?php if(empty($hoadon_ganday)): ?>
        <tr><td colspan="4" class="text-center text-muted">Chưa có đơn hàng nào hoàn thành</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
  
  <!-- Thống kê Khách hàng mua nhiều nhất -->
  <h5>Khách hàng mua nhiều nhất</h5>
  <div class="table-responsive mb-4">
    <table class="table table-bordered table-striped">
      <thead>
        <tr>
          <th>Mã khách hàng</th>
          <th>Tên khách hàng</th>
          <th>Số đơn đã mua</th>
          <th>Tổng chi tiêu</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach($top_khachhang as $kh): ?>
        <tr>
          <td><?= htmlspecialchars($kh['MAKH']) ?></td>
          <td><?= htmlspecialchars($kh['TENKH']) ?></td>
          <td><?= $kh['so_don'] ?></td>
          <td><?= number_format($kh['tong_chi']) ?>đ</td>
        </tr>
        <?php endforeach; ?>
        <?php if(empty($top_khachhang)): ?>
        <tr><td colspan="4" class="text-center text-muted">Chưa có dữ liệu</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <!-- Thống kê Sản phẩm bán chạy nhất -->
  <h5>Sản phẩm bán chạy nhất</h5>
  <div class="table-responsive mb-4">
    <table class="table table-bordered table-striped">
      <thead>
        <tr>
          <th>Mã sản phẩm</th>
          <th>Tên sản phẩm</th>
          <th>Số lượng bán</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach($top_sanpham as $sp): ?>
        <tr>
          <td><?= htmlspecialchars($sp['MASP']) ?></td>
          <td><?= htmlspecialchars($sp['TENSP']) ?></td>
          <td><?= $sp['so_luong_ban'] ?></td>
        </tr>
        <?php endforeach; ?>
        <?php if(empty($top_sanpham)): ?>
        <tr><td colspan="3" class="text-center text-muted">Chưa có dữ liệu</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
</div>
</body>
</html>
