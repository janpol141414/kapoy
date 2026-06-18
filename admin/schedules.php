<?php
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../models/Schedule.php';
require_once '../models/Engineer.php';

if (!isLoggedIn() || !hasRole('admin')) redirect('/auth/login.php');

$db = (new Database())->getConnection();
$scheduleModel = new Schedule($db);
$engineerModel = new Engineer($db);

// Admin has VIEW-ONLY access to schedules. Engineers manage their own schedules.
$engineers = $engineerModel->getAll();
$filterEng = intval($_GET['engineer_id'] ?? 0);
$slots     = $filterEng ? $scheduleModel->getByEngineerId($filterEng) : $scheduleModel->getAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Schedules – Admin | GeoSurvey</title>
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
    <div><h1><i class="fas fa-clock"></i> Schedule Slots</h1><p>View engineer availability (read-only — engineers manage their own schedules)</p></div>
</div>

<!-- View-Only Notice -->
<div style="background:#f0f7ff;border:1.5px solid #bfdbfe;border-radius:12px;padding:14px 18px;margin-bottom:20px;display:flex;align-items:center;gap:10px">
    <i class="fas fa-info-circle" style="color:#3b82f6;font-size:18px"></i>
    <span style="font-size:13px;color:#1e40af;font-weight:500">
        Engineers manage their own availability from their dashboard. This is a read-only view for monitoring purposes.
    </span>
</div>

<!-- Filter -->
<div class="schedule-calendar-wrapper">
    <div class="schedule-filter">
        <form method="GET" style="display:flex;align-items:center;gap:12px;flex-wrap:wrap">
            <label>Filter by Engineer:</label>
            <select name="engineer_id" onchange="this.form.submit()">
                <option value="">All Engineers</option>
                <?php foreach ($engineers as $eng): ?>
                <option value="<?= $eng['id'] ?>" <?= $filterEng==$eng['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($eng['name']) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </form>
        <span style="font-size:13px;color:#9ca3af"><?= count($slots) ?> slots found</span>
    </div>

    <div class="table-responsive">
        <table class="slots-table">
            <thead><tr>
                <th>Engineer</th><th>Date</th><th>Start</th><th>End</th>
                <th>Type</th><th>Status</th><th>Notes</th>
            </tr></thead>
            <tbody>
            <?php if (empty($slots)): ?>
            <tr><td colspan="7" style="text-align:center;padding:40px;color:#9ca3af">No slots found</td></tr>
            <?php else: foreach ($slots as $slot): ?>
            <tr>
                <td><strong><?= htmlspecialchars($slot['engineer_name'] ?? '—') ?></strong></td>
                <td><?= date('M d, Y', strtotime($slot['date'])) ?></td>
                <td><?= date('h:i A', strtotime($slot['start_time'])) ?></td>
                <td><?= date('h:i A', strtotime($slot['end_time'])) ?></td>
                <td><span class="status-badge confirmed"><?= ucfirst(str_replace('_',' ',$slot['slot_type'])) ?></span></td>
                <td>
                    <?php if ($slot['is_available']): ?>
                    <span class="slot-available"><i class="fas fa-check-circle"></i> Available</span>
                    <?php else: ?>
                    <span class="slot-unavailable"><i class="fas fa-times-circle"></i> Unavailable</span>
                    <?php endif; ?>
                </td>
                <td><?= htmlspecialchars($slot['notes'] ?? '—') ?></td>
            </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

</main></div>
<script src="../assets/js/dashboard.js"></script>
</body></html>
