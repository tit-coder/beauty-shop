<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/config.php';

if (!is_logged_in()) {
    echo json_encode(['success' => false, 'require_login' => true, 'message' => 'Vui lòng đăng nhập để lưu sản phẩm yêu thích!']);
    exit;
}

$user_id = $_SESSION['user_id'];
$raw = file_get_contents('php://input');
$data = json_decode($raw, true) ?: $_POST;
$product_id = isset($data['product_id']) ? (int)$data['product_id'] : 0;

if ($product_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Sản phẩm không hợp lệ']);
    exit;
}

try {
    // Kiểm tra xem đã có trong wishlist chưa
    $check = $pdo->prepare("SELECT id FROM wishlists WHERE user_id = ? AND product_id = ?");
    $check->execute([$user_id, $product_id]);
    $item = $check->fetch();

    if ($item) {
        // Xóa khỏi wishlist
        $del = $pdo->prepare("DELETE FROM wishlists WHERE user_id = ? AND product_id = ?");
        $del->execute([$user_id, $product_id]);
        $action = 'removed';
    } else {
        // Thêm vào wishlist
        $ins = $pdo->prepare("INSERT INTO wishlists (user_id, product_id, created_at) VALUES (?, ?, NOW())");
        $ins->execute([$user_id, $product_id]);
        $action = 'added';
    }

    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM wishlists WHERE user_id = ?");
    $countStmt->execute([$user_id]);
    $count = (int)$countStmt->fetchColumn();

    echo json_encode([
        'success' => true,
        'action' => $action,
        'count' => $count
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()]);
}

