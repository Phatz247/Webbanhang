<?php
// Xử lý add, edit, delete danh mục
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_category'])) {
        $tendm = $_POST['tendm'];
        $mota = $_POST['mota'];
        $stmt = $conn->query("SELECT MAX(CAST(SUBSTRING(MADM, 3) AS UNSIGNED)) AS max_madm FROM danhmuc");
        $row = $stmt->fetch();
        $nextNumber = ($row['max_madm'] ?? 0) + 1;
        $madm = 'DM' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
        $stmt = $conn->prepare("INSERT INTO danhmuc (MADM, TENDM, MOTA) VALUES (?, ?, ?)");
        $stmt->execute([$madm, $tendm, $mota]);
        header("Location: admin.php?section=danhmuc&success=add");
        exit;
    }
    elseif (isset($_POST['update_category'])) {
        $madm = $_POST['madm'];
        $tendm = $_POST['tendm'];
        $mota = $_POST['mota'];
        $stmt = $conn->prepare("UPDATE danhmuc SET TENDM = ?, MOTA = ? WHERE MADM = ?");
        $stmt->execute([$tendm, $mota, $madm]);
        header("Location: admin.php?section=danhmuc&success=edit");
        exit;
    }
}

if (isset($_GET['delete'])) {
    $madm = $_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM danhmuc WHERE MADM = ?");
    $stmt->execute([$madm]);
    header("Location: admin.php?section=danhmuc&success=delete");
    exit;
}

$stmt = $conn->query("SELECT * FROM danhmuc");
$danhmucs = $stmt->fetchAll(PDO::FETCH_ASSOC);

$editCategory = null;
if (isset($_GET['edit'])) {
    $madm = $_GET['edit'];
    $stmt = $conn->prepare("SELECT * FROM danhmuc WHERE MADM = ?");
    $stmt->execute([$madm]);
    $editCategory = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>
<div class="card shadow-sm mb-4" style="border-radius: 12px;">
  <div class="card-body">
    <h4 class="mb-4 fw-bold">
      <?= $editCategory ? 'Cập nhật Danh mục' : 'Thêm Danh mục mới' ?>
    </h4>
    <form method="POST" class="row g-4 align-items-end">
      <input type="hidden" name="madm" value="<?= $editCategory['MADM'] ?? '' ?>">
      <div class="col-lg-4 col-md-6">
        <label class="form-label fw-semibold">Tên danh mục <span class="text-danger">*</span></label>
        <input name="tendm" class="form-control rounded-3" value="<?= htmlspecialchars($editCategory['TENDM'] ?? '') ?>" required>
      </div>
      <div class="col-lg-6 col-md-6">
        <label class="form-label fw-semibold">Mô tả</label>
        <textarea name="mota" class="form-control rounded-3" rows="1"><?= htmlspecialchars($editCategory['MOTA'] ?? '') ?></textarea>
      </div>
      <div class="col-lg-2 col-12 d-flex align-items-end">
        <?php if ($editCategory): ?>
          <button name="update_category" class="btn btn-warning w-100 fw-bold shadow-sm">Cập nhật</button>
        <?php else: ?>
          <button name="add_category" class="btn btn-primary w-100 fw-bold shadow-sm">Thêm</button>
        <?php endif; ?>
      </div>
    </form>
  </div>
</div>

<div class="card shadow table-section">
  <div class="card-body">
    <h5 class="mb-3 fw-bold">Danh sách danh mục</h5>
    <div class="table-responsive">
      <table class="table table-striped align-middle">
        <thead class="table-light">
          <tr>
            <th style="width:90px">Mã DM</th>
            <th style="width:200px">Tên danh mục</th>
            <th>Mô tả</th>
            <th style="width:120px">Thao tác</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach($danhmucs as $dm): ?>
          <tr>
            <td class="text-center fw-semibold"><?= htmlspecialchars($dm['MADM']) ?></td>
            <td><?= htmlspecialchars($dm['TENDM']) ?></td>
            <td><?= htmlspecialchars($dm['MOTA']) ?></td>
            <td>
              <a href="?section=danhmuc&edit=<?= $dm['MADM'] ?>" class="btn btn-sm btn-warning me-1">Sửa</a>
              <a href="?section=danhmuc&delete=<?= $dm['MADM'] ?>" 
                class="btn btn-sm btn-danger"
                onclick="return confirm('Bạn có chắc muốn xóa danh mục này?')">Xóa</a>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php if (empty($danhmucs)): ?>
          <tr>
            <td colspan="4" class="text-center text-muted">Chưa có danh mục nào</td>
          </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
