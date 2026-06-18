<?php
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../models/User.php';

if (!isLoggedIn() || !hasRole('admin')) redirect('/auth/login.php');

$db = (new Database())->getConnection();
$userModel = new User($db);

$success = $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id = intval($_POST['id'] ?? 0);
    if ($action === 'toggle' && $id) {
        $stmt = $db->prepare("UPDATE users SET is_active = NOT is_active WHERE id=:id");
        $stmt->execute([':id'=>$id]);
        $success = 'User status updated.';
    }
}

$roleFilter = $_GET['role'] ?? '';
$query = "SELECT * FROM users WHERE 1=1";
$params = [];
if ($roleFilter) { $query .= " AND role=:role"; $params[':role'] = $roleFilter; }
$query .= " ORDER BY created_at DESC";
$stmt = $db->prepare($query);
$stmt->execute($params);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

$totalStmt = $db->query("SELECT role, COUNT(*) as cnt FROM users GROUP BY role");
$roleCounts = [];
foreach ($totalStmt->fetchAll(PDO::FETCH_ASSOC) as $r) $roleCounts[$r['role']] = $r['cnt'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Users – Admin | GeoSurvey</title>
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
    <div><h1><i class="fas fa-users"></i> Users</h1><p>Manage all system users</p></div>
</div>

<?php if ($success): ?><div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= $success ?></div><?php endif; ?>

<!-- Stats -->
<div class="stats-grid mini">
    <?php foreach ([
        ['All','',array_sum($roleCounts),'fa-users','#667eea'],
        ['Clients','client',$roleCounts['client']??0,'fa-user','#4facfe'],
        ['Engineers','engineer',$roleCounts['engineer']??0,'fa-hard-hat','#f093fb'],
        ['Admins','admin',$roleCounts['admin']??0,'fa-user-shield','#43e97b'],
    ] as [$lbl,$role,$cnt,$icon,$color]): ?>
    <a href="users.php<?= $role ? '?role='.$role : '' ?>" class="stat-card mini" style="--accent:<?= $color ?>;text-decoration:none">
        <div class="stat-icon"><i class="fas <?= $icon ?>"></i></div>
        <div class="stat-info"><span class="stat-value"><?= $cnt ?></span><span class="stat-label"><?= $lbl ?></span></div>
    </a>
    <?php endforeach; ?>
</div>

<!-- Filter -->
<div class="filter-bar">
    <div class="filter-tabs">
        <?php foreach ([''=> 'All','client'=>'Clients','engineer'=>'Engineers','admin'=>'Admins'] as $k=>$v): ?>
        <a href="users.php<?= $k ? '?role='.$k : '' ?>" class="filter-tab <?= $roleFilter===$k ? 'active' : '' ?>"><?= $v ?></a>
        <?php endforeach; ?>
    </div>
    <span class="results-count"><?= count($users) ?> users</span>
</div>

<!-- Table -->
<div class="dashboard-card">
    <div class="card-body" style="padding:0">
        <div class="table-responsive">
            <table class="data-table">
                <thead><tr>
                    <th>User</th><th>Email</th><th>Role</th><th>Phone</th><th>Joined</th><th>Status</th><th>Action</th>
                </tr></thead>
                <tbody>
                <?php if (empty($users)): ?>
                <tr><td colspan="7" style="text-align:center;padding:40px;color:#9ca3af">No users found</td></tr>
                <?php else: foreach ($users as $u): ?>
                <tr>
                    <td>
                        <div class="table-user">
                            <img src="<?= UPLOADS_URL ?>/profiles/<?= $u['profile_photo'] ?? 'default_avatar.png' ?>" alt=""
                                 onerror="this.src='<?= ASSETS_URL ?>/images/default_avatar.png'">
                            <strong><?= htmlspecialchars($u['name']) ?></strong>
                        </div>
                    </td>
                    <td style="font-size:13px"><?= htmlspecialchars($u['email']) ?></td>
                    <td><span class="sidebar-role-badge <?= $u['role'] ?>"><?= ucfirst($u['role']) ?></span></td>
                    <td style="font-size:13px;color:#6b7280"><?= htmlspecialchars($u['phone'] ?? '—') ?></td>
                    <td style="font-size:12px;color:#9ca3af"><?= date('M d, Y', strtotime($u['created_at'])) ?></td>
                    <td>
                        <span class="status-badge <?= $u['is_active'] ? 'confirmed' : 'cancelled' ?>">
                            <?= $u['is_active'] ? 'Active' : 'Inactive' ?>
                        </span>
                    </td>
                    <td>
                        <?php if ($u['id'] != $_SESSION['user_id']): ?>
                        <form method="POST" style="display:inline">
                            <input type="hidden" name="action" value="toggle">
                            <input type="hidden" name="id" value="<?= $u['id'] ?>">
                            <button type="submit" class="btn-table-action" title="<?= $u['is_active'] ? 'Deactivate' : 'Activate' ?>">
                                <i class="fas <?= $u['is_active'] ? 'fa-ban' : 'fa-check' ?>"></i>
                            </button>
                        </form>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

</main></div>
<script src="../assets/js/dashboard.js"></script>
</body></html>
