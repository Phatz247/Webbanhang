<?php
// model/review.php
require_once __DIR__ . '/database.php';

class ReviewModel {
    public static function getEligibleOrdersForReview(PDO $conn, string $makh, string $masp): array {
        // Eligible if user has invoice containing product and delivery marked delivered
        $sql = "
            SELECT DISTINCT hd.MAHD, gh.MADONHANG, COALESCE(gh.TRANGTHAIGH, '') AS TRANGTHAIGH
            FROM chitiethoadon cthd
            JOIN hoadon hd ON hd.MAHD = cthd.MAHD AND hd.MAKH = :makh
            LEFT JOIN giaohang gh ON gh.MAHD = hd.MAHD
            WHERE cthd.MASP = :masp
              AND (gh.TRANGTHAIGH IN ('Đã giao','Đã nhận','Hoàn thành'))
        ";
        $st = $conn->prepare($sql);
        $st->execute([':makh' => $makh, ':masp' => $masp]);
        return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public static function hasReviewed(PDO $conn, string $makh, string $masp, string $mahd): bool {
        $st = $conn->prepare("SELECT 1 FROM danhgia WHERE MAKH = ? AND MASP = ? AND MAHD = ? LIMIT 1");
        $st->execute([$makh, $masp, $mahd]);
        return (bool)$st->fetchColumn();
    }

    public static function addReview(PDO $conn, array $data): bool {
        $sql = "INSERT INTO danhgia (MADG, MASP, MAKH, MAHD, SOSAODANHGIA, NOIDUNG, THOIGIAN)
                VALUES (:MADG, :MASP, :MAKH, :MAHD, :SOSAODANHGIA, :NOIDUNG, NOW())";
        $st = $conn->prepare($sql);
        return $st->execute([
            ':MADG' => $data['MADG'],
            ':MASP' => $data['MASP'],
            ':MAKH' => $data['MAKH'],
            ':MAHD' => $data['MAHD'],
            ':SOSAODANHGIA' => $data['SOSAODANHGIA'],
            ':NOIDUNG' => $data['NOIDUNG'],
        ]);
    }

    public static function getReviewsByProduct(PDO $conn, string $masp): array {
        $sql = "
            SELECT dg.MADG, dg.SOSAODANHGIA, dg.NOIDUNG, dg.THOIGIAN, kh.TENKH
            FROM danhgia dg
            LEFT JOIN khachhang kh ON kh.MAKH = dg.MAKH
            WHERE dg.MASP = :masp
            ORDER BY dg.THOIGIAN DESC
        ";
        $st = $conn->prepare($sql);
        $st->execute([':masp' => $masp]);
        return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public static function getAverageByProduct(PDO $conn, string $masp): array {
        $st = $conn->prepare("SELECT AVG(SOSAODANHGIA) as avg_rating, COUNT(*) as total FROM danhgia WHERE MASP = ?");
        $st->execute([$masp]);
        $row = $st->fetch(PDO::FETCH_ASSOC) ?: ['avg_rating' => null, 'total' => 0];
        return [
            'avg' => $row['avg_rating'] ? round((float)$row['avg_rating'], 1) : 0,
            'total' => (int)$row['total'],
        ];
    }
}
?>
