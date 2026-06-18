<?php
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../models/User.php';

if (!isLoggedIn() || !hasRole('admin')) redirect('/auth/login.php');

$db        = (new Database())->getConnection();
$userModel = new User($db);
$user      = $userModel->getById($_SESSION['user_id']);

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

    if ($action === 'save_system') {
        // System settings — stored as a simple JSON file for now
        $settings = [
            'site_name'          => sanitize($_POST['site_name'] ?? 'GeoSurvey Portal'),
            'contact_email'      => sanitize($_POST['contact_email'] ?? ''),
            'contact_phone'      => sanitize($_POST['contact_phone'] ?? ''),
            'maintenance_mode'   => isset($_POST['maintenance_mode']) ? 1 : 0,
            'allow_registration' => isset($_POST['allow_registration']) ? 1 : 0,
        ];
        file_put_contents(__DIR__ . '/../config/system_settings.json', json_encode($settings, JSON_PRETTY_PRINT));
        $success = 'System settings saved.';
        $activeTab = 'system';
    }
}

// Load system settings
$sysSettings = ['site_name'=>'GeoSurvey Portal','contact_email'=>'','contact_phone'=>'','maintenance_mode'=>0,'allow_registration'=>1];
$sysFile = __DIR__ . '/../config/system_settings.json';
if (file_exists($sysFile)) {
    $sysSettings = array_merge($sysSettings, json_decode(file_get_contents($sysFile), true) ?? []);
}

// Quick stats
$statsStmt = $db->query("SELECT
    (SELECT COUNT(*) FROM users WHERE role='client')   AS clients,
    (SELECT COUNT(*) FROM users WHERE role='engineer') AS engineers,
    (SELECT COUNT(*) FROM appointments)                AS appointments,
    (SELECT COUNT(*) FROM payments WHERE status='verified') AS payments");
$stats = $statsStmt->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Settings – Admin | GeoSurvey</title>
<link rel="stylesheet" href="../assets/css/main.css">
<link rel="stylesheet" href="../assets/css/dashboard.css">
<link rel="stylesheet" href="../assets/css/profile.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<style>
.settings-layout{display:grid;grid-template-columns:240px 1fr;gap:24px;max-width:960px}
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
.sys-stat-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:24px}
.sys-stat{background:#f8fafc;border-radius:12px;padding:16px;text-align:center}
.sys-stat .num{font-size:28px;font-weight:900;color:#1a1a2e}
.sys-stat .lbl{font-size:12px;color:#9ca3af;font-weight:500}
@media(max-width:768px){.settings-layout{grid-template-columns:1fr}.info-grid{grid-template-columns:1fr}.sys-stat-grid{grid-template-columns:repeat(2,1fr)}}
</style>
</head>
<body class="app-body">
<?php include '../includes/header.php'; ?>
<div class="app-layout">
<?php include '../includes/sidebar_admin.php'; ?>
<main class="main-content">

<div class="page-header">
    <div><h1><i class="fas fa-cog"></i> Settings</h1><p>System configuration and account security</p></div>
</div>

<?php if ($success): ?><div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= htmlspecialchars($success) ?></div><?php endif; ?>
<?php if ($error):   ?><div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div><?php endif; ?>

<div class="settings-layout">
    <nav class="settings-nav">
        <a class="settings-nav-item <?= $activeTab==='password'?'active':'' ?>" onclick="switchTab('password')"  href="#password"><i class="fas fa-lock"></i> Password</a>
        <a class="settings-nav-item <?= $activeTab==='system'  ?'active':'' ?>" onclick="switchTab('system')"    href="#system"><i class="fas fa-server"></i> System</a>
        <a class="settings-nav-item <?= $activeTab==='account' ?'active':'' ?>" onclick="switchTab('account')"   href="#account"><i class="fas fa-user-shield"></i> Account Info</a>
        <div class="settings-nav-divider"></div>
        <a class="settings-nav-item" href="../auth/logout.php" style="color:#dc2626"><i class="fas fa-sign-out-alt"></i> Sign Out</a>
    </nav>

    <div>
        <!-- PASSWORD -->
        <div class="settings-panel <?= $activeTab==='password'?'active':'' ?>" id="panel-password">
            <div class="settings-card">
                <div class="settings-card-header">
                    <div class="settings-card-header-icon" style="background:linear-gradient(135deg,#667eea,#764ba2)"><i class="fas fa-lock"></i></div>
                    <div><h3>Change Password</h3><p>Keep your admin account secure</p></div>
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

        <!-- SYSTEM -->
        <div class="settings-panel <?= $activeTab==='system'?'active':'' ?>" id="panel-system">
            <!-- Stats -->
            <div class="settings-card">
                <div class="settings-card-header">
                    <div class="settings-card-header-icon" style="background:linear-gradient(135deg,#43e97b,#38f9d7)"><i class="fas fa-chart-bar"></i></div>
                    <div><h3>System Overview</h3><p>Current platform statistics</p></div>
                </div>
                <div class="settings-card-body">
                    <div class="sys-stat-grid">
                        <div class="sys-stat"><div class="num"><?= $stats['clients'] ?></div><div class="lbl">Clients</div></div>
                        <div class="sys-stat"><div class="num"><?= $stats['engineers'] ?></div><div class="lbl">Engineers</div></div>
                        <div class="sys-stat"><div class="num"><?= $stats['appointments'] ?></div><div class="lbl">Appointments</div></div>
                        <div class="sys-stat"><div class="num"><?= $stats['payments'] ?></div><div class="lbl">Verified Payments</div></div>
                    </div>
                </div>
            </div>

            <!-- System Config -->
            <div class="settings-card">
                <div class="settings-card-header">
                    <div class="settings-card-header-icon" style="background:linear-gradient(135deg,#4facfe,#00f2fe)"><i class="fas fa-server"></i></div>
                    <div><h3>System Configuration</h3><p>Global platform settings</p></div>
                </div>
                <div class="settings-card-body">
                    <form method="POST">
                        <input type="hidden" name="action" value="save_system">
                        <div class="form-row">
                            <div class="form-group">
                                <label>Site Name</label>
                                <div class="input-wrapper"><i class="fas fa-globe input-icon"></i>
                                <input type="text" name="site_name" value="<?= htmlspecialchars($sysSettings['site_name']) ?>"></div>
                            </div>
                            <div class="form-group">
                                <label>Contact Email</label>
                                <div class="input-wrapper"><i class="fas fa-envelope input-icon"></i>
                                <input type="email" name="contact_email" value="<?= htmlspecialchars($sysSettings['contact_email']) ?>"></div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Contact Phone</label>
                            <div class="input-wrapper"><i class="fas fa-phone input-icon"></i>
                            <input type="text" name="contact_phone" value="<?= htmlspecialchars($sysSettings['contact_phone']) ?>"></div>
                        </div>
                        <div class="toggle-row">
                            <div class="toggle-label">
                                <strong><i class="fas fa-user-plus" style="color:#1e40af;margin-right:6px"></i>Allow New Registrations</strong>
                                <span>Let new clients and engineers register</span>
                            </div>
                            <label class="toggle-switch">
                                <input type="checkbox" name="allow_registration" <?= $sysSettings['allow_registration']?'checked':'' ?>>
                                <span class="toggle-slider"></span>
                            </label>
                        </div>
                        <div class="toggle-row">
                            <div class="toggle-label">
                                <strong><i class="fas fa-tools" style="color:#dc2626;margin-right:6px"></i>Maintenance Mode</strong>
                                <span>Show maintenance page to all non-admin users</span>
                            </div>
                            <label class="toggle-switch">
                                <input type="checkbox" name="maintenance_mode" <?= $sysSettings['maintenance_mode']?'checked':'' ?>>
                                <span class="toggle-slider"></span>
                            </label>
                        </div>
                        <button type="submit" class="btn-settings-save"><i class="fas fa-save"></i> Save System Settings</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- ACCOUNT INFO -->
        <div class="settings-panel <?= $activeTab==='account'?'active':'' ?>" id="panel-account">
            <div class="settings-card">
                <div class="settings-card-header">
                    <div class="settings-card-header-icon" style="background:linear-gradient(135deg,#fa709a,#fee140)"><i class="fas fa-user-shield"></i></div>
                    <div><h3>Admin Account</h3><p>Your administrator account details</p></div>
                </div>
                <div class="settings-card-body">
                    <div class="info-grid">
                        <div class="info-item"><label>Full Name</label><span><?= htmlspecialchars($user['name']) ?></span></div>
                        <div class="info-item"><label>Email</label><span><?= htmlspecialchars($user['email']) ?></span></div>
                        <div class="info-item"><label>Role</label><span style="color:#dc2626;font-weight:800">Administrator</span></div>
                        <div class="info-item"><label>Member Since</label><span><?= date('F d, Y', strtotime($user['created_at'])) ?></span></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

</main></div>
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
    const lvl = [{w:'0%',bg:'#e2e8f0',t:''},{w:'25%',bg:'#ef4444',t:'Weak'},{w:'50%',bg:'#f59e0b',t:'Fair'},{w:'75%',bg:'#3b82f6',t:'Good'},{w:'100%',bg:'#10b981',t:'Strong'}][Math.min(s,4)];
    bar.style.width = lvl.w; bar.style.background = lvl.bg; label.textContent = lvl.t; label.style.color = lvl.bg;
}
</script>
</body>
</html>
