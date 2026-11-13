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
    if (!preg_match('/^[a-zA-Z0-9_]+\z/', $username)) {
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
    if (strlen($password) > 72) {
        return "Password must be at most 72 characters long.";
    }
    return true;
}

function security_config() {
    static $config = null;
    if ($config === null) {
        $config = ['trusted_proxies' => [], 'client_ip_header' => 'HTTP_X_FORWARDED_FOR'];
        $file = __DIR__ . '/../config/security.php';
        if (is_file($file)) {
            $loaded = require $file;
            if (is_array($loaded)) {
                $config = array_merge($config, $loaded);
            }
        }
    }
    return $config;
}

function ip_in_ranges($ip, array $ranges) {
    $packed = @inet_pton($ip);
    if ($packed === false) {
        return false;
    }
    foreach ($ranges as $range) {
        $parts = explode('/', (string) $range, 2);
        $network = @inet_pton($parts[0]);
        if ($network === false || strlen($network) !== strlen($packed)) {
            continue;
        }
        $bits = isset($parts[1]) ? (int) $parts[1] : strlen($network) * 8;
        $bits = max(0, min($bits, strlen($network) * 8));
        $bytes = intdiv($bits, 8);
        $remainder = $bits % 8;
        if ($bytes > 0 && substr($packed, 0, $bytes) !== substr($network, 0, $bytes)) {
            continue;
        }
        if ($remainder > 0) {
            $mask = (0xFF << (8 - $remainder)) & 0xFF;
            if ((ord($packed[$bytes]) & $mask) !== (ord($network[$bytes]) & $mask)) {
                continue;
            }
        }
        return true;
    }
    return false;
}

function client_ip() {
    static $resolved = null;
    if ($resolved !== null) {
        return $resolved;
    }
    $remote = $_SERVER['REMOTE_ADDR'] ?? '';
    if (!filter_var($remote, FILTER_VALIDATE_IP)) {
        $resolved = 'unknown';
        return $resolved;
    }
    $resolved = $remote;
    $config = security_config();
    $trusted = is_array($config['trusted_proxies']) ? $config['trusted_proxies'] : [];
    if (empty($trusted) || !ip_in_ranges($remote, $trusted)) {
        return $resolved;
    }
    $header = (string) $config['client_ip_header'];
    $raw = $_SERVER[$header] ?? '';
    if (!is_string($raw) || $raw === '') {
        return $resolved;
    }
    $candidates = array_map('trim', explode(',', $raw));
    for ($i = count($candidates) - 1; $i >= 0; $i--) {
        if (!filter_var($candidates[$i], FILTER_VALIDATE_IP)) {
            return $resolved;
        }
        if (!ip_in_ranges($candidates[$i], $trusted)) {
            $resolved = $candidates[$i];
            return $resolved;
        }
    }
    return $resolved;
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
    $imageTypes = [
        IMAGETYPE_JPEG => 'image/jpeg',
        IMAGETYPE_PNG => 'image/png',
        IMAGETYPE_GIF => 'image/gif',
        IMAGETYPE_WEBP => 'image/webp',
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

    if (!isset($file['tmp_name']) || !is_string($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
        return ['ok' => false, 'error' => 'Invalid upload.'];
    }

    $maxSize = 2 * 1024 * 1024;
    $size = filesize($file['tmp_name']);
    if ($size === false || $size <= 0) {
        return ['ok' => false, 'error' => 'File upload failed.'];
    }
    if ($size > $maxSize) {
        return ['ok' => false, 'error' => 'File is too large. Maximum size is 2MB.'];
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!is_string($mime) || !isset($allowedTypes[$mime])) {
        return ['ok' => false, 'error' => 'Only JPG, PNG, GIF, and WEBP files are allowed.'];
    }

    $info = @getimagesize($file['tmp_name']);
    if (!is_array($info) || !isset($info[0], $info[1], $info[2]) || !isset($imageTypes[$info[2]]) || $imageTypes[$info[2]] !== $mime) {
        return ['ok' => false, 'error' => 'Only JPG, PNG, GIF, and WEBP files are allowed.'];
    }

    if ($info[0] < 1 || $info[1] < 1 || $info[0] > 8000 || $info[1] > 8000 || ($info[0] * $info[1]) > 40000000) {
        return ['ok' => false, 'error' => 'Image dimensions are too large.'];
    }

    $extension = $allowedTypes[$mime];
    $safeName = bin2hex(random_bytes(16)) . '.' . $extension;

    return [
        'ok' => true,
        'skip' => false,
        'extension' => $extension,
        'mime' => $mime,
        'safe_name' => $safeName,
        'size' => $size,
    ];
}

function is_valid_url($url) {
    if ($url === '' || $url === null) return true;
    if (!is_string($url) || strlen($url) > 500) {
        return false;
    }
    if (preg_match('/[\x00-\x20\x7F]/', $url)) {
        return false;
    }
    if (!filter_var($url, FILTER_VALIDATE_URL)) {
        return false;
    }
    $parts = parse_url($url);
    if (!is_array($parts) || empty($parts['host']) || isset($parts['user']) || isset($parts['pass'])) {
        return false;
    }
    return in_array(strtolower($parts['scheme'] ?? ''), ['http', 'https'], true);
}

function safe_href($url) {
    if (!is_string($url) || !is_valid_url($url) || $url === '') {
        return '#';
    }
    return $url;
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

function app_base() {
    static $base = null;
    if ($base !== null) {
        return $base;
    }
    $root = str_replace('\\', '/', realpath(dirname(__DIR__)));
    $scriptDir = realpath(dirname($_SERVER['SCRIPT_FILENAME'] ?? ''));
    $scriptDir = $scriptDir ? str_replace('\\', '/', $scriptDir) : $root;
    $base = '';
    if ($root !== '' && $scriptDir !== $root && strpos($scriptDir, $root) === 0) {
        $relative = trim(substr($scriptDir, strlen($root)), '/');
        if ($relative !== '') {
            $base = str_repeat('../', count(explode('/', $relative)));
        }
    }
    return $base;
}

function asset_url($path) {
    return app_base() . ltrim($path, '/');
}

function upload_url($fileName) {
    return app_base() . 'file.php?f=' . rawurlencode($fileName);
}

function is_stored_filename($name) {
    return is_string($name) && preg_match('/^[a-f0-9]{32}\.(jpg|png|gif|webp)\z/', $name) === 1;
}

function stored_upload_path($name) {
    if (!is_stored_filename($name)) {
        return null;
    }
    return dirname(__DIR__) . '/uploads/' . $name;
}

function delete_stored_upload($name) {
    $path = stored_upload_path($name);
    if ($path !== null && is_file($path)) {
        @unlink($path);
    }
}

function input_string(array $source, $key, $maxBytes = 10000) {
    $value = $source[$key] ?? '';
    if (!is_string($value)) {
        return '';
    }
    if (strlen($value) > $maxBytes) {
        return substr($value, 0, $maxBytes);
    }
    return $value;
}

function post_str($key, $maxBytes = 10000) {
    return input_string($_POST, $key, $maxBytes);
}

function get_str($key, $maxBytes = 1000) {
    return input_string($_GET, $key, $maxBytes);
}

function site_url($path = '') {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
    if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
        $protocol = strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https' ? 'https://' : 'http://';
    }
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    if (!is_string($host) || !preg_match('/^[A-Za-z0-9.\-]+(:[0-9]{1,5})?\z/', $host)) {
        $host = 'localhost';
    }
    $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
    $depth = substr_count(app_base(), '../');
    while ($depth > 0 && $scriptDir !== '' && $scriptDir !== '/' && $scriptDir !== '.') {
        $scriptDir = dirname($scriptDir);
        $depth--;
    }
    $scriptDir = str_replace('\\', '/', $scriptDir);
    if ($scriptDir === '/' || $scriptDir === '.' || $scriptDir === '\\') {
        $scriptDir = '';
    }
    return $protocol . $host . $scriptDir . '/' . ltrim($path, '/');
}

function profile_url($username) {
    return site_url('profile.php?username=' . urlencode($username));
}
