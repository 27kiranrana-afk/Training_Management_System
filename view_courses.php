<?php
session_start();
include("config/db.php");
include("includes/auth.php");
include("includes/csrf.php");
require_login();

// Delete course (admin only)
if(isset($_GET['delete']) && $_SESSION['role'] == 'admin'){
    csrf_verify();
    $id = intval($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM courses WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    header("Location: view_courses.php?deleted=1"); exit();
}

// Search filter
$search = trim($_GET['search'] ?? '');
if($search){
    $stmt = $conn->prepare("SELECT * FROM courses WHERE title LIKE ? ORDER BY id DESC");
    $like = "%$search%";
    $stmt->bind_param("s", $like);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $conn->query("SELECT * FROM courses ORDER BY id DESC");
}
?>
<?php include("includes/header.php"); ?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <h3>Available Courses</h3>
  <?php if(in_array($_SESSION['role'], ['admin','trainer'])): ?>
    <a href="add_course.php" class="btn btn-primary">➕ Add Course</a>
  <?php endif; ?>
</div>

<?php if(isset($_GET['deleted'])): ?>
  <div class="alert alert-success alert-dismissible fade show">
    Course deleted.<button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
<?php endif; ?>

<!-- Search -->
<form method="GET" class="mb-3 d-flex gap-2" style="max-width:400px">
  <input type="text" name="search" class="form-control" placeholder="Search courses..." value="<?php echo htmlspecialchars($search); ?>">
  <button class="btn btn-outline-secondary">Search</button>
  <?php if($search): ?><a href="view_courses.php" class="btn btn-outline-danger">Clear</a><?php endif; ?>
</form>

<div class="table-responsive">
<table class="table table-bordered table-hover">
  <thead class="table-dark">
    <tr>
      <th>#</th><th>Title</th><th>Duration</th>
      <?php if($_SESSION['role'] == 'student'): ?><th>Action</th><?php endif; ?>
      <?php if($_SESSION['role'] == 'admin'): ?><th>Manage</th><?php endif; ?>
    </tr>
  </thead>
  <tbody>
  <?php if($result->num_rows > 0): ?>
    <?php while($row = $result->fetch_assoc()): ?>
    <tr>
      <td><?php echo $row['id']; ?></td>
      <td><?php echo htmlspecialchars($row['title']); ?></td>
      <td><?php echo htmlspecialchars($row['duration']); ?></td>
      <?php if($_SESSION['role'] == 'student'): ?>
      <td>
        <a href="enroll.php?course_id=<?php echo $row['id']; ?>"
           class="btn btn-sm btn-success"
           onclick="return confirm('Enroll in <?php echo htmlspecialchars($row['title']); ?>?')">
          Enroll
        </a>
      </td>
      <?php endif; ?>
      <?php if($_SESSION['role'] == 'admin'): ?>
      <td>
        <a href="edit_course.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-warning">Edit</a>
        <a href="view_courses.php?delete=<?php echo $row['id']; ?>&csrf_token=<?php echo csrf_token(); ?>"
           class="btn btn-sm btn-danger ms-1"
           onclick="return confirm('Delete this course? This cannot be undone.')">
          Delete
        </a>
      </td>
      <?php endif; ?>
    </tr>
    <?php endwhile; ?>
  <?php else: ?>
    <tr><td colspan="5" class="text-center">No courses found.</td></tr>
  <?php endif; ?>
  </tbody>
</table>
</div>
<a href="dashboard.php" class="btn btn-secondary">Back</a>

<?php include("includes/footer.php"); ?>
