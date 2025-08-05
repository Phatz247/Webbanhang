<?php
session_start();
session_unset();  // Xóa toàn bộ biến session
session_destroy(); // Hủy session

// Chuyển hướng về trang đăng nhập
header("Location: /web_3/view/login.php");
exit;
?>