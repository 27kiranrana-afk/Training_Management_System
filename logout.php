<?php
session_start();
include("config/db.php");
include("includes/base.php");

// Clear remember me cookie
if(isset($_COOKIE['remember_token'])){
    setcookie('remember_token', '', time() - 3600, '/');
}

// Clear remember token from DB
if(isset($_SESSION['user_id'])){
    $uid  = $_SESSION['user_id'];
    $stmt = $conn->prepare("UPDATE users SET remember_token=NULL WHERE id=?");
    $stmt->bind_param("i", $uid);
    $stmt->execute();
}

session_unset();
session_destroy();

header("Location: " . BASE_URL . "login.php");
exit();
?>
