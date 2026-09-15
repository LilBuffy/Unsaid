<?php

function e($string) {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

function redirect($path) {
    header("Location: " . $path);
    exit;
}

function flash($key, $message = null) {
    if ($message !== null) {
        $_SESSION['flash'][$key] = $message;
        return;
    }
    if (!empty($_SESSION['flash'][$key])) {
        $msg = $_SESSION['flash'][$key];
        unset($_SESSION['flash'][$key]);
        return $msg;
    }
    return null;
}

function reserved_usernames() {
    return [
        'admin', 'administrator', 'login', 'logout', 'register', 'about',
        'dashboard', 'messages', 'profile', 'report', 'uploads', 'assets',
        'config', 'includes', 'database', 'index', 'send-message',
        'message-actions', 'print-message', 'print-messages', 'edit-profile',
        'unsaid', 'api', 'static', 'public', 'root', 'null', 'undefined'
    ];
}

function is_valid_username($username) {
    if (strlen($username) < 3 || strlen($username) > 20) {
        return "Username must be between 3 and 20 characters.";
    }
    if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        return "Username can only contain letters, numbers, and underscores.";
    }
    if (in_array(strtolower($username), reserved_usernames())) {
        return "This username is reserved and cannot be used.";
    }
    return true;
}

function is_valid_password($password) {
    if (strlen($password) < 6) {
        return "Password must be at least 6 characters long.";
    }
    return true;
}

function client_ip() {
    return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
}

function parse_user_agent($userAgent) {
    $browser = 'Unknown Browser';
    $os = 'Unknown OS';

    if (preg_match('/Edg/i', $userAgent)) $browser = 'Edge';
    elseif (preg_match('/OPR|Opera/i', $userAgent)) $browser = 'Opera';
    elseif (preg_match('/Chrome/i', $userAgent)) $browser = 'Chrome';
    elseif (preg_match('/Firefox/i', $userAgent)) $browser = 'Firefox';
    elseif (preg_match('/Safari/i', $userAgent)) $browser = 'Safari';

    if (preg_match('/Windows/i', $userAgent)) $os = 'Windows';
    elseif (preg_match('/Android/i', $userAgent)) $os = 'Android';
    elseif (preg_match('/iPhone|iPad|iOS/i', $userAgent)) $os = 'iOS';
    elseif (preg_match('/Mac OS/i', $userAgent)) $os = 'macOS';
    elseif (preg_match('/Linux/i', $userAgent)) $os = 'Linux';

    return [$browser, $os];
}

function device_type($userAgent) {
    if (preg_match('/Mobile|Android|iPhone/i', $userAgent)) {
        return 'Mobile';
    }
    if (preg_match('/Tablet|iPad/i', $userAgent)) {
        return 'Tablet';
    }
    return 'Desktop';
}

function lookup_country($ip) {
    if ($ip === '127.0.0.1' || $ip === '::1' || $ip === 'unknown') {
        return 'Local';
    }

    $url = 'https://ipwho.is/' . urlencode($ip);

    $context = stream_context_create([
        'http' => [
            'timeout' => 4
        ]
    ]);

    $response = @file_get_contents($url, false, $context);

    if ($response === false) {
        return 'Unknown';
    }

    $data = json_decode($response, true);

    if (!is_array($data) || empty($data['success']) || empty($data['country'])) {
        return 'Unknown';
    }

    return $data['country'];
}

function validate_upload($file) {
    $allowedTypes = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif',
        'image/webp' => 'webp',
    ];

    if (!isset($file['error']) || is_array($file['error'])) {
        return ['ok' => false, 'error' => 'Invalid upload request.'];
    }

    if ($file['error'] === UPLOAD_ERR_NO_FILE) {
        return ['ok' => true, 'skip' => true];
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['ok' => false, 'error' => 'File upload failed.'];
    }

    $maxSize = 2 * 1024 * 1024;
    if ($file['size'] > $maxSize) {
        return ['ok' => false, 'error' => 'File is too large. Maximum size is 2MB.'];
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!isset($allowedTypes[$mime])) {
        return ['ok' => false, 'error' => 'Only JPG, PNG, GIF, and WEBP files are allowed.'];
    }

    if (!is_uploaded_file($file['tmp_name'])) {
        return ['ok' => false, 'error' => 'Invalid upload.'];
    }

    $extension = $allowedTypes[$mime];
    $safeName = bin2hex(random_bytes(16)) . '.' . $extension;

    return [
        'ok' => true,
        'skip' => false,
        'extension' => $extension,
        'mime' => $mime,
        'safe_name' => $safeName,
        'size' => $file['size'],
    ];
}

function is_valid_url($url) {
    if (empty($url)) return true;
    if (!filter_var($url, FILTER_VALIDATE_URL)) {
        return false;
    }
    $scheme = parse_url($url, PHP_URL_SCHEME);
    return in_array($scheme, ['http', 'https']);
}

function check_rate_limit($profileId) {
    $key = 'last_message_' . $profileId;
    $now = time();
    if (isset($_SESSION[$key]) && ($now - $_SESSION[$key]) < 15) {
        return false;
    }
    $_SESSION[$key] = $now;
    return true;
}

function is_ip_banned($pdo, $ip) {
    if (empty($ip) || $ip === 'unknown') {
        return false;
    }
    $stmt = $pdo->prepare("SELECT id FROM banned_senders WHERE ip_address = ?");
    $stmt->execute([$ip]);
    return (bool) $stmt->fetch();
}

function time_ago($datetime) {
    $timestamp = strtotime($datetime);
    $diff = time() - $timestamp;

    if ($diff < 60) return "just now";
    if ($diff < 3600) return floor($diff / 60) . "m ago";
    if ($diff < 86400) return floor($diff / 3600) . "h ago";
    if ($diff < 604800) return floor($diff / 86400) . "d ago";

    return date("M j, Y", $timestamp);
}
