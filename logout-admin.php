<?php
require_once __DIR__ . '/includes/auth.php';

if (is_admin_logged_in()) {
    security_log($pdo, 'admin_logout', 'admin', $_SESSION['admin_id']);
}

logout_admin();

redirect('admin/login.php');
