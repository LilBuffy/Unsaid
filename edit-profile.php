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

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_require();

    $displayName = trim($_POST['display_name'] ?? '');
    $bio = trim($_POST['bio'] ?? '');
    $pageTitleField = trim($_POST['page_title'] ?? '');
    $placeholder = trim($_POST['message_placeholder'] ?? '');
    $buttonText = trim($_POST['button_text'] ?? '');

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
                    $oldPath = __DIR__ . '/uploads/' . $profile['profile_image'];
                    if (is_file($oldPath)) {
                        unlink($oldPath);
                    }
                }
                $stmt = $pdo->prepare("UPDATE profiles SET display_name = ?, bio = ?, page_title = ?, message_placeholder = ?, button_text = ?, profile_image = ? WHERE id = ?");
                $stmt->execute([$displayName, $bio, $pageTitleField, $placeholder, $buttonText, $newImage['safe_name'], $profile['profile_id']]);
                $profile['profile_image'] = $newImage['safe_name'];
            }
        } else {
            $stmt = $pdo->prepare("UPDATE profiles SET display_name = ?, bio = ?, page_title = ?, message_placeholder = ?, button_text = ? WHERE id = ?");
            $stmt->execute([$displayName, $bio, $pageTitleField, $placeholder, $buttonText, $profile['profile_id']]);
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
include __DIR__ . '/includes/header.php';
?>

<section class="edit-profile-page">
    <div class="section-inner narrow">
        <div class="dashboard-header">
            <h1 class="page-title">Edit your profile</h1>
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
                        <img src="uploads/<?php echo e($profile['profile_image']); ?>" alt="Profile picture">
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

            <button type="submit" class="btn btn-primary btn-block">Save changes</button>
        </form>
    </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
