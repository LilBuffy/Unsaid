<?php
require_once __DIR__ . '/includes/auth.php';

unset($_SESSION['admin_id']);
unset($_SESSION['admin_username']);

redirect('admin/login.php');
