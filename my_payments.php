<?php
session_start();
include("config/db.php");
include("includes/auth.php");
require_role('student');

$user_id = $_SESSION['user_id'];

// Fetch payment history
$stmt = $conn->prepare("
    SELECT p.*, c.title AS course_title, c.duration
    FROM payments p
    JOIN courses c ON p.course_id = c.id
    WHERE p.user_id = ?
    ORDER BY p.paid_at DESC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$payments = $stmt->get_result();

$total_spent = $conn->prepare("SELECT SUM(amount) FROM payments WHERE user_id=? AND status='success'");
$total_spent->bind_param("i", $user_id);
$total_spent->execute();
$total = $total_spent->get_result()->fetch_row()[0] ?? 0;
?>
<?php include("includes/header.php"); ?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <h3>💳 My Payments</h3>
  <a href="dashboard.php" class="btn btn-secondary btn-sm">← Back</a>
</div>

<div class="row g-3 mb-4">
  <div class="col-md-3">
    <div class="card bg-success text-white text-center p-3">
      <div class="fs-4 fw-bold">₹<?php echo number_format($total, 2); ?></div>
      <div>Total Spent</div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card bg-primary text-white text-center p-3">
      <div class="fs-4 fw-bold"><?php echo $payments->num_rows; ?></div>
      <div>Transactions</div>
    </div>
  </div>
</div>

<?php if($payments->num_rows > 0): ?>
<div class="table-responsive">
<table class="table table-bordered table-hover">
  <thead class="table-dark">
    <tr><th>#</th><th>Course</th><th>Amount</th><th>Payment ID</th><th>Date</th><th>Status</th></tr>
  </thead>
  <tbody>
  <?php $sno=1; $payments->data_seek(0); while($p = $payments->fetch_assoc()): ?>
  <tr>
    <td><?php echo $sno++; ?></td>
    <td>
      <a href="course_detail.php?id=<?php echo $p['course_id']; ?>" class="text-decoration-none fw-bold">
        <?php echo htmlspecialchars($p['course_title']); ?>
      </a>
    </td>
    <td class="fw-bold text-success">₹<?php echo number_format($p['amount'], 2); ?></td>
    <td><small class="text-muted"><?php echo htmlspecialchars($p['razorpay_payment_id'] ?? '—'); ?></small></td>
    <td><?php echo date('d M Y, h:i A', strtotime($p['paid_at'])); ?></td>
    <td><span class="badge bg-<?php echo $p['status']==='success'?'success':'danger'; ?>"><?php echo ucfirst($p['status']); ?></span></td>
  </tr>
  <?php endwhile; ?>
  </tbody>
</table>
</div>
<?php else: ?>
  <div class="alert alert-info">No payment records found. <a href="view_courses.php">Browse paid courses</a></div>
<?php endif; ?>

<?php include("includes/footer.php"); ?>
