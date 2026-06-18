<?php
/**
 * Migration: Add updated_at to schedules table
 * Run this once to enable real-time schedule sync.
 * Access via browser: http://localhost/kapoy/add_updated_at.php
 */
require_once 'config/config.php';
require_once 'config/database.php';

$db = (new Database())->getConnection();
$results = [];

// Add updated_at to schedules if missing
try {
    $db->exec("ALTER TABLE schedules ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");
    $results[] = ['status' => 'success', 'msg' => 'Added updated_at column to schedules table.'];
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate column') !== false) {
        $results[] = ['status' => 'info', 'msg' => 'updated_at already exists in schedules table.'];
    } else {
        $results[] = ['status' => 'error', 'msg' => 'schedules: ' . $e->getMessage()];
    }
}

// Backfill updated_at with created_at for existing rows
try {
    $db->exec("UPDATE schedules SET updated_at = created_at WHERE updated_at IS NULL OR updated_at = '0000-00-00 00:00:00'");
    $results[] = ['status' => 'success', 'msg' => 'Backfilled updated_at values from created_at.'];
} catch (PDOException $e) {
    $results[] = ['status' => 'info', 'msg' => 'Backfill skipped: ' . $e->getMessage()];
}

// Ensure appointments.updated_at exists (should already, but just in case)
try {
    $db->exec("ALTER TABLE appointments ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");
    $results[] = ['status' => 'success', 'msg' => 'Added updated_at to appointments table.'];
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate column') !== false) {
        $results[] = ['status' => 'info', 'msg' => 'updated_at already exists in appointments table.'];
    } else {
        $results[] = ['status' => 'error', 'msg' => 'appointments: ' . $e->getMessage()];
    }
}

// Ensure payments.updated_at exists
try {
    $db->exec("ALTER TABLE payments ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");
    $results[] = ['status' => 'success', 'msg' => 'Added updated_at to payments table.'];
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate column') !== false) {
        $results[] = ['status' => 'info', 'msg' => 'updated_at already exists in payments table.'];
    } else {
        $results[] = ['status' => 'error', 'msg' => 'payments: ' . $e->getMessage()];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Database Migration – GeoSurvey</title>
<style>
body { font-family: Inter, sans-serif; max-width: 700px; margin: 60px auto; padding: 0 20px; background: #f8fafc; }
h1 { color: #1a3c5e; margin-bottom: 24px; }
.result { padding: 12px 16px; border-radius: 8px; margin-bottom: 10px; font-size: 14px; display: flex; align-items: center; gap: 10px; }
.success { background: #d1fae5; color: #065f46; border: 1px solid #a7f3d0; }
.info    { background: #dbeafe; color: #1e40af; border: 1px solid #bfdbfe; }
.error   { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
.done { margin-top: 24px; padding: 16px; background: #fff; border-radius: 12px; border: 1px solid #e2e8f0; font-size: 14px; color: #374151; }
a { color: #2d6a9f; font-weight: 600; }
</style>
</head>
<body>
<h1>🔧 Database Migration</h1>
<?php foreach ($results as $r): ?>
<div class="result <?= $r['status'] ?>">
    <?php if ($r['status'] === 'success'): ?><span>✅</span>
    <?php elseif ($r['status'] === 'info'): ?><span>ℹ️</span>
    <?php else: ?><span>❌</span><?php endif; ?>
    <?= htmlspecialchars($r['msg']) ?>
</div>
<?php endforeach; ?>
<div class="done">
    <strong>Migration complete.</strong> Real-time schedule synchronization is now enabled.<br><br>
    <a href="admin/dashboard.php">→ Go to Admin Dashboard</a>
</div>
</body>
</html>
