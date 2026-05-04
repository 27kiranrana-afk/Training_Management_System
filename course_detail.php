<?php
session_start();
include("config/db.php");
include("includes/auth.php");
include("includes/base.php");
include("includes/csrf.php");
require_login();

// Auto-create material_completions table if not exists
$conn->query("CREATE TABLE IF NOT EXISTS material_completions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    material_id INT NOT NULL,
    completed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_completion (user_id, material_id)
)");

// Auto-create certificates table with all required columns
$conn->query("CREATE TABLE IF NOT EXISTS certificates (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    enrollment_id  INT          NOT NULL UNIQUE,
    user_id        INT          NOT NULL,
    course_id      INT          NOT NULL,
    certificate_no VARCHAR(50)  NOT NULL UNIQUE,
    issued_at      TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
)");

// Add certificate_no column if missing (for existing tables)
@$conn->query("ALTER TABLE certificates ADD COLUMN IF NOT EXISTS certificate_no VARCHAR(50) UNIQUE");
@$conn->query("ALTER TABLE certificates ADD COLUMN IF NOT EXISTS enrollment_id INT DEFAULT NULL");

// Auto-create payments table
$conn->query("CREATE TABLE IF NOT EXISTS payments (
    id                  INT AUTO_INCREMENT PRIMARY KEY,
    user_id             INT           NOT NULL,
    course_id           INT           NOT NULL,
    razorpay_order_id   VARCHAR(100)  DEFAULT NULL,
    razorpay_payment_id VARCHAR(100)  DEFAULT NULL,
    amount              DECIMAL(10,2) NOT NULL DEFAULT 0,
    status              VARCHAR(20)   DEFAULT 'success',
    paid_at             TIMESTAMP     DEFAULT CURRENT_TIMESTAMP
)");

$id   = intval($_GET['id'] ?? 0);
$uid  = $_SESSION['user_id'];
$role = $_SESSION['role'];
if($id <= 0){ header("Location: view_courses.php"); exit(); }

// Fetch course
$stmt = $conn->prepare("
    SELECT courses.*, users.name AS trainer_name
    FROM courses LEFT JOIN users ON courses.created_by = users.id
    WHERE courses.id = ?
");
$stmt->bind_param("i", $id);
$stmt->execute();
$course = $stmt->get_result()->fetch_assoc();
if(!$course){ header("Location: view_courses.php"); exit(); }

// Check enrollment
$enrolled = false;
$enroll_id = null;
if($role === 'student'){
    $chk = $conn->prepare("SELECT id FROM enrollments WHERE user_id=? AND course_id=?");
    $chk->bind_param("ii", $uid, $id);
    $chk->execute();
    $chk_row = $chk->get_result()->fetch_assoc();
    if($chk_row){ $enrolled = true; $enroll_id = $chk_row['id']; }
}

// Handle mark complete / uncomplete
if($role === 'student' && $enrolled && isset($_POST['toggle_material'])){
    csrf_verify();
    $mid = intval($_POST['material_id']);

    // Check if already completed
    $exists = $conn->prepare("SELECT id FROM material_completions WHERE user_id=? AND material_id=?");
    $exists->bind_param("ii", $uid, $mid);
    $exists->execute();
    $exists->store_result();

    if($exists->num_rows > 0){
        // Unmark
        $del = $conn->prepare("DELETE FROM material_completions WHERE user_id=? AND material_id=?");
        $del->bind_param("ii", $uid, $mid);
        $del->execute();
    } else {
        // Mark complete
        $ins = $conn->prepare("INSERT INTO material_completions (user_id, material_id) VALUES (?,?)");
        $ins->bind_param("ii", $uid, $mid);
        $ins->execute();
    }

    // Recalculate progress
    $total_mats = $conn->prepare("SELECT COUNT(*) FROM course_materials WHERE course_id=?");
    $total_mats->bind_param("i", $id);
    $total_mats->execute();
    $total = $total_mats->get_result()->fetch_row()[0];

    $done_mats = $conn->prepare("
        SELECT COUNT(*) FROM material_completions mc
        JOIN course_materials cm ON mc.material_id = cm.id
        WHERE cm.course_id=? AND mc.user_id=?
    ");
    $done_mats->bind_param("ii", $id, $uid);
    $done_mats->execute();
    $done = $done_mats->get_result()->fetch_row()[0];

    $progress = $total > 0 ? round(($done / $total) * 100) : 0;

    // Update enrollment progress
    if($progress >= 100){
        $upd = $conn->prepare("UPDATE enrollments SET progress=100, status='completed', completed_at=NOW() WHERE id=?");
        $upd->bind_param("i", $enroll_id);
        $upd->execute();
        // Auto-issue certificate
        $cc = $conn->prepare("SELECT id FROM certificates WHERE user_id=? AND course_id=?");
        $cc->bind_param("ii", $uid, $id);
        $cc->execute(); $cc->store_result();
        if($cc->num_rows == 0){
            $cert_no = 'TMS-' . date('Y') . '-' . str_pad($enroll_id, 5, '0', STR_PAD_LEFT);
            $ci = $conn->prepare("INSERT IGNORE INTO certificates (enrollment_id, user_id, course_id, certificate_no) VALUES (?,?,?,?)");
            $ci->bind_param("iiis", $enroll_id, $uid, $id, $cert_no);
            $ci->execute();
        }
    } else {
        $upd = $conn->prepare("UPDATE enrollments SET progress=?, status='enrolled', completed_at=NULL WHERE id=?");
        $upd->bind_param("ii", $progress, $enroll_id);
        $upd->execute();
    }

    header("Location: course_detail.php?id=$id"); exit();
}

// Fetch materials
$mats = $conn->prepare("SELECT * FROM course_materials WHERE course_id=? ORDER BY sort_order ASC, id ASC");
$mats->bind_param("i", $id);
$mats->execute();
$materials = $mats->get_result();
$total_materials = $materials->num_rows;

// Fetch completed material IDs for this student
$completed_ids = [];
if($role === 'student' && $enrolled){
    $comp = $conn->prepare("
        SELECT material_id FROM material_completions mc
        JOIN course_materials cm ON mc.material_id = cm.id
        WHERE cm.course_id=? AND mc.user_id=?
    ");
    $comp->bind_param("ii", $id, $uid);
    $comp->execute();
    $comp_result = $comp->get_result();
    while($cr = $comp_result->fetch_assoc()) $completed_ids[] = $cr['material_id'];
}

$enrolled_count = $conn->query("SELECT COUNT(*) FROM enrollments WHERE course_id=$id")->fetch_row()[0];

// Current progress
$current_progress = 0;
if($role === 'student' && $enrolled){
    $pr = $conn->prepare("SELECT progress FROM enrollments WHERE id=?");
    $pr->bind_param("i", $enroll_id);
    $pr->execute();
    $current_progress = $pr->get_result()->fetch_row()[0];
}
?>
<?php include("includes/header.php"); ?>

<div class="row">
  <div class="col-md-8">
    <h3><?php echo htmlspecialchars($course['title']); ?></h3>
    <p class="text-muted">By <?php echo htmlspecialchars($course['trainer_name'] ?? 'TMS'); ?></p>
    <hr>

    <div class="row g-3 mb-4">
      <div class="col-md-4">
        <div class="card text-center p-3 bg-light">
          <div class="fw-bold fs-5">⏱ <?php echo htmlspecialchars($course['duration']); ?></div>
          <small class="text-muted">Duration</small>
        </div>
      </div>
      <div class="col-md-4">
        <div class="card text-center p-3 bg-light">
          <div class="fw-bold fs-5">👥 <?php echo $enrolled_count; ?></div>
          <small class="text-muted">Students Enrolled</small>
        </div>
      </div>
      <div class="col-md-4">
        <div class="card text-center p-3 bg-light">
          <div class="fw-bold fs-5">📖 <?php echo $total_materials; ?></div>
          <small class="text-muted">Materials</small>
        </div>
      </div>
    </div>

    <?php if($role === 'student' && $enrolled && $total_materials > 0): ?>
    <div class="mb-4">
      <div class="d-flex justify-content-between mb-1">
        <span class="fw-bold">Your Progress</span>
        <span><?php echo $current_progress; ?>%</span>
      </div>
      <div class="progress" style="height:22px">
        <div class="progress-bar <?php echo $current_progress>=100?'bg-success':($current_progress>0?'bg-info':'bg-secondary'); ?>"
             style="width:<?php echo $current_progress; ?>%">
          <?php echo $current_progress; ?>%
        </div>
      </div>
      <?php if($current_progress >= 100): ?>
        <div class="alert alert-success mt-2">🎉 Course completed! <a href="certificate.php?course_id=<?php echo $id; ?>">Download Certificate</a></div>
      <?php endif; ?>
    </div>
    <?php endif; ?>

    <h5>About this Course</h5>
    <p><?php echo nl2br(htmlspecialchars($course['description'] ?? 'No description provided.')); ?></p>

    <?php if($total_materials > 0): ?>
    <h5 class="mt-4">📚 Course Materials
      <?php if($role === 'student' && $enrolled): ?>
        <small class="text-muted fs-6">(tick each item as you complete it)</small>
      <?php endif; ?>
    </h5>
    <div class="accordion" id="materialsAccordion">
    <?php $materials->data_seek(0); $idx=0; while($m = $materials->fetch_assoc()): $idx++;
      $is_done = in_array($m['id'], $completed_ids);
    ?>
      <div class="accordion-item <?php echo $is_done ? 'border-success' : ''; ?>">
        <h2 class="accordion-header">
          <button class="accordion-button collapsed <?php echo $is_done ? 'text-success' : ''; ?>"
                  type="button" data-bs-toggle="collapse" data-bs-target="#mat<?php echo $idx; ?>">
            <?php
              $icons = ['video_url'=>'🎬','video_file'=>'📁','notes'=>'📄','notes_url'=>'🔗'];
              echo ($icons[$m['type']] ?? '📎') . ' ' . htmlspecialchars($m['title']);
              if($is_done) echo ' <span class="badge bg-success ms-2">✓ Done</span>';
            ?>
          </button>
        </h2>
        <div id="mat<?php echo $idx; ?>" class="accordion-collapse collapse">
          <div class="accordion-body">
            <?php if($m['type'] === 'video_url'): ?>
              <?php
                $url = $m['content'];
                if(preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/)([a-zA-Z0-9_-]+)/', $url, $ym)){
                    $url = 'https://www.youtube.com/embed/' . $ym[1];
                }
              ?>
              <div class="ratio ratio-16x9 mb-3">
                <iframe src="<?php echo htmlspecialchars($url); ?>" allowfullscreen></iframe>
              </div>
            <?php elseif($m['type'] === 'video_file'): ?>
              <video controls class="w-100 mb-3">
                <source src="<?php echo BASE_URL . htmlspecialchars($m['content']); ?>">
              </video>
            <?php elseif($m['type'] === 'notes'): ?>
              <a href="<?php echo BASE_URL . htmlspecialchars($m['content']); ?>"
                 class="btn btn-outline-primary mb-3" target="_blank">📄 Download / View Notes</a>
            <?php elseif($m['type'] === 'notes_url'): ?>
              <a href="<?php echo htmlspecialchars($m['content']); ?>"
                 class="btn btn-outline-primary mb-3" target="_blank">🔗 Open Notes</a>
            <?php endif; ?>

            <?php if($role === 'student' && $enrolled): ?>
            <form method="POST">
              <?php csrf_field(); ?>
              <input type="hidden" name="material_id" value="<?php echo $m['id']; ?>">
              <button type="submit" name="toggle_material"
                      class="btn btn-sm <?php echo $is_done ? 'btn-success' : 'btn-outline-success'; ?>">
                <?php echo $is_done ? '✓ Completed' : 'Mark as Complete'; ?>
              </button>
            </form>
            <?php endif; ?>
          </div>
        </div>
      </div>
    <?php endwhile; ?>
    </div>
    <?php elseif($role === 'student' && $enrolled): ?>
      <div class="alert alert-info mt-3">No materials added yet for this course.</div>
    <?php endif; ?>
  </div>

  <div class="col-md-4">
    <div class="card p-4 shadow-sm">
      <h5 class="mb-3">Get Started</h5>
      <?php if($role === 'student'): ?>
        <?php if($enrolled): ?>
          <div class="alert alert-success py-2">✅ You are enrolled!</div>
          <a href="my_courses.php" class="btn btn-primary w-100 mb-2">My Courses</a>
        <?php else: ?>
          <?php if(!empty($course['fees']) && $course['fees'] > 0): ?>
            <a href="payment.php?course_id=<?php echo $course['id']; ?>"
               class="btn btn-success w-100 mb-2">
              💳 Pay & Enroll — ₹<?php echo number_format($course['fees'],2); ?>
            </a>
          <?php else: ?>
            <a href="enroll.php?course_id=<?php echo $course['id']; ?>"
               class="btn btn-success w-100 mb-2"
               onclick="return confirm('Enroll in <?php echo htmlspecialchars($course['title']); ?>?')">
              🆓 Enroll for Free
            </a>
          <?php endif; ?>
        <?php endif; ?>
      <?php elseif(in_array($role, ['admin','trainer'])): ?>
        <a href="edit_course.php?id=<?php echo $course['id']; ?>" class="btn btn-warning w-100 mb-2">Edit Course</a>
        <a href="course_progress.php" class="btn btn-info w-100 mb-2">📈 View Progress</a>
      <?php endif; ?>
      <?php if(!empty($course['fees']) && $course['fees'] > 0): ?>
        <div class="alert alert-secondary py-2 text-center">💰 ₹<?php echo number_format($course['fees'],2); ?></div>
      <?php else: ?>
        <div class="alert alert-success py-2 text-center">🆓 Free Course</div>
      <?php endif; ?>
      <a href="view_courses.php" class="btn btn-outline-secondary w-100">← All Courses</a>
    </div>
  </div>
</div>

<?php include("includes/footer.php"); ?>
