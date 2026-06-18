<?php
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../models/User.php';
require_once '../models/Engineer.php';
require_once '../models/Company.php';

if (!isLoggedIn() || !hasRole('engineer')) redirect('/auth/login.php');

$db = (new Database())->getConnection();
$userModel     = new User($db);
$engineerModel = new Engineer($db);
$companyModel  = new Company($db);

$user     = $userModel->getById($_SESSION['user_id']);
$engineer = $engineerModel->getByUserId($_SESSION['user_id']);
$companies = $companyModel->getAll();

$success = $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // User fields
    $uData = [
        'name'    => sanitize($_POST['name'] ?? ''),
        'phone'   => sanitize($_POST['phone'] ?? ''),
        'address' => sanitize($_POST['address'] ?? ''),
    ];
    if (!empty($_FILES['profile_photo']['name'])) {
        $ext = strtolower(pathinfo($_FILES['profile_photo']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg','jpeg','png','gif','webp'])) {
            $fn = 'user_'.time().'_'.rand(1000,9999).'.'.$ext;
            if (move_uploaded_file($_FILES['profile_photo']['tmp_name'], PROFILE_PHOTO_PATH.$fn)) {
                $uData['profile_photo'] = $fn;
                $_SESSION['profile_photo'] = $fn;
            }
        }
    }
    $userModel->update($_SESSION['user_id'], $uData);
    $_SESSION['name'] = $uData['name'];

    // Engineer fields
    $eData = [
        'company_id'          => intval($_POST['company_id']) ?: null,
        'license_number'      => sanitize($_POST['license_number'] ?? ''),
        'specialization'      => sanitize($_POST['specialization'] ?? ''),
        'experience_years'    => intval($_POST['experience_years'] ?? 0),
        'availability_status' => $_POST['availability_status'] ?? 'available',
        'skills'              => sanitize($_POST['skills'] ?? ''),
        'certifications'      => sanitize($_POST['certifications'] ?? ''),
        'hourly_rate'         => floatval($_POST['hourly_rate'] ?? 0),
        'bio'                 => sanitize($_POST['bio'] ?? ''),
    ];
    if ($engineer) {
        $engineerModel->update($engineer['id'], $eData);
    } else {
        $eData['user_id'] = $_SESSION['user_id'];
        $engineerModel->create($eData);
    }

    $user     = $userModel->getById($_SESSION['user_id']);
    $engineer = $engineerModel->getByUserId($_SESSION['user_id']);
    $success  = 'Profile updated successfully!';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>My Profile – Engineer | GeoSurvey</title>
<link rel="stylesheet" href="../assets/css/main.css">
<link rel="stylesheet" href="../assets/css/dashboard.css">
<link rel="stylesheet" href="../assets/css/profile.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body class="app-body">
<?php include '../includes/header.php'; ?>
<div class="app-layout">
<?php include '../includes/sidebar_engineer.php'; ?>
<main class="main-content">

<div class="page-header">
    <div><h1><i class="fas fa-user-circle"></i> My Profile</h1><p>Manage your professional information</p></div>
</div>

<?php if ($success): ?><div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= $success ?></div><?php endif; ?>
<?php if ($error):   ?><div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div><?php endif; ?>

<div class="profile-edit-container">
    <div class="profile-edit-card">
        <form method="POST" enctype="multipart/form-data">

            <!-- Photo -->
            <div class="profile-photo-section">
                <div class="current-photo">
                    <img src="<?= UPLOADS_URL ?>/profiles/<?= $user['profile_photo'] ?? 'default_avatar.png' ?>"
                         alt="Profile" id="profilePhotoPreview"
                         onerror="this.src='<?= ASSETS_URL ?>/images/default_avatar.png'">
                </div>
                <div class="photo-upload-btn">
                    <input type="file" name="profile_photo" id="photoInput" accept="image/*" onchange="previewPhoto(this)">
                    <label for="photoInput" class="btn-upload-photo"><i class="fas fa-camera"></i> Change Photo</label>
                </div>
                <?php if ($engineer): ?>
                <div style="display:flex;gap:4px;margin-top:4px">
                    <?php for ($i=1;$i<=5;$i++): ?>
                    <i class="fas fa-star" style="color:<?= $i<=round($engineer['rating']) ? '#f59e0b' : '#d1d5db' ?>;font-size:16px"></i>
                    <?php endfor; ?>
                    <span style="font-size:13px;color:#6b7280;margin-left:4px"><?= number_format($engineer['rating']??0,1) ?> (<?= $engineer['total_reviews']??0 ?> reviews)</span>
                </div>
                <?php endif; ?>
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:24px">
                <!-- Personal Info -->
                <div>
                    <h4 style="font-size:14px;font-weight:700;color:#1a1a2e;margin-bottom:16px;text-transform:uppercase;letter-spacing:0.5px">
                        <i class="fas fa-user" style="color:#2d6a9f"></i> Personal Information
                    </h4>
                    <div class="form-group">
                        <label>Full Name *</label>
                        <div class="input-wrapper"><i class="fas fa-user input-icon"></i>
                        <input type="text" name="name" value="<?= htmlspecialchars($user['name']) ?>" required></div>
                    </div>
                    <div class="form-group">
                        <label>Email (read-only)</label>
                        <div class="input-wrapper"><i class="fas fa-envelope input-icon"></i>
                        <input type="email" value="<?= htmlspecialchars($user['email']) ?>" disabled></div>
                    </div>
                    <div class="form-group">
                        <label>Phone</label>
                        <div class="input-wrapper"><i class="fas fa-phone input-icon"></i>
                        <input type="tel" name="phone" value="<?= htmlspecialchars($user['phone'] ?? '') ?>"></div>
                    </div>
                    <div class="form-group">
                        <label>Address</label>
                        <div class="input-wrapper"><i class="fas fa-map-marker-alt input-icon"></i>
                        <input type="text" name="address" value="<?= htmlspecialchars($user['address'] ?? '') ?>"></div>
                    </div>
                    <div class="form-group">
                        <label>Availability Status</label>
                        <select name="availability_status">
                            <?php foreach (['available'=>'Available','busy'=>'Busy','offline'=>'Offline'] as $v=>$l): ?>
                            <option value="<?= $v ?>" <?= ($engineer['availability_status']??'available')===$v ? 'selected' : '' ?>><?= $l ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <!-- Professional Info -->
                <div>
                    <h4 style="font-size:14px;font-weight:700;color:#1a1a2e;margin-bottom:16px;text-transform:uppercase;letter-spacing:0.5px">
                        <i class="fas fa-hard-hat" style="color:#2d6a9f"></i> Professional Details
                    </h4>
                    <div class="form-group">
                        <label>PRC License Number</label>
                        <div class="input-wrapper"><i class="fas fa-id-card input-icon"></i>
                        <input type="text" name="license_number" value="<?= htmlspecialchars($engineer['license_number'] ?? '') ?>" placeholder="GE-XXXX-XXXXXX"></div>
                    </div>
                    <div class="form-group">
                        <label>Specialization</label>
                        <div class="input-wrapper"><i class="fas fa-star input-icon"></i>
                        <input type="text" name="specialization" value="<?= htmlspecialchars($engineer['specialization'] ?? '') ?>"></div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Experience (years)</label>
                            <div class="input-wrapper"><i class="fas fa-briefcase input-icon"></i>
                            <input type="number" name="experience_years" value="<?= $engineer['experience_years'] ?? 0 ?>" min="0"></div>
                        </div>
                        <div class="form-group">
                            <label>Hourly Rate (₱)</label>
                            <div class="input-wrapper"><i class="fas fa-peso-sign input-icon"></i>
                            <input type="number" name="hourly_rate" value="<?= $engineer['hourly_rate'] ?? 0 ?>" min="0" step="100"></div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Company</label>
                        <select name="company_id">
                            <option value="">Independent</option>
                            <?php foreach ($companies as $c): ?>
                            <option value="<?= $c['id'] ?>" <?= ($engineer['company_id']??0)==$c['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($c['name']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>

            <div class="form-group" style="margin-top:8px">
                <label>Skills (comma-separated)</label>
                <div class="input-wrapper"><i class="fas fa-tools input-icon"></i>
                <input type="text" name="skills" value="<?= htmlspecialchars($engineer['skills'] ?? '') ?>" placeholder="GPS, AutoCAD, Total Station, Drone Piloting..."></div>
            </div>
            <div class="form-group">
                <label>Certifications</label>
                <div class="input-wrapper"><i class="fas fa-certificate input-icon"></i>
                <input type="text" name="certifications" value="<?= htmlspecialchars($engineer['certifications'] ?? '') ?>" placeholder="PRC Licensed GE, NAMRIA Accredited..."></div>
            </div>
            <div class="form-group">
                <label>Professional Bio</label>
                <textarea name="bio" rows="4" placeholder="Describe your experience and expertise..."><?= htmlspecialchars($engineer['bio'] ?? '') ?></textarea>
            </div>

            <button type="submit" class="btn-save-profile"><i class="fas fa-save"></i> Save Profile</button>
        </form>
    </div>
</div>

</main></div>
<?php include '../includes/chatbot.php'; ?>
<script src="../assets/js/chatbot.js"></script>
<script>
function previewPhoto(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = e => { document.getElementById('profilePhotoPreview').src = e.target.result; };
        reader.readAsDataURL(input.files[0]);
    }
}
</script>
</body></html>
