<?php
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../models/User.php';

if (isLoggedIn()) {
    $role = getCurrentUserRole();
    if ($role === 'admin') redirect('/admin/dashboard.php');
    elseif ($role === 'engineer') redirect('/engineer/dashboard.php');
    else redirect('/client/dashboard.php');
}

$error = '';
$success = '';

// Check if fix_passwords.php needs to be run
$db_check = null;
try {
    $db_check = (new Database())->getConnection();
} catch (Exception $e) {
    $error = 'Database connection failed. Check config/database.php settings.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $db_check) {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Please fill in all fields.';
    } else {
        $userModel = new User($db_check);
        $user = $userModel->login($email, $password);

        if ($user) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['name'] = $user['name'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['profile_photo'] = $user['profile_photo'];

            if ($user['role'] === 'admin') redirect('/admin/dashboard.php');
            elseif ($user['role'] === 'engineer') redirect('/engineer/dashboard.php');
            else redirect('/client/dashboard.php');
        } else {
            // Check if user exists but password is wrong (helps diagnose hash issues)
            $stmt = $db_check->prepare("SELECT id, password FROM users WHERE email = :email AND is_active = 1 LIMIT 1");
            $stmt->execute([':email' => $email]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$row) {
                $error = 'No account found with that email address.';
            } else {
                // Hash in DB looks like the wrong Laravel hash — guide user to fix
                $storedHash = $row['password'];
                $isWrongHash = ($storedHash === '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');
                if ($isWrongHash) {
                    $error = 'Password hash mismatch detected. Please run <a href="../fix_passwords.php" style="color:#1e40af;font-weight:700;">fix_passwords.php</a> once to fix all demo account passwords.';
                } else {
                    $error = 'Invalid email or password. Please try again.';
                }
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
    <title>Sign In - Land Surveying Portal</title>
    <link rel="stylesheet" href="../assets/css/main.css">
    <link rel="stylesheet" href="../assets/css/auth.css">
    <link rel="stylesheet" href="../assets/css/enhancements.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body class="auth-body">

<div class="auth-container">
    <div class="auth-left">
        <div class="auth-left-content">
            <div class="auth-brand">
                <div class="brand-icon-lg"><i class="fas fa-map-marked-alt"></i></div>
                <h1>LandSurvey Portal</h1>
                <p>Professional Land Surveying Services</p>
            </div>
            <div class="auth-features">
                <div class="auth-feature">
                    <div class="feature-icon"><i class="fas fa-calendar-check"></i></div>
                    <div>
                        <h4>Easy Booking</h4>
                        <p>Book appointments with licensed engineers in minutes</p>
                    </div>
                </div>
                <div class="auth-feature">
                    <div class="feature-icon"><i class="fas fa-map-pin"></i></div>
                    <div>
                        <h4>Real-time Tracking</h4>
                        <p>Monitor your survey progress live</p>
                    </div>
                </div>
                <div class="auth-feature">
                    <div class="feature-icon"><i class="fas fa-shield-alt"></i></div>
                    <div>
                        <h4>Secure Platform</h4>
                        <p>Your data is protected and encrypted</p>
                    </div>
                </div>
            </div>
            <div class="auth-testimonial">
                <p>"The best platform for land surveying services. Professional, fast, and reliable!"</p>
                <div class="testimonial-mini">
                    <img src="../assets/images/client1.jpg" alt="" onerror="this.src='../assets/images/default_avatar.png'">
                    <span>Juan dela Cruz, Property Owner</span>
                </div>
            </div>
        </div>
    </div>

    <div class="auth-right">
        <div class="auth-form-container">
            <div class="auth-form-header">
                <a href="../index.php" class="back-link"><i class="fas fa-arrow-left"></i> Back to Home</a>
                <h2>Welcome Back</h2>
                <p>Sign in to your account to continue</p>
            </div>

            <?php if ($error): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i> <?= $error /* intentionally not escaped — contains trusted HTML link */ ?>
            </div>
            <?php endif; ?>

            <form method="POST" class="auth-form" id="loginForm">
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <div class="input-wrapper">
                        <i class="fas fa-envelope input-icon"></i>
                        <input type="email" id="email" name="email" placeholder="Enter your email" 
                               value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="input-wrapper">
                        <i class="fas fa-lock input-icon"></i>
                        <input type="password" id="password" name="password" placeholder="Enter your password" required>
                        <button type="button" class="toggle-password" onclick="togglePassword('password')">
                            <i class="fas fa-eye" id="password-eye"></i>
                        </button>
                    </div>
                </div>

                <div class="form-options">
                    <label class="checkbox-label">
                        <input type="checkbox" name="remember"> Remember me
                    </label>
                    <a href="#" class="forgot-link">Forgot password?</a>
                </div>

                <button type="submit" class="btn-auth-submit" id="loginBtn">
                    <span>Sign In</span>
                    <i class="fas fa-arrow-right"></i>
                </button>
            </form>

            <div class="auth-divider"><span>or sign in with</span></div>

            <div class="demo-accounts">
                <p class="demo-title">Demo Accounts</p>
                <div class="demo-grid">
                    <button class="demo-btn" onclick="fillDemo('client@test.com', '123456')">
                        <i class="fas fa-user"></i> Client
                    </button>
                    <button class="demo-btn" onclick="fillDemo('engineer@test.com', '123456')">
                        <i class="fas fa-hard-hat"></i> Engineer
                    </button>
                    <button class="demo-btn" onclick="fillDemo('admin@test.com', '123456')">
                        <i class="fas fa-user-shield"></i> Admin
                    </button>
                </div>
            </div>

            <div class="auth-footer">
                <p>Don't have an account? <a href="register.php">Create one free</a></p>
            </div>
        </div>
    </div>
</div>

<script>
function togglePassword(id) {
    const input = document.getElementById(id);
    const eye = document.getElementById(id + '-eye');
    if (input.type === 'password') {
        input.type = 'text';
        eye.classList.replace('fa-eye', 'fa-eye-slash');
    } else {
        input.type = 'password';
        eye.classList.replace('fa-eye-slash', 'fa-eye');
    }
}

function fillDemo(email, password) {
    document.getElementById('email').value = email;
    document.getElementById('password').value = password;
    document.getElementById('loginForm').submit();
}

document.getElementById('loginForm').addEventListener('submit', function() {
    const btn = document.getElementById('loginBtn');
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Signing in...';
    btn.disabled = true;
});
</script>
</body>
</html>
