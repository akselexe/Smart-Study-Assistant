<?php
require_once 'config/config.php';

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "<pre>Checking 'exercises' table for 'course' and 'topic' columns...\n";

$checkCourse = "SELECT COUNT(*) as cnt FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = '" . $conn->real_escape_string(DB_NAME) . "' AND TABLE_NAME = 'exercises' AND COLUMN_NAME = 'course'";
$res = $conn->query($checkCourse);
$row = $res->fetch_assoc();
if (intval($row['cnt']) === 0) {
    echo "Adding 'course' column... ";
    if ($conn->query("ALTER TABLE exercises ADD COLUMN course VARCHAR(100) DEFAULT NULL")) {
        echo "OK\n";
    } else {
        echo "FAILED: " . $conn->error . "\n";
    }
} else {
    echo "'course' column already exists.\n";
}

$checkTopic = "SELECT COUNT(*) as cnt FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = '" . $conn->real_escape_string(DB_NAME) . "' AND TABLE_NAME = 'exercises' AND COLUMN_NAME = 'topic'";
$res = $conn->query($checkTopic);
$row = $res->fetch_assoc();
if (intval($row['cnt']) === 0) {
    echo "Adding 'topic' column... ";
    if ($conn->query("ALTER TABLE exercises ADD COLUMN topic VARCHAR(100) DEFAULT NULL")) {
        echo "OK\n";
    } else {
        echo "FAILED: " . $conn->error . "\n";
    }
} else {
    echo "'topic' column already exists.\n";
}

echo "Done.\n</pre>";
$conn->close();

?>
