<?php
session_start();
require_once __DIR__ . '/config.php';

$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($product_id <= 0) {
    header("Location: products.php");
    exit;
}

// 1. Lấy thông tin sản phẩm và thương hiệu, danh mục
$stmt = $pdo->prepare("
    SELECT p.*, c.category_name, b.brand_name, b.origin_country, b.slug as brand_slug
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.category_id
    LEFT JOIN brands b ON p.brand_id = b.brand_id
    WHERE p.product_id = ?
");
$stmt->execute([$product_id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    header("Location: products.php");
    exit;
}

// 2. Lấy danh sách loại da phù hợp
$stmt_st = $pdo->prepare("
    SELECT st.name, st.icon, st.code
    FROM product_skin_types pst
    JOIN skin_types st ON pst.skin_type_id = st.id
    WHERE pst.product_id = ?
");
$stmt_st->execute([$product_id]);
$product_skin_types = $stmt_st->fetchAll(PDO::FETCH_ASSOC);

// 3. Lấy danh sách thành phần chính
$stmt_ing = $pdo->prepare("
    SELECT i.name, i.safety_rating, i.benefit_description
    FROM product_ingredients pi
    JOIN ingredients i ON pi.ingredient_id = i.id
    WHERE pi.product_id = ?
");
$stmt_ing->execute([$product_id]);
$product_ingredients = $stmt_ing->fetchAll(PDO::FETCH_ASSOC);

// 4. Lấy sản phẩm liên quan (cùng danh mục)
$stmt_rel = $pdo->prepare("
    SELECT p.*, b.brand_name
    FROM products p
    LEFT JOIN brands b ON p.brand_id = b.brand_id
    WHERE p.category_id = ? AND p.product_id != ? AND p.is_active = 1
    ORDER BY p.sold_count DESC
    LIMIT 4
");
$stmt_rel->execute([$product['category_id'], $product_id]);
$related_products = $stmt_rel->fetchAll(PDO::FETCH_ASSOC);

// 5. Kiểm tra trạng thái yêu thích
$is_wishlisted = false;
if (is_logged_in()) {
    $stmt_wl_check = $pdo->prepare("SELECT id FROM wishlists WHERE user_id = ? AND product_id = ?");
    $stmt_wl_check->execute([$_SESSION['user_id'], $product_id]);
    $is_wishlisted = (bool)$stmt_wl_check->fetch();
}

$page_title = htmlspecialchars($product['product_name']) . ' - BeautyShop';
$discount_pct = ($product['original_price'] > $product['price']) ? round((($product['original_price'] - $product['price']) / $product['original_price']) * 100) : 0;

include 'header.php';
?>

<!-- Khoảng cách bù header -->
<div style="height: 125px;"></div>

<!-- Breadcrumb -->
<div class="py-2 bg-light border-bottom">
  <div class="container-fluid px-lg-5">
    <nav aria-label="breadcrumb">
      <ol class="breadcrumb mb-0 small">
        <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none text-muted">Trang chủ</a></li>
        <li class="breadcrumb-item"><a href="products.php?cat=<?= $product['category_id'] ?>" class="text-decoration-none text-muted"><?= htmlspecialchars($product['category_name']) ?></a></li>
        <li class="breadcrumb-item active text-truncate" style="max-width: 400px; color: #9e4760;"><?= htmlspecialchars($product['product_name']) ?></li>
      </ol>
    </nav>
  </div>
</div>

<div class="py-5" style="background: #fffdfb;">
  <div class="container-fluid px-lg-5">
    <div class="row g-4 g-lg-5">
      
      <!-- CỘT ẢNH SẢN PHẨM -->
      <div class="col-lg-5">
        <div class="position-sticky" style="top: 140px;">
          <div class="card border-0 rounded-4 overflow-hidden shadow-sm p-3 bg-white text-center">
            <div class="position-relative">
              <?php if ($discount_pct > 0): ?>
                <span class="discount-badge fs-6 px-3 py-1">-<?= $discount_pct ?>%</span>
              <?php endif; ?>
              
              <button class="wishlist-heart-btn <?= $is_wishlisted ? 'active text-danger' : '' ?>" 
                      id="btnWishlist" onclick="toggleWishlist(<?= $product_id ?>)" title="Thêm vào yêu thích">
                <i class="bi bi-heart<?= $is_wishlisted ? '-fill' : '' ?> fs-5"></i>
              </button>

              <img src="../images/products/<?= htmlspecialchars($product['image']) ?>" 
                   alt="<?= htmlspecialchars($product['product_name']) ?>" 
                   class="img-fluid rounded-4 object-fit-cover shadow-xs" 
                   style="max-height: 480px; width: 100%;">
            </div>
            
            <div class="d-flex justify-content-center gap-3 mt-3 text-muted small">
              <span><i class="bi bi-shield-check text-success me-1"></i> Chính hãng 100%</span>
              <span>•</span>
              <span><i class="bi bi-truck text-primary me-1"></i> Giao nhanh toàn quốc</span>
              <span>•</span>
              <span><i class="bi bi-arrow-repeat text-danger me-1"></i> Đổi trả 7 ngày</span>
            </div>
          </div>
        </div>
      </div>

      <!-- CỘT THÔNG TIN CHI TIẾT SẢN PHẨM -->
      <div class="col-lg-7">
        <div class="card border-0 rounded-4 shadow-sm p-4 p-md-5 bg-white">
          
          <!-- Thương hiệu & Nguồn gốc -->
          <div class="d-flex align-items-center gap-2 mb-2">
            <span class="badge rounded-pill px-3 py-1 text-white fw-bold" style="background: linear-gradient(135deg, #d47a88, #9e4760); font-size: 0.75rem;">
              <?= htmlspecialchars($product['brand_name'] ?? 'CHÍNH HÃNG') ?>
            </span>
            <small class="text-muted">• Xuất xứ: <?= htmlspecialchars($product['origin_country'] ?? 'Quốc tế') ?></small>
            <small class="text-muted">• Dung tích: <?= htmlspecialchars($product['capacity']) ?></small>
          </div>

          <!-- Tên sản phẩm -->
          <h1 class="h3 fw-bold mb-3" style="color: #3b1e28; line-height: 1.4;">
            <?= htmlspecialchars($product['product_name']) ?>
          </h1>

          <!-- Đánh giá sao & Lượt bán -->
          <div class="d-flex align-items-center gap-3 mb-4 pb-3 border-bottom flex-wrap">
            <div class="d-flex align-items-center gap-1 text-warning">
              <i class="bi bi-star-fill"></i>
              <strong class="text-dark fs-6"><?= number_format($product['rating_avg'], 1) ?></strong>
              <small class="text-muted">(<?= $product['rating_count'] ?> đánh giá)</small>
            </div>
            <span class="text-muted">•</span>
            <div class="small text-muted">
              Đã bán: <strong class="text-dark"><?= $product['sold_count'] ?></strong> sản phẩm
            </div>
            <span class="text-muted">•</span>
            <div>
              <?php if ($product['quantity'] > 0): ?>
                <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-3 py-1">
                  <i class="bi bi-check-circle-fill me-1"></i> Còn hàng (<?= $product['quantity'] ?> sản phẩm)
                </span>
              <?php else: ?>
                <span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill px-3 py-1">
                  Tạm hết hàng
                </span>
              <?php endif; ?>
            </div>
          </div>

          <!-- Giá bán -->
          <div class="p-3 rounded-4 mb-4" style="background: #fdf5f7; border: 1px solid #f6e1e6;">
            <div class="d-flex align-items-baseline gap-3">
              <span class="display-6 fw-bold text-danger"><?= format_price($product['price']) ?></span>
              <?php if ($product['original_price'] > $product['price']): ?>
                <span class="text-muted text-decoration-line-through fs-5"><?= format_price($product['original_price']) ?></span>
                <span class="badge bg-danger rounded-pill px-2 py-1 small">Tiết kiệm <?= format_price($product['original_price'] - $product['price']) ?></span>
              <?php endif; ?>
            </div>
            <small class="text-muted d-block mt-1">Đã bao gồm thuế VAT & phí đóng gói chống sốc an toàn</small>
          </div>

          <!-- Phù hợp loại da -->
          <div class="mb-4">
            <div class="fw-bold small text-muted text-uppercase mb-2">Loại da khuyên dùng:</div>
            <div class="d-flex flex-wrap gap-2">
              <?php if (!empty($product_skin_types)): ?>
                <?php foreach ($product_skin_types as $pst): ?>
                  <span class="badge bg-white text-dark border px-3 py-2 rounded-pill shadow-xs">
                    <i class="bi <?= $pst['icon'] ?: 'bi-check' ?> text-danger me-1"></i> <?= htmlspecialchars($pst['name']) ?>
                  </span>
                <?php endforeach; ?>
              <?php else: ?>
                <span class="badge bg-white text-dark border px-3 py-2 rounded-pill shadow-xs">
                  <i class="bi bi-stars text-danger me-1"></i> <?= htmlspecialchars($product['skin_type_summary']) ?>
                </span>
              <?php endif; ?>
            </div>
          </div>

          <!-- Chọn số lượng & Nút Thêm vào giỏ -->
          <div class="mb-4">
            <div class="fw-bold small text-muted text-uppercase mb-2">Số lượng:</div>
            <div class="d-flex align-items-center gap-3">
              <div class="input-group" style="width: 140px;">
                <button class="btn btn-outline-secondary" type="button" onclick="changeQty(-1)">-</button>
                <input type="number" id="detailQty" class="form-control text-center fw-bold" value="1" min="1" max="<?= max(1, $product['quantity']) ?>" readonly>
                <button class="btn btn-outline-secondary" type="button" onclick="changeQty(1)">+</button>
              </div>
              <small class="text-muted">Tối đa <?= $product['quantity'] ?> sản phẩm trong kho</small>
            </div>
          </div>

          <!-- Nhóm Nút Mua Hàng -->
          <div class="d-flex gap-3 pt-2">
            <button onclick="handleAddToCart()" class="btn btn-outline-danger btn-lg rounded-pill flex-fill py-3 fw-bold d-flex align-items-center justify-content-center gap-2"
                    style="border-color: #d47a88; color: #d47a88;" <?= $product['quantity'] <= 0 ? 'disabled' : '' ?>>
              <i class="bi bi-cart-plus-fill fs-5"></i> Thêm vào giỏ
            </button>
            <button onclick="handleBuyNow()" class="btn btn-danger btn-lg rounded-pill flex-fill py-3 fw-bold text-white shadow d-flex align-items-center justify-content-center gap-2"
                    style="background: linear-gradient(135deg, #d47a88, #9e4760); border: none;" <?= $product['quantity'] <= 0 ? 'disabled' : '' ?>>
              <i class="bi bi-bag-check-fill fs-5"></i> Mua ngay
            </button>
          </div>

          <!-- Banner AI tư vấn về sản phẩm này -->
          <div class="mt-4 p-3 rounded-4 bg-light border d-flex align-items-center justify-content-between gap-3">
            <div class="d-flex align-items-center gap-2">
              <div class="rounded-circle d-flex align-items-center justify-content-center text-white" 
                   style="width: 36px; height: 36px; background: #d47a88;">
                <i class="bi bi-robot"></i>
              </div>
              <div>
                <small class="fw-bold text-dark d-block">Bạn muốn biết sản phẩm này có hợp với da mình?</small>
                <small class="text-muted">Hỏi chuyên viên AI Beauty Advisor ngay lập tức</small>
              </div>
            </div>
            <a href="ai_advisor.php" class="btn btn-sm btn-danger rounded-pill px-3 py-1 text-white text-nowrap" style="background:#d47a88; border:none;">
              Hỏi AI ngay
            </a>
          </div>

        </div>
      </div>

    </div>

    <!-- TABS CHI TIẾT SẢN PHẨM (MÔ TẢ - THÀNH PHẦN - HƯỚNG DẪN - ĐÁNH GIÁ) -->
    <div class="row mt-5">
      <div class="col-12">
        <div class="card border-0 rounded-4 shadow-sm bg-white p-4 p-md-5">
          
          <ul class="nav nav-pills gap-2 mb-4 border-bottom pb-3" id="productTab" role="tablist">
            <li class="nav-item">
              <button class="nav-link active rounded-pill px-4 py-2 fw-semibold" id="desc-tab" data-bs-toggle="pill" data-bs-target="#desc" type="button" role="tab">
                <i class="bi bi-text-paragraph me-1"></i> Mô Tả Chi Tiết
              </button>
            </li>
            <li class="nav-item">
              <button class="nav-link rounded-pill px-4 py-2 fw-semibold" id="ingredients-tab" data-bs-toggle="pill" data-bs-target="#ingredients" type="button" role="tab">
                <i class="bi bi-droplet-half me-1"></i> Thành Phần Hoạt Chất
              </button>
            </li>
            <li class="nav-item">
              <button class="nav-link rounded-pill px-4 py-2 fw-semibold" id="usage-tab" data-bs-toggle="pill" data-bs-target="#usage" type="button" role="tab">
                <i class="bi bi-check-circle me-1"></i> Hướng Dẫn Sử Dụng
              </button>
            </li>
            <li class="nav-item">
              <button class="nav-link rounded-pill px-4 py-2 fw-semibold" id="reviews-tab" data-bs-toggle="pill" data-bs-target="#reviews" type="button" role="tab">
                <i class="bi bi-star-fill me-1 text-warning"></i> Đánh Giá Từ Khách Hàng (<?= $product['rating_count'] ?>)
              </button>
            </li>
          </ul>

          <div class="tab-content" id="productTabContent">
            
            <!-- Tab 1: Mô tả chi tiết -->
            <div class="tab-pane fade show active" id="desc" role="tabpanel">
              <h5 class="fw-bold mb-3" style="color: #4a2835;">Thông tin sản phẩm <?= htmlspecialchars($product['product_name']) ?></h5>
              <div class="text-secondary" style="line-height: 1.8; font-size: 1rem;">
                <?= nl2br(htmlspecialchars($product['description'])) ?>
              </div>
            </div>

            <!-- Tab 2: Thành phần -->
            <div class="tab-pane fade" id="ingredients" role="tabpanel">
              <h5 class="fw-bold mb-3" style="color: #4a2835;">Bảng thành phần công thức</h5>
              <div class="p-3 rounded-3 bg-light border mb-4">
                <strong>Danh sách thành phần đầy đủ:</strong><br>
                <span class="text-muted small"><?= htmlspecialchars($product['ingredients_text'] ?: 'Aqua, Glycerin, Butylene Glycol, Caprylic/Capric Triglyceride, Phenoxyethanol, Ethylhexylglycerin.') ?></span>
              </div>

              <?php if (!empty($product_ingredients)): ?>
                <h6 class="fw-bold mb-3 text-dark">Các hoạt chất nổi bật trong sản phẩm:</h6>
                <div class="row g-3">
                  <?php foreach ($product_ingredients as $ing): ?>
                    <div class="col-md-6">
                      <div class="p-3 rounded-4 border bg-white shadow-xs h-100">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                          <strong class="text-danger"><?= htmlspecialchars($ing['name']) ?></strong>
                          <span class="badge bg-success-subtle text-success rounded-pill"><?= htmlspecialchars($ing['safety_rating']) ?></span>
                        </div>
                        <p class="small text-muted mb-0"><?= htmlspecialchars($ing['benefit_description']) ?></p>
                      </div>
                    </div>
                  <?php endforeach; ?>
                </div>
              <?php endif; ?>
            </div>

            <!-- Tab 3: Hướng dẫn sử dụng -->
            <div class="tab-pane fade" id="usage" role="tabpanel">
              <h5 class="fw-bold mb-3" style="color: #4a2835;">Quy trình sử dụng hiệu quả</h5>
              <div class="text-secondary mb-4" style="line-height: 1.8;">
                <?= nl2br(htmlspecialchars($product['usage_instructions'] ?: "Sau bước làm sạch da mặt, lấy một lượng vừa đủ thoa đều khắp mặt và cổ. Vỗ nhẹ nhàng để dưỡng chất thẩm thấu sâu. Sử dụng đều đặn mỗi ngày sáng và tối.")) ?>
              </div>
              <div class="alert alert-info border-0 rounded-4" style="background:#f4f9fd;">
                <i class="bi bi-lightbulb-fill text-warning me-2"></i>
                <strong>Lời khuyên từ chuyên viên:</strong> Nên bảo quản sản phẩm ở nơi khô ráo, tránh ánh nắng trực tiếp hoặc nhiệt độ cao để duy trì hoạt tính tối đa của các dưỡng chất.
              </div>
            </div>

            <!-- Tab 4: Đánh giá & nhận xét -->
            <div class="tab-pane fade" id="reviews" role="tabpanel">
              <?php 
                // Nhúng module đánh giá reviews.php
                include __DIR__ . '/reviews.php'; 
              ?>
            </div>

          </div>

        </div>
      </div>
    </div>

    <!-- SẢN PHẨM CÙNG DANH MỤC LIÊN QUAN -->
    <?php if (!empty($related_products)): ?>
      <div class="mt-5">
        <h4 class="fw-bold mb-4" style="color: #4a2835;">Sản Phẩm Cùng Danh Mục Được Yêu Thích</h4>
        <div class="row row-cols-2 row-cols-md-4 g-4">
          <?php foreach ($related_products as $rel): ?>
            <div class="col">
              <div class="beauty-card h-100 d-flex flex-column p-3">
                <a href="product_detail.php?id=<?= $rel['product_id'] ?>" class="text-decoration-none flex-grow-1">
                  <div class="img-box rounded-3 mb-3">
                    <img src="../images/products/<?= htmlspecialchars($rel['image']) ?>" alt="<?= htmlspecialchars($rel['product_name']) ?>" loading="lazy">
                  </div>
                  <div class="text-muted small mb-1"><?= htmlspecialchars($rel['brand_name'] ?? 'Chính hãng') ?></div>
                  <h6 class="text-dark fw-bold line-clamp-2 mb-2" title="<?= htmlspecialchars($rel['product_name']) ?>">
                    <?= htmlspecialchars($rel['product_name']) ?>
                  </h6>
                  <div class="d-flex align-items-baseline gap-2 mb-3">
                    <span class="fw-bold text-danger fs-5"><?= format_price($rel['price']) ?></span>
                  </div>
                </a>
                <a href="product_detail.php?id=<?= $rel['product_id'] ?>" class="btn btn-outline-danger btn-sm rounded-pill py-2 w-100">
                  Xem chi tiết
                </a>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endif; ?>

  </div>
</div>

<script>
function changeQty(delta) {
  const input = document.getElementById('detailQty');
  let val = parseInt(input.value) || 1;
  const max = parseInt(input.getAttribute('max')) || 999;
  val = Math.max(1, Math.min(max, val + delta));
  input.value = val;
}

function handleAddToCart() {
  const qty = parseInt(document.getElementById('detailQty').value) || 1;
  addToCart(<?= $product_id ?>, qty);
}

function handleBuyNow() {
  const qty = parseInt(document.getElementById('detailQty').value) || 1;
  // Thêm vào giỏ trước rồi chuyển ngay sang checkout
  fetch('add_to_cart.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: `product_id=<?= $product_id ?>&quantity=${qty}`
  })
  .then(res => res.json())
  .then(data => {
    if (data.success) {
      window.location.href = 'checkout.php';
    } else {
      alert(data.message || 'Không thể đặt mua');
    }
  })
  .catch(err => {
    window.location.href = 'cart.php';
  });
}

function toggleWishlist(productId) {
  fetch('api_wishlist.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ product_id: productId })
  })
  .then(r => r.json())
  .then(data => {
    if (data.success) {
      const btn = document.getElementById('btnWishlist');
      if (data.action === 'added') {
        btn.classList.add('active', 'text-danger');
        btn.querySelector('i').className = 'bi bi-heart-fill fs-5';
        showNotification('Đã thêm vào danh sách yêu thích!', 'success');
      } else {
        btn.classList.remove('active', 'text-danger');
        btn.querySelector('i').className = 'bi bi-heart fs-5';
        showNotification('Đã xóa khỏi danh sách yêu thích!', 'info');
      }
    } else {
      if (data.require_login) {
        window.location.href = 'login.php';
      } else {
        alert(data.message);
      }
    }
  });
}
</script>

<?php include 'footer.php'; ?>
