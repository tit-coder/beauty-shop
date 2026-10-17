<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/config.php';

// Bảo vệ admin
if (!is_logged_in() || !is_admin()) {
    header("Location: login.php?redirect=" . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

$page_title = 'Chỉnh Sửa Sản Phẩm - BeautyShop Admin';
$admin_section_title = 'Chỉnh Sửa Mỹ Phẩm';

$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($product_id <= 0) {
    header("Location: manage_products.php");
    exit;
}

// Lấy thông tin sản phẩm
$stmt = $pdo->prepare("SELECT * FROM products WHERE product_id = ?");
$stmt->execute([$product_id]);
$product = $stmt->fetch();

if (!$product) {
    $_SESSION['error_msg'] = "Không tìm thấy sản phẩm #$product_id.";
    header("Location: manage_products.php");
    exit;
}

$error = '';
$upload_dir = __DIR__ . '/../images/products/';

// Lấy danh mục, thương hiệu và loại da
$categories = $pdo->query("SELECT category_id, category_name FROM categories ORDER BY category_name ASC")->fetchAll();
$brands     = $pdo->query("SELECT brand_id, name as brand_name FROM brands ORDER BY name ASC")->fetchAll();
$skin_types = $pdo->query("SELECT id, name FROM skin_types ORDER BY id ASC")->fetchAll();

// Lấy danh sách ID các loại da đã gắn cho sản phẩm
$current_skin_ids = $pdo->prepare("SELECT skin_type_id FROM product_skin_types WHERE product_id = ?");
$current_skin_ids->execute([$product_id]);
$assigned_skins = $current_skin_ids->fetchAll(PDO::FETCH_COLUMN);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_name       = trim($_POST['product_name'] ?? '');
    $category_id        = (int)($_POST['category_id'] ?? 0);
    $brand_id           = !empty($_POST['brand_id']) ? (int)$_POST['brand_id'] : null;
    $original_price     = (float)($_POST['original_price'] ?? 0);
    $price              = (float)($_POST['price'] ?? 0);
    $quantity           = max(0, (int)($_POST['quantity'] ?? 0));
    $capacity           = trim($_POST['capacity'] ?? '100ml');
    $skin_type_summary  = trim($_POST['skin_type_summary'] ?? 'Mọi loại da');
    $description        = trim($_POST['description'] ?? '');
    $ingredients_text   = trim($_POST['ingredients_text'] ?? '');
    $usage_instructions = trim($_POST['usage_instructions'] ?? '');
    $is_featured        = isset($_POST['is_featured']) ? 1 : 0;
    $is_new             = isset($_POST['is_new']) ? 1 : 0;
    $is_active          = isset($_POST['is_active']) ? 1 : 0;
    $selected_skins     = $_POST['skin_types'] ?? [];

    $slug = trim($_POST['slug'] ?? '');
    if (empty($slug)) {
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $product_name)));
    }

    if (empty($product_name) || $category_id <= 0 || $price < 0) {
        $error = "Vui lòng nhập tên sản phẩm, danh mục và giá bán hợp lệ.";
    } else {
        $image_name = $product['image']; // Giữ ảnh hiện tại

        // Upload ảnh mới nếu có
        if (!empty($_FILES['image_file']['name']) && $_FILES['image_file']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['image_file'];
            $ext  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

            if (!in_array($ext, $allowed)) {
                $error = "Định dạng file không hợp lệ (chỉ chấp nhận JPG, PNG, WEBP).";
            } elseif ($file['size'] > 5 * 1024 * 1024) {
                $error = "Dung lượng ảnh không được vượt quá 5MB.";
            } else {
                $safe_filename = time() . '_' . preg_replace('/[^a-zA-Z0-9_-]/', '', pathinfo($file['name'], PATHINFO_FILENAME)) . '.' . $ext;
                if (move_uploaded_file($file['tmp_name'], $upload_dir . $safe_filename)) {
                    $image_name = $safe_filename;
                }
            }
        } elseif (!empty($_POST['image_filename'])) {
            $image_name = trim($_POST['image_filename']);
        }

        if (empty($error)) {
            try {
                $pdo->beginTransaction();

                if ($original_price <= 0) {
                    $original_price = $price;
                }

                $up_stmt = $pdo->prepare("
                    UPDATE products SET 
                        category_id = ?, brand_id = ?, product_name = ?, slug = ?,
                        original_price = ?, price = ?, quantity = ?, image = ?,
                        description = ?, ingredients_text = ?, usage_instructions = ?,
                        capacity = ?, skin_type_summary = ?, is_featured = ?,
                        is_new = ?, is_active = ?, updated_at = NOW()
                    WHERE product_id = ?
                ");

                $up_stmt->execute([
                    $category_id, $brand_id, $product_name, $slug,
                    $original_price, $price, $quantity, $image_name,
                    $description, $ingredients_text, $usage_instructions,
                    $capacity, $skin_type_summary, $is_featured,
                    $is_new, $is_active, $product_id
                ]);

                // Cập nhật quan hệ loại da
                $pdo->prepare("DELETE FROM product_skin_types WHERE product_id = ?")->execute([$product_id]);
                if (!empty($selected_skins) && is_array($selected_skins)) {
                    $skin_stmt = $pdo->prepare("INSERT INTO product_skin_types (product_id, skin_type_id) VALUES (?, ?)");
                    foreach ($selected_skins as $st_id) {
                        $skin_stmt->execute([$product_id, (int)$st_id]);
                    }
                }

                $pdo->commit();
                $_SESSION['success_msg'] = "Đã cập nhật sản phẩm #$product_id thành công!";
                header("Location: manage_products.php");
                exit;
            } catch (Exception $e) {
                $pdo->rollBack();
                $error = "Lỗi khi cập nhật sản phẩm: " . $e->getMessage();
            }
        }
    }
}

include 'admin_header.php';
?>

<div class="row justify-content-center">
  <div class="col-lg-10">

    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h4 class="fw-bold text-dark mb-1">Chỉnh Sửa Sản Phẩm #<?= $product['product_id'] ?></h4>
        <p class="text-muted small mb-0"><?= htmlspecialchars($product['product_name']) ?></p>
      </div>
      <div class="d-flex gap-2">
        <a href="product_detail.php?id=<?= $product['product_id'] ?>" target="_blank" class="btn btn-outline-info btn-sm rounded-pill px-3">
          <i class="bi bi-box-arrow-up-right me-1"></i> Xem trang khách
        </a>
        <a href="manage_products.php" class="btn btn-outline-secondary btn-sm rounded-pill px-3">
          <i class="bi bi-arrow-left me-1"></i> Quay lại
        </a>
      </div>
    </div>

    <?php if (!empty($error)): ?>
      <div class="alert alert-danger alert-dismissible fade show rounded-3 shadow-sm d-flex align-items-center gap-2 mb-4">
        <i class="bi bi-exclamation-triangle-fill fs-5"></i>
        <div><?= htmlspecialchars($error) ?></div>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    <?php endif; ?>

    <form method="POST" action="edit_product.php?id=<?= $product['product_id'] ?>" enctype="multipart/form-data" class="card border-0 shadow-sm rounded-4 p-4 mb-5">
      
      <!-- PHẦN 1: THÔNG TIN CƠ BẢN -->
      <h6 class="fw-bold text-uppercase text-danger mb-3 pb-2 border-bottom" style="letter-spacing: 1px;">
        <i class="bi bi-info-circle me-1"></i> 1. Thông Tin Cơ Bản
      </h6>

      <div class="row g-3 mb-4">
        <div class="col-md-8">
          <label class="form-label small fw-semibold text-secondary">Tên sản phẩm <span class="text-danger">*</span></label>
          <input type="text" name="product_name" id="product_name" class="form-control rounded-3" 
                 value="<?= htmlspecialchars($product['product_name']) ?>" required>
        </div>

        <div class="col-md-4">
          <label class="form-label small fw-semibold text-secondary">Dung tích / Trọng lượng</label>
          <input type="text" name="capacity" class="form-control rounded-3" 
                 value="<?= htmlspecialchars($product['capacity'] ?? '100ml') ?>">
        </div>

        <div class="col-md-4">
          <label class="form-label small fw-semibold text-secondary">Đường dẫn tĩnh (Slug URL)</label>
          <input type="text" name="slug" class="form-control rounded-3" 
                 value="<?= htmlspecialchars($product['slug']) ?>">
        </div>

        <div class="col-md-4">
          <label class="form-label small fw-semibold text-secondary">Danh mục mỹ phẩm <span class="text-danger">*</span></label>
          <select name="category_id" class="form-select rounded-3" required>
            <?php foreach ($categories as $cat): ?>
              <option value="<?= $cat['category_id'] ?>" <?= (int)$product['category_id'] === (int)$cat['category_id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($cat['category_name']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="col-md-4">
          <label class="form-label small fw-semibold text-secondary">Thương hiệu</label>
          <select name="brand_id" class="form-select rounded-3">
            <option value="">-- Chưa chọn thương hiệu --</option>
            <?php foreach ($brands as $b): ?>
              <option value="<?= $b['brand_id'] ?>" <?= (int)$product['brand_id'] === (int)$b['brand_id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($b['brand_name']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <!-- PHẦN 2: GIÁ & TỒN KHO -->
      <h6 class="fw-bold text-uppercase text-danger mb-3 pb-2 border-bottom" style="letter-spacing: 1px;">
        <i class="bi bi-tags me-1"></i> 2. Giá Bán & Quản Lý Kho
      </h6>

      <div class="row g-3 mb-4">
        <div class="col-md-4">
          <label class="form-label small fw-semibold text-secondary">Giá bán khuyến mãi (VND) <span class="text-danger">*</span></label>
          <div class="input-group">
            <input type="number" name="price" class="form-control rounded-start-3" min="0" step="1000" 
                   value="<?= (int)$product['price'] ?>" required>
            <span class="input-group-text bg-light">₫</span>
          </div>
        </div>

        <div class="col-md-4">
          <label class="form-label small fw-semibold text-secondary">Giá gốc niêm yết (Gạch chân)</label>
          <div class="input-group">
            <input type="number" name="original_price" class="form-control rounded-start-3" min="0" step="1000" 
                   value="<?= (int)$product['original_price'] ?>">
            <span class="input-group-text bg-light">₫</span>
          </div>
        </div>

        <div class="col-md-4">
          <label class="form-label small fw-semibold text-secondary">Số lượng tồn kho <span class="text-danger">*</span></label>
          <input type="number" name="quantity" class="form-control rounded-3" min="0" 
                 value="<?= (int)$product['quantity'] ?>" required>
        </div>
      </div>

      <!-- PHẦN 3: PHÙ HỢP LOẠI DA -->
      <h6 class="fw-bold text-uppercase text-danger mb-3 pb-2 border-bottom" style="letter-spacing: 1px;">
        <i class="bi bi-stars me-1"></i> 3. Loại Da Phù Hợp (Dành cho AI Tư Vấn & Bộ Lọc)
      </h6>

      <div class="row g-3 mb-4">
        <div class="col-md-6">
          <label class="form-label small fw-semibold text-secondary">Tóm tắt loại da hiển thị trên thẻ</label>
          <input type="text" name="skin_type_summary" class="form-control rounded-3" 
                 value="<?= htmlspecialchars($product['skin_type_summary'] ?? 'Mọi loại da') ?>">
        </div>

        <div class="col-md-6">
          <label class="form-label small fw-semibold text-secondary d-block">Gán nhãn chuyên sâu:</label>
          <div class="d-flex flex-wrap gap-3 mt-2">
            <?php foreach ($skin_types as $st): ?>
              <div class="form-check">
                <input class="form-check-input" type="checkbox" name="skin_types[]" value="<?= $st['id'] ?>" 
                       id="st_<?= $st['id'] ?>" <?= in_array($st['id'], $assigned_skins) ? 'checked' : '' ?>>
                <label class="form-check-label small" for="st_<?= $st['id'] ?>"><?= htmlspecialchars($st['name']) ?></label>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>

      <!-- PHẦN 4: HÌNH ẢNH SẢN PHẨM -->
      <h6 class="fw-bold text-uppercase text-danger mb-3 pb-2 border-bottom" style="letter-spacing: 1px;">
        <i class="bi bi-image me-1"></i> 4. Hình Ảnh Sản Phẩm
      </h6>

      <div class="row g-3 mb-4 align-items-center">
        <div class="col-md-2 text-center">
          <?php 
            $current_img_src = (strpos($product['image'], 'http') === 0) ? $product['image'] : '../images/products/' . $product['image'];
          ?>
          <img src="<?= htmlspecialchars($current_img_src) ?>" alt="Ảnh hiện tại" 
               class="rounded-3 border shadow-sm img-thumbnail" style="width: 100px; height: 100px; object-fit: cover;"
               onerror="this.src='../images/products/kem-duong-phuc-hoi-b5.jpg'">
          <div class="small text-muted mt-1">Ảnh hiện tại</div>
        </div>

        <div class="col-md-5">
          <label class="form-label small fw-semibold text-secondary">Tải tệp ảnh mới (thay thế)</label>
          <input type="file" name="image_file" class="form-control rounded-3" accept="image/*">
          <small class="text-muted">Chọn tệp nếu muốn đổi ảnh khác</small>
        </div>

        <div class="col-md-5">
          <label class="form-label small fw-semibold text-secondary">Hoặc đổi tên file ảnh trực tiếp</label>
          <input type="text" name="image_filename" class="form-control rounded-3" 
                 value="<?= htmlspecialchars($product['image']) ?>">
          <small class="text-muted">Tên file trong thư mục images/products/</small>
        </div>
      </div>

      <!-- PHẦN 5: MÔ TẢ & THÀNH PHẦN -->
      <h6 class="fw-bold text-uppercase text-danger mb-3 pb-2 border-bottom" style="letter-spacing: 1px;">
        <i class="bi bi-card-text me-1"></i> 5. Chi Tiết Công Thức & Hướng Dẫn Sử Dụng
      </h6>

      <div class="mb-3">
        <label class="form-label small fw-semibold text-secondary">Mô tả công dụng sản phẩm</label>
        <textarea name="description" class="form-control rounded-3" rows="3"><?= htmlspecialchars($product['description'] ?? '') ?></textarea>
      </div>

      <div class="row g-3 mb-4">
        <div class="col-md-6">
          <label class="form-label small fw-semibold text-secondary">Bảng thành phần hoạt chất chính (Ingredients)</label>
          <textarea name="ingredients_text" class="form-control rounded-3" rows="3"><?= htmlspecialchars($product['ingredients_text'] ?? '') ?></textarea>
        </div>

        <div class="col-md-6">
          <label class="form-label small fw-semibold text-secondary">Hướng dẫn sử dụng (Usage)</label>
          <textarea name="usage_instructions" class="form-control rounded-3" rows="3"><?= htmlspecialchars($product['usage_instructions'] ?? '') ?></textarea>
        </div>
      </div>

      <!-- PHẦN 6: TRẠNG THÁI HIỂN THỊ -->
      <div class="d-flex flex-wrap gap-4 p-3 bg-light rounded-3 mb-4">
        <div class="form-check form-switch">
          <input class="form-check-input" type="checkbox" name="is_active" id="isActive" <?= $product['is_active'] ? 'checked' : '' ?>>
          <label class="form-check-label small fw-semibold" for="isActive">Kinh doanh (Hiển thị cho khách hàng)</label>
        </div>

        <div class="form-check form-switch">
          <input class="form-check-input" type="checkbox" name="is_featured" id="isFeatured" <?= $product['is_featured'] ? 'checked' : '' ?>>
          <label class="form-check-label small fw-semibold text-warning" for="isFeatured">Huy hiệu Nổi bật (Trang chủ)</label>
        </div>

        <div class="form-check form-switch">
          <input class="form-check-input" type="checkbox" name="is_new" id="isNew" <?= $product['is_new'] ? 'checked' : '' ?>>
          <label class="form-check-label small fw-semibold text-success" for="isNew">Huy hiệu "Hàng Mới Về"</label>
        </div>
      </div>

      <div class="d-flex justify-content-end gap-2">
        <a href="manage_products.php" class="btn btn-outline-secondary rounded-pill px-4">Hủy bỏ</a>
        <button type="submit" class="btn btn-danger rounded-pill px-5 shadow-sm" style="background: #d47a88; border-color: #d47a88;">
          <i class="bi bi-save me-1"></i> Cập Nhật Sản Phẩm
        </button>
      </div>

    </form>

  </div>
</div>

<?php include 'admin_footer.php'; ?>
