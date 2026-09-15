<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/csrf.php';

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
