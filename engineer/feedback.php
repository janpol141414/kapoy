<?php
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../models/Engineer.php';

if (!isLoggedIn() || !hasRole('engineer')) redirect('/auth/login.php');

$db = (new Database())->getConnection();
$engineerModel = new Engineer($db);
$engineer = $engineerModel->getByUserId($_SESSION['user_id']);
if (!$engineer) redirect('/auth/login.php');

// Filters
$filterRating = intval($_GET['rating'] ?? 0);
$filterMonth  = intval($_GET['month'] ?? 0);

$query = "SELECT f.*, u.name AS client_name, u.profile_photo AS client_photo,
                 a.service_type, a.appointment_date
          FROM feedback f
          JOIN users u ON f.client_id = u.id
          LEFT JOIN appointments a ON f.appointment_id = a.id
          WHERE f.engineer_id = :eid";
$params = [':eid' => $engineer['id']];

if ($filterRating) {
    $query .= " AND f.rating = :rating";
    $params[':rating'] = $filterRating;
}
if ($filterMonth) {
    $query .= " AND MONTH(f.created_at) = :month";
    $params[':month'] = $filterMonth;
}
$query .= " ORDER BY f.created_at DESC";

$stmt = $db->prepare($query);
$stmt->execute($params);
$feedbacks = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Stats
$statsStmt = $db->prepare(
    "SELECT COUNT(*) AS total,
            AVG(rating) AS avg_rating,
            SUM(CASE WHEN rating=5 THEN 1 ELSE 0 END) AS five_star,
            SUM(CASE WHEN rating=4 THEN 1 ELSE 0 END) AS four_star,
            SUM(CASE WHEN rating=3 THEN 1 ELSE 0 END) AS three_star,
            SUM(CASE WHEN rating=2 THEN 1 ELSE 0 END) AS two_star,
            SUM(CASE WHEN rating=1 THEN 1 ELSE 0 END) AS one_star
     FROM feedback WHERE engineer_id = :eid"
);
$statsStmt->execute([':eid' => $engineer['id']]);
$stats = $statsStmt->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>My Feedback – Engineer | GeoSurvey</title>
<link rel="stylesheet" href="../assets/css/main.css">
<link rel="stylesheet" href="../assets/css/dashboard.css">
<link rel="stylesheet" href="../assets/css/feedback.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<style>
/* ── Engineer Feedback Page Styles ── */
.eng-fb-hero {
    background: linear-gradient(135deg, #0f2540 0%, #1a3c5e 60%, #2d6a9f 100%);
    border-radius: 20px;
    padding: 28px 36px;
    margin-bottom: 24px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    box-shadow: 0 8px 32px rgba(26,60,94,0.2);
    position: relative;
    overflow: hidden;
}
.eng-fb-hero::before {
    content: '';
    position: absolute;
    top: -50px; right: -50px;
    width: 200px; height: 200px;
    background: rgba(255,255,255,0.04);
    border-radius: 50%;
}
.eng-fb-hero-left h2 { font-size: 22px; font-weight: 900; color: #fff; margin-bottom: 4px; }
.eng-fb-hero-left p  { font-size: 14px; color: rgba(255,255,255,0.7); }
.eng-fb-hero-rating {
    text-align: center; z-index: 1;
}
.eng-fb-hero-rating .big-score {
    font-size: 52px; font-weight: 900; color: #fff; line-height: 1;
}
.eng-fb-hero-rating .stars-row {
    display: flex; gap: 4px; justify-content: center; margin: 6px 0;
}
.eng-fb-hero-rating .stars-row i { color: #f59e0b; font-size: 18px; }
.eng-fb-hero-rating .review-count { font-size: 13px; color: rgba(255,255,255,0.7); }

/* Rating breakdown */
.rating-breakdown {
    background: #fff;
    border-radius: 16px;
    padding: 24px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.06);
    border: 1px solid #f1f5f9;
    margin-bottom: 24px;
}
.rating-breakdown h3 {
    font-size: 15px; font-weight: 800; color: #1a1a2e;
    margin-bottom: 16px;
    display: flex; align-items: center; gap: 8px;
}
.rating-bar-row {
    display: flex; align-items: center; gap: 12px;
    margin-bottom: 10px;
}
.rating-bar-label {
    display: flex; align-items: center; gap: 4px;
    font-size: 13px; font-weight: 600; color: #374151;
    min-width: 60px;
}
.rating-bar-label i { color: #f59e0b; font-size: 12px; }
.rating-bar-track {
    flex: 1; height: 8px;
    background: #f1f5f9;
    border-radius: 8px;
    overflow: hidden;
}
.rating-bar-fill {
    height: 100%;
    border-radius: 8px;
    background: linear-gradient(90deg, #f59e0b, #fbbf24);
    transition: width 0.6s ease;
}
.rating-bar-count {
    font-size: 12px; color: #9ca3af;
    min-width: 30px; text-align: right;
}

/* Feedback cards */
.feedback-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
    gap: 16px;
}
.fb-card {
    background: #fff;
    border-radius: 16px;
    padding: 20px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.06);
    border: 1px solid #f1f5f9;
    transition: all 0.25s;
}
.fb-card:hover { transform: translateY(-3px); box-shadow: 0 8px 24px rgba(0,0,0,0.1); }
.fb-card-header {
    display: flex; align-items: center; gap: 12px;
    margin-bottom: 14px;
}
.fb-client-avatar {
    width: 44px; height: 44px;
    border-radius: 50%; object-fit: cover;
    border: 2px solid #f1f5f9;
}
.fb-client-info { flex: 1; }
.fb-client-info strong { display: block; font-size: 14px; font-weight: 700; color: #1a1a2e; }
.fb-client-info span   { display: block; font-size: 12px; color: #9ca3af; }
.fb-stars { display: flex; gap: 3px; }
.fb-stars i { font-size: 14px; }
.fb-comment {
    font-size: 13.5px; color: #4b5563;
    line-height: 1.6;
    padding: 12px;
    background: #f8fafc;
    border-radius: 10px;
    border-left: 3px solid #f59e0b;
    margin-bottom: 12px;
    font-style: italic;
}
.fb-comment:empty { display: none; }
.fb-meta {
    display: flex; align-items: center; justify-content: space-between;
    font-size: 11px; color: #9ca3af;
}
.fb-service-tag {
    display: inline-flex; align-items: center; gap: 4px;
    padding: 3px 10px;
    background: #f0f7ff;
    color: #1a3c5e;
    border-radius: 20px;
    font-size: 11px; font-weight: 600;
}

/* Filter bar */
.fb-filter-bar {
    display: flex; align-items: center; gap: 12px;
    flex-wrap: wrap;
    margin-bottom: 20px;
    padding: 14px 18px;
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
    border: 1px solid #f1f5f9;
}
.fb-filter-bar label { font-size: 13px; font-weight: 600; color: #374151; }
.fb-filter-select {
    padding: 7px 12px;
    border: 1.5px solid #e2e8f0;
    border-radius: 8px;
    font-size: 13px;
    outline: none;
    background: #f8fafc;
    font-family: inherit;
    cursor: pointer;
}
.fb-filter-select:focus { border-color: #2d6a9f; }
.fb-results-count {
    margin-left: auto;
    font-size: 13px; color: #9ca3af; font-weight: 500;
}

/* Empty state */
.fb-empty {
    text-align: center;
    padding: 60px 20px;
    color: #9ca3af;
}
.fb-empty i { font-size: 48px; margin-bottom: 16px; opacity: 0.4; }
.fb-empty h3 { font-size: 18px; font-weight: 700; color: #374151; margin-bottom: 8px; }
.fb-empty p  { font-size: 14px; }

/* Dark mode */
body.dark-mode .rating-breakdown,
body.dark-mode .fb-card,
body.dark-mode .fb-filter-bar { background: #1e293b !important; border-color: #334155 !important; }
body.dark-mode .rating-breakdown h3 { color: #f1f5f9 !important; }
body.dark-mode .rating-bar-track { background: #334155 !important; }
body.dark-mode .fb-client-info strong { color: #f1f5f9 !important; }
body.dark-mode .fb-comment { background: #0f172a !important; color: #cbd5e1 !important; border-left-color: #f59e0b !important; }
body.dark-mode .fb-service-tag { background: #1e3a5f !important; color: #60a5fa !important; }
body.dark-mode .fb-filter-select { background: #0f172a !important; border-color: #334155 !important; color: #f1f5f9 !important; }
body.dark-mode .fb-filter-bar label { color: #cbd5e1 !important; }
body.dark-mode .rating-bar-label { color: #cbd5e1 !important; }
</style>
</head>
<body class="app-body">
<?php include '../includes/header.php'; ?>
<div class="app-layout">
<?php include '../includes/sidebar_engineer.php'; ?>
<main class="main-content">

<!-- Hero -->
<div class="eng-fb-hero">
    <div class="eng-fb-hero-left">
        <h2><i class="fas fa-star" style="color:#f59e0b;margin-right:8px"></i>My Feedback & Reviews</h2>
        <p>See what clients are saying about your work</p>
    </div>
    <div class="eng-fb-hero-rating">
        <div class="big-score"><?= number_format($stats['avg_rating'] ?? 0, 1) ?></div>
        <div class="stars-row">
            <?php $avg = round($stats['avg_rating'] ?? 0);
            for ($i=1;$i<=5;$i++): ?>
            <i class="fas fa-star" style="color:<?= $i<=$avg ? '#f59e0b' : 'rgba(255,255,255,0.3)' ?>"></i>
            <?php endfor; ?>
        </div>
        <div class="review-count"><?= $stats['total'] ?> review<?= $stats['total'] != 1 ? 's' : '' ?></div>
    </div>
</div>

<!-- Rating Breakdown -->
<?php if ($stats['total'] > 0): ?>
<div class="rating-breakdown">
    <h3><i class="fas fa-chart-bar" style="color:#2d6a9f"></i> Rating Breakdown</h3>
    <?php
    $ratingLabels = [5=>'5 Stars',4=>'4 Stars',3=>'3 Stars',2=>'2 Stars',1=>'1 Star'];
    $ratingKeys   = [5=>'five_star',4=>'four_star',3=>'three_star',2=>'two_star',1=>'one_star'];
    foreach ([5,4,3,2,1] as $r):
        $count = intval($stats[$ratingKeys[$r]] ?? 0);
        $pct   = $stats['total'] > 0 ? ($count / $stats['total'] * 100) : 0;
    ?>
    <div class="rating-bar-row">
        <div class="rating-bar-label">
            <?= $r ?> <i class="fas fa-star"></i>
        </div>
        <div class="rating-bar-track">
            <div class="rating-bar-fill" style="width:<?= $pct ?>%"></div>
        </div>
        <div class="rating-bar-count"><?= $count ?></div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- Filters -->
<div class="fb-filter-bar">
    <label><i class="fas fa-filter" style="margin-right:5px;color:#2d6a9f"></i>Filter:</label>
    <form method="GET" style="display:flex;align-items:center;gap:10px;flex-wrap:wrap">
        <select name="rating" class="fb-filter-select" onchange="this.form.submit()">
            <option value="">All Ratings</option>
            <?php for ($i=5;$i>=1;$i--): ?>
            <option value="<?= $i ?>" <?= $filterRating==$i ? 'selected' : '' ?>><?= $i ?> Stars</option>
            <?php endfor; ?>
        </select>
        <select name="month" class="fb-filter-select" onchange="this.form.submit()">
            <option value="">All Months</option>
            <?php for ($m=1;$m<=12;$m++): ?>
            <option value="<?= $m ?>" <?= $filterMonth==$m ? 'selected' : '' ?>><?= date('F', mktime(0,0,0,$m,1)) ?></option>
            <?php endfor; ?>
        </select>
        <?php if ($filterRating || $filterMonth): ?>
        <a href="feedback.php" style="font-size:13px;color:#dc2626;font-weight:600;text-decoration:none">
            <i class="fas fa-times"></i> Clear
        </a>
        <?php endif; ?>
    </form>
    <div class="fb-results-count"><?= count($feedbacks) ?> review<?= count($feedbacks) != 1 ? 's' : '' ?></div>
</div>

<!-- Feedback Grid -->
<?php if (empty($feedbacks)): ?>
<div class="fb-empty">
    <i class="fas fa-star"></i>
    <h3>No feedback yet</h3>
    <p>Client reviews will appear here once they complete appointments with you.</p>
</div>
<?php else: ?>
<div class="feedback-grid">
    <?php foreach ($feedbacks as $fb): ?>
    <div class="fb-card">
        <div class="fb-card-header">
            <img src="<?= UPLOADS_URL ?>/profiles/<?= $fb['client_photo'] ?? 'default_avatar.png' ?>"
                 alt="" class="fb-client-avatar"
                 onerror="this.src='<?= ASSETS_URL ?>/images/default_avatar.png'">
            <div class="fb-client-info">
                <strong><?= htmlspecialchars($fb['client_name']) ?></strong>
                <span><?= date('M d, Y', strtotime($fb['created_at'])) ?></span>
            </div>
            <div class="fb-stars">
                <?php for ($i=1;$i<=5;$i++): ?>
                <i class="fas fa-star" style="color:<?= $i<=$fb['rating'] ? '#f59e0b' : '#d1d5db' ?>"></i>
                <?php endfor; ?>
            </div>
        </div>

        <?php if (!empty($fb['comment'])): ?>
        <div class="fb-comment">"<?= htmlspecialchars($fb['comment']) ?>"</div>
        <?php endif; ?>

        <div class="fb-meta">
            <?php if (!empty($fb['service_type'])): ?>
            <span class="fb-service-tag">
                <i class="fas fa-map"></i> <?= htmlspecialchars($fb['service_type']) ?>
            </span>
            <?php else: ?>
            <span></span>
            <?php endif; ?>
            <?php if (!empty($fb['appointment_date'])): ?>
            <span><i class="fas fa-calendar-alt" style="margin-right:4px"></i><?= date('M d, Y', strtotime($fb['appointment_date'])) ?></span>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

</main></div>
<?php include '../includes/chatbot.php'; ?>
<script src="../assets/js/dashboard.js"></script>
<script src="../assets/js/chatbot.js"></script>
</body>
</html>
