<?php
session_start();
include("config/db.php");
include("includes/auth.php");
require_role('student');

$user_id   = $_SESSION['user_id'];
$course_id = intval($_GET['course_id'] ?? 0);

if($course_id <= 0){ header("Location: view_courses.php"); exit(); }

// Fetch course to check fees
$cStmt = $conn->prepare("SELECT fees FROM courses WHERE id = ? AND is_active = 1");
$cStmt->bind_param("i", $course_id);
$cStmt->execute();
$course_row = $cStmt->get_result()->fetch_assoc();

if(!$course_row){ header("Location: view_courses.php"); exit(); }

// Block direct access for paid courses — must go through payment
if($course_row['fees'] > 0){
    // Check if payment exists for this user+course
    $conn->query("CREATE TABLE IF NOT EXISTS payments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        course_id INT NOT NULL,
        razorpay_order_id VARCHAR(100),
        razorpay_payment_id VARCHAR(100),
        amount DECIMAL(10,2),
        status VARCHAR(20) DEFAULT 'success',
        paid_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    $pChk = $conn->prepare("SELECT id FROM payments WHERE user_id=? AND course_id=? AND status='success'");
    $pChk->bind_param("ii", $user_id, $course_id);
    $pChk->execute();
    $pChk->store_result();
    if($pChk->num_rows === 0){
        // No payment found — redirect to payment page
        header("Location: payment.php?course_id=$course_id");
        exit();
    }
}

$check = $conn->prepare("SELECT id FROM enrollments WHERE user_id=? AND course_id=?");
$check->bind_param("ii", $user_id, $course_id);
$check->execute();
$check->store_result();

if($check->num_rows > 0){
    $msg = "already";
} else {
    $stmt = $conn->prepare("INSERT INTO enrollments (user_id, course_id, progress) VALUES (?, ?, 0)");
    $stmt->bind_param("ii", $user_id, $course_id);
    $msg = $stmt->execute() ? "success" : "error";
}
?>
<?php include("includes/header.php"); ?>

<div class="mt-4">
  <?php if($msg == "success"): ?>
    <div class="alert alert-success alert-dismissible fade show">
      Enrolled successfully!<button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php elseif($msg == "already"): ?>
    <div class="alert alert-warning alert-dismissible fade show">
      You are already enrolled in this course.<button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php else: ?>
    <div class="alert alert-danger">Enrollment failed. Please try again.</div>
  <?php endif; ?>
  <a href="my_courses.php" class="btn btn-primary">My Courses</a>
  <a href="view_courses.php" class="btn btn-secondary ms-2">Browse More</a>
</div>

<?php include("includes/footer.php"); ?>
