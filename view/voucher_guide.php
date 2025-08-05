<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hướng dẫn sử dụng Voucher</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; margin: 0; padding: 20px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; }
        .container { max-width: 800px; margin: 0 auto; }
        .card { background: white; padding: 30px; margin: 20px 0; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.2); }
        h1 { color: white; text-align: center; text-shadow: 0 2px 4px rgba(0,0,0,0.3); margin-bottom: 30px; }
        h2 { color: #2d3748; border-bottom: 3px solid #667eea; padding-bottom: 10px; }
        .step { background: #f8fafc; padding: 20px; margin: 15px 0; border-radius: 10px; border-left: 5px solid #667eea; }
        .step-number { background: #667eea; color: white; width: 30px; height: 30px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-weight: bold; margin-right: 15px; }
        .voucher-codes { background: #f0fdf4; border: 2px solid #10b981; border-radius: 10px; padding: 20px; margin: 20px 0; }
        .voucher-code { background: #10b981; color: white; padding: 8px 15px; border-radius: 5px; font-family: monospace; font-weight: bold; margin: 5px; display: inline-block; }
        .warning { background: #fef2f2; border: 2px solid #dc2626; border-radius: 10px; padding: 15px; margin: 20px 0; color: #dc2626; }
        .success { background: #f0fdf4; border: 2px solid #10b981; border-radius: 10px; padding: 15px; margin: 20px 0; color: #10b981; }
        .btn { background: #667eea; color: white; padding: 12px 24px; border: none; border-radius: 8px; text-decoration: none; display: inline-block; font-weight: 600; transition: all 0.3s; }
        .btn:hover { background: #5a67d8; transform: translateY(-2px); }
    </style>
</head>
<body>
    <div class="container">
        <h1><i class="fas fa-ticket-alt"></i> Hướng dẫn sử dụng Voucher</h1>
        
        <div class="card">
            <h2><i class="fas fa-info-circle"></i> Thông tin quan trọng</h2>
            
            <div class="warning">
                <i class="fas fa-exclamation-triangle"></i>
                <strong>Lưu ý:</strong> Để sử dụng voucher, bạn cần hoàn thành đầy đủ các bước sau đây.
            </div>
            
            <div class="voucher-codes">
                <h3><i class="fas fa-gift"></i> Mã voucher hiện có:</h3>
                <div class="voucher-code">SALE10</div> - Giảm 10% (tối đa 30k) cho đơn từ 100k
                <br>
                <div class="voucher-code">GIAM20K</div> - Giảm 20k cho đơn từ 150k
                <br>
                <div class="voucher-code">FREESHIP</div> - Miễn phí vận chuyển cho đơn từ 200k
            </div>
        </div>
        
        <div class="card">
            <h2><i class="fas fa-list-ol"></i> Các bước thực hiện</h2>
            
            <div class="step">
                <span class="step-number">1</span>
                <strong>Đăng nhập tài khoản</strong>
                <p>Truy cập trang <a href="login.php" class="btn">Đăng nhập</a> và đăng nhập vào tài khoản của bạn.</p>
            </div>
            
            <div class="step">
                <span class="step-number">2</span>
                <strong>Thêm sản phẩm vào giỏ hàng</strong>
                <p>Duyệt sản phẩm và thêm ít nhất 1 sản phẩm vào giỏ hàng với tổng giá trị tối thiểu 100,000đ để sử dụng voucher.</p>
                <a href="../index.php" class="btn">Xem sản phẩm</a>
            </div>
            
            <div class="step">
                <span class="step-number">3</span>
                <strong>Vào giỏ hàng và chọn sản phẩm</strong>
                <p>Truy cập <a href="shoppingcart.php" class="btn">Giỏ hàng</a>, chọn sản phẩm muốn mua và click "Thanh toán".</p>
            </div>
            
            <div class="step">
                <span class="step-number">4</span>
                <strong>Áp dụng voucher tại trang thanh toán</strong>
                <p>Tại trang thanh toán, phần "Mã giảm giá" sẽ hiển thị:</p>
                <ul>
                    <li>Ô nhập mã voucher</li>
                    <li>Nút "Xem voucher có thể sử dụng" (nếu có voucher khả dụng)</li>
                </ul>
            </div>
        </div>
        
        <div class="card">
            <h2><i class="fas fa-question-circle"></i> Tại sao voucher không hiển thị?</h2>
            
            <div class="warning">
                <strong>Các nguyên nhân phổ biến:</strong>
                <ul>
                    <li><i class="fas fa-times"></i> Chưa đăng nhập tài khoản</li>
                    <li><i class="fas fa-times"></i> Giỏ hàng trống hoặc chưa chọn sản phẩm</li>
                    <li><i class="fas fa-times"></i> Tổng tiền đơn hàng quá thấp (dưới 100,000đ)</li>
                    <li><i class="fas fa-times"></i> Truy cập trực tiếp vào trang checkout.php</li>
                </ul>
            </div>
            
            <div class="success">
                <strong>Điều kiện để voucher hiển thị:</strong>
                <ul>
                    <li><i class="fas fa-check"></i> Đã đăng nhập</li>
                    <li><i class="fas fa-check"></i> Có sản phẩm trong giỏ hàng</li>
                    <li><i class="fas fa-check"></i> Đã chọn sản phẩm và click "Thanh toán"</li>
                    <li><i class="fas fa-check"></i> Tổng tiền đạt điều kiện tối thiểu</li>
                </ul>
            </div>
        </div>
        
        <div class="card">
            <h2><i class="fas fa-tools"></i> Test Voucher</h2>
            <p>Để test voucher ngay lập tức mà không cần thực hiện đầy đủ quy trình:</p>
            <a href="checkout_test_full.php" class="btn">
                <i class="fas fa-flask"></i> Test Voucher Display
            </a>
        </div>
    </div>
</body>
</html>
