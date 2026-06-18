<?php
require_once '../config/config.php';
require_once '../config/database.php';

if (!isLoggedIn() || !hasRole('admin')) redirect('/auth/login.php');

$db = (new Database())->getConnection();

// Stats
$statsStmt = $db->query("SELECT COUNT(*) as total, AVG(rating) as avg_rating,
    SUM(CASE WHEN rating=5 THEN 1 ELSE 0 END) as five_star,
    SUM(CASE WHEN rating>=4 THEN 1 ELSE 0 END) as four_plus
    FROM feedback");
$stats = $statsStmt->fetch(PDO::FETCH_ASSOC);

// Filter
$filterEng = intval($_GET['engineer_id'] ?? 0);
$filterRating = intval($_GET['rating'] ?? 0);

$query = "SELECT f.*, u.name as client_name, u.profile_photo as client_photo,
                 eu.name as engineer_name, eu.profile_photo as engineer_photo
          FROM feedback f
          JOIN users u ON f.client_id=u.id
          JOIN engineers e ON f.engineer_id=e.id
          JOIN users eu ON e.user_id=eu.id
          WHERE 1=1";
$params = [];
if ($filterEng)    { $query .= " AND f.engineer_id=:eid"; $params[':eid'] = $filterEng; }
if ($filterRating) { $query .= " AND f.rating=:rating";   $params[':rating'] = $filterRating; }
$query .= " ORDER BY f.created_at DESC";
$stmt = $db->prepare($query);
$stmt->execute($params);
$feedbacks = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Engineers for filter
$engStmt = $db->query("SELECT e.id, u.name FROM engineers e JOIN users u ON e.user_id=u.id ORDER BY u.name");
$engineers = $engStmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Feedback – Admin | GeoSurvey</title>
<link rel="stylesheet" href="../assets/css/main.css">
<link rel="stylesheet" href="../assets/css/dashboard.css">
<link rel="stylesheet" href="../assets/css/admin.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body class="app-body">
<?php include '../includes/header.php'; ?>
<div class="app-layout">
<?php include '../includes/sidebar_admin.php'; ?>
<main class="main-content">

<div class="page-header">
    <div><h1><i class="fas fa-star"></i> Feedback & Reviews</h1><p>All client reviews and ratings</p></div>
</div>

<!-- Stats -->
<div class="feedback-stats">
    <div class="feedback-stat-card">
        <div class="big-num"><?= $stats['total'] ?></div>
        <div class="stars-display">
            <?php for ($i=1;$i<=5;$i++): ?><i class="fas fa-star filled"></i><?php endfor; ?>
        </div>
        <div class="big-label">Total Reviews</div>
    </div>
    <div class="feedback-stat-card">
        <div class="big-num"><?= number_format($stats['avg_rating'] ?? 0, 1) ?></div>
        <div class="stars-display">
            <?php $avg = round($stats['avg_rating'] ?? 0);
            for ($i=1;$i<=5;$i++): ?>
            <i class="fas fa-star <?= $i<=$avg ? 'filled' : '' ?>"></i>
            <?php endfor; ?>
        </div>
        <div class="big-label">Average Rating</div>
    </div>
    <div class="feedback-stat-card">
        <div class="big-num"><?= $stats['five_star'] ?></div>
        <div class="stars-display">
            <?php for ($i=1;$i<=5;$i++): ?><i class="fas fa-star filled"></i><?php endfor; ?>
        </div>
        <div class="big-label">5-Star Reviews</div>
    </div>
</div>

<!-- Filters -->
<div class="filter-bar">
    <form method="GET" class="filter-form">
        <select name="engineer_id" class="filter-select" onchange="this.form.submit()">
            <option value="">All Engineers</option>
            <?php foreach ($engineers as $eng): ?>
            <option value="<?= $eng['id'] ?>" <?= $filterEng==$eng['id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($eng['name']) ?>
            </option>
            <?php endforeach; ?>
        </select>
        <select name="rating" class="filter-select" onchange="this.form.submit()">
            <option value="">All Ratings</option>
            <?php for ($i=5;$i>=1;$i--): ?>
            <option value="<?= $i ?>" <?= $filterRating==$i ? 'selected' : '' ?>><?= $i ?> Stars</option>
            <?php endfor; ?>
        </select>
        <?php if ($filterEng || $filterRating): ?>
        <a href="feedback.php" class="btn-reset"><i class="fas fa-times"></i> Clear</a>
        <?php endif; ?>
    </form>
    <span class="results-count"><?= count($feedbacks) ?> reviews</span>
</div>

<!-- Feedback Table -->
<div class="feedback-table-wrapper">
    <div class="table-responsive">
        <table class="data-table">
            <thead><tr>
                <th>Client</th><th>Engineer</th><th>Rating</th><th>Comment</th><th>Date</th>
            </tr></thead>
            <tbody>
            <?php if (empty($feedbacks)): ?>
            <tr><td colspan="5" style="text-align:center;padding:40px;color:#9ca3af">No feedback found</td></tr>
            <?php else: foreach ($feedbacks as $fb): ?>
            <tr>
                <td>
                    <div class="table-user">
                        <img src="<?= UPLOADS_URL ?>/profiles/<?= $fb['client_photo'] ?? 'default_avatar.png' ?>" alt=""
                             onerror="this.src='<?= ASSETS_URL ?>/images/default_avatar.png'">
                        <span><?= htmlspecialchars($fb['client_name']) ?></span>
                    </div>
                </td>
                <td>
                    <div class="table-user">
                        <img src="<?= UPLOADS_URL ?>/profiles/<?= $fb['engineer_photo'] ?? 'default_avatar.png' ?>" alt=""
                             onerror="this.src='<?= ASSETS_URL ?>/images/default_avatar.png'">
                        <span><?= htmlspecialchars($fb['engineer_name']) ?></span>
                    </div>
                </td>
                <td>
                    <div style="display:flex;gap:2px">
                        <?php for ($i=1;$i<=5;$i++): ?>
                        <i class="fas fa-star" style="font-size:13px;color:<?= $i<=$fb['rating'] ? '#f59e0b' : '#d1d5db' ?>"></i>
                        <?php endfor; ?>
                    </div>
                </td>
                <td style="max-width:300px;font-size:13px;color:#6b7280"><?= htmlspecialchars(substr($fb['comment'] ?? '', 0, 120)) ?><?= strlen($fb['comment'] ?? '') > 120 ? '...' : '' ?></td>
                <td style="font-size:12px;color:#9ca3af"><?= date('M d, Y', strtotime($fb['created_at'])) ?></td>
            </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

</main></div>
<script src="../assets/js/dashboard.js"></script>
</body></html>
