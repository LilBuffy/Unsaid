<?php
require_once __DIR__ . '/includes/auth.php';
require_login();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Invalid request method.']);
    exit;
}

$token = $_POST['csrf_token'] ?? '';
if (!csrf_verify($token)) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Invalid security token.']);
    exit;
}

$messageId = (int) post_str('message_id', 20);
$action = post_str('action', 20);
$userId = current_user_id();

$stmt = $pdo->prepare("SELECT m.id, m.profile_id FROM messages m JOIN profiles p ON p.id = m.profile_id WHERE m.id = ? AND p.user_id = ?");
$stmt->execute([$messageId, $userId]);
$message = $stmt->fetch();

if (!$message) {
    http_response_code(404);
    echo json_encode(['ok' => false, 'error' => 'Message not found or access denied.']);
    exit;
}

if ($action === 'read') {
    $stmt = $pdo->prepare("UPDATE messages SET is_read = 1 WHERE id = ?");
    $stmt->execute([$messageId]);
    echo json_encode(['ok' => true]);
} elseif ($action === 'unread') {
    $stmt = $pdo->prepare("UPDATE messages SET is_read = 0 WHERE id = ?");
    $stmt->execute([$messageId]);
    echo json_encode(['ok' => true]);
} elseif ($action === 'archive' || $action === 'restore') {
    if (!archive_ready($pdo)) {
        http_response_code(409);
        echo json_encode(['ok' => false, 'error' => 'Archiving is not available until the database update is applied.']);
        exit;
    }
    $stmt = $pdo->prepare("UPDATE messages SET is_archived = ? WHERE id = ?");
    $stmt->execute([$action === 'archive' ? 1 : 0, $messageId]);
    echo json_encode(['ok' => true]);
} elseif ($action === 'delete') {
    $stmt = $pdo->prepare("SELECT file_path FROM attachments WHERE message_id = ? AND file_path IS NOT NULL");
    $stmt->execute([$messageId]);
    foreach ($stmt->fetchAll() as $att) {
        delete_stored_upload($att['file_path']);
    }
    $stmt = $pdo->prepare("DELETE FROM messages WHERE id = ?");
    $stmt->execute([$messageId]);
    echo json_encode(['ok' => true]);
} elseif ($action === 'report') {
    $stmt = $pdo->prepare("SELECT id FROM reports WHERE message_id = ?");
    $stmt->execute([$messageId]);
    if ($stmt->fetch()) {
        http_response_code(409);
        echo json_encode(['ok' => false, 'error' => 'You have already reported this message.']);
        exit;
    }

    $stmt = $pdo->prepare("SELECT message, sender_ip FROM messages WHERE id = ?");
    $stmt->execute([$messageId]);
    $fullMessage = $stmt->fetch();

    $stmt = $pdo->prepare("INSERT INTO reports (message_id, profile_id, reported_message, sender_ip) VALUES (?, ?, ?, ?)");
    $stmt->execute([$messageId, $message['profile_id'], $fullMessage['message'], $fullMessage['sender_ip']]);
    echo json_encode(['ok' => true]);
} else {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Unknown action.']);
}
