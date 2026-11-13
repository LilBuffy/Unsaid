<?php

require_once __DIR__ . '/security.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/csrf.php';

define('APP_SECRET', hash('sha256', $DB_HOST . '|' . $DB_NAME . '|' . $DB_USER . '|' . $DB_PASS));

require_once __DIR__ . '/rate_limit.php';
require_once __DIR__ . '/profile_tools.php';
require_once __DIR__ . '/boot.php';

const DUMMY_PASSWORD_HASH = '$2y$10$cACqlYzToxURNp85cYf4jeBC1ukm5AMQ4qF0SRCzlPUAxnKZxW15S';

function validate_session_principals($pdo) {
    if (!empty($_SESSION['user_id'])) {
        $stmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $name = $stmt->fetchColumn();
        $sessionName = $_SESSION['username'] ?? null;
        if ($name === false || !is_string($sessionName) || $name !== $sessionName) {
            unset($_SESSION['user_id'], $_SESSION['username'], $_SESSION['user_login_at'], $_SESSION['user_seen_at']);
            unset($_SESSION['csrf_token']);
        }
    }
    if (!empty($_SESSION['admin_id'])) {
        $stmt = $pdo->prepare("SELECT username FROM administrators WHERE id = ?");
        $stmt->execute([$_SESSION['admin_id']]);
        $name = $stmt->fetchColumn();
        $sessionName = $_SESSION['admin_username'] ?? null;
        if ($name === false || !is_string($sessionName) || $name !== $sessionName) {
            unset($_SESSION['admin_id'], $_SESSION['admin_username'], $_SESSION['admin_login_at'], $_SESSION['admin_seen_at']);
            unset($_SESSION['csrf_token']);
        }
    }
}

validate_session_principals($pdo);

function is_logged_in() {
    return !empty($_SESSION['user_id']);
}

function current_user_id() {
    return $_SESSION['user_id'] ?? null;
}

function require_login() {
    if (!is_logged_in()) {
        redirect('login.php');
    }
}

function is_admin_logged_in() {
    return !empty($_SESSION['admin_id']);
}

function require_admin() {
    if (!is_admin_logged_in()) {
        redirect('login.php');
    }
}

function login_user($userId, $username) {
    session_regenerate_id(true);
    unset($_SESSION['csrf_token']);
    $_SESSION['user_id'] = (int) $userId;
    $_SESSION['username'] = (string) $username;
    $_SESSION['user_login_at'] = time();
    $_SESSION['user_seen_at'] = time();
}

function login_admin($adminId, $username) {
    session_regenerate_id(true);
    unset($_SESSION['csrf_token']);
    $_SESSION['admin_id'] = (int) $adminId;
    $_SESSION['admin_username'] = (string) $username;
    $_SESSION['admin_login_at'] = time();
    $_SESSION['admin_seen_at'] = time();
}

function logout_admin() {
    unset($_SESSION['admin_id'], $_SESSION['admin_username'], $_SESSION['admin_login_at'], $_SESSION['admin_seen_at']);
    unset($_SESSION['csrf_token']);
    session_regenerate_id(true);
}

function authenticate_credentials($pdo, $kind, $username, $password) {
    $isAdmin = $kind === 'admin';
    $prefix = $isAdmin ? 'admin' : 'login';
    $actor = $isAdmin ? 'admin' : 'user';

    $user = false;
    if (strlen($username) <= 100) {
        $sql = $isAdmin
            ? "SELECT id, username, password FROM administrators WHERE username = ?"
            : "SELECT id, username, password FROM users WHERE username = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$username]);
        $user = $stmt->fetch();
    }

    $ipBucket = rl_ip_bucket(client_ip());
    $accountKey = rl_normalize_account($user ? $user['username'] : $username);
    $known = $user ? rate_limit_is_known($pdo, $kind, $ipBucket, $accountKey) : false;

    $begin = rate_limit_begin($pdo, [
        ['policy' => $prefix . '_ip', 'key' => $ipBucket],
        ['policy' => $prefix . '_account', 'key' => $accountKey, 'exempt' => $known],
        ['policy' => $prefix . '_pair', 'key' => $ipBucket . '|' . $accountKey],
    ], false);

    if (!$begin['allowed']) {
        if ($begin['error']) {
            return ['status' => 'error'];
        }
        return ['status' => 'limited', 'retry_after' => $begin['retry_after']];
    }

    $storedHash = $user ? $user['password'] : DUMMY_PASSWORD_HASH;
    $passwordOk = password_verify($password, $storedHash);

    if ($user && $passwordOk) {
        rate_limit_succeed($pdo, $begin, [$prefix . '_pair', $prefix . '_account'], [$prefix . '_ip']);
        rate_limit_mark_known($pdo, $kind, $ipBucket, $accountKey);
        if (password_needs_rehash($storedHash, PASSWORD_DEFAULT)) {
            try {
                $table = $isAdmin ? "UPDATE administrators SET password = ? WHERE id = ?" : "UPDATE users SET password = ? WHERE id = ?";
                $pdo->prepare($table)->execute([password_hash($password, PASSWORD_DEFAULT), $user['id']]);
            } catch (Throwable $e) {
                error_log('Password rehash failed');
            }
        }
        security_log($pdo, $prefix . '_success', $actor, $user['id']);
        return ['status' => 'ok', 'user' => $user];
    }

    security_log($pdo, $prefix . '_failure', 'none', null, 'acct:' . substr(rl_hash($accountKey), 0, 12));

    if ($begin['locked_now']) {
        security_log($pdo, $prefix . '_lockout', 'none', null, 'cooldown:' . $begin['retry_after']);
        return ['status' => 'limited', 'retry_after' => $begin['retry_after']];
    }

    return ['status' => 'invalid'];
}
