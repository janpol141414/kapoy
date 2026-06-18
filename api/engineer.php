<?php
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../models/Engineer.php';
require_once '../models/Appointment.php';
require_once '../models/Notification.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$db = (new Database())->getConnection();
$engineerModel = new Engineer($db);
$engineer = $engineerModel->getByUserId($_SESSION['user_id']);

$data   = json_decode(file_get_contents('php://input'), true) ?? [];
$action = $data['action'] ?? $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'update_availability':
        if (!hasRole('engineer') || !$engineer) {
            echo json_encode(['success' => false, 'error' => 'Unauthorized']); exit;
        }
        $status = $data['status'] ?? '';
        if (!in_array($status, ['available','busy','offline'])) {
            echo json_encode(['success' => false, 'error' => 'Invalid status']); exit;
        }
        $engineerModel->updateAvailability($engineer['id'], $status);
        echo json_encode(['success' => true, 'status' => $status]);
        break;

    case 'accept_appointment':
        if (!hasRole('engineer') || !$engineer) {
            echo json_encode(['success' => false, 'error' => 'Unauthorized']); exit;
        }
        $apt_id = intval($data['apt_id'] ?? 0);
        $appointmentModel = new Appointment($db);
        $apt = $appointmentModel->getById($apt_id);
        if (!$apt || $apt['engineer_id'] != $engineer['id']) {
            echo json_encode(['success' => false, 'error' => 'Not found']); exit;
        }
        $appointmentModel->updateStatus($apt_id, 'confirmed');
        $notif = new Notification($db);
        $notif->create($apt['client_id'], 'Appointment Confirmed',
            "Your appointment ({$apt['confirmation_code']}) has been confirmed by the engineer.",
            'appointment', BASE_URL.'/client/track-status.php?id='.$apt_id);
        echo json_encode(['success' => true]);
        break;

    case 'reject_appointment':
        if (!hasRole('engineer') || !$engineer) {
            echo json_encode(['success' => false, 'error' => 'Unauthorized']); exit;
        }
        $apt_id = intval($data['apt_id'] ?? 0);
        $appointmentModel = new Appointment($db);
        $apt = $appointmentModel->getById($apt_id);
        if (!$apt || $apt['engineer_id'] != $engineer['id']) {
            echo json_encode(['success' => false, 'error' => 'Not found']); exit;
        }
        $appointmentModel->updateStatus($apt_id, 'cancelled');
        $notif = new Notification($db);
        $notif->create($apt['client_id'], 'Appointment Declined',
            "Your appointment ({$apt['confirmation_code']}) was declined by the engineer.",
            'appointment');
        echo json_encode(['success' => true]);
        break;

    case 'add_progress':
        if (!hasRole('engineer') || !$engineer) {
            echo json_encode(['success' => false, 'error' => 'Unauthorized']); exit;
        }
        $apt_id      = intval($data['apt_id'] ?? 0);
        $statusText  = sanitize($data['status'] ?? '');
        $description = sanitize($data['description'] ?? '');
        if (!$apt_id || !$statusText) {
            echo json_encode(['success' => false, 'error' => 'Missing fields']); exit;
        }
        $appointmentModel = new Appointment($db);
        $appointmentModel->addProgressUpdate([
            'appointment_id' => $apt_id,
            'engineer_id'    => $engineer['id'],
            'status'         => $statusText,
            'description'    => $description,
            'photo'          => null,
        ]);
        $apt = $appointmentModel->getById($apt_id);
        if ($apt && $apt['status'] === 'confirmed') {
            $appointmentModel->updateStatus($apt_id, 'in_progress');
        }
        $notif = new Notification($db);
        $notif->create($apt['client_id'], 'Survey Progress Update',
            "Progress update: $statusText", 'status',
            BASE_URL.'/client/track-status.php?id='.$apt_id);
        echo json_encode(['success' => true]);
        break;

    default:
        echo json_encode(['success' => false, 'error' => 'Invalid action']);
}
