<?php
// PHẦN XỬ LÝ PHP

// Kết nối DB
require_once __DIR__ . "/../model/database.php";
$db = new database();
$conn = $db->getConnection();

$alert = "";
// Phát hiện request AJAX để trả JSON thay vì redirect/reload
$isAjax = (
  (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
  || (isset($_POST['ajax']) && $_POST['ajax'] === '1')
);

// Hiển thị thông báo dựa trên tham số URL
if (isset($_GET['deleted'])) {
    $count = (int)$_GET['deleted'];
    $alert = "Đã ẩn $count sản phẩm thành công!";
} elseif (isset($_GET['restored'])) {
    $count = (int)$_GET['restored'];
    $out_of_stock = isset($_GET['out_of_stock']) ? (int)$_GET['out_of_stock'] : 0;
    
    if ($out_of_stock > 0) {
        $alert = "Đã khôi phục $count sản phẩm thành công! Tuy nhiên, $out_of_stock sản phẩm không thể khôi phục vì đã hết hàng.";
    } else {
        $alert = "Đã khôi phục $count sản phẩm thành công!";
    }
} elseif (isset($_GET['updated'])) {
    $alert = "Đã cập nhật sản phẩm thành công!";
} elseif (isset($_GET['success']) && $_GET['success'] === 'add') {
    $alert = "Đã thêm sản phẩm thành công!";
} elseif (isset($_GET['detail_added'])) {
  $num = (int)($_GET['detail_added'] ?? 0);
  $alert = $num > 0 ? ("Đã tải lên $num hình chi tiết thành công!") : "Không có hình nào được tải lên.";
} elseif (isset($_GET['detail_deleted'])) {
  $ok = (int)$_GET['detail_deleted'] === 1;
  $alert = $ok ? "Đã xóa hình chi tiết." : "Không thể xóa hình chi tiết.";
} elseif (isset($_GET['error'])) {
    switch ($_GET['error']) {
        case 'no_selection':
            $alert = "Vui lòng chọn ít nhất một sản phẩm để ẩn!";
            break;
        case 'not_found':
            $alert = "Không tìm thấy sản phẩm nào để khôi phục!";
            break;
        case 'no_code':
            $alert = "Vui lòng nhập ít nhất một mã sản phẩm để khôi phục!";
            break;
        case 'no_changes':
            $alert = "Không có thay đổi nào để cập nhật!";
            break;
        case 'all_out_of_stock':
            $count = isset($_GET['count']) ? (int)$_GET['count'] : 0;
            $alert = "Không thể khôi phục $count sản phẩm vì tất cả đều đã hết hàng! Vui lòng nhập hàng trước khi khôi phục.";
            break;
    case 'detail_no_group':
      $alert = "Vui lòng nhập GROUPSP để tải hình chi tiết.";
      break;
    case 'detail_group_not_found':
      $alert = "Không tìm thấy nhóm sản phẩm (GROUPSP) trong hệ thống.";
      break;
    }
}

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
  echo '<script>window.location.href = "../view/admin.php?section=sanpham&success=add";</script>';
        exit;
    }

    // Xóa mềm sản phẩm (Soft Delete)
    if ($action === 'delete') {
        $selected_products = $_POST['selected_products'] ?? [];
        
        // Debug: Log dữ liệu nhận được (có thể bỏ sau khi test)
        error_log("Selected products received: " . print_r($selected_products, true));
        
        // Đảm bảo $selected_products là mảng và có dữ liệu
        if (is_array($selected_products) && count($selected_products) > 0) {
            // Lọc bỏ các giá trị rỗng
            $selected_products = array_filter($selected_products);
            
            error_log("Selected products after filtering: " . print_r($selected_products, true));
            error_log("Count: " . count($selected_products));
            
            if (count($selected_products) > 0) {
                $placeholders = implode(',', array_fill(0, count($selected_products), '?'));
                $stmt = $conn->prepare("UPDATE sanpham SET IS_DELETED = 1 WHERE MASP IN ($placeholders)");
                $stmt->execute($selected_products);
                
                // Redirect sau khi xóa với số lượng chính xác
    echo '<script>window.location.href = "../view/admin.php?section=sanpham&deleted=' . count($selected_products) . '";</script>';
                exit;
            }
        }
        
        // Redirect với thông báo lỗi
  echo '<script>window.location.href = "../view/admin.php?section=sanpham&error=no_selection";</script>';
        exit;
    }

    // Khôi phục sản phẩm đã ẩn
    if ($action === 'restore') {
        $masps = $_POST['masp'] ?? '';
        $maspArr = array_filter(array_map(function($v) { return strtoupper(trim($v)); }, explode(',', $masps)));
        if (count($maspArr) > 0) {
            $placeholders = implode(',', array_fill(0, count($maspArr), '?'));
            
            // Kiểm tra sản phẩm tồn tại và đang ẩn
            $stmtCheck = $conn->prepare("SELECT MASP, SOLUONG FROM sanpham WHERE MASP IN ($placeholders) AND IS_DELETED = 1");
            $stmtCheck->execute($maspArr);
            $foundProducts = $stmtCheck->fetchAll(PDO::FETCH_ASSOC);

            if (count($foundProducts) == 0) {
                echo '<script>window.location.href = "../view/admin.php?section=sanpham&error=not_found";</script>';
                exit;
            }
            
            // Phân loại sản phẩm: có hàng vs hết hàng
            $canRestore = [];
            $outOfStock = [];
            
            foreach ($foundProducts as $product) {
                if ($product['SOLUONG'] > 0) {
                    $canRestore[] = $product['MASP'];
                } else {
                    $outOfStock[] = $product['MASP'];
                }
            }
            
            $restored_count = 0;
            $warning_message = '';
            
            // Khôi phục sản phẩm còn hàng
            if (count($canRestore) > 0) {
                $placeholders2 = implode(',', array_fill(0, count($canRestore), '?'));
                $stmt = $conn->prepare("UPDATE sanpham SET IS_DELETED = 0 WHERE MASP IN ($placeholders2)");
                $stmt->execute($canRestore);
                $restored_count = count($canRestore);
            }
            
            // Tạo thông báo cho sản phẩm hết hàng
            if (count($outOfStock) > 0) {
                if ($restored_count > 0) {
                    // Một số khôi phục được, một số hết hàng
          echo '<script>window.location.href = "../view/admin.php?section=sanpham&restored=' . $restored_count . '&out_of_stock=' . count($outOfStock) . '";</script>';
                } else {
                    // Tất cả đều hết hàng
          echo '<script>window.location.href = "../view/admin.php?section=sanpham&error=all_out_of_stock&count=' . count($outOfStock) . '";</script>';
                }
            } else {
                // Tất cả đều khôi phục thành công
        echo '<script>window.location.href = "../view/admin.php?section=sanpham&restored=' . $restored_count . '";</script>';
            }
            exit;
        } else {
      echo '<script>window.location.href = "../view/admin.php?section=sanpham&error=no_code";</script>';
            exit;
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
            echo '<script>window.location.href = "../view/admin.php?section=sanpham&updated=1";</script>';
            exit;
        } else {
            echo '<script>window.location.href = "../view/admin.php?section=sanpham&error=no_changes";</script>';
            exit;
        }
    }

  // Thêm hình chi tiết theo nhóm sản phẩm (GROUPSP)
  if ($action === 'add_detail_images') {
    $groupsp = trim($_POST['groupsp'] ?? '');
    if ($groupsp === '') {
      if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode(['ok' => false, 'error' => 'detail_no_group']);
      } else {
        echo '<script>window.location.href = "../view/admin.php?section=sanpham&error=detail_no_group";</script>';
      }
      exit;
    }

    // Kiểm tra nhóm tồn tại
    $stmt = $conn->prepare("SELECT COUNT(*) FROM sanpham WHERE GROUPSP = ?");
    $stmt->execute([$groupsp]);
    if ((int)$stmt->fetchColumn() === 0) {
      if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode(['ok' => false, 'error' => 'detail_group_not_found']);
      } else {
        echo '<script>window.location.href = "../view/admin.php?section=sanpham&error=detail_group_not_found&view_groupsp=' . urlencode($groupsp) . '";</script>';
      }
      exit;
    }

    // Lấy thứ tự hiện có
    $stmt = $conn->prepare("SELECT COALESCE(MAX(THUTU),0) FROM hinhanh_sanpham WHERE GROUPSP = ?");
    $stmt->execute([$groupsp]);
    $order = (int)$stmt->fetchColumn();

    $added = 0;
    if (!empty($_FILES['detail_images']['name']) && is_array($_FILES['detail_images']['name'])) {
      if (!file_exists('uploads')) { @mkdir('uploads', 0777, true); }
      $n = count($_FILES['detail_images']['name']);
      for ($i=0; $i<$n; $i++) {
        $name = $_FILES['detail_images']['name'][$i] ?? '';
        $tmp  = $_FILES['detail_images']['tmp_name'][$i] ?? '';
        $err  = $_FILES['detail_images']['error'][$i] ?? UPLOAD_ERR_NO_FILE;
        if ($err === UPLOAD_ERR_OK && $name && is_uploaded_file($tmp)) {
          $safe = basename($name);
          $target = 'uploads/' . $safe;
          if (file_exists($target)) {
            $pi = pathinfo($safe);
            $safe = ($pi['filename'] ?? 'img') . '_' . time() . (isset($pi['extension'])?'.'.$pi['extension']:'');
            $target = 'uploads/' . $safe;
          }
          if (move_uploaded_file($tmp, $target)) {
            $order++;
            $ins = $conn->prepare("INSERT INTO hinhanh_sanpham (GROUPSP, TENFILE, THUTU) VALUES (?,?,?)");
            $ins->execute([$groupsp, $safe, $order]);
            $added++;
          }
        }
      }
    }
    if ($isAjax) {
      // Lấy lại danh sách hình để trả về
      $stImgs = $conn->prepare("SELECT ID, TENFILE, THUTU FROM hinhanh_sanpham WHERE GROUPSP = ? ORDER BY THUTU ASC, ID ASC");
      $stImgs->execute([$groupsp]);
      $images = $stImgs->fetchAll(PDO::FETCH_ASSOC);
      header('Content-Type: application/json');
      echo json_encode(['ok' => true, 'added' => $added, 'images' => $images, 'groupsp' => $groupsp]);
    } else {
      echo '<script>window.location.href = "../view/admin.php?section=sanpham&detail_added=' . $added . '&view_groupsp=' . urlencode($groupsp) . '";</script>';
    }
    exit;
  }

  // Xóa 1 hình chi tiết theo ID
  if ($action === 'delete_detail_image') {
    $imageId = isset($_POST['image_id']) ? (int)$_POST['image_id'] : 0;
    if ($imageId > 0) {
      $st = $conn->prepare("SELECT TENFILE, GROUPSP FROM hinhanh_sanpham WHERE ID = ?");
      $st->execute([$imageId]);
      if ($row = $st->fetch(PDO::FETCH_ASSOC)) {
        $filePath = 'uploads/' . $row['TENFILE'];
        if (is_file($filePath)) { @unlink($filePath); }
        $conn->prepare("DELETE FROM hinhanh_sanpham WHERE ID = ?")->execute([$imageId]);
        if ($isAjax) {
          $groupsp = $row['GROUPSP'];
          $stImgs = $conn->prepare("SELECT ID, TENFILE, THUTU FROM hinhanh_sanpham WHERE GROUPSP = ? ORDER BY THUTU ASC, ID ASC");
          $stImgs->execute([$groupsp]);
          $images = $stImgs->fetchAll(PDO::FETCH_ASSOC);
          header('Content-Type: application/json');
          echo json_encode(['ok' => true, 'deleted' => 1, 'images' => $images, 'groupsp' => $groupsp]);
        } else {
          echo '<script>window.location.href = "../view/admin.php?section=sanpham&detail_deleted=1&view_groupsp=' . urlencode($row['GROUPSP']) . '";</script>';
        }
        exit;
      }
    }
    if ($isAjax) {
      header('Content-Type: application/json');
      echo json_encode(['ok' => false, 'deleted' => 0]);
    } else {
      echo '<script>window.location.href = "../view/admin.php?section=sanpham&detail_deleted=0";</script>';
    }
    exit;
  }
}

// Lọc sản phẩm
$filter_madm = $_GET['filter_madm'] ?? '';
$filter_maloai = $_GET['filter_maloai'] ?? '';
$filter_flag = $_GET['filter_flag'] ?? '';
$filter_deleted = $_GET['filter_deleted'] ?? '0'; // 0=Hiện, 1=Ẩn, all=Tất cả

$where_conditions = [];
$where_params = [];

// Filter theo trạng thái xóa mềm
if ($filter_deleted === '0') {
    $where_conditions[] = "sp.IS_DELETED = 0"; // Chỉ hiển thị sản phẩm chưa ẩn
} elseif ($filter_deleted === '1') {
    $where_conditions[] = "sp.IS_DELETED = 1"; // Chỉ hiển thị sản phẩm đã ẩn
}
// Nếu $filter_deleted === 'all' thì không thêm điều kiện (hiện tất cả)

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
    ctkm.TENCTKM,
  CASE WHEN ctkm.MACTKM IS NOT NULL THEN ct.gia_khuyenmai ELSE NULL END AS gia_khuyenmai,
  CASE WHEN ctkm.MACTKM IS NOT NULL THEN ct.giam_phantram ELSE NULL END AS giam_phantram,
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

// Dữ liệu hỗ trợ tab "Hình chi tiết"
$view_groupsp = $_GET['view_groupsp'] ?? '';
$detail_images_admin = [];
if ($view_groupsp !== '') {
  $stImgs = $conn->prepare("SELECT ID, TENFILE, THUTU FROM hinhanh_sanpham WHERE GROUPSP = ? ORDER BY THUTU ASC, ID ASC");
  $stImgs->execute([$view_groupsp]);
  $detail_images_admin = $stImgs->fetchAll(PDO::FETCH_ASSOC);
}

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
  let selectedProducts = Array.from(checkboxes).map(cb => cb.value);
  
  if (selectedProducts.length === 0) {
    alert('Vui lòng chọn ít nhất một sản phẩm để xóa!');
    return;
  }
  if (confirm(`Bạn có chắc muốn xóa ${selectedProducts.length} sản phẩm?`)) {
    const form = document.getElementById('delete-form');
    
    // Xóa các input cũ nếu có
    const oldInputs = form.querySelectorAll('input[name="selected_products[]"]');
    oldInputs.forEach(input => input.remove());
    
    // Tạo input riêng cho từng sản phẩm
    selectedProducts.forEach(productId => {
      const input = document.createElement('input');
      input.type = 'hidden';
      input.name = 'selected_products[]';
      input.value = productId;
      form.appendChild(input);
    });
    
    document.getElementById('delete_mode_input').value = 'checkbox';
    form.submit();
  }
}

function clearSelection() {
  const checkboxes = document.querySelectorAll('.product-checkbox');
  const selectAll = document.getElementById('select-all');
  checkboxes.forEach(checkbox => checkbox.checked = false);
  if(selectAll) selectAll.checked = false;
  updateSelectedCount();
}

// === FUNCTIONS FOR RESTORE TAB ===
function updateRestoreSelection() {
  const checkboxes = document.querySelectorAll('.restore-checkbox:checked:not(:disabled)');
  const count = checkboxes.length;
  const countSpan = document.getElementById('restore-selected-count');
  const selectionBar = document.getElementById('restore-selection-bar');
  
  if (countSpan) countSpan.textContent = count;
  
  if (count > 0) {
    selectionBar.style.display = 'block';
  } else {
    selectionBar.style.display = 'none';
  }
}

function toggleRestoreSelectAll() {
  const selectAll = document.getElementById('restore-select-all');
  const checkboxes = document.querySelectorAll('.restore-checkbox:not(:disabled)');
  
  checkboxes.forEach(checkbox => {
    checkbox.checked = selectAll.checked;
  });
  
  updateRestoreSelection();
}

function restoreSelectedProducts() {
  const checkboxes = document.querySelectorAll('.restore-checkbox:checked:not(:disabled)');
  const selectedProducts = Array.from(checkboxes).map(cb => cb.value);
  
  if (selectedProducts.length === 0) {
    alert('Vui lòng chọn ít nhất một sản phẩm để khôi phục!');
    return;
  }
  
  if (confirm(`Bạn có chắc muốn khôi phục ${selectedProducts.length} sản phẩm?`)) {
    // Đưa dữ liệu vào form ẩn và submit
    const hiddenInput = document.getElementById('restore-hidden-input');
    hiddenInput.value = selectedProducts.join(', ');
    
    // Submit form
    document.getElementById('restore-form').submit();
  }
}

function clearRestoreSelection() {
  const checkboxes = document.querySelectorAll('.restore-checkbox');
  const selectAll = document.getElementById('restore-select-all');
  
  checkboxes.forEach(checkbox => checkbox.checked = false);
  if(selectAll) selectAll.checked = false;
  
  updateRestoreSelection();
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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .restore-checkbox:disabled {
            cursor: not-allowed;
        }
        #restore-selection-bar {
            border-left: 4px solid #198754;
        }
        #delete-checkbox-bar, #restore-selection-bar {
            border-left: 4px solid #198754;
        }
        #checkbox-delete-bar {
            border-left: 4px solid #dc3545;
        }
        .table-warning {
            background-color: rgba(255, 193, 7, 0.1) !important;
        }
    </style>
</head>
<body>
  <div class="form-section mb-4" style="font-weight:bold;">
    <h4>Quản lý sản phẩm</h4>
    
    <?php if (!empty($alert)): ?>
      <?php 
        // Xác định loại thông báo dựa trên URL parameters
        $alert_type = 'alert-info'; // mặc định
        if (isset($_GET['error'])) {
          $alert_type = 'alert-danger';
        } elseif (isset($_GET['restored']) && isset($_GET['out_of_stock'])) {
          $alert_type = 'alert-warning'; // có cảnh báo về sản phẩm hết hàng
  } elseif (isset($_GET['deleted']) || isset($_GET['restored']) || isset($_GET['updated']) || isset($_GET['detail_added']) || isset($_GET['detail_deleted']) || (isset($_GET['success']) && $_GET['success'] === 'add')) {
          $alert_type = 'alert-success';
        }
      ?>
      <div class="alert <?= $alert_type ?> alert-dismissible fade show" role="alert">
        <?= htmlspecialchars($alert) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    <?php endif; ?>
    
    <div class="form-section mb-4">
      
  <ul class="nav nav-tabs mb-3" id="productTab" role="tablist">
    <li class="nav-item" role="presentation">
      <button class="nav-link active" id="add-tab" data-bs-toggle="tab" data-bs-target="#add" type="button" role="tab">Thêm</button>
    </li>
    <li class="nav-item" role="presentation">
      <button class="nav-link" id="delete-tab" data-bs-toggle="tab" data-bs-target="#delete" type="button" role="tab">Xóa</button>
    </li>
    <li class="nav-item" role="presentation">
      <button class="nav-link" id="restore-tab" data-bs-toggle="tab" data-bs-target="#restore" type="button" role="tab">Khôi phục</button>
    </li>
    <li class="nav-item" role="presentation">
      <button class="nav-link" id="edit-tab" data-bs-toggle="tab" data-bs-target="#edit" type="button" role="tab">Sửa</button>
    </li>
    <li class="nav-item" role="presentation">
      <button class="nav-link" id="detail-tab" data-bs-toggle="tab" data-bs-target="#detail" type="button" role="tab">Hình chi tiết</button>
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
      <div id="checkbox-delete-bar" class="delete-checkbox-section">
        <div class="d-flex justify-content-between align-items-center">
          <span><strong>Đã chọn: <span id="selected-count-bar">0</span> sản phẩm</strong></span>
          <div>
            <button type="button" class="btn btn-danger" onclick="deleteSelectedProducts()">Xóa sản phẩm</button>
            <button type="button" class="btn btn-secondary" onclick="clearSelection()">Bỏ chọn</button>
          </div>
        </div>
      </div>
      <form method="POST" class="row g-3" id="delete-form">
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="delete_mode" id="delete_mode_input" value="checkbox">
        <div id="checkbox-delete" class="col-md-12">
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
    <!-- Khôi phục sản phẩm -->
    <div class="tab-pane fade" id="restore" role="tabpanel">
      <div class="alert alert-info">
        <strong>Hướng dẫn:</strong> Tick chọn các sản phẩm cần khôi phục từ danh sách bên dưới và nhấn "Khôi phục đã chọn".
      </div>
      
      <!-- Thanh chọn sản phẩm -->
      <div id="restore-selection-bar" class="bg-light p-3 mb-3 rounded" style="display: none;">
        <div class="d-flex justify-content-between align-items-center">
          <span><strong>Đã chọn: <span id="restore-selected-count">0</span> sản phẩm</strong></span>
          <div>
            <button type="button" class="btn btn-success btn-sm" onclick="restoreSelectedProducts()">
              <i class="fas fa-undo"></i> Khôi phục đã chọn
            </button>
            <button type="button" class="btn btn-secondary btn-sm" onclick="clearRestoreSelection()">Bỏ chọn</button>
          </div>
        </div>
      </div>
      
      <!-- Form ẩn để submit -->
      <form method="POST" style="display: none;" id="restore-form">
        <input type="hidden" name="action" value="restore">
        <input type="hidden" name="masp" id="restore-hidden-input">
      </form>
      
      <!-- Danh sách sản phẩm đã ẩn -->
      <div class="mt-4">
        <h6>Danh sách sản phẩm đã ẩn (<?php 
        $hiddenCountStmt = $conn->query("SELECT COUNT(*) FROM sanpham WHERE IS_DELETED = 1");
        echo $hiddenCountStmt->fetchColumn();
        ?> sản phẩm)</h6>
        <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
          <table class="table table-sm table-striped">
            <thead class="sticky-top bg-white">
              <tr>
                <th style="width: 50px;">
                  <input type="checkbox" id="restore-select-all" onchange="toggleRestoreSelectAll()" title="Chọn tất cả">
                </th>
                <th>Mã SP</th>
                <th>Tên SP</th>
                <th>Màu</th>
                <th>Size</th>
                <th>Số lượng</th>
                <th>Trạng thái</th>
              </tr>
            </thead>
            <tbody>
              <?php 
              // Lấy danh sách sản phẩm đã ẩn
              $hiddenStmt = $conn->query("SELECT MASP, TENSP, MAUSAC, KICHTHUOC, SOLUONG FROM sanpham WHERE IS_DELETED = 1 ORDER BY MASP");
              $hiddenProducts = $hiddenStmt->fetchAll(PDO::FETCH_ASSOC);
              if (empty($hiddenProducts)): ?>
              <tr>
                <td colspan="7" class="text-center text-muted py-4">
                  <i class="fas fa-inbox fa-2x mb-2 d-block"></i>
                  Không có sản phẩm nào đã ẩn
                </td>
              </tr>
              <?php else:
              foreach($hiddenProducts as $hp): ?>
              <tr class="<?= $hp['SOLUONG'] <= 0 ? 'table-warning' : '' ?>">
                <td>
                  <input type="checkbox" 
                         class="restore-checkbox" 
                         value="<?= htmlspecialchars($hp['MASP']) ?>" 
                         onchange="updateRestoreSelection()"
                         <?= $hp['SOLUONG'] <= 0 ? 'disabled title="Không thể khôi phục - hết hàng"' : '' ?>>
                </td>
                <td>
                  <code class="fw-bold"><?= htmlspecialchars($hp['MASP']) ?></code>
                </td>
                <td class="fw-semibold"><?= htmlspecialchars($hp['TENSP']) ?></td>
                <td><?= htmlspecialchars($hp['MAUSAC'] ?? '-') ?></td>
                <td><?= htmlspecialchars($hp['KICHTHUOC'] ?? '-') ?></td>
                <td>
                  <span class="<?= $hp['SOLUONG'] <= 0 ? 'text-danger fw-bold' : 'text-success fw-semibold' ?>">
                    <?= $hp['SOLUONG'] ?>
                  </span>
                </td>
                <td>
                  <?php if($hp['SOLUONG'] > 0): ?>
                    <span class="badge bg-success">
                      <i class="fas fa-check"></i> Có thể khôi phục
                    </span>
                  <?php else: ?>
                    <span class="badge bg-warning">
                      <i class="fas fa-exclamation-triangle"></i> Hết hàng
                    </span>
                  <?php endif; ?>
                </td>
              </tr>
              <?php endforeach; 
              endif; ?>
            </tbody>
          </table>
        </div>
      </div>
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
    <!-- Hình chi tiết -->
    <div class="tab-pane fade" id="detail" role="tabpanel">
      <div class="card mb-3">
        <div class="card-body">
          <h6 class="card-title">Tải lên hình chi tiết theo GROUPSP</h6>
      <form method="POST" enctype="multipart/form-data" class="row g-3" id="detail-upload-form">
            <input type="hidden" name="action" value="add_detail_images">
            <div class="col-md-3">
              <label class="form-label">GROUPSP</label>
        <input name="groupsp" id="detail-groupsp-input" class="form-control" placeholder="VD: SP001" value="<?= htmlspecialchars($view_groupsp) ?>" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Chọn hình (nhiều)</label>
        <input type="file" name="detail_images[]" id="detail-images-files" class="form-control" accept="image/*" multiple required>
              <div class="form-text">Ảnh sẽ hiển thị dưới dạng thumbnail ở trang chi tiết.</div>
            </div>
            <div class="col-md-3">
              <label class="form-label invisible">&nbsp;</label>
              <button class="btn btn-success w-100"><i class="fa fa-upload"></i> Tải lên</button>
            </div>
          </form>
        </div>
      </div>
      <div class="card">
        <div class="card-body">
          <form method="GET" class="row g-2 mb-3">
            <input type="hidden" name="section" value="sanpham">
            <div class="col-auto">
              <input type="text" name="view_groupsp" class="form-control" placeholder="Nhập GROUPSP để xem" value="<?= htmlspecialchars($view_groupsp) ?>">
            </div>
            <div class="col-auto">
              <button class="btn btn-primary" type="submit">Xem ảnh</button>
            </div>
          </form>
          <div id="detail-images-container">
            <?php if ($view_groupsp !== ''): ?>
              <?php if (!empty($detail_images_admin)): ?>
                <div class="row g-3" id="detail-images-grid">
                  <?php foreach ($detail_images_admin as $di): ?>
                  <div class="col-auto text-center">
                    <img src="uploads/<?= htmlspecialchars($di['TENFILE']) ?>" style="width:100px;height:100px;object-fit:cover;border-radius:6px;border:1px solid #ddd;display:block;" alt=""/>
                    <small class="text-muted d-block">Thứ tự: <?= (int)$di['THUTU'] ?></small>
                    <form method="POST" class="mt-1 detail-delete-form" onsubmit="return confirm('Xóa ảnh này?')">
                      <input type="hidden" name="action" value="delete_detail_image">
                      <input type="hidden" name="image_id" value="<?= (int)$di['ID'] ?>">
                      <input type="hidden" name="groupsp" value="<?= htmlspecialchars($view_groupsp) ?>">
                      <button class="btn btn-sm btn-outline-danger">Xóa</button>
                    </form>
                  </div>
                  <?php endforeach; ?>
                </div>
              <?php else: ?>
                <div class="alert alert-warning mb-0">Chưa có hình cho GROUPSP "<?= htmlspecialchars($view_groupsp) ?>"</div>
              <?php endif; ?>
            <?php else: ?>
              <div class="text-muted">Nhập GROUPSP và bấm Xem ảnh để hiển thị.</div>
            <?php endif; ?>
          </div>
        </div>
      </div>
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
    <div class="col-md-2">
      <label class="form-label">Hiển thị</label>
      <select name="filter_deleted" class="form-select">
        <option value="0" <?= $filter_deleted == '0' ? 'selected' : '' ?>>Đang hiển thị</option>
        <option value="1" <?= $filter_deleted == '1' ? 'selected' : '' ?>>Đã ẩn</option>
        <option value="all" <?= $filter_deleted == 'all' ? 'selected' : '' ?>>Tất cả</option>
      </select>
    </div>
    <div class="col-md-1 d-flex align-items-end">
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
          <th>Trạng thái</th>
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
            <?php if (!empty($sp['gia_khuyenmai']) || (!empty($sp['giam_phantram']) && (int)$sp['giam_phantram'] > 0)): ?>
              <span class="price-original"><?= number_format($sp['GIA']) ?>đ</span><br>
              <span class="price-sale">
                <?php 
                  if (!empty($sp['gia_khuyenmai'])) {
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
          <td>
            <?php if($sp['IS_DELETED'] == 1): ?>
              <span class="badge bg-danger">Đã ẩn</span>
            <?php else: ?>
              <span class="badge bg-success">Hiển thị</span>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// AJAX upload hình chi tiết không reload trang
(function(){
  // Giữ vị trí cuộn khi load lại trang
  const SCROLL_KEY = 'scrollY:' + location.pathname + location.search;
  try { if ('scrollRestoration' in history) { history.scrollRestoration = 'manual'; } } catch(e){}
  const savedY = sessionStorage.getItem(SCROLL_KEY);
  if (savedY !== null) {
    const y = parseInt(savedY, 10);
    if (!Number.isNaN(y)) {
      window.scrollTo(0, y);
    }
    // Xóa sau khi khôi phục để tránh áp dụng cho lần truy cập khác
    setTimeout(()=>{ try { sessionStorage.removeItem(SCROLL_KEY); } catch(e){} }, 1500);
  }
  window.addEventListener('beforeunload', function(){
    try { sessionStorage.setItem(SCROLL_KEY, String(window.scrollY || window.pageYOffset || 0)); } catch(e){}
  });

  const uploadForm = document.getElementById('detail-upload-form');
  const groupspInput = document.getElementById('detail-groupsp-input');
  const imagesContainer = document.getElementById('detail-images-container');

  function renderImagesGrid(images) {
    if (!images || images.length === 0) {
      imagesContainer.innerHTML = '<div class="alert alert-warning mb-0">Chưa có hình cho GROUPSP "' + (groupspInput.value||'') + '"</div>';
      return;
    }
    const html = ['<div class="row g-3" id="detail-images-grid">'];
    images.forEach(img => {
      html.push(`
        <div class="col-auto text-center">
          <img src="uploads/${encodeURIComponent(img.TENFILE)}" style="width:100px;height:100px;object-fit:cover;border-radius:6px;border:1px solid #ddd;display:block;" alt=""/>
          <small class="text-muted d-block">Thứ tự: ${img.THUTU ?? 0}</small>
          <form method="POST" class="mt-1 detail-delete-form">
            <input type="hidden" name="action" value="delete_detail_image" />
            <input type="hidden" name="image_id" value="${img.ID}" />
            <input type="hidden" name="groupsp" value="${(groupspInput.value||'').replace(/"/g,'&quot;')}" />
            <button class="btn btn-sm btn-outline-danger" type="submit">Xóa</button>
          </form>
        </div>
      `);
    });
    html.push('</div>');
    imagesContainer.innerHTML = html.join('');
  }

  async function ajaxPost(formEl){
    const fd = new FormData(formEl);
    fd.append('ajax','1');
    // post to current URL to preserve section/view_groupsp context
    const url = window.location.href.replace(/#.*$/, '');
    const res = await fetch(url, { method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest' }});
    const data = await res.json().catch(()=>null);
    return data;
  }

  if (uploadForm) {
    uploadForm.addEventListener('submit', async (e)=>{
      e.preventDefault();
      if (!groupspInput.value) return;
      const btn = uploadForm.querySelector('button[type="submit"],button:not([type])');
      if (btn) { btn.disabled = true; btn.dataset._text = btn.innerHTML; btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Đang tải...'; }
      try {
        const data = await ajaxPost(uploadForm);
        if (data && data.ok) {
          renderImagesGrid(data.images || []);
        } else {
          alert('Tải lên thất bại' + (data && data.error ? (': ' + data.error) : ''));
        }
      } catch(err){
        alert('Lỗi kết nối, vui lòng thử lại');
      } finally {
        if (btn) { btn.disabled = false; btn.innerHTML = btn.dataset._text || 'Tải lên'; }
        // reset file input
        const fileInput = document.getElementById('detail-images-files');
        if (fileInput) fileInput.value = '';
      }
    });
  }

  // Delegation: Xóa hình chi tiết bằng AJAX
  if (imagesContainer) {
    imagesContainer.addEventListener('submit', async (e)=>{
      const form = e.target;
      if (!form.classList.contains('detail-delete-form')) return;
      e.preventDefault();
      if (!confirm('Xóa ảnh này?')) return;
      try {
        const data = await ajaxPost(form);
        if (data && data.ok) {
          renderImagesGrid(data.images || []);
        } else {
          alert('Xóa thất bại');
        }
      } catch(err) {
        alert('Lỗi kết nối');
      }
    });
  }
})();
</script>
</body>
</html>