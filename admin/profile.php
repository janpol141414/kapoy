<?php
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../models/User.php';

if (!isLoggedIn() || !hasRole('admin')) redirect('/auth/login.php');

$db        = (new Database())->getConnection();
$userModel = new User($db);
$user      = $userModel->getById($_SESSION['user_id']);

$success = $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $uData = [
        'name'    => sanitize($_POST['name'] ?? ''),
        'phone'   => sanitize($_POST['phone'] ?? ''),
        'address' => sanitize($_POST['address'] ?? ''),
        'bio'     => sanitize($_POST['bio'] ?? ''),
    ];

    if (!empty($_FILES['profile_photo']['name'])) {
        $ext = strtolower(pathinfo($_FILES['profile_photo']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg','jpeg','png','gif','webp'])) {
            $fn = 'admin_'.time().'_'.rand(1000,9999).'.'.$ext;
            if (move_uploaded_file($_FILES['profile_photo']['tmp_name'], PROFILE_PHOTO_PATH.$fn)) {
                $uData['profile_photo'] = $fn;
                $_SESSION['profile_photo'] = $fn;
            }
        } else {
            $error = 'Invalid file type. Please upload JPG, PNG, GIF, or WebP.';
        }
    }

    if (!$error) {
        $userModel->update($_SESSION['user_id'], $uData);
        $_SESSION['name'] = $uData['name'];
        $user = $userModel->getById($_SESSION['user_id']);
        $success = 'Profile updated successfully!';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>My Profile – Admin | GeoSurvey</title>
<link rel="stylesheet" href="../assets/css/main.css">
<link rel="stylesheet" href="../assets/css/dashboard.css">
<link rel="stylesheet" href="../assets/css/admin.css">
<link rel="stylesheet" href="../assets/css/profile.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<style>
.admin-profile-layout {
    display: grid;
    grid-template-columns: 300px 1fr;
    gap: 24px;
    max-width: 1000px;
}
.admin-profile-card {
    background: #fff;
    border-radius: 20px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.07);
    border: 1px solid #f1f5f9;
    overflow: hidden;
}
.admin-profile-cover {
    height: 100px;
    background: linear-gradient(135deg, #0f2540 0%, #1a3c5e 60%, #2d6a9f 100%);
    position: relative;
}
.admin-profile-avatar-wrap {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 0 24px 24px;
    margin-top: -50px;
    position: relative;
}
.admin-profile-avatar {
    width: 100px; height: 100px;
    border-radius: 50%;
    object-fit: cover;
    border: 4px solid #fff;
    box-shadow: 0 4px 16px rgba(0,0,0,0.15);
}
.admin-profile-name {
    font-size: 18px; font-weight: 800; color: #1a1a2e;
    margin-top: 12px; text-align: center;
}
.admin-profile-role-badge {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 4px 14px;
    background: linear-gradient(135deg, #dc2626, #b91c1c);
    color: #fff;
    border-radius: 20px;
    font-size: 12px; font-weight: 700;
    margin-top: 6px;
}
.admin-profile-meta {
    width: 100%;
    margin-top: 16px;
    border-top: 1px solid #f1f5f9;
    padding-top: 16px;
}
.admin-profile-meta-item {
    display: flex; align-items: center; gap: 10px;
    padding: 8px 0;
    font-size: 13px; color: #6b7280;
}
.admin-profile-meta-item i {
    width: 18px; text-align: center;
    color: #2d6a9f; font-size: 14px;
}
.admin-profile-meta-item strong { color: #1a1a2e; }

.admin-edit-card {
    background: #fff;
    border-radius: 20px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.07);
    border: 1px solid #f1f5f9;
    overflow: hidden;
}
.admin-edit-header {
    padding: 20px 28px;
    border-bottom: 1px solid #f1f5f9;
    display: flex; align-items: center; gap: 12px;
}
.admin-edit-header-icon {
    width: 44px; height: 44px;
    border-radius: 12px;
    background: linear-gradient(135deg, #1a3c5e, #2d6a9f);
    display: flex; align-items: center; justify-content: center;
    font-size: 18px; color: #fff;
}
.admin-edit-header h3 { font-size: 16px; font-weight: 800; color: #1a1a2e; }
.admin-edit-header p  { font-size: 13px; color: #9ca3af; }
.admin-edit-body { padding: 28px; }

.photo-upload-section {
    display: flex; align-items: center; gap: 16px;
    padding: 16px;
    background: #f8fafc;
    border-radius: 12px;
    border: 2px dashed #e2e8f0;
    margin-bottom: 24px;
    transition: border-color 0.2s;
}
.photo-upload-section:hover { border-color: #2d6a9f; }
.photo-preview-sm {
    width: 64px; height: 64px;
    border-radius: 50%; object-fit: cover;
    border: 3px solid #fff;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}
.btn-upload-photo {
    display: inline-flex; align-items: center; gap: 7px;
    padding: 8px 16px;
    background: linear-gradient(135deg, #1a3c5e, #2d6a9f);
    color: #fff; border-radius: 8px;
    font-size: 13px; font-weight: 600;
    cursor: pointer; transition: all 0.2s;
}
.btn-upload-photo:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(26,60,94,0.3); }
.photo-upload-hint { font-size: 12px; color: #9ca3af; margin-top: 4px; }

.btn-save-admin-profile {
    display: inline-flex; align-items: center; gap: 8px;
    padding: 12px 32px;
    background: linear-gradient(135deg, #1a3c5e, #2d6a9f);
    color: #fff; border: none; border-radius: 12px;
    font-size: 14px; font-weight: 700;
    cursor: pointer; transition: all 0.2s;
    margin-top: 8px;
}
.btn-save-admin-profile:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(26,60,94,0.3); }

.admin-stats-row {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 12px;
    margin-top: 16px;
}
.admin-stat-mini {
    background: #f8fafc;
    border-radius: 12px;
    padding: 14px;
    text-align: center;
    border: 1px solid #f1f5f9;
}
.admin-stat-mini .num { font-size: 22px; font-weight: 900; color: #1a1a2e; }
.admin-stat-mini .lbl { font-size: 11px; color: #9ca3af; font-weight: 500; margin-top: 2px; }

/* Dark mode */
body.dark-mode .admin-profile-card,
body.dark-mode .admin-edit-card { background: #1e293b !important; border-color: #334155 !important; }
body.dark-mode .admin-profile-name { color: #f1f5f9 !important; }
body.dark-mode .admin-profile-meta { border-color: #334155 !important; }
body.dark-mode .admin-profile-meta-item { color: #94a3b8 !important; }
body.dark-mode .admin-profile-meta-item strong { color: #f1f5f9 !important; }
body.dark-mode .admin-edit-header { border-color: #334155 !important; }
body.dark-mode .admin-edit-header h3 { color: #f1f5f9 !important; }
body.dark-mode .photo-upload-section { background: #0f172a !important; border-color: #334155 !important; }
body.dark-mode .admin-stat-mini { background: #0f172a !important; border-color: #334155 !important; }
body.dark-mode .admin-stat-mini .num { color: #f1f5f9 !important; }

@media (max-width: 768px) {
    .admin-profile-layout { grid-template-columns: 1fr; }
    .admin-stats-row { grid-template-columns: repeat(3, 1fr); }
}
</style>
</head>
<body class="app-body">
<?php include '../includes/header.php'; ?>
<div class="app-layout">
<?php include '../includes/sidebar_admin.php'; ?>
<main class="main-content">

<div class="page-header">
    <div><h1><i class="fas fa-user-shield"></i> My Profile</h1><p>Manage your administrator account</p></div>
    <a href="settings.php" class="btn-primary"><i class="fas fa-cog"></i> Settings</a>
</div>

<?php if ($success): ?><div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= htmlspecialchars($success) ?></div><?php endif; ?>
<?php if ($error):   ?><div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div><?php endif; ?>

<div class="admin-profile-layout">
    <!-- Left: Profile Card -->
    <div>
        <div class="admin-profile-card">
            <div class="admin-profile-cover"></div>
            <div class="admin-profile-avatar-wrap">
                <img src="<?= UPLOADS_URL ?>/profiles/<?= $user['profile_photo'] ?? 'default_avatar.png' ?>"
                     alt="Profile" class="admin-profile-avatar" id="profilePreviewCard"
                     onerror="this.src='<?= ASSETS_URL ?>/images/default_avatar.png'">
                <div class="admin-profile-name"><?= htmlspecialchars($user['name']) ?></div>
                <div class="admin-profile-role-badge"><i class="fas fa-shield-alt"></i> Administrator</div>

                <div class="admin-profile-meta">
                    <div class="admin-profile-meta-item">
                        <i class="fas fa-envelope"></i>
                        <span><?= htmlspecialchars($user['email']) ?></span>
                    </div>
                    <?php if (!empty($user['phone'])): ?>
                    <div class="admin-profile-meta-item">
                        <i class="fas fa-phone"></i>
                        <span><?= htmlspecialchars($user['phone']) ?></span>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($user['address'])): ?>
                    <div class="admin-profile-meta-item">
                        <i class="fas fa-map-marker-alt"></i>
                        <span><?= htmlspecialchars($user['address']) ?></span>
                    </div>
                    <?php endif; ?>
                    <div class="admin-profile-meta-item">
                        <i class="fas fa-calendar-alt"></i>
                        <span>Member since <strong><?= date('M Y', strtotime($user['created_at'])) ?></strong></span>
                    </div>
                    <div class="admin-profile-meta-item">
                        <i class="fas fa-circle" style="color:#10b981;font-size:10px"></i>
                        <span style="color:#10b981;font-weight:600">Active Account</span>
                    </div>
                </div>

                <?php
                // Quick stats
                $statsStmt = $db->query("SELECT
                    (SELECT COUNT(*) FROM appointments) AS appointments,
                    (SELECT COUNT(*) FROM users WHERE role='client') AS clients,
                    (SELECT COUNT(*) FROM users WHERE role='engineer') AS engineers");
                $qStats = $statsStmt->fetch(PDO::FETCH_ASSOC);
                ?>
                <div class="admin-stats-row">
                    <div class="admin-stat-mini">
                        <div class="num"><?= $qStats['appointments'] ?></div>
                        <div class="lbl">Appointments</div>
                    </div>
                    <div class="admin-stat-mini">
                        <div class="num"><?= $qStats['clients'] ?></div>
                        <div class="lbl">Clients</div>
                    </div>
                    <div class="admin-stat-mini">
                        <div class="num"><?= $qStats['engineers'] ?></div>
                        <div class="lbl">Engineers</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Right: Edit Form -->
    <div class="admin-edit-card">
        <div class="admin-edit-header">
            <div class="admin-edit-header-icon"><i class="fas fa-user-edit"></i></div>
            <div>
                <h3>Edit Profile Information</h3>
                <p>Update your personal details and photo</p>
            </div>
        </div>
        <div class="admin-edit-body">
            <form method="POST" enctype="multipart/form-data">

                <!-- Photo Upload -->
                <div class="photo-upload-section">
                    <img src="<?= UPLOADS_URL ?>/profiles/<?= $user['profile_photo'] ?? 'default_avatar.png' ?>"
                         alt="Profile" class="photo-preview-sm" id="profilePhotoPreview"
                         onerror="this.src='<?= ASSETS_URL ?>/images/default_avatar.png'">
                    <div>
                        <input type="file" name="profile_photo" id="photoInput" accept="image/*"
                               onchange="previewPhoto(this)" style="display:none">
                        <label for="photoInput" class="btn-upload-photo">
                            <i class="fas fa-camera"></i> Change Photo
                        </label>
                        <div class="photo-upload-hint">JPG, PNG, GIF or WebP · Max 5MB</div>
                    </div>
                </div>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px">
                    <div class="form-group">
                        <label>Full Name *</label>
                        <div class="input-wrapper">
                            <i class="fas fa-user input-icon"></i>
                            <input type="text" name="name" value="<?= htmlspecialchars($user['name']) ?>" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Email (read-only)</label>
                        <div class="input-wrapper">
                            <i class="fas fa-envelope input-icon"></i>
                            <input type="email" value="<?= htmlspecialchars($user['email']) ?>" disabled>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Phone Number</label>
                        <div class="input-wrapper">
                            <i class="fas fa-phone input-icon"></i>
                            <input type="tel" name="phone" value="<?= htmlspecialchars($user['phone'] ?? '') ?>"
                                   placeholder="+63 9XX XXX XXXX">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Office Address</label>
                        <div class="input-wrapper">
                            <i class="fas fa-map-marker-alt input-icon"></i>
                            <input type="text" name="address" value="<?= htmlspecialchars($user['address'] ?? '') ?>"
                                   placeholder="City, Province">
                        </div>
                    </div>
                </div>

                <div class="form-group" style="margin-top:4px">
                    <label>Bio / About</label>
                    <textarea name="bio" rows="4"
                              placeholder="Brief description about yourself as an administrator..."><?= htmlspecialchars($user['bio'] ?? '') ?></textarea>
                </div>

                <div style="border-top:1px solid #f1f5f9;padding-top:20px;margin-top:8px">
                    <p style="font-size:13px;color:#9ca3af;margin-bottom:16px">
                        <i class="fas fa-lock" style="margin-right:6px;color:#2d6a9f"></i>
                        To change your password, go to <a href="settings.php?tab=password" style="color:#2d6a9f;font-weight:600">Settings → Password</a>
                    </p>
                    <button type="submit" class="btn-save-admin-profile">
                        <i class="fas fa-save"></i> Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

</main></div>
<script src="../assets/js/dashboard.js"></script>
<script>
function previewPhoto(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = e => {
            document.getElementById('profilePhotoPreview').src = e.target.result;
            document.getElementById('profilePreviewCard').src = e.target.result;
        };
        reader.readAsDataURL(input.files[0]);
    }
}
</script>
</body>
</html>
