<?php
// ===================================================================
// BEAUTY SHOP - CẤU HÌNH HỆ THỐNG & KẾT NỐI CƠ SỞ DỮ LIỆU
// ===================================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Thiết lập múi giờ Việt Nam
date_default_timezone_set('Asia/Ho_Chi_Minh');

// Hàm đọc biến môi trường từ .env
function load_env($file_path) {
    if (!file_exists($file_path)) return;
    $lines = file($file_path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) continue;
        if (strpos($line, '=') !== false) {
            list($key, $val) = explode('=', $line, 2);
            $key = trim($key);
            $val = trim($val, " \t\n\r\0\x0B\"'");
            if (!array_key_exists($key, $_SERVER) && !array_key_exists($key, $_ENV)) {
                putenv("$key=$val");
                $_ENV[$key] = $val;
                $_SERVER[$key] = $val;
            }
        }
    }
}

// Nạp file .env từ thư mục gốc
load_env(dirname(__DIR__) . '/.env');

// Lấy thông số kết nối CSDL (ưu tiên .env, mặc định beauty_shop)
$db_host = getenv('DB_HOST') ?: '127.0.0.1';
$db_port = getenv('DB_PORT') ?: 3306;
$db_name = getenv('DB_NAME') ?: 'beauty_shop';
$db_user = getenv('DB_USER') ?: 'root';
$db_pass = getenv('DB_PASS') !== false ? getenv('DB_PASS') : '';

try {
    $pdo = new PDO("mysql:host=$db_host;port=$db_port;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
} catch (PDOException $e) {
    // Nếu chưa tạo database beauty_shop, thử fallback về beauty_cosmetics nếu có
    try {
        $pdo = new PDO("mysql:host=$db_host;port=$db_port;dbname=beauty_cosmetics;charset=utf8mb4", $db_user, $db_pass, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    } catch (PDOException $ex) {
        die("Lỗi kết nối CSDL MySQL: " . $e->getMessage() . "<br>Vui lòng chạy script khởi tạo: <code>php database/setup_db.php</code>");
    }
}

// ===================================================================
// CÁC HÀM TIỆN ÍCH DÙNG CHUNG (HELPERS)
// ===================================================================

/**
 * Định dạng tiền tệ VND chuẩn Việt Nam
 */
function format_price($number) {
    return number_format((float)$number, 0, ',', '.') . ' ₫';
}

/**
 * Định dạng ngày giờ Việt Nam
 */
function format_date($datetime, $format = 'd/m/Y H:i') {
    if (!$datetime) return '';
    return date($format, strtotime($datetime));
}

/**
 * Kiểm tra người dùng đã đăng nhập chưa
 */
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

/**
 * Kiểm tra quyền quản trị viên (Admin)
 */
function is_admin() {
    return isset($_SESSION['user_id']) && isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

/**
 * Lấy số lượng sản phẩm trong giỏ hàng
 */
function get_cart_count($pdo, $user_id) {
    if (!$user_id) return 0;
    try {
        $stmt = $pdo->prepare("
            SELECT COALESCE(SUM(ci.quantity), 0) as total_qty
            FROM cart_items ci
            JOIN cart c ON ci.cart_id = c.cart_id
            WHERE c.user_id = ?
        ");
        $stmt->execute([$user_id]);
        return (int)$stmt->fetchColumn();
    } catch (Exception $e) {
        return 0;
    }
}

/**
 * Lấy Google Gemini API Key từ môi trường
 */
function get_gemini_api_key() {
    return getenv('GEMINI_API_KEY') ?: '';
}