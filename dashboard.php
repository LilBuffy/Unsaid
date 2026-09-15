<?php
require_once __DIR__ . '/includes/auth.php';
require_login();

$userId = current_user_id();

$stmt = $pdo->prepare("SELECT u.username, p.id as profile_id, p.display_name, p.bio, p.profile_image FROM users u JOIN profiles p ON p.user_id = u.id WHERE u.id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

if (!$user) {
    redirect('logout.php');
}

$stmt = $pdo->prepare("SELECT COUNT(*) as total, SUM(CASE WHEN is_read = 0 THEN 1 ELSE 0 END) as unread FROM messages WHERE profile_id = ?");
$stmt->execute([$user['profile_id']]);
$counts = $stmt->fetch();
$totalMessages = (int) ($counts['total'] ?? 0);
$unreadMessages = (int) ($counts['unread'] ?? 0);

$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$profileLink = $protocol . $host . dirname($_SERVER['SCRIPT_NAME']) . '/profile.php?username=' . urlencode($user['username']);

$pageTitle = "Dashboard - UNSAID";
$linkPath = dirname($_SERVER['SCRIPT_NAME']);
if ($linkPath === '/' || $linkPath === '\\') {
    $linkPath = '';
}
$profileLink = $protocol . $host . $linkPath . '/profile.php?username=' . urlencode($user['username']);
include __DIR__ . '/includes/header.php';
?>

<section class="dashboard">
    <div class="section-inner">
        <div class="dashboard-header">
            <div>
                <h1 class="page-title">Hey, <?php echo e($user['display_name']); ?></h1>
                <p class="page-subtitle">Here's what's happening with your inbox.</p>
            </div>
            <a href="logout.php" class="btn btn-outline">Logout</a>
        </div>

        <div class="dashboard-stats">
            <div class="stat-card">
                <span class="stat-value"><?php echo $totalMessages; ?></span>
                <span class="stat-label">Total messages</span>
            </div>
            <div class="stat-card">
                <span class="stat-value"><?php echo $unreadMessages; ?></span>
                <span class="stat-label">Unread</span>
            </div>
            <div class="stat-card link-stat">
                <span class="stat-label">Your public link</span>
                <div class="link-row">
                    <input type="text" id="profileLink" value="<?php echo e($profileLink); ?>" readonly>
                    <button type="button" class="btn btn-small" onclick="copyProfileLink()">Copy</button>
                </div>
            </div>
        </div>

        <div class="dashboard-grid">
            <a href="messages.php" class="dashboard-tile">
                <h3>Messages</h3>
                <p>View, search, and manage everything you've received.</p>
            </a>
            <a href="edit-profile.php" class="dashboard-tile">
                <h3>Edit profile</h3>
                <p>Customize your display name, bio, and message form.</p>
            </a>
            <a href="report.php" class="dashboard-tile">
                <h3>Archives</h3>
                <p>Search, filter, and print your messages.</p>
            </a>
            <a href="profile.php?username=<?php echo urlencode($user['username']); ?>" class="dashboard-tile" target="_blank">
                <h3>View public profile</h3>
                <p>See what visitors see when they open your link.</p>
            </a>
        </div>
    </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
