<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . "/../model/database.php";
$db = new database();
$conn = $db->getConnection();

$alert = "";

// Danh sách KH để chọn tạo tài khoản
$stmt = $conn->query("SELECT MAKH, TENKH FROM khachhang");
$all_khachhang = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Danh sách tài khoản
$stmt = $conn->query("SELECT * FROM taikhoan ORDER BY ID DESC");
$accounts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Xử lý Sửa tài khoản
$edit_mode = false;
$edit_account = [];
if (isset($_GET['edit_acc'])) {
    if ($_GET['edit_acc'] === 'TK001') {
        $_SESSION['alert_success'] = "❌ Không thể sửa tài khoản admin!";
        header("Location: /web_3/controller/account_management.php");
        exit;
    }
    $edit_mode = true;
    $matk = $_GET['edit_acc'];
    $stmt = $conn->prepare("SELECT * FROM taikhoan WHERE MATK = ?");
    $stmt->execute([$matk]);
    $edit_account = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Thêm/sửa tài khoản
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Thêm TK
    if (isset($_POST['add_account'])) {
        $tendn = trim($_POST['tendn']);
        $matkhau = trim($_POST['matkhau']);
        $makh = trim($_POST['makh']);
        $vaitro = $_POST['vaitro'];

        // Kiểm tra MAKH tồn tại
        $stmt = $conn->prepare("SELECT COUNT(*) FROM khachhang WHERE MAKH = ?");
        $stmt->execute([$makh]);
        if ($stmt->fetchColumn() == 0) {
            $alert = "❌ Mã khách hàng không tồn tại!";
        } else {
            // Check đã có TK chưa
            $stmt = $conn->prepare("SELECT COUNT(*) FROM taikhoan WHERE MAKH = ?");
            $stmt->execute([$makh]);
            if ($stmt->fetchColumn() > 0) {
                $alert = "❌ Khách hàng này đã có tài khoản!";
            } else {
                // Tạo mã MATK tự động
                $stmt = $conn->query("SELECT MAX(MATK) as max_mtk FROM taikhoan WHERE MATK LIKE 'TK%'");
                $row = $stmt->fetch();
                if ($row && $row['max_mtk']) {
                    $max_number = (int)substr($row['max_mtk'], 2);
                    $new_number = $max_number + 1;
                    $matk = 'TK' . str_pad($new_number, 3, '0', STR_PAD_LEFT);
                } else {
                    $matk = 'TK001';
                }
                $hash = password_hash($matkhau, PASSWORD_DEFAULT);

                // Nếu chưa có tài khoản nào thì vai trò là admin
                $stmt = $conn->query("SELECT COUNT(*) FROM taikhoan");
                $total_acc = $stmt->fetchColumn();
                if ($total_acc == 0) {
                    $vaitro = 'admin';
                }

                $stmt = $conn->prepare("INSERT INTO taikhoan (MATK, TENDANGNHAP, MATKHAU, MAKH, VAITRO) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$matk, $tendn, $hash, $makh, $vaitro]);
                $_SESSION['alert_success'] = "✔️ Tạo tài khoản thành công!";
header("Location: /web_3/controller/account_management.php");
                exit;
            }
        }
    }
    // Sửa TK
    if (isset($_POST['update_account'])) {
        $matk = $_POST['matk'];
        if ($matk === 'TK001') {
            $_SESSION['alert_success'] = "❌ Không thể sửa tài khoản admin!";
            header("Location: /web_3/controller/account_management.php");
            exit;
        }
        $tendn = $_POST['tendn'];
        $makh = $_POST['makh'];
        $vaitro = $_POST['vaitro'];

        // Không cho sửa nếu MAKH đã thuộc về TK khác
        $stmt = $conn->prepare("SELECT MATK FROM taikhoan WHERE MAKH = ? AND MATK <> ?");
        $stmt->execute([$makh, $matk]);
        if ($stmt->fetch()) {
            $alert = "❌ MAKH này đã được gán cho tài khoản khác!";
        } else {
            $sql = "UPDATE taikhoan SET TENDANGNHAP=?, MAKH=?, VAITRO=?";
            $params = [$tendn, $makh, $vaitro];

            if (!empty($_POST['matkhau'])) {
                $sql .= ", MATKHAU=?";
                $params[] = password_hash($_POST['matkhau'], PASSWORD_DEFAULT);
            }
            $sql .= " WHERE MATK=?";
            $params[] = $matk;

            $stmt = $conn->prepare($sql);
            $stmt->execute($params);
            $_SESSION['alert_success'] = "✔️ Đã cập nhật tài khoản!";
            header("Location: /web_3/controller/account_management.php");
            exit;
        }
    }
}

// Xóa TK
if (isset($_GET['delete_acc'])) {
    $matk = $_GET['delete_acc'];
    if ($matk === 'TK001') {
        $_SESSION['alert_success'] = "❌ Không thể xóa tài khoản admin!";
        header("Location: /web_3/controller/account_management.php");
        exit;
    }
    $stmt = $conn->prepare("DELETE FROM taikhoan WHERE MATK = ?");
    $stmt->execute([$matk]);
    $_SESSION['alert_success'] = "✔️ Đã xoá tài khoản!";
    header("Location: /web_3/controller/account_management.php");
    exit;
}

// Include menu/sidebar
include_once __DIR__ . '/../view/upload/header_admin.php';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Quản lý tài khoản</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f6f6f6; }
        .form-section, .table-section {
            background: #fff; border-radius: 14px;
            padding: 36px 48px; margin-top: 30px;
            box-shadow: 0 2px 18px rgba(0,0,0,0.04);
            max-width: 1200px; margin-left:auto; margin-right:auto;
        }
        .form-section { margin-bottom:32px; }
        h4 { font-size: 1.6rem; font-weight: 600;}
        h5 { font-size: 1.2rem; font-weight: 500;}
        .alert-fixed { position: fixed; top: 20px; right: 40px; min-width: 240px; z-index: 9999; font-size: 16px; padding: 12px 18px; border-radius: 8px; box-shadow: 0 4px 20px rgba(0,0,0,0.11);}
.btn-action { min-width: 80px; }
        .d-action { display: flex; gap: 12px; justify-content: center;}
        .badge { font-size: .98em; padding:6px 14px; }
        .form-label { font-weight: 500; }
        @media (max-width: 900px){
            .form-section, .table-section { padding: 16px 6px; max-width: 100%; }
        }
    </style>
</head>
<body>
<div class="container-fluid py-4" style="padding-left:0; padding-right:0;">

    <?php if ($alert): ?>
        <div class="alert alert-danger alert-fixed" id="alert-msg"><?= $alert ?></div>
    <?php endif; ?>
    <?php if (!empty($_SESSION['alert_success'])): ?>
        <div class="alert alert-success alert-fixed" id="alert-msg"><?= $_SESSION['alert_success']; unset($_SESSION['alert_success']); ?></div>
    <?php endif; ?>

    <!-- FORM -->
    <div class="form-section shadow-sm mb-4">
      <h4 class="mb-4">Quản lý Tài khoản</h4>
      <form method="POST" autocomplete="off" class="row g-4 align-items-end">
        <input type="hidden" name="matk" value="<?= $edit_account['MATK'] ?? '' ?>">
        <div class="col-lg-3 col-md-6">
          <label class="form-label">Tên đăng nhập</label>
          <input name="tendn" required class="form-control" value="<?= htmlspecialchars($edit_account['TENDANGNHAP'] ?? '') ?>">
        </div>
        <div class="col-lg-3 col-md-6">
          <label class="form-label"><?= $edit_mode ? "Mật khẩu mới (bỏ qua nếu không đổi)" : "Mật khẩu" ?></label>
          <input name="matkhau" type="password" class="form-control" <?= $edit_mode ? '' : 'required' ?>>
        </div>
        <div class="col-lg-3 col-md-6">
          <label class="form-label">Khách hàng (MAKH)</label>
          <select name="makh" required class="form-select">
            <option value="">-- Chọn --</option>
            <?php
              foreach ($all_khachhang as $kh) {
                $makh_used = false;
                foreach ($accounts as $acc) {
                  if ($acc['MAKH'] == $kh['MAKH'] && (!$edit_mode || $acc['MATK'] != ($edit_account['MATK'] ?? ''))) {
                    $makh_used = true; break;
                  }
                }
                $selected = (isset($edit_account['MAKH']) && $edit_account['MAKH'] == $kh['MAKH']) ? "selected" : "";
                echo '<option value="'.$kh['MAKH'].'" '.$selected.($makh_used && !$selected?' disabled':'').'>'.$kh['MAKH'].' - '.htmlspecialchars($kh['TENKH']).($makh_used && !$selected?' (đã có TK)':'').'</option>';
              }
            ?>
          </select>
        </div>
        <div class="col-lg-2 col-md-6">
          <label class="form-label">Vai trò</label>
          <select name="vaitro" class="form-select" required <?= (count($accounts) == 0 && !$edit_mode) ? 'disabled' : '' ?>>
            <option value="user" <?= (isset($edit_account['VAITRO']) && $edit_account['VAITRO']=='user')?'selected':''; ?>>User</option>
<option value="admin" <?= (isset($edit_account['VAITRO']) && $edit_account['VAITRO']=='admin')?'selected':''; ?>>Admin</option>
          </select>
          <?php if (count($accounts) == 0 && !$edit_mode): ?>
            <div class="form-text text-danger">Tài khoản đầu tiên sẽ là Admin</div>
          <?php endif; ?>
        </div>
        <div class="col-lg-1 col-12 text-end d-flex flex-column align-items-end gap-2">
          <?php if ($edit_mode): ?>
            <button class="btn btn-warning btn-action w-100 mb-2" type="submit" name="update_account"><i class="bi bi-pencil"></i> Cập nhật</button>
            <a href="/web_3/controller/account_management.php" class="btn btn-secondary btn-action w-100">Hủy</a>
          <?php else: ?>
            <button class="btn btn-primary btn-action w-100" type="submit" name="add_account"><i class="bi bi-person-plus"></i> Thêm</button>
          <?php endif; ?>
        </div>
      </form>
    </div>

    <!-- Danh sách tài khoản -->
    <div class="table-section shadow-sm">
      <h5 class="mb-4">Danh sách tài khoản</h5>
      <div class="table-responsive">
        <table class="table table-bordered table-hover align-middle">
          <thead class="table-light">
            <tr>
              <th>MATK</th>
              <th>Tên đăng nhập</th>
              <th>MAKH</th>
              <th>Vai trò</th>
              <th class="text-center" style="width:160px;">Hành động</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($accounts as $acc): ?>
            <tr>
              <td><?= $acc['MATK'] ?></td>
              <td><?= htmlspecialchars($acc['TENDANGNHAP']) ?></td>
              <td><span class="badge bg-secondary"><?= $acc['MAKH'] ?></span></td>
              <td>
              <?php
                  $role = isset($acc['VAITRO']) ? $acc['VAITRO'] : 'user';
                  if ($role == 'admin') {
                    echo '<span class="badge bg-danger">Admin</span>';
                  } else {
                    echo '<span class="badge bg-primary">' . htmlspecialchars($role) . '</span>';
                  }
                ?>
              </td>
              <td class="text-center">
                <div class="d-action">
                <?php if ($acc['MATK'] !== 'TK001'): ?>
                  <a class="btn btn-sm btn-warning btn-action" href="/web_3/controller/account_management.php?edit_acc=<?= $acc['MATK'] ?>"><i class="bi bi-pencil"></i> Sửa</a>
                  <a class="btn btn-sm btn-danger btn-action" href="/web_3/controller/account_management.php?delete_acc=<?= $acc['MATK'] ?>" onclick="return confirm('Xoá tài khoản này?')"><i class="bi bi-trash"></i> Xóa</a>
                <?php else: ?>
                  <span class="text-muted fst-italic">Admin mặc định</span>
                <?php endif; ?>
                </div>
              </td>
            </tr>
            <?php endforeach; ?>
<?php if (empty($accounts)): ?>
            <tr>
              <td colspan="5" class="text-center text-muted">Chưa có tài khoản nào</td>
            </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
</div>

<!-- Optional: Bootstrap Icons -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

<script>
window.onload = function() {
  var alert = document.getElementById('alert-msg');
  if(alert){
    setTimeout(() => {
      alert.style.opacity = '0';
      setTimeout(() => alert.style.display = 'none', 600);
    }, 2000);
  }
}
</script>
</body>
</html>
