<?php
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../models/Appointment.php';
require_once '../models/Schedule.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$db = (new Database())->getConnection();
$appointmentModel = new Appointment($db);
$scheduleModel = new Schedule($db);

$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'get_available_slots':
        $engineer_id = intval($_GET['engineer_id'] ?? 0);
        $month = intval($_GET['month'] ?? date('n'));
        $year = intval($_GET['year'] ?? date('Y'));

        if (!$engineer_id) {
            echo json_encode(['success' => false, 'error' => 'Invalid engineer']);
            exit;
        }

        $slots = $scheduleModel->getByEngineerId($engineer_id, $month, $year);
        echo json_encode(['success' => true, 'slots' => $slots]);
        break;

    case 'get_time_slots':
        $engineer_id = intval($_GET['engineer_id'] ?? 0);
        $date = $_GET['date'] ?? '';

        if (!$engineer_id || !$date) {
            echo json_encode(['success' => false, 'error' => 'Invalid data']);
            exit;
        }

        $slots = $scheduleModel->getAvailableSlotsByDate($engineer_id, $date);
        echo json_encode(['success' => true, 'slots' => $slots]);
        break;

    case 'get_updates':
        $id = intval($_GET['id'] ?? 0);
        if (!$id) {
            echo json_encode(['success' => false]);
            exit;
        }

        $appointment = $appointmentModel->getById($id);
        if (!$appointment || ($appointment['client_id'] != $_SESSION['user_id'] && $appointment['engineer_id'] != $_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            exit;
        }

        echo json_encode([
            'success' => true,
            'status' => $appointment['status'],
            'updated_at' => $appointment['updated_at']
        ]);
        break;

    default:
        echo json_encode(['success' => false, 'error' => 'Invalid action']);
}
