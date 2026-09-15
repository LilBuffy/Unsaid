<?php
require_once __DIR__ . '/../includes/auth.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('reports.php');
}

csrf_require();

$reportId = (int) ($_POST['report_id'] ?? 0);
$action = $_POST['report_action'] ?? '';

$stmt = $pdo->prepare("SELECT id, message_id, sender_ip FROM reports WHERE id = ?");
$stmt->execute([$reportId]);
$report = $stmt->fetch();

if (!$report) {
    redirect('reports.php');
}

if ($action === 'ban_ip') {
    $reason = trim($_POST['reason'] ?? '');
    if (!empty($report['sender_ip']) && $report['sender_ip'] !== 'unknown') {
        $stmt = $pdo->prepare("INSERT INTO banned_senders (ip_address, reason) VALUES (?, ?) ON DUPLICATE KEY UPDATE reason = VALUES(reason)");
        $stmt->execute([$report['sender_ip'], $reason !== '' ? $reason : null]);
    }
    $stmt = $pdo->prepare("UPDATE reports SET status = 'reviewed' WHERE id = ?");
    $stmt->execute([$reportId]);
} elseif ($action === 'delete_message') {
    if ($report['message_id']) {
        $stmt = $pdo->prepare("SELECT file_path FROM attachments WHERE message_id = ? AND file_path IS NOT NULL");
        $stmt->execute([$report['message_id']]);
        foreach ($stmt->fetchAll() as $att) {
            $filePath = __DIR__ . '/../uploads/' . $att['file_path'];
            if (is_file($filePath)) {
                unlink($filePath);
            }
        }
        $stmt = $pdo->prepare("DELETE FROM messages WHERE id = ?");
        $stmt->execute([$report['message_id']]);
    }
    $stmt = $pdo->prepare("UPDATE reports SET status = 'reviewed' WHERE id = ?");
    $stmt->execute([$reportId]);
} elseif ($action === 'mark_reviewed') {
    $stmt = $pdo->prepare("UPDATE reports SET status = 'reviewed' WHERE id = ?");
    $stmt->execute([$reportId]);
}

redirect('reports.php');
