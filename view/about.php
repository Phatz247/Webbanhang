<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <title>Giới thiệu về MENSTA</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    html, body {
      height: 100%; margin: 0; padding: 0;
      background: linear-gradient(120deg, #f3f3f8 80%, #f7f7fa 100%);
      overflow: hidden;
    }
    body {
      font-family: 'Segoe UI', 'Roboto', Arial, Helvetica, sans-serif;
      color: #232323;
    }
    .about-main-form {
      max-width: 950px; width: 98vw;
      height: 92vh; min-height: 540px;
      margin: 3vh auto 0 auto;
      background: #fff;
      border-radius: 32px;
      box-shadow: 0 10px 56px 3px #23232317, 0 1.5px 12px #22222209;
      border: 1.6px solid #ededed;
      padding: 34px 36px 16px 36px;
      text-align: center;
      display: flex; flex-direction: column; justify-content: space-between;
      position: relative;
      overflow: hidden;
      animation: fadeIn .9s;
    }
    @keyframes fadeIn { from { opacity:0; transform:translateY(30px);} to{ opacity:1; transform:translateY(0);} }
    .about-logo { display: flex; justify-content: center; align-items: center; margin-bottom: 8px;}
    .about-logo-icon {
      background: linear-gradient(135deg, #222 0%, #444 100%);
      color: #fff; border-radius: 50%; width: 54px; height: 54px;
      display: flex; align-items: center; justify-content: center;
      font-size: 2.12rem; margin-right: 14px;
      box-shadow: 0 3px 13px #191a1d17;
    }
    .about-title {
      font-size: 2.05rem; font-weight: 900; color: #232323; letter-spacing: 2.1px;
      text-shadow: 0 2px 18px #8884; margin-bottom: 7px;
    }
    .about-slogan {
      font-size: 1.09rem; color: #49546a;
      margin-bottom: 23px; font-weight: 500;
      letter-spacing: .2px;
    }
    .about-row {
      display: flex; flex-wrap: wrap; gap: 22px;
      justify-content: center; margin-bottom: 18px;
      align-items: stretch;
    }
    .about-block {
      flex: 1 1 235px;
      min-width: 210px; max-width: 420px;
      background: #f7f8fa;
      border-radius: 17px;
      box-shadow: 0 2px 11px #191a1d0b;
      border: 1.2px solid #ededed;
      padding: 20px 16px 16px 16px;
      text-align: left;
      margin-bottom: 6px;
      transition: box-shadow .13s, border .13s, background .13s;
      display: flex; flex-direction: column; align-items: flex-start; justify-content: flex-start;
    }
    .about-block:hover {
      background: #f2f5fa;
      box-shadow: 0 8px 18px #4c6bb110, 0 1.5px 8px #22222209;
      border: 1.2px solid #5a6bb622;
    }
    .about-block-title {
font-size: 1.07rem; font-weight: 700; margin-bottom: 7px; color: #1d2945;
      letter-spacing: 0.5px; display: flex; align-items: center; gap: 7px;
    }
    .about-block-icon {
      font-size: 1.23em; color: #222;
    }
    .about-block-content {
      color: #25304c; font-size: 1.03rem; font-weight: 500;
      line-height: 1.54; letter-spacing: 0.09px;
    }
    .about-divider {
      margin: 8px auto 15px auto; height: 2px; width: 57%; background: linear-gradient(90deg, #232323 0%, #f7f7fa 100%);
      border-radius: 9px;
    }
    .go-back-btn {
      margin: 0 auto 0 auto; display: block;
      font-size: 1.07rem; font-weight: 600; border-radius: 3rem;
      background: transparent;
      border: 2px solid;
      border-image: linear-gradient(90deg, #5a7efc 20%, #eec77c 90%) 1;
      color: #232323;
      padding: 10px 30px;
      letter-spacing: 1px;
      box-shadow: 0 4px 12px #5a7efc22, 0 1.5px 6px #eec77c25;
      transition: background .13s, transform .13s, color .13s, border .13s;
      text-align: center; text-decoration: none;
      font-family: 'Segoe UI', 'Roboto', Arial, Helvetica, sans-serif;
    }
    .go-back-btn:hover {
      background: linear-gradient(90deg, #ecf2fc 10%, #fcf5e9 100%);
      color: #4262aa;
      border: 2px solid #eec77cbb;
      transform: translateY(-2.5px) scale(1.036);
      text-decoration: none;
      box-shadow: 0 6px 18px #eec77c16, 0 1px 12px #6078ea11;
    }
    @media (max-width:820px) {
      .about-row {flex-direction: column; gap: 9px;}
      .about-main-form{height:auto;}
      .about-title{font-size:1.13rem;}
    }
  </style>
</head>
<body>
  <div class="about-main-form">
    <div>
      <div class="about-logo">
        <div class="about-logo-icon"><i class="bi bi-person-arms-up"></i></div>
        <span style="font-weight:900; font-size:1.32rem; letter-spacing:1.3px; color:#222; text-shadow: 0 1.5px 7px #2222;">MENSTA</span>
      </div>
      <div class="about-title">Thời trang nam MENSTA</div>
      <div class="about-slogan">
        MENSTA – “Chất riêng phái mạnh”<br>
        Không chỉ là shop, MENSTA là phong cách sống cho đàn ông hiện đại: Đơn giản – Lịch lãm – Cá tính.
      </div>
      <div class="about-row" style="margin-bottom: 9px;">
        <div class="about-block">
          <div class="about-block-title"><i class="bi bi-gem about-block-icon"></i> Sứ mệnh</div>
          <div class="about-block-content">
            Mang đến cho phái mạnh Việt Nam những sản phẩm thời trang chất lượng, kiểu dáng hiện đại, phù hợp mọi hoàn cảnh – từ công sở đến dạo phố.
          </div>
        </div>
        <div class="about-block">
          <div class="about-block-title"><i class="bi bi-person-check about-block-icon"></i> Giá trị cốt lõi</div>
          <div class="about-block-content">
<b>Chất lượng thật</b> – Cam kết từng đường may, chất liệu; <b>Đổi trả dễ dàng</b>, dịch vụ tận tâm.<br>
            <b>Khách hàng là trung tâm</b> – MENSTA luôn lắng nghe và thấu hiểu.
          </div>
        </div>
      </div>
      <div class="about-row">
        <div class="about-block">
          <div class="about-block-title"><i class="bi bi-shirt about-block-icon"></i> Sản phẩm chủ lực</div>
          <div class="about-block-content">
            <b>Áo nam</b> (thun, polo, khoác, sơ mi), <b>Quần nam</b> (short, kaki, tây, jean), <b>Phụ kiện</b> (nơ, cà vạt, ví, thắt lưng)...<br>
            <span style="color:#565;">Tất cả đều mang phong cách tối giản, trẻ trung, dễ mix & match nhưng vẫn giữ chất MENSTA riêng biệt.</span>
          </div>
        </div>
        <div class="about-block">
          <div class="about-block-title"><i class="bi bi-truck about-block-icon"></i> Lý do chọn MENSTA?</div>
          <div class="about-block-content">
            <b>Thiết kế độc quyền</b>, số lượng giới hạn.<br>
            Giao hàng nhanh toàn quốc, ưu đãi thành viên hấp dẫn.<br>
            MENSTA – Khi thời trang không chỉ để mặc, mà là khẳng định bản lĩnh nam giới hiện đại.
          </div>
        </div>
      </div>
    </div>
    <div>
      <div class="about-divider"></div>
      <a href="/web_3/index.php" class="go-back-btn"><i class="bi bi-arrow-left"></i> Về trang chủ</a>
    </div>
  </div>
</body>
</html>
