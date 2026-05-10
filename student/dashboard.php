<?php
session_start();
include("../config/db.php");
include("../includes/auth.php");
require_role('student');

$uid = $_SESSION['user_id'];

$enrolled_stmt = $conn->prepare("SELECT COUNT(*) FROM enrollments WHERE user_id=?");
$enrolled_stmt->bind_param("i", $uid); $enrolled_stmt->execute();
$enrolled = $enrolled_stmt->get_result()->fetch_row()[0];

$completed_stmt = $conn->prepare("SELECT COUNT(*) FROM enrollments WHERE user_id=? AND progress=100");
$completed_stmt->bind_param("i", $uid); $completed_stmt->execute();
$completed = $completed_stmt->get_result()->fetch_row()[0];

$inprog_stmt = $conn->prepare("SELECT COUNT(*) FROM enrollments WHERE user_id=? AND progress > 0 AND progress < 100");
$inprog_stmt->bind_param("i", $uid); $inprog_stmt->execute();
$in_progress = $inprog_stmt->get_result()->fetch_row()[0];

$cert_stmt = $conn->prepare("SELECT COUNT(*) FROM certificates WHERE user_id=?");
$cert_stmt->bind_param("i", $uid); $cert_stmt->execute();
$certificates = $cert_stmt->get_result()->fetch_row()[0] ?? 0;

$pinq_stmt = $conn->prepare("SELECT COUNT(*) FROM inquiries WHERE user_id=? AND status='pending'");
$pinq_stmt->bind_param("i", $uid); $pinq_stmt->execute();
$pending_inq = $pinq_stmt->get_result()->fetch_row()[0];

// Unread messages
$unread_msgs = 0;
$mCheck = $conn->query("SHOW TABLES LIKE 'messages'");
if($mCheck && $mCheck->num_rows > 0){
    $unread_stmt = $conn->prepare("SELECT COUNT(*) FROM messages WHERE user_id=? AND is_read=0");
    $unread_stmt->bind_param("i", $uid);
    $unread_stmt->execute();
    $unread_msgs = $unread_stmt->get_result()->fetch_row()[0];
}

// My enrolled courses
$courses = $conn->prepare("
    SELECT courses.id, courses.title, courses.duration, courses.fees,
           enrollments.progress, enrollments.enrolled_at,
           users.name AS trainer_name
    FROM enrollments
    JOIN courses ON enrollments.course_id = courses.id
    LEFT JOIN users ON courses.created_by = users.id
    WHERE enrollments.user_id = ?
    ORDER BY enrollments.progress ASC, enrollments.id DESC
    LIMIT 5
");
$courses->bind_param("i", $uid);
$courses->execute();
$my_courses = $courses->get_result();
?>
<?php include("../includes/header.php"); ?>

<div class="d-flex justify-content-between align-items-center mb-2">
  <div>
    <h3 class="mb-0">🎓 Student Dashboard</h3>
    <p class="text-muted mb-0">Welcome back, <strong><?php echo htmlspecialchars($_SESSION['user']); ?></strong></p>
  </div>
  <small class="text-muted"><?php echo date('d M Y, l'); ?></small>
</div>
<hr>

<!-- Stats -->
<div class="row g-3 mb-4">
  <div class="col-6 col-md-3">
    <a href="../my_courses.php" class="text-decoration-none">
      <div class="card text-white bg-primary text-center p-3 h-100">
        <div class="fs-2 fw-bold"><?php echo $enrolled; ?></div>
        <div>Enrolled</div>
      </div>
    </a>
  </div>
  <div class="col-6 col-md-3">
    <a href="../my_courses.php" class="text-decoration-none">
      <div class="card text-white bg-warning text-center p-3 h-100">
        <div class="fs-2 fw-bold"><?php echo $in_progress; ?></div>
        <div>In Progress</div>
      </div>
    </a>
  </div>
  <div class="col-6 col-md-3">
    <a href="../my_courses.php" class="text-decoration-none">
      <div class="card text-white bg-success text-center p-3 h-100">
        <div class="fs-2 fw-bold"><?php echo $completed; ?></div>
        <div>Completed</div>
      </div>
    </a>
  </div>
  <div class="col-6 col-md-3">
    <a href="../messages.php" class="text-decoration-none">
      <div class="card text-white bg-dark text-center p-3 h-100 position-relative">
        <div class="fs-2 fw-bold"><?php echo $unread_msgs; ?></div>
        <div>New Messages</div>
        <?php if($unread_msgs > 0): ?>
          <span class="position-absolute top-0 end-0 badge bg-danger m-1"><?php echo $unread_msgs; ?></span>
        <?php endif; ?>
      </div>
    </a>
  </div>
</div>

<!-- Quick Actions -->
<h5 class="mb-3">Quick Actions</h5>
<div class="row g-2 mb-4">
  <div class="col-6 col-md-3"><a href="../view_courses.php" class="btn btn-primary w-100">📚 Browse Courses</a></div>
  <div class="col-6 col-md-3"><a href="../my_courses.php" class="btn btn-success w-100">🎓 My Courses</a></div>
  <div class="col-6 col-md-3"><a href="../inquiries.php" class="btn btn-warning w-100">💬 Inquiries</a></div>
  <div class="col-6 col-md-3"><a href="../my_payments.php" class="btn btn-info w-100">💳 My Payments</a></div>
</div>

<!-- Alerts -->
<?php if($pending_inq > 0): ?>
  <div class="alert alert-warning py-2">
    📋 You have <strong><?php echo $pending_inq; ?></strong> pending inquiry(ies). <a href="../inquiries.php">View</a>
  </div>
<?php endif; ?>

<!-- My Courses Table -->
<div class="d-flex justify-content-between align-items-center mb-2">
  <h5 class="mb-0">My Courses</h5>
  <a href="../my_courses.php" class="btn btn-sm btn-outline-primary">View All →</a>
</div>
<div class="table-responsive">
<table class="table table-bordered table-hover">
  <thead class="table-dark">
    <tr><th>Course</th><th>Trainer</th><th>Type</th><th>Progress</th><th>Action</th></tr>
  </thead>
  <tbody>
  <?php if($my_courses->num_rows > 0): ?>
    <?php while($row = $my_courses->fetch_assoc()): ?>
    <tr>
      <td>
        <a href="../course_detail.php?id=<?php echo $row['id']; ?>" class="fw-bold text-decoration-none">
          <?php echo htmlspecialchars($row['title']); ?>
        </a>
        <br><small class="text-muted">Enrolled: <?php echo date('d M Y', strtotime($row['enrolled_at'])); ?></small>
      </td>
      <td><?php echo htmlspecialchars($row['trainer_name'] ?? '—'); ?></td>
      <td><?php echo $row['fees'] > 0 ? '<span class="badge bg-primary">Paid</span>' : '<span class="badge bg-success">Free</span>'; ?></td>
      <td>
        <div class="progress" style="height:18px">
          <div class="progress-bar <?php echo $row['progress']==100?'bg-success':($row['progress']>0?'bg-info':'bg-secondary'); ?>"
               style="width:<?php echo max($row['progress'],5); ?>%">
            <?php echo $row['progress']; ?>%
          </div>
        </div>
      </td>
      <td>
        <?php if($row['progress'] >= 100): ?>
          <a href="../certificate.php?course_id=<?php echo $row['id']; ?>" class="btn btn-sm btn-success">🎓 Certificate</a>
        <?php else: ?>
          <a href="../course_detail.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-primary">Continue →</a>
        <?php endif; ?>
      </td>
    </tr>
    <?php endwhile; ?>
  <?php else: ?>
    <tr>
      <td colspan="5" class="text-center py-3">
        You haven't enrolled in any courses yet.<br>
        <a href="../view_courses.php" class="btn btn-primary btn-sm mt-2">Browse Courses</a>
      </td>
    </tr>
  <?php endif; ?>
  </tbody>
</table>
</div>

<?php include("../includes/footer.php"); ?>
