<?php
require_once __DIR__ . '/includes/auth.php';

$username = trim($_GET['username'] ?? '');

if ($username === '') {
    http_response_code(404);
    die("Profile not found.");
}

$stmt = $pdo->prepare("SELECT u.id as user_id, u.username, p.id as profile_id, p.display_name, p.bio, p.profile_image, p.page_title, p.message_placeholder, p.button_text FROM users u JOIN profiles p ON p.user_id = u.id WHERE u.username = ?");
$stmt->execute([$username]);
$profile = $stmt->fetch();

if (!$profile) {
    http_response_code(404);
    $pageTitle = "Profile not found - UNSAID";
    $basePath = "";
    include __DIR__ . '/includes/header.php';
    echo '<section class="page-hero"><div class="section-inner"><h1 class="page-title">Profile not found</h1><p class="page-subtitle">This UNSAID link doesn\'t exist or may have been removed.</p></div></section>';
    include __DIR__ . '/includes/footer.php';
    exit;
}

$success = flash('message_sent');
$errors = $_SESSION['message_errors'] ?? [];
unset($_SESSION['message_errors']);

$pageTitle = e($profile['display_name']) . " - UNSAID";
$basePath = "";
include __DIR__ . '/includes/header.php';
?>

<section class="public-profile">
    <div class="profile-card">
        <div class="profile-avatar">
            <?php if (!empty($profile['profile_image'])): ?>
                <img src="uploads/<?php echo e($profile['profile_image']); ?>" alt="<?php echo e($profile['display_name']); ?>">
            <?php else: ?>
                <span><?php echo e(mb_substr($profile['display_name'], 0, 1)); ?></span>
            <?php endif; ?>
        </div>
        <h1 class="profile-name"><?php echo e($profile['display_name']); ?></h1>
        <p class="profile-handle">@<?php echo e($profile['username']); ?></p>
        <?php if (!empty($profile['bio'])): ?>
            <p class="profile-bio"><?php echo e($profile['bio']); ?></p>
        <?php endif; ?>

        <div class="message-box">
            <h2 class="message-box-title"><?php echo e($profile['page_title']); ?></h2>

            <?php if ($success): ?>
                <div class="alert alert-success">Your message was sent anonymously.</div>
            <?php endif; ?>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-error">
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo e($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="POST" action="send-message.php" enctype="multipart/form-data" class="message-form">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="profile_id" value="<?php echo (int) $profile['profile_id']; ?>">
                <input type="hidden" name="username" value="<?php echo e($profile['username']); ?>">

                <textarea name="message" maxlength="500" rows="4" required placeholder="<?php echo e($profile['message_placeholder']); ?>"></textarea>

                <div class="attachment-row">
                    <label class="file-label">
                        Add image or GIF
                        <input type="file" name="attachment_file" accept="image/png, image/jpeg, image/gif, image/webp">
                    </label>
                    <input type="url" name="attachment_link" placeholder="Attach a link (optional)" maxlength="500">
                </div>

                <button type="submit" class="btn btn-primary btn-block"><?php echo e($profile['button_text']); ?></button>
            </form>
        </div>
    </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
