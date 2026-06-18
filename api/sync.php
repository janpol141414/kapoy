<?php
/**
 * Real-time Sync API
 * Returns counts/summaries of changed data since a given timestamp.
 * Called every 15 seconds by all dashboards.
 */
require_once '../config/config.php';
require_once '../config/database.php';

header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');

if (!isLoggedIn()) {
    echo json_encode(['success' => false]);
    exit;
}

$db   = (new Database())->getConnection();
$role = getCurrentUserRole();
$uid  = (int)$_SESSION['user_id'];

// Last-seen timestamp sent by client (default: 60 seconds ago)
$since = $_GET['since'] ?? date('Y-m-d H:i:s', time() - 60);

$data = [
    'success'   => true,
    'timestamp' => date('Y-m-d H:i:s'),
    'updates'   => [],
];

/* ── APPOINTMENTS ── */
if ($role === 'client') {
    $stmt = $db->prepare(
        "SELECT a.id, a.status, a.service_type, a.appointment_date,
                eu.name as engineer_name, a.updated_at
         FROM appointments a
         JOIN engineers e ON a.engineer_id = e.id
         JOIN users eu ON e.user_id = eu.id
         WHERE a.client_id = :uid AND a.updated_at > :since
         ORDER BY a.updated_at DESC"
    );
    $stmt->execute([':uid' => $uid, ':since' => $since]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if ($rows) {
        $data['updates'][] = [
            'type'    => 'appointments',
            'count'   => count($rows),
            'items'   => $rows,
            'message' => count($rows) . ' appointment' . (count($rows) > 1 ? 's' : '') . ' updated',
        ];
    }

    // Payments
    $stmt = $db->prepare(
        "SELECT p.id, p.status, p.amount, p.updated_at
         FROM payments p
         WHERE p.client_id = :uid AND p.updated_at > :since
         ORDER BY p.updated_at DESC"
    );
    $stmt->execute([':uid' => $uid, ':since' => $since]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if ($rows) {
        $data['updates'][] = [
            'type'    => 'payments',
            'count'   => count($rows),
            'items'   => $rows,
            'message' => count($rows) . ' payment' . (count($rows) > 1 ? 's' : '') . ' updated',
        ];
    }

    // Progress updates
    $stmt = $db->prepare(
        "SELECT pu.id, pu.status, pu.description, pu.created_at,
                a.service_type, a.id as appointment_id
         FROM progress_updates pu
         JOIN appointments a ON pu.appointment_id = a.id
         WHERE a.client_id = :uid AND pu.created_at > :since
         ORDER BY pu.created_at DESC"
    );
    $stmt->execute([':uid' => $uid, ':since' => $since]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if ($rows) {
        $data['updates'][] = [
            'type'    => 'progress',
            'count'   => count($rows),
            'items'   => $rows,
            'message' => count($rows) . ' progress update' . (count($rows) > 1 ? 's' : '') . ' posted',
        ];
    }

} elseif ($role === 'engineer') {
    // Get engineer ID
    $eStmt = $db->prepare("SELECT id FROM engineers WHERE user_id = :uid LIMIT 1");
    $eStmt->execute([':uid' => $uid]);
    $eng = $eStmt->fetch(PDO::FETCH_ASSOC);
    $eid = $eng ? (int)$eng['id'] : 0;

    if ($eid) {
        // New/updated appointments
        $stmt = $db->prepare(
            "SELECT a.id, a.status, a.service_type, a.appointment_date,
                    u.name as client_name, a.updated_at
             FROM appointments a
             JOIN users u ON a.client_id = u.id
             WHERE a.engineer_id = :eid AND a.updated_at > :since
             ORDER BY a.updated_at DESC"
        );
        $stmt->execute([':eid' => $eid, ':since' => $since]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if ($rows) {
            $data['updates'][] = [
                'type'    => 'appointments',
                'count'   => count($rows),
                'items'   => $rows,
                'message' => count($rows) . ' appointment' . (count($rows) > 1 ? 's' : '') . ' updated',
            ];
        }

        // New feedback
        $stmt = $db->prepare(
            "SELECT f.id, f.rating, f.comment, f.created_at, u.name as client_name
             FROM feedback f
             JOIN users u ON f.client_id = u.id
             WHERE f.engineer_id = :eid AND f.created_at > :since
             ORDER BY f.created_at DESC"
        );
        $stmt->execute([':eid' => $eid, ':since' => $since]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if ($rows) {
            $data['updates'][] = [
                'type'    => 'feedback',
                'count'   => count($rows),
                'items'   => $rows,
                'message' => count($rows) . ' new review' . (count($rows) > 1 ? 's' : '') . ' received',
            ];
        }
    }

} elseif ($role === 'admin') {
    // New appointments
    $stmt = $db->prepare(
        "SELECT COUNT(*) as cnt FROM appointments WHERE created_at > :since"
    );
    $stmt->execute([':since' => $since]);
    $cnt = (int)$stmt->fetchColumn();
    if ($cnt > 0) {
        $data['updates'][] = ['type' => 'appointments', 'count' => $cnt, 'message' => $cnt . ' new appointment' . ($cnt > 1 ? 's' : '')];
    }

    // Updated appointment statuses
    $stmt = $db->prepare(
        "SELECT COUNT(*) as cnt FROM appointments WHERE updated_at > :since AND created_at <= :since"
    );
    $stmt->execute([':since' => $since]);
    $cnt = (int)$stmt->fetchColumn();
    if ($cnt > 0) {
        $data['updates'][] = ['type' => 'status_changes', 'count' => $cnt, 'message' => $cnt . ' appointment status' . ($cnt > 1 ? 'es' : '') . ' changed'];
    }

    // New payments
    $stmt = $db->prepare("SELECT COUNT(*) as cnt FROM payments WHERE created_at > :since");
    $stmt->execute([':since' => $since]);
    $cnt = (int)$stmt->fetchColumn();
    if ($cnt > 0) {
        $data['updates'][] = ['type' => 'payments', 'count' => $cnt, 'message' => $cnt . ' new payment' . ($cnt > 1 ? 's' : '') . ' submitted'];
    }

    // New feedback
    $stmt = $db->prepare("SELECT COUNT(*) as cnt FROM feedback WHERE created_at > :since");
    $stmt->execute([':since' => $since]);
    $cnt = (int)$stmt->fetchColumn();
    if ($cnt > 0) {
        $data['updates'][] = ['type' => 'feedback', 'count' => $cnt, 'message' => $cnt . ' new review' . ($cnt > 1 ? 's' : '')];
    }

    // New users
    $stmt = $db->prepare("SELECT COUNT(*) as cnt FROM users WHERE created_at > :since");
    $stmt->execute([':since' => $since]);
    $cnt = (int)$stmt->fetchColumn();
    if ($cnt > 0) {
        $data['updates'][] = ['type' => 'users', 'count' => $cnt, 'message' => $cnt . ' new user' . ($cnt > 1 ? 's' : '') . ' registered'];
    }
}

/* ── UNREAD MESSAGES (all roles) ── */
$stmt = $db->prepare(
    "SELECT COUNT(*) as cnt FROM messages
     WHERE receiver_id = :uid AND is_read = 0 AND is_ai_reply = 0"
);
$stmt->execute([':uid' => $uid]);
$unread = (int)$stmt->fetchColumn();
$data['unread_messages'] = $unread;

/* ── UNREAD NOTIFICATIONS (all roles) ── */
$stmt = $db->prepare(
    "SELECT COUNT(*) as cnt FROM notifications WHERE user_id = :uid AND is_read = 0"
);
$stmt->execute([':uid' => $uid]);
$data['unread_notifications'] = (int)$stmt->fetchColumn();

/* ── SCHEDULE CHANGES (for client booking calendar) ── */
if ($role === 'client') {
    try {
        $stmt = $db->prepare(
            "SELECT COUNT(*) as cnt FROM schedules WHERE updated_at > :since"
        );
        $stmt->execute([':since' => $since]);
        $data['schedule_changes'] = (int)$stmt->fetchColumn();
    } catch (Exception $e) {
        // updated_at column may not exist yet — fall back to created_at
        try {
            $stmt = $db->prepare(
                "SELECT COUNT(*) as cnt FROM schedules WHERE created_at > :since"
            );
            $stmt->execute([':since' => $since]);
            $data['schedule_changes'] = (int)$stmt->fetchColumn();
        } catch (Exception $e2) {
            $data['schedule_changes'] = 0;
        }
    }
}

/* ── SCHEDULE CHANGES (for engineer — their own slots) ── */
if ($role === 'engineer' && $eid) {
    try {
        $stmt = $db->prepare(
            "SELECT COUNT(*) as cnt FROM schedules WHERE engineer_id = :eid AND updated_at > :since"
        );
        $stmt->execute([':eid' => $eid, ':since' => $since]);
        $data['schedule_changes'] = (int)$stmt->fetchColumn();
    } catch (Exception $e) {
        $data['schedule_changes'] = 0;
    }
}

/* ── SCHEDULE CHANGES (for admin — all engineers) ── */
if ($role === 'admin') {
    try {
        $stmt = $db->prepare(
            "SELECT COUNT(*) as cnt FROM schedules WHERE updated_at > :since"
        );
        $stmt->execute([':since' => $since]);
        $cnt = (int)$stmt->fetchColumn();
        if ($cnt > 0) {
            $data['updates'][] = ['type' => 'schedules', 'count' => $cnt, 'message' => $cnt . ' schedule slot' . ($cnt > 1 ? 's' : '') . ' updated by engineers'];
        }
    } catch (Exception $e) {
        // updated_at not available
    }
}

echo json_encode($data);
