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

    try {
        $stmt = $pdo->prepare("DELETE FROM cart_items WHERE cart_item_id = ?");
        $stmt->execute([$cart_item_id]);

        echo json_encode(['success' => true, 'message' => 'Đã xóa sản phẩm khỏi giỏ hàng!']);

    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()]);
    }
}
