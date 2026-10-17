<?php
session_start();
include 'config.php';

header('Content-Type: application/json');

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập để thêm vào giỏ hàng!']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $product_id = $_POST['product_id'] ?? 0;
    $quantity = $_POST['quantity'] ?? 1;

    try {
        // Kiểm tra sản phẩm tồn tại và số lượng trong kho
        $stmt = $pdo->prepare("SELECT product_name, quantity, price FROM products WHERE product_id = ?");
        $stmt->execute([$product_id]);
        $product = $stmt->fetch();

        if (!$product) {
            echo json_encode(['success' => false, 'message' => 'Sản phẩm không tồn tại!']);
            exit;
        }

        if ($product['quantity'] < $quantity) {
            echo json_encode(['success' => false, 'message' => 'Số lượng trong kho không đủ! Còn lại: ' . $product['quantity']]);
            exit;
        }

        // Tìm hoặc tạo giỏ hàng cho user
        $stmt = $pdo->prepare("SELECT cart_id FROM cart WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $cart = $stmt->fetch();

        if (!$cart) {
            // Tạo giỏ hàng mới
            $stmt = $pdo->prepare("INSERT INTO cart (user_id) VALUES (?)");
            $stmt->execute([$user_id]);
            $cart_id = $pdo->lastInsertId();
        } else {
            $cart_id = $cart['cart_id'];
        }

        // Kiểm tra sản phẩm đã có trong giỏ chưa
        $stmt = $pdo->prepare("SELECT cart_item_id, quantity FROM cart_items WHERE cart_id = ? AND product_id = ?");
        $stmt->execute([$cart_id, $product_id]);
        $cart_item = $stmt->fetch();

        if ($cart_item) {
            // Cập nhật số lượng
            $new_quantity = $cart_item['quantity'] + $quantity;
            
            if ($new_quantity > $product['quantity']) {
                echo json_encode(['success' => false, 'message' => 'Số lượng vượt quá tồn kho!']);
                exit;
            }

            $stmt = $pdo->prepare("UPDATE cart_items SET quantity = ? WHERE cart_item_id = ?");
            $stmt->execute([$new_quantity, $cart_item['cart_item_id']]);
            $message = 'Đã cập nhật số lượng trong giỏ hàng!';
        } else {
            // Thêm sản phẩm mới vào giỏ
            $stmt = $pdo->prepare("INSERT INTO cart_items (cart_id, product_id, quantity) VALUES (?, ?, ?)");
            $stmt->execute([$cart_id, $product_id, $quantity]);
            $message = 'Đã thêm vào giỏ hàng!';
        }

        // Đếm số lượng sản phẩm trong giỏ
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM cart_items WHERE cart_id = ?");
        $stmt->execute([$cart_id]);
        $cart_count = $stmt->fetch()['count'];

        echo json_encode([
            'success' => true, 
            'message' => $message,
            'cart_count' => $cart_count
        ]);

    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
