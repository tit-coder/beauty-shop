<?php
session_start();
include 'config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập!']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cart_item_id = $_POST['cart_item_id'] ?? 0;
    $quantity = $_POST['quantity'] ?? 1;

    try {
        // Kiểm tra số lượng tồn kho
        $stmt = $pdo->prepare("
            SELECT p.quantity as stock 
            FROM cart_items ci
            JOIN products p ON ci.product_id = p.product_id
            WHERE ci.cart_item_id = ?
        ");
        $stmt->execute([$cart_item_id]);
        $item = $stmt->fetch();

        if (!$item) {
            echo json_encode(['success' => false, 'message' => 'Sản phẩm không tồn tại!']);
            exit;
        }

        if ($quantity > $item['stock']) {
            echo json_encode(['success' => false, 'message' => 'Số lượng vượt quá tồn kho!']);
            exit;
        }

        // Cập nhật số lượng
        $stmt = $pdo->prepare("UPDATE cart_items SET quantity = ? WHERE cart_item_id = ?");
        $stmt->execute([$quantity, $cart_item_id]);

        echo json_encode(['success' => true, 'message' => 'Đã cập nhật giỏ hàng!']);

    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()]);
    }
}
