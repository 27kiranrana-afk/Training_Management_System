<?php
session_start();
include("../config/db.php");
include("../includes/auth.php");
require_role('trainer');

$uid = $_SESSION['user_id'];

$my_courses_count = $conn->prepare("SELECT COUNT(*) FROM courses WHERE created_by=?");
$my_courses_count->bind_param("i", $uid);
$my_courses_count->execute();
$my_courses_count = $my_courses_count->get_result()->fetch_row()[0];

$my_students_stmt = $conn->prepare("SELECT COUNT(DISTINCT enrollments.user_id) FROM enrollments JOIN courses ON enrollments.course_id = courses.id WHERE courses.created_by = ?");
$my_students_stmt->bind_param("i", $uid);
$my_students_stmt->execute();
$my_students = $my_students_stmt->get_result()->fetch_row()[0];

$completions_stmt = $conn->prepare("SELECT COUNT(*) FROM enrollments JOIN courses ON enrollments.course_id = courses.id WHERE courses.created_by = ? AND enrollments.progress = 100");
$completions_stmt->bind_param("i", $uid);
$completions_stmt->execute();
$completions = $completions_stmt->get_result()->fetch_row()[0];

// Unread messages
$unread_msgs = 0;
$mCheck = $conn->query("SHOW TABLES LIKE 'messages'");
if($mCheck && $mCheck->num_rows > 0){
    $unread_stmt = $conn->prepare("SELECT COUNT(*) FROM messages WHERE user_id=? AND is_read=0");
    $unread_stmt->bind_param("i", $uid);
    $unread_stmt->execute();
    $unread_msgs = $unread_stmt->get_result()->fetch_row()[0];
}

// My courses with student counts and fees
$courses = $conn->prepare("
    SELECT courses.id, courses.title, courses.duration, courses.fees,
           COUNT(enrollments.id) AS enrolled_count,
           SUM(CASE WHEN enrollments.progress=100 THEN 1 ELSE 0 END) AS completed_count
    FROM courses
    LEFT JOIN enrollments ON enrollments.course_id = courses.id
    WHERE courses.created_by = ?
    GROUP BY courses.id
    ORDER BY courses.title ASC
");
$courses->bind_param("i", $uid);
$courses->execute();
$courses = $courses->get_result();

// Students in my courses with progress
$students = $conn->prepare("
    SELECT users.name AS student, courses.title AS course,
           enrollments.progress, enrollments.enrolled_at
    FROM enrollments
    JOIN users ON enrollments.user_id = users.id
    JOIN courses ON enrollments.course_id = courses.id
    WHERE courses.created_by = ?
    ORDER BY enrollments.progress ASC, courses.title ASC
    LIMIT 10
");
$students->bind_param("i", $uid);
$students->execute();
$students = $students->get_result();
?>
<?php include("../includes/header.php"); ?>

<div class="d-flex justify-content-between align-items-center mb-2">
  <div>
    <h3 class="mb-0">👨‍🏫 Trainer Dashboard</h3>
    <p class="text-muted mb-0">Welcome back, <strong><?php echo htmlspecialchars($_SESSION['user']); ?></strong></p>
  </div>
  <small class="text-muted"><?php echo date('d M Y, l'); ?></small>
</div>
<hr>

<!-- Stats -->
<div class="row g-3 mb-4">
  <div class="col-6 col-md-3">
    <a href="my_courses.php" class="text-decoration-none">
      <div class="card text-white bg-primary text-center p-3 h-100">
        <div class="fs-2 fw-bold"><?php echo $my_courses_count; ?></div>
        <div>My Courses</div>
      </div>
    </a>
  </div>
  <div class="col-6 col-md-3">
    <a href="../update_progress.php" class="text-decoration-none">
      <div class="card text-white bg-success text-center p-3 h-100">
        <div class="fs-2 fw-bold"><?php echo $my_students; ?></div>
        <div>My Students</div>
      </div>
    </a>
  </div>
  <div class="col-6 col-md-3">
    <a href="../course_progress.php" class="text-decoration-none">
      <div class="card text-white bg-dark text-center p-3 h-100">
        <div class="fs-2 fw-bold"><?php echo $completions; ?></div>
        <div>Completions</div>
      </div>
    </a>
  </div>
  <div class="col-6 col-md-3">
    <a href="../messages.php" class="text-decoration-none">
      <div class="card text-white bg-info text-center p-3 h-100 position-relative">
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
  <div class="col-6 col-md-3"><a href="../add_course.php" class="btn btn-primary w-100">➕ Add Course</a></div>
  <div class="col-6 col-md-3"><a href="../view_courses.php" class="btn btn-secondary w-100">📚 All Courses</a></div>
  <div class="col-6 col-md-3"><a href="../course_progress.php" class="btn btn-info w-100">📈 Progress</a></div>
  <div class="col-6 col-md-3"><a href="../inquiries.php" class="btn btn-warning w-100">💬 Inquiry</a></div>
</div>

<div class="row g-4">
  <!-- My Courses -->
  <div class="col-md-5">
    <div class="d-flex justify-content-between align-items-center mb-2">
      <h5 class="mb-0">My Courses</h5>
      <a href="../add_course.php" class="btn btn-sm btn-outline-primary">+ Add</a>
    </div>
    <div class="table-responsive">
    <table class="table table-sm table-bordered table-hover">
      <thead class="table-dark">
        <tr><th>Title</th><th>Type</th><th>Students</th><th>Done</th></tr>
      </thead>
      <tbody>
      <?php if($courses->num_rows > 0): ?>
        <?php while($c = $courses->fetch_assoc()): ?>
        <tr>
          <td>
            <a href="../course_detail.php?id=<?php echo $c['id']; ?>" class="text-decoration-none">
              <?php echo htmlspecialchars($c['title']); ?>
            </a>
          </td>
          <td><?php echo $c['fees'] > 0 ? '<span class="badge bg-primary">Paid</span>' : '<span class="badge bg-success">Free</span>'; ?></td>
          <td><?php echo $c['enrolled_count']; ?></td>
          <td><?php echo $c['completed_count'] ?? 0; ?></td>
        </tr>
        <?php endwhile; ?>
      <?php else: ?>
        <tr><td colspan="4" class="text-center">No courses yet. <a href="../add_course.php">Add one</a></td></tr>
      <?php endif; ?>
      </tbody>
    </table>
    </div>
  </div>

  <!-- Student Progress -->
  <div class="col-md-7">
    <div class="d-flex justify-content-between align-items-center mb-2">
      <h5 class="mb-0">Student Progress</h5>
      <a href="../course_progress.php" class="btn btn-sm btn-outline-info">View All →</a>
    </div>
    <div class="table-responsive">
    <table class="table table-sm table-bordered table-hover">
      <thead class="table-dark">
        <tr><th>Student</th><th>Course</th><th>Progress</th></tr>
      </thead>
      <tbody>
      <?php if($students->num_rows > 0): ?>
        <?php while($s = $students->fetch_assoc()): ?>
        <tr>
          <td><?php echo htmlspecialchars($s['student']); ?></td>
          <td><?php echo htmlspecialchars($s['course']); ?></td>
          <td>
            <div class="progress" style="height:16px">
              <div class="progress-bar <?php echo $s['progress']==100?'bg-success':($s['progress']>0?'bg-info':'bg-secondary'); ?>"
                   style="width:<?php echo max($s['progress'],3); ?>%">
                <?php echo $s['progress']; ?>%
              </div>
            </div>
          </td>
        </tr>
        <?php endwhile; ?>
      <?php else: ?>
        <tr><td colspan="3" class="text-center">No students enrolled yet.</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
    </div>
  </div>
</div>

<?php include("../includes/footer.php"); ?>
