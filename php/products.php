<?php
session_start();
require_once __DIR__ . '/config.php';

$page_title = 'Tất Cả Sản Phẩm - Beauty Cosmetics';

// Lọc các điều kiện
$cat_id = isset($_GET['cat']) ? (int)$_GET['cat'] : 0;
$brand_id = isset($_GET['brand']) ? (int)$_GET['brand'] : 0;
$skin_type_id = isset($_GET['skin_type']) ? (int)$_GET['skin_type'] : 0;
$price_range = isset($_GET['price_range']) ? $_GET['price_range'] : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'newest';
$search_query = isset($_GET['q']) ? trim($_GET['q']) : '';

// Xây dựng câu truy vấn lọc
$where = ["p.is_active = 1"];
$params = [];

if ($cat_id > 0) {
    $where[] = "p.category_id = ?";
    $params[] = $cat_id;
}

if ($brand_id > 0) {
    $where[] = "p.brand_id = ?";
    $params[] = $brand_id;
}

if ($skin_type_id > 0) {
    $where[] = "(pst.skin_type_id = ? OR p.skin_type_summary LIKE '%Mọi loại da%')";
    $params[] = $skin_type_id;
}

if (!empty($search_query)) {
    $where[] = "(p.product_name LIKE ? OR p.description LIKE ? OR p.ingredients_text LIKE ?)";
    $s_term = "%$search_query%";
    $params[] = $s_term;
    $params[] = $s_term;
    $params[] = $s_term;
}

if ($price_range === 'under_200') {
    $where[] = "p.price < 200000";
} elseif ($price_range === '200_400') {
    $where[] = "p.price BETWEEN 200000 AND 400000";
} elseif ($price_range === 'above_400') {
    $where[] = "p.price > 400000";
}

// Sắp xếp
$order_by = match($sort) {
    'price_asc' => 'p.price ASC',
    'price_desc' => 'p.price DESC',
    'best_seller' => 'p.sold_count DESC',
    'rating' => 'p.rating_avg DESC',
    default => 'p.product_id DESC',
};

// Phân trang
$limit = 12;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $limit;

// Đếm tổng số lượng
$count_sql = "
    SELECT COUNT(DISTINCT p.product_id)
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.category_id
    LEFT JOIN brands b ON p.brand_id = b.brand_id
    " . ($skin_type_id > 0 ? " LEFT JOIN product_skin_types pst ON p.product_id = pst.product_id " : "") . "
    WHERE " . implode(" AND ", $where);

$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute($params);
$total_products = (int)$count_stmt->fetchColumn();
$total_pages = ceil($total_products / $limit);

// Lấy danh sách sản phẩm
$sql = "
    SELECT DISTINCT p.*, c.category_name, b.brand_name
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.category_id
    LEFT JOIN brands b ON p.brand_id = b.brand_id
    " . ($skin_type_id > 0 ? " LEFT JOIN product_skin_types pst ON p.product_id = pst.product_id " : "") . "
    WHERE " . implode(" AND ", $where) . "
    ORDER BY $order_by
    LIMIT $limit OFFSET $offset
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Lấy dữ liệu cho bộ lọc
$all_categories = $pdo->query("SELECT * FROM categories WHERE is_active = 1 ORDER BY category_name ASC")->fetchAll();
$all_brands = $pdo->query("SELECT * FROM brands WHERE is_active = 1 ORDER BY brand_name ASC")->fetchAll();
$all_skin_types = $pdo->query("SELECT * FROM skin_types ORDER BY id ASC")->fetchAll();

include 'header.php';
?>

<!-- Khoảng cách bù header -->
<div style="height: 125px;"></div>

<!-- Breadcrumb -->
<div class="py-2 bg-light border-bottom">
  <div class="container-fluid px-lg-5">
    <nav aria-label="breadcrumb">
      <ol class="breadcrumb mb-0 small">
        <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none text-muted">Trang chủ</a></li>
        <li class="breadcrumb-item active" aria-current="page" style="color: #9e4760;">Tất cả sản phẩm</li>
        <?php if (!empty($search_query)): ?>
          <li class="breadcrumb-item active">Tìm kiếm: "<?= htmlspecialchars($search_query) ?>"</li>
        <?php endif; ?>
      </ol>
    </nav>
  </div>
</div>

<div class="py-4" style="background: #fffdfb;">
  <div class="container-fluid px-lg-5">
    <div class="row g-4">
      
      <!-- CỘT BỘ LỌC (SIDEBAR FILTER) -->
      <div class="col-lg-3">
        <div class="card border-0 rounded-4 shadow-sm p-4 bg-white sticky-top" style="top: 140px;">
          <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
            <h6 class="fw-bold mb-0" style="color: #4a2835;">
              <i class="bi bi-funnel-fill me-1" style="color: #d47a88;"></i> Bộ Lọc Sản Phẩm
            </h6>
            <a href="products.php" class="small text-danger text-decoration-none">Đặt lại</a>
          </div>

          <form action="products.php" method="GET" id="filterForm">
            <?php if (!empty($search_query)): ?>
              <input type="hidden" name="q" value="<?= htmlspecialchars($search_query) ?>">
            <?php endif; ?>

            <!-- Lọc Danh mục -->
            <div class="mb-4">
              <label class="form-label fw-bold small text-muted text-uppercase mb-2">Danh mục</label>
              <select name="cat" class="form-select form-select-sm rounded-3" onchange="this.form.submit()">
                <option value="0">Tất cả danh mục</option>
                <?php foreach ($all_categories as $c): ?>
                  <option value="<?= $c['category_id'] ?>" <?= $cat_id == $c['category_id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($c['category_name']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <!-- Lọc Thương hiệu -->
            <div class="mb-4">
              <label class="form-label fw-bold small text-muted text-uppercase mb-2">Thương hiệu</label>
              <select name="brand" class="form-select form-select-sm rounded-3" onchange="this.form.submit()">
                <option value="0">Tất cả thương hiệu</option>
                <?php foreach ($all_brands as $b): ?>
                  <option value="<?= $b['brand_id'] ?>" <?= $brand_id == $b['brand_id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($b['brand_name']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <!-- Lọc Loại da -->
            <div class="mb-4">
              <label class="form-label fw-bold small text-muted text-uppercase mb-2">Phù hợp loại da</label>
              <select name="skin_type" class="form-select form-select-sm rounded-3" onchange="this.form.submit()">
                <option value="0">Mọi loại da</option>
                <?php foreach ($all_skin_types as $st): ?>
                  <option value="<?= $st['id'] ?>" <?= $skin_type_id == $st['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($st['name']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <!-- Lọc Khoảng giá -->
            <div class="mb-4">
              <label class="form-label fw-bold small text-muted text-uppercase mb-2">Khoảng giá</label>
              <div class="d-flex flex-column gap-2 small">
                <div class="form-check">
                  <input class="form-check-input" type="radio" name="price_range" value="" id="pr_all" <?= empty($price_range) ? 'checked' : '' ?> onchange="this.form.submit()">
                  <label class="form-check-label" for="pr_all">Tất cả mức giá</label>
                </div>
                <div class="form-check">
                  <input class="form-check-input" type="radio" name="price_range" value="under_200" id="pr_1" <?= $price_range === 'under_200' ? 'checked' : '' ?> onchange="this.form.submit()">
                  <label class="form-check-label" for="pr_1">Dưới 200.000₫</label>
                </div>
                <div class="form-check">
                  <input class="form-check-input" type="radio" name="price_range" value="200_400" id="pr_2" <?= $price_range === '200_400' ? 'checked' : '' ?> onchange="this.form.submit()">
                  <label class="form-check-label" for="pr_2">200.000₫ - 400.000₫</label>
                </div>
                <div class="form-check">
                  <input class="form-check-input" type="radio" name="price_range" value="above_400" id="pr_3" <?= $price_range === 'above_400' ? 'checked' : '' ?> onchange="this.form.submit()">
                  <label class="form-check-label" for="pr_3">Trên 400.000₫</label>
                </div>
              </div>
            </div>

            <button type="submit" class="btn btn-danger btn-sm w-100 rounded-pill py-2 fw-semibold" style="background:#d47a88; border:none;">
              Áp dụng lọc
            </button>
          </form>
        </div>
      </div>

      <!-- CỘT DANH SÁCH SẢN PHẨM -->
      <div class="col-lg-9">
        
        <!-- Top Toolbar: Số lượng & Sắp xếp -->
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 p-3 bg-white rounded-4 border shadow-xs gap-3">
          <div>
            <span class="text-muted small">Tìm thấy <strong class="text-danger"><?= $total_products ?></strong> sản phẩm</span>
            <?php if (!empty($search_query)): ?>
              <span class="badge bg-danger ms-2">Từ khóa: <?= htmlspecialchars($search_query) ?></span>
            <?php endif; ?>
          </div>

          <div class="d-flex align-items-center gap-2">
            <span class="small text-muted text-nowrap">Sắp xếp:</span>
            <select class="form-select form-select-sm rounded-pill" style="min-width: 170px;" 
                    onchange="location.href=updateQueryStringParameter(window.location.href, 'sort', this.value)">
              <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Mới nhất</option>
              <option value="best_seller" <?= $sort === 'best_seller' ? 'selected' : '' ?>>Bán chạy nhất</option>
              <option value="rating" <?= $sort === 'rating' ? 'selected' : '' ?>>Đánh giá cao nhất</option>
              <option value="price_asc" <?= $sort === 'price_asc' ? 'selected' : '' ?>>Giá: Thấp đến Cao</option>
              <option value="price_desc" <?= $sort === 'price_desc' ? 'selected' : '' ?>>Giá: Cao đến Thấp</option>
            </select>
          </div>
        </div>

        <!-- Lưới sản phẩm -->
        <?php if (empty($products)): ?>
          <div class="text-center py-5 bg-white rounded-4 border p-5">
            <i class="bi bi-search text-muted" style="font-size: 3.5rem;"></i>
            <h5 class="fw-bold mt-3" style="color: #4a2835;">Không tìm thấy sản phẩm phù hợp</h5>
            <p class="text-muted small">Hãy thử bỏ bớt bộ lọc hoặc tìm kiếm với từ khóa khác bạn nhé.</p>
            <a href="products.php" class="btn btn-outline-danger rounded-pill px-4 btn-sm">Xem tất cả sản phẩm</a>
          </div>
        <?php else: ?>
          <div class="row row-cols-2 row-cols-md-3 g-3 g-lg-4">
            <?php foreach ($products as $p): 
              $discount_pct = ($p['original_price'] > $p['price']) ? round((($p['original_price'] - $p['price']) / $p['original_price']) * 100) : 0;
            ?>
              <div class="col">
                <div class="beauty-card h-100 d-flex flex-column p-3">
                  <?php if ($discount_pct > 0): ?>
                    <span class="discount-badge">-<?= $discount_pct ?>%</span>
                  <?php endif; ?>

                  <a href="product_detail.php?id=<?= $p['product_id'] ?>" class="text-decoration-none flex-grow-1">
                    <div class="img-box rounded-3 mb-3">
                      <img src="../images/products/<?= htmlspecialchars($p['image']) ?>" alt="<?= htmlspecialchars($p['product_name']) ?>" loading="lazy">
                    </div>
                    
                    <div class="text-muted small mb-1"><?= htmlspecialchars($p['brand_name'] ?? 'Chính hãng') ?> • <?= htmlspecialchars($p['capacity'] ?? '') ?></div>
                    <h6 class="text-dark fw-bold line-clamp-2 mb-2" title="<?= htmlspecialchars($p['product_name']) ?>">
                      <?= htmlspecialchars($p['product_name']) ?>
                    </h6>
                    
                    <div class="d-flex align-items-center gap-1 mb-2">
                      <span class="text-warning small"><i class="bi bi-star-fill"></i> <?= number_format($p['rating_avg'], 1) ?></span>
                      <span class="text-muted small">(<?= $p['rating_count'] ?>)</span>
                      <span class="text-muted small ms-auto">Đã bán <?= $p['sold_count'] ?></span>
                    </div>

                    <div class="d-flex align-items-baseline gap-2 mb-3">
                      <span class="fw-bold text-danger fs-5"><?= format_price($p['price']) ?></span>
                      <?php if ($p['original_price'] > $p['price']): ?>
                        <span class="text-muted text-decoration-line-through small"><?= format_price($p['original_price']) ?></span>
                      <?php endif; ?>
                    </div>
                  </a>

                  <div class="d-flex gap-2 mt-auto">
                    <a href="product_detail.php?id=<?= $p['product_id'] ?>" class="btn btn-outline-secondary btn-sm flex-grow-1 rounded-pill py-2">
                      Chi tiết
                    </a>
                    <button onclick="addToCart(<?= $p['product_id'] ?>, 1)" class="btn btn-danger btn-sm flex-grow-1 rounded-pill py-2 text-white" style="background: #d47a88; border-color: #d47a88;">
                      <i class="bi bi-cart-plus me-1"></i> Mua ngay
                    </button>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>

          <!-- Phân trang -->
          <?php if ($total_pages > 1): ?>
            <div class="d-flex justify-content-center mt-5">
              <nav aria-label="Page navigation">
                <ul class="pagination pagination-sm gap-1">
                  <?php if ($page > 1): ?>
                    <li class="page-item">
                      <a class="page-link rounded-circle d-flex align-items-center justify-content-center" 
                         style="width: 36px; height: 36px;"
                         href="<?= update_page_url($page - 1) ?>" aria-label="Previous">
                        <i class="bi bi-chevron-left"></i>
                      </a>
                    </li>
                  <?php endif; ?>

                  <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                      <a class="page-link rounded-circle d-flex align-items-center justify-content-center <?= $i == $page ? 'bg-danger border-danger text-white' : '' ?>" 
                         style="width: 36px; height: 36px;"
                         href="<?= update_page_url($i) ?>">
                        <?= $i ?>
                      </a>
                    </li>
                  <?php endfor; ?>

                  <?php if ($page < $total_pages): ?>
                    <li class="page-item">
                      <a class="page-link rounded-circle d-flex align-items-center justify-content-center" 
                         style="width: 36px; height: 36px;"
                         href="<?= update_page_url($page + 1) ?>" aria-label="Next">
                        <i class="bi bi-chevron-right"></i>
                      </a>
                    </li>
                  <?php endif; ?>
                </ul>
              </nav>
            </div>
          <?php endif; ?>

        <?php endif; ?>

      </div>

    </div>
  </div>
</div>

<script>
function updateQueryStringParameter(uri, key, value) {
  var re = new RegExp("([?&])" + key + "=.*?(&|$)", "i");
  var separator = uri.indexOf('?') !== -1 ? "&" : "?";
  if (uri.match(re)) {
    return uri.replace(re, '$1' + key + "=" + value + '$2');
  } else {
    return uri + separator + key + "=" + value;
  }
}
</script>

<?php 
function update_page_url($target_page) {
    $params = $_GET;
    $params['page'] = $target_page;
    return 'products.php?' . http_build_query($params);
}

include 'footer.php'; 
?>

