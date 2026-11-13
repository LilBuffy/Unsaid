<?php
require_once __DIR__ . '/includes/auth.php';
require_login();

$userId = current_user_id();

$stmt = $pdo->prepare("SELECT u.username, p.id as profile_id, p.display_name, p.bio, p.profile_image, p.page_title, p.message_placeholder, p.button_text FROM users u JOIN profiles p ON p.user_id = u.id WHERE u.id = ?");
$stmt->execute([$userId]);
$profile = $stmt->fetch();

if (!$profile) {
    redirect('logout.php');
}

$successReady = success_settings_ready($pdo);
if ($successReady) {
    $stmt = $pdo->prepare("SELECT success_message, success_effect FROM profiles WHERE id = ?");
    $stmt->execute([$profile['profile_id']]);
    $profile = array_merge($profile, $stmt->fetch() ?: []);
}
$effectOptions = success_effect_options();

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require();

    $displayName = trim(post_str('display_name', 1000));
    $bio = trim(post_str('bio', 4000));
    $pageTitleField = trim(post_str('page_title', 1000));
    $placeholder = trim(post_str('message_placeholder', 1000));
    $buttonText = trim(post_str('button_text', 1000));

    if ($displayName === '' || mb_strlen($displayName) > 50) {
        $errors[] = "Display name must be between 1 and 50 characters.";
    }
    if (mb_strlen($bio) > 300) {
        $errors[] = "Bio must be under 300 characters.";
    }
    if ($pageTitleField === '' || mb_strlen($pageTitleField) > 100) {
        $errors[] = "Page title must be between 1 and 100 characters.";
    }
    if ($placeholder === '' || mb_strlen($placeholder) > 150) {
        $errors[] = "Message placeholder must be between 1 and 150 characters.";
    }
    if ($buttonText === '' || mb_strlen($buttonText) > 50) {
        $errors[] = "Button text must be between 1 and 50 characters.";
    }

    $successText = null;
    $successEffect = null;
    if ($successReady) {
        $successText = clean_success_text(post_str('success_message', 2000));
        $effectRaw = post_str('success_effect', 40);
        if (mb_strlen($successText) > SUCCESS_TEXT_MAX) {
            $errors[] = "Success message must be " . SUCCESS_TEXT_MAX . " characters or fewer.";
        }
        if (!array_key_exists($effectRaw, $effectOptions)) {
            $errors[] = "Please choose one of the listed success effects.";
        } else {
            $successEffect = $effectRaw;
        }
    }

    $newImage = null;
    if (!empty($_FILES['profile_image']) && $_FILES['profile_image']['error'] !== UPLOAD_ERR_NO_FILE) {
        $uploadResult = validate_upload($_FILES['profile_image']);
        if (!$uploadResult['ok']) {
            $errors[] = $uploadResult['error'];
        } elseif (empty($uploadResult['skip'])) {
            $newImage = $uploadResult;
        }
    }

    if (empty($errors)) {
        if ($newImage) {
            $destination = __DIR__ . '/uploads/' . $newImage['safe_name'];
            if (!move_uploaded_file($_FILES['profile_image']['tmp_name'], $destination)) {
                $errors[] = "Failed to upload profile picture.";
            } else {
                if (!empty($profile['profile_image'])) {
                    delete_stored_upload($profile['profile_image']);
                }
                $stmt = $pdo->prepare("UPDATE profiles SET display_name = ?, bio = ?, page_title = ?, message_placeholder = ?, button_text = ?, profile_image = ? WHERE id = ?");
                $stmt->execute([$displayName, $bio, $pageTitleField, $placeholder, $buttonText, $newImage['safe_name'], $profile['profile_id']]);
                $profile['profile_image'] = $newImage['safe_name'];
            }
        } else {
            $stmt = $pdo->prepare("UPDATE profiles SET display_name = ?, bio = ?, page_title = ?, message_placeholder = ?, button_text = ? WHERE id = ?");
            $stmt->execute([$displayName, $bio, $pageTitleField, $placeholder, $buttonText, $profile['profile_id']]);
        }

        if (empty($errors) && $successReady) {
            $stmt = $pdo->prepare("UPDATE profiles SET success_message = ?, success_effect = ? WHERE id = ?");
            $stmt->execute([$successText === '' ? null : $successText, $successEffect, $profile['profile_id']]);
            $profile['success_message'] = $successText === '' ? null : $successText;
            $profile['success_effect'] = $successEffect;
        }

        if (empty($errors)) {
            $success = true;
            $profile['display_name'] = $displayName;
            $profile['bio'] = $bio;
            $profile['page_title'] = $pageTitleField;
            $profile['message_placeholder'] = $placeholder;
            $profile['button_text'] = $buttonText;
        }
    }
}

$pageTitle = "Edit profile - UNSAID";
$basePath = "";
$bodyClass = 'page-app';
$bgMode = 'static';
$bgImages = [6];
$currentSettings = profile_success_settings($profile);
include __DIR__ . '/includes/header.php';
?>

<section class="app-page edit-profile-page">
    <div class="section-inner narrow">
        <div class="page-head">
            <div>
                <span class="section-eyebrow">Profile</span>
                <h1 class="page-title">Edit your profile</h1>
            </div>
            <a href="dashboard.php" class="btn btn-outline">Back to dashboard</a>
        </div>

        <?php if ($success): ?>
            <div class="alert alert-success">Your profile has been updated.</div>
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

        <form method="POST" action="edit-profile.php" enctype="multipart/form-data" class="edit-form">
            <?php echo csrf_field(); ?>

            <div class="form-avatar-row">
                <div class="profile-avatar small">
                    <?php if (!empty($profile['profile_image'])): ?>
                        <img src="<?php echo upload_url($profile['profile_image']); ?>" alt="Profile picture">
                    <?php else: ?>
                        <span><?php echo e(mb_substr($profile['display_name'], 0, 1)); ?></span>
                    <?php endif; ?>
                </div>
                <label class="file-label">
                    Change profile picture
                    <input type="file" name="profile_image" accept="image/png, image/jpeg, image/gif, image/webp">
                </label>
            </div>

            <label for="display_name">Display name</label>
            <input type="text" id="display_name" name="display_name" value="<?php echo e($profile['display_name']); ?>" maxlength="50" required>

            <label for="bio">Bio</label>
            <textarea id="bio" name="bio" maxlength="300" rows="3"><?php echo e($profile['bio']); ?></textarea>

            <label for="page_title">Page title</label>
            <input type="text" id="page_title" name="page_title" value="<?php echo e($profile['page_title']); ?>" maxlength="100" required>

            <label for="message_placeholder">Message placeholder</label>
            <input type="text" id="message_placeholder" name="message_placeholder" value="<?php echo e($profile['message_placeholder']); ?>" maxlength="150" required>

            <label for="button_text">Button text</label>
            <input type="text" id="button_text" name="button_text" value="<?php echo e($profile['button_text']); ?>" maxlength="50" required>

            <?php if ($successReady): ?>
            <fieldset class="settings-group">
                <legend>After a message is sent</legend>
                <p class="field-help">This is what senders see on your public page once their message is delivered.</p>

                <label for="success_message">Confirmation text</label>
                <textarea id="success_message" name="success_message" rows="2" maxlength="<?php echo SUCCESS_TEXT_MAX; ?>" placeholder="<?php echo e(DEFAULT_SUCCESS_TEXT); ?>"><?php echo e($currentSettings['text']); ?></textarea>
                <span class="field-count"><span id="successCount">0</span>/<?php echo SUCCESS_TEXT_MAX; ?> &middot; Leave empty to use the default text.</span>

                <span class="group-label">Effect</span>
                <div class="effect-options" id="effectOptions">
                    <?php foreach ($effectOptions as $key => $option): ?>
                        <label class="effect-option">
                            <input type="radio" name="success_effect" value="<?php echo e($key); ?>" <?php echo $currentSettings['effect'] === $key ? 'checked' : ''; ?>>
                            <span class="effect-name"><?php echo e($option[0]); ?></span>
                            <span class="effect-desc"><?php echo e($option[1]); ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
                <button type="button" class="btn btn-small btn-outline" id="previewEffect">Preview effect</button>
                <div class="effect-preview" id="effectPreview" aria-live="polite"></div>
            </fieldset>
            <?php endif; ?>

            <button type="submit" class="btn btn-primary btn-block">Save changes</button>
        </form>
    </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
