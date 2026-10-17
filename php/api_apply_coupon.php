<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/config.php';

if (!is_logged_in()) {
    echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập để sử dụng mã giảm giá!']);
    exit;
}

$raw = file_get_contents('php://input');
$data = json_decode($raw, true) ?: $_POST;
$code = strtoupper(trim($data['code'] ?? ''));
$action = $data['action'] ?? 'apply';

if ($action === 'remove') {
    unset($_SESSION['applied_coupon']);
    echo json_encode(['success' => true, 'message' => 'Đã hủy áp dụng mã giảm giá!']);
    exit;
}

if (empty($code)) {
    echo json_encode(['success' => false, 'message' => 'Vui lòng nhập mã giảm giá!']);
    exit;
}

// Tính tổng tiền giỏ hàng hiện tại từ database
$user_id = $_SESSION['user_id'];
$cartStmt = $pdo->prepare("
    SELECT SUM(ci.quantity * p.price) as cart_total
    FROM cart_items ci
    JOIN cart c ON ci.cart_id = c.cart_id
    JOIN products p ON ci.product_id = p.product_id
    WHERE c.user_id = ?
");
$cartStmt->execute([$user_id]);
$cart_total = (float)$cartStmt->fetchColumn();

if ($cart_total <= 0) {
    echo json_encode(['success' => false, 'message' => 'Giỏ hàng của bạn đang trống!']);
    exit;
}

// Kiểm tra mã giảm giá trong CSDL
$stmt = $pdo->prepare("
    SELECT * FROM coupons 
    WHERE code = ? AND is_active = 1 AND start_date <= NOW() AND end_date >= NOW()
");
$stmt->execute([$code]);
$coupon = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$coupon) {
    echo json_encode(['success' => false, 'message' => 'Mã giảm giá không tồn tại hoặc đã hết hạn!']);
    exit;
}

if ($coupon['used_count'] >= $coupon['usage_limit']) {
    echo json_encode(['success' => false, 'message' => 'Mã giảm giá đã hết lượt sử dụng!']);
    exit;
}

if ($cart_total < $coupon['min_order_amount']) {
    echo json_encode([
        'success' => false, 
        'message' => 'Đơn hàng chưa đạt giá trị tối thiểu ' . format_price($coupon['min_order_amount']) . ' để dùng mã này!'
    ]);
    exit;
}

// Tính tiền giảm
$discount = 0;
if ($coupon['discount_type'] === 'percent') {
    $discount = $cart_total * ((float)$coupon['discount_value'] / 100);
    if ($coupon['max_discount_amount'] && $discount > $coupon['max_discount_amount']) {
        $discount = (float)$coupon['max_discount_amount'];
    }
} else {
    $discount = (float)$coupon['discount_value'];
}

$discount = min($discount, $cart_total);
$final_total = max(0, $cart_total - $discount);

// Lưu vào session
$_SESSION['applied_coupon'] = [
    'id' => $coupon['id'],
    'code' => $coupon['code'],
    'discount_amount' => $discount,
    'discount_type' => $coupon['discount_type'],
    'discount_value' => $coupon['discount_value'],
];

echo json_encode([
    'success' => true,
    'message' => "Áp dụng mã {$coupon['code']} thành công!",
    'code' => $coupon['code'],
    'discount_amount' => $discount,
    'discount_formatted' => format_price($discount),
    'final_total' => $final_total,
    'final_total_formatted' => format_price($final_total)
]);

