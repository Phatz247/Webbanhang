<?php
session_start();
require_once __DIR__ . '/../model/database.php';
$db   = new database();
$conn = $db->getConnection();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: product_detail.php');
    exit;
}

$masp         = $_POST['masp'] ?? '';
$tensp        = $_POST['tensp'] ?? '';
$gia_goc      = max(0, (int)($_POST['gia_goc'] ?? 0));
$gia_ban      = max(0, (int)($_POST['gia_ban'] ?? 0));
$hinhanh      = $_POST['hinhanh'] ?? '';
$mausac       = $_POST['mausac'] ?? '';
$kichthuoc    = $_POST['kichthuoc'] ?? '';
$soluong      = max(1, (int)($_POST['soluong'] ?? 1));
$promotion_name = $_POST['promotion_name'] ?? '';
$has_promotion = (int)($_POST['has_promotion'] ?? 0);

// Bảo vệ thiếu dữ liệu
if (!$masp || !$tensp || !$kichthuoc || $gia_goc <= 0) {
    header('Location: product_detail.php?masp=' . $masp);
    exit;
}

// Kiểm tra tồn kho từ database (bảo mật)
$stmt = $conn->prepare("
    SELECT SOLUONG 
    FROM sanpham 
    WHERE MASP = ? AND KICHTHUOC = ?
");
$stmt->execute([$masp, $kichthuoc]);
$sp = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$sp || $sp['SOLUONG'] < $soluong) {
    $_SESSION['cart_error'] = 'Sản phẩm không đủ hàng!';
    header('Location: product_detail.php?masp=' . $masp);
    exit;
}

// Khởi tạo giỏ hàng nếu chưa có
if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];

// Mỗi sản phẩm có key duy nhất là MASP_MAUSAC_KICHTHUOC
$key = $masp . '_' . $mausac . '_' . $kichthuoc;

if (isset($_SESSION['cart'][$key])) {
    // Nếu đã có, cộng thêm số lượng
    $_SESSION['cart'][$key]['soluong'] += $soluong;
} else {
    // Thêm mới vào giỏ hàng
    $_SESSION['cart'][$key] = [
        'masp'           => $masp,
        'tensp'          => $tensp,
        'gia_goc'        => $gia_goc,
        'gia_ban'        => $gia_ban,
        'hinhanh'        => $hinhanh,
        'mausac'         => $mausac,
        'kichthuoc'      => $kichthuoc,
        'soluong'        => $soluong,
        'promotion_name' => $promotion_name,
        'has_promotion'  => $has_promotion,
        'added_time'     => time() // Để track thời gian thêm
    ];
}

$_SESSION['cart_success'] = 'Đã thêm sản phẩm vào giỏ hàng!';

// Kiểm tra nếu là "mua ngay" thì chuyển thẳng đến checkout
if (isset($_POST['buy_now'])) {
    $_SESSION['quick_checkout'] = [$key]; // Chỉ checkout sản phẩm vừa thêm
    header('Location: checkout.php');
} else {
    header('Location: shoppingcart.php');
}
exit;
?>