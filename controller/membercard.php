<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . "/../model/database.php";
$db = new database();
$conn = $db->getConnection();

// Lấy tổng tiền đã mua của từng tài khoản (dựa vào đơn hàng Đã thanh toán)
$stmt = $conn->query("
  SELECT
    tk.MATK,
    tk.TENDANGNHAP,
    kh.TENKH,
    tk.MAKH,
    COALESCE(SUM(dh.TONGTIEN), 0) AS tong_mua
  FROM taikhoan tk
  LEFT JOIN khachhang kh ON tk.MAKH = kh.MAKH
  LEFT JOIN donhang dh ON tk.MAKH = dh.MAKH AND dh.TRANGTHAI = 'Đã hoàn thành'
  WHERE tk.MATK <> 'TK001'
  GROUP BY tk.MATK, tk.TENDANGNHAP, kh.TENKH, tk.MAKH
  ORDER BY tong_mua DESC
");
$members = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Xác định hạng thành viên
function get_rank($total) {
    if ($total >= 20000000) return ['Kim cương', 'bi-gem', 'bg-gradient-info text-white'];
    if ($total >= 10000000) return ['Vàng', 'bi-award-fill', 'bg-warning text-dark'];
    if ($total >= 5000000) return ['Bạc', 'bi-trophy', 'bg-secondary text-white'];
    return ['Thành viên', 'bi-person', 'bg-light text-dark'];
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <title>Quản lý thẻ thành viên</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    body { background: #f6f6f6; }
    .member-card { background: #fff; border-radius: 16px; box-shadow: 0 4px 24px rgba(0,0,0,0.08); padding: 36px 36px 24px 36px; margin-top: 40px;}
    .table thead { background: #e8f0fe; }
    .search-box { max-width: 320px; float: right; margin-bottom: 14px; }
    .rank-badge { font-size: 1.07em; font-weight: 500; padding: 7px 17px; border-radius: 2rem; }
    .bg-gradient-info {
      background: linear-gradient(90deg,#17ead9 0,#6078ea 100%)!important; color: #fff;
    }
    @media (max-width: 800px) {
      .member-card { padding: 12px 4px; }
      .search-box { float: none; margin-bottom: 16px; width: 100%; }
    }
  </style>
</head>
<body>
  <div class="container py-4">
    <div class="member-card shadow-sm">
      <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <h3 class="mb-0 fw-bold"><i class="bi bi-person-vcard"></i> Quản lý thẻ thành viên</h3>
        <input type="text" class="form-control search-box" id="search" placeholder="Tìm tài khoản, tên KH...">
      </div>
      <div class="table-responsive">
        <table class="table table-bordered align-middle text-center" id="memberTable">
          <thead>
            <tr>
              <th>#</th>
              <th>Tài khoản</th>
              <th>Tên khách hàng</th>
              <th>Tổng tiền đã mua</th>
              <th>Hạng thành viên</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($members as $i => $mb):
[$rank, $icon, $badge] = get_rank($mb['tong_mua']);
            ?>
            <tr>
              <td><?= $i+1 ?></td>
              <td>
                <span class="fw-semibold"><?= htmlspecialchars($mb['TENDANGNHAP']) ?></span>
                <br><span class="badge bg-light text-secondary"><?= $mb['MAKH'] ?></span>
              </td>
              <td><?= htmlspecialchars($mb['TENKH'] ?? '---') ?></td>
              <td class="fw-bold text-primary"><?= number_format($mb['tong_mua'],0,',','.') ?>đ</td>
              <td>
                <span class="rank-badge <?= $badge ?>">
                  <i class="bi <?= $icon ?>"></i> <?= $rank ?>
                </span>
              </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($members)): ?>
            <tr>
              <td colspan="5" class="text-muted">Chưa có thành viên nào</td>
            </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
      <div class="mt-4 d-flex gap-3 flex-wrap">
        <span class="badge bg-gradient-info rank-badge"><i class="bi bi-gem"></i> Kim cương: &ge; 20.000.000đ</span>
        <span class="badge bg-warning text-dark rank-badge"><i class="bi bi-award-fill"></i> Vàng: &ge; 10.000.000đ</span>
        <span class="badge bg-secondary rank-badge"><i class="bi bi-trophy"></i> Bạc: &ge; 5.000.000đ</span>
        <span class="badge bg-light text-dark rank-badge"><i class="bi bi-person"></i> Thành viên: &lt; 5.000.000đ</span>
      </div>
    </div>
  </div>
  <script>
    // Tìm kiếm realtime
    document.getElementById('search').addEventListener('input', function(){
      let q = this.value.trim().toLowerCase();
      let rows = document.querySelectorAll('#memberTable tbody tr');
      rows.forEach(row => {
        let text = row.innerText.toLowerCase();
        row.style.display = text.includes(q) ? '' : 'none';
      });
    });
  </script>
</body>
</html>
