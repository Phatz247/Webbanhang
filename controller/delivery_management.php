<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../model/database.php';
$db = new database();
$conn = $db->getConnection();

// Helpers
function mapDeliveryToOrderStatus($delivery)
{
    switch ($delivery) {
        case 'Chờ xử lý':
        case 'Đang chuẩn bị':
            return 'Đang xử lý';
        case 'Đang giao':
            return 'Đang giao hàng';
        case 'Đã giao':
            return 'Đã giao hàng';
        case 'Yêu cầu hoàn':
            return 'Yêu cầu hoàn hàng';
        case 'Đã hoàn':
            return 'Đã hoàn hàng';
        case 'Đã hủy':
            return 'Đã hủy';
        default:
            return null;
    }
}

$alert = '';

// Update delivery status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_delivery'])) {
    $magh = $_POST['magh'];
    $madonhang = $_POST['madonhang'];
    $trangthai = $_POST['trangthaigh'];
    $ghichu = $_POST['ghichu_giao'] ?? null;

    $conn->beginTransaction();
    try {
        // Update giaohang
        $sql = "UPDATE giaohang SET TRANGTHAIGH = ?, NGAYCAPNHAT = NOW(), GHICHU_GH = ?";
        $params = [$trangthai, $ghichu];
        if ($trangthai === 'Đã giao') {
            $sql .= ", NGAYGIAO = COALESCE(NGAYGIAO, NOW())";
        }
        $sql .= " WHERE MAGH = ?";
        $params[] = $magh;
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);

        // Sync donhang
        $orderStatus = mapDeliveryToOrderStatus($trangthai);
        if ($orderStatus) {
            $stmt = $conn->prepare("UPDATE donhang SET TRANGTHAI = ? WHERE MADONHANG = ?");
            $stmt->execute([$orderStatus, $madonhang]);
        }

        $conn->commit();
        $_SESSION['alert_success'] = '✔️ Đã cập nhật trạng thái giao hàng!';
        echo "<script>window.location.href='/web_3/view/admin.php?section=giaohang';</script>"; exit;
    } catch (Exception $e) {
        $conn->rollBack();
        $alert = 'Có lỗi khi cập nhật: ' . $e->getMessage();
    }
}

// Fetch deliveries
$stmt = $conn->query("SELECT gh.*, dh.TRANGTHAI as TRANGTHAI_DH, dh.PHUONGTHUCTHANHTOAN, dh.TONGTIEN
                      FROM giaohang gh
                      LEFT JOIN donhang dh ON gh.MADONHANG = dh.MADONHANG
                      ORDER BY gh.NGAYTAO DESC");
$deliveries = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<div class="container py-4">
  <?php if ($alert): ?>
    <div class="alert alert-danger alert-fixed" id="alert-msg"><?= htmlspecialchars($alert) ?></div>
  <?php endif; ?>
  <?php if (!empty($_SESSION['alert_success'])): ?>
    <div class="alert alert-success alert-fixed" id="alert-msg"><?= $_SESSION['alert_success']; unset($_SESSION['alert_success']); ?></div>
  <?php endif; ?>

  <div class="table-section">
    <h4>Quản lý giao hàng</h4>
    <div class="table-responsive">
      <table class="table table-striped table-hover align-middle">
        <thead>
          <tr>
            <th>Mã GH</th>
            <th>Mã ĐH</th>
            <th>Ngày tạo</th>
            <th>Trạng thái GH</th>
            <th>Trạng thái đơn</th>
            <th>Người nhận</th>
            <th>Địa chỉ / SĐT</th>
            <th>Ghi chú</th>
            <th>Thao tác</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($deliveries as $gh): ?>
            <tr>
              <td><code><?= htmlspecialchars($gh['MAGH']) ?></code></td>
              <td><code><?= htmlspecialchars($gh['MADONHANG']) ?></code></td>
              <td><?= $gh['NGAYTAO'] ? date('d/m/Y H:i', strtotime($gh['NGAYTAO'])) : '-' ?></td>
              <td>
                <span class="badge <?= $gh['TRANGTHAIGH']==='Đang giao'?'bg-warning':($gh['TRANGTHAIGH']==='Đã giao'?'bg-success':($gh['TRANGTHAIGH']==='Đã hủy'?'bg-danger':'bg-secondary')) ?>">
                  <?= htmlspecialchars($gh['TRANGTHAIGH'] ?? 'Chờ xử lý') ?>
                </span>
                <?php if (!empty($gh['NGAYGIAO'])): ?>
                  <div class="text-muted small">Giao: <?= date('d/m/Y H:i', strtotime($gh['NGAYGIAO'])) ?></div>
                <?php endif; ?>
              </td>
              <td><span class="badge bg-info"><?= htmlspecialchars($gh['TRANGTHAI_DH'] ?? '-') ?></span></td>
              <td>
                <div><?= htmlspecialchars($gh['TEN_NGUOINHAN'] ?? '-') ?></div>
              </td>
              <td>
                <div class="small"><?= htmlspecialchars($gh['DIACHIGIAO'] ?? '-') ?></div>
                <div class="small"><?= htmlspecialchars($gh['SDT_NHAN'] ?? '-') ?></div>
              </td>
              <td class="small"><?= htmlspecialchars($gh['GHICHU_GH'] ?? '-') ?></td>
              <td>
                <form method="post" class="d-flex align-items-center" style="gap:6px;">
                  <input type="hidden" name="update_delivery" value="1" />
                  <input type="hidden" name="magh" value="<?= htmlspecialchars($gh['MAGH']) ?>" />
                  <input type="hidden" name="madonhang" value="<?= htmlspecialchars($gh['MADONHANG']) ?>" />
                  <select name="trangthaigh" class="form-select form-select-sm" style="width:150px;">
                    <?php $opts = ['Chờ xử lý','Đang chuẩn bị','Đang giao','Đã giao','Yêu cầu hoàn','Đã hoàn','Đã hủy'];
                      foreach ($opts as $opt): ?>
                      <option value="<?= $opt ?>" <?= ($gh['TRANGTHAIGH'] ?? 'Chờ xử lý') === $opt ? 'selected' : '' ?>><?= $opt ?></option>
                    <?php endforeach; ?>
                  </select>
                  <input type="text" name="ghichu_giao" class="form-control form-control-sm" placeholder="Ghi chú" value="<?= htmlspecialchars($gh['GHICHU_GH'] ?? '') ?>" />
                  <button class="btn btn-sm btn-primary">Cập nhật</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<style>
.table-section { background:#fff; border-radius:10px; padding:24px; box-shadow:0 4px 18px rgba(0,0,0,0.06); }
.alert-fixed { position:fixed; top:24px; right:32px; z-index:10000; }
</style>

<?php
// end file
?>
