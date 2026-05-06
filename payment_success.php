<?php
session_start();
include("config/db.php");
include("includes/auth.php");
require_login();

$course_id = intval($_GET['course_id'] ?? 0);
$course    = null;
$payment   = null;

if($course_id > 0){
    $stmt = $conn->prepare("SELECT title, duration, fees FROM courses WHERE id=?");
    $stmt->bind_param("i", $course_id);
    $stmt->execute();
    $course = $stmt->get_result()->fetch_assoc();

    // Get latest payment for this course
    $pStmt = $conn->prepare("SELECT * FROM payments WHERE user_id=? AND course_id=? ORDER BY paid_at DESC LIMIT 1");
    $pStmt->bind_param("ii", $_SESSION['user_id'], $course_id);
    $pStmt->execute();
    $payment = $pStmt->get_result()->fetch_assoc();
}
?>
<?php include("includes/header.php"); ?>

<div class="row justify-content-center mt-4">
  <div class="col-md-6 text-center">
    <div class="card shadow p-5">
      <div class="display-1 mb-3">🎉</div>
      <h3 class="text-success fw-bold">Payment Successful!</h3>
      <p class="text-muted">Your enrollment has been confirmed.</p>

      <?php if($course): ?>
      <div class="card bg-light p-3 mt-3 text-start">
        <h6 class="fw-bold mb-2">📋 Enrollment Details</h6>
        <table class="table table-sm mb-0">
          <tr><td class="text-muted">Course</td><td><strong><?php echo htmlspecialchars($course['title']); ?></strong></td></tr>
          <tr><td class="text-muted">Duration</td><td><?php echo htmlspecialchars($course['duration']); ?></td></tr>
          <tr><td class="text-muted">Amount Paid</td><td><strong class="text-success">₹<?php echo number_format($course['fees'], 2); ?></strong></td></tr>
          <?php if($payment): ?>
          <tr><td class="text-muted">Payment ID</td><td><small><?php echo htmlspecialchars($payment['razorpay_payment_id'] ?? '—'); ?></small></td></tr>
          <tr><td class="text-muted">Date</td><td><?php echo date('d M Y, h:i A', strtotime($payment['paid_at'])); ?></td></tr>
          <?php endif; ?>
          <tr><td class="text-muted">Status</td><td><span class="badge bg-success">✅ Confirmed</span></td></tr>
        </table>
      </div>
      <?php endif; ?>

      <div class="mt-4 d-flex gap-2 justify-content-center flex-wrap">
        <a href="course_detail.php?id=<?php echo $course_id; ?>" class="btn btn-success">▶ Start Learning</a>
        <a href="my_courses.php" class="btn btn-primary">📚 My Courses</a>
        <a href="my_payments.php" class="btn btn-outline-secondary">💳 Payment History</a>
      </div>
    </div>
  </div>
</div>

<?php include("includes/footer.php"); ?>
