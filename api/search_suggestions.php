<?php
// api/search_suggestions.php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Đọc dữ liệu JSON từ request
$input = json_decode(file_get_contents('php://input'), true);
$query = trim($input['query'] ?? '');

if (strlen($query) < 2) {
    echo json_encode(['suggestions' => []]);
    exit;
}

try {
    // Kết nối database
    require_once __DIR__ . '/../model/database.php';
    $db = new Database();
    $conn = $db->getConnection();
    
    // Tìm kiếm sản phẩm với LIKE pattern
    $searchPattern = '%' . $query . '%';
    
    $sql = "
        SELECT DISTINCT
            sp.TENSP as name,
            lsp.TENLOAI as category,
            sp.GIA as price,
            sp.MASP as id
        FROM sanpham sp
        LEFT JOIN loaisanpham lsp ON sp.MALOAI = lsp.MALOAI
        WHERE sp.IS_DELETED = 0 
        AND (
            sp.TENSP LIKE :pattern1
            OR lsp.TENLOAI LIKE :pattern2
        )
        ORDER BY 
            CASE WHEN sp.TENSP LIKE :exact_pattern THEN 1 ELSE 2 END,
            sp.TENSP
        LIMIT 8
    ";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute([
        ':pattern1' => $searchPattern,
        ':pattern2' => $searchPattern,
        ':exact_pattern' => $query . '%'
    ]);
    
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format kết quả
    $suggestions = array_map(function($row) {
        return [
            'name' => $row['name'],
            'category' => $row['category'] ?? '',
            'price' => number_format($row['price']) . '₫',
            'id' => $row['id']
        ];
    }, $results);
    
    echo json_encode([
        'suggestions' => $suggestions,
        'total' => count($suggestions)
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Database error',
        'message' => $e->getMessage()
    ]);
}
?>
<?php
// api/search_suggestions.php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Đọc dữ liệu JSON từ request
$input = json_decode(file_get_contents('php://input'), true);
$query = trim($input['query'] ?? '');

if (strlen($query) < 2) {
    echo json_encode(['suggestions' => []]);
    exit;
}

try {
    // Kết nối database
    require_once '../model/database.php';
    $db = new Database();
    $conn = $db->getConnection();
    
    // Tìm kiếm sản phẩm với LIKE pattern
    $searchPattern = '%' . $query . '%';
    
    $sql = "
        SELECT DISTINCT
            sp.TENSP as name,
            lsp.TENLOAI as category,
            sp.GIA as price,
            sp.MASP as id
        FROM sanpham sp
        LEFT JOIN loaisanpham lsp ON sp.MALOAI = lsp.MALOAI
        WHERE sp.IS_DELETED = 0 
        AND (
            sp.TENSP LIKE :pattern1
            OR lsp.TENLOAI LIKE :pattern2
        )
        ORDER BY 
            CASE WHEN sp.TENSP LIKE :exact_pattern THEN 1 ELSE 2 END,
            sp.TENSP
        LIMIT 8
    ";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute([
        ':pattern1' => $searchPattern,
        ':pattern2' => $searchPattern,
        ':exact_pattern' => $query . '%'
    ]);
    
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format kết quả
    $suggestions = array_map(function($row) {
        return [
            'name' => $row['name'],
            'category' => $row['category'] ?? '',
            'price' => number_format($row['price']) . '₫',
            'id' => $row['id']
        ];
    }, $results);
    
    echo json_encode([
        'suggestions' => $suggestions,
        'total' => count($suggestions)
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Database error',
        'message' => $e->getMessage()
    ]);
}
?>
