<?php
/**
 * BeautyShop - API Endpoint AI Beauty Advisor
 * Nhận request từ frontend và phản hồi JSON
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/ai_advisor_service.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Phương thức không được hỗ trợ']);
    exit;
}

// Đọc dữ liệu đầu vào (hỗ trợ cả JSON body lẫn $_POST)
$rawInput = file_get_contents('php://input');
$data = json_decode($rawInput, true) ?: $_POST;

$userMessage = trim($data['message'] ?? '');
if (empty($userMessage)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Vui lòng nhập câu hỏi hoặc yêu cầu tư vấn!']);
    exit;
}

if (mb_strlen($userMessage, 'UTF-8') > 1000) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Nội dung tin nhắn quá dài (tối đa 1000 ký tự)!']);
    exit;
}

// Quản lý session token của đoạn hội thoại
$sessionToken = trim($data['session_token'] ?? '');
if (empty($sessionToken)) {
    $sessionToken = 'chat_' . bin2hex(random_bytes(16));
}

$skinType = isset($data['skin_type']) ? (int)$data['skin_type'] : null;
$budget = isset($data['budget']) ? (float)$data['budget'] : null;
$userId = $_SESSION['user_id'] ?? null;

$advisor = new AIBeautyAdvisorService($pdo);

// Lấy lịch sử hội thoại 6 tin nhắn gần nhất để ngữ cảnh liên tục
$history = $advisor->getSessionMessages($sessionToken);
$recentHistory = array_slice($history, -6);

// 1. Lưu tin nhắn người dùng vào database
$advisor->logMessage($sessionToken, $userId, 'user', $userMessage);

// 2. Lấy phản hồi từ AI
try {
    $result = $advisor->getAdvice($userMessage, $recentHistory, $skinType, $budget);

    // 3. Lưu câu trả lời của AI vào database kèm danh sách sản phẩm gợi ý
    $advisor->logMessage($sessionToken, $userId, 'assistant', $result['text'], $result['products']);

    echo json_encode([
        'success' => true,
        'reply' => $result['text'],
        'source' => $result['source'],
        'products' => $result['products'],
        'disclaimer' => $result['disclaimer'],
        'session_token' => $sessionToken,
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Hệ thống tư vấn đang bận: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}

