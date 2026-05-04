<?php
include("config/db.php");

// Clean orphaned enrollments
$conn->query("DELETE FROM enrollments WHERE course_id NOT IN (SELECT id FROM courses)");
echo "<p style='color:green'>✅ Orphaned enrollments removed: " . $conn->affected_rows . "</p>";

// Clean orphaned inquiries (from deleted users)
$conn->query("DELETE FROM inquiries WHERE user_id NOT IN (SELECT id FROM users)");
echo "<p style='color:green'>✅ Orphaned inquiries removed: " . $conn->affected_rows . "</p>";

// Clean orphaned certificates
$conn->query("DELETE FROM certificates WHERE course_id NOT IN (SELECT id FROM courses)");
$conn->query("DELETE FROM certificates WHERE enrollment_id NOT IN (SELECT id FROM enrollments)");
echo "<p style='color:green'>✅ Orphaned certificates removed.</p>";

// Clean orphaned progress logs
$conn->query("DELETE FROM progress_log WHERE enrollment_id NOT IN (SELECT id FROM enrollments)");
echo "<p style='color:green'>✅ Orphaned progress logs removed.</p>";

// Show current counts
$students   = $conn->query("SELECT COUNT(*) FROM users WHERE role='student'")->fetch_row()[0];
$trainers   = $conn->query("SELECT COUNT(*) FROM users WHERE role='trainer'")->fetch_row()[0];
$courses    = $conn->query("SELECT COUNT(*) FROM courses")->fetch_row()[0];
$enrollments= $conn->query("SELECT COUNT(*) FROM enrollments")->fetch_row()[0];
$inquiries  = $conn->query("SELECT COUNT(*) FROM inquiries")->fetch_row()[0];

echo "<hr><h4>Current Counts:</h4>";
echo "<ul>";
echo "<li>Students: $students</li>";
echo "<li>Trainers: $trainers</li>";
echo "<li>Courses: $courses</li>";
echo "<li>Enrollments: $enrollments</li>";
echo "<li>Inquiries: $inquiries</li>";
echo "</ul>";

echo "<p><a href='admin/users.php'>→ Manage Users (delete duplicates)</a></p>";
echo "<a href='admin/dashboard.php'>→ Admin Dashboard</a>";

@unlink(__FILE__);
?>
