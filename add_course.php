<?php
session_start();
include("config/db.php");
include("includes/auth.php");
include("includes/csrf.php");
require_role('admin', 'trainer');

$success = $error = "";

if(isset($_POST['add'])){
    csrf_verify();
    $title    = trim($_POST['title']);
    $duration = trim($_POST['duration']);
    $created_by = $_SESSION['user_id'];

    $stmt = $conn->prepare("INSERT INTO courses (title, duration, created_by) VALUES (?, ?, ?)");
    $stmt->bind_param("ssi", $title, $duration, $created_by);
    $success = $stmt->execute() ? "Course added successfully!" : "Failed to add course.";
}
?>
<?php include("includes/header.php"); ?>

<h3>Add Course</h3>

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
        <input type="text" name="title" class="form-control" required>
      </div>
      <div class="mb-3">
        <label>Duration (e.g. 3 months)</label>
        <input type="text" name="duration" class="form-control" required>
      </div>
      <button type="submit" name="add" class="btn btn-primary">Add Course</button>
      <a href="dashboard.php" class="btn btn-secondary ms-2">Back</a>
    </form>
  </div>
</div>

<?php include("includes/footer.php"); ?>
