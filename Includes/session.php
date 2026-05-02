<?php
session_start(); 

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo "<script type='text/javascript'>
        window.location = '../index.php';
    </script>";
    exit();
}

// Optional: fetch user info if needed
// include 'connection.php';
// $user_id = $_SESSION['user_id'];
// $result = $conn->query("SELECT * FROM users WHERE user_id='$user_id' LIMIT 1");
// $user = $result->fetch_assoc();
?>
