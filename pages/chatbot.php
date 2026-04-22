<?php
session_start();
header('Content-Type: application/json; charset=UTF-8');

include '../includes/db.php';
require_once '../includes/order_system.php';
require_once '../includes/services/OpenAIService.php';
require_once '../includes/services/SearchService.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['reply' => 'Method not allowed.']);
    exit();
}

$payload = json_decode(file_get_contents('php://input'), true);
$message = trim((string) ($payload['message'] ?? ''));

if ($message === '') {
    echo json_encode(['reply' => 'Please type a message so I can help.']);
    exit();
}

$config = require '../includes/config.php';
$openAI = new OpenAIService($config);
$searchService = new SearchService();
$userId = isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;

$searchResults = $searchService->searchProducts($conn, $message, 4);
$recentOrders = [];

if ($userId) {
    $orderStmt = $conn->prepare('SELECT id, order_status, total, created_at FROM orders WHERE user_id = ? ORDER BY id DESC LIMIT 3');
    $orderStmt->execute([$userId]);
    $recentOrders = $orderStmt->fetchAll(PDO::FETCH_ASSOC);
}

$fallbackReply = [];
$fallbackReply[] = 'I can help with products, order tracking, and shopping guidance.';

if (!empty($recentOrders)) {
    $latestOrder = $recentOrders[0];
    $fallbackReply[] = 'Your latest order is #' . $latestOrder['id'] . ' and its status is ' . ($latestOrder['order_status'] ?? 'Pending') . '.';
}

if (!empty($searchResults['products'])) {
    $names = array_map(static fn(array $product): string => $product['name'], $searchResults['products']);
    $fallbackReply[] = 'Matching products: ' . implode(', ', array_slice($names, 0, 3)) . '.';
}

$systemPrompt = 'You are a concise ecommerce assistant for a PHP shopping site. Help with product discovery, order tracking, and buying guidance. Prefer store context when available. If data is missing, say so clearly.';
$contextPrompt = 'User message: ' . $message;

if (!empty($searchResults['products'])) {
    $contextPrompt .= "\nRelevant products:\n";
    foreach ($searchResults['products'] as $product) {
        $contextPrompt .= '- ' . $product['name'] . ' | Category: ' . ($product['category'] ?? 'N/A') . ' | Price: Rs ' . number_format((float) $product['price'], 2) . "\n";
    }
}

if (!empty($recentOrders)) {
    $contextPrompt .= "\nRecent orders:\n";
    foreach ($recentOrders as $order) {
        $contextPrompt .= '- Order #' . $order['id'] . ' | Status: ' . ($order['order_status'] ?? 'Pending') . ' | Total: Rs ' . number_format((float) $order['total'], 2) . "\n";
    }
}

$aiResponse = $openAI->chat([
    ['role' => 'system', 'content' => $systemPrompt],
    ['role' => 'user', 'content' => $contextPrompt],
]);

$reply = $aiResponse['success']
    ? $aiResponse['reply']
    : implode(' ', $fallbackReply);

echo json_encode([
    'reply' => $reply,
    'suggestions' => array_slice(array_map(static function (array $product): string {
        return $product['name'] . ' - Rs ' . number_format((float) $product['price'], 2);
    }, $searchResults['products']), 0, 3),
]);
