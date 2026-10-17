<?php
session_start();
require_once __DIR__ . '/config.php';

if (!is_logged_in() || !isset($_GET['order_id'])) {
    header("Location: index.php");
    exit;
}

$order_id = (int)$_GET['order_id'];
$user_id = $_SESSION['user_id'];

// Lấy thông tin đơn hàng
$stmt = $pdo->prepare("SELECT * FROM orders WHERE order_id = ? AND user_id = ?");
$stmt->execute([$order_id, $user_id]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    header("Location: index.php");
    exit;
}

// Lấy chi tiết sản phẩm
$stmt_items = $pdo->prepare("SELECT * FROM order_items WHERE order_id = ?");
$stmt_items->execute([$order_id]);
$order_items = $stmt_items->fetchAll(PDO::FETCH_ASSOC);

$page_title = 'Đặt Hàng Thành Công - BeautyShop';

include 'header.php';
?>

<div style="height: 125px;"></div>

<div class="py-5" style="background: #fffdfb; min-height: 80vh;">
  <div class="container" style="max-width: 820px;">
    
    <div class="card border-0 rounded-4 shadow-sm p-4 p-md-5 bg-white text-center mb-4">
      <div class="rounded-circle d-flex align-items-center justify-content-center mx-auto mb-3 text-white"
           style="width: 75px; height: 75px; background: #28a745; font-size: 2.2rem;">
        <i class="bi bi-check-lg"></i>
      </div>
      
      <h3 class="fw-bold mb-2" style="color: #4a2835;">Đặt Hàng Thành Công!</h3>
      <p class="text-muted mb-2">Cảm ơn bạn đã tin tưởng mua sắm mỹ phẩm chính hãng tại BeautyShop.</p>
      <div class="d-inline-block px-3 py-1 rounded-pill bg-light border fw-bold text-danger mb-4">
        Mã đơn hàng: #<?= htmlspecialchars($order['order_code']) ?>
      </div>

      <!-- Hướng dẫn chuyển khoản nếu thanh toán qua Ngân hàng -->
      <?php if ($order['payment_method'] === 'bank_transfer'): ?>
        <div class="p-4 rounded-4 border text-start mb-4" style="background: #fdfaf6; border-color: #f7e1d7 !important;">
          <h6 class="fw-bold text-dark mb-3"><i class="bi bi-qr-code-scan text-danger me-2"></i> Hướng Dẫn Chuyển Khoản Qua Mã QR VietQR</h6>
          <div class="row align-items-center g-3">
            <div class="col-md-7 small text-secondary">
              <p class="mb-1">Ngân hàng: <strong>MB BANK</strong></p>
              <p class="mb-1">Số tài khoản: <strong class="text-danger fs-6">0909888999</strong></p>
              <p class="mb-1">Chủ tài khoản: <strong>BEAUTYSHOP VIETNAM</strong></p>
              <p class="mb-1">Số tiền cần chuyển: <strong class="text-danger fs-6"><?= format_price($order['total_amount']) ?></strong></p>
              <p class="mb-0">Nội dung chuyển khoản: <strong class="badge bg-danger fs-6 text-uppercase"><?= htmlspecialchars($order['order_code']) ?></strong></p>
            </div>
            <div class="col-md-5 text-center">
              <div class="p-2 bg-white rounded-3 border d-inline-block shadow-xs">
                <img src="https://api.vietqr.io/image/970422-0909888999-W8Zq9gM.jpg?accountName=BEAUTYSHOP%20VIETNAM&amount=<?= (int)$order['total_amount'] ?>&addInfo=<?= urlencode($order['order_code']) ?>" 
                     alt="VietQR" class="img-fluid rounded" style="max-width: 140px;">
                <small class="d-block text-muted mt-1" style="font-size: 0.65rem;">Quét tự động điền STK & Nội dung</small>
              </div>
            </div>
          </div>
        </div>
      <?php endif; ?>

      <!-- Bảng tóm tắt đơn hàng -->
      <div class="rounded-4 border p-4 text-start mb-4 bg-light">
        <h6 class="fw-bold text-dark mb-3 border-bottom pb-2">Thông Tin Nhận Hàng</h6>
        <div class="row g-2 small mb-3">
          <div class="col-sm-4 text-muted">Người nhận:</div>
          <div class="col-sm-8 fw-bold"><?= htmlspecialchars($order['customer_name']) ?> (<?= htmlspecialchars($order['customer_phone']) ?>)</div>
          
          <div class="col-sm-4 text-muted">Địa chỉ giao:</div>
          <div class="col-sm-8"><?= htmlspecialchars($order['customer_address']) ?></div>

          <div class="col-sm-4 text-muted">Hình thức thanh toán:</div>
          <div class="col-sm-8 fw-bold">
            <?= $order['payment_method'] === 'cod' ? 'Thanh toán tiền mặt khi nhận hàng (COD)' : 'Chuyển khoản ngân hàng (VietQR)' ?>
          </div>

          <?php if (!empty($order['shipping_note'])): ?>
            <div class="col-sm-4 text-muted">Ghi chú:</div>
            <div class="col-sm-8 fst-italic"><?= htmlspecialchars($order['shipping_note']) ?></div>
          <?php endif; ?>
        </div>

        <h6 class="fw-bold text-dark mb-2 border-bottom pb-2">Chi Tiết Sản Phẩm Đã Mua</h6>
        <div class="d-flex flex-column gap-2 mb-3">
          <?php foreach ($order_items as $item): ?>
            <div class="d-flex justify-content-between align-items-center small py-1">
              <div>
                <strong><?= htmlspecialchars($item['product_name']) ?></strong>
                <span class="text-muted">× <?= $item['quantity'] ?></span>
              </div>
              <span class="text-danger fw-bold"><?= format_price($item['subtotal']) ?></span>
            </div>
          <?php endforeach; ?>
        </div>

        <div class="d-flex justify-content-between align-items-baseline pt-2 border-top">
          <span class="fw-bold">Tổng thanh toán:</span>
          <span class="fs-5 fw-bold text-danger"><?= format_price($order['total_amount']) ?></span>
        </div>
      </div>

      <div class="d-flex justify-content-center gap-3 flex-wrap">
        <a href="my_orders.php" class="btn btn-outline-danger rounded-pill px-4 py-2" style="border-color:#d47a88; color:#d47a88;">
          <i class="bi bi-box-seam me-1"></i> Xem danh sách đơn hàng
        </a>
        <a href="index.php" class="btn btn-danger rounded-pill px-4 py-2 text-white" style="background: linear-gradient(135deg, #d47a88, #9e4760); border:none;">
          <i class="bi bi-house-door me-1"></i> Về trang chủ
        </a>
      </div>

    </div>

  </div>
</div>

<?php include 'footer.php'; ?>
