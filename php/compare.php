<?php
session_start();
require_once __DIR__ . '/config.php';

$page_title = 'So Sánh Sản Phẩm Mỹ Phẩm - BeautyShop';

// Nhận danh sách product IDs cần so sánh
$p1_id = isset($_GET['p1']) ? (int)$_GET['p1'] : 1;
$p2_id = isset($_GET['p2']) ? (int)$_GET['p2'] : 2;
$p3_id = isset($_GET['p3']) ? (int)$_GET['p3'] : 4;

function getProductCompareData($pdo, $id) {
    if (!$id) return null;
    $stmt = $pdo->prepare("
        SELECT p.*, c.category_name, b.brand_name
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.category_id
        LEFT JOIN brands b ON p.brand_id = b.brand_id
        WHERE p.product_id = ?
    ");
    $stmt->execute([$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

$p1 = getProductCompareData($pdo, $p1_id);
$p2 = getProductCompareData($pdo, $p2_id);
$p3 = getProductCompareData($pdo, $p3_id);

$all_products = $pdo->query("SELECT product_id, product_name FROM products WHERE is_active = 1 ORDER BY product_name ASC")->fetchAll();

include 'header.php';
?>

<div style="height: 125px;"></div>

<!-- Breadcrumb -->
<div class="py-2 bg-light border-bottom">
  <div class="container-fluid px-lg-5">
    <nav aria-label="breadcrumb">
      <ol class="breadcrumb mb-0 small">
        <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none text-muted">Trang chủ</a></li>
        <li class="breadcrumb-item active" style="color: #9e4760;">So sánh sản phẩm</li>
      </ol>
    </nav>
  </div>
</div>

<div class="py-5" style="background: #fffdfb; min-height: 80vh;">
  <div class="container-fluid px-lg-5">
    
    <div class="mb-4 text-center text-md-start">
      <h2 class="fw-bold mb-1" style="color: #4a2835;">
        <i class="bi bi-columns-gap text-danger me-2"></i> So Sánh Chi Tiết Sản Phẩm
      </h2>
      <p class="text-muted small">Đặt cạnh nhau để so sánh công dụng, thành phần, giá bán và đánh giá thực tế</p>
    </div>

    <!-- Thanh Chọn Sản Phẩm Để So Sánh -->
    <div class="card border-0 rounded-4 shadow-sm bg-white p-3 mb-4">
      <form method="GET" class="row g-2 align-items-center">
        <div class="col-md-3">
          <small class="fw-bold text-muted d-block mb-1">Sản phẩm 1:</small>
          <select name="p1" class="form-select form-select-sm rounded-3" onchange="this.form.submit()">
            <?php foreach ($all_products as $ap): ?>
              <option value="<?= $ap['product_id'] ?>" <?= $p1_id == $ap['product_id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($ap['product_name']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="col-md-3">
          <small class="fw-bold text-muted d-block mb-1">Sản phẩm 2:</small>
          <select name="p2" class="form-select form-select-sm rounded-3" onchange="this.form.submit()">
            <?php foreach ($all_products as $ap): ?>
              <option value="<?= $ap['product_id'] ?>" <?= $p2_id == $ap['product_id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($ap['product_name']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="col-md-3">
          <small class="fw-bold text-muted d-block mb-1">Sản phẩm 3 (Tùy chọn):</small>
          <select name="p3" class="form-select form-select-sm rounded-3" onchange="this.form.submit()">
            <option value="0">-- Không chọn --</option>
            <?php foreach ($all_products as $ap): ?>
              <option value="<?= $ap['product_id'] ?>" <?= $p3_id == $ap['product_id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($ap['product_name']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="col-md-3 text-md-end mt-md-4">
          <button type="submit" class="btn btn-sm btn-danger rounded-pill px-4" style="background:#d47a88; border:none;">
            Cập nhật bảng so sánh
          </button>
        </div>
      </form>
    </div>

    <!-- Bảng So Sánh Chi Tiết -->
    <div class="card border-0 rounded-4 shadow-sm bg-white overflow-hidden p-4">
      <div class="table-responsive">
        <table class="table table-bordered align-middle text-center mb-0" style="min-width: 700px;">
          <thead class="bg-light">
            <tr>
              <th style="width: 20%;" class="text-start bg-light">Tiêu chí</th>
              <th style="width: 26%;">Sản phẩm 1</th>
              <th style="width: 26%;">Sản phẩm 2</th>
              <?php if ($p3): ?><th style="width: 26%;">Sản phẩm 3</th><?php endif; ?>
            </tr>
          </thead>
          <tbody>
            
            <!-- Hàng 1: Hình ảnh & Tên -->
            <tr>
              <td class="text-start fw-bold text-muted">Sản phẩm</td>
              <?php foreach ([$p1, $p2, $p3] as $p): if (!$p) continue; ?>
                <td class="p-3">
                  <img src="../images/products/<?= htmlspecialchars($p['image']) ?>" alt="" 
                       class="rounded-3 mb-2 object-fit-cover shadow-xs" style="width: 90px; height: 90px;">
                  <h6 class="fw-bold text-dark mb-1 small"><?= htmlspecialchars($p['product_name']) ?></h6>
                  <span class="badge bg-secondary-subtle text-dark"><?= htmlspecialchars($p['brand_name'] ?? 'Chính hãng') ?></span>
                </td>
              <?php endforeach; ?>
            </tr>

            <!-- Hàng 2: Giá bán -->
            <tr>
              <td class="text-start fw-bold text-muted">Giá ưu đãi</td>
              <?php foreach ([$p1, $p2, $p3] as $p): if (!$p) continue; ?>
                <td>
                  <span class="fs-5 fw-bold text-danger"><?= format_price($p['price']) ?></span>
                  <?php if ($p['original_price'] > $p['price']): ?>
                    <small class="text-muted text-decoration-line-through d-block"><?= format_price($p['original_price']) ?></small>
                  <?php endif; ?>
                </td>
              <?php endforeach; ?>
            </tr>

            <!-- Hàng 3: Đánh giá sao -->
            <tr>
              <td class="text-start fw-bold text-muted">Đánh giá khách hàng</td>
              <?php foreach ([$p1, $p2, $p3] as $p): if (!$p) continue; ?>
                <td>
                  <span class="text-warning fw-bold"><i class="bi bi-star-fill"></i> <?= number_format($p['rating_avg'], 1) ?></span>
                  <small class="text-muted">(<?= $p['rating_count'] ?> lượt)</small>
                </td>
              <?php endforeach; ?>
            </tr>

            <!-- Hàng 4: Dung tích -->
            <tr>
              <td class="text-start fw-bold text-muted">Dung tích / Trọng lượng</td>
              <?php foreach ([$p1, $p2, $p3] as $p): if (!$p) continue; ?>
                <td><strong><?= htmlspecialchars($p['capacity']) ?></strong></td>
              <?php endforeach; ?>
            </tr>

            <!-- Hàng 5: Loại da phù hợp -->
            <tr>
              <td class="text-start fw-bold text-muted">Loại da khuyên dùng</td>
              <?php foreach ([$p1, $p2, $p3] as $p): if (!$p) continue; ?>
                <td>
                  <span class="badge bg-light text-dark border p-2"><?= htmlspecialchars($p['skin_type_summary']) ?></span>
                </td>
              <?php endforeach; ?>
            </tr>

            <!-- Hàng 6: Thành phần tiêu biểu -->
            <tr>
              <td class="text-start fw-bold text-muted">Thành phần chính</td>
              <?php foreach ([$p1, $p2, $p3] as $p): if (!$p) continue; ?>
                <td class="small text-muted text-start" style="line-height: 1.6;">
                  <?= htmlspecialchars($p['ingredients_text'] ?: 'Thành phần an toàn, không chứa cồn xấu hay hương liệu độc hại.') ?>
                </td>
              <?php endforeach; ?>
            </tr>

            <!-- Hàng 7: Tồn kho -->
            <tr>
              <td class="text-start fw-bold text-muted">Trạng thái kho</td>
              <?php foreach ([$p1, $p2, $p3] as $p): if (!$p) continue; ?>
                <td>
                  <?php if ($p['quantity'] > 0): ?>
                    <span class="badge bg-success-subtle text-success">Còn hàng (<?= $p['quantity'] ?>)</span>
                  <?php else: ?>
                    <span class="badge bg-danger-subtle text-danger">Tạm hết</span>
                  <?php endif; ?>
                </td>
              <?php endforeach; ?>
            </tr>

            <!-- Hàng 8: Nút Mua -->
            <tr>
              <td class="text-start fw-bold text-muted">Thao tác</td>
              <?php foreach ([$p1, $p2, $p3] as $p): if (!$p) continue; ?>
                <td>
                  <div class="d-flex flex-column gap-2">
                    <button onclick="addToCart(<?= $p['product_id'] ?>, 1)" class="btn btn-sm btn-danger rounded-pill py-2 text-white" style="background:#d47a88; border:none;">
                      <i class="bi bi-cart-plus me-1"></i> Chọn mua
                    </button>
                    <a href="product_detail.php?id=<?= $p['product_id'] ?>" class="btn btn-sm btn-outline-secondary rounded-pill py-1 small">
                      Xem chi tiết
                    </a>
                  </div>
                </td>
              <?php endforeach; ?>
            </tr>

          </tbody>
        </table>
      </div>
    </div>

  </div>
</div>

<?php include 'footer.php'; ?>

