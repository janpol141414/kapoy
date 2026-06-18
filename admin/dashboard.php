<?php
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../models/Appointment.php';
require_once '../models/Payment.php';
require_once '../models/User.php';
require_once '../models/Engineer.php';

if (!isLoggedIn() || !hasRole('admin')) redirect('/auth/login.php');

$db = (new Database())->getConnection();
$appointmentModel = new Appointment($db);
$paymentModel     = new Payment($db);
$userModel        = new User($db);
$engineerModel    = new Engineer($db);

$appointmentStats  = $appointmentModel->getStats();
$paymentStats      = $paymentModel->getStats();
$recentAppointments= array_slice($appointmentModel->getAll(), 0, 8);
$pendingPayments   = $paymentModel->getAll(['status' => 'pending']);
$totalUsers        = count($userModel->getAll());
$totalEngineers    = count($engineerModel->getAll());
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Admin Dashboard – GeoSurvey Portal</title>
<link rel="stylesheet" href="../assets/css/main.css">
<link rel="stylesheet" href="../assets/css/dashboard.css">
<link rel="stylesheet" href="../assets/css/admin.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
<style>
/* ── Admin Dashboard Unique Styles ── */
.admin-hero {
    background: linear-gradient(135deg, #0f2540 0%, #1a3c5e 60%, #2d6a9f 100%);
    border-radius: 20px;
    padding: 28px 36px;
    margin-bottom: 28px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    box-shadow: 0 8px 32px rgba(26,60,94,0.2);
    position: relative;
    overflow: hidden;
}
.admin-hero::before {
    content: '';
    position: absolute;
    top: -50px; right: -50px;
    width: 220px; height: 220px;
    background: rgba(255,255,255,0.04);
    border-radius: 50%;
}
.admin-hero-text h1 { font-size: 26px; font-weight: 900; color: #fff; margin-bottom: 4px; }
.admin-hero-text p  { font-size: 14px; color: rgba(255,255,255,0.7); }
.admin-hero-time {
    text-align: right; color: rgba(255,255,255,0.8);
    font-size: 13px; z-index: 1;
}
.admin-hero-time strong { display: block; font-size: 22px; font-weight: 800; color: #fff; }

/* KPI cards */
.admin-kpi-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 18px;
    margin-bottom: 28px;
}
.admin-kpi-card {
    background: #fff;
    border-radius: 18px;
    padding: 22px 24px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.06);
    border: 1px solid #f1f5f9;
    position: relative;
    overflow: hidden;
    transition: all 0.25s;
}
.admin-kpi-card:hover { transform: translateY(-3px); box-shadow: 0 8px 28px rgba(0,0,0,0.1); }
.admin-kpi-card::after {
    content: '';
    position: absolute;
    top: 0; right: 0;
    width: 80px; height: 80px;
    border-radius: 0 18px 0 80px;
    opacity: 0.06;
}
.admin-kpi-top { display: flex; align-items: flex-start; justify-content: space-between; margin-bottom: 14px; }
.admin-kpi-icon {
    width: 48px; height: 48px;
    border-radius: 14px;
    display: flex; align-items: center; justify-content: center;
    font-size: 20px; color: #fff;
}
.admin-kpi-trend {
    display: inline-flex; align-items: center; gap: 4px;
    padding: 3px 8px;
    border-radius: 20px;
    font-size: 11px; font-weight: 700;
}
.admin-kpi-trend.up   { background: #d1fae5; color: #065f46; }
.admin-kpi-trend.warn { background: #fef3c7; color: #92400e; }
.admin-kpi-value { font-size: 32px; font-weight: 900; color: #1a1a2e; line-height: 1; margin-bottom: 4px; }
.admin-kpi-label { font-size: 13px; color: #9ca3af; font-weight: 500; }
.admin-kpi-sub   { font-size: 11px; color: #b0b8c4; margin-top: 8px; }

/* Content grid */
.admin-content-grid {
    display: grid;
    grid-template-columns: 1fr 360px;
    gap: 20px;
    margin-bottom: 20px;
}

/* Section cards */
.admin-section {
    background: #fff;
    border-radius: 18px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.06);
    border: 1px solid #f1f5f9;
    overflow: hidden;
}
.admin-section-header {
    display: flex; align-items: center; justify-content: space-between;
    padding: 18px 24px;
    border-bottom: 1px solid #f8fafc;
}
.admin-section-header h3 {
    font-size: 15px; font-weight: 800; color: #1a1a2e;
    display: flex; align-items: center; gap: 8px;
}
.admin-section-header h3 i { font-size: 16px; }
.admin-section-link {
    font-size: 12px; font-weight: 700; color: var(--primary);
    text-decoration: none; display: flex; align-items: center; gap: 4px;
    padding: 5px 12px; border-radius: 8px; background: #f0f7ff;
    transition: all 0.2s;
}
.admin-section-link:hover { background: var(--primary); color: #fff; }

/* Appointments table */
.admin-apt-table { width: 100%; border-collapse: collapse; }
.admin-apt-table th {
    padding: 10px 16px;
    text-align: left; font-size: 11px; font-weight: 700;
    text-transform: uppercase; letter-spacing: 0.5px;
    color: #9ca3af; background: #f8fafc;
    border-bottom: 1px solid #f1f5f9;
}
.admin-apt-table td {
    padding: 13px 16px;
    border-bottom: 1px solid #f8fafc;
    font-size: 13px; color: #374151;
    vertical-align: middle;
}
.admin-apt-table tr:last-child td { border-bottom: none; }
.admin-apt-table tr:hover td { background: #f8fafc; }
.admin-user-cell { display: flex; align-items: center; gap: 9px; }
.admin-user-cell img { width: 30px; height: 30px; border-radius: 50%; object-fit: cover; }
.admin-user-cell span { font-weight: 600; color: #1a1a2e; }

/* Pending payments list */
.admin-pay-list { padding: 8px 0; }
.admin-pay-item {
    display: flex; align-items: center; gap: 12px;
    padding: 13px 24px;
    border-bottom: 1px solid #f8fafc;
    transition: background 0.15s;
}
.admin-pay-item:last-child { border-bottom: none; }
.admin-pay-item:hover { background: #f8fafc; }
.admin-pay-avatar {
    width: 38px; height: 38px;
    border-radius: 50%; object-fit: cover; flex-shrink: 0;
}
.admin-pay-info { flex: 1; min-width: 0; }
.admin-pay-info strong { display: block; font-size: 13px; font-weight: 700; color: #1a1a2e; }
.admin-pay-info span   { display: block; font-size: 11px; color: #9ca3af; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.admin-pay-amount { font-size: 14px; font-weight: 800; color: #1a3c5e; flex-shrink: 0; }
.admin-pay-verify {
    padding: 5px 12px;
    background: linear-gradient(135deg, #10b981, #059669);
    color: #fff; border: none; border-radius: 8px;
    font-size: 11px; font-weight: 700; cursor: pointer;
    text-decoration: none; transition: all 0.2s; flex-shrink: 0;
}
.admin-pay-verify:hover { transform: translateY(-1px); box-shadow: 0 4px 10px rgba(16,185,129,0.3); }

/* Quick nav */
.admin-quick-nav {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 14px;
    margin-bottom: 20px;
}
.admin-nav-btn {
    display: flex; flex-direction: column; align-items: center; gap: 8px;
    padding: 18px 12px;
    background: #fff;
    border-radius: 16px;
    border: 1.5px solid #f1f5f9;
    text-decoration: none;
    transition: all 0.2s;
    box-shadow: 0 2px 8px rgba(0,0,0,0.04);
}
.admin-nav-btn:hover { border-color: var(--primary-light); transform: translateY(-2px); box-shadow: 0 6px 20px rgba(0,0,0,0.08); }
.admin-nav-icon {
    width: 44px; height: 44px;
    border-radius: 12px;
    display: flex; align-items: center; justify-content: center;
    font-size: 18px; color: #fff;
}
.admin-nav-btn span { font-size: 12px; font-weight: 700; color: #374151; text-align: center; }

/* Admin search bar in card header */
.admin-dash-search {
    position: relative;
    display: flex;
    align-items: center;
}
.admin-dash-search i {
    position: absolute;
    left: 12px;
    color: #9ca3af;
    font-size: 13px;
    pointer-events: none;
}
.admin-dash-search input {
    padding: 7px 12px 7px 34px;
    border: 1.5px solid #e2e8f0;
    border-radius: 20px;
    font-size: 13px;
    outline: none;
    width: 180px;
    transition: all 0.2s;
    font-family: inherit;
    background: #f8fafc;
}
.admin-dash-search input:focus {
    border-color: #2d6a9f;
    background: #fff;
    box-shadow: 0 0 0 3px rgba(45,106,159,0.1);
}
body.dark-mode .admin-dash-search input {
    background: #0f172a !important;
    border-color: #334155 !important;
    color: #f1f5f9 !important;
}
body.dark-mode .admin-dash-search i { color: #64748b !important; }

/* Dark mode */
body.dark-mode .admin-kpi-card,
body.dark-mode .admin-section,
body.dark-mode .admin-nav-btn { background: #1e293b !important; border-color: #334155 !important; }
body.dark-mode .admin-kpi-value { color: #f1f5f9 !important; }
body.dark-mode .admin-kpi-label { color: #94a3b8 !important; }
body.dark-mode .admin-kpi-sub   { color: #64748b !important; }
body.dark-mode .admin-section-header { border-color: #334155 !important; }
body.dark-mode .admin-section-header h3 { color: #f1f5f9 !important; }
body.dark-mode .admin-section-link { background: #1e3a5f !important; color: #60a5fa !important; }
body.dark-mode .admin-section-link:hover { background: #60a5fa !important; color: #0f172a !important; }
body.dark-mode .admin-apt-table th { background: #0f172a !important; color: #64748b !important; border-color: #334155 !important; }
body.dark-mode .admin-apt-table td { color: #cbd5e1 !important; border-color: #1e293b !important; }
body.dark-mode .admin-apt-table tr:hover td { background: #334155 !important; }
body.dark-mode .admin-user-cell span { color: #f1f5f9 !important; }
body.dark-mode .admin-pay-item { border-color: #1e293b !important; }
body.dark-mode .admin-pay-item:hover { background: #334155 !important; }
body.dark-mode .admin-pay-info strong { color: #f1f5f9 !important; }
body.dark-mode .admin-pay-info span   { color: #94a3b8 !important; }
body.dark-mode .admin-pay-amount { color: #60a5fa !important; }
body.dark-mode .admin-nav-btn span { color: #cbd5e1 !important; }

@media (max-width: 1100px) {
    .admin-content-grid { grid-template-columns: 1fr; }
    .admin-kpi-grid { grid-template-columns: repeat(2,1fr); }
    .admin-quick-nav { grid-template-columns: repeat(4,1fr); }
}
@media (max-width: 600px) {
    .admin-kpi-grid { grid-template-columns: 1fr; }
    .admin-quick-nav { grid-template-columns: repeat(2,1fr); }
}
</style>
</head>
<body class="app-body">
<?php include '../includes/header.php'; ?>
<div class="app-layout">
<?php include '../includes/sidebar_admin.php'; ?>
<main class="main-content">

    <!-- ── Hero ── -->
    <div class="admin-hero">
        <div class="admin-hero-text">
            <h1>🛠️ Admin Dashboard</h1>
            <p>Welcome back, <?= htmlspecialchars($_SESSION['name']) ?>. Here's your system overview.</p>
        </div>
        <div class="admin-hero-time">
            <strong id="adminClock"></strong>
            <span><?= date('l, F d, Y') ?></span>
        </div>
    </div>

    <!-- ── KPI Cards ── -->
    <div class="admin-kpi-grid">
        <div class="admin-kpi-card">
            <div class="admin-kpi-top">
                <div class="admin-kpi-icon" style="background:linear-gradient(135deg,#667eea,#764ba2)"><i class="fas fa-calendar-alt"></i></div>
                <span class="admin-kpi-trend up"><i class="fas fa-arrow-up"></i> Active</span>
            </div>
            <div class="admin-kpi-value"><?= $appointmentStats['total'] ?></div>
            <div class="admin-kpi-label">Total Appointments</div>
            <div class="admin-kpi-sub">Pending: <?= $appointmentStats['pending'] ?> · Completed: <?= $appointmentStats['completed'] ?></div>
        </div>
        <div class="admin-kpi-card">
            <div class="admin-kpi-top">
                <div class="admin-kpi-icon" style="background:linear-gradient(135deg,#43e97b,#38f9d7)"><i class="fas fa-peso-sign"></i></div>
                <span class="admin-kpi-trend up"><i class="fas fa-arrow-up"></i> Revenue</span>
            </div>
            <div class="admin-kpi-value">₱<?= number_format($paymentStats['total_revenue'] ?? 0, 0) ?></div>
            <div class="admin-kpi-label">Total Revenue</div>
            <div class="admin-kpi-sub">Verified: <?= $paymentStats['verified'] ?> · Pending: <?= $paymentStats['pending'] ?></div>
        </div>
        <div class="admin-kpi-card">
            <div class="admin-kpi-top">
                <div class="admin-kpi-icon" style="background:linear-gradient(135deg,#4facfe,#00f2fe)"><i class="fas fa-users"></i></div>
                <span class="admin-kpi-trend up"><i class="fas fa-arrow-up"></i> Growing</span>
            </div>
            <div class="admin-kpi-value"><?= $totalUsers ?></div>
            <div class="admin-kpi-label">Total Users</div>
            <div class="admin-kpi-sub">Engineers: <?= $totalEngineers ?></div>
        </div>
        <div class="admin-kpi-card">
            <div class="admin-kpi-top">
                <div class="admin-kpi-icon" style="background:linear-gradient(135deg,#f093fb,#f5576c)"><i class="fas fa-credit-card"></i></div>
                <span class="admin-kpi-trend warn"><i class="fas fa-clock"></i> Pending</span>
            </div>
            <div class="admin-kpi-value"><?= $paymentStats['pending'] ?></div>
            <div class="admin-kpi-label">Payments to Verify</div>
            <div class="admin-kpi-sub">Rejected: <?= $paymentStats['rejected'] ?></div>
        </div>
    </div>

    <!-- ── Quick Navigation ── -->
    <div class="admin-quick-nav">
        <?php
        $navItems = [
            ['appointments.php','fa-calendar-alt','Appointments','linear-gradient(135deg,#667eea,#764ba2)'],
            ['payments.php',    'fa-credit-card', 'Payments',    'linear-gradient(135deg,#43e97b,#38f9d7)'],
            ['engineers.php',   'fa-hard-hat',    'Engineers',   'linear-gradient(135deg,#4facfe,#00f2fe)'],
            ['companies.php',   'fa-building',    'Companies',   'linear-gradient(135deg,#f093fb,#f5576c)'],
            ['schedules.php',   'fa-clock',       'Schedules',   'linear-gradient(135deg,#fa709a,#fee140)'],
            ['feedback.php',    'fa-star',        'Feedback',    'linear-gradient(135deg,#a18cd1,#fbc2eb)'],
            ['users.php',       'fa-users',       'Users',       'linear-gradient(135deg,#43e97b,#38f9d7)'],
            ['settings.php',    'fa-cog',         'Settings',    'linear-gradient(135deg,#667eea,#764ba2)'],
        ];
        foreach ($navItems as [$href,$icon,$label,$grad]): ?>
        <a href="<?= $href ?>" class="admin-nav-btn">
            <div class="admin-nav-icon" style="background:<?= $grad ?>"><i class="fas <?= $icon ?>"></i></div>
            <span><?= $label ?></span>
        </a>
        <?php endforeach; ?>
    </div>

    <!-- ── Content Grid ── -->
    <div class="admin-content-grid">

        <!-- Recent Appointments -->
        <div class="admin-section">
            <div class="admin-section-header">
                <h3><i class="fas fa-calendar-alt" style="color:#667eea"></i> Recent Appointments</h3>
                <div style="display:flex;align-items:center;gap:10px">
                    <div class="admin-dash-search">
                        <i class="fas fa-search"></i>
                        <input type="text" id="adminAptSearch" placeholder="Search…" oninput="filterAdminApts(this.value)">
                    </div>
                    <a href="appointments.php" class="admin-section-link">View All <i class="fas fa-arrow-right"></i></a>
                </div>
            </div>
            <?php if (empty($recentAppointments)): ?>
            <div class="empty-state small"><i class="fas fa-calendar-times"></i><p>No appointments yet</p></div>
            <?php else: ?>
            <div style="overflow-x:auto">
                <table class="admin-apt-table">
                    <thead>
                        <tr>
                            <th>Client</th>
                            <th>Engineer</th>
                            <th>Service</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($recentAppointments as $apt): ?>
                    <tr>
                        <td>
                            <div class="admin-user-cell">
                                <img src="<?= UPLOADS_URL ?>/profiles/<?= $apt['client_photo'] ?? 'default_avatar.png' ?>" alt=""
                                     onerror="this.src='<?= ASSETS_URL ?>/images/default_avatar.png'">
                                <span><?= htmlspecialchars($apt['client_name']) ?></span>
                            </div>
                        </td>
                        <td>
                            <div class="admin-user-cell">
                                <img src="<?= UPLOADS_URL ?>/profiles/<?= $apt['engineer_photo'] ?? 'default_avatar.png' ?>" alt=""
                                     onerror="this.src='<?= ASSETS_URL ?>/images/default_avatar.png'">
                                <span><?= htmlspecialchars($apt['engineer_name']) ?></span>
                            </div>
                        </td>
                        <td style="font-weight:600"><?= htmlspecialchars($apt['service_type']) ?></td>
                        <td><?= date('M d, Y', strtotime($apt['appointment_date'])) ?></td>
                        <td><span class="status-badge <?= $apt['status'] ?>"><?= ucfirst(str_replace('_',' ',$apt['status'])) ?></span></td>
                        <td><a href="appointments.php?id=<?= $apt['id'] ?>" class="btn-table-action"><i class="fas fa-eye"></i></a></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>

        <!-- Pending Payments -->
        <div class="admin-section">
            <div class="admin-section-header">
                <h3><i class="fas fa-credit-card" style="color:#10b981"></i> Pending Payments</h3>
                <a href="payments.php" class="admin-section-link">View All <i class="fas fa-arrow-right"></i></a>
            </div>
            <?php if (empty($pendingPayments)): ?>
            <div class="empty-state small"><i class="fas fa-check-circle" style="color:#10b981"></i><p>All payments verified!</p></div>
            <?php else: ?>
            <div class="admin-pay-list">
                <?php foreach (array_slice($pendingPayments, 0, 6) as $pay): ?>
                <div class="admin-pay-item">
                    <img src="<?= UPLOADS_URL ?>/profiles/<?= $pay['client_photo'] ?? 'default_avatar.png' ?>"
                         alt="" class="admin-pay-avatar"
                         onerror="this.src='<?= ASSETS_URL ?>/images/default_avatar.png'">
                    <div class="admin-pay-info">
                        <strong><?= htmlspecialchars($pay['client_name']) ?></strong>
                        <span><?= htmlspecialchars($pay['service_type']) ?></span>
                    </div>
                    <span class="admin-pay-amount">₱<?= number_format($pay['amount'], 0) ?></span>
                    <a href="payments.php?id=<?= $pay['id'] ?>" class="admin-pay-verify">Verify</a>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

    </div>

</main>
</div>

<script src="../assets/js/dashboard.js"></script>
<script>
// Live clock
function updateClock() {
    const now = new Date();
    let h = now.getHours(), m = now.getMinutes(), s = now.getSeconds();
    const ap = h >= 12 ? 'PM' : 'AM';
    h = h % 12 || 12;
    document.getElementById('adminClock').textContent =
        h + ':' + String(m).padStart(2,'0') + ':' + String(s).padStart(2,'0') + ' ' + ap;
}
updateClock();
setInterval(updateClock, 1000);

// Live filter for appointments table
function filterAdminApts(q) {
    q = q.toLowerCase().trim();
    document.querySelectorAll('.admin-apt-table tbody tr').forEach(function(row) {
        row.style.display = (!q || row.textContent.toLowerCase().includes(q)) ? '' : 'none';
    });
}

// Auto-refresh KPI numbers every 30 seconds
const BASE_URL_ADMIN = '<?= BASE_URL ?>';
setInterval(function() {
    fetch(BASE_URL_ADMIN + '/api/sync.php?since=' + encodeURIComponent(
        new Date(Date.now() - 35000).toISOString().slice(0,19).replace('T',' ')
    ))
    .then(r => r.json())
    .then(data => {
        if (data.updates && data.updates.length > 0 && !document.querySelector(':focus') && !document.hidden) {
            location.reload();
        }
    })
    .catch(() => {});
}, 30000);
</script>
</body>
</html>
