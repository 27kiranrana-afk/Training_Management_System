<?php
session_start();
include("config/db.php");
include("includes/auth.php");
require_login();

$role = $_SESSION['role'];
$uid  = $_SESSION['user_id'];

// Build query based on role — progress is auto-calculated from material completions
if($role === 'student'){
    $stmt = $conn->prepare("
        SELECT enrollments.id, enrollments.progress, enrollments.status,
               users.name AS student, courses.title AS course, courses.id AS course_id
        FROM enrollments
        JOIN users ON enrollments.user_id = users.id
        JOIN courses ON enrollments.course_id = courses.id
        WHERE enrollments.user_id = ?
        ORDER BY courses.title
    ");
    $stmt->bind_param("i", $uid);
} elseif($role === 'trainer'){
    $stmt = $conn->prepare("
        SELECT enrollments.id, enrollments.progress, enrollments.status,
               users.name AS student, courses.title AS course, courses.id AS course_id
        FROM enrollments
        JOIN users ON enrollments.user_id = users.id
        JOIN courses ON enrollments.course_id = courses.id
        WHERE courses.created_by = ?
        ORDER BY courses.title, users.name
    ");
    $stmt->bind_param("i", $uid);
} else {
    $stmt = $conn->prepare("
        SELECT enrollments.id, enrollments.progress, enrollments.status,
               users.name AS student, courses.title AS course, courses.id AS course_id
        FROM enrollments
        JOIN users ON enrollments.user_id = users.id
        JOIN courses ON enrollments.course_id = courses.id
        ORDER BY courses.title, users.name
    ");
}
$stmt->execute();
$result = $stmt->get_result();

// Group by course
$grouped = [];
while($row = $result->fetch_assoc()){
    $grouped[$row['course']][] = $row;
}
?>
<?php include("includes/header.php"); ?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <h3>📈 Course Progress</h3>
  <a href="dashboard.php" class="btn btn-secondary">← Back</a>
</div>

<?php if($role === 'student'): ?>
  <p class="text-muted">Your progress updates automatically as you complete course materials.</p>
<?php else: ?>
  <p class="text-muted">Progress is automatically calculated based on materials completed by each student.</p>
<?php endif; ?>

<?php if(empty($grouped)): ?>
  <div class="alert alert-info">No enrollments found.</div>
<?php else: ?>
  <?php foreach($grouped as $course_name => $students): ?>
  <div class="card mb-4">
    <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
      <span>📚 <?php echo htmlspecialchars($course_name); ?></span>
      <span class="badge bg-secondary"><?php echo count($students); ?> student(s)</span>
    </div>
    <div class="card-body p-0">
      <div class="table-responsive">
      <table class="table table-bordered mb-0">
        <thead class="table-light">
          <tr>
            <th>Student</th>
            <th>Progress</th>
            <th>Status</th>
            <th>Certificate</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach($students as $s):
          $pct   = $s['progress'];
          $color = $pct >= 100 ? 'bg-success' : ($pct >= 50 ? 'bg-info' : ($pct > 0 ? 'bg-warning' : 'bg-secondary'));
        ?>
        <tr>
          <td><?php echo htmlspecialchars($s['student']); ?></td>
          <td style="min-width:200px">
            <div class="d-flex align-items-center gap-2">
              <div class="progress flex-grow-1" style="height:22px">
                <div class="progress-bar <?php echo $color; ?>" style="width:<?php echo $pct; ?>%">
                  <?php echo $pct; ?>%
                </div>
              </div>
            </div>
          </td>
          <td>
            <?php
              $badges = ['completed'=>'bg-success','enrolled'=>'bg-primary','dropped'=>'bg-danger'];
              $badge  = $badges[$s['status']] ?? 'bg-secondary';
            ?>
            <span class="badge <?php echo $badge; ?>"><?php echo ucfirst($s['status']); ?></span>
          </td>
          <td>
            <?php if($s['status'] === 'completed'): ?>
              <a href="certificate.php?course_id=<?php echo $s['course_id']; ?>" class="btn btn-sm btn-success">🎓 Download</a>
            <?php else: ?>
              <span class="text-muted small">Complete course to unlock</span>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
<?php endif; ?>

<?php include("includes/footer.php"); ?>
