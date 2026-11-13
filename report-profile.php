<?php
require_once __DIR__ . '/includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('index.php');
}

csrf_require();

$profileId = (int) post_str('profile_id', 20);
$username = trim(post_str('username', 100));
$reason = post_str('reason', 40);
$details = clean_report_details(post_str('details', 4000));

$stmt = $pdo->prepare("SELECT p.id, p.user_id FROM profiles p JOIN users u ON u.id = p.user_id WHERE p.id = ? AND u.username = ?");
$stmt->execute([$profileId, $username]);
$profile = $stmt->fetch();

if (!$profile) {
    http_response_code(404);
    die("Profile not found.");
}

$back = 'profile.php?username=' . urlencode($username) . '#report-profile';
$reasons = profile_report_reasons();
$errors = [];

if (!profile_reports_ready($pdo)) {
    $errors[] = "Profile reports aren't available right now.";
} elseif (is_logged_in() && (int) current_user_id() === (int) $profile['user_id']) {
    $errors[] = "You can't report your own profile.";
} else {
    if (!array_key_exists($reason, $reasons)) {
        $errors[] = "Please choose a reason for your report.";
    }
    if (mb_strlen($details) > 300) {
        $errors[] = "Details must be 300 characters or fewer.";
    }
    if ($reason === 'other' && $details === '') {
        $errors[] = "Please add a short explanation when you choose Other.";
    }
}

$reporter = null;
if (empty($errors)) {
    $reporter = profile_reporter_hash(is_logged_in() ? current_user_id() : null);
    $stmt = $pdo->prepare("SELECT 1 FROM reports WHERE report_type = 'profile' AND profile_id = ? AND reporter_hash = ? AND (status = 'pending' OR created_at > (NOW() - INTERVAL 1 DAY)) LIMIT 1");
    $stmt->execute([$profileId, $reporter]);
    if ($stmt->fetchColumn()) {
        $errors[] = "You already reported this profile. It's still being reviewed.";
    }
}

if (empty($errors)) {
    $ipBucket = rl_ip_bucket(client_ip());
    $limit = rate_limit_begin($pdo, [
        ['policy' => 'preport_ip', 'key' => $ipBucket],
        ['policy' => 'preport_pair', 'key' => $ipBucket . '|' . $profileId],
    ], true);
    if (!$limit['allowed']) {
        $errors[] = "You're sending reports too quickly. Please wait a while and try again.";
    } elseif ($limit['locked_now']) {
        security_log($pdo, 'profile_report_rate_limited');
    }
}

if (empty($errors)) {
    try {
        $stmt = $pdo->prepare("INSERT INTO reports (message_id, profile_id, reported_message, sender_ip, report_type, reason, details, reporter_hash) VALUES (NULL, ?, '', NULL, 'profile', ?, ?, ?)");
        $stmt->execute([$profileId, $reason, $details === '' ? null : $details, $reporter]);
        security_log($pdo, 'profile_report', is_logged_in() ? 'user' : 'none', is_logged_in() ? current_user_id() : null, 'profile:' . $profileId);
        flash('profile_report_ok', "Salamat. Natanggap na namin ang report mo at ire-review ito.");
    } catch (Throwable $e) {
        error_log('Profile report write failed');
        $errors[] = "Something went wrong while sending your report. Please try again.";
    }
}

if (!empty($errors)) {
    $_SESSION['profile_report_errors'] = $errors;
}

redirect($back);
