<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/config.php';

$is_logged_in = is_logged_in();
$username = $_SESSION['username'] ?? '';
$role = $_SESSION['role'] ?? '';
$user_id = $_SESSION['user_id'] ?? 0;

// Lấy số lượng giỏ hàng thực tế từ database
$cart_count = get_cart_count($pdo, $user_id);

// Lấy số lượng yêu thích
$wishlist_count = 0;
if ($is_logged_in) {
    try {
        $stmt_wl = $pdo->prepare("SELECT COUNT(*) FROM wishlists WHERE user_id = ?");
        $stmt_wl->execute([$user_id]);
        $wishlist_count = (int)$stmt_wl->fetchColumn();
    } catch (Exception $e) {
        $wishlist_count = 0;
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <title><?= isset($page_title) ? htmlspecialchars($page_title) : 'BeautyShop - Mỹ Phẩm Chính Hãng & Tư Vấn Da Thông Minh' ?></title>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Hệ thống bán mỹ phẩm chính hãng 100%, tích hợp AI Beauty Advisor tư vấn quy trình chăm sóc da chuẩn chuyên gia da liễu.">
  
  <!-- Favicon -->
  <link rel="icon" type="image/png" href="../images/cosmetics.png">

  <!-- Fonts & Icons -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,600;0,700;1,400&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@9/swiper-bundle.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="../css/cart_styles.css">

  <style>
    :root {
      --primary: #d47a88;
      --primary-dark: #9e4760;
      --primary-light: #fbebed;
      --accent-gold: #c59b27;
      --text-dark: #2c1820;
      --text-muted: #705860;
      --bg-light: #fffdfb;
      --border-soft: #f0e2e5;
    }

    body {
      font-family: 'Plus Jakarta Sans', -apple-system, sans-serif;
      color: var(--text-dark);
      background-color: var(--bg-light);
      overflow-x: hidden;
    }

    h1, h2, h3, .brand-font {
      font-family: 'Playfair Display', Georgia, serif;
    }

    /* Header Styling */
    .top-bar-announcement {
      background: linear-gradient(90deg, #4a2835 0%, #2f1722 100%);
      color: #f7dcd6;
      font-size: 0.8rem;
      letter-spacing: 0.5px;
    }

    .main-navbar-sticky {
      background: rgba(255, 255, 255, 0.98);
      backdrop-filter: blur(12px);
      box-shadow: 0 4px 20px rgba(74, 40, 53, 0.06);
      transition: all 0.3s ease;
    }

    .nav-link-custom {
      color: var(--text-dark) !important;
      font-weight: 600;
      font-size: 0.92rem;
      padding: 0.6rem 0.9rem !important;
      border-radius: 6px;
      transition: all 0.25s ease;
      display: inline-flex;
      align-items: center;
      gap: 5px;
    }

    .nav-link-custom:hover {
      color: var(--primary-dark) !important;
      background-color: var(--primary-light);
    }

    .ai-nav-pill {
      background: linear-gradient(135deg, #d47a88, #9e4760);
      color: white !important;
      border-radius: 20px !important;
      padding: 0.4rem 1rem !important;
      box-shadow: 0 4px 12px rgba(212, 122, 136, 0.35);
    }

    .ai-nav-pill:hover {
      transform: translateY(-2px);
      box-shadow: 0 6px 16px rgba(212, 122, 136, 0.45);
      background: linear-gradient(135deg, #c06574, #87364d) !important;
      color: white !important;
    }

    /* Product Card Luxury */
    .beauty-card {
      background: white;
      border: 1px solid var(--border-soft);
      border-radius: 14px;
      transition: all 0.35s cubic-bezier(0.165, 0.84, 0.44, 1);
      position: relative;
      overflow: hidden;
    }

    .beauty-card:hover {
      transform: translateY(-6px);
      border-color: #e5b3bd;
      box-shadow: 0 15px 35px rgba(158, 71, 96, 0.12);
    }

    .beauty-card .img-box {
      height: 230px;
      overflow: hidden;
      background: #faf7f5;
      display: flex;
      align-items: center;
      justify-content: center;
      position: relative;
    }

    .beauty-card .img-box img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      transition: transform 0.5s ease;
    }

    .beauty-card:hover .img-box img {
      transform: scale(1.08);
    }

    .discount-badge {
      position: absolute;
      top: 10px;
      left: 10px;
      background: #d4223d;
      color: white;
      font-size: 0.72rem;
      font-weight: 700;
      padding: 3px 8px;
      border-radius: 6px;
      z-index: 2;
    }

    .wishlist-heart-btn {
      position: absolute;
      top: 10px;
      right: 10px;
      width: 32px;
      height: 32px;
      background: white;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      color: #999;
      border: none;
      box-shadow: 0 2px 8px rgba(0,0,0,0.1);
      transition: all 0.2s;
      z-index: 2;
    }

    .wishlist-heart-btn:hover, .wishlist-heart-btn.active {
      color: #d47a88;
      transform: scale(1.15);
    }

    .line-clamp-2 {
      display: -webkit-box;
      -webkit-line-clamp: 2;
      -webkit-box-orient: vertical;
      overflow: hidden;
      min-height: 2.6em;
    }

    /* Cart Toast */
    .cart-notification {
      position: fixed;
      bottom: 25px;
      left: 25px;
      background: white;
      border-left: 4px solid #28a745;
      padding: 14px 20px;
      border-radius: 8px;
      box-shadow: 0 10px 30px rgba(0,0,0,0.15);
      display: flex;
      align-items: center;
      gap: 12px;
      z-index: 11000;
      transform: translateY(100px);
      opacity: 0;
      transition: all 0.3s ease;
    }
    .cart-notification.show {
      transform: translateY(0);
      opacity: 1;
    }
    .cart-notification.error {
      border-left-color: #dc3545;
    }
  </style>
</head>
<body>

<!-- Header Cố Định -->
<header class="fixed-top" style="z-index: 1030;">
  
  <!-- Thanh Thông Báo Khuyến Mãi Trên Cùng -->
  <div class="top-bar-announcement py-1 px-3 d-none d-md-block">
    <div class="container-fluid px-lg-5">
      <div class="d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center gap-3">
          <span><i class="bi bi-truck me-1"></i> Miễn phí vận chuyển cho đơn từ 150.000₫ (Mã: <strong>FREESHIP</strong>)</span>
          <span>•</span>
          <span><i class="bi bi-shield-check me-1"></i> Cam kết 100% chính hãng – Hoàn tiền gấp đôi nếu phát hiện hàng giả</span>
        </div>
        <div class="d-flex align-items-center gap-3">
          <a href="tel:19001234" class="text-decoration-none text-white-50 hover-white">
            <i class="bi bi-telephone me-1"></i> Hotline: 1900 1234
          </a>
          <span>•</span>
          <a href="routine_builder.php" class="text-decoration-none text-warning fw-semibold">
            <i class="bi bi-magic me-1"></i> Trắc nghiệm Routine da
          </a>
        </div>
      </div>
    </div>
  </div>

  <!-- Thanh Chính: Logo + Tìm Kiếm + Tài Khoản + Giỏ Hàng -->
  <div class="main-navbar-sticky py-2 px-3 border-bottom">
    <div class="container-fluid px-lg-5">
      <div class="row align-items-center">
        
        <!-- Logo Thương Hiệu -->
        <div class="col-6 col-lg-2">
          <a href="index.php" class="d-flex align-items-center text-decoration-none gap-2">
            <div class="rounded-circle d-flex align-items-center justify-content-center text-white"
                 style="width: 42px; height: 42px; background: linear-gradient(135deg, #d47a88, #9e4760);">
              <i class="bi bi-flower1 fs-4"></i>
            </div>
            <div>
              <span class="fs-4 fw-bold text-dark tracking-wide" style="font-family: 'Playfair Display', serif; letter-spacing: 0.5px;">BEAUTYSHOP</span>
              <div class="text-uppercase" style="font-size: 0.6rem; letter-spacing: 1.5px; color: #9e4760; font-weight: 600;">Authentic & AI</div>
            </div>
          </a>
        </div>

        <!-- Thanh Tìm Kiếm Trung Tâm -->
        <div class="col-12 col-lg-6 order-3 order-lg-2 mt-2 mt-lg-0">
          <form action="search.php" method="GET" class="position-relative">
            <div class="input-group">
              <input type="text" name="q" class="form-control rounded-pill px-4 border" 
                     placeholder="Tìm son môi, kem dưỡng B5, kem chống nắng, toner..."
                     value="<?= isset($_GET['q']) ? htmlspecialchars($_GET['q']) : '' ?>"
                     style="height: 44px; padding-right: 50px; border-color: #eedde0; font-size: 0.92rem; background: #fffdfb;">
              <button type="submit" class="btn position-absolute end-0 top-50 translate-middle-y me-1 rounded-circle d-flex align-items-center justify-content-center text-white border-0 shadow-xs"
                      style="width: 36px; height: 36px; background: linear-gradient(135deg, #d47a88, #9e4760); z-index: 5;">
                <i class="bi bi-search" style="font-size: 0.85rem;"></i>
              </button>
            </div>
          </form>
        </div>

        <!-- Cụm Biểu Tượng Tiện Ích: Yêu Thích + Giỏ Hàng + Tài Khoản -->
        <div class="col-6 col-lg-4 order-2 order-lg-3 d-flex justify-content-end align-items-center gap-3">
          
          <!-- Danh sách yêu thích -->
          <a href="wishlist.php" class="btn btn-light rounded-circle position-relative p-0 d-none d-sm-flex align-items-center justify-content-center border"
             style="width: 40px; height: 40px;" title="Sản phẩm yêu thích">
            <i class="bi bi-heart text-danger fs-5"></i>
            <?php if ($wishlist_count > 0): ?>
              <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size: 0.65rem;">
                <?= $wishlist_count ?>
              </span>
            <?php endif; ?>
          </a>

          <!-- Giỏ hàng -->
          <a href="cart.php" class="btn btn-light rounded-circle position-relative p-0 d-flex align-items-center justify-content-center border"
             style="width: 40px; height: 40px;" title="Giỏ hàng của bạn">
            <i class="bi bi-bag-heart-fill fs-5" style="color: #d47a88;"></i>
            <span class="cart-count position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" 
                  style="font-size: 0.65rem; display: <?= $cart_count > 0 ? 'inline-block' : 'none' ?>;">
              <?= $cart_count ?>
            </span>
          </a>

          <!-- Tài khoản người dùng -->
          <?php if ($is_logged_in): ?>
            <div class="dropdown">
              <a href="#" class="d-flex align-items-center text-decoration-none dropdown-toggle" data-bs-toggle="dropdown">
                <div class="rounded-circle d-flex align-items-center justify-content-center text-white"
                     style="width: 40px; height: 40px; background: linear-gradient(135deg, #d47a88, #9e4760); border: 2px solid white; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                  <i class="bi bi-person-fill fs-5"></i>
                </div>
                <div class="ms-2 d-none d-xl-block text-start">
                  <div class="text-muted" style="font-size: 0.72rem; line-height: 1;">Xin chào</div>
                  <div class="fw-bold text-truncate" style="max-width: 110px; font-size: 0.88rem; color: #9e4760;">
                    <?= htmlspecialchars($username) ?>
                  </div>
                </div>
              </a>
              <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0 rounded-4 mt-2 p-2" style="min-width: 210px;">
                <li class="px-3 py-2 border-bottom mb-1">
                  <div class="fw-bold" style="color: #4a2835;"><?= htmlspecialchars($username) ?></div>
                  <small class="badge bg-secondary"><?= $role === 'admin' ? 'Quản trị viên' : 'Khách hàng thân thiết' ?></small>
                </li>
                <?php if ($role === 'admin'): ?>
                  <li>
                    <a class="dropdown-item py-2 rounded-2 fw-semibold text-danger" href="admin_dashboard.php">
                      <i class="bi bi-speedometer2 me-2"></i> Trang Quản Trị Admin
                    </a>
                  </li>
                  <li><hr class="dropdown-divider"></li>
                <?php endif; ?>
                <li>
                  <a class="dropdown-item py-2 rounded-2" href="my_orders.php">
                    <i class="bi bi-bag-check me-2 text-danger"></i> Đơn hàng của tôi
                  </a>
                </li>
                <li>
                  <a class="dropdown-item py-2 rounded-2" href="wishlist.php">
                    <i class="bi bi-heart me-2 text-danger"></i> Danh sách yêu thích
                  </a>
                </li>
                <li>
                  <a class="dropdown-item py-2 rounded-2" href="profile.php">
                    <i class="bi bi-person-gear me-2 text-danger"></i> Tài khoản & Địa chỉ
                  </a>
                </li>
                <li><hr class="dropdown-divider"></li>
                <li>
                  <a class="dropdown-item py-2 rounded-2 text-danger" href="index.php?logout=1">
                    <i class="bi bi-box-arrow-right me-2"></i> Đăng xuất
                  </a>
                </li>
              </ul>
            </div>
          <?php else: ?>
            <div class="d-flex align-items-center gap-1">
              <a href="login.php" class="btn btn-outline-danger btn-sm rounded-pill px-3 py-1" style="border-color: #d47a88; color: #d47a88;">
                Đăng nhập
              </a>
              <a href="register.php" class="btn btn-danger btn-sm rounded-pill px-3 py-1 text-white d-none d-sm-inline-block" style="background: #d47a88; border-color: #d47a88;">
                Đăng ký
              </a>
            </div>
          <?php endif; ?>

        </div>
      </div>
    </div>
  </div>

  <!-- Thanh Menu Điều Hướng Chính -->
  <div class="border-bottom bg-white d-none d-lg-block">
    <div class="container-fluid px-lg-5">
      <nav class="d-flex align-items-center justify-content-between py-1">
        <ul class="nav gap-1 m-0 align-items-center">
          <li class="nav-item">
            <a href="index.php" class="nav-link-custom">
              <i class="bi bi-house"></i> Trang chủ
            </a>
          </li>
          <li class="nav-item">
            <a href="products.php" class="nav-link-custom">
              <i class="bi bi-grid"></i> Tất cả sản phẩm
            </a>
          </li>
          <li class="nav-item">
            <a href="index.php#category-section" class="nav-link-custom">
              <i class="bi bi-collection"></i> Danh mục
            </a>
          </li>
          <li class="nav-item">
            <a href="index.php#flash-sale-section" class="nav-link-custom text-danger">
              <i class="bi bi-lightning-charge-fill text-warning"></i> Flash Sale
            </a>
          </li>
          <li class="nav-item">
            <a href="routine_builder.php" class="nav-link-custom">
              <i class="bi bi-magic"></i> Routine Builder
            </a>
          </li>
          <li class="nav-item">
            <a href="compare.php" class="nav-link-custom">
              <i class="bi bi-columns-gap"></i> So sánh sản phẩm
            </a>
          </li>
          <li class="nav-item">
            <a href="my_orders.php" class="nav-link-custom">
              <i class="bi bi-truck"></i> Tra cứu đơn hàng
            </a>
          </li>
          <li class="nav-item">
            <a href="#lienhe" class="nav-link-custom">
              <i class="bi bi-geo-alt"></i> Cửa hàng
            </a>
          </li>
        </ul>

        <!-- Nút Trợ Lý AI Nổi Bật Trên Menu -->
        <div class="py-1">
          <a href="ai_advisor.php" class="ai-nav-pill text-decoration-none d-inline-flex align-items-center gap-2">
            <i class="bi bi-stars"></i>
            <span>AI Beauty Advisor</span>
            <span class="badge bg-white text-danger rounded-pill" style="font-size: 0.65rem;">HOT</span>
          </a>
        </div>
      </nav>
    </div>
  </div>

  <!-- Thanh Menu Cho Mobile -->
  <div class="d-lg-none bg-white border-bottom px-3 py-2 overflow-x-auto text-nowrap">
    <div class="d-flex gap-2">
      <a href="index.php" class="btn btn-sm btn-light rounded-pill px-3">Trang chủ</a>
      <a href="products.php" class="btn btn-sm btn-light rounded-pill px-3">Sản phẩm</a>
      <a href="ai_advisor.php" class="btn btn-sm btn-danger rounded-pill px-3 text-white" style="background:#d47a88; border:none;">
        <i class="bi bi-stars"></i> AI Advisor
      </a>
      <a href="index.php#flash-sale-section" class="btn btn-sm btn-outline-danger rounded-pill px-3">Flash Sale</a>
      <a href="routine_builder.php" class="btn btn-sm btn-light rounded-pill px-3">Routine</a>
      <a href="my_orders.php" class="btn btn-sm btn-light rounded-pill px-3">Đơn hàng</a>
    </div>
  </div>

</header>
