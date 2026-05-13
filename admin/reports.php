<?php
session_start();
include("../config/db.php");
include("../includes/auth.php");
require_role('admin');

// Enrollments per course (for bar chart)
$course_stats = $conn->query("
    SELECT courses.title, COUNT(enrollments.id) AS total,
           SUM(CASE WHEN enrollments.progress=100 THEN 1 ELSE 0 END) AS completed
    FROM courses
    LEFT JOIN enrollments ON enrollments.course_id = courses.id
    GROUP BY courses.id
    ORDER BY total DESC
");

$chart_labels = $chart_enrolled = $chart_completed = [];
while($r = $course_stats->fetch_assoc()){
    $chart_labels[]    = $r['title'];
    $chart_enrolled[]  = (int)$r['total'];
    $chart_completed[] = (int)$r['completed'];
}
$chart_labels_json    = json_encode($chart_labels,    JSON_HEX_TAG | JSON_HEX_QUOT | JSON_HEX_AMP);
$chart_enrolled_json  = json_encode($chart_enrolled,  JSON_HEX_TAG | JSON_HEX_QUOT | JSON_HEX_AMP);
$chart_completed_json = json_encode($chart_completed, JSON_HEX_TAG | JSON_HEX_QUOT | JSON_HEX_AMP);

// Role distribution (for pie chart)
$students = $conn->query("SELECT COUNT(*) FROM users WHERE role='student'")->fetch_row()[0];
$trainers = $conn->query("SELECT COUNT(*) FROM users WHERE role='trainer'")->fetch_row()[0];

// Monthly enrollments (last 6 months)
$monthly = $conn->query("
    SELECT DATE_FORMAT(enrolled_at, '%b %Y') AS month,
           COUNT(*) AS total
    FROM enrollments
    WHERE enrolled_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(enrolled_at, '%Y-%m')
    ORDER BY enrolled_at ASC
");
$month_labels = $month_data = [];
while($m = $monthly->fetch_assoc()){
    $month_labels[] = $m['month'];
    $month_data[]   = $m['total'];
}

$month_labels_json = json_encode($month_labels, JSON_HEX_TAG | JSON_HEX_QUOT | JSON_HEX_AMP);
$month_data_json   = json_encode($month_data,   JSON_HEX_TAG | JSON_HEX_QUOT | JSON_HEX_AMP);
if(isset($_GET['export'])){
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="students_report_'.date('Y-m-d').'.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Student Name','Email','Course','Progress','Status','Enrolled Date']);
    $rows = $conn->query("
        SELECT users.name, users.email, courses.title, enrollments.progress,
               enrollments.status, enrollments.enrolled_at
        FROM enrollments
        JOIN users ON enrollments.user_id = users.id
        JOIN courses ON enrollments.course_id = courses.id
        ORDER BY users.name
    ");
    while($row = $rows->fetch_assoc()){
        // Prefix cells starting with formula chars to prevent CSV injection
        $safe = [];
        foreach([$row['name'], $row['email'], $row['title'], $row['progress'].'%', ucfirst($row['status']), $row['enrolled_at']] as $cell){
            $cell = (string)$cell;
            if(in_array(substr($cell, 0, 1), ['=','+','-','@',"\t","\r"])){
                $cell = "'" . $cell;
            }
            $safe[] = $cell;
        }
        fputcsv($out, $safe);
    }
    fclose($out); exit();
}
?>
<?php include("../includes/header.php"); ?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <h3>📊 Reports & Analytics</h3>
  <div>
    <a href="reports.php?export=1" class="btn btn-success">⬇ Export CSV</a>
    <a href="dashboard.php" class="btn btn-secondary ms-2">← Back</a>
  </div>
</div>

<div class="row g-4 mb-4">

  <!-- Bar Chart: Enrollments per Course -->
  <div class="col-md-8">
    <div class="card p-3">
      <h5 class="mb-3">Enrollments per Course</h5>
      <canvas id="courseChart" height="120"></canvas>
    </div>
  </div>

  <!-- Pie Chart: Role Distribution -->
  <div class="col-md-4">
    <div class="card p-3">
      <h5 class="mb-3">User Distribution</h5>
      <canvas id="roleChart"></canvas>
    </div>
  </div>

  <!-- Line Chart: Monthly Enrollments -->
  <div class="col-md-12">
    <div class="card p-3">
      <h5 class="mb-3">Monthly Enrollments (Last 6 Months)</h5>
      <canvas id="monthChart" height="80"></canvas>
    </div>
  </div>

</div>

<!-- Detailed Table -->
<h5>All Enrollments</h5>
<div class="table-responsive">
<table class="table table-bordered table-sm">
  <thead class="table-dark">
    <tr><th>Student</th><th>Email</th><th>Course</th><th>Progress</th><th>Status</th><th>Enrolled</th></tr>
  </thead>
  <tbody>
  <?php
  $all = $conn->query("
      SELECT users.name, users.email, courses.title, enrollments.progress,
             enrollments.status, enrollments.enrolled_at
      FROM enrollments
      JOIN users ON enrollments.user_id = users.id
      JOIN courses ON enrollments.course_id = courses.id
      ORDER BY enrollments.enrolled_at DESC
  ");
  if($all->num_rows > 0):
    while($row = $all->fetch_assoc()): ?>
    <tr>
      <td><?php echo htmlspecialchars($row['name']); ?></td>
      <td><?php echo htmlspecialchars($row['email']); ?></td>
      <td><?php echo htmlspecialchars($row['title']); ?></td>
      <td>
        <div class="progress" style="height:16px">
          <div class="progress-bar <?php echo $row['progress']==100?'bg-success':''; ?>"
               style="width:<?php echo $row['progress']; ?>%"><?php echo $row['progress']; ?>%</div>
        </div>
      </td>
      <td><span class="badge <?php echo $row['status']=='completed'?'bg-success':($row['status']=='dropped'?'bg-danger':'bg-primary'); ?>">
        <?php echo ucfirst($row['status']); ?></span></td>
      <td><?php echo date('d M Y', strtotime($row['enrolled_at'])); ?></td>
    </tr>
    <?php endwhile;
  else: ?>
    <tr><td colspan="6" class="text-center">No enrollments yet.</td></tr>
  <?php endif; ?>
  </tbody>
</table>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
const labels    = <?php echo $chart_labels_json; ?>;
const enrolled  = <?php echo $chart_enrolled_json; ?>;
const completed = <?php echo $chart_completed_json; ?>;

// Bar chart
new Chart(document.getElementById('courseChart'), {
  type: 'bar',
  data: {
    labels: labels,
    datasets: [
      { label: 'Enrolled',  data: enrolled,  backgroundColor: 'rgba(54,162,235,0.7)' },
      { label: 'Completed', data: completed, backgroundColor: 'rgba(75,192,92,0.7)'  }
    ]
  },
  options: { responsive: true, plugins: { legend: { position: 'top' } } }
});

// Pie chart
new Chart(document.getElementById('roleChart'), {
  type: 'doughnut',
  data: {
    labels: ['Students', 'Trainers'],
    datasets: [{ data: [<?php echo $students; ?>, <?php echo $trainers; ?>],
      backgroundColor: ['rgba(54,162,235,0.8)', 'rgba(75,192,92,0.8)'] }]
  }
});

// Line chart
new Chart(document.getElementById('monthChart'), {
  type: 'line',
  data: {
    labels: <?php echo $month_labels_json; ?>,
    datasets: [{
      label: 'Enrollments',
      data: <?php echo $month_data_json; ?>,
      borderColor: 'rgba(255,99,132,1)',
      backgroundColor: 'rgba(255,99,132,0.1)',
      fill: true, tension: 0.4
    }]
  },
  options: { responsive: true }
});
</script>

<?php include("../includes/footer.php"); ?>
