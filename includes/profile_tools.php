<?php
if (!function_exists('e')) { http_response_code(403); exit; }

const SUCCESS_TEXT_MAX = 150;
const DEFAULT_SUCCESS_TEXT = 'It was delivered anonymously. No name attached.';
const DEFAULT_SUCCESS_ALERT = 'Your message was sent anonymously.';
const VIEW_DEDUPE_SECONDS = 1800;
const VISITOR_COOKIE = 'unsaid_vid';

function db_has_column($pdo, $table, $column) {
    static $cache = [];
    $key = $table . '.' . $column;
    if (!isset($cache[$key])) {
        try {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?");
            $stmt->execute([$table, $column]);
            $cache[$key] = (int) $stmt->fetchColumn() > 0;
        } catch (Throwable $e) {
            $cache[$key] = false;
        }
    }
    return $cache[$key];
}

function db_has_table($pdo, $table) {
    static $cache = [];
    if (!isset($cache[$table])) {
        try {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?");
            $stmt->execute([$table]);
            $cache[$table] = (int) $stmt->fetchColumn() > 0;
        } catch (Throwable $e) {
            $cache[$table] = false;
        }
    }
    return $cache[$table];
}

function success_effect_options() {
    return [
        'confirm' => ['Confirmation', 'A quiet check mark card, the default.'],
        'hearts' => ['Floating hearts', 'Soft hearts drift upward.'],
        'emojis' => ['Gentle emojis', 'A few friendly emojis float up.'],
        'sparkles' => ['Sparkles', 'Small light particles rise and fade.'],
        'none' => ['None', 'Only the confirmation text.'],
    ];
}

function normalize_success_effect($value) {
    $value = is_string($value) ? $value : '';
    return array_key_exists($value, success_effect_options()) ? $value : 'confirm';
}

function clean_success_text($text) {
    $text = trim(preg_replace('/\s+/u', ' ', (string) $text));
    return $text;
}

function success_settings_ready($pdo) {
    return db_has_column($pdo, 'profiles', 'success_message') && db_has_column($pdo, 'profiles', 'success_effect');
}

function profile_success_settings(array $profile) {
    $text = isset($profile['success_message']) ? clean_success_text($profile['success_message']) : '';
    if ($text === '' || mb_strlen($text) > SUCCESS_TEXT_MAX) {
        $text = '';
    }
    return [
        'text' => $text,
        'effect' => normalize_success_effect($profile['success_effect'] ?? 'confirm'),
    ];
}

function archive_ready($pdo) {
    return db_has_column($pdo, 'messages', 'is_archived');
}

function analytics_ready($pdo) {
    return db_has_table($pdo, 'profile_views');
}

function request_looks_automated() {
    $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    if ($method !== 'GET') {
        return true;
    }
    $purpose = strtolower((string) ($_SERVER['HTTP_PURPOSE'] ?? $_SERVER['HTTP_SEC_PURPOSE'] ?? $_SERVER['HTTP_X_MOZ'] ?? ''));
    if ($purpose !== '' && (strpos($purpose, 'prefetch') !== false || strpos($purpose, 'preview') !== false)) {
        return true;
    }
    $agent = (string) ($_SERVER['HTTP_USER_AGENT'] ?? '');
    if ($agent === '') {
        return true;
    }
    return preg_match('/bot|crawl|spider|slurp|facebookexternalhit|embedly|preview|fetch|monitor|headless|curl|wget|python-requests|httpclient/i', $agent) === 1;
}

function visitor_token() {
    $token = $_COOKIE[VISITOR_COOKIE] ?? '';
    if (is_string($token) && preg_match('/^[a-f0-9]{32}\z/', $token)) {
        return $token;
    }
    $token = bin2hex(random_bytes(16));
    if (!headers_sent()) {
        setcookie(VISITOR_COOKIE, $token, [
            'expires' => time() + 2592000,
            'path' => '/',
            'secure' => request_is_https(),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }
    $_COOKIE[VISITOR_COOKIE] = $token;
    return $token;
}

function record_profile_view($pdo, $profileId, $ownerUserId) {
    if (!analytics_ready($pdo) || request_looks_automated()) {
        return false;
    }
    if (is_logged_in() && (int) current_user_id() === (int) $ownerUserId) {
        return false;
    }
    if (is_admin_logged_in()) {
        return false;
    }
    $hash = substr(hash_hmac('sha256', visitor_token(), APP_SECRET), 0, 32);
    try {
        $stmt = $pdo->prepare("SELECT 1 FROM profile_views WHERE profile_id = ? AND visitor_hash = ? AND viewed_at > (NOW() - INTERVAL " . VIEW_DEDUPE_SECONDS . " SECOND) LIMIT 1");
        $stmt->execute([$profileId, $hash]);
        if ($stmt->fetchColumn()) {
            return false;
        }
        $pdo->prepare("INSERT INTO profile_views (profile_id, visitor_hash) VALUES (?, ?)")->execute([$profileId, $hash]);
        return true;
    } catch (Throwable $e) {
        error_log('Profile view write failed');
        return false;
    }
}

function profile_view_totals($pdo, $profileId) {
    $totals = ['views' => 0, 'unique' => 0, 'last7' => 0, 'last30' => 0];
    if (!analytics_ready($pdo)) {
        return $totals;
    }
    $stmt = $pdo->prepare("SELECT COUNT(*) AS views, COUNT(DISTINCT visitor_hash) AS uniq, SUM(viewed_at >= (NOW() - INTERVAL 7 DAY)) AS last7, SUM(viewed_at >= (NOW() - INTERVAL 30 DAY)) AS last30 FROM profile_views WHERE profile_id = ?");
    $stmt->execute([$profileId]);
    $row = $stmt->fetch();
    if ($row) {
        $totals = [
            'views' => (int) $row['views'],
            'unique' => (int) $row['uniq'],
            'last7' => (int) $row['last7'],
            'last30' => (int) $row['last30'],
        ];
    }
    return $totals;
}

function profile_view_series($pdo, $profileId, $days) {
    $days = max(1, min(90, (int) $days));
    $series = [];
    for ($i = $days - 1; $i >= 0; $i--) {
        $series[date('Y-m-d', strtotime("-$i day"))] = ['views' => 0, 'unique' => 0];
    }
    if (!analytics_ready($pdo)) {
        return $series;
    }
    $stmt = $pdo->prepare("SELECT DATE(viewed_at) AS d, COUNT(*) AS views, COUNT(DISTINCT visitor_hash) AS uniq FROM profile_views WHERE profile_id = ? AND viewed_at >= (CURDATE() - INTERVAL " . ($days - 1) . " DAY) GROUP BY DATE(viewed_at)");
    $stmt->execute([$profileId]);
    foreach ($stmt->fetchAll() as $row) {
        if (isset($series[$row['d']])) {
            $series[$row['d']] = ['views' => (int) $row['views'], 'unique' => (int) $row['uniq']];
        }
    }
    return $series;
}

function json_for_script($data) {
    return str_replace(['<', '>', '&'], ['<', '>', '&'], json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
}

function message_attachments($pdo, array $messageIds) {
    $map = [];
    if (empty($messageIds)) {
        return $map;
    }
    $placeholders = implode(',', array_fill(0, count($messageIds), '?'));
    $stmt = $pdo->prepare("SELECT id, message_id, file_path, file_type, file_size, link_url FROM attachments WHERE message_id IN ($placeholders)");
    $stmt->execute(array_values($messageIds));
    foreach ($stmt->fetchAll() as $row) {
        $map[$row['message_id']][] = $row;
    }
    return $map;
}

function escape_like($text) {
    return str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $text);
}

function bg_preload_links(array $images) {
    if (empty($images)) {
        return '';
    }
    $base = app_base() . 'assets/backgrounds/';
    $n = (int) $images[0];
    return '<link rel="preload" as="image" href="' . e($base . 'bg-' . $n . '-sm.jpg') . '" media="(max-width: 900px)">' . "\n"
        . '<link rel="preload" as="image" href="' . e($base . 'bg-' . $n . '-lg.jpg') . '" media="(min-width: 901px)">' . "\n";
}

function bg_stage_html($mode, array $images) {
    if ($mode === 'none' || empty($images)) {
        return '';
    }
    $base = app_base() . 'assets/backgrounds/';
    $out = '<div class="bg-stage" id="bgStage" data-mode="' . e($mode) . '" data-base="' . e($base) . '" aria-hidden="true">' . "\n";
    foreach (array_values($images) as $i => $n) {
        $n = (int) $n;
        if ($i === 0) {
            $out .= '<img class="bg-layer is-active" data-bg="' . $n . '" alt="" decoding="async" fetchpriority="high" srcset="' . e($base . 'bg-' . $n . '-sm.jpg') . ' 960w, ' . e($base . 'bg-' . $n . '-lg.jpg') . ' 1920w" sizes="100vw" src="' . e($base . 'bg-' . $n . '-lg.jpg') . '">' . "\n";
        } else {
            $out .= '<img class="bg-layer" data-bg="' . $n . '" alt="" decoding="async">' . "\n";
        }
    }
    return $out . '<div class="bg-shade"></div>' . "\n" . '</div>' . "\n";
}

function profile_report_reasons() {
    return [
        'harassment' => 'Harassment or abusive content',
        'impersonation' => 'Impersonation',
        'inappropriate' => 'Inappropriate profile content',
        'spam' => 'Spam or misleading content',
        'other' => 'Other',
    ];
}

function profile_reports_ready($pdo) {
    return db_has_column($pdo, 'reports', 'report_type')
        && db_has_column($pdo, 'reports', 'reason')
        && db_has_column($pdo, 'reports', 'details')
        && db_has_column($pdo, 'reports', 'reporter_hash')
        && db_has_column($pdo, 'reports', 'resolution');
}

function tutorial_ready($pdo) {
    return db_has_column($pdo, 'users', 'tutorial_pending');
}

function tutorial_pending($pdo, $userId) {
    if (!tutorial_ready($pdo)) {
        return false;
    }
    $stmt = $pdo->prepare("SELECT tutorial_pending FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    return (int) $stmt->fetchColumn() === 1;
}

function profile_reporter_hash($userId) {
    $seed = $userId ? 'u:' . (int) $userId : 'v:' . visitor_token();
    return substr(hash_hmac('sha256', $seed, APP_SECRET), 0, 32);
}

function clean_report_details($text) {
    $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', (string) $text);
    $text = preg_replace('/\R{3,}/u', "\n\n", trim($text));
    return $text;
}

function report_type_expr($pdo, $alias = 'r') {
    return profile_reports_ready($pdo) ? $alias . '.report_type' : "'message'";
}
