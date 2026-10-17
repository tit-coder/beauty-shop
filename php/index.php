<?php
session_start();
include 'config.php'; // File kết nối CSDL bạn đã có từ trước

// Kiểm tra người dùng đã đăng nhập chưa
$is_logged_in = isset($_SESSION['user_id']);
$username = $is_logged_in ? $_SESSION['username'] : '';
$role = $is_logged_in ? $_SESSION['role'] : '';

// Xử lý đăng xuất
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: index.php");
    exit;
}

// Xử lý tìm kiếm
$search_query = isset($_GET['q']) ? trim($_GET['q']) : '';
$is_searching = !empty($search_query);
$search_results = [];
// PHÂN TRANG
$limit = 15; // số sản phẩm mỗi trang
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $limit;


if ($is_searching) {
    // Tìm kiếm sản phẩm theo tên hoặc mô tả
    $stmt = $pdo->prepare("SELECT p.*, c.category_name 
                          FROM products p 
                          LEFT JOIN category c ON p.category_id = c.category_id 
                          WHERE p.product_name LIKE ? OR p.description LIKE ?
                          ORDER BY p.product_name ASC");
    $search_param = "%$search_query%";
    $stmt->execute([$search_param, $search_param]);
    $search_results = $stmt->fetchAll();
}
$page_title = 'Beauty Cosmetics - Mỹ phẩm chính hãng';
include 'header.php';
?>

<!-- Khoảng trống bù cho header -->
<div style="height: 135px;"></div>
    
    <section class="py-4" style="background-image: url('../images/background-pattern.jpg');background-repeat: no-repeat;background-size: cover; padding-top: 2rem !important;">
      <div class="container-fluid">
        <div class="row">
          <div class="col-md-12">

            <!-- BANNER SLIDER TỰ ĐỘNG - CHẠY SAU 3 GIÂY -->
<div class="banner-slider-wrapper position-relative" style="margin-top: 10px;">
  <div class="swiper banner-swiper">
    <div class="swiper-wrapper">
      
      <!-- Slide 1 -->
      <div class="swiper-slide">
        <div class="banner-slide-item rounded-4 overflow-hidden shadow">
          <img src="../images/baner-beplain.jpg" class="w-100" alt="Beauty Cosmetics" style="height: 480px; object-fit: cover;">
        </div>
      </div>

      <!-- Slide 2 -->
      <div class="swiper-slide">
        <div class="banner-slide-item rounded-4 overflow-hidden shadow">
          <img src="../images/baner-lore.jpg" class="w-100" alt="La Roche-Posay" style="height: 480px; object-fit: cover;">
        </div>
      </div>

      <!-- Slide 3 -->
      <div class="swiper-slide">
        <div class="banner-slide-item rounded-4 overflow-hidden shadow">
          <img src="../images/baner-son.avif" class="w-100" alt="Beauty Collection" style="height: 480px; object-fit: cover;">
        </div>
      </div>

    </div>
    
    <!-- Nút điều hướng -->
    <div class="swiper-button-next"></div>
    <div class="swiper-button-prev"></div>
    
    <!-- Pagination dots -->
    <div class="swiper-pagination"></div>
  </div>
</div>

            </div>
            <!-- / Banner Slider -->
              
          </div>
        </div>
      </div>
    </section>

    <!-- DANH MỤC SẢN PHẨM -->
    <section id="category-section" class="py-5 overflow-hidden" style="background: linear-gradient(180deg, #fff 0%, #fffaf5 100%); margin-top: 0; scroll-margin-top: 150px;">
      <div class="container-fluid">
        <div class="text-center mb-5">
          <h2 class="fw-bold mb-2" style="color:#ff1493; font-size:2rem;">Danh mục sản phẩm</h2>
          <p class="text-muted">Khám phá các dòng mỹ phẩm chính hãng</p>
        </div>

        <div class="row g-4" id="category-list">
      <?php
      try {
          $stmt = $pdo->query("SELECT category_id, category_name FROM category ORDER BY category_name");
          while ($cat = $stmt->fetch()) {
              $active = (isset($_GET['cat']) && $_GET['cat'] == $cat['category_id']) ? 'active' : '';
      ?>
      <div class="col-6 col-md-4 col-lg-3">
        <a href="category.php?cat=<?= $cat['category_id'] ?>" 
           class="category-item text-center d-block p-4 bg-white rounded-4 text-decoration-none border
           <?= (isset($_GET['cat']) && $_GET['cat'] == $cat['category_id']) ? 'border-primary' : 'border-light' ?>"
           style="box-shadow: 0 4px 15px rgba(0,0,0,0.08);">
          <div class="mb-3 d-flex align-items-center justify-content-center" style="height:70px;">
            <img src="../images/cosmetics.png" alt="<?= htmlspecialchars($cat['category_name']) ?>" style="height:60px; filter: drop-shadow(0 4px 8px rgba(255,105,180,0.3));">
          </div>
          <h3 class="fs-6 fw-bold mb-0" style="color:#333;"><?= htmlspecialchars($cat['category_name']) ?></h3>
        </a>
      </div>
      <?php
          }
      } catch(Exception $e) {
          echo "<p class='text-danger text-center'>Lỗi tải danh mục!</p>";
      }
      ?>
    </div>
  </div>
</section>

          </div>
        </div>
      </div>
    </section>


   <!-- PHẦN SẢN PHẨM CHUYÊN NGHIỆP -->
<section id="products-section" class="py-5" style="background: linear-gradient(180deg, #fffaf5 0%, #fff 100%); scroll-margin-top: 150px;">
  <div class="container-fluid">
    <div class="row">
      <div class="col-12 text-center mb-5">
       <?php if ($is_searching): ?>
         <h2 class="fw-bold mb-2" style="color:#ff1493; font-size:2rem;">
           <i class="bi bi-search"></i> Kết quả tìm kiếm
         </h2>
         <p class="text-muted">
           Tìm thấy <strong class="text-danger"><?= count($search_results) ?></strong> sản phẩm 
           cho từ khóa "<strong><?= htmlspecialchars($search_query) ?></strong>"
         </p>
         <a href="index.php" class="btn btn-outline-secondary btn-sm">
           <i class="bi bi-x-circle"></i> Xóa tìm kiếm
         </a>
       <?php else: ?>
         <h2 class="fw-bold mb-2" style="color:#ff1493; font-size:2rem;">Tất cả sản phẩm</h2>
         <p class="text-muted">Mỹ phẩm chính hãng 100% - Giá tốt nhất thị trường</p>
       <?php endif; ?>
      </div>
    </div>

    <div class="row row-cols-2 row-cols-md-3 row-cols-lg-4 row-cols-xl-5 g-3 g-lg-4">

      <?php
      try {
          // Nếu đang tìm kiếm, dùng kết quả tìm kiếm, nếu không thì lấy tất cả
          if ($is_searching) {
              // Đếm tổng kết quả tìm kiếm
$count_stmt = $pdo->prepare("SELECT COUNT(*) 
                             FROM products 
                             WHERE product_name LIKE ? OR description LIKE ?");
$count_stmt->execute([$search_param, $search_param]);
$total_products = $count_stmt->fetchColumn();
$total_pages = ceil($total_products / $limit);

// Lấy sản phẩm theo trang
$stmt = $pdo->prepare("SELECT p.*, c.category_name 
                      FROM products p 
                      LEFT JOIN category c ON p.category_id = c.category_id 
                      WHERE p.product_name LIKE ? OR p.description LIKE ?
                      ORDER BY p.product_name ASC
                      LIMIT ? OFFSET ?");

$stmt->execute([$search_param, $search_param, $limit, $offset]);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

          } else {
              // Đếm tổng sản phẩm
$total_stmt = $pdo->query("SELECT COUNT(*) FROM products");
$total_products = $total_stmt->fetchColumn();
$total_pages = ceil($total_products / $limit);

// Lấy sản phẩm theo trang
$stmt = $pdo->prepare("SELECT p.*, c.category_name 
                      FROM products p 
                      LEFT JOIN category c ON p.category_id = c.category_id 
                      ORDER BY p.product_id DESC
                      LIMIT :limit OFFSET :offset");

$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();

$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

          }

          if (count($products) > 0) {
              foreach ($products as $p) {
              $image = !empty($p['image']) ? '../images/products/' . htmlspecialchars($p['image']) : '../images/no-image.jpg';
              $rating = $p['rating'] ?? 4.8;
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
                <button class="btn-add-to-cart btn btn-sm rounded-pill shadow-sm"
                        onclick="addToCart(<?= $p['product_id'] ?>)"
                        style="background: linear-gradient(135deg, #ff69b4, #ff1493); color: white; font-weight: 600; padding: 10px;">
                  <i class="fas fa-cart-plus me-2"></i>Thêm vào giỏ
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
          } else {
              // Không có sản phẩm
              echo '<div class="col-12 text-center py-5">';
              echo '<i class="bi bi-search" style="font-size: 4rem; color: #ddd;"></i>';
              echo '<h4 class="mt-3">Không tìm thấy sản phẩm nào</h4>';
              echo '<p class="text-muted">Không có sản phẩm nào phù hợp với từ khóa "<strong>' . htmlspecialchars($search_query) . '</strong>"</p>';
              echo '<a href="index.php" class="btn btn-primary mt-3"><i class="bi bi-house"></i> Về trang chủ</a>';
              echo '</div>';
          }
      } catch (Exception $e) {
          echo '<div class="col-12 text-danger text-center py-5">Lỗi tải sản phẩm!</div>';
      }
      ?>

    </div>
  </div>
  <?php if ($total_pages > 1): ?>
<div class="d-flex justify-content-center mt-5">
  <nav>
    <ul class="pagination pagination-lg">

      <!-- Trang trước -->
      <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
        <a class="page-link" 
           href="?<?= $is_searching ? 'q='.urlencode($search_query).'&' : '' ?>page=<?= $page - 1 ?>">
          &laquo;
        </a>
      </li>

      <!-- Các số trang -->
      <?php for ($i = 1; $i <= $total_pages; $i++): ?>
        <li class="page-item <?= $i == $page ? 'active' : '' ?>">
          <a class="page-link" 
             href="?<?= $is_searching ? 'q='.urlencode($search_query).'&' : '' ?>page=<?= $i ?>">
            <?= $i ?>
          </a>
        </li>
      <?php endfor; ?>

      <!-- Trang sau -->
      <li class="page-item <?= $page >= $total_pages ? 'disabled' : '' ?>">
        <a class="page-link" 
           href="?<?= $is_searching ? 'q='.urlencode($search_query).'&' : '' ?>page=<?= $page + 1 ?>">
          &raquo;
        </a>
      </li>

    </ul>
  </nav>
</div>
<?php endif; ?>

</section>
      

    

<?php include 'footer.php'; ?>

<!-- Banner Slider Script -->
<script>
  // Khởi tạo Banner Swiper - Tự động chạy sau 3 giây
  const bannerSwiper = new Swiper('.banner-swiper', {
    loop: true,
    autoplay: {
      delay: 3000,
      disableOnInteraction: false,
    },
    speed: 800,
    effect: 'slide',
    pagination: {
      el: '.swiper-pagination',
      clickable: true,
    },
    navigation: {
      nextEl: '.swiper-button-next',
      prevEl: '.swiper-button-prev',
    },
  });

  // Auto scroll đến phần sản phẩm khi có kết quả tìm kiếm
  <?php if ($is_searching): ?>
  window.addEventListener('load', function() {
    setTimeout(function() {
      const productsSection = document.getElementById('products-section');
      if (productsSection) {
        productsSection.scrollIntoView({ 
          behavior: 'smooth', 
          block: 'start' 
        });
      }
    }, 300); // Delay 300ms để trang load xong
  });
  <?php endif; ?>

  // Smooth scroll với offset cho các anchor links
  document.addEventListener('DOMContentLoaded', function() {
    // Lấy tất cả các link có href bắt đầu bằng #
    const anchorLinks = document.querySelectorAll('a[href^="#"]');
    
    anchorLinks.forEach(link => {
      link.addEventListener('click', function(e) {
        const targetId = this.getAttribute('href');
        
        // Bỏ qua nếu chỉ là #
        if (targetId === '#') return;
        
        const targetElement = document.querySelector(targetId);
        
        if (targetElement) {
          e.preventDefault();
          
          // Offset để tránh bị che bởi header (header cao khoảng 135px)
          const headerOffset = 145;
          const elementPosition = targetElement.getBoundingClientRect().top;
          const offsetPosition = elementPosition + window.pageYOffset - headerOffset;
          
          window.scrollTo({
            top: offsetPosition,
            behavior: 'smooth'
          });
        }
      });
    });
  });
</script>
<script>
  // Tự cuộn xuống sản phẩm khi đổi trang
  if (new URLSearchParams(window.location.search).has('page')) {
    window.addEventListener('load', function() {
      setTimeout(() => {
        const products = document.getElementById('products-section');
        if (products) {
          products.scrollIntoView({ behavior: 'smooth' });
        }
      }, 300);
    });
  }
</script>
