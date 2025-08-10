<?php
// view/upload/header.php

// 1) Khởi động session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2) Kết nối Database (điều chỉnh đường dẫn nếu cần)
require_once __DIR__ . '/../../model/database.php';
$db   = new database();  
$conn = $db->getConnection();

// 3) Hàm tiện ích lấy loại sản phẩm theo tên danh mục
function getTypesByCategory(PDO $conn, string $categoryName) {
    $sql = "
      SELECT l.MALOAI, l.TENLOAI
      FROM loaisanpham l
      JOIN danhmuc d ON l.MADM = d.MADM
      WHERE d.TENDM = :tendm
      ORDER BY l.TENLOAI
    ";
    $stmt = $conn->prepare($sql);
    $stmt->execute([':tendm' => mb_strtoupper($categoryName)]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// 4) Lấy dữ liệu cho 3 menu động
$aoTypes      = getTypesByCategory($conn, 'ÁO NAM');
$quanTypes    = getTypesByCategory($conn, 'QUẦN NAM');
$phuKienTypes = getTypesByCategory($conn, 'PHỤ KIỆN');

// 5) Đọc param GET để highlight mục active
$currentAo   = $_GET['loai_ao']   ?? '';
$currentQuan = $_GET['loai_quan'] ?? '';
$currentPk   = $_GET['loai_pk']   ?? '';

// 6) Tính số lượng sản phẩm trong giỏ (tổng số lượng, không chỉ số dòng)
$cartCount = 0;
if (!empty($_SESSION['cart']) && is_array($_SESSION['cart'])) {
  foreach ($_SESSION['cart'] as $ci) {
    $cartCount += isset($ci['soluong']) ? (int)$ci['soluong'] : 0;
  }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>MENSTA</title>
  <link rel="stylesheet" href="/web_3/view/css/header.css">
  <link rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"
        integrity="sha512-k6RqeWeciB5apq6h+6U+v2Hl8jm7usBdGx7p6J9J89kvFjd1whvV1Ll0x0CqK5jkG2eCjMdS2Cg+Yl9oZ5eEvA=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />
  <style>
    /* Badge số lượng giỏ hàng */
    .header-icons .header-icon-item .icon-wrap { position: relative; display: inline-block; }
    .header-icons .cart-count-badge {
      position: absolute; top: -6px; right: -6px; min-width: 16px; height: 16px;
      padding: 0 4px; border-radius: 10px; background: #fff; color: #e53935; font-size: 11px;
      line-height: 16px; text-align: center; font-weight: 700; z-index: 2;
      box-shadow: 0 0 0 1px rgba(0,0,0,0.1);
    }
    /* Stack label under icons and align all 3 evenly */
    .header-icons .header-icon-item.icon-stack {
      display: inline-flex; flex-direction: column; align-items: center; gap: 4px;
      text-align: center; width: 90px;
    }
    .header-icons .header-icon-item.icon-stack span { display: block; }
  </style>
</head>
<body>
  <div class="topbar">
    <div class="topbar-container">
      <div class="topbar-left">
        <span class="topbar-item">
          <i class="fas fa-phone-alt"></i> Hotline: 0999.999.999
        </span>
      </div>
    </div>
  </div>

  <header class="main-header">
    <div class="logo">
      <a href="/web_3/">
        <img src="/web_3/view/img/logo2.jpg" alt="MENSTA Logo">
      </a>
    </div>

    <div class="search-container position-relative">
      <input type="text" id="searchInput" placeholder="Tìm kiếm sản phẩm…" autocomplete="off">
      <button type="button" id="btnSearch"><i class="fas fa-search"></i></button>
      <div id="historyList" class="search-history-dropdown"></div>
    </div>

    <div class="header-icons">
      <a href="/web_3/view/membership.php" class="header-icon-item icon-stack">
        <span class="icon-wrap"><i class="fas fa-id-card"></i></span>
        <span>Chính sách khách VIP</span>
      </a>
      <!-- 
  AI CODE MỚI HIỂU      -->
     <div style="display:none">
<?php if (isset($_SESSION['username'])): ?>
  <!-- Nếu đã đăng nhập: hiện tên và dẫn đến profile -->
  <a href="/web_3/view/profile.php" class="header-icon-item icon-stack" title="Thông tin tài khoản">
    <span class="icon-wrap"><i class="fas fa-user"></i></span>
    <span><?php echo htmlspecialchars($_SESSION['username']); ?></span>
  </a>
<?php else: ?>
<!-- Nếu chưa đăng nhập: dẫn đến trang đăng nhập -->
  <a href="/web_3/view/login.php" class="header-icon-item" title="Đăng nhập">
    <i class="fas fa-user"></i>
    <span>Đăng nhập</span>
  </a>
<?php endif; ?>
</div>


<!-- PHẦN MỚI TÀI KHOẢN -->
      <?php if (isset($_SESSION['username'])): ?>
  <a href="/web_3/view/profile.php" class="header-icon-item" title="Thông tin tài khoản">
    <i class="fas fa-user"></i>
    <span><?php echo htmlspecialchars($_SESSION['username']); ?></span>
  </a>
<?php else: ?>
  <a href="/web_3/view/login.php" class="header-icon-item icon-stack" title="Đăng nhập">
    <span class="icon-wrap"><i class="fas fa-user"></i></span>
    <span>Tài khoản</span>
  </a>
<?php endif; ?>

  <a href="/web_3/view/shoppingcart.php" class="header-icon-item icon-stack">
        <span class="icon-wrap">
          <i class="fas fa-shopping-cart"></i>
          <?php if ($cartCount > 0): ?>
            <span class="cart-count-badge"><?= (int)$cartCount ?></span>
          <?php endif; ?>
        </span>
        <span>Giỏ hàng</span>
      </a>
    </div>
  </header>

  <nav class="main-nav-bar">
    <div class="main-nav-container">
      <ul class="main-nav-list">

        <!-- Trang chủ & Giới thiệu -->
        <li><a href="/web_3/">TRANG CHỦ</a></li>
        <li><a href="/web_3/view/about.php">GIỚI THIỆU</a></li>

        <!-- Dropdown ÁO NAM -->
        <li class="dropdown">
          <a href="/web_3/view/menshirt.php"
             class="<?= $currentAo === '' ? 'active' : '' ?>">
            ÁO NAM <i class="fas fa-chevron-down"></i>
          </a>
          <ul class="dropdown-menu">
            <li>
              <a href="/web_3/view/menshirt.php"
                 class="<?= $currentAo === '' ? 'active' : '' ?>">
                Tất cả
              </a>
            </li>
            <?php foreach($aoTypes as $t): ?>
              <li>
                <a href="/web_3/view/menshirt.php?loai_ao=<?= urlencode($t['MALOAI']) ?>"
                   class="<?= $t['MALOAI'] === $currentAo ? 'active' : '' ?>">
                  <?= htmlspecialchars($t['TENLOAI']) ?>
                </a>
              </li>
            <?php endforeach; ?>
          </ul>
        </li>

        <!-- Dropdown QUẦN NAM -->
        <li class="dropdown">
          <a href="/web_3/view/menpants.php"
             class="<?= $currentQuan === '' ? 'active' : '' ?>">
            QUẦN NAM <i class="fas fa-chevron-down"></i>
          </a>
          <ul class="dropdown-menu">
            <li>
              <a href="/web_3/view/menpants.php"
                 class="<?= $currentQuan === '' ? 'active' : '' ?>">
                Tất cả
              </a>
            </li>
            <?php foreach($quanTypes as $t): ?>
              <li>
                <a href="/web_3/view/menpants.php?loai_quan=<?= urlencode($t['MALOAI']) ?>"
                   class="<?= $t['MALOAI'] === $currentQuan ? 'active' : '' ?>">
                  <?= htmlspecialchars($t['TENLOAI']) ?>
                </a>
              </li>
            <?php endforeach; ?>
          </ul>
        </li>

        <!-- Dropdown PHỤ KIỆN -->
        <li class="dropdown">
<a href="/web_3/view/accessory.php"
             class="<?= $currentPk === '' ? 'active' : '' ?>">
            PHỤ KIỆN <i class="fas fa-chevron-down"></i>
          </a>
          <ul class="dropdown-menu">
            <li>
              <a href="/web_3/view/accessory.php"
                 class="<?= $currentPk === '' ? 'active' : '' ?>">
                Tất cả
              </a>
            </li>
            <?php foreach($phuKienTypes as $t): ?>
              <li>
                <a href="/web_3/view/accessory.php?loai_pk=<?= urlencode($t['MALOAI']) ?>"
                   class="<?= $t['MALOAI'] === $currentPk ? 'active' : '' ?>">
                  <?= htmlspecialchars($t['TENLOAI']) ?>
                </a>
              </li>
            <?php endforeach; ?>
          </ul>
        </li>

      </ul>
    </div>
  </nav>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <!-- Giữ nguyên script tìm kiếm và lịch sử tìm kiếm nếu có -->
   <!-- tìm kiếm -->
<script>
function saveSearchHistory(query) {
  if (!query.trim()) return;
  let history = JSON.parse(localStorage.getItem('search_history') || '[]');
  history = history.filter(item => item.toLowerCase() !== query.toLowerCase());
  history.unshift(query);
  if (history.length > 8) history = history.slice(0, 5);
  localStorage.setItem('search_history', JSON.stringify(history));
}

function showHistory(filter="") {
  let history = JSON.parse(localStorage.getItem('search_history') || '[]');
  if (filter) {
    history = history.filter(item => item.toLowerCase().includes(filter.toLowerCase()));
  }
  const box = document.getElementById('historyList');
  if (history.length === 0) return box.style.display = "none";
  box.innerHTML = history.map(item => <div class="history-item">${item}</div>).join('');
  box.style.display = "block";
}

// Sự kiện tìm kiếm
document.getElementById('btnSearch').onclick = doSearch;
document.getElementById('searchInput').addEventListener('keydown', function(e){
  if (e.key === 'Enter') doSearch();
});
document.getElementById('searchInput').addEventListener('input', function(){
  showHistory(this.value);
});
document.getElementById('searchInput').addEventListener('focus', function(){
  showHistory(this.value);
});
document.addEventListener('click', function(e){
  if (!e.target.closest('.search-container')) {
    document.getElementById('historyList').style.display = "none";
  }
});
document.getElementById('historyList').onclick = function(e) {
  if (e.target.classList.contains('history-item')) {
document.getElementById('searchInput').value = e.target.textContent;
    doSearch();
  }
};

function doSearch() {
  const value = document.getElementById('searchInput').value.trim();
  if (value !== "") {
    saveSearchHistory(value);
    window.location.href = '/web_3/view/search.php?keyword=' + encodeURIComponent(value);
  }
}

const input = document.getElementById("searchInput");
const dropdown = document.getElementById("search-history-dropdown");


</script>

</body>
</html>
