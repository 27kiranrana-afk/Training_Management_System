<?php
session_start();
include("../config/db.php");
include("../includes/auth.php");
require_role('admin');

$total_students    = $conn->query("SELECT COUNT(*) FROM users WHERE role='student'")->fetch_row()[0];
$total_trainers    = $conn->query("SELECT COUNT(*) FROM users WHERE role='trainer'")->fetch_row()[0];
$total_courses     = $conn->query("SELECT COUNT(*) FROM courses WHERE is_active=1")->fetch_row()[0];
$total_enrollments = $conn->query("SELECT COUNT(*) FROM enrollments")->fetch_row()[0];
$pending_inquiries = $conn->query("SELECT COUNT(*) FROM inquiries WHERE status='pending'")->fetch_row()[0];
$completed         = $conn->query("SELECT COUNT(*) FROM enrollments WHERE progress=100")->fetch_row()[0];
$pending_refunds   = 0;
$rCheck = $conn->query("SHOW TABLES LIKE 'refund_requests'");
if($rCheck && $rCheck->num_rows > 0){
    $pending_refunds = $conn->query("SELECT COUNT(*) FROM refund_requests WHERE status IN ('pending','processing')")->fetch_row()[0];
}

// Recent enrollments — use enrolled_at column
$recent = $conn->query("
    SELECT users.name AS student, courses.title AS course,
           enrollments.progress, enrollments.enrolled_at
    FROM enrollments
    JOIN users ON enrollments.user_id = users.id
    JOIN courses ON enrollments.course_id = courses.id
    ORDER BY enrollments.enrolled_at DESC
    LIMIT 5
");

// All trainers with their course count
$trainers = $conn->query("
    SELECT users.name, users.email,
           COUNT(courses.id) AS course_count
    FROM users
    LEFT JOIN courses ON courses.created_by = users.id AND courses.is_active=1
    WHERE users.role = 'trainer' AND users.is_active=1
    GROUP BY users.id
    ORDER BY users.name ASC
");
?>
<?php include("../includes/header.php"); ?>

<div class="d-flex justify-content-between align-items-center mb-2">
  <div>
    <h3 class="mb-0">🛡️ Admin Dashboard</h3>
    <p class="text-muted">Welcome, <?php echo htmlspecialchars($_SESSION['user']); ?></p>
  </div>
</div>
<hr>

<!-- Stats Cards -->
<div class="row g-3 mb-4">
  <div class="col-md-2">
    <a href="users.php?role=student" class="text-decoration-none">
      <div class="card text-white bg-primary text-center p-3 h-100">
        <div class="fs-2 fw-bold"><?php echo $total_students; ?></div>
        <div>Students</div>
      </div>
    </a>
  </div>
  <div class="col-md-2">
    <a href="users.php?role=trainer" class="text-decoration-none">
      <div class="card text-white bg-success text-center p-3 h-100">
        <div class="fs-2 fw-bold"><?php echo $total_trainers; ?></div>
        <div>Trainers</div>
      </div>
    </a>
  </div>
  <div class="col-md-2">
    <a href="../view_courses.php" class="text-decoration-none">
      <div class="card text-white bg-info text-center p-3 h-100">
        <div class="fs-2 fw-bold"><?php echo $total_courses; ?></div>
        <div>Courses</div>
      </div>
    </a>
  </div>
  <div class="col-md-2">
    <a href="../course_progress.php" class="text-decoration-none">
      <div class="card text-white bg-secondary text-center p-3 h-100">
        <div class="fs-2 fw-bold"><?php echo $total_enrollments; ?></div>
        <div>Enrollments</div>
      </div>
    </a>
  </div>
  <div class="col-md-2">
    <a href="../manage_inquiries.php" class="text-decoration-none">
      <div class="card text-white bg-warning text-center p-3 h-100">
        <div class="fs-2 fw-bold"><?php echo $pending_inquiries; ?></div>
        <div>Pending Inquiries</div>
      </div>
    </a>
  </div>
  <div class="col-md-2">
    <a href="../update_progress.php" class="text-decoration-none">
      <div class="card text-white bg-dark text-center p-3 h-100">
        <div class="fs-2 fw-bold"><?php echo $completed; ?></div>
        <div>Completions</div>
      </div>
    </a>
  </div>
  <div class="col-md-2">
    <a href="refunds.php" class="text-decoration-none">
      <div class="card text-white bg-danger text-center p-3 h-100">
        <div class="fs-2 fw-bold"><?php echo $pending_refunds; ?></div>
        <div>Pending Refunds</div>
      </div>
    </a>
  </div>
</div>

<!-- Quick Actions -->
<h5 class="mb-3">Quick Actions</h5>
<div class="row g-3 mb-4">
  <div class="col-md-3"><a href="../view_courses.php" class="btn btn-secondary w-100">📚 Courses</a></div>
  <div class="col-md-3"><a href="../manage_inquiries.php" class="btn btn-warning w-100">💬 Inquiries</a></div>
  <div class="col-md-3"><a href="../course_progress.php" class="btn btn-info w-100">📈 Progress</a></div>
  <div class="col-md-3"><a href="users.php" class="btn btn-dark w-100">👥 Users</a></div>
  <div class="col-md-3"><a href="reports.php" class="btn btn-success w-100">📊 Reports</a></div>
  <div class="col-md-3"><a href="refunds.php" class="btn btn-danger w-100">💰 Refund Requests</a></div>
</div>

<div class="row g-4">
  <!-- Recent Enrollments -->
  <div class="col-md-7">
    <h5>Recent Enrollments</h5>
    <table class="table table-sm table-bordered">
      <thead class="table-dark">
        <tr><th>Student</th><th>Course</th><th>Progress</th><th>Date</th></tr>
      </thead>
      <tbody>
      <?php if($recent->num_rows > 0): ?>
        <?php while($r = $recent->fetch_assoc()): ?>
        <tr>
          <td><?php echo htmlspecialchars($r['student']); ?></td>
          <td><?php echo htmlspecialchars($r['course']); ?></td>
          <td>
            <div class="progress" style="height:16px">
              <div class="progress-bar <?php echo $r['progress']==100?'bg-success':''; ?>"
                   style="width:<?php echo $r['progress']; ?>%">
                <?php echo $r['progress']; ?>%
              </div>
            </div>
          </td>
          <td><?php echo date('d M Y', strtotime($r['enrolled_at'] ?? 'now')); ?></td>
        </tr>
        <?php endwhile; ?>
      <?php else: ?>
        <tr><td colspan="4" class="text-center">No enrollments yet.</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>

  <!-- Trainers Overview -->
  <div class="col-md-5">
    <h5>Trainers Overview</h5>
    <table class="table table-sm table-bordered">
      <thead class="table-dark">
        <tr><th>Name</th><th>Email</th><th>Courses</th></tr>
      </thead>
      <tbody>
      <?php if($trainers->num_rows > 0): ?>
        <?php while($t = $trainers->fetch_assoc()): ?>
        <tr>
          <td><?php echo htmlspecialchars($t['name']); ?></td>
          <td><?php echo htmlspecialchars($t['email']); ?></td>
          <td><?php echo $t['course_count']; ?></td>
        </tr>
        <?php endwhile; ?>
      <?php else: ?>
        <tr><td colspan="3" class="text-center">No trainers yet.</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Charts Row -->
<?php
$course_stats = $conn->query("
    SELECT courses.title, COUNT(enrollments.id) AS total,
           SUM(CASE WHEN enrollments.progress=100 THEN 1 ELSE 0 END) AS completed
    FROM courses LEFT JOIN enrollments ON enrollments.course_id = courses.id
    GROUP BY courses.id ORDER BY total DESC LIMIT 6
");
$clabels = $cenrolled = $ccompleted = [];
while($r = $course_stats->fetch_assoc()){
    $clabels[]    = $r['title'];
    $cenrolled[]  = (int)$r['total'];
    $ccompleted[] = (int)$r['completed'];
}
?>
<div class="row g-4 mt-2">
  <div class="col-md-4">
    <div class="card p-3">
      <h6 class="mb-3">User Distribution</h6>
      <canvas id="roleChart"></canvas>
    </div>
  </div>
  <div class="col-md-8">
    <div class="card p-3">
      <h6 class="mb-3">Enrollments per Course</h6>
      <canvas id="courseChart" height="120"></canvas>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
new Chart(document.getElementById('courseChart'), {
  type: 'doughnut',
  data: {
    labels: <?php echo json_encode($clabels); ?>,
    datasets: [{
      data: <?php echo json_encode($cenrolled); ?>,
      backgroundColor: [
        'rgba(54,162,235,0.8)','rgba(75,192,92,0.8)','rgba(255,99,132,0.8)',
        'rgba(255,206,86,0.8)','rgba(153,102,255,0.8)','rgba(255,159,64,0.8)'
      ]
    }]
  },
  options: { responsive: true, plugins: { legend: { position: 'right' } } }
});
new Chart(document.getElementById('roleChart'), {
  type: 'bar',
  data: {
    labels: ['Students','Trainers'],
    datasets: [{
      label: 'Users',
      data: [<?php echo $total_students; ?>, <?php echo $total_trainers; ?>],
      backgroundColor: ['rgba(54,162,235,0.8)','rgba(75,192,92,0.8)']
    }]
  },
  options: { responsive: true, plugins: { legend: { display: false } } }
});
</script>

<?php include("../includes/footer.php"); ?>
