<?php
session_start();

// Xử lý đăng xuất
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: index.php");
    exit;
}

// Bật hiển thị lỗi để debug
error_reporting(E_ALL);
ini_set('display_errors', 1);

include 'config.php';

// Kiểm tra đăng nhập
$is_logged_in = isset($_SESSION['user_id']);
$username = $is_logged_in ? $_SESSION['username'] : '';
$role = $is_logged_in ? $_SESSION['role'] : '';

// Lấy ID sản phẩm
$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($product_id <= 0) {
    $_SESSION['error'] = 'ID sản phẩm không hợp lệ';
    header("Location: index.php");
    exit;
}

try {
    // Lấy thông tin sản phẩm
    $stmt = $pdo->prepare("
        SELECT p.*, c.category_name 
        FROM products p 
        LEFT JOIN category c ON p.category_id = c.category_id 
        WHERE p.product_id = ?
    ");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        $_SESSION['error'] = 'Không tìm thấy sản phẩm';
        header("Location: index.php");
        exit;
    }

    // Lấy sản phẩm liên quan (cùng danh mục)
    $related_stmt = $pdo->prepare("
        SELECT p.*, c.category_name 
        FROM products p 
        LEFT JOIN category c ON p.category_id = c.category_id 
        WHERE p.category_id = ? AND p.product_id != ? 
        LIMIT 4
    ");
    $related_stmt->execute([$product['category_id'], $product_id]);
    $related_products = $related_stmt->fetchAll(PDO::FETCH_ASSOC);

    $image = !empty($product['image']) ? '../images/products/' . htmlspecialchars($product['image']) : '../images/no-image.jpg';
    
} catch (PDOException $e) {
    $_SESSION['error'] = 'Lỗi database: ' . $e->getMessage();
    header("Location: index.php");
    exit;
}

// Set page title cho header
$page_title = htmlspecialchars($product['product_name']) . ' - Beauty Cosmetics';

// Include header
include 'header.php';
?>

<!-- CSS tùy chỉnh cho trang chi tiết sản phẩm -->
<style>
  body { padding-top: 160px; }
  .product-detail-img { 
    width: 100%; 
    height: 500px; 
    object-fit: cover; 
    border-radius: 15px;
    box-shadow: 0 8px 30px rgba(0,0,0,0.1);
  }
  .price-tag {
    font-size: 2.5rem;
    font-weight: 700;
    color: #ff1493;
  }
  .quantity-selector {
    display: inline-flex;
    align-items: center;
    border: 2px solid #ffe4f4;
    border-radius: 50px;
    overflow: hidden;
  }
  .quantity-selector button {
    width: 45px;
    height: 45px;
    border: none;
    background: #fff5f8;
    font-size: 1.3rem;
    font-weight: bold;
    cursor: pointer;
    transition: all 0.3s;
  }
  .quantity-selector button:hover {
    background: #ff69b4;
    color: white;
  }
  .quantity-selector input {
    width: 70px;
    height: 45px;
    border: none;
    text-align: center;
    font-size: 1.2rem;
    font-weight: bold;
  }
  @media (max-width: 768px) {
    body { padding-top: 180px; }
    .product-detail-img { height: 350px; }
    .price-tag { font-size: 2rem; }
  }
</style>

<!-- CHI TIẾT SẢN PHẨM -->
<section class="py-5" style="background: linear-gradient(135deg, #fff5f8 0%, #ffe4f0 100%);">
  <div class="container">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-4">
      <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none" style="color:#ff1493;">Trang chủ</a></li>
        <li class="breadcrumb-item"><a href="category.php?cat=<?= $product['category_id'] ?>" class="text-decoration-none" style="color:#ff1493;"><?= htmlspecialchars($product['category_name']) ?></a></li>
        <li class="breadcrumb-item active"><?= htmlspecialchars($product['product_name']) ?></li>
      </ol>
    </nav>

    <div class="row g-4">
      <!-- Hình ảnh sản phẩm -->
      <div class="col-lg-6">
        <div class="bg-white p-4 rounded-4 shadow">
          <img src="<?= $image ?>" alt="<?= htmlspecialchars($product['product_name']) ?>" class="product-detail-img">
        </div>
      </div>

      <!-- Thông tin sản phẩm -->
      <div class="col-lg-6">
        <div class="bg-white p-4 rounded-4 shadow h-100">
          <span class="badge rounded-pill px-3 py-2 mb-3" 
                style="background: linear-gradient(135deg, #ff69b4, #ff1493); font-size: 0.9rem;">
            <?= htmlspecialchars($product['category_name']) ?>
          </span>
          
          <h1 class="fw-bold mb-3" style="color:#333; font-size:2rem;">
            <?= htmlspecialchars($product['product_name']) ?>
          </h1>

          <div class="mb-4">
            <span class="price-tag"><?= number_format($product['price']) ?>₫</span>
          </div>

          <div class="mb-4">
            <p class="text-muted mb-2"><i class="bi bi-box-seam me-2"></i>Tình trạng: 
              <?php 
              $quantity = isset($product['quantity']) ? (int)$product['quantity'] : 0;
              if ($quantity > 0): 
              ?>
                <span class="text-success fw-bold">Còn <?= $quantity ?> sản phẩm</span>
              <?php else: ?>
                <span class="text-danger fw-bold">Hết hàng</span>
              <?php endif; ?>
            </p>
          </div>

          <?php if (!empty($product['description'])): ?>
          <div class="mb-4">
            <h5 class="fw-bold mb-3" style="color:#ff1493;">Mô tả sản phẩm</h5>
            <p class="text-muted" style="line-height:1.8;">
              <?= nl2br(htmlspecialchars($product['description'])) ?>
            </p>
          </div>
          <?php endif; ?>

          <!-- Chọn số lượng -->
          <div class="mb-4">
            <label class="fw-bold mb-2" style="color:#333;">Số lượng:</label>
            <div class="quantity-selector">
              <button type="button" onclick="decreaseQty()">−</button>
              <input type="number" id="quantity" value="1" min="1" max="<?= $quantity ?>" readonly>
              <button type="button" onclick="increaseQty()">+</button>
            </div>
          </div>

          <!-- Nút hành động -->
          <div class="d-grid gap-3">
            <?php if ($quantity > 0): ?>
            <button class="btn-add-to-cart btn btn-lg rounded-pill shadow" 
                    style="background: linear-gradient(135deg, #ff69b4, #ff1493); color: white; font-weight: 600; padding: 15px;"
                    onclick="addToCartWithQty(<?= $product_id ?>)">
              <i class="fas fa-cart-plus me-2"></i>Thêm vào giỏ hàng
            </button>
            <?php else: ?>
            <button class="btn btn-secondary btn-lg rounded-pill" disabled>
              <i class="bi bi-x-circle me-2"></i>Hết hàng
            </button>
            <?php endif; ?>
            
            <a href="index.php" class="btn btn-outline-secondary btn-lg rounded-pill">
              <i class="bi bi-arrow-left me-2"></i>Tiếp tục mua sắm
            </a>
          </div>
        </div>
      </div>
    </div>
<?php include 'reviews.php'; ?>


    <!-- Sản phẩm liên quan -->
    <?php if (count($related_products) > 0): ?>
    <div class="mt-5">
      <h3 class="fw-bold mb-4 text-center" style="color:#ff1493;">Sản phẩm liên quan</h3>
      <div class="row row-cols-2 row-cols-md-4 g-4">
        <?php foreach ($related_products as $rp): 
          $rp_image = !empty($rp['image']) ? '../images/products/' . htmlspecialchars($rp['image']) : '../images/no-image.jpg';
        ?>
        <div class="col">
          <div class="product-card bg-white rounded-4 overflow-hidden shadow h-100 d-flex flex-column" 
               style="transition: all 0.3s ease; border: 2px solid transparent;">
            <a href="product_detail.php?id=<?= $rp['product_id'] ?>" class="d-block overflow-hidden">
              <img src="<?= $rp_image ?>" class="w-100" 
                   alt="<?= htmlspecialchars($rp['product_name']) ?>" 
                   style="height: 250px; object-fit: cover;">
            </a>
            <div class="p-3 flex-grow-1 d-flex flex-column">
              <h5 class="fs-6 fw-semibold mb-2 line-clamp-2">
                <a href="product_detail.php?id=<?= $rp['product_id'] ?>" 
                   class="text-decoration-none text-dark">
                  <?= htmlspecialchars($rp['product_name']) ?>
                </a>
              </h5>
              <div class="mb-3">
                <span class="fw-bold fs-5" style="color: #ff1493;">
                  <?= number_format($rp['price']) ?>₫
                </span>
              </div>
              <div class="mt-auto">
                <a href="product_detail.php?id=<?= $rp['product_id'] ?>" 
                   class="btn btn-sm btn-outline-secondary rounded-pill w-100">
                  Xem chi tiết
                </a>
              </div>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>
  </div>
</section>

<!-- JavaScript tùy chỉnh cho trang chi tiết sản phẩm -->
<script>
function increaseQty() {
  const input = document.getElementById('quantity');
  const max = parseInt(input.max);
  if (parseInt(input.value) < max) {
    input.value = parseInt(input.value) + 1;
  }
}

function decreaseQty() {
  const input = document.getElementById('quantity');
  if (parseInt(input.value) > 1) {
    input.value = parseInt(input.value) - 1;
  }
}

function addToCartWithQty(productId) {
  const qty = document.getElementById('quantity').value;
  addToCart(productId, qty);
}
</script>

<?php include 'footer.php'; ?>
<script>
function increaseQty() {
  const input = document.getElementById('quantity');
  const max = parseInt(input.max);
  if (parseInt(input.value) < max) {
    input.value = parseInt(input.value) + 1;
  }
}

function decreaseQty() {
  const input = document.getElementById('quantity');
  if (parseInt(input.value) > 1) {
    input.value = parseInt(input.value) - 1;
  }
}

function addToCartWithQty(productId) {
  const qty = document.getElementById('quantity').value;
  addToCart(productId, qty);
}
</script>
