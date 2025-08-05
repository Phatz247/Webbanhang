<?php
// PHẦN XỬ LÝ PHP

// Kết nối DB
require_once __DIR__ . "/../model/database.php";
$db = new database();
$conn = $db->getConnection();

$alert = "";

// Lấy danh mục & loại sản phẩm
$stmt = $conn->query("SELECT * FROM danhmuc");
$danhmucs = $stmt->fetchAll(PDO::FETCH_ASSOC);
$stmt = $conn->query("SELECT * FROM loaisanpham");
$loaisanphams = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ==== XỬ LÝ POST ====
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // Thêm sản phẩm
    if ($action === 'add') {
        $tenSP = $_POST['name'];
        $madm = $_POST['madm'];
        $MALOAI = $_POST['MALOAI'];
        $flag = $_POST['flag'] ?? 'none';
        $mota = $_POST['desc'];
        $hot = $new = $sale = 0;
        if ($flag === 'hot') $hot = 1;
        if ($flag === 'new') $new = 1;
        if ($flag === 'sale') $sale = 1;

        $stmt = $conn->query("SELECT MASP FROM sanpham WHERE MASP LIKE 'SP%' ORDER BY MASP DESC LIMIT 1");
        $last = $stmt->fetchColumn();
        $number = $last ? (int)substr($last, 2, 3) : 0;
        $groupSP = 'SP' . str_pad($number + 1, 3, '0', STR_PAD_LEFT);

        $mainImageIndex = $_POST['main_image'] ?? 0;

        // Xác định loại size theo danh mục chọn
        $sizeType = 'ao';
        foreach ($danhmucs as $dm) {
            if ($dm['MADM'] == $madm) {
                if (stripos($dm['TENDM'], 'quần') !== false) $sizeType = 'quan';
                else if (stripos($dm['TENDM'], 'phụ kiện') !== false) $sizeType = 'phukien';
                else $sizeType = 'ao';
                break;
            }
        }

        $sizes_arr = [];
        if ($sizeType == 'ao') $sizes_arr = ['S','M','L','XL'];
        elseif ($sizeType == 'quan') $sizes_arr = ['28','29','30','31','32','33','34'];
        else $sizes_arr = ['Freesize'];

        foreach ($_POST['colors'] as $index => $mau) {
            $imgName = $_FILES['images']['name'][$index];
            $tmpName = $_FILES['images']['tmp_name'][$index];
            if (!file_exists('uploads')) mkdir('uploads', 0777, true);
            move_uploaded_file($tmpName, "uploads/" . $imgName);

            $is_main = ($index == $mainImageIndex) ? 1 : 0;
            $size_type = $_POST['sizes_type'][$index] ?? 'all';

            if ($sizeType == 'phukien') {
                $maSP = $groupSP . chr(65 + $index);
                $price = $_POST['prices'][$index];
                $qty = $_POST['qtys'][$index];
                $stmt = $conn->prepare("INSERT INTO sanpham 
                    (MASP, GROUPSP, TENSP, MAUSAC, KICHTHUOC, GIA, SOLUONG, MOTA, HINHANH, MADM, MALOAI, hot, news, outsale, is_main)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$maSP, $groupSP, $tenSP, $mau, 'Freesize', $price, $qty, $mota, $imgName, $madm, $MALOAI, $hot, $new, $sale, $is_main]);
            } elseif ($size_type === 'custom') {
                foreach ($sizes_arr as $si) {
                    $price = trim($_POST['custom_price_' . $si][$index] ?? '');
                    $qty = trim($_POST['custom_qty_' . $si][$index] ?? '');
                    if ($price === '' || $qty === '') continue;
                    $maSP = $groupSP . chr(65 + $index) . $si;
                    $stmt = $conn->prepare("INSERT INTO sanpham 
                        (MASP, GROUPSP, TENSP, MAUSAC, KICHTHUOC, GIA, SOLUONG, MOTA, HINHANH, MADM, MALOAI, hot, news, outsale, is_main)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$maSP, $groupSP, $tenSP, $mau, $si, $price, $qty, $mota, $imgName, $madm, $MALOAI, $hot, $new, $sale, $is_main]);
                }
            } else {
                $price = $_POST['prices'][$index];
                $qty = $_POST['qtys'][$index];
                foreach ($sizes_arr as $si) {
                    $maSP = $groupSP . chr(65 + $index) . $si;
                    $stmt = $conn->prepare("INSERT INTO sanpham 
                        (MASP, GROUPSP, TENSP, MAUSAC, KICHTHUOC, GIA, SOLUONG, MOTA, HINHANH, MADM, MALOAI, hot, news, outsale, is_main)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$maSP, $groupSP, $tenSP, $mau, $si, $price, $qty, $mota, $imgName, $madm, $MALOAI, $hot, $new, $sale, $is_main]);
                }
            }
        }
        header("Location: ../view/admin.php?section=sanpham&success=add");
        exit;
    }

    // Xóa sản phẩm
    if ($action === 'delete') {
        $delete_mode = $_POST['delete_mode'] ?? 'manual';
        if ($delete_mode === 'checkbox') {
            $selected_products = $_POST['selected_products'] ?? [];
            if (!empty($selected_products)) {
                if (is_array($selected_products) && count($selected_products) == 1 && strpos($selected_products[0], ',') !== false) {
                    $selected_products = array_filter(array_map('trim', explode(',', $selected_products[0])));
                }
                $placeholders = implode(',', array_fill(0, count($selected_products), '?'));
                $stmt = $conn->prepare("UPDATE sanpham SET T_Thai = 1 WHERE MASP IN ($placeholders)");
                $stmt->execute($selected_products);
                echo "<script>alert('Đã ẩn ".count($selected_products)." sản phẩm được chọn!');</script>";
            } else {
                echo "<script>alert('Vui lòng chọn ít nhất một sản phẩm để ẩn!');</script>";
            }
        } else {
            $masps = $_POST['masp'] ?? '';
            $maspArr = array_filter(array_map(function($v) { return strtoupper(trim($v)); }, explode(',', $masps)));
            if (count($maspArr) > 0) {
                $placeholders = implode(',', array_fill(0, count($maspArr), '?'));
                $stmtCheck = $conn->prepare("SELECT MASP FROM sanpham WHERE MASP IN ($placeholders)");
                $stmtCheck->execute($maspArr);
                $foundMASP = $stmtCheck->fetchAll(PDO::FETCH_COLUMN);

                if (count($foundMASP) == 0) {
                    echo "<script>alert('Không tìm thấy sản phẩm nào để ẩn!');</script>";
                } else {
                    $placeholders2 = implode(',', array_fill(0, count($foundMASP), '?'));
                    $stmt = $conn->prepare("UPDATE sanpham SET T_Thai = 1 WHERE MASP IN ($placeholders2)");
                    $stmt->execute($foundMASP);
                    echo "<script>alert('Đã ẩn các mã: ".implode(', ', $foundMASP)."');</script>";
                }
            } else {
                echo "<script>alert('Vui lòng nhập ít nhất một mã sản phẩm để ẩn!');</script>";
            }
        }
    }

    // Sửa sản phẩm
    if ($action === 'edit') {
        $masp = $_POST['masp'];
        $fields = [];
        $params = [];
        foreach (['name' => 'TENSP', 'color' => 'MAUSAC', 'size' => 'KICHTHUOC', 'price' => 'GIA', 'qty' => 'SOLUONG', 'desc' => 'MOTA', 'madm' => 'MADM'] as $f => $col) {
            if (!empty($_POST[$f])) {
                $fields[] = "$col = ?";
                $params[] = $_POST[$f];
            }
        }
        if (!empty($_FILES['image']['name'])) {
            $img = $_FILES['image']['name'];
            move_uploaded_file($_FILES['image']['tmp_name'], "uploads/" . $img);
            $fields[] = "HINHANH = ?";
            $params[] = $img;
        }
        if (!empty($_POST['MALOAI'])) {
            $fields[] = "MALOAI = ?";
            $params[] = $_POST['MALOAI'];
        }
        $flag = $_POST['flag'] ?? 'none';
        $hot = $new = $sale = 0;
        if ($flag === 'hot') $hot = 1;
        if ($flag === 'new') $new = 1;
        if ($flag === 'sale') $sale = 1;
        $fields[] = "hot = ?";
        $params[] = $hot;
        $fields[] = "news = ?";
        $params[] = $new;
        $fields[] = "outsale = ?";
        $params[] = $sale;

        if ($fields) {
            $params[] = $masp;
            $sql = "UPDATE sanpham SET " . implode(", ", $fields) . " WHERE MASP = ?";
            $conn->prepare($sql)->execute($params);
            header("Location: sanpham.php?success=edit");
            exit;
        }
    }
}

// Lọc sản phẩm
$filter_madm = $_GET['filter_madm'] ?? '';
$filter_maloai = $_GET['filter_maloai'] ?? '';
$filter_flag = $_GET['filter_flag'] ?? '';

$where_conditions = [];
$where_params = [];
if ($filter_madm) {
    $where_conditions[] = "sp.MADM = ?";
    $where_params[] = $filter_madm;
}
if ($filter_maloai) {
    $where_conditions[] = "sp.MALOAI = ?";
    $where_params[] = $filter_maloai;
}
if ($filter_flag) {
    switch ($filter_flag) {
        case 'hot': $where_conditions[] = "sp.hot = 1"; break;
        case 'new': $where_conditions[] = "sp.news = 1"; break;
        case 'sale': $where_conditions[] = "sp.outsale = 1"; break;
    }
}
$where_clause = '';
if (!empty($where_conditions)) {
    $where_clause = ' WHERE ' . implode(' AND ', $where_conditions);
}
$sql = "SELECT sp.*, dm.TENDM, lsp.TENLOAI, 
        ctkm.TENCTKM, ct.gia_khuyenmai, ct.giam_phantram,
        ctkm.NGAYBATDAU, ctkm.NGAYKETTHUC
        FROM sanpham sp 
        LEFT JOIN danhmuc dm ON sp.MADM = dm.MADM 
        LEFT JOIN loaisanpham lsp ON sp.MALOAI = lsp.MALOAI 
        LEFT JOIN chitietctkm ct ON sp.MASP = ct.MASP
        LEFT JOIN chuongtrinhkhuyenmai ctkm ON ct.MACTKM = ctkm.MACTKM 
          AND NOW() BETWEEN ctkm.NGAYBATDAU AND ctkm.NGAYKETTHUC" . $where_clause . " 
        ORDER BY sp.ID DESC";
$stmt = $conn->prepare($sql);
$stmt->execute($where_params);
$sanphams = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!-- BẮT ĐẦU PHẦN HTML + JS (KHÔNG ĐƯỢC ĐỂ TRONG <?php ?>) -->
<div class="form-section mb-4">
  <!-- ... (phần HTML quản lý sản phẩm của bạn, giống code trước) ... -->
  <!-- Bỏ phần HTML ở đây vì quá dài, bạn copy đúng từ file cũ sang -->
</div>

<!-- Filter sản phẩm -->
<!-- ... (phần HTML filter sản phẩm, giống file bạn gửi) ... -->

<!-- Danh sách sản phẩm -->
<!-- ... (phần HTML danh sách sản phẩm) ... -->

<script>
const PRODUCT_TYPE_DATA = <?= json_encode($loaisanphams) ?>;
const CATEGORY_DATA = <?= json_encode($danhmucs) ?>;
const SIZE_MAP = {
  'ao': ['S','M','L','XL'],
  'quan': ['28','29','30','31','32','33','34'],
  'phukien': ['Freesize']
};

window.CURRENT_SIZES = SIZE_MAP['ao'];

function getCurrentSizeType() {
  const madmSelect = document.getElementById('madm-select');
  if (!madmSelect) return 'ao';
  const selected = madmSelect.options[madmSelect.selectedIndex];
  return selected?.getAttribute('data-size-type') || 'ao';
}

function updateProductTypeByCategory() {
  const madmSelect = document.getElementById('madm-select');
  const typeSelect = document.getElementById('product-type-select');
  if (!madmSelect || !typeSelect) return;
  const selectedMadm = madmSelect.value;
  typeSelect.innerHTML = '<option value="">--Chọn loại--</option>';
  if(selectedMadm) {
    const filteredTypes = PRODUCT_TYPE_DATA.filter(type => type.MADM === selectedMadm);
    if(filteredTypes.length > 0) {
      filteredTypes.forEach(type => {
        const option = document.createElement('option');
        option.value = type.MALOAI;
        option.textContent = type.TENLOAI;
        typeSelect.appendChild(option);
      });
      typeSelect.disabled = false;
    } else {
      typeSelect.disabled = true;
    }
  } else {
    typeSelect.disabled = true;
  }
}

function updateSizesByCategory() {
  window.CURRENT_SIZES = SIZE_MAP[getCurrentSizeType()] || ['S','M','L','XL'];
  const multiColor = document.getElementById('multi-color-images');
  if(multiColor) multiColor.innerHTML = '';
}

function addColorImage() {
  const sizes = window.CURRENT_SIZES || ['S','M','L','XL'];
  const container = document.getElementById('multi-color-images');
  const count = container.querySelectorAll('.color-image-set').length;
  const hasSize = sizes.length > 0;
  const sizeType = getCurrentSizeType();
  let sizeInputHtml = '';
  if (sizeType === 'phukien') {
    sizeInputHtml = `
      <div class="col-md-4">
        <label class="form-label">Giá:</label>
        <input name="prices[]" class="form-control form-control-sm mb-1" type="number" min="0" placeholder="Giá" required>
        <label class="form-label">Số lượng:</label>
        <input name="qtys[]" class="form-control form-control-sm" type="number" min="1" placeholder="Số lượng" required>
        <input type="hidden" name="sizes_type[]" value="freesize">
      </div>
    `;
  } else if (hasSize) {
    sizeInputHtml = `
      <div class="col-md-3 size-all-input">
        <label class="form-label">Giá:</label>
        <input name="prices[]" class="form-control form-control-sm mb-1" type="number" min="0" placeholder="Giá" required>
        <label class="form-label">Số lượng:</label>
        <input name="qtys[]" class="form-control form-control-sm" type="number" min="1" placeholder="Số lượng" required>
      </div>
      <div class="col-md-5 d-none size-custom-input">
        <div class="row">
          ${sizes.map(sz => `
            <div class="col">
              <label class="form-label mb-1">${sz}:</label>
              <input name="custom_price_${sz}[]" type="number" class="form-control form-control-sm mb-1" min="0" placeholder="Giá" style="min-width:60px;height:40px;font-size:16px;text-align:center;">
              <input name="custom_qty_${sz}[]" type="number" class="form-control form-control-sm mb-2" min="1" placeholder="SL" style="min-width:60px;height:40px;font-size:16px;text-align:center;">
            </div>
          `).join('')}
        </div>
      </div>
      <div class="col-md-2">
        <label class="form-label">Size:</label>
        <select name="sizes_type[]" class="form-select form-select-sm size-type" onchange="toggleSizeInputs(this)">
          <option value="all">Tất cả size</option>
          <option value="custom">Chọn size</option>
        </select>
      </div>
    `;
  }
  const div = document.createElement('div');
  div.className = "color-image-set p-2";
  div.innerHTML = `
    <div class="row g-2 align-items-end">
      <div class="col-md-2">
        <label class="form-label">Màu:</label>
        <input name="colors[]" class="form-control form-control-sm" required>
      </div>
      <div class="col-md-2">
        <label class="form-label">Ảnh:</label>
        <input type="file" name="images[]" class="form-control form-control-sm" required>
      </div>
      ${sizeInputHtml}
      <div class="col-md-1">
        <label class="form-label d-block">&nbsp;</label>
        <input type="radio" name="main_image" value="${count}" title="Chọn làm hình chính" ${count==0 ? 'checked' : ''} />
        <span style="font-size:12px">Chính</span>
        <button type="button" class="btn btn-link text-danger btn-sm d-block" onclick="this.closest('.color-image-set').remove()" title="Xóa màu">&times;</button>
      </div>
    </div>
  `;
  container.appendChild(div);
}

function toggleSizeInputs(select) {
  const parent = select.closest('.color-image-set');
  const allDiv = parent.querySelector('.size-all-input');
  const customDiv = parent.querySelector('.size-custom-input');
  const allInputs = allDiv?.querySelectorAll('input') || [];
  const customInputs = customDiv?.querySelectorAll('input') || [];
  if (select.value === 'custom') {
    allDiv.classList.add('d-none');
    customDiv.classList.remove('d-none');
    allInputs.forEach(input => input.removeAttribute('required'));
  } else {
    allDiv.classList.remove('d-none');
    customDiv.classList.add('d-none');
    allInputs.forEach(input => input.setAttribute('required', 'required'));
    customInputs.forEach(input => input.removeAttribute('required'));
  }
}

function toggleDeleteMode() {
  const manualMode = document.getElementById('manual_mode');
  const checkboxMode = document.getElementById('checkbox_mode');
  const manualDiv = document.getElementById('manual-delete');
  const checkboxDiv = document.getElementById('checkbox-delete');
  const deleteBar = document.getElementById('checkbox-delete-bar');
  const deleteModeInput = document.getElementById('delete_mode_input');
  if (checkboxMode.checked) {
    manualDiv.style.display = 'none';
    checkboxDiv.style.display = 'block';
    deleteModeInput.value = 'checkbox';
    updateSelectedCount();
  } else {
    manualDiv.style.display = 'block';
    checkboxDiv.style.display = 'none';
    deleteBar.style.display = 'none';
    deleteModeInput.value = 'manual';
  }
}

function updateSelectedCount() {
  const checkboxes = document.querySelectorAll('.product-checkbox:checked');
  const count = checkboxes.length;
  const countBarSpan = document.getElementById('selected-count-bar');
  const deleteBar = document.getElementById('checkbox-delete-bar');
  if (countBarSpan) countBarSpan.textContent = count;
  if (count > 0) {
    deleteBar.style.display = 'block';
  } else {
    deleteBar.style.display = 'none';
  }
}

function toggleSelectAll() {
  const selectAll = document.getElementById('select-all');
  const checkboxes = document.querySelectorAll('.product-checkbox');
  checkboxes.forEach(checkbox => {
    checkbox.checked = selectAll.checked;
  });
  updateSelectedCount();
}

function deleteSelectedProducts() {
  const checkboxes = document.querySelectorAll('.product-checkbox:checked');
  const manualInput = document.getElementById('manual-input-bar').value.trim();
  let selectedProducts = Array.from(checkboxes).map(cb => cb.value);
  if (manualInput) {
    const manualProducts = manualInput.split(',').map(p => p.trim().toUpperCase()).filter(p => p);
    selectedProducts = selectedProducts.concat(manualProducts);
  }
  if (selectedProducts.length === 0) {
    alert('Vui lòng chọn ít nhất một sản phẩm để xóa!');
    return;
  }
  if (confirm(`Bạn có chắc muốn xóa ${selectedProducts.length} sản phẩm?`)) {
    const form = document.getElementById('delete-form');
    const input = document.createElement('input');
    input.type = 'hidden';
    input.name = 'selected_products[]';
    input.value = selectedProducts.join(',');
    form.appendChild(input);
    document.getElementById('delete_mode_input').value = 'checkbox';
    form.submit();
  }
}

function clearSelection() {
  const checkboxes = document.querySelectorAll('.product-checkbox');
  const selectAll = document.getElementById('select-all');
  const manualInput = document.getElementById('manual-input-bar');
  checkboxes.forEach(checkbox => checkbox.checked = false);
  if(selectAll) selectAll.checked = false;
  if(manualInput) manualInput.value = '';
  updateSelectedCount();
}

window.addEventListener('DOMContentLoaded', function() {
  updateProductTypeByCategory();
  window.CURRENT_SIZES = SIZE_MAP[getCurrentSizeType()] || ['S','M','L','XL'];
});
</script>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<body>
  <div class="form-section mb-4" style="font-weight:bold;">
    <h4>Quản lý sản phẩm</h4>
    <div class="form-section mb-4">
      
  <ul class="nav nav-tabs mb-3" id="productTab" role="tablist">
    <li class="nav-item" role="presentation">
      <button class="nav-link active" id="add-tab" data-bs-toggle="tab" data-bs-target="#add" type="button" role="tab">Thêm</button>
    </li>
    <li class="nav-item" role="presentation">
      <button class="nav-link" id="delete-tab" data-bs-toggle="tab" data-bs-target="#delete" type="button" role="tab">Xóa</button>
    </li>
    <li class="nav-item" role="presentation">
      <button class="nav-link" id="edit-tab" data-bs-toggle="tab" data-bs-target="#edit" type="button" role="tab">Sửa</button>
    </li>
  </ul>
  <div class="tab-content" id="productTabContent">
    <!-- Thêm sản phẩm -->
    <div class="tab-pane fade show active" id="add" role="tabpanel">
      <form method="POST" enctype="multipart/form-data" class="row g-3">
        <input type="hidden" name="action" value="add">
        <div class="col-md-3">
          <label class="form-label">Tên sản phẩm</label>
          <input name="name" class="form-control" required>
        </div>
        <div class="col-md-2">
          <label class="form-label">Danh mục</label>
          <select name="madm" class="form-select" id="madm-select" required onchange="updateProductTypeByCategory();updateSizesByCategory();">
            <option value="">-- Chọn danh mục --</option>
            <?php foreach($danhmucs as $dm): ?>
              <?php
                $sizeType = 'ao';
                if (stripos($dm['TENDM'], 'quần') !== false) $sizeType = 'quan';
                if (stripos($dm['TENDM'], 'phụ kiện') !== false) $sizeType = 'phukien';
              ?>
              <option value="<?= $dm['MADM'] ?>" data-size-type="<?= $sizeType ?>"><?= $dm['TENDM'] ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-2">
          <label class="form-label">Loại sản phẩm</label>
          <select name="MALOAI" class="form-select" id="product-type-select" required>
            <option value="">--Chọn loại--</option>
          </select>
        </div>
        <div class="col-md-2">
          <label class="form-label">Trạng thái</label>
          <select name="flag" class="form-select" required>
            <option value="none">Không có</option>
            <option value="hot">HOT</option>
            <option value="new">NEW</option>
            <option value="sale">SALE</option>
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label">Mô tả</label>
          <textarea name="desc" class="form-control"></textarea>
        </div>
        <div class="col-md-12">
          <label class="form-label mb-2">Màu sắc, Size, Ảnh, Giá, Số lượng</label>
          <div id="multi-color-images"></div>
          <button type="button" class="btn btn-secondary btn-sm mt-2" onclick="addColorImage()">+ Thêm màu & ảnh</button>
        </div>
        <div class="col-12 text-end">
          <button class="btn btn-primary">Thêm sản phẩm</button>
        </div>
      </form>
    </div>
    <!-- Xóa sản phẩm -->
    <div class="tab-pane fade" id="delete" role="tabpanel">
      <div class="delete-mode-toggle">
        <div class="form-check form-check-inline">
          <input class="form-check-input" type="radio" name="delete_mode_radio" id="manual_mode" value="manual" checked onchange="toggleDeleteMode()">
          <label class="form-check-label" for="manual_mode">Nhập mã thủ công</label>
        </div>
        <div class="form-check form-check-inline">
          <input class="form-check-input" type="radio" name="delete_mode_radio" id="checkbox_mode" value="checkbox" onchange="toggleDeleteMode()">
          <label class="form-check-label" for="checkbox_mode">Chọn từ danh sách</label>
        </div>
      </div>
      <div id="checkbox-delete-bar" class="delete-checkbox-section" style="display: none;">
        <div class="d-flex justify-content-between align-items-center">
          <span><strong>Đã chọn: <span id="selected-count-bar">0</span> sản phẩm</strong></span>
          <div>
            <input type="text" id="manual-input-bar" class="form-control d-inline-block me-2" style="width: 200px;" placeholder="Nhập thêm mã sản phẩm">
            <button type="button" class="btn btn-danger" onclick="deleteSelectedProducts()">Xóa sản phẩm</button>
            <button type="button" class="btn btn-secondary" onclick="clearSelection()">Bỏ chọn</button>
          </div>
        </div>
      </div>
      <form method="POST" class="row g-3" id="delete-form">
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="delete_mode" id="delete_mode_input" value="manual">
        <div id="manual-delete" class="col-md-12">
          <div class="row">
            <div class="col-md-6">
              <label class="form-label">Mã sản phẩm (MASP) - cách nhau bởi dấu phẩy</label>
              <input name="masp" class="form-control" placeholder="Ví dụ: SP001A, SP002B" id="manual-input-main">
            </div>
            <div class="col-md-6 d-flex align-items-end">
              <button type="submit" class="btn btn-danger">Xóa sản phẩm</button>
            </div>
          </div>
        </div>
        <div id="checkbox-delete" class="col-md-12" style="display: none;">
          <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
            <table class="table table-sm table-striped">
              <thead class="sticky-top bg-white">
                <tr>
                  <th><input type="checkbox" id="select-all" onchange="toggleSelectAll()"></th>
                  <th>Mã SP</th>
                  <th>Tên SP</th>
                  <th>Màu</th>
                  <th>Size</th>
                  <th>Giá</th>
                  <th>Hình ảnh</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach($sanphams as $sp): ?>
                <tr>
                  <td><input type="checkbox" name="selected_products[]" value="<?= $sp['MASP'] ?>" class="product-checkbox" onchange="updateSelectedCount()"></td>
                  <td><?= htmlspecialchars($sp['MASP']) ?></td>
                  <td><?= htmlspecialchars($sp['TENSP']) ?></td>
                  <td><?= htmlspecialchars($sp['MAUSAC'] ?? '') ?></td>
                  <td><?= htmlspecialchars($sp['KICHTHUOC'] ?? '') ?></td>
                  <td><?= number_format($sp['GIA']) ?>đ</td>
                  <td>
                    <?php if($sp['HINHANH']): ?>
                      <img src="uploads/<?= $sp['HINHANH'] ?>" alt="img" style="max-width:30px;">
                    <?php endif; ?>
                  </td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      </form>
    </div>
    <!-- Sửa sản phẩm -->
    <div class="tab-pane fade" id="edit" role="tabpanel">
      <form method="POST" enctype="multipart/form-data" class="row g-3">
        <input type="hidden" name="action" value="edit">
        <div class="col-md-2">
          <label class="form-label">Mã sản phẩm</label>
          <input name="masp" class="form-control" required>
        </div>
        <div class="col-md-2">
          <label class="form-label">Tên mới</label>
          <input name="name" class="form-control">
        </div>
        <div class="col-md-2">
          <label class="form-label">Màu sắc</label>
          <input name="color" class="form-control">
        </div>
        <div class="col-md-2">
          <label class="form-label">Kích thước</label>
          <input name="size" class="form-control">
        </div>
        <div class="col-md-2">
          <label class="form-label">Giá</label>
          <input name="price" class="form-control">
        </div>
        <div class="col-md-2">
          <label class="form-label">Số lượng</label>
          <input name="qty" class="form-control">
        </div>
        <div class="col-md-4">
          <label class="form-label">Mô tả</label>
          <textarea name="desc" class="form-control"></textarea>
        </div>
        <div class="col-md-4">
          <label class="form-label">Hình ảnh mới</label>
          <input type="file" name="image" class="form-control">
        </div>
        <div class="col-md-2">
          <label class="form-label">Danh mục mới</label>
          <select name="madm" class="form-select">
            <option value="">--Không đổi--</option>
            <?php foreach($danhmucs as $dm): ?>
              <option value="<?= htmlspecialchars($dm['MADM']) ?>"><?= htmlspecialchars($dm['TENDM']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-2">
          <label class="form-label">Loại mới</label>
          <select name="MALOAI" class="form-select">
            <option value="">--Không đổi--</option>
            <?php foreach($loaisanphams as $lsp): ?>
              <option value="<?= htmlspecialchars($lsp['MALOAI']) ?>"><?= htmlspecialchars($lsp['TENLOAI']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-2">
          <label class="form-label">Trạng thái</label>
          <select name="flag" class="form-select">
            <option value="none">Không đổi</option>
            <option value="hot">HOT</option>
            <option value="new">NEW</option>
            <option value="sale">SALE</option>
          </select>
        </div>
        <div class="col-12 text-end">
          <button class="btn btn-warning">Sửa sản phẩm</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Filter sản phẩm -->
<div class="filter-section">
  <h6>Lọc sản phẩm</h6>
  <form method="GET" class="row g-3">
    <input type="hidden" name="section" value="sanpham">
    <div class="col-md-3">
      <label class="form-label">Danh mục</label>
      <select name="filter_madm" class="form-select">
        <option value="">-- Tất cả danh mục --</option>
        <?php foreach($danhmucs as $dm): ?>
          <option value="<?= $dm['MADM'] ?>" <?= $filter_madm == $dm['MADM'] ? 'selected' : '' ?>>
            <?= htmlspecialchars($dm['TENDM']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-3">
      <label class="form-label">Loại sản phẩm</label>
      <select name="filter_maloai" class="form-select">
        <option value="">-- Tất cả loại --</option>
        <?php foreach($loaisanphams as $lsp): ?>
          <option value="<?= $lsp['MALOAI'] ?>" <?= $filter_maloai == $lsp['MALOAI'] ? 'selected' : '' ?>>
            <?= htmlspecialchars($lsp['TENLOAI']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-3">
      <label class="form-label">Trạng thái</label>
      <select name="filter_flag" class="form-select">
        <option value="">-- Tất cả trạng thái --</option>
        <option value="hot" <?= $filter_flag == 'hot' ? 'selected' : '' ?>>HOT</option>
        <option value="new" <?= $filter_flag == 'new' ? 'selected' : '' ?>>NEW</option>
        <option value="sale" <?= $filter_flag == 'sale' ? 'selected' : '' ?>>SALE</option>
      </select>
    </div>
    <div class="col-md-3 d-flex align-items-end">
      <button type="submit" class="btn btn-primary me-2">Lọc</button>
      <a href="?section=sanpham" class="btn btn-secondary">Reset</a>
    </div>
  </form>
</div>

<!-- Danh sách sản phẩm -->
<div class="table-section">
  <h5>Danh sách sản phẩm (<?= count($sanphams) ?> sản phẩm)</h5>
  <div class="table-responsive">
    <table class="table table-striped">
      <thead>
        <tr>
          <th>Mã SP</th>
          <th>Group</th>
          <th>Tên SP</th>
          <th>Màu sắc</th>
          <th>Size</th>
          <th>Giá</th>
          <th>SL</th>
          <th>Hình ảnh</th>
          <th>Danh mục</th>
          <th>Loại</th>
          <th>Flags</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach($sanphams as $sp): ?>
        <tr>
          <td><?= htmlspecialchars($sp['MASP']) ?></td>
          <td><?= htmlspecialchars($sp['GROUPSP'] ?? '') ?></td>
          <td><?= htmlspecialchars($sp['TENSP']) ?></td>
          <td><?= htmlspecialchars($sp['MAUSAC'] ?? '') ?></td>
          <td><?= htmlspecialchars($sp['KICHTHUOC'] ?? '') ?></td>
          <td>
            <?php if ($sp['gia_khuyenmai'] || $sp['giam_phantram']): ?>
              <span class="price-original"><?= number_format($sp['GIA']) ?>đ</span><br>
              <span class="price-sale">
                <?php 
                  if ($sp['gia_khuyenmai']) {
                    echo number_format($sp['gia_khuyenmai']) . 'đ';
                  } else {
                    $gia_sale = $sp['GIA'] * (1 - $sp['giam_phantram']/100);
                    echo number_format($gia_sale) . 'đ (-' . $sp['giam_phantram'] . '%)';
                  }
                ?>
              </span>
            <?php else: ?>
              <?= number_format($sp['GIA']) ?>đ
            <?php endif; ?>
          </td>
          <td><?= $sp['SOLUONG'] ?></td>
          <td>
            <?php if($sp['HINHANH']): ?>
              <img src="uploads/<?= $sp['HINHANH'] ?>" alt="img" class="img-thumbnail" style="max-width:50px;">
            <?php endif; ?>
          </td>
          <td><?= htmlspecialchars($sp['TENDM'] ?? $sp['MADM']) ?></td>
          <td><?= htmlspecialchars($sp['TENLOAI'] ?? $sp['MALOAI']) ?></td>
          <td>
            <?php if($sp['hot']): ?><span class="badge bg-danger">HOT</span><?php endif; ?>
            <?php if($sp['news']): ?><span class="badge bg-success">NEW</span><?php endif; ?>
            <?php if($sp['outsale']): ?><span class="badge bg-warning">SALE</span><?php endif; ?>
            <?php if($sp['is_main']): ?><span class="badge bg-info">MAIN</span><?php endif; ?>
            <?php if($sp['TENCTKM']): ?><span class="badge bg-secondary"><?= htmlspecialchars($sp['TENCTKM']) ?></span><?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
</div>
</body>
</html>