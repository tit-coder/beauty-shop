<?php
session_start();

// Bảo vệ trang admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

include 'config.php';

// Lấy ID sản phẩm từ URL
$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($product_id <= 0) {
    header("Location: manage_products.php");
    exit;
}

try {
    // Lấy thông tin sản phẩm trước khi xóa (để xóa ảnh)
    $stmt = $pdo->prepare("SELECT image FROM products WHERE product_id = ?");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch();
    
    if ($product) {
        // Xóa sản phẩm khỏi database
        $delete_stmt = $pdo->prepare("DELETE FROM products WHERE product_id = ?");
        $delete_stmt->execute([$product_id]);
        
        // Xóa ảnh sản phẩm nếu có
        if (!empty($product['image'])) {
            $image_path = '../images/products/' . $product['image'];
            if (file_exists($image_path)) {
                unlink($image_path);
            }
        }
        
        // Chuyển hướng với thông báo thành công
        $_SESSION['delete_success'] = 'Đã xóa sản phẩm thành công!';
    }
    
} catch (PDOException $e) {
    $_SESSION['delete_error'] = 'Lỗi khi xóa sản phẩm: ' . $e->getMessage();
}

header("Location: manage_products.php");
exit;
?>
