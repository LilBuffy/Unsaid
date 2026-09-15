<?php
require_once __DIR__ . '/../includes/auth.php';
require_admin();

$totalUsers = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$totalMessages = $pdo->query("SELECT COUNT(*) FROM messages")->fetchColumn();
$todaySignups = $pdo->query("SELECT COUNT(*) FROM users WHERE DATE(created_at) = CURDATE()")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Dashboard - UNSAID</title>
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

<section class="dashboard">
    <div class="section-inner">
        <h1 class="page-title">Administrator overview</h1>
        <div class="dashboard-stats">
            <div class="stat-card">
                <span class="stat-value"><?php echo (int) $totalUsers; ?></span>
                <span class="stat-label">Registered users</span>
            </div>
            <div class="stat-card">
                <span class="stat-value"><?php echo (int) $totalMessages; ?></span>
                <span class="stat-label">Total messages sent</span>
            </div>
            <div class="stat-card">
                <span class="stat-value"><?php echo (int) $todaySignups; ?></span>
                <span class="stat-label">Signups today</span>
            </div>
        </div>
        <a href="users.php" class="btn btn-primary">Manage users</a>
    </div>
</section>
</body>
</html>
