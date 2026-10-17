<?php
session_start();
require_once __DIR__ . '/config.php';

if (!is_logged_in()) {
    header("Location: login.php?redirect=cart.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$page_title = 'Giỏ Hàng Của Bạn - BeautyShop';

// Lấy giỏ hàng của user
$stmt = $pdo->prepare("SELECT cart_id FROM cart WHERE user_id = ?");
$stmt->execute([$user_id]);
$cart = $stmt->fetch();

$cart_items = [];
$subtotal = 0;

if ($cart) {
    $stmt = $pdo->prepare("
        SELECT ci.cart_item_id, ci.product_id, ci.quantity, 
               p.product_name, p.price, p.original_price, p.image, p.capacity, p.quantity as stock,
               b.brand_name
        FROM cart_items ci
        JOIN products p ON ci.product_id = p.product_id
        LEFT JOIN brands b ON p.brand_id = b.brand_id
        WHERE ci.cart_id = ?
        ORDER BY ci.cart_item_id DESC
    ");
    $stmt->execute([$cart['cart_id']]);
    $cart_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($cart_items as $item) {
        $subtotal += $item['price'] * $item['quantity'];
    }
}

// Tính phí vận chuyển (Freeship từ 150.000₫)
$shipping_fee = ($subtotal >= 150000 || $subtotal == 0) ? 0 : 30000;

// Tính giảm giá nếu đã áp coupon
$coupon = $_SESSION['applied_coupon'] ?? null;
$discount_amount = 0;
if ($coupon && $subtotal > 0) {
    if ($coupon['discount_type'] === 'percent') {
        $discount_amount = $subtotal * ((float)$coupon['discount_value'] / 100);
    } else {
        $discount_amount = (float)$coupon['discount_value'];
    }
    $discount_amount = min($discount_amount, $subtotal);
} else {
    unset($_SESSION['applied_coupon']);
    $coupon = null;
}

$final_total = max(0, $subtotal - $discount_amount + $shipping_fee);

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
        <li class="breadcrumb-item active" style="color: #9e4760;">Giỏ hàng của bạn</li>
      </ol>
    </nav>
  </div>
</div>

<div class="py-5" style="background: #fffdfb; min-height: 75vh;">
  <div class="container-fluid px-lg-5">
    
    <div class="d-flex align-items-center gap-2 mb-4">
      <h2 class="fw-bold mb-0" style="color: #4a2835;">
        <i class="bi bi-bag-heart-fill text-danger me-2"></i> Giỏ Hàng
      </h2>
      <span class="badge bg-danger rounded-pill fs-6" id="cartCountBadge"><?= count($cart_items) ?> món</span>
    </div>

    <?php if (empty($cart_items)): ?>
      <div class="text-center py-5 bg-white rounded-4 border p-5 shadow-xs">
        <i class="bi bi-cart-x text-muted" style="font-size: 4rem;"></i>
        <h4 class="fw-bold mt-3" style="color: #4a2835;">Giỏ hàng của bạn đang trống</h4>
        <p class="text-muted small mb-4">Hãy dạo quanh cửa hàng và chọn những sản phẩm mỹ phẩm yêu thích bạn nhé!</p>
        <a href="products.php" class="btn btn-danger rounded-pill px-4 py-2 text-white" style="background: #d47a88; border:none;">
          <i class="bi bi-bag-plus me-1"></i> Khám phá sản phẩm ngay
        </a>
      </div>
    <?php else: ?>
      <div class="row g-4" id="cartContentArea">
        
        <!-- Cột Trái: Danh sách sản phẩm -->
        <div class="col-lg-8">
          <div class="card border-0 rounded-4 shadow-sm bg-white overflow-hidden p-3 p-md-4">
            
            <div class="d-none d-md-flex text-muted small fw-bold pb-3 border-bottom mb-3">
              <div style="flex: 2;">SẢN PHẨM</div>
              <div style="flex: 1;" class="text-center">ĐƠN GIÁ</div>
              <div style="flex: 1;" class="text-center">SỐ LƯỢNG</div>
              <div style="flex: 1;" class="text-end">THÀNH TIỀN</div>
              <div style="width: 45px;"></div>
            </div>

            <div class="d-flex flex-column gap-3" id="cartItemList">
              <?php foreach ($cart_items as $item): 
                $item_subtotal = $item['price'] * $item['quantity'];
                $is_out_of_stock = ($item['quantity'] > $item['stock']);
              ?>
                <div class="cart-row p-3 rounded-3 border d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 shadow-xs bg-white" 
                     id="cartRow-<?= $item['cart_item_id'] ?>">
                  
                  <!-- Info -->
                  <div class="d-flex align-items-center gap-3" style="flex: 2;">
                    <a href="product_detail.php?id=<?= $item['product_id'] ?>">
                      <img src="../images/products/<?= htmlspecialchars($item['image']) ?>" 
                           alt="<?= htmlspecialchars($item['product_name']) ?>" 
                           class="rounded-3 object-fit-cover shadow-xs" 
                           style="width: 75px; height: 75px;">
                    </a>
                    <div>
                      <small class="text-muted"><?= htmlspecialchars($item['brand_name'] ?? 'Chính hãng') ?> • <?= htmlspecialchars($item['capacity'] ?? '') ?></small>
                      <h6 class="mb-1 fw-bold text-dark line-clamp-2" style="font-size: 0.95rem;">
                        <a href="product_detail.php?id=<?= $item['product_id'] ?>" class="text-decoration-none text-dark hover-primary">
                          <?= htmlspecialchars($item['product_name']) ?>
                        </a>
                      </h6>
                      <small class="<?= $is_out_of_stock ? 'text-danger fw-bold' : 'text-muted' ?>">
                        <?= $is_out_of_stock ? "Kho chỉ còn {$item['stock']} sản phẩm!" : "Còn lại: {$item['stock']}" ?>
                      </small>
                    </div>
                  </div>

                  <!-- Price -->
                  <div class="text-md-center" style="flex: 1;">
                    <span class="fw-bold text-danger"><?= format_price($item['price']) ?></span>
                  </div>

                  <!-- Quantity selector -->
                  <div class="d-flex justify-content-md-center align-items-center" style="flex: 1;">
                    <div class="input-group input-group-sm" style="width: 105px;">
                      <button class="btn btn-outline-secondary" type="button" 
                              onclick="updateCartItem(<?= $item['cart_item_id'] ?>, <?= $item['quantity'] - 1 ?>, <?= $item['stock'] ?>)">-</button>
                      <input type="text" class="form-control text-center fw-bold bg-white" value="<?= $item['quantity'] ?>" readonly>
                      <button class="btn btn-outline-secondary" type="button" 
                              onclick="updateCartItem(<?= $item['cart_item_id'] ?>, <?= $item['quantity'] + 1 ?>, <?= $item['stock'] ?>)">+</button>
                    </div>
                  </div>

                  <!-- Subtotal -->
                  <div class="text-md-end" style="flex: 1;">
                    <strong class="text-danger fs-6" id="subtotal-<?= $item['cart_item_id'] ?>">
                      <?= format_price($item_subtotal) ?>
                    </strong>
                  </div>

                  <!-- Remove btn -->
                  <div class="text-end" style="width: 45px;">
                    <button onclick="removeCartItem(<?= $item['cart_item_id'] ?>)" class="btn btn-sm btn-light text-danger rounded-circle p-2" title="Xóa khỏi giỏ">
                      <i class="bi bi-trash"></i>
                    </button>
                  </div>

                </div>
              <?php endforeach; ?>
            </div>

            <div class="mt-4 pt-3 border-top d-flex justify-content-between align-items-center">
              <a href="products.php" class="text-decoration-none text-muted small">
                <i class="bi bi-arrow-left me-1"></i> Tiếp tục chọn thêm sản phẩm
              </a>
              <span class="text-muted small">
                <i class="bi bi-shield-check text-success me-1"></i> Giá đã bao gồm thuế VAT
              </span>
            </div>

          </div>
        </div>

        <!-- Cột Phải: Tóm tắt đơn hàng & Mã giảm giá -->
        <div class="col-lg-4">
          <div class="card border-0 rounded-4 shadow-sm bg-white p-4 sticky-top" style="top: 140px;">
            <h5 class="fw-bold mb-3" style="color: #4a2835;">Tóm Tắt Đơn Hàng</h5>

            <!-- Khung nhập mã giảm giá -->
            <div class="mb-4">
              <label class="form-label small fw-bold text-muted text-uppercase">Mã giảm giá / Voucher</label>
              <div class="input-group">
                <input type="text" id="couponCodeInput" class="form-control rounded-start-pill text-uppercase" 
                       placeholder="Nhập BEAUTYNEW..." value="<?= $coupon['code'] ?? '' ?>" <?= $coupon ? 'readonly' : '' ?>>
                <?php if ($coupon): ?>
                  <button class="btn btn-outline-danger rounded-end-pill px-3" type="button" onclick="removeCoupon()">
                    <i class="bi bi-x-circle"></i> Hủy
                  </button>
                <?php else: ?>
                  <button class="btn btn-danger rounded-end-pill px-3 text-white" type="button" onclick="applyCoupon()" style="background:#d47a88; border:none;">
                    Áp dụng
                  </button>
                <?php endif; ?>
              </div>
              
              <!-- Gợi ý mã có sẵn -->
              <div class="mt-2 d-flex flex-wrap gap-1">
                <span class="badge bg-light text-dark border cursor-pointer" onclick="fillCoupon('BEAUTYNEW')">BEAUTYNEW (-10%)</span>
                <span class="badge bg-light text-dark border cursor-pointer" onclick="fillCoupon('FREESHIP')">FREESHIP (-30k)</span>
                <span class="badge bg-light text-dark border cursor-pointer" onclick="fillCoupon('XINCHAO50')">XINCHAO50 (-50k)</span>
              </div>
            </div>

            <!-- Bảng tính tiền -->
            <div class="d-flex flex-column gap-2 pb-3 border-bottom small">
              <div class="d-flex justify-content-between">
                <span class="text-muted">Tổng tiền hàng:</span>
                <strong class="text-dark" id="displaySubtotal"><?= format_price($subtotal) ?></strong>
              </div>

              <div class="d-flex justify-content-between">
                <span class="text-muted">Giảm giá voucher:</span>
                <strong class="text-success" id="displayDiscount">-<?= format_price($discount_amount) ?></strong>
              </div>

              <div class="d-flex justify-content-between">
                <span class="text-muted">Phí vận chuyển:</span>
                <span id="displayShipping">
                  <?php if ($shipping_fee === 0): ?>
                    <span class="text-success fw-bold">MIỄN PHÍ</span>
                  <?php else: ?>
                    <strong><?= format_price($shipping_fee) ?></strong>
                  <?php endif; ?>
                </span>
              </div>
            </div>

            <!-- Tổng thanh toán -->
            <div class="d-flex justify-content-between align-items-baseline my-3">
              <span class="fw-bold" style="color: #4a2835;">Tổng thanh toán:</span>
              <span class="display-6 fw-bold text-danger" style="font-size: 1.8rem;" id="displayFinalTotal">
                <?= format_price($final_total) ?>
              </span>
            </div>

            <!-- Nút Đặt hàng -->
            <a href="checkout.php" class="btn btn-danger btn-lg rounded-pill w-100 py-3 text-white fw-bold shadow-sm d-flex align-items-center justify-content-center gap-2"
               style="background: linear-gradient(135deg, #d47a88, #9e4760); border: none;">
              Tiến hành thanh toán <i class="bi bi-arrow-right"></i>
            </a>

            <div class="mt-3 text-center">
              <small class="text-muted" style="font-size: 0.75rem;">
                <i class="bi bi-lock-fill text-success me-1"></i> Cam kết bảo mật thông tin thanh toán 100%
              </small>
            </div>

          </div>
        </div>

      </div>
    <?php endif; ?>

  </div>
</div>

<script>
function updateCartItem(itemId, newQty, stock) {
  if (newQty < 1) {
    if (confirm('Bạn có muốn xóa sản phẩm này khỏi giỏ hàng?')) {
      removeCartItem(itemId);
    }
    return;
  }
  if (newQty > stock) {
    alert('Kho hàng chỉ còn tối đa ' + stock + ' sản phẩm!');
    return;
  }

  fetch('update_cart.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: `cart_item_id=${itemId}&quantity=${newQty}`
  })
  .then(r => r.json())
  .then(data => {
    if (data.success) {
      location.reload(); // Làm mới để cập nhật coupon và phí ship chính xác
    } else {
      alert(data.message);
    }
  });
}

function removeCartItem(itemId) {
  fetch('remove_from_cart.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: `cart_item_id=${itemId}`
  })
  .then(r => r.json())
  .then(data => {
    if (data.success) {
      location.reload();
    } else {
      alert(data.message);
    }
  });
}

function fillCoupon(code) {
  const input = document.getElementById('couponCodeInput');
  if (input && !input.readOnly) {
    input.value = code;
  }
}

function applyCoupon() {
  const code = document.getElementById('couponCodeInput').value.trim();
  if (!code) {
    alert('Vui lòng nhập mã giảm giá!');
    return;
  }

  fetch('api_apply_coupon.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ code: code, action: 'apply' })
  })
  .then(r => r.json())
  .then(data => {
    if (data.success) {
      alert(data.message);
      location.reload();
    } else {
      alert(data.message);
    }
  });
}

function removeCoupon() {
  fetch('api_apply_coupon.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ action: 'remove' })
  })
  .then(r => r.json())
  .then(data => {
    location.reload();
  });
}
</script>

<?php include 'footer.php'; ?>
