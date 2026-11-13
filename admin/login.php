<?php
require_once __DIR__ . '/../includes/auth.php';

if (is_admin_logged_in()) {
    redirect('index.php');
}

$errors = [];
$oldUsername = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require();

    $username = trim(post_str('username', 200));
    $password = post_str('password', 2000);
    $oldUsername = mb_substr($username, 0, 100);

    if ($username === '' || $password === '') {
        $errors[] = "Please enter both fields.";
    } else {
        $result = authenticate_credentials($pdo, 'admin', $username, $password);

        if ($result['status'] === 'ok') {
            login_admin($result['user']['id'], $result['user']['username']);
            redirect('index.php');
        } elseif ($result['status'] === 'limited') {
            http_response_code(429);
            header('Retry-After: ' . (int) $result['retry_after']);
            $errors[] = rate_limit_message($result['retry_after']);
        } elseif ($result['status'] === 'error') {
            http_response_code(503);
            header('Retry-After: 30');
            $errors[] = "Login is temporarily unavailable. Please try again shortly.";
        } else {
            $errors[] = "Invalid administrator credentials.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Administrator Login - UNSAID</title>
<meta name="robots" content="noindex, nofollow">
<?php echo boot_head_html(); ?>
<link rel="icon" type="image/x-icon" href="<?php echo asset_url('includes/favicon.ico'); ?>">
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
<?php echo bg_preload_links([4]); ?>
<link rel="stylesheet" href="<?php echo asset_url('assets/css/style.css'); ?>">
</head>
<body class="admin-login page-auth has-stage">
<?php echo bg_stage_html('static', [4]); ?>
<section class="auth-section admin-auth">
    <div class="auth-card">
        <span class="auth-brand">UNSAID ADMIN</span>
        <h1 class="auth-title">Administrator Login</h1>
        <p class="auth-subtitle">Restricted access.</p>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-error">
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo e($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="POST" action="login.php" class="auth-form">
            <?php echo csrf_field(); ?>
            <label for="username">Username</label>
            <input type="text" id="username" name="username" value="<?php echo e($oldUsername); ?>" required>

            <label for="password">Password</label>
            <input type="password" id="password" name="password" required>

            <button type="submit" class="btn btn-primary btn-block">Log in</button>
        </form>
    </div>
</section>
</body>
</html>
