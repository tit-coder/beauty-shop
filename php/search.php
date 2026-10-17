<?php
session_start();
include 'config.php';

// Lấy từ khóa tìm kiếm
$search_query = isset($_GET['q']) ? trim($_GET['q']) : '';
$products = [];
$total_results = 0;

if (!empty($search_query)) {
    // Tìm kiếm sản phẩm theo tên hoặc mô tả
    $stmt = $pdo->prepare("SELECT p.*, c.category_name 
                          FROM products p 
                          LEFT JOIN category c ON p.category_id = c.category_id 
                          WHERE p.product_name LIKE ? OR p.description LIKE ?
                          ORDER BY p.product_name ASC");
    $search_param = "%$search_query%";
    $stmt->execute([$search_param, $search_param]);
    $products = $stmt->fetchAll();
    $total_results = count($products);
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tìm kiếm: <?= htmlspecialchars($search_query) ?> - Beauty Cosmetics</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../css/style.css">
    <style>
        body { padding-top: 160px; background: #f8f9fa; }
        .search-header { 
            background: linear-gradient(135deg, #ff6da5ff, rgba(248, 155, 229, 1)); 
            color: white; 
            padding: 30px 0; 
            margin-bottom: 30px;
        }
        .product-card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            transition: all 0.3s ease;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            height: 100%;
        }
        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(255,105,180,0.2);
        }
        .product-img {
            width: 100%;
            height: 250px;
            object-fit: cover;
        }
        .product-body {
            padding: 1.5rem;
        }
        .product-name {
            font-weight: 600;
            color: #333;
            margin-bottom: 0.5rem;
            min-height: 48px;
        }
        .product-price {
            color: #ff1493;
            font-size: 1.3rem;
            font-weight: 700;
        }
        .category-badge {
            background: linear-gradient(135deg, #ff69b4, #ff1493);
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.85rem;
        }
        .search-box {
            max-width: 600px;
            margin: 0 auto;
        }
        .no-results {
            text-align: center;
            padding: 60px 20px;
        }
        .no-results i {
            font-size: 5rem;
            color: #ddd;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>

<!-- HEADER -->
<header class="fixed-top" style="background:white; z-index:1000;">
  <div class="container-fluid" style="background:white; padding:8px 0; border-bottom:1px solid #ffe4f4;">
    <div class="row align-items-center" style="min-height:70px;">
      <!-- Logo -->
      <div class="col-6 col-lg-3">
        <a href="index.php" class="d-flex align-items-center text-decoration-none">
          <img src="../images/beautylogo.png" alt="Beauty Logo" style="height:50px;">
        </a>
      </div>
      <!-- Thanh tìm kiếm -->
      <div class="col-12 col-lg-6 mt-2 mt-lg-0">
        <form action="search.php" method="get" class="d-flex position-relative">
          <input type="text" name="q" class="form-control rounded-pill shadow-sm"
                 placeholder="Tìm son, kem dưỡng, nước hoa..."
                 value="<?= htmlspecialchars($search_query) ?>"
                 style="padding:10px 50px 10px 20px; border:2px solid #ffe4f4;">
          <button type="submit" class="btn position-absolute"
                  style="right:5px; top:50%; transform:translateY(-50%); background:linear-gradient(135deg,#ff69b4,#ff1493); color:white; width:38px; height:38px; border-radius:50%;">
            <i class="bi bi-search"></i>
          </button>
        </form>
      </div>
      <!-- User & Cart -->
      <div class="col-6 col-lg-3 text-end">
        <div class="d-flex justify-content-end align-items-center gap-3">
          <?php if (isset($_SESSION['user_id'])): ?>
            <a href="my_orders.php" class="text-decoration-none text-dark d-none d-lg-inline">
              <i class="bi bi-person-circle fs-4"></i>
              <span class="ms-1"><?= htmlspecialchars($_SESSION['username']) ?></span>
            </a>
          <?php else: ?>
            <a href="login.php" class="text-decoration-none text-dark d-none d-lg-inline">
              <i class="bi bi-person-circle fs-4"></i> Đăng nhập
            </a>
          <?php endif; ?>
          <a href="cart.php" class="text-decoration-none text-dark position-relative">
            <i class="bi bi-bag-heart-fill fs-4" style="color:#ff1493;"></i>
            <span id="cart-count" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">0</span>
          </a>
        </div>
      </div>
    </div>
  </div>
  
  <!-- Menu -->
  <nav style="background:#fff; border-bottom:1px solid #ffe4f4;">
    <div class="container-fluid">
      <ul class="nav justify-content-center" style="padding:8px 0;">
        <li class="nav-item"><a class="nav-link text-dark fw-semibold" href="index.php"><i class="bi bi-house-door"></i> Trang chủ</a></li>
        <li class="nav-item"><a class="nav-link text-dark fw-semibold" href="products.php"><i class="bi bi-grid"></i> Sản phẩm</a></li>
        <li class="nav-item"><a class="nav-link text-dark fw-semibold" href="cart.php"><i class="bi bi-cart"></i> Giỏ hàng</a></li>
        <?php if (isset($_SESSION['user_id'])): ?>
          <li class="nav-item"><a class="nav-link text-dark fw-semibold" href="my_orders.php"><i class="bi bi-box-seam"></i> Đơn hàng</a></li>
          <?php if ($_SESSION['role'] === 'admin'): ?>
            <li class="nav-item"><a class="nav-link text-danger fw-semibold" href="admin_dashboard.php"><i class="bi bi-speedometer2"></i> Admin</a></li>
          <?php endif; ?>
          <li class="nav-item"><a class="nav-link text-dark fw-semibold" href="?logout=1"><i class="bi bi-box-arrow-right"></i> Đăng xuất</a></li>
        <?php else: ?>
          <li class="nav-item"><a class="nav-link text-dark fw-semibold" href="login.php"><i class="bi bi-person"></i> Đăng nhập</a></li>
        <?php endif; ?>
      </ul>
    </div>
  </nav>
</header>

<div class="search-header">
    <div class="container">
        <h2 class="mb-3"><i class="bi bi-search"></i> Kết quả tìm kiếm</h2>
        <div class="search-box">
            <form action="search.php" method="get" class="d-flex position-relative">
                <input type="text" name="q" class="form-control rounded-pill shadow-sm" 
                       placeholder="Tìm son, kem dưỡng, nước hoa..."
                       value="<?= htmlspecialchars($search_query) ?>"
                       style="padding: 12px 50px 12px 20px; border: none;">
                <button type="submit" class="btn position-absolute"
                        style="right:5px; top:50%; transform:translateY(-50%); background:white; color:#ff1493; width:38px; height:38px; border-radius:50%;">
                    <i class="bi bi-search"></i>
                </button>
            </form>
        </div>
    </div>
</div>

<div class="container mb-5">
    <?php if (!empty($search_query)): ?>
        <div class="mb-4">
            <h5>
                Tìm thấy <strong class="text-danger"><?= $total_results ?></strong> sản phẩm 
                cho từ khóa "<strong><?= htmlspecialchars($search_query) ?></strong>"
            </h5>
        </div>

        <?php if ($total_results > 0): ?>
            <div class="row g-4">
                <?php foreach ($products as $product): 
                    $image_path = !empty($product['image']) && file_exists("../images/products/".$product['image'])
                        ? "../images/products/".$product['image']
                        : "../images/no-image.jpg";
                ?>
                    <div class="col-6 col-md-4 col-lg-3">
                        <div class="product-card">
                            <a href="product_detail.php?id=<?= $product['product_id'] ?>">
                                <img src="<?= $image_path ?>" class="product-img" alt="<?= htmlspecialchars($product['product_name']) ?>">
                            </a>
                            <div class="product-body">
                                <?php if (!empty($product['category_name'])): ?>
                                    <div class="mb-2">
                                        <span class="category-badge"><?= htmlspecialchars($product['category_name']) ?></span>
                                    </div>
                                <?php endif; ?>
                                <h6 class="product-name">
                                    <a href="product_detail.php?id=<?= $product['product_id'] ?>" 
                                       class="text-decoration-none text-dark">
                                        <?= htmlspecialchars($product['product_name']) ?>
                                    </a>
                                </h6>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="product-price"><?= number_format($product['price']) ?> ₫</span>
                                    <button class="btn btn-sm" 
                                            style="background: linear-gradient(135deg, #ff69b4, #ff1493); color: white;"
                                            onclick="addToCart(<?= $product['product_id'] ?>)">
                                        <i class="bi bi-cart-plus"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="no-results">
                <i class="bi bi-search"></i>
                <h4>Không tìm thấy sản phẩm nào</h4>
                <p class="text-muted">Không có sản phẩm nào phù hợp với từ khóa "<strong><?= htmlspecialchars($search_query) ?></strong>"</p>
                <p class="text-muted">Vui lòng thử lại với từ khóa khác hoặc xem các sản phẩm khác của chúng tôi.</p>
                <a href="index.php" class="btn btn-primary mt-3">
                    <i class="bi bi-house"></i> Về trang chủ
                </a>
            </div>
        <?php endif; ?>
    <?php else: ?>
        <div class="no-results">
            <i class="bi bi-search"></i>
            <h4>Nhập từ khóa để tìm kiếm</h4>
            <p class="text-muted">Vui lòng nhập tên sản phẩm bạn muốn tìm</p>
        </div>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="../js/cart.js"></script>
</body>
</html>
