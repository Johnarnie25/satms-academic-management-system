<?php
// ========================================
// DATABASE CONNECTION
// ========================================

$host = "localhost";         // database host
$db_user = "root";           // database username
$db_pass = "";               // database password
$db_name = "stms_db"; // database name

// Create connection
$conn = new mysqli($host, $db_user, $db_pass, $db_name);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Optional: set charset
$conn->set_charset("utf8");
?>
