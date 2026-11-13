<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/admin_layout.php';
require_admin();

$detailId = (int) get_str('id', 20);

if ($detailId > 0) {
    $stmt = $pdo->prepare("SELECT u.id, u.username, u.created_at, p.id AS profile_id, p.display_name, r.ip_address, r.country, r.device, r.browser, r.operating_system FROM users u LEFT JOIN profiles p ON p.user_id = u.id LEFT JOIN registration_logs r ON r.user_id = u.id WHERE u.id = ?");
    $stmt->execute([$detailId]);
    $detail = $stmt->fetch();
    if (!$detail) {
        flash('admin_error', 'That account no longer exists.');
        redirect('users.php');
    }

    $messageCount = 0;
    $unreadCount = 0;
    $reportCount = 0;
    if ($detail['profile_id']) {
        $stmt = $pdo->prepare("SELECT COUNT(*), COALESCE(SUM(is_read = 0), 0) FROM messages WHERE profile_id = ?");
        $stmt->execute([$detail['profile_id']]);
        [$messageCount, $unreadCount] = $stmt->fetch(PDO::FETCH_NUM);
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM reports WHERE profile_id = ?");
        $stmt->execute([$detail['profile_id']]);
        $reportCount = (int) $stmt->fetchColumn();
    }

    $ipBanned = false;
    $sharing = [];
    if (!empty($detail['ip_address'])) {
        $stmt = $pdo->prepare("SELECT 1 FROM banned_senders WHERE ip_address = ?");
        $stmt->execute([$detail['ip_address']]);
        $ipBanned = (bool) $stmt->fetchColumn();
        $stmt = $pdo->prepare("SELECT u.id, u.username FROM registration_logs r JOIN users u ON u.id = r.user_id WHERE r.ip_address = ? AND u.id <> ? ORDER BY u.created_at DESC LIMIT 20");
        $stmt->execute([$detail['ip_address'], $detail['id']]);
        $sharing = $stmt->fetchAll();
    }

    $asSender = [];
    if (!empty($detail['ip_address'])) {
        $stmt = $pdo->prepare("SELECT r.id, r.status, r.created_at FROM reports r WHERE r.sender_ip = ? ORDER BY r.created_at DESC LIMIT 10");
        $stmt->execute([$detail['ip_address']]);
        $asSender = $stmt->fetchAll();
    }

    admin_header('@' . $detail['username'], 'users');
    ?>
<section class="app-page admin-page">
    <div class="section-inner">
        <a href="users.php" class="back-link">Back to users</a>
        <div class="page-head">
            <div>
                <span class="section-eyebrow">User</span>
                <h1 class="page-title">@<?php echo e($detail['username']); ?></h1>
                <p class="page-subtitle"><?php echo e($detail['display_name'] ?? $detail['username']); ?> &middot; registered <?php echo e(date('M j, Y g:i A', strtotime($detail['created_at']))); ?></p>
            </div>
            <div class="head-actions">
                <a class="btn btn-outline" href="../profile.php?username=<?php echo urlencode($detail['username']); ?>" target="_blank" rel="noopener">Open public profile</a>
            </div>
        </div>

        <div class="stat-row">
            <div class="stat"><span class="stat-value"><?php echo (int) $messageCount; ?></span><span class="stat-label">Messages received</span></div>
            <div class="stat"><span class="stat-value"><?php echo (int) $unreadCount; ?></span><span class="stat-label">Unread</span></div>
            <div class="stat"><span class="stat-value"><?php echo $reportCount; ?></span><span class="stat-label">Reports filed</span></div>
            <div class="stat <?php echo $ipBanned ? 'is-alert' : ''; ?>"><span class="stat-value"><?php echo $ipBanned ? 'Banned' : 'Clear'; ?></span><span class="stat-label">Registration IP status</span></div>
        </div>

        <div class="dash-columns admin-columns">
            <div class="panel">
                <h2 class="panel-title">Registration record</h2>
                <dl class="detail-list">
                    <div><dt>IP address</dt><dd><?php echo e($detail['ip_address'] ?? 'Not recorded'); ?></dd></div>
                    <div><dt>Country</dt><dd><?php echo e($detail['country'] ?? 'Not recorded'); ?></dd></div>
                    <div><dt>Device</dt><dd><?php echo e($detail['device'] ?? 'Not recorded'); ?></dd></div>
                    <div><dt>Browser</dt><dd><?php echo e($detail['browser'] ?? 'Not recorded'); ?></dd></div>
                    <div><dt>Operating system</dt><dd><?php echo e($detail['operating_system'] ?? 'Not recorded'); ?></dd></div>
                </dl>
                <?php if (!empty($sharing)): ?>
                    <h3 class="sub-title">Other accounts from the same registration IP</h3>
                    <p class="field-help">Shared IPs are only a hint and are not proof of identity.</p>
                    <div class="chip-row">
                        <?php foreach ($sharing as $other): ?>
                            <a class="chip" href="users.php?id=<?php echo (int) $other['id']; ?>">@<?php echo e($other['username']); ?></a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="panel">
                <h2 class="panel-title">Reports from this IP as a sender</h2>
                <p class="field-help">Reports whose sender IP equals this account's registration IP. This is a possible match, not a confirmed one.</p>
                <?php if (empty($asSender)): ?>
                    <p class="empty-state">No reports match this IP.</p>
                <?php endif; ?>
                <ul class="mini-list">
                    <?php foreach ($asSender as $rep): ?>
                        <li><a class="mini-item" href="reports.php?status=all#report-<?php echo (int) $rep['id']; ?>"><span class="mini-text">Report #<?php echo (int) $rep['id']; ?></span><span class="mini-date"><?php echo e(ucfirst($rep['status'])); ?> &middot; <?php echo e(date('M j, Y', strtotime($rep['created_at']))); ?></span></a></li>
                    <?php endforeach; ?>
                </ul>

                <h2 class="panel-title danger-title">Delete account</h2>
                <p class="field-help">Deletes the account, its messages and its uploads permanently.</p>
                <form method="POST" action="delete-user.php" data-confirm="Delete @<?php echo e($detail['username']); ?> permanently? This cannot be undone.">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="user_id" value="<?php echo (int) $detail['id']; ?>">
                    <button type="submit" class="btn btn-small danger">Delete @<?php echo e($detail['username']); ?></button>
                </form>
            </div>
        </div>
    </div>
</section>
<?php
    admin_footer();
    exit;
}

$q = mb_substr(trim(get_str('q', 200)), 0, 60);
$sort = get_str('sort', 12);
if (!in_array($sort, ['newest', 'oldest', 'name'], true)) {
    $sort = 'newest';
}
$orderBy = ['newest' => 'u.created_at DESC', 'oldest' => 'u.created_at ASC', 'name' => 'u.username ASC'][$sort];

$where = '';
$params = [];
if ($q !== '') {
    $where = "WHERE (u.username LIKE ? ESCAPE '!' OR r.ip_address LIKE ? ESCAPE '!' OR r.country LIKE ? ESCAPE '!')";
    $like = '%' . escape_like($q) . '%';
    $params = [$like, $like, $like];
}

$perPage = 20;
$stmt = $pdo->prepare("SELECT COUNT(*) FROM users u LEFT JOIN registration_logs r ON r.user_id = u.id $where");
$stmt->execute($params);
$total = (int) $stmt->fetchColumn();
$pages = max(1, (int) ceil($total / $perPage));
$page = min(admin_page_number(), $pages);
$offset = ($page - 1) * $perPage;

$stmt = $pdo->prepare("SELECT u.id, u.username, u.created_at, r.ip_address, r.country, r.device, r.browser, r.operating_system, (SELECT COUNT(*) FROM messages m JOIN profiles p ON p.id = m.profile_id WHERE p.user_id = u.id) AS message_count, (r.ip_address IS NOT NULL AND r.ip_address IN (SELECT ip_address FROM banned_senders)) AS ip_banned FROM users u LEFT JOIN registration_logs r ON r.user_id = u.id $where ORDER BY $orderBy LIMIT $perPage OFFSET $offset");
$stmt->execute($params);
$users = $stmt->fetchAll();

admin_header('Users', 'users');
?>
<section class="app-page admin-page">
    <div class="section-inner">
        <div class="page-head">
            <div>
                <span class="section-eyebrow">People</span>
                <h1 class="page-title">Registered users</h1>
                <p class="page-subtitle"><?php echo $total; ?> account<?php echo $total === 1 ? '' : 's'; ?></p>
            </div>
        </div>

        <form method="GET" action="users.php" class="filter-bar">
            <input type="text" name="q" value="<?php echo e($q); ?>" placeholder="Search username, IP, or country" aria-label="Search users">
            <select name="sort" aria-label="Sort">
                <option value="newest" <?php echo $sort === 'newest' ? 'selected' : ''; ?>>Newest first</option>
                <option value="oldest" <?php echo $sort === 'oldest' ? 'selected' : ''; ?>>Oldest first</option>
                <option value="name" <?php echo $sort === 'name' ? 'selected' : ''; ?>>Username A to Z</option>
            </select>
            <button type="submit" class="btn btn-primary">Search</button>
            <a href="users.php" class="btn btn-ghost">Reset</a>
        </form>

        <div class="table-wrap">
            <table class="admin-table">
                <thead>
                <tr>
                    <th>Username</th>
                    <th>Registered</th>
                    <th>Messages</th>
                    <th>IP address</th>
                    <th>Country</th>
                    <th>Device</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
                </thead>
                <tbody>
                <?php if (empty($users)): ?>
                    <tr><td colspan="8" class="empty-cell">No users match.</td></tr>
                <?php endif; ?>
                <?php foreach ($users as $user): ?>
                    <tr>
                        <td data-label="Username"><a class="text-link" href="users.php?id=<?php echo (int) $user['id']; ?>">@<?php echo e($user['username']); ?></a></td>
                        <td data-label="Registered"><?php echo e(date('M j, Y g:i A', strtotime($user['created_at']))); ?></td>
                        <td data-label="Messages"><?php echo (int) $user['message_count']; ?></td>
                        <td data-label="IP address"><?php echo e($user['ip_address'] ?? 'N/A'); ?></td>
                        <td data-label="Country"><?php echo e($user['country'] ?? 'N/A'); ?></td>
                        <td data-label="Device"><?php echo e(trim(($user['device'] ?? '') . ' ' . ($user['browser'] ?? '')) ?: 'N/A'); ?></td>
                        <td data-label="Status"><span class="status-pill <?php echo $user['ip_banned'] ? 'pending' : ''; ?>"><?php echo $user['ip_banned'] ? 'IP banned' : 'Active'; ?></span></td>
                        <td data-label="Actions">
                            <div class="row-actions">
                                <a class="btn btn-small" href="users.php?id=<?php echo (int) $user['id']; ?>">Details</a>
                                <form method="POST" action="delete-user.php" data-confirm="Delete @<?php echo e($user['username']); ?> permanently? This cannot be undone.">
                                    <?php echo csrf_field(); ?>
                                    <input type="hidden" name="user_id" value="<?php echo (int) $user['id']; ?>">
                                    <input type="hidden" name="return" value="users">
                                    <button type="submit" class="btn btn-small danger">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php echo admin_pager($page, $pages, ['q' => $q, 'sort' => $sort]); ?>
    </div>
</section>
<?php admin_footer(); ?>
