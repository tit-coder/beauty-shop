<?php
session_start();
include 'config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => true, 'count' => 0]);
    exit;
}

$user_id = $_SESSION['user_id'];

try {
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count 
        FROM cart_items ci
        JOIN cart c ON ci.cart_id = c.cart_id
        WHERE c.user_id = ?
    ");
    $stmt->execute([$user_id]);
    $result = $stmt->fetch();

    echo json_encode(['success' => true, 'count' => $result['count']]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'count' => 0]);
}
