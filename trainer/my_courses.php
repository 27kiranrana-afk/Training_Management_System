<?php
session_start();
include("../config/db.php");
include("../includes/auth.php");
include("../includes/csrf.php");
require_role('trainer');

$uid = $_SESSION['user_id'];

// Delete own course
if(isset($_POST['delete_course'])){
    csrf_verify();
    $cid  = intval($_POST['course_id']);
    $stmt = $conn->prepare("DELETE FROM courses WHERE id=? AND created_by=?");
    $stmt->bind_param("ii", $cid, $uid);
    $stmt->execute();
    header("Location: my_courses.php?deleted=1"); exit();
}

// Fetch only this trainer's courses
$stmt = $conn->prepare("
    SELECT courses.*, COUNT(enrollments.id) AS enrolled_count
    FROM courses
    LEFT JOIN enrollments ON enrollments.course_id = courses.id
    WHERE courses.created_by = ?
    GROUP BY courses.id
    ORDER BY courses.id DESC
");
$stmt->bind_param("i", $uid);
$stmt->execute();
$result = $stmt->get_result();
?>
<?php include("../includes/header.php"); ?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <h3>📚 My Courses</h3>
  <a href="../add_course.php" class="btn btn-primary">➕ Add Course</a>
</div>

<?php if(isset($_GET['deleted'])): ?>
  <div class="alert alert-warning alert-dismissible fade show">Course deleted.<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>

<div class="table-responsive">
<table class="table table-bordered table-hover">
  <thead class="table-dark">
    <tr><th>#</th><th>Title</th><th>Duration</th><th>Fees</th><th>Students</th><th>Actions</th></tr>
  </thead>
  <tbody>
  <?php if($result->num_rows > 0): $sno=1; while($row = $result->fetch_assoc()): ?>
    <tr>
      <td><?php echo $sno++; ?></td>
      <td><a href="../course_detail.php?id=<?php echo $row['id']; ?>"><?php echo htmlspecialchars($row['title']); ?></a></td>
      <td><?php echo htmlspecialchars($row['duration']); ?></td>
      <td><?php echo $row['fees'] > 0 ? '₹'.number_format($row['fees'],2) : '<span class="badge bg-success">Free</span>'; ?></td>
      <td><?php echo $row['enrolled_count']; ?></td>
      <td>
        <a href="../edit_course.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-warning">Edit</a>
        <form method="POST" class="d-inline ms-1" onsubmit="return confirm('Delete this course?')">
          <?php csrf_field(); ?>
          <input type="hidden" name="course_id" value="<?php echo $row['id']; ?>">
          <button type="submit" name="delete_course" class="btn btn-sm btn-danger">Delete</button>
        </form>
      </td>
    </tr>
  <?php endwhile; else: ?>
    <tr><td colspan="6" class="text-center">No courses yet. <a href="../add_course.php">Add one</a></td></tr>
  <?php endif; ?>
  </tbody>
</table>
</div>
<a href="dashboard.php" class="btn btn-secondary">Back</a>

<?php include("../includes/footer.php"); ?>
