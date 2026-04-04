<?php
session_start();
include("config/db.php");

if(!isset($_SESSION['user'])){
    header("Location: login.php");
    exit();
}

$name = $_SESSION['user'];

// Get user id
$user = $conn->query("SELECT id FROM users WHERE name='$name'");
$user_data = $user->fetch_assoc();
$user_id = $user_data['id'];

// Get enrolled courses
$sql = "SELECT courses.title, courses.duration, enrollments.progress 
        FROM enrollments 
        JOIN courses ON enrollments.course_id = courses.id 
        WHERE enrollments.user_id='$user_id'";

$result = $conn->query($sql);

echo "<h2>My Courses</h2>";

if($result->num_rows > 0){
    while($row = $result->fetch_assoc()){
        echo "Course: " . $row['title'] . " | Duration: " . $row['duration'] . " | Progress: " . $row['progress'] . "%<br><br>";
    }
} else {
    echo "No courses enrolled!";
}
?>