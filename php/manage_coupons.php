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

$page_title = 'Quản Lý Mã Giảm Giá - BeautyShop Admin';
$admin_section_title = 'Quản Lý Mã Giảm Giá (Coupons)';

$message = '';
$error = '';

// ==========================================
// XỬ LÝ ACTIONS VOUCHER
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $act = $_POST['action'];

    if ($act === 'add') {
        $code = strtoupper(trim($_POST['code'] ?? ''));
        $description = trim($_POST['description'] ?? '');
        $discount_type = $_POST['discount_type'] === 'fixed' ? 'fixed' : 'percent';
        $discount_value = (float)($_POST['discount_value'] ?? 0);
        $min_order_value = (float)($_POST['min_order_value'] ?? 0);
        $max_discount_amount = !empty($_POST['max_discount_amount']) ? (float)$_POST['max_discount_amount'] : null;
        $usage_limit = max(1, (int)($_POST['usage_limit'] ?? 100));
        $start_date = !empty($_POST['start_date']) ? $_POST['start_date'] : date('Y-m-d H:i:s');
        $end_date = !empty($_POST['end_date']) ? $_POST['end_date'] : date('Y-m-d H:i:s', strtotime('+30 days'));

        if (empty($code) || $discount_value <= 0) {
            $error = "Vui lòng nhập mã giảm giá và giá trị chiết khấu hợp lệ.";
        } else {
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO coupons (
                        code, description, discount_type, discount_value, min_order_value,
                        max_discount_amount, usage_limit, used_count, start_date, end_date, is_active
                    ) VALUES (
                        ?, ?, ?, ?, ?,
                        ?, ?, 0, ?, ?, 1
                    )
                ");
                $stmt->execute([
                    $code, $description, $discount_type, $discount_value, $min_order_value,
                    $max_discount_amount, $usage_limit, $start_date, $end_date
                ]);
                $message = "Đã tạo mã giảm giá '$code' thành công!";
            } catch (Exception $e) {
                $error = "Lỗi khi tạo mã giảm giá: Mã có thể đã tồn tại (" . $e->getMessage() . ")";
            }
        }
    } elseif ($act === 'edit') {
        $coupon_id = (int)$_POST['coupon_id'];
        $code = strtoupper(trim($_POST['code'] ?? ''));
        $description = trim($_POST['description'] ?? '');
        $discount_type = $_POST['discount_type'] === 'fixed' ? 'fixed' : 'percent';
        $discount_value = (float)($_POST['discount_value'] ?? 0);
        $min_order_value = (float)($_POST['min_order_value'] ?? 0);
        $max_discount_amount = !empty($_POST['max_discount_amount']) ? (float)$_POST['max_discount_amount'] : null;
        $usage_limit = max(1, (int)($_POST['usage_limit'] ?? 100));
        $start_date = $_POST['start_date'];
        $end_date = $_POST['end_date'];
        $is_active = isset($_POST['is_active']) ? 1 : 0;

        if (empty($code) || $coupon_id <= 0 || $discount_value <= 0) {
            $error = "Dữ liệu cập nhật voucher không hợp lệ.";
        } else {
            try {
                $stmt = $pdo->prepare("
                    UPDATE coupons SET 
                        code = ?, description = ?, discount_type = ?, discount_value = ?,
                        min_order_value = ?, max_discount_amount = ?, usage_limit = ?,
                        start_date = ?, end_date = ?, is_active = ?
                    WHERE coupon_id = ?
                ");
                $stmt->execute([
                    $code, $description, $discount_type, $discount_value,
                    $min_order_value, $max_discount_amount, $usage_limit,
                    $start_date, $end_date, $is_active, $coupon_id
                ]);
                $message = "Đã cập nhật mã giảm giá '$code' thành công!";
            } catch (Exception $e) {
                $error = "Lỗi khi cập nhật voucher: " . $e->getMessage();
            }
        }
    } elseif ($act === 'toggle_active') {
        $coupon_id = (int)$_POST['coupon_id'];
        $pdo->prepare("UPDATE coupons SET is_active = IF(is_active = 1, 0, 1) WHERE coupon_id = ?")->execute([$coupon_id]);
        $message = "Đã thay đổi trạng thái kích hoạt của mã giảm giá.";
    } elseif ($act === 'delete') {
        $coupon_id = (int)$_POST['coupon_id'];
        try {
            $pdo->prepare("DELETE FROM coupons WHERE coupon_id = ?")->execute([$coupon_id]);
            $message = "Đã xóa mã giảm giá thành công!";
        } catch (Exception $e) {
            $error = "Không thể xóa voucher đã có lịch sử áp dụng (" . $e->getMessage() . ")";
        }
    }
}

// Lấy danh sách coupons
$coupons = $pdo->query("SELECT * FROM coupons ORDER BY coupon_id DESC")->fetchAll();

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
    <h4 class="fw-bold text-dark mb-1">Quản Lý Mã Giảm Giá & Voucher Khuyến Mãi</h4>
    <p class="text-muted small mb-0">Tạo mã ưu đãi cho khách hàng mới, miễn phí vận chuyển hoặc kích cầu doanh số</p>
  </div>
  <button class="btn btn-danger btn-sm rounded-pill px-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#addCouponModal" style="background:#d47a88; border-color:#d47a88;">
    <i class="bi bi-plus-lg me-1"></i> Tạo Voucher Mới
  </button>
</div>

<!-- BẢNG DANH SÁCH MÃ GIẢM GIÁ -->
<div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
      <thead class="table-light text-uppercase text-secondary" style="font-size: 0.75rem; letter-spacing: 0.5px;">
        <tr>
          <th class="ps-4">Mã Voucher</th>
          <th>Loại & Mức Giảm</th>
          <th>Đơn Tối Thiểu</th>
          <th>Giảm Tối Đa</th>
          <th>Lượt Dùng</th>
          <th>Thời Hạn Áp Dụng</th>
          <th>Trạng Thái</th>
          <th class="text-end pe-4">Thao Tác</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($coupons)): ?>
          <tr>
            <td colspan="8" class="text-center py-5 text-muted">Chưa có mã giảm giá nào trong hệ thống.</td>
          </tr>
        <?php else: ?>
          <?php foreach ($coupons as $c): 
            $now = date('Y-m-d H:i:s');
            $is_expired = ($c['end_date'] < $now);
          ?>
            <tr>
              <td class="ps-4">
                <div class="d-flex align-items-center gap-2">
                  <div class="badge bg-danger-subtle text-danger fs-6 px-3 py-2 font-monospace border border-danger-subtle">
                    <?= htmlspecialchars($c['code']) ?>
                  </div>
                </div>
                <small class="text-muted text-truncate d-block mt-1" style="max-width: 200px;">
                  <?= htmlspecialchars($c['description'] ?? '') ?>
                </small>
              </td>
              <td>
                <?php if ($c['discount_type'] === 'percent'): ?>
                  <span class="fw-bold text-danger fs-6">Giảm <?= (int)$c['discount_value'] ?>%</span>
                <?php else: ?>
                  <span class="fw-bold text-success fs-6">Giảm <?= format_price($c['discount_value']) ?></span>
                <?php endif; ?>
              </td>
              <td>
                <span class="text-dark fw-semibold"><?= format_price($c['min_order_value']) ?></span>
              </td>
              <td>
                <span class="text-muted"><?= $c['max_discount_amount'] ? format_price($c['max_discount_amount']) : 'Không giới hạn' ?></span>
              </td>
              <td>
                <div class="d-flex align-items-center gap-2">
                  <span class="fw-bold"><?= $c['used_count'] ?></span> / <span class="text-muted"><?= $c['usage_limit'] ?></span>
                </div>
                <div class="progress mt-1" style="height: 5px; width: 80px;">
                  <?php $pct = min(100, round(($c['used_count'] / max(1, $c['usage_limit'])) * 100)); ?>
                  <div class="progress-bar bg-danger" style="width: <?= $pct ?>%;"></div>
                </div>
              </td>
              <td>
                <div class="small">
                  <i class="bi bi-calendar-event me-1 text-muted"></i><?= date('d/m/Y', strtotime($c['start_date'])) ?>
                  đến <strong><?= date('d/m/Y', strtotime($c['end_date'])) ?></strong>
                </div>
                <?php if ($is_expired): ?>
                  <span class="badge bg-danger-subtle text-danger" style="font-size: 0.65rem;">Đã hết hạn</span>
                <?php endif; ?>
              </td>
              <td>
                <form method="POST" style="display:inline;">
                  <input type="hidden" name="action" value="toggle_active">
                  <input type="hidden" name="coupon_id" value="<?= $c['coupon_id'] ?>">
                  <button type="submit" class="badge rounded-pill border-0 px-3 py-2 <?= ($c['is_active'] && !$is_expired) ? 'bg-success text-white' : 'bg-secondary text-white' ?>" 
                          title="Bấm để bật hoặc tắt voucher">
                    <?= ($c['is_active'] && !$is_expired) ? 'Đang chạy' : 'Đã tắt' ?>
                  </button>
                </form>
              </td>
              <td class="text-end pe-4">
                <button class="btn btn-sm btn-outline-primary rounded-pill me-1" 
                        onclick="editCoupon(<?= htmlspecialchars(json_encode($c)) ?>)">
                  <i class="bi bi-pencil-square"></i>
                </button>
                <form method="POST" style="display:inline;" onsubmit="return confirm('Bạn có chắc muốn xóa mã giảm giá này?');">
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="coupon_id" value="<?= $c['coupon_id'] ?>">
                  <button type="submit" class="btn btn-sm btn-outline-danger rounded-pill">
                    <i class="bi bi-trash3"></i>
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

<!-- MODAL THÊM VOUCHER -->
<div class="modal fade" id="addCouponModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content rounded-4 border-0 shadow">
      <form method="POST">
        <input type="hidden" name="action" value="add">
        <div class="modal-header">
          <h5 class="modal-title fw-bold">Tạo Mã Giảm Giá Mới</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body p-4">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label small fw-semibold">Mã code voucher <span class="text-danger">*</span></label>
              <input type="text" name="code" class="form-control text-uppercase" placeholder="ví dụ: BEAUTY20" required>
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-semibold">Loại chiết khấu <span class="text-danger">*</span></label>
              <select name="discount_type" class="form-select" required>
                <option value="percent">Giảm theo tỷ lệ phần trăm (%)</option>
                <option value="fixed">Giảm số tiền cố định (₫)</option>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-semibold">Giá trị giảm (% hoặc VNĐ) <span class="text-danger">*</span></label>
              <input type="number" name="discount_value" class="form-control" placeholder="ví dụ: 10 hoặc 50000" min="1" required>
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-semibold">Đơn hàng tối thiểu (VNĐ)</label>
              <input type="number" name="min_order_value" class="form-control" value="0" min="0">
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-semibold">Mức giảm tối đa (Áp dụng cho %)</label>
              <input type="number" name="max_discount_amount" class="form-control" placeholder="Để trống nếu không giới hạn">
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-semibold">Tổng lượt sử dụng tối đa</label>
              <input type="number" name="usage_limit" class="form-control" value="100" min="1">
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-semibold">Ngày bắt đầu</label>
              <input type="datetime-local" name="start_date" class="form-control" value="<?= date('Y-m-d\TH:i') ?>">
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-semibold">Ngày kết thúc</label>
              <input type="datetime-local" name="end_date" class="form-control" value="<?= date('Y-m-d\TH:i', strtotime('+30 days')) ?>">
            </div>
            <div class="col-12">
              <label class="form-label small fw-semibold">Mô tả chương trình voucher</label>
              <textarea name="description" class="form-control" rows="2" placeholder="ví dụ: Giảm 10% tối đa 50k cho khách hàng mới..."></textarea>
            </div>
          </div>
        </div>
        <div class="modal-footer border-0 pt-0">
          <button type="button" class="btn btn-secondary btn-sm rounded-pill" data-bs-dismiss="modal">Đóng</button>
          <button type="submit" class="btn btn-danger btn-sm rounded-pill px-4" style="background:#d47a88; border-color:#d47a88;">Lưu Voucher</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- MODAL SỬA VOUCHER -->
<div class="modal fade" id="editCouponModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content rounded-4 border-0 shadow">
      <form method="POST">
        <input type="hidden" name="action" value="edit">
        <input type="hidden" name="coupon_id" id="edit_c_id">
        <div class="modal-header">
          <h5 class="modal-title fw-bold">Chỉnh Sửa Mã Giảm Giá</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body p-4">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label small fw-semibold">Mã code</label>
              <input type="text" name="code" id="edit_c_code" class="form-control text-uppercase" required>
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-semibold">Loại chiết khấu</label>
              <select name="discount_type" id="edit_c_type" class="form-select">
                <option value="percent">Giảm theo tỷ lệ phần trăm (%)</option>
                <option value="fixed">Giảm số tiền cố định (₫)</option>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-semibold">Giá trị giảm</label>
              <input type="number" name="discount_value" id="edit_c_value" class="form-control" required>
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-semibold">Đơn hàng tối thiểu (VNĐ)</label>
              <input type="number" name="min_order_value" id="edit_c_min" class="form-control">
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-semibold">Mức giảm tối đa</label>
              <input type="number" name="max_discount_amount" id="edit_c_max" class="form-control">
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-semibold">Tổng lượt sử dụng tối đa</label>
              <input type="number" name="usage_limit" id="edit_c_limit" class="form-control">
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-semibold">Ngày bắt đầu</label>
              <input type="datetime-local" name="start_date" id="edit_c_start" class="form-control">
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-semibold">Ngày kết thúc</label>
              <input type="datetime-local" name="end_date" id="edit_c_end" class="form-control">
            </div>
            <div class="col-12">
              <label class="form-label small fw-semibold">Mô tả</label>
              <textarea name="description" id="edit_c_desc" class="form-control" rows="2"></textarea>
            </div>
            <div class="col-12">
              <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" name="is_active" id="edit_c_active">
                <label class="form-check-label small" for="edit_c_active">Kích hoạt mã này</label>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer border-0 pt-0">
          <button type="button" class="btn btn-secondary btn-sm rounded-pill" data-bs-dismiss="modal">Đóng</button>
          <button type="submit" class="btn btn-danger btn-sm rounded-pill px-4" style="background:#d47a88; border-color:#d47a88;">Cập Nhật Voucher</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
function editCoupon(c) {
  document.getElementById('edit_c_id').value = c.coupon_id;
  document.getElementById('edit_c_code').value = c.code;
  document.getElementById('edit_c_type').value = c.discount_type;
  document.getElementById('edit_c_value').value = parseFloat(c.discount_value);
  document.getElementById('edit_c_min').value = parseFloat(c.min_order_value);
  document.getElementById('edit_c_max').value = c.max_discount_amount ? parseFloat(c.max_discount_amount) : '';
  document.getElementById('edit_c_limit').value = c.usage_limit;
  document.getElementById('edit_c_start').value = c.start_date.replace(' ', 'T').substring(0, 16);
  document.getElementById('edit_c_end').value = c.end_date.replace(' ', 'T').substring(0, 16);
  document.getElementById('edit_c_desc').value = c.description || '';
  document.getElementById('edit_c_active').checked = c.is_active == 1;
  new bootstrap.Modal(document.getElementById('editCouponModal')).show();
}
</script>

<?php include 'admin_footer.php'; ?>

