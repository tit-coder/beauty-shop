<?php
session_start();
include 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$message = '';

// Lấy giỏ hàng
$stmt = $pdo->prepare("SELECT cart_id FROM cart WHERE user_id = ?");
$stmt->execute([$user_id]);
$cart = $stmt->fetch();

if (!$cart) {
    header("Location: cart.php");
    exit;
}

$stmt = $pdo->prepare("
    SELECT ci.cart_item_id, ci.product_id, ci.quantity, 
           p.product_name, p.price, p.image, p.quantity as stock
    FROM cart_items ci
    JOIN products p ON ci.product_id = p.product_id
    WHERE ci.cart_id = ?
");
$stmt->execute([$cart['cart_id']]);
$cart_items = $stmt->fetchAll();

if (empty($cart_items)) {
    header("Location: cart.php");
    exit;
}

$total = 0;
foreach ($cart_items as $item) {
    $total += $item['price'] * $item['quantity'];
}

// Lấy thông tin user
$stmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Xử lý đặt hàng
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customer_name = trim($_POST['customer_name'] ?? '');
    $customer_phone = trim($_POST['customer_phone'] ?? '');
    $customer_address = trim($_POST['customer_address'] ?? '');
    $payment_method = $_POST['payment_method'] ?? 'cod';

    if (empty($customer_name) || empty($customer_phone) || empty($customer_address)) {
        $message = '<div class="alert alert-danger">Vui lòng điền đầy đủ thông tin!</div>';
    } else {
        try {
            $pdo->beginTransaction();

            // Kiểm tra tồn kho trước khi đặt hàng
            foreach ($cart_items as $item) {
                if ($item['quantity'] > $item['stock']) {
                    throw new Exception("Sản phẩm '{$item['product_name']}' không đủ số lượng trong kho!");
                }
            }

            // Tạo đơn hàng
            $stmt = $pdo->prepare("
                INSERT INTO orders (user_id, customer_name, customer_phone, customer_address, payment_method, total_amount)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$user_id, $customer_name, $customer_phone, $customer_address, $payment_method, $total]);
            $order_id = $pdo->lastInsertId();

            // Thêm chi tiết đơn hàng và trừ tồn kho
            foreach ($cart_items as $item) {
                // Thêm order_items
                $stmt = $pdo->prepare("
                    INSERT INTO order_items (order_id, product_id, product_name, price, quantity, subtotal)
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                $subtotal = $item['price'] * $item['quantity'];
                $stmt->execute([
                    $order_id,
                    $item['product_id'],
                    $item['product_name'],
                    $item['price'],
                    $item['quantity'],
                    $subtotal
                ]);

                // Trừ số lượng trong kho
                $stmt = $pdo->prepare("UPDATE products SET quantity = quantity - ? WHERE product_id = ?");
                $stmt->execute([$item['quantity'], $item['product_id']]);
            }

            // Xóa giỏ hàng sau khi đặt hàng thành công
            $stmt = $pdo->prepare("DELETE FROM cart_items WHERE cart_id = ?");
            $stmt->execute([$cart['cart_id']]);

            $pdo->commit();

            // Chuyển đến trang xác nhận
            header("Location: order_success.php?order_id=" . $order_id);
            exit;

        } catch (Exception $e) {
            $pdo->rollBack();
            $message = '<div class="alert alert-danger">Lỗi: ' . $e->getMessage() . '</div>';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thanh toán - Beauty Cosmetics</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: #f8f9fa; }
        .checkout-header { background: linear-gradient(135deg, #ff6da5ff, rgba(248, 155, 229, 1)); color: white; padding: 30px 0; }
        .checkout-section { background: white; border-radius: 10px; padding: 30px; margin-bottom: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .product-item { border-bottom: 1px solid #eee; padding: 15px 0; }
        .product-item:last-child { border-bottom: none; }
        .product-item img { width: 60px; height: 60px; object-fit: cover; border-radius: 5px; }
        .btn-order { background: #e3245d; color: white; width: 100%; padding: 15px; border: none; border-radius: 8px; font-weight: bold; font-size: 18px; }
        .btn-order:hover { background: #ff6da5ff; }
        .payment-option { border: 2px solid #ddd; padding: 15px; border-radius: 8px; margin-bottom: 10px; cursor: pointer; transition: all 0.3s; }
        .payment-option:hover { border-color: #e3245d; }
        .payment-option input[type="radio"] { margin-right: 10px; }
        .payment-option.selected { border-color: #e3245d; background: #fff5f8; }
    </style>
</head>
<body>
    <div class="checkout-header">
        <div class="container">
            <h2><i class="fas fa-credit-card"></i> Thanh toán</h2>
        </div>
    </div>

    <div class="container my-5">
        <?= $message ?>

        <form method="POST">
            <div class="row">
                <div class="col-lg-7">
                    <div class="checkout-section">
                        <h4 class="mb-4"><i class="fas fa-user"></i> Thông tin người nhận</h4>
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold">Họ và tên <span class="text-danger">*</span></label>
                            <input type="text" name="customer_name" class="form-control" required 
                                   value="<?= htmlspecialchars($_POST['customer_name'] ?? $user['username']) ?>">
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Số điện thoại <span class="text-danger">*</span></label>
                            <input type="tel" name="customer_phone" class="form-control" required 
                                   value="<?= htmlspecialchars($_POST['customer_phone'] ?? '') ?>" 
                                   pattern="[0-9]{10,11}" placeholder="0123456789">
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Địa chỉ giao hàng <span class="text-danger">*</span></label>
                            <textarea name="customer_address" class="form-control" rows="3" required 
                                      placeholder="Số nhà, tên đường, phường/xã, quận/huyện, tỉnh/thành phố"><?= htmlspecialchars($_POST['customer_address'] ?? '') ?></textarea>
                        </div>
                    </div>

                    <div class="checkout-section">
                        <h4 class="mb-4"><i class="fas fa-wallet"></i> Phương thức thanh toán</h4>
                        
                        <label class="payment-option">
                            <input type="radio" name="payment_method" value="cod" checked>
                            <i class="fas fa-money-bill-wave"></i> Thanh toán khi nhận hàng (COD)
                        </label>

                        <label class="payment-option">
                            <input type="radio" name="payment_method" value="bank_transfer">
                            <i class="fas fa-university"></i> Chuyển khoản ngân hàng
                        </label>

                        <label class="payment-option">
                            <input type="radio" name="payment_method" value="momo">
                            <i class="fas fa-mobile-alt"></i> Ví MoMo
                        </label>

                        <label class="payment-option">
                            <input type="radio" name="payment_method" value="vnpay">
                            <i class="fas fa-credit-card"></i> VNPay
                        </label>
                    </div>
                </div>

                <div class="col-lg-5">
                    <div class="checkout-section">
                        <h4 class="mb-4"><i class="fas fa-shopping-bag"></i> Đơn hàng của bạn</h4>
                        
                        <?php foreach ($cart_items as $item): ?>
                            <div class="product-item">
                                <div class="d-flex align-items-center">
                                    <img src="../images/products/<?= htmlspecialchars($item['image']) ?>" alt="">
                                    <div class="flex-grow-1 ms-3">
                                        <div class="fw-bold"><?= htmlspecialchars($item['product_name']) ?></div>
                                        <small class="text-muted">Số lượng: <?= $item['quantity'] ?></small>
                                    </div>
                                    <div class="text-end">
                                        <strong><?= number_format($item['price'] * $item['quantity'], 0, ',', '.') ?> ₫</strong>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>

                        <hr class="my-3">

                        <div class="d-flex justify-content-between mb-2">
                            <span>Tạm tính:</span>
                            <strong><?= number_format($total, 0, ',', '.') ?> ₫</strong>
                        </div>

                        <div class="d-flex justify-content-between mb-2">
                            <span>Phí vận chuyển:</span>
                            <strong>Miễn phí</strong>
                        </div>

                        <hr class="my-3">

                        <div class="d-flex justify-content-between mb-4">
                            <h5>Tổng cộng:</h5>
                            <h5 class="text-danger"><?= number_format($total, 0, ',', '.') ?> ₫</h5>
                        </div>

                        <button type="submit" class="btn btn-order">
                            <i class="fas fa-check-circle"></i> ĐẶT HÀNG
                        </button>

                        <a href="cart.php" class="btn btn-outline-secondary w-100 mt-2">
                            <i class="fas fa-arrow-left"></i> Quay lại giỏ hàng
                        </a>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <script>
        // Highlight selected payment method
        document.querySelectorAll('.payment-option').forEach(option => {
            option.addEventListener('click', function() {
                document.querySelectorAll('.payment-option').forEach(o => o.classList.remove('selected'));
                this.classList.add('selected');
                this.querySelector('input[type="radio"]').checked = true;
            });
        });

        // Set initial selected
        document.querySelector('.payment-option input:checked').closest('.payment-option').classList.add('selected');
    </script>
</body>
</html>
