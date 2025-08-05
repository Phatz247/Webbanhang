<?php
// controller/voucher_management.php
if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . "/../model/database.php";
$db = new database();
$conn = $db->getConnection();

$alert = "";

// Xử lý form thêm/sửa/xóa voucher
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Thêm voucher
    if (isset($_POST['add_voucher'])) {
        $mavoucher = strtoupper(trim($_POST['mavoucher']));
        $tenvoucher = trim($_POST['tenvoucher']);
        $mota = trim($_POST['mota']);
        $loaivoucher = $_POST['loaivoucher'];
        $giatri = floatval($_POST['giatri']);
        $giatrimin = floatval($_POST['giatrimin']);
        $giatrimax = !empty($_POST['giatrimax']) ? floatval($_POST['giatrimax']) : null;
        $soluong = intval($_POST['soluong']);
        $ngaybatdau = $_POST['ngaybatdau'];
        $ngayhethan = $_POST['ngayhethan'];
        $trangthai = $_POST['trangthai'];

        // Validate
        $errors = [];
        if (empty($mavoucher)) $errors[] = "Mã voucher không được để trống";
        if (empty($tenvoucher)) $errors[] = "Tên voucher không được để trống";
        if ($giatri <= 0 && $loaivoucher != 'freeship') $errors[] = "Giá trị voucher phải lớn hơn 0";
        if ($giatrimin < 0) $errors[] = "Giá trị đơn hàng tối thiểu không hợp lệ";
        if ($soluong <= 0) $errors[] = "Số lượng voucher phải lớn hơn 0";
        if (strtotime($ngayhethan) <= strtotime($ngaybatdau)) $errors[] = "Ngày hết hạn phải sau ngày bắt đầu";

        // Kiểm tra trùng mã
        $stmt = $conn->prepare("SELECT COUNT(*) FROM voucher WHERE MAVOUCHER = ?");
        $stmt->execute([$mavoucher]);
        if ($stmt->fetchColumn() > 0) {
            $errors[] = "Mã voucher đã tồn tại";
        }

        if (empty($errors)) {
            $stmt = $conn->prepare("INSERT INTO voucher (MAVOUCHER, TENVOUCHER, MOTA, LOAIVOUCHER, GIATRI, GIATRIMIN, GIATRIMAX, SOLUONG, NGAYBATDAU, NGAYHETHAN, TRANGTHAI) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            if ($stmt->execute([$mavoucher, $tenvoucher, $mota, $loaivoucher, $giatri, $giatrimin, $giatrimax, $soluong, $ngaybatdau, $ngayhethan, $trangthai])) {
                $_SESSION['alert_success'] = "✔️ Thêm voucher thành công!";
                header("Location: admin.php?section=voucher");
                exit;
            } else {
                $alert = "Có lỗi khi thêm voucher!";
            }
        } else {
            $alert = implode("<br>", $errors);
        }
    }

    // Sửa voucher
    if (isset($_POST['update_voucher'])) {
        $id = intval($_POST['id']);
        $mavoucher = strtoupper(trim($_POST['mavoucher']));
        $tenvoucher = trim($_POST['tenvoucher']);
        $mota = trim($_POST['mota']);
        $loaivoucher = $_POST['loaivoucher'];
        $giatri = floatval($_POST['giatri']);
        $giatrimin = floatval($_POST['giatrimin']);
        $giatrimax = !empty($_POST['giatrimax']) ? floatval($_POST['giatrimax']) : null;
        $soluong = intval($_POST['soluong']);
        $ngaybatdau = $_POST['ngaybatdau'];
        $ngayhethan = $_POST['ngayhethan'];
        $trangthai = $_POST['trangthai'];

        $stmt = $conn->prepare("UPDATE voucher SET TENVOUCHER=?, MOTA=?, LOAIVOUCHER=?, GIATRI=?, GIATRIMIN=?, GIATRIMAX=?, SOLUONG=?, NGAYBATDAU=?, NGAYHETHAN=?, TRANGTHAI=? WHERE ID=?");
        if ($stmt->execute([$tenvoucher, $mota, $loaivoucher, $giatri, $giatrimin, $giatrimax, $soluong, $ngaybatdau, $ngayhethan, $trangthai, $id])) {
            $_SESSION['alert_success'] = "✔️ Cập nhật voucher thành công!";
            header("Location: admin.php?section=voucher");
            exit;
        } else {
            $alert = "Có lỗi khi cập nhật voucher!";
        }
    }

    // Toggle trạng thái voucher
    if (isset($_POST['toggle_status'])) {
        $id = intval($_POST['id']);
        $current_status = $_POST['current_status'];
        $new_status = ($current_status === 'active') ? 'inactive' : 'active';
        
        $stmt = $conn->prepare("UPDATE voucher SET TRANGTHAI = ? WHERE ID = ?");
        if ($stmt->execute([$new_status, $id])) {
            $_SESSION['alert_success'] = "✔️ Đã cập nhật trạng thái voucher!";
            header("Location: admin.php?section=voucher");
            exit;
        }
    }
}

// Xóa voucher
if (isset($_GET['delete_voucher'])) {
    $id = intval($_GET['delete_voucher']);
    
    // Kiểm tra xem voucher đã được sử dụng chưa
    $stmt = $conn->prepare("SELECT COUNT(*) FROM voucher_usage WHERE MAVOUCHER = (SELECT MAVOUCHER FROM voucher WHERE ID = ?)");
    $stmt->execute([$id]);
    $usage_count = $stmt->fetchColumn();
    
    if ($usage_count > 0) {
        $_SESSION['alert_error'] = "❌ Không thể xóa voucher đã được sử dụng!";
    } else {
        $stmt = $conn->prepare("DELETE FROM voucher WHERE ID = ?");
        if ($stmt->execute([$id])) {
            $_SESSION['alert_success'] = "✔️ Đã xóa voucher thành công!";
        } else {
            $_SESSION['alert_error'] = "❌ Có lỗi khi xóa voucher!";
        }
    }
    header("Location: admin.php?section=voucher");
    exit;
}

// Lấy danh sách voucher
$search = $_GET['search'] ?? '';
$filter_status = $_GET['filter_status'] ?? '';
$filter_type = $_GET['filter_type'] ?? '';

$where_conditions = [];
$params = [];

if (!empty($search)) {
    $where_conditions[] = "(MAVOUCHER LIKE ? OR TENVOUCHER LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if (!empty($filter_status)) {
    $where_conditions[] = "TRANGTHAI = ?";
    $params[] = $filter_status;
}

if (!empty($filter_type)) {
    $where_conditions[] = "LOAIVOUCHER = ?";
    $params[] = $filter_type;
}

$where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

$stmt = $conn->prepare("SELECT *, 
    CASE 
        WHEN NOW() > NGAYHETHAN THEN 'expired'
        WHEN NOW() < NGAYBATDAU THEN 'upcoming'
        ELSE TRANGTHAI
    END as computed_status
    FROM voucher $where_clause ORDER BY NGAYTAO DESC");
$stmt->execute($params);
$vouchers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Lấy voucher để sửa
$editVoucher = null;
if (isset($_GET['edit_voucher'])) {
    $id = intval($_GET['edit_voucher']);
    $stmt = $conn->prepare("SELECT * FROM voucher WHERE ID = ?");
    $stmt->execute([$id]);
    $editVoucher = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Lấy thống kê voucher
$stmt = $conn->query("SELECT 
    COUNT(*) as total_vouchers,
    SUM(CASE WHEN TRANGTHAI = 'active' AND NOW() BETWEEN NGAYBATDAU AND NGAYHETHAN THEN 1 ELSE 0 END) as active_vouchers,
    SUM(CASE WHEN NOW() > NGAYHETHAN THEN 1 ELSE 0 END) as expired_vouchers,
    SUM(SOLUONGSUDUNG) as total_used
    FROM voucher");
$stats = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <title>Quản lý Voucher</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <style>
    body { background: #f6f6f7; }
    .form-section, .table-section, .stats-section {
      background: #fff;
      border-radius: 14px;
      padding: 25px;
      margin-top: 25px;
      box-shadow: 0 4px 18px rgba(0,0,0,0.06);
      border: 1px solid #e9ecef;
    }
    
    .stats-section {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      color: white;
      margin-bottom: 0;
    }
    
    .stats-row {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 20px;
    }
    
    .stat-card {
      background: rgba(255,255,255,0.1);
      padding: 20px;
      border-radius: 10px;
      text-align: center;
      backdrop-filter: blur(10px);
      border: 1px solid rgba(255,255,255,0.2);
    }
    
    .stat-number {
      font-size: 2rem;
      font-weight: bold;
      margin-bottom: 5px;
    }
    
    .stat-label {
      font-size: 0.9rem;
      opacity: 0.9;
    }

    .nav-tabs {
      border-bottom: 1.5px solid #e3e6ea;
    }
    
    .nav-tabs .nav-link.active {
      background: #1d8cf8;
      color: #fff;
      border-color: #1d8cf8 #1d8cf8 #fff;
      border-radius: 9px 9px 0 0;
      font-weight: 600;
    }
    
    .nav-tabs .nav-link {
      color: #585b63;
      font-weight: 500;
      border: none;
      border-radius: 9px 9px 0 0;
      transition: background .25s;
    }
    
    .nav-tabs .nav-link:not(.active):hover {
      background: #e8f0fe;
      color: #1d8cf8;
    }

    .form-label {
      font-weight: 500;
      color: #212529;
      font-size: 1rem;
    }
    
    .form-control, .form-select {
      border-radius: 7px;
      border: 1px solid #d1d9e6;
      font-size: 15px;
      background: #fafcff;
      transition: border-color .2s;
    }
    
    .form-control:focus, .form-select:focus {
      border-color: #1d8cf8;
      box-shadow: 0 0 0 1.5px #a4d5ff50;
      background: #fff;
    }

    .badge {
      font-size: .9em;
      padding: 0.5em 0.8em;
      border-radius: 8px;
      font-weight: 500;
    }
    
    .badge-success { background: #28c76f !important; }
    .badge-warning { background: #ff9f43 !important; }
    .badge-danger { background: #ea5455 !important; }
    .badge-secondary { background: #6c757d !important; }
    .badge-info { background: #00cfe8 !important; }
    .badge-primary { background: #7367f0 !important; }

    .voucher-type-percent { color: #28a745; }
    .voucher-type-fixed { color: #dc3545; }
    .voucher-type-freeship { color: #17a2b8; }

    .alert-fixed {
      position: fixed;
      top: 28px;
      right: 40px;
      min-width: 280px;
      z-index: 10000;
      font-size: 16px;
      padding: 16px 22px;
      border-radius: 9px;
      box-shadow: 0 8px 28px rgba(45, 157, 255, 0.12);
      font-weight: 600;
      border: none;
    }

    .btn {
      border-radius: 7px;
      font-weight: 500;
      letter-spacing: .2px;
      box-shadow: 0 2px 8px rgba(45, 157, 255, 0.03);
      border: none;
    }

    .table {
      border-radius: 9px;
      overflow: hidden;
      border: 1px solid #ecedf1;
      background: #fff;
    }
    
    .table th, .table td {
      vertical-align: middle;
      border-color: #edf1f7;
    }
    
    .table thead th {
      background: #f5f7fa;
      font-weight: 600;
      color: #47546b;
      border-bottom-width: 2px;
    }

    .search-filters {
      background: #f8f9fa;
      padding: 20px;
      border-radius: 10px;
      margin-bottom: 20px;
    }

    .voucher-value {
      font-weight: bold;
      font-size: 1.1em;
    }

    .usage-progress {
      width: 100px;
      height: 8px;
      background: #e9ecef;
      border-radius: 4px;
      overflow: hidden;
    }

    .usage-progress-bar {
      height: 100%;
      background: linear-gradient(90deg, #28a745, #20c997);
      transition: width 0.3s ease;
    }

    @media (max-width: 768px) {
      .form-section, .table-section, .stats-section { 
        padding: 15px; 
        margin-top: 15px;
      }
      
      .stats-row {
        grid-template-columns: 1fr;
        gap: 15px;
      }
      
      .alert-fixed { 
        right: 10px; 
        left: 10px; 
      }
    }
  </style>
</head>
<body>
<div class="container-fluid px-4">

    <!-- Thông báo -->
    <?php if ($alert): ?>
      <div class="alert alert-danger alert-fixed" id="alert-msg"><?= $alert ?></div>
    <?php endif; ?>
    <?php if (!empty($_SESSION['alert_success'])): ?>
      <div class="alert alert-success alert-fixed" id="alert-msg"><?= $_SESSION['alert_success']; unset($_SESSION['alert_success']); ?></div>
    <?php endif; ?>
    <?php if (!empty($_SESSION['alert_error'])): ?>
      <div class="alert alert-danger alert-fixed" id="alert-msg"><?= $_SESSION['alert_error']; unset($_SESSION['alert_error']); ?></div>
    <?php endif; ?>

    <!-- Thống kê tổng quan -->
    <div class="stats-section">
      <h4 class="mb-4"><i class="fas fa-chart-bar"></i> Thống kê Voucher</h4>
      <div class="stats-row">
        <div class="stat-card">
          <div class="stat-number"><?= $stats['total_vouchers'] ?></div>
          <div class="stat-label">Tổng voucher</div>
        </div>
        <div class="stat-card">
          <div class="stat-number"><?= $stats['active_vouchers'] ?></div>
          <div class="stat-label">Đang hoạt động</div>
        </div>
        <div class="stat-card">
          <div class="stat-number"><?= $stats['expired_vouchers'] ?></div>
          <div class="stat-label">Đã hết hạn</div>
        </div>
        <div class="stat-card">
          <div class="stat-number"><?= $stats['total_used'] ?></div>
          <div class="stat-label">Lượt sử dụng</div>
        </div>
      </div>
    </div>

    <!-- Form quản lý voucher -->
    <div class="form-section">
      <ul class="nav nav-tabs mb-4" id="voucherTab" role="tablist">
        <li class="nav-item" role="presentation">
          <button class="nav-link active" id="add-tab" data-bs-toggle="tab" data-bs-target="#add" type="button" role="tab">
            <i class="fas fa-plus"></i> Thêm Voucher
          </button>
        </li>
        <li class="nav-item" role="presentation">
          <button class="nav-link" id="list-tab" data-bs-toggle="tab" data-bs-target="#list" type="button" role="tab">
            <i class="fas fa-list"></i> Danh sách Voucher
          </button>
        </li>
      </ul>

      <div class="tab-content" id="voucherTabContent">
        <!-- Tab thêm/sửa voucher -->
        <div class="tab-pane fade show active" id="add" role="tabpanel">
          <h5><i class="fas fa-ticket-alt"></i> <?= $editVoucher ? 'Sửa' : 'Thêm' ?> Voucher</h5>
          
          <form method="POST" class="row g-3">
            <?php if ($editVoucher): ?>
              <input type="hidden" name="id" value="<?= $editVoucher['ID'] ?>">
            <?php endif; ?>
            
            <div class="col-md-4">
              <label class="form-label">Mã Voucher <span class="text-danger">*</span></label>
              <input name="mavoucher" class="form-control" 
                     value="<?= htmlspecialchars($editVoucher['MAVOUCHER'] ?? '') ?>" 
                     placeholder="VD: WELCOME10" 
                     <?= $editVoucher ? 'readonly' : '' ?>
                     required>
              <small class="text-muted">Chỉ được dùng chữ cái, số và dấu gạch dưới</small>
            </div>
            
            <div class="col-md-8">
              <label class="form-label">Tên Voucher <span class="text-danger">*</span></label>
              <input name="tenvoucher" class="form-control" 
                     value="<?= htmlspecialchars($editVoucher['TENVOUCHER'] ?? '') ?>" 
                     placeholder="Tên hiển thị của voucher"
                     required>
            </div>

            <div class="col-12">
              <label class="form-label">Mô tả</label>
              <textarea name="mota" class="form-control" rows="2" 
                        placeholder="Mô tả chi tiết về voucher"><?= htmlspecialchars($editVoucher['MOTA'] ?? '') ?></textarea>
            </div>

            <div class="col-md-3">
              <label class="form-label">Loại Voucher <span class="text-danger">*</span></label>
              <select name="loaivoucher" class="form-select" id="voucher-type" required>
                <option value="percent" <?= (($editVoucher['LOAIVOUCHER'] ?? '') === 'percent') ? 'selected' : '' ?>>Giảm theo %</option>
                <option value="fixed" <?= (($editVoucher['LOAIVOUCHER'] ?? '') === 'fixed') ? 'selected' : '' ?>>Giảm cố định</option>
                <option value="freeship" <?= (($editVoucher['LOAIVOUCHER'] ?? '') === 'freeship') ? 'selected' : '' ?>>Miễn phí ship</option>
              </select>
            </div>

            <div class="col-md-3" id="value-field">
              <label class="form-label">Giá trị <span class="text-danger">*</span></label>
              <div class="input-group">
                <input name="giatri" type="number" class="form-control" 
                       value="<?= $editVoucher['GIATRI'] ?? '' ?>" 
                       min="0" step="0.01">
                <span class="input-group-text" id="value-unit">%</span>
              </div>
            </div>

            <div class="col-md-3">
              <label class="form-label">Đơn hàng tối thiểu</label>
              <div class="input-group">
                <input name="giatrimin" type="number" class="form-control" 
                       value="<?= $editVoucher['GIATRIMIN'] ?? '' ?>" 
                       min="0" step="1000">
                <span class="input-group-text">đ</span>
              </div>
            </div>

            <div class="col-md-3" id="max-value-field">
              <label class="form-label">Giảm tối đa</label>
              <div class="input-group">
                <input name="giatrimax" type="number" class="form-control" 
                       value="<?= $editVoucher['GIATRIMAX'] ?? '' ?>" 
                       min="0" step="1000">
                <span class="input-group-text">đ</span>
              </div>
            </div>

            <div class="col-md-2">
              <label class="form-label">Số lượng <span class="text-danger">*</span></label>
              <input name="soluong" type="number" class="form-control" 
                     value="<?= $editVoucher['SOLUONG'] ?? '1' ?>" 
                     min="1" required>
            </div>

            <div class="col-md-3">
              <label class="form-label">Ngày bắt đầu <span class="text-danger">*</span></label>
              <input name="ngaybatdau" type="datetime-local" class="form-control" 
                     value="<?= isset($editVoucher['NGAYBATDAU']) ? date('Y-m-d\TH:i', strtotime($editVoucher['NGAYBATDAU'])) : '' ?>" 
                     required>
            </div>

            <div class="col-md-3">
              <label class="form-label">Ngày hết hạn <span class="text-danger">*</span></label>
              <input name="ngayhethan" type="datetime-local" class="form-control" 
                     value="<?= isset($editVoucher['NGAYHETHAN']) ? date('Y-m-d\TH:i', strtotime($editVoucher['NGAYHETHAN'])) : '' ?>" 
                     required>
            </div>

            <div class="col-md-2">
              <label class="form-label">Trạng thái</label>
              <select name="trangthai" class="form-select">
                <option value="active" <?= (($editVoucher['TRANGTHAI'] ?? 'active') === 'active') ? 'selected' : '' ?>>Kích hoạt</option>
                <option value="inactive" <?= (($editVoucher['TRANGTHAI'] ?? '') === 'inactive') ? 'selected' : '' ?>>Tạm dừng</option>
              </select>
            </div>

            <div class="col-12">
              <div class="d-flex gap-2">
                <?php if ($editVoucher): ?>
                  <button name="update_voucher" class="btn btn-warning">
                    <i class="fas fa-save"></i> Cập nhật Voucher
                  </button>
                  <a href="admin.php?section=voucher" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Hủy
                  </a>
                <?php else: ?>
                  <button name="add_voucher" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Thêm Voucher
                  </button>
                  <button type="reset" class="btn btn-secondary">
                    <i class="fas fa-redo"></i> Làm mới
                  </button>
                <?php endif; ?>
              </div>
            </div>
          </form>
        </div>

        <!-- Tab danh sách voucher -->
        <div class="tab-pane fade" id="list" role="tabpanel">
          <!-- Bộ lọc và tìm kiếm -->
          <div class="search-filters">
            <form method="GET" class="row g-3 align-items-end">
              <input type="hidden" name="section" value="voucher">
              
              <div class="col-md-4">
                <label class="form-label">Tìm kiếm</label>
                <input name="search" class="form-control" 
                       value="<?= htmlspecialchars($search) ?>" 
                       placeholder="Tìm theo mã hoặc tên voucher">
              </div>
              
              <div class="col-md-3">
                <label class="form-label">Trạng thái</label>
                <select name="filter_status" class="form-select">
                  <option value="">Tất cả trạng thái</option>
                  <option value="active" <?= $filter_status === 'active' ? 'selected' : '' ?>>Đang hoạt động</option>
                  <option value="inactive" <?= $filter_status === 'inactive' ? 'selected' : '' ?>>Tạm dừng</option>
                  <option value="expired" <?= $filter_status === 'expired' ? 'selected' : '' ?>>Hết hạn</option>
                </select>
              </div>
              
              <div class="col-md-3">
                <label class="form-label">Loại voucher</label>
                <select name="filter_type" class="form-select">
                  <option value="">Tất cả loại</option>
                  <option value="percent" <?= $filter_type === 'percent' ? 'selected' : '' ?>>Giảm theo %</option>
                  <option value="fixed" <?= $filter_type === 'fixed' ? 'selected' : '' ?>>Giảm cố định</option>
                  <option value="freeship" <?= $filter_type === 'freeship' ? 'selected' : '' ?>>Miễn phí ship</option>
                </select>
              </div>
              
              <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">
                  <i class="fas fa-search"></i> Lọc
                </button>
              </div>
            </form>
          </div>

          <!-- Bảng danh sách voucher -->
          <div class="table-responsive">
            <table class="table table-hover">
              <thead>
                <tr>
                  <th>Mã Voucher</th>
                  <th>Tên & Mô tả</th>
                  <th>Loại & Giá trị</th>
                  <th>Điều kiện</th>
                  <th>Thời gian</th>
                  <th>Sử dụng</th>
                  <th>Trạng thái</th>
                  <th>Thao tác</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach($vouchers as $v): 
                  $usage_percent = $v['SOLUONG'] > 0 ? ($v['SOLUONGSUDUNG'] / $v['SOLUONG']) * 100 : 0;
                  $is_expired = strtotime($v['NGAYHETHAN']) < time();
                  $is_upcoming = strtotime($v['NGAYBATDAU']) > time();
                ?>
                <tr>
                  <td>
                    <strong class="text-primary"><?= htmlspecialchars($v['MAVOUCHER']) ?></strong>
                  </td>
                  
                  <td>
                    <div class="fw-bold"><?= htmlspecialchars($v['TENVOUCHER']) ?></div>
                    <?php if ($v['MOTA']): ?>
                      <small class="text-muted"><?= htmlspecialchars($v['MOTA']) ?></small>
                    <?php endif; ?>
                  </td>
                  
                  <td>
                    <?php if ($v['LOAIVOUCHER'] === 'percent'): ?>
                      <span class="voucher-type-percent">
                        <i class="fas fa-percentage"></i> <?= number_format($v['GIATRI']) ?>%
                      </span>
                    <?php elseif ($v['LOAIVOUCHER'] === 'fixed'): ?>
                      <span class="voucher-type-fixed">
                        <i class="fas fa-money-bill"></i> <?= number_format($v['GIATRI']) ?>đ
                      </span>
                    <?php else: ?>
                      <span class="voucher-type-freeship">
                        <i class="fas fa-shipping-fast"></i> Miễn phí ship
                      </span>
                    <?php endif; ?>
                    
                    <?php if ($v['GIATRIMAX'] && $v['LOAIVOUCHER'] === 'percent'): ?>
                      <br><small class="text-muted">Tối đa: <?= number_format($v['GIATRIMAX']) ?>đ</small>
                    <?php endif; ?>
                  </td>
                  
                  <td>
                    <?php if ($v['GIATRIMIN'] > 0): ?>
                      <small>Đơn tối thiểu:<br><strong><?= number_format($v['GIATRIMIN']) ?>đ</strong></small>
                    <?php else: ?>
                      <small class="text-muted">Không yêu cầu</small>
                    <?php endif; ?>
                  </td>
                  
                  <td>
                    <small>
                      <strong>Từ:</strong> <?= date('d/m/Y H:i', strtotime($v['NGAYBATDAU'])) ?><br>
                      <strong>Đến:</strong> <?= date('d/m/Y H:i', strtotime($v['NGAYHETHAN'])) ?>
                    </small>
                  </td>
                  
                  <td>
                    <div class="usage-progress mb-1">
                      <div class="usage-progress-bar" style="width: <?= $usage_percent ?>%"></div>
                    </div>
                    <small><?= $v['SOLUONGSUDUNG'] ?>/<?= $v['SOLUONG'] ?> lượt</small>
                  </td>
                  
                  <td>
                    <?php if ($is_expired): ?>
                      <span class="badge badge-danger">Hết hạn</span>
                    <?php elseif ($is_upcoming): ?>
                      <span class="badge badge-warning">Sắp diễn ra</span>
                    <?php elseif ($v['TRANGTHAI'] === 'active'): ?>
                      <span class="badge badge-success">Hoạt động</span>
                    <?php else: ?>
                      <span class="badge badge-secondary">Tạm dừng</span>
                    <?php endif; ?>
                  </td>
                  
                  <td>
                    <div class="btn-group" role="group">
                      <a href="admin.php?section=voucher&edit_voucher=<?= $v['ID'] ?>" 
                         class="btn btn-sm btn-outline-warning" title="Sửa">
                        <i class="fas fa-edit"></i>
                      </a>
                      
                      <?php if (!$is_expired): ?>
                        <form method="POST" style="display: inline;">
                          <input type="hidden" name="id" value="<?= $v['ID'] ?>">
                          <input type="hidden" name="current_status" value="<?= $v['TRANGTHAI'] ?>">
                          <button name="toggle_status" type="submit" 
                                  class="btn btn-sm btn-outline-<?= $v['TRANGTHAI'] === 'active' ? 'secondary' : 'success' ?>" 
                                  title="<?= $v['TRANGTHAI'] === 'active' ? 'Tạm dừng' : 'Kích hoạt' ?>">
                            <i class="fas fa-<?= $v['TRANGTHAI'] === 'active' ? 'pause' : 'play' ?>"></i>
                          </button>
                        </form>
                      <?php endif; ?>
                      
                      <a href="admin.php?section=voucher&delete_voucher=<?= $v['ID'] ?>" 
                         class="btn btn-sm btn-outline-danger" 
                         onclick="return confirm('Bạn có chắc muốn xóa voucher này?')" title="Xóa">
                        <i class="fas fa-trash"></i>
                      </a>
                    </div>
                  </td>
                </tr>
                <?php endforeach; ?>
                
                <?php if (empty($vouchers)): ?>
                <tr>
                  <td colspan="8" class="text-center text-muted py-4">
                    <i class="fas fa-ticket-alt fa-2x mb-2"></i>
                    <br>Không tìm thấy voucher nào
                  </td>
                </tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Xử lý thay đổi loại voucher
document.getElementById('voucher-type').addEventListener('change', function() {
  const type = this.value;
  const valueField = document.getElementById('value-field');
  const maxValueField = document.getElementById('max-value-field');
  const valueUnit = document.getElementById('value-unit');
  const valueInput = document.querySelector('input[name="giatri"]');
  
  if (type === 'freeship') {
    valueField.style.display = 'none';
    maxValueField.style.display = 'none';
    valueInput.value = '0';
  } else {
    valueField.style.display = 'block';
    
    if (type === 'percent') {
      valueUnit.textContent = '%';
      maxValueField.style.display = 'block';
      valueInput.max = '100';
      valueInput.step = '0.1';
    } else if (type === 'fixed') {
      valueUnit.textContent = 'đ';
      maxValueField.style.display = 'none';
      valueInput.max = '';
      valueInput.step = '1000';
    }
  }
});

// Trigger change event on page load để set đúng trạng thái
document.getElementById('voucher-type').dispatchEvent(new Event('change'));

// Auto ẩn alert sau 3s
window.onload = function() {
  var alert = document.getElementById('alert-msg');
  if(alert){
    setTimeout(() => {
      alert.style.opacity = '0';
      setTimeout(() => alert.style.display = 'none', 600);
    }, 3000);
  }
}

// Validation form
document.querySelector('form').addEventListener('submit', function(e) {
  const type = document.getElementById('voucher-type').value;
  const value = parseFloat(document.querySelector('input[name="giatri"]').value) || 0;
  
  if (type === 'percent' && (value <= 0 || value > 100)) {
    e.preventDefault();
    alert('Giá trị giảm theo % phải từ 0.1 đến 100');
    return;
  }
  
  if (type === 'fixed' && value <= 0) {
    e.preventDefault();
    alert('Giá trị giảm cố định phải lớn hơn 0');
    return;
  }
});
</script>
</body>
</html>