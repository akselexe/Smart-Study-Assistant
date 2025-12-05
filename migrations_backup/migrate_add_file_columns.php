<?php
require_once 'config/config.php';

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "<pre>Checking 'exercises' table for file columns...\n";

$cols = [
    'file_path' => "ALTER TABLE exercises ADD COLUMN file_path VARCHAR(255) DEFAULT NULL",
    'file_name' => "ALTER TABLE exercises ADD COLUMN file_name VARCHAR(255) DEFAULT NULL",
];

foreach ($cols as $col => $sql) {
    $check = "SELECT COUNT(*) as cnt FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = '" . $conn->real_escape_string(DB_NAME) . "' AND TABLE_NAME = 'exercises' AND COLUMN_NAME = '" . $conn->real_escape_string($col) . "'";
    $res = $conn->query($check);
    $row = $res->fetch_assoc();
    if (intval($row['cnt']) === 0) {
        echo "Adding '$col' column... ";
        if ($conn->query($sql)) {
            echo "OK\n";
        } else {
            echo "FAILED: " . $conn->error . "\n";
        }
    } else {
        echo "'$col' column already exists.\n";
    }
}

echo "Done.\n</pre>";
$conn->close();

?>
