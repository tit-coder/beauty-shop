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

$page_title = 'Quản Lý Flash Sale - BeautyShop Admin';
$admin_section_title = 'Quản Lý Flash Sale';

$message = '';
$error = '';

// ==========================================
// XỬ LÝ ACTIONS CHIẾN DỊCH FLASH SALE
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $act = $_POST['action'];

    if ($act === 'create_campaign') {
        $title = trim($_POST['title'] ?? '');
        $start_time = $_POST['start_time'] ?? '';
        $end_time = $_POST['end_time'] ?? '';

        if (empty($title) || empty($start_time) || empty($end_time)) {
            $error = "Vui lòng nhập tên chương trình và thời gian áp dụng.";
        } else {
            try {
                $stmt = $pdo->prepare("INSERT INTO flash_sales (title, start_time, end_time, is_active) VALUES (?, ?, ?, 1)");
                $stmt->execute([$title, $start_time, $end_time]);
                $message = "Đã khởi tạo sự kiện Flash Sale '$title' thành công!";
            } catch (Exception $e) {
                $error = "Lỗi tạo sự kiện: " . $e->getMessage();
            }
        }
    } elseif ($act === 'add_item') {
        $fs_id = (int)$_POST['flash_sale_id'];
        $product_id = (int)$_POST['product_id'];
        $flash_price = (float)$_POST['flash_price'];
        $qty_limit = max(1, (int)$_POST['quantity_limit']);

        if ($fs_id <= 0 || $product_id <= 0 || $flash_price <= 0) {
            $error = "Vui lòng chọn sản phẩm và nhập giá Flash Sale hợp lệ.";
        } else {
            try {
                // Kiểm tra xem sản phẩm đã có trong sale này chưa
                $check = $pdo->prepare("SELECT id FROM flash_sale_items WHERE flash_sale_id = ? AND product_id = ?");
                $check->execute([$fs_id, $product_id]);
                if ($check->fetch()) {
                    $error = "Sản phẩm này đã nằm trong danh sách Flash Sale hiện tại!";
                } else {
                    $stmt = $pdo->prepare("INSERT INTO flash_sale_items (flash_sale_id, product_id, flash_price, quantity_limit, sold_count) VALUES (?, ?, ?, ?, 0)");
                    $stmt->execute([$fs_id, $product_id, $flash_price, $qty_limit]);
                    $message = "Đã thêm sản phẩm vào sự kiện Flash Sale!";
                }
            } catch (Exception $e) {
                $error = "Lỗi khi thêm sản phẩm: " . $e->getMessage();
            }
        }
    } elseif ($act === 'delete_item') {
        $item_id = (int)$_POST['item_id'];
        $pdo->prepare("DELETE FROM flash_sale_items WHERE id = ?")->execute([$item_id]);
        $message = "Đã gỡ sản phẩm khỏi sự kiện Flash Sale.";
    } elseif ($act === 'toggle_campaign') {
        $fs_id = (int)$_POST['flash_sale_id'];
        $pdo->prepare("UPDATE flash_sales SET is_active = IF(is_active = 1, 0, 1) WHERE id = ?")->execute([$fs_id]);
        $message = "Đã thay đổi trạng thái kích hoạt của chiến dịch.";
    } elseif ($act === 'delete_campaign') {
        $fs_id = (int)$_POST['flash_sale_id'];
        $pdo->prepare("DELETE FROM flash_sales WHERE id = ?")->execute([$fs_id]);
        $message = "Đã xóa sự kiện Flash Sale.";
    }
}

// Lấy danh sách Flash Sales
$flash_sales = $pdo->query("
    SELECT fs.*, 
           (SELECT COUNT(*) FROM flash_sale_items WHERE flash_sale_id = fs.id) as total_items
    FROM flash_sales fs
    ORDER BY fs.id DESC
")->fetchAll();

// Lấy danh sách sản phẩm để thêm vào Flash Sale
$all_products = $pdo->query("SELECT product_id, product_name, price, original_price FROM products WHERE is_active = 1 ORDER BY product_name ASC")->fetchAll();

// Chi tiết các sản phẩm đang trong đợt sale được chọn (mặc định lấy đợt đầu tiên hoặc theo id)
$selected_fs_id = (int)($_GET['fs_id'] ?? ($flash_sales[0]['id'] ?? 0));
$current_fs = null;
$sale_items = [];

if ($selected_fs_id > 0) {
    $c_stmt = $pdo->prepare("SELECT * FROM flash_sales WHERE id = ?");
    $c_stmt->execute([$selected_fs_id]);
    $current_fs = $c_stmt->fetch();

    $items_stmt = $pdo->prepare("
        SELECT fsi.*, p.product_name, p.price as regular_price, p.original_price, p.image, p.quantity as stock
        FROM flash_sale_items fsi
        JOIN products p ON fsi.product_id = p.product_id
        WHERE fsi.flash_sale_id = ?
        ORDER BY fsi.id DESC
    ");
    $items_stmt->execute([$selected_fs_id]);
    $sale_items = $items_stmt->fetchAll();
}

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

<div class="d-flex justify-content-between align-items-center mb-4">
  <div>
    <h4 class="fw-bold text-dark mb-1">Cấu Hình Khuyến Mãi Flash Sale</h4>
    <p class="text-muted small mb-0">Quản lý các đợt giảm giá chớp nhoáng theo khung giờ thực tế</p>
  </div>
  <button class="btn btn-danger btn-sm rounded-pill px-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#createCampaignModal" style="background:#d47a88; border-color:#d47a88;">
    <i class="bi bi-plus-lg me-1"></i> Khởi Tạo Đợt Flash Sale Mới
  </button>
</div>

<div class="row g-4">
  
  <!-- CỘT 1: DANH SÁCH CÁC ĐỢT FLASH SALE -->
  <div class="col-lg-4">
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
      <div class="card-header bg-white py-3 border-bottom">
        <h6 class="fw-bold text-dark mb-0"><i class="bi bi-lightning-charge me-1 text-danger"></i> Các Đợt Flash Sale</h6>
      </div>
      <div class="list-group list-group-flush">
        <?php if (empty($flash_sales)): ?>
          <div class="p-4 text-center text-muted small">Chưa có sự kiện Flash Sale nào.</div>
        <?php else: ?>
          <?php foreach ($flash_sales as $fs): 
            $now = date('Y-m-d H:i:s');
            $is_running = ($fs['is_active'] && $fs['start_time'] <= $now && $fs['end_time'] >= $now);
            $is_ended = ($fs['end_time'] < $now);
          ?>
            <a href="manage_flash_sales.php?fs_id=<?= $fs['id'] ?>" 
               class="list-group-item list-group-item-action p-3 <?= $selected_fs_id === (int)$fs['id'] ? 'bg-light border-start border-danger border-4' : '' ?>">
              <div class="d-flex justify-content-between align-items-start mb-1">
                <span class="fw-bold text-dark"><?= htmlspecialchars($fs['title']) ?></span>
                <?php if ($is_running): ?>
                  <span class="badge bg-danger rounded-pill">Đang chạy</span>
                <?php elseif ($is_ended): ?>
                  <span class="badge bg-secondary rounded-pill">Đã kết thúc</span>
                <?php else: ?>
                  <span class="badge bg-warning text-dark rounded-pill">Sắp diễn ra</span>
                <?php endif; ?>
              </div>
              <div class="small text-muted mb-2">
                <i class="bi bi-clock me-1"></i><?= date('H:i d/m', strtotime($fs['start_time'])) ?> - <?= date('H:i d/m/Y', strtotime($fs['end_time'])) ?>
              </div>
              <div class="d-flex justify-content-between align-items-center">
                <span class="badge bg-danger-subtle text-danger"><?= $fs['total_items'] ?> sản phẩm</span>
                
                <div class="d-flex gap-1" onclick="event.stopPropagation();">
                  <form method="POST" style="display:inline;">
                    <input type="hidden" name="action" value="toggle_campaign">
                    <input type="hidden" name="flash_sale_id" value="<?= $fs['id'] ?>">
                    <button type="submit" class="btn btn-sm btn-link p-0 text-decoration-none text-muted" title="Bật/Tắt">
                      <i class="bi bi-power fs-6"></i>
                    </button>
                  </form>
                  <form method="POST" style="display:inline;" onsubmit="return confirm('Bạn có chắc muốn xóa đợt Flash Sale này?');">
                    <input type="hidden" name="action" value="delete_campaign">
                    <input type="hidden" name="flash_sale_id" value="<?= $fs['id'] ?>">
                    <button type="submit" class="btn btn-sm btn-link p-0 text-decoration-none text-danger ms-2" title="Xóa">
                      <i class="bi bi-trash3 fs-6"></i>
                    </button>
                  </form>
                </div>
              </div>
            </a>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- CỘT 2: CHI TIẾT SẢN PHẨM TRONG ĐỢT FLASH SALE ĐANG CHỌN -->
  <div class="col-lg-8">
    <?php if ($current_fs): ?>
      <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
          <div>
            <h6 class="fw-bold text-dark mb-0"><?= htmlspecialchars($current_fs['title']) ?></h6>
            <small class="text-muted">
              <?= date('H:i d/m/Y', strtotime($current_fs['start_time'])) ?> &rarr; <?= date('H:i d/m/Y', strtotime($current_fs['end_time'])) ?>
            </small>
          </div>
          <button class="btn btn-danger btn-sm rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#addItemModal" style="background:#d47a88; border-color:#d47a88;">
            <i class="bi bi-plus-lg me-1"></i> Thêm Sản Phẩm Giảm Giá
          </button>
        </div>

        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead class="table-light text-uppercase text-secondary" style="font-size: 0.75rem;">
              <tr>
                <th class="ps-4">Sản Phẩm</th>
                <th>Giá Gốc / Thường</th>
                <th>Giá Flash Sale</th>
                <th>Mức Giảm</th>
                <th>Đã Bán / Giới Hạn</th>
                <th class="text-end pe-4">Gỡ</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($sale_items)): ?>
                <tr>
                  <td colspan="6" class="text-center py-5 text-muted">
                    Chưa có sản phẩm nào trong đợt sale này. Bấm nút "Thêm Sản Phẩm Giảm Giá" ở trên.
                  </td>
                </tr>
              <?php else: ?>
                <?php foreach ($sale_items as $item): 
                  $img = (strpos($item['image'], 'http') === 0) ? $item['image'] : '../images/products/' . $item['image'];
                  $discount_pct = round((($item['regular_price'] - $item['flash_price']) / $item['regular_price']) * 100);
                ?>
                  <tr>
                    <td class="ps-4">
                      <div class="d-flex align-items-center gap-2">
                        <img src="<?= htmlspecialchars($img) ?>" class="rounded-2 border" style="width: 40px; height: 40px; object-fit: cover;" onerror="this.src='../images/products/kem-duong-phuc-hoi-b5.jpg'">
                        <div>
                          <div class="fw-bold text-dark text-truncate" style="max-width: 220px;"><?= htmlspecialchars($item['product_name']) ?></div>
                          <small class="text-muted">Kho còn: <?= $item['stock'] ?></small>
                        </div>
                      </div>
                    </td>
                    <td>
                      <del class="text-muted small"><?= format_price($item['regular_price']) ?></del>
                    </td>
                    <td>
                      <span class="fw-bold text-danger fs-6"><?= format_price($item['flash_price']) ?></span>
                    </td>
                    <td>
                      <span class="badge bg-danger rounded-pill">-<?= $discount_pct ?>%</span>
                    </td>
                    <td>
                      <div class="small fw-semibold"><?= $item['sold_count'] ?> / <?= $item['quantity_limit'] ?> sp</div>
                      <div class="progress mt-1" style="height: 4px; width: 80px;">
                        <div class="progress-bar bg-danger" style="width: <?= min(100, round(($item['sold_count'] / $item['quantity_limit']) * 100)) ?>%;"></div>
                      </div>
                    </td>
                    <td class="text-end pe-4">
                      <form method="POST" onsubmit="return confirm('Gỡ sản phẩm này khỏi Flash Sale?');">
                        <input type="hidden" name="action" value="delete_item">
                        <input type="hidden" name="item_id" value="<?= $item['id'] ?>">
                        <button type="submit" class="btn btn-sm btn-outline-danger rounded-pill">
                          <i class="bi bi-x-lg"></i>
                        </button>
                      </form>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    <?php else: ?>
      <div class="card border-0 shadow-sm rounded-4 p-5 text-center text-muted">
        Vui lòng chọn hoặc tạo một đợt Flash Sale ở cột bên trái để quản lý sản phẩm.
      </div>
    <?php endif; ?>
  </div>

</div>

<!-- MODAL KHỞI TẠO CHIẾN DỊCH -->
<div class="modal fade" id="createCampaignModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content rounded-4 border-0 shadow">
      <form method="POST">
        <input type="hidden" name="action" value="create_campaign">
        <div class="modal-header">
          <h5 class="modal-title fw-bold">Khởi Tạo Đợt Flash Sale Mới</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label small fw-semibold">Tên sự kiện / Khung giờ <span class="text-danger">*</span></label>
            <input type="text" name="title" class="form-control" placeholder="ví dụ: Siêu Sale Khung Giờ Vàng 12:00 - 14:00" required>
          </div>
          <div class="mb-3">
            <label class="form-label small fw-semibold">Thời gian bắt đầu <span class="text-danger">*</span></label>
            <input type="datetime-local" name="start_time" class="form-control" value="<?= date('Y-m-d\TH:i') ?>" required>
          </div>
          <div class="mb-3">
            <label class="form-label small fw-semibold">Thời gian kết thúc <span class="text-danger">*</span></label>
            <input type="datetime-local" name="end_time" class="form-control" value="<?= date('Y-m-d\TH:i', strtotime('+3 days')) ?>" required>
          </div>
        </div>
        <div class="modal-footer border-0">
          <button type="button" class="btn btn-secondary btn-sm rounded-pill" data-bs-dismiss="modal">Đóng</button>
          <button type="submit" class="btn btn-danger btn-sm rounded-pill px-3" style="background:#d47a88; border-color:#d47a88;">Tạo Sự Kiện</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- MODAL THÊM SẢN PHẨM VÀO SALE -->
<?php if ($current_fs): ?>
<div class="modal fade" id="addItemModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content rounded-4 border-0 shadow">
      <form method="POST">
        <input type="hidden" name="action" value="add_item">
        <input type="hidden" name="flash_sale_id" value="<?= $current_fs['id'] ?>">
        <div class="modal-header">
          <h5 class="modal-title fw-bold">Thêm Mỹ Phẩm Vào Flash Sale</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label small fw-semibold">Chọn sản phẩm <span class="text-danger">*</span></label>
            <select name="product_id" id="fs_product_select" class="form-select" onchange="updateRegularPrice()" required>
              <option value="">-- Chọn sản phẩm đang kinh doanh --</option>
              <?php foreach ($all_products as $prod): ?>
                <option value="<?= $prod['product_id'] ?>" data-price="<?= (int)$prod['price'] ?>">
                  <?= htmlspecialchars($prod['product_name']) ?> (Giá gốc: <?= number_format($prod['price']) ?>₫)
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label small fw-semibold">Giá bán Flash Sale (VND) <span class="text-danger">*</span></label>
            <input type="number" name="flash_price" id="fs_price_input" class="form-control" placeholder="ví dụ: 199000" min="1000" step="1000" required>
            <small class="text-muted" id="price_hint">Nhập mức giá ưu đãi đặc biệt cho đợt sale</small>
          </div>
          <div class="mb-3">
            <label class="form-label small fw-semibold">Giới hạn số lượng bán trong sale</label>
            <input type="number" name="quantity_limit" class="form-control" value="20" min="1">
          </div>
        </div>
        <div class="modal-footer border-0">
          <button type="button" class="btn btn-secondary btn-sm rounded-pill" data-bs-dismiss="modal">Đóng</button>
          <button type="submit" class="btn btn-danger btn-sm rounded-pill px-3" style="background:#d47a88; border-color:#d47a88;">Thêm Sản Phẩm</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
function updateRegularPrice() {
  const sel = document.getElementById('fs_product_select');
  const opt = sel.options[sel.selectedIndex];
  const regularPrice = opt.getAttribute('data-price');
  if (regularPrice) {
    const discounted = Math.round(regularPrice * 0.7); // gợi ý giảm 30%
    document.getElementById('fs_price_input').value = discounted;
    document.getElementById('price_hint').innerText = 'Gợi ý giảm 30%: ' + discounted.toLocaleString('vi-VN') + '₫ (Giá thường: ' + parseInt(regularPrice).toLocaleString('vi-VN') + '₫)';
  }
}
</script>
<?php endif; ?>

<?php include 'admin_footer.php'; ?>

