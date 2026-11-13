<?php
require_once __DIR__ . '/includes/auth.php';

function file_not_found() {
    http_response_code(404);
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'Not found.';
    exit;
}

$name = get_str('f', 100);
$path = stored_upload_path($name);
if ($path === null || !is_file($path)) {
    file_not_found();
}

$public = false;
$stmt = $pdo->prepare("SELECT 1 FROM profiles WHERE profile_image = ? LIMIT 1");
$stmt->execute([$name]);
if ($stmt->fetchColumn()) {
    $public = true;
}

if (!$public) {
    $stmt = $pdo->prepare("SELECT p.user_id, EXISTS(SELECT 1 FROM reports r WHERE r.message_id = m.id) AS reported FROM attachments a JOIN messages m ON m.id = a.message_id JOIN profiles p ON p.id = m.profile_id WHERE a.file_path = ? LIMIT 1");
    $stmt->execute([$name]);
    $owner = $stmt->fetch();
    $allowed = false;
    if ($owner) {
        if (is_logged_in() && (int) $owner['user_id'] === (int) current_user_id()) {
            $allowed = true;
        } elseif (is_admin_logged_in() && (int) $owner['reported'] === 1) {
            $allowed = true;
        }
    }
    if (!$allowed) {
        file_not_found();
    }
}

session_write_close();

$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime = finfo_file($finfo, $path);
finfo_close($finfo);
$allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
if (!is_string($mime) || !in_array($mime, $allowedMimes, true)) {
    file_not_found();
}

header_remove('Pragma');
header_remove('Expires');
header('Content-Type: ' . $mime);
header('Content-Length: ' . filesize($path));
header('Content-Disposition: inline; filename="' . $name . '"');
header("Content-Security-Policy: default-src 'none'; sandbox");
header('X-Content-Type-Options: nosniff');
header('Cross-Origin-Resource-Policy: same-origin');
if ($public) {
    header('Cache-Control: public, max-age=86400');
} else {
    header('Cache-Control: private, no-store');
}
readfile($path);
