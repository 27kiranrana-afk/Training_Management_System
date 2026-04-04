<?php
session_start();
include("config/db.php");

if(!isset($_SESSION['user_id'])){ header("Location: login.php"); exit(); }

$user_id   = $_SESSION['user_id'];
$course_id = intval($_GET['course_id'] ?? 0);

// Verify the student has completed this course
$stmt = $conn->prepare("
    SELECT enrollments.progress, users.name, courses.title, courses.duration
    FROM enrollments
    JOIN users ON enrollments.user_id = users.id
    JOIN courses ON enrollments.course_id = courses.id
    WHERE enrollments.user_id = ? AND enrollments.course_id = ?
");
$stmt->bind_param("ii", $user_id, $course_id);
$stmt->execute();
$result = $stmt->get_result();
$data = $result->fetch_assoc();

if(!$data || $data['progress'] < 100){
    header("Location: my_courses.php"); exit();
}
?>
<?php include("includes/header.php"); ?>

<div class="text-center mt-4 p-5 border border-3 border-dark rounded" style="max-width:700px;margin:auto;background:#fffdf0;">
  <h2 class="mb-1">🎓 Certificate of Completion</h2>
  <p class="text-muted">Training Management System</p>
  <hr>
  <p class="fs-5 mt-3">This is to certify that</p>
  <h3 class="fw-bold"><?php echo htmlspecialchars($data['name']); ?></h3>
  <p class="fs-5">has successfully completed the course</p>
  <h4 class="text-primary"><?php echo htmlspecialchars($data['title']); ?></h4>
  <p>Duration: <?php echo htmlspecialchars($data['duration']); ?></p>
  <p class="text-muted mt-4">Date: <?php echo date("d M Y"); ?></p>
  <hr>
  <p class="text-muted small">Issued by Training Management System</p>
</div>

<div class="text-center mt-3">
  <button onclick="window.print()" class="btn btn-success">Print / Save as PDF</button>
  <a href="my_courses.php" class="btn btn-secondary ms-2">Back</a>
</div>

<?php include("includes/footer.php"); ?>
