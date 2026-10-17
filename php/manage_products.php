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

$page_title = 'Quản Lý Sản Phẩm - BeautyShop Admin';
$admin_section_title = 'Quản Lý Sản Phẩm';

$message = $_SESSION['success_msg'] ?? '';
$error   = $_SESSION['error_msg'] ?? '';
unset($_SESSION['success_msg'], $_SESSION['error_msg']);

// ==========================================
// XỬ LÝ NHANH CÁC THAO TÁC AJAX / POST
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $product_id = (int)($_POST['product_id'] ?? 0);
    $action = $_POST['action'];

    if ($product_id > 0) {
        try {
            if ($action === 'toggle_active') {
                $stmt = $pdo->prepare("UPDATE products SET is_active = IF(is_active = 1, 0, 1) WHERE product_id = ?");
                $stmt->execute([$product_id]);
                $message = "Đã thay đổi trạng thái hiển thị của sản phẩm #$product_id.";
            } elseif ($action === 'toggle_featured') {
                $stmt = $pdo->prepare("UPDATE products SET is_featured = IF(is_featured = 1, 0, 1) WHERE product_id = ?");
                $stmt->execute([$product_id]);
                $message = "Đã cập nhật huy hiệu Nổi bật cho sản phẩm #$product_id.";
            } elseif ($action === 'quick_stock') {
                $new_qty = max(0, (int)($_POST['new_quantity'] ?? 0));
                $stmt = $pdo->prepare("UPDATE products SET quantity = ? WHERE product_id = ?");
                $stmt->execute([$new_qty, $product_id]);
                $message = "Đã cập nhật tồn kho sản phẩm #$product_id thành $new_qty.";
            }
        } catch (Exception $e) {
            $error = "Lỗi thao tác: " . $e->getMessage();
        }
    }
}

// ==========================================
// BỘ LỌC VÀ TRUY VẤN DỮ LIỆU
// ==========================================
$cat_id = (int)($_GET['category_id'] ?? 0);
$brand_id = (int)($_GET['brand_id'] ?? 0);
$stock_status = $_GET['stock'] ?? 'all';
$active_filter = $_GET['status'] ?? 'all';
$search = trim($_GET['search'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 12;
$offset = ($page - 1) * $limit;

$where_clauses = ["1=1"];
$params = [];

if ($cat_id > 0) {
    $where_clauses[] = "p.category_id = ?";
    $params[] = $cat_id;
}

if ($brand_id > 0) {
    $where_clauses[] = "p.brand_id = ?";
    $params[] = $brand_id;
}

if ($stock_status === 'out') {
    $where_clauses[] = "p.quantity <= 0";
} elseif ($stock_status === 'low') {
    $where_clauses[] = "p.quantity > 0 AND p.quantity <= 5";
} elseif ($stock_status === 'in') {
    $where_clauses[] = "p.quantity > 5";
}

if ($active_filter === 'active') {
    $where_clauses[] = "p.is_active = 1";
} elseif ($active_filter === 'hidden') {
    $where_clauses[] = "p.is_active = 0";
}

if (!empty($search)) {
    $where_clauses[] = "(p.product_name LIKE ? OR p.slug LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$where_sql = implode(" AND ", $where_clauses);

// Tổng số sản phẩm thỏa điều kiện
$count_stmt = $pdo->prepare("SELECT COUNT(*) FROM products p WHERE $where_sql");
$count_stmt->execute($params);
$total_products = $count_stmt->fetchColumn();
$total_pages = ceil($total_products / $limit);

// Lấy danh sách sản phẩm
$sql = "
    SELECT p.*, c.category_name, b.name as brand_name
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.category_id
    LEFT JOIN brands b ON p.brand_id = b.brand_id
    WHERE $where_sql
    ORDER BY p.product_id DESC
    LIMIT $limit OFFSET $offset
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

// Thống kê nhanh toàn bộ catalog
$stat = $pdo->query("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active_cnt,
        SUM(CASE WHEN quantity <= 5 THEN 1 ELSE 0 END) as low_stock_cnt,
        SUM(sold_count) as total_sold
    FROM products
")->fetch();

// Danh sách danh mục & thương hiệu để lọc
$categories = $pdo->query("SELECT category_id, category_name FROM categories ORDER BY category_name ASC")->fetchAll();
$brands = $pdo->query("SELECT brand_id, name as brand_name FROM brands ORDER BY name ASC")->fetchAll();

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

<!-- 1. THỐNG KÊ NHANH KHO SẢN PHẨM -->
<div class="row g-3 mb-4">
  <div class="col-6 col-md-3">
    <div class="stat-card-modern p-3 text-center">
      <div class="text-muted small fw-semibold">Tổng Sản Phẩm</div>
      <div class="fs-4 fw-bold text-dark mt-1"><?= number_format($stat['total'] ?? 0) ?></div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="stat-card-modern p-3 text-center border-start border-success border-4">
      <div class="text-success small fw-semibold">Đang Kinh Doanh</div>
      <div class="fs-4 fw-bold text-success mt-1"><?= number_format($stat['active_cnt'] ?? 0) ?></div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="stat-card-modern p-3 text-center border-start border-warning border-4">
      <div class="text-warning small fw-semibold">Sắp / Đã Hết Hàng</div>
      <div class="fs-4 fw-bold text-warning mt-1"><?= number_format($stat['low_stock_cnt'] ?? 0) ?></div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="stat-card-modern p-3 text-center border-start border-primary border-4">
      <div class="text-primary small fw-semibold">Tổng Lượng Đã Bán</div>
      <div class="fs-4 fw-bold text-primary mt-1"><?= number_format($stat['total_sold'] ?? 0) ?></div>
    </div>
  </div>
</div>

<!-- 2. THANH CÔNG CỤ VÀ BỘ LỌC -->
<div class="card border-0 shadow-sm rounded-4 mb-4">
  <div class="card-body p-3">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
      <div class="fw-bold text-dark fs-5">
        <i class="bi bi-box-seam me-2 text-danger"></i>Danh Sách Mỹ Phẩm
      </div>
      <a href="add_product.php" class="btn btn-danger btn-sm rounded-pill px-3 shadow-sm" style="background: #d47a88; border-color: #d47a88;">
        <i class="bi bi-plus-lg me-1"></i> Thêm Sản Phẩm Mới
      </a>
    </div>

    <form method="GET" action="manage_products.php" class="row g-2 align-items-center">
      <div class="col-md-3">
        <div class="input-group input-group-sm">
          <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
          <input type="text" name="search" class="form-control border-start-0" 
                 placeholder="Tìm tên sản phẩm, mã slug..." 
                 value="<?= htmlspecialchars($search) ?>">
        </div>
      </div>

      <div class="col-md-2">
        <select name="category_id" class="form-select form-select-sm" onchange="this.form.submit()">
          <option value="0">Tất cả danh mục</option>
          <?php foreach ($categories as $cat): ?>
            <option value="<?= $cat['category_id'] ?>" <?= $cat_id === (int)$cat['category_id'] ? 'selected' : '' ?>>
              <?= htmlspecialchars($cat['category_name']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="col-md-2">
        <select name="brand_id" class="form-select form-select-sm" onchange="this.form.submit()">
          <option value="0">Tất cả thương hiệu</option>
          <?php foreach ($brands as $b): ?>
            <option value="<?= $b['brand_id'] ?>" <?= $brand_id === (int)$b['brand_id'] ? 'selected' : '' ?>>
              <?= htmlspecialchars($b['brand_name']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="col-md-2">
        <select name="stock" class="form-select form-select-sm" onchange="this.form.submit()">
          <option value="all" <?= $stock_status === 'all' ? 'selected' : '' ?>>Tất cả tồn kho</option>
          <option value="low" <?= $stock_status === 'low' ? 'selected' : '' ?>>Sắp hết (&le; 5)</option>
          <option value="out" <?= $stock_status === 'out' ? 'selected' : '' ?>>Hết hàng (= 0)</option>
          <option value="in" <?= $stock_status === 'in' ? 'selected' : '' ?>>Còn nhiều (&gt; 5)</option>
        </select>
      </div>

      <div class="col-md-3 d-flex gap-2">
        <button type="submit" class="btn btn-sm btn-dark rounded-pill px-3">
          <i class="bi bi-funnel me-1"></i> Lọc
        </button>
        <?php if (!empty($search) || $cat_id > 0 || $brand_id > 0 || $stock_status !== 'all' || $active_filter !== 'all'): ?>
          <a href="manage_products.php" class="btn btn-sm btn-outline-secondary rounded-pill px-3">
            <i class="bi bi-arrow-counterclockwise"></i> Xóa lọc
          </a>
        <?php endif; ?>
      </div>
    </form>
  </div>
</div>

<!-- 3. BẢNG DANH SÁCH SẢN PHẨM -->
<div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
      <thead class="table-light text-uppercase text-secondary" style="font-size: 0.75rem; letter-spacing: 0.5px;">
        <tr>
          <th class="ps-4">Sản Phẩm</th>
          <th>Phân Loại & Hãng</th>
          <th>Giá Bán</th>
          <th>Tồn Kho</th>
          <th>Đã Bán</th>
          <th>Nổi Bật</th>
          <th>Trạng Thái</th>
          <th class="text-end pe-4">Hành Động</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($products)): ?>
          <tr>
            <td colspan="8" class="text-center py-5 text-muted">
              <i class="bi bi-box2 fs-1 d-block mb-2 text-black-50"></i>
              Không tìm thấy sản phẩm nào phù hợp.
            </td>
          </tr>
        <?php else: ?>
          <?php foreach ($products as $p): 
            $img_url = (strpos($p['image'], 'http') === 0) ? $p['image'] : '../images/products/' . $p['image'];
          ?>
            <tr>
              <td class="ps-4">
                <div class="d-flex align-items-center gap-3">
                  <img src="<?= htmlspecialchars($img_url) ?>" 
                       alt="<?= htmlspecialchars($p['product_name']) ?>" 
                       class="rounded-3 border shadow-sm" 
                       style="width: 50px; height: 50px; object-fit: cover;"
                       onerror="this.src='../images/products/kem-duong-phuc-hoi-b5.jpg'">
                  <div>
                    <a href="product_detail.php?id=<?= $p['product_id'] ?>" target="_blank" 
                       class="fw-bold text-dark text-decoration-none d-block text-truncate" style="max-width: 250px;"
                       title="<?= htmlspecialchars($p['product_name']) ?>">
                      <?= htmlspecialchars($p['product_name']) ?>
                    </a>
                    <div class="small text-muted">
                      <span class="badge bg-light text-dark border me-1"><?= htmlspecialchars($p['capacity'] ?? '100ml') ?></span>
                      <small>ID: #<?= $p['product_id'] ?></small>
                    </div>
                  </div>
                </div>
              </td>
              <td>
                <div class="badge bg-danger-subtle text-danger-emphasis mb-1">
                  <?= htmlspecialchars($p['category_name'] ?? 'Chưa phân loại') ?>
                </div>
                <div class="small text-muted">
                  <i class="bi bi-award me-1"></i><?= htmlspecialchars($p['brand_name'] ?? 'BeautyShop') ?>
                </div>
              </td>
              <td>
                <div class="fw-bold text-danger"><?= format_price($p['price']) ?></div>
                <?php if ($p['original_price'] > $p['price']): ?>
                  <del class="small text-muted"><?= format_price($p['original_price']) ?></del>
                  <span class="badge bg-danger ms-1" style="font-size: 0.65rem;">
                    -<?= round((($p['original_price'] - $p['price']) / $p['original_price']) * 100) ?>%
                  </span>
                <?php endif; ?>
              </td>
              <td>
                <?php if ($p['quantity'] <= 0): ?>
                  <span class="badge bg-danger px-2 py-1">Hết hàng (0)</span>
                <?php elseif ($p['quantity'] <= 5): ?>
                  <span class="badge bg-warning text-dark px-2 py-1">Chỉ còn <?= $p['quantity'] ?></span>
                <?php else: ?>
                  <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1"><?= $p['quantity'] ?> sp</span>
                <?php endif; ?>
              </td>
              <td>
                <span class="fw-semibold text-secondary"><?= number_format($p['sold_count']) ?></span>
              </td>
              <td>
                <form method="POST" style="display:inline;">
                  <input type="hidden" name="product_id" value="<?= $p['product_id'] ?>">
                  <input type="hidden" name="action" value="toggle_featured">
                  <button type="submit" class="btn btn-sm border-0 p-0 text-<?= $p['is_featured'] ? 'warning' : 'black-50' ?>" title="Bấm để bật/tắt nổi bật">
                    <i class="bi bi-star<?= $p['is_featured'] ? '-fill fs-5' : ' fs-5' ?>"></i>
                  </button>
                </form>
              </td>
              <td>
                <form method="POST" style="display:inline;">
                  <input type="hidden" name="product_id" value="<?= $p['product_id'] ?>">
                  <input type="hidden" name="action" value="toggle_active">
                  <button type="submit" class="badge rounded-pill border-0 px-3 py-2 <?= $p['is_active'] ? 'bg-success text-white' : 'bg-secondary text-white' ?>" 
                          title="Bấm để ẩn hoặc hiện sản phẩm">
                    <?= $p['is_active'] ? 'Đang bán' : 'Đã ẩn' ?>
                  </button>
                </form>
              </td>
              <td class="text-end pe-4">
                <div class="btn-group">
                  <a href="edit_product.php?id=<?= $p['product_id'] ?>" class="btn btn-sm btn-outline-primary rounded-pill me-1" title="Chỉnh sửa sản phẩm">
                    <i class="bi bi-pencil-square"></i>
                  </a>
                  <a href="delete_product.php?id=<?= $p['product_id'] ?>" class="btn btn-sm btn-outline-danger rounded-pill" 
                     onclick="return confirm('Bạn có chắc muốn xóa/ẩn sản phẩm này?');" title="Xóa sản phẩm">
                    <i class="bi bi-trash3"></i>
                  </a>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <!-- PHÂN TRANG -->
  <?php if ($total_pages > 1): ?>
    <div class="card-footer bg-white border-0 py-3 d-flex justify-content-between align-items-center">
      <div class="small text-muted">Hiển thị trang <strong><?= $page ?></strong> trên <strong><?= $total_pages ?></strong> (Tổng: <?= $total_products ?> sản phẩm)</div>
      <nav>
        <ul class="pagination pagination-sm mb-0">
          <?php for ($i = 1; $i <= $total_pages; $i++): ?>
            <li class="page-item <?= $i === $page ? 'active' : '' ?>">
              <a class="page-link" href="manage_products.php?category_id=<?= $cat_id ?>&brand_id=<?= $brand_id ?>&stock=<?= urlencode($stock_status) ?>&search=<?= urlencode($search) ?>&page=<?= $i ?>"><?= $i ?></a>
            </li>
          <?php endfor; ?>
        </ul>
      </nav>
    </div>
  <?php endif; ?>
</div>

<?php include 'admin_footer.php'; ?>