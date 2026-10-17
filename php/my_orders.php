<?php
session_start();
require_once __DIR__ . '/config.php';

if (!is_logged_in()) {
    header("Location: login.php?redirect=my_orders.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$page_title = 'Đơn Hàng Của Tôi - BeautyShop';

// Xử lý HỦY ĐƠN HÀNG của khách
$alert_msg = '';
$alert_type = 'info';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'cancel_order') {
    $cancel_order_id = (int)($_POST['order_id'] ?? 0);
    $cancel_reason = trim($_POST['cancel_reason'] ?? 'Khách hàng yêu cầu hủy đơn');

    try {
        $pdo->beginTransaction();

        // 1. Kiểm tra đơn hàng thuộc về user và đang ở trạng thái pending hoặc confirmed
        $checkStmt = $pdo->prepare("SELECT * FROM orders WHERE order_id = ? AND user_id = ? FOR UPDATE");
        $checkStmt->execute([$cancel_order_id, $user_id]);
        $orderToCancel = $checkStmt->fetch(PDO::FETCH_ASSOC);

        if (!$orderToCancel) {
            throw new Exception("Không tìm thấy đơn hàng!");
        }

        if (!in_array($orderToCancel['status'], ['pending', 'confirmed'])) {
            throw new Exception("Đơn hàng đang ở trạng thái '" . $orderToCancel['status'] . "', không thể hủy trực tiếp!");
        }

        // 2. Cập nhật trạng thái đơn thành cancelled
        $upOrder = $pdo->prepare("
            UPDATE orders 
            SET status = 'cancelled', cancellation_reason = ?, updated_at = NOW() 
            WHERE order_id = ?
        ");
        $upOrder->execute([$cancel_reason, $cancel_order_id]);

        // 3. Hoàn trả tồn kho cho các sản phẩm trong đơn hàng
        $itemsStmt = $pdo->prepare("SELECT product_id, quantity FROM order_items WHERE order_id = ?");
        $itemsStmt->execute([$cancel_order_id]);
        $itemsToRefund = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($itemsToRefund as $it) {
            $pdo->prepare("
                UPDATE products 
                SET quantity = quantity + ?, sold_count = GREATEST(0, sold_count - ?)
                WHERE product_id = ?
            ")->execute([$it['quantity'], $it['quantity'], $it['product_id']]);
        }

        // 4. Ghi log lịch sử trạng thái
        $pdo->prepare("
            INSERT INTO order_status_history (order_id, old_status, new_status, note, changed_by, created_at)
            VALUES (?, ?, 'cancelled', ?, ?, NOW())
        ")->execute([$cancel_order_id, $orderToCancel['status'], "Khách hàng hủy đơn: $cancel_reason", $user_id]);

        $pdo->commit();
        $alert_msg = "Đã hủy đơn hàng #{$orderToCancel['order_code']} và hoàn trả tồn kho thành công!";
        $alert_type = 'success';

    } catch (Exception $e) {
        $pdo->rollBack();
        $alert_msg = "Lỗi khi hủy đơn hàng: " . $e->getMessage();
        $alert_type = 'danger';
    }
}

// Lọc theo trạng thái
$filter_status = $_GET['status'] ?? 'all';
$sql = "SELECT * FROM orders WHERE user_id = ?";
$params = [$user_id];

if (in_array($filter_status, ['pending', 'confirmed', 'shipping', 'completed', 'cancelled'])) {
    $sql .= " AND status = ?";
    $params[] = $filter_status;
}
$sql .= " ORDER BY order_id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Map status labels
$status_badges = [
    'pending'   => '<span class="badge bg-warning-subtle text-warning border border-warning-subtle px-3 py-2 rounded-pill"><i class="bi bi-clock me-1"></i> Chờ xác nhận</span>',
    'confirmed' => '<span class="badge bg-info-subtle text-info border border-info-subtle px-3 py-2 rounded-pill"><i class="bi bi-check-circle me-1"></i> Đã xác nhận</span>',
    'shipping'  => '<span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-2 rounded-pill"><i class="bi bi-truck me-1"></i> Đang giao hàng</span>',
    'completed' => '<span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2 rounded-pill"><i class="bi bi-check-all me-1"></i> Hoàn thành</span>',
    'cancelled' => '<span class="badge bg-danger-subtle text-danger border border-danger-subtle px-3 py-2 rounded-pill"><i class="bi bi-x-circle me-1"></i> Đã hủy</span>'
];

include 'header.php';
?>

<div style="height: 125px;"></div>

<!-- Breadcrumb -->
<div class="py-2 bg-light border-bottom">
  <div class="container-fluid px-lg-5">
    <nav aria-label="breadcrumb">
      <ol class="breadcrumb mb-0 small">
        <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none text-muted">Trang chủ</a></li>
        <li class="breadcrumb-item active" style="color: #9e4760;">Đơn hàng của tôi</li>
      </ol>
    </nav>
  </div>
</div>

<div class="py-5" style="background: #fffdfb; min-height: 80vh;">
  <div class="container-fluid px-lg-5">
    
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
      <div>
        <h2 class="fw-bold mb-1" style="color: #4a2835;">
          <i class="bi bi-box-seam-fill text-danger me-2"></i> Lịch Sử Đơn Hàng Của Bạn
        </h2>
        <p class="text-muted small">Theo dõi tiến trình vận chuyển và quản lý trạng thái các đơn hàng đã đặt</p>
      </div>
      <a href="products.php" class="btn btn-outline-danger btn-sm rounded-pill px-4">
        <i class="bi bi-bag-plus me-1"></i> Mua sắm thêm
      </a>
    </div>

    <?php if ($alert_msg): ?>
      <div class="alert alert-<?= $alert_type ?> rounded-4 p-3 mb-4 shadow-xs">
        <i class="bi bi-info-circle-fill me-2"></i> <?= htmlspecialchars($alert_msg) ?>
      </div>
    <?php endif; ?>

    <!-- Bộ lọc trạng thái đơn hàng -->
    <div class="d-flex overflow-x-auto gap-2 pb-3 mb-4 border-bottom text-nowrap">
      <a href="my_orders.php?status=all" class="btn btn-sm rounded-pill px-3 <?= $filter_status === 'all' ? 'btn-danger text-white' : 'btn-light' ?>" style="<?= $filter_status === 'all' ? 'background:#d47a88; border:none;' : '' ?>">
        Tất cả
      </a>
      <a href="my_orders.php?status=pending" class="btn btn-sm rounded-pill px-3 <?= $filter_status === 'pending' ? 'btn-danger text-white' : 'btn-light' ?>" style="<?= $filter_status === 'pending' ? 'background:#d47a88; border:none;' : '' ?>">
        Chờ xác nhận
      </a>
      <a href="my_orders.php?status=confirmed" class="btn btn-sm rounded-pill px-3 <?= $filter_status === 'confirmed' ? 'btn-danger text-white' : 'btn-light' ?>" style="<?= $filter_status === 'confirmed' ? 'background:#d47a88; border:none;' : '' ?>">
        Đã duyệt
      </a>
      <a href="my_orders.php?status=shipping" class="btn btn-sm rounded-pill px-3 <?= $filter_status === 'shipping' ? 'btn-danger text-white' : 'btn-light' ?>" style="<?= $filter_status === 'shipping' ? 'background:#d47a88; border:none;' : '' ?>">
        Đang giao
      </a>
      <a href="my_orders.php?status=completed" class="btn btn-sm rounded-pill px-3 <?= $filter_status === 'completed' ? 'btn-danger text-white' : 'btn-light' ?>" style="<?= $filter_status === 'completed' ? 'background:#d47a88; border:none;' : '' ?>">
        Hoàn thành
      </a>
      <a href="my_orders.php?status=cancelled" class="btn btn-sm rounded-pill px-3 <?= $filter_status === 'cancelled' ? 'btn-danger text-white' : 'btn-light' ?>" style="<?= $filter_status === 'cancelled' ? 'background:#d47a88; border:none;' : '' ?>">
        Đã hủy
      </a>
    </div>

    <!-- Danh sách đơn hàng -->
    <?php if (empty($orders)): ?>
      <div class="text-center py-5 bg-white rounded-4 border p-5 shadow-xs">
        <i class="bi bi-inbox text-muted" style="font-size: 4rem;"></i>
        <h4 class="fw-bold mt-3" style="color: #4a2835;">Không có đơn hàng nào trong mục này</h4>
        <p class="text-muted small mb-4">Bạn chưa có đơn hàng nào với trạng thái này. Hãy tiếp tục chọn mua mỹ phẩm nhé!</p>
        <a href="products.php" class="btn btn-danger rounded-pill px-4 py-2 text-white" style="background: #d47a88; border:none;">
          Khám phá sản phẩm ngay
        </a>
      </div>
    <?php else: ?>
      <div class="d-flex flex-column gap-3">
        <?php foreach ($orders as $ord): 
          // Lấy các sản phẩm trong đơn hàng này để hiển thị tóm tắt
          $stmt_it = $pdo->prepare("SELECT * FROM order_items WHERE order_id = ?");
          $stmt_it->execute([$ord['order_id']]);
          $ord_items = $stmt_it->fetchAll(PDO::FETCH_ASSOC);
        ?>
          <div class="card border-0 rounded-4 shadow-sm bg-white p-4">
            
            <!-- Header đơn hàng -->
            <div class="d-flex flex-wrap justify-content-between align-items-center pb-3 border-bottom mb-3 gap-2">
              <div class="d-flex align-items-center gap-2">
                <span class="fw-bold fs-6 text-dark">Mã đơn: #<?= htmlspecialchars($ord['order_code']) ?></span>
                <span class="text-muted">•</span>
                <small class="text-muted"><i class="bi bi-calendar3 me-1"></i> <?= format_date($ord['created_at']) ?></small>
              </div>
              <div>
                <?= $status_badges[$ord['status']] ?? $ord['status'] ?>
              </div>
            </div>

            <!-- Danh sách sản phẩm thu gọn -->
            <div class="d-flex flex-column gap-2 mb-3">
              <?php foreach ($ord_items as $it): ?>
                <div class="d-flex align-items-center justify-content-between small">
                  <div class="d-flex align-items-center gap-2">
                    <?php if (!empty($it['image'])): ?>
                      <img src="../images/products/<?= htmlspecialchars($it['image']) ?>" alt="" class="rounded-2 border object-fit-cover" style="width: 42px; height: 42px;">
                    <?php endif; ?>
                    <div>
                      <div class="fw-semibold text-dark"><?= htmlspecialchars($it['product_name']) ?></div>
                      <small class="text-muted">SL: <?= $it['quantity'] ?> × <?= format_price($it['price']) ?></small>
                    </div>
                  </div>
                  <strong class="text-danger"><?= format_price($it['subtotal']) ?></strong>
                </div>
              <?php endforeach; ?>
            </div>

            <!-- Footer đơn hàng: Tổng tiền + Các nút thao tác -->
            <div class="d-flex flex-wrap justify-content-between align-items-center pt-3 border-top gap-3">
              <div>
                <span class="text-muted small">Tổng thanh toán: </span>
                <span class="fs-5 fw-bold text-danger"><?= format_price($ord['total_amount']) ?></span>
                <small class="text-muted ms-2">(<?= $ord['payment_method'] === 'cod' ? 'COD' : 'VietQR' ?>)</small>
              </div>

              <div class="d-flex gap-2">
                <a href="order_detail.php?order_id=<?= $ord['order_id'] ?>" class="btn btn-sm btn-outline-secondary rounded-pill px-3">
                  <i class="bi bi-eye me-1"></i> Xem chi tiết
                </a>

                <?php if (in_array($ord['status'], ['pending', 'confirmed'])): ?>
                  <button type="button" class="btn btn-sm btn-outline-danger rounded-pill px-3" 
                          onclick="showCancelModal(<?= $ord['order_id'] ?>, '<?= htmlspecialchars($ord['order_code']) ?>')">
                    <i class="bi bi-x-circle me-1"></i> Hủy đơn
                  </button>
                <?php endif; ?>
              </div>
            </div>

          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

  </div>
</div>

<!-- Modal xác nhận hủy đơn hàng -->
<div class="modal fade" id="cancelModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content rounded-4 border-0 shadow">
      <form method="POST">
        <input type="hidden" name="action" value="cancel_order">
        <input type="hidden" name="order_id" id="modalCancelOrderId">

        <div class="modal-header border-0 pb-0">
          <h5 class="modal-title fw-bold text-danger">Xác Nhận Hủy Đơn Hàng</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>

        <div class="modal-body py-4">
          <p class="mb-3">Bạn có chắc chắn muốn hủy đơn hàng <strong id="modalCancelOrderCode" class="text-dark"></strong>?</p>
          <div class="mb-3">
            <label class="form-label small fw-bold text-muted">Lý do hủy đơn:</label>
            <select name="cancel_reason" class="form-select form-select-sm rounded-3">
              <option value="Tôi muốn thay đổi địa chỉ nhận hàng">Tôi muốn thay đổi địa chỉ nhận hàng</option>
              <option value="Tôi muốn đổi sản phẩm khác">Tôi muốn đổi sản phẩm khác</option>
              <option value="Tôi tìm thấy sản phẩm giá tốt hơn">Tôi tìm thấy sản phẩm giá tốt hơn</option>
              <option value="Đổi ý không muốn mua nữa">Đổi ý không muốn mua nữa</option>
              <option value="Lý do khác">Lý do khác</option>
            </select>
          </div>
          <small class="text-muted d-block">
            <i class="bi bi-info-circle me-1"></i> Sau khi hủy, sản phẩm sẽ được tự động hoàn lại vào kho hàng.
          </small>
        </div>

        <div class="modal-footer border-0 pt-0">
          <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Đóng</button>
          <button type="submit" class="btn btn-danger rounded-pill px-4 text-white" style="background:#d47a88; border:none;">Xác nhận hủy</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
function showCancelModal(orderId, orderCode) {
  document.getElementById('modalCancelOrderId').value = orderId;
  document.getElementById('modalCancelOrderCode').textContent = '#' + orderCode;
  const modal = new bootstrap.Modal(document.getElementById('cancelModal'));
  modal.show();
}
</script>

<?php include 'footer.php'; ?>
