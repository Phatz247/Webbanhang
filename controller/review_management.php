<?php
// controller/review_management.php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../model/database.php';
require_once __DIR__ . '/../model/review.php';

$db = new Database();
$conn = $db->getConnection();

header('Content-Type: application/json; charset=utf-8');

$action = $_GET['action'] ?? $_POST['action'] ?? '';

function respond($data, $code = 200) {
  http_response_code($code);
  echo json_encode($data);
  exit;
}

if ($action === 'eligible') {
  $user = $_SESSION['user'] ?? null;
  if (!$user || empty($user['MAKH'])) {
    respond(['ok' => false, 'message' => 'Unauthenticated'], 401);
  }
  $makh = $user['MAKH'];
  $masp = trim($_GET['masp'] ?? '');
  if (!$masp) respond(['ok' => false, 'message' => 'Thiếu MASP'], 400);
  $items = ReviewModel::getEligibleOrdersForReview($conn, $makh, $masp);
  respond(['ok' => true, 'items' => $items]);
}

if ($action === 'create') {
  $user = $_SESSION['user'] ?? null;
  if (!$user || empty($user['MAKH'])) {
    respond(['ok' => false, 'message' => 'Bạn cần đăng nhập để đánh giá.'], 401);
  }
  $makh = $user['MAKH'];
  $masp = trim($_POST['masp'] ?? '');
  $mahd = trim($_POST['mahd'] ?? '');
  $stars = (int)($_POST['stars'] ?? 0);
  $content = trim($_POST['content'] ?? '');

  if (!$masp || !$mahd) respond(['ok' => false, 'message' => 'Thiếu thông tin sản phẩm/hoá đơn'], 400);
  if ($stars < 1 || $stars > 5) respond(['ok' => false, 'message' => 'Số sao không hợp lệ'], 422);
  if (mb_strlen($content) > 500) respond(['ok' => false, 'message' => 'Nội dung tối đa 500 ký tự'], 422);

  // Check eligibility
  $eligible = ReviewModel::getEligibleOrdersForReview($conn, $makh, $masp);
  $isEligibleInvoice = false;
  foreach ($eligible as $row) {
    if ($row['MAHD'] === $mahd) { $isEligibleInvoice = true; break; }
  }
  if (!$isEligibleInvoice) {
    respond(['ok' => false, 'message' => 'Bạn chỉ có thể đánh giá sau khi đơn hàng đã giao.'], 403);
  }

  if (ReviewModel::hasReviewed($conn, $makh, $masp, $mahd)) {
    respond(['ok' => false, 'message' => 'Bạn đã đánh giá sản phẩm này cho hoá đơn này.'], 409);
  }

  // Generate MADG (max length 10 per schema): 'DG' + yymmdd + 2-digit random => 10 chars
  $madg = 'DG' . date('ymd') . str_pad((string)random_int(0, 99), 2, '0', STR_PAD_LEFT);
  $ok = ReviewModel::addReview($conn, [
    'MADG' => $madg,
    'MASP' => $masp,
    'MAKH' => $makh,
    'MAHD' => $mahd,
    'SOSAODANHGIA' => $stars,
    'NOIDUNG' => $content,
  ]);

  if ($ok) respond(['ok' => true, 'message' => 'Cảm ơn bạn đã đánh giá!', 'madg' => $madg]);
  respond(['ok' => false, 'message' => 'Không thể lưu đánh giá, thử lại sau.'], 500);
}

if ($action === 'list') {
  $masp = trim($_GET['masp'] ?? '');
  if (!$masp) respond(['ok' => false, 'message' => 'Thiếu MASP'], 400);
  $avg = ReviewModel::getAverageByProduct($conn, $masp);
  $items = ReviewModel::getReviewsByProduct($conn, $masp);
  respond(['ok' => true, 'avg' => $avg, 'items' => $items]);
}

respond(['ok' => false, 'message' => 'Hành động không hợp lệ'], 400);
