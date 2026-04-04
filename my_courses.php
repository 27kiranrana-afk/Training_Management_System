<?php
session_start();
include("config/db.php");
include("includes/auth.php");
require_login();

$user_id = $_SESSION['user_id'];

$stmt = $conn->prepare("
    SELECT courses.id, courses.title, courses.duration, enrollments.progress
    FROM enrollments
    JOIN courses ON enrollments.course_id = courses.id
    WHERE enrollments.user_id = ?
    ORDER BY enrollments.id DESC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
?>
<?php include("includes/header.php"); ?>

<h3>My Courses</h3>
<div class="table-responsive">
<table class="table table-bordered mt-3">
  <thead class="table-dark">
    <tr><th>Course</th><th>Duration</th><th>Progress</th><th>Certificate</th></tr>
  </thead>
  <tbody>
  <?php if($result->num_rows > 0): ?>
    <?php while($row = $result->fetch_assoc()): ?>
    <tr>
      <td><?php echo htmlspecialchars($row['title']); ?></td>
      <td><?php echo htmlspecialchars($row['duration']); ?></td>
      <td>
        <div class="progress" style="height:20px">
          <div class="progress-bar <?php echo $row['progress']==100 ? 'bg-success' : ''; ?>"
               style="width:<?php echo $row['progress']; ?>%">
            <?php echo $row['progress']; ?>%
          </div>
        </div>
      </td>
      <td>
        <?php if($row['progress'] >= 100): ?>
          <a href="certificate.php?course_id=<?php echo $row['id']; ?>" class="btn btn-sm btn-success">🎓 Download</a>
        <?php else: ?>
          <span class="text-muted small">Complete course to unlock</span>
        <?php endif; ?>
      </td>
    </tr>
    <?php endwhile; ?>
  <?php else: ?>
    <tr><td colspan="4" class="text-center">No courses enrolled yet. <a href="view_courses.php">Browse courses</a></td></tr>
  <?php endif; ?>
  </tbody>
</table>
</div>
<a href="view_courses.php" class="btn btn-primary">Browse Courses</a>
<a href="dashboard.php" class="btn btn-secondary ms-2">Back</a>

<?php include("includes/footer.php"); ?>
