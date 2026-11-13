<?php
require_once __DIR__ . '/includes/auth.php';
require_login();

$userId = current_user_id();

$stmt = $pdo->prepare("SELECT u.username, p.id as profile_id, p.display_name FROM users u JOIN profiles p ON p.user_id = u.id WHERE u.id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

if (!$user) {
    redirect('logout.php');
}

$hasArchive = archive_ready($pdo);
$archiveSelect = $hasArchive ? ", SUM(CASE WHEN is_archived = 1 THEN 1 ELSE 0 END) as archived" : "";
$stmt = $pdo->prepare("SELECT COUNT(*) as total, SUM(CASE WHEN is_read = 0 THEN 1 ELSE 0 END) as unread" . $archiveSelect . " FROM messages WHERE profile_id = ?");
$stmt->execute([$user['profile_id']]);
$counts = $stmt->fetch();
$totalMessages = (int) ($counts['total'] ?? 0);
$unreadMessages = (int) ($counts['unread'] ?? 0);
$archivedMessages = $hasArchive ? (int) ($counts['archived'] ?? 0) : 0;
$readMessages = $totalMessages - $unreadMessages;

$recentWhere = $hasArchive ? " AND is_archived = 0" : "";
$stmt = $pdo->prepare("SELECT id, message, is_read, created_at FROM messages WHERE profile_id = ?" . $recentWhere . " ORDER BY created_at DESC LIMIT 3");
$stmt->execute([$user['profile_id']]);
$recent = $stmt->fetchAll();

$analyticsOn = analytics_ready($pdo);
$totals = profile_view_totals($pdo, $user['profile_id']);
$series30 = profile_view_series($pdo, $user['profile_id'], 30);
$chartData = [];
foreach ($series30 as $day => $row) {
    $chartData[] = ['d' => $day, 'v' => $row['views'], 'u' => $row['unique']];
}

$profileLink = profile_url($user['username']);

$pageTitle = "Dashboard - UNSAID";
$bodyClass = 'page-app';
$bgMode = 'static';
$bgImages = [6];
$showTutorial = tutorial_pending($pdo, $userId);
$extraScripts = ['assets/js/charts.js'];
if ($showTutorial) {
    $extraScripts[] = 'assets/js/tutorial.js';
}
include __DIR__ . '/includes/header.php';
?>

<section class="app-page dashboard">
    <div class="section-inner">
        <div class="page-head">
            <div class="head-main">
                <span class="section-eyebrow">Dashboard</span>
                <h1 class="page-title">Hey, <?php echo e($user['display_name']); ?></h1>
                <p class="page-subtitle">Here's what's happening with your inbox.</p>
                <div class="link-bar">
                    <span class="link-bar-label">Your public link</span>
                    <input type="text" id="profileLink" value="<?php echo e($profileLink); ?>" readonly aria-label="Your public link">
                    <button type="button" class="btn btn-small" id="copyLinkBtn">Copy</button>
                </div>
            </div>
            <a href="logout.php" class="btn btn-outline">Logout</a>
        </div>

        <div class="stat-row">
            <a class="stat" href="messages.php">
                <span class="stat-value"><?php echo $totalMessages; ?></span>
                <span class="stat-label">Total messages</span>
            </a>
            <a class="stat" href="messages.php?status=unread">
                <span class="stat-value"><?php echo $unreadMessages; ?></span>
                <span class="stat-label">Unread</span>
            </a>
            <a class="stat" href="messages.php?status=read">
                <span class="stat-value"><?php echo $readMessages; ?></span>
                <span class="stat-label">Read</span>
            </a>
            <a class="stat" href="#analytics">
                <span class="stat-value"><?php echo $totals['views']; ?></span>
                <span class="stat-label">Link visits</span>
            </a>
        </div>

        <div class="main-actions">
            <a href="messages.php" class="main-action">
                <span class="main-action-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 5h16v12H7l-3 3z"></path></svg></span>
                <span class="main-action-title">Messages</span>
                <span class="main-action-text">View, search, and manage everything you've received.</span>
            </a>
            <a href="edit-profile.php" class="main-action">
                <span class="main-action-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 20h4l10-10-4-4L4 16z"></path><path d="M14 6l4 4"></path></svg></span>
                <span class="main-action-title">Edit profile</span>
                <span class="main-action-text">Customize your display name, bio, and message form.</span>
            </a>
            <a href="profile.php?username=<?php echo urlencode($user['username']); ?>" class="main-action" target="_blank" rel="noopener">
                <span class="main-action-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="8" r="3.4"></circle><path d="M5 20c1.5-4 4-6 7-6s5.5 2 7 6"></path></svg></span>
                <span class="main-action-title">View public profile</span>
                <span class="main-action-text">See what visitors see when they open your link.</span>
            </a>
        </div>

        <div class="dash-split">
        <div class="panel latest">
            <div class="panel-head">
                <h2 class="panel-title">Latest messages</h2>
                <a href="messages.php" class="text-link">Open messages</a>
            </div>
            <?php if (empty($recent)): ?>
                <p class="empty-state">Nothing yet. Share your link and wait.</p>
            <?php endif; ?>
            <ul class="mini-list">
                <?php foreach ($recent as $msg): ?>
                    <li>
                        <a href="messages.php?id=<?php echo (int) $msg['id']; ?>" class="mini-item <?php echo $msg['is_read'] ? '' : 'unread'; ?>">
                            <span class="mini-dot" aria-hidden="true"></span>
                            <span class="mini-text"><?php echo e(mb_strimwidth(preg_replace('/\s+/u', ' ', $msg['message']), 0, 110, '...')); ?></span>
                            <span class="mini-date"><?php echo e(time_ago($msg['created_at'])); ?></span>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>

        <div class="panel analytics" id="analytics">
            <div class="panel-head">
                <h2 class="panel-title">Link visits</h2>
                <div class="range-toggle" role="group" aria-label="Chart range">
                    <button type="button" class="range-btn is-active" data-range="7">7 days</button>
                    <button type="button" class="range-btn" data-range="30">30 days</button>
                </div>
            </div>
            <?php if ($analyticsOn): ?>
                <div class="analytics-body">
                    <div class="analytics-figures">
                        <div><span class="figure-value"><?php echo $totals['views']; ?></span><span class="figure-label">Link visits</span></div>
                        <div><span class="figure-value"><?php echo $totals['unique']; ?></span><span class="figure-label">Estimated unique visitors</span></div>
                        <div><span class="figure-value" id="rangeTotal"><?php echo $totals['last7']; ?></span><span class="figure-label" id="rangeLabel">Last 7 days</span></div>
                    </div>
                    <div class="chart" id="visitChart" role="img" aria-label="Daily link visits"></div>
                </div>
                <script type="application/json" id="chartData"><?php echo json_for_script($chartData); ?></script>
                <p class="panel-note">A visit is one page load of your public profile. Reloads by the same browser within 30 minutes, your own visits, bots and admins are not counted. Unique visitors are estimated from an anonymous browser token kept for 30 days, so people who clear cookies or switch devices can be counted more than once.</p>
            <?php else: ?>
                <p class="empty-state">Link visit analytics will appear here once the V3 database update has been applied.</p>
            <?php endif; ?>
        </div>
        </div>
    </div>
</section>

<?php if ($showTutorial): ?>
<div class="tour" id="tour" role="dialog" aria-modal="false" aria-labelledby="tourTitle" data-archive="<?php echo $hasArchive ? '1' : '0'; ?>" data-visits="<?php echo $analyticsOn ? '1' : '0'; ?>" data-effects="<?php echo success_settings_ready($pdo) ? '1' : '0'; ?>" hidden>
    <input type="hidden" id="tourToken" value="<?php echo e(csrf_token()); ?>">
    <div class="tour-card">
        <div class="tour-top">
            <span class="tour-step" id="tourStep"></span>
            <button type="button" class="tour-skip" id="tourSkip">Skip</button>
        </div>
        <h2 class="tour-title" id="tourTitle"></h2>
        <p class="tour-text" id="tourText"></p>
        <p class="tour-error" id="tourError" role="alert" hidden></p>
        <div class="tour-dots" id="tourDots" aria-hidden="true"></div>
        <div class="tour-actions">
            <button type="button" class="btn btn-small btn-outline" id="tourBack">Back</button>
            <button type="button" class="btn btn-small btn-primary" id="tourNext">Next</button>
        </div>
    </div>
</div>
<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>
