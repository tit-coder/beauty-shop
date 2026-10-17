<?php
session_start();

// Bảo vệ trang admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

include 'config.php';

// Lấy ID sản phẩm từ URL
$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($product_id <= 0) {
    header("Location: manage_products.php");
    exit;
}

// Lấy thông tin sản phẩm hiện tại
$stmt = $pdo->prepare("SELECT * FROM products WHERE product_id = ?");
$stmt->execute([$product_id]);
$product = $stmt->fetch();

if (!$product) {
    header("Location: manage_products.php");
    exit;
}

// Xử lý cập nhật sản phẩm
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_name = trim($_POST['product_name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price = floatval($_POST['price'] ?? 0);
    $quantity = intval($_POST['quantity'] ?? 0);
    $category_id = intval($_POST['category_id'] ?? 0);
    
    // Xử lý upload ảnh mới (nếu có)
    $new_image = $product['image']; // Giữ ảnh cũ mặc định
    
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../images/products/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file_ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $allowed_ext = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        
        if (in_array($file_ext, $allowed_ext)) {
            $new_filename = uniqid('product_') . '.' . $file_ext;
            $upload_path = $upload_dir . $new_filename;
            
            if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                // Xóa ảnh cũ nếu có
                if (!empty($product['image']) && file_exists($upload_dir . $product['image'])) {
                    unlink($upload_dir . $product['image']);
                }
                $new_image = $new_filename;
            }
        }
    }
    
    // Cập nhật sản phẩm
    try {
        $update_stmt = $pdo->prepare("
            UPDATE products 
            SET product_name = ?, description = ?, price = ?, quantity = ?, category_id = ?, image = ?
            WHERE product_id = ?
        ");
        $update_stmt->execute([
            $product_name,
            $description,
            $price,
            $quantity,
            $category_id,
            $new_image,
            $product_id
        ]);
        
        $message = 'Cập nhật sản phẩm thành công!';
        
        // Lấy lại thông tin sản phẩm sau khi cập nhật
        $stmt->execute([$product_id]);
        $product = $stmt->fetch();
        
    } catch (PDOException $e) {
        $error = 'Lỗi: ' . $e->getMessage();
    }
}

// Lấy danh sách danh mục
$categories = $pdo->query("SELECT * FROM category ORDER BY category_name")->fetchAll();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sửa sản phẩm - Beauty Cosmetics</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        :root {
            --admin-primary: #ff69b4;
            --admin-secondary: #ff1493;
        }
        
        body {
            background: linear-gradient(135deg, #fff5f8 0%, #ffe4f0 100%);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
        }
        
        .admin-header {
            background: linear-gradient(135deg, var(--admin-secondary), var(--admin-primary));
            box-shadow: 0 4px 20px rgba(255,105,180,0.3);
            padding: 1.5rem 0;
            margin-bottom: 2rem;
        }
        
        .admin-header h1 {
            color: white;
            font-weight: 700;
            margin: 0;
        }
        
        .form-container {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 8px 30px rgba(0,0,0,0.1);
            max-width: 800px;
            margin: 0 auto;
        }
        
        .form-label {
            font-weight: 600;
            color: #333;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--admin-secondary), var(--admin-primary));
            border: none;
            padding: 0.75rem 2rem;
            font-weight: 600;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255,105,180,0.4);
        }
        
        .current-image {
            max-width: 200px;
            border-radius: 10px;
            border: 3px solid var(--admin-primary);
        }
    </style>
</head>
<body>

<div class="admin-header">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center">
            <h1><i class="bi bi-pencil-square"></i> Sửa sản phẩm</h1>
            <a href="manage_products.php" class="btn btn-light">
                <i class="bi bi-arrow-left"></i> Quay lại
            </a>
        </div>
    </div>
</div>

<div class="container pb-5">
    <?php if ($message): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle"></i> <?= htmlspecialchars($message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle"></i> <?= htmlspecialchars($error) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <div class="form-container">
        <form method="POST" enctype="multipart/form-data">
            <div class="mb-3">
                <label class="form-label">Tên sản phẩm <span class="text-danger">*</span></label>
                <input type="text" name="product_name" class="form-control" 
                       value="<?= htmlspecialchars($product['product_name']) ?>" required>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Mô tả</label>
                <textarea name="description" class="form-control" rows="4"><?= htmlspecialchars($product['description'] ?? '') ?></textarea>
            </div>
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Giá (₫) <span class="text-danger">*</span></label>
                    <input type="number" name="price" class="form-control" 
                           value="<?= $product['price'] ?>" min="0" step="1000" required>
                </div>
                
                <div class="col-md-6 mb-3">
                    <label class="form-label">Số lượng <span class="text-danger">*</span></label>
                    <input type="number" name="quantity" class="form-control" 
                           value="<?= $product['quantity'] ?? 0 ?>" min="0" required>
                </div>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Danh mục <span class="text-danger">*</span></label>
                <select name="category_id" class="form-select" required>
                    <option value="">-- Chọn danh mục --</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['category_id'] ?>" 
                                <?= $cat['category_id'] == $product['category_id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cat['category_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Hình ảnh hiện tại</label>
                <div class="mb-2">
                    <?php
                    $current_img = !empty($product['image']) && file_exists("../images/products/".$product['image'])
                        ? "../images/products/".$product['image']
                        : "../images/no-image.jpg";
                    ?>
                    <img src="<?= $current_img ?>" class="current-image" alt="Current product">
                </div>
                <label class="form-label">Thay đổi hình ảnh (nếu muốn)</label>
                <input type="file" name="image" class="form-control" accept="image/*">
                <small class="text-muted">Để trống nếu không muốn thay đổi ảnh</small>
            </div>
            
            <div class="d-grid gap-2">
                <button type="submit" class="btn btn-primary btn-lg">
                    <i class="bi bi-save"></i> Cập nhật sản phẩm
                </button>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
