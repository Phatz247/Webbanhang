<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <title>Thẻ thành viên</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    body {
      background: linear-gradient(120deg, #f3f3f8 70%, #f7f7fa 100%);
      min-height: 100vh;
      color: #232323;
      font-family: 'Segoe UI', 'Roboto', Arial, Helvetica, sans-serif;
    }
    .member-main-form {
      max-width: 1250px;
      margin: 25px auto 0 auto;
      background: #fff;
      border-radius: 36px;
      box-shadow: 0 12px 64px 6px #1a2a4020, 0 2.5px 18px #4c4c6e16;
      border: 1.9px solid #ededed;
      padding: 30px 24px 30px 24px;
      text-align: center;
      position: relative;
      animation: fadeIn .7s;
    }
    @keyframes fadeIn {
      from { opacity:0; transform:translateY(40px);}
      to   { opacity:1; transform:translateY(0);}
    }
    .title-main {
      font-size: 2.15rem; font-weight: 800;
      margin-bottom: 28px; color: #1e1f23;
      letter-spacing: .7px;
      text-shadow: 0 2px 16px #a6b3c6aa;
    }
    .form-desc {
      font-size: 1.13rem; color: #636478;
      margin-bottom: 36px; font-weight: 500;
    }
    .badge-grid {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 26px;
      margin-bottom: 38px;
    }
    .member-badge-card {
      background: #f8fafd;
      border-radius: 19px;
      padding: 28px 12px 21px 12px;
      box-shadow: 0 2px 12px #23232312;
      text-align: center;
      border: 1.5px solid #eceef3;
      transition: box-shadow .18s, border-color .14s, transform .15s;
      position: relative;
      min-width: 0;
    }
    .member-badge-card:hover {
      box-shadow: 0 8px 32px #0e58b41c, 0 3px 13px #12228e11;
      border-color: #3482ea66;
      z-index: 3;
      transform: translateY(-2.5px) scale(1.025);
      background: #f4f7fb;
    }
    .badge-icon {
      font-size: 2.45rem;
      border-radius: 14px;
      width: 59px; height: 59px;
      display: flex; align-items: center; justify-content: center;
      margin: 0 auto 15px auto;
      background: #fff;
      box-shadow: 0 2px 14px #e7e7e7c9, 0 1.5px 7px #87c9e522;
      border: 2.5px solid #e5e9f6;
      position: relative; z-index: 2;
    }
    .badge-kimcuong   { background: linear-gradient(135deg,#7de2fc 20%,#b9b6e5 60%,#e6c2f7 100%)!important; color: #224168; border: none;}
    .badge-vang       { background: linear-gradient(135deg,#fffbe8 8%,#ffe8a6 43%,#e0bb7c 100%)!important; color: #856200; border: none;}
    .badge-bac        { background: linear-gradient(120deg,#f7fafd 10%,#cfd9df 100%)!important; color: #676f7b; border: none;}
.badge-member     { background: linear-gradient(120deg,#f7fafd 70%,#f2f3f4 100%)!important; color: #818ba1; border: none;}
    .level-badge-label {
      font-size: 1.1rem; font-weight: 700;
      border-radius: 1.7rem; padding: 7.5px 23px;
      margin-bottom: 9px; display: inline-block;
      letter-spacing: 1.2px;
      margin-top: 3px;
      box-shadow: 0 1.5px 12px #aaaaaa12;
      text-transform: uppercase;
      border: none;
    }
    .level-condition {
      font-size: 1.01rem; color: #2c2f41; font-weight: 600;
      margin-bottom: 11px; margin-top: 2.5px;
    }
    .level-ul {
      padding-left: 15px; text-align: left;
      color: #374569; font-size: 0.98rem;
      margin-bottom: 0; font-weight: 500;
    }
    .level-ul li { margin-bottom: 8px; }
    .alert-info-glass {
      border-radius: 1.4rem;
      background: rgba(40,50,60,0.81); color: #fff;
      border: none; font-size: 1.08rem;
      margin: 0 auto 0 auto; display: inline-block;
      box-shadow: 0 1.5px 12px #37383826;
      padding: 15px 28px; margin-bottom: 0;
      letter-spacing: 0.4px;
      backdrop-filter: blur(3px);
    }
    .go-back-btn {
      margin: 37px auto 0 auto; display: block;
      font-size: 1.13rem; font-weight: 600; border-radius: 3rem;
      background: transparent;
      border: 2.2px solid;
      border-image: linear-gradient(90deg, #5a7efc 20%, #eec77c 90%) 1;
      color: #232323;
      padding: 13px 45px;
      letter-spacing: 1.1px;
      box-shadow: 0 4px 16px #5a7efc22, 0 1.5px 7px #eec77c25;
      transition: background .14s, transform .13s, color .13s, border .15s;
      text-align: center; text-decoration: none;
      font-family: 'Segoe UI', 'Roboto', Arial, Helvetica, sans-serif;
    }
    .go-back-btn:hover {
      background: linear-gradient(90deg, #ecf2fc 10%, #fcf5e9 100%);
      color: #4262aa;
      border: 2.2px solid #eec77cbb;
      transform: translateY(-2.5px) scale(1.038);
      text-decoration: none;
      box-shadow: 0 9px 22px #eec77c18, 0 1px 14px #6078ea18;
    }
    @media (max-width: 1200px) { .badge-grid { grid-template-columns: 1fr 1fr 1fr; } }
    @media (max-width: 900px)  { .badge-grid { grid-template-columns: 1fr 1fr; gap:18px;} }
    @media (max-width: 650px)  { .badge-grid { grid-template-columns: 1fr; gap:13px;} .title-main {font-size:1.08rem;} }
    @media (max-width: 490px)  { .member-main-form {padding: 12px 2vw 14px 2vw;} }
  </style>
</head>
<body>
  <div class="member-main-form">
    <div class="title-main">
      <i class="bi bi-stars"></i> HỆ THỐNG THẺ THÀNH VIÊN & ƯU ĐÃI
    </div>
    <div class="form-desc">
      Càng mua sắm càng nâng hạng – mở khóa ưu đãi <b>đặc biệt</b> dành riêng cho khách hàng thân thiết!
    </div>
    <div class="badge-grid mb-2">
      <!-- Kim cương -->
      <div class="member-badge-card">
<div class="badge-icon badge-kimcuong mb-2"><i class="bi bi-gem"></i></div>
        <div class="level-badge-label badge-kimcuong mb-2"><i class="bi bi-gem"></i> KIM CƯƠNG</div>
        <div class="level-condition mb-2">Từ 20.000.000đ tổng chi tiêu</div>
        <ul class="level-ul">
          <li><b>Giảm giá 15%</b> mọi đơn hàng</li>
          <li><b>Freeship</b> toàn quốc không giới hạn</li>
          <li>Quà sinh nhật VIP + voucher <b>1.000.000đ</b></li>
          <li>Ưu tiên hỗ trợ riêng, mời sự kiện VIP</li>
        </ul>
      </div>
      <!-- Vàng -->
      <div class="member-badge-card">
        <div class="badge-icon badge-vang mb-2"><i class="bi bi-award-fill"></i></div>
        <div class="level-badge-label badge-vang mb-2"><i class="bi bi-award-fill"></i> VÀNG</div>
        <div class="level-condition mb-2">Từ 10.000.000đ tổng chi tiêu</div>
        <ul class="level-ul">
          <li><b>Giảm giá 10%</b> mọi đơn hàng</li>
          <li>Freeship <b>10 đơn/tháng</b></li>
          <li>Quà sinh nhật đặc biệt</li>
          <li>Ưu tiên hỗ trợ khách hàng</li>
        </ul>
      </div>
      <!-- Bạc -->
      <div class="member-badge-card">
        <div class="badge-icon badge-bac mb-2"><i class="bi bi-trophy"></i></div>
        <div class="level-badge-label badge-bac mb-2"><i class="bi bi-trophy"></i> BẠC</div>
        <div class="level-condition mb-2">Từ 5.000.000đ tổng chi tiêu</div>
        <ul class="level-ul">
          <li><b>Giảm giá 5%</b> mọi đơn hàng</li>
          <li>Freeship <b>3 đơn/tháng</b></li>
          <li>Voucher sinh nhật 100.000đ</li>
        </ul>
      </div>
      <!-- Thành viên -->
      <div class="member-badge-card">
        <div class="badge-icon badge-member mb-2"><i class="bi bi-person"></i></div>
        <div class="level-badge-label badge-member mb-2"><i class="bi bi-person"></i> THÀNH VIÊN</div>
        <div class="level-condition mb-2">Dưới 5.000.000đ tổng chi tiêu</div>
        <ul class="level-ul">
          <li>Tích điểm đổi ưu đãi</li>
          <li>Tham gia các chương trình khuyến mãi thường niên</li>
        </ul>
      </div>
    </div>

    <!-- ALERT: Đưa xuống giữa, đổi màu trắng-xám -->
    <div class="text-center mt-4 mb-2">
      <div style="
        display:inline-block; 
        background: #fff; 
        color: #314780; 
        border: 1.5px solid #e0e8f0; 
        box-shadow: 0 2px 13px #1a2a4020;
        border-radius: 14px; 
        font-size:1.07rem; 
        padding: 13px 34px 13px 27px; 
        margin-bottom:0;
      ">
        <i class="bi bi-info-circle" style="color:#7ca2d7;font-size:1.3em;margin-right:7px;"></i>
        <b>Hạng thẻ được cập nhật tự động</b> dựa trên tổng tiền mua sắm.<br>
        Ưu đãi được áp dụng trực tiếp khi đặt hàng.
      </div>
    </div>

    <!-- BUTTON QUAY LẠI -->
<a href="/web_3/index.php" class="go-back-btn mt-3"><i class="bi bi-arrow-left"></i> Về trang chủ</a>
  </div>
</body>
</html>
