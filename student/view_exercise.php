<?php
require_once __DIR__ . '/../config/roles.php';
require_once __DIR__ . '/../config/db.php';
$auth = requireStudent();

$db = new Database();
$username = $auth->getUsername();
$exerciseId = $_GET['id'] ?? null;

if (!$exerciseId) {
    header('Location: exercises.php');
    exit;
}

$stmt = $db->prepare("SELECT e.*, u.username as professor_name FROM exercises e 
                      LEFT JOIN users u ON e.professor_id = u.id 
                      WHERE e.id = ?");
$stmt->bind_param("i", $exerciseId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Location: exercises.php?error=' . urlencode('Exercise not found'));
    exit;
}

$exercise = $result->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($exercise['title']); ?> - Smart Study Assistant</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/exercises.css">
</head>
<body>
    <div class="container">
        <header>
            <div class="header-top">
                <div>
                    <h1>📚 Exercise Details</h1>
                    <p class="subtitle"><?php echo htmlspecialchars($exercise['title']); ?></p>
                </div>
                <div class="user-info">
                    <a href="exercises.php" class="btn-logout">← Back to Exercises</a>
                    <a href="../index.php" class="btn-logout">Chat Assistant</a>
                </div>
            </div>
        </header>

        <div class="exercise-detail-container">
            <div class="exercise-detail-card">
                <div class="exercise-header">
                    <h2><?php echo htmlspecialchars($exercise['title']); ?></h2>
                    <div class="exercise-meta">
                        <span class="badge"><?php echo htmlspecialchars($exercise['subject']); ?></span>
                        <?php if (!empty($exercise['course'])): ?>
                            <span class="badge"><?php echo htmlspecialchars($exercise['course']); ?></span>
                        <?php endif; ?>
                        <?php if (!empty($exercise['topic'])): ?>
                            <span class="badge"><?php echo htmlspecialchars($exercise['topic']); ?></span>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if (!empty($exercise['description'])): ?>
                    <div class="exercise-section">
                        <h3>Description</h3>
                        <p><?php echo nl2br(htmlspecialchars($exercise['description'])); ?></p>
                    </div>
                <?php endif; ?>

                <?php if (!empty($exercise['content'])): ?>
                    <div class="exercise-section">
                        <h3>Exercise Content</h3>
                        <div class="exercise-content">
                            <?php echo nl2br(htmlspecialchars($exercise['content'])); ?>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if (!empty($exercise['file_path']) && !empty($exercise['file_name'])): ?>
                    <div class="exercise-section">
                        <h3>Attached File</h3>
                        <a href="../<?php echo htmlspecialchars($exercise['file_path']); ?>" download class="btn-primary">
                            📎 Download <?php echo htmlspecialchars($exercise['file_name']); ?>
                        </a>
                    </div>
                <?php endif; ?>

                <div class="exercise-actions">
                    <a href="../index.php?exercise_id=<?php echo $exercise['id']; ?>" class="btn-primary btn-large">
                        💬 Get Help with This Exercise
                    </a>
                </div>

                <div class="exercise-info">
                    <p><strong>Posted by:</strong> <?php echo htmlspecialchars($exercise['professor_name'] ?? 'Unknown'); ?></p>
                    <p><strong>Date:</strong> <?php echo date('F d, Y \a\t g:i A', strtotime($exercise['created_at'])); ?></p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
<?php $db->close(); ?>
