<?php
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../models/User.php';

if (!isLoggedIn() || !hasRole('client')) redirect('/auth/login.php');

$db = (new Database())->getConnection();
$userModel = new User($db);
$user = $userModel->getById($_SESSION['user_id']);

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $bio = trim($_POST['bio'] ?? '');

    if (empty($name)) {
        $error = 'Name is required.';
    } else {
        $data = [
            'name' => $name,
            'phone' => $phone,
            'address' => $address,
            'bio' => $bio
        ];

        // Handle photo upload
        if (!empty($_FILES['profile_photo']['name'])) {
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            $ext = strtolower(pathinfo($_FILES['profile_photo']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, $allowed)) {
                $filename = 'user_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
                if (move_uploaded_file($_FILES['profile_photo']['tmp_name'], PROFILE_PHOTO_PATH . $filename)) {
                    $data['profile_photo'] = $filename;
                    $_SESSION['profile_photo'] = $filename;
                }
            }
        }

        if ($userModel->update($_SESSION['user_id'], $data)) {
            $_SESSION['name'] = $name;
            $user = $userModel->getById($_SESSION['user_id']);
            $success = 'Profile updated successfully!';
        } else {
            $error = 'Failed to update profile.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - GeoSurvey Portal</title>
    <link rel="stylesheet" href="../assets/css/main.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="../assets/css/profile.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body class="app-body">

<?php include '../includes/header.php'; ?>

<div class="app-layout">
    <?php include '../includes/sidebar_client.php'; ?>

    <main class="main-content">
        <div class="page-header">
            <div>
                <h1><i class="fas fa-user-circle"></i> My Profile</h1>
                <p>Manage your account information</p>
            </div>
        </div>

        <?php if ($success): ?>
        <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= $success ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
        <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="profile-edit-container">
            <div class="profile-edit-card">
                <form method="POST" enctype="multipart/form-data">
                    <div class="profile-photo-section">
                        <div class="current-photo">
                            <img src="<?= UPLOADS_URL ?>/profiles/<?= $user['profile_photo'] ?? 'default_avatar.png' ?>" 
                                 alt="Profile" id="profilePhotoPreview"
                                 onerror="this.src='<?= ASSETS_URL ?>/images/default_avatar.png'">
                        </div>
                        <div class="photo-upload-btn">
                            <input type="file" name="profile_photo" id="photoInput" accept="image/*" onchange="previewPhoto(this)">
                            <label for="photoInput" class="btn-upload-photo">
                                <i class="fas fa-camera"></i> Change Photo
                            </label>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Full Name *</label>
                            <div class="input-wrapper">
                                <i class="fas fa-user input-icon"></i>
                                <input type="text" name="name" value="<?= htmlspecialchars($user['name']) ?>" required>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Email Address</label>
                            <div class="input-wrapper">
                                <i class="fas fa-envelope input-icon"></i>
                                <input type="email" value="<?= htmlspecialchars($user['email']) ?>" disabled>
                            </div>
                            <small>Email cannot be changed</small>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Phone Number</label>
                            <div class="input-wrapper">
                                <i class="fas fa-phone input-icon"></i>
                                <input type="tel" name="phone" value="<?= htmlspecialchars($user['phone'] ?? '') ?>">
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Member Since</label>
                            <div class="input-wrapper">
                                <i class="fas fa-calendar input-icon"></i>
                                <input type="text" value="<?= date('F Y', strtotime($user['created_at'])) ?>" disabled>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Address</label>
                        <div class="input-wrapper">
                            <i class="fas fa-map-marker-alt input-icon"></i>
                            <input type="text" name="address" value="<?= htmlspecialchars($user['address'] ?? '') ?>">
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Bio</label>
                        <textarea name="bio" rows="4" placeholder="Tell us about yourself..."><?= htmlspecialchars($user['bio'] ?? '') ?></textarea>
                    </div>

                    <button type="submit" class="btn-save-profile">
                        <i class="fas fa-save"></i> Save Changes
                    </button>
                </form>
            </div>
        </div>
    </main>
</div>

<?php include '../includes/chatbot.php'; ?>
<script src="../assets/js/chatbot.js"></script>
<script>
function previewPhoto(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = e => {
            document.getElementById('profilePhotoPreview').src = e.target.result;
        };
        reader.readAsDataURL(input.files[0]);
    }
}
</script>
</body>
</html>
