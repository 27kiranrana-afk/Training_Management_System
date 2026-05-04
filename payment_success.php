<?php
session_start();
include("config/db.php");
include("includes/auth.php");
require_login();

$course_id = intval($_GET['course_id'] ?? 0);
$course = null;
if ($course_id > 0) {
    $stmt = $conn->prepare("SELECT title FROM courses WHERE id=?");
    $stmt->bind_param("i", $course_id);
    $stmt->execute();
    $course = $stmt->get_result()->fetch_assoc();
}
?>
<?php include("includes/header.php"); ?>

<div class="row justify-content-center mt-5">
  <div class="col-md-6 text-center">
    <div class="card shadow p-5">
      <div class="display-1 mb-3">🎉</div>
      <h3 class="text-success">Payment Successful!</h3>
      <?php if ($course): ?>
        <p class="text-muted mt-2">You are now enrolled in <strong><?php echo htmlspecialchars($course['title']); ?></strong></p>
      <?php endif; ?>
      <div class="mt-4 d-flex gap-2 justify-content-center">
        <a href="my_courses.php" class="btn btn-primary">📚 Go to My Courses</a>
        <a href="view_courses.php" class="btn btn-outline-secondary">Browse More</a>
      </div>
    </div>
  </div>
</div>

<?php include("includes/footer.php"); ?>
