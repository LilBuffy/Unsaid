<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/admin_layout.php';
require_admin();

$q = mb_substr(trim(get_str('q', 200)), 0, 80);
$sort = get_str('sort', 10) === 'oldest' ? 'oldest' : 'newest';

$where = '';
$params = [];
if ($q !== '') {
    $where = "WHERE (b.ip_address LIKE ? ESCAPE '!' OR b.reason LIKE ? ESCAPE '!')";
    $like = '%' . escape_like($q) . '%';
    $params = [$like, $like];
}

$perPage = 20;
$stmt = $pdo->prepare("SELECT COUNT(*) FROM banned_senders b $where");
$stmt->execute($params);
$total = (int) $stmt->fetchColumn();
$pages = max(1, (int) ceil($total / $perPage));
$page = min(admin_page_number(), $pages);
$offset = ($page - 1) * $perPage;
$order = $sort === 'oldest' ? 'b.banned_at ASC' : 'b.banned_at DESC';

$stmt = $pdo->prepare("SELECT b.id, b.ip_address, b.reason, b.banned_at, (SELECT COUNT(*) FROM reports r WHERE r.sender_ip = b.ip_address) AS report_count FROM banned_senders b $where ORDER BY $order LIMIT $perPage OFFSET $offset");
$stmt->execute($params);
$banned = $stmt->fetchAll();

$ipToUsers = [];
foreach ($pdo->query("SELECT rl.ip_address, u.id, u.username FROM registration_logs rl JOIN users u ON u.id = rl.user_id")->fetchAll() as $row) {
    if (!empty($row['ip_address'])) {
        $ipToUsers[$row['ip_address']][] = ['id' => $row['id'], 'username' => $row['username']];
    }
}

admin_header('Banned senders', 'banned');
?>
<section class="app-page admin-page">
    <div class="section-inner">
        <div class="page-head">
            <div>
                <span class="section-eyebrow">Moderation</span>
                <h1 class="page-title">Banned senders</h1>
                <p class="page-subtitle">Unbanning restores the sender's ability to send anonymous messages immediately.</p>
            </div>
        </div>

        <form method="GET" action="banned.php" class="filter-bar">
            <input type="text" name="q" value="<?php echo e($q); ?>" placeholder="Search IP address or reason" aria-label="Search banned senders">
            <select name="sort" aria-label="Sort">
                <option value="newest" <?php echo $sort === 'newest' ? 'selected' : ''; ?>>Newest first</option>
                <option value="oldest" <?php echo $sort === 'oldest' ? 'selected' : ''; ?>>Oldest first</option>
            </select>
            <button type="submit" class="btn btn-primary">Search</button>
            <a href="banned.php" class="btn btn-ghost">Reset</a>
        </form>

        <div class="table-wrap">
            <table class="admin-table">
                <thead>
                <tr>
                    <th>IP address</th>
                    <th>Possible match</th>
                    <th>Reason</th>
                    <th>Reports</th>
                    <th>Banned on</th>
                    <th>Action</th>
                </tr>
                </thead>
                <tbody>
                <?php if (empty($banned)): ?>
                    <tr><td colspan="6" class="empty-cell">No senders are currently banned.</td></tr>
                <?php endif; ?>

                <?php foreach ($banned as $ban): ?>
                    <?php $matches = $ipToUsers[$ban['ip_address']] ?? []; ?>
                    <tr>
                        <td data-label="IP address"><?php echo e($ban['ip_address']); ?></td>
                        <td data-label="Possible match">
                            <?php if (empty($matches)): ?>
                                <span class="match-note">No match found</span>
                            <?php else: ?>
                                <div class="chip-row">
                                    <?php foreach ($matches as $match): ?>
                                        <a class="chip" href="users.php?id=<?php echo (int) $match['id']; ?>">@<?php echo e($match['username']); ?></a>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td data-label="Reason"><?php echo e($ban['reason'] ?? 'N/A'); ?></td>
                        <td data-label="Reports"><?php echo (int) $ban['report_count']; ?></td>
                        <td data-label="Banned on"><?php echo e(date('M j, Y g:i A', strtotime($ban['banned_at']))); ?></td>
                        <td data-label="Action">
                            <form method="POST" action="report-actions.php" data-confirm="Unban this sender and allow them to send messages again?">
                                <?php echo csrf_field(); echo admin_return_field(); ?>
                                <input type="hidden" name="report_action" value="unban_ip">
                                <input type="hidden" name="ip_address" value="<?php echo e($ban['ip_address']); ?>">
                                <input type="hidden" name="redirect_to" value="banned">
                                <button type="submit" class="btn btn-small">Unban</button>
                            </form>
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
