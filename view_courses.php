<?php
session_start();
include("config/db.php");

$result = $conn->query("SELECT * FROM courses");

echo "<h2>Available Courses</h2>";

while($row = $result->fetch_assoc()){
    echo "<p>";
    echo $row['title'] . " - " . $row['duration'];

    // Show enroll button only for students
    if(isset($_SESSION['role']) && $_SESSION['role'] == 'student'){
        echo " <a href='enroll.php?course_id=".$row['id']."'>Enroll</a>";
    }

    echo "</p>";
}
?>