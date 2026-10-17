<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/config.php';

// Bảo vệ toàn bộ trang Admin: chỉ tài khoản có role = 'admin' mới được vào
if (!is_logged_in() || !is_admin()) {
    header("Location: login.php?redirect=" . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

$admin_user = $_SESSION['username'] ?? 'Admin';
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= isset($page_title) ? htmlspecialchars($page_title) : 'Quản Trị Hệ Thống - BeautyShop' ?></title>
  
  <link rel="icon" type="image/png" href="../images/cosmetics.png">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

  <style>
    :root {
      --admin-primary: #d47a88;
      --admin-dark: #3b1e28;
      --admin-bg: #f8f6f7;
      --sidebar-width: 260px;
    }

    body {
      font-family: 'Plus Jakarta Sans', -apple-system, sans-serif;
      background-color: var(--admin-bg);
      color: #333;
    }

    /* Sidebar Navigation */
    .admin-sidebar {
      width: var(--sidebar-width);
      position: fixed;
      top: 0;
      bottom: 0;
      left: 0;
      background: #2f1722;
      color: #f7dcd6;
      z-index: 1040;
      overflow-y: auto;
      transition: all 0.3s;
    }

    .admin-sidebar .sidebar-brand {
      padding: 1.5rem 1.2rem;
      border-bottom: 1px solid rgba(255, 255, 255, 0.08);
      display: flex;
      align-items: center;
      gap: 10px;
      color: white;
      text-decoration: none;
    }

    .admin-nav-item {
      padding: 0.75rem 1.2rem;
      display: flex;
      align-items: center;
      gap: 12px;
      color: #d1b8be;
      text-decoration: none;
      font-weight: 500;
      font-size: 0.92rem;
      border-left: 3px solid transparent;
      transition: all 0.2s;
    }

    .admin-nav-item:hover, .admin-nav-item.active {
      color: white;
      background: rgba(212, 122, 136, 0.15);
      border-left-color: var(--admin-primary);
    }

    .admin-nav-item i {
      font-size: 1.15rem;
    }

    /* Main Content Wrapper */
    .admin-main {
      margin-left: var(--sidebar-width);
      min-height: 100vh;
      display: flex;
      flex-direction: column;
    }

    .admin-topbar {
      background: white;
      box-shadow: 0 2px 10px rgba(0,0,0,0.04);
      padding: 0.8rem 1.5rem;
      display: flex;
      align-items: center;
      justify-content: space-between;
      position: sticky;
      top: 0;
      z-index: 1030;
    }

    .stat-card-modern {
      background: white;
      border-radius: 16px;
      padding: 1.5rem;
      border: 1px solid #f0e6e8;
      box-shadow: 0 4px 15px rgba(0,0,0,0.03);
      transition: transform 0.25s, box-shadow 0.25s;
    }

    .stat-card-modern:hover {
      transform: translateY(-4px);
      box-shadow: 0 10px 25px rgba(74, 40, 53, 0.08);
    }

    @media (max-width: 992px) {
      .admin-sidebar {
        margin-left: calc(-1 * var(--sidebar-width));
      }
      .admin-sidebar.show {
        margin-left: 0;
      }
      .admin-main {
        margin-left: 0;
      }
    }
  </style>
</head>
<body>

<!-- SIDEBAR -->
<aside class="admin-sidebar shadow" id="adminSidebar">
  <a href="admin_dashboard.php" class="sidebar-brand">
    <div class="rounded-circle d-flex align-items-center justify-content-center text-white" 
         style="width: 38px; height: 38px; background: linear-gradient(135deg, #d47a88, #9e4760);">
      <i class="bi bi-flower1 fs-5"></i>
    </div>
    <div>
      <span class="fw-bold tracking-wide text-white" style="font-size: 1.1rem;">BEAUTYSHOP</span>
      <div class="text-uppercase" style="font-size: 0.65rem; color: #e8a598; letter-spacing: 1px;">Admin Portal</div>
    </div>
  </a>

  <div class="py-3">
    <div class="px-4 py-1 text-uppercase text-white-50" style="font-size: 0.7rem; font-weight: 700; letter-spacing: 1px;">Tổng quan</div>
    <a href="admin_dashboard.php" class="admin-nav-item <?= $current_page === 'admin_dashboard.php' ? 'active' : '' ?>">
      <i class="bi bi-speedometer2 text-danger"></i> Dashboard Thống Kê
    </a>

    <div class="px-4 py-1 mt-3 text-uppercase text-white-50" style="font-size: 0.7rem; font-weight: 700; letter-spacing: 1px;">Kinh doanh</div>
    <a href="manage_orders.php" class="admin-nav-item <?= $current_page === 'manage_orders.php' ? 'active' : '' ?>">
      <i class="bi bi-cart-check text-warning"></i> Quản Lý Đơn Hàng
    </a>
    <a href="manage_products.php" class="admin-nav-item <?= in_array($current_page, ['manage_products.php', 'add_product.php', 'edit_product.php']) ? 'active' : '' ?>">
      <i class="bi bi-box-seam text-info"></i> Quản Lý Sản Phẩm
    </a>
    <a href="manage_categories.php" class="admin-nav-item <?= $current_page === 'manage_categories.php' ? 'active' : '' ?>">
      <i class="bi bi-collection text-success"></i> Danh Mục & Thương Hiệu
    </a>
    <a href="manage_coupons.php" class="admin-nav-item <?= $current_page === 'manage_coupons.php' ? 'active' : '' ?>">
      <i class="bi bi-ticket-perforated text-primary"></i> Quản Lý Mã Giảm Giá
    </a>
    <a href="manage_flash_sales.php" class="admin-nav-item <?= $current_page === 'manage_flash_sales.php' ? 'active' : '' ?>">
      <i class="bi bi-lightning-charge text-danger"></i> Quản Lý Flash Sale
    </a>

    <div class="px-4 py-1 mt-3 text-uppercase text-white-50" style="font-size: 0.7rem; font-weight: 700; letter-spacing: 1px;">Khách hàng & Dịch vụ</div>
    <a href="manage_users.php" class="admin-nav-item <?= $current_page === 'manage_users.php' ? 'active' : '' ?>">
      <i class="bi bi-people text-light"></i> Khách Hàng & Tài Khoản
    </a>
    <a href="manage_reviews.php" class="admin-nav-item <?= $current_page === 'manage_reviews.php' ? 'active' : '' ?>">
      <i class="bi bi-star-fill text-warning"></i> Đánh Giá Sản Phẩm
    </a>

    <div class="px-4 py-1 mt-3 text-uppercase text-white-50" style="font-size: 0.7rem; font-weight: 700; letter-spacing: 1px;">Hệ thống</div>
    <a href="index.php" class="admin-nav-item" target="_blank">
      <i class="bi bi-box-arrow-up-right text-info"></i> Xem Website Khách
    </a>
    <a href="index.php?logout=1" class="admin-nav-item text-danger">
      <i class="bi bi-box-arrow-right"></i> Đăng Xuất
    </a>
  </div>
</aside>

<!-- MAIN CONTENT WRAPPER -->
<div class="admin-main">
  
  <!-- TOPBAR -->
  <header class="admin-topbar">
    <div class="d-flex align-items-center gap-3">
      <button class="btn btn-sm btn-light d-lg-none" onclick="document.getElementById('adminSidebar').classList.toggle('show')">
        <i class="bi bi-list fs-5"></i>
      </button>
      <div class="fw-bold text-dark fs-5"><?= isset($admin_section_title) ? htmlspecialchars($admin_section_title) : 'Bảng Điều Khiển Quản Trị' ?></div>
    </div>

    <div class="d-flex align-items-center gap-3">
      <div class="d-flex align-items-center gap-2">
        <div class="rounded-circle d-flex align-items-center justify-content-center text-white"
             style="width: 36px; height: 36px; background: linear-gradient(135deg, #d47a88, #9e4760);">
          <i class="bi bi-person-fill"></i>
        </div>
        <div class="d-none d-sm-block text-start">
          <div class="fw-bold small text-dark"><?= htmlspecialchars($admin_user) ?></div>
          <small class="badge bg-danger" style="font-size: 0.65rem;">Quản trị viên</small>
        </div>
      </div>
      <a href="index.php?logout=1" class="btn btn-outline-danger btn-sm rounded-pill px-3" title="Đăng xuất">
        <i class="bi bi-power"></i>
      </a>
    </div>
  </header>

  <!-- BODY CONTAINER -->
  <div class="p-4 flex-grow-1">

