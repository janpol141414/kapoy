<?php
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../models/Appointment.php';
require_once '../models/Payment.php';

if (!isLoggedIn() || !hasRole('client')) redirect('/auth/login.php');

$db = (new Database())->getConnection();
$appointmentModel = new Appointment($db);
$paymentModel = new Payment($db);

$appointments = $appointmentModel->getByClientId($_SESSION['user_id']);
$payments = $paymentModel->getByClientId($_SESSION['user_id']);

$stats = [
    'total'     => count($appointments),
    'pending'   => count(array_filter($appointments, fn($a) => $a['status'] === 'pending')),
    'confirmed' => count(array_filter($appointments, fn($a) => $a['status'] === 'confirmed')),
    'completed' => count(array_filter($appointments, fn($a) => $a['status'] === 'completed')),
];

$recentAppointments = array_slice($appointments, 0, 5);

// Recommended Engineers — top rated with recent feedback
$recStmt = $db->prepare(
    "SELECT e.id, u.name, u.profile_photo, e.specialization, e.rating,
            e.total_reviews, e.experience_years, e.availability_status,
            e.hourly_rate,
            (SELECT f.comment FROM feedback f WHERE f.engineer_id = e.id
             AND f.is_public = 1 AND f.comment != '' ORDER BY f.created_at DESC LIMIT 1) AS latest_comment,
            (SELECT u2.name FROM feedback f2 JOIN users u2 ON f2.client_id = u2.id
             WHERE f2.engineer_id = e.id AND f2.is_public = 1 ORDER BY f2.created_at DESC LIMIT 1) AS latest_reviewer,
            (SELECT f3.rating FROM feedback f3 WHERE f3.engineer_id = e.id
             AND f3.is_public = 1 ORDER BY f3.created_at DESC LIMIT 1) AS latest_rating
     FROM engineers e
     JOIN users u ON e.user_id = u.id
     WHERE u.is_active = 1 AND e.availability_status != 'offline'
     ORDER BY e.rating DESC, e.total_reviews DESC
     LIMIT 4"
);
$recStmt->execute();
$recommendedEngineers = $recStmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Client Dashboard - LandSurvey Portal</title>
    <link rel="stylesheet" href="../assets/css/main.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
    /* ── Recommended Engineers Section ── */
    .rec-engineers-section {
        margin-top: 28px;
    }
    .rec-section-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 20px;
        flex-wrap: wrap;
        gap: 12px;
    }
    .rec-section-header h2 {
        font-size: 20px;
        font-weight: 800;
        color: #1a1a2e;
        display: flex;
        align-items: center;
        gap: 8px;
        margin-bottom: 4px;
    }
    .rec-section-header p {
        font-size: 13px;
        color: #9ca3af;
    }
    .rec-engineers-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
        gap: 18px;
    }
    .rec-eng-card {
        background: #fff;
        border-radius: 18px;
        padding: 20px;
        box-shadow: 0 2px 12px rgba(0,0,0,0.06);
        border: 1px solid #f1f5f9;
        transition: all 0.25s;
        display: flex;
        flex-direction: column;
        gap: 12px;
    }
    .rec-eng-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 10px 28px rgba(0,0,0,0.1);
        border-color: #e0e7ff;
    }
    .rec-eng-top {
        display: flex;
        align-items: center;
        gap: 12px;
    }
    .rec-eng-avatar-wrap {
        position: relative;
        flex-shrink: 0;
    }
    .rec-eng-avatar {
        width: 56px;
        height: 56px;
        border-radius: 50%;
        object-fit: cover;
        border: 3px solid #f1f5f9;
    }
    .rec-eng-status-dot {
        position: absolute;
        bottom: 2px;
        right: 2px;
        width: 13px;
        height: 13px;
        border-radius: 50%;
        border: 2px solid #fff;
    }
    .rec-eng-info { flex: 1; min-width: 0; }
    .rec-eng-info h4 {
        font-size: 14px;
        font-weight: 700;
        color: #1a1a2e;
        margin-bottom: 2px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .rec-eng-spec {
        display: block;
        font-size: 11px;
        color: #9ca3af;
        margin-bottom: 5px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .rec-eng-avail {
        display: inline-flex;
        align-items: center;
        padding: 2px 9px;
        border-radius: 20px;
        font-size: 10px;
        font-weight: 700;
    }
    .rec-eng-rating-row {
        display: flex;
        align-items: center;
        gap: 5px;
    }
    .rec-eng-score {
        font-size: 14px;
        font-weight: 800;
        color: #1a1a2e;
    }
    .rec-eng-reviews {
        font-size: 11px;
        color: #9ca3af;
    }
    .rec-eng-meta {
        display: flex;
        gap: 14px;
        font-size: 12px;
        color: #6b7280;
    }
    .rec-eng-meta span {
        display: flex;
        align-items: center;
        gap: 4px;
    }
    .rec-eng-meta i { color: #9ca3af; font-size: 11px; }
    .rec-eng-review {
        background: #f8fafc;
        border-radius: 10px;
        padding: 10px 12px;
        border-left: 3px solid #f59e0b;
        font-size: 12px;
        color: #4b5563;
        line-height: 1.5;
        font-style: italic;
    }
    .rec-eng-reviewer {
        font-size: 11px;
        color: #9ca3af;
        font-style: normal;
        margin-top: 4px;
        font-weight: 600;
    }
    .rec-eng-actions {
        display: flex;
        gap: 8px;
        margin-top: 2px;
    }
    .rec-btn-profile {
        flex: 1;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 5px;
        padding: 8px 12px;
        border: 1.5px solid #e2e8f0;
        border-radius: 10px;
        font-size: 12px;
        font-weight: 600;
        color: #374151;
        text-decoration: none;
        transition: all 0.2s;
    }
    .rec-btn-profile:hover {
        border-color: #1a3c5e;
        color: #1a3c5e;
        background: #f0f7ff;
    }
    .rec-btn-book {
        flex: 1;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 5px;
        padding: 8px 12px;
        background: linear-gradient(135deg, #1a3c5e, #2d6a9f);
        border-radius: 10px;
        font-size: 12px;
        font-weight: 600;
        color: #fff;
        text-decoration: none;
        transition: all 0.2s;
        border: none;
    }
    .rec-btn-book:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(26,60,94,0.3);
    }

    /* Dark mode */
    body.dark-mode .rec-section-header h2 { color: #f1f5f9 !important; }
    body.dark-mode .rec-section-header p  { color: #94a3b8 !important; }
    body.dark-mode .rec-eng-card          { background: #1e293b !important; border-color: #334155 !important; }
    body.dark-mode .rec-eng-card:hover    { border-color: #3b82f6 !important; }
    body.dark-mode .rec-eng-avatar        { border-color: #334155 !important; }
    body.dark-mode .rec-eng-status-dot    { border-color: #1e293b !important; }
    body.dark-mode .rec-eng-info h4       { color: #f1f5f9 !important; }
    body.dark-mode .rec-eng-spec          { color: #94a3b8 !important; }
    body.dark-mode .rec-eng-score         { color: #f1f5f9 !important; }
    body.dark-mode .rec-eng-reviews       { color: #64748b !important; }
    body.dark-mode .rec-eng-meta          { color: #94a3b8 !important; }
    body.dark-mode .rec-eng-meta i        { color: #64748b !important; }
    body.dark-mode .rec-eng-review        { background: #0f172a !important; color: #cbd5e1 !important; }
    body.dark-mode .rec-eng-reviewer      { color: #64748b !important; }
    body.dark-mode .rec-btn-profile       { border-color: #334155 !important; color: #cbd5e1 !important; }
    body.dark-mode .rec-btn-profile:hover { border-color: #60a5fa !important; color: #60a5fa !important; background: #1e3a5f !important; }

    @media (max-width: 768px) {
        .rec-engineers-grid { grid-template-columns: 1fr 1fr; }
    }
    @media (max-width: 480px) {
        .rec-engineers-grid { grid-template-columns: 1fr; }
    }
    </style>
</head>
<body class="app-body">

<?php include '../includes/header.php'; ?>

<div class="app-layout">
    <?php include '../includes/sidebar_client.php'; ?>

    <main class="main-content">
        <!-- Welcome Banner -->
        <div class="welcome-banner">
            <div class="welcome-text">
                <h1>Welcome back, <?= htmlspecialchars(explode(' ', $_SESSION['name'])[0]) ?>! 👋</h1>
                <p>Here's what's happening with your survey projects today.</p>
            </div>
            <div class="welcome-actions">
                <a href="book-appointment.php" class="btn-primary">
                    <i class="fas fa-calendar-plus"></i> Book Appointment
                </a>
                <a href="engineers.php" class="btn-outline">
                    <i class="fas fa-search"></i> Find Engineers
                </a>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="stats-grid">
            <div class="stat-card" style="--accent: #667eea;">
                <div class="stat-icon"><i class="fas fa-calendar-alt"></i></div>
                <div class="stat-info">
                    <span class="stat-value"><?= $stats['total'] ?></span>
                    <span class="stat-label">Total Appointments</span>
                </div>
                <div class="stat-trend up"><i class="fas fa-arrow-up"></i> All time</div>
            </div>
            <div class="stat-card" style="--accent: #f093fb;">
                <div class="stat-icon"><i class="fas fa-clock"></i></div>
                <div class="stat-info">
                    <span class="stat-value"><?= $stats['pending'] ?></span>
                    <span class="stat-label">Pending</span>
                </div>
                <div class="stat-trend neutral"><i class="fas fa-minus"></i> Awaiting</div>
            </div>
            <div class="stat-card" style="--accent: #4facfe;">
                <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
                <div class="stat-info">
                    <span class="stat-value"><?= $stats['confirmed'] ?></span>
                    <span class="stat-label">Confirmed</span>
                </div>
                <div class="stat-trend up"><i class="fas fa-arrow-up"></i> Active</div>
            </div>
            <div class="stat-card" style="--accent: #43e97b;">
                <div class="stat-icon"><i class="fas fa-trophy"></i></div>
                <div class="stat-info">
                    <span class="stat-value"><?= $stats['completed'] ?></span>
                    <span class="stat-label">Completed</span>
                </div>
                <div class="stat-trend up"><i class="fas fa-arrow-up"></i> Done</div>
            </div>
        </div>

        <div class="dashboard-grid">
            <!-- Recent Appointments -->
            <div class="dashboard-card wide">
                <div class="card-header">
                    <h3><i class="fas fa-calendar-alt"></i> Recent Appointments</h3>
                    <a href="track-status.php" class="card-link">View All <i class="fas fa-arrow-right"></i></a>
                </div>
                <div class="card-body">
                    <?php if (empty($recentAppointments)): ?>
                    <div class="empty-state">
                        <i class="fas fa-calendar-times"></i>
                        <h4>No appointments yet</h4>
                        <p>Book your first appointment to get started</p>
                        <a href="book-appointment.php" class="btn-primary">Book Now</a>
                    </div>
                    <?php else: ?>
                    <div class="appointments-list">
                        <?php foreach ($recentAppointments as $apt): ?>
                        <div class="appointment-item">
                            <div class="apt-engineer">
                                <img src="<?= UPLOADS_URL ?>/profiles/<?= $apt['engineer_photo'] ?? 'default_avatar.png' ?>" 
                                     alt="" onerror="this.src='<?= ASSETS_URL ?>/images/default_avatar.png'">
                                <div>
                                    <strong><?= htmlspecialchars($apt['engineer_name']) ?></strong>
                                    <span><?= htmlspecialchars($apt['specialization']) ?></span>
                                </div>
                            </div>
                            <div class="apt-service">
                                <i class="fas fa-map"></i>
                                <?= htmlspecialchars($apt['service_type']) ?>
                            </div>
                            <div class="apt-date">
                                <i class="fas fa-calendar"></i>
                                <?= date('M d, Y', strtotime($apt['appointment_date'])) ?>
                                <span><?= date('h:i A', strtotime($apt['appointment_time'])) ?></span>
                            </div>
                            <div class="apt-status">
                                <span class="status-badge <?= $apt['status'] ?>">
                                    <?= ucfirst(str_replace('_', ' ', $apt['status'])) ?>
                                </span>
                            </div>
                            <!-- Eye icon → track status for this appointment -->
                            <a href="track-status.php?id=<?= $apt['id'] ?>" class="apt-action" title="Track Status">
                                <i class="fas fa-eye"></i>
                            </a>
                            <!-- Message icon → engineer's user account (not client) -->
                            <a href="messages.php?contact=<?= $apt['engineer_user_id'] ?? '' ?>" class="apt-action" title="Message Engineer" style="background:linear-gradient(135deg,#667eea,#764ba2)">
                                <i class="fas fa-comment"></i>
                            </a>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="dashboard-card">
                <div class="card-header">
                    <h3><i class="fas fa-bolt"></i> Quick Actions</h3>
                </div>
                <div class="card-body">
                    <div class="quick-actions">
                        <a href="book-appointment.php" class="quick-action-btn">
                            <div class="qa-icon" style="background: linear-gradient(135deg, #667eea, #764ba2);">
                                <i class="fas fa-calendar-plus"></i>
                            </div>
                            <span>Book Appointment</span>
                        </a>
                        <a href="engineers.php" class="quick-action-btn">
                            <div class="qa-icon" style="background: linear-gradient(135deg, #f093fb, #f5576c);">
                                <i class="fas fa-hard-hat"></i>
                            </div>
                            <span>Find Engineers</span>
                        </a>
                        <a href="payment.php" class="quick-action-btn">
                            <div class="qa-icon" style="background: linear-gradient(135deg, #4facfe, #00f2fe);">
                                <i class="fas fa-credit-card"></i>
                            </div>
                            <span>Submit Payment</span>
                        </a>
                        <a href="track-status.php" class="quick-action-btn">
                            <div class="qa-icon" style="background: linear-gradient(135deg, #43e97b, #38f9d7);">
                                <i class="fas fa-map-pin"></i>
                            </div>
                            <span>Track Status</span>
                        </a>
                        <a href="feedback.php" class="quick-action-btn">
                            <div class="qa-icon" style="background: linear-gradient(135deg, #fa709a, #fee140);">
                                <i class="fas fa-star"></i>
                            </div>
                            <span>Give Feedback</span>
                        </a>
                        <a href="messages.php" class="quick-action-btn">
                            <div class="qa-icon" style="background: linear-gradient(135deg, #a18cd1, #fbc2eb);">
                                <i class="fas fa-comments"></i>
                            </div>
                            <span>Messages</span>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Payment Status -->
            <div class="dashboard-card">
                <div class="card-header">
                    <h3><i class="fas fa-credit-card"></i> Recent Payments</h3>
                    <a href="payment.php" class="card-link">View All</a>
                </div>
                <div class="card-body">
                    <?php if (empty($payments)): ?>
                    <div class="empty-state small">
                        <i class="fas fa-receipt"></i>
                        <p>No payment records</p>
                    </div>
                    <?php else: ?>
                    <?php foreach (array_slice($payments, 0, 4) as $pay): ?>
                    <div class="payment-mini-item">
                        <div class="pay-info">
                            <strong><?= htmlspecialchars($pay['service_type']) ?></strong>
                            <span><?= date('M d, Y', strtotime($pay['created_at'])) ?></span>
                        </div>
                        <div class="pay-right">
                            <span class="pay-amount">₱<?= number_format($pay['amount'], 2) ?></span>
                            <span class="status-badge <?= $pay['status'] ?>"><?= ucfirst($pay['status']) ?></span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- ── Recommended Engineers ── -->
        <?php if (!empty($recommendedEngineers)): ?>
        <div class="rec-engineers-section">
            <div class="rec-section-header">
                <div>
                    <h2><i class="fas fa-star" style="color:#f59e0b"></i> Recommended Engineers</h2>
                    <p>Top-rated engineers based on client reviews and ratings</p>
                </div>
                <a href="engineers.php" class="btn-primary" style="font-size:13px;padding:9px 18px">
                    <i class="fas fa-hard-hat"></i> Browse All
                </a>
            </div>
            <div class="rec-engineers-grid">
                <?php foreach ($recommendedEngineers as $eng):
                    $availColors = [
                        'available' => ['dot'=>'#10b981','label'=>'Available','bg'=>'#d1fae5','color'=>'#065f46'],
                        'busy'      => ['dot'=>'#f59e0b','label'=>'Busy',     'bg'=>'#fef3c7','color'=>'#92400e'],
                        'offline'   => ['dot'=>'#9ca3af','label'=>'Offline',  'bg'=>'#f3f4f6','color'=>'#6b7280'],
                    ];
                    $av = $availColors[$eng['availability_status']] ?? $availColors['offline'];
                ?>
                <div class="rec-eng-card">
                    <div class="rec-eng-top">
                        <div class="rec-eng-avatar-wrap">
                            <img src="<?= UPLOADS_URL ?>/profiles/<?= $eng['profile_photo'] ?? 'default_avatar.png' ?>"
                                 alt="" class="rec-eng-avatar"
                                 onerror="this.src='<?= ASSETS_URL ?>/images/default_avatar.png'">
                            <span class="rec-eng-status-dot" style="background:<?= $av['dot'] ?>"></span>
                        </div>
                        <div class="rec-eng-info">
                            <h4><?= htmlspecialchars($eng['name']) ?></h4>
                            <span class="rec-eng-spec"><?= htmlspecialchars($eng['specialization'] ?? 'Geodetic Engineer') ?></span>
                            <span class="rec-eng-avail" style="background:<?= $av['bg'] ?>;color:<?= $av['color'] ?>">
                                <?= $av['label'] ?>
                            </span>
                        </div>
                    </div>

                    <div class="rec-eng-rating-row">
                        <div class="rec-eng-stars">
                            <?php for ($i=1;$i<=5;$i++): ?>
                            <i class="fas fa-star" style="color:<?= $i<=round($eng['rating']) ? '#f59e0b' : '#d1d5db' ?>;font-size:13px"></i>
                            <?php endfor; ?>
                        </div>
                        <span class="rec-eng-score"><?= number_format($eng['rating'],1) ?></span>
                        <span class="rec-eng-reviews">(<?= $eng['total_reviews'] ?> review<?= $eng['total_reviews'] != 1 ? 's' : '' ?>)</span>
                    </div>

                    <div class="rec-eng-meta">
                        <span><i class="fas fa-briefcase"></i> <?= $eng['experience_years'] ?> yrs exp.</span>
                        <?php if ($eng['hourly_rate'] > 0): ?>
                        <span><i class="fas fa-peso-sign"></i> <?= number_format($eng['hourly_rate'],0) ?>/hr</span>
                        <?php endif; ?>
                    </div>

                    <?php if (!empty($eng['latest_comment'])): ?>
                    <div class="rec-eng-review">
                        <i class="fas fa-quote-left" style="color:#f59e0b;font-size:11px;margin-right:5px"></i>
                        <span class="rec-eng-review-text"><?= htmlspecialchars(mb_substr($eng['latest_comment'], 0, 90)) ?><?= mb_strlen($eng['latest_comment']) > 90 ? '…' : '' ?></span>
                        <?php if (!empty($eng['latest_reviewer'])): ?>
                        <div class="rec-eng-reviewer">— <?= htmlspecialchars($eng['latest_reviewer']) ?>
                            <?php if ($eng['latest_rating']): ?>
                            <span style="color:#f59e0b;margin-left:4px"><?= str_repeat('★', intval($eng['latest_rating'])) ?></span>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>

                    <div class="rec-eng-actions">
                        <a href="engineer-profile.php?id=<?= $eng['id'] ?>" class="rec-btn-profile">
                            <i class="fas fa-user"></i> View Profile
                        </a>
                        <a href="book-appointment.php?engineer_id=<?= $eng['id'] ?>" class="rec-btn-book">
                            <i class="fas fa-calendar-plus"></i> Book
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

    </main>
</div>

<!-- AI Chatbot -->
<?php include '../includes/chatbot.php'; ?>

<script src="../assets/js/dashboard.js"></script>
<script src="../assets/js/chatbot.js"></script>
</body>
</html>
