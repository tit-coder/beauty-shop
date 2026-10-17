<?php
session_start();
include 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Lấy danh sách đơn hàng
$stmt = $pdo->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$user_id]);
$orders = $stmt->fetchAll();

$status_labels = [
    'pending' => '<span class="badge bg-warning">Chờ xác nhận</span>',
    'confirmed' => '<span class="badge bg-info">Đã xác nhận</span>',
    'shipping' => '<span class="badge bg-primary">Đang giao</span>',
    'completed' => '<span class="badge bg-success">Hoàn thành</span>',
    'cancelled' => '<span class="badge bg-danger">Đã hủy</span>'
];
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đơn hàng của tôi - Beauty Cosmetics</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: #f8f9fa; }
        .orders-header { background: linear-gradient(135deg, #ff6da5ff, rgba(248, 155, 229, 1)); color: white; padding: 30px 0; }
        .order-card { background: white; border-radius: 10px; padding: 20px; margin-bottom: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .order-header { border-bottom: 2px solid #f0f0f0; padding-bottom: 15px; margin-bottom: 15px; }
        .btn-detail { background: #e3245d; color: white; border: none; padding: 8px 20px; border-radius: 5px; }
        .btn-detail:hover { background: #ff6da5ff; color: white; }
    </style>
</head>
<body>
    <div class="orders-header">
        <div class="container">
            <h2><i class="fas fa-box"></i> Đơn hàng của tôi</h2>
        </div>
    </div>

    <div class="container my-5">
        <?php if (empty($orders)): ?>
            <div class="text-center py-5">
                <i class="fas fa-box-open" style="font-size: 80px; color: #ddd;"></i>
                <h3 class="mt-3">Chưa có đơn hàng nào</h3>
                <p class="text-muted">Hãy mua sắm ngay để trải nghiệm dịch vụ của chúng tôi!</p>
                <a href="index.php" class="btn btn-primary mt-3">Mua sắm ngay</a>
            </div>
        <?php else: ?>
            <?php foreach ($orders as $order): ?>
                <div class="order-card">
                    <div class="order-header">
                        <div class="row align-items-center">
                            <div class="col-md-3">
                                <strong>Mã đơn: #<?= str_pad($order['order_id'], 6, '0', STR_PAD_LEFT) ?></strong>
                            </div>
                            <div class="col-md-3">
                                <small class="text-muted">
                                    <i class="far fa-calendar"></i> 
                                    <?= date('d/m/Y H:i', strtotime($order['created_at'])) ?>
                                </small>
                            </div>
                            <div class="col-md-3">
                                <?= $status_labels[$order['status']] ?? $order['status'] ?>
                            </div>
                            <div class="col-md-3 text-end">
                                <strong class="text-danger"><?= number_format($order['total_amount'], 0, ',', '.') ?> ₫</strong>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <p class="mb-1"><strong>Người nhận:</strong> <?= htmlspecialchars($order['customer_name']) ?></p>
                            <p class="mb-1"><strong>SĐT:</strong> <?= htmlspecialchars($order['customer_phone']) ?></p>
                        </div>
                        <div class="col-md-6 text-end">
                            <a href="order_detail.php?order_id=<?= $order['order_id'] ?>" class="btn btn-detail">
                                <i class="fas fa-eye"></i> Xem chi tiết
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <div class="text-center mt-4">
            <a href="index.php" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left"></i> Về trang chủ
            </a>
        </div>
    </div>
</body>
</html>
