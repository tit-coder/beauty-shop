<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}
include 'config.php';

$message = '';
$upload_dir = '../images/products/';  // Thư mục lưu ảnh

// ===== TỰ ĐỘNG TẠO THƯ MỤC NẾU CHƯA TỒN TẠI =====
if (!is_dir($upload_dir)) {
    if (!mkdir($upload_dir, 0755, true)) {
        die("Không thể tạo thư mục $upload_dir. Vui lòng tạo thủ công và cấp quyền ghi!");
    }
    // Trên Windows XAMPP, 0755 đôi khi vẫn bị chặn → thêm chmod bổ sung
    @chmod($upload_dir, 0777);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $category_id  = $_POST['category_id'] ?? '';
    $product_name = trim($_POST['product_name'] ?? '');
    $price        = $_POST['price'] ?? 0;
    $quantity     = $_POST['quantity'] ?? 1;
    $description  = trim($_POST['description'] ?? '');

    // Validate dữ liệu cơ bản
    if (empty($product_name) || empty($category_id) || $price < 0 || $quantity < 1) {
        $message = '<div class="alert alert-danger">Vui lòng điền đầy đủ và chính xác thông tin!</div>';
    }
    // Xử lý upload ảnh
    elseif (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
        $message = '<div class="alert alert-danger">Vui lòng chọn hình ảnh!</div>';
    } else {
        $file = $_FILES['image'];
        $ext  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

        if (!in_array($ext, $allowed)) {
            $message = '<div class="alert alert-danger">Chỉ chấp nhận JPG, PNG, GIF, WEBP!</div>';
        }
        elseif ($file['size'] > 5 * 1024 * 1024) { // 5MB
            $message = '<div class="alert alert-danger">Ảnh không được quá 5MB!</div>';
        }
        else {
            // Tên file an toàn, không trùng
            $safe_name = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', $file['name']);
            $target_path = $upload_dir . $safe_name;

            if (move_uploaded_file($file['tmp_name'], $target_path)) {
                // Lưu vào CSDL
                try {
                    $sql = "INSERT INTO products (product_name, category_id, price, quantity, image, description) 
                            VALUES (:name, :cat_id, :price, :qty, :image, :description)";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([
                        ':name'        => $product_name,
                        ':cat_id'      => $category_id,
                        ':price'       => $price,
                        ':qty'         => $quantity,
                        ':image'       => $safe_name,
                        ':description' => $description
                    ]);

                    $message = '<div class="alert alert-success">Thêm sản phẩm thành công!</div>';
                    // Reset form sau khi thành công
                    $_POST = [];
                } catch (Exception $e) {
                    $message = '<div class="alert alert-danger">Lỗi database: '.$e->getMessage().'</div>';
                    @unlink($target_path); // xóa ảnh nếu lưu DB lỗi
                }
            } else {
                $message = '<div class="alert alert-danger">Upload thất bại! Kiểm tra quyền ghi thư mục.</div>';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Thêm sản phẩm mới</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: linear-gradient(135deg, #ff6da5ff, rgba(248, 155, 229, 1)); padding: 50px 0; min-height: 100vh; }
        .card { max-width: 800px; margin: auto; border-radius: 20px; box-shadow: 0 20px 50px rgba(0,0,0,0.15); }
        .btn-primary { background: #e3245d; border: none; font-weight: bold; }
        .btn-primary:hover { background: #ff6da5ff; }
        h3 { color: #000000ff; }
    </style>
</head>
<body>
<div class="container">
    <div class="card p-5">
        <h3 class="text-center mb-4">THÊM SẢN PHẨM MỚI</h3>
        <?= $message ?>

        <form method="POST" enctype="multipart/form-data" class="row g-3">
            <div class="col-md-6">
                <label class="form-label fw-bold">Danh mục <span class="text-danger">*</span></label>
                <select name="category_id" class="form-select" required>
                    <option value="">-- Chọn danh mục --</option>
                    <?php
                    $stmt = $pdo->query("SELECT category_id, category_name FROM category ORDER BY category_name");
                    while ($c = $stmt->fetch()) {
                        $selected = (isset($_POST['category_id']) && $_POST['category_id'] == $c['category_id']) ? 'selected' : '';
                        echo "<option value='{$c['category_id']}' $selected>" . htmlspecialchars($c['category_name']) . "</option>";
                    }
                    ?>
                </select>
            </div>

            <div class="col-md-6">
                <label class="form-label fw-bold">Tên sản phẩm <span class="text-danger">*</span></label>
                <input type="text" name="product_name" class="form-control" required placeholder="Nhập tên sản phẩm..." value="<?= htmlspecialchars($_POST['product_name'] ?? '') ?>">
            </div>

            <div class="col-md-6">
                <label class="form-label fw-bold">Giá (VNĐ) <span class="text-danger">*</span></label>
                <input type="number" name="price" class="form-control" required min="0" value="<?= $_POST['price'] ?? '' ?>">
            </div>

            <div class="col-md-6">
                <label class="form-label fw-bold">Số lượng <span class="text-danger">*</span></label>
                <input type="number" name="quantity" class="form-control" required min="1" value="<?= $_POST['quantity'] ?? '1' ?>">
            </div>

            <div class="col-12">
                <label class="form-label fw-bold">Mô tả sản phẩm</label>
                <textarea name="description" class="form-control" rows="4" placeholder="Nhập mô tả chi tiết về sản phẩm..."><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
            </div>

            <div class="col-12">
                <label class="form-label fw-bold">Hình ảnh sản phẩm <span class="text-danger">*</span></label>
                <input type="file" name="image" class="form-control" accept="image/*" required>
                <small class="text-muted">JPG, PNG, GIF, WEBP - Tối đa 5MB</small>
            </div>

            <div class="col-12 text-center mt-4">
                <button type="submit" class="btn btn-primary btn-lg px-5">THÊM SẢN PHẨM</button>
                <a href="manage_products.php" class="btn btn-outline-secondary btn-lg px-5">Quay lại</a>
            </div>
        </form>
    </div>
</div>
</body>
</html>