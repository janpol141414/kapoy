<?php
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../models/Appointment.php';
require_once '../models/Engineer.php';
require_once '../models/Notification.php';
require_once '../helpers/EmailHelper.php';

if (!isLoggedIn() || !hasRole('engineer')) redirect('/auth/login.php');

$db = (new Database())->getConnection();
$engineerModel = new Engineer($db);
$engineer = $engineerModel->getByUserId($_SESSION['user_id']);
if (!$engineer) redirect('/auth/login.php');

$appointmentModel = new Appointment($db);
$success = $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $apt_id = intval($_POST['apt_id'] ?? 0);
    $action = $_POST['action'] ?? '';

    if ($apt_id && in_array($action, ['confirm','cancel'])) {
        $newStatus = $action === 'confirm' ? 'confirmed' : 'cancelled';
        if ($appointmentModel->updateStatus($apt_id, $newStatus)) {
            $apt = $appointmentModel->getById($apt_id);
            $notif = new Notification($db);
            $msg = $action === 'confirm'
                ? "Your appointment ({$apt['confirmation_code']}) has been confirmed by the engineer."
                : "Your appointment ({$apt['confirmation_code']}) was declined by the engineer.";
            $notif->create($apt['client_id'], 'Appointment '.ucfirst($newStatus), $msg, 'appointment',
                BASE_URL.'/client/track-status.php?id='.$apt_id);
            if ($action === 'confirm') {
                EmailHelper::sendAppointmentConfirmation($apt['client_email'], $apt['client_name'], $apt);
            }
            $success = 'Appointment '.($action==='confirm' ? 'confirmed' : 'declined').'.';
        }
    }
}

$statusFilter = $_GET['status'] ?? '';
$appointments = $appointmentModel->getByEngineerId($engineer['id']);
if ($statusFilter) {
    $appointments = array_filter($appointments, fn($a) => $a['status'] === $statusFilter);
}

$selectedId  = intval($_GET['id'] ?? 0);
$selectedApt = $selectedId ? $appointmentModel->getById($selectedId) : null;
$progress    = $selectedId ? $appointmentModel->getProgressUpdates($selectedId) : [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>My Appointments – Engineer | GeoSurvey</title>
<link rel="stylesheet" href="../assets/css/main.css">
<link rel="stylesheet" href="../assets/css/dashboard.css">
<link rel="stylesheet" href="../assets/css/tracking.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body class="app-body">
<?php include '../includes/header.php'; ?>
<div class="app-layout">
<?php include '../includes/sidebar_engineer.php'; ?>
<main class="main-content">

<div class="page-header">
    <div><h1><i class="fas fa-calendar-alt"></i> My Appointments</h1><p>View and manage your assigned appointments</p></div>
</div>

<?php if ($success): ?><div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= $success ?></div><?php endif; ?>

<!-- Filter -->
<div class="filter-bar">
    <div class="filter-tabs">
        <?php foreach ([''=> 'All','pending'=>'Pending','confirmed'=>'Confirmed','in_progress'=>'In Progress','completed'=>'Completed'] as $k=>$v): ?>
        <a href="appointments.php?status=<?= $k ?><?= $selectedId ? '&id='.$selectedId : '' ?>"
           class="filter-tab <?= $statusFilter===$k ? 'active' : '' ?>"><?= $v ?></a>
        <?php endforeach; ?>
    </div>
</div>

<div class="tracking-layout">
    <!-- List -->
    <div class="tracking-list">
        <div class="tracking-list-header">
            <h3>Appointments (<?= count($appointments) ?>)</h3>
        </div>
        <?php if (empty($appointments)): ?>
        <div class="empty-state small"><i class="fas fa-calendar-times"></i><p>No appointments</p></div>
        <?php else: foreach ($appointments as $apt): ?>
        <a href="appointments.php?id=<?= $apt['id'] ?><?= $statusFilter ? '&status='.$statusFilter : '' ?>"
           class="tracking-item <?= $selectedId==$apt['id'] ? 'active' : '' ?>">
            <div class="tracking-item-icon"><i class="fas fa-map"></i></div>
            <div class="tracking-item-info">
                <strong><?= htmlspecialchars($apt['client_name']) ?></strong>
                <span><?= htmlspecialchars($apt['service_type']) ?></span>
                <span class="tracking-date"><?= date('M d, Y', strtotime($apt['appointment_date'])) ?></span>
            </div>
            <span class="status-badge <?= $apt['status'] ?>"><?= ucfirst(str_replace('_',' ',$apt['status'])) ?></span>
        </a>
        <?php endforeach; endif; ?>
    </div>

    <!-- Detail -->
    <div class="tracking-detail">
        <?php if (!$selectedApt): ?>
        <div class="tracking-placeholder"><i class="fas fa-calendar-alt"></i><h3>Select an appointment</h3><p>Click to view details</p></div>
        <?php else: ?>
        <div class="tracking-detail-content">
            <div class="tracking-detail-header">
                <div class="tracking-conf-code">
                    <span>Confirmation Code</span>
                    <strong><?= htmlspecialchars($selectedApt['confirmation_code']) ?></strong>
                </div>
                <span class="status-badge-lg <?= $selectedApt['status'] ?>"><?= ucfirst(str_replace('_',' ',$selectedApt['status'])) ?></span>
            </div>

            <!-- Client Info -->
            <div class="tracking-info-card" style="margin-bottom:16px">
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
                <a href="messages.php?contact=<?= $selectedApt['client_id'] ?>" class="btn-contact-eng" style="margin-top:10px">
                    <i class="fas fa-comment"></i> Message Client
                </a>
            </div>

            <!-- Service Details -->
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

            <!-- Progress -->
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

            <!-- Actions -->
            <?php if ($selectedApt['status'] === 'pending'): ?>
            <div class="status-update-form">
                <h4><i class="fas fa-check-circle"></i> Respond to Appointment</h4>
                <div style="display:flex;gap:12px">
                    <form method="POST" style="flex:1">
                        <input type="hidden" name="apt_id" value="<?= $selectedApt['id'] ?>">
                        <input type="hidden" name="action" value="confirm">
                        <button type="submit" class="btn-verify" style="width:100%">
                            <i class="fas fa-check"></i> Accept & Confirm
                        </button>
                    </form>
                    <form method="POST" style="flex:1">
                        <input type="hidden" name="apt_id" value="<?= $selectedApt['id'] ?>">
                        <input type="hidden" name="action" value="cancel">
                        <button type="submit" class="btn-reject" style="width:100%" onclick="return confirm('Decline this appointment?')">
                            <i class="fas fa-times"></i> Decline
                        </button>
                    </form>
                </div>
            </div>
            <?php elseif (in_array($selectedApt['status'], ['confirmed','in_progress'])): ?>
            <div style="margin-top:16px">
                <a href="progress.php?apt_id=<?= $selectedApt['id'] ?>" class="btn-update-status">
                    <i class="fas fa-tasks"></i> Update Progress
                </a>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

</main></div>
<?php include '../includes/chatbot.php'; ?>
<script src="../assets/js/dashboard.js"></script>
<script src="../assets/js/chatbot.js"></script>
</body></html>
