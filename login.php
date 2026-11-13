<?php
require_once __DIR__ . '/includes/auth.php';

if (is_logged_in()) {
    redirect('dashboard.php');
}

$errors = [];
$oldUsername = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require();

    $username = trim(post_str('username', 200));
    $password = post_str('password', 2000);
    $oldUsername = mb_substr($username, 0, 100);

    if ($username === '' || $password === '') {
        $errors[] = "Please enter both your username and password.";
    } else {
        $result = authenticate_credentials($pdo, 'user', $username, $password);

        if ($result['status'] === 'ok') {
            login_user($result['user']['id'], $result['user']['username']);
            redirect('dashboard.php');
        } elseif ($result['status'] === 'limited') {
            http_response_code(429);
            header('Retry-After: ' . (int) $result['retry_after']);
            $errors[] = rate_limit_message($result['retry_after']);
        } elseif ($result['status'] === 'error') {
            http_response_code(503);
            header('Retry-After: 30');
            $errors[] = "Login is temporarily unavailable. Please try again shortly.";
        } else {
            $errors[] = "Invalid username or password.";
        }
    }
}

$pageTitle = "Log in - UNSAID";
$basePath = "";
$bgMode = 'static';
$bodyClass = 'page-auth';
$bgImages = [4];
include __DIR__ . '/includes/header.php';
?>

<section class="auth-section auth-login">
    <div class="auth-card">
        <span class="auth-brand">UNSAID</span>
        <h1 class="auth-title">Welcome back</h1>
        <p class="auth-subtitle">Log in ka na, baka may nagkalat na tungkol sa’yo.</p>

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
            <input type="text" id="username" name="username" value="<?php echo e($oldUsername); ?>" required placeholder="yourname">

            <label for="password">Password</label>
            <input type="password" id="password" name="password" required placeholder="Your password">

            <button type="submit" class="btn btn-primary btn-block">Log in</button>
        </form>

        <p class="auth-switch">Don't have an account? <a href="register.php">Create one</a></p>
    </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
