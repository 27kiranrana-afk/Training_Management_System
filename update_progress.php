<?php
session_start();
include("config/db.php");
include("includes/auth.php");
include("includes/csrf.php");
require_role('admin', 'trainer');

$success = $error = "";

if(isset($_POST['update'])){
    csrf_verify();
    $enroll_id = intval($_POST['enroll_id']);
    $progress  = max(0, min(100, intval($_POST['progress'])));

    $stmt = $conn->prepare("UPDATE enrollments SET progress = ? WHERE id = ?");
    $stmt->bind_param("ii", $progress, $enroll_id);
    $success = $stmt->execute() ? "Progress updated!" : "Update failed.";
}

// Trainers see only their courses; admins see all
if($_SESSION['role'] == 'trainer'){
    $uid = $_SESSION['user_id'];
    $result = $conn->query("
        SELECT enrollments.id, users.name AS student, courses.title AS course, enrollments.progress
        FROM enrollments
        JOIN users ON enrollments.user_id = users.id
        JOIN courses ON enrollments.course_id = courses.id
        WHERE courses.created_by = $uid
        ORDER BY courses.title, users.name
    ");
} else {
    $result = $conn->query("
        SELECT enrollments.id, users.name AS student, courses.title AS course, enrollments.progress
        FROM enrollments
        JOIN users ON enrollments.user_id = users.id
        JOIN courses ON enrollments.course_id = courses.id
        ORDER BY courses.title, users.name
    ");
}
?>
<?php include("includes/header.php"); ?>

<h3>Update Student Progress</h3>

<?php if($success): ?>
  <div class="alert alert-success alert-dismissible fade show">
    <?php echo $success; ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
<?php endif; ?>

<div class="table-responsive">
<table class="table table-bordered mt-3">
  <thead class="table-dark">
    <tr><th>Student</th><th>Course</th><th>Progress</th><th>Update</th></tr>
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
      <td>
        <form method="POST" class="d-flex gap-2">
          <?php csrf_field(); ?>
          <input type="hidden" name="enroll_id" value="<?php echo $row['id']; ?>">
          <input type="number" name="progress" class="form-control form-control-sm" style="width:80px"
                 value="<?php echo $row['progress']; ?>" min="0" max="100" required>
          <button type="submit" name="update" class="btn btn-sm btn-primary">Save</button>
        </form>
      </td>
    </tr>
    <?php endwhile; ?>
  <?php else: ?>
    <tr><td colspan="4" class="text-center">No enrollments found.</td></tr>
  <?php endif; ?>
  </tbody>
</table>
</div>
<a href="dashboard.php" class="btn btn-secondary">Back</a>

<?php include("includes/footer.php"); ?>
