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

$page_title = 'Thêm Sản Phẩm Mới - BeautyShop Admin';
$admin_section_title = 'Thêm Mỹ Phẩm Mới';

$error = '';
$upload_dir = __DIR__ . '/../images/products/';
if (!is_dir($upload_dir)) {
    @mkdir($upload_dir, 0777, true);
}

// Lấy danh mục, thương hiệu và loại da
$categories = $pdo->query("SELECT category_id, category_name FROM categories ORDER BY category_name ASC")->fetchAll();
$brands     = $pdo->query("SELECT brand_id, name as brand_name FROM brands ORDER BY name ASC")->fetchAll();
$skin_types = $pdo->query("SELECT id, name FROM skin_types ORDER BY id ASC")->fetchAll();

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

    // Tạo slug từ tên sản phẩm
    $slug = trim($_POST['slug'] ?? '');
    if (empty($slug)) {
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $product_name)));
    }

    if (empty($product_name) || $category_id <= 0 || $price < 0) {
        $error = "Vui lòng nhập tên sản phẩm, danh mục và giá bán hợp lệ.";
    } else {
        // Xử lý ảnh: ưu tiên file upload, nếu không thì dùng tên file nhập hoặc ảnh mặc định
        $image_name = 'kem-duong-phuc-hoi-b5.jpg'; // mặc định

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

                $stmt = $pdo->prepare("
                    INSERT INTO products (
                        category_id, brand_id, product_name, slug, original_price, price,
                        quantity, sold_count, image, description, ingredients_text,
                        usage_instructions, capacity, skin_type_summary, is_featured,
                        is_new, is_active, rating_avg, rating_count, created_at
                    ) VALUES (
                        ?, ?, ?, ?, ?, ?,
                        ?, 0, ?, ?, ?,
                        ?, ?, ?, ?,
                        ?, ?, 5.0, 1, NOW()
                    )
                ");

                $stmt->execute([
                    $category_id, $brand_id, $product_name, $slug, $original_price, $price,
                    $quantity, $image_name, $description, $ingredients_text,
                    $usage_instructions, $capacity, $skin_type_summary, $is_featured,
                    $is_new, $is_active
                ]);

                $new_id = $pdo->lastInsertId();

                // Lưu liên kết loại da (product_skin_types)
                if (!empty($selected_skins) && is_array($selected_skins)) {
                    $skin_stmt = $pdo->prepare("INSERT INTO product_skin_types (product_id, skin_type_id) VALUES (?, ?)");
                    foreach ($selected_skins as $st_id) {
                        $skin_stmt->execute([$new_id, (int)$st_id]);
                    }
                }

                $pdo->commit();
                $_SESSION['success_msg'] = "Đã thêm mới mỹ phẩm '$product_name' thành công!";
                header("Location: manage_products.php");
                exit;
            } catch (Exception $e) {
                $pdo->rollBack();
                $error = "Lỗi khi lưu sản phẩm: " . $e->getMessage();
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
        <h4 class="fw-bold text-dark mb-1">Thêm Sản Phẩm Mỹ Phẩm Mới</h4>
        <p class="text-muted small mb-0">Nhập đầy đủ thông tin chi tiết, giá bán, tồn kho và công thức</p>
      </div>
      <a href="manage_products.php" class="btn btn-outline-secondary btn-sm rounded-pill px-3">
        <i class="bi bi-arrow-left me-1"></i> Quay lại danh sách
      </a>
    </div>

    <?php if (!empty($error)): ?>
      <div class="alert alert-danger alert-dismissible fade show rounded-3 shadow-sm d-flex align-items-center gap-2 mb-4">
        <i class="bi bi-exclamation-triangle-fill fs-5"></i>
        <div><?= htmlspecialchars($error) ?></div>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    <?php endif; ?>

    <form method="POST" action="add_product.php" enctype="multipart/form-data" class="card border-0 shadow-sm rounded-4 p-4 mb-5">
      
      <!-- PHẦN 1: THÔNG TIN CƠ BẢN -->
      <h6 class="fw-bold text-uppercase text-danger mb-3 pb-2 border-bottom" style="letter-spacing: 1px;">
        <i class="bi bi-info-circle me-1"></i> 1. Thông Tin Cơ Bản
      </h6>

      <div class="row g-3 mb-4">
        <div class="col-md-8">
          <label class="form-label small fw-semibold text-secondary">Tên sản phẩm <span class="text-danger">*</span></label>
          <input type="text" name="product_name" id="product_name" class="form-control rounded-3" 
                 placeholder="ví dụ: Kem Dưỡng Ẩm Phục Hồi Da B5 La Roche-Posay" 
                 value="<?= htmlspecialchars($_POST['product_name'] ?? '') ?>" required>
        </div>

        <div class="col-md-4">
          <label class="form-label small fw-semibold text-secondary">Dung tích / Trọng lượng</label>
          <input type="text" name="capacity" class="form-control rounded-3" 
                 placeholder="ví dụ: 50ml, 100ml, 30g..." 
                 value="<?= htmlspecialchars($_POST['capacity'] ?? '50ml') ?>">
        </div>

        <div class="col-md-6">
          <label class="form-label small fw-semibold text-secondary">Danh mục mỹ phẩm <span class="text-danger">*</span></label>
          <select name="category_id" class="form-select rounded-3" required>
            <option value="">-- Chọn danh mục --</option>
            <?php foreach ($categories as $cat): ?>
              <option value="<?= $cat['category_id'] ?>" <?= (isset($_POST['category_id']) && (int)$_POST['category_id'] === (int)$cat['category_id']) ? 'selected' : '' ?>>
                <?= htmlspecialchars($cat['category_name']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="col-md-6">
          <label class="form-label small fw-semibold text-secondary">Thương hiệu</label>
          <select name="brand_id" class="form-select rounded-3">
            <option value="">-- Chọn thương hiệu (Không bắt buộc) --</option>
            <?php foreach ($brands as $b): ?>
              <option value="<?= $b['brand_id'] ?>" <?= (isset($_POST['brand_id']) && (int)$_POST['brand_id'] === (int)$b['brand_id']) ? 'selected' : '' ?>>
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
          <label class="form-label small fw-semibold text-secondary">Giá bán thực tế (VND) <span class="text-danger">*</span></label>
          <div class="input-group">
            <input type="number" name="price" class="form-control rounded-start-3" min="0" step="1000" 
                   placeholder="250000" value="<?= htmlspecialchars($_POST['price'] ?? '') ?>" required>
            <span class="input-group-text bg-light">₫</span>
          </div>
        </div>

        <div class="col-md-4">
          <label class="form-label small fw-semibold text-secondary">Giá niêm yết gốc (Gạch chân)</label>
          <div class="input-group">
            <input type="number" name="original_price" class="form-control rounded-start-3" min="0" step="1000" 
                   placeholder="320000" value="<?= htmlspecialchars($_POST['original_price'] ?? '') ?>">
            <span class="input-group-text bg-light">₫</span>
          </div>
        </div>

        <div class="col-md-4">
          <label class="form-label small fw-semibold text-secondary">Số lượng nhập kho <span class="text-danger">*</span></label>
          <input type="number" name="quantity" class="form-control rounded-3" min="0" 
                 value="<?= htmlspecialchars($_POST['quantity'] ?? '50') ?>" required>
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
                 placeholder="ví dụ: Da dầu mụn, Da nhạy cảm..." 
                 value="<?= htmlspecialchars($_POST['skin_type_summary'] ?? 'Mọi loại da') ?>">
        </div>

        <div class="col-md-6">
          <label class="form-label small fw-semibold text-secondary d-block">Gán nhãn chuyên sâu:</label>
          <div class="d-flex flex-wrap gap-3 mt-2">
            <?php foreach ($skin_types as $st): ?>
              <div class="form-check">
                <input class="form-check-input" type="checkbox" name="skin_types[]" value="<?= $st['id'] ?>" id="st_<?= $st['id'] ?>">
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

      <div class="row g-3 mb-4">
        <div class="col-md-6">
          <label class="form-label small fw-semibold text-secondary">Tải tệp ảnh từ máy tính</label>
          <input type="file" name="image_file" class="form-control rounded-3" accept="image/*">
          <small class="text-muted">Hỗ trợ JPG, PNG, WEBP tối đa 5MB</small>
        </div>

        <div class="col-md-6">
          <label class="form-label small fw-semibold text-secondary">Hoặc dùng tên file ảnh đã có trong thư mục</label>
          <input type="text" name="image_filename" class="form-control rounded-3" 
                 placeholder="ví dụ: serum-b5-la-roche-posay.jpg"
                 value="<?= htmlspecialchars($_POST['image_filename'] ?? '') ?>">
          <small class="text-muted">Nếu để trống và không upload, hệ thống sẽ gán ảnh mặc định</small>
        </div>
      </div>

      <!-- PHẦN 5: MÔ TẢ & THÀNH PHẦN -->
      <h6 class="fw-bold text-uppercase text-danger mb-3 pb-2 border-bottom" style="letter-spacing: 1px;">
        <i class="bi bi-card-text me-1"></i> 5. Chi Tiết Công Thức & Hướng Dẫn Sử Dụng
      </h6>

      <div class="mb-3">
        <label class="form-label small fw-semibold text-secondary">Mô tả công dụng sản phẩm</label>
        <textarea name="description" class="form-control rounded-3" rows="3" 
                  placeholder="Mô tả công dụng chính, điểm nổi bật của sản phẩm..."><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
      </div>

      <div class="row g-3 mb-4">
        <div class="col-md-6">
          <label class="form-label small fw-semibold text-secondary">Bảng thành phần hoạt chất chính (Ingredients)</label>
          <textarea name="ingredients_text" class="form-control rounded-3" rows="3" 
                    placeholder="ví dụ: Panthenol 5%, Madecassoside, Zinc Gluconate, Niacinamide..."><?= htmlspecialchars($_POST['ingredients_text'] ?? '') ?></textarea>
        </div>

        <div class="col-md-6">
          <label class="form-label small fw-semibold text-secondary">Hướng dẫn sử dụng (Usage)</label>
          <textarea name="usage_instructions" class="form-control rounded-3" rows="3" 
                    placeholder="Sử dụng 2 lần mỗi ngày sau bước toner, thoa đều và vỗ nhẹ..."><?= htmlspecialchars($_POST['usage_instructions'] ?? '') ?></textarea>
        </div>
      </div>

      <!-- PHẦN 6: TRẠNG THÁI HIỂN THỊ -->
      <div class="d-flex flex-wrap gap-4 p-3 bg-light rounded-3 mb-4">
        <div class="form-check form-switch">
          <input class="form-check-input" type="checkbox" name="is_active" id="isActive" checked>
          <label class="form-check-label small fw-semibold" for="isActive">Kinh doanh ngay (Hiển thị cho khách hàng)</label>
        </div>

        <div class="form-check form-switch">
          <input class="form-check-input" type="checkbox" name="is_featured" id="isFeatured">
          <label class="form-check-label small fw-semibold text-warning" for="isFeatured">Đánh dấu Nổi bật (Gợi ý trang chủ)</label>
        </div>

        <div class="form-check form-switch">
          <input class="form-check-input" type="checkbox" name="is_new" id="isNew" checked>
          <label class="form-check-label small fw-semibold text-success" for="isNew">Huy hiệu "Hàng Mới Về"</label>
        </div>
      </div>

      <div class="d-flex justify-content-end gap-2">
        <a href="manage_products.php" class="btn btn-outline-secondary rounded-pill px-4">Hủy bỏ</a>
        <button type="submit" class="btn btn-danger rounded-pill px-5 shadow-sm" style="background: #d47a88; border-color: #d47a88;">
          <i class="bi bi-check-lg me-1"></i> Lưu Mỹ Phẩm
        </button>
      </div>

    </form>

  </div>
</div>

<?php include 'admin_footer.php'; ?>