<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

$db = new Database();
$userId = $auth->getUserId();

$sessionId = $_GET['session_id'] ?? null;

try {
    if (empty($sessionId)) {
        $stmt = $db->prepare("SELECT session_id FROM chat_sessions WHERE user_id = ? ORDER BY updated_at DESC LIMIT 1");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res && $res->num_rows > 0) {
            $row = $res->fetch_assoc();
            $sessionId = $row['session_id'];
        }
    }

    if (empty($sessionId)) {
        echo json_encode(['success' => true, 'messages' => [], 'session_id' => null]);
        exit;
    }

    // Verify session belongs to this user
    $stmt = $db->prepare("SELECT user_id FROM chat_sessions WHERE session_id = ? LIMIT 1");
    $stmt->bind_param("s", $sessionId);
    $stmt->execute();
    $res = $stmt->get_result();
    if (!$res || $res->num_rows === 0) {
        echo json_encode(['success' => false, 'error' => 'Session not found']);
        exit;
    }
    $row = $res->fetch_assoc();
    if ((int)$row['user_id'] !== (int)$userId) {
        echo json_encode(['success' => false, 'error' => 'Permission denied']);
        exit;
    }

    $stmt = $db->prepare("SELECT role, message, created_at FROM chat_messages WHERE session_id = ? ORDER BY created_at ASC LIMIT 500");
    $stmt->bind_param("s", $sessionId);
    $stmt->execute();
    $res = $stmt->get_result();
    $messages = [];
    while ($r = $res->fetch_assoc()) {
        $messages[] = $r;
    }

    echo json_encode(['success' => true, 'messages' => $messages, 'session_id' => $sessionId]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

$db->close();
?>
