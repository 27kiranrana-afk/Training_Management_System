<?php
session_start();
include("config/db.php");
include("includes/auth.php");
include("includes/csrf.php");
require_role('student');

// Auto-create required tables
$conn->query("CREATE TABLE IF NOT EXISTS refund_requests (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    user_id         INT NOT NULL,
    course_id       INT NOT NULL,
    enrollment_id   INT NOT NULL,
    payment_id      INT DEFAULT NULL,
    amount          DECIMAL(10,2) NOT NULL DEFAULT 0,
    reason          TEXT DEFAULT NULL,
    status          ENUM('pending','processing','completed','rejected') DEFAULT 'pending',
    requested_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    processed_at    TIMESTAMP NULL DEFAULT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)");

$conn->query("CREATE TABLE IF NOT EXISTS messages (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    user_id     INT NOT NULL,
    title       VARCHAR(200) NOT NULL,
    body        TEXT NOT NULL,
    is_read     TINYINT(1) DEFAULT 0,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)");

$user_id   = $_SESSION['user_id'];
$course_id = intval($_POST['course_id'] ?? 0);
$reason    = trim($_POST['reason'] ?? '');

if(!$course_id){ header("Location: my_courses.php"); exit(); }
csrf_verify();

// Fetch enrollment — must be within 24 hours
$stmt = $conn->prepare("
    SELECT enrollments.id, enrollments.enrolled_at, enrollments.progress,
           courses.title, courses.fees
    FROM enrollments
    JOIN courses ON enrollments.course_id = courses.id
    WHERE enrollments.user_id = ? AND enrollments.course_id = ?
");
$stmt->bind_param("ii", $user_id, $course_id);
$stmt->execute();
$enrollment = $stmt->get_result()->fetch_assoc();

if(!$enrollment){
    header("Location: my_courses.php?error=not_enrolled"); exit();
}

// Check 24 hour window
$enrolled_at   = strtotime($enrollment['enrolled_at']);
$hours_elapsed = (time() - $enrolled_at) / 3600;

if($hours_elapsed > 24){
    header("Location: my_courses.php?error=cancel_expired"); exit();
}

// Check if already requested
$existing = $conn->prepare("SELECT id FROM refund_requests WHERE user_id=? AND course_id=?");
$existing->bind_param("ii", $user_id, $course_id);
$existing->execute();
$existing->store_result();
if($existing->num_rows > 0){
    header("Location: my_courses.php?error=already_requested"); exit();
}

// Fetch payment record if paid course
$payment = null;
$amount  = 0;
if($enrollment['fees'] > 0){
    $pStmt = $conn->prepare("SELECT id, amount FROM payments WHERE user_id=? AND course_id=? AND status='success' LIMIT 1");
    $pStmt->bind_param("ii", $user_id, $course_id);
    $pStmt->execute();
    $payment = $pStmt->get_result()->fetch_assoc();
    $amount  = $payment ? $payment['amount'] : $enrollment['fees'];
}

// Create refund request
$payment_id = $payment ? $payment['id'] : null;
$ins = $conn->prepare("INSERT INTO refund_requests (user_id, course_id, enrollment_id, payment_id, amount, reason) VALUES (?,?,?,?,?,?)");
$ins->bind_param("iiiids", $user_id, $course_id, $enrollment['id'], $payment_id, $amount, $reason);
$ins->execute();

// Remove enrollment
$del = $conn->prepare("DELETE FROM enrollments WHERE user_id=? AND course_id=?");
$del->bind_param("ii", $user_id, $course_id);
$del->execute();

// Remove material completions
$delMc = $conn->prepare("
    DELETE mc FROM material_completions mc
    JOIN course_materials cm ON mc.material_id = cm.id
    WHERE cm.course_id = ? AND mc.user_id = ?
");
$delMc->bind_param("ii", $course_id, $user_id);
$delMc->execute();

// Send confirmation message to student
$title = "Enrollment Cancellation Received — " . $enrollment['title'];
$body  = $enrollment['fees'] > 0
    ? "Your enrollment in \"" . $enrollment['title'] . "\" has been cancelled.\n\nRefund of ₹" . number_format($amount, 2) . " is being processed. You will receive a confirmation once the refund is completed (usually within 5-7 business days)."
    : "Your enrollment in \"" . $enrollment['title'] . "\" has been cancelled successfully.";

$mIns = $conn->prepare("INSERT INTO messages (user_id, title, body) VALUES (?,?,?)");
$mIns->bind_param("iss", $user_id, $title, $body);
$mIns->execute();

header("Location: my_courses.php?cancelled=1"); exit();
?>
