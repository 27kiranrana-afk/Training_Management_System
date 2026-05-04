<?php
session_start();
include("includes/auth.php");
require_login();

// Route each role to its own dashboard
switch($_SESSION['role']){
    case 'admin':   header("Location: admin/dashboard.php");   break;
    case 'trainer': header("Location: trainer/dashboard.php"); break;
    default:        header("Location: student/dashboard.php"); break;
}
exit();
?>
