<?php
session_start();

if(!isset($_SESSION['user'])){
    header("Location: login.php");
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Dashboard</title>
</head>
<body>

<h2>Welcome <?php echo $_SESSION['user']; ?></h2>
<p>Your Role: <?php echo $_SESSION['role']; ?></p>

<?php
if($_SESSION['role'] == 'admin'){
    echo "<h3>Admin Panel</h3>";
    echo "<p>Manage users, courses, reports</p>";
    echo '<a href="add_course.php">Add Course</a><br>';
}
elseif($_SESSION['role'] == 'trainer'){
    echo "<h3>Trainer Panel</h3>";
    echo "<p>Create and manage courses</p>";
    echo '<a href="add_course.php">Add Course</a><br>';
}
else{
    echo "<h3>Student Panel</h3>";
    echo "<p>View enrolled courses and progress</p>";
    echo '<a href="view_courses.php">View Courses</a><br>';
    echo '<a href="my_courses.php">My Courses</a><br>';
}
?>

<br>
<a href="logout.php">Logout</a>

</body>
</html>