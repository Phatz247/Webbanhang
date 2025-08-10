<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . "/../model/database.php";
$db = new database();
$conn = $db->getConnection();

// Tổng doanh thu ONLINE (chỉ đơn đã hoàn thành)
$stmt = $conn->query("SELECT SUM(TONGTIEN) as total_revenue FROM donhang WHERE TRANGTHAI='Đã hoàn thành'");
$row = $stmt->fetch(PDO::FETCH_ASSOC);
$tong_doanh_thu = $row['total_revenue'] ?? 0;

// Tổng số đơn hàng hoàn thành
$stmt = $conn->query("SELECT COUNT(*) as total_orders FROM donhang WHERE TRANGTHAI='Đã hoàn thành'");
$row = $stmt->fetch(PDO::FETCH_ASSOC);
$tong_don_hang = $row['total_orders'] ?? 0;

// Doanh thu hôm nay
$stmt = $conn->query("
  SELECT SUM(TONGTIEN) as today_revenue 
  FROM donhang 
  WHERE TRANGTHAI='Đã hoàn thành' AND DATE(NGAYDAT) = CURDATE()
");
$row = $stmt->fetch(PDO::FETCH_ASSOC);
$doanh_thu_hom_nay = $row['today_revenue'] ?? 0;

// Doanh thu tháng này
$stmt = $conn->query("
  SELECT SUM(TONGTIEN) as month_revenue 
  FROM donhang 
  WHERE TRANGTHAI='Đã hoàn thành' 
  AND YEAR(NGAYDAT) = YEAR(CURDATE()) 
  AND MONTH(NGAYDAT) = MONTH(CURDATE())
");
$row = $stmt->fetch(PDO::FETCH_ASSOC);
$doanh_thu_thang_nay = $row['month_revenue'] ?? 0;

// Doanh thu theo tháng (12 tháng gần nhất)
$stmt = $conn->query("
  SELECT DATE_FORMAT(NGAYDAT, '%Y-%m') as thang, 
         DATE_FORMAT(NGAYDAT, '%m/%Y') as thang_display,
         SUM(TONGTIEN) as doanh_thu
  FROM donhang
  WHERE TRANGTHAI='Đã hoàn thành'
  AND NGAYDAT >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
  GROUP BY thang, thang_display
  ORDER BY thang ASC
");
$doanhthu_theo_thang = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Doanh thu theo tuần (8 tuần gần nhất)
$stmt = $conn->query("
  SELECT WEEK(NGAYDAT, 1) as tuan,
         YEAR(NGAYDAT) as nam,
         CONCAT('Tuần ', WEEK(NGAYDAT, 1), '/', YEAR(NGAYDAT)) as tuan_display,
         SUM(TONGTIEN) as doanh_thu
  FROM donhang
  WHERE TRANGTHAI='Đã hoàn thành'
  AND NGAYDAT >= DATE_SUB(CURDATE(), INTERVAL 8 WEEK)
  GROUP BY tuan, nam, tuan_display
  ORDER BY nam ASC, tuan ASC
");
$doanhthu_theo_tuan = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Đơn hàng hoàn thành gần đây ONLINE
$stmt = $conn->query("
  SELECT MADONHANG, NGAYDAT, TONGTIEN, HOTEN
  FROM donhang
  WHERE TRANGTHAI='Đã hoàn thành'
  ORDER BY NGAYDAT DESC
  LIMIT 10
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Thống Kê Doanh Thu</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .dashboard-header {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            color: white;
            text-align: center;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }
        
        .stat-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            border: none;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.15);
        }
        
        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
            margin-bottom: 10px;
            background: linear-gradient(45deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .stat-label {
            color: #6c757d;
            font-size: 1.1rem;
            font-weight: 500;
        }
        
        .stat-icon {
            font-size: 3rem;
            margin-bottom: 15px;
            opacity: 0.7;
        }
        
        .chart-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }
        
        .table-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }
        
        .table-card h5 {
            color: #495057;
            font-weight: 600;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #e9ecef;
        }
        
        .table {
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
        }
        
        .table thead th {
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
            border: none;
            font-weight: 600;
            padding: 15px;
        }
        
        .table tbody tr {
            transition: background-color 0.3s ease;
        }
        
        .table tbody tr:hover {
            background-color: #f8f9fa;
        }
        
        .table tbody td {
            padding: 12px 15px;
            vertical-align: middle;
        }
        
        .btn-chart-toggle {
            background: linear-gradient(45deg, #667eea, #764ba2);
            border: none;
            border-radius: 25px;
            padding: 10px 25px;
            color: white;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .btn-chart-toggle:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }
        
        .btn-chart-toggle.active {
            background: linear-gradient(45deg, #28a745, #20c997);
        }
        
        @media (max-width: 768px) {
            .stat-number {
                font-size: 2rem;
            }
            .dashboard-header {
                padding: 20px;
            }
            .stat-card, .chart-card, .table-card {
                padding: 20px;
            }
        }
        
        .gradient-text {
            background: linear-gradient(45deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
    </style>
</head>
<body>
<div class="container-fluid py-4">
    <!-- Header -->
    <div class="dashboard-header">
        <h1><i class="fas fa-chart-line me-3"></i>Thống Kê Doanh Thu</h1>
        <p class="mb-0">Theo dõi hiệu suất kinh doanh trực tuyến</p>
    </div>

    <!-- Thống kê tổng quan -->
    <div class="row">
        <div class="col-xl-3 col-md-6">
            <div class="stat-card text-center">
                <div class="stat-icon text-primary">
                    <i class="fas fa-money-bill-wave"></i>
                </div>
                <div class="stat-number"><?= number_format($tong_doanh_thu) ?>đ</div>
                <div class="stat-label">Tổng Doanh Thu</div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="stat-card text-center">
                <div class="stat-icon text-success">
                    <i class="fas fa-shopping-cart"></i>
                </div>
                <div class="stat-number"><?= number_format($tong_don_hang) ?></div>
                <div class="stat-label">Đơn Hàng Hoàn Thành</div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="stat-card text-center">
                <div class="stat-icon text-info">
                    <i class="fas fa-calendar-day"></i>
                </div>
                <div class="stat-number"><?= number_format($doanh_thu_hom_nay) ?>đ</div>
                <div class="stat-label">Doanh Thu Hôm Nay</div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="stat-card text-center">
                <div class="stat-icon text-warning">
                    <i class="fas fa-calendar-alt"></i>
                </div>
                <div class="stat-number"><?= number_format($doanh_thu_thang_nay) ?>đ</div>
                <div class="stat-label">Doanh Thu Tháng Này</div>
            </div>
        </div>
    </div>

    <!-- Biểu đồ doanh thu -->
    <div class="row">
        <div class="col-12">
            <div class="chart-card">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h5 class="gradient-text mb-0"><i class="fas fa-chart-area me-2"></i>Biểu Đồ Doanh Thu</h5>
                    <div>
                        <button class="btn btn-chart-toggle active me-2" onclick="toggleChart('month')" id="btnMonth">
                            <i class="fas fa-calendar-alt me-1"></i>Theo Tháng
                        </button>
                        <button class="btn btn-chart-toggle" onclick="toggleChart('week')" id="btnWeek">
                            <i class="fas fa-calendar-week me-1"></i>Theo Tuần
                        </button>
                    </div>
                </div>
                <div style="height: 400px;">
                    <canvas id="revenueChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Bảng thống kê -->
    <div class="row">
        <!-- Đơn hàng gần đây -->
        <div class="col-lg-6">
            <div class="table-card">
                <h5><i class="fas fa-clock me-2"></i>Đơn Hàng Hoàn Thành Gần Đây</h5>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Mã Đơn</th>
                                <th>Ngày</th>
                                <th>Tổng Tiền</th>
                                <th>Khách Hàng</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($hoadon_ganday as $dh): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($dh['MADONHANG']) ?></strong></td>
                                <td><?= date('d/m/Y', strtotime($dh['NGAYDAT'])) ?></td>
                                <td><span class="text-success fw-bold"><?= number_format($dh['TONGTIEN']) ?>đ</span></td>
                                <td><?= htmlspecialchars($dh['HOTEN']) ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if(empty($hoadon_ganday)): ?>
                            <tr><td colspan="4" class="text-center text-muted py-4">Chưa có đơn hàng nào hoàn thành</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Top khách hàng -->
        <div class="col-lg-6">
            <div class="table-card">
                <h5><i class="fas fa-users me-2"></i>Top Khách Hàng VIP</h5>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Tên Khách Hàng</th>
                                <th>Số Đơn</th>
                                <th>Tổng Chi Tiêu</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($top_khachhang as $index => $kh): ?>
                            <tr>
                                <td>
                                    <?php if($index == 0): ?>
                                        <i class="fas fa-crown text-warning me-1"></i>
                                    <?php endif; ?>
                                    <strong><?= htmlspecialchars($kh['TENKH']) ?></strong>
                                </td>
                                <td><span class="badge bg-primary"><?= $kh['so_don'] ?></span></td>
                                <td><span class="text-success fw-bold"><?= number_format($kh['tong_chi']) ?>đ</span></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if(empty($top_khachhang)): ?>
                            <tr><td colspan="3" class="text-center text-muted py-4">Chưa có dữ liệu</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Top sản phẩm -->
    <div class="row">
        <div class="col-12">
            <div class="table-card">
                <h5><i class="fas fa-star me-2"></i>Sản Phẩm Bán Chạy Nhất</h5>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Xếp Hạng</th>
                                <th>Mã Sản Phẩm</th>
                                <th>Tên Sản Phẩm</th>
                                <th>Số Lượng Bán</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($top_sanpham as $index => $sp): ?>
                            <tr>
                                <td>
                                    <?php 
                                    $rankIcons = ['fas fa-medal text-warning', 'fas fa-medal text-secondary', 'fas fa-medal text-info'];
                                    $icon = $rankIcons[$index] ?? 'fas fa-hashtag text-muted';
                                    ?>
                                    <i class="<?= $icon ?> me-1"></i>
                                    <strong><?= $index + 1 ?></strong>
                                </td>
                                <td><code><?= htmlspecialchars($sp['MASP']) ?></code></td>
                                <td><strong><?= htmlspecialchars($sp['TENSP']) ?></strong></td>
                                <td><span class="badge bg-success fs-6"><?= number_format($sp['so_luong_ban']) ?></span></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if(empty($top_sanpham)): ?>
                            <tr><td colspan="4" class="text-center text-muted py-4">Chưa có dữ liệu</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Dữ liệu cho biểu đồ
const monthlyData = {
    labels: [<?php echo "'" . implode("','", array_column($doanhthu_theo_thang, 'thang_display')) . "'"; ?>],
    datasets: [{
        label: 'Doanh thu theo tháng',
        data: [<?php echo implode(',', array_column($doanhthu_theo_thang, 'doanh_thu')); ?>],
        borderColor: 'rgb(102, 126, 234)',
        backgroundColor: 'rgba(102, 126, 234, 0.1)',
        borderWidth: 3,
        fill: true,
        tension: 0.4
    }]
};

const weeklyData = {
    labels: [<?php echo "'" . implode("','", array_column($doanhthu_theo_tuan, 'tuan_display')) . "'"; ?>],
    datasets: [{
        label: 'Doanh thu theo tuần',
        data: [<?php echo implode(',', array_column($doanhthu_theo_tuan, 'doanh_thu')); ?>],
        borderColor: 'rgb(118, 75, 162)',
        backgroundColor: 'rgba(118, 75, 162, 0.1)',
        borderWidth: 3,
        fill: true,
        tension: 0.4
    }]
};

// Khởi tạo biểu đồ
const ctx = document.getElementById('revenueChart').getContext('2d');
let currentChart = new Chart(ctx, {
    type: 'line',
    data: monthlyData,
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: true,
                position: 'top',
                labels: {
                    usePointStyle: true,
                    padding: 20,
                    font: {
                        size: 14,
                        weight: 'bold'
                    }
                }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                grid: {
                    color: 'rgba(0,0,0,0.1)'
                },
                ticks: {
                    callback: function(value) {
                        return new Intl.NumberFormat('vi-VN', {
                            style: 'currency',
                            currency: 'VND'
                        }).format(value);
                    }
                }
            },
            x: {
                grid: {
                    color: 'rgba(0,0,0,0.05)'
                }
            }
        },
        elements: {
            point: {
                radius: 6,
                hoverRadius: 8,
                backgroundColor: '#fff',
                borderWidth: 3
            }
        }
    }
});

// Chuyển đổi biểu đồ
function toggleChart(type) {
    const btnMonth = document.getElementById('btnMonth');
    const btnWeek = document.getElementById('btnWeek');
    
    if (type === 'month') {
        currentChart.data = monthlyData;
        btnMonth.classList.add('active');
        btnWeek.classList.remove('active');
    } else {
        currentChart.data = weeklyData;
        btnWeek.classList.add('active');
        btnMonth.classList.remove('active');
    }
    
    currentChart.update('active');
}

// Animation khi load trang
document.addEventListener('DOMContentLoaded', function() {
    const statCards = document.querySelectorAll('.stat-card');
    statCards.forEach((card, index) => {
        setTimeout(() => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(20px)';
            card.style.transition = 'all 0.6s ease';
            
            setTimeout(() => {
                card.style.opacity = '1';
                card.style.transform = 'translateY(0)';
            }, 100);
        }, index * 100);
    });
});
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>