<?php
// view/upload/footer.php - Hoàn chỉnh
?>
<footer class="footer-full-black">
  <div class="footer-container">
    <div class="footer-col">
      <img src="/web_3/view/img/logo1.jpg" alt="MENSTA" class="logo-footer" 
           onerror="this.style.display='none'; this.nextElementSibling.style.display='block';" />
      <div style="display:none; color: #fff; font-weight: bold; margin-bottom: 15px;">MENSTA</div>
      <ul>
        <li><a href="#">Giới thiệu</a></li>
        <li><a href="#">Liên hệ</a></li>
        <li><a href="#">Tuyển dụng</a></li>
        <li><a href="#">Tin tức</a></li>
        <li><i class="fa fa-envelope"></i> Email: info@mensta.com</li>
        <li><i class="fa fa-phone"></i> Hotline: 0999.999.999</li>
      </ul>
    </div>
    
    <div class="footer-col">
      <div class="footer-title">HỖ TRỢ KHÁCH HÀNG</div>
      <ul>
        <li><a href="#">Hướng dẫn đặt hàng</a></li>
        <li><a href="#">Hướng dẫn chọn size</a></li>
        <li><a href="#">Câu hỏi thường gặp</a></li>
        <li><a href="#">Chính sách khách VIP</a></li>
        <li><a href="#">Thanh toán - Giao hàng</a></li>
        <li><a href="#">Chính sách bảo mật</a></li>
        <li><a href="#">Chính sách cookie</a></li>
      </ul>
    </div>
    
    <div class="footer-col">
      <div class="footer-title">VỊ TRÍ CỬA HÀNG</div>
      <img src="/web_3/view/img/map.jpg" alt="Bản đồ cửa hàng"
           onerror="this.style.display='none'; this.nextElementSibling.style.display='block';"
           style="width: 100%; max-width: 170px; border-radius: 6px; margin-bottom: 8px;" />
      <div style="display:none; color: #ccc; font-size: 14px;">
        123 Đường ABC, Quận 1<br>
        TP. Hồ Chí Minh<br>
        Điện thoại: 0999.999.999
      </div>
    </div>
    
    <div class="footer-col">
      <div class="footer-title">KẾT NỐI VỚI MENSTA</div>
      <div class="footer-social">
        <a href="#" title="Facebook"><i class="fab fa-facebook-f"></i></a>
        <a href="#" title="Instagram"><i class="fab fa-instagram"></i></a>
        <a href="#" title="YouTube"><i class="fab fa-youtube"></i></a>
        <a href="#" title="TikTok"><i class="fab fa-tiktok"></i></a>
      </div>
      <div style="color: #ccc; font-size: 12px; margin-top: 10px;">
        Theo dõi chúng tôi để cập nhật những xu hướng thời trang mới nhất!
      </div>
    </div>
  </div>
  
  <div class="footer-bottom">
    <p>&copy; 2024 MENSTA. Tất cả quyền được bảo lưu. | Thiết kế bởi MENSTA Team</p>
  </div>
</footer>

<style>
/* CSS bổ sung để đảm bảo footer hiển thị đúng */
.footer-full-black {
  background: #333 !important;
  color: #fff !important;
  padding: 40px 20px 20px 20px !important;
  margin-top: 50px !important;
  width: 100vw !important;   /* Thay vì 100% */
  position: relative;
  left: 50%;
  right: 50%;
  margin-left: -50vw;
  margin-right: -50vw;
  clear: both !important;
}
.footer-container {
  max-width: 1200px !important;
  margin: 0 auto !important;
  display: flex !important;
  flex-wrap: wrap !important;
  justify-content: space-between !important;
}

.footer-col {
  flex: 1 1 250px !important;
  margin: 0 15px 30px 15px !important;
  min-width: 200px !important;
}

.footer-col .logo-footer {
  height: 40px !important;
  margin-bottom: 15px !important;
  object-fit: contain !important;
  max-width: 150px !important;
}

.footer-title {
  font-weight: bold !important;
  font-size: 16px !important;
  margin-bottom: 15px !important;
  color: #fff !important;
  text-transform: uppercase !important;
}

.footer-col ul {
  list-style: none !important;
  padding: 0 !important;
  margin: 0 !important;
}

.footer-col ul li {
  margin-bottom: 8px !important;
  font-size: 14px !important;
  color: #ccc !important;
}

.footer-col ul li a {
  color: #ccc !important;
  text-decoration: none !important;
  transition: color 0.3s ease !important;
}

.footer-col ul li a:hover {
  color: #fff !important;
}

.footer-col ul li i {
  margin-right: 8px !important;
  width: 16px !important;
}

.footer-social {
  display: flex !important;
  gap: 15px !important;
  margin-top: 15px !important;
}

.footer-social a {
  color: #fff !important;
  font-size: 20px !important;
  transition: color 0.3s ease, transform 0.3s ease !important;
  display: inline-block !important;
}

.footer-social a:hover {
  color: #007bff !important;
  transform: translateY(-2px) !important;
}

.footer-bottom {
  border-top: 1px solid #555 !important;
  margin-top: 30px !important;
  padding-top: 20px !important;
  text-align: center !important;
  font-size: 13px !important;
  color: #aaa !important;
}

.footer-bottom p {
  margin: 0 !important;
}

/* Responsive */
@media (max-width: 768px) {
  .footer-container {
    flex-direction: column !important;
  }
  
  .footer-col {
    margin: 0 0 30px 0 !important;
  }
  
  .footer-social {
    justify-content: center !important;
  }
}
</style>
