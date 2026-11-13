<?php
require_once __DIR__ . '/includes/auth.php';
require_login();

$query = [];
foreach (['search', 'status', 'date'] as $key) {
    $value = get_str($key, 400);
    if ($value !== '') {
        $query[$key] = $value;
    }
}

redirect('messages.php' . (empty($query) ? '' : '?' . http_build_query($query)));
