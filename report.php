<?php
require_once __DIR__ . '/includes/auth.php';
require_login();

$userId = current_user_id();

$stmt = $pdo->prepare("SELECT id FROM profiles WHERE user_id = ?");
$stmt->execute([$userId]);
$profile = $stmt->fetch();

if (!$profile) {
    redirect('logout.php');
}

$profileId = $profile['id'];

$search = trim($_GET['search'] ?? '');
$status = $_GET['status'] ?? 'all';
$date = $_GET['date'] ?? '';

$conditions = ["profile_id = ?"];
$params = [$profileId];

if ($search !== '') {
    $conditions[] = "message LIKE ?";
    $params[] = '%' . $search . '%';
}
if ($status === 'read') {
    $conditions[] = "is_read = 1";
} elseif ($status === 'unread') {
    $conditions[] = "is_read = 0";
}
if ($date !== '') {
    $conditions[] = "DATE(created_at) = ?";
    $params[] = $date;
}

$where = implode(' AND ', $conditions);

$stmt = $pdo->prepare("SELECT id, message, is_read, created_at FROM messages WHERE $where ORDER BY created_at DESC");
$stmt->execute($params);
$messages = $stmt->fetchAll();

$pageTitle = "Archives - UNSAID";
$basePath = "";
include __DIR__ . '/includes/header.php';
?>

<section class="report-page">
    <div class="section-inner">
        <div class="dashboard-header">
            <div>
                <h1 class="page-title">Your archives</h1>
                <p class="page-subtitle">Search, select, and print your messages.</p>
            </div>
            <a href="dashboard.php" class="btn btn-outline">Back to dashboard</a>
        </div>

        <form method="GET" action="report.php" class="filter-bar">
            <input type="text" name="search" value="<?php echo e($search); ?>" placeholder="Search messages...">
            <select name="status">
                <option value="all" <?php echo $status === 'all' ? 'selected' : ''; ?>>All</option>
                <option value="unread" <?php echo $status === 'unread' ? 'selected' : ''; ?>>Unread</option>
                <option value="read" <?php echo $status === 'read' ? 'selected' : ''; ?>>Read</option>
            </select>
            <input type="date" name="date" value="<?php echo e($date); ?>">
            <button type="submit" class="btn btn-primary">Search</button>
            <a href="report.php" class="btn btn-ghost">Reset</a>
        </form>

        <form method="GET" action="print-messages.php" target="_blank" id="reportForm">
            <div class="report-toolbar">
                <label class="checkbox-label"><input type="checkbox" id="selectAll"> Select all</label>
                <button type="submit" class="btn btn-primary btn-small">Print selected</button>
                <a href="print-messages.php?all=1" target="_blank" class="btn btn-outline btn-small">Print all</a>
            </div>

            <div class="report-list">
                <?php if (empty($messages)): ?>
                    <p class="empty-state">No messages match your search yet.</p>
                <?php endif; ?>

                <?php foreach ($messages as $msg): ?>
                    <label class="report-row">
                        <input type="checkbox" name="ids[]" value="<?php echo (int) $msg['id']; ?>" class="report-checkbox">
                        <span class="report-snippet"><?php echo e(mb_strimwidth($msg['message'], 0, 80, '...')); ?></span>
                        <span class="report-status <?php echo $msg['is_read'] ? '' : 'unread'; ?>"><?php echo $msg['is_read'] ? 'Read' : 'Unread'; ?></span>
                        <span class="report-date"><?php echo e(date('M j, Y g:i A', strtotime($msg['created_at']))); ?></span>
                        <a href="print-message.php?id=<?php echo (int) $msg['id']; ?>" target="_blank" class="btn btn-small">Print</a>
                    </label>
                <?php endforeach; ?>
            </div>
        </form>
    </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
