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

// Xử lý xóa người dùng
$message = '';
$messageType = '';

if (isset($_GET['delete']) && isset($_GET['id'])) {
    $user_id_to_delete = (int)$_GET['id'];
    
    // Không cho phép xóa chính mình
    if ($user_id_to_delete === $_SESSION['user_id']) {
        $message = "Bạn không thể xóa tài khoản của chính mình!";
        $messageType = 'danger';
    } else {
        try {
            // Kiểm tra xem user có tồn tại không
            $stmt = $pdo->prepare("SELECT username, role FROM users WHERE id = ?");
            $stmt->execute([$user_id_to_delete]);
            $user_to_delete = $stmt->fetch();
            
            if ($user_to_delete) {
                // Xóa người dùng
                $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
                $stmt->execute([$user_id_to_delete]);
                
                $message = "Đã xóa người dùng '" . htmlspecialchars($user_to_delete['username']) . "' thành công!";
                $messageType = 'success';
            } else {
                $message = "Người dùng không tồn tại!";
                $messageType = 'warning';
            }
        } catch (Exception $e) {
            $message = "Lỗi khi xóa người dùng: " . $e->getMessage();
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
    <title>Quản lý Người dùng - Beauty Cosmetics</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        :root { --admin-primary: #ff69b4; --admin-secondary: #ff1493; --admin-dark: #222; }
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
            <li class="nav-item"><a class="nav-link" href="manage_orders.php"><i class="bi bi-cart-check"></i> Quản lý đơn hàng</a></li>
            <li class="nav-item"><a class="nav-link active" href="manage_users.php"><i class="bi bi-people"></i> Quản lý người dùng</a></li>
            <li class="nav-item"><a class="nav-link" href="manage_reviews.php"><i class="bi bi-star"></i> Quản lý đánh giá</a></li>
        </ul>
    </div>
</nav>

<div class="container-fluid py-4">
    <div class="content-card">
        <h2 style="color: var(--admin-primary); font-weight: 700;"><i class="bi bi-people"></i> Quản lý Người dùng</h2>
        <hr>
        
        <?php if ($message): ?>
        <div class="alert alert-<?= $messageType ?> alert-dismissible fade show" role="alert">
            <i class="bi bi-<?= $messageType === 'success' ? 'check-circle' : 'exclamation-triangle' ?>"></i> <?= $message ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>
        
        <?php
        // Đếm số lượng người dùng
        $total_users = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
        $total_admins = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn();
        $total_regular_users = $total_users - $total_admins;
        ?>
        
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card border-primary">
                    <div class="card-body text-center">
                        <i class="bi bi-people-fill fs-1 text-primary"></i>
                        <h3 class="mt-2"><?= $total_users ?></h3>
                        <p class="text-muted mb-0">Tổng người dùng</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-danger">
                    <div class="card-body text-center">
                        <i class="bi bi-shield-fill-check fs-1 text-danger"></i>
                        <h3 class="mt-2"><?= $total_admins ?></h3>
                        <p class="text-muted mb-0">Quản trị viên</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-success">
                    <div class="card-body text-center">
                        <i class="bi bi-person-fill fs-1 text-success"></i>
                        <h3 class="mt-2"><?= $total_regular_users ?></h3>
                        <p class="text-muted mb-0">Người dùng thường</p>
                    </div>
                </div>
            </div>
        </div>
        
        <?php
        try {
            $stmt = $pdo->query("SELECT * FROM users ORDER BY id DESC");
        ?>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead style="background: linear-gradient(135deg, #ff69b4, #ff1493); color: white;">
                    <tr>
                        <th>ID</th>
                        <th>Tên đăng nhập</th>
                        <th>Vai trò</th>
                        <th>Ngày tạo</th>
                        <th>Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($user = $stmt->fetch()): ?>
                    <tr>
                        <td><?= $user['id'] ?></td>
                        <td><strong><?= htmlspecialchars($user['username']) ?></strong></td>
                        <td>
                            <?php if($user['role'] === 'admin'): ?>
                                <span class="badge bg-danger">Admin</span>
                            <?php else: ?>
                                <span class="badge bg-primary">User</span>
                            <?php endif; ?>
                        </td>
                        <td><?= $user['created_at'] ?? 'N/A' ?></td>
                        <td>
                            <?php if($user['id'] != $_SESSION['user_id']): ?>
                                <a href="?delete=1&id=<?= $user['id'] ?>" 
                                   class="btn btn-sm btn-danger" 
                                   onclick="return confirm('Bạn có chắc muốn xóa người dùng \'<?= htmlspecialchars($user['username']) ?>\'?\n\nLưu ý: Hành động này không thể hoàn tác!')">
                                    <i class="bi bi-trash"></i> Xóa
                                </a>
                            <?php else: ?>
                                <span class="badge bg-info"><i class="bi bi-person-check"></i> Tài khoản của bạn</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        <?php
        } catch (Exception $e) {
            echo '<div class="alert alert-danger"><i class="bi bi-exclamation-triangle"></i> Lỗi: ' . htmlspecialchars($e->getMessage()) . '</div>';
        }
        ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
