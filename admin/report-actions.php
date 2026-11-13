<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/admin_layout.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('reports.php');
}

csrf_require();

$reportId = (int) post_str('report_id', 20);
$action = post_str('report_action', 30);
$adminId = $_SESSION['admin_id'];

if ($action === 'unban_ip') {
    $ipAddress = trim(post_str('ip_address', 64));
    if ($ipAddress !== '' && filter_var($ipAddress, FILTER_VALIDATE_IP)) {
        $stmt = $pdo->prepare("DELETE FROM banned_senders WHERE ip_address = ?");
        $stmt->execute([$ipAddress]);
        security_log($pdo, 'admin_unban_ip', 'admin', $adminId);
        flash('admin_notice', 'The ban on ' . $ipAddress . ' was lifted.');
    } else {
        flash('admin_error', 'That IP address is not valid.');
    }
    if (post_str('redirect_to', 20) === 'banned') {
        redirect(admin_back_url('banned.php', ['q', 'sort', 'page']));
    }
    redirect(admin_back_url('reports.php', ['status', 'type', 'q', 'page']));
}

$profileReady = profile_reports_ready($pdo);
$typeColumn = $profileReady ? 'report_type' : "'message' AS report_type";
$stmt = $pdo->prepare("SELECT id, message_id, profile_id, sender_ip, status, $typeColumn FROM reports WHERE id = ?");
$stmt->execute([$reportId]);
$report = $stmt->fetch();

$back = admin_back_url('reports.php', ['status', 'type', 'q', 'page']);

if (!$report) {
    flash('admin_error', 'That report no longer exists.');
    redirect($back);
}

$submittedType = post_str('report_type', 12);
if ($submittedType !== $report['report_type']) {
    flash('admin_error', 'That action does not match the report type.');
    redirect($back);
}

$messageActions = ['ban_ip', 'delete_message'];
$profileActions = ['dismiss'];
if (($report['report_type'] === 'profile' && in_array($action, $messageActions, true))
    || ($report['report_type'] === 'message' && in_array($action, $profileActions, true))) {
    flash('admin_error', 'That action is not available for this type of report.');
    redirect($back);
}

if ($report['report_type'] === 'profile') {
    $stmt = $pdo->prepare("SELECT 1 FROM profiles WHERE id = ?");
    $stmt->execute([$report['profile_id']]);
    if (!$stmt->fetchColumn()) {
        flash('admin_error', 'The reported profile no longer exists.');
        redirect($back);
    }
}

if ($action === 'ban_ip') {
    $reason = mb_substr(trim(post_str('reason', 1000)), 0, 255);
    if (!empty($report['sender_ip']) && $report['sender_ip'] !== 'unknown') {
        $stmt = $pdo->prepare("INSERT INTO banned_senders (ip_address, reason) VALUES (?, ?) ON DUPLICATE KEY UPDATE reason = VALUES(reason)");
        $stmt->execute([$report['sender_ip'], $reason !== '' ? $reason : null]);
        security_log($pdo, 'admin_ban_ip', 'admin', $adminId, 'report:' . $reportId);
        flash('admin_notice', 'Banned ' . $report['sender_ip'] . ' and marked the report as reviewed.');
    } else {
        flash('admin_error', 'This report has no usable sender IP to ban.');
    }
    $pdo->prepare("UPDATE reports SET status = 'reviewed' WHERE id = ?")->execute([$reportId]);
} elseif ($action === 'delete_message') {
    if ($report['message_id']) {
        $stmt = $pdo->prepare("SELECT file_path FROM attachments WHERE message_id = ? AND file_path IS NOT NULL");
        $stmt->execute([$report['message_id']]);
        foreach ($stmt->fetchAll() as $att) {
            delete_stored_upload($att['file_path']);
        }
        $stmt = $pdo->prepare("DELETE FROM messages WHERE id = ?");
        $stmt->execute([$report['message_id']]);
        security_log($pdo, 'admin_delete_message', 'admin', $adminId, 'report:' . $reportId);
        flash('admin_notice', 'Deleted the reported message and marked the report as reviewed.');
    }
    $pdo->prepare("UPDATE reports SET status = 'reviewed' WHERE id = ?")->execute([$reportId]);
} elseif ($action === 'mark_reviewed') {
    if ($profileReady) {
        $pdo->prepare("UPDATE reports SET status = 'reviewed', resolution = 'reviewed' WHERE id = ?")->execute([$reportId]);
    } else {
        $pdo->prepare("UPDATE reports SET status = 'reviewed' WHERE id = ?")->execute([$reportId]);
    }
    security_log($pdo, 'admin_mark_reviewed', 'admin', $adminId, 'report:' . $reportId);
    flash('admin_notice', 'Report #' . $reportId . ' marked as reviewed.');
} elseif ($action === 'dismiss') {
    $pdo->prepare("UPDATE reports SET status = 'reviewed', resolution = 'dismissed' WHERE id = ?")->execute([$reportId]);
    security_log($pdo, 'admin_dismiss_report', 'admin', $adminId, 'report:' . $reportId);
    flash('admin_notice', 'Report #' . $reportId . ' dismissed.');
} else {
    flash('admin_error', 'Unknown action.');
}

redirect($back);
