<?php
session_start();
require_once __DIR__ . '/config.php';

if (!is_logged_in() || !isset($_GET['order_id'])) {
    header("Location: login.php");
    exit;
}

$order_id = (int)($_GET['order_id'] ?? $_GET['id'] ?? 0);
$user_id = $_SESSION['user_id'] ?? 0;
$is_admin = is_admin();
$is_raw = isset($_GET['raw']) && $_GET['raw'] == 1;

if ($order_id <= 0) {
    if ($is_raw) {
        echo '<div class="alert alert-danger">Mã đơn hàng không hợp lệ.</div>';
        exit;
    }
    header("Location: my_orders.php");
    exit;
}

// Kiểm tra quyền xem đơn hàng
if ($is_admin) {
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE order_id = ?");
    $stmt->execute([$order_id]);
} else {
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE order_id = ? AND user_id = ?");
    $stmt->execute([$order_id, $user_id]);
}
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    if ($is_raw) {
        echo '<div class="alert alert-warning">Không tìm thấy thông tin đơn hàng này.</div>';
        exit;
    }
    header("Location: my_orders.php");
    exit;
}

// Lấy danh sách sản phẩm
$stmt_items = $pdo->prepare("SELECT * FROM order_items WHERE order_id = ?");
$stmt_items->execute([$order_id]);
$order_items = $stmt_items->fetchAll(PDO::FETCH_ASSOC);

// Lấy lịch sử trạng thái
$stmt_hist = $pdo->prepare("SELECT * FROM order_status_history WHERE order_id = ? ORDER BY id ASC");
$stmt_hist->execute([$order_id]);
$status_history = $stmt_hist->fetchAll(PDO::FETCH_ASSOC);

$status_badges = [
    'pending'   => '<span class="badge bg-warning-subtle text-warning border border-warning-subtle px-3 py-2 rounded-pill"><i class="bi bi-clock me-1"></i> Chờ xác nhận</span>',
    'confirmed' => '<span class="badge bg-info-subtle text-info border border-info-subtle px-3 py-2 rounded-pill"><i class="bi bi-check-circle me-1"></i> Đã xác nhận</span>',
    'shipping'  => '<span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-2 rounded-pill"><i class="bi bi-truck me-1"></i> Đang giao hàng</span>',
    'completed' => '<span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2 rounded-pill"><i class="bi bi-check-all me-1"></i> Hoàn thành</span>',
    'cancelled' => '<span class="badge bg-danger-subtle text-danger border border-danger-subtle px-3 py-2 rounded-pill"><i class="bi bi-x-circle me-1"></i> Đã hủy</span>'
];

$page_title = 'Chi Tiết Đơn Hàng #' . htmlspecialchars($order['order_code']) . ' - BeautyShop';

if (!$is_raw) {
    include 'header.php';
    echo '<div style="height: 125px;"></div>';
}
?>

<?php if (!$is_raw): ?>
<!-- Breadcrumb -->
<div class="py-2 bg-light border-bottom">
  <div class="container-fluid px-lg-5">
    <nav aria-label="breadcrumb">
      <ol class="breadcrumb mb-0 small">
        <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none text-muted">Trang chủ</a></li>
        <li class="breadcrumb-item"><a href="my_orders.php" class="text-decoration-none text-muted">Đơn hàng của tôi</a></li>
        <li class="breadcrumb-item active" style="color: #9e4760;">Chi tiết #<?= htmlspecialchars($order['order_code']) ?></li>
      </ol>
    </nav>
  </div>
</div>
<?php endif; ?>

<div class="<?= $is_raw ? 'p-2' : 'py-5' ?>" style="background: <?= $is_raw ? 'transparent' : '#fffdfb' ?>; <?= $is_raw ? '' : 'min-height: 80vh;' ?>">
  <div class="<?= $is_raw ? '' : 'container-fluid px-lg-5' ?>">
    
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
      <div>
        <h2 class="fw-bold mb-1" style="color: #4a2835;">
          Chi Tiết Đơn Hàng #<?= htmlspecialchars($order['order_code']) ?>
        </h2>
        <small class="text-muted">Đặt lúc <?= format_date($order['created_at']) ?></small>
      </div>
      <div>
        <?= $status_badges[$order['status']] ?? $order['status'] ?>
      </div>
    </div>

    <div class="row g-4">
      
      <!-- Cột Trái: Danh sách sản phẩm & Tiến trình lịch sử -->
      <div class="col-lg-8">
        
        <!-- Sản phẩm trong đơn -->
        <div class="card border-0 rounded-4 shadow-sm bg-white p-4 mb-4">
          <h5 class="fw-bold mb-3" style="color: #4a2835;">Danh Sách Sản Phẩm Đã Mua</h5>
          
          <div class="d-flex flex-column gap-3">
            <?php foreach ($order_items as $it): ?>
              <div class="d-flex align-items-center justify-content-between p-3 rounded-3 border bg-light shadow-xs gap-3">
                <div class="d-flex align-items-center gap-3">
                  <?php if (!empty($it['image'])): ?>
                    <img src="../images/products/<?= htmlspecialchars($it['image']) ?>" alt="" class="rounded-3 border object-fit-cover shadow-xs" style="width: 65px; height: 65px;">
                  <?php endif; ?>
                  <div>
                    <h6 class="mb-1 fw-bold text-dark"><?= htmlspecialchars($it['product_name']) ?></h6>
                    <small class="text-muted">Đơn giá: <?= format_price($it['price']) ?> × Số lượng: <strong><?= $it['quantity'] ?></strong></small>
                  </div>
                </div>
                <div class="text-end">
                  <span class="fs-6 fw-bold text-danger"><?= format_price($it['subtotal']) ?></span>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>

        <!-- Timeline tiến trình trạng thái -->
        <div class="card border-0 rounded-4 shadow-sm bg-white p-4">
          <h5 class="fw-bold mb-3" style="color: #4a2835;">Lịch Sử Tiến Trình Đơn Hàng</h5>
          
          <div class="timeline ps-3 border-start border-2 border-danger border-opacity-25 ms-2">
            <?php if (!empty($status_history)): ?>
              <?php foreach ($status_history as $hist): ?>
                <div class="position-relative mb-4 ps-3">
                  <div class="position-absolute rounded-circle bg-danger" 
                       style="width: 12px; height: 12px; left: -22px; top: 5px;"></div>
                  <div class="fw-bold text-dark small text-uppercase">
                    <?= $hist['new_status'] ?>
                  </div>
                  <small class="text-muted d-block"><?= format_date($hist['created_at']) ?></small>
                  <?php if (!empty($hist['note'])): ?>
                    <div class="small text-secondary mt-1"><?= htmlspecialchars($hist['note']) ?></div>
                  <?php endif; ?>
                </div>
              <?php endforeach; ?>
            <?php else: ?>
              <p class="text-muted small">Đơn hàng mới được tiếp nhận vào hệ thống.</p>
            <?php endif; ?>
          </div>
        </div>

      </div>

      <!-- Cột Phải: Thông tin người nhận & Bảng tính tiền -->
      <div class="col-lg-4">
        
        <!-- Thông tin người nhận -->
        <div class="card border-0 rounded-4 shadow-sm bg-white p-4 mb-4">
          <h5 class="fw-bold mb-3" style="color: #4a2835;">Thông Tin Nhận Hàng</h5>
          
          <div class="small d-flex flex-column gap-2 text-secondary">
            <div><strong class="text-dark">Người nhận:</strong> <?= htmlspecialchars($order['customer_name']) ?></div>
            <div><strong class="text-dark">Số điện thoại:</strong> <?= htmlspecialchars($order['customer_phone']) ?></div>
            <?php if (!empty($order['customer_email'])): ?>
              <div><strong class="text-dark">Email:</strong> <?= htmlspecialchars($order['customer_email']) ?></div>
            <?php endif; ?>
            <div><strong class="text-dark">Địa chỉ:</strong> <?= htmlspecialchars($order['customer_address']) ?></div>
            <?php if (!empty($order['shipping_note'])): ?>
              <div><strong class="text-dark">Ghi chú:</strong> <em><?= htmlspecialchars($order['shipping_note']) ?></em></div>
            <?php endif; ?>
            <div>
              <strong class="text-dark">Thanh toán:</strong> 
              <?= $order['payment_method'] === 'cod' ? 'Thanh toán tiền mặt khi nhận hàng (COD)' : 'Chuyển khoản qua VietQR' ?>
            </div>
            <div>
              <strong class="text-dark">Tình trạng thanh toán:</strong> 
              <span class="badge bg-<?= $order['payment_status'] === 'paid' ? 'success' : 'secondary' ?>">
                <?= $order['payment_status'] === 'paid' ? 'Đã thanh toán' : 'Chưa thanh toán' ?>
              </span>
            </div>
          </div>
        </div>

        <!-- Bảng tính tiền -->
        <div class="card border-0 rounded-4 shadow-sm bg-white p-4">
          <h5 class="fw-bold mb-3" style="color: #4a2835;">Tổng Kết Đơn Hàng</h5>
          
          <div class="d-flex flex-column gap-2 pb-3 border-bottom small">
            <?php 
              $sub = 0;
              foreach ($order_items as $i) $sub += $i['subtotal'];
            ?>
            <div class="d-flex justify-content-between">
              <span class="text-muted">Tổng tiền hàng:</span>
              <strong><?= format_price($sub) ?></strong>
            </div>

            <?php if ($order['discount_amount'] > 0): ?>
              <div class="d-flex justify-content-between">
                <span class="text-muted">Giảm giá voucher (<?= htmlspecialchars($order['coupon_code'] ?? '') ?>):</span>
                <strong class="text-success">-<?= format_price($order['discount_amount']) ?></strong>
              </div>
            <?php endif; ?>

            <div class="d-flex justify-content-between">
              <span class="text-muted">Phí vận chuyển:</span>
              <strong><?= $order['shipping_fee'] > 0 ? format_price($order['shipping_fee']) : 'MIỄN PHÍ' ?></strong>
            </div>
          </div>

          <div class="d-flex justify-content-between align-items-baseline my-3">
            <span class="fw-bold" style="color: #4a2835;">Tổng cộng:</span>
            <span class="fs-4 fw-bold text-danger"><?= format_price($order['total_amount']) ?></span>
          </div>

          <?php if (!$is_raw): ?>
          <div class="d-flex flex-column gap-2 mt-2">
            <a href="my_orders.php" class="btn btn-outline-secondary rounded-pill py-2 small">
              <i class="bi bi-arrow-left me-1"></i> Quay lại danh sách đơn
            </a>
          </div>
          <?php endif; ?>

        </div>

      </div>

    </div>

  </div>
</div>

<?php 
if (!$is_raw) {
    include 'footer.php'; 
}
?>
