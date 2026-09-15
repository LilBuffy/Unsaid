<?php
require_once __DIR__ . '/includes/auth.php';
require_login();

$userId = current_user_id();

$stmt = $pdo->prepare("SELECT id FROM profiles WHERE user_id = ?");
$stmt->execute([$userId]);
$profile = $stmt->fetch();

if (!$profile) {
    redirect('logout.php');
}

$profileId = $profile['id'];
$printAll = isset($_GET['all']);
$ids = $_GET['ids'] ?? [];

if ($printAll) {
    $stmt = $pdo->prepare("SELECT id, message, is_read, created_at FROM messages WHERE profile_id = ? ORDER BY created_at DESC");
    $stmt->execute([$profileId]);
    $messages = $stmt->fetchAll();
} else {
    $ids = array_filter(array_map('intval', (array) $ids));
    if (empty($ids)) {
        die("No messages selected.");
    }
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $pdo->prepare("SELECT id, message, is_read, created_at FROM messages WHERE profile_id = ? AND id IN ($placeholders) ORDER BY created_at DESC");
    $stmt->execute(array_merge([$profileId], $ids));
    $messages = $stmt->fetchAll();
}

$stmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
$stmt->execute([$userId]);
$recipient = $stmt->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>UNSAID Report — <?php echo e($recipient); ?></title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="print-body">
<div class="print-report">
    <div class="print-header">
        <h1>UNSAID</h1>
        <p>Message Report for <?php echo e($recipient); ?></p>
        <p><?php echo count($messages); ?> message<?php echo count($messages) === 1 ? '' : 's'; ?></p>
    </div>

    <?php foreach ($messages as $msg): ?>
        <div class="print-message-block">
            <table class="print-table">
                <tr><th>Sender</th><td>Anonymous</td></tr>
                <tr><th>Date &amp; time</th><td><?php echo e(date('F j, Y g:i A', strtotime($msg['created_at']))); ?></td></tr>
                <tr><th>Status</th><td><?php echo $msg['is_read'] ? 'Read' : 'Unread'; ?></td></tr>
            </table>
            <div class="print-message-body">
                <?php echo nl2br(e($msg['message'])); ?>
            </div>
        </div>
        <hr class="print-divider">
    <?php endforeach; ?>

    <button class="btn btn-primary no-print" onclick="window.print()">Print this report</button>
</div>
</body>
</html>
