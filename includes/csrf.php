<?php

function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field() {
    $token = csrf_token();
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token) . '">';
}

function csrf_verify($token) {
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

function csrf_require() {
    $token = $_POST['csrf_token'] ?? '';
    if (!csrf_verify($token)) {
        http_response_code(403);
        die("Invalid or expired security token. Please go back and try again.");
    }
}
