<?php
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../models/Appointment.php';
require_once '../models/Engineer.php';
require_once '../models/Notification.php';

if (!isLoggedIn() || !hasRole('engineer')) redirect('/auth/login.php');

$db = (new Database())->getConnection();
$engineerModel = new Engineer($db);
$engineer = $engineerModel->getByUserId($_SESSION['user_id']);
if (!$engineer) redirect('/auth/login.php');

$appointmentModel = new Appointment($db);
$success = $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $apt_id      = intval($_POST['apt_id'] ?? 0);
    $statusText  = sanitize($_POST['status_text'] ?? '');
    $description = sanitize($_POST['description'] ?? '');

    if ($apt_id && $statusText) {
        $photo = null;
        if (!empty($_FILES['photo']['name'])) {
            $ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg','jpeg','png','gif','webp'])) {
                $fn = 'progress_'.time().'_'.rand(1000,9999).'.'.$ext;
                if (move_uploaded_file($_FILES['photo']['tmp_name'], PROGRESS_PHOTO_PATH.$fn)) $photo = $fn;
            }
        }

        $appointmentModel->addProgressUpdate([
            'appointment_id' => $apt_id,
            'engineer_id'    => $engineer['id'],
            'status'         => $statusText,
            'description'    => $description,
            'photo'          => $photo,
        ]);

        // Update appointment status to in_progress if still confirmed
        $apt = $appointmentModel->getById($apt_id);
        if ($apt && $apt['status'] === 'confirmed') {
            $appointmentModel->updateStatus($apt_id, 'in_progress');
        }

        // Notify client and admin
        $notif = new Notification($db);
        $notif->create($apt['client_id'], 'Survey Progress Update',
            "Progress update on your appointment ({$apt['confirmation_code']}): $statusText",
            'status', BASE_URL.'/client/track-status.php?id='.$apt_id);
        $notif->create(3, 'Engineer Progress Update',
            "{$engineer['name']} updated progress on appointment {$apt['confirmation_code']}: $statusText",
            'status');

        $success = 'Progress update saved and client notified.';
    } else {
        $error = 'Please fill in all required fields.';
    }
}

// Get active appointments
$allApts = $appointmentModel->getByEngineerId($engineer['id']);
$activeApts = array_filter($allApts, fn($a) => in_array($a['status'], ['confirmed','in_progress']));

$selectedId  = intval($_GET['apt_id'] ?? (count($activeApts) ? array_key_first($activeApts) : 0));
$selectedApt = $selectedId ? $appointmentModel->getById($selectedId) : null;
$progress    = $selectedId ? $appointmentModel->getProgressUpdates($selectedId) : [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Update Progress – Engineer | GeoSurvey</title>
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
    <div><h1><i class="fas fa-tasks"></i> Update Progress</h1><p>Post updates on your active survey projects</p></div>
</div>

<?php if ($success): ?><div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= $success ?></div><?php endif; ?>
<?php if ($error):   ?><div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div><?php endif; ?>

<?php if (empty($activeApts)): ?>
<div class="empty-state full">
    <i class="fas fa-tasks"></i>
    <h3>No active appointments</h3>
    <p>You have no confirmed or in-progress appointments to update.</p>
    <a href="appointments.php" class="btn-primary">View All Appointments</a>
</div>
<?php else: ?>

<div style="display:grid;grid-template-columns:300px 1fr;gap:20px">
    <!-- Appointment Selector -->
    <div class="dashboard-card">
        <div class="card-header"><h3><i class="fas fa-list"></i> Active Jobs</h3></div>
        <div class="card-body" style="padding:0">
            <?php foreach ($activeApts as $apt): ?>
            <a href="progress.php?apt_id=<?= $apt['id'] ?>"
               class="tracking-item <?= $selectedId==$apt['id'] ? 'active' : '' ?>">
                <div class="tracking-item-icon"><i class="fas fa-map"></i></div>
                <div class="tracking-item-info">
                    <strong><?= htmlspecialchars($apt['client_name']) ?></strong>
                    <span><?= htmlspecialchars($apt['service_type']) ?></span>
                    <span class="tracking-date"><?= date('M d, Y', strtotime($apt['appointment_date'])) ?></span>
                </div>
                <span class="status-badge <?= $apt['status'] ?>"><?= ucfirst(str_replace('_',' ',$apt['status'])) ?></span>
            </a>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Progress Panel -->
    <div>
        <?php if ($selectedApt): ?>
        <!-- Add Update Form -->
        <div class="dashboard-card" style="margin-bottom:20px">
            <div class="card-header">
                <h3><i class="fas fa-plus-circle"></i> Post Update — <?= htmlspecialchars($selectedApt['service_type']) ?></h3>
                <span class="status-badge <?= $selectedApt['status'] ?>"><?= ucfirst(str_replace('_',' ',$selectedApt['status'])) ?></span>
            </div>
            <div class="card-body">
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="apt_id" value="<?= $selectedApt['id'] ?>">
                    <div class="form-group">
                        <label>Status / Milestone *</label>
                        <div class="input-wrapper"><i class="fas fa-flag input-icon"></i>
                        <input type="text" name="status_text" placeholder="e.g. Site inspection completed, Boundary markers placed..." required></div>
                    </div>
                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" rows="3" placeholder="Detailed description of work done..."></textarea>
                    </div>
                    <div class="form-group">
                        <label>Photo (optional)</label>
                        <input type="file" name="photo" accept="image/*">
                    </div>
                    <button type="submit" class="btn-update-status">
                        <i class="fas fa-paper-plane"></i> Post Update & Notify Client
                    </button>
                </form>
            </div>
        </div>

        <!-- Timeline -->
        <div class="dashboard-card">
            <div class="card-header"><h3><i class="fas fa-history"></i> Progress History</h3></div>
            <div class="card-body">
                <?php if (empty($progress)): ?>
                <div class="empty-state small"><i class="fas fa-clock"></i><p>No updates posted yet</p></div>
                <?php else: ?>
                <div class="timeline">
                    <?php foreach (array_reverse($progress) as $upd): ?>
                    <div class="timeline-item">
                        <div class="timeline-dot"></div>
                        <div class="timeline-content">
                            <div class="timeline-header">
                                <strong><?= htmlspecialchars($upd['status']) ?></strong>
                                <span><?= date('M d, Y h:i A', strtotime($upd['created_at'])) ?></span>
                            </div>
                            <p><?= htmlspecialchars($upd['description']) ?></p>
                            <?php if ($upd['photo']): ?>
                            <img src="<?= UPLOADS_URL ?>/progress/<?= $upd['photo'] ?>" alt="Progress" class="timeline-photo">
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

</main></div>
<?php include '../includes/chatbot.php'; ?>
<script src="../assets/js/dashboard.js"></script>
<script src="../assets/js/chatbot.js"></script>
</body></html>
