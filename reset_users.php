<?php
include("config/db.php");

// Disable foreign key checks temporarily
$conn->query("SET FOREIGN_KEY_CHECKS = 0");
$conn->query("TRUNCATE TABLE inquiries");
$conn->query("SET FOREIGN_KEY_CHECKS = 1");

$remaining = $conn->query("SELECT COUNT(*) FROM inquiries")->fetch_row()[0];
echo "<h3 style='color:green'>✅ All inquiries removed. Remaining: $remaining</h3>";

@unlink(__FILE__);
echo "<p style='color:green'>🗑️ File deleted.</p>";
echo "<a href='admin/dashboard.php'>→ Admin Dashboard</a>";
?>
