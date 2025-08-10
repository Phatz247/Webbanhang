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

// Lấy tất cả sản phẩm để hiển thị trong danh sách gán sale
// Bao gồm trạng thái chương trình: đang diễn ra, sắp diễn ra, đã kết thúc, hoặc chưa có
$stmt = $conn->query("SELECT sp.MASP, sp.TENSP, sp.GIA, sp.MAUSAC, sp.KICHTHUOC, sp.SOLUONG,
                      ct.gia_khuyenmai, ct.giam_phantram, ctkm.TENCTKM, ctkm.MACTKM,
                      ctkm.NGAYBATDAU, ctkm.NGAYKETTHUC,
                      CASE 
                        WHEN ctkm.MACTKM IS NULL THEN 'no_sale'
                        WHEN NOW() BETWEEN ctkm.NGAYBATDAU AND ctkm.NGAYKETTHUC THEN 'active_sale'
                        WHEN NOW() < ctkm.NGAYBATDAU THEN 'future_sale'
                        WHEN NOW() > ctkm.NGAYKETTHUC THEN 'expired_sale'
                        ELSE 'no_sale'
                      END AS sale_status
                      FROM sanpham sp 
                      LEFT JOIN chitietctkm ct ON sp.MASP = ct.MASP
                      LEFT JOIN chuongtrinhkhuyenmai ctkm ON ct.MACTKM = ctkm.MACTKM
                      WHERE sp.IS_DELETED = 0
                      ORDER BY sp.TENSP, sp.MAUSAC, sp.KICHTHUOC");
$all_products = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
        echo "<script>window.location.href = '/web_3/view/admin.php?section=khuyenmai';</script>"; exit;
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
        echo "<script>window.location.href = '/web_3/view/admin.php?section=khuyenmai';</script>"; exit;
    }
    // Gán sale cho sản phẩm
  if (isset($_POST['action']) && $_POST['action'] === 'assign_sale') {
    $mactkm = $_POST['mactkm'];
    $masp = trim($_POST['masp']);
    $sale_type = $_POST['sale_type'];
    $gia_khuyenmai = $sale_type === 'fixed' ? ($_POST['gia_khuyenmai'] !== '' ? (int)$_POST['gia_khuyenmai'] : null) : null;
    $giam_phantram = $sale_type === 'percent' ? ($_POST['giam_phantram'] !== '' ? (int)$_POST['giam_phantram'] : null) : null;
    $apply_group_color = !empty($_POST['apply_group_color']);

    // Validate: only one type is provided
    if ($sale_type === 'fixed' && ($gia_khuyenmai === null || $gia_khuyenmai < 0)) {
      $_SESSION['alert_success'] = "⚠️ Vui lòng nhập giá khuyến mãi hợp lệ.";
      echo "<script>window.location.href = '/web_3/view/admin.php?section=khuyenmai';</script>"; exit;
    }
    if ($sale_type === 'percent' && ($giam_phantram === null || $giam_phantram < 1 || $giam_phantram > 99)) {
      $_SESSION['alert_success'] = "⚠️ Vui lòng nhập phần trăm giảm từ 1 đến 99.";
      echo "<script>window.location.href = '/web_3/view/admin.php?section=khuyenmai';</script>"; exit;
    }

    // Helper to upsert a single MASP
    $upsert = function($maspItem) use ($conn, $mactkm, $gia_khuyenmai, $giam_phantram) {
      $stmt = $conn->prepare("SELECT 1 FROM chitietctkm WHERE MASP = ? AND MACTKM = ?");
      $stmt->execute([$maspItem, $mactkm]);
      if ($stmt->fetchColumn()) {
        $stmt = $conn->prepare("UPDATE chitietctkm SET gia_khuyenmai = ?, giam_phantram = ? WHERE MASP = ? AND MACTKM = ?");
        $stmt->execute([$gia_khuyenmai, $giam_phantram, $maspItem, $mactkm]);
      } else {
        $stmt = $conn->prepare("INSERT INTO chitietctkm (MASP, MACTKM, gia_khuyenmai, giam_phantram) VALUES (?, ?, ?, ?)");
        $stmt->execute([$maspItem, $mactkm, $gia_khuyenmai, $giam_phantram]);
      }
    };

    $affected = 0;
    if ($apply_group_color) {
      // Find the group and color of the provided MASP
      $stmt = $conn->prepare("SELECT GROUPSP, MAUSAC FROM sanpham WHERE MASP = ? LIMIT 1");
      $stmt->execute([$masp]);
      $src = $stmt->fetch(PDO::FETCH_ASSOC);
      if ($src && $src['GROUPSP']) {
        // Apply to all variants in the same group and color (i.e., all sizes of this color)
        $stmt = $conn->prepare("SELECT MASP FROM sanpham WHERE GROUPSP = ? AND MAUSAC = ? AND IS_DELETED = 0");
        $stmt->execute([$src['GROUPSP'], $src['MAUSAC']]);
        $variants = $stmt->fetchAll(PDO::FETCH_COLUMN);
        if ($variants) {
          $conn->beginTransaction();
          try {
            foreach ($variants as $vmasp) {
              $upsert($vmasp);
              $affected++;
            }
            $conn->commit();
          } catch (Exception $e) {
            $conn->rollBack();
            throw $e;
          }
        }
      } else {
        // Fallback to single when MASP not found
        $upsert($masp);
        $affected = 1;
      }
    } else {
      // Only this variant
      $upsert($masp);
      $affected = 1;
    }

    $_SESSION['alert_success'] = "✔️ Đã gán sale cho " . ($affected ?: 1) . " biến thể!";
    echo "<script>window.location.href = '/web_3/view/admin.php?section=khuyenmai';</script>"; exit;
  }
}
// Xoá CTKM
if (isset($_GET['delete_sale'])) {
    $mactkm = $_GET['delete_sale'];
    $stmt = $conn->prepare("DELETE FROM chuongtrinhkhuyenmai WHERE MACTKM = ?");
    $stmt->execute([$mactkm]);
    $_SESSION['alert_success'] = "✔️ Đã xóa chương trình khuyến mãi!";
    echo "<script>window.location.href = '/web_3/view/admin.php?section=khuyenmai';</script>"; exit;
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
    echo "<script>window.location.href = '/web_3/view/admin.php?section=khuyenmai';</script>"; exit;
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <title>Quản lý khuyến mãi</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
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
        <li class="nav-item" role="presentation">
          <button class="nav-link" id="products-tab" data-bs-toggle="tab" data-bs-target="#products" type="button" role="tab">Danh sách SP</button>
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
            <div class="col-md-3">
              <label class="form-label">Mã sản phẩm</label>
              <input name="masp" class="form-control" required placeholder="VD: SP001A">
              <div class="form-check mt-2">
                <input class="form-check-input" type="checkbox" value="1" id="apply_group_color" name="apply_group_color">
                <label class="form-check-label" for="apply_group_color">
                  Áp dụng cho tất cả size của cùng màu
                </label>
              </div>
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
            <div class="col-md-2">
              <label class="form-label invisible">Hành động</label>
              <button class="btn btn-success w-100">Gán Sale</button>
            </div>
            
          </form>
        </div>
        
        <!-- Danh sách tất cả sản phẩm -->
        <div class="tab-pane fade" id="products" role="tabpanel">
          <h4>Danh sách tất cả sản phẩm</h4>
          <div class="row mb-3">
            <div class="col-md-4">
              <input type="text" id="searchProduct" class="form-control" placeholder="🔍 Tìm kiếm theo tên hoặc mã sản phẩm...">
            </div>
            <div class="col-md-3">
              <select id="filterSaleStatus" class="form-select">
                <option value="">-- Tất cả trạng thái --</option>
                <option value="active_sale">Đang diễn ra</option>
                <option value="future_sale">Sắp diễn ra</option>
                <option value="expired_sale">Đã kết thúc</option>
                <option value="no_sale">Chưa có sale</option>
              </select>
            </div>
          </div>
          <div class="table-responsive">
            <table class="table table-striped table-hover align-middle" id="productsTable">
              <thead>
                <tr>
                  <th>Mã SP</th>
                  <th>Tên sản phẩm</th>
                  <th>Màu / Size</th>
                  <th>Giá gốc</th>
                  <th>Giá sale</th>
                  <th>Chương trình</th>
                  <th>Tồn kho</th>
                  <th>Thao tác</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach($all_products as $product): ?>
        <tr class="product-row" 
          data-name="<?= htmlspecialchars($product['TENSP']) ?>" 
          data-code="<?= htmlspecialchars($product['MASP']) ?>"
          data-sale-status="<?= htmlspecialchars($product['sale_status'] ?? 'no_sale') ?>">
                  <td><code><?= htmlspecialchars($product['MASP']) ?></code></td>
                  <td><?= htmlspecialchars($product['TENSP']) ?></td>
                  <td>
                    <span class="badge bg-secondary"><?= htmlspecialchars($product['MAUSAC']) ?></span>
                    <span class="badge bg-info"><?= htmlspecialchars($product['KICHTHUOC']) ?></span>
                  </td>
                  <td><span class="price-original"><?= number_format($product['GIA']) ?>đ</span></td>
                  <td>
                    <?php if ($product['TENCTKM']): ?>
                      <span class="price-sale">
                        <?php 
                          if ($product['gia_khuyenmai']) {
                            echo number_format($product['gia_khuyenmai']) . 'đ';
                          } else {
                            $gia_sale = $product['GIA'] * (1 - $product['giam_phantram']/100);
                            echo number_format($gia_sale) . 'đ <span class="badge bg-warning ms-1">-' . $product['giam_phantram'] . '%</span>';
                          }
                        ?>
                      </span>
                    <?php else: ?>
                      <span class="text-muted">Chưa sale</span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <?php 
                      $status = $product['sale_status'] ?? 'no_sale';
                      $badgeClass = [
                        'active_sale' => 'bg-success',
                        'future_sale' => 'bg-warning',
                        'expired_sale' => 'bg-dark',
                        'no_sale' => 'bg-secondary',
                      ][$status];
                      $statusText = [
                        'active_sale' => 'Đang diễn ra',
                        'future_sale' => 'Sắp diễn ra',
                        'expired_sale' => 'Đã kết thúc',
                        'no_sale' => 'Chưa có',
                      ][$status];
                    ?>
                    <?php if ($product['TENCTKM']): ?>
                      <span class="badge bg-primary me-1"><?= htmlspecialchars($product['TENCTKM']) ?></span>
                    <?php endif; ?>
                    <span class="badge <?= $badgeClass ?>"><?= $statusText ?></span>
                  </td>
                  <td>
                    <span class="badge <?= $product['SOLUONG'] > 0 ? 'bg-success' : 'bg-danger' ?>">
                      <?= $product['SOLUONG'] ?>
                    </span>
                  </td>
                  <td>
                    <?php if ($product['TENCTKM']): ?>
                      <a href="/web_3/view/admin.php?section=khuyenmai&remove_sale=<?= $product['MASP'] ?>&mactkm=<?= $product['MACTKM'] ?>"
                         class="btn btn-sm btn-danger"
                         onclick="return confirm('Bạn có chắc muốn xóa sale khỏi sản phẩm này?')">
                        Xóa Sale
                      </a>
                    <?php else: ?>
                      <button class="btn btn-sm btn-success" onclick="assignSaleQuick('<?= $product['MASP'] ?>')">
                        Gán Sale
                      </button>
                    <?php endif; ?>
                  </td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
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
                <a href="/web_3/view/admin.php?section=khuyenmai&edit_sale=<?= $ctkm['MACTKM'] ?>" class="btn btn-sm btn-warning me-1">Sửa</a>
                <a href="/web_3/view/admin.php?section=khuyenmai&delete_sale=<?= $ctkm['MACTKM'] ?>" class="btn btn-sm btn-danger"
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
                <a href="/web_3/view/admin.php?section=khuyenmai&remove_sale=<?= $sp_sale['MASP'] ?>&mactkm=<?= $sp_sale['MACTKM'] ?>"
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

// Tìm kiếm và filter sản phẩm
function filterProducts() {
  const searchTerm = document.getElementById('searchProduct').value.toLowerCase();
  const saleStatus = document.getElementById('filterSaleStatus').value;
  const rows = document.querySelectorAll('.product-row');
  
  rows.forEach(row => {
    const productName = row.dataset.name.toLowerCase();
    const productCode = row.dataset.code.toLowerCase();
  const productSaleStatus = row.dataset.saleStatus || 'no_sale';
    
    const matchesSearch = productName.includes(searchTerm) || productCode.includes(searchTerm);
    const matchesStatus = !saleStatus || productSaleStatus === saleStatus;
    
    if (matchesSearch && matchesStatus) {
      row.style.display = '';
    } else {
      row.style.display = 'none';
    }
  });
}

// Gán sale nhanh
function assignSaleQuick(masp) {
  // Chuyển sang tab gán sale và điền sẵn mã sản phẩm
  const assignTab = document.getElementById('assign-tab');
  const assignTabPane = new bootstrap.Tab(assignTab);
  assignTabPane.show();
  
  // Điền mã sản phẩm
  setTimeout(() => {
    document.querySelector('input[name="masp"]').value = masp;
    document.querySelector('input[name="masp"]').focus();
  }, 100);
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
  
  // Thêm event listeners cho tìm kiếm và filter
  document.getElementById('searchProduct')?.addEventListener('input', filterProducts);
  document.getElementById('filterSaleStatus')?.addEventListener('change', filterProducts);
}
</script>
</body>
</html>
