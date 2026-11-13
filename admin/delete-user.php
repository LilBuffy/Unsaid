<?php
require_once __DIR__ . '/../includes/auth.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('users.php');
}

csrf_require();

$userId = (int) post_str('user_id', 20);

if ($userId <= 0) {
    redirect('users.php');
}

$stmt = $pdo->prepare("SELECT id, username FROM users WHERE id = ?");
$stmt->execute([$userId]);
$target = $stmt->fetch();
if (!$target) {
    flash('admin_error', 'That account no longer exists.');
    redirect('users.php');
}

$stmt = $pdo->prepare("SELECT id FROM profiles WHERE user_id = ?");
$stmt->execute([$userId]);
$profile = $stmt->fetch();

if ($profile) {
    $stmt = $pdo->prepare("SELECT file_path FROM attachments WHERE message_id IN (SELECT id FROM messages WHERE profile_id = ?) AND file_path IS NOT NULL");
    $stmt->execute([$profile['id']]);
    foreach ($stmt->fetchAll() as $att) {
        delete_stored_upload($att['file_path']);
    }

    $stmt = $pdo->prepare("SELECT profile_image FROM profiles WHERE id = ?");
    $stmt->execute([$profile['id']]);
    $img = $stmt->fetchColumn();
    if ($img) {
        delete_stored_upload($img);
    }
}

$stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
$stmt->execute([$userId]);
security_log($pdo, 'admin_delete_user', 'admin', $_SESSION['admin_id'], 'user:' . $userId);
flash('admin_notice', 'Deleted the account @' . $target['username'] . '.');

redirect(post_str('return', 20) === 'reports' ? 'reports.php' : 'users.php');
