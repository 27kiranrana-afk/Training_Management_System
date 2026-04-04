<?php
session_start();
include("config/db.php");
include("includes/auth.php");
require_login();

$role = $_SESSION['role'];

// Stats for admin
if($role == 'admin'){
    $total_students  = $conn->query("SELECT COUNT(*) FROM users WHERE role='student'")->fetch_row()[0];
    $total_trainers  = $conn->query("SELECT COUNT(*) FROM users WHERE role='trainer'")->fetch_row()[0];
    $total_courses   = $conn->query("SELECT COUNT(*) FROM courses")->fetch_row()[0];
    $total_enrollments = $conn->query("SELECT COUNT(*) FROM enrollments")->fetch_row()[0];
    $pending_inquiries = $conn->query("SELECT COUNT(*) FROM inquiries WHERE status='pending'")->fetch_row()[0];
    $completed        = $conn->query("SELECT COUNT(*) FROM enrollments WHERE progress=100")->fetch_row()[0];
}

// Stats for trainer
if($role == 'trainer'){
    $my_courses   = $conn->query("SELECT COUNT(*) FROM courses WHERE created_by=" . $_SESSION['user_id'])->fetch_row()[0];
    $my_students  = $conn->query("SELECT COUNT(DISTINCT enrollments.user_id) FROM enrollments JOIN courses ON enrollments.course_id=courses.id WHERE courses.created_by=" . $_SESSION['user_id'])->fetch_row()[0];
}

// Stats for student
if($role == 'student'){
    $uid = $_SESSION['user_id'];
    $enrolled = $conn->query("SELECT COUNT(*) FROM enrollments WHERE user_id=$uid")->fetch_row()[0];
    $completed_s = $conn->query("SELECT COUNT(*) FROM enrollments WHERE user_id=$uid AND progress=100")->fetch_row()[0];
}
?>
<?php include("includes/header.php"); ?>

<h3>Welcome, <?php echo htmlspecialchars($_SESSION['user']); ?></h3>
<p class="text-muted mb-4">Role: <?php echo ucfirst($role); ?></p>

<?php if($role == 'admin'): ?>

  <!-- Admin Stats Cards -->
  <div class="row g-3 mb-4">
    <div class="col-md-2">
      <div class="card text-white bg-primary text-center p-3">
        <div class="fs-2 fw-bold"><?php echo $total_students; ?></div>
        <div>Students</div>
      </div>
    </div>
    <div class="col-md-2">
      <div class="card text-white bg-success text-center p-3">
        <div class="fs-2 fw-bold"><?php echo $total_trainers; ?></div>
        <div>Trainers</div>
      </div>
    </div>
    <div class="col-md-2">
      <div class="card text-white bg-info text-center p-3">
        <div class="fs-2 fw-bold"><?php echo $total_courses; ?></div>
        <div>Courses</div>
      </div>
    </div>
    <div class="col-md-2">
      <div class="card text-white bg-secondary text-center p-3">
        <div class="fs-2 fw-bold"><?php echo $total_enrollments; ?></div>
        <div>Enrollments</div>
      </div>
    </div>
    <div class="col-md-2">
      <div class="card text-white bg-warning text-center p-3">
        <div class="fs-2 fw-bold"><?php echo $pending_inquiries; ?></div>
        <div>Inquiries</div>
      </div>
    </div>
    <div class="col-md-2">
      <div class="card text-white bg-dark text-center p-3">
        <div class="fs-2 fw-bold"><?php echo $completed; ?></div>
        <div>Completed</div>
      </div>
    </div>
  </div>

  <!-- Admin Actions -->
  <h5 class="mb-3">Quick Actions</h5>
  <div class="row g-3">
    <div class="col-md-3"><a href="add_course.php" class="btn btn-primary w-100">➕ Add Course</a></div>
    <div class="col-md-3"><a href="view_courses.php" class="btn btn-secondary w-100">📚 View Courses</a></div>
    <div class="col-md-3"><a href="manage_inquiries.php" class="btn btn-warning w-100">💬 Manage Inquiries</a></div>
    <div class="col-md-3"><a href="update_progress.php" class="btn btn-info w-100">📈 Update Progress</a></div>
  </div>

<?php elseif($role == 'trainer'): ?>

  <!-- Trainer Stats -->
  <div class="row g-3 mb-4">
    <div class="col-md-3">
      <div class="card text-white bg-primary text-center p-3">
        <div class="fs-2 fw-bold"><?php echo $my_courses; ?></div>
        <div>My Courses</div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card text-white bg-success text-center p-3">
        <div class="fs-2 fw-bold"><?php echo $my_students; ?></div>
        <div>My Students</div>
      </div>
    </div>
  </div>

  <h5 class="mb-3">Quick Actions</h5>
  <div class="row g-3">
    <div class="col-md-3"><a href="add_course.php" class="btn btn-primary w-100">➕ Add Course</a></div>
    <div class="col-md-3"><a href="update_progress.php" class="btn btn-info w-100">📈 Update Progress</a></div>
    <div class="col-md-3"><a href="view_courses.php" class="btn btn-secondary w-100">📚 View Courses</a></div>
  </div>

<?php else: ?>

  <!-- Student Stats -->
  <div class="row g-3 mb-4">
    <div class="col-md-3">
      <div class="card text-white bg-primary text-center p-3">
        <div class="fs-2 fw-bold"><?php echo $enrolled; ?></div>
        <div>Enrolled</div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card text-white bg-success text-center p-3">
        <div class="fs-2 fw-bold"><?php echo $completed_s; ?></div>
        <div>Completed</div>
      </div>
    </div>
  </div>

  <h5 class="mb-3">Quick Actions</h5>
  <div class="row g-3">
    <div class="col-md-3"><a href="view_courses.php" class="btn btn-primary w-100">📚 Browse Courses</a></div>
    <div class="col-md-3"><a href="my_courses.php" class="btn btn-success w-100">🎓 My Courses</a></div>
    <div class="col-md-3"><a href="inquiries.php" class="btn btn-warning w-100">💬 Submit Inquiry</a></div>
  </div>

<?php endif; ?>

<?php include("includes/footer.php"); ?>
