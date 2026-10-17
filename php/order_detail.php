<?php
session_start();
include 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Kiểm tra xem có order_id trong GET không (từ manage_orders.php dùng ?id=)
if (isset($_GET['id'])) {
    $order_id = $_GET['id'];
} elseif (isset($_GET['order_id'])) {
    $order_id = $_GET['order_id'];
} else {
    header("Location: index.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$is_admin = ($_SESSION['role'] ?? '') === 'admin';

// Lấy thông tin đơn hàng
if ($is_admin) {
    // Admin có thể xem tất cả đơn hàng
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE order_id = ?");
    $stmt->execute([$order_id]);
} else {
    // User chỉ xem đơn hàng của mình
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE order_id = ? AND user_id = ?");
    $stmt->execute([$order_id, $user_id]);
}

$order = $stmt->fetch();

if (!$order) {
    if ($is_admin) {
        header("Location: manage_orders.php");
    } else {
        header("Location: my_orders.php");
    }
    exit;
}

// Lấy chi tiết sản phẩm
$stmt = $pdo->prepare("SELECT * FROM order_items WHERE order_id = ?");
$stmt->execute([$order_id]);
$order_items = $stmt->fetchAll();

$status_labels = [
    'pending' => '<span class="badge bg-warning">Chờ xác nhận</span>',
    'confirmed' => '<span class="badge bg-info">Đã xác nhận</span>',
    'shipping' => '<span class="badge bg-primary">Đang giao</span>',
    'completed' => '<span class="badge bg-success">Hoàn thành</span>',
    'cancelled' => '<span class="badge bg-danger">Đã hủy</span>'
];

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
    <title>Chi tiết đơn hàng #<?= str_pad($order_id, 6, '0', STR_PAD_LEFT) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: #f8f9fa; }
        .order-header { background: linear-gradient(135deg, #ff6da5ff, rgba(248, 155, 229, 1)); color: white; padding: 30px 0; }
        .order-section { background: white; border-radius: 10px; padding: 30px; margin-bottom: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .product-item { border-bottom: 1px solid #eee; padding: 15px 0; }
        .product-item:last-child { border-bottom: none; }
        .timeline { position: relative; padding-left: 30px; }
        .timeline-item { position: relative; padding-bottom: 20px; }
        .timeline-item::before { content: ''; position: absolute; left: -23px; top: 5px; width: 12px; height: 12px; border-radius: 50%; background: #e3245d; }
        .timeline-item::after { content: ''; position: absolute; left: -18px; top: 17px; width: 2px; height: 100%; background: #ddd; }
        .timeline-item:last-child::after { display: none; }
    </style>
</head>
<body>
    <div class="order-header">
        <div class="container">
            <h2><i class="fas fa-file-invoice"></i> Chi tiết đơn hàng #<?= str_pad($order_id, 6, '0', STR_PAD_LEFT) ?></h2>
        </div>
    </div>

    <div class="container my-5">
        <div class="row">
            <div class="col-lg-8">
                <div class="order-section">
                    <h4 class="mb-4"><i class="fas fa-box"></i> Sản phẩm đã đặt</h4>
                    
                    <?php foreach ($order_items as $item): ?>
                        <div class="product-item">
                            <div class="row align-items-center">
                                <div class="col-md-6">
                                    <strong><?= htmlspecialchars($item['product_name']) ?></strong>
                                    <div class="text-muted small">Số lượng: <?= $item['quantity'] ?></div>
                                </div>
                                <div class="col-md-3 text-center">
                                    <?= number_format($item['price'], 0, ',', '.') ?> ₫
                                </div>
                                <div class="col-md-3 text-end">
                                    <strong><?= number_format($item['subtotal'], 0, ',', '.') ?> ₫</strong>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <hr class="my-3">

                    <div class="d-flex justify-content-between">
                        <h5>Tổng cộng:</h5>
                        <h5 class="text-danger"><?= number_format($order['total_amount'], 0, ',', '.') ?> ₫</h5>
                    </div>
                </div>

                <div class="order-section">
                    <h4 class="mb-4"><i class="fas fa-truck"></i> Trạng thái đơn hàng</h4>
                    
                    <div class="timeline">
                        <div class="timeline-item">
                            <strong>Đơn hàng đã đặt</strong>
                            <div class="text-muted small"><?= date('d/m/Y H:i', strtotime($order['created_at'])) ?></div>
                        </div>
                        
                        <?php if (in_array($order['status'], ['confirmed', 'shipping', 'completed'])): ?>
                        <div class="timeline-item">
                            <strong>Đã xác nhận</strong>
                            <div class="text-muted small">Đơn hàng đã được xác nhận</div>
                        </div>
                        <?php endif; ?>

                        <?php if (in_array($order['status'], ['shipping', 'completed'])): ?>
                        <div class="timeline-item">
                            <strong>Đang giao hàng</strong>
                            <div class="text-muted small">Đơn hàng đang được vận chuyển</div>
                        </div>
                        <?php endif; ?>

                        <?php if ($order['status'] === 'completed'): ?>
                        <div class="timeline-item">
                            <strong>Đã giao hàng</strong>
                            <div class="text-muted small">Đơn hàng đã được giao thành công</div>
                        </div>
                        <?php endif; ?>

                        <?php if ($order['status'] === 'cancelled'): ?>
                        <div class="timeline-item">
                            <strong>Đã hủy</strong>
                            <div class="text-muted small">Đơn hàng đã bị hủy</div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="order-section">
                    <h4 class="mb-4"><i class="fas fa-info-circle"></i> Thông tin đơn hàng</h4>
                    
                    <div class="mb-3">
                        <strong>Trạng thái:</strong><br>
                        <?= $status_labels[$order['status']] ?? $order['status'] ?>
                    </div>

                    <div class="mb-3">
                        <strong>Người nhận:</strong><br>
                        <?= htmlspecialchars($order['customer_name']) ?>
                    </div>

                    <div class="mb-3">
                        <strong>Số điện thoại:</strong><br>
                        <?= htmlspecialchars($order['customer_phone']) ?>
                    </div>

                    <div class="mb-3">
                        <strong>Địa chỉ giao hàng:</strong><br>
                        <?= htmlspecialchars($order['customer_address']) ?>
                    </div>

                    <div class="mb-3">
                        <strong>Phương thức thanh toán:</strong><br>
                        <?= $payment_methods[$order['payment_method']] ?? $order['payment_method'] ?>
                    </div>

                    <div class="mb-3">
                        <strong>Ngày đặt:</strong><br>
                        <?= date('d/m/Y H:i', strtotime($order['created_at'])) ?>
                    </div>

                    <hr>

                    <?php if ($is_admin): ?>
                        <a href="manage_orders.php" class="btn btn-outline-secondary w-100 mb-2">
                            <i class="fas fa-arrow-left"></i> Quay lại quản lý
                        </a>
                        
                        <?php if ($order['status'] === 'pending'): ?>
                            <form method="POST" action="manage_orders.php" onsubmit="return confirm('Xác nhận đơn hàng này?');">
                                <input type="hidden" name="order_id" value="<?= $order['order_id'] ?>">
                                <input type="hidden" name="action" value="confirm">
                                <button type="submit" class="btn btn-success w-100 mb-2">
                                    <i class="fas fa-check-circle"></i> Xác nhận đơn hàng
                                </button>
                            </form>
                        <?php endif; ?>
                        
                        <?php if ($order['status'] === 'confirmed'): ?>
                            <form method="POST" action="manage_orders.php" onsubmit="return confirm('Đánh dấu đơn hàng đã hoàn thành?');">
                                <input type="hidden" name="order_id" value="<?= $order['order_id'] ?>">
                                <input type="hidden" name="action" value="complete">
                                <button type="submit" class="btn btn-primary w-100 mb-2">
                                    <i class="fas fa-check-double"></i> Hoàn thành
                                </button>
                            </form>
                        <?php endif; ?>
                        
                        <?php if ($order['status'] !== 'cancelled' && $order['status'] !== 'completed'): ?>
                            <form method="POST" action="manage_orders.php" onsubmit="return confirm('Bạn có chắc muốn HỦY đơn hàng này?');">
                                <input type="hidden" name="order_id" value="<?= $order['order_id'] ?>">
                                <input type="hidden" name="action" value="cancel">
                                <button type="submit" class="btn btn-danger w-100">
                                    <i class="fas fa-times-circle"></i> Hủy đơn hàng
                                </button>
                            </form>
                        <?php endif; ?>
                    <?php else: ?>
                        <a href="my_orders.php" class="btn btn-outline-secondary w-100">
                            <i class="fas fa-arrow-left"></i> Quay lại
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
