<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/admin_layout.php';
require_admin();

$status = get_str('status', 12);
if (!in_array($status, ['pending', 'reviewed', 'all'], true)) {
    $status = 'pending';
}
$q = mb_substr(trim(get_str('q', 200)), 0, 80);
$profileReady = profile_reports_ready($pdo);
$type = get_str('type', 12);
if (!$profileReady || !in_array($type, ['message', 'profile', 'all'], true)) {
    $type = 'all';
}
$typeSql = $type === 'all' ? '' : ' AND r.report_type = ' . $pdo->quote($type);

$conditions = [];
$params = [];
if ($status !== 'all') {
    $conditions[] = "r.status = ?";
    $params[] = $status;
}
if ($type !== 'all') {
    $conditions[] = "r.report_type = ?";
    $params[] = $type;
}
if ($q !== '') {
    $detailsSearch = $profileReady ? " OR r.details LIKE ? ESCAPE '!'" : '';
    $conditions[] = "(r.reported_message LIKE ? ESCAPE '!' OR r.sender_ip LIKE ? ESCAPE '!' OR u.username LIKE ? ESCAPE '!'" . $detailsSearch . ")";
    $like = '%' . escape_like($q) . '%';
    array_push($params, $like, $like, $like);
    if ($profileReady) {
        $params[] = $like;
    }
}
$where = empty($conditions) ? '' : 'WHERE ' . implode(' AND ', $conditions);

$counts = ['pending' => 0, 'reviewed' => 0];
foreach ($pdo->query("SELECT r.status, COUNT(*) AS c FROM reports r WHERE 1 = 1" . $typeSql . " GROUP BY r.status")->fetchAll() as $row) {
    $counts[$row['status']] = (int) $row['c'];
}
$typeCounts = ['message' => 0, 'profile' => 0];
if ($profileReady) {
    foreach ($pdo->query("SELECT report_type, COUNT(*) AS c FROM reports GROUP BY report_type")->fetchAll() as $row) {
        $typeCounts[$row['report_type']] = (int) $row['c'];
    }
} else {
    $typeCounts['message'] = (int) $pdo->query("SELECT COUNT(*) FROM reports")->fetchColumn();
}
$reasonLabels = profile_report_reasons();

$perPage = 10;
$stmt = $pdo->prepare("SELECT COUNT(*) FROM reports r JOIN profiles p ON p.id = r.profile_id JOIN users u ON u.id = p.user_id $where");
$stmt->execute($params);
$total = (int) $stmt->fetchColumn();
$pages = max(1, (int) ceil($total / $perPage));
$page = min(admin_page_number(), $pages);
$offset = ($page - 1) * $perPage;

$extraCols = $profileReady ? "r.report_type, r.reason, r.details, r.resolution" : "'message' AS report_type, NULL AS reason, NULL AS details, NULL AS resolution";
$stmt = $pdo->prepare("SELECT r.id, r.message_id, r.reported_message, r.sender_ip, r.status, r.created_at, $extraCols, u.id AS recipient_id, u.username AS recipient_username FROM reports r JOIN profiles p ON p.id = r.profile_id JOIN users u ON u.id = p.user_id $where ORDER BY r.created_at DESC LIMIT $perPage OFFSET $offset");
$stmt->execute($params);
$reports = $stmt->fetchAll();

$ipToUsers = [];
foreach ($pdo->query("SELECT rl.ip_address, u2.id AS user_id, u2.username FROM registration_logs rl JOIN users u2 ON u2.id = rl.user_id")->fetchAll() as $row) {
    if (!empty($row['ip_address'])) {
        $ipToUsers[$row['ip_address']][] = ['id' => $row['user_id'], 'username' => $row['username']];
    }
}
$bannedIps = $pdo->query("SELECT ip_address FROM banned_senders")->fetchAll(PDO::FETCH_COLUMN);

$history = [];
$profileIds = [];
foreach ($reports as $row) {
    if ($row['report_type'] === 'profile') {
        $stmt = $pdo->prepare("SELECT id FROM profiles WHERE user_id = ?");
        $stmt->execute([$row['recipient_id']]);
        $profileIds[(int) $row['recipient_id']] = (int) $stmt->fetchColumn();
    }
}
foreach ($profileIds as $userId => $pid) {
    $stmt = $pdo->prepare("SELECT report_type, status, resolution FROM reports WHERE profile_id = ?");
    $stmt->execute([$pid]);
    $entry = ['profile' => 0, 'message' => 0, 'pending' => 0, 'dismissed' => 0];
    foreach ($stmt->fetchAll() as $h) {
        $entry[$h['report_type']]++;
        if ($h['status'] === 'pending') {
            $entry['pending']++;
        }
        if ($h['resolution'] === 'dismissed') {
            $entry['dismissed']++;
        }
    }
    $history[$userId] = $entry;
}

$attachmentsByMessage = message_attachments($pdo, array_filter(array_map(function ($r) { return (int) $r['message_id']; }, $reports)));

admin_header('Reports', 'reports');
?>
<section class="app-page admin-page">
    <div class="section-inner">
        <div class="page-head">
            <div>
                <span class="section-eyebrow">Moderation</span>
                <h1 class="page-title">Reports</h1>
                <p class="page-subtitle">Possible sender matches are based on shared registration IP history and are not guaranteed to be accurate. Profile reports never include any identity or IP match.</p>
            </div>
        </div>

        <?php if ($profileReady): ?>
        <div class="tabs type-tabs">
            <a class="tab <?php echo $type === 'all' ? 'is-active' : ''; ?>" href="reports.php?<?php echo e(http_build_query(['status' => $status, 'type' => 'all'])); ?>">All types <span class="tab-count"><?php echo $typeCounts['message'] + $typeCounts['profile']; ?></span></a>
            <a class="tab <?php echo $type === 'message' ? 'is-active' : ''; ?>" href="reports.php?<?php echo e(http_build_query(['status' => $status, 'type' => 'message'])); ?>">Message reports <span class="tab-count"><?php echo $typeCounts['message']; ?></span></a>
            <a class="tab <?php echo $type === 'profile' ? 'is-active' : ''; ?>" href="reports.php?<?php echo e(http_build_query(['status' => $status, 'type' => 'profile'])); ?>">Profile reports <span class="tab-count"><?php echo $typeCounts['profile']; ?></span></a>
        </div>
        <?php endif; ?>

        <div class="tabs">
            <a class="tab <?php echo $status === 'pending' ? 'is-active' : ''; ?>" href="reports.php?<?php echo e(http_build_query(['status' => 'pending', 'type' => $type])); ?>">Pending <span class="tab-count"><?php echo $counts['pending']; ?></span></a>
            <a class="tab <?php echo $status === 'reviewed' ? 'is-active' : ''; ?>" href="reports.php?<?php echo e(http_build_query(['status' => 'reviewed', 'type' => $type])); ?>">Reviewed <span class="tab-count"><?php echo $counts['reviewed']; ?></span></a>
            <a class="tab <?php echo $status === 'all' ? 'is-active' : ''; ?>" href="reports.php?<?php echo e(http_build_query(['status' => 'all', 'type' => $type])); ?>">All <span class="tab-count"><?php echo $counts['pending'] + $counts['reviewed']; ?></span></a>
        </div>

        <form method="GET" action="reports.php" class="filter-bar">
            <input type="hidden" name="status" value="<?php echo e($status); ?>">
            <input type="hidden" name="type" value="<?php echo e($type); ?>">
            <input type="text" name="q" value="<?php echo e($q); ?>" placeholder="Search message, details, sender IP, or username" aria-label="Search reports">
            <button type="submit" class="btn btn-primary">Search</button>
            <a href="reports.php?<?php echo e(http_build_query(['status' => $status, 'type' => $type])); ?>" class="btn btn-ghost">Reset</a>
        </form>

        <?php if (empty($reports)): ?>
            <p class="empty-state">No reports to show here.</p>
        <?php endif; ?>

        <div class="report-list">
            <?php foreach ($reports as $report): ?>
                <?php if ($report['report_type'] === 'profile'): ?>
                <?php
                    $dismissed = $report['resolution'] === 'dismissed';
                    $hist = $history[(int) $report['recipient_id']] ?? ['profile' => 0, 'message' => 0, 'pending' => 0, 'dismissed' => 0];
                    $reasonLabel = $reasonLabels[$report['reason']] ?? 'Other';
                ?>
                <article class="report-card report-card-profile" id="report-<?php echo (int) $report['id']; ?>">
                    <header class="report-head">
                        <div class="report-meta">
                            <span class="report-id">Report #<?php echo (int) $report['id']; ?></span>
                            <span class="status-pill type-pill">Profile report</span>
                            <span class="status-pill <?php echo $report['status'] === 'pending' ? 'pending' : ''; ?>"><?php echo e($dismissed ? 'Dismissed' : ucfirst($report['status'])); ?></span>
                        </div>
                        <span class="report-time"><?php echo e(date('M j, Y g:i A', strtotime($report['created_at']))); ?></span>
                    </header>

                    <div class="report-body">
                        <div class="report-main">
                            <p class="report-by">Reported profile <a class="text-link" href="users.php?id=<?php echo (int) $report['recipient_id']; ?>">@<?php echo e($report['recipient_username']); ?></a></p>
                            <p class="report-reason"><?php echo e($reasonLabel); ?></p>
                            <?php if (!empty($report['details'])): ?>
                                <blockquote class="report-message"><?php echo nl2br(e($report['details'])); ?></blockquote>
                            <?php else: ?>
                                <p class="match-note">No explanation was added.</p>
                            <?php endif; ?>
                        </div>

                        <dl class="report-facts">
                            <div><dt>Reason</dt><dd><?php echo e($reasonLabel); ?></dd></div>
                            <div><dt>Moderation history</dt><dd><?php echo (int) $hist['profile']; ?> profile, <?php echo (int) $hist['message']; ?> message reports on this account<?php if ($hist['pending'] > 0): ?> (<?php echo (int) $hist['pending']; ?> pending)<?php endif; ?><?php if ($hist['dismissed'] > 0): ?>, <?php echo (int) $hist['dismissed']; ?> dismissed<?php endif; ?></dd></div>
                        </dl>
                    </div>

                    <footer class="report-actions">
                        <?php if ($report['status'] === 'pending'): ?>
                            <form method="POST" action="report-actions.php">
                                <?php echo csrf_field(); echo admin_return_field(); ?>
                                <input type="hidden" name="report_id" value="<?php echo (int) $report['id']; ?>">
                                <input type="hidden" name="report_type" value="profile">
                                <input type="hidden" name="report_action" value="mark_reviewed">
                                <button type="submit" class="btn btn-small">Mark reviewed</button>
                            </form>
                            <form method="POST" action="report-actions.php" data-confirm="Dismiss this profile report? The profile will not be changed.">
                                <?php echo csrf_field(); echo admin_return_field(); ?>
                                <input type="hidden" name="report_id" value="<?php echo (int) $report['id']; ?>">
                                <input type="hidden" name="report_type" value="profile">
                                <input type="hidden" name="report_action" value="dismiss">
                                <button type="submit" class="btn btn-small">Dismiss</button>
                            </form>
                        <?php endif; ?>
                        <a class="btn btn-small btn-outline" href="../profile.php?username=<?php echo urlencode($report['recipient_username']); ?>" target="_blank" rel="noopener">Open profile</a>
                        <a class="btn btn-small btn-outline" href="users.php?id=<?php echo (int) $report['recipient_id']; ?>">View user</a>
                    </footer>
                </article>
                <?php continue; ?>
                <?php endif; ?>
                <?php
                    $matches = $ipToUsers[$report['sender_ip']] ?? [];
                    $hasIp = !empty($report['sender_ip']) && $report['sender_ip'] !== 'unknown';
                    $isBanned = $hasIp && in_array($report['sender_ip'], $bannedIps, true);
                    $reportAttachments = $attachmentsByMessage[$report['message_id']] ?? [];
                ?>
                <article class="report-card" id="report-<?php echo (int) $report['id']; ?>">
                    <header class="report-head">
                        <div class="report-meta">
                            <span class="report-id">Report #<?php echo (int) $report['id']; ?></span>
                            <?php if ($profileReady): ?><span class="status-pill type-pill">Message report</span><?php endif; ?>
                            <span class="status-pill <?php echo $report['status'] === 'pending' ? 'pending' : ''; ?>"><?php echo e(ucfirst($report['status'])); ?></span>
                            <?php if ($isBanned): ?><span class="status-pill banned">Sender banned</span><?php endif; ?>
                        </div>
                        <span class="report-time"><?php echo e(date('M j, Y g:i A', strtotime($report['created_at']))); ?></span>
                    </header>

                    <div class="report-body">
                        <div class="report-main">
                            <p class="report-by">Reported by <a class="text-link" href="users.php?id=<?php echo (int) $report['recipient_id']; ?>">@<?php echo e($report['recipient_username']); ?></a></p>
                            <blockquote class="report-message"><?php echo nl2br(e($report['reported_message'])); ?></blockquote>
                            <?php if (!empty($reportAttachments)): ?>
                                <div class="message-attachments">
                                    <?php foreach ($reportAttachments as $att): ?>
                                        <?php if ($att['file_type'] === 'link'): ?>
                                            <a href="<?php echo e(safe_href($att['link_url'])); ?>" target="_blank" rel="noopener noreferrer nofollow" class="attachment-link"><?php echo e($att['link_url']); ?></a>
                                        <?php elseif (!empty($att['file_path'])): ?>
                                            <a href="<?php echo upload_url($att['file_path']); ?>" target="_blank" rel="noopener noreferrer"><img src="<?php echo upload_url($att['file_path']); ?>" alt="Reported attachment" class="report-attachment-image"></a>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <dl class="report-facts">
                            <div><dt>Sender IP</dt><dd><?php echo e($report['sender_ip'] ?? 'N/A'); ?></dd></div>
                            <div>
                                <dt>Possible match</dt>
                                <dd>
                                    <?php if (empty($matches)): ?>
                                        <span class="match-note">No match found</span>
                                    <?php else: ?>
                                        <div class="chip-row">
                                            <?php foreach ($matches as $match): ?>
                                                <a class="chip" href="users.php?id=<?php echo (int) $match['id']; ?>">@<?php echo e($match['username']); ?></a>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </dd>
                            </div>
                        </dl>
                    </div>

                    <footer class="report-actions">
                        <?php if ($report['status'] === 'pending'): ?>
                            <form method="POST" action="report-actions.php">
                                <?php echo csrf_field(); echo admin_return_field(); ?>
                                <input type="hidden" name="report_id" value="<?php echo (int) $report['id']; ?>">
                                <input type="hidden" name="report_type" value="message">
                                <input type="hidden" name="report_action" value="mark_reviewed">
                                <button type="submit" class="btn btn-small">Mark reviewed</button>
                            </form>
                        <?php endif; ?>

                        <?php if ($report['message_id']): ?>
                            <form method="POST" action="report-actions.php" data-confirm="Delete the reported message? This also removes its attachments.">
                                <?php echo csrf_field(); echo admin_return_field(); ?>
                                <input type="hidden" name="report_id" value="<?php echo (int) $report['id']; ?>">
                                <input type="hidden" name="report_type" value="message">
                                <input type="hidden" name="report_action" value="delete_message">
                                <button type="submit" class="btn btn-small danger">Delete message</button>
                            </form>
                        <?php endif; ?>

                        <?php if ($hasIp): ?>
                            <?php if ($isBanned): ?>
                                <form method="POST" action="report-actions.php" data-confirm="Unban this sender and allow them to send messages again?">
                                    <?php echo csrf_field(); echo admin_return_field(); ?>
                                    <input type="hidden" name="report_action" value="unban_ip">
                                    <input type="hidden" name="ip_address" value="<?php echo e($report['sender_ip']); ?>">
                                    <input type="hidden" name="redirect_to" value="reports">
                                    <button type="submit" class="btn btn-small">Unban sender</button>
                                </form>
                            <?php else: ?>
                                <details class="ban-box">
                                    <summary class="btn btn-small danger">Ban sender</summary>
                                    <form method="POST" action="report-actions.php" class="ban-form" data-confirm="Ban <?php echo e($report['sender_ip']); ?> from sending future anonymous messages?">
                                        <?php echo csrf_field(); echo admin_return_field(); ?>
                                        <input type="hidden" name="report_id" value="<?php echo (int) $report['id']; ?>">
                                        <input type="hidden" name="report_type" value="message">
                                        <input type="hidden" name="report_action" value="ban_ip">
                                        <label for="reason-<?php echo (int) $report['id']; ?>">Reason</label>
                                        <input type="text" id="reason-<?php echo (int) $report['id']; ?>" name="reason" maxlength="255" placeholder="Why is this sender being banned?">
                                        <p class="field-help">The ban applies to this IP address until it is lifted. Bans have no expiry.</p>
                                        <button type="submit" class="btn btn-small danger">Confirm ban</button>
                                    </form>
                                </details>
                            <?php endif; ?>
                        <?php endif; ?>
                    </footer>
                </article>
            <?php endforeach; ?>
        </div>
        <?php echo admin_pager($page, $pages, ['status' => $status, 'type' => $type, 'q' => $q]); ?>
    </div>
</section>
<?php admin_footer(); ?>
