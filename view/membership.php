<!DOCTYPE html>
<html lang="vi">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Thẻ Thành Viên MENSTA</title>
    <link
      rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"
    />
    <link rel="stylesheet" href="view/css/style.css" />
    <style>
      * {
        box-sizing: border-box;
      }

      body {
        font-family: "Segoe UI", sans-serif;
        background-color: #f7f7f7;
        margin: 0;
        padding: 0;
        color: #333;
      }

      header {
        background-color: #000;
        color: #fff;
        padding: 20px;
        text-align: center;
      }

      .container {
        max-width: 1200px;
        margin: 40px auto;
        padding: 0 20px;
      }

      .intro {
        text-align: center;
        margin-bottom: 40px;
      }

      .intro h2 {
        font-size: 32px;
        margin-bottom: 10px;
      }

      .intro p {
        font-size: 18px;
        color: #555;
      }

      .card-container {
        display: flex;
        flex-wrap: wrap;
        gap: 30px;
        justify-content: center;
      }

      .member-card {
        background: #fff;
        border-radius: 12px;
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.08);
        width: 300px;
        padding: 30px 20px;
        text-align: center;
        transition: transform 0.3s, box-shadow 0.3s;
        position: relative;
      }

      .member-card:hover {
        transform: translateY(-10px);
        box-shadow: 0 12px 30px rgba(0, 0, 0, 0.15);
      }

      .card-title {
        font-size: 24px;
        font-weight: bold;
        margin-bottom: 10px;
        text-transform: uppercase;
      }

      .silver {
        border-top: 6px solid #c0c0c0;
      }

      .gold {
        border-top: 6px solid #d4af37;
      }

      .diamond {
        border-top: 6px solid #3093ccff;
      }

      .price {
        font-size: 20px;
        margin: 10px 0;
        color: #111;
      }

      .benefits {
        list-style: none;
        padding: 0;
        margin: 20px 0;
        text-align: left;
      }

      .benefits li {
        margin-bottom: 10px;
      }

      .benefits i {
        color: #28a745;
        margin-right: 8px;
      }

      .buy-btn {
        background: #000;
        color: #fff;
        padding: 10px 18px;
        border: none;
        border-radius: 6px;
        font-size: 16px;
        cursor: pointer;
        transition: background 0.3s;
        margin-top: 15px;
      }

      .buy-btn:hover {
        background: #333;
      }

      .conditions {
        background-color: #fff;
        padding: 30px;
        margin-top: 60px;
        border-radius: 12px;
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.05);
      }

      .conditions h3 {
        margin-bottom: 20px;
        font-size: 22px;
        text-align: center;
      }

      .conditions ul {
        list-style: disc;
        padding-left: 40px;
        color: #555;
      }
    </style>
  </head>
  <body>
    <header>
      <h1>Ưu Đãi Thẻ Thành Viên MENSTA</h1>
    </header>

    <div class="container">
      <div class="intro">
        <h2>Chọn cấp độ thành viên phù hợp với bạn</h2>
        <p>Càng mua nhiều – Ưu đãi càng lớn!</p>
      </div>

      <div class="card-container">
        <!-- Silver -->
        <div class="member-card silver">
          <div class="card-title">Thẻ Bạc</div>
          <div class="price">Chỉ từ 500.000₫</div>
          <ul class="benefits">
            <li><i class="fas fa-check-circle"></i> Tích điểm đổi quà</li>
            <li>
              <i class="fas fa-check-circle"></i> Giảm 5% đơn hàng tiếp theo
            </li>
            <li><i class="fas fa-check-circle"></i> Ưu đãi sinh nhật 10%</li>
          </ul>
          <button class="buy-btn" onclick="buy('Thẻ Bạc')">Mua ngay</button>
        </div>

        <!-- Gold -->
        <div class="member-card gold">
          <div class="card-title">Thẻ Vàng</div>
          <div class="price">Từ 2.000.000₫</div>
          <ul class="benefits">
            <li>
              <i class="fas fa-check-circle"></i> Giảm 10% toàn bộ sản phẩm
            </li>
            <li><i class="fas fa-check-circle"></i> Quà sinh nhật đặc biệt</li>
            <li><i class="fas fa-check-circle"></i> Ưu đãi trước khuyến mãi</li>
          </ul>
          <button class="buy-btn" onclick="buy('Thẻ Vàng')">Mua ngay</button>
        </div>

        <!-- Diamond -->
        <div class="member-card diamond">
          <div class="card-title">Thẻ Kim Cương</div>
          <div class="price">Từ 5.000.000₫</div>
          <ul class="benefits">
            <li><i class="fas fa-check-circle"></i> Giảm 20% vĩnh viễn</li>
            <li><i class="fas fa-check-circle"></i> Tặng quà mỗi quý</li>
            <li><i class="fas fa-check-circle"></i> Mua trước BST mới</li>
          </ul>
          <button class="buy-btn" onclick="buy('Thẻ Kim Cương')">
            Mua ngay
          </button>
        </div>
      </div>

      <div class="conditions">
        <h3>Điều kiện & Chính sách</h3>
        <ul>
          <li>Thẻ có hiệu lực trong vòng 1 năm kể từ ngày cấp.</li>
          <li>Tổng chi tiêu dựa trên các đơn hàng đã thanh toán thành công.</li>
          <li>Không áp dụng đồng thời với các chương trình khuyến mãi khác.</li>
          <li>Chỉ 1 thẻ cho mỗi khách hàng (theo số điện thoại).</li>
        </ul>
      </div>
    </div>


    <script>
      function buy(tier) {
        alert(
          `Bạn đã đăng ký thành công ${tier}! Xin cảm ơn bạn đã ủng hộ MENSTA.`
        );
      }
    </script>
  </body>
</html>
