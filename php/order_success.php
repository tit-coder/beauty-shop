<?php
session_start();
include 'config.php';

if (!isset($_SESSION['user_id']) || !isset($_GET['order_id'])) {
    header("Location: index.php");
    exit;
}

$order_id = $_GET['order_id'];
$user_id = $_SESSION['user_id'];

// Lấy thông tin đơn hàng
$stmt = $pdo->prepare("SELECT * FROM orders WHERE order_id = ? AND user_id = ?");
$stmt->execute([$order_id, $user_id]);
$order = $stmt->fetch();

if (!$order) {
    header("Location: index.php");
    exit;
}

// Lấy chi tiết đơn hàng
$stmt = $pdo->prepare("SELECT * FROM order_items WHERE order_id = ?");
$stmt->execute([$order_id]);
$order_items = $stmt->fetchAll();

$payment_methods = [
    'cod' => 'Thanh toán khi nhận hàng (COD)',
    'bank_transfer' => 'Chuyển khoản ngân hàng',
    'momo' => 'Ví MoMo',
    'vnpay' => 'VNPay',
    'cash' => 'Tiền mặt'
];
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đặt hàng thành công - Beauty Cosmetics</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: #f8f9fa; }
        .success-container { max-width: 800px; margin: 50px auto; }
        .success-card { background: white; border-radius: 15px; padding: 40px; box-shadow: 0 5px 20px rgba(0,0,0,0.1); text-align: center; }
        .success-icon { font-size: 80px; color: #28a745; margin-bottom: 20px; }
        .order-info { background: #f8f9fa; border-radius: 10px; padding: 20px; margin: 30px 0; text-align: left; }
        .product-item { border-bottom: 1px solid #ddd; padding: 15px 0; }
        .product-item:last-child { border-bottom: none; }
        .btn-home { background: #e3245d; color: white; padding: 12px 40px; border: none; border-radius: 8px; font-weight: bold; }
        .btn-home:hover { background: #ff6da5ff; color: white; }
    </style>
</head>
<body>
    <div class="success-container">
        <div class="success-card">
            <i class="fas fa-check-circle success-icon"></i>
            <h2 class="text-success mb-3">Đặt hàng thành công!</h2>
            <p class="text-muted">Cảm ơn bạn đã mua hàng tại Beauty Cosmetics</p>
            <p class="mb-4">Mã đơn hàng: <strong class="text-primary">#<?= str_pad($order_id, 6, '0', STR_PAD_LEFT) ?></strong></p>

            <div class="order-info">
                <h5 class="mb-3"><i class="fas fa-info-circle"></i> Thông tin đơn hàng</h5>
                
                <div class="row mb-2">
                    <div class="col-6"><strong>Người nhận:</strong></div>
                    <div class="col-6 text-end"><?= htmlspecialchars($order['customer_name']) ?></div>
                </div>

                <div class="row mb-2">
                    <div class="col-6"><strong>Số điện thoại:</strong></div>
                    <div class="col-6 text-end"><?= htmlspecialchars($order['customer_phone']) ?></div>
                </div>

                <div class="row mb-2">
                    <div class="col-6"><strong>Địa chỉ:</strong></div>
                    <div class="col-6 text-end"><?= htmlspecialchars($order['customer_address']) ?></div>
                </div>

                <div class="row mb-2">
                    <div class="col-6"><strong>Phương thức thanh toán:</strong></div>
                    <div class="col-6 text-end"><?= $payment_methods[$order['payment_method']] ?? $order['payment_method'] ?></div>
                </div>

                <hr class="my-3">

                <h6 class="mb-3">Sản phẩm đã đặt:</h6>
                <?php foreach ($order_items as $item): ?>
                    <div class="product-item">
                        <div class="d-flex justify-content-between">
                            <div>
                                <div><?= htmlspecialchars($item['product_name']) ?></div>
                                <small class="text-muted">Số lượng: <?= $item['quantity'] ?> × <?= number_format($item['price'], 0, ',', '.') ?> ₫</small>
                            </div>
                            <strong><?= number_format($item['subtotal'], 0, ',', '.') ?> ₫</strong>
                        </div>
                    </div>
                <?php endforeach; ?>

                <hr class="my-3">

                <div class="d-flex justify-content-between">
                    <h5>Tổng cộng:</h5>
                    <h5 class="text-danger"><?= number_format($order['total_amount'], 0, ',', '.') ?> ₫</h5>
                </div>
            </div>

            <div class="alert alert-info mt-4">
                <i class="fas fa-info-circle"></i> 
                Đơn hàng của bạn đang được xử lý. Chúng tôi sẽ liên hệ với bạn sớm nhất!
            </div>

            <div class="mt-4">
                <a href="index.php" class="btn btn-home me-2">
                    <i class="fas fa-home"></i> Về trang chủ
                </a>
                <a href="my_orders.php" class="btn btn-outline-primary">
                    <i class="fas fa-list"></i> Xem đơn hàng của tôi
                </a>
            </div>
        </div>
    </div>
</body>
</html>
