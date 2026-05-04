<?php
// Trainer attendance — delegates to admin/attendance.php with trainer role check
session_start();
include("../config/db.php");
include("../includes/auth.php");
require_role('trainer');

// Redirect to the shared attendance page
header("Location: ../admin/attendance.php");
exit();
?>
