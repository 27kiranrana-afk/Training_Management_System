<?php
session_start();
include("../config/db.php");
include("../includes/auth.php");
require_role('student');

$uid = $_SESSION['user_id'];

$enrolled    = $conn->query("SELECT COUNT(*) FROM enrollments WHERE user_id=$uid")->fetch_row()[0];
$completed   = $conn->query("SELECT COUNT(*) FROM enrollments WHERE user_id=$uid AND progress=100")->fetch_row()[0];
$in_progress = $conn->query("SELECT COUNT(*) FROM enrollments WHERE user_id=$uid AND progress > 0 AND progress < 100")->fetch_row()[0];

// My enrolled courses
$courses = $conn->prepare("
    SELECT courses.id, courses.title, courses.duration, enrollments.progress,
           users.name AS trainer_name
    FROM enrollments
    JOIN courses ON enrollments.course_id = courses.id
    LEFT JOIN users ON courses.created_by = users.id
    WHERE enrollments.user_id = ?
    ORDER BY enrollments.id DESC
");
$courses->bind_param("i", $uid);
$courses->execute();
$my_courses = $courses->get_result();
?>
<?php include("../includes/header.php"); ?>

<div class="mb-2">
  <h3 class="mb-0">🎓 Student Dashboard</h3>
  <p class="text-muted">Welcome, <?php echo htmlspecialchars($_SESSION['user']); ?></p>
</div>
<hr>

<!-- Stats -->
<div class="row g-3 mb-4">
  <div class="col-md-3">
    <a href="../view_courses.php" class="text-decoration-none">
      <div class="card text-white bg-primary text-center p-3">
        <div class="fs-2 fw-bold"><?php echo $enrolled; ?></div>
        <div>Enrolled</div>
      </div>
    </a>
  </div>
  <div class="col-md-3">
    <a href="../my_courses.php" class="text-decoration-none">
      <div class="card text-white bg-warning text-center p-3">
        <div class="fs-2 fw-bold"><?php echo $in_progress; ?></div>
        <div>In Progress</div>
      </div>
    </a>
  </div>
  <div class="col-md-3">
    <a href="../my_courses.php" class="text-decoration-none">
      <div class="card text-white bg-success text-center p-3">
        <div class="fs-2 fw-bold"><?php echo $completed; ?></div>
        <div>Completed</div>
      </div>
    </a>
  </div>
</div>

<!-- Quick Actions -->
<h5 class="mb-3">Quick Actions</h5>
<div class="row g-3 mb-4">
  <div class="col-md-3"><a href="../view_courses.php" class="btn btn-primary w-100">📚 Browse Courses</a></div>
  <div class="col-md-3"><a href="../my_courses.php" class="btn btn-success w-100">🎓 My Courses</a></div>
  <div class="col-md-3"><a href="../course_progress.php" class="btn btn-info w-100">📈 My Progress</a></div>
  <div class="col-md-3"><a href="../inquiries.php" class="btn btn-warning w-100">💬 Submit Inquiry</a></div>
</div>

<!-- My Courses Table -->
<h5>My Courses</h5>
<div class="table-responsive">
<table class="table table-bordered">
  <thead class="table-dark">
    <tr><th>Course</th><th>Trainer</th><th>Progress</th><th>Certificate</th></tr>
  </thead>
  <tbody>
  <?php if($my_courses->num_rows > 0): ?>
    <?php while($row = $my_courses->fetch_assoc()): ?>
    <tr>
      <td>
        <a href="../course_detail.php?id=<?php echo $row['id']; ?>" class="fw-bold text-decoration-none">
          <?php echo htmlspecialchars($row['title']); ?>
        </a>
        <br><small class="text-muted">Click to update progress</small>
      </td>
      <td><?php echo htmlspecialchars($row['trainer_name'] ?? '—'); ?></td>
      <td>
        <div class="progress" style="height:18px">
          <div class="progress-bar <?php echo $row['progress']==100?'bg-success':($row['progress']>0?'bg-info':''); ?>"
               style="width:<?php echo $row['progress']; ?>%">
            <?php echo $row['progress']; ?>%
          </div>
        </div>
      </td>
      <td>
        <?php if($row['progress'] >= 100): ?>
          <a href="../certificate.php?course_id=<?php echo $row['id']; ?>" class="btn btn-sm btn-success">🎓 Download</a>
        <?php else: ?>
          <a href="../course_detail.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-primary">Continue →</a>
        <?php endif; ?>
      </td>
    </tr>
    <?php endwhile; ?>
  <?php else: ?>
    <tr>
      <td colspan="4" class="text-center">
        You haven't enrolled in any courses yet.
        <a href="../view_courses.php">Browse courses</a>
      </td>
    </tr>
  <?php endif; ?>
  </tbody>
</table>
</div>

<?php include("../includes/footer.php"); ?>
