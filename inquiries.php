<?php
session_start();
include("config/db.php");
include("includes/auth.php");
include("includes/csrf.php");
require_once("config/salesforce.php");
require_login();

$success = $error = "";
$user_id = $_SESSION['user_id'];

if(isset($_POST['submit'])){
    csrf_verify();
    $subject = trim($_POST['subject']);
    $message = trim($_POST['message']);

    if(empty($subject) || empty($message)){
        $error = "Subject and message are required.";
    } else {
        $stmt = $conn->prepare("INSERT INTO inquiries (user_id, subject, message) VALUES (?, ?, ?)");
        $stmt->bind_param("iss", $user_id, $subject, $message);
        if($stmt->execute()){
            $success = "Inquiry submitted successfully! We'll get back to you soon.";
            $emailStmt = $conn->prepare("SELECT email FROM users WHERE id = ?");
            $emailStmt->bind_param("i", $user_id);
            $emailStmt->execute();
            $emailRow  = $emailStmt->get_result()->fetch_assoc();
            $userEmail = $emailRow['email'] ?? '';
            sf_create_case($subject, $message, $userEmail);
        } else {
            $error = "Failed to submit. Please try again.";
        }
    }
}

// Fetch inquiry history for this user
$history = $conn->prepare("
    SELECT * FROM inquiries WHERE user_id = ? ORDER BY created_at DESC
");
$history->bind_param("i", $user_id);
$history->execute();
$inquiries = $history->get_result();
?>
<?php include("includes/header.php"); ?>

<div class="row g-4">
  <!-- Submit Form -->
  <div class="col-md-5">
    <div class="card shadow-sm p-4">
      <h4 class="mb-3">💬 Submit an Inquiry</h4>

      <?php if($success): ?>
        <div class="alert alert-success alert-dismissible fade show">
          <?php echo $success; ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
      <?php endif; ?>
      <?php if($error): ?>
        <div class="alert alert-danger alert-dismissible fade show">
          <?php echo $error; ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
      <?php endif; ?>

      <form method="POST" id="inquiryForm">
        <?php csrf_field(); ?>
        <div class="mb-3">
          <label class="form-label fw-bold">Subject <span class="text-danger">*</span></label>
          <input type="text" name="subject" class="form-control" maxlength="200"
                 placeholder="e.g. Course access issue, Payment query..." required>
        </div>
        <div class="mb-3">
          <label class="form-label fw-bold">Message <span class="text-danger">*</span></label>
          <textarea name="message" id="messageArea" class="form-control" rows="5"
                    maxlength="1000" placeholder="Describe your issue in detail..." required></textarea>
          <div class="text-end"><small class="text-muted" id="charCount">0/1000</small></div>
        </div>
        <button type="submit" name="submit" class="btn btn-warning w-100">
          📤 Submit Inquiry
        </button>
        <a href="dashboard.php" class="btn btn-outline-secondary w-100 mt-2">← Back</a>
      </form>
    </div>
  </div>

  <!-- Inquiry History -->
  <div class="col-md-7">
    <h4 class="mb-3">📋 My Inquiry History</h4>
    <?php if($inquiries->num_rows > 0): ?>
      <?php while($inq = $inquiries->fetch_assoc()): ?>
      <div class="card mb-3 <?php echo $inq['status']==='resolved' ? 'border-success' : 'border-warning'; ?>">
        <div class="card-header d-flex justify-content-between align-items-center
                    <?php echo $inq['status']==='resolved' ? 'bg-success text-white' : 'bg-warning text-dark'; ?>">
          <span class="fw-bold"><?php echo htmlspecialchars($inq['subject']); ?></span>
          <span class="badge <?php echo $inq['status']==='resolved' ? 'bg-light text-success' : 'bg-dark'; ?>">
            <?php echo ucfirst($inq['status']); ?>
          </span>
        </div>
        <div class="card-body">
          <p class="mb-2 text-muted small"><?php echo htmlspecialchars($inq['message']); ?></p>
          <?php if($inq['admin_reply']): ?>
            <div class="alert alert-success py-2 mb-1">
              <strong>Admin Reply:</strong> <?php echo htmlspecialchars($inq['admin_reply']); ?>
            </div>
          <?php endif; ?>
          <small class="text-muted">
            Submitted: <?php echo date('d M Y, h:i A', strtotime($inq['created_at'])); ?>
            <?php if($inq['resolved_at']): ?>
              · Resolved: <?php echo date('d M Y', strtotime($inq['resolved_at'])); ?>
            <?php endif; ?>
          </small>
        </div>
      </div>
      <?php endwhile; ?>
    <?php else: ?>
      <div class="alert alert-info">No inquiries submitted yet.</div>
    <?php endif; ?>
  </div>
</div>

<script>
// Character counter
const msgArea = document.getElementById('messageArea');
const charCount = document.getElementById('charCount');
if(msgArea){
  msgArea.addEventListener('input', function(){
    charCount.textContent = this.value.length + '/1000';
    charCount.className = this.value.length > 900 ? 'text-danger' : 'text-muted';
  });
}
</script>

<?php include("includes/footer.php"); ?>
