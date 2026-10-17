<?php
session_start();
require_once __DIR__ . '/config.php';

// Xử lý đăng xuất
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: index.php");
    exit;
}

$page_title = 'BeautyShop - Mỹ Phẩm Chính Hãng & AI Chăm Sóc Da';

// 1. Lấy danh mục sản phẩm
$categories = $pdo->query("SELECT * FROM categories WHERE is_active = 1 ORDER BY sort_order ASC, category_name ASC")->fetchAll();

// 2. Lấy danh sách Flash Sale còn hiệu lực
$flash_sale = $pdo->query("
    SELECT fs.*, fsi.flash_price, fsi.quantity_limit, fsi.sold_count as flash_sold,
           p.product_id, p.product_name, p.original_price, p.price, p.image, p.capacity, b.brand_name
    FROM flash_sales fs
    JOIN flash_sale_items fsi ON fs.id = fsi.flash_sale_id
    JOIN products p ON fsi.product_id = p.product_id
    LEFT JOIN brands b ON p.brand_id = b.brand_id
    WHERE fs.is_active = 1 AND fs.start_time <= NOW() AND fs.end_time >= NOW()
    ORDER BY fsi.id ASC
    LIMIT 6
")->fetchAll();

// 3. Lấy sản phẩm bán chạy nhất
$best_sellers = $pdo->query("
    SELECT p.*, c.category_name, b.brand_name 
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.category_id
    LEFT JOIN brands b ON p.brand_id = b.brand_id
    WHERE p.is_active = 1
    ORDER BY p.sold_count DESC
    LIMIT 8
")->fetchAll();

// 4. Lấy sản phẩm mới nhất
$new_arrivals = $pdo->query("
    SELECT p.*, c.category_name, b.brand_name 
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.category_id
    LEFT JOIN brands b ON p.brand_id = b.brand_id
    WHERE p.is_active = 1 AND p.is_new = 1
    ORDER BY p.product_id DESC
    LIMIT 8
")->fetchAll();

// 5. Lấy danh sách loại da
$skin_types = $pdo->query("SELECT * FROM skin_types ORDER BY id ASC")->fetchAll();

// 6. Lấy đánh giá khách hàng tiêu biểu
$customer_reviews = $pdo->query("
    SELECT r.*, u.full_name, u.username, p.product_name, p.image as product_image
    FROM reviews r
    JOIN users u ON r.user_id = u.id
    JOIN products p ON r.product_id = p.product_id
    WHERE r.rating >= 4 AND r.is_approved = 1
    ORDER BY r.review_id DESC
    LIMIT 4
")->fetchAll();

include 'header.php';
?>

<!-- Khoảng cách bù header cố định -->
<div style="height: 125px;"></div>

<!-- HERO BANNER SLIDER TỰ ĐỘNG -->
<section class="hero-section py-3" style="background: linear-gradient(180deg, #fdf8f5 0%, #fff 100%);">
  <div class="container-fluid px-lg-5">
    <div class="row g-3">
      
      <!-- Main Swiper Banner -->
      <div class="col-lg-8">
        <div class="swiper hero-swiper rounded-4 overflow-hidden shadow-sm position-relative" style="height: 440px;">
          <div class="swiper-wrapper">
            
            <!-- Slide 1: Mỹ phẩm thuần chay Cocoon -->
            <div class="swiper-slide position-relative">
              <img src="../images/baner-beplain.jpg" class="w-100 h-100 object-fit-cover" alt="Mỹ phẩm thuần chay">
              <div class="position-absolute top-0 start-0 w-100 h-100 d-flex align-items-center"
                   style="background: linear-gradient(90deg, rgba(74, 40, 53, 0.75) 0%, rgba(74, 40, 53, 0.1) 70%);">
                <div class="text-white p-4 p-md-5" style="max-width: 580px;">
                  <span class="badge rounded-pill px-3 py-2 mb-3" style="background: #e8a598; color: #4a2835; font-weight: 700;">
                    THUẦN CHAY 100% VIỆT NAM
                  </span>
                  <h2 class="display-6 fw-bold mb-3" style="font-family: 'Playfair Display', serif;">
                    Vẻ Đẹp Tinh Khiết Từ Nông Sản Bản Địa
                  </h2>
                  <p class="mb-4 text-white-50" style="font-size: 1.05rem;">
                    Bí đao mát lành, cà phê Đắk Lắk và hoa cúc hữu cơ nuôi dưỡng làn da rạng rỡ, an lành tuyệt đối.
                  </p>
                  <div class="d-flex gap-3">
                    <a href="category.php?cat=1" class="btn btn-danger rounded-pill px-4 py-2 fw-semibold" style="background: #d47a88; border: none;">
                      Khám phá ngay <i class="bi bi-arrow-right ms-1"></i>
                    </a>
                    <a href="ai_advisor.php" class="btn btn-outline-light rounded-pill px-4 py-2 fw-semibold">
                      <i class="bi bi-stars me-1"></i> AI Tư vấn
                    </a>
                  </div>
                </div>
              </div>
            </div>

            <!-- Slide 2: L'Oréal Paris Chuyên Sâu -->
            <div class="swiper-slide position-relative">
              <img src="../images/baner-lore.jpg" class="w-100 h-100 object-fit-cover" alt="L'Oréal Paris">
              <div class="position-absolute top-0 start-0 w-100 h-100 d-flex align-items-center"
                   style="background: linear-gradient(90deg, rgba(40, 20, 30, 0.8) 0%, rgba(40, 20, 30, 0.15) 70%);">
                <div class="text-white p-4 p-md-5" style="max-width: 580px;">
                  <span class="badge rounded-pill bg-warning text-dark px-3 py-2 mb-3 fw-bold">
                    CÔNG NGHỆ CHỐNG LÃO HÓA
                  </span>
                  <h2 class="display-6 fw-bold mb-3" style="font-family: 'Playfair Display', serif;">
                    L'Oréal Revitalift Hyaluronic Acid
                  </h2>
                  <p class="mb-4 text-white-50" style="font-size: 1.05rem;">
                    Cấp ẩm đa tầng tức thì, căng mọng da và xóa mờ rãnh nhăn nông hiệu quả sau 7 ngày.
                  </p>
                  <a href="product_detail.php?id=17" class="btn btn-danger rounded-pill px-4 py-2 fw-semibold" style="background: #d47a88; border: none;">
                    Mua với giá ưu đãi <i class="bi bi-arrow-right ms-1"></i>
                  </a>
                </div>
              </div>
            </div>

            <!-- Slide 3: Son môi & Trang điểm -->
            <div class="swiper-slide position-relative">
              <img src="../images/baner-son.avif" class="w-100 h-100 object-fit-cover" alt="Bộ sưu tập son môi">
              <div class="position-absolute top-0 start-0 w-100 h-100 d-flex align-items-center"
                   style="background: linear-gradient(90deg, rgba(80, 20, 40, 0.75) 0%, rgba(80, 20, 40, 0.1) 70%);">
                <div class="text-white p-4 p-md-5" style="max-width: 580px;">
                  <span class="badge rounded-pill px-3 py-2 mb-3" style="background: #d4223d;">
                    BỘ SƯU TẬP MỚI 2026
                  </span>
                  <h2 class="display-6 fw-bold mb-3" style="font-family: 'Playfair Display', serif;">
                    Sắc Môi Mịn Mượt Căng Tràn Quyến Rũ
                  </h2>
                  <p class="mb-4 text-white-50" style="font-size: 1.05rem;">
                    Tuyển tập các dòng son lì Romand, 3CE chuẩn màu thời thượng dành cho mọi tông da châu Á.
                  </p>
                  <a href="category.php?cat=7" class="btn btn-danger rounded-pill px-4 py-2 fw-semibold" style="background: #d47a88; border: none;">
                    Xem bộ sưu tập <i class="bi bi-arrow-right ms-1"></i>
                  </a>
                </div>
              </div>
            </div>

          </div>
          <!-- Swiper Controls -->
          <div class="swiper-button-next text-white"></div>
          <div class="swiper-button-prev text-white"></div>
          <div class="swiper-pagination"></div>
        </div>
      </div>

      <!-- Right Promotion Mini Banners -->
      <div class="col-lg-4 d-flex flex-column gap-3">
        
        <!-- Box 1: AI Advisor Promo Card -->
        <div class="p-4 rounded-4 text-white position-relative overflow-hidden flex-fill d-flex flex-column justify-content-between shadow-sm"
             style="background: linear-gradient(135deg, #9e4760 0%, #d47a88 100%); min-height: 205px;">
          <div>
            <div class="d-flex align-items-center gap-2 mb-2">
              <span class="badge bg-white text-danger px-2 py-1 rounded-pill fw-bold" style="font-size: 0.7rem;">HOT FEATURE</span>
              <small class="text-white-50">Tư vấn trực tuyến</small>
            </div>
            <h4 class="fw-bold mb-1" style="font-family: 'Playfair Display', serif;">AI Beauty Advisor</h4>
            <p class="small text-white-50 mb-0">Chưa biết da mình cần gì? Trò chuyện với AI để nhận gợi ý sản phẩm và routine chuẩn khoa học.</p>
          </div>
          <div class="pt-3">
            <a href="ai_advisor.php" class="btn btn-sm btn-light rounded-pill px-3 fw-bold text-danger">
              Trải nghiệm ngay <i class="bi bi-stars ms-1"></i>
            </a>
          </div>
        </div>

        <!-- Box 2: Routine Builder Promo Card -->
        <div class="p-4 rounded-4 text-dark position-relative overflow-hidden flex-fill d-flex flex-column justify-content-between shadow-sm border"
             style="background: #fdf5f3; min-height: 205px;">
          <div>
            <div class="d-flex align-items-center gap-2 mb-2">
              <span class="badge bg-danger text-white px-2 py-1 rounded-pill" style="font-size: 0.7rem;">CÁ NHÂN HÓA</span>
              <small class="text-muted">3 Bước đơn giản</small>
            </div>
            <h4 class="fw-bold mb-1" style="color: #4a2835; font-family: 'Playfair Display', serif;">Xây Dựng Routine Da</h4>
            <p class="small text-muted mb-0">Thiết kế lộ trình Skincare buổi sáng và tối tối ưu chi phí, hiệu quả cao.</p>
          </div>
          <div class="pt-3">
            <a href="routine_builder.php" class="btn btn-sm btn-outline-danger rounded-pill px-3 fw-bold" style="border-color:#d47a88; color:#d47a88;">
              Bắt đầu tạo routine <i class="bi bi-magic ms-1"></i>
            </a>
          </div>
        </div>

      </div>

    </div>
  </div>
</section>

<!-- 4 CAM KẾT VÀNG TỪ BEAUTYSHOP -->
<section class="py-4 border-top border-bottom bg-white">
  <div class="container-fluid px-lg-5">
    <div class="row g-3 text-center text-md-start">
      <div class="col-6 col-md-3">
        <div class="d-flex align-items-center gap-3 p-2">
          <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" 
               style="width: 50px; height: 50px; background: #fdf0f2; color: #d47a88;">
            <i class="bi bi-patch-check-fill fs-3"></i>
          </div>
          <div>
            <div class="fw-bold" style="font-size: 0.95rem;">100% Chính Hãng</div>
            <small class="text-muted">Đầy đủ tem phụ & hóa đơn</small>
          </div>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="d-flex align-items-center gap-3 p-2">
          <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" 
               style="width: 50px; height: 50px; background: #fdf0f2; color: #d47a88;">
            <i class="bi bi-truck fs-3"></i>
          </div>
          <div>
            <div class="fw-bold" style="font-size: 0.95rem;">Giao Hàng Thần Tốc</div>
            <small class="text-muted">Freeship đơn từ 150.000₫</small>
          </div>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="d-flex align-items-center gap-3 p-2">
          <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" 
               style="width: 50px; height: 50px; background: #fdf0f2; color: #d47a88;">
            <i class="bi bi-arrow-repeat fs-3"></i>
          </div>
          <div>
            <div class="fw-bold" style="font-size: 0.95rem;">Đổi Trả Trong 7 Ngày</div>
            <small class="text-muted">Bảo vệ quyền lợi tối đa</small>
          </div>
        </div>
      </div>
      <div class="col-6 col-md-3">
        <div class="d-flex align-items-center gap-3 p-2">
          <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" 
               style="width: 50px; height: 50px; background: #fdf0f2; color: #d47a88;">
            <i class="bi bi-robot fs-3"></i>
          </div>
          <div>
            <div class="fw-bold" style="font-size: 0.95rem;">AI Advisor 24/7</div>
            <small class="text-muted">Tư vấn chuyên sâu miễn phí</small>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- DANH MỤC MỸ PHẨM NỔI BẬT -->
<section id="category-section" class="py-5" style="background: #fffdfb;">
  <div class="container-fluid px-lg-5">
    <div class="d-flex justify-content-between align-items-end mb-4">
      <div>
        <span class="text-uppercase fw-bold" style="color: #d47a88; font-size: 0.8rem; letter-spacing: 1.5px;">BỘ SƯU TẬP</span>
        <h2 class="fw-bold mb-0" style="color: #4a2835;">Danh Mục Mỹ Phẩm</h2>
      </div>
      <a href="products.php" class="text-decoration-none fw-bold" style="color: #d47a88;">
        Xem tất cả <i class="bi bi-chevron-right"></i>
      </a>
    </div>

    <div class="row row-cols-2 row-cols-md-4 row-cols-lg-7 g-3">
      <?php foreach ($categories as $cat): ?>
        <div class="col">
          <a href="category.php?cat=<?= $cat['category_id'] ?>" 
             class="card h-100 border text-center p-3 text-decoration-none rounded-4 category-box shadow-xs bg-white"
             style="transition: all 0.25s ease;">
            <div class="rounded-circle mx-auto d-flex align-items-center justify-content-center mb-3"
                 style="width: 70px; height: 70px; background: #fff5f7;">
              <img src="../images/cosmetics.png" alt="<?= htmlspecialchars($cat['category_name']) ?>" style="height: 38px; filter: drop-shadow(0 2px 4px rgba(212,122,136,0.3));">
            </div>
            <h6 class="fw-bold text-dark mb-0 small" style="line-height: 1.4;"><?= htmlspecialchars($cat['category_name']) ?></h6>
          </a>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- FLASH SALE GIỜ VÀNG (ĐẾM NGƯỢC THỜI GIAN) -->
<?php if (!empty($flash_sale)): ?>
<section id="flash-sale-section" class="py-5" style="background: linear-gradient(135deg, #fff0f3 0%, #fff 100%);">
  <div class="container-fluid px-lg-5">
    <div class="p-4 p-md-5 rounded-4 shadow-sm border border-danger border-opacity-25" style="background: white;">
      
      <!-- Flash Sale Header -->
      <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
        <div class="d-flex align-items-center gap-3">
          <div class="rounded-pill px-3 py-2 text-white fw-bold d-flex align-items-center gap-2"
               style="background: linear-gradient(135deg, #d4223d, #ff5722);">
            <i class="bi bi-lightning-charge-fill fs-5"></i>
            <span class="fs-5 tracking-wide">FLASH SALE</span>
          </div>
          <span class="text-muted d-none d-md-inline">Giảm giá chớp nhoáng – Số lượng có hạn</span>
        </div>

        <!-- Đồng hồ đếm ngược -->
        <div class="d-flex align-items-center gap-2">
          <span class="text-muted small fw-semibold">KẾT THÚC SAU:</span>
          <div class="d-flex gap-1" id="countdownTimer">
            <span class="badge bg-dark px-2 py-2 fs-6" id="cdHours">05</span>
            <span class="fw-bold">:</span>
            <span class="badge bg-dark px-2 py-2 fs-6" id="cdMinutes">42</span>
            <span class="fw-bold">:</span>
            <span class="badge bg-dark px-2 py-2 fs-6 text-danger" id="cdSeconds">18</span>
          </div>
        </div>
      </div>

      <!-- Flash Sale Products Grid -->
      <div class="row row-cols-2 row-cols-md-3 row-cols-lg-6 g-3">
        <?php foreach ($flash_sale as $item): 
          $discount_pct = round((($item['original_price'] - $item['flash_price']) / $item['original_price']) * 100);
          $progress_pct = min(100, round(($item['flash_sold'] / $item['quantity_limit']) * 100));
        ?>
          <div class="col">
            <div class="beauty-card h-100 p-2">
              <span class="discount-badge">-<?= $discount_pct ?>%</span>
              <a href="product_detail.php?id=<?= $item['product_id'] ?>" class="text-decoration-none">
                <div class="img-box rounded-3 mb-2">
                  <img src="../images/products/<?= htmlspecialchars($item['image']) ?>" alt="<?= htmlspecialchars($item['product_name']) ?>" loading="lazy">
                </div>
                <div class="px-1">
                  <div class="text-muted" style="font-size: 0.72rem;"><?= htmlspecialchars($item['brand_name'] ?? 'Chính hãng') ?></div>
                  <h6 class="text-dark fw-bold line-clamp-2 small mb-1" title="<?= htmlspecialchars($item['product_name']) ?>">
                    <?= htmlspecialchars($item['product_name']) ?>
                  </h6>
                  <div class="d-flex align-items-baseline gap-2 mb-2">
                    <span class="fw-bold text-danger fs-6"><?= format_price($item['flash_price']) ?></span>
                    <span class="text-muted text-decoration-line-through" style="font-size: 0.75rem;"><?= format_price($item['original_price']) ?></span>
                  </div>
                  <!-- Progress bar đã bán -->
                  <div class="progress mb-2" style="height: 12px; border-radius: 6px; background:#f0e6e8;">
                    <div class="progress-bar bg-danger" style="width: <?= $progress_pct ?>%; font-size: 0.65rem;">
                      Đã bán <?= $item['flash_sold'] ?>
                    </div>
                  </div>
                </div>
              </a>
              <button onclick="addToCart(<?= $item['product_id'] ?>, 1)" class="btn btn-sm btn-outline-danger w-100 rounded-pill py-1 mt-auto" style="font-size: 0.8rem;">
                <i class="bi bi-cart-plus"></i> Chọn mua
              </button>
            </div>
          </div>
        <?php endforeach; ?>
      </div>

    </div>
  </div>
</section>
<?php endif; ?>

<!-- KHÁM PHÁ SẢN PHẨM THEO TỪNG LOẠI DA -->
<section class="py-5" style="background: #faf7f5;">
  <div class="container-fluid px-lg-5">
    <div class="text-center mb-4">
      <span class="text-uppercase fw-bold" style="color: #d47a88; font-size: 0.8rem; letter-spacing: 1.5px;">CÁ NHÂN HÓA LÀN DA</span>
      <h2 class="fw-bold mb-2" style="color: #4a2835;">Khám Phá Mỹ Phẩm Theo Loại Da Của Bạn</h2>
      <p class="text-muted" style="max-width: 600px; margin: 0 auto;">Mỗi loại da có một cơ chế sinh học riêng. Hãy lựa chọn sản phẩm chuyên biệt để đạt hiệu quả tối ưu.</p>
    </div>

    <!-- Nút chọn loại da -->
    <div class="d-flex justify-content-center flex-wrap gap-2 mb-4">
      <?php foreach ($skin_types as $idx => $st): ?>
        <a href="products.php?skin_type=<?= $st['id'] ?>" 
           class="btn btn-outline-secondary rounded-pill px-4 py-2 bg-white border shadow-xs d-flex align-items-center gap-2 hover-shadow">
          <i class="bi <?= $st['icon'] ?: 'bi-stars' ?> text-danger"></i>
          <span class="fw-bold"><?= htmlspecialchars($st['name']) ?></span>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- SẢN PHẨM BÁN CHẠY NHẤT (BEST SELLERS) -->
<section class="py-5 bg-white">
  <div class="container-fluid px-lg-5">
    <div class="d-flex justify-content-between align-items-end mb-4">
      <div>
        <span class="text-uppercase fw-bold" style="color: #d47a88; font-size: 0.8rem; letter-spacing: 1.5px;">ĐƯỢC YÊU THÍCH NHẤT</span>
        <h2 class="fw-bold mb-0" style="color: #4a2835;">Sản Phẩm Bán Chạy</h2>
      </div>
      <a href="products.php?sort=best_seller" class="text-decoration-none fw-bold" style="color: #d47a88;">
        Xem thêm <i class="bi bi-chevron-right"></i>
      </a>
    </div>

    <div class="row row-cols-2 row-cols-md-3 row-cols-lg-4 g-4">
      <?php foreach ($best_sellers as $p): 
        $discount_pct = ($p['original_price'] > $p['price']) ? round((($p['original_price'] - $p['price']) / $p['original_price']) * 100) : 0;
      ?>
        <div class="col">
          <div class="beauty-card h-100 d-flex flex-column p-3">
            <?php if ($discount_pct > 0): ?>
              <span class="discount-badge">-<?= $discount_pct ?>%</span>
            <?php endif; ?>
            
            <a href="product_detail.php?id=<?= $p['product_id'] ?>" class="text-decoration-none flex-grow-1">
              <div class="img-box rounded-3 mb-3">
                <img src="../images/products/<?= htmlspecialchars($p['image']) ?>" alt="<?= htmlspecialchars($p['product_name']) ?>" loading="lazy">
              </div>
              
              <div class="text-muted small mb-1"><?= htmlspecialchars($p['brand_name'] ?? 'Chính hãng') ?> • <?= htmlspecialchars($p['capacity'] ?? '') ?></div>
              <h6 class="text-dark fw-bold line-clamp-2 mb-2" title="<?= htmlspecialchars($p['product_name']) ?>">
                <?= htmlspecialchars($p['product_name']) ?>
              </h6>
              
              <div class="d-flex align-items-center gap-1 mb-2">
                <span class="text-warning small"><i class="bi bi-star-fill"></i> <?= number_format($p['rating_avg'], 1) ?></span>
                <span class="text-muted small">(<?= $p['rating_count'] ?> đánh giá)</span>
                <span class="text-muted small ms-auto">Đã bán <?= $p['sold_count'] ?></span>
              </div>

              <div class="d-flex align-items-baseline gap-2 mb-3">
                <span class="fw-bold text-danger fs-5"><?= format_price($p['price']) ?></span>
                <?php if ($p['original_price'] > $p['price']): ?>
                  <span class="text-muted text-decoration-line-through small"><?= format_price($p['original_price']) ?></span>
                <?php endif; ?>
              </div>
            </a>

            <div class="d-flex gap-2 mt-auto">
              <a href="product_detail.php?id=<?= $p['product_id'] ?>" class="btn btn-outline-secondary btn-sm flex-grow-1 rounded-pill py-2">
                Chi tiết
              </a>
              <button onclick="addToCart(<?= $p['product_id'] ?>, 1)" class="btn btn-danger btn-sm flex-grow-1 rounded-pill py-2 text-white" style="background: #d47a88; border-color: #d47a88;">
                <i class="bi bi-cart-plus me-1"></i> Mua ngay
              </button>
            </div>

          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- BANNER CTA: AI BEAUTY ADVISOR -->
<section class="py-5" style="background: linear-gradient(135deg, #4a2835 0%, #683042 100%);">
  <div class="container-fluid px-lg-5">
    <div class="row align-items-center text-white py-4">
      <div class="col-lg-8">
        <span class="badge rounded-pill bg-warning text-dark px-3 py-2 mb-3 fw-bold">
          <i class="bi bi-stars"></i> TRÍ TUỆ NHÂN TẠO GEMINI
        </span>
        <h2 class="display-6 fw-bold mb-3" style="font-family: 'Playfair Display', serif;">
          Trợ Lý AI Tư Vấn Da – Chuẩn Khoa Học & Phù Hợp Túi Tiền
        </h2>
        <p class="text-white-50 mb-4" style="font-size: 1.1rem; max-width: 650px;">
          Bạn đang băn khoăn về mụn viêm, da khô căng hay muốn tìm một loại kem chống nắng không bết rít? Trợ lý AI của chúng tôi sẽ tra cứu kho dữ liệu sản phẩm thật và gợi ý ngay cho bạn!
        </p>
        <div class="d-flex flex-wrap gap-3">
          <a href="ai_advisor.php" class="btn btn-danger rounded-pill px-4 py-3 fw-bold text-white shadow" style="background: #d47a88; border: none;">
            <i class="bi bi-chat-dots-fill me-2"></i> Bắt đầu trò chuyện với AI
          </a>
          <a href="routine_builder.php" class="btn btn-outline-light rounded-pill px-4 py-3 fw-bold">
            <i class="bi bi-magic me-2"></i> Thiết kế Routine Skincare
          </a>
        </div>
      </div>
      <div class="col-lg-4 text-center mt-4 mt-lg-0">
        <div class="rounded-4 p-4 d-inline-block text-start shadow-lg" style="background: rgba(255, 255, 255, 0.1); backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.2);">
          <div class="d-flex align-items-center gap-2 mb-3">
            <div class="rounded-circle bg-white text-danger d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;">
              <i class="bi bi-robot"></i>
            </div>
            <div>
              <div class="fw-bold">Beauty Advisor</div>
              <small class="text-white-50">Gợi ý câu hỏi:</small>
            </div>
          </div>
          <p class="small text-white-50 fst-italic mb-2">"Da mình hỗn hợp thiên dầu, có ít mụn ẩn ở trán thì nên dùng sữa rửa mặt và toner nào?"</p>
          <p class="small text-white-50 fst-italic mb-0">"Cho mình xem serum phục hồi B5 giá dưới 400.000₫ đang có sẵn trong shop nhé!"</p>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- SẢN PHẨM MỚI VỀ (NEW ARRIVALS) -->
<section class="py-5 bg-white">
  <div class="container-fluid px-lg-5">
    <div class="d-flex justify-content-between align-items-end mb-4">
      <div>
        <span class="text-uppercase fw-bold" style="color: #d47a88; font-size: 0.8rem; letter-spacing: 1.5px;">CẬP NHẬT MỚI NHẤT</span>
        <h2 class="fw-bold mb-0" style="color: #4a2835;">Sản Phẩm Mới Về</h2>
      </div>
      <a href="products.php?sort=newest" class="text-decoration-none fw-bold" style="color: #d47a88;">
        Xem thêm <i class="bi bi-chevron-right"></i>
      </a>
    </div>

    <div class="row row-cols-2 row-cols-md-3 row-cols-lg-4 g-4">
      <?php foreach ($new_arrivals as $p): 
        $discount_pct = ($p['original_price'] > $p['price']) ? round((($p['original_price'] - $p['price']) / $p['original_price']) * 100) : 0;
      ?>
        <div class="col">
          <div class="beauty-card h-100 d-flex flex-column p-3">
            <span class="badge rounded-pill bg-success position-absolute" style="top: 10px; left: 10px; z-index: 2; font-size: 0.7rem;">MỚI</span>
            
            <a href="product_detail.php?id=<?= $p['product_id'] ?>" class="text-decoration-none flex-grow-1">
              <div class="img-box rounded-3 mb-3">
                <img src="../images/products/<?= htmlspecialchars($p['image']) ?>" alt="<?= htmlspecialchars($p['product_name']) ?>" loading="lazy">
              </div>
              
              <div class="text-muted small mb-1"><?= htmlspecialchars($p['brand_name'] ?? 'Chính hãng') ?> • <?= htmlspecialchars($p['capacity'] ?? '') ?></div>
              <h6 class="text-dark fw-bold line-clamp-2 mb-2" title="<?= htmlspecialchars($p['product_name']) ?>">
                <?= htmlspecialchars($p['product_name']) ?>
              </h6>
              
              <div class="d-flex align-items-center gap-1 mb-2">
                <span class="text-warning small"><i class="bi bi-star-fill"></i> <?= number_format($p['rating_avg'], 1) ?></span>
                <span class="text-muted small">(<?= $p['rating_count'] ?>)</span>
              </div>

              <div class="d-flex align-items-baseline gap-2 mb-3">
                <span class="fw-bold text-danger fs-5"><?= format_price($p['price']) ?></span>
                <?php if ($p['original_price'] > $p['price']): ?>
                  <span class="text-muted text-decoration-line-through small"><?= format_price($p['original_price']) ?></span>
                <?php endif; ?>
              </div>
            </a>

            <div class="d-flex gap-2 mt-auto">
              <a href="product_detail.php?id=<?= $p['product_id'] ?>" class="btn btn-outline-secondary btn-sm flex-grow-1 rounded-pill py-2">
                Chi tiết
              </a>
              <button onclick="addToCart(<?= $p['product_id'] ?>, 1)" class="btn btn-danger btn-sm flex-grow-1 rounded-pill py-2 text-white" style="background: #d47a88; border-color: #d47a88;">
                <i class="bi bi-cart-plus me-1"></i> Mua ngay
              </button>
            </div>

          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ĐÁNH GIÁ KHÁCH HÀNG THỰC TẾ -->
<?php if (!empty($customer_reviews)): ?>
<section class="py-5" style="background: #faf7f5;">
  <div class="container-fluid px-lg-5">
    <div class="text-center mb-5">
      <span class="text-uppercase fw-bold" style="color: #d47a88; font-size: 0.8rem; letter-spacing: 1.5px;">CẢM NHẬN KHÁCH HÀNG</span>
      <h2 class="fw-bold mb-2" style="color: #4a2835;">Khách Hàng Nói Gì Về BeautyShop?</h2>
      <p class="text-muted">Trải nghiệm mua sắm và chăm sóc da thực tế từ các cô gái Việt</p>
    </div>

    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-4 g-4">
      <?php foreach ($customer_reviews as $rev): ?>
        <div class="col">
          <div class="card h-100 border-0 rounded-4 shadow-xs p-4 bg-white d-flex flex-column justify-content-between">
            <div>
              <div class="text-warning mb-2">
                <?php for ($i=1; $i<=$rev['rating']; $i++): ?>
                  <i class="bi bi-star-fill"></i>
                <?php endfor; ?>
              </div>
              <p class="text-muted small fst-italic mb-3" style="line-height: 1.7;">
                "<?= htmlspecialchars($rev['comment']) ?>"
              </p>
            </div>
            
            <div class="d-flex align-items-center gap-2 pt-3 border-top">
              <div class="rounded-circle d-flex align-items-center justify-content-center text-white"
                   style="width: 38px; height: 38px; background: linear-gradient(135deg, #d47a88, #9e4760);">
                <i class="bi bi-person-fill"></i>
              </div>
              <div>
                <div class="fw-bold small text-dark"><?= htmlspecialchars($rev['full_name'] ?: $rev['username']) ?></div>
                <small class="text-success" style="font-size: 0.7rem;"><i class="bi bi-check-circle-fill"></i> Đã mua hàng</small>
              </div>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- BANNER ĐĂNG KÝ NHẬN BẢN TIN & MÃ GIẢM GIÁ -->
<section class="py-5" style="background: linear-gradient(135deg, #fcedf2 0%, #fff 100%);">
  <div class="container text-center py-4">
    <div class="rounded-circle d-flex align-items-center justify-content-center mx-auto mb-3"
         style="width: 60px; height: 60px; background: #d47a88; color: white;">
      <i class="bi bi-envelope-heart-fill fs-3"></i>
    </div>
    <h3 class="fw-bold mb-2" style="color: #4a2835;">Nhận Ngay Mã Giảm 10% Cho Đơn Hàng Đầu Tiên</h3>
    <p class="text-muted mb-4" style="max-width: 500px; margin: 0 auto;">
      Đăng ký email để nhận thông tin về các chương trình Flash Sale độc quyền và bí quyết dưỡng da từ chuyên gia.
    </p>
    <div class="row justify-content-center">
      <div class="col-md-6 col-lg-5">
        <form class="d-flex gap-2" onsubmit="event.preventDefault(); alert('Cảm ơn bạn đã đăng ký! Mã giảm giá BEAUTYNEW đã sẵn sàng cho bạn tại giỏ hàng.');">
          <input type="email" class="form-control rounded-pill px-4" placeholder="Nhập email của bạn..." required>
          <button type="submit" class="btn btn-danger rounded-pill px-4 fw-bold flex-shrink-0" style="background: #d47a88; border: none;">
            Đăng ký
          </button>
        </form>
      </div>
    </div>
  </div>
</section>

<!-- Countdown Script cho Flash Sale & Swiper Initialization -->
<script>
document.addEventListener('DOMContentLoaded', function() {
  // 1. Swiper Banner Slider
  if (typeof Swiper !== 'undefined') {
    new Swiper('.hero-swiper', {
      loop: true,
      autoplay: {
        delay: 4000,
        disableOnInteraction: false,
      },
      pagination: {
        el: '.swiper-pagination',
        clickable: true,
      },
      navigation: {
        nextEl: '.swiper-button-next',
        prevEl: '.swiper-button-prev',
      },
    });
  }

  // 2. Countdown Timer Simulator (Giờ phút giây)
  let totalSeconds = 5 * 3600 + 42 * 60 + 18;
  const cdH = document.getElementById('cdHours');
  const cdM = document.getElementById('cdMinutes');
  const cdS = document.getElementById('cdSeconds');

  if (cdH && cdM && cdS) {
    setInterval(() => {
      if (totalSeconds <= 0) totalSeconds = 24 * 3600;
      totalSeconds--;
      const h = Math.floor(totalSeconds / 3600);
      const m = Math.floor((totalSeconds % 3600) / 60);
      const s = totalSeconds % 60;
      cdH.textContent = String(h).padStart(2, '0');
      cdM.textContent = String(m).padStart(2, '0');
      cdS.textContent = String(s).padStart(2, '0');
    }, 1000);
  }
});
</script>

<?php include 'footer.php'; ?>

<?php include 'footer.php'; ?>

