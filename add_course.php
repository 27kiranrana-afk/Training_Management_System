<?php
session_start();
include("config/db.php");

if($_SESSION['role'] != 'admin' && $_SESSION['role'] != 'trainer'){
    echo "Access Denied!";
    exit();
}

if(isset($_POST['add'])){
    $title = $_POST['title'];
    $duration = $_POST['duration'];

    $sql = "INSERT INTO courses (title, duration) VALUES ('$title', '$duration')";

    if($conn->query($sql)){
        echo "Course Added Successfully!";
    } else {
        echo "Error: " . $conn->error;
    }
}
?>

<h2>Add Course</h2>

<form method="POST">
    Course Title: <input type="text" name="title" required><br><br>
    Duration: <input type="text" name="duration" required><br><br>

    <input type="submit" name="add" value="Add Course">
</form>