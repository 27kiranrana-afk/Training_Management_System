<?php
session_start();
include("config/db.php");
include("includes/auth.php");
include("includes/csrf.php");
require_login();

// Delete course (admin or trainer who owns it)
if(isset($_POST['delete_course'])){
    csrf_verify();
    $id  = intval($_POST['course_id']);
    $uid = $_SESSION['user_id'];

    if($_SESSION['role'] === 'admin'){
        $stmt = $conn->prepare("DELETE FROM courses WHERE id = ?");
        $stmt->bind_param("i", $id);
    } elseif($_SESSION['role'] === 'trainer'){
        // Trainer can delete their own courses OR unassigned courses
        $stmt = $conn->prepare("DELETE FROM courses WHERE id = ? AND (created_by = ? OR created_by IS NULL)");
        $stmt->bind_param("iii", $id, $uid, $uid);
    } else {
        header("Location: view_courses.php"); exit();
    }
    $stmt->execute();
    header("Location: view_courses.php?deleted=1"); exit();
}

// Search filter
$search = trim($_GET['search'] ?? '');
$uid    = $_SESSION['user_id'];

// Pre-fetch enrolled course IDs for this student
$enrolled_ids = [];
if($_SESSION['role'] === 'student'){
    $en = $conn->prepare("SELECT course_id FROM enrollments WHERE user_id = ?");
    $en->bind_param("i", $uid);
    $en->execute();
    $en_result = $en->get_result();
    while($er = $en_result->fetch_assoc()){
        $enrolled_ids[] = $er['course_id'];
    }
}

if($search){
    $stmt = $conn->prepare("SELECT courses.*, users.name AS trainer_name FROM courses LEFT JOIN users ON courses.created_by=users.id WHERE courses.title LIKE ? ORDER BY courses.id DESC");
    $like = "%$search%";
    $stmt->bind_param("s", $like);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $conn->query("SELECT courses.*, users.name AS trainer_name FROM courses LEFT JOIN users ON courses.created_by=users.id ORDER BY courses.id DESC");
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
      <th>#</th><th>Title</th><th>Duration</th><th>Fees</th><th>Trainer</th>
      <?php if($_SESSION['role'] == 'student'): ?><th>Action</th><?php endif; ?>
      <?php if(in_array($_SESSION['role'],['admin','trainer'])): ?><th>Manage</th><?php endif; ?>
    </tr>
  </thead>
  <tbody>
  <?php $sno = 1; if($result->num_rows > 0): ?>
    <?php while($row = $result->fetch_assoc()): ?>
    <tr>
      <td><?php echo $sno++; ?></td>
      <td><a href="course_detail.php?id=<?php echo $row['id']; ?>"><?php echo htmlspecialchars($row['title']); ?></a></td>
      <td><?php echo htmlspecialchars($row['duration']); ?></td>
      <td><?php echo $row['fees'] > 0 ? '₹'.number_format($row['fees'],2) : '<span class="badge bg-success">Free</span>'; ?></td>
      <td><?php echo htmlspecialchars($row['trainer_name'] ?? '—'); ?></td>
      <?php if($_SESSION['role'] == 'student'): ?>
      <td>
        <?php if(in_array($row['id'], $enrolled_ids)): ?>
          <span class="badge bg-success px-3 py-2">✓ Enrolled</span>
        <?php else: ?>
          <a href="enroll.php?course_id=<?php echo $row['id']; ?>"
             class="btn btn-sm btn-success"
             onclick="return confirm('Enroll in <?php echo htmlspecialchars($row['title']); ?>?')">
            Enroll
          </a>
        <?php endif; ?>
      </td>
      <?php endif; ?>
      <?php if(in_array($_SESSION['role'],['admin','trainer'])): ?>
      <td>
        <?php
          $can_edit = $_SESSION['role']==='admin' 
                   || $row['created_by']==$uid 
                   || ($_SESSION['role']==='trainer' && $row['created_by'] === null);
        ?>
        <?php if($can_edit): ?>
          <a href="edit_course.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-warning">Edit</a>
          <form method="POST" class="d-inline ms-1"
                onsubmit="return confirm('Delete this course? This cannot be undone.')">
            <?php csrf_field(); ?>
            <input type="hidden" name="course_id" value="<?php echo $row['id']; ?>">
            <button type="submit" name="delete_course" class="btn btn-sm btn-danger">Delete</button>
          </form>
        <?php endif; ?>
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
