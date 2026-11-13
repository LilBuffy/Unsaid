<?php

error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
ini_set('log_errors', '1');

const SESSION_USER_IDLE = 7200;
const SESSION_USER_ABSOLUTE = 86400;
const SESSION_ADMIN_IDLE = 1800;
const SESSION_ADMIN_ABSOLUTE = 28800;

set_exception_handler(function ($e) {
    error_log('Unhandled exception: ' . get_class($e) . ' in ' . basename($e->getFile()) . ':' . $e->getLine());
    if (!headers_sent()) {
        http_response_code(500);
        header('Content-Type: text/plain; charset=UTF-8');
    }
    echo 'Something went wrong. Please try again later.';
});

function request_is_https() {
    if (!empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off') {
        return true;
    }
    if (($_SERVER['SERVER_PORT'] ?? '') === '443') {
        return true;
    }
    return strtolower($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';
}

function is_admin_area_request() {
    $dir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
    return basename($dir) === 'admin';
}

function send_security_headers() {
    if (headers_sent()) {
        return;
    }
    header_remove('X-Powered-By');
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: camera=(), microphone=(), geolocation=(), payment=(), usb=()');
    header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdnjs.cloudflare.com; font-src 'self' https://fonts.gstatic.com https://cdnjs.cloudflare.com; img-src 'self' data:; connect-src 'self'; object-src 'none'; base-uri 'self'; form-action 'self'; frame-ancestors 'none'");
    if (request_is_https()) {
        header('Strict-Transport-Security: max-age=15552000');
    }
    if (is_admin_area_request()) {
        header('X-Robots-Tag: noindex, nofollow');
    }
}

function enforce_session_lifetimes() {
    $now = time();

    if (!empty($_SESSION['user_id'])) {
        if (!isset($_SESSION['user_login_at'], $_SESSION['user_seen_at'])) {
            $_SESSION['user_login_at'] = $now;
            $_SESSION['user_seen_at'] = $now;
        } elseif (($now - (int) $_SESSION['user_seen_at']) > SESSION_USER_IDLE
            || ($now - (int) $_SESSION['user_login_at']) > SESSION_USER_ABSOLUTE) {
            unset($_SESSION['user_id'], $_SESSION['username'], $_SESSION['user_login_at'], $_SESSION['user_seen_at']);
            unset($_SESSION['csrf_token']);
            session_regenerate_id(true);
        } else {
            $_SESSION['user_seen_at'] = $now;
        }
    }

    if (!empty($_SESSION['admin_id'])) {
        if (!isset($_SESSION['admin_login_at'], $_SESSION['admin_seen_at'])) {
            $_SESSION['admin_login_at'] = $now;
            $_SESSION['admin_seen_at'] = $now;
        } elseif (($now - (int) $_SESSION['admin_seen_at']) > SESSION_ADMIN_IDLE
            || ($now - (int) $_SESSION['admin_login_at']) > SESSION_ADMIN_ABSOLUTE) {
            unset($_SESSION['admin_id'], $_SESSION['admin_username'], $_SESSION['admin_login_at'], $_SESSION['admin_seen_at']);
            unset($_SESSION['csrf_token']);
            session_regenerate_id(true);
        } else {
            $_SESSION['admin_seen_at'] = $now;
        }
    }
}

function start_secure_session() {
    if (session_status() !== PHP_SESSION_NONE) {
        return;
    }
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.use_trans_sid', '0');
    ini_set('session.gc_maxlifetime', (string) SESSION_USER_ABSOLUTE);
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => request_is_https(),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
    enforce_session_lifetimes();
}

send_security_headers();
start_secure_session();
