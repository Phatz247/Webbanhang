<?php
session_start();
if (empty($_GET['order_id']) || empty($_GET['product'])) {
    echo "Thiếu thông tin đánh giá!"; exit;
}
$order_id = $_GET['order_id'];
$product_name = $_GET['product'];
$success = false;

// Lấy thông tin sản phẩm trong order
$image = '';
$qty = '';
$size = '';
$total = '';
if (isset($_SESSION['current_order']['products'])) {
    foreach ($_SESSION['current_order']['products'] as $item) {
        if ($item['name'] === $product_name) {
            $image = $item['image'];
            $qty = $item['quantity'];
            $size = $item['size'];
            $total = number_format($item['price'] * $item['quantity'], 0, ',', '.');
            break;
        }
    }
}

// Xử lý lưu đánh giá
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["review_rating"])) {
    $review = [
        "order_id"      => $order_id,
        "product_name"  => $_POST["product_name"],
        "customer_name" => $_POST["customer_name"],
        "rating"        => $_POST["review_rating"],
        "comment"       => $_POST["review_comment"],
        "review_time"   => date("Y-m-d H:i:s")
    ];
    file_put_contents("reviews.txt", json_encode($review) . PHP_EOL, FILE_APPEND);
    $success = true;
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Đánh giá sản phẩm</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f7f7f7;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            margin: 0;
        }
        .review-form-box {
            background: #fff;
            max-width: 500px;
            width: 100%;
            margin: 30px auto;
            border-radius: 10px;
            box-shadow: 0 3px 20px #eaeaea;
            padding: 32px 22px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        h2 {
            text-align: center;
            color: #219150;
            margin-bottom: 16px;
            width: 100%;
        }
        .review-product {
            display: flex;
            align-items: flex-start;
            gap: 14px;
            margin-bottom: 16px;
            width: 100%;
            justify-content: center;
        }
        .review-product-img img {
            width: 70px;
            height: 70px;
            object-fit: cover;
            border-radius: 8px;
            border:1.5px solid #eee;
            box-shadow:0 2px 10px #eee;
            background:#fff;
        }
        .review-product-info {
            flex: 1;
            font-size: 15px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        .review-product-info b {
            font-size: 17px;
        }
        .review-product-info .detail { color: #444; font-size: 14px;}
        .review-product-info .total {
            font-weight: bold; font-size: 16px; color: #222; margin-top: 1px;
            display: inline-block;
        }
        .review-stars {
            display: flex;
            justify-content: center;
            font-size: 30px;
            margin: 12px 0 18px 0;
            width: 100%;
        }
        .review-stars input { display: none; }
        .review-stars label {
            cursor: pointer;
            color: #ccc;
            margin: 0 2px;
            transition: .2s;
        }
        .review-stars label.selected,
        .review-stars label:hover,
        .review-stars label:hover ~ label { color: #ffc107 !important;}
        .review-stars label.selected ~ label { color: #ccc !important;}
        form {
            width: 100%;
        }
        textarea {
            width: 100%;
            border-radius: 6px;
            border: 1.5px solid #222;
            padding: 9px 10px;
            font-size: 16px;
            margin-bottom: 15px;
            resize: vertical;
            box-sizing: border-box;
            display: block;
        }
        button {
            background: #27ae60;
            color: #fff;
            border: none;
            padding: 10px 24px;
            border-radius: 7px;
            font-size: 17px;
            cursor: pointer;
            width: 100%;
        }
        .review-success {
            color: #27ae60;
            font-size: 17px;
            text-align: center;
            margin-top: 15px;
        }
        .back-link {
            text-align: center;
            margin-top: 24px;
        }
        .back-link a {
            color: #219150;
            text-decoration: none;
        }
        .back-link a:hover {
            text-decoration: underline;
        }
        @media (max-width: 600px) {
            .review-form-box { padding: 14px 4px;}
            .review-product { flex-direction: column; align-items: center;}
            .review-product-img img { margin-bottom: 6px;}
        }
    </style>
</head>
<body>
<div class="review-form-box">
    <h2>Đánh giá sản phẩm</h2>
    <div class="review-product">
        <div class="review-product-img">
            <img src="<?php echo htmlspecialchars($image); ?>" alt="Ảnh sản phẩm">
        </div>
        <div class="review-product-info">
            <b><?php echo htmlspecialchars($product_name); ?></b>
            <span class="detail">Số lượng: <?php echo $qty ? $qty : '-'; ?> | Size: <?php echo htmlspecialchars($size ? $size : '-'); ?></span>
            <span class="total">Thành tiền: <?php echo $total ? $total : '-'; ?> đ</span>
        </div>
    </div>
    <?php if ($success): ?>
        <div class="review-success">Cảm ơn bạn đã đánh giá sản phẩm!</div>
        <div class="back-link"><a href="order_status.php">Quay lại đơn hàng</a></div>
    <?php else: ?>
    <form method="POST">
        <input type="hidden" name="product_name" value="<?php echo htmlspecialchars($product_name); ?>">
        <input type="hidden" name="customer_name" value="<?php echo isset($_SESSION['current_order']['customer_name']) ? htmlspecialchars($_SESSION['current_order']['customer_name']) : ''; ?>">
        <div class="review-stars" id="star-rating">
            <?php for ($i=5; $i>=1; $i--): ?>
                <input type="radio" id="star<?php echo $i; ?>" name="review_rating" value="<?php echo $i; ?>" <?php echo ($i==5 ? 'checked' : ''); ?>>
                <label for="star<?php echo $i; ?>" data-value="<?php echo $i; ?>">&#9733;</label>
            <?php endfor; ?>
        </div>
        <textarea name="review_comment" placeholder="Nhận xét của bạn về sản phẩm..." required></textarea>
        <button type="submit">Gửi đánh giá</button>
    </form>
    <script>
    // Sao vàng cho review
    const stars = document.querySelectorAll('#star-rating label');
    const radios = document.querySelectorAll('#star-rating input');
    stars.forEach(label => {
      label.addEventListener('mouseover', function() {
        let val = this.getAttribute('data-value');
        stars.forEach(lab => {
          lab.style.color = (lab.getAttribute('data-value') <= val) ? '#ffc107' : '#ccc';
        });
      });
      label.addEventListener('mouseout', function() {
        let checked = document.querySelector('#star-rating input:checked');
        let val = checked ? checked.value : 5;
        stars.forEach(lab => {
          lab.style.color = (lab.getAttribute('data-value') <= val) ? '#ffc107' : '#ccc';
        });
      });
      label.addEventListener('click', function() {
        let val = this.getAttribute('data-value');
        radios.forEach(r => {
          r.checked = (r.value == val);
        });
        stars.forEach(lab => {
          lab.style.color = (lab.getAttribute('data-value') <= val) ? '#ffc107' : '#ccc';
        });
      });
    });
    window.addEventListener('DOMContentLoaded', function(){
      let checked = document.querySelector('#star-rating input:checked');
      let val = checked ? checked.value : 5;
      stars.forEach(lab => {
        lab.style.color = (lab.getAttribute('data-value') <= val) ? '#ffc107' : '#ccc';
      });
    });
    </script>
    <?php endif; ?>
</div>
</body>
</html>
