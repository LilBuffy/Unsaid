<?php
require_once __DIR__ . '/includes/auth.php';
require_login();

$userId = current_user_id();
$messageId = (int) ($_GET['id'] ?? 0);

$stmt = $pdo->prepare("SELECT m.id, m.message, m.is_read, m.created_at, u.username FROM messages m JOIN profiles p ON p.id = m.profile_id JOIN users u ON u.id = p.user_id WHERE m.id = ? AND p.user_id = ?");
$stmt->execute([$messageId, $userId]);
$message = $stmt->fetch();

if (!$message) {
    http_response_code(404);
    die("Message not found or access denied.");
}

$stmt = $pdo->prepare("SELECT file_type, link_url FROM attachments WHERE message_id = ?");
$stmt->execute([$messageId]);
$attachments = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>UNSAID Report - Message #<?php echo (int) $message['id']; ?></title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="print-body">
<div class="print-report">
    <div class="print-header">
        <h1>UNSAID</h1>
        <p>Message Report</p>
    </div>
    <table class="print-table">
        <tr><th>Recipient</th><td><?php echo e($message['username']); ?></td></tr>
        <tr><th>Sender</th><td>Anonymous</td></tr>
        <tr><th>Date &amp; time</th><td><?php echo e(date('F j, Y g:i A', strtotime($message['created_at']))); ?></td></tr>
        <tr><th>Status</th><td><?php echo $message['is_read'] ? 'Read' : 'Unread'; ?></td></tr>
    </table>
    <div class="print-message-body">
        <?php echo nl2br(e($message['message'])); ?>
    </div>
    <?php if (!empty($attachments)): ?>
        <div class="print-attachments">
            <h3>Attachments</h3>
            <?php foreach ($attachments as $att): ?>
                <?php if ($att['file_type'] === 'link'): ?>
                    <p>Link: <?php echo e($att['link_url']); ?></p>
                <?php else: ?>
                    <p>Attachment type: <?php echo e($att['file_type']); ?></p>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    <button class="btn btn-primary no-print" onclick="window.print()">Print this report</button>
</div>
</body>
</html>
