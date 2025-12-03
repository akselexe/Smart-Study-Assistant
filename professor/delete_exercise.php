<?php
require_once __DIR__ . '/../config/roles.php';
require_once __DIR__ . '/../config/db.php';
$auth = requireProfessor();

$db = new Database();
$professorId = $auth->getUserId();
$exerciseId = $_GET['id'] ?? null;

if (!$exerciseId) {
    header('Location: dashboard.php');
    exit;
}

// Verify exercise belongs to professor
$stmt = $db->prepare("SELECT * FROM exercises WHERE id = ? AND professor_id = ?");
$stmt->bind_param("ii", $exerciseId, $professorId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Location: dashboard.php?error=' . urlencode('Exercise not found or access denied'));
    exit;
}

$exercise = $result->fetch_assoc();

// Delete file if exists
if (!empty($exercise['file_path'])) {
    $filePath = __DIR__ . '/../' . $exercise['file_path'];
    if (file_exists($filePath)) {
        unlink($filePath);
    }
}

// Delete exercise from database
$stmt = $db->prepare("DELETE FROM exercises WHERE id = ? AND professor_id = ?");
$stmt->bind_param("ii", $exerciseId, $professorId);

if ($stmt->execute()) {
    header('Location: dashboard.php?success=' . urlencode('Exercise deleted successfully'));
} else {
    header('Location: dashboard.php?error=' . urlencode('Failed to delete exercise'));
}
exit;
?>
<?php $db->close(); ?>
