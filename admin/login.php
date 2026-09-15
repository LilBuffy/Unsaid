<?php
require_once __DIR__ . '/../includes/auth.php';

if (is_admin_logged_in()) {
    redirect('index.php');
}

$errors = [];
$oldUsername = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require();

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $oldUsername = $username;

    if ($username === '' || $password === '') {
        $errors[] = "Please enter both fields.";
    } else {
        $stmt = $pdo->prepare("SELECT id, username, password FROM administrators WHERE username = ?");
        $stmt->execute([$username]);
        $admin = $stmt->fetch();

        if (!$admin || !password_verify($password, $admin['password'])) {
            $errors[] = "Invalid administrator credentials.";
        } else {
            session_regenerate_id(true);
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_username'] = $admin['username'];
            redirect('index.php');
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
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<section class="auth-section admin-auth">
    <div class="auth-card">
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
