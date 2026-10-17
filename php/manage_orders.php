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

$page_title = 'Quản Lý Đơn Hàng - BeautyShop Admin';
$admin_section_title = 'Quản Lý Đơn Hàng';

$message = '';
$message_type = '';

// ==========================================
// XỬ LÝ CẬP NHẬT TRẠNG THÁI ĐƠN HÀNG
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $order_id = (int)($_POST['order_id'] ?? 0);
    $action = $_POST['action'];

    if ($order_id > 0) {
        try {
            $pdo->beginTransaction();

            // Lấy thông tin đơn hàng hiện tại
            $stmt = $pdo->prepare("SELECT * FROM orders WHERE order_id = ? FOR UPDATE");
            $stmt->execute([$order_id]);
            $order = $stmt->fetch();

            if (!$order) {
                throw new Exception("Không tìm thấy đơn hàng #$order_id.");
            }

            $current_status = $order['status'];
            $new_status = null;
            $note = trim($_POST['status_note'] ?? '');

            if ($action === 'confirm' && $current_status === 'pending') {
                $new_status = 'confirmed';
                $note = !empty($note) ? $note : 'Quản trị viên đã xác nhận đơn hàng';
            } elseif ($action === 'ship' && in_array($current_status, ['pending', 'confirmed'])) {
                $new_status = 'shipping';
                $note = !empty($note) ? $note : 'Đơn hàng đã được bàn giao cho đối tác vận chuyển';
            } elseif ($action === 'complete' && in_array($current_status, ['confirmed', 'shipping'])) {
                $new_status = 'completed';
                $note = !empty($note) ? $note : 'Giao hàng thành công và hoàn tất đơn hàng';
                // Tự động đánh dấu thanh toán nếu chưa thanh toán (COD)
                $up_pay = $pdo->prepare("UPDATE orders SET payment_status = 'paid' WHERE order_id = ? AND payment_status = 'unpaid'");
                $up_pay->execute([$order_id]);
            } elseif ($action === 'cancel' && in_array($current_status, ['pending', 'confirmed'])) {
                $new_status = 'cancelled';
                $note = !empty($note) ? $note : 'Đơn hàng bị hủy bởi Quản trị viên';

                // ==========================================
                // HOÀN TRẢ TỒN KHO KHI HỦY ĐƠN HÀNG (ACID)
                // ==========================================
                $item_stmt = $pdo->prepare("SELECT product_id, quantity FROM order_items WHERE order_id = ?");
                $item_stmt->execute([$order_id]);
                $items = $item_stmt->fetchAll();

                $restore_stock = $pdo->prepare("UPDATE products SET quantity = quantity + ?, sold_count = GREATEST(0, sold_count - ?) WHERE product_id = ?");
                foreach ($items as $item) {
                    $restore_stock->execute([(int)$item['quantity'], (int)$item['quantity'], (int)$item['product_id']]);
                }
            } elseif ($action === 'toggle_payment') {
                $target_payment = ($order['payment_status'] === 'paid') ? 'unpaid' : 'paid';
                $pdo->prepare("UPDATE orders SET payment_status = ? WHERE order_id = ?")->execute([$target_payment, $order_id]);
                $message = "Đã cập nhật trạng thái thanh toán thành: " . ($target_payment === 'paid' ? 'Đã thanh toán' : 'Chưa thanh toán');
                $message_type = 'success';
            }

            if ($new_status) {
                // Cập nhật trạng thái đơn
                $up_order = $pdo->prepare("UPDATE orders SET status = ?, updated_at = NOW() WHERE order_id = ?");
                $up_order->execute([$new_status, $order_id]);

                // Ghi vết lịch sử trạng thái
                $history_stmt = $pdo->prepare("INSERT INTO order_status_history (order_id, status, note, created_at) VALUES (?, ?, ?, NOW())");
                $history_stmt->execute([$order_id, $new_status, $note]);

                $message = "Đã chuyển trạng thái đơn hàng #{$order['order_code']} sang " . strtoupper($new_status) . " thành công!";
                $message_type = 'success';
            }

            $pdo->commit();
        } catch (Exception $e) {
            $pdo->rollBack();
            $message = "Lỗi khi xử lý đơn hàng: " . $e->getMessage();
            $message_type = 'danger';
        }
    }
}

// ==========================================
// BỘ LỌC VÀ TRUY VẤN DỮ LIỆU
// ==========================================
$filter_status = $_GET['status'] ?? 'all';
$filter_payment = $_GET['payment'] ?? 'all';
$search = trim($_GET['search'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 12;
$offset = ($page - 1) * $limit;

$where_clauses = ["1=1"];
$params = [];

if ($filter_status !== 'all') {
    $where_clauses[] = "o.status = ?";
    $params[] = $filter_status;
}

if ($filter_payment !== 'all') {
    $where_clauses[] = "o.payment_status = ?";
    $params[] = $filter_payment;
}

if (!empty($search)) {
    $where_clauses[] = "(o.order_code LIKE ? OR o.customer_name LIKE ? OR o.customer_phone LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

$where_sql = implode(" AND ", $where_clauses);

// Đếm tổng số đơn để phân trang
$count_stmt = $pdo->prepare("SELECT COUNT(*) FROM orders o WHERE $where_sql");
$count_stmt->execute($params);
$total_orders = $count_stmt->fetchColumn();
$total_pages = ceil($total_orders / $limit);

// Lấy danh sách đơn hàng
$sql = "
    SELECT o.*, u.username as account_username,
           (SELECT COUNT(*) FROM order_items WHERE order_id = o.order_id) as total_items
    FROM orders o
    LEFT JOIN users u ON o.user_id = u.user_id
    WHERE $where_sql
    ORDER BY o.created_at DESC
    LIMIT $limit OFFSET $offset
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$orders = $stmt->fetchAll();

// Thống kê nhanh số lượng đơn theo từng trạng thái
$stat_counts = $pdo->query("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_cnt,
        SUM(CASE WHEN status = 'confirmed' THEN 1 ELSE 0 END) as confirmed_cnt,
        SUM(CASE WHEN status = 'shipping' THEN 1 ELSE 0 END) as shipping_cnt,
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_cnt,
        SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_cnt,
        SUM(CASE WHEN status = 'completed' THEN total_amount ELSE 0 END) as completed_revenue
    FROM orders
")->fetch();

include 'admin_header.php';
?>

<?php if (!empty($message)): ?>
  <div class="alert alert-<?= $message_type ?> alert-dismissible fade show rounded-3 shadow-sm d-flex align-items-center gap-2 mb-4">
    <i class="bi bi-<?= $message_type === 'success' ? 'check-circle-fill' : 'exclamation-triangle-fill' ?> fs-5"></i>
    <div><?= htmlspecialchars($message) ?></div>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
<?php endif; ?>

<!-- 1. THỐNG KÊ NHANH ĐƠN HÀNG -->
<div class="row g-3 mb-4">
  <div class="col-6 col-lg-2">
    <div class="stat-card-modern p-3 text-center">
      <div class="text-muted small fw-semibold">Tổng Đơn Hàng</div>
      <div class="fs-4 fw-bold text-dark mt-1"><?= number_format($stat_counts['total'] ?? 0) ?></div>
    </div>
  </div>
  <div class="col-6 col-lg-2">
    <div class="stat-card-modern p-3 text-center border-start border-warning border-4">
      <div class="text-warning small fw-semibold">Chờ xác nhận</div>
      <div class="fs-4 fw-bold text-warning mt-1"><?= number_format($stat_counts['pending_cnt'] ?? 0) ?></div>
    </div>
  </div>
  <div class="col-6 col-lg-2">
    <div class="stat-card-modern p-3 text-center border-start border-info border-4">
      <div class="text-info small fw-semibold">Đã xác nhận</div>
      <div class="fs-4 fw-bold text-info mt-1"><?= number_format($stat_counts['confirmed_cnt'] ?? 0) ?></div>
    </div>
  </div>
  <div class="col-6 col-lg-2">
    <div class="stat-card-modern p-3 text-center border-start border-primary border-4">
      <div class="text-primary small fw-semibold">Đang giao hàng</div>
      <div class="fs-4 fw-bold text-primary mt-1"><?= number_format($stat_counts['shipping_cnt'] ?? 0) ?></div>
    </div>
  </div>
  <div class="col-6 col-lg-2">
    <div class="stat-card-modern p-3 text-center border-start border-success border-4">
      <div class="text-success small fw-semibold">Hoàn thành</div>
      <div class="fs-4 fw-bold text-success mt-1"><?= number_format($stat_counts['completed_cnt'] ?? 0) ?></div>
    </div>
  </div>
  <div class="col-6 col-lg-2">
    <div class="stat-card-modern p-3 text-center border-start border-danger border-4">
      <div class="text-danger small fw-semibold">Đã hủy</div>
      <div class="fs-4 fw-bold text-danger mt-1"><?= number_format($stat_counts['cancelled_cnt'] ?? 0) ?></div>
    </div>
  </div>
</div>

<!-- 2. BỘ LỌC TÌM KIẾM VÀ TABS -->
<div class="card border-0 shadow-sm rounded-4 mb-4">
  <div class="card-body p-3">
    <div class="d-flex flex-wrap gap-2 border-bottom pb-3 mb-3">
      <a href="manage_orders.php?status=all" class="btn btn-sm <?= $filter_status === 'all' ? 'btn-danger' : 'btn-outline-secondary' ?> rounded-pill px-3">
        Tất cả (<?= $stat_counts['total'] ?? 0 ?>)
      </a>
      <a href="manage_orders.php?status=pending" class="btn btn-sm <?= $filter_status === 'pending' ? 'btn-warning text-dark fw-bold' : 'btn-outline-secondary' ?> rounded-pill px-3">
        <i class="bi bi-clock-history me-1"></i> Chờ xử lý (<?= $stat_counts['pending_cnt'] ?? 0 ?>)
      </a>
      <a href="manage_orders.php?status=confirmed" class="btn btn-sm <?= $filter_status === 'confirmed' ? 'btn-info text-white' : 'btn-outline-secondary' ?> rounded-pill px-3">
        <i class="bi bi-patch-check me-1"></i> Đã duyệt (<?= $stat_counts['confirmed_cnt'] ?? 0 ?>)
      </a>
      <a href="manage_orders.php?status=shipping" class="btn btn-sm <?= $filter_status === 'shipping' ? 'btn-primary' : 'btn-outline-secondary' ?> rounded-pill px-3">
        <i class="bi bi-truck me-1"></i> Đang giao (<?= $stat_counts['shipping_cnt'] ?? 0 ?>)
      </a>
      <a href="manage_orders.php?status=completed" class="btn btn-sm <?= $filter_status === 'completed' ? 'btn-success' : 'btn-outline-secondary' ?> rounded-pill px-3">
        <i class="bi bi-check2-circle me-1"></i> Đã giao (<?= $stat_counts['completed_cnt'] ?? 0 ?>)
      </a>
      <a href="manage_orders.php?status=cancelled" class="btn btn-sm <?= $filter_status === 'cancelled' ? 'btn-danger' : 'btn-outline-secondary' ?> rounded-pill px-3">
        <i class="bi bi-x-circle me-1"></i> Đã hủy (<?= $stat_counts['cancelled_cnt'] ?? 0 ?>)
      </a>
    </div>

    <form method="GET" action="manage_orders.php" class="row g-2 align-items-center">
      <input type="hidden" name="status" value="<?= htmlspecialchars($filter_status) ?>">
      
      <div class="col-md-5">
        <div class="input-group input-group-sm">
          <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
          <input type="text" name="search" class="form-control border-start-0" 
                 placeholder="Tìm theo Mã đơn (ORD-...), Tên người nhận, SĐT..." 
                 value="<?= htmlspecialchars($search) ?>">
        </div>
      </div>

      <div class="col-md-3">
        <select name="payment" class="form-select form-select-sm" onchange="this.form.submit()">
          <option value="all" <?= $filter_payment === 'all' ? 'selected' : '' ?>>Tất cả thanh toán</option>
          <option value="unpaid" <?= $filter_payment === 'unpaid' ? 'selected' : '' ?>>Chưa thanh toán</option>
          <option value="paid" <?= $filter_payment === 'paid' ? 'selected' : '' ?>>Đã thanh toán</option>
        </select>
      </div>

      <div class="col-md-4 d-flex gap-2">
        <button type="submit" class="btn btn-sm btn-dark rounded-pill px-3">
          <i class="bi bi-funnel me-1"></i> Lọc
        </button>
        <?php if (!empty($search) || $filter_status !== 'all' || $filter_payment !== 'all'): ?>
          <a href="manage_orders.php" class="btn btn-sm btn-outline-secondary rounded-pill px-3">
            <i class="bi bi-arrow-counterclockwise"></i> Xóa lọc
          </a>
        <?php endif; ?>
      </div>
    </form>
  </div>
</div>

<!-- 3. BẢNG DANH SÁCH ĐƠN HÀNG -->
<div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
      <thead class="table-light text-uppercase text-secondary" style="font-size: 0.75rem; letter-spacing: 0.5px;">
        <tr>
          <th class="ps-4">Mã Đơn / Ngày tạo</th>
          <th>Khách Hàng</th>
          <th>Thanh Toán</th>
          <th>Tổng Tiền</th>
          <th>Trạng Thái Đơn</th>
          <th class="text-end pe-4">Thao Tác</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($orders)): ?>
          <tr>
            <td colspan="6" class="text-center py-5 text-muted">
              <i class="bi bi-inbox fs-1 d-block mb-2 text-black-50"></i>
              Không tìm thấy đơn hàng nào phù hợp với bộ lọc hiện tại.
            </td>
          </tr>
        <?php else: ?>
          <?php foreach ($orders as $ord): 
            $status_class = [
                'pending'   => 'bg-warning text-dark',
                'confirmed' => 'bg-info text-white',
                'shipping'  => 'bg-primary text-white',
                'completed' => 'bg-success text-white',
                'cancelled' => 'bg-danger text-white'
            ][$ord['status']] ?? 'bg-secondary text-white';

            $status_labels = [
                'pending'   => 'Chờ xác nhận',
                'confirmed' => 'Đã xác nhận',
                'shipping'  => 'Đang giao hàng',
                'completed' => 'Hoàn tất',
                'cancelled' => 'Đã hủy'
            ];
          ?>
            <tr>
              <td class="ps-4">
                <div class="fw-bold text-dark font-monospace"><?= htmlspecialchars($ord['order_code']) ?></div>
                <small class="text-muted"><i class="bi bi-clock me-1"></i><?= format_date($ord['created_at']) ?></small>
              </td>
              <td>
                <div class="fw-semibold text-dark"><?= htmlspecialchars($ord['customer_name'] ?? '') ?></div>
                <div class="small text-muted"><i class="bi bi-telephone me-1"></i><?= htmlspecialchars($ord['customer_phone'] ?? '') ?></div>
                <div class="small text-truncate text-muted" style="max-width: 250px;" title="<?= htmlspecialchars($ord['customer_address'] ?? '') ?>">
                  <i class="bi bi-geo-alt me-1"></i><?= htmlspecialchars($ord['customer_address'] ?? '') ?>
                </div>
              </td>
              <td>
                <div class="mb-1">
                  <span class="badge <?= $ord['payment_method'] === 'vietqr' ? 'bg-primary' : 'bg-secondary' ?> text-uppercase">
                    <?= $ord['payment_method'] === 'vietqr' ? 'VietQR CK' : 'COD (Tiền mặt)' ?>
                  </span>
                </div>
                <div>
                  <form method="POST" style="display:inline;">
                    <input type="hidden" name="order_id" value="<?= $ord['order_id'] ?>">
                    <input type="hidden" name="action" value="toggle_payment">
                    <button type="submit" class="badge <?= $ord['payment_status'] === 'paid' ? 'bg-success-subtle text-success border border-success' : 'bg-warning-subtle text-warning-emphasis border border-warning' ?> border-0 cursor-pointer"
                            title="Bấm để đổi trạng thái thanh toán">
                      <i class="bi bi-<?= $ord['payment_status'] === 'paid' ? 'check-circle' : 'hourglass-split' ?> me-1"></i>
                      <?= $ord['payment_status'] === 'paid' ? 'Đã thanh toán' : 'Chưa thanh toán' ?>
                    </button>
                  </form>
                </div>
              </td>
              <td>
                <div class="fw-bold text-danger fs-6"><?= format_price($ord['total_amount']) ?></div>
                <small class="text-muted"><?= $ord['total_items'] ?> sản phẩm</small>
                <?php if ($ord['discount_amount'] > 0): ?>
                  <div class="text-success small">-<?= format_price($ord['discount_amount']) ?> (Voucher)</div>
                <?php endif; ?>
              </td>
              <td>
                <span class="badge <?= $status_class ?> px-3 py-2 rounded-pill">
                  <?= $status_labels[$ord['status']] ?? $ord['status'] ?>
                </span>
              </td>
              <td class="text-end pe-4">
                <div class="btn-group">
                  <!-- Xem chi tiết modal -->
                  <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill me-1" 
                          onclick="viewOrderModal(<?= $ord['order_id'] ?>)" title="Xem chi tiết đơn">
                    <i class="bi bi-eye"></i>
                  </button>

                  <!-- Menu chuyển trạng thái -->
                  <?php if ($ord['status'] !== 'completed' && $ord['status'] !== 'cancelled'): ?>
                    <button type="button" class="btn btn-sm btn-outline-dark dropdown-toggle rounded-pill" data-bs-toggle="dropdown">
                      Xử lý
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow border-0 rounded-3">
                      <?php if ($ord['status'] === 'pending'): ?>
                        <li>
                          <form method="POST">
                            <input type="hidden" name="order_id" value="<?= $ord['order_id'] ?>">
                            <input type="hidden" name="action" value="confirm">
                            <button type="submit" class="dropdown-item text-info py-2">
                              <i class="bi bi-check-circle me-2"></i> Xác nhận đơn hàng
                            </button>
                          </form>
                        </li>
                      <?php endif; ?>

                      <?php if (in_array($ord['status'], ['pending', 'confirmed'])): ?>
                        <li>
                          <form method="POST">
                            <input type="hidden" name="order_id" value="<?= $ord['order_id'] ?>">
                            <input type="hidden" name="action" value="ship">
                            <button type="submit" class="dropdown-item text-primary py-2">
                              <i class="bi bi-truck me-2"></i> Chuyển sang Giao hàng
                            </button>
                          </form>
                        </li>
                      <?php endif; ?>

                      <?php if (in_array($ord['status'], ['confirmed', 'shipping'])): ?>
                        <li>
                          <form method="POST">
                            <input type="hidden" name="order_id" value="<?= $ord['order_id'] ?>">
                            <input type="hidden" name="action" value="complete">
                            <button type="submit" class="dropdown-item text-success py-2">
                              <i class="bi bi-patch-check-fill me-2"></i> Hoàn tất giao hàng
                            </button>
                          </form>
                        </li>
                      <?php endif; ?>

                      <?php if (in_array($ord['status'], ['pending', 'confirmed'])): ?>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                          <form method="POST" onsubmit="return confirm('Bạn có chắc chắn muốn HỦY đơn hàng này? Hệ thống sẽ tự động hoàn trả số lượng vào kho.');">
                            <input type="hidden" name="order_id" value="<?= $ord['order_id'] ?>">
                            <input type="hidden" name="action" value="cancel">
                            <button type="submit" class="dropdown-item text-danger py-2">
                              <i class="bi bi-x-circle me-2"></i> Hủy đơn (Hoàn kho)
                            </button>
                          </form>
                        </li>
                      <?php endif; ?>
                    </ul>
                  <?php else: ?>
                    <span class="text-muted small">Đã đóng</span>
                  <?php endif; ?>
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
      <div class="small text-muted">Hiển thị trang <strong><?= $page ?></strong> trên <strong><?= $total_pages ?></strong></div>
      <nav>
        <ul class="pagination pagination-sm mb-0">
          <?php for ($i = 1; $i <= $total_pages; $i++): ?>
            <li class="page-item <?= $i === $page ? 'active' : '' ?>">
              <a class="page-link" href="manage_orders.php?status=<?= urlencode($filter_status) ?>&payment=<?= urlencode($filter_payment) ?>&search=<?= urlencode($search) ?>&page=<?= $i ?>"><?= $i ?></a>
            </li>
          <?php endfor; ?>
        </ul>
      </nav>
    </div>
  <?php endif; ?>
</div>

<!-- MODAL XEM CHI TIẾT ĐƠN HÀNG AJAX -->
<div class="modal fade" id="orderDetailModal" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content rounded-4 border-0 shadow">
      <div class="modal-header border-0 pb-0">
        <h5 class="modal-title fw-bold" id="modalOrderCode">Chi tiết đơn hàng</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body p-4" id="modalOrderBody">
        <div class="text-center py-4">
          <div class="spinner-border text-danger" role="status"></div>
          <p class="small text-muted mt-2">Đang tải thông tin đơn hàng...</p>
        </div>
      </div>
      <div class="modal-footer border-0 pt-0">
        <button type="button" class="btn btn-secondary btn-sm rounded-pill" data-bs-dismiss="modal">Đóng</button>
        <button type="button" class="btn btn-danger btn-sm rounded-pill px-3" onclick="window.print()">
          <i class="bi bi-printer me-1"></i> In Đơn Hàng
        </button>
      </div>
    </div>
  </div>
</div>

<script>
function viewOrderModal(orderId) {
  const modalEl = document.getElementById('orderDetailModal');
  const modal = new bootstrap.Modal(modalEl);
  modal.show();

  const bodyEl = document.getElementById('modalOrderBody');
  bodyEl.innerHTML = '<div class="text-center py-4"><div class="spinner-border text-danger" role="status"></div><p class="small text-muted mt-2">Đang tải...</p></div>';

  fetch('order_detail.php?id=' + orderId + '&raw=1')
    .then(res => res.text())
    .then(html => {
      bodyEl.innerHTML = html;
    })
    .catch(err => {
      bodyEl.innerHTML = '<div class="alert alert-danger">Không thể tải thông tin đơn hàng: ' + err + '</div>';
    });
}
</script>

<?php include 'admin_footer.php'; ?>
