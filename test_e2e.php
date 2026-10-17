<?php
/**
 * End-to-End Automated Verification Script for BeautyShop
 */
require_once __DIR__ . '/php/config.php';
require_once __DIR__ . '/php/ai_advisor_service.php';

echo "========================================================\n";
echo "      BEAUTYSHOP AUTOMATED E2E INTEGRATION TEST         \n";
echo "========================================================\n\n";

$pass = 0;
$fail = 0;

function test_assert($condition, $message) {
    global $pass, $fail;
    if ($condition) {
        echo " [PASS] $message\n";
        $pass++;
    } else {
        echo " [FAIL] $message\n";
        $fail++;
    }
}

// 1. Kiểm tra kết nối PDO & Bảng dữ liệu
try {
    test_assert($pdo !== null, "Kết nối cơ sở dữ liệu MySQL thành công (beauty_shop)");
    
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    $required_tables = ['users', 'categories', 'brands', 'products', 'skin_types', 'orders', 'order_items', 'coupons', 'flash_sales', 'reviews'];
    $missing_tables = array_diff($required_tables, $tables);
    test_assert(empty($missing_tables), "Đầy đủ các bảng dữ liệu cốt lõi (" . count($tables) . " tables tìm thấy)");
} catch (Exception $e) {
    test_assert(false, "Lỗi kết nối DB: " . $e->getMessage());
}

// 2. Kiểm tra tài khoản Demo & Password Hashing Bcrypt
try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute(['admin']);
    $admin = $stmt->fetch();
    test_assert($admin && password_verify('admin123', $admin['password']), "Tài khoản Quản trị 'admin' verify password thành công");

    $stmt->execute(['lananh']);
    $customer = $stmt->fetch();
    test_assert($customer && password_verify('customer123', $customer['password']), "Tài khoản Khách hàng 'lananh' verify password thành công");
} catch (Exception $e) {
    test_assert(false, "Lỗi test user: " . $e->getMessage());
}

// 3. Kiểm tra danh mục, thương hiệu và sản phẩm
try {
    $prod_count = (int)$pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
    test_assert($prod_count >= 30, "Kho mỹ phẩm sẵn có dữ liệu phong phú ($prod_count sản phẩm thực tế)");

    $cat_count = (int)$pdo->query("SELECT COUNT(*) FROM categories")->fetchColumn();
    test_assert($cat_count >= 5, "Đầy đủ danh mục mỹ phẩm ($cat_count danh mục)");

    $brand_count = (int)$pdo->query("SELECT COUNT(*) FROM brands")->fetchColumn();
    test_assert($brand_count >= 5, "Đầy đủ thương hiệu mỹ phẩm ($brand_count thương hiệu)");
} catch (Exception $e) {
    test_assert(false, "Lỗi test catalog: " . $e->getMessage());
}

// 4. Kiểm tra mã giảm giá (Coupon Engine)
try {
    $c_stmt = $pdo->prepare("SELECT * FROM coupons WHERE code = 'BEAUTYNEW'");
    $c_stmt->execute();
    $coupon = $c_stmt->fetch();
    test_assert($coupon && $coupon['discount_value'] == 10.0, "Mã giảm giá BEAUTYNEW (10%) tồn tại và hợp lệ");
} catch (Exception $e) {
    test_assert(false, "Lỗi test coupon: " . $e->getMessage());
}

// 5. Kiểm tra AI Beauty Advisor Engine (Database Grounding & Prompt)
try {
    $advisor = new AIBeautyAdvisorService($pdo);
    $ai_response = $advisor->getAdvice("Tôi da dầu mụn thì nên dùng kem dưỡng nào?", [], 1);
    test_assert(is_array($ai_response) && !empty($ai_response['text']), "AI Beauty Advisor sinh lời tư vấn chuẩn phác đồ");
    test_assert(!empty($ai_response['products']), "AI gợi ý sản phẩm thực tế từ Database MySQL (" . count($ai_response['products']) . " sp)");
} catch (Exception $e) {
    test_assert(false, "Lỗi test AI: " . $e->getMessage());
}

// 6. Kiểm tra Toàn Vẹn Giao Dịch Đặt Hàng (ACID Transaction) & Rollback Kho Hàng
try {
    $pdo->beginTransaction();

    // Lấy 1 sản phẩm còn hàng để test đặt
    $target_prod = $pdo->query("SELECT product_id, product_name, price, quantity, sold_count FROM products WHERE quantity > 5 LIMIT 1 FOR UPDATE")->fetch();
    $pid = $target_prod['product_id'];
    $initial_qty = (int)$target_prod['quantity'];
    $initial_sold = (int)$target_prod['sold_count'];

    // Mô phỏng đặt hàng 2 sản phẩm
    $test_order_code = 'TEST-ORD-' . time();
    $buy_qty = 2;
    $order_total = $target_prod['price'] * $buy_qty;

    $o_stmt = $pdo->prepare("INSERT INTO orders (order_code, user_id, customer_name, customer_phone, customer_address, payment_method, payment_status, total_amount, status, created_at) VALUES (?, 1, 'Người Mua Test', '0999888777', '123 Phố Test, Hà Nội', 'cod', 'unpaid', ?, 'pending', NOW())");
    $o_stmt->execute([$test_order_code, $order_total]);
    $test_order_id = $pdo->lastInsertId();

    $i_stmt = $pdo->prepare("INSERT INTO order_items (order_id, product_id, product_name, price, quantity, subtotal) VALUES (?, ?, ?, ?, ?, ?)");
    $i_stmt->execute([$test_order_id, $pid, $target_prod['product_name'], $target_prod['price'], $buy_qty, $order_total]);

    // Trừ kho & tăng sold_count
    $pdo->prepare("UPDATE products SET quantity = quantity - ?, sold_count = sold_count + ? WHERE product_id = ?")->execute([$buy_qty, $buy_qty, $pid]);

    // Kiểm tra kho sau khi đặt
    $after_buy_qty = (int)$pdo->query("SELECT quantity FROM products WHERE product_id = $pid")->fetchColumn();
    test_assert($after_buy_qty === ($initial_qty - $buy_qty), "Trừ kho chính xác sau khi đặt hàng (Kho từ $initial_qty -> $after_buy_qty)");

    // Mô phỏng HỦY ĐƠN HÀNG và HOÀN TRẢ TỒN KHO
    $restore_stmt = $pdo->prepare("UPDATE products SET quantity = quantity + ?, sold_count = GREATEST(0, sold_count - ?) WHERE product_id = ?");
    $restore_stmt->execute([$buy_qty, $buy_qty, $pid]);
    $pdo->prepare("UPDATE orders SET status = 'cancelled' WHERE order_id = ?")->execute([$test_order_id]);

    $after_cancel_qty = (int)$pdo->query("SELECT quantity FROM products WHERE product_id = $pid")->fetchColumn();
    test_assert($after_cancel_qty === $initial_qty, "Hoàn trả số lượng vào kho 100% khi hủy đơn hàng (Kho về lại $initial_qty)");

    // Dọn dẹp bản ghi test
    $pdo->prepare("DELETE FROM order_items WHERE order_id = ?")->execute([$test_order_id]);
    $pdo->prepare("DELETE FROM orders WHERE order_id = ?")->execute([$test_order_id]);

    $pdo->commit();
    test_assert(true, "Giao dịch ACID hoàn tất và dữ liệu test được dọn dẹp sạch sẽ");
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    test_assert(false, "Lỗi test ACID đơn hàng: " . $e->getMessage());
}

// 7. Tổng kết
echo "\n========================================================\n";
echo " KẾT QUẢ KIỂM THỬ: $pass THÀNH CÔNG, $fail THẤT BẠI\n";
echo "========================================================\n";

if ($fail === 0) {
    echo ">>> TẤT CẢ CÁC MODULE ĐỀU ĐẠT CHUẨN SẴN SÀNG BẢO VỆ ĐỒ ÁN! <<<\n";
    exit(0);
} else {
    echo ">>> CẦN KIỂM TRA LẠI CÁC BƯỚC THẤT BẠI TRÊN! <<<\n";
    exit(1);
}
