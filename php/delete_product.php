<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/config.php';

// Bảo vệ trang admin
if (!is_logged_in() || !is_admin()) {
    header("Location: login.php");
    exit;
}

$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($product_id <= 0) {
    header("Location: manage_products.php");
    exit;
}

try {
    // Kiểm tra sản phẩm có tồn tại
    $stmt = $pdo->prepare("SELECT product_name, image FROM products WHERE product_id = ?");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch();

    if ($product) {
        $product_name = $product['product_name'];

        // Kiểm tra xem sản phẩm đã phát sinh trong đơn hàng chưa (Ràng buộc toàn vẹn)
        $order_check = $pdo->prepare("SELECT COUNT(*) FROM order_items WHERE product_id = ?");
        $order_check->execute([$product_id]);
        $has_orders = $order_check->fetchColumn() > 0;

        if ($has_orders) {
            // Không xóa cứng nếu đã có đơn hàng -> Thực hiện Soft Delete
            $pdo->prepare("UPDATE products SET is_active = 0 WHERE product_id = ?")->execute([$product_id]);
            $_SESSION['success_msg'] = "Sản phẩm '$product_name' đã có lịch sử đơn hàng nên đã được chuyển sang trạng thái ẨN (Soft Delete) để bảo toàn dữ liệu kế toán.";
        } else {
            // Xóa cứng an toàn các bảng phụ thuộc
            $pdo->beginTransaction();
            $pdo->prepare("DELETE FROM product_skin_types WHERE product_id = ?")->execute([$product_id]);
            $pdo->prepare("DELETE FROM product_ingredients WHERE product_id = ?")->execute([$product_id]);
            $pdo->prepare("DELETE FROM product_images WHERE product_id = ?")->execute([$product_id]);
            $pdo->prepare("DELETE FROM cart_items WHERE product_id = ?")->execute([$product_id]);
            $pdo->prepare("DELETE FROM wishlists WHERE product_id = ?")->execute([$product_id]);
            $pdo->prepare("DELETE FROM flash_sale_items WHERE product_id = ?")->execute([$product_id]);
            $pdo->prepare("DELETE FROM reviews WHERE product_id = ?")->execute([$product_id]);
            $pdo->prepare("DELETE FROM products WHERE product_id = ?")->execute([$product_id]);
            $pdo->commit();

            $_SESSION['success_msg'] = "Đã xóa vĩnh viễn sản phẩm '$product_name' khỏi cơ sở dữ liệu!";
        }
    }
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $_SESSION['error_msg'] = "Lỗi khi xóa sản phẩm: " . $e->getMessage();
}

header("Location: manage_products.php");
exit;
