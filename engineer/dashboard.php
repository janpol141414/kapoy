<?php
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../models/Appointment.php';
require_once '../models/Engineer.php';

if (!isLoggedIn() || !hasRole('engineer')) redirect('/auth/login.php');

$db = (new Database())->getConnection();
$engineerModel    = new Engineer($db);
$appointmentModel = new Appointment($db);

$engineer = $engineerModel->getByUserId($_SESSION['user_id']);
if (!$engineer) redirect('/auth/login.php');

$appointments = $appointmentModel->getByEngineerId($engineer['id']);
$stats = [
    'total'     => count($appointments),
    'pending'   => count(array_filter($appointments, fn($a) => $a['status'] === 'pending')),
    'confirmed' => count(array_filter($appointments, fn($a) => $a['status'] === 'confirmed')),
    'completed' => count(array_filter($appointments, fn($a) => $a['status'] === 'completed')),
];
$upcoming = array_slice(
    array_filter($appointments, fn($a) => in_array($a['status'], ['pending','confirmed','in_progress'])),
    0, 5
);

$availColors = [
    'available' => ['bg'=>'#d1fae5','color'=>'#065f46','dot'=>'#10b981','label'=>'Available'],
    'busy'      => ['bg'=>'#fef3c7','color'=>'#92400e','dot'=>'#f59e0b','label'=>'Busy'],
    'offline'   => ['bg'=>'#f3f4f6','color'=>'#6b7280','dot'=>'#9ca3af','label'=>'Offline'],
];
$avail = $availColors[$engineer['availability_status']] ?? $availColors['offline'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Engineer Dashboard – GeoSurvey Portal</title>
<link rel="stylesheet" href="../assets/css/main.css">
<link rel="stylesheet" href="../assets/css/dashboard.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
<style>
/* ── Engineer Dashboard Unique Styles ── */
.eng-hero {
    background: linear-gradient(135deg, #0f2540 0%, #1a3c5e 50%, #2d6a9f 100%);
    border-radius: 20px;
    padding: 32px;
    margin-bottom: 24px;
    display: flex;
    align-items: center;
    gap: 28px;
    position: relative;
    overflow: hidden;
    box-shadow: 0 8px 32px rgba(26,60,94,0.25);
}
.eng-hero::before {
    content: '';
    position: absolute;
    top: -40px; right: -40px;
    width: 200px; height: 200px;
    background: rgba(255,255,255,0.05);
    border-radius: 50%;
}
.eng-hero::after {
    content: '';
    position: absolute;
    bottom: -60px; right: 80px;
    width: 160px; height: 160px;
    background: rgba(79,172,254,0.08);
    border-radius: 50%;
}
.eng-hero-avatar {
    position: relative;
    flex-shrink: 0;
    z-index: 1;
}
.eng-hero-avatar img {
    width: 90px; height: 90px;
    border-radius: 50%;
    object-fit: cover;
    border: 4px solid rgba(255,255,255,0.3);
    box-shadow: 0 4px 16px rgba(0,0,0,0.3);
}
.eng-hero-avatar .avail-ring {
    position: absolute;
    bottom: 4px; right: 4px;
    width: 20px; height: 20px;
    border-radius: 50%;
    border: 3px solid #1a3c5e;
}
.eng-hero-info { flex: 1; z-index: 1; }
.eng-hero-info h1 {
    font-size: 26px; font-weight: 900;
    color: #fff; margin-bottom: 4px;
}
.eng-hero-info .eng-title {
    font-size: 14px; color: rgba(255,255,255,0.75);
    margin-bottom: 10px;
}
.eng-hero-rating {
    display: flex; align-items: center; gap: 8px;
    margin-bottom: 12px;
}
.eng-hero-rating .stars i { color: #f59e0b; font-size: 14px; }
.eng-hero-rating span { color: rgba(255,255,255,0.8); font-size: 13px; }
.eng-hero-badges { display: flex; gap: 8px; flex-wrap: wrap; }
.eng-hero-badge {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 4px 12px;
    background: rgba(255,255,255,0.12);
    border: 1px solid rgba(255,255,255,0.2);
    border-radius: 20px;
    font-size: 12px; font-weight: 600; color: #fff;
    backdrop-filter: blur(4px);
}
.eng-hero-actions { display: flex; flex-direction: column; gap: 10px; z-index: 1; flex-shrink: 0; }
.eng-avail-select {
    display: flex; align-items: center; gap: 8px;
    padding: 8px 14px;
    background: rgba(255,255,255,0.12);
    border: 1px solid rgba(255,255,255,0.25);
    border-radius: 10px;
    backdrop-filter: blur(4px);
}
.eng-avail-select label { font-size: 11px; color: rgba(255,255,255,0.7); font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; white-space: nowrap; }
.eng-avail-select select {
    background: transparent; border: none; color: #fff;
    font-size: 13px; font-weight: 700; outline: none; cursor: pointer;
    font-family: inherit;
}
.eng-avail-select select option { background: #1a3c5e; color: #fff; }
.btn-edit-profile {
    display: inline-flex; align-items: center; gap: 7px;
    padding: 9px 18px;
    background: rgba(255,255,255,0.15);
    border: 1.5px solid rgba(255,255,255,0.3);
    border-radius: 10px;
    color: #fff; font-size: 13px; font-weight: 600;
    text-decoration: none; transition: all 0.2s;
    backdrop-filter: blur(4px);
}
.btn-edit-profile:hover { background: rgba(255,255,255,0.25); }

/* Stats row */
.eng-stats-row {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 16px;
    margin-bottom: 24px;
}
.eng-stat-card {
    background: #fff;
    border-radius: 16px;
    padding: 20px 22px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.06);
    border: 1px solid #f1f5f9;
    display: flex;
    align-items: center;
    gap: 14px;
    transition: all 0.25s;
    position: relative;
    overflow: hidden;
}
.eng-stat-card::before {
    content: '';
    position: absolute;
    left: 0; top: 0; bottom: 0;
    width: 4px;
    background: var(--accent-color, #667eea);
    border-radius: 4px 0 0 4px;
}
.eng-stat-card:hover { transform: translateY(-3px); box-shadow: 0 8px 24px rgba(0,0,0,0.1); }
.eng-stat-icon {
    width: 48px; height: 48px;
    border-radius: 12px;
    display: flex; align-items: center; justify-content: center;
    font-size: 20px; color: #fff;
    flex-shrink: 0;
}
.eng-stat-info { flex: 1; }
.eng-stat-value { display: block; font-size: 28px; font-weight: 900; color: #1a1a2e; line-height: 1; }
.eng-stat-label { display: block; font-size: 12px; color: #9ca3af; font-weight: 500; margin-top: 3px; }

/* Quick actions */
.eng-quick-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 12px;
    margin-bottom: 24px;
}
.eng-quick-btn {
    display: flex; align-items: center; gap: 12px;
    padding: 16px 18px;
    background: #fff;
    border-radius: 14px;
    border: 1.5px solid #f1f5f9;
    text-decoration: none;
    transition: all 0.2s;
    box-shadow: 0 2px 8px rgba(0,0,0,0.04);
}
.eng-quick-btn:hover { border-color: var(--primary-light); transform: translateY(-2px); box-shadow: 0 6px 20px rgba(0,0,0,0.08); }
.eng-quick-icon {
    width: 40px; height: 40px;
    border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    font-size: 17px; color: #fff; flex-shrink: 0;
}
.eng-quick-text strong { display: block; font-size: 13px; font-weight: 700; color: #1a1a2e; }
.eng-quick-text span   { display: block; font-size: 11px; color: #9ca3af; }

/* Appointments table */
.eng-apt-table { width: 100%; border-collapse: collapse; }
.eng-apt-table th {
    padding: 10px 16px;
    text-align: left;
    font-size: 11px; font-weight: 700;
    text-transform: uppercase; letter-spacing: 0.5px;
    color: #9ca3af;
    background: #f8fafc;
    border-bottom: 1px solid #f1f5f9;
}
.eng-apt-table td {
    padding: 14px 16px;
    border-bottom: 1px solid #f8fafc;
    vertical-align: middle;
    font-size: 13.5px;
    color: #374151;
}
.eng-apt-table tr:last-child td { border-bottom: none; }
.eng-apt-table tr:hover td { background: #f8fafc; }
.eng-apt-client { display: flex; align-items: center; gap: 10px; }
.eng-apt-client img { width: 34px; height: 34px; border-radius: 50%; object-fit: cover; }
.eng-apt-client strong { display: block; font-size: 13px; font-weight: 700; color: #1a1a2e; }
.eng-apt-client span   { display: block; font-size: 11px; color: #9ca3af; }
.eng-apt-actions { display: flex; gap: 6px; }
.eng-apt-action {
    width: 32px; height: 32px;
    border-radius: 8px;
    display: flex; align-items: center; justify-content: center;
    font-size: 13px; text-decoration: none;
    transition: all 0.2s;
}
.eng-apt-action.view { background: #f0f7ff; color: #1a3c5e; }
.eng-apt-action.view:hover { background: #1a3c5e; color: #fff; }

/* Search bar in card header */
.eng-dash-search {
    position: relative;
    display: flex;
    align-items: center;
}
.eng-dash-search i {
    position: absolute;
    left: 12px;
    color: #9ca3af;
    font-size: 13px;
    pointer-events: none;
}
.eng-dash-search input {
    padding: 7px 12px 7px 34px;
    border: 1.5px solid #e2e8f0;
    border-radius: 20px;
    font-size: 13px;
    outline: none;
    width: 200px;
    transition: all 0.2s;
    font-family: inherit;
    background: #f8fafc;
}
.eng-dash-search input:focus {
    border-color: #2d6a9f;
    background: #fff;
    box-shadow: 0 0 0 3px rgba(45,106,159,0.1);
}
body.dark-mode .eng-dash-search input {
    background: #0f172a !important;
    border-color: #334155 !important;
    color: #f1f5f9 !important;
}
body.dark-mode .eng-dash-search i { color: #64748b !important; }
body.dark-mode .eng-stat-value { color: #f1f5f9 !important; }
body.dark-mode .eng-stat-label { color: #94a3b8 !important; }
body.dark-mode .eng-quick-btn  { background: #1e293b !important; border-color: #334155 !important; }
body.dark-mode .eng-quick-text strong { color: #f1f5f9 !important; }
body.dark-mode .eng-quick-text span   { color: #94a3b8 !important; }
body.dark-mode .eng-apt-table th { background: #0f172a !important; color: #64748b !important; border-color: #334155 !important; }
body.dark-mode .eng-apt-table td { color: #cbd5e1 !important; border-color: #1e293b !important; }
body.dark-mode .eng-apt-table tr:hover td { background: #334155 !important; }
body.dark-mode .eng-apt-client strong { color: #f1f5f9 !important; }
body.dark-mode .eng-apt-client span   { color: #94a3b8 !important; }
body.dark-mode .eng-apt-action.view { background: #1e3a5f !important; color: #60a5fa !important; }
body.dark-mode .dashboard-card { background: #1e293b !important; border-color: #334155 !important; }
body.dark-mode .card-header { border-color: #334155 !important; }
body.dark-mode .card-header h3 { color: #f1f5f9 !important; }

@media (max-width: 900px) {
    .eng-hero { flex-direction: column; text-align: center; }
    .eng-hero-actions { flex-direction: row; flex-wrap: wrap; justify-content: center; }
    .eng-stats-row { grid-template-columns: repeat(2,1fr); }
    .eng-quick-grid { grid-template-columns: repeat(2,1fr); }
}
</style>
</head>
<body class="app-body">
<?php include '../includes/header.php'; ?>
<div class="app-layout">
<?php include '../includes/sidebar_engineer.php'; ?>
<main class="main-content">

    <!-- ── Hero Card ── -->
    <div class="eng-hero">
        <div class="eng-hero-avatar">
            <img src="<?= UPLOADS_URL ?>/profiles/<?= $engineer['profile_photo'] ?? 'default_avatar.png' ?>"
                 alt="" onerror="this.src='<?= ASSETS_URL ?>/images/default_avatar.png'">
            <div class="avail-ring" style="background:<?= $avail['dot'] ?>"></div>
        </div>
        <div class="eng-hero-info">
            <h1><?= htmlspecialchars($engineer['name']) ?></h1>
            <p class="eng-title"><?= htmlspecialchars($engineer['specialization'] ?? 'Geodetic Engineer') ?></p>
            <div class="eng-hero-rating">
                <div class="stars">
                    <?php for ($i=1;$i<=5;$i++): ?>
                    <i class="fas fa-star <?= $i<=round($engineer['rating']) ? 'filled' : '' ?>"></i>
                    <?php endfor; ?>
                </div>
                <span><?= number_format($engineer['rating'],1) ?> · <?= $engineer['total_reviews'] ?> reviews</span>
            </div>
            <div class="eng-hero-badges">
                <span class="eng-hero-badge"><i class="fas fa-id-card"></i> <?= htmlspecialchars($engineer['license_number'] ?? 'PRC Licensed') ?></span>
                <span class="eng-hero-badge"><i class="fas fa-briefcase"></i> <?= $engineer['experience_years'] ?> yrs exp.</span>
                <?php if ($engineer['company_name']): ?>
                <span class="eng-hero-badge"><i class="fas fa-building"></i> <?= htmlspecialchars($engineer['company_name']) ?></span>
                <?php endif; ?>
                <span class="eng-hero-badge" style="background:<?= $avail['bg'] ?>20;border-color:<?= $avail['dot'] ?>40;color:<?= $avail['dot'] ?>">
                    <i class="fas fa-circle" style="font-size:8px"></i> <?= $avail['label'] ?>
                </span>
            </div>
        </div>
        <div class="eng-hero-actions">
            <div class="eng-avail-select">
                <label>Status</label>
                <select id="availabilitySelect" onchange="updateAvailability(this.value)">
                    <option value="available" <?= $engineer['availability_status']==='available'?'selected':'' ?>>🟢 Available</option>
                    <option value="busy"      <?= $engineer['availability_status']==='busy'     ?'selected':'' ?>>🟡 Busy</option>
                    <option value="offline"   <?= $engineer['availability_status']==='offline'  ?'selected':'' ?>>⚫ Offline</option>
                </select>
            </div>
            <a href="profile.php" class="btn-edit-profile"><i class="fas fa-edit"></i> Edit Profile</a>
        </div>
    </div>

    <!-- ── Stats ── -->
    <div class="eng-stats-row">
        <div class="eng-stat-card" style="--accent-color:#667eea">
            <div class="eng-stat-icon" style="background:linear-gradient(135deg,#667eea,#764ba2)"><i class="fas fa-calendar-alt"></i></div>
            <div class="eng-stat-info">
                <span class="eng-stat-value"><?= $stats['total'] ?></span>
                <span class="eng-stat-label">Total Jobs</span>
            </div>
        </div>
        <div class="eng-stat-card" style="--accent-color:#f093fb">
            <div class="eng-stat-icon" style="background:linear-gradient(135deg,#f093fb,#f5576c)"><i class="fas fa-clock"></i></div>
            <div class="eng-stat-info">
                <span class="eng-stat-value"><?= $stats['pending'] ?></span>
                <span class="eng-stat-label">Pending</span>
            </div>
        </div>
        <div class="eng-stat-card" style="--accent-color:#4facfe">
            <div class="eng-stat-icon" style="background:linear-gradient(135deg,#4facfe,#00f2fe)"><i class="fas fa-check-circle"></i></div>
            <div class="eng-stat-info">
                <span class="eng-stat-value"><?= $stats['confirmed'] ?></span>
                <span class="eng-stat-label">Confirmed</span>
            </div>
        </div>
        <div class="eng-stat-card" style="--accent-color:#43e97b">
            <div class="eng-stat-icon" style="background:linear-gradient(135deg,#43e97b,#38f9d7)"><i class="fas fa-trophy"></i></div>
            <div class="eng-stat-info">
                <span class="eng-stat-value"><?= $stats['completed'] ?></span>
                <span class="eng-stat-label">Completed</span>
            </div>
        </div>
    </div>

    <!-- ── Quick Actions ── -->
    <div class="eng-quick-grid">
        <a href="appointments.php" class="eng-quick-btn">
            <div class="eng-quick-icon" style="background:linear-gradient(135deg,#667eea,#764ba2)"><i class="fas fa-calendar-alt"></i></div>
            <div class="eng-quick-text"><strong>Appointments</strong><span>View all requests</span></div>
        </a>
        <a href="schedule.php" class="eng-quick-btn">
            <div class="eng-quick-icon" style="background:linear-gradient(135deg,#43e97b,#38f9d7)"><i class="fas fa-clock"></i></div>
            <div class="eng-quick-text"><strong>My Schedule</strong><span>Set availability</span></div>
        </a>
        <a href="progress.php" class="eng-quick-btn">
            <div class="eng-quick-icon" style="background:linear-gradient(135deg,#f093fb,#f5576c)"><i class="fas fa-tasks"></i></div>
            <div class="eng-quick-text"><strong>Update Progress</strong><span>Post field updates</span></div>
        </a>
        <a href="messages.php" class="eng-quick-btn">
            <div class="eng-quick-icon" style="background:linear-gradient(135deg,#4facfe,#00f2fe)"><i class="fas fa-comments"></i></div>
            <div class="eng-quick-text"><strong>Messages</strong><span>Chat with clients</span></div>
        </a>
        <a href="profile.php" class="eng-quick-btn">
            <div class="eng-quick-icon" style="background:linear-gradient(135deg,#fa709a,#fee140)"><i class="fas fa-user-circle"></i></div>
            <div class="eng-quick-text"><strong>My Profile</strong><span>Edit information</span></div>
        </a>
        <a href="settings.php" class="eng-quick-btn">
            <div class="eng-quick-icon" style="background:linear-gradient(135deg,#a18cd1,#fbc2eb)"><i class="fas fa-cog"></i></div>
            <div class="eng-quick-text"><strong>Settings</strong><span>Preferences</span></div>
        </a>
    </div>

    <!-- ── Upcoming Appointments ── -->
    <div class="dashboard-card">
        <div class="card-header">
            <h3><i class="fas fa-calendar-check" style="color:#667eea"></i> Upcoming Appointments</h3>
            <div style="display:flex;align-items:center;gap:10px">
                <div class="eng-dash-search">
                    <i class="fas fa-search"></i>
                    <input type="text" id="engDashSearch" placeholder="Search appointments…" oninput="filterEngDashApts(this.value)">
                </div>
                <a href="appointments.php" class="card-link">View All <i class="fas fa-arrow-right" style="font-size:11px"></i></a>
            </div>
        </div>
        <div class="card-body" style="padding:0">
            <?php if (empty($upcoming)): ?>
            <div class="empty-state" style="padding:48px 20px">
                <i class="fas fa-calendar-check" style="color:#10b981;opacity:0.5"></i>
                <h4>Schedule is clear!</h4>
                <p>No upcoming appointments. Enjoy your free time.</p>
                <a href="schedule.php" class="btn-primary" style="margin-top:8px">Set Availability</a>
            </div>
            <?php else: ?>
            <table class="eng-apt-table">
                <thead>
                    <tr>
                        <th>Client</th>
                        <th>Service</th>
                        <th>Date & Time</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($upcoming as $apt): ?>
                <tr>
                    <td>
                        <div class="eng-apt-client">
                            <img src="<?= UPLOADS_URL ?>/profiles/<?= $apt['client_photo'] ?? 'default_avatar.png' ?>"
                                 alt="" onerror="this.src='<?= ASSETS_URL ?>/images/default_avatar.png'">
                            <div>
                                <strong><?= htmlspecialchars($apt['client_name']) ?></strong>
                                <span><?= htmlspecialchars($apt['client_phone'] ?? '') ?></span>
                            </div>
                        </div>
                    </td>
                    <td>
                        <span style="font-weight:600;color:#1a1a2e"><?= htmlspecialchars($apt['service_type']) ?></span>
                    </td>
                    <td>
                        <span style="font-weight:600"><?= date('M d, Y', strtotime($apt['appointment_date'])) ?></span><br>
                        <span style="font-size:12px;color:#9ca3af"><?= date('h:i A', strtotime($apt['appointment_time'])) ?></span>
                    </td>
                    <td>
                        <span class="status-badge <?= $apt['status'] ?>"><?= ucfirst(str_replace('_',' ',$apt['status'])) ?></span>
                    </td>
                    <td>
                        <div class="eng-apt-actions">
                            <a href="appointments.php?id=<?= $apt['id'] ?>" class="eng-apt-action view" title="View Details">
                                <i class="fas fa-eye"></i>
                            </a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    </div>

</main>
</div>

<?php include '../includes/chatbot.php'; ?>
<script src="../assets/js/chatbot.js"></script>
<script>
const BASE_URL = '<?= BASE_URL ?>';

function updateAvailability(status) {
    fetch(BASE_URL + '/api/engineer.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({action: 'update_availability', status: status})
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            const ring = document.querySelector('.avail-ring');
            const colors = {available:'#10b981', busy:'#f59e0b', offline:'#9ca3af'};
            if (ring) ring.style.background = colors[status] || '#9ca3af';
        }
    });
}

// Live filter for appointments table
function filterEngDashApts(q) {
    q = q.toLowerCase().trim();
    document.querySelectorAll('.eng-apt-table tbody tr').forEach(function(row) {
        var text = row.textContent.toLowerCase();
        row.style.display = (!q || text.includes(q)) ? '' : 'none';
    });
}

// Auto-refresh dashboard data every 15 seconds
setInterval(function() {
    fetch(BASE_URL + '/api/sync.php?since=' + encodeURIComponent(new Date(Date.now() - 20000).toISOString().slice(0,19).replace('T',' ')))
        .then(r => r.json())
        .then(data => {
            if (data.updates && data.updates.length > 0) {
                // Reload the page silently to show fresh data
                // Only reload if user is not actively interacting
                if (!document.querySelector(':focus') && !document.hidden) {
                    location.reload();
                }
            }
        })
        .catch(() => {});
}, 30000); // reload every 30s if there are updates
</script>
</body>
</html>
