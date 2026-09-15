<?php if (!isset($pageTitle)) { $pageTitle = "UNSAID"; } ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo e($pageTitle); ?></title>
<link rel="icon" type="image/x-icon" href="<?php echo isset($basePath) ? $basePath : ''; ?>includes/favicon.ico">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?php echo isset($basePath) ? $basePath : ''; ?>assets/css/style.css">
</head>
<body>
<nav class="site-nav">
    <div class="nav-inner">
        <a href="<?php echo isset($basePath) ? $basePath : ''; ?>index.php" class="brand">UNSAID</a>
        <button class="nav-toggle" id="navToggle" aria-label="Toggle navigation">
            <span></span><span></span><span></span>
        </button>
        <div class="nav-links" id="navLinks">
            <a href="<?php echo isset($basePath) ? $basePath : ''; ?>index.php">Home</a>
            <a href="<?php echo isset($basePath) ? $basePath : ''; ?>about.php">About</a>
            <?php if (is_logged_in()): ?>
                <a href="<?php echo isset($basePath) ? $basePath : ''; ?>dashboard.php" class="nav-cta">Dashboard</a>
            <?php else: ?>
                <a href="<?php echo isset($basePath) ? $basePath : ''; ?>login.php">Login</a>
                <a href="<?php echo isset($basePath) ? $basePath : ''; ?>register.php">Register</a>
            <?php endif; ?>
        </div>
    </div>
</nav>
