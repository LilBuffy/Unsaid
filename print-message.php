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

$stmt = $pdo->prepare("SELECT file_name, file_path, file_type, file_size, link_url FROM attachments WHERE message_id = ?");
$stmt->execute([$messageId]);
$attachments = $stmt->fetchAll();

$imageAttachments = [];
$linkAttachments = [];
foreach ($attachments as $att) {
    if ($att['file_type'] === 'link') {
        $linkAttachments[] = $att;
    } elseif (!empty($att['file_path'])) {
        $imageAttachments[] = $att;
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
            <p class="print-doc-type">Message Report</p>
            <p class="print-doc-meta">Reference MSG-<?php echo str_pad((int) $message['id'], 6, '0', STR_PAD_LEFT); ?></p>
            <p class="print-doc-meta">Generated <?php echo e(date('F j, Y g:i A')); ?></p>
        </div>
    </div>

    <div class="print-meta-grid">
        <div class="print-meta-item">
            <span class="print-meta-label">Recipient</span>
            <span class="print-meta-value"><?php echo e($message['username']); ?></span>
        </div>
        <div class="print-meta-item">
            <span class="print-meta-label">Sender</span>
            <span class="print-meta-value">Anonymous</span>
        </div>
        <div class="print-meta-item">
            <span class="print-meta-label">Date received</span>
            <span class="print-meta-value"><?php echo e(date('F j, Y', strtotime($message['created_at']))); ?></span>
        </div>
        <div class="print-meta-item">
            <span class="print-meta-label">Time received</span>
            <span class="print-meta-value"><?php echo e(date('g:i A', strtotime($message['created_at']))); ?></span>
        </div>
        <div class="print-meta-item">
            <span class="print-meta-label">Status</span>
            <span class="print-meta-value"><?php echo $message['is_read'] ? 'Read' : 'Unread'; ?></span>
        </div>
        <div class="print-meta-item">
            <span class="print-meta-label">Attachments</span>
            <span class="print-meta-value"><?php echo count($attachments) > 0 ? count($attachments) : 'None'; ?></span>
        </div>
    </div>

    <div class="print-section">
        <h2 class="print-section-title">Message</h2>
        <div class="print-quote"><?php echo nl2br(e($message['message'])); ?></div>
    </div>

    <?php if (!empty($linkAttachments)): ?>
        <div class="print-section">
            <h2 class="print-section-title">Attached links</h2>
            <?php foreach ($linkAttachments as $att): ?>
                <p class="print-link-item"><?php echo e($att['link_url']); ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($imageAttachments)): ?>
        <div class="print-section">
            <h2 class="print-section-title">Attached media</h2>
            <?php foreach ($imageAttachments as $att): ?>
                <figure class="print-figure">
                    <img src="<?php echo upload_url($att['file_path']); ?>" alt="Attached media">
                    <figcaption><?php echo strtoupper(e($att['file_type'])); ?><?php if (!empty($att['file_size'])): ?><?php echo ', ' . round(((int) $att['file_size']) / 1024) . ' KB'; ?><?php endif; ?></figcaption>
                </figure>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="print-footer">
        <span>UNSAID Message Report</span>
        <span>Sender identities are never recorded in this report.</span>
    </div>
</div>
</body>
</html>
