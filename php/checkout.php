<?php
session_start();
require_once __DIR__ . '/config.php';

if (!is_logged_in()) {
    header("Location: login.php?redirect=checkout.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$page_title = 'Thanh Toán Đơn Hàng - BeautyShop';

// 1. Lấy giỏ hàng của user
$stmt = $pdo->prepare("SELECT cart_id FROM cart WHERE user_id = ?");
$stmt->execute([$user_id]);
$cart = $stmt->fetch();

if (!$cart) {
    header("Location: cart.php");
    exit;
}

// 2. Lấy danh sách sản phẩm trong giỏ
$stmt = $pdo->prepare("
    SELECT ci.cart_item_id, ci.product_id, ci.quantity, 
           p.product_name, p.price, p.image, p.capacity, p.quantity as stock,
           b.brand_name
    FROM cart_items ci
    JOIN products p ON ci.product_id = p.product_id
    LEFT JOIN brands b ON p.brand_id = b.brand_id
    WHERE ci.cart_id = ?
");
$stmt->execute([$cart['cart_id']]);
$cart_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($cart_items)) {
    header("Location: cart.php");
    exit;
}

// 3. Tính toán tiền hàng ở Backend
$subtotal = 0;
foreach ($cart_items as $item) {
    $subtotal += $item['price'] * $item['quantity'];
}

$shipping_fee = ($subtotal >= 150000 || $subtotal == 0) ? 0 : 30000;

// Giảm giá coupon nếu có
$coupon = $_SESSION['applied_coupon'] ?? null;
$discount_amount = 0;
if ($coupon && $subtotal > 0) {
    if ($coupon['discount_type'] === 'percent') {
        $discount_amount = $subtotal * ((float)$coupon['discount_value'] / 100);
        if (!empty($coupon['max_discount_amount']) && $discount_amount > $coupon['max_discount_amount']) {
            $discount_amount = (float)$coupon['max_discount_amount'];
        }
    } else {
        $discount_amount = (float)$coupon['discount_value'];
    }
    $discount_amount = min($discount_amount, $subtotal);
}

$total_amount = max(0, $subtotal - $discount_amount + $shipping_fee);

// 4. Lấy thông tin user & sổ địa chỉ mặc định
$userStmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$userStmt->execute([$user_id]);
$user = $userStmt->fetch(PDO::FETCH_ASSOC);

$addrStmt = $pdo->prepare("SELECT * FROM addresses WHERE user_id = ? ORDER BY is_default DESC, id DESC LIMIT 1");
$addrStmt->execute([$user_id]);
$default_addr = $addrStmt->fetch(PDO::FETCH_ASSOC);

$customer_name = $default_addr['receiver_name'] ?? ($user['full_name'] ?: $user['username']);
$customer_phone = $default_addr['receiver_phone'] ?? $user['phone'];
$customer_email = $user['email'] ?? '';
$customer_address = $default_addr ? ($default_addr['street_address'] . ', ' . $default_addr['ward'] . ', ' . $default_addr['district'] . ', ' . $default_addr['province']) : '';

$error_message = '';

// 5. Xử lý ĐẶT HÀNG (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $post_name = trim($_POST['customer_name'] ?? '');
    $post_phone = trim($_POST['customer_phone'] ?? '');
    $post_email = trim($_POST['customer_email'] ?? '');
    $post_address = trim($_POST['customer_address'] ?? '');
    $post_note = trim($_POST['shipping_note'] ?? '');
    $post_payment = $_POST['payment_method'] ?? 'cod';
    if (!in_array($post_payment, ['cod', 'bank_transfer'])) {
        $post_payment = 'cod';
    }

    if (empty($post_name) || empty($post_phone) || empty($post_address)) {
        $error_message = 'Vui lòng điền đầy đủ Họ tên, Số điện thoại và Địa chỉ nhận hàng!';
    } else {
        try {
            $pdo->beginTransaction();

            // 5.1. Kiểm tra tồn kho nghiêm ngặt với SELECT FOR UPDATE
            foreach ($cart_items as $item) {
                $stockCheck = $pdo->prepare("SELECT quantity, product_name FROM products WHERE product_id = ? FOR UPDATE");
                $stockCheck->execute([$item['product_id']]);
                $currProd = $stockCheck->fetch(PDO::FETCH_ASSOC);

                if (!$currProd || $currProd['quantity'] < $item['quantity']) {
                    throw new Exception("Sản phẩm '{$item['product_name']}' hiện không đủ số lượng trong kho (chỉ còn {$currProd['quantity']} sản phẩm)!");
                }
            }

            // 5.2. Tạo mã đơn hàng duy nhất
            $order_code = 'ORD-' . date('Ymd') . '-' . mt_rand(1000, 9999);

            // 5.3. Tạo đơn hàng trong bảng `orders`
            $orderStmt = $pdo->prepare("
                INSERT INTO orders (
                    order_code, user_id, customer_name, customer_phone, customer_email,
                    customer_address, shipping_note, payment_method, payment_status,
                    shipping_fee, discount_amount, coupon_code, total_amount, status, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'unpaid', ?, ?, ?, ?, 'pending', NOW())
            ");
            $coupon_code_used = $coupon['code'] ?? null;
            $orderStmt->execute([
                $order_code, $user_id, $post_name, $post_phone, $post_email,
                $post_address, $post_note, $post_payment,
                $shipping_fee, $discount_amount, $coupon_code_used, $total_amount
            ]);
            $order_id = $pdo->lastInsertId();

            // 5.4. Thêm từng sản phẩm vào `order_items` và trừ kho
            foreach ($cart_items as $item) {
                $sub = $item['price'] * $item['quantity'];
                $itemStmt = $pdo->prepare("
                    INSERT INTO order_items (order_id, product_id, product_name, price, quantity, subtotal, image)
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                $itemStmt->execute([
                    $order_id, $item['product_id'], $item['product_name'],
                    $item['price'], $item['quantity'], $sub, $item['image']
                ]);

                // Trừ tồn kho và tăng lượt bán
                $upStock = $pdo->prepare("
                    UPDATE products 
                    SET quantity = quantity - ?, sold_count = sold_count + ?
                    WHERE product_id = ?
                ");
                $upStock->execute([$item['quantity'], $item['quantity'], $item['product_id']]);
            }

            // 5.5. Cập nhật coupon nếu có
            if ($coupon) {
                $pdo->prepare("UPDATE coupons SET used_count = used_count + 1 WHERE id = ?")->execute([$coupon['id']]);
                $pdo->prepare("
                    INSERT INTO coupon_usages (coupon_id, user_id, order_id, discount_amount, used_at)
                    VALUES (?, ?, ?, ?, NOW())
                ")->execute([$coupon['id'], $user_id, $order_id, $discount_amount]);
            }

            // 5.6. Ghi log lịch sử trạng thái đơn hàng
            $pdo->prepare("
                INSERT INTO order_status_history (order_id, old_status, new_status, note, changed_by, created_at)
                VALUES (?, NULL, 'pending', 'Khách hàng tạo đơn hàng mới', ?)
            ")->execute([$order_id, $user_id]);

            // 5.7. Xóa sạch giỏ hàng của user
            $pdo->prepare("DELETE FROM cart_items WHERE cart_id = ?")->execute([$cart['cart_id']]);
            unset($_SESSION['applied_coupon']);

            $pdo->commit();

            // Chuyển hướng tới trang hoàn tất đơn hàng
            header("Location: order_success.php?order_id=" . $order_id);
            exit;

        } catch (Exception $e) {
            $pdo->rollBack();
            $error_message = $e->getMessage();
        }
    }
}

include 'header.php';
?>

<div style="height: 125px;"></div>

<!-- Breadcrumb -->
<div class="py-2 bg-light border-bottom">
  <div class="container-fluid px-lg-5">
    <nav aria-label="breadcrumb">
      <ol class="breadcrumb mb-0 small">
        <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none text-muted">Trang chủ</a></li>
        <li class="breadcrumb-item"><a href="cart.php" class="text-decoration-none text-muted">Giỏ hàng</a></li>
        <li class="breadcrumb-item active" style="color: #9e4760;">Xác nhận đặt hàng & Thanh toán</li>
      </ol>
    </nav>
  </div>
</div>

<div class="py-5" style="background: #fffdfb; min-height: 80vh;">
  <div class="container-fluid px-lg-5">
    
    <div class="mb-4">
      <h2 class="fw-bold mb-1" style="color: #4a2835;">
        <i class="bi bi-credit-card-2-front-fill text-danger me-2"></i> Xác Nhận Đơn Hàng & Thanh Toán
      </h2>
      <p class="text-muted small">Vui lòng kiểm tra lại thông tin người nhận và phương thức thanh toán</p>
    </div>

    <?php if ($error_message): ?>
      <div class="alert alert-danger rounded-4 p-3 mb-4 shadow-xs">
        <i class="bi bi-exclamation-triangle-fill me-2"></i> <?= htmlspecialchars($error_message) ?>
      </div>
    <?php endif; ?>

    <form method="POST" id="checkoutForm">
      <div class="row g-4">
        
        <!-- Cột Trái: Thông tin người nhận & Địa chỉ -->
        <div class="col-lg-7">
          <div class="card border-0 rounded-4 shadow-sm bg-white p-4 p-md-5 mb-4">
            <h5 class="fw-bold mb-4" style="color: #4a2835;">
              <i class="bi bi-geo-alt-fill text-danger me-2"></i> 1. Thông Tin Nhận Hàng
            </h5>

            <div class="mb-3">
              <label class="form-label fw-bold small text-muted">Họ và tên người nhận <span class="text-danger">*</span></label>
              <input type="text" name="customer_name" class="form-control rounded-3" 
                     value="<?= htmlspecialchars($customer_name) ?>" placeholder="Nguyễn Văn A" required>
            </div>

            <div class="row g-3 mb-3">
              <div class="col-md-6">
                <label class="form-label fw-bold small text-muted">Số điện thoại <span class="text-danger">*</span></label>
                <input type="tel" name="customer_phone" class="form-control rounded-3" 
                       value="<?= htmlspecialchars($customer_phone) ?>" placeholder="0901234567" required>
              </div>
              <div class="col-md-6">
                <label class="form-label fw-bold small text-muted">Email nhận thông báo</label>
                <input type="email" name="customer_email" class="form-control rounded-3" 
                       value="<?= htmlspecialchars($customer_email) ?>" placeholder="email@gmail.com">
              </div>
            </div>

            <div class="mb-3">
              <label class="form-label fw-bold small text-muted">Địa chỉ nhận hàng chi tiết <span class="text-danger">*</span></label>
              <textarea name="customer_address" class="form-control rounded-3" rows="3" 
                        placeholder="Số nhà, tên đường, phường/xã, quận/huyện, tỉnh/thành phố..." required><?= htmlspecialchars($customer_address) ?></textarea>
            </div>

            <div class="mb-0">
              <label class="form-label fw-bold small text-muted">Ghi chú giao hàng (Tùy chọn)</label>
              <input type="text" name="shipping_note" class="form-control rounded-3" 
                     placeholder="Ví dụ: Giao giờ hành chính, gọi trước khi giao 15 phút...">
            </div>

          </div>

          <!-- Phương thức thanh toán -->
          <div class="card border-0 rounded-4 shadow-sm bg-white p-4 p-md-5">
            <h5 class="fw-bold mb-4" style="color: #4a2835;">
              <i class="bi bi-wallet2 text-danger me-2"></i> 2. Phương Thức Thanh Toán
            </h5>

            <div class="d-flex flex-column gap-3">
              
              <!-- Option 1: COD -->
              <label class="p-3 rounded-4 border d-flex align-items-center gap-3 cursor-pointer payment-option active" style="background:#fffafc;">
                <input type="radio" name="payment_method" value="cod" class="form-check-input mt-0" checked onchange="toggleBankTransferInfo(false)">
                <div class="rounded-circle d-flex align-items-center justify-content-center bg-white text-danger border" style="width: 44px; height: 44px;">
                  <i class="bi bi-cash-stack fs-4"></i>
                </div>
                <div class="flex-grow-1">
                  <div class="fw-bold text-dark">Thanh toán tiền mặt khi nhận hàng (COD)</div>
                  <small class="text-muted">Bạn chỉ thanh toán khi đã nhận hàng và kiểm tra sản phẩm an toàn.</small>
                </div>
              </label>

              <!-- Option 2: Chuyển khoản QR -->
              <label class="p-3 rounded-4 border d-flex align-items-center gap-3 cursor-pointer payment-option" style="background:#fff;">
                <input type="radio" name="payment_method" value="bank_transfer" class="form-check-input mt-0" onchange="toggleBankTransferInfo(true)">
                <div class="rounded-circle d-flex align-items-center justify-content-center bg-white text-primary border" style="width: 44px; height: 44px;">
                  <i class="bi bi-qr-code-scan fs-4"></i>
                </div>
                <div class="flex-grow-1">
                  <div class="fw-bold text-dark">Chuyển khoản ngân hàng qua mã QR (VietQR)</div>
                  <small class="text-muted">Quét mã QR 24/7 với Vietcombank, MB Bank, Techcombank, MoMo...</small>
                </div>
              </label>

              <!-- Khung hướng dẫn chuyển khoản (Hiện khi chọn Bank Transfer) -->
              <div id="bankTransferBox" class="p-4 rounded-4 border bg-light d-none">
                <h6 class="fw-bold text-dark mb-3"><i class="bi bi-bank me-2 text-primary"></i> Thông Tin Tài Khoản Ngân Hàng</h6>
                <div class="row g-3 align-items-center">
                  <div class="col-md-7 small">
                    <p class="mb-1">Ngân hàng: <strong>MB BANK (Ngân hàng Quân Đội)</strong></p>
                    <p class="mb-1">Số tài khoản: <strong class="text-danger fs-6">0909888999</strong></p>
                    <p class="mb-1">Chủ tài khoản: <strong>BEAUTYSHOP VIETNAM</strong></p>
                    <p class="mb-2">Chi nhánh: <strong>Đà Nẵng</strong></p>
                    <div class="alert alert-warning p-2 small mb-0">
                      <i class="bi bi-info-circle me-1"></i> Nội dung chuyển khoản sẽ là: <strong>Mã đơn hàng của bạn</strong> (sẽ hiển thị sau khi bấm Xác nhận).
                    </div>
                  </div>
                  <div class="col-md-5 text-center">
                    <div class="p-2 bg-white rounded-3 border d-inline-block shadow-xs">
                      <img src="https://api.vietqr.io/image/970422-0909888999-W8Zq9gM.jpg?accountName=BEAUTYSHOP%20VIETNAM&amount=<?= (int)$total_amount ?>" 
                           alt="VietQR" class="img-fluid rounded" style="max-width: 150px;">
                      <small class="d-block text-muted mt-1" style="font-size: 0.65rem;">Quét để thanh toán</small>
                    </div>
                  </div>
                </div>
              </div>

            </div>

          </div>
        </div>

        <!-- Cột Phải: Xem lại danh sách & Tổng tiền -->
        <div class="col-lg-5">
          <div class="card border-0 rounded-4 shadow-sm bg-white p-4 p-md-5 sticky-top" style="top: 140px;">
            <h5 class="fw-bold mb-3" style="color: #4a2835;">Đơn Hàng (<?= count($cart_items) ?> món)</h5>

            <!-- Danh sách món hàng -->
            <div class="d-flex flex-column gap-3 mb-4 pb-3 border-bottom overflow-y-auto" style="max-height: 280px;">
              <?php foreach ($cart_items as $item): ?>
                <div class="d-flex align-items-center gap-3">
                  <img src="../images/products/<?= htmlspecialchars($item['image']) ?>" 
                       alt="<?= htmlspecialchars($item['product_name']) ?>" 
                       class="rounded-3 object-fit-cover border" style="width: 55px; height: 55px;">
                  <div class="flex-grow-1 overflow-hidden">
                    <div class="fw-bold small text-truncate" title="<?= htmlspecialchars($item['product_name']) ?>">
                      <?= htmlspecialchars($item['product_name']) ?>
                    </div>
                    <small class="text-muted">SL: <?= $item['quantity'] ?> × <?= format_price($item['price']) ?></small>
                  </div>
                  <div class="fw-bold text-danger small">
                    <?= format_price($item['price'] * $item['quantity']) ?>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>

            <!-- Bảng tính tiền chi tiết -->
            <div class="d-flex flex-column gap-2 pb-3 border-bottom small">
              <div class="d-flex justify-content-between">
                <span class="text-muted">Tổng tiền hàng:</span>
                <strong class="text-dark"><?= format_price($subtotal) ?></strong>
              </div>

              <?php if ($discount_amount > 0): ?>
                <div class="d-flex justify-content-between">
                  <span class="text-muted">Mã giảm giá (<?= htmlspecialchars($coupon['code']) ?>):</span>
                  <strong class="text-success">-<?= format_price($discount_amount) ?></strong>
                </div>
              <?php endif; ?>

              <div class="d-flex justify-content-between">
                <span class="text-muted">Phí vận chuyển:</span>
                <span>
                  <?php if ($shipping_fee === 0): ?>
                    <span class="badge bg-success-subtle text-success">MIỄN PHÍ</span>
                  <?php else: ?>
                    <strong><?= format_price($shipping_fee) ?></strong>
                  <?php endif; ?>
                </span>
              </div>
            </div>

            <!-- Tổng thanh toán cuối cùng -->
            <div class="d-flex justify-content-between align-items-baseline my-3">
              <span class="fw-bold fs-5" style="color: #4a2835;">Tổng thanh toán:</span>
              <span class="display-6 fw-bold text-danger" style="font-size: 2rem;">
                <?= format_price($total_amount) ?>
              </span>
            </div>

            <!-- Nút Xác nhận đặt hàng -->
            <button type="submit" class="btn btn-danger btn-lg rounded-pill w-100 py-3 text-white fw-bold shadow-sm d-flex align-items-center justify-content-center gap-2"
                    style="background: linear-gradient(135deg, #d47a88, #9e4760); border: none;">
              <i class="bi bi-bag-check-fill fs-5"></i> XÁC NHẬN ĐẶT HÀNG
            </button>

            <div class="mt-3 text-center">
              <small class="text-muted" style="font-size: 0.75rem;">
                Bằng việc bấm Đặt hàng, bạn đồng ý với <a href="policies.php?p=shipping" class="text-danger">Điều khoản & Chính sách giao hàng</a> của BeautyShop.
              </small>
            </div>

          </div>
        </div>

      </div>
    </form>

  </div>
</div>

<script>
function toggleBankTransferInfo(show) {
  const box = document.getElementById('bankTransferBox');
  if (show) {
    box.classList.remove('d-none');
  } else {
    box.classList.add('d-none');
  }
}
</script>

<?php include 'footer.php'; ?>
