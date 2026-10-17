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

// ← DÒNG DUY NHẤT KẾT NỐI DB – BẮT BUỘC PHẢI CÓ FILE config.php Ở TRÊN
include 'config.php';
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý Sản phẩm - Beauty Cosmetics</title>
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
        
        .product-img { width:80px; height:80px; object-fit:cover; border-radius:8px; border:2px solid #eee; }
        
        .category-section {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
        }
        
        .category-section h4 {
            color: var(--admin-primary);
            font-weight: 700;
            border-bottom: 3px solid var(--admin-primary);
            padding-bottom: 0.5rem;
            margin-bottom: 1rem;
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
                Admin Dashboard
            </a>
            <div class="d-flex align-items-center gap-3">
                <span class="text-white">
                    <i class="bi bi-person-circle"></i>
                    <strong><?= htmlspecialchars($_SESSION['username']) ?></strong>
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
                <a class="nav-link" href="admin_dashboard.php">
                    <i class="bi bi-speedometer2"></i> Tổng quan
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link active" href="manage_products.php">
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

<div class="container-fluid py-4">

    <?php if (isset($_SESSION['delete_success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle"></i> <?= htmlspecialchars($_SESSION['delete_success']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['delete_success']); ?>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['delete_error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle"></i> <?= htmlspecialchars($_SESSION['delete_error']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['delete_error']); ?>
    <?php endif; ?>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 style="color: var(--admin-primary); font-weight: 700;">
            <i class="bi bi-box-seam"></i> Quản lý sản phẩm
        </h2>
        <a href="add_product.php" class="btn btn-primary btn-lg">
            <i class="bi bi-plus-circle"></i> Thêm sản phẩm mới
        </a>
    </div>

    <?php
    // Lấy danh mục
    $cat_result = $pdo->query("SELECT * FROM category ORDER BY category_name");

    while ($cat = $cat_result->fetch()) {
        $cat_id = $cat['category_id'];

        // Lấy sản phẩm theo danh mục
        $stmt = $pdo->prepare("SELECT * FROM products WHERE category_id = ? ORDER BY product_id DESC");
        $stmt->execute([$cat_id]);
        $products = $stmt->fetchAll();
    ?>
        <div class="category-section">
            <h4>
                <?= htmlspecialchars($cat['category_name']) ?>
                <span class="badge bg-danger ms-2"><?= count($products)?> sản phẩm</span>
            </h4>
            <?php if (count($products) > 0): ?>
    <div class="table-responsive">
        <table class="table table-hover">
            <thead style="background: linear-gradient(135deg, #ff69b4, #ff1493); color: white;">
                <tr>
                    <th>Hình</th>
                    <th>Tên sản phẩm</th>
                    <th>Giá</th>
                    <th>Số lượng</th>
                    <th class="text-center">Hành động</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($products as $p): ?>
                <tr>
                    <td>
                        <?php
                        $img = !empty($p['image']) && file_exists("../images/products/".$p['image'])
                            ? "../images/products/".$p['image']
                            : "../images/no-image.jpg";
                        ?>
                        <img src="<?= $img ?>" class="product-img" alt="product">
                    </td>
                    <td><strong><?= htmlspecialchars($p['product_name']) ?></strong></td>
                    <td><strong style="color: #ff1493;"><?= number_format($p['price']) ?> ₫</strong></td>
                    <td class="text-center"><span class="badge bg-info"><?= $p['quantity'] ?? 0 ?></span></td>
                    <td class="text-center">
                        <a href="edit_product.php?id=<?= $p['product_id'] ?>" class="btn btn-warning btn-sm">
                            <i class="bi bi-pencil"></i> Sửa
                        </a>
                        <a href="delete_product.php?id=<?= $p['product_id'] ?>" class="btn btn-danger btn-sm"
                           onclick="return confirm('Bạn có chắc muốn xóa sản phẩm này?')">
                            <i class="bi bi-trash"></i> Xóa
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php else: ?>
    <p class="text-muted text-center py-3">
        <i class="bi bi-inbox"></i> Chưa có sản phẩm nào trong danh mục này.
    </p>
<?php endif; ?>
        </div>
    <?php } ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>