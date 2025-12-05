<?php
require_once 'config/config.php';

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "<pre>Checking 'exercises' table for 'description' column...\n";

$check = "SELECT COUNT(*) as cnt FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = '" . $conn->real_escape_string(DB_NAME) . "' AND TABLE_NAME = 'exercises' AND COLUMN_NAME = 'description'";
$res = $conn->query($check);
$row = $res->fetch_assoc();
if (intval($row['cnt']) === 0) {
    echo "Adding 'description' column... ";
    if ($conn->query("ALTER TABLE exercises ADD COLUMN description TEXT DEFAULT NULL")) {
        echo "OK\n";
    } else {
        echo "FAILED: " . $conn->error . "\n";
    }
} else {
    echo "'description' column already exists.\n";
}

echo "Done.\n</pre>";
$conn->close();

?>
