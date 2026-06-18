<?php
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../models/Appointment.php';

if (!isLoggedIn() || !hasRole('client')) redirect('/auth/login.php');

$db = (new Database())->getConnection();
$appointmentModel = new Appointment($db);

$appointments = $appointmentModel->getByClientId($_SESSION['user_id']);

$selectedId = intval($_GET['id'] ?? 0);
$selectedAppointment = null;
$progressUpdates = [];

if ($selectedId) {
    $selectedAppointment = $appointmentModel->getById($selectedId);
    if ($selectedAppointment && $selectedAppointment['client_id'] != $_SESSION['user_id']) {
        $selectedAppointment = null;
    }
    if ($selectedAppointment) {
        $progressUpdates = $appointmentModel->getProgressUpdates($selectedId);
    }
}

$statusSteps = ['pending', 'confirmed', 'in_progress', 'completed'];
$statusLabels = ['Pending', 'Confirmed', 'In Progress', 'Completed'];
$statusIcons = ['fa-clock', 'fa-check', 'fa-hard-hat', 'fa-trophy'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Track Status - GeoSurvey Portal</title>
    <link rel="stylesheet" href="../assets/css/main.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="../assets/css/tracking.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body class="app-body">

<?php include '../includes/header.php'; ?>

<div class="app-layout">
    <?php include '../includes/sidebar_client.php'; ?>

    <main class="main-content">
        <div class="page-header">
            <div>
                <h1><i class="fas fa-map-pin"></i> Track Status</h1>
                <p>Monitor your survey appointments in real-time</p>
            </div>
        </div>

        <div class="tracking-layout">
            <!-- Appointments List -->
            <div class="tracking-list">
                <div class="tracking-list-header">
                    <h3>My Appointments</h3>
                    <a href="book-appointment.php" class="btn-sm-primary">+ New</a>
                </div>
                <?php if (empty($appointments)): ?>
                <div class="empty-state small">
                    <i class="fas fa-calendar-times"></i>
                    <p>No appointments yet</p>
                    <a href="book-appointment.php" class="btn-primary">Book Now</a>
                </div>
                <?php else: ?>
                <?php foreach ($appointments as $apt): ?>
                <a href="track-status.php?id=<?= $apt['id'] ?>" 
                   class="tracking-item <?= $selectedId == $apt['id'] ? 'active' : '' ?>">
                    <div class="tracking-item-icon">
                        <i class="fas fa-map"></i>
                    </div>
                    <div class="tracking-item-info">
                        <strong><?= htmlspecialchars($apt['service_type']) ?></strong>
                        <span><?= htmlspecialchars($apt['engineer_name']) ?></span>
                        <span class="tracking-date"><?= date('M d, Y', strtotime($apt['appointment_date'])) ?></span>
                    </div>
                    <span class="status-badge <?= $apt['status'] ?>"><?= ucfirst(str_replace('_', ' ', $apt['status'])) ?></span>
                </a>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Appointment Detail -->
            <div class="tracking-detail">
                <?php if (!$selectedAppointment): ?>
                <div class="tracking-placeholder">
                    <i class="fas fa-map-pin"></i>
                    <h3>Select an appointment</h3>
                    <p>Click on an appointment to view its status and progress</p>
                </div>
                <?php else: ?>
                <div class="tracking-detail-content">
                    <!-- Header -->
                    <div class="tracking-detail-header">
                        <div class="tracking-conf-code">
                            <span>Confirmation Code</span>
                            <strong><?= htmlspecialchars($selectedAppointment['confirmation_code']) ?></strong>
                        </div>
                        <span class="status-badge-lg <?= $selectedAppointment['status'] ?>">
                            <?= ucfirst(str_replace('_', ' ', $selectedAppointment['status'])) ?>
                        </span>
                    </div>

                    <!-- Progress Tracker -->
                    <div class="progress-tracker">
                        <?php
                        $currentStatusIndex = array_search($selectedAppointment['status'], $statusSteps);
                        if ($selectedAppointment['status'] === 'cancelled') $currentStatusIndex = -1;
                        ?>
                        <?php foreach ($statusSteps as $i => $step): ?>
                        <div class="progress-step <?= $i <= $currentStatusIndex ? 'completed' : '' ?> <?= $i === $currentStatusIndex ? 'current' : '' ?>">
                            <div class="progress-step-icon">
                                <i class="fas <?= $statusIcons[$i] ?>"></i>
                            </div>
                            <span><?= $statusLabels[$i] ?></span>
                        </div>
                        <?php if ($i < count($statusSteps) - 1): ?>
                        <div class="progress-line <?= $i < $currentStatusIndex ? 'completed' : '' ?>"></div>
                        <?php endif; ?>
                        <?php endforeach; ?>
                    </div>

                    <!-- Appointment Info -->
                    <div class="tracking-info-grid">
                        <div class="tracking-info-card">
                            <h4><i class="fas fa-map"></i> Service Details</h4>
                            <div class="info-rows">
                                <div class="info-row">
                                    <span>Service</span>
                                    <strong><?= htmlspecialchars($selectedAppointment['service_type']) ?></strong>
                                </div>
                                <div class="info-row">
                                    <span>Date</span>
                                    <strong><?= date('F d, Y', strtotime($selectedAppointment['appointment_date'])) ?></strong>
                                </div>
                                <div class="info-row">
                                    <span>Time</span>
                                    <strong><?= date('h:i A', strtotime($selectedAppointment['appointment_time'])) ?></strong>
                                </div>
                                <div class="info-row">
                                    <span>Location</span>
                                    <strong><?= htmlspecialchars($selectedAppointment['location']) ?></strong>
                                </div>
                                <div class="info-row">
                                    <span>Amount</span>
                                    <strong>₱<?= number_format($selectedAppointment['total_amount'], 2) ?></strong>
                                </div>
                            </div>
                        </div>

                        <div class="tracking-info-card">
                            <h4><i class="fas fa-hard-hat"></i> Engineer</h4>
                            <div class="engineer-tracking-card">
                                <img src="<?= UPLOADS_URL ?>/profiles/<?= $selectedAppointment['engineer_photo'] ?? 'default_avatar.png' ?>" 
                                     alt="" onerror="this.src='<?= ASSETS_URL ?>/images/default_avatar.png'">
                                <div>
                                    <strong><?= htmlspecialchars($selectedAppointment['engineer_name']) ?></strong>
                                    <span><?= htmlspecialchars($selectedAppointment['specialization']) ?></span>
                                    <span><?= htmlspecialchars($selectedAppointment['engineer_phone']) ?></span>
                                </div>
                            </div>
                            <a href="messages.php?contact=<?= $selectedAppointment['engineer_id'] ?>" class="btn-contact-eng">
                                <i class="fas fa-comment"></i> Contact Engineer
                            </a>
                        </div>
                    </div>

                    <!-- Progress Updates Timeline -->
                    <div class="progress-timeline">
                        <h4><i class="fas fa-history"></i> Progress Updates</h4>
                        <?php if (empty($progressUpdates)): ?>
                        <div class="timeline-empty">
                            <i class="fas fa-clock"></i>
                            <p>No updates yet. Your engineer will post updates here.</p>
                        </div>
                        <?php else: ?>
                        <div class="timeline">
                            <?php foreach ($progressUpdates as $update): ?>
                            <div class="timeline-item">
                                <div class="timeline-dot"></div>
                                <div class="timeline-content">
                                    <div class="timeline-header">
                                        <strong><?= htmlspecialchars($update['status']) ?></strong>
                                        <span><?= date('M d, Y h:i A', strtotime($update['created_at'])) ?></span>
                                    </div>
                                    <p><?= htmlspecialchars($update['description']) ?></p>
                                    <?php if ($update['photo']): ?>
                                    <img src="<?= UPLOADS_URL ?>/progress/<?= $update['photo'] ?>" 
                                         alt="Progress photo" class="timeline-photo">
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- Actions -->
                    <div class="tracking-actions">
                        <?php if ($selectedAppointment['status'] === 'completed'): ?>
                        <a href="feedback.php?appointment_id=<?= $selectedAppointment['id'] ?>" class="btn-primary">
                            <i class="fas fa-star"></i> Leave Review
                        </a>
                        <?php endif; ?>
                        <a href="payment.php?appointment_id=<?= $selectedAppointment['id'] ?>" class="btn-outline">
                            <i class="fas fa-credit-card"></i> Submit Payment
                        </a>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
</div>

<?php include '../includes/chatbot.php'; ?>
<script src="../assets/js/chatbot.js"></script>
<script>
// Auto-refresh every 30 seconds
setInterval(() => {
    if (<?= $selectedId ?>) {
        fetch('<?= BASE_URL ?>/api/appointments.php?action=get_updates&id=<?= $selectedId ?>')
            .then(r => r.json())
            .then(data => {
                if (data.status !== '<?= $selectedAppointment['status'] ?? '' ?>') {
                    location.reload();
                }
            });
    }
}, 30000);
</script>
</body>
</html>
