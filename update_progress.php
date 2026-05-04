<?php
session_start();
include("config/db.php");
include("includes/auth.php");
include("includes/csrf.php");
require_role('admin', 'trainer');

$success = $error = "";

if(isset($_POST['update'])){
    csrf_verify();
    $enroll_id   = intval($_POST['enroll_id']);
    $new_progress = max(0, min(100, intval($_POST['progress'])));
    $note        = trim($_POST['note'] ?? '');
    $updated_by  = $_SESSION['user_id'];

    // Get current progress before update
    $cur = $conn->prepare("SELECT progress, user_id, course_id FROM enrollments WHERE id = ?");
    $cur->bind_param("i", $enroll_id);
    $cur->execute();
    $cur_data = $cur->get_result()->fetch_assoc();
    $old_progress = $cur_data['progress'];

    // Update progress; mark completed_at and status if 100%
    if($new_progress >= 100){
        $stmt = $conn->prepare("UPDATE enrollments SET progress=100, status='completed', completed_at=NOW() WHERE id=?");
        $stmt->bind_param("i", $enroll_id);
    } else {
        $stmt = $conn->prepare("UPDATE enrollments SET progress=?, status='enrolled', completed_at=NULL WHERE id=?");
        $stmt->bind_param("ii", $new_progress, $enroll_id);
    }

    if($stmt->execute()){
        // Log the change
        $log = $conn->prepare("INSERT INTO progress_log (enrollment_id, updated_by, old_progress, new_progress, note) VALUES (?,?,?,?,?)");
        $log->bind_param("iiiis", $enroll_id, $updated_by, $old_progress, $new_progress, $note);
        $log->execute();

        // Auto-issue certificate if completed and not already issued
        if($new_progress >= 100){
            $cert_check = $conn->prepare("SELECT id FROM certificates WHERE enrollment_id=?");
            $cert_check->bind_param("i", $enroll_id);
            $cert_check->execute();
            $cert_check->store_result();
            if($cert_check->num_rows == 0){
                $cert_no = 'TMS-' . date('Y') . '-' . str_pad($enroll_id, 5, '0', STR_PAD_LEFT);
                $cert = $conn->prepare("INSERT INTO certificates (enrollment_id, user_id, course_id, certificate_no) VALUES (?,?,?,?)");
                $cert->bind_param("iiis", $enroll_id, $cur_data['user_id'], $cur_data['course_id'], $cert_no);
                $cert->execute();
            }
        }
        $success = "Progress updated!";
    } else {
        $error = "Update failed.";
    }
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
        <form method="POST" class="d-flex gap-2 align-items-center">
          <?php csrf_field(); ?>
          <input type="hidden" name="enroll_id" value="<?php echo $row['id']; ?>">
          <input type="number" name="progress" class="form-control form-control-sm" style="width:80px"
                 value="<?php echo $row['progress']; ?>" min="0" max="100" required>
          <input type="text" name="note" class="form-control form-control-sm" style="width:140px" placeholder="Note (optional)">
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
