<?php
session_start();

// Xử lý đăng xuất
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: index.php");
    exit;
}

include 'config.php'; // File kết nối CSDL của bạn

// Lấy ID danh mục
$cat_id = isset($_GET['cat']) ? (int)$_GET['cat'] : 0;

// Lấy tên danh mục để hiển thị
$cat_name = "Tất cả sản phẩm";
if ($cat_id > 0) {
    $stmt = $pdo->prepare("SELECT category_name FROM category WHERE category_id = ?");
    $stmt->execute([$cat_id]);
    $cat = $stmt->fetch();
    if ($cat) {
        $cat_name = htmlspecialchars($cat['category_name']);
    }
}

// Kiểm tra đăng nhập (giống index.php)
$is_logged_in = isset($_SESSION['user_id']);
$username = $is_logged_in ? $_SESSION['username'] : '';
$role = $is_logged_in ? $_SESSION['role'] : '';

// Set page title cho header
$page_title = $cat_name . ' - Beauty Cosmetics';

// Include header
include 'header.php';
?>

<!-- CSS tùy chỉnh cho trang danh mục -->
<style>
  body { padding-top: 160px; }
  .nav-link:hover { opacity: 0.8; }
  
  /* Product Card Hover Effects */
  .product-card:hover {
    transform: translateY(-8px);
    border-color: #ff69b4 !important;
    box-shadow: 0 12px 35px rgba(255, 105, 180, 0.3) !important;
  }
  
  .product-card:hover .product-img-hover {
    transform: scale(1.08);
  }
  
  .hover-primary:hover {
    color: #ff1493 !important;
  }
  
  /* Responsive adjustments */
  @media (max-width: 768px) {
    body { padding-top: 180px; }
    .product-card img { height: 200px !important; }
  }
</style>

<!-- Nội dung danh mục -->
<section class="py-5" style="background: linear-gradient(135deg, #fff5f8 0%, #ffe4f0 100%);">
  <div class="container-fluid">
    <!-- Nút quay lại và tiêu đề -->
    <div class="row mb-4">
      <div class="col-12">
        <div class="d-flex justify-content-between align-items-center">
          <a href="index.php" class="btn btn-outline-danger rounded-pill px-4 py-2 shadow-sm">
            <i class="bi bi-arrow-left me-2"></i>Quay lại trang chủ
          </a>
          <h2 class="fw-bold mb-0">
            <span style="background: linear-gradient(135deg, #ff1493, #ff69b4); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">
              <?= $cat_name ?>
            </span>
          </h2>
          <div style="width: 180px;"></div> <!-- Spacer để căn giữa tiêu đề -->
        </div>
      </div>
    </div>

    <div class="row row-cols-2 row-cols-md-3 row-cols-lg-4 row-cols-xl-5 g-4">

      <?php
      try {
          if ($cat_id > 0) {
              $stmt = $pdo->prepare("SELECT p.*, c.category_name FROM products p LEFT JOIN category c ON p.category_id = c.category_id WHERE p.category_id = ? ORDER BY p.product_id DESC");
              $stmt->execute([$cat_id]);
          } else {
              $stmt = $pdo->query("SELECT p.*, c.category_name FROM products p LEFT JOIN category c ON p.category_id = c.category_id ORDER BY p.product_id DESC");
          }

          $found = false;
          while ($p = $stmt->fetch(PDO::FETCH_ASSOC)) {
              $found = true;
              $image = !empty($p['image']) ? '../images/products/' . htmlspecialchars($p['image']) : '../images/no-image.jpg';
      ?>
      <div class="col">
        <div class="product-card bg-white rounded-4 overflow-hidden shadow h-100 d-flex flex-column position-relative" 
             style="transition: all 0.3s ease; border: 2px solid transparent;">
          
          <!-- Badge danh mục -->
          <div class="position-absolute top-0 start-0 m-2 z-1">
            <span class="badge rounded-pill px-3 py-2" 
                  style="background: linear-gradient(135deg, #ff69b4, #ff1493); font-size: 0.7rem;">
              <?= htmlspecialchars($p['category_name'] ?? 'Sản phẩm') ?>
            </span>
          </div>

          <!-- Hình ảnh sản phẩm -->
          <a href="product_detail.php?id=<?= $p['product_id'] ?>" class="d-block overflow-hidden position-relative">
            <img src="<?= $image ?>" class="w-100 product-img-hover" 
                 alt="<?= htmlspecialchars($p['product_name']) ?>" 
                 style="height: 280px; object-fit: cover; transition: transform 0.4s ease;">
            <div class="position-absolute bottom-0 start-0 end-0 p-2 text-center" 
                 style="background: linear-gradient(to top, rgba(0,0,0,0.6), transparent);">
              <span class="badge bg-light text-dark px-3 py-1">
                <i class="bi bi-eye me-1"></i>Xem nhanh
              </span>
            </div>
          </a>

          <!-- Thông tin sản phẩm -->
          <div class="p-3 flex-grow-1 d-flex flex-column">
            <h3 class="fs-6 fw-semibold text-dark mb-2 line-clamp-2" style="min-height: 40px;">
              <a href="product_detail.php?id=<?= $p['product_id'] ?>" 
                 class="text-decoration-none text-dark hover-primary">
                <?= htmlspecialchars($p['product_name']) ?>
              </a>
            </h3>

            <!-- Giá -->
            <div class="mb-3">
              <div class="d-flex align-items-baseline gap-2">
                <span class="fw-bold fs-4" style="color: #ff1493;">
                  <?= number_format($p['price']) ?>₫
                </span>
              </div>
              <small class="text-muted">
                <i class="bi bi-box-seam me-1"></i>Còn <?= $p['quantity'] ?? 0 ?> sản phẩm
              </small>
            </div>

            <!-- Nút hành động -->
            <div class="mt-auto">
              <div class="d-grid gap-2">
                <button onclick="addToCart(<?= $p['product_id'] ?>, 1)" 
                        class="btn btn-sm rounded-pill shadow-sm"
                        style="background: linear-gradient(135deg, #ff69b4, #ff1493); color: white; font-weight: 600; padding: 10px;">
                  <i class="bi bi-cart-plus me-2"></i>Thêm vào giỏ
                </button>
                <a href="product_detail.php?id=<?= $p['product_id'] ?>" 
                   class="btn btn-outline-secondary btn-sm rounded-pill">
                  <i class="bi bi-info-circle me-1"></i>Chi tiết
                </a>
              </div>
            </div>
          </div>
        </div>
      </div>
      <?php
          }

          if (!$found) {
              echo '<div class="col-12 text-center py-5">
                      <div class="p-5 bg-white rounded-4 shadow-sm">
                        <i class="bi bi-inbox fs-1 text-muted mb-3 d-block"></i>
                        <h5 class="text-muted">Chưa có sản phẩm nào trong danh mục này.</h5>
                        <a href="index.php" class="btn btn-primary mt-3 rounded-pill px-4">
                          <i class="bi bi-arrow-left me-2"></i>Quay lại trang chủ
                        </a>
                      </div>
                    </div>';
          }

      } catch (Exception $e) {
          echo '<div class="col-12 text-danger text-center py-5">
                  <div class="alert alert-danger">
                    <i class="bi bi-exclamation-triangle me-2"></i>Lỗi tải sản phẩm!
                  </div>
                </div>';
      }
      ?>

    </div>
  </div>
</section>

<?php include 'footer.php'; ?>