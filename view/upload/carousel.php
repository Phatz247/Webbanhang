<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="view/css/carousel.css">
    <title>Carousel</title>
</head>
<body>
     <main class="main-content">
      <section class="banner-carousel full-width-banner">
        <div class="carousel-track">
          <img
            src="view/img/banner.jpg"
            alt="Banner khuyến mãi"
            class="carousel-img full-width-img active"
          />
          />
          <img
            src="view/img/banner1.jpg"
            alt="Banner thời trang mới"
            class="carousel-img full-width-img"
          
          />
          />
          <img
            src="view/img/banner2.jpg"
            alt="Banner sale off"
            class="carousel-img full-width-img"
          />
          />
        </div>
        <button class="carousel-btn prev" aria-label="Ảnh trước">
          <i class="fas fa-chevron-left"></i>
        </button>
        <button class="carousel-btn next" aria-label="Ảnh sau">
          <i class="fas fa-chevron-right"></i>
        </button>
        <div class="carousel-dots">
          <span class="dot active" data-slide="0"></span>
          <span class="dot" data-slide="1"></span>
          <span class="dot" data-slide="2"></span>
        </div>
      </section>
</body>
</html>