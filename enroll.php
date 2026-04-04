<?php
session_start();
include("config/db.php");

// Check login
if(!isset($_SESSION['user_id'])){
    header("Location: login.php");
    exit();
}

// Only student allowed
if($_SESSION['role'] != 'student'){
    echo "Access Denied!";
    exit();
}

// Get data
$user_id = $_SESSION['user_id'];
$course_id = $_GET['course_id'];

// Prevent duplicate enrollment
$check = $conn->query("SELECT * FROM enrollments WHERE user_id='$user_id' AND course_id='$course_id'");

if($check->num_rows > 0){
    echo "Already Enrolled!";
} else {
    $sql = "INSERT INTO enrollments (user_id, course_id, progress) VALUES ('$user_id', '$course_id', 0)";
    
    if($conn->query($sql)){
        echo "Enrolled Successfully!";
    } else {
        echo "Error: " . $conn->error;
    }
}
?>