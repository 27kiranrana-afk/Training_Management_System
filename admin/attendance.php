<?php
session_start();
include("../config/db.php");
include("../includes/auth.php");
include("../includes/csrf.php");
require_role('admin', 'trainer');

$success = $error = "";

if(isset($_POST['update_attendance'])){
    csrf_verify();
    $enroll_id    = intval($_POST['enroll_id']);
    $attended     = intval($_POST['attendance']);
    $total        = intval($_POST['total_classes']);

    if($total < $attended){
        $error = "Attended classes cannot exceed total classes.";
    } else {
        $stmt = $conn->prepare("UPDATE enrollments SET attendance=?, total_classes=? WHERE id=?");
        $stmt->bind_param("iii", $attended, $total, $enroll_id);
        $success = $stmt->execute() ? "Attendance updated!" : "Update failed.";
    }
}

// Trainers see only their courses
if($_SESSION['role'] == 'trainer'){
    $uid = $_SESSION['user_id'];
    $result = $conn->query("
        SELECT enrollments.id, users.name AS student, courses.title AS course,
               enrollments.attendance, enrollments.total_classes, enrollments.progress
        FROM enrollments
        JOIN users ON enrollments.user_id = users.id
        JOIN courses ON enrollments.course_id = courses.id
        WHERE courses.created_by = $uid
        ORDER BY courses.title, users.name
    ");
} else {
    $result = $conn->query("
        SELECT enrollments.id, users.name AS student, courses.title AS course,
               enrollments.attendance, enrollments.total_classes, enrollments.progress
        FROM enrollments
        JOIN users ON enrollments.user_id = users.id
        JOIN courses ON enrollments.course_id = courses.id
        ORDER BY courses.title, users.name
    ");
}
?>
<?php include("../includes/header.php"); ?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <h3>📋 Attendance Tracking</h3>
  <a href="../dashboard.php" class="btn btn-secondary">← Back</a>
</div>

<?php if($success): ?>
  <div class="alert alert-success alert-dismissible fade show"><?php echo $success; ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>
<?php if($error): ?>
  <div class="alert alert-danger alert-dismissible fade show"><?php echo $error; ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>

<div class="table-responsive">
<table class="table table-bordered">
  <thead class="table-dark">
    <tr><th>Student</th><th>Attended</th><th>Total Classes</th><th>Attendance %</th><th>Progress</th><th>Update</th></tr>
  </thead>
  <tbody>
  <?php if($result && $result->num_rows > 0):
    $current_course = '';
    while($row = $result->fetch_assoc()):
      $pct   = $row['total_classes'] > 0 ? round(($row['attendance'] / $row['total_classes']) * 100) : 0;
      $color = $pct >= 75 ? 'bg-success' : ($pct >= 50 ? 'bg-warning' : 'bg-danger');

      // Print course header when course changes
      if($row['course'] !== $current_course):
          $current_course = $row['course'];
  ?>
    <tr class="table-primary">
      <td colspan="6" class="fw-bold">📚 <?php echo htmlspecialchars($current_course); ?></td>
    </tr>
  <?php endif; ?>
    <tr>
      <td><?php echo htmlspecialchars($row['student']); ?></td>
      <td><?php echo $row['attendance']; ?></td>
      <td><?php echo $row['total_classes']; ?></td>
      <td>
        <div class="progress" style="height:18px">
          <div class="progress-bar <?php echo $color; ?>" style="width:<?php echo $pct; ?>%"><?php echo $pct; ?>%</div>
        </div>
      </td>
      <td>
        <div class="progress" style="height:18px">
          <div class="progress-bar <?php echo $row['progress']==100?'bg-success':''; ?>"
               style="width:<?php echo $row['progress']; ?>%"><?php echo $row['progress']; ?>%</div>
        </div>
      </td>
      <td>
        <form method="POST" class="d-flex gap-1 align-items-center">
          <?php csrf_field(); ?>
          <input type="hidden" name="enroll_id" value="<?php echo $row['id']; ?>">
          <input type="number" name="attendance" class="form-control form-control-sm" style="width:65px"
                 value="<?php echo $row['attendance']; ?>" min="0" required>
          <span class="text-muted">/</span>
          <input type="number" name="total_classes" class="form-control form-control-sm" style="width:65px"
                 value="<?php echo $row['total_classes']; ?>" min="0" required>
          <button type="submit" name="update_attendance" class="btn btn-sm btn-primary">Save</button>
        </form>
      </td>
    </tr>
  <?php endwhile; ?>
  <?php else: ?>
    <tr><td colspan="6" class="text-center">No enrollments found.</td></tr>
  <?php endif; ?>
  </tbody>
</table>
</div>

<?php include("../includes/footer.php"); ?>
