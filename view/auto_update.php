<?php
require_once __DIR__ . '/../model/database.php';
$db = new database();
$conn = $db->getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['madonhang'])) {
    $madonhang = $_POST['madonhang'];
    $stmt = $conn->prepare("UPDATE donhang SET TRANGTHAI = 'Đã giao hàng' WHERE MADONHANG = ? AND TRANGTHAI = 'Đang giao hàng' AND (is_confirmed = 0 OR is_confirmed IS NULL)");
    $stmt->execute([$madonhang]);
    echo "ok";
}
?>