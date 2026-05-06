<?php
session_start();
include("config/db.php");
include("includes/auth.php");
include("includes/csrf.php");
require_role('admin');

// Auto-create messages table
$conn->query("CREATE TABLE IF NOT EXISTS messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    body TEXT NOT NULL,
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

if(isset($_GET['resolve'])){
    csrf_verify();
    $id    = intval($_GET['resolve']);
    $reply = trim($_POST['reply'] ?? '');

    // Fetch inquiry details to send message
    $inqStmt = $conn->prepare("SELECT inquiries.*, users.name FROM inquiries JOIN users ON inquiries.user_id = users.id WHERE inquiries.id=?");
    $inqStmt->bind_param("i", $id);
    $inqStmt->execute();
    $inq = $inqStmt->get_result()->fetch_assoc();

    $stmt = $conn->prepare("UPDATE inquiries SET status='resolved', admin_reply=?, resolved_at=NOW() WHERE id=?");
    $stmt->bind_param("si", $reply, $id);
    $stmt->execute();

    // Send message to student
    if($inq){
        $now   = date('d M Y, h:i A');
        $title = "Inquiry Resolved — " . $inq['subject'];
        $body  = "Dear " . $inq['name'] . ",\n\nYour inquiry has been resolved.\n\nSubject: " . $inq['subject'] . "\n\nAdmin Reply: " . $reply . "\n\nResolved on: $now";
        $mIns  = $conn->prepare("INSERT INTO messages (user_id, title, body) VALUES (?,?,?)");
        $mIns->bind_param("iss", $inq['user_id'], $title, $body);
        $mIns->execute();
    }

    header("Location: manage_inquiries.php?done=1"); exit();
}

// Filter
$filter = $_GET['filter'] ?? 'all';
$where  = $filter === 'pending' ? "WHERE inquiries.status='pending'" : ($filter === 'resolved' ? "WHERE inquiries.status='resolved'" : "");

$result = $conn->query("
    SELECT inquiries.*, users.name AS student_name, users.email
    FROM inquiries
    JOIN users ON inquiries.user_id = users.id
    $where
    ORDER BY FIELD(inquiries.status,'pending','resolved'), inquiries.created_at DESC
");

$pending_count  = $conn->query("SELECT COUNT(*) FROM inquiries WHERE status='pending'")->fetch_row()[0];
$resolved_count = $conn->query("SELECT COUNT(*) FROM inquiries WHERE status='resolved'")->fetch_row()[0];
?>
<?php include("includes/header.php"); ?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <h3>💬 Manage Inquiries</h3>
  <a href="admin/dashboard.php" class="btn btn-secondary btn-sm">← Back</a>
</div>

<!-- Filter tabs -->
<ul class="nav nav-pills mb-3">
  <li class="nav-item">
    <a class="nav-link <?php echo $filter==='all'?'active':''; ?>" href="manage_inquiries.php">
      All <span class="badge bg-secondary"><?php echo $pending_count + $resolved_count; ?></span>
    </a>
  </li>
  <li class="nav-item">
    <a class="nav-link <?php echo $filter==='pending'?'active':''; ?>" href="manage_inquiries.php?filter=pending">
      Pending <span class="badge bg-warning text-dark"><?php echo $pending_count; ?></span>
    </a>
  </li>
  <li class="nav-item">
    <a class="nav-link <?php echo $filter==='resolved'?'active':''; ?>" href="manage_inquiries.php?filter=resolved">
      Resolved <span class="badge bg-success"><?php echo $resolved_count; ?></span>
    </a>
  </li>
</ul>

<?php if(isset($_GET['done'])): ?>
  <div class="alert alert-success alert-dismissible fade show">
    ✅ Inquiry resolved and student notified via message.
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
<?php endif; ?>

<div class="table-responsive">
<table class="table table-bordered table-hover">
  <thead class="table-dark">
    <tr><th>#</th><th>Student</th><th>Subject</th><th>Message</th><th>Status</th><th>Date</th><th>Action</th></tr>
  </thead>
  <tbody>
  <?php if($result && $result->num_rows > 0): $sno=1; ?>
    <?php while($row = $result->fetch_assoc()): ?>
    <tr class="<?php echo $row['status']==='resolved' ? 'table-success' : ''; ?>">
      <td><?php echo $sno++; ?></td>
      <td>
        <?php echo htmlspecialchars($row['student_name']); ?>
        <br><small class="text-muted"><?php echo htmlspecialchars($row['email']); ?></small>
      </td>
      <td><?php echo htmlspecialchars($row['subject']); ?></td>
      <td>
        <span title="<?php echo htmlspecialchars($row['message']); ?>">
          <?php echo htmlspecialchars(mb_strimwidth($row['message'], 0, 60, '...')); ?>
        </span>
      </td>
      <td>
        <span class="badge <?php echo $row['status']==='resolved' ? 'bg-success' : 'bg-warning text-dark'; ?>">
          <?php echo ucfirst($row['status']); ?>
        </span>
      </td>
      <td><?php echo date('d M Y', strtotime($row['created_at'])); ?></td>
      <td>
        <?php if($row['status']==='pending'): ?>
          <button class="btn btn-sm btn-success" data-bs-toggle="modal"
                  data-bs-target="#replyModal<?php echo $row['id']; ?>">Reply & Resolve</button>

          <div class="modal fade" id="replyModal<?php echo $row['id']; ?>" tabindex="-1">
            <div class="modal-dialog modal-lg">
              <div class="modal-content">
                <form method="POST" action="manage_inquiries.php?resolve=<?php echo $row['id']; ?>">
                  <?php csrf_field(); ?>
                  <div class="modal-header bg-success text-white">
                    <h5 class="modal-title">Reply to Inquiry</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                  </div>
                  <div class="modal-body">
                    <div class="mb-3 p-3 bg-light rounded">
                      <strong>From:</strong> <?php echo htmlspecialchars($row['student_name']); ?><br>
                      <strong>Subject:</strong> <?php echo htmlspecialchars($row['subject']); ?><br>
                      <strong>Message:</strong> <?php echo htmlspecialchars($row['message']); ?>
                    </div>
                    <label class="fw-bold">Your Reply <span class="text-danger">*</span></label>
                    <textarea name="reply" class="form-control" rows="4"
                              placeholder="Type your reply here..." required></textarea>
                    <small class="text-muted">The student will receive this reply in their message inbox.</small>
                  </div>
                  <div class="modal-footer">
                    <button type="submit" class="btn btn-success">✅ Send & Resolve</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                  </div>
                </form>
              </div>
            </div>
          </div>
        <?php else: ?>
          <small class="text-success">✅ <?php echo htmlspecialchars(mb_strimwidth($row['admin_reply'] ?? 'Resolved', 0, 40, '...')); ?></small>
        <?php endif; ?>
      </td>
    </tr>
    <?php endwhile; ?>
  <?php else: ?>
    <tr><td colspan="7" class="text-center py-3">No inquiries found.</td></tr>
  <?php endif; ?>
  </tbody>
</table>
</div>

<?php include("includes/footer.php"); ?>
