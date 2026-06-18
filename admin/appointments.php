<?php
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../models/Appointment.php';

if (!isLoggedIn() || !hasRole('admin')) redirect('/auth/login.php');

$db = (new Database())->getConnection();
$appointmentModel = new Appointment($db);

// Admin has VIEW-ONLY access. Appointment confirmation is handled by Engineers only.
$success = $error = '';

$statusFilter = $_GET['status'] ?? '';
$appointments  = $appointmentModel->getAll($statusFilter ? ['status'=>$statusFilter] : []);
$stats         = $appointmentModel->getStats();

$selectedId  = intval($_GET['id'] ?? 0);
$selectedApt = $selectedId ? $appointmentModel->getById($selectedId) : null;
$progress    = $selectedId ? $appointmentModel->getProgressUpdates($selectedId) : [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Appointments – Admin | GeoSurvey</title>
<link rel="stylesheet" href="../assets/css/main.css">
<link rel="stylesheet" href="../assets/css/dashboard.css">
<link rel="stylesheet" href="../assets/css/admin.css">
<link rel="stylesheet" href="../assets/css/tracking.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body class="app-body">
<?php include '../includes/header.php'; ?>
<div class="app-layout">
<?php include '../includes/sidebar_admin.php'; ?>
<main class="main-content">

<div class="page-header">
    <div><h1><i class="fas fa-calendar-alt"></i> Appointments</h1><p>View all survey appointments (read-only — engineers manage confirmations)</p></div>
    <a href="schedules.php" class="btn-primary"><i class="fas fa-clock"></i> View Schedules</a>
</div>

<?php if ($success): ?><div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= $success ?></div><?php endif; ?>
<?php if ($error):   ?><div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div><?php endif; ?>

<!-- Stats -->
<div class="stats-grid mini" style="grid-template-columns:repeat(5,1fr)">
    <?php
    $sc = [
        ['label'=>'Total',       'val'=>$stats['total'],       'icon'=>'fa-calendar-alt', 'color'=>'#667eea'],
        ['label'=>'Pending',     'val'=>$stats['pending'],     'icon'=>'fa-clock',        'color'=>'#f093fb'],
        ['label'=>'Confirmed',   'val'=>$stats['confirmed'],   'icon'=>'fa-check',        'color'=>'#4facfe'],
        ['label'=>'In Progress', 'val'=>$stats['in_progress'], 'icon'=>'fa-hard-hat',     'color'=>'#fa709a'],
        ['label'=>'Completed',   'val'=>$stats['completed'],   'icon'=>'fa-trophy',       'color'=>'#43e97b'],
    ];
    foreach ($sc as $s): ?>
    <div class="stat-card mini" style="--accent:<?= $s['color'] ?>">
        <div class="stat-icon"><i class="fas <?= $s['icon'] ?>"></i></div>
        <div class="stat-info"><span class="stat-value"><?= $s['val'] ?></span><span class="stat-label"><?= $s['label'] ?></span></div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Filter Tabs -->
<div class="filter-bar">
    <div class="filter-tabs">
        <?php foreach ([''=>'All','pending'=>'Pending','confirmed'=>'Confirmed','in_progress'=>'In Progress','completed'=>'Completed','cancelled'=>'Cancelled'] as $k=>$v): ?>
        <a href="appointments.php?status=<?= $k ?><?= $selectedId ? '&id='.$selectedId : '' ?>"
           class="filter-tab <?= $statusFilter===$k ? 'active' : '' ?>"><?= $v ?></a>
        <?php endforeach; ?>
    </div>
</div>

<div class="admin-layout">
    <!-- List -->
    <div class="admin-list">
        <?php if (empty($appointments)): ?>
        <div class="empty-state small"><i class="fas fa-calendar-times"></i><p>No appointments found</p></div>
        <?php else: foreach ($appointments as $apt): ?>
        <a href="appointments.php?id=<?= $apt['id'] ?><?= $statusFilter ? '&status='.$statusFilter : '' ?>"
           class="admin-list-item <?= $selectedId==$apt['id'] ? 'active' : '' ?>">
            <div class="admin-item-avatar">
                <img src="<?= UPLOADS_URL ?>/profiles/<?= $apt['client_photo'] ?? 'default_avatar.png' ?>" alt=""
                     onerror="this.src='<?= ASSETS_URL ?>/images/default_avatar.png'">
            </div>
            <div class="admin-item-info">
                <strong><?= htmlspecialchars($apt['client_name']) ?></strong>
                <span><?= htmlspecialchars($apt['service_type']) ?></span>
                <span class="admin-item-date"><?= date('M d, Y', strtotime($apt['appointment_date'])) ?></span>
            </div>
            <span class="status-badge <?= $apt['status'] ?>"><?= ucfirst(str_replace('_',' ',$apt['status'])) ?></span>
        </a>
        <?php endforeach; endif; ?>
    </div>

    <!-- Detail -->
    <div class="admin-detail">
        <?php if (!$selectedApt): ?>
        <div class="admin-placeholder"><i class="fas fa-calendar-alt"></i><h3>Select an appointment</h3><p>Click to view details and update status</p></div>
        <?php else: ?>
        <div class="tracking-detail-content">
            <div class="tracking-detail-header">
                <div class="tracking-conf-code">
                    <span>Confirmation Code</span>
                    <strong><?= htmlspecialchars($selectedApt['confirmation_code']) ?></strong>
                </div>
                <span class="status-badge-lg <?= $selectedApt['status'] ?>"><?= ucfirst(str_replace('_',' ',$selectedApt['status'])) ?></span>
            </div>

            <div class="tracking-info-grid">
                <div class="tracking-info-card">
                    <h4><i class="fas fa-user"></i> Client</h4>
                    <div class="engineer-tracking-card">
                        <img src="<?= UPLOADS_URL ?>/profiles/<?= $selectedApt['client_photo'] ?? 'default_avatar.png' ?>" alt=""
                             onerror="this.src='<?= ASSETS_URL ?>/images/default_avatar.png'">
                        <div>
                            <strong><?= htmlspecialchars($selectedApt['client_name']) ?></strong>
                            <span><?= htmlspecialchars($selectedApt['client_email']) ?></span>
                            <span><?= htmlspecialchars($selectedApt['client_phone'] ?? '') ?></span>
                        </div>
                    </div>
                </div>
                <div class="tracking-info-card">
                    <h4><i class="fas fa-hard-hat"></i> Engineer</h4>
                    <div class="engineer-tracking-card">
                        <img src="<?= UPLOADS_URL ?>/profiles/<?= $selectedApt['engineer_photo'] ?? 'default_avatar.png' ?>" alt=""
                             onerror="this.src='<?= ASSETS_URL ?>/images/default_avatar.png'">
                        <div>
                            <strong><?= htmlspecialchars($selectedApt['engineer_name']) ?></strong>
                            <span><?= htmlspecialchars($selectedApt['specialization']) ?></span>
                            <span><?= htmlspecialchars($selectedApt['engineer_phone'] ?? '') ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="tracking-info-card" style="margin-bottom:16px">
                <h4><i class="fas fa-map"></i> Service Details</h4>
                <div class="info-rows">
                    <?php foreach ([
                        'Service'  => $selectedApt['service_type'],
                        'Date'     => date('F d, Y', strtotime($selectedApt['appointment_date'])),
                        'Time'     => date('h:i A', strtotime($selectedApt['appointment_time'])),
                        'Location' => $selectedApt['location'],
                        'Amount'   => '₱'.number_format($selectedApt['total_amount'],2),
                        'Notes'    => $selectedApt['notes'] ?: '—',
                    ] as $lbl => $val): ?>
                    <div class="info-row"><span><?= $lbl ?></span><strong><?= htmlspecialchars($val) ?></strong></div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Progress Timeline -->
            <?php if (!empty($progress)): ?>
            <div class="progress-timeline" style="margin-bottom:16px">
                <h4><i class="fas fa-history"></i> Progress Updates</h4>
                <div class="timeline">
                    <?php foreach ($progress as $upd): ?>
                    <div class="timeline-item">
                        <div class="timeline-dot"></div>
                        <div class="timeline-content">
                            <div class="timeline-header">
                                <strong><?= htmlspecialchars($upd['status']) ?></strong>
                                <span><?= date('M d, Y h:i A', strtotime($upd['created_at'])) ?></span>
                            </div>
                            <p><?= htmlspecialchars($upd['description']) ?></p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- View-Only Notice -->
            <div class="status-update-form" style="background:#f0f7ff;border:1.5px solid #bfdbfe;border-radius:12px;padding:16px">
                <h4 style="color:#1e40af;margin-bottom:8px"><i class="fas fa-info-circle"></i> Admin View Only</h4>
                <p style="font-size:13px;color:#3b82f6;margin:0">
                    Appointment confirmation and status updates are managed by the assigned engineer.
                    The engineer can accept, decline, or update progress from their dashboard.
                </p>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>
</main></div>
<script src="../assets/js/dashboard.js"></script>
</body></html>
