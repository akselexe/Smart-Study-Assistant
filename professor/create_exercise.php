<?php
require_once __DIR__ . '/../config/roles.php';
require_once __DIR__ . '/../config/db.php';
$auth = requireProfessor();

$db = new Database();
$professorId = $auth->getUserId();
$username = $auth->getUsername();

$exercise = null;
$exerciseId = $_GET['id'] ?? null;
$error = '';
$success = '';

if ($exerciseId) {
    $stmt = $db->prepare("SELECT * FROM exercises WHERE id = ? AND professor_id = ?");
    $stmt->bind_param("ii", $exerciseId, $professorId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 0) {
        header('Location: dashboard.php');
        exit;
    }
    $exercise = $result->fetch_assoc();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $course = trim($_POST['course'] ?? '');
    $topic = trim($_POST['topic'] ?? '');
    $content = trim($_POST['content'] ?? '');

    if (empty($title) || empty($subject)) {
        $error = 'Title and Subject are required';
    } else {
        $filePath = null;
        $fileName = null;
        if (isset($_FILES['exercise_file']) && $_FILES['exercise_file']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['exercise_file'];
            $allowedTypes = ['application/pdf', 'image/jpeg', 'image/png', 'image/gif', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
            $allowedExtensions = ['pdf', 'jpg', 'jpeg', 'png', 'gif', 'doc', 'docx'];
            $maxSize = 10 * 1024 * 1024; // 10MB

            // Validate file type
            $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if (!in_array($fileExtension, $allowedExtensions) || !in_array($file['type'], $allowedTypes)) {
                $error = 'Invalid file type. Allowed: PDF, DOC, DOCX, JPG, PNG, GIF';
            } elseif ($file['size'] > $maxSize) {
                $error = 'File size exceeds 10MB limit';
            } else {
                // Create uploads directory if it doesn't exist
                $uploadsDir = __DIR__ . '/../uploads/exercises';
                if (!file_exists($uploadsDir)) {
                    mkdir($uploadsDir, 0755, true);
                }

                // Generate unique filename
                $fileName = $file['name'];
                $uniqueName = uniqid() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $fileName);
                $filePath = $uploadsDir . '/' . $uniqueName;

                if (!move_uploaded_file($file['tmp_name'], $filePath)) {
                    $error = 'Failed to upload file';
                } else {
                    $filePath = 'uploads/exercises/' . $uniqueName;
                }
            }
        }

        if (empty($error)) {
            if ($exerciseId) {
                if ($filePath) {
                    // Delete old file if exists
                    if (!empty($exercise['file_path'])) {
                        $oldFilePath = __DIR__ . '/../' . $exercise['file_path'];
                        if (file_exists($oldFilePath)) {
                            unlink($oldFilePath);
                        }
                    }
                    $stmt = $db->prepare("UPDATE exercises SET title = ?, description = ?, subject = ?, course = ?, topic = ?, content = ?, file_path = ?, file_name = ? WHERE id = ? AND professor_id = ?");
                    $stmt->bind_param("ssssssssii", $title, $description, $subject, $course, $topic, $content, $filePath, $fileName, $exerciseId, $professorId);
                } else {
                    $stmt = $db->prepare("UPDATE exercises SET title = ?, description = ?, subject = ?, course = ?, topic = ?, content = ? WHERE id = ? AND professor_id = ?");
                    $stmt->bind_param("ssssssii", $title, $description, $subject, $course, $topic, $content, $exerciseId, $professorId);
                }
            } else {
                
                if ($filePath) {
                    $stmt = $db->prepare("INSERT INTO exercises (professor_id, title, description, subject, course, topic, content, file_path, file_name) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->bind_param("issssssss", $professorId, $title, $description, $subject, $course, $topic, $content, $filePath, $fileName);
                } else {
                    $stmt = $db->prepare("INSERT INTO exercises (professor_id, title, description, subject, course, topic, content) VALUES (?, ?, ?, ?, ?, ?, ?)");
                    $stmt->bind_param("issssss", $professorId, $title, $description, $subject, $course, $topic, $content);
                }
            }

            if ($stmt->execute()) {
                $success = $exerciseId ? 'Exercise updated successfully!' : 'Exercise created successfully!';
                header('Location: dashboard.php?success=' . urlencode($success));
                exit;
            } else {
                $error = 'Failed to save exercise';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $exerciseId ? 'Edit' : 'Create'; ?> Exercise - Smart Study Assistant</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/exercises.css">
</head>
<body>
    <div class="container">
        <header>
            <div class="header-top">
                <div>
                    <h1>📚 <?php echo $exerciseId ? 'Edit' : 'Create'; ?> Exercise</h1>
                    <p class="subtitle"><?php echo $exerciseId ? 'Update exercise details' : 'Add a new exercise for students'; ?></p>
                </div>
                <div class="user-info">
                    <a href="dashboard.php" class="btn-logout">← Back to Dashboard</a>
                </div>
            </div>
        </header>

        <div class="form-container">
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data" class="exercise-form">
                <div class="form-group">
                    <label for="title">Title *</label>
                    <input 
                        type="text" 
                        id="title" 
                        name="title" 
                        value="<?php echo htmlspecialchars($exercise['title'] ?? $_POST['title'] ?? ''); ?>"
                        required
                        placeholder="Exercise title"
                    >
                </div>

                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea 
                        id="description" 
                        name="description" 
                        rows="3"
                        placeholder="Brief description of the exercise"
                    ><?php echo htmlspecialchars($exercise['description'] ?? $_POST['description'] ?? ''); ?></textarea>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="subject">Subject *</label>
                        <select id="subject" name="subject" required>
                            <option value="">Select Subject</option>
                            <option value="Mathematics" <?php echo (($exercise['subject'] ?? $_POST['subject'] ?? '') === 'Mathematics') ? 'selected' : ''; ?>>Mathematics</option>
                            <option value="Science" <?php echo (($exercise['subject'] ?? $_POST['subject'] ?? '') === 'Science') ? 'selected' : ''; ?>>Science</option>
                            <option value="History" <?php echo (($exercise['subject'] ?? $_POST['subject'] ?? '') === 'History') ? 'selected' : ''; ?>>History</option>
                            <option value="Programming" <?php echo (($exercise['subject'] ?? $_POST['subject'] ?? '') === 'Programming') ? 'selected' : ''; ?>>Programming</option>
                            <option value="General" <?php echo (($exercise['subject'] ?? $_POST['subject'] ?? '') === 'General') ? 'selected' : ''; ?>>General</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="course">Course</label>
                        <input 
                            type="text" 
                            id="course" 
                            name="course" 
                            value="<?php echo htmlspecialchars($exercise['course'] ?? $_POST['course'] ?? ''); ?>"
                            placeholder="e.g., CS101, Math 201"
                        >
                    </div>

                    <div class="form-group">
                        <label for="topic">Topic/Chapter</label>
                        <input 
                            type="text" 
                            id="topic" 
                            name="topic" 
                            value="<?php echo htmlspecialchars($exercise['topic'] ?? $_POST['topic'] ?? ''); ?>"
                            placeholder="e.g., Chapter 5, Functions"
                        >
                    </div>
                </div>

                <div class="form-group">
                    <label for="content">Exercise Content</label>
                    <textarea 
                        id="content" 
                        name="content" 
                        rows="10"
                        placeholder="Enter the exercise content, questions, instructions, etc."
                    ><?php echo htmlspecialchars($exercise['content'] ?? $_POST['content'] ?? ''); ?></textarea>
                </div>

                <div class="form-group">
                    <label for="exercise_file">Attach File (Optional)</label>
                    <input 
                        type="file" 
                        id="exercise_file" 
                        name="exercise_file" 
                        accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.gif"
                    >
                    <small>Allowed: PDF, DOC, DOCX, JPG, PNG, GIF (Max 10MB)</small>
                    <?php if (!empty($exercise['file_name'])): ?>
                        <div class="current-file">
                            <p>Current file: <strong><?php echo htmlspecialchars($exercise['file_name']); ?></strong></p>
                            <p><small>Upload a new file to replace it</small></p>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn-primary"><?php echo $exerciseId ? 'Update Exercise' : 'Create Exercise'; ?></button>
                    <a href="dashboard.php" class="btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
<?php $db->close(); ?>
