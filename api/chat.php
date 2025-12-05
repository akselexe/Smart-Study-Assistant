<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';


$auth = new Auth();
if (!$auth->isLoggedIn()) {
    echo json_encode([
        'success' => false,
        'error' => 'Please login to use the chat'
    ]);
    exit;
}


if (empty(GROQ_API_KEY) || GROQ_API_KEY === 'your_groq_api_key_here') {
    echo json_encode([
        'success' => false,
        'error' => 'Groq API key is not configured. Set the GROQ_API_KEY environment variable or update config/config.php (avoid committing secrets).'
    ]);
    exit;
}

$db = new Database();
$userId = $auth->getUserId();


function callGroqAPI($messages, $apiKey) {
    $url = 'https://api.groq.com/openai/v1/chat/completions';
    
    $data = [
        'model' => 'llama-3.1-8b-instant',
        'messages' => $messages,
        'temperature' => 0.7,
        'max_tokens' => 1000
    ];
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $apiKey
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        throw new Exception('cURL Error: ' . $error);
    }
    
    if ($httpCode !== 200) {
        $errorData = json_decode($response, true);
        throw new Exception('API Error: ' . ($errorData['error']['message'] ?? 'HTTP ' . $httpCode));
    }
    
    $result = json_decode($response, true);
    return $result;
}


$input = json_decode(file_get_contents('php://input'), true);
$message = $input['message'] ?? $_POST['message'] ?? '';
$sessionId = $input['session_id'] ?? $_POST['session_id'] ?? session_id();
$subject = $input['subject'] ?? $_POST['subject'] ?? 'General';
$exerciseId = $input['exercise_id'] ?? $_POST['exercise_id'] ?? null;

if (empty($message)) {
    echo json_encode([
        'success' => false,
        'error' => 'Message is required'
    ]);
    exit;
}

try {
    
    $stmt = $db->prepare("INSERT IGNORE INTO chat_sessions (session_id, user_id, subject) VALUES (?, ?, ?)");
    $stmt->bind_param("sis", $sessionId, $userId, $subject);
    $stmt->execute();
    
    
    $stmt = $db->prepare("UPDATE chat_sessions SET subject = ? WHERE session_id = ?");
    $stmt->bind_param("ss", $subject, $sessionId);
    $stmt->execute();
    
    
    $stmt = $db->prepare("INSERT INTO chat_messages (session_id, role, message) VALUES (?, 'user', ?)");
    $stmt->bind_param("ss", $sessionId, $message);
    $stmt->execute();
    
    
    $stmt = $db->prepare("SELECT role, message FROM chat_messages WHERE session_id = ? ORDER BY created_at ASC LIMIT 10");
    $stmt->bind_param("s", $sessionId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $messages = [];
    while ($row = $result->fetch_assoc()) {
        $messages[] = [
            'role' => $row['role'],
            'content' => $row['message']
        ];
    }
    
    
    $exerciseContext = null;
    if ($exerciseId) {
        $stmt = $db->prepare("SELECT title, content, subject FROM exercises WHERE id = ?");
        $stmt->bind_param("i", $exerciseId);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $exerciseContext = $result->fetch_assoc();
            if (!empty($exerciseContext['subject'])) {
                $subject = $exerciseContext['subject'];
            }
        }
    }
    
    
    $systemPrompt = "You are a helpful study assistant. Provide clear, educational explanations to help students learn. Be concise but thorough.";
    if ($subject !== 'General') {
        $systemPrompt .= " The current subject is: " . $subject . ".";
    }
    
    
    if ($exerciseContext) {
        $systemPrompt .= "\n\nThe student is working on an exercise titled: \"" . $exerciseContext['title'] . "\"";
        if (!empty($exerciseContext['content'])) {
            $systemPrompt .= "\nExercise content: " . substr($exerciseContext['content'], 0, 500);
        }
        $systemPrompt .= "\nPlease help them understand and solve this exercise.";
    }
    
    
    $groqMessages = [
        ['role' => 'system', 'content' => $systemPrompt]
    ];
    
    foreach ($messages as $msg) {
        $groqMessages[] = [
            'role' => $msg['role'],
            'content' => $msg['content']
        ];
    }
    
    
    $response = callGroqAPI($groqMessages, GROQ_API_KEY);

    
    $assistantMessage = 'Sorry, I could not generate a response.';
    if (is_array($response)) {
        
        if (!empty($response['choices'][0]['message']['content'])) {
            $assistantMessage = $response['choices'][0]['message']['content'];
        } elseif (!empty($response['choices'][0]['text'])) {
            $assistantMessage = $response['choices'][0]['text'];
        } elseif (!empty($response['choices'][0]['message'])) {
            
            $msg = $response['choices'][0]['message'];
            if (is_string($msg)) {
                $assistantMessage = $msg;
            } elseif (!empty($msg['content']) && is_string($msg['content'])) {
                $assistantMessage = $msg['content'];
            }
        } elseif (!empty($response['output'][0]['content'][0]['text'])) {
            
            $assistantMessage = $response['output'][0]['content'][0]['text'];
        }
        
        if (is_array($assistantMessage)) {
            $assistantMessage = implode("\n", array_map(function($p){
                if (is_array($p)) return json_encode($p);
                return (string)$p;
            }, $assistantMessage));
        }
    }
    
    
    $stmt = $db->prepare("INSERT INTO chat_messages (session_id, role, message) VALUES (?, 'assistant', ?)");
    $stmt->bind_param("ss", $sessionId, $assistantMessage);
    $stmt->execute();
    
    
    $stmt = $db->prepare("UPDATE chat_sessions SET updated_at = CURRENT_TIMESTAMP WHERE session_id = ?");
    $stmt->bind_param("s", $sessionId);
    $stmt->execute();
    
    echo json_encode([
        'success' => true,
        'message' => $assistantMessage,
        'session_id' => $sessionId
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Error: ' . $e->getMessage()
    ]);
}

$db->close();
?>

