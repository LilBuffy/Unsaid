<?php
require_once __DIR__ . '/includes/auth.php';

if (is_logged_in()) {
    redirect('dashboard.php');
}

$errors = [];
$oldUsername = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require();

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $oldUsername = $username;

    if ($username === '' || $password === '') {
        $errors[] = "Please enter both your username and password.";
    } else {
        $stmt = $pdo->prepare("SELECT id, username, password FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password'])) {
            $errors[] = "Invalid username or password.";
        } else {
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            redirect('dashboard.php');
        }
    }
}

$pageTitle = "Log in - UNSAID";
$basePath = "";
include __DIR__ . '/includes/header.php';
?>

<section class="auth-section">
    <div class="auth-card">
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
