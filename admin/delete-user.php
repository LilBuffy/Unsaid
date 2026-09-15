<?php
require_once __DIR__ . '/../includes/auth.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('users.php');
}

csrf_require();

$userId = (int) ($_POST['user_id'] ?? 0);

if ($userId <= 0) {
    redirect('users.php');
}

$stmt = $pdo->prepare("SELECT id FROM users WHERE id = ?");
$stmt->execute([$userId]);
if (!$stmt->fetch()) {
    redirect('users.php');
}

$stmt = $pdo->prepare("SELECT id FROM profiles WHERE user_id = ?");
$stmt->execute([$userId]);
$profile = $stmt->fetch();

if ($profile) {
    $stmt = $pdo->prepare("SELECT file_path FROM attachments WHERE message_id IN (SELECT id FROM messages WHERE profile_id = ?) AND file_path IS NOT NULL");
    $stmt->execute([$profile['id']]);
    foreach ($stmt->fetchAll() as $att) {
        $filePath = __DIR__ . '/../uploads/' . $att['file_path'];
        if (is_file($filePath)) {
            unlink($filePath);
        }
    }

    $stmt = $pdo->prepare("SELECT profile_image FROM profiles WHERE id = ?");
    $stmt->execute([$profile['id']]);
    $img = $stmt->fetchColumn();
    if ($img) {
        $imgPath = __DIR__ . '/../uploads/' . $img;
        if (is_file($imgPath)) {
            unlink($imgPath);
        }
    }
}

$stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
$stmt->execute([$userId]);

redirect('users.php');
