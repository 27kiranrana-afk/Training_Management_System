<?php
session_start();
include("../config/db.php");
include("../includes/auth.php");
require_role('trainer');

$uid = $_SESSION['user_id'];

$my_courses  = $conn->query("SELECT COUNT(*) FROM courses WHERE created_by=$uid")->fetch_row()[0];
$my_students = $conn->query("
    SELECT COUNT(DISTINCT enrollments.user_id)
    FROM enrollments
    JOIN courses ON enrollments.course_id = courses.id
    WHERE courses.created_by = $uid
")->fetch_row()[0];
$completions = $conn->query("
    SELECT COUNT(*)
    FROM enrollments
    JOIN courses ON enrollments.course_id = courses.id
    WHERE courses.created_by = $uid AND enrollments.progress = 100
")->fetch_row()[0];

// My courses with student counts
$courses = $conn->query("
    SELECT courses.id, courses.title, courses.duration,
           COUNT(enrollments.id) AS enrolled_count
    FROM courses
    LEFT JOIN enrollments ON enrollments.course_id = courses.id
    WHERE courses.created_by = $uid
    GROUP BY courses.id
    ORDER BY courses.id DESC
");

// Students in my courses with progress
$students = $conn->query("
    SELECT users.name AS student, courses.title AS course, enrollments.progress
    FROM enrollments
    JOIN users ON enrollments.user_id = users.id
    JOIN courses ON enrollments.course_id = courses.id
    WHERE courses.created_by = $uid
    ORDER BY courses.title, users.name
");
?>
<?php include("../includes/header.php"); ?>

<div class="mb-2">
  <h3 class="mb-0">👨‍🏫 Trainer Dashboard</h3>
  <p class="text-muted">Welcome, <?php echo htmlspecialchars($_SESSION['user']); ?></p>
</div>
<hr>

<!-- Stats -->
<div class="row g-3 mb-4">
  <div class="col-md-3">
    <a href="my_courses.php" class="text-decoration-none">
      <div class="card text-white bg-primary text-center p-3 h-100">
        <div class="fs-2 fw-bold"><?php echo $my_courses; ?></div>
        <div>My Courses</div>
      </div>
    </a>
  </div>
  <div class="col-md-3">
    <a href="../update_progress.php" class="text-decoration-none">
      <div class="card text-white bg-success text-center p-3 h-100">
        <div class="fs-2 fw-bold"><?php echo $my_students; ?></div>
        <div>My Students</div>
      </div>
    </a>
  </div>
  <div class="col-md-3">
    <a href="../update_progress.php" class="text-decoration-none">
      <div class="card text-white bg-dark text-center p-3 h-100">
        <div class="fs-2 fw-bold"><?php echo $completions; ?></div>
        <div>Completions</div>
      </div>
    </a>
  </div>
</div>

<!-- Quick Actions -->
<h5 class="mb-3">Quick Actions</h5>
<div class="row g-3 mb-4">
  <div class="col-md-3"><a href="../add_course.php" class="btn btn-primary w-100">➕ Add Course</a></div>
  <div class="col-md-3"><a href="../course_progress.php" class="btn btn-info w-100">📈 Progress</a></div>
  <div class="col-md-3"><a href="my_courses.php" class="btn btn-secondary w-100">📚 My Courses</a></div>
</div>

<div class="row g-4">
  <!-- My Courses -->
  <div class="col-md-5">
    <h5>My Courses</h5>
    <table class="table table-sm table-bordered">
      <thead class="table-dark">
        <tr><th>Title</th><th>Duration</th><th>Students</th></tr>
      </thead>
      <tbody>
      <?php if($courses->num_rows > 0): ?>
        <?php while($c = $courses->fetch_assoc()): ?>
        <tr>
          <td><?php echo htmlspecialchars($c['title']); ?></td>
          <td><?php echo htmlspecialchars($c['duration']); ?></td>
          <td><?php echo $c['enrolled_count']; ?></td>
        </tr>
        <?php endwhile; ?>
      <?php else: ?>
        <tr><td colspan="3" class="text-center">No courses yet. <a href="../add_course.php">Add one</a></td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>

  <!-- Student Progress -->
  <div class="col-md-7">
    <h5>Student Progress</h5>
    <table class="table table-sm table-bordered">
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
              <div class="progress-bar <?php echo $s['progress']==100?'bg-success':''; ?>"
                   style="width:<?php echo $s['progress']; ?>%">
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

<?php include("../includes/footer.php"); ?>
