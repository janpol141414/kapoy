<?php
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../models/User.php';
require_once '../models/Engineer.php';

if (isLoggedIn()) redirect('/client/dashboard.php');

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $role = $_POST['role'] ?? 'client';
    $phone = trim($_POST['phone'] ?? '');

    if (empty($name) || empty($email) || empty($password)) {
        $error = 'Please fill in all required fields.';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif (!in_array($role, ['client', 'engineer'])) {
        $error = 'Invalid role selected.';
    } else {
        $db = (new Database())->getConnection();
        $userModel = new User($db);

        if ($userModel->emailExists($email)) {
            $error = 'This email is already registered. Please use a different email.';
        } else {
            // Handle profile photo upload
            $profile_photo = 'default_avatar.png';
            if (!empty($_FILES['profile_photo']['name'])) {
                $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                $ext = strtolower(pathinfo($_FILES['profile_photo']['name'], PATHINFO_EXTENSION));
                if (in_array($ext, $allowed)) {
                    $filename = 'user_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
                    if (move_uploaded_file($_FILES['profile_photo']['tmp_name'], PROFILE_PHOTO_PATH . $filename)) {
                        $profile_photo = $filename;
                    }
                }
            }

            $userModel->name = $name;
            $userModel->email = $email;
            $userModel->password = $password;
            $userModel->role = $role;
            $userModel->phone = $phone;
            $userModel->address = trim($_POST['address'] ?? '');
            $userModel->profile_photo = $profile_photo;
            $userModel->bio = trim($_POST['bio'] ?? '');

            $userId = $userModel->register();

            if ($userId) {
                // If engineer, create engineer profile
                if ($role === 'engineer') {
                    $engineerModel = new Engineer($db);
                    $engineerModel->create([
                        'user_id' => $userId,
                        'company_id' => null,
                        'license_number' => trim($_POST['license_number'] ?? ''),
                        'specialization' => trim($_POST['specialization'] ?? ''),
                        'experience_years' => intval($_POST['experience_years'] ?? 0),
                        'availability_status' => 'available',
                        'bio' => trim($_POST['bio'] ?? ''),
                        'skills' => trim($_POST['skills'] ?? ''),
                        'certifications' => trim($_POST['certifications'] ?? ''),
                        'hourly_rate' => floatval($_POST['hourly_rate'] ?? 0)
                    ]);
                }

                // Auto login
                $_SESSION['user_id'] = $userId;
                $_SESSION['name'] = $name;
                $_SESSION['email'] = $email;
                $_SESSION['role'] = $role;
                $_SESSION['profile_photo'] = $profile_photo;

                if ($role === 'engineer') redirect('/engineer/dashboard.php');
                else redirect('/client/dashboard.php');
            } else {
                $error = 'Registration failed. Please try again.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account - LandSurvey Portal</title>
    <link rel="stylesheet" href="../assets/css/main.css">
    <link rel="stylesheet" href="../assets/css/auth.css">
    <link rel="stylesheet" href="../assets/css/enhancements.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
    /* ── Register page — no layout jump on role switch ── */
    .register-page-body {
        background: linear-gradient(135deg, #0f2540 0%, #1a3c5e 50%, #2d6a9f 100%);
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 40px 20px;
    }
    .register-card {
        background: #fff;
        border-radius: 24px;
        box-shadow: 0 24px 80px rgba(0,0,0,0.25);
        width: 100%;
        max-width: 680px;
        overflow: hidden;
    }
    /* Coloured top bar */
    .register-card-top {
        background: linear-gradient(135deg, #0f2540, #1a3c5e, #2d6a9f);
        padding: 32px 40px 28px;
        text-align: center;
        position: relative;
        overflow: hidden;
    }
    .register-card-top::before {
        content: '';
        position: absolute;
        top: -40px; right: -40px;
        width: 160px; height: 160px;
        background: rgba(255,255,255,0.06);
        border-radius: 50%;
    }
    .register-card-top::after {
        content: '';
        position: absolute;
        bottom: -30px; left: -30px;
        width: 120px; height: 120px;
        background: rgba(79,172,254,0.1);
        border-radius: 50%;
    }
    .reg-brand {
        display: flex; align-items: center; justify-content: center; gap: 10px;
        margin-bottom: 16px; position: relative; z-index: 1;
    }
    .reg-brand-icon {
        width: 44px; height: 44px;
        background: rgba(255,255,255,0.15);
        border-radius: 12px;
        display: flex; align-items: center; justify-content: center;
        font-size: 20px; color: #fff;
    }
    .reg-brand-name { font-size: 22px; font-weight: 900; color: #fff; }
    .register-card-top h2 { font-size: 20px; font-weight: 800; color: #fff; margin-bottom: 4px; position: relative; z-index: 1; }
    .register-card-top p  { font-size: 13px; color: rgba(255,255,255,0.75); position: relative; z-index: 1; }

    /* Role selector — pill style, no layout shift */
    .role-selector-new {
        display: flex;
        background: rgba(255,255,255,0.12);
        border-radius: 50px;
        padding: 4px;
        margin: 20px auto 0;
        width: fit-content;
        position: relative; z-index: 1;
        gap: 4px;
    }
    .role-pill {
        display: flex; align-items: center; gap: 7px;
        padding: 9px 22px;
        border-radius: 50px;
        font-size: 13px; font-weight: 700;
        color: rgba(255,255,255,0.7);
        cursor: pointer;
        transition: all 0.25s;
        border: none; background: transparent;
        white-space: nowrap;
    }
    .role-pill i { font-size: 14px; }
    .role-pill.active {
        background: #fff;
        color: #1a3c5e;
        box-shadow: 0 2px 12px rgba(0,0,0,0.15);
    }
    .role-pill:hover:not(.active) { color: #fff; background: rgba(255,255,255,0.15); }

    /* Form body */
    .register-card-body { padding: 32px 40px 36px; }

    /* Engineer fields — always rendered, height-animated */
    .engineer-fields-wrap {
        overflow: hidden;
        max-height: 0;
        opacity: 0;
        transition: max-height 0.4s cubic-bezier(0.4,0,0.2,1), opacity 0.3s ease;
    }
    .engineer-fields-wrap.open {
        max-height: 600px;
        opacity: 1;
    }
    .eng-fields-divider {
        display: flex; align-items: center; gap: 12px;
        margin: 4px 0 20px;
        font-size: 12px; font-weight: 700; color: #9ca3af;
        text-transform: uppercase; letter-spacing: 1px;
    }
    .eng-fields-divider::before, .eng-fields-divider::after {
        content: ''; flex: 1; height: 1px; background: #e2e8f0;
    }

    /* Back link */
    .reg-back-link {
        display: inline-flex; align-items: center; gap: 6px;
        color: #9ca3af; font-size: 13px; text-decoration: none;
        margin-bottom: 20px; transition: color 0.2s;
    }
    .reg-back-link:hover { color: #1a3c5e; }

    /* Benefits strip */
    .reg-benefits {
        display: flex; flex-wrap: wrap; gap: 8px;
        margin-bottom: 24px;
    }
    .reg-benefit {
        display: inline-flex; align-items: center; gap: 5px;
        padding: 5px 12px;
        background: #f0fdf4; border: 1px solid #bbf7d0;
        border-radius: 20px; font-size: 12px; font-weight: 600; color: #065f46;
    }
    .reg-benefit i { color: #10b981; font-size: 11px; }

    @media (max-width: 600px) {
        .register-card-top { padding: 24px 20px 20px; }
        .register-card-body { padding: 24px 20px 28px; }
    }
    </style>
</head>
<body class="register-page-body">

<div class="register-card">
    <!-- Top coloured header with role switcher -->
    <div class="register-card-top">
        <div class="reg-brand">
            <div class="reg-brand-icon"><i class="fas fa-map-marked-alt"></i></div>
            <span class="reg-brand-name">LandSurvey</span>
        </div>
        <h2>Create Your Account</h2>
        <p>Join LandSurvey Portal — it's free</p>
        <!-- Role switcher as pills -->
        <div class="role-selector-new">
            <button type="button" class="role-pill active" id="pillClient" onclick="selectRole('client')">
                <i class="fas fa-user"></i> I'm a Client
            </button>
            <button type="button" class="role-pill" id="pillEngineer" onclick="selectRole('engineer')">
                <i class="fas fa-hard-hat"></i> I'm an Engineer
            </button>
        </div>
    </div>

    <!-- Form body -->
    <div class="register-card-body">
        <a href="../index.php" class="reg-back-link"><i class="fas fa-arrow-left"></i> Back to Home</a>

        <!-- Benefits -->
        <div class="reg-benefits">
            <span class="reg-benefit"><i class="fas fa-check"></i> Free account</span>
            <span class="reg-benefit"><i class="fas fa-check"></i> 50+ engineers</span>
            <span class="reg-benefit"><i class="fas fa-check"></i> Real-time tracking</span>
            <span class="reg-benefit"><i class="fas fa-check"></i> 24/7 AI support</span>
        </div>

        <?php if ($error): ?>
        <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data" class="auth-form" id="registerForm">
            <input type="hidden" name="role" id="roleInput" value="client">

            <div class="form-row">
                <div class="form-group">
                    <label>Full Name *</label>
                    <div class="input-wrapper">
                        <i class="fas fa-user input-icon"></i>
                        <input type="text" name="name" placeholder="Your full name"
                               value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" required>
                    </div>
                </div>
                <div class="form-group">
                    <label>Phone Number</label>
                    <div class="input-wrapper">
                        <i class="fas fa-phone input-icon"></i>
                        <input type="tel" name="phone" placeholder="+63 9XX XXX XXXX"
                               value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label>Email Address *</label>
                <div class="input-wrapper">
                    <i class="fas fa-envelope input-icon"></i>
                    <input type="email" name="email" placeholder="your@email.com"
                           value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Password *</label>
                    <div class="input-wrapper">
                        <i class="fas fa-lock input-icon"></i>
                        <input type="password" id="password" name="password" placeholder="Min. 6 characters" required>
                        <button type="button" class="toggle-password" onclick="togglePassword('password')">
                            <i class="fas fa-eye" id="password-eye"></i>
                        </button>
                    </div>
                </div>
                <div class="form-group">
                    <label>Confirm Password *</label>
                    <div class="input-wrapper">
                        <i class="fas fa-lock input-icon"></i>
                        <input type="password" id="confirm_password" name="confirm_password" placeholder="Repeat password" required>
                        <button type="button" class="toggle-password" onclick="togglePassword('confirm_password')">
                            <i class="fas fa-eye" id="confirm_password-eye"></i>
                        </button>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label>Address</label>
                <div class="input-wrapper">
                    <i class="fas fa-map-marker-alt input-icon"></i>
                    <input type="text" name="address" placeholder="Your address"
                           value="<?= htmlspecialchars($_POST['address'] ?? '') ?>">
                </div>
            </div>

            <!-- Engineer fields — smooth height animation, no layout jump -->
            <div class="engineer-fields-wrap" id="engineerFields">
                <div class="eng-fields-divider"><span>Engineer Details</span></div>
                <div class="form-row">
                    <div class="form-group">
                        <label>PRC License Number</label>
                        <div class="input-wrapper">
                            <i class="fas fa-id-card input-icon"></i>
                            <input type="text" name="license_number" placeholder="GE-XXXX-XXXXXX">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Years of Experience</label>
                        <div class="input-wrapper">
                            <i class="fas fa-briefcase input-icon"></i>
                            <input type="number" name="experience_years" placeholder="0" min="0" max="50">
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <label>Specialization</label>
                    <div class="input-wrapper">
                        <i class="fas fa-star input-icon"></i>
                        <input type="text" name="specialization" placeholder="e.g., Boundary & Topographic Survey">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Skills</label>
                        <div class="input-wrapper">
                            <i class="fas fa-tools input-icon"></i>
                            <input type="text" name="skills" placeholder="GPS, AutoCAD, Total Station...">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Hourly Rate (₱)</label>
                        <div class="input-wrapper">
                            <i class="fas fa-peso-sign input-icon"></i>
                            <input type="number" name="hourly_rate" placeholder="1500" min="0">
                        </div>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label>Profile Photo</label>
                <div class="photo-upload" id="photoUpload">
                    <input type="file" name="profile_photo" id="photoInput" accept="image/*" onchange="previewPhoto(this)">
                    <div class="photo-preview" id="photoPreview">
                        <i class="fas fa-camera"></i>
                        <span>Click to upload photo</span>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label class="checkbox-label terms-check">
                    <input type="checkbox" required>
                    I agree to the <a href="#">Terms of Service</a> and <a href="#">Privacy Policy</a>
                </label>
            </div>

            <button type="submit" class="btn-auth-submit" id="registerBtn">
                <span>Create Account</span>
                <i class="fas fa-arrow-right"></i>
            </button>
        </form>

        <div class="auth-footer">
            <p>Already have an account? <a href="login.php">Sign in</a></p>
        </div>
    </div>
</div>

<script>
function selectRole(role) {
    document.getElementById('roleInput').value = role;
    document.getElementById('pillClient').classList.toggle('active', role === 'client');
    document.getElementById('pillEngineer').classList.toggle('active', role === 'engineer');
    const wrap = document.getElementById('engineerFields');
    wrap.classList.toggle('open', role === 'engineer');
}

function togglePassword(id) {
    const inp = document.getElementById(id);
    const eye = document.getElementById(id + '-eye');
    if (inp.type === 'password') { inp.type = 'text'; eye.classList.replace('fa-eye','fa-eye-slash'); }
    else { inp.type = 'password'; eye.classList.replace('fa-eye-slash','fa-eye'); }
}

function previewPhoto(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = e => {
            document.getElementById('photoPreview').innerHTML =
                '<img src="' + e.target.result + '" style="width:80px;height:80px;border-radius:50%;object-fit:cover;">';
        };
        reader.readAsDataURL(input.files[0]);
    }
}

document.getElementById('registerForm').addEventListener('submit', function() {
    const btn = document.getElementById('registerBtn');
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Creating account...';
    btn.disabled = true;
});
</script>
</body>
</html>


