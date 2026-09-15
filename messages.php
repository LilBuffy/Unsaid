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

$search = trim($_GET['search'] ?? '');
$status = $_GET['status'] ?? 'all';
$date = $_GET['date'] ?? '';

$conditions = ["m.profile_id = ?"];
$params = [$profileId];

if ($search !== '') {
    $conditions[] = "m.message LIKE ?";
    $params[] = '%' . $search . '%';
}

if ($status === 'read') {
    $conditions[] = "m.is_read = 1";
} elseif ($status === 'unread') {
    $conditions[] = "m.is_read = 0";
}

if ($date !== '') {
    $conditions[] = "DATE(m.created_at) = ?";
    $params[] = $date;
}

$where = implode(' AND ', $conditions);

$stmt = $pdo->prepare("SELECT m.id, m.message, m.is_read, m.created_at FROM messages m WHERE $where ORDER BY m.created_at DESC");
$stmt->execute($params);
$messages = $stmt->fetchAll();

$messageIds = array_column($messages, 'id');
$attachmentsByMessage = [];
if (!empty($messageIds)) {
    $placeholders = implode(',', array_fill(0, count($messageIds), '?'));
    $stmt = $pdo->prepare("SELECT id, message_id, file_path, file_type, link_url FROM attachments WHERE message_id IN ($placeholders)");
    $stmt->execute($messageIds);
    foreach ($stmt->fetchAll() as $attachment) {
        $attachmentsByMessage[$attachment['message_id']][] = $attachment;
    }
}

$reportedMessageIds = [];
if (!empty($messageIds)) {
    $stmt = $pdo->prepare("SELECT message_id FROM reports WHERE message_id IN ($placeholders)");
    $stmt->execute($messageIds);
    $reportedMessageIds = array_column($stmt->fetchAll(), 'message_id');
}

$pageTitle = "Messages - UNSAID";
$basePath = "";
include __DIR__ . '/includes/header.php';
?>

<section class="messages-page">
    <div class="section-inner">
        <div class="dashboard-header">
            <div>
                <h1 class="page-title">Your messages</h1>
                <p class="page-subtitle"><?php echo count($messages); ?> message<?php echo count($messages) === 1 ? '' : 's'; ?> found</p>
            </div>
            <a href="dashboard.php" class="btn btn-outline">Back to dashboard</a>
        </div>

        <form method="GET" action="messages.php" class="filter-bar">
            <input type="text" name="search" value="<?php echo e($search); ?>" placeholder="Search messages...">
            <select name="status">
                <option value="all" <?php echo $status === 'all' ? 'selected' : ''; ?>>All</option>
                <option value="unread" <?php echo $status === 'unread' ? 'selected' : ''; ?>>Unread</option>
                <option value="read" <?php echo $status === 'read' ? 'selected' : ''; ?>>Read</option>
            </select>
            <input type="date" name="date" value="<?php echo e($date); ?>">
            <button type="submit" class="btn btn-primary">Search</button>
            <a href="messages.php" class="btn btn-ghost">Reset</a>
        </form>

        <div class="message-list">
            <?php if (empty($messages)): ?>
                <p class="empty-state">No messages match your search yet.</p>
            <?php endif; ?>

            <?php foreach ($messages as $msg): ?>
                <div class="message-card <?php echo $msg['is_read'] ? '' : 'unread'; ?>" data-id="<?php echo (int) $msg['id']; ?>">
                    <div class="message-card-top">
                        <span class="message-sender">Anonymous</span>
                        <span class="message-date"><?php echo e(time_ago($msg['created_at'])); ?></span>
                    </div>
                    <p class="message-text"><?php echo nl2br(e($msg['message'])); ?></p>

                    <?php if (!empty($attachmentsByMessage[$msg['id']])): ?>
                        <div class="message-attachments">
                            <?php foreach ($attachmentsByMessage[$msg['id']] as $att): ?>
                                <?php if ($att['file_type'] === 'link'): ?>
                                    <a href="<?php echo e($att['link_url']); ?>" target="_blank" rel="noopener noreferrer nofollow" class="attachment-link">🔗 <?php echo e($att['link_url']); ?></a>
                                <?php else: ?>
                                    <img src="uploads/<?php echo e($att['file_path']); ?>" alt="attachment" class="attachment-image">
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <div class="message-actions">
                        <?php if ($msg['is_read']): ?>
                            <button class="action-btn" data-action="unread" data-id="<?php echo (int) $msg['id']; ?>">Mark unread</button>
                        <?php else: ?>
                            <button class="action-btn" data-action="read" data-id="<?php echo (int) $msg['id']; ?>">Mark read</button>
                        <?php endif; ?>
                        <button class="action-btn danger" data-action="delete" data-id="<?php echo (int) $msg['id']; ?>">Delete</button>
                        <!--<a href="print-message.php?id=<?php echo (int) $msg['id']; ?>" class="action-btn">Print</a>-->
                        <?php if (in_array($msg['id'], $reportedMessageIds)): ?>
                            <span class="action-btn reported">Reported</span>
                        <?php else: ?>
                            <button class="action-btn danger" data-action="report" data-id="<?php echo (int) $msg['id']; ?>">Report</button>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<input type="hidden" id="csrfToken" value="<?php echo e(csrf_token()); ?>">

<?php include __DIR__ . '/includes/footer.php'; ?>
