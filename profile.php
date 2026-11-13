<?php
require_once __DIR__ . '/includes/auth.php';

$username = trim(get_str('username', 100));

if ($username === '') {
    http_response_code(404);
    die("Profile not found.");
}

$stmt = $pdo->prepare("SELECT u.id as user_id, u.username, p.* FROM users u JOIN profiles p ON p.user_id = u.id WHERE u.username = ?");
$stmt->execute([$username]);
$profile = $stmt->fetch();

if (!$profile) {
    http_response_code(404);
    $pageTitle = "Profile not found - UNSAID";
    $basePath = "";
    $bgMode = 'static';
    $bgImages = [6];
    include __DIR__ . '/includes/header.php';
    echo '<section class="page-hero"><div class="section-inner"><h1 class="page-title">Profile not found</h1><p class="page-subtitle">This UNSAID link doesn\'t exist or may have been removed.</p></div></section>';
    include __DIR__ . '/includes/footer.php';
    exit;
}

record_profile_view($pdo, (int) $profile['id'], (int) $profile['user_id']);

$success = flash('message_sent');
$errors = $_SESSION['message_errors'] ?? [];
unset($_SESSION['message_errors']);
$reportOk = flash('profile_report_ok');
$reportErrors = $_SESSION['profile_report_errors'] ?? [];
unset($_SESSION['profile_report_errors']);
$reportReady = profile_reports_ready($pdo);
$isOwner = is_logged_in() && (int) current_user_id() === (int) $profile['user_id'];
$reportReasons = profile_report_reasons();
$settings = profile_success_settings($profile);
$successText = $settings['text'] !== '' ? $settings['text'] : DEFAULT_SUCCESS_TEXT;
$alertText = $settings['text'] !== '' ? $settings['text'] : DEFAULT_SUCCESS_ALERT;

$pageTitle = e($profile['display_name']) . " - UNSAID";
$basePath = "";
$bgMode = 'static';
$bgImages = [6];
$bodyClass = 'page-profile';
include __DIR__ . '/includes/header.php';
?>

<?php if ($success && $settings['effect'] !== 'none'): ?>
    <div class="success-fx" id="successFx" data-effect="<?php echo e($settings['effect']); ?>">
        <?php if ($settings['effect'] === 'confirm'): ?>
            <div class="send-overlay-card">
                <div class="send-overlay-mark">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 12.5l5.5 5.5L20 7"></path></svg>
                </div>
                <p class="send-overlay-title">Message sent</p>
                <p class="send-overlay-text"><?php echo e($successText); ?></p>
            </div>
        <?php endif; ?>
    </div>
<?php endif; ?>

<section class="public-profile">
    <div class="profile-layout">
        <aside class="profile-identity">
            <div class="profile-avatar">
                <?php if (!empty($profile['profile_image'])): ?>
                    <img src="<?php echo upload_url($profile['profile_image']); ?>" alt="<?php echo e($profile['display_name']); ?>">
                <?php else: ?>
                    <span><?php echo e(mb_substr($profile['display_name'], 0, 1)); ?></span>
                <?php endif; ?>
            </div>
            <div class="profile-identity-copy">
                <h1 class="profile-name"><?php echo e($profile['display_name']); ?></h1>
                <p class="profile-handle">@<?php echo e($profile['username']); ?></p>
                <?php if (!empty($profile['bio'])): ?>
                    <p class="profile-bio"><?php echo e($profile['bio']); ?></p>
                <?php endif; ?>
            </div>
        </aside>

        <div class="message-box">
            <h2 class="message-box-title"><?php echo e($profile['page_title']); ?></h2>

            <?php if ($success): ?>
                <div class="alert alert-success" role="status"><?php echo e($alertText); ?></div>
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
                <input type="hidden" name="profile_id" value="<?php echo (int) $profile['id']; ?>">
                <input type="hidden" name="username" value="<?php echo e($profile['username']); ?>">

                <textarea name="message" maxlength="500" rows="5" required placeholder="<?php echo e($profile['message_placeholder']); ?>"></textarea>

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

        <?php if (!$isOwner && $reportReady): ?>
        <div class="profile-report" id="report-profile">
            <details <?php echo ($reportOk || !empty($reportErrors)) ? 'open' : ''; ?>>
                <summary>Report profile</summary>
                <?php if ($reportOk): ?>
                    <div class="alert alert-success" role="status"><?php echo e($reportOk); ?></div>
                <?php endif; ?>
                <?php if (!empty($reportErrors)): ?>
                    <div class="alert alert-error" role="alert">
                        <ul>
                            <?php foreach ($reportErrors as $error): ?>
                                <li><?php echo e($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                <form method="POST" action="report-profile.php" class="report-form">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="profile_id" value="<?php echo (int) $profile['id']; ?>">
                    <input type="hidden" name="username" value="<?php echo e($profile['username']); ?>">

                    <label for="report_reason">Reason</label>
                    <select id="report_reason" name="reason" required>
                        <option value="">Choose a reason</option>
                        <?php foreach ($reportReasons as $key => $label): ?>
                            <option value="<?php echo e($key); ?>"><?php echo e($label); ?></option>
                        <?php endforeach; ?>
                    </select>

                    <label for="report_details">Explanation (optional)</label>
                    <textarea id="report_details" name="details" rows="3" maxlength="300" placeholder="Anything that helps the admin understand the problem?"></textarea>

                    <p class="field-help">Anonymous ang report mo. Hindi makikita ng profile owner kung sino ang nag-report.</p>
                    <button type="submit" class="btn btn-small btn-outline">Submit report</button>
                </form>
            </details>
        </div>
        <?php endif; ?>
    </div>
</section>
<br>

<?php include __DIR__ . '/includes/footer.php'; ?>
