<?php
session_start();
include("config/db.php");
include("includes/auth.php");
require_login();

// Auto-create tables
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
    processed_at    TIMESTAMP NULL DEFAULT NULL
)");

$conn->query("CREATE TABLE IF NOT EXISTS messages (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    user_id     INT NOT NULL,
    title       VARCHAR(200) NOT NULL,
    body        TEXT NOT NULL,
    is_read     TINYINT(1) DEFAULT 0,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

$user_id = $_SESSION['user_id'];

$stmt = $conn->prepare("
    SELECT courses.id, courses.title, courses.duration, courses.fees,
           enrollments.id AS enroll_id, enrollments.progress, enrollments.enrolled_at,
           users.name AS trainer_name
    FROM enrollments
    JOIN courses ON enrollments.course_id = courses.id
    LEFT JOIN users ON courses.created_by = users.id
    WHERE enrollments.user_id = ?
    ORDER BY enrollments.id DESC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

// Get already-requested refund course IDs
$refund_requested = [];
$rr = $conn->prepare("SELECT course_id FROM refund_requests WHERE user_id=?");
$rr->bind_param("i", $user_id);
$rr->execute();
$rr_result = $rr->get_result();
while($rrow = $rr_result->fetch_assoc()) $refund_requested[] = $rrow['course_id'];
?>
<?php include("includes/header.php"); ?>

<h3>My Courses</h3>

<?php if(isset($_GET['cancelled'])): ?>
  <div class="alert alert-success alert-dismissible fade show">
    Enrollment cancelled successfully. <?php if(isset($_GET['refund'])): ?>Refund request submitted — check your <a href="messages.php">messages</a> for updates.<?php endif; ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
<?php endif; ?>
<?php if(isset($_GET['error'])): ?>
  <?php
    $errors = [
      'cancel_expired'   => 'Cancellation window has expired. You can only cancel within 24 hours of enrollment.',
      'already_requested'=> 'You have already submitted a cancellation request for this course.',
      'not_enrolled'     => 'Enrollment not found.',
    ];
    echo '<div class="alert alert-danger alert-dismissible fade show">' . ($errors[$_GET['error']] ?? 'An error occurred.') . '<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
  ?>
<?php endif; ?>

<div class="table-responsive">
<table class="table table-bordered mt-3">
  <thead class="table-dark">
    <tr><th>Course</th><th>Trainer</th><th>Progress</th><th>Certificate</th><th>Action</th></tr>
  </thead>
  <tbody>
  <?php if($result->num_rows > 0): ?>
    <?php while($row = $result->fetch_assoc()):
      $hours_elapsed  = (time() - strtotime($row['enrolled_at'])) / 3600;
      $can_cancel     = ($hours_elapsed <= 24 && $row['progress'] == 0);
      $already_req    = in_array($row['id'], $refund_requested);
    ?>
    <tr>
      <td>
        <a href="course_detail.php?id=<?php echo $row['id']; ?>" class="fw-bold text-decoration-none">
          <?php echo htmlspecialchars($row['title']); ?>
        </a>
        <br>
        <small class="text-muted">
          <?php echo $row['fees'] > 0 ? '💳 Paid — ₹'.number_format($row['fees'],2) : '🆓 Free'; ?>
          · Enrolled: <?php echo date('d M Y', strtotime($row['enrolled_at'])); ?>
        </small>
      </td>
      <td><?php echo htmlspecialchars($row['trainer_name'] ?? '—'); ?></td>
      <td>
        <div class="progress" style="height:20px">
          <div class="progress-bar <?php echo $row['progress']==100 ? 'bg-success' : ($row['progress']>0 ? 'bg-info' : ''); ?>"
               style="width:<?php echo $row['progress']; ?>%">
            <?php echo $row['progress']; ?>%
          </div>
        </div>
      </td>
      <td>
        <?php if($row['progress'] >= 100): ?>
          <a href="certificate.php?course_id=<?php echo $row['id']; ?>" class="btn btn-sm btn-success">🎓 Download</a>
        <?php else: ?>
          <a href="course_detail.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-primary">Continue →</a>
        <?php endif; ?>
      </td>
      <td>
        <?php if($already_req): ?>
          <span class="badge bg-warning text-dark">Cancellation Requested</span>
        <?php elseif($can_cancel): ?>
          <button class="btn btn-sm btn-outline-danger"
                  data-bs-toggle="modal"
                  data-bs-target="#cancelModal<?php echo $row['id']; ?>">
            Cancel Enrollment
          </button>

          <!-- Cancel Modal -->
          <div class="modal fade" id="cancelModal<?php echo $row['id']; ?>" tabindex="-1">
            <div class="modal-dialog">
              <div class="modal-content">
                <form method="POST" action="cancel_enrollment.php">
                  <?php include("includes/csrf.php"); csrf_field(); ?>
                  <input type="hidden" name="course_id" value="<?php echo $row['id']; ?>">
                  <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title">Cancel Enrollment</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                  </div>
                  <div class="modal-body">
                    <p>Are you sure you want to cancel your enrollment in <strong><?php echo htmlspecialchars($row['title']); ?></strong>?</p>
                    <?php if($row['fees'] > 0): ?>
                      <div class="alert alert-info py-2">
                        💰 A refund of <strong>₹<?php echo number_format($row['fees'],2); ?></strong> will be processed within 5-7 business days.
                      </div>
                    <?php endif; ?>
                    <div class="mb-3">
                      <label>Reason for cancellation (optional)</label>
                      <textarea name="reason" class="form-control" rows="3"
                                placeholder="e.g. Enrolled by mistake, found a better course..."></textarea>
                    </div>
                    <small class="text-muted">⚠️ This action cannot be undone. You can only cancel within 24 hours of enrollment.</small>
                  </div>
                  <div class="modal-footer">
                    <button type="submit" class="btn btn-danger">Yes, Cancel Enrollment</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Keep Enrollment</button>
                  </div>
                </form>
              </div>
            </div>
          </div>
        <?php elseif($hours_elapsed <= 24 && $row['progress'] > 0): ?>
          <small class="text-muted">Cannot cancel<br>(progress started)</small>
        <?php else: ?>
          <small class="text-muted">—</small>
        <?php endif; ?>
      </td>
    </tr>
    <?php endwhile; ?>
  <?php else: ?>
    <tr><td colspan="5" class="text-center">No courses enrolled yet. <a href="view_courses.php">Browse courses</a></td></tr>
  <?php endif; ?>
  </tbody>
</table>
</div>
<a href="view_courses.php" class="btn btn-primary">Browse Courses</a>
<a href="dashboard.php" class="btn btn-secondary ms-2">Back</a>

<?php include("includes/footer.php"); ?>
