<?php
// Xử lý thêm, sửa, xóa loại sản phẩm
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_product_type'])) {
        $tenloai = $_POST['tenloai'];
        $madm = $_POST['madm'];
        $stmt = $conn->query("SELECT MAX(CAST(SUBSTRING(MALOAI, 3) AS UNSIGNED)) AS max_maloai FROM loaisanpham");
        $row = $stmt->fetch();
        $nextNumber = ($row['max_maloai'] ?? 0) + 1;
        $maloai = 'L' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
        $stmt = $conn->prepare("INSERT INTO loaisanpham (MALOAI, TENLOAI, MADM) VALUES (?, ?, ?)");
        $stmt->execute([$maloai, $tenloai, $madm]);
        header("Location: admin.php?section=loaisanpham&success=add");
        exit;
    }
    elseif (isset($_POST['update_product_type'])) {
        $maloai = $_POST['maloai'];
        $tenloai = $_POST['tenloai'];
        $madm = $_POST['madm'];
        $stmt = $conn->prepare("UPDATE loaisanpham SET TENLOAI = ?, MADM = ? WHERE MALOAI = ?");
        $stmt->execute([$tenloai, $madm, $maloai]);
        header("Location: admin.php?section=loaisanpham&success=edit");
        exit;
    }
}

if (isset($_GET['delete_type'])) {
    $maloai = $_GET['delete_type'];
    $stmt = $conn->prepare("DELETE FROM loaisanpham WHERE MALOAI = ?");
    $stmt->execute([$maloai]);
    header("Location: admin.php?section=loaisanpham&success=delete");
    exit;
}

$stmt = $conn->query("SELECT * FROM danhmuc");
$danhmucs = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $conn->query("SELECT * FROM loaisanpham");
$loaisanphams = $stmt->fetchAll(PDO::FETCH_ASSOC);

$editProductType = null;
if (isset($_GET['edit_type'])) {
    $maloai = $_GET['edit_type'];
    $stmt = $conn->prepare("SELECT * FROM loaisanpham WHERE MALOAI = ?");
    $stmt->execute([$maloai]);
    $editProductType = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>

<div class="form-section mb-4" style="font-weight:bold;">
  <h3>Quản lý Loại sản phẩm</h3>
  <form method="POST" class="row g-3">
    <input type="hidden" name="maloai" value="<?= $editProductType['MALOAI'] ?? '' ?>">
    <div class="col-md-3">
      <label class="form-label">Tên loại sản phẩm</label>
      <input name="tenloai" class="form-control" value="<?= htmlspecialchars($editProductType['TENLOAI'] ?? '') ?>" required>
    </div>
    <div class="col-md-3">
      <label class="form-label">Danh mục</label>
      <select name="madm" class="form-select" required>
        <option value="">-- Chọn danh mục --</option>
        <?php foreach($danhmucs as $dm): ?>
          <option value="<?= $dm['MADM'] ?>" <?= (isset($editProductType['MADM']) && $editProductType['MADM'] == $dm['MADM']) ? 'selected' : '' ?>>
            <?= htmlspecialchars($dm['TENDM']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-2 d-flex align-items-end">
      <?php if ($editProductType): ?>
        <button name="update_product_type" class="btn btn-warning">Cập nhật</button>
      <?php else: ?>
        <button name="add_product_type" class="btn btn-primary">Thêm</button>
      <?php endif; ?>
    </div>
  </form>
</div>

<div class="table-section">
  <h5>Danh sách loại sản phẩm</h5>
  <table class="table table-striped">
    <thead>
      <tr>
        <th>Mã Loại</th>
        <th>Tên loại</th>
        <th>Danh mục</th>
        <th>Thao tác</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach($loaisanphams as $lsp): ?>
      <tr>
        <td><?= htmlspecialchars($lsp['MALOAI']) ?></td>
        <td><?= htmlspecialchars($lsp['TENLOAI']) ?></td>
        <td>
          <?php
            foreach($danhmucs as $dm) {
              if($dm['MADM'] == $lsp['MADM']) {
                echo htmlspecialchars($dm['TENDM']);
                break;
              }
            }
          ?>
        </td>
        <td>
          <a href="?section=loaisanpham&edit_type=<?= $lsp['MALOAI'] ?>" class="btn btn-sm btn-warning">Sửa</a>
          <a href="?section=loaisanpham&delete_type=<?= $lsp['MALOAI'] ?>" 
            class="btn btn-sm btn-danger" 
            onclick="return confirm('Bạn có chắc muốn xóa loại sản phẩm này?')">Xóa</a>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
