<?php
// search.php - Điều hướng tìm kiếm đến trang sản phẩm hoàn chỉnh
$q = isset($_GET['q']) ? trim($_GET['q']) : '';
if (!empty($q)) {
    header("Location: products.php?q=" . urlencode($q));
} else {
    header("Location: products.php");
}
exit;
