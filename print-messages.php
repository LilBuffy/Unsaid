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
if (!is_array($ids)) {
    $ids = [];
}

if ($printAll) {
    $stmt = $pdo->prepare("SELECT id, message, is_read, created_at FROM messages WHERE profile_id = ? ORDER BY created_at DESC");
    $stmt->execute([$profileId]);
    $messages = $stmt->fetchAll();
} else {
    $ids = array_slice(array_values(array_unique(array_filter(array_map('intval', array_filter($ids, 'is_scalar'))))), 0, 500);
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

$attachmentsByMessage = [];
$messageIds = array_column($messages, 'id');
if (!empty($messageIds)) {
    $placeholders = implode(',', array_fill(0, count($messageIds), '?'));
    $stmt = $pdo->prepare("SELECT message_id, file_path, file_type, file_size, link_url FROM attachments WHERE message_id IN ($placeholders)");
    $stmt->execute($messageIds);
    foreach ($stmt->fetchAll() as $att) {
        $attachmentsByMessage[$att['message_id']][] = $att;
    }
}

$unreadCount = 0;
foreach ($messages as $msg) {
    if (!$msg['is_read']) {
        $unreadCount++;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>UNSAID Message Report</title>
<link rel="stylesheet" href="<?php echo asset_url('assets/css/print.css'); ?>">
</head>
<body class="print-body">
<div class="print-toolbar no-print">
    <a href="messages.php" class="print-toolbar-link">Back to messages</a>
    <button type="button" class="print-toolbar-btn" onclick="window.print()">Print this report</button>
</div>

<div class="print-sheet">
    <div class="print-masthead">
        <div class="print-masthead-left">
            <div class="print-logo">UNSAID</div>
            <p class="print-tagline">Say what you can't say.</p>
        </div>
        <div class="print-masthead-right">
            <p class="print-doc-type">Inbox Report</p>
            <p class="print-doc-meta">Prepared for <?php echo e($recipient); ?></p>
            <p class="print-doc-meta">Generated <?php echo e(date('F j, Y g:i A')); ?></p>
        </div>
    </div>

    <div class="print-summary">
        <div class="print-summary-item">
            <span class="print-summary-value"><?php echo count($messages); ?></span>
            <span class="print-summary-label">Messages in report</span>
        </div>
        <div class="print-summary-item">
            <span class="print-summary-value"><?php echo (int) $unreadCount; ?></span>
            <span class="print-summary-label">Unread</span>
        </div>
        <div class="print-summary-item">
            <span class="print-summary-value"><?php echo count($messages) - (int) $unreadCount; ?></span>
            <span class="print-summary-label">Read</span>
        </div>
    </div>

    <?php if (empty($messages)): ?>
        <p class="print-empty">No messages were included in this report.</p>
    <?php endif; ?>

    <?php $index = 0; ?>
    <?php foreach ($messages as $msg): ?>
        <?php $index++; ?>
        <article class="print-entry">
            <div class="print-entry-head">
                <span class="print-entry-number"><?php echo str_pad($index, 2, '0', STR_PAD_LEFT); ?></span>
                <div class="print-entry-headings">
                    <span class="print-entry-sender">Anonymous</span>
                    <span class="print-entry-date"><?php echo e(date('F j, Y g:i A', strtotime($msg['created_at']))); ?></span>
                </div>
                <span class="print-entry-status"><?php echo $msg['is_read'] ? 'Read' : 'Unread'; ?></span>
            </div>

            <div class="print-quote"><?php echo nl2br(e($msg['message'])); ?></div>

            <?php $entryAttachments = $attachmentsByMessage[$msg['id']] ?? []; ?>
            <?php if (!empty($entryAttachments)): ?>
                <div class="print-entry-attachments">
                    <?php foreach ($entryAttachments as $att): ?>
                        <?php if ($att['file_type'] === 'link'): ?>
                            <p class="print-link-item"><?php echo e($att['link_url']); ?></p>
                        <?php elseif (!empty($att['file_path'])): ?>
                            <figure class="print-figure">
                                <img src="<?php echo upload_url($att['file_path']); ?>" alt="Attached media">
                                <figcaption><?php echo strtoupper(e($att['file_type'])); ?></figcaption>
                            </figure>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </article>
    <?php endforeach; ?>

    <div class="print-footer">
        <span>UNSAID Inbox Report</span>
        <span>Sender identities are never recorded in this report.</span>
    </div>
</div>
</body>
</html>
