<?php
// Nếu là file riêng biệt, kiểm tra session:
if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . "/../model/database.php";
$db = new database();
$conn = $db->getConnection();

$alert = "";

// Lấy danh sách chương trình khuyến mãi
$stmt = $conn->query("SELECT * FROM chuongtrinhkhuyenmai ORDER BY NGAYBATDAU DESC");
$chuongtrinhkm = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Lấy sản phẩm có sale hiện tại
$stmt = $conn->query("SELECT sp.MASP, sp.TENSP, sp.GIA, ct.gia_khuyenmai, ct.giam_phantram, ctkm.TENCTKM, ctkm.MACTKM
                      FROM sanpham sp 
                      JOIN chitietctkm ct ON sp.MASP = ct.MASP
                      JOIN chuongtrinhkhuyenmai ctkm ON ct.MACTKM = ctkm.MACTKM
                      WHERE NOW() BETWEEN ctkm.NGAYBATDAU AND ctkm.NGAYKETTHUC
                      ORDER BY sp.TENSP");
$sanpham_co_sale = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Xử lý form thêm/sửa/xóa/gán sale
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Thêm CTKM
    if (isset($_POST['add_sale_program'])) {
        $tenctkm = $_POST['tenctkm'];
        $ngaybatdau = $_POST['ngaybatdau'];
        $ngayketthuc = $_POST['ngayketthuc'];
        $mota = $_POST['mota'];

        $stmt = $conn->query("SELECT MAX(CAST(SUBSTRING(MACTKM, 3) AS UNSIGNED)) AS max_mactkm FROM chuongtrinhkhuyenmai");
        $row = $stmt->fetch();
        $nextNumber = ($row['max_mactkm'] ?? 0) + 1;
        $mactkm = 'CT' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);

        $stmt = $conn->prepare("INSERT INTO chuongtrinhkhuyenmai (MACTKM, TENCTKM, NGAYBATDAU, NGAYKETTHUC, MOTA) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$mactkm, $tenctkm, $ngaybatdau, $ngayketthuc, $mota]);
        $_SESSION['alert_success'] = "✔️ Thêm chương trình khuyến mãi thành công!";
        header("Location: khuyenmai.php"); exit;
    }
    // Sửa CTKM
    if (isset($_POST['update_sale_program'])) {
        $mactkm = $_POST['mactkm'];
        $tenctkm = $_POST['tenctkm'];
        $ngaybatdau = $_POST['ngaybatdau'];
        $ngayketthuc = $_POST['ngayketthuc'];
        $mota = $_POST['mota'];
        $stmt = $conn->prepare("UPDATE chuongtrinhkhuyenmai SET TENCTKM=?, NGAYBATDAU=?, NGAYKETTHUC=?, MOTA=? WHERE MACTKM=?");
        $stmt->execute([$tenctkm, $ngaybatdau, $ngayketthuc, $mota, $mactkm]);
        $_SESSION['alert_success'] = "✔️ Đã cập nhật chương trình!";
        header("Location: khuyenmai.php"); exit;
    }
    // Gán sale cho sản phẩm
    if (isset($_POST['action']) && $_POST['action'] === 'assign_sale') {
        $mactkm = $_POST['mactkm'];
        $masp = $_POST['masp'];
        $sale_type = $_POST['sale_type'];
        $gia_khuyenmai = $sale_type === 'fixed' ? $_POST['gia_khuyenmai'] : null;
        $giam_phantram = $sale_type === 'percent' ? $_POST['giam_phantram'] : null;

        // Check liên kết
        $stmt = $conn->prepare("SELECT * FROM chitietctkm WHERE MASP = ? AND MACTKM = ?");
        $stmt->execute([$masp, $mactkm]);
        if ($stmt->fetch()) {
            $stmt = $conn->prepare("UPDATE chitietctkm SET gia_khuyenmai = ?, giam_phantram = ? WHERE MASP = ? AND MACTKM = ?");
            $stmt->execute([$gia_khuyenmai, $giam_phantram, $masp, $mactkm]);
        } else {
            $stmt = $conn->prepare("INSERT INTO chitietctkm (MASP, MACTKM, gia_khuyenmai, giam_phantram) VALUES (?, ?, ?, ?)");
            $stmt->execute([$masp, $mactkm, $gia_khuyenmai, $giam_phantram]);
        }
        $_SESSION['alert_success'] = "✔️ Gán sale cho sản phẩm thành công!";
        header("Location: khuyenmai.php"); exit;
    }
}
// Xoá CTKM
if (isset($_GET['delete_sale'])) {
    $mactkm = $_GET['delete_sale'];
    $stmt = $conn->prepare("DELETE FROM chuongtrinhkhuyenmai WHERE MACTKM = ?");
    $stmt->execute([$mactkm]);
    $_SESSION['alert_success'] = "✔️ Đã xóa chương trình khuyến mãi!";
    header("Location: khuyenmai.php"); exit;
}
// Sửa chương trình
$editSaleProgram = null;
if (isset($_GET['edit_sale'])) {
    $mactkm = $_GET['edit_sale'];
    $stmt = $conn->prepare("SELECT * FROM chuongtrinhkhuyenmai WHERE MACTKM = ?");
    $stmt->execute([$mactkm]);
    $editSaleProgram = $stmt->fetch(PDO::FETCH_ASSOC);
}
// Xóa sale khỏi sản phẩm
if (isset($_GET['remove_sale'])) {
    $masp = $_GET['remove_sale'];
    $mactkm = $_GET['mactkm'];
    $stmt = $conn->prepare("DELETE FROM chitietctkm WHERE MASP = ? AND MACTKM = ?");
    $stmt->execute([$masp, $mactkm]);
    $_SESSION['alert_success'] = "✔️ Đã xóa sale khỏi sản phẩm!";
    header("Location: khuyenmai.php"); exit;
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <title>Quản lý khuyến mãi</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body { background: #f6f6f7; }
    .form-section, .table-section {
      background: #fff;
      border-radius: 14px;
      padding: 30px 28px 26px 28px;
      margin-top: 32px;
      box-shadow: 0 4px 18px rgba(0,0,0,0.06);
      border: 1px solid #e9ecef;
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
      letter-spacing: .5px;
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
    .table-section h5, .form-section h4 {
      font-weight: 600;
      letter-spacing: 0.5px;
      margin-bottom: 18px;
      color: #1d8cf8;
    }
    .form-label {
      font-weight: 500;
      color: #212529;
      font-size: 1rem;
    }
    .form-control, .form-select, textarea.form-control {
      border-radius: 7px;
      border: 1px solid #d1d9e6;
      font-size: 15px;
      background: #fafcff;
      transition: border-color .2s;
    }
    .form-control:focus, .form-select:focus, textarea.form-control:focus {
      border-color: #1d8cf8;
      box-shadow: 0 0 0 1.5px #a4d5ff50;
      background: #fff;
    }
    .alert-fixed {
      position: fixed;
      top: 28px;
      right: 40px;
      min-width: 280px;
      z-index: 10000;
      font-size: 17px;
      padding: 16px 22px;
      border-radius: 9px;
      box-shadow: 0 8px 28px rgba(45, 157, 255, 0.12);
      font-weight: 600;
      letter-spacing: 0.2px;
      border: none;
    }
    .badge {
      font-size: .93em;
      font-weight: 500;
      padding: 0.44em 0.85em;
      border-radius: 12px;
      letter-spacing: .4px;
    }
    .badge.bg-success { background: #28c76f !important; }
    .badge.bg-secondary { background: #d8d9fd !important; color: #3949ab;}
    .badge.bg-dark { background: #60646b !important; }
    .badge.bg-warning { background: #ffed9b !important; color: #a37605; }
    .badge.bg-info { background: #17a2b8 !important; }
    .badge.bg-danger { background: #f14c56 !important; }
    .badge.bg-primary { background: #1d8cf8 !important; }
    .price-original { text-decoration: line-through; color: #a3adb8; font-size: 1em; }
    .price-sale { color: #f14c56; font-weight: bold; font-size: 1.06em; }
    .btn-sm { padding: .35rem .75rem; font-size: .95rem; border-radius: 6px;}
    .btn-success, .btn-warning, .btn-primary, .btn-danger {
      border-radius: 7px;
      font-weight: 500;
      letter-spacing: .2px;
      box-shadow: 0 2px 8px rgba(45, 157, 255, 0.03);
      border: none;
    }
    table.table {
      border-radius: 9px;
      overflow: hidden;
      border: 1px solid #ecedf1;
      background: #fff;
      margin-bottom: 0;
    }
    table.table th, table.table td {
      vertical-align: middle;
      border-color: #edf1f7;
      background: #fff;
    }
    table.table thead th {
      background: #f5f7fa;
      font-weight: 600;
      color: #47546b;
      border-bottom-width: 2px;
    }
    .table-responsive { border-radius: 10px; }
    @media (max-width: 767px) {
      .form-section, .table-section { padding: 12px 5px !important; }
      .alert-fixed { right: 10px; left: 10px; }
      .table-responsive { padding: 0; }
    }
  </style>
</head>
<body>
<div class="container py-4">

    <?php if ($alert): ?>
      <div class="alert alert-danger alert-fixed" id="alert-msg"><?= $alert ?></div>
    <?php endif; ?>
    <?php if (!empty($_SESSION['alert_success'])): ?>
      <div class="alert alert-success alert-fixed" id="alert-msg"><?= $_SESSION['alert_success']; unset($_SESSION['alert_success']); ?></div>
    <?php endif; ?>

    <!-- FORM CHƯƠNG TRÌNH KHUYẾN MÃI -->
    <div class="form-section">
      <ul class="nav nav-tabs mb-3" id="saleTab" role="tablist">
        <li class="nav-item" role="presentation">
          <button class="nav-link active" id="program-tab" data-bs-toggle="tab" data-bs-target="#program" type="button" role="tab">Chương trình KM</button>
        </li>
        <li class="nav-item" role="presentation">
          <button class="nav-link" id="assign-tab" data-bs-toggle="tab" data-bs-target="#assign" type="button" role="tab">Gán Sale</button>
        </li>
      </ul>
      <div class="tab-content" id="saleTabContent">
        <!-- Thêm/sửa chương trình -->
        <div class="tab-pane fade show active" id="program" role="tabpanel">
          <h4>Quản lý Chương trình khuyến mãi</h4>
         <form method="POST" class="row g-3 align-items-end">
  <input type="hidden" name="mactkm" value="<?= $editSaleProgram['MACTKM'] ?? '' ?>">
  <div class="col-md-3">
    <label class="form-label">Tên chương trình</label>
    <input name="tenctkm" class="form-control" value="<?= htmlspecialchars($editSaleProgram['TENCTKM'] ?? '') ?>" required>
  </div>
  <div class="col-md-2">
    <label class="form-label">Ngày bắt đầu</label>
    <input name="ngaybatdau" type="datetime-local" class="form-control" value="<?= isset($editSaleProgram['NGAYBATDAU']) ? date('Y-m-d\TH:i', strtotime($editSaleProgram['NGAYBATDAU'])) : '' ?>" required>
  </div>
  <div class="col-md-2">
    <label class="form-label">Ngày kết thúc</label>
    <input name="ngayketthuc" type="datetime-local" class="form-control" value="<?= isset($editSaleProgram['NGAYKETTHUC']) ? date('Y-m-d\TH:i', strtotime($editSaleProgram['NGAYKETTHUC'])) : '' ?>" required>
  </div>
  <div class="col-md-3">
    <label class="form-label">Mô tả</label>
    <input name="mota" class="form-control" value="<?= htmlspecialchars($editSaleProgram['MOTA'] ?? '') ?>">
  </div>
  <div class="col-md-2 d-flex align-items-end">
    <?php if ($editSaleProgram): ?>
      <button name="update_sale_program" class="btn btn-warning w-100">Cập nhật</button>
    <?php else: ?>
      <button name="add_sale_program" class="btn btn-primary w-100">Thêm</button>
    <?php endif; ?>
  </div>
</form>

        </div>
        <!-- Gán sale cho sản phẩm -->
        <div class="tab-pane fade" id="assign" role="tabpanel">
          <h4>Gán Sale cho sản phẩm</h4>
          <form method="POST" class="row g-3">
            <input type="hidden" name="action" value="assign_sale">
            <div class="col-md-3">
              <label class="form-label">Chương trình KM</label>
              <select name="mactkm" class="form-select" required>
                <option value="">-- Chọn chương trình --</option>
                <?php foreach($chuongtrinhkm as $ctkm): ?>
                  <option value="<?= $ctkm['MACTKM'] ?>"><?= htmlspecialchars($ctkm['TENCTKM']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-2">
              <label class="form-label">Mã sản phẩm</label>
              <input name="masp" class="form-control" required placeholder="VD: SP001A">
            </div>
            <div class="col-md-2">
              <label class="form-label">Loại giảm giá</label>
              <select name="sale_type" class="form-select" onchange="toggleSaleInputs()" required>
                <option value="fixed">Giá cố định</option>
                <option value="percent">Giảm theo %</option>
              </select>
            </div>
            <div class="col-md-2" id="fixed-price-input">
              <label class="form-label">Giá khuyến mãi</label>
              <input name="gia_khuyenmai" type="number" class="form-control" min="0">
            </div>
            <div class="col-md-2" id="percent-input" style="display: none;">
              <label class="form-label">Giảm (%)</label>
              <input name="giam_phantram" type="number" class="form-control" min="1" max="99">
            </div>
            <div class="col-md-1 d-flex align-items-end">
              <button class="btn btn-success w-100">Gán Sale</button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- Danh sách chương trình khuyến mãi -->
    <div class="table-section mb-4">
      <h5>Danh sách chương trình khuyến mãi</h5>
      <div class="table-responsive">
        <table class="table table-bordered table-hover align-middle">
          <thead>
            <tr>
              <th>Mã CTKM</th>
              <th>Tên chương trình</th>
              <th>Ngày bắt đầu</th>
              <th>Ngày kết thúc</th>
              <th>Trạng thái</th>
              <th>Mô tả</th>
              <th>Thao tác</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach($chuongtrinhkm as $ctkm): ?>
            <tr>
              <td><?= htmlspecialchars($ctkm['MACTKM']) ?></td>
              <td><?= htmlspecialchars($ctkm['TENCTKM']) ?></td>
              <td><?= date('d/m/Y H:i', strtotime($ctkm['NGAYBATDAU'])) ?></td>
              <td><?= date('d/m/Y H:i', strtotime($ctkm['NGAYKETTHUC'])) ?></td>
              <td>
                <?php
                  $now = date('Y-m-d H:i:s');
                  if ($now < $ctkm['NGAYBATDAU']) {
                    echo '<span class="badge bg-secondary">Chưa bắt đầu</span>';
                  } elseif ($now > $ctkm['NGAYKETTHUC']) {
                    echo '<span class="badge bg-dark">Đã kết thúc</span>';
                  } else {
                    echo '<span class="badge bg-success">Đang diễn ra</span>';
                  }
                ?>
              </td>
              <td><?= htmlspecialchars($ctkm['MOTA'] ?? '') ?></td>
              <td>
                <a href="?edit_sale=<?= $ctkm['MACTKM'] ?>" class="btn btn-sm btn-warning me-1">Sửa</a>
                <a href="?delete_sale=<?= $ctkm['MACTKM'] ?>" class="btn btn-sm btn-danger"
                  onclick="return confirm('Bạn có chắc muốn xóa chương trình này?')">Xóa</a>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Danh sách sản phẩm có sale -->
    <div class="table-section">
      <h5>Sản phẩm đang có sale</h5>
      <div class="table-responsive">
        <table class="table table-striped table-hover align-middle">
          <thead>
            <tr>
              <th>Mã SP</th>
              <th>Tên sản phẩm</th>
              <th>Giá gốc</th>
              <th>Giá sale</th>
              <th>Chương trình</th>
              <th>Thao tác</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach($sanpham_co_sale as $sp_sale): ?>
            <tr>
              <td><?= htmlspecialchars($sp_sale['MASP']) ?></td>
              <td><?= htmlspecialchars($sp_sale['TENSP']) ?></td>
              <td><span class="price-original"><?= number_format($sp_sale['GIA']) ?>đ</span></td>
              <td class="price-sale">
                <?php 
                  if ($sp_sale['gia_khuyenmai']) {
                    echo number_format($sp_sale['gia_khuyenmai']) . 'đ';
                  } else {
                    $gia_sale = $sp_sale['GIA'] * (1 - $sp_sale['giam_phantram']/100);
                    echo number_format($gia_sale) . 'đ <span class="badge bg-warning ms-1">-' . $sp_sale['giam_phantram'] . '%</span>';
                  }
                ?>
              </td>
              <td><span class="badge bg-primary"><?= htmlspecialchars($sp_sale['TENCTKM']) ?></span></td>
              <td>
                <a href="?remove_sale=<?= $sp_sale['MASP'] ?>&mactkm=<?= $sp_sale['MACTKM'] ?>"
                   class="btn btn-sm btn-danger"
                   onclick="return confirm('Bạn có chắc muốn xóa sale khỏi sản phẩm này?')">
                  Xóa Sale
                </a>
              </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($sanpham_co_sale)): ?>
            <tr>
              <td colspan="6" class="text-center text-muted">Không có sản phẩm nào đang sale</td>
            </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
</div>

<script>
function toggleSaleInputs() {
  const saleType = document.querySelector('select[name="sale_type"]').value;
  const fixedInput = document.getElementById('fixed-price-input');
  const percentInput = document.getElementById('percent-input');
  if (saleType === 'fixed') {
    fixedInput.style.display = 'block';
    percentInput.style.display = 'none';
  } else {
    fixedInput.style.display = 'none';
    percentInput.style.display = 'block';
  }
}
// Auto ẩn alert sau 2s
window.onload = function() {
  var alert = document.getElementById('alert-msg');
  if(alert){
    setTimeout(() => {
      alert.style.opacity = '0';
      setTimeout(() => alert.style.display = 'none', 600);
    }, 2200);
  }
}
</script>
</body>
</html>
