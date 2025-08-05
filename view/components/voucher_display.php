<?php
// view/components/voucher_display.php
// Component để hiển thị voucher có thể sử dụng trên trang sản phẩm

function displayAvailableVouchers($conn, $customerId = null) {
    // Lấy voucher đang hoạt động
    $stmt = $conn->prepare("
        SELECT MAVOUCHER, TENVOUCHER, LOAIVOUCHER, GIATRI, GIATRIMIN, GIATRIMAX, 
               NGAYHETHAN, (SOLUONG - SOLUONGSUDUNG) as remaining
        FROM voucher 
        WHERE TRANGTHAI = 'active' 
        AND NOW() BETWEEN NGAYBATDAU AND NGAYHETHAN
        AND SOLUONGSUDUNG < SOLUONG
        ORDER BY GIATRI DESC
        LIMIT 3
    ");
    $stmt->execute();
    $vouchers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($vouchers)) return '';
    
    $html = '
    <div class="voucher-promotion-section" style="margin: 20px 0; padding: 15px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 10px; color: white;">
        <h4 style="margin-bottom: 15px; color: white;"><i class="fas fa-gift"></i> Voucher khuyến mãi</h4>
        <div class="voucher-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 10px;">
    ';
    
    foreach ($vouchers as $voucher) {
        $voucherType = '';
        $voucherValue = '';
        
        switch ($voucher['LOAIVOUCHER']) {
            case 'percent':
                $voucherType = 'Giảm ' . $voucher['GIATRI'] . '%';
                if ($voucher['GIATRIMAX']) {
                    $voucherType .= ' (tối đa ' . number_format($voucher['GIATRIMAX']) . 'đ)';
                }
                break;
            case 'fixed':
                $voucherType = 'Giảm ' . number_format($voucher['GIATRI']) . 'đ';
                break;
            case 'freeship':
                $voucherType = 'Miễn phí ship';
                break;
        }
        
        $condition = $voucher['GIATRIMIN'] > 0 ? 'Đơn từ ' . number_format($voucher['GIATRIMIN']) . 'đ' : 'Không điều kiện';
        $expires = date('d/m/Y', strtotime($voucher['NGAYHETHAN']));
        
        $html .= '
        <div class="voucher-card" style="background: rgba(255,255,255,0.15); padding: 12px; border-radius: 8px; backdrop-filter: blur(10px); border: 1px solid rgba(255,255,255,0.2); cursor: pointer;" 
             onclick="copyVoucherCode(\'' . htmlspecialchars($voucher['MAVOUCHER']) . '\')" 
             title="Click để copy mã">
            <div class="voucher-code" style="font-weight: bold; font-size: 16px; margin-bottom: 5px; color: #FFE082;">
                ' . htmlspecialchars($voucher['MAVOUCHER']) . '
            </div>
            <div class="voucher-name" style="font-size: 14px; margin-bottom: 8px; opacity: 0.9;">
                ' . htmlspecialchars($voucher['TENVOUCHER']) . '
            </div>
            <div class="voucher-details" style="font-size: 12px; opacity: 0.8;">
                <div>' . $voucherType . '</div>
                <div>' . $condition . '</div>
                <div>HSD: ' . $expires . '</div>
                <div style="color: #FFE082;">Còn ' . $voucher['remaining'] . ' lượt</div>
            </div>
        </div>';
    }
    
    $html .= '
        </div>
        <div style="text-align: center; margin-top: 15px; font-size: 12px; opacity: 0.8;">
            <i class="fas fa-info-circle"></i> Click vào voucher để copy mã
        </div>
    </div>
    
    <script>
    function copyVoucherCode(code) {
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(code).then(function() {
                showVoucherToast("Đã copy mã voucher: " + code);
            }).catch(function() {
                fallbackCopyTextToClipboard(code);
            });
        } else {
            fallbackCopyTextToClipboard(code);
        }
    }
    
    function fallbackCopyTextToClipboard(text) {
        var textArea = document.createElement("textarea");
        textArea.value = text;
        textArea.style.top = "0";
        textArea.style.left = "0";
        textArea.style.position = "fixed";
        document.body.appendChild(textArea);
        textArea.focus();
        textArea.select();
        
        try {
            var successful = document.execCommand("copy");
            if (successful) {
                showVoucherToast("Đã copy mã voucher: " + text);
            } else {
                showVoucherToast("Không thể copy. Mã voucher: " + text);
            }
        } catch (err) {
            showVoucherToast("Không thể copy. Mã voucher: " + text);
        }
        
        document.body.removeChild(textArea);
    }
    
    function showVoucherToast(message) {
        // Tạo toast notification
        var toast = document.createElement("div");
        toast.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: #28a745;
            color: white;
            padding: 12px 20px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            z-index: 10000;
            font-size: 14px;
            font-weight: 500;
            opacity: 0;
            transition: opacity 0.3s ease;
        `;
        toast.textContent = message;
        
        document.body.appendChild(toast);
        
        // Hiển thị toast
        setTimeout(() => {
            toast.style.opacity = "1";
        }, 100);
        
        // Ẩn toast sau 3 giây
        setTimeout(() => {
            toast.style.opacity = "0";
            setTimeout(() => {
                document.body.removeChild(toast);
            }, 300);
        }, 3000);
    }
    </script>
    ';
    
    return $html;
}

// Sử dụng trong trang sản phẩm:
// echo displayAvailableVouchers($conn, $_SESSION['user']['MAKH'] ?? null);
?>
