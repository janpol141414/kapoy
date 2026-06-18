<?php
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../models/Message.php';
require_once '../helpers/AIHelper.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$db           = (new Database())->getConnection();
$messageModel = new Message($db);

$action = $_GET['action'] ?? '';
if ($action === '') {
    $body   = json_decode(file_get_contents('php://input'), true) ?? [];
    $action = $body['action'] ?? '';
} else {
    $body = [];
}

switch ($action) {

    /* ── Send a file / image / voice ─────────────────────────── */
    case 'send_file':
        $receiver_id = (int)($_POST['receiver_id'] ?? 0);
        $is_voice    = (int)($_POST['is_voice']    ?? 0);

        if (!$receiver_id || empty($_FILES['file'])) {
            echo json_encode(['success' => false, 'error' => 'Missing data']);
            exit;
        }

        $file     = $_FILES['file'];
        $ext      = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed  = ['jpg','jpeg','png','gif','webp','mp4','mov','pdf','doc','docx','xls','xlsx','zip','webm','ogg','mp3'];

        if (!in_array($ext, $allowed)) {
            echo json_encode(['success' => false, 'error' => 'File type not allowed']);
            exit;
        }

        $uploadDir = __DIR__ . '/../uploads/messages/';
        if (!file_exists($uploadDir)) mkdir($uploadDir, 0777, true);

        $filename = ($is_voice ? 'voice_' : 'file_') . time() . '_' . rand(1000,9999) . '.' . $ext;
        if (!move_uploaded_file($file['tmp_name'], $uploadDir . $filename)) {
            echo json_encode(['success' => false, 'error' => 'Upload failed']);
            exit;
        }

        // Determine message prefix
        $imgExts   = ['jpg','jpeg','png','gif','webp'];
        $voiceExts = ['webm','ogg','mp3'];
        if ($is_voice || in_array($ext, $voiceExts)) {
            $msgText = '[VOICE]' . $filename;
        } elseif (in_array($ext, $imgExts)) {
            $msgText = '[IMAGE]' . $filename;
        } else {
            $msgText = '[FILE]' . $filename . '|' . basename($file['name']);
        }

        $msgId = $messageModel->send($_SESSION['user_id'], $receiver_id, $msgText);
        echo json_encode(['success' => (bool)$msgId, 'id' => $msgId]);
        break;

    /* ── Send a message ─────────────────────────────────────── */
    case 'send':
        $data        = $body ?: (json_decode(file_get_contents('php://input'), true) ?? []);
        $receiver_id = (int)($data['receiver_id'] ?? 0);
        $message     = trim($data['message'] ?? '');

        if (!$receiver_id || $message === '') {
            echo json_encode(['success' => false, 'error' => 'Missing fields']);
            exit;
        }

        // Save the real message
        $msgId = $messageModel->send($_SESSION['user_id'], $receiver_id, $message);
        if (!$msgId) {
            echo json_encode(['success' => false, 'error' => 'DB insert failed']);
            exit;
        }

        // AI auto-reply ONLY when:
        //   • the receiver is an engineer (they may not be online)
        //   • the sender is a client
        //   • the message is NOT already an AI reply
        $aiReply = null;
        $aiId    = null;

        $receiverStmt = $db->prepare("SELECT role FROM users WHERE id = :id LIMIT 1");
        $receiverStmt->execute([':id' => $receiver_id]);
        $receiverRole = $receiverStmt->fetchColumn();

        $senderRole = $_SESSION['role'] ?? '';

        if ($senderRole === 'client' && $receiverRole === 'engineer') {
            $aiReply = AIHelper::generateResponse($message);
            // AI reply comes FROM the engineer (receiver) TO the client (sender)
            $aiId = $messageModel->send($receiver_id, $_SESSION['user_id'], $aiReply, 1);
        }

        echo json_encode([
            'success'  => true,
            'id'       => $msgId,
            'ai_reply' => $aiReply,
            'ai_id'    => $aiId,
        ]);
        break;

    /* ── Poll for new messages ──────────────────────────────── */
    case 'poll':
        $contact_id = (int)($_GET['contact_id'] ?? 0);
        $last_id    = (int)($_GET['last_id']    ?? 0);

        if (!$contact_id) {
            echo json_encode(['success' => false, 'messages' => []]);
            exit;
        }

        $msgs = $messageModel->getNewMessages($_SESSION['user_id'], $contact_id, $last_id);
        echo json_encode(['success' => true, 'messages' => $msgs]);
        break;

    /* ── Mark messages as read ──────────────────────────────── */
    case 'mark_read':
        $sender_id = (int)($_GET['sender_id'] ?? $body['sender_id'] ?? 0);
        if ($sender_id) {
            $messageModel->markAsRead($sender_id, $_SESSION['user_id']);
        }
        echo json_encode(['success' => true]);
        break;

    /* ── Soft-delete a message (hide from sender's view) ───── */
    case 'delete_message':
        $msg_id = (int)($body['message_id'] ?? 0);
        if ($msg_id) {
            // Only allow deleting own messages
            $stmt = $db->prepare("UPDATE messages SET message = '[DELETED]', is_read = 1 WHERE id = :id AND sender_id = :uid");
            $stmt->execute([':id' => $msg_id, ':uid' => $_SESSION['user_id']]);
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false]);
        }
        break;

    /* ── Unread count ───────────────────────────────────────── */
    case 'unread_count':
        echo json_encode([
            'success' => true,
            'count'   => $messageModel->getUnreadCount($_SESSION['user_id']),
        ]);
        break;

    default:
        echo json_encode(['success' => false, 'error' => 'Unknown action']);
}
