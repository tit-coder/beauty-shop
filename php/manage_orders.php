<?php
session_start();

// Xử lý đăng xuất
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: login.php");
    exit;
}

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

include 'config.php';

// Xử lý cập nhật trạng thái đơn hàng
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && isset($_POST['order_id'])) {
        $order_id = (int)$_POST['order_id'];
        $action = $_POST['action'];
        
        try {
            if ($action === 'confirm') {
                // Xác nhận đơn hàng
                $stmt = $pdo->prepare("UPDATE orders SET status = 'confirmed' WHERE order_id = ?");
                $stmt->execute([$order_id]);
                $message = "Đã xác nhận đơn hàng #$order_id thành công!";
                $messageType = 'success';
            } elseif ($action === 'cancel') {
                // Hủy đơn hàng
                $stmt = $pdo->prepare("UPDATE orders SET status = 'cancelled' WHERE order_id = ?");
                $stmt->execute([$order_id]);
                $message = "Đã hủy đơn hàng #$order_id!";
                $messageType = 'warning';
            } elseif ($action === 'complete') {
                // Hoàn thành đơn hàng
                $stmt = $pdo->prepare("UPDATE orders SET status = 'completed' WHERE order_id = ?");
                $stmt->execute([$order_id]);
                $message = "Đơn hàng #$order_id đã hoàn thành!";
                $messageType = 'success';
            }
        } catch (Exception $e) {
            $message = "Lỗi: " . $e->getMessage();
            $messageType = 'danger';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý Đơn hàng - Beauty Cosmetics</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        :root {
            --admin-primary: #ff69b4;
            --admin-secondary: #ff1493;
            --admin-dark: #222;
        }
        body { background: linear-gradient(135deg, #fff5f8 0%, #ffe4f0 100%); font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .admin-header { background: linear-gradient(135deg, var(--admin-secondary), var(--admin-primary)); box-shadow: 0 4px 20px rgba(255,105,180,0.3); padding: 0; }
        .admin-header .navbar-brand { color: white !important; font-weight: 700; font-size: 1.4rem; padding: 1rem 0; }
        .admin-header .navbar-brand i { font-size: 1.6rem; margin-right: 0.5rem; }
        .admin-nav { background: white; box-shadow: 0 2px 10px rgba(0,0,0,0.08); padding: 0; }
        .admin-nav .nav-link { color: var(--admin-dark) !important; font-weight: 600; padding: 1rem 1.5rem !important; transition: all 0.3s ease; border-bottom: 3px solid transparent; }
        .admin-nav .nav-link:hover, .admin-nav .nav-link.active { color: var(--admin-primary) !important; border-bottom-color: var(--admin-primary); background: rgba(255,105,180,0.05); }
        .admin-nav .nav-link i { margin-right: 0.5rem; font-size: 1.1rem; }
        .content-card { background: white; border-radius: 12px; padding: 2rem; box-shadow: 0 4px 15px rgba(0,0,0,0.08); }
    </style>
</head>
<body>

<header class="admin-header">
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center py-2">
            <a class="navbar-brand" href="admin_dashboard.php"><i class="bi bi-speedometer2"></i> Admin Dashboard</a>
            <div class="d-flex align-items-center gap-3">
                <span class="text-white"><i class="bi bi-person-circle"></i> <strong><?= htmlspecialchars($_SESSION['username']) ?></strong></span>
                <a href="?logout=1" class="btn btn-outline-light btn-sm"><i class="bi bi-box-arrow-right"></i> Đăng xuất</a>
            </div>
        </div>
    </div>
</header>

<nav class="admin-nav">
    <div class="container-fluid">
        <ul class="nav nav-pills">
            <li class="nav-item"><a class="nav-link" href="admin_dashboard.php"><i class="bi bi-speedometer2"></i> Tổng quan</a></li>
            <li class="nav-item"><a class="nav-link" href="manage_products.php"><i class="bi bi-box-seam"></i> Quản lý sản phẩm</a></li>
            <li class="nav-item"><a class="nav-link active" href="manage_orders.php"><i class="bi bi-cart-check"></i> Quản lý đơn hàng</a></li>
            <li class="nav-item"><a class="nav-link" href="manage_users.php"><i class="bi bi-people"></i> Quản lý người dùng</a></li>
            <li class="nav-item"><a class="nav-link" href="manage_reviews.php"><i class="bi bi-star"></i> Quản lý đánh giá</a></li>
        </ul>
    </div>
</nav>

<div class="container-fluid py-4">
    <div class="content-card">
        <h2 style="color: var(--admin-primary); font-weight: 700;"><i class="bi bi-cart-check"></i> Quản lý Đơn hàng</h2>
        <hr>
        
        <?php if ($message): ?>
        <div class="alert alert-<?= $messageType ?> alert-dismissible fade show" role="alert">
            <i class="bi bi-<?= $messageType === 'success' ? 'check-circle' : 'exclamation-triangle' ?>"></i> <?= $message ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>
        
        <?php
        try {
            $stmt = $pdo->query("SELECT * FROM orders ORDER BY order_id DESC");
            ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead style="background: linear-gradient(135deg, #ff69b4, #ff1493); color: white;">
                        <tr>
                            <th>Mã đơn</th>
                            <th>Khách hàng</th>
                            <th>Số điện thoại</th>
                            <th>Địa chỉ</th>
                            <th>Tổng tiền</th>
                            <th>Trạng thái</th>
                            <th>Ngày đặt</th>
                            <th>Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($order = $stmt->fetch()): 
                            // Xác định màu badge theo trạng thái
                            $statusBadge = [
                                'pending' => 'bg-warning',
                                'confirmed' => 'bg-info',
                                'completed' => 'bg-success',
                                'cancelled' => 'bg-danger'
                            ];
                            $statusText = [
                                'pending' => 'Chờ xác nhận',
                                'confirmed' => 'Đã xác nhận',
                                'completed' => 'Hoàn thành',
                                'cancelled' => 'Đã hủy'
                            ];
                            $currentStatus = $order['status'] ?? 'pending';
                        ?>
                        <tr>
                            <td><strong>#<?= $order['order_id'] ?></strong></td>
                            <td><?= htmlspecialchars($order['customer_name']) ?></td>
                            <td><small><?= htmlspecialchars($order['customer_phone']) ?></small></td>
                            <td><small><?= htmlspecialchars($order['customer_address']) ?></small></td>
                            <td><strong style="color: #ff1493;"><?= number_format($order['total_amount']) ?> ₫</strong></td>
                            <td>
                                <span class="badge <?= $statusBadge[$currentStatus] ?? 'bg-secondary' ?>">
                                    <?= $statusText[$currentStatus] ?? $currentStatus ?>
                                </span>
                            </td>
                            <td><small><?= date('d/m/Y H:i', strtotime($order['created_at'])) ?></small></td>
                            <td>
                                <div class="btn-group" role="group">
                                    <a href="order_detail.php?id=<?= $order['order_id'] ?>" class="btn btn-sm btn-info" title="Xem chi tiết">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    
                                    <?php if ($currentStatus === 'pending'): ?>
                                        <form method="POST" style="display: inline;" onsubmit="return confirm('Xác nhận đơn hàng này?');">
                                            <input type="hidden" name="order_id" value="<?= $order['order_id'] ?>">
                                            <input type="hidden" name="action" value="confirm">
                                            <button type="submit" class="btn btn-sm btn-success" title="Xác nhận">
                                                <i class="bi bi-check-circle"></i>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                    
                                    <?php if ($currentStatus === 'confirmed'): ?>
                                        <form method="POST" style="display: inline;" onsubmit="return confirm('Đánh dấu đơn hàng đã hoàn thành?');">
                                            <input type="hidden" name="order_id" value="<?= $order['order_id'] ?>">
                                            <input type="hidden" name="action" value="complete">
                                            <button type="submit" class="btn btn-sm btn-primary" title="Hoàn thành">
                                                <i class="bi bi-check-all"></i>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                    
                                    <?php if ($currentStatus !== 'cancelled' && $currentStatus !== 'completed'): ?>
                                        <form method="POST" style="display: inline;" onsubmit="return confirm('Bạn có chắc muốn HỦY đơn hàng này?');">
                                            <input type="hidden" name="order_id" value="<?= $order['order_id'] ?>">
                                            <input type="hidden" name="action" value="cancel">
                                            <button type="submit" class="btn btn-sm btn-danger" title="Hủy đơn">
                                                <i class="bi bi-x-circle"></i>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            <?php
        } catch(Exception $e) {
            echo '<div class="alert alert-warning"><i class="bi bi-exclamation-triangle"></i> Có lỗi xảy ra: ' . htmlspecialchars($e->getMessage()) . '</div>';
        }
        ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
