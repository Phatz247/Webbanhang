<?php
// model/VoucherHelper.php
class VoucherHelper {
    private $conn;
    
    public function __construct($connection) {
        $this->conn = $connection;
    }
    
    /**
     * Kiểm tra voucher có hợp lệ không
     */
    public function validateVoucher($voucherCode, $customerId, $orderTotal) {
        $stmt = $this->conn->prepare("
            SELECT * FROM voucher 
            WHERE MAVOUCHER = ? 
            AND TRANGTHAI = 'active'
            AND NOW() BETWEEN NGAYBATDAU AND NGAYHETHAN
            AND SOLUONGSUDUNG < SOLUONG
        ");
        $stmt->execute([$voucherCode]);
        $voucher = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$voucher) {
            return ['valid' => false, 'message' => 'Voucher không tồn tại hoặc đã hết hạn'];
        }
        
        // Kiểm tra điều kiện đơn hàng tối thiểu
        if ($voucher['GIATRIMIN'] > 0 && $orderTotal < $voucher['GIATRIMIN']) {
            return [
                'valid' => false, 
                'message' => 'Đơn hàng tối thiểu ' . number_format($voucher['GIATRIMIN']) . 'đ để sử dụng voucher này'
            ];
        }
        
        // Kiểm tra khách hàng đã sử dụng voucher này chưa (nếu cần)
        $stmt = $this->conn->prepare("
            SELECT COUNT(*) FROM voucher_usage 
            WHERE MAVOUCHER = ? AND MAKH = ?
        ");
        $stmt->execute([$voucherCode, $customerId]);
        $usageCount = $stmt->fetchColumn();
        
        // Giới hạn mỗi khách hàng chỉ dùng 1 lần (có thể tùy chỉnh)
        if ($usageCount > 0) {
            return ['valid' => false, 'message' => 'Bạn đã sử dụng voucher này rồi'];
        }
        
        return ['valid' => true, 'voucher' => $voucher];
    }
    
    /**
     * Tính giá trị giảm của voucher
     */
    public function calculateDiscount($voucher, $orderTotal) {
        $discount = 0;
        
        switch ($voucher['LOAIVOUCHER']) {
            case 'percent':
                $discount = ($orderTotal * $voucher['GIATRI']) / 100;
                // Áp dụng giảm tối đa nếu có
                if ($voucher['GIATRIMAX'] && $discount > $voucher['GIATRIMAX']) {
                    $discount = $voucher['GIATRIMAX'];
                }
                break;
                
            case 'fixed':
                $discount = $voucher['GIATRI'];
                // Không được giảm quá tổng đơn hàng
                if ($discount > $orderTotal) {
                    $discount = $orderTotal;
                }
                break;
                
            case 'freeship':
                // Miễn phí ship - có thể tùy chỉnh logic
                $discount = 30000; // Giả sử phí ship cố định 30k
                break;
        }
        
        return $discount;
    }
    
    /**
     * Áp dụng voucher cho đơn hàng
     */
    public function applyVoucher($voucherCode, $customerId, $orderCode, $orderTotal) {
        try {
            $this->conn->beginTransaction();
            
            // Validate voucher
            $validation = $this->validateVoucher($voucherCode, $customerId, $orderTotal);
            if (!$validation['valid']) {
                throw new Exception($validation['message']);
            }
            
            $voucher = $validation['voucher'];
            $discount = $this->calculateDiscount($voucher, $orderTotal);
            
            // Cập nhật số lượng sử dụng voucher
            $stmt = $this->conn->prepare("
                UPDATE voucher 
                SET SOLUONGSUDUNG = SOLUONGSUDUNG + 1 
                WHERE MAVOUCHER = ?
            ");
            $stmt->execute([$voucherCode]);
            
            // Lưu lịch sử sử dụng
            $stmt = $this->conn->prepare("
                INSERT INTO voucher_usage (MAVOUCHER, MAKH, MADONHANG, GIATRIGIAM) 
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$voucherCode, $customerId, $orderCode, $discount]);
            
            // Cập nhật đơn hàng
            $newTotal = $orderTotal - $discount;
            $stmt = $this->conn->prepare("
                UPDATE donhang 
                SET MAVOUCHER = ?, GIATRIGIAM = ?, TONGTIEN = ? 
                WHERE MADONHANG = ?
            ");
            $stmt->execute([$voucherCode, $discount, $newTotal, $orderCode]);
            
            $this->conn->commit();
            
            return [
                'success' => true,
                'discount' => $discount,
                'new_total' => $newTotal,
                'voucher_info' => $voucher
            ];
            
        } catch (Exception $e) {
            $this->conn->rollBack();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Lấy danh sách voucher có thể sử dụng
     */
    public function getAvailableVouchers($customerId, $orderTotal) {
        $stmt = $this->conn->prepare("
            SELECT v.*, 
                   (v.SOLUONG - v.SOLUONGSUDUNG) as remaining,
                   CASE 
                       WHEN v.GIATRIMIN > ? THEN 0 
                       ELSE 1 
                   END as can_use
            FROM voucher v
            LEFT JOIN voucher_usage vu ON v.MAVOUCHER = vu.MAVOUCHER AND vu.MAKH = ?
            WHERE v.TRANGTHAI = 'active'
            AND NOW() BETWEEN v.NGAYBATDAU AND v.NGAYHETHAN
            AND v.SOLUONGSUDUNG < v.SOLUONG
            AND vu.ID IS NULL
            ORDER BY can_use DESC, v.GIATRI DESC
        ");
        $stmt->execute([$orderTotal, $customerId]);
        $vouchers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Format dữ liệu cho checkout.php
        $formatted_vouchers = [];
        foreach ($vouchers as $voucher) {
            $info = $this->formatVoucherInfo($voucher);
            $discount = $this->calculateDiscount($voucher, $orderTotal);
            
            $formatted_vouchers[] = [
                'code' => $voucher['MAVOUCHER'],
                'name' => $voucher['TENVOUCHER'],
                'description' => $voucher['MOTA'],
                'can_use' => (bool)$voucher['can_use'],
                'condition' => $info['condition'],
                'expires' => $info['expires'],
                'remaining' => $info['remaining'],
                'formatted_discount' => $info['type'],
                'discount_value' => $discount
            ];
        }
        
        return $formatted_vouchers;
    }
    
    /**
     * Format thông tin voucher để hiển thị
     */
    public function formatVoucherInfo($voucher) {
        $info = [];
        
        switch ($voucher['LOAIVOUCHER']) {
            case 'percent':
                $info['type'] = 'Giảm ' . $voucher['GIATRI'] . '%';
                if ($voucher['GIATRIMAX']) {
                    $info['type'] .= ' (tối đa ' . number_format($voucher['GIATRIMAX']) . 'đ)';
                }
                break;
                
            case 'fixed':
                $info['type'] = 'Giảm ' . number_format($voucher['GIATRI']) . 'đ';
                break;
                
            case 'freeship':
                $info['type'] = 'Miễn phí vận chuyển';
                break;
        }
        
        $info['condition'] = $voucher['GIATRIMIN'] > 0 
            ? 'Cho đơn từ ' . number_format($voucher['GIATRIMIN']) . 'đ' 
            : 'Không điều kiện';
            
        $info['expires'] = 'HSD: ' . date('d/m/Y', strtotime($voucher['NGAYHETHAN']));
        $info['remaining'] = ($voucher['SOLUONG'] - $voucher['SOLUONGSUDUNG']) . ' lượt còn lại';
        
        return $info;
    }
    
    /**
     * Hủy voucher đã áp dụng (khi hủy đơn hàng)
     */
    public function cancelVoucher($orderCode) {
        try {
            $this->conn->beginTransaction();
            
            // Lấy thông tin voucher đã sử dụng
            $stmt = $this->conn->prepare("
                SELECT MAVOUCHER, GIATRIGIAM FROM donhang 
                WHERE MADONHANG = ? AND MAVOUCHER IS NOT NULL
            ");
            $stmt->execute([$orderCode]);
            $order = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($order && $order['MAVOUCHER']) {
                // Giảm số lượng sử dụng voucher
                $stmt = $this->conn->prepare("
                    UPDATE voucher 
                    SET SOLUONGSUDUNG = SOLUONGSUDUNG - 1 
                    WHERE MAVOUCHER = ? AND SOLUONGSUDUNG > 0
                ");
                $stmt->execute([$order['MAVOUCHER']]);
                
                // Xóa lịch sử sử dụng
                $stmt = $this->conn->prepare("
                    DELETE FROM voucher_usage 
                    WHERE MADONHANG = ?
                ");
                $stmt->execute([$orderCode]);
                
                // Cập nhật lại đơn hàng
                $stmt = $this->conn->prepare("
                    UPDATE donhang 
                    SET MAVOUCHER = NULL, GIATRIGIAM = 0, TONGTIEN = TONGTIEN + ? 
                    WHERE MADONHANG = ?
                ");
                $stmt->execute([$order['GIATRIGIAM'], $orderCode]);
            }
            
            $this->conn->commit();
            return ['success' => true];
            
        } catch (Exception $e) {
            $this->conn->rollBack();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}