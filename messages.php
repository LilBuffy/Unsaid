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

$profileId = (int) $profile['id'];
$hasArchive = archive_ready($pdo);

$tab = get_str('tab', 20) === 'archived' && $hasArchive ? 'archived' : 'inbox';
$search = mb_substr(trim(get_str('search', 400)), 0, 100);
$status = get_str('status', 20);
if (!in_array($status, ['all', 'read', 'unread'], true)) {
    $status = 'all';
}
$date = get_str('date', 20);
if ($date !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}\z/', $date)) {
    $date = '';
}
$openId = (int) get_str('id', 20);

$archivedExpr = $hasArchive ? 'm.is_archived' : '0';

if ($openId > 0) {
    $stmt = $pdo->prepare("SELECT m.id, m.message, m.is_read, m.created_at, $archivedExpr AS is_archived FROM messages m WHERE m.id = ? AND m.profile_id = ?");
    $stmt->execute([$openId, $profileId]);
    $open = $stmt->fetch();
    if (!$open) {
        redirect('messages.php');
    }
    if (!$open['is_read']) {
        $pdo->prepare("UPDATE messages SET is_read = 1 WHERE id = ?")->execute([$openId]);
        $open['is_read'] = 1;
    }
    $tab = $open['is_archived'] ? 'archived' : 'inbox';
    $openAttachments = message_attachments($pdo, [$openId])[$openId] ?? [];
    $stmt = $pdo->prepare("SELECT 1 FROM reports WHERE message_id = ? LIMIT 1");
    $stmt->execute([$openId]);
    $openReported = (bool) $stmt->fetchColumn();
}

$stmt = $pdo->prepare("SELECT COUNT(*) AS total, SUM(CASE WHEN $archivedExpr = 1 THEN 1 ELSE 0 END) AS archived FROM messages m WHERE m.profile_id = ?");
$stmt->execute([$profileId]);
$tabCounts = $stmt->fetch();
$archivedCount = (int) ($tabCounts['archived'] ?? 0);
$inboxCount = (int) ($tabCounts['total'] ?? 0) - $archivedCount;

$messages = [];
$attachmentsByMessage = [];
if ($openId <= 0) {
    $conditions = ["m.profile_id = ?"];
    $params = [$profileId];
    if ($hasArchive) {
        $conditions[] = "m.is_archived = ?";
        $params[] = $tab === 'archived' ? 1 : 0;
    }
    if ($search !== '') {
        $conditions[] = "m.message LIKE ? ESCAPE '!'";
        $params[] = '%' . escape_like($search) . '%';
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
    $stmt = $pdo->prepare("SELECT m.id, m.message, m.is_read, m.created_at FROM messages m WHERE " . implode(' AND ', $conditions) . " ORDER BY m.created_at DESC");
    $stmt->execute($params);
    $messages = $stmt->fetchAll();
    $attachmentsByMessage = message_attachments($pdo, array_column($messages, 'id'));
}

function messages_tab_url($tab) {
    return 'messages.php' . ($tab === 'archived' ? '?tab=archived' : '');
}

$pageTitle = "Messages - UNSAID";
$bodyClass = 'page-app';
$bgMode = 'static';
$bgImages = [6];
include __DIR__ . '/includes/header.php';
?>

<section class="app-page messages-page">
    <div class="section-inner">
        <div class="page-head">
            <div>
                <span class="section-eyebrow">Messages</span>
                <h1 class="page-title">Your messages</h1>
                <?php if ($openId <= 0): ?>
                    <p class="page-subtitle"><?php echo count($messages); ?> message<?php echo count($messages) === 1 ? '' : 's'; ?> found</p>
                <?php endif; ?>
            </div>
            <a href="dashboard.php" class="btn btn-outline">Back to dashboard</a>
        </div>

        <?php if ($hasArchive): ?>
        <div class="tabs" role="tablist">
            <a href="messages.php" class="tab <?php echo $tab === 'inbox' ? 'is-active' : ''; ?>" role="tab" aria-selected="<?php echo $tab === 'inbox' ? 'true' : 'false'; ?>">Inbox <span class="tab-count"><?php echo $inboxCount; ?></span></a>
            <a href="messages.php?tab=archived" class="tab <?php echo $tab === 'archived' ? 'is-active' : ''; ?>" role="tab" aria-selected="<?php echo $tab === 'archived' ? 'true' : 'false'; ?>">Archived <span class="tab-count"><?php echo $archivedCount; ?></span></a>
        </div>
        <?php endif; ?>

        <?php if ($openId > 0): ?>
            <article class="message-detail" data-id="<?php echo (int) $open['id']; ?>" data-tab="<?php echo e($tab); ?>">
                <a href="<?php echo e(messages_tab_url($tab)); ?>" class="back-link">Back to <?php echo $tab === 'archived' ? 'archived' : 'inbox'; ?></a>
                <header class="detail-head">
                    <div>
                        <span class="detail-sender">Anonymous</span>
                        <span class="detail-date"><?php echo e(date('F j, Y g:i A', strtotime($open['created_at']))); ?></span>
                    </div>
                    <span class="status-tag"><?php echo $open['is_read'] ? 'Read' : 'Unread'; ?></span>
                </header>
                <div class="detail-body"><?php echo nl2br(e($open['message'])); ?></div>

                <?php if (!empty($openAttachments)): ?>
                    <div class="message-attachments">
                        <?php foreach ($openAttachments as $att): ?>
                            <?php if ($att['file_type'] === 'link'): ?>
                                <a href="<?php echo e(safe_href($att['link_url'])); ?>" target="_blank" rel="noopener noreferrer nofollow" class="attachment-link">🔗 <?php echo e($att['link_url']); ?></a>
                            <?php else: ?>
                                <img src="<?php echo upload_url($att['file_path']); ?>" alt="attachment" class="attachment-image">
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <div class="detail-actions">
                    <?php if ($hasArchive): ?>
                        <?php if ($open['is_archived']): ?>
                            <button type="button" class="action-btn" data-action="restore" data-id="<?php echo (int) $open['id']; ?>">Restore</button>
                        <?php else: ?>
                            <button type="button" class="action-btn" data-action="archive" data-id="<?php echo (int) $open['id']; ?>">Archive</button>
                        <?php endif; ?>
                    <?php endif; ?>
                    <button type="button" class="action-btn" data-action="unread" data-id="<?php echo (int) $open['id']; ?>">Mark unread</button>
                    <a href="print-message.php?id=<?php echo (int) $open['id']; ?>" target="_blank" rel="noopener" class="action-btn">Print</a>
                    <?php if ($openReported): ?>
                        <span class="action-btn reported">Reported</span>
                    <?php else: ?>
                        <button type="button" class="action-btn danger" data-action="report" data-id="<?php echo (int) $open['id']; ?>">Report</button>
                    <?php endif; ?>
                    <button type="button" class="action-btn danger" data-action="delete" data-id="<?php echo (int) $open['id']; ?>">Delete</button>
                </div>
            </article>
        <?php else: ?>
            <form method="GET" action="messages.php" class="filter-bar">
                <?php if ($tab === 'archived'): ?><input type="hidden" name="tab" value="archived"><?php endif; ?>
                <input type="text" name="search" value="<?php echo e($search); ?>" placeholder="Search messages..." aria-label="Search messages">
                <select name="status" aria-label="Status">
                    <option value="all" <?php echo $status === 'all' ? 'selected' : ''; ?>>All</option>
                    <option value="unread" <?php echo $status === 'unread' ? 'selected' : ''; ?>>Unread</option>
                    <option value="read" <?php echo $status === 'read' ? 'selected' : ''; ?>>Read</option>
                </select>
                <input type="date" name="date" value="<?php echo e($date); ?>" aria-label="Date">
                <button type="submit" class="btn btn-primary">Search</button>
                <a href="<?php echo e(messages_tab_url($tab)); ?>" class="btn btn-ghost">Reset</a>
            </form>

            <form method="GET" action="print-messages.php" target="_blank" id="reportForm">
                <div class="list-toolbar">
                    <label class="checkbox-label"><input type="checkbox" id="selectAll"> Select all</label>
                    <div class="toolbar-actions">
                        <button type="submit" class="btn btn-small btn-primary">Print selected</button>
                        <a href="print-messages.php?all=1" target="_blank" rel="noopener" class="btn btn-small btn-outline">Print all</a>
                    </div>
                </div>

                <div class="message-rows">
                    <?php if (empty($messages)): ?>
                        <p class="empty-state"><?php echo $tab === 'archived' ? 'Nothing is archived.' : 'No messages match your search yet.'; ?></p>
                    <?php endif; ?>
                    <?php foreach ($messages as $msg): ?>
                        <div class="message-row <?php echo $msg['is_read'] ? '' : 'unread'; ?>">
                            <input type="checkbox" name="ids[]" value="<?php echo (int) $msg['id']; ?>" class="report-checkbox" aria-label="Select message">
                            <a href="messages.php?id=<?php echo (int) $msg['id']; ?>" class="row-link">
                                <span class="row-dot" aria-hidden="true"></span>
                                <span class="row-text"><?php echo e(mb_strimwidth(preg_replace('/\s+/u', ' ', $msg['message']), 0, 110, '...')); ?></span>
                                <?php if (!empty($attachmentsByMessage[$msg['id']])): ?><span class="row-attach" title="Has attachment">Attachment</span><?php endif; ?>
                                <span class="row-date"><?php echo e(time_ago($msg['created_at'])); ?></span>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            </form>
        <?php endif; ?>
    </div>
</section>

<input type="hidden" id="csrfToken" value="<?php echo e(csrf_token()); ?>">

<?php include __DIR__ . '/includes/footer.php'; ?>
