<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../PHPMailer-master/src/Exception.php';
require '../PHPMailer-master/src/PHPMailer.php';
require '../PHPMailer-master/src/SMTP.php';
require_once __DIR__ . "/../model/database.php";

// Biến lưu lỗi và thành công
$error = "";
$success = "";

// Biến giữ lại dữ liệu nhập
$username = $email = $tenkh = $namsinh = $diachi = $gioitinh = $sdt = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = trim($_POST["username"] ?? '');
    $email = trim($_POST["email"] ?? '');
    $password = $_POST["password"] ?? '';
    $confirmPassword = $_POST["confirmPassword"] ?? '';
    $tenkh = trim($_POST["tenkh"] ?? '');
    $namsinh = $_POST["namsinh"] ?? '';
    $diachi = trim($_POST["diachi"] ?? '');
    $gioitinh = $_POST["gioitinh"] ?? '';
    $sdt = trim($_POST["sdt"] ?? '');

    if (!$username || !$email || !$password || !$confirmPassword || !$tenkh || !$namsinh || !$diachi || !$gioitinh || !$sdt) {
        $error = "❌ Vui lòng điền đầy đủ thông tin!";
    } elseif ($password !== $confirmPassword) {
        $error = "❌ Mật khẩu không khớp!";
    } elseif (strlen($password) < 6) {
        $error = "❌ Mật khẩu phải có ít nhất 6 ký tự!";
    } else {
        try {
            $db = new Database();
            $conn = $db->getConnection();

            // Kiểm tra username
            $stmt = $conn->prepare("SELECT 1 FROM taikhoan WHERE TENDANGNHAP = ?");
            $stmt->execute([$username]);
            if ($stmt->rowCount() > 0) {
                $error = "❌ Tên đăng nhập đã tồn tại!";
            }

            // Kiểm tra email
            $stmt = $conn->prepare("SELECT 1 FROM khachhang WHERE EMAIL = ?");
            $stmt->execute([$email]);
            if ($stmt->rowCount() > 0) {
                $error = "❌ Email đã tồn tại!";
            }

            if (empty($error)) {
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

                // Thêm khách hàng
                $stmt = $conn->prepare("INSERT INTO khachhang (TENKH, GIOITINH, NGAYSINH, DIACHI, SDT, EMAIL)
                                        VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$tenkh, $gioitinh, $namsinh, $diachi, $sdt, $email]);

                $lastID = $conn->lastInsertId();
                $makh = "KH" . str_pad($lastID, 3, "0", STR_PAD_LEFT);
                $stmt = $conn->prepare("UPDATE khachhang SET MAKH = ? WHERE ID = ?");
                $stmt->execute([$makh, $lastID]);

                // Thêm tài khoản
                $stmt = $conn->prepare("INSERT INTO taikhoan (TENDANGNHAP, MATKHAU, MAKH)
                                        VALUES (?, ?, ?)");
                $stmt->execute([$username, $hashedPassword, $makh]);

                $lastID_TK = $conn->lastInsertId();
$matk = "TK" . str_pad($lastID_TK, 3, "0", STR_PAD_LEFT);
$stmt = $conn->prepare("UPDATE taikhoan SET MATK = ? WHERE ID = ?");
                $stmt->execute([$matk, $lastID_TK]);

                // Gửi email
                $mail = new PHPMailer(true);
                $mail->CharSet = 'UTF-8';
                $mail->Encoding = 'base64';
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = 'nguyenquocthinh0844441172@gmail.com';
                $mail->Password = 'ttue ngqn vnat evgh';
                $mail->SMTPSecure = 'tls';
                $mail->Port = 587;

                $mail->setFrom('nguyenquocthinh0844441172@gmail.com', 'MENSTA');
                $mail->addAddress($email, $tenkh);
                $mail->isHTML(true);
                $mail->Subject = 'Đăng ký tài khoản thành công';
                $mail->Body = "<h2>Xin chào $tenkh!</h2><p>Bạn đã đăng ký tài khoản thành công tại MENSTA!</p>";

                $mail->send();

                session_start();
$_SESSION['register_success'] = "Đăng ký thành công! Vui lòng đăng nhập.";
header("Location: login.php");
exit;

            }
        } catch (Exception $e) {
            $error = "❌ Lỗi: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8" />
  <title>Đăng ký tài khoản</title>
</head>
   <link rel="stylesheet" href="../view/css/register.css?v=<?php echo time(); ?>">

<body>
  <div class="form-container">
    <h2>Đăng ký tài khoản</h2>
    <form method="POST" class="form-grid">
      <!-- Cột trái -->
      <div class="form-column">
        <h3>Thông tin tài khoản</h3>

        <label>Tên đăng nhập <span class="required">*</span></label>
        <input name="username" placeholder="Tên đăng nhập" required value="<?php echo htmlspecialchars($username); ?>">

        <label>Mật khẩu <span class="required">*</span></label>
        <input name="password" type="password" placeholder="Mật khẩu" required>

        <label>Xác nhận mật khẩu <span class="required">*</span></label>
        <input name="confirmPassword" type="password" placeholder="Xác nhận mật khẩu" required>

        <?php if (!empty($error)) : ?>
          <p class="error" style="color: red;"><?php echo $error; ?></p>
        <?php endif; ?>

        <div class="spacer"></div>
      </div>

      <!-- Cột phải -->
      <div class="form-column">
        <h3>Thông tin khách hàng</h3>

        <label>Email <span class="required">*</span></label>
        <input name="email" type="email" placeholder="Email" required value="<?php echo htmlspecialchars($email); ?>">

        <label>Họ tên <span class="required">*</span></label>
        <input name="tenkh" placeholder="Họ tên" required value="<?php echo htmlspecialchars($tenkh); ?>">
<label>Ngày sinh <span class="required">*</span></label>
<input name="namsinh" type="date" required value="<?php echo htmlspecialchars($namsinh); ?>">

        <label>Địa chỉ <span class="required">*</span></label>
        <textarea name="diachi" placeholder="Địa chỉ" rows="3" required><?php echo htmlspecialchars($diachi); ?></textarea>

        <label>Giới tính <span class="required">*</span></label>
        <select name="gioitinh" required>
          <option value="">-- Giới tính --</option>
          <option value="Nam" <?php if ($gioitinh == "Nam") echo "selected"; ?>>Nam</option>
          <option value="Nữ" <?php if ($gioitinh == "Nữ") echo "selected"; ?>>Nữ</option>
          <option value="Khác" <?php if ($gioitinh == "Khác") echo "selected"; ?>>Khác</option>
        </select>

        <label>Số điện thoại <span class="required">*</span></label>
        <input name="sdt" placeholder="Số điện thoại" required value="<?php echo htmlspecialchars($sdt); ?>">
      </div>

    <div class="form-full">
  <label class="terms">
    <input type="checkbox" name="terms" id="agree">
    Tôi đồng ý với điều khoản đăng ký
  </label>
  <button type="submit" id="submitBtn" disabled>Đăng ký</button>
</div>

    </form>
  </div>

<script>
document.addEventListener('DOMContentLoaded', () => {
  const agree = document.getElementById('agree');
  const submitBtn = document.getElementById('submitBtn');

  if (agree && submitBtn) {
    agree.addEventListener('change', () => {
      submitBtn.disabled = !agree.checked;
    });
    submitBtn.disabled = !agree.checked;
  }
});
</script>

</body>
</html>
  