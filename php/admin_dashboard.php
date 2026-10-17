<?php
session_start();

// Xử lý đăng xuất
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: login.php");
    exit;
}

// Bảo vệ trang admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

include 'config.php';

$username = $_SESSION['username'];
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Beauty Cosmetics</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        :root {
            --admin-primary: #ff69b4;
            --admin-secondary: #ff1493;
            --admin-dark: #222;
        }
        
        body {
            background: linear-gradient(135deg, #fff5f8 0%, #ffe4f0 100%);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        /* Admin Header */
        .admin-header {
            background: linear-gradient(135deg, var(--admin-secondary), var(--admin-primary));
            box-shadow: 0 4px 20px rgba(255,105,180,0.3);
            padding: 0;
        }
        
        .admin-header .navbar-brand {
            color: white !important;
            font-weight: 700;
            font-size: 1.4rem;
            padding: 1rem 0;
        }
        
        .admin-header .navbar-brand i {
            font-size: 1.6rem;
            margin-right: 0.5rem;
        }
        
        /* Admin Navigation */
        .admin-nav {
            background: white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            padding: 0;
        }
        
        .admin-nav .nav-link {
            color: var(--admin-dark) !important;
            font-weight: 600;
            padding: 1rem 1.5rem !important;
            transition: all 0.3s ease;
            border-bottom: 3px solid transparent;
            position: relative;
        }
        
        .admin-nav .nav-link:hover,
        .admin-nav .nav-link.active {
            color: var(--admin-primary) !important;
            border-bottom-color: var(--admin-primary);
            background: rgba(255,105,180,0.05);
        }
        
        .admin-nav .nav-link i {
            margin-right: 0.5rem;
            font-size: 1.1rem;
        }
        
        /* Dashboard Content */
        .dashboard-content {
            padding: 2rem 0;
        }
        
        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
            border-left: 4px solid var(--admin-primary);
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(255,105,180,0.2);
        }
        
        .stat-card .icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            color: white;
        }
        
        .stat-card.products .icon { background: linear-gradient(135deg, #667eea, #764ba2); }
        .stat-card.orders .icon { background: linear-gradient(135deg, #f093fb, #f5576c); }
        .stat-card.users .icon { background: linear-gradient(135deg, #4facfe, #00f2fe); }
        .stat-card.reviews .icon { background: linear-gradient(135deg, #43e97b, #38f9d7); }
        
        .stat-card h3 {
            font-size: 2rem;
            font-weight: 700;
            color: var(--admin-dark);
            margin: 0.5rem 0;
        }
        
        .stat-card p {
            color: #666;
            margin: 0;
            font-size: 0.95rem;
        }
        
        .welcome-card {
            background: linear-gradient(135deg, var(--admin-primary), var(--admin-secondary));
            color: white;
            border-radius: 12px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 8px 25px rgba(255,105,180,0.3);
        }
        
        .welcome-card h2 {
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        
        .quick-actions {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            margin-top: 2rem;
        }
        
        .quick-actions h4 {
            color: var(--admin-dark);
            font-weight: 700;
            margin-bottom: 1.5rem;
        }
        
        .quick-actions .btn {
            margin: 0.5rem;
        }
    </style>
</head>
<body>

<!-- Admin Header -->
<header class="admin-header">
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center py-2">
            <a class="navbar-brand" href="admin_dashboard.php">
                <i class="bi bi-speedometer2"></i>
                QUẢN TRỊ VIÊN
            </a>
            <div class="d-flex align-items-center gap-3">
                <span class="text-white">
                    <i class="bi bi-person-circle"></i>
                    <strong><?= htmlspecialchars($username) ?></strong>
                </span>
                <a href="?logout=1" class="btn btn-outline-light btn-sm">
                    <i class="bi bi-box-arrow-right"></i> Đăng xuất
                </a>
            </div>
        </div>
    </div>
</header>

<!-- Admin Navigation -->
<nav class="admin-nav">
    <div class="container-fluid">
        <ul class="nav nav-pills">
            <li class="nav-item">
                <a class="nav-link active" href="admin_dashboard.php">
                    <i class="bi bi-speedometer2"></i> Tổng quan
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="manage_products.php">
                    <i class="bi bi-box-seam"></i> Quản lý sản phẩm
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="manage_orders.php">
                    <i class="bi bi-cart-check"></i> Quản lý đơn hàng
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="manage_users.php">
                    <i class="bi bi-people"></i> Quản lý người dùng
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="manage_reviews.php">
                    <i class="bi bi-star"></i> Quản lý đánh giá
                </a>
            </li>
        </ul>
    </div>
</nav>

<!-- Dashboard Content -->
<div class="dashboard-content">
    <div class="container-fluid">
        
        <!-- Welcome Card -->
        <div class="welcome-card">
            <h2><i class="bi bi-emoji-smile"></i> Xin chào, <?= htmlspecialchars($username) ?>!</h2>
            <p class="mb-0">Chào mừng bạn đến với trang quản trị Beauty Cosmetics. Quản lý toàn bộ hệ thống của bạn tại đây.</p>
        </div>

        <!-- Statistics Cards -->
        <div class="row g-4">
            <?php
            // Đếm số lượng sản phẩm
            $product_count = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
            
            // Đếm số đơn hàng (giả sử có bảng orders)
            $order_count = 0;
            try {
                $order_count = $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
            } catch(Exception $e) {}
            
            // Đếm số người dùng
            $user_count = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
            
            // Đếm số đánh giá (giả sử có bảng reviews)
            $review_count = 0;
            try {
                $review_count = $pdo->query("SELECT COUNT(*) FROM reviews")->fetchColumn();
            } catch(Exception $e) {}
            ?>
            
            <div class="col-md-6 col-lg-3">
                <div class="stat-card products">
                    <div class="d-flex align-items-center">
                        <div class="icon">
                            <i class="bi bi-box-seam"></i>
                        </div>
                        <div class="ms-3">
                            <h3><?= $product_count ?></h3>
                            <p>Sản phẩm</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6 col-lg-3">
                <div class="stat-card orders">
                    <div class="d-flex align-items-center">
                        <div class="icon">
                            <i class="bi bi-cart-check"></i>
                        </div>
                        <div class="ms-3">
                            <h3><?= $order_count ?></h3>
                            <p>Đơn hàng</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6 col-lg-3">
                <div class="stat-card users">
                    <div class="d-flex align-items-center">
                        <div class="icon">
                            <i class="bi bi-people"></i>
                        </div>
                        <div class="ms-3">
                            <h3><?= $user_count ?></h3>
                            <p>Người dùng</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6 col-lg-3">
                <div class="stat-card reviews">
                    <div class="d-flex align-items-center">
                        <div class="icon">
                            <i class="bi bi-star"></i>
                        </div>
                        <div class="ms-3">
                            <h3><?= $review_count ?></h3>
                            <p>Đánh giá</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="quick-actions">
            <h4><i class="bi bi-lightning"></i> Thao tác nhanh</h4>
            <div class="d-flex flex-wrap">
                <a href="add_product.php" class="btn btn-primary">
                    <i class="bi bi-plus-circle"></i> Thêm sản phẩm mới
                </a>
                <a href="manage_products.php" class="btn btn-outline-primary">
                    <i class="bi bi-box-seam"></i> Xem tất cả sản phẩm
                </a>
                <a href="manage_orders.php" class="btn btn-outline-success">
                    <i class="bi bi-cart-check"></i> Xem đơn hàng
                </a>
                <a href="manage_users.php" class="btn btn-outline-info">
                    <i class="bi bi-people"></i> Quản lý người dùng
                </a>
                <a href="manage_reviews.php" class="btn btn-outline-warning">
                    <i class="bi bi-star"></i> Xem đánh giá
                </a>
            </div>
        </div>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
