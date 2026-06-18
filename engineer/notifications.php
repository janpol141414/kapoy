<?php
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../models/Notification.php';

if (!isLoggedIn() || !hasRole('engineer')) redirect('/auth/login.php');

$db  = (new Database())->getConnection();
$notifModel = new Notification($db);

// Mark all read when page opens
if (isset($_GET['mark_read'])) {
    $notifModel->markAllRead($_SESSION['user_id']);
    redirect('/engineer/notifications.php');
}

$notifications = $notifModel->getByUserId($_SESSION['user_id'], 50);

$typeIcons = [
    'appointment' => ['icon' => 'fa-calendar-check', 'bg' => '#dbeafe', 'color' => '#1e40af'],
    'payment'     => ['icon' => 'fa-credit-card',    'bg' => '#d1fae5', 'color' => '#065f46'],
    'message'     => ['icon' => 'fa-comment-dots',   'bg' => '#ede9fe', 'color' => '#5b21b6'],
    'status'      => ['icon' => 'fa-tasks',          'bg' => '#fef3c7', 'color' => '#92400e'],
    'system'      => ['icon' => 'fa-bell',           'bg' => '#f3f4f6', 'color' => '#6b7280'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Notifications – GeoSurvey Portal</title>
<link rel="stylesheet" href="../assets/css/main.css">
<link rel="stylesheet" href="../assets/css/dashboard.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<style>
.notif-page-list { display: flex; flex-direction: column; gap: 0; }
.notif-page-item {
    display: flex; align-items: flex-start; gap: 16px;
    padding: 18px 24px;
    background: #fff;
    border-bottom: 1px solid #f1f5f9;
    transition: background 0.15s;
    text-decoration: none; color: inherit;
    position: relative;
}
.notif-page-item:first-child { border-radius: 16px 16px 0 0; }
.notif-page-item:last-child  { border-bottom: none; border-radius: 0 0 16px 16px; }
.notif-page-item:hover { background: #f8fafc; }
.notif-page-item.unread { background: #f0f7ff; }
.notif-page-item.unread:hover { background: #e0f0ff; }

.notif-page-icon {
    width: 48px; height: 48px; border-radius: 14px;
    display: flex; align-items: center; justify-content: center;
    font-size: 20px; flex-shrink: 0;
}
.notif-page-body { flex: 1; min-width: 0; }
.notif-page-title {
    font-size: 15px; font-weight: 700; color: #1a1a2e; margin-bottom: 4px;
}
.notif-page-msg { font-size: 13.5px; color: #6b7280; line-height: 1.5; margin-bottom: 6px; }
.notif-page-time { font-size: 12px; color: #b0b8c4; display: flex; align-items: center; gap: 4px; }
.notif-page-dot {
    width: 10px; height: 10px; border-radius: 50%;
    background: #2d6a9f; flex-shrink: 0; margin-top: 6px;
}
.notif-page-card {
    background: #fff; border-radius: 16px;
    box-shadow: 0 4px 16px rgba(0,0,0,0.07);
    overflow: hidden;
}
.notif-page-header-bar {
    display: flex; align-items: center; justify-content: space-between;
    padding: 20px 24px; border-bottom: 1px solid #f1f5f9;
}
.notif-page-header-bar h3 { font-size: 16px; font-weight: 700; color: #1a1a2e; display: flex; align-items: center; gap: 8px; }
.btn-mark-all {
    padding: 7px 16px; background: #f0f7ff; color: #1a3c5e;
    border: 1.5px solid #bfdbfe; border-radius: 8px;
    font-size: 12px; font-weight: 700; cursor: pointer;
    text-decoration: none; transition: all 0.2s;
}
.btn-mark-all:hover { background: #1a3c5e; color: #fff; border-color: #1a3c5e; }
</style>
</head>
<body class="app-body">
<?php include '../includes/header.php'; ?>
<div class="app-layout">
<?php include '../includes/sidebar_engineer.php'; ?>
<main class="main-content">

<div class="page-header">
    <div>
        <h1><i class="fas fa-bell"></i> Notifications</h1>
        <p>All your system notifications in one place</p>
    </div>
</div>

<div class="notif-page-card">
    <div class="notif-page-header-bar">
        <h3><i class="fas fa-bell"></i> All Notifications (<?= count($notifications) ?>)</h3>
        <a href="notifications.php?mark_read=1" class="btn-mark-all">
            <i class="fas fa-check-double"></i> Mark all as read
        </a>
    </div>

    <?php if (empty($notifications)): ?>
    <div class="empty-state" style="padding:60px 20px">
        <i class="fas fa-bell-slash"></i>
        <h3>No notifications yet</h3>
        <p>You'll see appointment updates, payment confirmations, and messages here.</p>
    </div>
    <?php else: ?>
    <div class="notif-page-list">
        <?php foreach ($notifications as $n):
            $ic   = $typeIcons[$n['type']] ?? $typeIcons['system'];
            $link = !empty($n['link']) ? $n['link'] : '#';
        ?>
        <a href="<?= htmlspecialchars($link) ?>"
           class="notif-page-item <?= $n['is_read'] ? '' : 'unread' ?>"
           onclick="markRead(<?= $n['id'] ?>, this)">
            <div class="notif-page-icon"
                 style="background:<?= $ic['bg'] ?>;color:<?= $ic['color'] ?>">
                <i class="fas <?= $ic['icon'] ?>"></i>
            </div>
            <div class="notif-page-body">
                <p class="notif-page-title"><?= htmlspecialchars($n['title']) ?></p>
                <p class="notif-page-msg"><?= htmlspecialchars($n['message']) ?></p>
                <span class="notif-page-time">
                    <i class="fas fa-clock"></i>
                    <?= date('F d, Y \a\t h:i A', strtotime($n['created_at'])) ?>
                </span>
            </div>
            <?php if (!$n['is_read']): ?>
            <div class="notif-page-dot"></div>
            <?php endif; ?>
        </a>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

</main></div>
<?php include '../includes/chatbot.php'; ?>
<script src="../assets/js/chatbot.js"></script>
<script>
const BASE_URL = '<?= BASE_URL ?>';
function markRead(id, el) {
    fetch(BASE_URL + '/api/notifications.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({action: 'mark_read', id: id})
    });
    el.classList.remove('unread');
    const dot = el.querySelector('.notif-page-dot');
    if (dot) dot.remove();
}
</script>
</body></html>
