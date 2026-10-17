<?php
// category.php - Điều hướng đến trang danh mục sản phẩm hoàn chỉnh
$cat = isset($_GET['cat']) ? (int)$_GET['cat'] : 0;
if ($cat > 0) {
    header("Location: products.php?cat=" . $cat);
} else {
    header("Location: products.php");
}
exit;