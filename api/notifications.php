<?php
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../models/Notification.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$db = (new Database())->getConnection();
$notifModel = new Notification($db);

$data = json_decode(file_get_contents('php://input'), true);
$action = $data['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'mark_all_read':
        $notifModel->markAllRead($_SESSION['user_id']);
        echo json_encode(['success' => true]);
        break;

    case 'mark_read':
        $id = intval($data['id'] ?? $_GET['id'] ?? 0);
        if ($id) {
            $notifModel->markRead($id);
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false]);
        }
        break;

    case 'get_unread_count':
        $count = $notifModel->getUnreadCount($_SESSION['user_id']);
        echo json_encode(['success' => true, 'count' => $count]);
        break;

    case 'poll_new':
        $last_id = intval($_GET['last_id'] ?? 0);
        $stmt = $db->prepare("SELECT * FROM notifications WHERE user_id = :uid AND id > :lid ORDER BY id DESC LIMIT 10");
        $stmt->execute([':uid' => $_SESSION['user_id'], ':lid' => $last_id]);
        $new = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'notifications' => $new]);
        break;

    default:
        echo json_encode(['success' => false, 'error' => 'Invalid action']);
}
