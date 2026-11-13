<?php

function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field() {
    $token = csrf_token();
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
}

function csrf_verify($token) {
    if (!is_string($token) || $token === '') {
        return false;
    }
    if (empty($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

function csrf_require() {
    $token = $_POST['csrf_token'] ?? '';
    if (!csrf_verify($token)) {
        if (function_exists('is_admin_logged_in') && is_admin_logged_in()) {
            global $pdo;
            security_log($pdo, 'admin_csrf_failure', 'admin', $_SESSION['admin_id']);
        }
        http_response_code(403);
        die("Invalid or expired security token. Please go back and try again.");
    }
}
