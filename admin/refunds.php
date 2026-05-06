<?php
session_start();
include("../config/db.php");
include("../includes/auth.php");
include("../includes/csrf.php");
require_role('admin');

// Auto-create tables
$conn->query("CREATE TABLE IF NOT EXISTS refund_requests (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    user_id         INT NOT NULL,
    course_id       INT NOT NULL,
    enrollment_id   INT NOT NULL,
    payment_id      INT DEFAULT NULL,
    amount          DECIMAL(10,2) NOT NULL DEFAULT 0,
    reason          TEXT DEFAULT NULL,
    status          ENUM('pending','processing','completed','rejected') DEFAULT 'pending',
    requested_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    processed_at    TIMESTAMP NULL DEFAULT NULL
)");

$conn->query("CREATE TABLE IF NOT EXISTS messages (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    user_id     INT NOT NULL,
    title       VARCHAR(200) NOT NULL,
    body        TEXT NOT NULL,
    is_read     TINYINT(1) DEFAULT 0,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

$success = $error = "";

// Process refund action
if(isset($_POST['process_refund'])){
    csrf_verify();
    $refund_id = intval($_POST['refund_id']);
    $action    = $_POST['action']; // 'processing', 'completed', 'rejected'
    $note      = trim($_POST['admin_note'] ?? '');

    // Fetch refund details
    $rStmt = $conn->prepare("
        SELECT rr.*, u.name AS student_name, c.title AS course_title
        FROM refund_requests rr
        JOIN users u ON rr.user_id = u.id
        JOIN courses c ON rr.course_id = c.id
        WHERE rr.id = ?
    ");
    $rStmt->bind_param("i", $refund_id);
    $rStmt->execute();
    $refund = $rStmt->get_result()->fetch_assoc();

    if($refund){
        // Update refund status
        $upd = $conn->prepare("UPDATE refund_requests SET status=?, processed_at=NOW() WHERE id=?");
        $upd->bind_param("si", $action, $refund_id);
        $upd->execute();

        // Send message to student
        $now = date('d M Y, h:i A');
        if($action === 'processing'){
            $title = "Refund Processing — " . $refund['course_title'];
            $body  = "Dear " . $refund['student_name'] . ",\n\nYour refund request for \"" . $refund['course_title'] . "\" is now being processed.\n\nRefund Amount: ₹" . number_format($refund['amount'], 2) . "\nStatus: Processing\nDate: $now\n\n" . ($note ? "Admin Note: $note" : "You will receive the refund within 5-7 business days.");
        } elseif($action === 'completed'){
            $title = "Refund Completed — " . $refund['course_title'];
            $body  = "Dear " . $refund['student_name'] . ",\n\nYour refund for \"" . $refund['course_title'] . "\" has been successfully completed.\n\nRefund Amount: ₹" . number_format($refund['amount'], 2) . "\nCompleted On: $now\n\n" . ($note ? "Admin Note: $note" : "The amount has been credited back to your original payment method.");
        } else {
            $title = "Refund Request Rejected — " . $refund['course_title'];
            $body  = "Dear " . $refund['student_name'] . ",\n\nUnfortunately, your refund request for \"" . $refund['course_title'] . "\" has been rejected.\n\nDate: $now\n\n" . ($note ? "Reason: $note" : "Please contact admin for more details.");
        }

        $mIns = $conn->prepare("INSERT INTO messages (user_id, title, body) VALUES (?,?,?)");
        $mIns->bind_param("iss", $refund['user_id'], $title, $body);
        $mIns->execute();

        $success = "Refund status updated and student notified.";
    }
}

// Fetch all refund requests
$refunds = $conn->query("
    SELECT rr.*, u.name AS student_name, u.email, c.title AS course_title
    FROM refund_requests rr
    JOIN users u ON rr.user_id = u.id
    JOIN courses c ON rr.course_id = c.id
    ORDER BY FIELD(rr.status,'pending','processing','completed','rejected'), rr.requested_at DESC
");
?>
<?php include("../includes/header.php"); ?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <h3>💰 Refund Requests</h3>
  <a href="dashboard.php" class="btn btn-secondary">← Back</a>
</div>

<?php if($success): ?>
  <div class="alert alert-success alert-dismissible fade show"><?php echo $success; ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>

<?php if($refunds->num_rows > 0): ?>
<div class="table-responsive">
<table class="table table-bordered table-hover">
  <thead class="table-dark">
    <tr><th>#</th><th>Student</th><th>Course</th><th>Amount</th><th>Reason</th><th>Requested</th><th>Status</th><th>Action</th></tr>
  </thead>
  <tbody>
  <?php $sno=1; while($r = $refunds->fetch_assoc()): ?>
  <tr class="<?php echo $r['status']==='pending'?'table-warning':($r['status']==='completed'?'table-success':($r['status']==='rejected'?'table-danger':'')); ?>">
    <td><?php echo $sno++; ?></td>
    <td><?php echo htmlspecialchars($r['student_name']); ?><br><small class="text-muted"><?php echo htmlspecialchars($r['email']); ?></small></td>
    <td><?php echo htmlspecialchars($r['course_title']); ?></td>
    <td><?php echo $r['amount'] > 0 ? '₹'.number_format($r['amount'],2) : '<span class="badge bg-success">Free</span>'; ?></td>
    <td><?php echo htmlspecialchars($r['reason'] ?: '—'); ?></td>
    <td><?php echo date('d M Y, h:i A', strtotime($r['requested_at'])); ?></td>
    <td>
      <span class="badge <?php
        echo $r['status']==='pending'?'bg-warning text-dark':
            ($r['status']==='processing'?'bg-info':
            ($r['status']==='completed'?'bg-success':'bg-danger'));
      ?>">
        <?php echo ucfirst($r['status']); ?>
      </span>
    </td>
    <td>
      <?php if(in_array($r['status'], ['pending','processing'])): ?>
      <button class="btn btn-sm btn-primary" data-bs-toggle="modal"
              data-bs-target="#refundModal<?php echo $r['id']; ?>">
        Update
      </button>

      <!-- Modal -->
      <div class="modal fade" id="refundModal<?php echo $r['id']; ?>" tabindex="-1">
        <div class="modal-dialog">
          <div class="modal-content">
            <form method="POST">
              <?php csrf_field(); ?>
              <input type="hidden" name="refund_id" value="<?php echo $r['id']; ?>">
              <div class="modal-header">
                <h5 class="modal-title">Update Refund — <?php echo htmlspecialchars($r['student_name']); ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
              </div>
              <div class="modal-body">
                <p><strong>Course:</strong> <?php echo htmlspecialchars($r['course_title']); ?></p>
                <p><strong>Amount:</strong> ₹<?php echo number_format($r['amount'],2); ?></p>
                <div class="mb-3">
                  <label class="fw-bold">Update Status</label>
                  <select name="action" class="form-select">
                    <option value="processing">🔄 Mark as Processing</option>
                    <option value="completed">✅ Mark as Completed</option>
                    <option value="rejected">❌ Reject Refund</option>
                  </select>
                </div>
                <div class="mb-3">
                  <label>Note to Student (optional)</label>
                  <textarea name="admin_note" class="form-control" rows="3"
                            placeholder="e.g. Refund credited to your account..."></textarea>
                </div>
              </div>
              <div class="modal-footer">
                <button type="submit" name="process_refund" class="btn btn-primary">Send & Update</button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
              </div>
            </form>
          </div>
        </div>
      </div>
      <?php else: ?>
        <span class="text-muted small">Processed<br><?php echo $r['processed_at'] ? date('d M Y', strtotime($r['processed_at'])) : ''; ?></span>
      <?php endif; ?>
    </td>
  </tr>
  <?php endwhile; ?>
  </tbody>
</table>
</div>
<?php else: ?>
  <div class="alert alert-info">No refund requests yet.</div>
<?php endif; ?>

<?php include("../includes/footer.php"); ?>
