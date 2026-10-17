<?php
session_start();
include 'config.php';

// Kiểm tra đăng nhập
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

$cart_items = [];
$total = 0;

if ($cart) {
    $stmt = $pdo->prepare("
        SELECT ci.cart_item_id, ci.product_id, ci.quantity, 
               p.product_name, p.price, p.image, p.quantity as stock
        FROM cart_items ci
        JOIN products p ON ci.product_id = p.product_id
        WHERE ci.cart_id = ?
    ");
    $stmt->execute([$cart['cart_id']]);
    $cart_items = $stmt->fetchAll();

    foreach ($cart_items as $item) {
        $total += $item['price'] * $item['quantity'];
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Giỏ hàng - Beauty Cosmetics</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: #f8f9fa; }
        .cart-header { background: linear-gradient(135deg, #ff6da5ff, rgba(248, 155, 229, 1)); color: white; padding: 30px 0; }
        .cart-item { background: white; border-radius: 10px; padding: 20px; margin-bottom: 15px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .cart-item img { width: 100px; height: 100px; object-fit: cover; border-radius: 8px; }
        .quantity-control { display: flex; align-items: center; gap: 10px; }
        .quantity-control button { width: 35px; height: 35px; border: none; background: #e3245d; color: white; border-radius: 5px; cursor: pointer; }
        .quantity-control button:hover { background: #ff6da5ff; }
        .quantity-control input { width: 60px; text-align: center; border: 1px solid #ddd; border-radius: 5px; }
        .btn-remove { background: #dc3545; color: white; border: none; padding: 8px 15px; border-radius: 5px; }
        .btn-remove:hover { background: #c82333; }
        .cart-summary { background: white; border-radius: 10px; padding: 25px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); position: sticky; top: 20px; }
        .btn-checkout { background: #e3245d; color: white; width: 100%; padding: 12px; border: none; border-radius: 8px; font-weight: bold; }
        .btn-checkout:hover { background: #ff6da5ff; }
    </style>
</head>
<body>
    <div class="cart-header">
        <div class="container">
            <h2><i class="fas fa-shopping-cart"></i> Giỏ hàng của bạn</h2>
        </div>
    </div>

    <div class="container my-5">
        <?php if ($message): ?>
            <div class="alert alert-info"><?= $message ?></div>
        <?php endif; ?>

        <?php if (empty($cart_items)): ?>
            <div class="text-center py-5">
                <i class="fas fa-shopping-cart" style="font-size: 80px; color: #ddd;"></i>
                <h3 class="mt-3">Giỏ hàng trống</h3>
                <p class="text-muted">Hãy thêm sản phẩm vào giỏ hàng để tiếp tục mua sắm!</p>
                <a href="index.php" class="btn btn-primary mt-3">Tiếp tục mua sắm</a>
            </div>
        <?php else: ?>
            <div class="row">
                <div class="col-lg-8">
                    <?php foreach ($cart_items as $item): ?>
                        <div class="cart-item">
                            <div class="row align-items-center">
                                <div class="col-md-2">
                                    <img src="../images/products/<?= htmlspecialchars($item['image']) ?>" alt="<?= htmlspecialchars($item['product_name']) ?>">
                                </div>
                                <div class="col-md-4">
                                    <h5><?= htmlspecialchars($item['product_name']) ?></h5>
                                    <p class="text-muted mb-0">Còn lại: <?= $item['stock'] ?> sản phẩm</p>
                                </div>
                                <div class="col-md-2">
                                    <strong><?= number_format($item['price'], 0, ',', '.') ?> ₫</strong>
                                </div>
                                <div class="col-md-3">
                                    <div class="quantity-control">
                                        <button onclick="updateQuantity(<?= $item['cart_item_id'] ?>, -1, <?= $item['stock'] ?>)">-</button>
                                        <input type="number" value="<?= $item['quantity'] ?>" id="qty-<?= $item['cart_item_id'] ?>" readonly>
                                        <button onclick="updateQuantity(<?= $item['cart_item_id'] ?>, 1, <?= $item['stock'] ?>)">+</button>
                                    </div>
                                </div>
                                <div class="col-md-1 text-end">
                                    <button class="btn-remove" onclick="removeItem(<?= $item['cart_item_id'] ?>)">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="col-lg-4">
                    <div class="cart-summary">
                        <h4 class="mb-4">Tóm tắt đơn hàng</h4>
                        <div class="d-flex justify-content-between mb-3">
                            <span>Tạm tính:</span>
                            <strong><?= number_format($total, 0, ',', '.') ?> ₫</strong>
                        </div>
                        <div class="d-flex justify-content-between mb-3">
                            <span>Phí vận chuyển:</span>
                            <strong>Miễn phí</strong>
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between mb-4">
                            <h5>Tổng cộng:</h5>
                            <h5 class="text-danger"><?= number_format($total, 0, ',', '.') ?> ₫</h5>
                        </div>
                        <a href="checkout.php" class="btn btn-checkout">Tiến hành thanh toán</a>
                        <a href="index.php" class="btn btn-outline-secondary w-100 mt-2">Tiếp tục mua sắm</a>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script>
        function updateQuantity(cartItemId, change, stock) {
            const input = document.getElementById('qty-' + cartItemId);
            let newQty = parseInt(input.value) + change;

            if (newQty < 1) {
                if (confirm('Bạn có muốn xóa sản phẩm này khỏi giỏ hàng?')) {
                    removeItem(cartItemId);
                }
                return;
            }

            if (newQty > stock) {
                alert('Số lượng vượt quá tồn kho!');
                return;
            }

            fetch('update_cart.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `cart_item_id=${cartItemId}&quantity=${newQty}`
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert(data.message);
                }
            });
        }

        function removeItem(cartItemId) {
            if (!confirm('Bạn có chắc muốn xóa sản phẩm này?')) return;

            fetch('remove_from_cart.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `cart_item_id=${cartItemId}`
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert(data.message);
                }
            });
        }
    </script>
</body>
</html>
