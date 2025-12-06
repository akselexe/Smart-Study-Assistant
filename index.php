<?php
require_once 'config/auth.php';

$auth = new Auth();
$auth->requireLogin();

if ($auth->isProfessor()) {
    header('Location: professor/dashboard.php');
    exit;
}

$username = $auth->getUsername();
$userRole = $auth->getUserRole();
$exerciseId = $_GET['exercise_id'] ?? null;

$exerciseContext = null;
if ($exerciseId) {
    require_once 'config/db.php';
    $db = new Database();
    $stmt = $db->prepare("SELECT title, content, subject FROM exercises WHERE id = ?");
    $stmt->bind_param("i", $exerciseId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $exerciseContext = $result->fetch_assoc();
    }
    $db->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Smart Study Assistant</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="container">
        <header>
            <div class="header-top">
                <div>
                    <h1>📚 Smart Study Assistant</h1>
                    <p class="subtitle">Your AI-powered learning companion</p>
                </div>
                <div class="user-info">
                    <span class="welcome-text">Welcome, <strong><?php echo htmlspecialchars($username); ?></strong>! (<?php echo ucfirst($userRole); ?>)</span>
                    <?php if ($userRole === 'student'): ?>
                        <a href="student/exercises.php" class="btn-logout">Exercises</a>
                    <?php elseif ($userRole === 'professor'): ?>
                        <a href="professor/dashboard.php" class="btn-logout">Dashboard</a>
                    <?php endif; ?>
                    <a href="logout.php" class="btn-logout">Logout</a>
                </div>
            </div>
        </header>

        <div class="chat-container">
            <div class="subject-selector">
                <label for="subject">Subject:</label>
                <select id="subject">
                    <option value="General">General</option>
                    <option value="Mathematics">Mathematics</option>
                    <option value="Science">Science</option>
                    <option value="History">History</option>
                    <option value="Programming">Programming</option>
                </select>
            </div>

            <div class="chat-messages" id="chatMessages">
                <div class="message assistant">
                    <div class="message-content">
                        <strong>Assistant:</strong> 
                        <?php if ($exerciseContext): ?>
                            Hello! I see you're working on the exercise: "<?php echo htmlspecialchars($exerciseContext['title']); ?>". How can I help you with it?
                        <?php else: ?>
                            Hello! I'm your Smart Study Assistant. How can I help you learn today?
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="chat-input-container">
                <form id="chatForm">
                    <input 
                        type="text" 
                        id="messageInput" 
                        placeholder="Type your question here..." 
                        autocomplete="off"
                        required
                    >
                    <button type="submit" id="sendButton">
                        <span>Send</span>
                    </button>
                </form>
            </div>
        </div>

        <div class="info-panel">
            <button id="clearChat" class="btn-secondary">Clear Chat</button>
            <button id="newSession" class="btn-secondary">New Session</button>
        </div>
    </div>

    <script src="assets/js/chat.js"></script>
    <?php if ($exerciseContext): ?>
    <script>
        window.exerciseContext = {
            id: <?php echo $exerciseId; ?>,
            title: <?php echo json_encode($exerciseContext['title']); ?>,
            subject: <?php echo json_encode($exerciseContext['subject']); ?>
        };
    </script>
    <?php endif; ?>
</body>
</html>

