<?php
require_once __DIR__ . '/includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('index.php');
}

csrf_require();

$profileId = (int) ($_POST['profile_id'] ?? 0);
$username = trim($_POST['username'] ?? '');
$message = trim($_POST['message'] ?? '');
$attachmentLink = trim($_POST['attachment_link'] ?? '');

$errors = [];

$stmt = $pdo->prepare("SELECT id FROM profiles WHERE id = ? AND user_id = (SELECT id FROM users WHERE username = ?)");
$stmt->execute([$profileId, $username]);
$profile = $stmt->fetch();

if (!$profile) {
    http_response_code(404);
    die("Profile not found.");
}

if (is_ip_banned($pdo, client_ip())) {
    $_SESSION['message_errors'] = ["You are not able to send messages at this time."];
    redirect('profile.php?username=' . urlencode($username));
}

if ($message === '') {
    $errors[] = "Your message can't be empty.";
} elseif (mb_strlen($message) > 500) {
    $errors[] = "Your message is too long. Keep it under 500 characters.";
}

if ($attachmentLink !== '' && !is_valid_url($attachmentLink)) {
    $errors[] = "That link doesn't look valid. Use a full http or https URL.";
}

$uploadResult = null;
if (!empty($_FILES['attachment_file']) && $_FILES['attachment_file']['error'] !== UPLOAD_ERR_NO_FILE) {
    $uploadResult = validate_upload($_FILES['attachment_file']);
    if (!$uploadResult['ok']) {
        $errors[] = $uploadResult['error'];
    }
}

if (!check_rate_limit($profileId)) {
    $errors[] = "You're sending messages too quickly. Please wait a moment and try again.";
}

if (!empty($errors)) {
    $_SESSION['message_errors'] = $errors;
    redirect('profile.php?username=' . urlencode($username));
}

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("INSERT INTO messages (profile_id, message, is_read, sender_ip) VALUES (?, ?, 0, ?)");
    $stmt->execute([$profileId, $message, client_ip()]);
    $messageId = $pdo->lastInsertId();

    if ($uploadResult && $uploadResult['ok'] && empty($uploadResult['skip'])) {
        $destination = __DIR__ . '/uploads/' . $uploadResult['safe_name'];
        if (!move_uploaded_file($_FILES['attachment_file']['tmp_name'], $destination)) {
            throw new Exception("Failed to store uploaded file.");
        }
        $fileType = ($uploadResult['mime'] === 'image/gif') ? 'gif' : 'image';
        $stmt = $pdo->prepare("INSERT INTO attachments (message_id, file_name, file_path, file_type, file_size) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$messageId, $uploadResult['safe_name'], $uploadResult['safe_name'], $fileType, $uploadResult['size']]);
    }

    if ($attachmentLink !== '') {
        $stmt = $pdo->prepare("INSERT INTO attachments (message_id, file_type, link_url) VALUES (?, 'link', ?)");
        $stmt->execute([$messageId, $attachmentLink]);
    }

    $pdo->commit();
    flash('message_sent', true);
} catch (Exception $e) {
    $pdo->rollBack();
    $_SESSION['message_errors'] = ["Something went wrong while sending your message. Please try again."];
}

redirect('profile.php?username=' . urlencode($username));
