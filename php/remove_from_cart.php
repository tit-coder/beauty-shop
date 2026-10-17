<?php
session_start();
require_once __DIR__ . '/config.php';

header('Content-Type: application/json; charset=utf-8');

if (!is_logged_in()) {
    echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập!']);
    exit;
}

$user_id = $_SESSION['user_id'];
$cart_item_id = (int)($_POST['cart_item_id'] ?? 0);

if ($cart_item_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Dữ liệu không hợp lệ!']);
    exit;
}

try {
    // 1. Kiểm tra quyền sở hữu
    $stmt = $pdo->prepare("
        SELECT ci.cart_item_id 
        FROM cart_items ci
        JOIN cart c ON ci.cart_id = c.cart_id
        WHERE ci.cart_item_id = ? AND c.user_id = ?
    ");
    $stmt->execute([$cart_item_id, $user_id]);
    if (!$stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Sản phẩm không thuộc giỏ hàng của bạn!']);
        exit;
    }

    // 2. Xóa
    $del = $pdo->prepare("DELETE FROM cart_items WHERE cart_item_id = ?");
    $del->execute([$cart_item_id]);

    // 3. Tính lại tổng
    $totStmt = $pdo->prepare("
        SELECT COALESCE(SUM(ci.quantity * p.price), 0) as cart_total, COALESCE(SUM(ci.quantity), 0) as cart_count
        FROM cart_items ci
        JOIN cart c ON ci.cart_id = c.cart_id
        JOIN products p ON ci.product_id = p.product_id
        WHERE c.user_id = ?
    ");
    $totStmt->execute([$user_id]);
    $res = $totStmt->fetch();

    $cart_total = (float)$res['cart_total'];
    $cart_count = (int)$res['cart_count'];

    echo json_encode([
        'success' => true,
        'message' => 'Đã xóa sản phẩm khỏi giỏ hàng!',
        'cart_total' => $cart_total,
        'cart_total_formatted' => format_price($cart_total),
        'cart_count' => $cart_count
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()]);
}
