<?php
if (!function_exists('e')) { http_response_code(403); exit; }

function admin_header($title, $active) {
    $links = [
        'dashboard' => ['index.php', 'Dashboard'],
        'users' => ['users.php', 'Users'],
        'reports' => ['reports.php', 'Reports'],
        'banned' => ['banned.php', 'Banned'],
    ];
    $base = asset_url('assets/');
    $stageImages = [6];
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex, nofollow">
<?php echo boot_head_html(); ?>
<title><?php echo e($title); ?> - UNSAID Admin</title>
<link rel="icon" type="image/x-icon" href="<?php echo e(asset_url('includes/favicon.ico')); ?>">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
<?php echo bg_preload_links($stageImages); ?>
<link rel="stylesheet" href="<?php echo e($base); ?>css/style.css">
</head>
<body class="admin-body has-stage">
<?php echo bg_stage_html('static', $stageImages); ?>
<nav class="site-nav admin-nav" id="siteNav">
    <div class="nav-inner">
        <a href="index.php" class="brand">UNSAID <span class="admin-badge">Admin</span></a>
        <div class="nav-links" id="navLinks">
            <?php foreach ($links as $key => $link): ?>
                <a href="<?php echo e($link[0]); ?>"<?php echo $active === $key ? ' aria-current="page"' : ''; ?>><?php echo e($link[1]); ?></a>
            <?php endforeach; ?>
        </div>
        <div class="nav-end">
            <a href="../logout-admin.php" class="nav-logout">Logout</a>
            <button class="nav-toggle" id="navToggle" type="button" aria-label="Toggle navigation" aria-expanded="false" aria-controls="navLinks">
                <span></span><span></span><span></span>
            </button>
        </div>
    </div>
</nav>
<main class="site-main admin-main">
<?php
    $notice = flash('admin_notice');
    if ($notice) {
        echo '<div class="section-inner"><div class="alert alert-success" role="status">' . e($notice) . '</div></div>';
    }
    $problem = flash('admin_error');
    if ($problem) {
        echo '<div class="section-inner"><div class="alert alert-error" role="alert">' . e($problem) . '</div></div>';
    }
}

function admin_footer() {
    ?>
</main>
<footer class="admin-footer">
    <div class="section-inner"><span>UNSAID Admin</span><span>Restricted area. Actions are logged.</span></div>
</footer>
<script src="<?php echo e(asset_url('assets/js/script.js')); ?>"></script>
</body>
</html>
<?php
}

function admin_page_number() {
    $page = (int) get_str('page', 10);
    return $page > 0 ? min($page, 100000) : 1;
}

function admin_pager($page, $pages, array $params) {
    if ($pages <= 1) {
        return '';
    }
    $link = function ($p, $label, $class = '') use ($params) {
        $q = $params;
        $q['page'] = $p;
        return '<a class="pager-link ' . $class . '" href="?' . e(http_build_query($q)) . '">' . e($label) . '</a>';
    };
    $out = '<nav class="pager" aria-label="Pages">';
    $out .= $page > 1 ? $link($page - 1, 'Previous') : '<span class="pager-link is-disabled">Previous</span>';
    $out .= '<span class="pager-info">Page ' . (int) $page . ' of ' . (int) $pages . '</span>';
    $out .= $page < $pages ? $link($page + 1, 'Next') : '<span class="pager-link is-disabled">Next</span>';
    return $out . '</nav>';
}

function admin_return_field() {
    $qs = (string) ($_SERVER['QUERY_STRING'] ?? '');
    return '<input type="hidden" name="back" value="' . e(substr($qs, 0, 300)) . '">';
}

function admin_back_url($page, array $allowedKeys) {
    $raw = post_str('back', 300);
    $parsed = [];
    parse_str($raw, $parsed);
    $clean = [];
    foreach ($allowedKeys as $key) {
        if (isset($parsed[$key]) && is_string($parsed[$key]) && $parsed[$key] !== '') {
            $clean[$key] = mb_substr($parsed[$key], 0, 100);
        }
    }
    return $page . (empty($clean) ? '' : '?' . http_build_query($clean));
}
