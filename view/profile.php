<?php
session_start();
require_once __DIR__ . '/../model/database.php';

if (!isset($_SESSION['username'])) {
    header("Location: /web_3/index.php");
    exit;
}

$db = new Database();
$conn = $db->getConnection();

$username = $_SESSION['username'];

// Xử lý cập nhật thông tin cá nhân
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_profile') {
    $hoten = $_POST['TENKH'] ?? '';
    $email = $_POST['EMAIL'] ?? '';
    $gioitinh = $_POST['GIOITINH'] ?? '';
    $ngaysinh = $_POST['NGAYSINH'] ?? '';
    $diachi = $_POST['DIACHI'] ?? '';
    $sdt = $_POST['SDT'] ?? '';

    $stmt = $conn->prepare("UPDATE khachhang 
        SET TENKH = ?, EMAIL = ?, GIOITINH = ?, NGAYSINH = ?, DIACHI = ?, SDT = ?
        WHERE MAKH = (SELECT MAKH FROM taikhoan WHERE TENDANGNHAP = ?)");
    
    if ($stmt->execute([$hoten, $email, $gioitinh, $ngaysinh, $diachi, $sdt, $username])) {
        $_SESSION['update_success'] = true;
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }
}

// Lấy thông tin người dùng
$stmt = $conn->prepare("SELECT t.TENDANGNHAP, t.MAKH, k.TENKH, k.EMAIL, k.GIOITINH, k.NGAYSINH, k.DIACHI, k.SDT
    FROM taikhoan t 
    JOIN khachhang k ON t.MAKH = k.MAKH
    WHERE t.TENDANGNHAP = ?");
$stmt->execute([$username]);
$userInfo = $stmt->fetch(PDO::FETCH_ASSOC);

// Lấy danh sách đơn hàng của khách hàng
$orderStmt = $conn->prepare("
    SELECT 
        dh.MADONHANG,
        dh.NGAYDAT,
        dh.TONGTIEN,
        dh.TRANGTHAI,
        dh.PHUONGTHUCTHANHTOAN,
        COUNT(ct.MASP) as SO_SANPHAM,
        SUM(ct.SOLUONG) as TONG_SOLUONG
    FROM donhang dh
    LEFT JOIN chitietdonhang ct ON dh.MADONHANG = ct.MADONHANG
    WHERE dh.MAKH = ?
    GROUP BY dh.MADONHANG, dh.NGAYDAT, dh.TONGTIEN, dh.TRANGTHAI, dh.PHUONGTHUCTHANHTOAN
    ORDER BY dh.NGAYDAT DESC
");
$orderStmt->execute([$userInfo['MAKH']]);
$orders = $orderStmt->fetchAll(PDO::FETCH_ASSOC);

// Lấy danh sách voucher còn khả dụng
$voucherStmt = $conn->prepare("
    SELECT 
        v.MAVOUCHER,
        v.TENVOUCHER,
        v.MOTA,
        v.LOAIVOUCHER,
        v.GIATRI,
        v.GIATRIMIN,
        v.GIATRIMAX,
        v.SOLUONG,
        v.SOLUONGSUDUNG,
        v.NGAYBATDAU,
        v.NGAYHETHAN,
        v.TRANGTHAI
    FROM voucher v
    WHERE v.TRANGTHAI = 'active'
    AND v.NGAYBATDAU <= NOW()
    AND v.NGAYHETHAN >= NOW()  
    AND (v.SOLUONG - v.SOLUONGSUDUNG) > 0
    ORDER BY v.NGAYHETHAN ASC
");
$voucherStmt->execute();
$userVouchers = $voucherStmt->fetchAll(PDO::FETCH_ASSOC);

// Tính membership level dựa trên tổng chi tiêu
$totalSpent = array_sum(array_column($orders, 'TONGTIEN'));

function getMembershipLevel($totalSpent) {
    if ($totalSpent >= 20000000) {
        return [
            'level' => 'Kim cương',
            'icon' => 'bi-gem',
            'color' => 'linear-gradient(135deg,#7de2fc 20%,#b9b6e5 60%,#e6c2f7 100%)',
            'textColor' => '#224168',
            'discount' => 15,
            'benefits' => [
                'Giảm giá 15% mọi đơn hàng',
                'Freeship toàn quốc không giới hạn',
                'Quà sinh nhật VIP + voucher 1.000.000đ',
                'Ưu tiên hỗ trợ riêng, mời sự kiện VIP'
            ],
            'nextLevel' => null,
            'amountToNext' => 0
        ];
    } elseif ($totalSpent >= 10000000) {
        return [
            'level' => 'Vàng',
            'icon' => 'bi-award-fill',
            'color' => 'linear-gradient(135deg,#fffbe8 8%,#ffe8a6 43%,#e0bb7c 100%)',
            'textColor' => '#856200',
            'discount' => 10,
            'benefits' => [
                'Giảm giá 10% mọi đơn hàng',
                'Freeship 10 đơn/tháng',
                'Quà sinh nhật đặc biệt',
                'Ưu tiên hỗ trợ khách hàng'
            ],
            'nextLevel' => 'Kim cương',
            'amountToNext' => 20000000 - $totalSpent
        ];
    } elseif ($totalSpent >= 5000000) {
        return [
            'level' => 'Bạc',
            'icon' => 'bi-trophy',
            'color' => 'linear-gradient(120deg,#f7fafd 10%,#cfd9df 100%)',
            'textColor' => '#676f7b',
            'discount' => 5,
            'benefits' => [
                'Giảm giá 5% mọi đơn hàng',
                'Freeship 3 đơn/tháng',
                'Voucher sinh nhật 100.000đ'
            ],
            'nextLevel' => 'Vàng',
            'amountToNext' => 10000000 - $totalSpent
        ];
    } else {
        return [
            'level' => 'Thành viên',
            'icon' => 'bi-person',
            'color' => 'linear-gradient(120deg,#f7fafd 70%,#f2f3f4 100%)',
            'textColor' => '#818ba1',
            'discount' => 0,
            'benefits' => [
                'Tích điểm đổi ưu đãi',
                'Tham gia các chương trình khuyến mãi thường niên'
            ],
            'nextLevel' => 'Bạc',
            'amountToNext' => 5000000 - $totalSpent
        ];
    }
}

$membershipData = getMembershipLevel($totalSpent);

// Lấy tab hiện tại
$currentTab = $_GET['tab'] ?? 'profile';
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tài khoản của tôi - MENSTA</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Inter', sans-serif; background: #f8f9fc; line-height: 1.6; }
        
        .container { max-width: 1200px; margin: 0 auto; padding: 20px; }
        
        .profile-header { background: white; padding: 30px; border-radius: 12px; box-shadow: 0 2px 15px rgba(0,0,0,0.1); margin-bottom: 30px; text-align: center; }
        .profile-header h1 { color: #2c3e50; font-size: 32px; font-weight: 700; margin-bottom: 10px; }
.profile-header .user-info { color: #7f8c8d; font-size: 16px; }
        
        .tabs { background: white; border-radius: 12px; box-shadow: 0 2px 15px rgba(0,0,0,0.1); margin-bottom: 30px; overflow: hidden; }
        .tab-nav { display: flex; border-bottom: 1px solid #eee; }
        .tab-btn { flex: 1; padding: 20px; background: none; border: none; font-size: 16px; font-weight: 500; cursor: pointer; transition: all 0.3s; display: flex; align-items: center; justify-content: center; gap: 10px; }
        .tab-btn:hover { background: #f8f9fc; }
        .tab-btn.active { background: #3498db; color: white; }
        .tab-content { padding: 30px; }
        .tab-pane { display: none; }
        .tab-pane.active { display: block; }
        
        /* Profile Form Styles */
        .profile-form { max-width: 600px; margin: 0 auto; }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px; }
        .form-group { display: flex; flex-direction: column; }
        .form-group.full-width { grid-column: 1 / -1; }
        .form-label { font-weight: 500; margin-bottom: 8px; color: #555; font-size: 14px; }
        .form-control { padding: 12px 15px; border: 1px solid #ddd; border-radius: 8px; font-size: 15px; }
        .form-control:focus { border-color: #3498db; box-shadow: 0 0 0 2px rgba(52, 152, 219, 0.2); outline: none; }
        .form-control:read-only { background: #f8f9fc; color: #666; }
        
        /* Orders Styles */
        .orders-filter { margin-bottom: 20px; display: flex; gap: 10px; flex-wrap: wrap; }
        .filter-btn { padding: 8px 16px; border: 1px solid #ddd; background: white; border-radius: 20px; font-size: 14px; cursor: pointer; transition: all 0.3s; }
        .filter-btn:hover, .filter-btn.active { background: #3498db; color: white; border-color: #3498db; }
        
        .order-card { background: white; border: 1px solid #eee; border-radius: 8px; margin-bottom: 15px; transition: all 0.3s; }
        .order-card:hover { box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
        .order-header { padding: 20px; border-bottom: 1px solid #eee; display: flex; justify-content: between; align-items: center; flex-wrap: wrap; gap: 15px; }
        .order-id { font-weight: 600; color: #2c3e50; font-size: 16px; }
        .order-date { color: #7f8c8d; font-size: 14px; }
        .order-status { padding: 6px 12px; border-radius: 15px; font-size: 12px; font-weight: 600; }
        .status-pending { background: #fff3cd; color: #856404; }
        .status-processing { background: #d1ecf1; color: #0c5460; }
        .status-shipping { background: #e2e3f1; color: #383d41; }
        .status-delivered { background: #d4edda; color: #155724; }
        .status-cancelled { background: #f8d7da; color: #721c24; }
        
        .order-body { padding: 20px; }
.order-info { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 15px; }
        .info-item { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #f0f0f0; }
        .info-label { color: #7f8c8d; font-size: 14px; }
        .info-value { font-weight: 500; color: #2c3e50; }
        
        .order-actions { text-align: right; }
        .btn { padding: 10px 20px; border: none; border-radius: 6px; cursor: pointer; font-size: 14px; font-weight: 500; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; transition: all 0.3s; }
        .btn-primary { background: #3498db; color: white; }
        .btn-primary:hover { background: #2980b9; }
        .btn-success { background: #27ae60; color: white; }
        .btn-success:hover { background: #229954; }
        .btn-outline { background: white; color: #3498db; border: 1px solid #3498db; }
        .btn-outline:hover { background: #3498db; color: white; }
        
        /* Tooltip styles */
        .tooltip-container { position: relative; display: inline-block; }
        .tooltip-text { 
            visibility: hidden; 
            width: 200px; 
            background-color: #555; 
            color: white; 
            text-align: center; 
            border-radius: 6px; 
            padding: 8px; 
            position: absolute; 
            z-index: 1; 
            bottom: 125%; 
            left: 50%; 
            margin-left: -100px; 
            opacity: 0; 
            transition: opacity 0.3s; 
            font-size: 12px;
        }
        .tooltip-text::after { 
            content: ""; 
            position: absolute; 
            top: 100%; 
            left: 50%; 
            margin-left: -5px; 
            border-width: 5px; 
            border-style: solid; 
            border-color: #555 transparent transparent transparent; 
        }
        .tooltip-container:hover .tooltip-text { visibility: visible; opacity: 1; }
        
        .alert { padding: 15px; border-radius: 8px; margin-bottom: 20px; text-align: center; }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        
        .empty-state { text-align: center; padding: 60px 20px; color: #7f8c8d; }
        .empty-state i { font-size: 48px; margin-bottom: 20px; color: #bdc3c7; }
        
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); text-align: center; }
        .stat-number { font-size: 24px; font-weight: 700; color: #2c3e50; margin-bottom: 5px; }
        .stat-label { color: #7f8c8d; font-size: 14px; }
        
        /* Voucher Styles */
        .voucher-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; }
        .voucher-card { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border-radius: 12px; padding: 20px; position: relative; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.2); transition: all 0.3s; }
        .voucher-card:hover { transform: translateY(-5px); box-shadow: 0 8px 25px rgba(0,0,0,0.3); }
        .voucher-card.expired { background: linear-gradient(135deg, #757575 0%, #9e9e9e 100%); opacity: 0.7; }
        .voucher-card.freeship { background: linear-gradient(135deg, #4CAF50 0%, #45a049 100%); }
        .voucher-card.fixed { background: linear-gradient(135deg, #f39c12 0%, #e67e22 100%); }
        .voucher-card.percent { background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%); }
        
        .voucher-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 15px; }
        .voucher-type { background: rgba(255,255,255,0.2); padding: 4px 8px; border-radius: 15px; font-size: 11px; text-transform: uppercase; font-weight: 600; }
        .voucher-value { font-size: 24px; font-weight: 700; text-align: right; }
        
        .voucher-title { font-size: 16px; font-weight: 600; margin-bottom: 8px; }
        .voucher-desc { font-size: 13px; opacity: 0.9; margin-bottom: 12px; line-height: 1.4; }
        .voucher-condition { font-size: 12px; opacity: 0.8; margin-bottom: 8px; }
        .voucher-expiry { font-size: 11px; opacity: 0.7; display: flex; align-items: center; gap: 5px; }
        
        .voucher-actions { margin-top: 15px; text-align: center; }
        .voucher-btn { background: rgba(255,255,255,0.2); color: white; border: 1px solid rgba(255,255,255,0.3); padding: 8px 16px; border-radius: 20px; font-size: 12px; cursor: pointer; transition: all 0.3s; }
        .voucher-btn:hover { background: rgba(255,255,255,0.3); }
        .voucher-btn:disabled { opacity: 0.5; cursor: not-allowed; }
        
        .voucher-pattern { position: absolute; top: -20px; right: -20px; width: 60px; height: 60px; background: rgba(255,255,255,0.1); border-radius: 50%; }
        .voucher-pattern::after { content: ''; position: absolute; top: 20px; left: 20px; width: 30px; height: 30px; background: rgba(255,255,255,0.1); border-radius: 50%; }
        
        /* Switch Toggle Styles */
        .switch { position: relative; display: inline-block; width: 50px; height: 24px; }
        .switch input { opacity: 0; width: 0; height: 0; }
        .slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #ccc; transition: .4s; border-radius: 24px; }
        .slider:before { position: absolute; content: ""; height: 18px; width: 18px; left: 3px; bottom: 3px; background-color: white; transition: .4s; border-radius: 50%; }
        input:checked + .slider { background-color: #3498db; }
        input:checked + .slider:before { transform: translateX(26px); }
        
        /* Membership Styles */
        .membership-container { max-width: 800px; margin: 0 auto; }
        .current-level-card { 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 15px;
            text-align: center;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
        }
        .level-icon { 
            font-size: 4rem; 
            margin-bottom: 15px;
            padding: 20px;
            border-radius: 50%;
            display: inline-block;
            background: rgba(255,255,255,0.2);
            backdrop-filter: blur(10px);
        }
        .level-name { font-size: 2rem; font-weight: 700; margin-bottom: 10px; }
        .level-spending { font-size: 1.2rem; opacity: 0.9; margin-bottom: 20px; }
        .level-progress { 
            background: rgba(255,255,255,0.2); 
            height: 8px; 
            border-radius: 4px; 
            margin: 20px 0;
            overflow: hidden;
        }
        .level-progress-bar { 
            height: 100%; 
            background: rgba(255,255,255,0.8);
            border-radius: 4px;
            transition: width 0.5s ease;
        }
        .next-level-info { font-size: 1rem; opacity: 0.9; }
        
        .benefits-grid { 
            display: grid; 
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); 
            gap: 20px; 
            margin-bottom: 30px; 
        }
        .benefit-card { 
            background: white; 
            padding: 25px; 
            border-radius: 12px; 
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            border-left: 4px solid #3498db;
        }
        .benefit-card h4 { color: #2c3e50; margin-bottom: 15px; font-size: 1.2rem; }
        .benefit-list { list-style: none; }
        .benefit-list li { 
            padding: 8px 0; 
            color: #555; 
            position: relative;
            padding-left: 25px;
        }
        .benefit-list li:before { 
            content: "✓"; 
            color: #27ae60; 
            font-weight: bold; 
            position: absolute; 
            left: 0; 
        }
        
        .all-levels { 
            background: white; 
            padding: 30px; 
            border-radius: 12px; 
            box-shadow: 0 4px 15px rgba(0,0,0,0.1); 
        }
        .all-levels h3 { 
            text-align: center; 
            margin-bottom: 30px; 
            color: #2c3e50; 
            font-size: 1.5rem; 
        }
        .levels-grid { 
            display: grid; 
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); 
            gap: 20px; 
        }
        .level-card { 
            padding: 20px; 
            border-radius: 12px; 
            text-align: center; 
            border: 2px solid #e9ecef;
            transition: all 0.3s ease;
        }
        .level-card:hover { 
            transform: translateY(-5px); 
            box-shadow: 0 8px 25px rgba(0,0,0,0.15); 
        }
        .level-card.current { 
            border-color: #3498db; 
            background: linear-gradient(135deg, #f8f9ff 0%, #e3f2fd 100%);
        }
        .level-card-icon { 
            font-size: 2.5rem; 
            margin-bottom: 15px; 
            padding: 15px;
            border-radius: 50%;
            display: inline-block;
        }
        .level-card-name { 
            font-size: 1.3rem; 
            font-weight: 700; 
            margin-bottom: 10px; 
        }
        .level-card-requirement { 
            font-size: 0.9rem; 
            color: #666; 
            margin-bottom: 15px; 
        }
        .level-card-benefits { 
            font-size: 0.85rem; 
            color: #555; 
            text-align: left;
        }
        
        @media (max-width: 768px) {
            .container { padding: 15px; }
            .tab-nav { flex-direction: column; }
            .form-row { grid-template-columns: 1fr; }
            .order-header { flex-direction: column; align-items: flex-start; }
            .order-info { grid-template-columns: 1fr; }
            .stats-grid { grid-template-columns: 1fr; }
            .voucher-grid { grid-template-columns: 1fr; }
            .levels-grid { grid-template-columns: 1fr; }
            .benefits-grid { grid-template-columns: 1fr; }
            .level-icon { font-size: 3rem; }
            .level-name { font-size: 1.5rem; }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="profile-header">
            <h1><i class="fas fa-user-circle"></i> Tài khoản của tôi</h1>
            <div class="user-info">
                Xin chào, <strong><?php echo htmlspecialchars($userInfo['TENKH']); ?></strong>
                <span style="margin: 0 10px;">•</span>
                <a href="/web_3/view/logout.php" style="color: #e74c3c; text-decoration: none;">
                    <i class="fas fa-sign-out-alt"></i> Đăng xuất
                </a>
            </div>
        </div>

        <!-- Tabs -->
        <div class="tabs">
            <div class="tab-nav">
                <button class="tab-btn <?php echo $currentTab === 'profile' ? 'active' : ''; ?>" onclick="switchTab('profile')">
                    <i class="fas fa-user"></i> Thông tin cá nhân
                </button>
                <button class="tab-btn <?php echo $currentTab === 'orders' ? 'active' : ''; ?>" onclick="switchTab('orders')">
                    <i class="fas fa-shopping-bag"></i> Đơn hàng của tôi
                </button>
                <button class="tab-btn <?php echo $currentTab === 'vouchers' ? 'active' : ''; ?>" onclick="switchTab('vouchers')">
                    <i class="fas fa-ticket-alt"></i> Voucher của tôi
                </button>
                <button class="tab-btn <?php echo $currentTab === 'membership' ? 'active' : ''; ?>" onclick="switchTab('membership')">
                    <i class="fas fa-crown"></i> Thành viên VIP
                </button>
                <button class="tab-btn <?php echo $currentTab === 'settings' ? 'active' : ''; ?>" onclick="switchTab('settings')">
                    <i class="fas fa-cog"></i> Cài đặt
                </button>
            </div>            <div class="tab-content">
                <!-- Tab Thông tin cá nhân -->
                <div class="tab-pane <?php echo $currentTab === 'profile' ? 'active' : ''; ?>" id="profile-tab">
                    <?php if (isset($_SESSION['update_success'])): ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle"></i> Thông tin đã được cập nhật thành công!
                        </div>
                        <?php unset($_SESSION['update_success']); ?>
                    <?php endif; ?>

                    <form method="POST" class="profile-form">
                        <input type="hidden" name="action" value="update_profile">
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Tên đăng nhập</label>
                                <input type="text" class="form-control" value="<?php echo htmlspecialchars($userInfo['TENDANGNHAP']); ?>" readonly>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Mã khách hàng</label>
                                <input type="text" class="form-control" value="<?php echo htmlspecialchars($userInfo['MAKH']); ?>" readonly>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Họ và tên <span style="color: red;">*</span></label>
                                <input type="text" name="TENKH" class="form-control" value="<?php echo htmlspecialchars($userInfo['TENKH']); ?>" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Email <span style="color: red;">*</span></label>
                                <input type="email" name="EMAIL" class="form-control" value="<?php echo htmlspecialchars($userInfo['EMAIL']); ?>" required>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Giới tính</label>
                                <select name="GIOITINH" class="form-control">
<option value="Nam" <?php if ($userInfo['GIOITINH'] == 'Nam') echo 'selected'; ?>>Nam</option>
                                    <option value="Nữ" <?php if ($userInfo['GIOITINH'] == 'Nữ') echo 'selected'; ?>>Nữ</option>
                                    <option value="Khác" <?php if ($userInfo['GIOITINH'] == 'Khác') echo 'selected'; ?>>Khác</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Ngày sinh</label>
                                <input type="date" name="NGAYSINH" class="form-control" value="<?php echo htmlspecialchars($userInfo['NGAYSINH']); ?>">
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Số điện thoại <span style="color: red;">*</span></label>
                                <input type="tel" name="SDT" class="form-control" value="<?php echo htmlspecialchars($userInfo['SDT']); ?>" required>
                            </div>
                            <div class="form-group full-width">
                                <label class="form-label">Địa chỉ <span style="color: red;">*</span></label>
                                <input type="text" name="DIACHI" class="form-control" value="<?php echo htmlspecialchars($userInfo['DIACHI']); ?>" required>
                            </div>
                        </div>

                        <div style="text-align: center; margin-top: 30px;">
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-save"></i> Cập nhật thông tin
                            </button>
                            <a href="/web_3/index.php" class="btn btn-outline">
                                <i class="fas fa-home"></i> Về trang chủ
                            </a>
                        </div>
                    </form>
                </div>

                <!-- Tab Đơn hàng -->
                <div class="tab-pane <?php echo $currentTab === 'orders' ? 'active' : ''; ?>" id="orders-tab">
                    <!-- Thống kê -->
                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-number"><?php echo count($orders); ?></div>
                            <div class="stat-label">Tổng đơn hàng</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-number"><?php echo count(array_filter($orders, fn($o) => $o['TRANGTHAI'] === 'Đã hoàn thành')); ?></div>
                            <div class="stat-label">Đã hoàn thành</div>
                        </div>
<div class="stat-card">
                            <div class="stat-number"><?php echo count(array_filter($orders, fn($o) => in_array($o['TRANGTHAI'], ['Chờ xử lý', 'Đang xử lý', 'Đang giao hàng']))); ?></div>
                            <div class="stat-label">Đang xử lý</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-number"><?php echo number_format(array_sum(array_column($orders, 'TONGTIEN'))); ?>đ</div>
                            <div class="stat-label">Tổng chi tiêu</div>
                        </div>
                    </div>

                    <!-- Filter -->
                    <div class="orders-filter">
                        <button class="filter-btn active" onclick="filterOrders('all')">Tất cả</button>
                        <button class="filter-btn" onclick="filterOrders('Chờ xử lý')">Chờ xử lý</button>
                        <button class="filter-btn" onclick="filterOrders('Đang xử lý')">Đang xử lý</button>
                        <button class="filter-btn" onclick="filterOrders('Đang giao hàng')">Đang giao hàng</button>
                        <button class="filter-btn" onclick="filterOrders('Đã hoàn thành')">Đã hoàn thành</button>
                        <button class="filter-btn" onclick="filterOrders('Yêu cầu hoàn hàng')">Yêu cầu hoàn hàng</button>
                        <button class="filter-btn" onclick="filterOrders('Đã hủy')">Đã hủy</button>
                    </div>

                    <!-- Danh sách đơn hàng -->
                    <?php if (empty($orders)): ?>
                        <div class="empty-state">
                            <i class="fas fa-shopping-bag"></i>
                            <h3>Chưa có đơn hàng nào</h3>
                            <p>Hãy mua sắm và tạo đơn hàng đầu tiên của bạn!</p>
                            <a href="/web_3/index.php" class="btn btn-primary">
                                <i class="fas fa-shopping-cart"></i> Mua sắm ngay
                            </a>
                        </div>
                    <?php else: ?>
                        <?php foreach ($orders as $order): ?>
                            <div class="order-card" data-status="<?php echo $order['TRANGTHAI']; ?>">
                                <div class="order-header">
                                    <div>
                                        <div class="order-id">#<?php echo $order['MADONHANG']; ?></div>
                                        <div class="order-date"><?php echo date('d/m/Y H:i', strtotime($order['NGAYDAT'])); ?></div>
                                    </div>
                                    <div class="order-status <?php
                                        switch ($order['TRANGTHAI']) {
                                            case 'Chờ xử lý': echo 'status-pending'; break;
                                            case 'Đang xử lý': echo 'status-processing'; break;
case 'Đang giao hàng': echo 'status-shipping'; break;
                                            case 'Đã giao hàng': echo 'status-delivered'; break;
                                            case 'Đã hủy': echo 'status-cancelled'; break;
                                        }
                                    ?>">
                                        <?php echo $order['TRANGTHAI']; ?>
                                    </div>
                                </div>
                                
                                <div class="order-body">
                                    <div class="order-info">
                                        <div class="info-item">
                                            <span class="info-label">Số sản phẩm:</span>
                                            <span class="info-value"><?php echo $order['SO_SANPHAM']; ?> loại</span>
                                        </div>
                                        <div class="info-item">
                                            <span class="info-label">Tổng số lượng:</span>
                                            <span class="info-value"><?php echo $order['TONG_SOLUONG']; ?> cái</span>
                                        </div>
                                        <div class="info-item">
                                            <span class="info-label">Thanh toán:</span>
                                            <span class="info-value"><?php echo $order['PHUONGTHUCTHANHTOAN']; ?></span>
                                        </div>
                                        <div class="info-item">
                                            <span class="info-label">Thành tiền:</span>
                                            <span class="info-value" style="color: #e74c3c; font-weight: 600;"><?php echo number_format($order['TONGTIEN']); ?>đ</span>
                                        </div>
                                    </div>
                                    
                                    <div class="order-actions">
                                        <a href="/web_3/view/track_order.php?order_code=<?php echo $order['MADONHANG']; ?>" class="btn btn-primary">
                                            <i class="fas fa-eye"></i> Xem chi tiết
                                        </a>
                                        <?php if ($order['TRANGTHAI'] === 'Chờ xử lý'): ?>
                                            <button class="btn btn-outline" onclick="cancelOrder('<?php echo $order['MADONHANG']; ?>')" style="color: #e74c3c; border-color: #e74c3c;">
                                                <i class="fas fa-times"></i> Hủy đơn
                                            </button>
                                        <?php elseif ($order['TRANGTHAI'] === 'Đã hoàn thành'): ?>
                                            <button class="btn btn-outline" onclick="returnOrder('<?php echo $order['MADONHANG']; ?>')" style="color: #f39c12; border-color: #f39c12;">
                                                <i class="fas fa-undo"></i> Hoàn hàng
                                            </button>
                                        <?php elseif ($order['TRANGTHAI'] === 'Đã hủy'): ?>
                                            <span class="btn" style="background: #f8d7da; color: #721c24; cursor: not-allowed;" disabled>
                                                <i class="fas fa-ban"></i> Đã hủy
                                            </span>
                                        <?php elseif ($order['TRANGTHAI'] === 'Yêu cầu hoàn hàng'): ?>
                                            <span class="btn" style="background: #fff3cd; color: #856404; cursor: not-allowed;" disabled>
                                                <i class="fas fa-clock"></i> Chờ duyệt hoàn hàng
                                            </span>
                                        <?php elseif (in_array($order['TRANGTHAI'], ['Đang xử lý', 'Đang giao hàng'])): ?>
                                            <div class="tooltip-container">
                                                <span class="btn" style="background: #f0f0f0; color: #666; cursor: not-allowed;" disabled>
                                                    <i class="fas fa-info-circle"></i> Không thể hủy
                                                </span>
                                                <span class="tooltip-text">Chỉ có thể hủy đơn hàng ở trạng thái "Chờ xử lý"</span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
<?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <!-- Tab Voucher -->
                <div class="tab-pane <?php echo $currentTab === 'vouchers' ? 'active' : ''; ?>" id="vouchers-tab">
                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-number"><?php echo count($userVouchers); ?></div>
                            <div class="stat-label">Voucher khả dụng</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-number"><?php echo count(array_filter($userVouchers, fn($v) => $v['LOAIVOUCHER'] === 'freeship')); ?></div>
                            <div class="stat-label">Miễn phí ship</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-number"><?php echo count(array_filter($userVouchers, fn($v) => $v['LOAIVOUCHER'] === 'percent')); ?></div>
                            <div class="stat-label">Giảm theo %</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-number"><?php echo count(array_filter($userVouchers, fn($v) => $v['LOAIVOUCHER'] === 'fixed')); ?></div>
                            <div class="stat-label">Giảm cố định</div>
                        </div>
                    </div>

                    <?php if (empty($userVouchers)): ?>
                        <div class="empty-state">
                            <i class="fas fa-ticket-alt"></i>
                            <h3>Chưa có voucher nào</h3>
                            <p>Hãy mua sắm nhiều hơn để nhận được các voucher hấp dẫn!</p>
                            <a href="/web_3/index.php" class="btn btn-primary">
                                <i class="fas fa-shopping-cart"></i> Mua sắm ngay
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="voucher-grid">
                            <?php foreach ($userVouchers as $voucher): ?>
                                <div class="voucher-card <?php echo $voucher['LOAIVOUCHER']; ?>">
                                    <div class="voucher-pattern"></div>
                                    
                                    <div class="voucher-header">
                                        <div class="voucher-type">
                                            <?php 
                                                switch ($voucher['LOAIVOUCHER']) {
                                                    case 'percent': echo 'Giảm %'; break;
                                                    case 'fixed': echo 'Giảm tiền'; break;
                                                    case 'freeship': echo 'Free ship'; break;
                                                }
                                            ?>
                                        </div>
                                        <div class="voucher-value">
                                            <?php 
                                                if ($voucher['LOAIVOUCHER'] === 'percent') {
                                                    echo $voucher['GIATRI'] . '%';
                                                } elseif ($voucher['LOAIVOUCHER'] === 'fixed') {
                                                    echo number_format($voucher['GIATRI']) . 'đ';
                                                } else {
                                                    echo 'FREE';
                                                }
                                            ?>
                                        </div>
                                    </div>
                                    
                                    <div class="voucher-title"><?php echo htmlspecialchars($voucher['TENVOUCHER']); ?></div>
                                    <div class="voucher-desc"><?php echo htmlspecialchars($voucher['MOTA']); ?></div>
                                    
                                    <?php if ($voucher['GIATRIMIN'] > 0): ?>
                                        <div class="voucher-condition">
                                            <i class="fas fa-info-circle"></i>
                                            Đơn tối thiểu: <?php echo number_format($voucher['GIATRIMIN']); ?>đ
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($voucher['GIATRIMAX'] > 0): ?>
                                        <div class="voucher-condition">
                                            <i class="fas fa-info-circle"></i>
                                            Giảm tối đa: <?php echo number_format($voucher['GIATRIMAX']); ?>đ
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="voucher-expiry">
                                        <i class="fas fa-clock"></i>
                                        HSD: <?php echo date('d/m/Y', strtotime($voucher['NGAYHETHAN'])); ?>
                                    </div>
                                    
                                    <div class="voucher-expiry" style="margin-top: 5px;">
                                        <i class="fas fa-users"></i>
                                        Còn lại: <?php echo ($voucher['SOLUONG'] - $voucher['SOLUONGSUDUNG']); ?>/<?php echo $voucher['SOLUONG']; ?>
                                    </div>
                                    
                                    <div class="voucher-actions">
                                        <button class="voucher-btn" onclick="copyVoucherCode('<?php echo $voucher['MAVOUCHER']; ?>')">
                                            <i class="fas fa-copy"></i> Sao chép mã
                                        </button>
                                        <a href="/web_3/index.php" class="voucher-btn" style="text-decoration: none; margin-left: 8px;">
                                            <i class="fas fa-shopping-cart"></i> Dùng ngay
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Tab Cài đặt -->
                <div class="tab-pane <?php echo $currentTab === 'settings' ? 'active' : ''; ?>" id="settings-tab">
                    <div style="max-width: 600px; margin: 0 auto;">
                        <h3 style="margin-bottom: 30px; color: #2c3e50;"><i class="fas fa-cog"></i> Cài đặt tài khoản</h3>
                        
                        <div style="background: white; padding: 30px; border-radius: 12px; box-shadow: 0 2px 15px rgba(0,0,0,0.1); margin-bottom: 20px;">
                            <h4 style="margin-bottom: 20px; color: #34495e;"><i class="fas fa-key"></i> Đổi mật khẩu</h4>
                            <form method="POST" id="changePasswordForm">
                                <input type="hidden" name="action" value="change_password">
                                
                                <div class="form-group" style="margin-bottom: 20px;">
                                    <label class="form-label">Mật khẩu hiện tại</label>
                                    <input type="password" name="current_password" class="form-control" required>
                                </div>
                                
                                <div class="form-group" style="margin-bottom: 20px;">
                                    <label class="form-label">Mật khẩu mới</label>
                                    <input type="password" name="new_password" class="form-control" required minlength="6">
                                </div>
                                
                                <div class="form-group" style="margin-bottom: 20px;">
                                    <label class="form-label">Xác nhận mật khẩu mới</label>
                                    <input type="password" name="confirm_password" class="form-control" required>
                                </div>
                                
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Đổi mật khẩu
                                </button>
                            </form>
                        </div>
                        
                        <div style="background: white; padding: 30px; border-radius: 12px; box-shadow: 0 2px 15px rgba(0,0,0,0.1);">
                            <h4 style="margin-bottom: 20px; color: #34495e;"><i class="fas fa-bell"></i> Thông báo</h4>
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                                <span>Thông báo đơn hàng</span>
                                <label class="switch">
                                    <input type="checkbox" checked>
                                    <span class="slider"></span>
                                </label>
                            </div>
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                                <span>Thông báo khuyến mãi</span>
                                <label class="switch">
                                    <input type="checkbox" checked>
                                    <span class="slider"></span>
                                </label>
                            </div>
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <span>Email marketing</span>
                                <label class="switch">
                                    <input type="checkbox">
                                    <span class="slider"></span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tab Membership -->
                <div class="tab-pane <?php echo $currentTab === 'membership' ? 'active' : ''; ?>" id="membership-tab">
                    <div class="membership-container">
                        <!-- Current Level Card -->
                        <div class="current-level-card">
                            <div class="level-icon" style="background: <?php echo $membershipData['color']; ?>; color: <?php echo $membershipData['textColor']; ?>;">
                                <i class="<?php echo $membershipData['icon']; ?>"></i>
                            </div>
                            <div class="level-name"><?php echo $membershipData['level']; ?></div>
                            <div class="level-spending">
                                Tổng chi tiêu: <strong><?php echo number_format($totalSpent); ?>đ</strong>
                            </div>
                            
                            <?php if ($membershipData['nextLevel']): ?>
                                <div class="level-progress">
                                    <?php 
                                    $currentLevelMin = 0;
                                    if ($membershipData['level'] === 'Thành viên') $currentLevelMin = 0;
                                    elseif ($membershipData['level'] === 'Bạc') $currentLevelMin = 5000000;
                                    elseif ($membershipData['level'] === 'Vàng') $currentLevelMin = 10000000;
                                    
                                    $nextLevelMin = $currentLevelMin + $membershipData['amountToNext'];
                                    $progress = (($totalSpent - $currentLevelMin) / ($nextLevelMin - $currentLevelMin)) * 100;
                                    ?>
                                    <div class="level-progress-bar" style="width: <?php echo min(100, max(0, $progress)); ?>%"></div>
                                </div>
                                <div class="next-level-info">
                                    Còn <strong><?php echo number_format($membershipData['amountToNext']); ?>đ</strong> 
                                    để lên hạng <strong><?php echo $membershipData['nextLevel']; ?></strong>
                                </div>
                            <?php else: ?>
                                <div class="next-level-info">
                                    🎉 Bạn đã đạt hạng cao nhất!
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Current Benefits -->
                        <div class="benefits-grid">
                            <div class="benefit-card">
                                <h4><i class="fas fa-gift"></i> Quyền lợi hiện tại</h4>
                                <ul class="benefit-list">
                                    <?php foreach ($membershipData['benefits'] as $benefit): ?>
                                        <li><?php echo $benefit; ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                            
                            <div class="benefit-card">
                                <h4><i class="fas fa-chart-line"></i> Thống kê của bạn</h4>
                                <ul class="benefit-list">
                                    <li>Tổng đơn hàng: <strong><?php echo count($orders); ?></strong></li>
                                    <li>Đơn hoàn thành: <strong><?php echo count(array_filter($orders, fn($o) => $o['TRANGTHAI'] === 'Đã hoàn thành')); ?></strong></li>
                                    <li>Voucher có: <strong><?php echo count($userVouchers); ?></strong></li>
                                    <li>Hạng thành viên: <strong><?php echo $membershipData['level']; ?></strong></li>
                                </ul>
                            </div>
                        </div>

                        <!-- All Levels -->
                        <div class="all-levels">
                            <h3><i class="fas fa-layer-group"></i> Tất cả hạng thành viên</h3>
                            <div class="levels-grid">
                                <!-- Kim cương -->
                                <div class="level-card <?php echo $membershipData['level'] === 'Kim cương' ? 'current' : ''; ?>">
                                    <div class="level-card-icon" style="background: linear-gradient(135deg,#7de2fc 20%,#b9b6e5 60%,#e6c2f7 100%); color: #224168;">
                                        <i class="bi bi-gem"></i>
                                    </div>
                                    <div class="level-card-name" style="color: #224168;">KIM CƯƠNG</div>
                                    <div class="level-card-requirement">Từ 20.000.000đ</div>
                                    <div class="level-card-benefits">
                                        • Giảm giá 15% mọi đơn hàng<br>
                                        • Freeship toàn quốc<br>
                                        • Quà sinh nhật VIP<br>
                                        • Ưu tiên hỗ trợ riêng
                                    </div>
                                </div>

                                <!-- Vàng -->
                                <div class="level-card <?php echo $membershipData['level'] === 'Vàng' ? 'current' : ''; ?>">
                                    <div class="level-card-icon" style="background: linear-gradient(135deg,#fffbe8 8%,#ffe8a6 43%,#e0bb7c 100%); color: #856200;">
                                        <i class="bi bi-award-fill"></i>
                                    </div>
                                    <div class="level-card-name" style="color: #856200;">VÀNG</div>
                                    <div class="level-card-requirement">Từ 10.000.000đ</div>
                                    <div class="level-card-benefits">
                                        • Giảm giá 10% mọi đơn hàng<br>
                                        • Freeship 10 đơn/tháng<br>
                                        • Quà sinh nhật đặc biệt<br>
                                        • Ưu tiên hỗ trợ
                                    </div>
                                </div>

                                <!-- Bạc -->
                                <div class="level-card <?php echo $membershipData['level'] === 'Bạc' ? 'current' : ''; ?>">
                                    <div class="level-card-icon" style="background: linear-gradient(120deg,#f7fafd 10%,#cfd9df 100%); color: #676f7b;">
                                        <i class="bi bi-trophy"></i>
                                    </div>
                                    <div class="level-card-name" style="color: #676f7b;">BẠC</div>
                                    <div class="level-card-requirement">Từ 5.000.000đ</div>
                                    <div class="level-card-benefits">
                                        • Giảm giá 5% mọi đơn hàng<br>
                                        • Freeship 3 đơn/tháng<br>
                                        • Voucher sinh nhật 100.000đ
                                    </div>
                                </div>

                                <!-- Thành viên -->
                                <div class="level-card <?php echo $membershipData['level'] === 'Thành viên' ? 'current' : ''; ?>">
                                    <div class="level-card-icon" style="background: linear-gradient(120deg,#f7fafd 70%,#f2f3f4 100%); color: #818ba1;">
                                        <i class="bi bi-person"></i>
                                    </div>
                                    <div class="level-card-name" style="color: #818ba1;">THÀNH VIÊN</div>
                                    <div class="level-card-requirement">Dưới 5.000.000đ</div>
                                    <div class="level-card-benefits">
                                        • Tích điểm đổi ưu đãi<br>
                                        • Tham gia khuyến mãi<br>
                                        • Hỗ trợ khách hàng
                                    </div>
                                </div>
                            </div>

                            <div style="text-align: center; margin-top: 30px; padding: 20px; background: #f8f9fc; border-radius: 10px;">
                                <p style="color: #666; margin-bottom: 15px;">
                                    <i class="fas fa-info-circle"></i>
                                    <strong>Hạng thẻ được cập nhật tự động</strong> dựa trên tổng tiền mua sắm.<br>
                                    Ưu đãi được áp dụng trực tiếp khi đặt hàng.
                                </p>
                                <a href="/web_3/view/membership.php" class="btn btn-primary" style="text-decoration: none;">
                                    <i class="fas fa-external-link-alt"></i> Xem chi tiết hệ thống thành viên
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function switchTab(tabName) {
            // Ẩn tất cả tab panes
            document.querySelectorAll('.tab-pane').forEach(pane => {
                pane.classList.remove('active');
            });
            
            // Bỏ active cho tất cả tab buttons
            document.querySelectorAll('.tab-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            
            // Hiện tab được chọn
            document.getElementById(tabName + '-tab').classList.add('active');
            event.target.classList.add('active');
            
            // Cập nhật URL
            const url = new URL(window.location);
            url.searchParams.set('tab', tabName);
            window.history.pushState({}, '', url);
        }

        function filterOrders(status) {
            // Cập nhật active button
            document.querySelectorAll('.filter-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            event.target.classList.add('active');
            
            // Lọc đơn hàng
            document.querySelectorAll('.order-card').forEach(card => {
                if (status === 'all' || card.dataset.status === status) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        }

        function cancelOrder(orderCode) {
            // Tạo modal xác nhận đẹp hơn
            const modal = document.createElement('div');
            modal.style.cssText = `
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0,0,0,0.5);
                display: flex;
                justify-content: center;
                align-items: center;
                z-index: 10000;
            `;
            
            const modalContent = document.createElement('div');
            modalContent.style.cssText = `
                background: white;
                padding: 30px;
                border-radius: 12px;
                max-width: 400px;
                width: 90%;
                text-align: center;
                box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            `;
            
            modalContent.innerHTML = `
                <i class="fas fa-exclamation-triangle" style="font-size: 48px; color: #f39c12; margin-bottom: 20px;"></i>
                <h3 style="margin-bottom: 15px; color: #2c3e50;">Xác nhận hủy đơn hàng</h3>
                <p style="margin-bottom: 25px; color: #7f8c8d;">Bạn có chắc chắn muốn hủy đơn hàng <strong>#${orderCode}</strong>?</p>
                <p style="margin-bottom: 25px; color: #e74c3c; font-size: 14px;">Thao tác này không thể hoàn tác!</p>
                <div style="display: flex; gap: 15px; justify-content: center;">
                    <button id="cancelBtn" style="
                        background: #95a5a6; 
                        color: white; 
                        border: none; 
                        padding: 12px 24px; 
                        border-radius: 6px; 
                        cursor: pointer;
                        font-weight: 500;
                    ">Không</button>
                    <button id="confirmBtn" style="
                        background: #e74c3c; 
                        color: white; 
                        border: none; 
                        padding: 12px 24px; 
                        border-radius: 6px; 
                        cursor: pointer;
                        font-weight: 500;
                    ">Xác nhận hủy</button>
                </div>
            `;
            
            modal.appendChild(modalContent);
            document.body.appendChild(modal);
            
            // Xử lý sự kiện
            document.getElementById('cancelBtn').onclick = () => {
                document.body.removeChild(modal);
            };
            
            document.getElementById('confirmBtn').onclick = () => {
                document.body.removeChild(modal);
                
                // Hiển thị loading
                const loadingToast = showToast('Đang xử lý...', 'info', 0);
                
                // Gửi request hủy đơn hàng
                fetch('/web_3/view/cancel_order.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'order_code=' + orderCode
                })
                .then(response => response.json())
                .then(data => {
                    loadingToast.remove();
                    
                    if (data.success) {
                        showToast('Đơn hàng đã được hủy thành công!', 'success');
                        setTimeout(() => location.reload(), 1500);
                    } else {
                        showToast('Có lỗi xảy ra: ' + data.message, 'error');
                    }
                })
                .catch(error => {
                    loadingToast.remove();
                    console.error('Error:', error);
                    showToast('Có lỗi xảy ra khi hủy đơn hàng!', 'error');
                });
            };
            
            // Đóng modal khi click outside
            modal.onclick = (e) => {
                if (e.target === modal) {
                    document.body.removeChild(modal);
                }
            };
        }

        function returnOrder(orderCode) {
            // Tạo modal yêu cầu hoàn hàng với form lý do
            const modal = document.createElement('div');
            modal.style.cssText = `
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0,0,0,0.5);
                display: flex;
                justify-content: center;
                align-items: center;
                z-index: 10000;
            `;
            
            const modalContent = document.createElement('div');
            modalContent.style.cssText = `
                background: white;
                padding: 30px;
                border-radius: 12px;
                max-width: 500px;
                width: 90%;
                box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            `;
            
            modalContent.innerHTML = `
                <div style="text-align: center; margin-bottom: 25px;">
                    <i class="fas fa-undo" style="font-size: 48px; color: #f39c12; margin-bottom: 15px;"></i>
                    <h3 style="margin-bottom: 10px; color: #2c3e50;">Yêu cầu hoàn hàng</h3>
                    <p style="color: #7f8c8d;">Đơn hàng: <strong>#${orderCode}</strong></p>
                </div>
                
                <div style="margin-bottom: 20px;">
                    <label style="display: block; margin-bottom: 8px; font-weight: 500; color: #555;">Lý do hoàn hàng:</label>
                    <select id="returnReason" style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 6px; margin-bottom: 10px;">
                        <option value="">-- Chọn lý do --</option>
                        <option value="Sản phẩm bị lỗi">Sản phẩm bị lỗi</option>
                        <option value="Không đúng mô tả">Không đúng mô tả</option>
                        <option value="Sai size/màu sắc">Sai size/màu sắc</option>
                        <option value="Không vừa ý">Không vừa ý</option>
                        <option value="Khác">Khác</option>
                    </select>
                    <textarea id="returnNote" placeholder="Ghi chú thêm (tùy chọn)..." style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 6px; resize: vertical; min-height: 80px;"></textarea>
                </div>
                
                <div style="background: #fff3cd; padding: 15px; border-radius: 6px; margin-bottom: 20px;">
                    <p style="margin: 0; color: #856404; font-size: 14px;">
                        <i class="fas fa-info-circle"></i> 
                        Yêu cầu hoàn hàng sẽ được Admin xem xét trong vòng 24h. 
                        Bạn sẽ được thông báo kết quả qua email.
                    </p>
                </div>
                
                <div style="display: flex; gap: 15px; justify-content: center;">
                    <button id="cancelReturnBtn" style="
                        background: #95a5a6; 
                        color: white; 
                        border: none; 
                        padding: 12px 24px; 
                        border-radius: 6px; 
                        cursor: pointer;
                        font-weight: 500;
                    ">Hủy</button>
                    <button id="confirmReturnBtn" style="
                        background: #f39c12; 
                        color: white; 
                        border: none; 
                        padding: 12px 24px; 
                        border-radius: 6px; 
                        cursor: pointer;
                        font-weight: 500;
                    ">Gửi yêu cầu</button>
                </div>
            `;
            
            modal.appendChild(modalContent);
            document.body.appendChild(modal);
            
            // Xử lý sự kiện
            document.getElementById('cancelReturnBtn').onclick = () => {
                document.body.removeChild(modal);
            };
            
            document.getElementById('confirmReturnBtn').onclick = () => {
                const reason = document.getElementById('returnReason').value;
                const note = document.getElementById('returnNote').value;
                
                if (!reason) {
                    showToast('Vui lòng chọn lý do hoàn hàng', 'warning');
                    return;
                }
                
                document.body.removeChild(modal);
                
                // Hiển thị loading
                const loadingToast = showToast('Đang gửi yêu cầu...', 'info', 0);
                
                // Tạo form data
                const formData = new FormData();
                formData.append('order_code', orderCode);
                formData.append('action', 'return');
                formData.append('return_reason', reason + (note ? ' - ' + note : ''));
                
                // Gửi request hoàn hàng
                fetch('/web_3/view/cancel_order.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    loadingToast.remove();
                    
                    if (data.success) {
                        showToast(data.message, 'success');
                        setTimeout(() => location.reload(), 2000);
                    } else {
                        showToast('Có lỗi xảy ra: ' + data.message, 'error');
                    }
                })
                .catch(error => {
                    loadingToast.remove();
                    console.error('Error:', error);
                    showToast('Có lỗi xảy ra khi gửi yêu cầu hoàn hàng!', 'error');
                });
            };
            
            // Đóng modal khi click outside
            modal.onclick = (e) => {
                if (e.target === modal) {
                    document.body.removeChild(modal);
                }
            };
        }

        function showToast(message, type = 'info', duration = 3000) {
            const toast = document.createElement('div');
            const colors = {
                success: '#27ae60',
                error: '#e74c3c',
                info: '#3498db',
                warning: '#f39c12'
            };
            
            const icons = {
                success: 'fas fa-check-circle',
                error: 'fas fa-times-circle',
                info: 'fas fa-info-circle',
                warning: 'fas fa-exclamation-triangle'
            };
            
            toast.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                background: ${colors[type]};
                color: white;
                padding: 15px 20px;
                border-radius: 8px;
                z-index: 9999;
                box-shadow: 0 4px 12px rgba(0,0,0,0.2);
                font-weight: 500;
                min-width: 300px;
                opacity: 0;
                transform: translateX(100%);
                transition: all 0.3s ease;
            `;
            
            toast.innerHTML = `<i class="${icons[type]}"></i> ${message}`;
            document.body.appendChild(toast);
            
            // Animate in
            setTimeout(() => {
                toast.style.opacity = '1';
                toast.style.transform = 'translateX(0)';
            }, 100);
            
            // Auto remove
            if (duration > 0) {
                setTimeout(() => {
                    toast.style.opacity = '0';
                    toast.style.transform = 'translateX(100%)';
                    setTimeout(() => {
                        if (toast.parentNode) {
                            toast.parentNode.removeChild(toast);
                        }
                    }, 300);
                }, duration);
            }
            
            return toast;
        }

        function copyVoucherCode(voucherCode) {
            navigator.clipboard.writeText(voucherCode).then(function() {
                // Tạo thông báo thành công
                const toast = document.createElement('div');
                toast.style.cssText = `
                    position: fixed;
                    top: 20px;
                    right: 20px;
                    background: #27ae60;
                    color: white;
                    padding: 12px 20px;
                    border-radius: 8px;
                    z-index: 9999;
                    box-shadow: 0 4px 12px rgba(0,0,0,0.2);
                    font-weight: 500;
                `;
                toast.innerHTML = '<i class="fas fa-check"></i> Đã sao chép mã: ' + voucherCode;
                document.body.appendChild(toast);
                
                setTimeout(() => {
                    toast.style.opacity = '0';
                    toast.style.transition = 'opacity 0.3s';
                    setTimeout(() => toast.remove(), 300);
                }, 2000);
            }).catch(err => {
                alert('Không thể sao chép mã voucher');
            });
        }

        // Auto hide success alert
        const alertBox = document.querySelector('.alert-success');
        if (alertBox) {
            setTimeout(() => {
                alertBox.style.opacity = '0';
                setTimeout(() => alertBox.remove(), 500);
            }, 3000);
        }
        
    </script>
    <script src="js/checkout-helper.js"></script>
</body>
</html>
