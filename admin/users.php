<?php
require_once __DIR__ . '/../includes/auth.php';
require_admin();

$stmt = $pdo->query("SELECT u.id, u.username, u.created_at, r.ip_address, r.country, r.device, r.browser, r.operating_system FROM users u LEFT JOIN registration_logs r ON r.user_id = u.id ORDER BY u.created_at DESC");
$users = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Manage Users - UNSAID Admin</title>
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
        <h1 class="page-title">Registered users</h1>

        <div class="table-wrap">
            <table class="admin-table">
                <thead>
                <tr>
                    <th>Username</th>
                    <th>Registered</th>
                    <th>IP address</th>
                    <th>Country</th>
                    <th>Device</th>
                    <th>Browser</th>
                    <th>OS</th>
                    <th>Action</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?php echo e($user['username']); ?></td>
                        <td><?php echo e(date('M j, Y g:i A', strtotime($user['created_at']))); ?></td>
                        <td><?php echo e($user['ip_address'] ?? '—'); ?></td>
                        <td><?php echo e($user['country'] ?? '—'); ?></td>
                        <td><?php echo e($user['device'] ?? '—'); ?></td>
                        <td><?php echo e($user['browser'] ?? '—'); ?></td>
                        <td><?php echo e($user['operating_system'] ?? '—'); ?></td>
                        <td>
                            <form method="POST" action="delete-user.php" onsubmit="return confirm('Delete this user permanently?');">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="user_id" value="<?php echo (int) $user['id']; ?>">
                                <button type="submit" class="btn btn-small danger">Delete</button>
                            </form>
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
