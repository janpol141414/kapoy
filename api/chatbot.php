<?php
require_once '../config/config.php';
require_once '../helpers/AIHelper.php';

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$message = trim($data['message'] ?? '');

if (empty($message)) {
    echo json_encode(['success' => false, 'error' => 'Empty message']);
    exit;
}

$response = AIHelper::generateResponse($message);

echo json_encode([
    'success' => true,
    'response' => $response,
    'timestamp' => date('Y-m-d H:i:s')
]);
