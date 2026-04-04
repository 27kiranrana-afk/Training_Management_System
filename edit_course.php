<?php
session_start();
include("config/db.php");
include("includes/auth.php");
include("includes/csrf.php");
require_role('admin');

$id = intval($_GET['id'] ?? 0);
if($id <= 0){ header("Location: view_courses.php"); exit(); }

$success = $error = "";

if(isset($_POST['update'])){
    csrf_verify();
    $title    = trim($_POST['title']);
    $duration = trim($_POST['duration']);
    $stmt = $conn->prepare("UPDATE courses SET title=?, duration=? WHERE id=?");
    $stmt->bind_param("ssi", $title, $duration, $id);
    $success = $stmt->execute() ? "Course updated!" : "Update failed.";
}

// Fetch current data
$stmt = $conn->prepare("SELECT * FROM courses WHERE id=?");
$stmt->bind_param("i", $id);
$stmt->execute();
$course = $stmt->get_result()->fetch_assoc();
if(!$course){ header("Location: view_courses.php"); exit(); }
?>
<?php include("includes/header.php"); ?>

<h3>Edit Course</h3>

<?php if($error): ?>
  <div class="alert alert-danger alert-dismissible fade show">
    <?php echo $error; ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
<?php endif; ?>
<?php if($success): ?>
  <div class="alert alert-success alert-dismissible fade show">
    <?php echo $success; ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
<?php endif; ?>

<div class="row">
  <div class="col-md-5">
    <form method="POST">
      <?php csrf_field(); ?>
      <div class="mb-3">
        <label>Course Title</label>
        <input type="text" name="title" class="form-control" value="<?php echo htmlspecialchars($course['title']); ?>" required>
      </div>
      <div class="mb-3">
        <label>Duration</label>
        <input type="text" name="duration" class="form-control" value="<?php echo htmlspecialchars($course['duration']); ?>" required>
      </div>
      <button type="submit" name="update" class="btn btn-warning">Update Course</button>
      <a href="view_courses.php" class="btn btn-secondary ms-2">Cancel</a>
    </form>
  </div>
</div>

<?php include("includes/footer.php"); ?>
