<?php
require_once 'config/config.php';

// Create connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);


if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "<!DOCTYPE html>
<html>
<head>
    <title>Add Role Column Migration</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 600px; margin: 50px auto; padding: 20px; background: #f5f5f5; }
        .container { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #333; }
        .success { color: #28a745; font-weight: bold; }
        .error { color: #dc3545; font-weight: bold; }
        .info { color: #17a2b8; }
    </style>
</head>
<body>
<div class='container'>
<h1>Add Role Column Migration</h1>";

// Check if users table exists
$tableCheck = $conn->query("SHOW TABLES LIKE 'users'");
if (!$tableCheck || $tableCheck->num_rows == 0) {
    echo "<p class='error'>✗ Users table does not exist. Please run setup.php first.</p>";
    echo "</div></body></html>";
    exit;
}

// Check if role column exists
$columnCheck = $conn->query("SHOW COLUMNS FROM users WHERE Field = 'role'");
$columnExists = ($columnCheck && $columnCheck->num_rows > 0);

if ($columnExists) {
    echo "<p class='success'>✓ Role column already exists in users table!</p>";
} else {
    // Add the role column
    $sql = "ALTER TABLE users ADD COLUMN role ENUM('student', 'professor') DEFAULT 'student' AFTER password";
    if ($conn->query($sql) === TRUE) {
        echo "<p class='success'>✓ Role column added successfully!</p>";
        
        // Update existing users
        $updateSql = "UPDATE users SET role = 'student' WHERE role IS NULL OR role = ''";
        if ($conn->query($updateSql) === TRUE) {
            $affectedRows = $conn->affected_rows;
            echo "<p class='success'>✓ Updated $affectedRows existing user(s) to 'student' role!</p>";
        }
    } else {
        echo "<p class='error'>✗ Error adding role column: " . $conn->error . "</p>";
        echo "<p class='info'>Try running this SQL manually in phpMyAdmin:</p>";
        echo "<pre style='background: #f8f9fa; padding: 10px; border-radius: 5px;'>ALTER TABLE users ADD COLUMN role ENUM('student', 'professor') DEFAULT 'student' AFTER password;</pre>";
    }
}

$conn->close();

echo "<br><p class='info'>You can now delete this file and try registering again.</p>";
echo "<p><a href='register.php'>Go to Registration</a> | <a href='index.php'>Go to Home</a></p>";
echo "</div></body></html>";
?>
