<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/admin_layout.php';
require_admin();

$totalUsers = (int) $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$totalMessages = (int) $pdo->query("SELECT COUNT(*) FROM messages")->fetchColumn();
$unreadMessages = (int) $pdo->query("SELECT COUNT(*) FROM messages WHERE is_read = 0")->fetchColumn();
$todaySignups = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE DATE(created_at) = CURDATE()")->fetchColumn();
$pendingReports = (int) $pdo->query("SELECT COUNT(*) FROM reports WHERE status = 'pending'")->fetchColumn();
$totalReports = (int) $pdo->query("SELECT COUNT(*) FROM reports")->fetchColumn();
$bannedCount = (int) $pdo->query("SELECT COUNT(*) FROM banned_senders")->fetchColumn();

$typeCol = profile_reports_ready($pdo) ? 'r.report_type, r.reason' : "'message' AS report_type, NULL AS reason";
$reasonLabels = profile_report_reasons();
$pending = $pdo->query("SELECT r.id, r.reported_message, r.sender_ip, r.created_at, $typeCol, u.username AS recipient FROM reports r JOIN profiles p ON p.id = r.profile_id JOIN users u ON u.id = p.user_id WHERE r.status = 'pending' ORDER BY r.created_at DESC LIMIT 5")->fetchAll();
$newest = $pdo->query("SELECT u.id, u.username, u.created_at, r.country FROM users u LEFT JOIN registration_logs r ON r.user_id = u.id ORDER BY u.created_at DESC LIMIT 5")->fetchAll();

admin_header('Dashboard', 'dashboard');
?>
<section class="app-page admin-page">
    <div class="section-inner">
        <div class="page-head">
            <div>
                <span class="section-eyebrow">Administrator</span>
                <h1 class="page-title">Dashboard</h1>
                <p class="page-subtitle">A snapshot of activity across UNSAID.</p>
            </div>
        </div>

        <div class="stat-row stat-row-6">
            <a class="stat" href="users.php"><span class="stat-value"><?php echo $totalUsers; ?></span><span class="stat-label">Registered users</span></a>
            <div class="stat"><span class="stat-value"><?php echo $totalMessages; ?></span><span class="stat-label">Messages sent</span></div>
            <div class="stat"><span class="stat-value"><?php echo $unreadMessages; ?></span><span class="stat-label">Unread messages</span></div>
            <a class="stat" href="users.php?sort=newest"><span class="stat-value"><?php echo $todaySignups; ?></span><span class="stat-label">Signups today</span></a>
            <a class="stat <?php echo $pendingReports > 0 ? 'is-alert' : ''; ?>" href="reports.php?status=pending"><span class="stat-value"><?php echo $pendingReports; ?></span><span class="stat-label">Pending reports</span></a>
            <a class="stat" href="banned.php"><span class="stat-value"><?php echo $bannedCount; ?></span><span class="stat-label">Banned senders</span></a>
        </div>

        <div class="dash-columns admin-columns">
            <div class="panel">
                <div class="panel-head">
                    <h2 class="panel-title">Reports waiting for review</h2>
                    <a href="reports.php" class="text-link">All reports (<?php echo $totalReports; ?>)</a>
                </div>
                <?php if (empty($pending)): ?>
                    <p class="empty-state">No reports are waiting.</p>
                <?php endif; ?>
                <ul class="mini-list">
                    <?php foreach ($pending as $report): ?>
                        <li>
                            <a class="mini-item" href="reports.php?status=pending#report-<?php echo (int) $report['id']; ?>">
                                <?php if ($report['report_type'] === 'profile'): ?>
                                <span class="mini-text">Profile report: <?php echo e($reasonLabels[$report['reason']] ?? 'Other'); ?></span>
                                <span class="mini-date">on @<?php echo e($report['recipient']); ?> &middot; <?php echo e(time_ago($report['created_at'])); ?></span>
                                <?php else: ?>
                                <span class="mini-text"><?php echo e(mb_strimwidth($report['reported_message'], 0, 80, '...')); ?></span>
                                <span class="mini-date">to @<?php echo e($report['recipient']); ?> &middot; <?php echo e(time_ago($report['created_at'])); ?></span>
                                <?php endif; ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <div class="panel">
                <div class="panel-head">
                    <h2 class="panel-title">Newest users</h2>
                    <a href="users.php" class="text-link">All users</a>
                </div>
                <ul class="mini-list">
                    <?php foreach ($newest as $row): ?>
                        <li>
                            <a class="mini-item" href="users.php?id=<?php echo (int) $row['id']; ?>">
                                <span class="mini-text">@<?php echo e($row['username']); ?></span>
                                <span class="mini-date"><?php echo e($row['country'] ?? 'Not recorded'); ?> &middot; <?php echo e(time_ago($row['created_at'])); ?></span>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <!--<div class="action-list">
                    <a href="users.php" class="action-row"><span>Manage users</span><span class="action-hint">Search accounts, view details, delete accounts.</span></a>
                    <a href="reports.php" class="action-row"><span>Review reports</span><span class="action-hint">Inspect message and profile reports, ban senders, delete messages, mark reviewed or dismiss.</span></a>
                    <a href="banned.php" class="action-row"><span>Banned senders</span><span class="action-hint">See who is banned and lift a ban.</span></a>
                </div>-->
            </div>
        </div>
    </div>
</section>
<?php admin_footer(); ?>
