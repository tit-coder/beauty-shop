<?php
session_start();
if (isset($_GET['logout'])) { session_destroy(); header("Location: login.php"); exit; }
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') { header("Location: login.php"); exit; }
include 'config.php';

// XÓA + TRẢ LỜI (giữ nguyên như cũ)
if (isset($_GET['delete_review'])) {
    $id = (int)$_GET['delete_review'];
    try {
        $stmt = $pdo->prepare("SELECT images FROM reviews WHERE review_id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        if ($row && !empty($row['images'])) {
            $images = json_decode($row['images'], true);
            if (is_array($images)) {
                foreach ($images as $img) {
                    $file = 'uploads/reviews/' . basename($img);
                    if (file_exists($file)) @unlink($file);
                }
            }
        }
        $pdo->prepare("DELETE FROM reviews WHERE review_id = ?")->execute([$id]);
        $_SESSION['success'] = "Đã xóa đánh giá thành công!";
    } catch (Exception $e) { $_SESSION['error'] = "Lỗi khi xóa đánh giá."; }
    header("Location: manage_reviews.php"); exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'reply') {
    $review_id = (int)$_POST['review_id'];
    $reply = trim($_POST['reply'] ?? '');
    if ($review_id <= 0 || strlen($reply) < 1) {
        $_SESSION['error'] = "Vui lòng nhập nội dung trả lời!";
    } else {
        try {
            $stmt = $pdo->prepare("UPDATE reviews SET reply = ?, replied_at = NOW() WHERE review_id = ?");
            $stmt->execute([$reply, $review_id]);
            $_SESSION['success'] = "Đã trả lời đánh giá thành công!";
        } catch (Exception $e) { $_SESSION['error'] = "Lỗi database"; }
    }
    header("Location: manage_reviews.php"); exit;
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý Đánh giá - Beauty Cosmetics</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        :root { --admin-primary: #ff69b4; --admin-secondary: #ff1493; --admin-dark: #222; }
        body { background: linear-gradient(135deg, #fff5f8 0%, #ffe4f0 100%); font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .admin-header { background: linear-gradient(135deg, var(--admin-secondary), var(--admin-primary)); box-shadow: 0 4px 20px rgba(255,105,180,0.3); padding: 0; }
        .admin-header .navbar-brand { color: white !important; font-weight: 700; font-size: 1.4rem; padding: 1rem 0; }
        .admin-nav { background: white; box-shadow: 0 2px 10px rgba(0,0,0,0.08); padding: 0; }
        .admin-nav .nav-link { color: var(--admin-dark) !important; font-weight: 600; padding: 1rem 1.5rem !important; transition: all 0.3s ease; border-bottom: 3px solid transparent; }
        .admin-nav .nav-link:hover, .admin-nav .nav-link.active { color: var(--admin-primary) !important; border-bottom-color: var(--admin-primary); background: rgba(255,105,180,0.05); }
        .content-card { background: white; border-radius: 16px; padding: 2rem; box-shadow: 0 10px 30px rgba(255,105,180,0.15); overflow: hidden; }

        /* BẢNG ĐẸP - ĐỀU MÀU, CỘT CÂN ĐỐI, VIỀN NHẠT NHƯ ẢNH */
        .review-table-wrapper {
            border: 1px solid #e0e0e0;
            border-radius: 14px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
        }
        .review-table thead {
            background: linear-gradient(135deg, var(--admin-secondary), var(--admin-primary));
            color: white;
        }
        .review-table th,
        .review-table td {
            border: 1px solid #e0e0e0 !important;
            padding: 14px 12px !important;
            vertical-align: middle;
            font-size: 0.95rem;
        }
        .review-table tbody tr:hover {
            background-color: #fdf2f8 !important;
        }
        .review-table thead th {
    text-align: center !important;
    vertical-align: middle !important;
}

        /* CỘT CÂN ĐỐI, ĐỀU MÀU */
        .col-stt { width: 5%; text-align: center; }
        .col-img { width: 8%; text-align: center; }
        .col-product { width: 18%; }
        .col-customer { width: 12%; }
        .col-rating { width: 8%; text-align: center; }
        .col-comment { width: 18%; }
        .col-media { width: 10%; text-align: center; }
        .col-reply { width: 15%; }
        .col-action { width: 10%; text-align: center; }

        .product-thumb { width: 70px; height: 70px; object-fit: cover; border-radius: 12px; border: 3px solid white; box-shadow: 0 4px 12px rgba(0,0,0,0.2); }
        .user-avatar { width: 40px; height: 40px; background: var(--admin-primary); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 1.1rem; }
        .review-img { width: 58px; height: 58px; object-fit: cover; border-radius: 10px; margin: 3px; cursor: zoom-in; border: 2px solid #eee; }
        .reply-box { background: #fff8fb; border-left: 5px solid var(--admin-primary); padding: 12px; border-radius: 0 10px 10px 0; font-size: 0.94rem; line-height: 1.6; }
        .comment-cell { line-height: 1.7; }
        .rating-cell i { font-size: 1.3rem; }
        .btn-action { padding: 0.5rem 1rem; font-size: 0.9rem; border-radius: 50px; font-weight: 600; min-width: 80px; }
    </style>
</head>
<body>

<header class="admin-header">
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center py-2">
            <a class="navbar-brand" href="admin_dashboard.php">Admin Dashboard</a>
            <div class="d-flex align-items-center gap-3">
                <span class="text-white"><i class="bi bi-person-circle"></i> <strong><?= htmlspecialchars($_SESSION['username']) ?></strong></span>
                <a href="?logout=1" class="btn btn-outline-light btn-sm">Đăng xuất</a>
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
            <li class="nav-item"><a class="nav-link" href="manage_users.php"><i class="bi bi-people"></i> Quản lý người dùng</a></li>
            <li class="nav-item"><a class="nav-link active" href="manage_reviews.php"><i class="bi bi-star"></i> Quản lý đánh giá</a></li>
        </ul>
    </div>
</nav>
<div class="container-fluid py-4">
    <div class="content-card">
        <h2 style="color: var(--admin-primary); font-weight: 700; margin-bottom: 1.5rem; text-align:center;">
            Quản lý Đánh giá & Bình luận
        </h2>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <?= $_SESSION['success'] ?> <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <?= $_SESSION['error'] ?> <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <div class="table-responsive review-table-wrapper">
            <table class="table table-hover align-middle review-table mb-0">
                <thead>
                    <tr>
                        <th class="col-stt">STT</th>
                        <th class="col-img">Ảnh SP</th>
                        <th class="col-product">Sản phẩm</th>
                        <th class="col-customer">Khách hàng</th>
                        <th class="col-rating">Đánh giá</th>
                        <th class="col-comment">Bình luận</th>
                        <th class="col-media">Ảnh/Video</th>
                        <th class="col-reply">Phản hồi Shop</th>
                        <th class="col-action">Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    try {
                        $stmt = $pdo->prepare("
                            SELECT r.*, u.username, p.product_name, p.image AS product_image
                            FROM reviews r
                            LEFT JOIN users u ON r.user_id = u.id
                            LEFT JOIN products p ON r.product_id = p.product_id
                            ORDER BY r.review_id DESC
                        ");
                        $stmt->execute();
                        $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);

                        if (empty($reviews)): ?>
                            <tr>
                                <td colspan="9" class="text-center py-5 text-muted">
                                    <i class="bi bi-star display-1 d-block"></i>
                                    <p class="mt-3">Chưa có đánh giá nào</p>
                                </td>
                            </tr>
                        <?php else:
                            $stt = 1;
                            foreach ($reviews as $r): ?>
                                <tr>
                                    <td class="text-center"><strong><?= $stt++ ?></strong></td>

                                    <td class="text-center">
                                        <?php 
                                        $img_path = !empty($r['product_image']) 
                                            ? '../images/products/' . htmlspecialchars($r['product_image'])
                                            : 'https://via.placeholder.com/70?text=NO+IMG';
                                        ?>
                                        <img src="<?= $img_path ?>" class="product-thumb" alt="SP">
                                    </td>

                                    <td class="fw-bold"><?= htmlspecialchars($r['product_name'] ?? 'Sản phẩm đã xóa') ?></td>

                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="user-avatar"><?= strtoupper(substr($r['username'] ?? 'K', 0, 1)) ?></div>
                                            <div><?= htmlspecialchars($r['username'] ?? 'Khách vãng lai') ?></div>
                                        </div>
                                    </td>

                                    <td class="text-center">
                                        <?php for($i=1; $i<=5; $i++): ?>
                                            <i class="bi <?= $i<=$r['rating'] ? 'bi-star-fill text-warning' : 'bi-star' ?>"></i>
                                        <?php endfor; ?>
                                    </td>

                                    <td class="comment-cell"><?= nl2br(htmlspecialchars($r['comment'])) ?></td>

                                    <td class="text-center">
                                        <?php
                                        if (!empty($r['images'])) {
                                            $imgs = json_decode($r['images'], true) ?: [];
                                            if (count($imgs) > 0) {
                                                foreach (array_slice($imgs, 0, 3) as $img):
                                                    $path = 'uploads/reviews/' . basename($img); ?>
                                                    <img src="<?= $path ?>" class="review-img" onclick="window.open(this.src)" alt="Review">
                                                <?php endforeach;
                                                if (count($imgs) > 3) echo '<small class="text-muted">+' . (count($imgs)-3) . '</small>';
                                            } else echo '<small class="text-muted">Không có</small>';
                                        } else echo '<small class="text-muted">Không có</small>';
                                        ?>
                                    </td>

                                    <td>
                                        <?php if (!empty($r['reply'])): ?>
                                            <div class="reply-box">
                                                <strong style="color:#ff1493;">Shop:</strong><br>
                                                <?= nl2br(htmlspecialchars($r['reply'])) ?>
                                                <small class="d-block text-muted mt-2">
                                                    <?= date('d/m/Y H:i', strtotime($r['replied_at'])) ?>
                                                </small>
                                            </div>
                                        <?php else: ?>
                                            <i class="text-muted fst-italic">Chưa trả lời</i>
                                        <?php endif; ?>
                                    </td>

                                    <td class="text-center">
                                        <div class="d-flex gap-2 justify-content-center">
                                            <button class="btn btn-primary btn-action" data-bs-toggle="modal" data-bs-target="#replyModal<?= $r['review_id'] ?>">Trả lời</button>
                                            <a href="?delete_review=<?= $r['review_id'] ?>" class="btn btn-danger btn-action"
                                               onclick="return confirm('Xóa đánh giá này và toàn bộ ảnh/video đi kèm?')">Xóa</a>
                                        </div>
                                    </td>
                                </tr>

                                <!-- Modal Trả lời -->
                                <div class="modal fade" id="replyModal<?= $r['review_id'] ?>">
                                    <div class="modal-dialog modal-lg">
                                        <form method="POST">
                                            <input type="hidden" name="action" value="reply">
                                            <input type="hidden" name="review_id" value="<?= $r['review_id'] ?>">
                                            <div class="modal-content">
                                                <div class="modal-header" style="background:var(--admin-primary);color:white;">
                                                    <h5 class="modal-title">Trả lời đánh giá #<?= $r['review_id'] ?></h5>
                                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <p><strong>Khách:</strong> <?= htmlspecialchars($r['username'] ?? 'Khách') ?></p>
                                                    <p><strong>Sản phẩm:</strong> <?= htmlspecialchars($r['product_name'] ?? '') ?></p>
                                                    <p><strong>Bình luận:</strong><br><?= nl2br(htmlspecialchars($r['comment'])) ?></p>
                                                    <hr>
                                                    <textarea name="reply" class="form-control" rows="6" required placeholder="Nhập nội dung trả lời..." maxlength="1000"><?= htmlspecialchars($r['reply'] ?? '') ?></textarea>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                                                    <button type="submit" class="btn btn-success">Gửi trả lời</button>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            <?php endforeach; endif; ?>
                    <?php } catch(Exception $e) { ?>
                        <tr><td colspan="9" class="text-danger text-center">Lỗi: <?= htmlspecialchars($e->getMessage()) ?></td></tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>