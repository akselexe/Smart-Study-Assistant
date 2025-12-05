<?php
require_once __DIR__ . '/../config/roles.php';
require_once __DIR__ . '/../config/db.php';
$auth = requireProfessor();

$db = new Database();
$professorId = $auth->getUserId();
$username = $auth->getUsername();

$success = $_GET['success'] ?? '';
$error = $_GET['error'] ?? '';

$filterSubject = $_GET['subject'] ?? '';
$filterCourse = $_GET['course'] ?? '';
$filterTopic = $_GET['topic'] ?? '';

$query = "SELECT * FROM exercises WHERE professor_id = ?";
$params = [$professorId];
$types = "i";

if (!empty($filterSubject)) {
    $query .= " AND subject = ?";
    $params[] = $filterSubject;
    $types .= "s";
}

if (!empty($filterCourse)) {
    $query .= " AND course = ?";
    $params[] = $filterCourse;
    $types .= "s";
}

if (!empty($filterTopic)) {
    $query .= " AND topic = ?";
    $params[] = $filterTopic;
    $types .= "s";
}

$query .= " ORDER BY created_at DESC";

$stmt = $db->prepare($query);
if (count($params) > 1) {
    $stmt->bind_param($types, ...$params);
} else {
    $stmt->bind_param($types, $professorId);
}
$stmt->execute();
$result = $stmt->get_result();
$exercises = $result->fetch_all(MYSQLI_ASSOC);

// Get unique subjects, courses, and topics for filters
$stmt = $db->prepare("SELECT DISTINCT subject, course, topic FROM exercises WHERE professor_id = ?");
$stmt->bind_param("i", $professorId);
$stmt->execute();
$filterResult = $stmt->get_result();
$subjects = [];
$courses = [];
$topics = [];
while ($row = $filterResult->fetch_assoc()) {
    if (!empty($row['subject']) && !in_array($row['subject'], $subjects)) {
        $subjects[] = $row['subject'];
    }
    if (!empty($row['course']) && !in_array($row['course'], $courses)) {
        $courses[] = $row['course'];
    }
    if (!empty($row['topic']) && !in_array($row['topic'], $topics)) {
        $topics[] = $row['topic'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Professor Dashboard - Smart Study Assistant</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/exercises.css">
</head>
<body>
    <div class="container">
        <header>
            <div class="header-top">
                <div>
                    <h1>📚 Professor Dashboard</h1>
                    <p class="subtitle">Manage your exercises</p>
                </div>
                <div class="user-info">
                    <span class="welcome-text">Welcome, <strong><?php echo htmlspecialchars($username); ?></strong>!</span>
                    <a href="../logout.php" class="btn-logout">Logout</a>
                </div>
            </div>
        </header>

        <div class="dashboard-content">
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-error">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <div class="dashboard-actions">
                <a href="create_exercise.php" class="btn-primary">+ Create New Exercise</a>
            </div>

            <div class="filters">
                <form method="GET" class="filter-form">
                    <select name="subject" onchange="this.form.submit()">
                        <option value="">All Subjects</option>
                        <?php foreach ($subjects as $subject): ?>
                            <option value="<?php echo htmlspecialchars($subject); ?>" <?php echo $filterSubject === $subject ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($subject); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <select name="course" onchange="this.form.submit()">
                        <option value="">All Courses</option>
                        <?php foreach ($courses as $course): ?>
                            <option value="<?php echo htmlspecialchars($course); ?>" <?php echo $filterCourse === $course ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($course); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <select name="topic" onchange="this.form.submit()">
                        <option value="">All Topics</option>
                        <?php foreach ($topics as $topic): ?>
                            <option value="<?php echo htmlspecialchars($topic); ?>" <?php echo $filterTopic === $topic ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($topic); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <a href="dashboard.php" class="btn-secondary">Clear Filters</a>
                </form>
            </div>

            <div class="exercises-list">
                <?php if (empty($exercises)): ?>
                    <div class="empty-state">
                        <p>No exercises found. <a href="create_exercise.php">Create your first exercise</a>!</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($exercises as $exercise): ?>
                        <div class="exercise-card">
                            <div class="exercise-header">
                                <h3><?php echo htmlspecialchars($exercise['title']); ?></h3>
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
                                <p class="exercise-description"><?php echo htmlspecialchars(substr($exercise['description'], 0, 150)); ?><?php echo strlen($exercise['description']) > 150 ? '...' : ''; ?></p>
                            <?php endif; ?>
                            <div class="exercise-actions">
                                <a href="create_exercise.php?id=<?php echo $exercise['id']; ?>" class="btn-small">Edit</a>
                                <a href="delete_exercise.php?id=<?php echo $exercise['id']; ?>" class="btn-small btn-danger" onclick="return confirm('Are you sure you want to delete this exercise?');">Delete</a>
                                <span class="exercise-date">Created: <?php echo date('M d, Y', strtotime($exercise['created_at'])); ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
<?php $db->close(); ?>
