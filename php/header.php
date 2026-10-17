<!DOCTYPE html>
<html lang="vi">
<head>
  <title><?= isset($page_title) ? $page_title : 'Beauty Cosmetics - Mỹ phẩm chính hãng' ?></title>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@9/swiper-bundle.min.css">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" type="text/css" href="../css/vendor.css">
  <link rel="stylesheet" type="text/css" href="../css/style.css">
  <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;700&family=Open+Sans:wght@400;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="../css/cart_styles.css">
  <style>
    /* Line clamp for product names */
    .line-clamp-2 { 
      display: -webkit-box; 
      -webkit-line-clamp: 2; 
      -webkit-box-orient: vertical; 
      overflow: hidden; 
    }
    
    /* Product Card Hover Effects */
    .product-card:hover {
      transform: translateY(-8px);
      border-color: #ff0080ff !important;
      box-shadow: 0 12px 35px rgba(255, 105, 180, 0.3) !important;
    }
    
    .product-card:hover .product-img-hover {
      transform: scale(1.08);
    }
    
    .hover-primary:hover {
      color: #ff1493 !important;
    }
    
    /* Enhanced Dropdown Menu Styles */
    .dropdown-menu {
      border: none !important;
      border-radius: 12px !important;
      box-shadow: 0 15px 30px rgba(255, 105, 180, 0.15) !important;
      padding: 0.8rem !important;
      min-width: 220px !important;
      backdrop-filter: blur(15px) !important;
      background: rgba(255, 255, 255, 0.98) !important;
      border: 1px solid rgba(255, 105, 180, 0.1) !important;
      animation: dropdownFadeIn 0.3s ease-out !important;
    }
    
    @keyframes dropdownFadeIn {
      from {
        opacity: 0;
        transform: translateY(-10px) scale(0.95);
      }
      to {
        opacity: 1;
        transform: translateY(0) scale(1);
      }
    }
    
    .dropdown-item {
      border-radius: 8px !important;
      padding: 10px 14px !important;
      margin-bottom: 4px !important;
      transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275) !important;
      font-weight: 500 !important;
      font-size: 0.9rem !important;
      position: relative !important;
      overflow: hidden !important;
    }
    
    .dropdown-item::before {
      content: '';
      position: absolute;
      top: 0;
      left: -100%;
      width: 100%;
      height: 100%;
      background: linear-gradient(135deg, #ff69b4, #ff1493);
      transition: left 0.3s ease;
      z-index: -1;
    }
    
    .dropdown-item:hover::before {
      left: 0;
    }
    
    .dropdown-item:hover {
      color: white !important;
      transform: translateX(8px) scale(1.02) !important;
      box-shadow: 0 8px 20px rgba(255, 105, 180, 0.3) !important;
    }
    
    .dropdown-item i {
      width: 22px;
      text-align: center;
      font-size: 1.1rem;
      transition: transform 0.3s ease;
    }
    
    .dropdown-item:hover i {
      transform: scale(1.2);
    }
    
    .dropdown-divider {
      margin: 8px 0 !important;
      border: none !important;
      height: 1px !important;
      background: linear-gradient(90deg, transparent, rgba(255, 105, 180, 0.3), transparent) !important;
    }
    
    /* User Avatar Enhancement */
    .user-avatar {
      position: relative;
      overflow: hidden;
      transition: all 0.3s ease !important;
    }
    
    .user-avatar::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: linear-gradient(45deg, rgba(255,255,255,0.2), transparent);
      opacity: 0;
      transition: opacity 0.3s ease;
    }
    
    .user-avatar:hover::before {
      opacity: 1;
    }
    
    .user-avatar:hover {
      transform: scale(1.05) !important;
      box-shadow: 0 8px 20px rgba(255, 105, 180, 0.4) !important;
    }
    
    /* Welcome Text Enhancement */
    .welcome-text {
      background: linear-gradient(135deg, #ff1493, #ff69b4) !important;
      -webkit-background-clip: text !important;
      -webkit-text-fill-color: transparent !important;
      background-clip: text !important;
      font-weight: 700 !important;
    }
    
    /* Admin Badge */
    .admin-badge {
      background: linear-gradient(135deg, #dc3545, #ff6b6b) !important;
      color: white !important;
      border-radius: 8px !important;
      padding: 2px 8px !important;
      font-size: 0.7rem !important;
      font-weight: 600 !important;
      margin-left: 8px !important;
      text-transform: uppercase !important;
      letter-spacing: 0.5px !important;
    }
    
    /* Logout Button Special Style */
    .logout-item {
      background: linear-gradient(135deg, rgba(220, 53, 69, 0.1), rgba(255, 107, 107, 0.1)) !important;
      border: 1px solid rgba(220, 53, 69, 0.2) !important;
    }
    
    .logout-item::before {
      background: linear-gradient(135deg, #dc3545, #ff6b6b) !important;
    }
    
    /* Enhanced Cart Icon */
    .cart-icon a:hover {
      background: rgba(255, 105, 180, 0.1) !important;
      transform: scale(1.1) !important;
      box-shadow: 0 8px 20px rgba(255, 105, 180, 0.3) !important;
    }
    
    .cart-icon a:hover i {
      color: #fc1490ff !important;
      transform: scale(1.1) !important;
    }
    
    /* Search Button Enhancement */
    .search-btn {
      transition: all 0.3s ease !important;
    }
    
    .search-btn:hover {
      transform: scale(1.05) !important;
      box-shadow: 0 6px 15px rgba(255, 105, 180, 0.4) !important;
    }
    
    /* Responsive adjustments */
    @media (max-width: 768px) {
      .product-card img { height: 200px !important; }
      .dropdown-menu {
        min-width: 200px !important;
        padding: 0.7rem !important;
      }
      .dropdown-item {
        padding: 8px 12px !important;
        font-size: 0.85rem !important;
      }
    }
  </style>
</head>
<body>
    <svg xmlns="http://www.w3.org/2000/svg" style="display: none;">
      <defs>
        <symbol xmlns="http://www.w3.org/2000/svg" id="link" viewBox="0 0 24 24">
          <path fill="currentColor" d="M12 19a1 1 0 1 0-1-1a1 1 0 0 0 1 1Zm5 0a1 1 0 1 0-1-1a1 1 0 0 0 1 1Zm0-4a1 1 0 1 0-1-1a1 1 0 0 0 1 1Zm-5 0a1 1 0 1 0-1-1a1 1 0 0 0 1 1Zm7-12h-1V2a1 1 0 0 0-2 0v1H8V2a1 1 0 0 0-2 0v1H5a3 3 0 0 0-3 3v14a3 3 0 0 0 3 3h14a3 3 0 0 0 3-3V6a3 3 0 0 0-3-3Zm1 17a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1v-9h16Zm0-11H4V6a1 1 0 0 1 1-1h1v1a1 1 0 0 0 2 0V5h8v1a1 1 0 0 0 2 0V5h1a1 1 0 0 1 1 1ZM7 15a1 1 0 1 0-1-1a1 1 0 0 0 1 1Zm0 4a1 1 0 1 0-1-1a1 1 0 0 0 1 1Z"/>
        </symbol>
        <symbol xmlns="http://www.w3.org/2000/svg" id="arrow-right" viewBox="0 0 24 24">
          <path fill="currentColor" d="M17.92 11.62a1 1 0 0 0-.21-.33l-5-5a1 1 0 0 0-1.42 1.42l3.3 3.29H7a1 1 0 0 0 0 2h7.59l-3.3 3.29a1 1 0 0 0 0 1.42a1 1 0 0 0 1.42 0l5-5a1 1 0 0 0 .21-.33a1 1 0 0 0 0-.76Z"/>
        </symbol>
        <symbol xmlns="http://www.w3.org/2000/svg" id="category" viewBox="0 0 24 24">
          <path fill="currentColor" d="M19 5.5h-6.28l-.32-1a3 3 0 0 0-2.84-2H5a3 3 0 0 0-3 3v13a3 3 0 0 0 3 3h14a3 3 0 0 0 3-3v-10a3 3 0 0 0-3-3Zm1 13a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1v-13a1 1 0 0 1 1-1h4.56a1 1 0 0 1 .95.68l.54 1.64a1 1 0 0 0 .95.68h7a1 1 0 0 1 1 1Z"/>
        </symbol>
        <symbol xmlns="http://www.w3.org/2000/svg" id="calendar" viewBox="0 0 24 24">
          <path fill="currentColor" d="M19 4h-2V3a1 1 0 0 0-2 0v1H9V3a1 1 0 0 0-2 0v1H5a3 3 0 0 0-3 3v12a3 3 0 0 0 3 3h14a3 3 0 0 0 3-3V7a3 3 0 0 0-3-3Zm1 15a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1v-7h16Zm0-9H4V7a1 1 0 0 1 1-1h2v1a1 1 0 0 0 2 0V6h6v1a1 1 0 0 0 2 0V6h2a1 1 0 0 1 1 1Z"/>
        </symbol>
        <symbol xmlns="http://www.w3.org/2000/svg" id="logout" viewBox="0 0 24 24">
          <path fill="currentColor" d="M20.16 4.61A6.27 6.27 0 0 0 12 4a6.27 6.27 0 0 0-8.16 9.48l7.45 7.45a1 1 0 0 0 1.42 0l7.45-7.45a6.27 6.27 0 0 0 0-8.87Zm-1.41 7.46L12 18.81l-6.75-6.74a4.28 4.28 0 0 1 3-7.3a4.25 4.25 0 0 1 3 1.25a1 1 0 0 0 1.42 0a4.27 4.27 0 0 1 6 6.05Z"/>
        </symbol>
        <symbol xmlns="http://www.w3.org/2000/svg" id="plus" viewBox="0 0 24 24">
          <path fill="currentColor" d="M19 11h-6V5a1 1 0 0 0-2 0v6H5a1 1 0 0 0 0 2h6v6a1 1 0 0 0 2 0v-6h6a1 1 0 0 0 0-2Z"/>
        </symbol>
        <symbol xmlns="http://www.w3.org/2000/svg" id="minus" viewBox="0 0 24 24">
          <path fill="currentColor" d="M19 11H5a1 1 0 0 0 0 2h14a1 1 0 0 0 0-2Z"/>
        </symbol>
        <symbol xmlns="http://www.w3.org/2000/svg" id="cart" viewBox="0 0 24 24">
          <path fill="currentColor" d="M8.5 19a1.5 1.5 0 1 0 1.5 1.5A1.5 1.5 0 0 0 8.5 19ZM19 16H7a1 1 0 0 1 0-2h8.491a3.013 3.013 0 0 0 2.885-2.176l1.585-5.55A1 1 0 0 0 19 5H6.74a3.007 3.007 0 0 0-2.82-2H3a1 1 0 0 0 0 2h.921a1.005 1.005 0 0 1 .962.725l.155.545v.005l1.641 5.742A3 3 0 0 0 7 18h12a1 1 0 0 0 0-2Zm-1.326-9l-1.22 4.274a1.005 1.005 0 0 1-.963.726H8.754l-.255-.892L7.326 7ZM16.5 19a1.5 1.5 0 1 0 1.5 1.5a1.5 1.5 0 0 0-1.5-1.5Z"/>
        </symbol>
        <symbol xmlns="http://www.w3.org/2000/svg" id="check" viewBox="0 0 24 24">
          <path fill="currentColor" d="M18.71 7.21a1 1 0 0 0-1.42 0l-7.45 7.46l-3.13-3.14A1 1 0 1 0 5.29 13l3.84 3.84a1 1 0 0 0 1.42 0l8.16-8.16a1 1 0 0 0 0-1.47Z"/>
        </symbol>
        <symbol xmlns="http://www.w3.org/2000/svg" id="trash" viewBox="0 0 24 24">
          <path fill="currentColor" d="M10 18a1 1 0 0 0 1-1v-6a1 1 0 0 0-2 0v6a1 1 0 0 0 1 1ZM20 6h-4V5a3 3 0 0 0-3-3h-2a3 3 0 0 0-3 3v1H4a1 1 0 0 0 0 2h1v11a3 3 0 0 0 3 3h8a3 3 0 0 0 3-3V8h1a1 1 0 0 0 0-2ZM10 5a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v1h-4Zm7 14a1 1 0 0 1-1 1H8a1 1 0 0 1-1-1V8h10Zm-3-1a1 1 0 0 0 1-1v-6a1 1 0 0 0-2 0v6a1 1 0 0 0 1 1Z"/>
        </symbol>
        <symbol xmlns="http://www.w3.org/2000/svg" id="star-outline" viewBox="0 0 15 15">
          <path fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" d="M7.5 9.804L5.337 11l.413-2.533L4 6.674l2.418-.37L7.5 4l1.082 2.304l2.418.37l-1.75 1.793L9.663 11L7.5 9.804Z"/>
        </symbol>
        <symbol xmlns="http://www.w3.org/2000/svg" id="star-solid" viewBox="0 0 15 15">
          <path fill="currentColor" d="M7.953 3.788a.5.5 0 0 0-.906 0L6.08 5.85l-2.154.33a.5.5 0 0 0-.283.843l1.574 1.613l-.373 2.284a.5.5 0 0 0 .736.518l1.92-1.063l1.921 1.063a.5.5 0 0 0 .736-.519l-.373-2.283l1.574-1.613a.5.5 0 0 0-.283-.844L8.921 5.85l-.968-2.062Z"/>
        </symbol>
        <symbol xmlns="http://www.w3.org/2000/svg" id="search" viewBox="0 0 24 24">
          <path fill="currentColor" d="M21.71 20.29L18 16.61A9 9 0 1 0 16.61 18l3.68 3.68a1 1 0 0 0 1.42 0a1 1 0 0 0 0-1.39ZM11 18a7 7 0 1 1 7-7a7 7 0 0 1-7 7Z"/>
        </symbol>
        <symbol xmlns="http://www.w3.org/2000/svg" id="user" viewBox="0 0 24 24">
          <path fill="currentColor" d="M15.71 12.71a6 6 0 1 0-7.42 0a10 10 0 0 0-6.22 8.18a1 1 0 0 0 2 .22a8 8 0 0 1 15.9 0a1 1 0 0 0 1 .89h.11a1 1 0 0 0 .88-1.1a10 10 0 0 0-6.25-8.19ZM12 12a4 4 0 1 1 4-4a4 4 0 0 1-4 4Z"/>
        </symbol>
        <symbol xmlns="http://www.w3.org/2000/svg" id="close" viewBox="0 0 15 15">
          <path fill="currentColor" d="M7.953 3.788a.5.5 0 0 0-.906 0L6.08 5.85l-2.154.33a.5.5 0 0 0-.283.843l1.574 1.613l-.373 2.284a.5.5 0 0 0 .736.518l1.92-1.063l1.921 1.063a.5.5 0 0 0 .736-.519l-.373-2.283l1.574-1.613a.5.5 0 0 0-.283-.844L8.921 5.85l-.968-2.062Z"/>
        </symbol>
      </defs>
    </svg>

    <div class="preloader-wrapper">
      <div class="preloader">
      </div>
    </div>
    
    <div class="offcanvas offcanvas-end" data-bs-scroll="true" tabindex="-1" id="offcanvasSearch" aria-labelledby="Search">
      <div class="offcanvas-header justify-content-center">
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
      </div>
      <div class="offcanvas-body">
        <div class="order-md-last">
          <h4 class="d-flex justify-content-between align-items-center mb-3">
            <span class="text-primary">Search</span>
          </h4>
          <form role="search" action="index.html" method="get" class="d-flex mt-3 gap-0">
            <input class="form-control rounded-start rounded-0 bg-light" type="email" placeholder="What are you looking for?" aria-label="What are you looking for?">
            <button class="btn btn-dark rounded-end rounded-0" type="submit">Search</button>
          </form>
        </div>
      </div>
    </div>

<!-- HEADER GỌN HỚN VÀ ĐẸP HƠN -->
<header class="fixed-top" style="background:white; z-index:1000;">
  <!-- PHẦN TRÊN: LOGO + TÌM KIẾM + USER -->
  <div class="container-fluid" style="background:white; padding:8px 0; border-bottom:1px solid #ffe4f4;">
    <div class="row align-items-center" style="min-height:70px;">
      <!-- Logo -->
      <div class="col-5 col-sm-4 col-lg-2 text-center text-sm-start" style="padding-left: 100px;">
        <a href="index.php">
          <img src="../images/logo-be.png" alt="Beauty Cosmetics" class="img-fluid" style="height: 100px;">
        </a>
      </div>
      <!-- Thanh tìm kiếm -->
      <div class="col-12 col-lg-6 mt-2 mt-lg-0">
        <form action="index.php" method="get" class="d-flex position-relative">
          <input type="text" name="q" class="form-control rounded-pill shadow-sm"
                 placeholder="Tìm son, kem dưỡng, nước hoa..."
                 style="height:45px; border:2px solid #ffecf4; padding-right:55px; font-size:0.95rem;">
          <button type="submit" class="btn position-absolute search-btn"
                  style="right:5px; top:50%; transform:translateY(-50%); background:linear-gradient(135deg,#ff69b4,#ff1493); color:white; width:38px; height:38px; border-radius:50%; border:none; box-shadow: 0 4px 12px rgba(255,105,180,0.3);">
            <i class="bi bi-search"></i>
          </button>
        </form>
      </div>
      <!-- User + Giỏ hàng -->
      <div class="col-7 col-sm-8 col-lg-4 d-flex justify-content-end align-items-center gap-2 gap-lg-3" style="padding-right: 100px;">
        <ul class="list-unstyled d-flex align-items-center m-0 gap-2">
          <?php if ($is_logged_in): ?>
            <li class="dropdown">
              <a href="#" class="d-flex align-items-center text-decoration-none dropdown-toggle" data-bs-toggle="dropdown">
                <div class="user-avatar rounded-circle d-flex align-items-center justify-content-center"
                     style="width:45px; height:45px; background:linear-gradient(135deg,#ff69b4,#ff1493); border:3px solid rgba(255,255,255,0.8); box-shadow: 0 4px 15px rgba(255,105,180,0.3);">
                  <i class="bi bi-person-fill text-white fs-5"></i>
                </div>
                <div class="ms-2 d-none d-xl-block">
                  <div style="font-size:0.75rem; color:#666; font-weight:500;">Xin chào</div>
                  <div style="font-size:0.9rem; font-weight:700; color:#ff1493;"><?= htmlspecialchars($username) ?></div>
                </div>
              </a>
              <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0 mt-2">
                <li>
                  <div class="dropdown-item-text text-center mb-1">
                    <div class="d-flex align-items-center gap-2">
                      <div class="rounded-circle d-flex align-items-center justify-content-center"
                           style="width:32px; height:32px; background:linear-gradient(135deg,#ff69b4,#ff1493);">
                        <i class="bi bi-person-fill text-white" style="font-size:0.9rem;"></i>
                      </div>
                      <div class="text-start">
                        <div class="welcome-text" style="font-size:0.85rem;">Xin chào!</div>
                        <div class="fw-bold text-dark" style="font-size:0.9rem;"><?= htmlspecialchars($username) ?></div>
                      </div>
                    </div>
                  </div>
                </li>
                
                <?php if ($role === 'admin'): ?>
                  <li>
                    <a class="dropdown-item" href="admin_dashboard.php">
                      <i class="bi bi-shield-lock me-2"></i> 
                      Quản trị viên
                      <span class="admin-badge">Admin</span>
                    </a>
                  </li>
                <?php endif; ?>
                
                <li>
                  <a class="dropdown-item" href="my_orders.php">
                    <i class="bi bi-bag-check me-2"></i> Đơn hàng của tôi
                  </a>
                </li>
                
                <li><hr class="dropdown-divider"></li>
                
                <li>
                  <a class="dropdown-item logout-item" href="?logout=1">
                    <i class="bi bi-box-arrow-right me-2"></i> Đăng xuất
                  </a>
                </li>
              </ul>
            </li>
          <?php else: ?>
            <li>
              <a href="login.php" class="rounded-circle d-flex align-items-center justify-content-center text-white"
                 style="width:42px; height:42px; background:linear-gradient(135deg,#ff69b4,#ff1493);">
                <i class="bi bi-person fs-5"></i>
              </a>
            </li>
          <?php endif; ?>
          <li class="d-none d-lg-block">
            <div class="cart-icon">
              <a href="cart.php" class="border-0 bg-transparent position-relative text-decoration-none d-flex align-items-center justify-content-center rounded-circle"
                 style="width:45px; height:45px; transition: all 0.3s ease;">
                <i class="bi bi-bag-heart-fill fs-4" style="color:#ff69b4; transition: all 0.3s ease;"></i>
                <span class="cart-count badge rounded-pill position-absolute" 
                      style="top:-6px; right:-10px; font-size:0.7rem; font-weight:900; color:#fff; background:#ff0a23ff; display:none; box-shadow: 0 2px 8px rgba(236, 17, 38, 0.9);">0</span>
              </a>
            </div>
          </li>
          <li class="d-lg-none"><a href="#" data-bs-toggle="offcanvas" data-bs-target="#offcanvasCart"><i class="bi bi-bag-heart-fill fs-4 text-dark"></i></a></li>
          <li class="d-lg-none"><a href="#" data-bs-toggle="offcanvas" data-bs-target="#offcanvasSearch"><i class="bi bi-search fs-4 text-dark"></i></a></li>
        </ul>
      </div>
    </div>
  </div>

  <!-- THANH MENU DÍNH -->
  <div style="background: linear-gradient(135deg,#ff69b4,#ff1493); box-shadow: 0 4px 15px rgba(255,105,180,0.4);">
    <div class="container-fluid">
      <nav class="navbar navbar-expand-lg py-2">
        <button class="navbar-toggler border-0 text-white" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasNavbar">
          <i class="bi bi-list fs-2"></i>
        </button>
        <div class="collapse navbar-collapse">
          <ul class="navbar-nav mx-auto text-center gap-3 text-white fw-600" style="font-size:1.05rem;">
            <li class="nav-item"><a href="index.php" class="nav-link text-white">TRANG CHỦ</a></li>
            <li class="nav-item"><a href="index.php#category-section" class="nav-link text-white">DANH MỤC</a></li>
            <li class="nav-item"><a href="index.php#products-section" class="nav-link text-white">SẢN PHẨM</a></li>
            <li class="nav-item"><a href="my_orders.php" class="nav-link text-white">ĐƠN HÀNG</a></li>
            <li class="nav-item"><a href="index.php#lienhe" class="nav-link text-white">LIÊN HỆ</a></li>
            <?php if ($role === 'admin'): ?>
              <li class="nav-item"><a href="admin_dashboard.php" class="nav-link text-warning fw-bold">QUẢN LÝ</a></li>
            <?php endif; ?>
          </ul>
        </div>
      </nav>
    </div>
  </div>
</header>
