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
$quantity = (int)($_POST['quantity'] ?? 1);

if ($cart_item_id <= 0 || $quantity < 1) {
    echo json_encode(['success' => false, 'message' => 'Dữ liệu không hợp lệ!']);
    exit;
}

try {
    // 1. Kiểm tra quyền sở hữu cart_item và kiểm tra tồn kho
    $stmt = $pdo->prepare("
        SELECT ci.cart_item_id, ci.cart_id, p.price, p.quantity as stock, p.product_name
        FROM cart_items ci
        JOIN cart c ON ci.cart_id = c.cart_id
        JOIN products p ON ci.product_id = p.product_id
        WHERE ci.cart_item_id = ? AND c.user_id = ?
    ");
    $stmt->execute([$cart_item_id, $user_id]);
    $item = $stmt->fetch();

    if (!$item) {
        echo json_encode(['success' => false, 'message' => 'Sản phẩm không tồn tại trong giỏ hàng!']);
        exit;
    }

    if ($quantity > $item['stock']) {
        echo json_encode([
            'success' => false, 
            'message' => "Sản phẩm '{$item['product_name']}' chỉ còn {$item['stock']} sản phẩm trong kho!"
        ]);
        exit;
    }

    // 2. Cập nhật số lượng
    $upStmt = $pdo->prepare("UPDATE cart_items SET quantity = ? WHERE cart_item_id = ?");
    $upStmt->execute([$quantity, $cart_item_id]);

    // 3. Tính lại tổng tiền
    $totStmt = $pdo->prepare("
        SELECT SUM(ci.quantity * p.price) as cart_total, SUM(ci.quantity) as cart_count
        FROM cart_items ci
        JOIN cart c ON ci.cart_id = c.cart_id
        JOIN products p ON ci.product_id = p.product_id
        WHERE c.user_id = ?
    ");
    $totStmt->execute([$user_id]);
    $res = $totStmt->fetch();

    $cart_total = (float)$res['cart_total'];
    $cart_count = (int)$res['cart_count'];
    $item_subtotal = (float)($item['price'] * $quantity);

    echo json_encode([
        'success' => true,
        'message' => 'Đã cập nhật giỏ hàng!',
        'item_subtotal' => $item_subtotal,
        'item_subtotal_formatted' => format_price($item_subtotal),
        'cart_total' => $cart_total,
        'cart_total_formatted' => format_price($cart_total),
        'cart_count' => $cart_count
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()]);
}
