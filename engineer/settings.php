<?php
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../models/User.php';
require_once '../models/Engineer.php';

if (!isLoggedIn() || !hasRole('engineer')) redirect('/auth/login.php');

$db           = (new Database())->getConnection();
$userModel    = new User($db);
$engineerModel= new Engineer($db);
$user         = $userModel->getById($_SESSION['user_id']);
$engineer     = $engineerModel->getByUserId($_SESSION['user_id']);

$success = $error = '';
$activeTab = $_GET['tab'] ?? 'password';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'change_password') {
        $current = $_POST['current_password'] ?? '';
        $new     = $_POST['new_password']      ?? '';
        $confirm = $_POST['confirm_password']  ?? '';
        if (!password_verify($current, $user['password'])) {
            $error = 'Current password is incorrect.';
        } elseif (strlen($new) < 6) {
            $error = 'New password must be at least 6 characters.';
        } elseif ($new !== $confirm) {
            $error = 'New passwords do not match.';
        } else {
            $userModel->update($_SESSION['user_id'], ['password' => password_hash($new, PASSWORD_BCRYPT)]);
            $success = 'Password changed successfully.';
        }
        $activeTab = 'password';
    }

    if ($action === 'save_notifications') {
        $prefs = [
            'email_appointments' => isset($_POST['email_appointments']) ? 1 : 0,
            'email_payments'     => isset($_POST['email_payments'])     ? 1 : 0,
            'email_messages'     => isset($_POST['email_messages'])     ? 1 : 0,
            'email_status'       => isset($_POST['email_status'])       ? 1 : 0,
        ];
        try {
            $stmt = $db->prepare("UPDATE users SET notification_prefs = :prefs WHERE id = :id");
            $stmt->execute([':prefs' => json_encode($prefs), ':id' => $_SESSION['user_id']]);
        } catch (\Exception $e) {}
        $success = 'Notification preferences saved.';
        $activeTab = 'notifications';
    }

    if ($action === 'update_availability') {
        $status = $_POST['availability_status'] ?? 'available';
        if (in_array($status, ['available','busy','offline']) && $engineer) {
            $engineerModel->updateAvailability($engineer['id'], $status);
            $engineer = $engineerModel->getByUserId($_SESSION['user_id']);
            $success = 'Availability status updated.';
        }
        $activeTab = 'availability';
    }
}

$notifPrefs = ['email_appointments'=>1,'email_payments'=>1,'email_messages'=>1,'email_status'=>1];
try {
    $stmt = $db->prepare("SELECT notification_prefs FROM users WHERE id = :id");
    $stmt->execute([':id' => $_SESSION['user_id']]);
    $raw = $stmt->fetchColumn();
    if ($raw) $notifPrefs = array_merge($notifPrefs, json_decode($raw, true) ?? []);
} catch (\Exception $e) {}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Settings – Engineer | GeoSurvey</title>
<link rel="stylesheet" href="../assets/css/main.css">
<link rel="stylesheet" href="../assets/css/dashboard.css">
<link rel="stylesheet" href="../assets/css/profile.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<style>
.settings-layout{display:grid;grid-template-columns:240px 1fr;gap:24px;max-width:900px}
.settings-nav{background:#fff;border-radius:16px;box-shadow:0 4px 16px rgba(0,0,0,.07);overflow:hidden;height:fit-content}
.settings-nav-item{display:flex;align-items:center;gap:12px;padding:14px 20px;font-size:14px;font-weight:500;color:#6b7280;text-decoration:none;border-left:3px solid transparent;transition:all .2s;cursor:pointer;border:none;background:none;width:100%}
.settings-nav-item i{width:18px;text-align:center;font-size:15px}
.settings-nav-item:hover{background:#f8fafc;color:#1a3c5e}
.settings-nav-item.active{background:#f0f7ff;color:#1a3c5e;font-weight:700;border-left-color:#1a3c5e}
.settings-nav-divider{height:1px;background:#f1f5f9;margin:4px 0}
.settings-panel{display:none}.settings-panel.active{display:block}
.settings-card{background:#fff;border-radius:16px;box-shadow:0 4px 16px rgba(0,0,0,.07);overflow:hidden;margin-bottom:20px}
.settings-card-header{padding:20px 28px 16px;border-bottom:1px solid #f1f5f9;display:flex;align-items:center;gap:12px}
.settings-card-header-icon{width:40px;height:40px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:18px;color:#fff}
.settings-card-header h3{font-size:16px;font-weight:800;color:#1a1a2e;margin-bottom:2px}
.settings-card-header p{font-size:13px;color:#9ca3af}
.settings-card-body{padding:24px 28px}
.password-strength{margin-top:6px;height:4px;border-radius:4px;background:#e2e8f0;overflow:hidden}
.password-strength-bar{height:100%;border-radius:4px;transition:width .3s,background .3s;width:0}
.toggle-row{display:flex;align-items:center;justify-content:space-between;padding:14px 0;border-bottom:1px solid #f1f5f9}
.toggle-row:last-child{border-bottom:none}
.toggle-label strong{display:block;font-size:14px;font-weight:600;color:#1a1a2e}
.toggle-label span{font-size:12px;color:#9ca3af}
.toggle-switch{position:relative;width:44px;height:24px;flex-shrink:0}
.toggle-switch input{opacity:0;width:0;height:0}
.toggle-slider{position:absolute;inset:0;background:#d1d5db;border-radius:24px;cursor:pointer;transition:.3s}
.toggle-slider::before{content:'';position:absolute;width:18px;height:18px;left:3px;bottom:3px;background:#fff;border-radius:50%;transition:.3s;box-shadow:0 1px 3px rgba(0,0,0,.2)}
.toggle-switch input:checked+.toggle-slider{background:#1a3c5e}
.toggle-switch input:checked+.toggle-slider::before{transform:translateX(20px)}
.info-grid{display:grid;grid-template-columns:1fr 1fr;gap:16px}
.info-item{background:#f8fafc;border-radius:10px;padding:14px 16px}
.info-item label{display:block;font-size:11px;font-weight:700;color:#9ca3af;text-transform:uppercase;letter-spacing:.5px;margin-bottom:4px}
.info-item span{font-size:14px;font-weight:600;color:#1a1a2e}
.btn-settings-save{display:inline-flex;align-items:center;gap:8px;padding:11px 28px;background:linear-gradient(135deg,#1a3c5e,#2d6a9f);color:#fff;border:none;border-radius:10px;font-size:14px;font-weight:700;cursor:pointer;transition:all .2s;margin-top:8px}
.btn-settings-save:hover{transform:translateY(-1px);box-shadow:0 6px 18px rgba(26,60,94,.3)}
.avail-option{display:flex;align-items:center;gap:14px;padding:14px 16px;border:2px solid #e2e8f0;border-radius:12px;cursor:pointer;transition:all .2s;margin-bottom:10px}
.avail-option:hover{border-color:#2d6a9f;background:#f0f7ff}
.avail-option.selected{border-color:#1a3c5e;background:#f0f7ff}
.avail-option input{display:none}
.avail-dot{width:14px;height:14px;border-radius:50%;flex-shrink:0}
.avail-dot.available{background:#10b981}
.avail-dot.busy{background:#f59e0b}
.avail-dot.offline{background:#9ca3af}
@media(max-width:768px){.settings-layout{grid-template-columns:1fr}.info-grid{grid-template-columns:1fr}}
</style>
</head>
<body class="app-body">
<?php include '../includes/header.php'; ?>
<div class="app-layout">
<?php include '../includes/sidebar_engineer.php'; ?>
<main class="main-content">

<div class="page-header">
    <div><h1><i class="fas fa-cog"></i> Settings</h1><p>Manage your account preferences and availability</p></div>
</div>

<?php if ($success): ?><div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= htmlspecialchars($success) ?></div><?php endif; ?>
<?php if ($error):   ?><div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div><?php endif; ?>

<div class="settings-layout">
    <nav class="settings-nav">
        <a class="settings-nav-item <?= $activeTab==='password'      ?'active':'' ?>" onclick="switchTab('password')"      href="#password"><i class="fas fa-lock"></i> Password</a>
        <a class="settings-nav-item <?= $activeTab==='availability'  ?'active':'' ?>" onclick="switchTab('availability')"  href="#availability"><i class="fas fa-circle"></i> Availability</a>
        <a class="settings-nav-item <?= $activeTab==='notifications' ?'active':'' ?>" onclick="switchTab('notifications')" href="#notifications"><i class="fas fa-bell"></i> Notifications</a>
        <a class="settings-nav-item <?= $activeTab==='account'       ?'active':'' ?>" onclick="switchTab('account')"       href="#account"><i class="fas fa-user-circle"></i> Account Info</a>
        <div class="settings-nav-divider"></div>
        <a class="settings-nav-item" href="profile.php"><i class="fas fa-id-card"></i> Edit Profile</a>
        <a class="settings-nav-item" href="../auth/logout.php" style="color:#dc2626"><i class="fas fa-sign-out-alt"></i> Sign Out</a>
    </nav>

    <div>
        <!-- PASSWORD -->
        <div class="settings-panel <?= $activeTab==='password'?'active':'' ?>" id="panel-password">
            <div class="settings-card">
                <div class="settings-card-header">
                    <div class="settings-card-header-icon" style="background:linear-gradient(135deg,#667eea,#764ba2)"><i class="fas fa-lock"></i></div>
                    <div><h3>Change Password</h3><p>Keep your account secure with a strong password</p></div>
                </div>
                <div class="settings-card-body">
                    <form method="POST">
                        <input type="hidden" name="action" value="change_password">
                        <div class="form-group">
                            <label>Current Password</label>
                            <div class="input-wrapper"><i class="fas fa-lock input-icon"></i>
                            <input type="password" name="current_password" id="currentPw" placeholder="Enter current password" required>
                            <button type="button" class="toggle-password" onclick="togglePw('currentPw','eye1')"><i class="fas fa-eye" id="eye1"></i></button></div>
                        </div>
                        <div class="form-group">
                            <label>New Password</label>
                            <div class="input-wrapper"><i class="fas fa-lock input-icon"></i>
                            <input type="password" name="new_password" id="newPw" placeholder="Min. 6 characters" required oninput="checkStrength(this.value)">
                            <button type="button" class="toggle-password" onclick="togglePw('newPw','eye2')"><i class="fas fa-eye" id="eye2"></i></button></div>
                            <div class="password-strength"><div class="password-strength-bar" id="strengthBar"></div></div>
                            <small id="strengthLabel" style="font-size:11px;color:#9ca3af;margin-top:4px;display:block"></small>
                        </div>
                        <div class="form-group">
                            <label>Confirm New Password</label>
                            <div class="input-wrapper"><i class="fas fa-lock input-icon"></i>
                            <input type="password" name="confirm_password" id="confirmPw" placeholder="Repeat new password" required>
                            <button type="button" class="toggle-password" onclick="togglePw('confirmPw','eye3')"><i class="fas fa-eye" id="eye3"></i></button></div>
                        </div>
                        <button type="submit" class="btn-settings-save"><i class="fas fa-save"></i> Update Password</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- AVAILABILITY -->
        <div class="settings-panel <?= $activeTab==='availability'?'active':'' ?>" id="panel-availability">
            <div class="settings-card">
                <div class="settings-card-header">
                    <div class="settings-card-header-icon" style="background:linear-gradient(135deg,#43e97b,#38f9d7)"><i class="fas fa-circle"></i></div>
                    <div><h3>Availability Status</h3><p>Control whether clients can book appointments with you</p></div>
                </div>
                <div class="settings-card-body">
                    <form method="POST">
                        <input type="hidden" name="action" value="update_availability">
                        <?php $curAvail = $engineer['availability_status'] ?? 'available'; ?>
                        <?php foreach ([
                            ['available','Available','Clients can book appointments with you','#10b981'],
                            ['busy',     'Busy',     'You are working — no new bookings',     '#f59e0b'],
                            ['offline',  'Offline',  'You are not accepting any bookings',    '#9ca3af'],
                        ] as [$val,$lbl,$desc,$col]): ?>
                        <label class="avail-option <?= $curAvail===$val?'selected':'' ?>" onclick="selectAvail(this)">
                            <input type="radio" name="availability_status" value="<?= $val ?>" <?= $curAvail===$val?'checked':'' ?>>
                            <div class="avail-dot <?= $val ?>"></div>
                            <div style="flex:1">
                                <strong style="display:block;font-size:14px;font-weight:700;color:#1a1a2e"><?= $lbl ?></strong>
                                <span style="font-size:12px;color:#9ca3af"><?= $desc ?></span>
                            </div>
                            <?php if ($curAvail===$val): ?>
                            <i class="fas fa-check-circle" style="color:#1a3c5e;font-size:18px"></i>
                            <?php endif; ?>
                        </label>
                        <?php endforeach; ?>
                        <button type="submit" class="btn-settings-save"><i class="fas fa-save"></i> Save Availability</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- NOTIFICATIONS -->
        <div class="settings-panel <?= $activeTab==='notifications'?'active':'' ?>" id="panel-notifications">
            <div class="settings-card">
                <div class="settings-card-header">
                    <div class="settings-card-header-icon" style="background:linear-gradient(135deg,#f093fb,#f5576c)"><i class="fas fa-bell"></i></div>
                    <div><h3>Notification Preferences</h3><p>Choose what email notifications you receive</p></div>
                </div>
                <div class="settings-card-body">
                    <form method="POST">
                        <input type="hidden" name="action" value="save_notifications">
                        <?php foreach ([
                            ['email_appointments','fa-calendar-check','#1e40af','New Appointment Requests','When a client books an appointment with you'],
                            ['email_payments',    'fa-credit-card',   '#065f46','Payment Notifications',   'When a client submits payment'],
                            ['email_messages',    'fa-comment-dots',  '#5b21b6','New Messages',            'When a client sends you a message'],
                            ['email_status',      'fa-tasks',         '#92400e','Status Reminders',        'Reminders to update your survey progress'],
                        ] as [$name,$icon,$color,$title,$desc]): ?>
                        <div class="toggle-row">
                            <div class="toggle-label">
                                <strong><i class="fas <?= $icon ?>" style="color:<?= $color ?>;margin-right:6px"></i><?= $title ?></strong>
                                <span><?= $desc ?></span>
                            </div>
                            <label class="toggle-switch">
                                <input type="checkbox" name="<?= $name ?>" <?= $notifPrefs[$name]?'checked':'' ?>>
                                <span class="toggle-slider"></span>
                            </label>
                        </div>
                        <?php endforeach; ?>
                        <button type="submit" class="btn-settings-save"><i class="fas fa-save"></i> Save Preferences</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- ACCOUNT INFO -->
        <div class="settings-panel <?= $activeTab==='account'?'active':'' ?>" id="panel-account">
            <div class="settings-card">
                <div class="settings-card-header">
                    <div class="settings-card-header-icon" style="background:linear-gradient(135deg,#4facfe,#00f2fe)"><i class="fas fa-user-circle"></i></div>
                    <div><h3>Account Information</h3><p>Your account and professional details</p></div>
                </div>
                <div class="settings-card-body">
                    <div class="info-grid" style="margin-bottom:24px">
                        <div class="info-item"><label>Full Name</label><span><?= htmlspecialchars($user['name']) ?></span></div>
                        <div class="info-item"><label>Email</label><span><?= htmlspecialchars($user['email']) ?></span></div>
                        <div class="info-item"><label>License No.</label><span><?= htmlspecialchars($engineer['license_number'] ?? 'Not set') ?></span></div>
                        <div class="info-item"><label>Specialization</label><span><?= htmlspecialchars($engineer['specialization'] ?? 'Not set') ?></span></div>
                        <div class="info-item"><label>Experience</label><span><?= ($engineer['experience_years'] ?? 0) ?> years</span></div>
                        <div class="info-item"><label>Member Since</label><span><?= date('F d, Y', strtotime($user['created_at'])) ?></span></div>
                    </div>
                    <a href="profile.php" class="btn-settings-save" style="text-decoration:none;display:inline-flex">
                        <i class="fas fa-edit"></i> Edit Profile
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

</main></div>
<?php include '../includes/chatbot.php'; ?>
<script src="../assets/js/chatbot.js"></script>
<script>
function switchTab(tab) {
    event.preventDefault();
    document.querySelectorAll('.settings-panel').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.settings-nav-item').forEach(n => n.classList.remove('active'));
    document.getElementById('panel-' + tab).classList.add('active');
    event.currentTarget.classList.add('active');
    history.replaceState(null, '', '?tab=' + tab);
}
function togglePw(id, eyeId) {
    const inp = document.getElementById(id), eye = document.getElementById(eyeId);
    if (inp.type === 'password') { inp.type = 'text'; eye.classList.replace('fa-eye','fa-eye-slash'); }
    else { inp.type = 'password'; eye.classList.replace('fa-eye-slash','fa-eye'); }
}
function checkStrength(pw) {
    const bar = document.getElementById('strengthBar'), label = document.getElementById('strengthLabel');
    let s = 0;
    if (pw.length >= 6) s++; if (pw.length >= 10) s++;
    if (/[A-Z]/.test(pw)) s++; if (/[0-9]/.test(pw)) s++; if (/[^A-Za-z0-9]/.test(pw)) s++;
    const lvl = [
        {w:'0%',bg:'#e2e8f0',t:''},
        {w:'25%',bg:'#ef4444',t:'Weak'},
        {w:'50%',bg:'#f59e0b',t:'Fair'},
        {w:'75%',bg:'#3b82f6',t:'Good'},
        {w:'100%',bg:'#10b981',t:'Strong'}
    ][Math.min(s,4)];
    bar.style.width = lvl.w; bar.style.background = lvl.bg;
    label.textContent = lvl.t; label.style.color = lvl.bg;
}
function selectAvail(el) {
    document.querySelectorAll('.avail-option').forEach(o => o.classList.remove('selected'));
    el.classList.add('selected');
}
</script>
</body>
</html>
