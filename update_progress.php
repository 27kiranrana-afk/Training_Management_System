<?php
session_start();
include("config/db.php");
include("includes/auth.php");
require_role('admin', 'trainer');

// Trainers see only their courses; admins see all
if($_SESSION['role'] == 'trainer'){
    $uid = $_SESSION['user_id'];
    $stmt = $conn->prepare("
        SELECT enrollments.id, users.name AS student, courses.title AS course, enrollments.progress
        FROM enrollments
        JOIN users ON enrollments.user_id = users.id
        JOIN courses ON enrollments.course_id = courses.id
        WHERE courses.created_by = ?
        ORDER BY courses.title, users.name
    ");
    $stmt->bind_param("i", $uid);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $stmt = $conn->prepare("
        SELECT enrollments.id, users.name AS student, courses.title AS course, enrollments.progress
        FROM enrollments
        JOIN users ON enrollments.user_id = users.id
        JOIN courses ON enrollments.course_id = courses.id
        ORDER BY courses.title, users.name
    ");
    $stmt->execute();
    $result = $stmt->get_result();
}
?>
<?php include("includes/header.php"); ?>

<h3>Update Student Progress</h3>

<div class="table-responsive">
<table class="table table-bordered mt-3">
  <thead class="table-dark">
    <tr><th>Student</th><th>Course</th><th>Progress</th></tr>
  </thead>
  <tbody>
  <?php if($result && $result->num_rows > 0): ?>
    <?php while($row = $result->fetch_assoc()): ?>
    <tr>
      <td><?php echo htmlspecialchars($row['student']); ?></td>
      <td><?php echo htmlspecialchars($row['course']); ?></td>
      <td>
        <div class="progress" style="height:20px">
          <div class="progress-bar <?php echo $row['progress']==100 ? 'bg-success' : ''; ?>"
               style="width:<?php echo $row['progress']; ?>%">
            <?php echo $row['progress']; ?>%
          </div>
        </div>
      </td>
    </tr>
    <?php endwhile; ?>
  <?php else: ?>
    <tr><td colspan="3" class="text-center">No enrollments found.</td></tr>
  <?php endif; ?>
  </tbody>
</table>
</div>
<a href="dashboard.php" class="btn btn-secondary">Back</a>

<?php include("includes/footer.php"); ?>
