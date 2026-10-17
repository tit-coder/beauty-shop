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

$page_title = 'Quản Lý Danh Mục & Thương Hiệu - BeautyShop Admin';
$admin_section_title = 'Danh Mục & Thương Hiệu';

$tab = $_GET['tab'] ?? 'categories'; // 'categories' hoặc 'brands'
$message = '';
$error = '';

// ==========================================
// XỬ LÝ ACTIONS DANH MỤC
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_category'])) {
    $act = $_POST['action_category'];
    if ($act === 'add') {
        $cat_name = trim($_POST['category_name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $sort_order = (int)($_POST['sort_order'] ?? 0);
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $cat_name)));

        if (empty($cat_name)) {
            $error = "Vui lòng nhập tên danh mục.";
        } else {
            try {
                $stmt = $pdo->prepare("INSERT INTO categories (category_name, slug, description, sort_order, is_active) VALUES (?, ?, ?, ?, 1)");
                $stmt->execute([$cat_name, $slug, $description, $sort_order]);
                $message = "Đã thêm danh mục mới '$cat_name' thành công!";
            } catch (Exception $e) {
                $error = "Lỗi khi thêm danh mục: " . $e->getMessage();
            }
        }
    } elseif ($act === 'edit') {
        $cat_id = (int)$_POST['category_id'];
        $cat_name = trim($_POST['category_name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $sort_order = (int)($_POST['sort_order'] ?? 0);
        $is_active = isset($_POST['is_active']) ? 1 : 0;

        if (empty($cat_name) || $cat_id <= 0) {
            $error = "Tên danh mục không được để trống.";
        } else {
            try {
                $stmt = $pdo->prepare("UPDATE categories SET category_name = ?, description = ?, sort_order = ?, is_active = ? WHERE category_id = ?");
                $stmt->execute([$cat_name, $description, $sort_order, $is_active, $cat_id]);
                $message = "Đã cập nhật danh mục #$cat_id thành công!";
            } catch (Exception $e) {
                $error = "Lỗi cập nhật danh mục: " . $e->getMessage();
            }
        }
    } elseif ($act === 'delete') {
        $cat_id = (int)$_POST['category_id'];
        try {
            // Kiểm tra số sản phẩm trực thuộc
            $check = $pdo->prepare("SELECT COUNT(*) FROM products WHERE category_id = ?");
            $check->execute([$cat_id]);
            $count = $check->fetchColumn();

            if ($count > 0) {
                $error = "Không thể xóa danh mục này vì đang có $count sản phẩm trực thuộc! Vui lòng chuyển sản phẩm sang danh mục khác trước.";
            } else {
                $pdo->prepare("DELETE FROM categories WHERE category_id = ?")->execute([$cat_id]);
                $message = "Đã xóa danh mục thành công!";
            }
        } catch (Exception $e) {
            $error = "Lỗi xóa danh mục: " . $e->getMessage();
        }
    }
}

// ==========================================
// XỬ LÝ ACTIONS THƯƠNG HIỆU
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_brand'])) {
    $act = $_POST['action_brand'];
    if ($act === 'add') {
        $b_name = trim($_POST['brand_name'] ?? '');
        $origin = trim($_POST['origin_country'] ?? '');
        $desc = trim($_POST['description'] ?? '');
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $b_name)));

        if (empty($b_name)) {
            $error = "Vui lòng nhập tên thương hiệu.";
        } else {
            try {
                $stmt = $pdo->prepare("INSERT INTO brands (name, slug, origin_country, description, is_active) VALUES (?, ?, ?, ?, 1)");
                $stmt->execute([$b_name, $slug, $origin, $desc]);
                $message = "Đã thêm thương hiệu '$b_name' thành công!";
            } catch (Exception $e) {
                $error = "Lỗi thêm thương hiệu: " . $e->getMessage();
            }
        }
    } elseif ($act === 'edit') {
        $b_id = (int)$_POST['brand_id'];
        $b_name = trim($_POST['brand_name'] ?? '');
        $origin = trim($_POST['origin_country'] ?? '');
        $desc = trim($_POST['description'] ?? '');
        $is_active = isset($_POST['is_active']) ? 1 : 0;

        if (empty($b_name) || $b_id <= 0) {
            $error = "Tên thương hiệu không hợp lệ.";
        } else {
            try {
                $stmt = $pdo->prepare("UPDATE brands SET name = ?, origin_country = ?, description = ?, is_active = ? WHERE brand_id = ?");
                $stmt->execute([$b_name, $origin, $desc, $is_active, $b_id]);
                $message = "Đã cập nhật thương hiệu #$b_id thành công!";
            } catch (Exception $e) {
                $error = "Lỗi cập nhật thương hiệu: " . $e->getMessage();
            }
        }
    } elseif ($act === 'delete') {
        $b_id = (int)$_POST['brand_id'];
        try {
            // Cập nhật các sản phẩm sang brand_id = null
            $pdo->prepare("UPDATE products SET brand_id = NULL WHERE brand_id = ?")->execute([$b_id]);
            $pdo->prepare("DELETE FROM brands WHERE brand_id = ?")->execute([$b_id]);
            $message = "Đã xóa thương hiệu thành công!";
        } catch (Exception $e) {
            $error = "Lỗi khi xóa thương hiệu: " . $e->getMessage();
        }
    }
}

// Lấy dữ liệu danh mục cùng số lượng sản phẩm
$categories_list = $pdo->query("
    SELECT c.*, COUNT(p.product_id) as total_products
    FROM categories c
    LEFT JOIN products p ON c.category_id = p.category_id
    GROUP BY c.category_id
    ORDER BY c.sort_order ASC, c.category_name ASC
")->fetchAll();

// Lấy dữ liệu thương hiệu cùng số lượng sản phẩm
$brands_list = $pdo->query("
    SELECT b.*, COUNT(p.product_id) as total_products
    FROM brands b
    LEFT JOIN products p ON b.brand_id = p.brand_id
    GROUP BY b.brand_id
    ORDER BY b.name ASC
")->fetchAll();

include 'admin_header.php';
?>

<?php if (!empty($message)): ?>
  <div class="alert alert-success alert-dismissible fade show rounded-3 shadow-sm d-flex align-items-center gap-2 mb-4">
    <i class="bi bi-check-circle-fill fs-5"></i>
    <div><?= htmlspecialchars($message) ?></div>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
<?php endif; ?>

<?php if (!empty($error)): ?>
  <div class="alert alert-danger alert-dismissible fade show rounded-3 shadow-sm d-flex align-items-center gap-2 mb-4">
    <i class="bi bi-exclamation-triangle-fill fs-5"></i>
    <div><?= htmlspecialchars($error) ?></div>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
<?php endif; ?>

<!-- THANH CHUYỂN TABS -->
<div class="d-flex justify-content-between align-items-center mb-4">
  <ul class="nav nav-pills bg-white p-1 rounded-pill shadow-sm border">
    <li class="nav-item">
      <a class="nav-link rounded-pill px-4 <?= $tab === 'categories' ? 'active bg-danger' : 'text-dark' ?>" 
         href="manage_categories.php?tab=categories" style="<?= $tab === 'categories' ? 'background: #d47a88 !important;' : '' ?>">
        <i class="bi bi-collection me-1"></i> Danh Mục Mỹ Phẩm (<?= count($categories_list) ?>)
      </a>
    </li>
    <li class="nav-item">
      <a class="nav-link rounded-pill px-4 <?= $tab === 'brands' ? 'active bg-danger' : 'text-dark' ?>" 
         href="manage_categories.php?tab=brands" style="<?= $tab === 'brands' ? 'background: #d47a88 !important;' : '' ?>">
        <i class="bi bi-award me-1"></i> Thương Hiệu Đối Tác (<?= count($brands_list) ?>)
      </a>
    </li>
  </ul>

  <?php if ($tab === 'categories'): ?>
    <button class="btn btn-danger btn-sm rounded-pill px-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#addCategoryModal" style="background: #d47a88; border-color: #d47a88;">
      <i class="bi bi-plus-lg me-1"></i> Thêm Danh Mục Mới
    </button>
  <?php else: ?>
    <button class="btn btn-danger btn-sm rounded-pill px-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#addBrandModal" style="background: #d47a88; border-color: #d47a88;">
      <i class="bi bi-plus-lg me-1"></i> Thêm Thương Hiệu Mới
    </button>
  <?php endif; ?>
</div>

<?php if ($tab === 'categories'): ?>
  <!-- BẢNG DANH MỤC -->
  <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light text-uppercase text-secondary" style="font-size: 0.75rem; letter-spacing: 0.5px;">
          <tr>
            <th class="ps-4" style="width: 70px;">Thứ tự</th>
            <th>Tên Danh Mục</th>
            <th>Slug URL</th>
            <th>Mô Tả</th>
            <th>Số Lượng Sản Phẩm</th>
            <th>Trạng Thái</th>
            <th class="text-end pe-4">Thao Tác</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($categories_list as $cat): ?>
            <tr>
              <td class="ps-4 fw-bold text-muted"><?= $cat['sort_order'] ?></td>
              <td>
                <div class="fw-bold text-dark"><?= htmlspecialchars($cat['category_name']) ?></div>
                <small class="text-muted">ID: #<?= $cat['category_id'] ?></small>
              </td>
              <td><code><?= htmlspecialchars($cat['slug']) ?></code></td>
              <td>
                <div class="small text-muted text-truncate" style="max-width: 300px;">
                  <?= htmlspecialchars($cat['description'] ?? 'Chưa có mô tả') ?>
                </div>
              </td>
              <td>
                <a href="manage_products.php?category_id=<?= $cat['category_id'] ?>" class="badge bg-light text-dark border text-decoration-none">
                  <?= $cat['total_products'] ?> sản phẩm
                </a>
              </td>
              <td>
                <span class="badge rounded-pill <?= $cat['is_active'] ? 'bg-success' : 'bg-secondary' ?>">
                  <?= $cat['is_active'] ? 'Hiển thị' : 'Đang ẩn' ?>
                </span>
              </td>
              <td class="text-end pe-4">
                <button class="btn btn-sm btn-outline-primary rounded-pill me-1" 
                        onclick="editCategory(<?= htmlspecialchars(json_encode($cat)) ?>)">
                  <i class="bi bi-pencil-square"></i>
                </button>
                <form method="POST" style="display:inline;" onsubmit="return confirm('Bạn có chắc muốn xóa danh mục này?');">
                  <input type="hidden" name="action_category" value="delete">
                  <input type="hidden" name="category_id" value="<?= $cat['category_id'] ?>">
                  <button type="submit" class="btn btn-sm btn-outline-danger rounded-pill" <?= $cat['total_products'] > 0 ? 'disabled title="Còn sản phẩm, không thể xóa"' : '' ?>>
                    <i class="bi bi-trash3"></i>
                  </button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

<?php else: ?>
  <!-- BẢNG THƯƠNG HIỆU -->
  <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light text-uppercase text-secondary" style="font-size: 0.75rem; letter-spacing: 0.5px;">
          <tr>
            <th class="ps-4">Thương Hiệu</th>
            <th>Xuất Xứ</th>
            <th>Mô Tả Thương Hiệu</th>
            <th>Sản Phẩm Trong Kho</th>
            <th>Trạng Thái</th>
            <th class="text-end pe-4">Thao Tác</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($brands_list as $b): ?>
            <tr>
              <td class="ps-4">
                <div class="fw-bold text-dark fs-6"><?= htmlspecialchars($b['name']) ?></div>
                <small class="text-muted">Slug: <code><?= htmlspecialchars($b['slug']) ?></code></small>
              </td>
              <td>
                <span class="badge bg-light text-dark border">
                  <i class="bi bi-globe-americas me-1"></i><?= htmlspecialchars($b['origin_country'] ?? 'Quốc tế') ?>
                </span>
              </td>
              <td>
                <div class="small text-muted text-truncate" style="max-width: 350px;">
                  <?= htmlspecialchars($b['description'] ?? 'Thương hiệu mỹ phẩm chính hãng tại BeautyShop') ?>
                </div>
              </td>
              <td>
                <a href="manage_products.php?brand_id=<?= $b['brand_id'] ?>" class="badge bg-light text-danger border text-decoration-none">
                  <?= $b['total_products'] ?> sản phẩm
                </a>
              </td>
              <td>
                <span class="badge rounded-pill <?= $b['is_active'] ? 'bg-success' : 'bg-secondary' ?>">
                  <?= $b['is_active'] ? 'Hợp tác' : 'Tạm dừng' ?>
                </span>
              </td>
              <td class="text-end pe-4">
                <button class="btn btn-sm btn-outline-primary rounded-pill me-1" 
                        onclick="editBrand(<?= htmlspecialchars(json_encode($b)) ?>)">
                  <i class="bi bi-pencil-square"></i>
                </button>
                <form method="POST" style="display:inline;" onsubmit="return confirm('Bạn có chắc muốn xóa thương hiệu này?');">
                  <input type="hidden" name="action_brand" value="delete">
                  <input type="hidden" name="brand_id" value="<?= $b['brand_id'] ?>">
                  <button type="submit" class="btn btn-sm btn-outline-danger rounded-pill">
                    <i class="bi bi-trash3"></i>
                  </button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
<?php endif; ?>

<!-- MODAL THÊM DANH MỤC -->
<div class="modal fade" id="addCategoryModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content rounded-4 border-0 shadow">
      <form method="POST">
        <input type="hidden" name="action_category" value="add">
        <div class="modal-header">
          <h5 class="modal-title fw-bold">Thêm Danh Mục Mỹ Phẩm Mới</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label small fw-semibold">Tên danh mục <span class="text-danger">*</span></label>
            <input type="text" name="category_name" class="form-control" placeholder="ví dụ: Serum & Tinh Chất" required>
          </div>
          <div class="mb-3">
            <label class="form-label small fw-semibold">Thứ tự hiển thị</label>
            <input type="number" name="sort_order" class="form-control" value="0">
          </div>
          <div class="mb-3">
            <label class="form-label small fw-semibold">Mô tả ngắn</label>
            <textarea name="description" class="form-control" rows="3" placeholder="Mô tả nhóm sản phẩm..."></textarea>
          </div>
        </div>
        <div class="modal-footer border-0">
          <button type="button" class="btn btn-secondary btn-sm rounded-pill" data-bs-dismiss="modal">Đóng</button>
          <button type="submit" class="btn btn-danger btn-sm rounded-pill px-3" style="background:#d47a88; border-color:#d47a88;">Lưu Danh Mục</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- MODAL SỬA DANH MỤC -->
<div class="modal fade" id="editCategoryModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content rounded-4 border-0 shadow">
      <form method="POST">
        <input type="hidden" name="action_category" value="edit">
        <input type="hidden" name="category_id" id="edit_cat_id">
        <div class="modal-header">
          <h5 class="modal-title fw-bold">Cập Nhật Danh Mục</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label small fw-semibold">Tên danh mục <span class="text-danger">*</span></label>
            <input type="text" name="category_name" id="edit_cat_name" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label small fw-semibold">Thứ tự hiển thị</label>
            <input type="number" name="sort_order" id="edit_cat_order" class="form-control">
          </div>
          <div class="mb-3">
            <label class="form-label small fw-semibold">Mô tả ngắn</label>
            <textarea name="description" id="edit_cat_desc" class="form-control" rows="3"></textarea>
          </div>
          <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" name="is_active" id="edit_cat_active">
            <label class="form-check-label small" for="edit_cat_active">Hiển thị trên website</label>
          </div>
        </div>
        <div class="modal-footer border-0">
          <button type="button" class="btn btn-secondary btn-sm rounded-pill" data-bs-dismiss="modal">Đóng</button>
          <button type="submit" class="btn btn-danger btn-sm rounded-pill px-3" style="background:#d47a88; border-color:#d47a88;">Cập Nhật</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- MODAL THÊM THƯƠNG HIỆU -->
<div class="modal fade" id="addBrandModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content rounded-4 border-0 shadow">
      <form method="POST">
        <input type="hidden" name="action_brand" value="add">
        <div class="modal-header">
          <h5 class="modal-title fw-bold">Thêm Thương Hiệu Mới</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label small fw-semibold">Tên thương hiệu <span class="text-danger">*</span></label>
            <input type="text" name="brand_name" class="form-control" placeholder="ví dụ: Kiehl's" required>
          </div>
          <div class="mb-3">
            <label class="form-label small fw-semibold">Quốc gia xuất xứ</label>
            <input type="text" name="origin_country" class="form-control" placeholder="ví dụ: Mỹ, Hàn Quốc, Pháp...">
          </div>
          <div class="mb-3">
            <label class="form-label small fw-semibold">Mô tả tóm tắt</label>
            <textarea name="description" class="form-control" rows="3" placeholder="Giới thiệu thương hiệu..."></textarea>
          </div>
        </div>
        <div class="modal-footer border-0">
          <button type="button" class="btn btn-secondary btn-sm rounded-pill" data-bs-dismiss="modal">Đóng</button>
          <button type="submit" class="btn btn-danger btn-sm rounded-pill px-3" style="background:#d47a88; border-color:#d47a88;">Lưu Thương Hiệu</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- MODAL SỬA THƯƠNG HIỆU -->
<div class="modal fade" id="editBrandModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content rounded-4 border-0 shadow">
      <form method="POST">
        <input type="hidden" name="action_brand" value="edit">
        <input type="hidden" name="brand_id" id="edit_b_id">
        <div class="modal-header">
          <h5 class="modal-title fw-bold">Cập Nhật Thương Hiệu</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label small fw-semibold">Tên thương hiệu <span class="text-danger">*</span></label>
            <input type="text" name="brand_name" id="edit_b_name" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label small fw-semibold">Quốc gia xuất xứ</label>
            <input type="text" name="origin_country" id="edit_b_origin" class="form-control">
          </div>
          <div class="mb-3">
            <label class="form-label small fw-semibold">Mô tả tóm tắt</label>
            <textarea name="description" id="edit_b_desc" class="form-control" rows="3"></textarea>
          </div>
          <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" name="is_active" id="edit_b_active">
            <label class="form-check-label small" for="edit_b_active">Trạng thái hợp tác</label>
          </div>
        </div>
        <div class="modal-footer border-0">
          <button type="button" class="btn btn-secondary btn-sm rounded-pill" data-bs-dismiss="modal">Đóng</button>
          <button type="submit" class="btn btn-danger btn-sm rounded-pill px-3" style="background:#d47a88; border-color:#d47a88;">Cập Nhật</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
function editCategory(cat) {
  document.getElementById('edit_cat_id').value = cat.category_id;
  document.getElementById('edit_cat_name').value = cat.category_name;
  document.getElementById('edit_cat_order').value = cat.sort_order;
  document.getElementById('edit_cat_desc').value = cat.description || '';
  document.getElementById('edit_cat_active').checked = cat.is_active == 1;
  new bootstrap.Modal(document.getElementById('editCategoryModal')).show();
}

function editBrand(b) {
  document.getElementById('edit_b_id').value = b.brand_id;
  document.getElementById('edit_b_name').value = b.name;
  document.getElementById('edit_b_origin').value = b.origin_country || '';
  document.getElementById('edit_b_desc').value = b.description || '';
  document.getElementById('edit_b_active').checked = b.is_active == 1;
  new bootstrap.Modal(document.getElementById('editBrandModal')).show();
}
</script>

<?php include 'admin_footer.php'; ?>

