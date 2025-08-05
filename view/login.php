<?php
session_start();

$redirect = $_GET['redirect'] ?? '';

// Nếu đã login rồi, điều hướng
if (isset($_SESSION['username'])) {
    if (!empty($redirect)) {
        header("Location: /web_3/view/" . ltrim($redirect, '/'));
    } else {
        header("Location: /web_3/view/profile.php");
    }
    exit;
}

require_once __DIR__ . '/../model/database.php';
$error = '';
$username_input = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username_input = $_POST['username'] ?? '';
    $password_input = $_POST['password'] ?? '';

    $db   = new Database();
    $conn = $db->getConnection();

    // Lấy thông tin user + khách hàng (JOIN lấy đủ trường)
    $stmt = $conn->prepare("
        SELECT tk.*, kh.TENKH, kh.EMAIL, kh.DIACHI, kh.SDT
        FROM taikhoan tk
        LEFT JOIN khachhang kh ON tk.MAKH = kh.MAKH
        WHERE tk.TENDANGNHAP = :username
        LIMIT 1
    ");
    $stmt->bindParam(':username', $username_input);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password_input, $user['MATKHAU'])) {
        $_SESSION['username'] = $username_input;
        $_SESSION['matk']     = $user['MATK'];
        $_SESSION['makh']     = $user['MAKH'];
        // Lưu cho checkout
      $_SESSION['user'] = [
    'MAKH'    => $user['MAKH'], // CHỮ HOA
    'TENKH'   => $user['TENKH'] ?? '',
    'EMAIL'   => $user['EMAIL'] ?? '',
    'DIACHI'  => $user['DIACHI'] ?? '',
    'SDT'     => $user['SDT'] ?? ''
];

        if (isset($user['MATK']) && strtoupper($user['MATK']) === 'TK001') {
            header("Location: /web_3/view/admin.php");
        } elseif (!empty($redirect)) {
            header("Location: /web_3/view/" . ltrim($redirect, '/'));
        } else {
            header("Location: /web_3/index.php");
        }
        exit;
    } else {
        $error = "❌ Tên đăng nhập hoặc mật khẩu không đúng!";
        unset($_SESSION['username'], $_SESSION['matk'], $_SESSION['makh'], $_SESSION['user']);
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <title>Đăng nhập</title>
  <link rel="stylesheet" href="../view/css/login.css?v=<?php echo time(); ?>">
  <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
</head>
<body>
  <div class="login-container">
    <?php if (isset($_SESSION['register_success'])): ?>
      <div class="success-alert" id="success-alert">
        <?= htmlspecialchars($_SESSION['register_success']) ?>
      </div>
      <?php unset($_SESSION['register_success']); ?>
    <?php endif; ?>

    <h2>Đăng nhập tài khoản</h2>
    <form method="post" action="/web_3/view/login.php<?php echo $redirect ? '?redirect=' . urlencode($redirect) : ''; ?>">
      <label for="username">Tên đăng nhập</label>
      <input type="text" id="username" name="username" required value="<?= htmlspecialchars($username_input) ?>">

      <label for="password">Mật khẩu</label>
      <div class="input-wrapper">
        <input type="password" id="password" name="password" required>
        <span class="toggle-password" onclick="togglePassword('password', this)">
          <i class="fa-solid fa-eye"></i>
        </span>
      </div>

      <?php if (!empty($error)): ?>
        <div class="error"><?= $error ?></div>
      <?php endif; ?>

      <button type="submit" class="btn">Đăng nhập</button>
      <div class="register-link">
        <a href="/web_3/view/register.php">Đăng ký tài khoản</a>
      </div>
    </form>
  </div>
  <script>
    function togglePassword(id, el) {
      const input = document.getElementById(id);
      const icon = el.querySelector('i');
      if (input.type === 'password') {
        input.type = 'text';
        icon.classList.replace('fa-eye', 'fa-eye-slash');
      } else {
        input.type = 'password';
        icon.classList.replace('fa-eye-slash', 'fa-eye');
      }
    }
    window.onload = () => {
      const alert = document.getElementById('success-alert');
      if (alert) setTimeout(() => {
        alert.style.opacity = '0';
        setTimeout(() => alert.style.display = 'none', 500);
      }, 2000);
    };
  </script>
</body>
</html>
