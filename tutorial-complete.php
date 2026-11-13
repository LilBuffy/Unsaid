<?php
require_once __DIR__ . '/includes/auth.php';

header('Content-Type: application/json');
header('Cache-Control: no-store');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false]);
    exit;
}

if (!is_logged_in()) {
    http_response_code(401);
    echo json_encode(['ok' => false]);
    exit;
}

if (!csrf_verify($_POST['csrf_token'] ?? '')) {
    http_response_code(403);
    echo json_encode(['ok' => false]);
    exit;
}

if (!tutorial_ready($pdo)) {
    echo json_encode(['ok' => true]);
    exit;
}

try {
    $stmt = $pdo->prepare("UPDATE users SET tutorial_pending = 0 WHERE id = ?");
    $stmt->execute([current_user_id()]);
    echo json_encode(['ok' => true]);
} catch (Throwable $e) {
    error_log('Tutorial completion write failed');
    http_response_code(500);
    echo json_encode(['ok' => false]);
}
