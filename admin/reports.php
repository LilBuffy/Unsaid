<?php
require_once __DIR__ . '/../includes/auth.php';
require_admin();

$stmt = $pdo->query("
    SELECT r.id, r.message_id, r.reported_message, r.sender_ip, r.status, r.created_at,
           u.username AS recipient_username
    FROM reports r
    JOIN profiles p ON p.id = r.profile_id
    JOIN users u ON u.id = p.user_id
    ORDER BY r.created_at DESC
");
$reports = $stmt->fetchAll();

$regRows = $pdo->query("
    SELECT rl.ip_address, u2.id AS user_id, u2.username
    FROM registration_logs rl
    JOIN users u2 ON u2.id = rl.user_id
")->fetchAll();

$ipToUsers = [];
foreach ($regRows as $row) {
    if (empty($row['ip_address'])) {
        continue;
    }
    $ipToUsers[$row['ip_address']][] = ['id' => $row['user_id'], 'username' => $row['username']];
}

$bannedIps = $pdo->query("SELECT ip_address FROM banned_senders")->fetchAll(PDO::FETCH_COLUMN);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Reported Users - UNSAID Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<nav class="site-nav admin-nav">
    <div class="nav-inner">
        <span class="brand">UNSAID <span class="admin-badge">Admin</span></span>
        <div class="nav-links">
            <a href="index.php">Overview</a>
            <a href="users.php">Users</a>
            <a href="reports.php">Reports</a>
            <a href="../logout-admin.php">Logout</a>
        </div>
    </div>
</nav>

<section class="admin-users">
    <div class="section-inner">
        <h1 class="page-title">Reported anonymous senders</h1>
        <p class="page-subtitle">Possible sender matches are based on shared registration IP history and are not guaranteed to be accurate.</p>

        <div class="table-wrap">
            <table class="admin-table">
                <thead>
                <tr>
                    <th>Reported by</th>
                    <th>Message</th>
                    <th>Sender IP</th>
                    <th>Possible match</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
                </thead>
                <tbody>
                <?php if (empty($reports)): ?>
                    <tr><td colspan="7">No reports have been submitted yet.</td></tr>
                <?php endif; ?>

                <?php foreach ($reports as $report): ?>
                    <?php
                        $matches = $ipToUsers[$report['sender_ip']] ?? [];
                        $isBanned = in_array($report['sender_ip'], $bannedIps);
                    ?>
                    <tr>
                        <td><?php echo e($report['recipient_username']); ?></td>
                        <td class="report-message-cell"><?php echo nl2br(e($report['reported_message'])); ?></td>
                        <td><?php echo e($report['sender_ip'] ?? '—'); ?></td>
                        <td>
                            <?php if (empty($matches)): ?>
                                <span class="match-note">No match found</span>
                            <?php else: ?>
                                <?php foreach ($matches as $match): ?>
                                    <div><?php echo e($match['username']); ?></div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="status-pill <?php echo $report['status'] === 'pending' ? 'pending' : ''; ?>">
                                <?php echo e(ucfirst($report['status'])); ?>
                            </span>
                        </td>
                        <td><?php echo e(date('M j, Y g:i A', strtotime($report['created_at']))); ?></td>
                        <td>
                            <div class="report-actions-cell">
                                <?php if ($report['message_id']): ?>
                                    <form method="POST" action="report-actions.php" onsubmit="return confirm('Delete the reported message?');">
                                        <?php echo csrf_field(); ?>
                                        <input type="hidden" name="report_id" value="<?php echo (int) $report['id']; ?>">
                                        <input type="hidden" name="report_action" value="delete_message">
                                        <button type="submit" class="btn btn-small danger">Delete message</button>
                                    </form>
                                <?php endif; ?>

                                <?php if (!empty($report['sender_ip']) && $report['sender_ip'] !== 'unknown'): ?>
                                    <?php if ($isBanned): ?>
                                        <span class="match-note">Sender already banned</span>
                                    <?php else: ?>
                                        <form method="POST" action="report-actions.php" onsubmit="return confirm('Ban this sender from sending future anonymous messages?');">
                                            <?php echo csrf_field(); ?>
                                            <input type="hidden" name="report_id" value="<?php echo (int) $report['id']; ?>">
                                            <input type="hidden" name="report_action" value="ban_ip">
                                            <button type="submit" class="btn btn-small danger">Ban sender</button>
                                        </form>
                                    <?php endif; ?>
                                <?php endif; ?>

                                <?php foreach ($matches as $match): ?>
                                    <form method="POST" action="delete-user.php" onsubmit="return confirm('Delete the account for @<?php echo e($match['username']); ?> permanently?');">
                                        <?php echo csrf_field(); ?>
                                        <input type="hidden" name="user_id" value="<?php echo (int) $match['id']; ?>">
                                        <button type="submit" class="btn btn-small danger">Delete @<?php echo e($match['username']); ?></button>
                                    </form>
                                <?php endforeach; ?>

                                <?php if ($report['status'] === 'pending'): ?>
                                    <form method="POST" action="report-actions.php">
                                        <?php echo csrf_field(); ?>
                                        <input type="hidden" name="report_id" value="<?php echo (int) $report['id']; ?>">
                                        <input type="hidden" name="report_action" value="mark_reviewed">
                                        <button type="submit" class="btn btn-small">Mark reviewed</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>
</body>
</html>
