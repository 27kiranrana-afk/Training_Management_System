<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

include("config/db.php");

if(isset($_POST['login'])){
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Check user
    $sql = "SELECT * FROM users WHERE email='$email' AND password='$password'";
    $result = $conn->query($sql);

    if($result->num_rows > 0){
        $row = $result->fetch_assoc();

        // ✅ FIXED SESSION VARIABLES
        $_SESSION['user_id'] = $row['id'];   // VERY IMPORTANT
        $_SESSION['user'] = $row['name'];    // for display
        $_SESSION['role'] = $row['role'];    // for access control

        header("Location: dashboard.php");
        exit();
    } else {
        echo "Invalid Email or Password!";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Login</title>
</head>
<body>

<h2>User Login</h2>

<form method="POST">
    Email: <input type="email" name="email" required><br><br>
    Password: <input type="password" name="password" required><br><br>

    <input type="submit" name="login" value="Login">
</form>

</body>
</html>