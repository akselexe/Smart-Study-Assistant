<?php
require_once __DIR__ . '/../config/roles.php';
require_once __DIR__ . '/../config/db.php';
$auth = requireStudent();

$db = new Database();
$username = $auth->getUsername();

$filterSubject = $_GET['subject'] ?? '';
$filterCourse = $_GET['course'] ?? '';
$filterTopic = $_GET['topic'] ?? '';
$search = trim($_GET['search'] ?? '');

// Build query
// (filters are applied below)
$query = "SELECT e.*, u.username as professor_name FROM exercises e 
          LEFT JOIN users u ON e.professor_id = u.id 
          WHERE 1=1";
$params = [];
$types = "";

if (!empty($filterSubject)) {
    $query .= " AND e.subject = ?";
    $params[] = $filterSubject;
    $types .= "s";
}

if (!empty($filterCourse)) {
    $query .= " AND e.course = ?";
    $params[] = $filterCourse;
    $types .= "s";
}

if (!empty($filterTopic)) {
    $query .= " AND e.topic = ?";
    $params[] = $filterTopic;
    $types .= "s";
}

if (!empty($search)) {
    $query .= " AND (e.title LIKE ? OR e.description LIKE ? OR e.content LIKE ?)";
    $searchParam = "%$search%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
    $types .= "sss";
}

$query .= " ORDER BY e.created_at DESC";

$stmt = $db->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$exercises = $result->fetch_all(MYSQLI_ASSOC);

$stmt = $db->query("SELECT DISTINCT subject, course, topic FROM exercises");
$subjects = [];
$courses = [];
$topics = [];
while ($row = $stmt->fetch_assoc()) {
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
    <title>Exercises - Smart Study Assistant</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/exercises.css">
</head>
<body>
    <div class="container">
        <header>
            <div class="header-top">
                <div>
                    <h1>📚 Exercises</h1>
                    <p class="subtitle">Practice exercises from your professors</p>
                </div>
                <div class="user-info">
                    <span class="welcome-text">Welcome, <strong><?php echo htmlspecialchars($username); ?></strong>!</span>
                    <a href="../index.php" class="btn-logout">Chat Assistant</a>
                    <a href="../logout.php" class="btn-logout">Logout</a>
                </div>
            </div>
        </header>

        <div class="dashboard-content">
            <div class="filters">
                <form method="GET" class="filter-form">
                    <input 
                        type="text" 
                        name="search" 
                        placeholder="Search exercises..." 
                        value="<?php echo htmlspecialchars($search); ?>"
                        class="search-input"
                    >
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
                    <button type="submit" class="btn-secondary">Search</button>
                    <a href="exercises.php" class="btn-secondary">Clear</a>
                </form>
            </div>

            <div class="exercises-list">
                <?php if (empty($exercises)): ?>
                    <div class="empty-state">
                        <p>No exercises found. Try adjusting your filters or search terms.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($exercises as $exercise): ?>
                        <div class="exercise-card">
                            <div class="exercise-header">
                                <h3><a href="view_exercise.php?id=<?php echo $exercise['id']; ?>"><?php echo htmlspecialchars($exercise['title']); ?></a></h3>
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
                                <p class="exercise-description"><?php echo htmlspecialchars(substr($exercise['description'], 0, 200)); ?><?php echo strlen($exercise['description']) > 200 ? '...' : ''; ?></p>
                            <?php endif; ?>
                            <div class="exercise-footer">
                                <a href="view_exercise.php?id=<?php echo $exercise['id']; ?>" class="btn-primary">View Exercise</a>
                                <?php if (!empty($exercise['file_name'])): ?>
                                    <span class="file-indicator">📎 Has attachment</span>
                                <?php endif; ?>
                                <span class="exercise-date">Posted: <?php echo date('M d, Y', strtotime($exercise['created_at'])); ?></span>
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
