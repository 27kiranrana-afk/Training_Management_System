<?php
// Database Configuration
// Copy this file to db.php and fill in your actual credentials

$host   = 'localhost';
$dbname = 'training_db';
$user   = 'your_db_username';    // e.g. root
$pass   = 'your_db_password';    // e.g. leave empty for XAMPP default

$conn = new mysqli($host, $user, $pass, $dbname);

if($conn->connect_error){
    die("Database connection failed: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");
