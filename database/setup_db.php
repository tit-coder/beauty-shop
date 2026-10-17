<?php
/**
 * BeautyShop - Automated Database Setup & Seed Tool
 * Hỗ trợ khởi tạo schema và nạp dữ liệu mẫu nhanh chóng qua CLI hoặc Web.
 */

// Bật hiển thị lỗi
error_reporting(E_ALL);
ini_set('display_errors', 1);

$is_cli = (php_sapi_name() === 'cli');

function output_log($msg, $type = 'info') {
    global $is_cli;
    $time = date('H:i:s');
    if ($is_cli) {
        $prefix = match($type) {
            'success' => "[✓ SUCCESS $time] ",
            'error'   => "[✗ ERROR $time] ",
            'warning' => "[! WARNING $time] ",
            default   => "[ℹ INFO $time] ",
        };
        echo $prefix . $msg . PHP_EOL;
    } else {
        $color = match($type) {
            'success' => '#155724; background: #d4edda;',
            'error'   => '#721c24; background: #f8d7da;',
            'warning' => '#856404; background: #fff3cd;',
            default   => '#0c5460; background: #d1ecf1;',
        };
        echo "<div style='padding: 8px 12px; margin: 4px 0; border-radius: 6px; font-family: sans-serif; color: $color'><strong>[$time]</strong> " . htmlspecialchars($msg) . "</div>";
    }
}

if (!$is_cli) {
    echo "<!DOCTYPE html><html lang='vi'><head><meta charset='UTF-8'><title>Khởi tạo CSDL BeautyShop</title><style>body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; max-width: 900px; margin: 40px auto; padding: 20px; background: #fdf8f5; } .card { background: white; padding: 30px; border-radius: 16px; box-shadow: 0 10px 30px rgba(0,0,0,0.06); } h1 { color: #d47a88; }</style></head><body><div class='card'><h1>🌸 Khởi Tạo Cơ Sở Dữ Liệu BeautyShop</h1>";
}

// 1. Đọc cấu hình từ .env nếu có
$env_file = dirname(__DIR__) . '/.env';
$db_host = '127.0.0.1';
$db_user = 'root';
$db_pass = '';
$db_name = 'beauty_shop';
$db_port = 3306;

if (file_exists($env_file)) {
    $lines = file($env_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) continue;
        if (strpos($line, '=') !== false) {
            list($key, $val) = explode('=', $line, 2);
            $key = trim($key);
            $val = trim($val, " \t\n\r\0\x0B\"'");
            match($key) {
                'DB_HOST' => $db_host = $val,
                'DB_USER' => $db_user = $val,
                'DB_PASS' => $db_pass = $val,
                'DB_NAME' => $db_name = $val,
                'DB_PORT' => $db_port = (int)$val,
                default   => null
            };
        }
    }
}

output_log("Đang kết nối tới máy chủ MySQL tại $db_host:$db_port với user '$db_user'...");

try {
    // Kết nối không chỉ định DB trước để tạo DB nếu chưa có
    $pdo = new PDO("mysql:host=$db_host;port=$db_port;charset=utf8mb4", $db_user, $db_pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::MYSQL_ATTR_MULTI_STATEMENTS => true,
    ]);
    output_log("Kết nối MySQL thành công!", 'success');

    // 2. Chạy Schema DDL
    $schema_file = __DIR__ . '/schema.sql';
    if (!file_exists($schema_file)) {
        throw new Exception("Không tìm thấy file schema tại: $schema_file");
    }

    output_log("Đang thực thi file cấu trúc database (schema.sql)...");
    $schema_sql = file_get_contents($schema_file);
    $pdo->exec($schema_sql);
    output_log("Tạo database `$db_name` và các bảng dữ liệu thành công!", 'success');

    // Kết nối trực tiếp vào database mới tạo
    $pdo->exec("USE `$db_name`");

    // 3. Chạy Seed Data
    $seed_file = __DIR__ . '/seed.sql';
    if (!file_exists($seed_file)) {
        throw new Exception("Không tìm thấy file dữ liệu mẫu tại: $seed_file");
    }

    output_log("Đang nạp dữ liệu mẫu mỹ phẩm (seed.sql)...");
    $seed_sql = file_get_contents($seed_file);
    $pdo->exec($seed_sql);
    output_log("Nạp dữ liệu mẫu mỹ phẩm, thương hiệu, danh mục thành công!", 'success');

    // 4. Kiểm tra đếm số lượng bản ghi
    $counts = [
        'users' => $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn(),
        'categories' => $pdo->query("SELECT COUNT(*) FROM categories")->fetchColumn(),
        'brands' => $pdo->query("SELECT COUNT(*) FROM brands")->fetchColumn(),
        'products' => $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn(),
        'coupons' => $pdo->query("SELECT COUNT(*) FROM coupons")->fetchColumn(),
        'orders' => $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn(),
        'reviews' => $pdo->query("SELECT COUNT(*) FROM reviews")->fetchColumn(),
    ];

    output_log("=== BÁO CÁO THỐNG KÊ SAU KHI KHỞI TẠO ===", 'info');
    foreach ($counts as $tbl => $cnt) {
        output_log("- Bảng `$tbl`: $cnt bản ghi", 'success');
    }

    output_log("Tài khoản demo quản trị: admin / admin123", 'info');
    output_log("Tài khoản demo khách hàng: lananh / customer123", 'info');
    output_log("🎉 HỆ THỐNG CƠ SỞ DỮ LIỆU ĐÃ SẴN SÀNG HOẠT ĐỘNG!", 'success');

} catch (PDOException $e) {
    output_log("Lỗi PDO: " . $e->getMessage(), 'error');
} catch (Exception $e) {
    output_log("Lỗi: " . $e->getMessage(), 'error');
}

if (!$is_cli) {
    echo "<br><a href='../php/index.php' style='display:inline-block; padding:12px 24px; background:linear-gradient(135deg, #d47a88, #9e4760); color:white; text-decoration:none; border-radius:8px; font-weight:bold;'>Về trang chủ BeautyShop</a></div></body></html>";
}

