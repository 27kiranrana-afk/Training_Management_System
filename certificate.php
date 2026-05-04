<?php
session_start();
include("config/db.php");
include("includes/auth.php");
require_login();

$user_id   = $_SESSION['user_id'];
$course_id = intval($_GET['course_id'] ?? 0);

if($course_id <= 0){ header("Location: my_courses.php"); exit(); }

// Verify the student has completed this course AND owns the enrollment
$stmt = $conn->prepare("
    SELECT enrollments.progress, users.name, courses.title, courses.duration, courses.id AS cid
    FROM enrollments
    JOIN users ON enrollments.user_id = users.id
    JOIN courses ON enrollments.course_id = courses.id
    WHERE enrollments.user_id = ? AND enrollments.course_id = ? AND enrollments.status = 'completed'
");
$stmt->bind_param("ii", $user_id, $course_id);
$stmt->execute();
$result = $stmt->get_result();
$data   = $result->fetch_assoc();

if(!$data || $data['progress'] < 100){
    header("Location: my_courses.php"); exit();
}

// Check if course has any YouTube/URL based materials → Self Paced
$mat_check = $conn->prepare("SELECT COUNT(*) FROM course_materials WHERE course_id=? AND type IN ('video_url','notes_url')");
$mat_check->bind_param("i", $course_id);
$mat_check->execute();
$url_count = $mat_check->get_result()->fetch_row()[0];
$display_duration = $url_count > 0 ? 'Self Paced' : $data['duration'];
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
  <p>Duration: <?php echo htmlspecialchars($display_duration); ?></p>
  <p class="text-muted mt-4">Date: <?php echo date("d M Y"); ?></p>
  <hr>
  <p class="text-muted small">Issued by Training Management System</p>
</div>

<div class="text-center mt-3">
  <button onclick="window.print()" class="btn btn-success">Print / Save as PDF</button>
  <a href="my_courses.php" class="btn btn-secondary ms-2">Back</a>
</div>

<?php include("includes/footer.php"); ?>
