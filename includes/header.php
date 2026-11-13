<?php if (!function_exists('e')) { http_response_code(403); exit; } ?>
<?php
if (!isset($pageTitle)) { $pageTitle = "UNSAID"; }
if (!isset($bgMode)) { $bgMode = 'none'; }
if (!isset($bgImages) || !is_array($bgImages)) { $bgImages = []; }
if (!isset($bodyClass)) { $bodyClass = ''; }
$currentScript = basename($_SERVER['SCRIPT_NAME'] ?? '');
$navCurrent = function ($file) use ($currentScript) {
    return $currentScript === $file ? ' aria-current="page"' : '';
};
$bgClass = $bgMode === 'none' ? '' : ' has-stage';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<?php echo boot_head_html(); ?>
<title><?php echo e($pageTitle); ?></title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
<link rel="icon" type="image/x-icon" href="<?php echo app_base(); ?>includes/favicon.ico">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
<?php echo bg_preload_links($bgImages); ?>
<link rel="stylesheet" href="<?php echo app_base(); ?>assets/css/style.css">
</head>
<body class="<?php echo e(trim($bodyClass . $bgClass)); ?>">
<?php echo bg_stage_html($bgMode, $bgImages); ?>
<nav class="site-nav" id="siteNav">
    <div class="nav-inner">
        <a href="<?php echo app_base(); ?>index.php" class="brand">UNSAID</a>
        <button class="nav-toggle" id="navToggle" type="button" aria-label="Toggle navigation" aria-expanded="false" aria-controls="navLinks">
            <span></span><span></span><span></span>
        </button>
        <div class="nav-links" id="navLinks">
            <a href="<?php echo app_base(); ?>index.php"<?php echo $navCurrent('index.php'); ?>>Home</a>
            <a href="<?php echo app_base(); ?>about.php"<?php echo $navCurrent('about.php'); ?>>About</a>
            <?php if (is_logged_in()): ?>
                <a href="<?php echo app_base(); ?>dashboard.php" class="nav-cta">Dashboard</a>
            <?php else: ?>
                <a href="<?php echo app_base(); ?>login.php"<?php echo $navCurrent('login.php'); ?>>Login</a>
                <a href="<?php echo app_base(); ?>register.php"<?php echo $navCurrent('register.php'); ?>>Register</a>
            <?php endif; ?>
        </div>
    </div>
</nav>
<main class="site-main">
