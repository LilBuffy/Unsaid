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
    $confirmPassword = post_str('confirm_password', 2000);
    $oldUsername = mb_substr($username, 0, 100);

    $usernameCheck = is_valid_username($username);
    if ($usernameCheck !== true) {
        $errors[] = $usernameCheck;
    }

    $passwordCheck = is_valid_password($password);
    if ($passwordCheck !== true) {
        $errors[] = $passwordCheck;
    }

    if ($password !== $confirmPassword) {
        $errors[] = "Passwords do not match.";
    }

    if (empty($errors)) {
        $limit = rate_limit_begin($pdo, [
            ['policy' => 'register_ip', 'key' => rl_ip_bucket(client_ip())],
        ], true);
        if (!$limit['allowed']) {
            http_response_code(429);
            header('Retry-After: ' . (int) $limit['retry_after']);
            $errors[] = rate_limit_message($limit['retry_after']);
        } elseif ($limit['locked_now']) {
            security_log($pdo, 'register_rate_limited');
        }
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetch()) {
            $errors[] = "That username is already taken.";
        }
    }

    if (empty($errors)) {
        $userAgent = substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 500);
        [$browser, $os] = parse_user_agent($userAgent);
        $device = device_type($userAgent);
        $ip = client_ip();
        $country = lookup_country($ip);

        try {
            $pdo->beginTransaction();

            $hash = password_hash($password, PASSWORD_DEFAULT);
            if (tutorial_ready($pdo)) {
                $stmt = $pdo->prepare("INSERT INTO users (username, password, tutorial_pending) VALUES (?, ?, 1)");
            } else {
                $stmt = $pdo->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
            }
            $stmt->execute([$username, $hash]);
            $userId = $pdo->lastInsertId();

            $stmt = $pdo->prepare("INSERT INTO profiles (user_id, display_name) VALUES (?, ?)");
            $stmt->execute([$userId, $username]);

            $stmt = $pdo->prepare("INSERT INTO registration_logs (user_id, ip_address, country, device, browser, operating_system) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$userId, $ip, $country, $device, $browser, $os]);

            $pdo->commit();

            security_log($pdo, 'register', 'user', $userId);
            login_user($userId, $username);

            redirect('dashboard.php');
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            if ($e instanceof PDOException && (string) $e->getCode() === '23000') {
                $errors[] = "That username is already taken.";
            } else {
                $errors[] = "Something went wrong while creating your account. Please try again.";
            }
        }
    }
}

$pageTitle = "Create your account - UNSAID";
$basePath = "";
$bgMode = 'static';
$bodyClass = 'page-auth';
$bgImages = [4];
include __DIR__ . '/includes/header.php';
?>

<section class="auth-section auth-register">
    <div class="auth-card">
        <span class="auth-brand">UNSAID</span>
        <h1 class="auth-title">Create your account</h1>
        <p class="auth-subtitle">Gumawa ka na ng account, saglit lang ’to.</p>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-error">
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo e($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="POST" action="register.php" class="auth-form">
            <?php echo csrf_field(); ?>
            <label for="username">Username</label>
            <input type="text" id="username" name="username" value="<?php echo e($oldUsername); ?>" required maxlength="20" placeholder="yourname">

            <label for="password">Password</label>
            <input type="password" id="password" name="password" required minlength="6" placeholder="At least 6 characters">

            <label for="confirm_password">Confirm password</label>
            <input type="password" id="confirm_password" name="confirm_password" required minlength="6" placeholder="Repeat your password">

            <button type="submit" class="btn btn-primary btn-block">Create account</button>
        </form>

        <p class="auth-switch">Already have an account? <a href="login.php">Log in</a></p>
    </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
