<?php
session_start();
include("config/db.php");
include("includes/auth.php");
include("includes/csrf.php");
require_role('admin');

if(isset($_GET['resolve'])){
    csrf_verify();
    $id    = intval($_GET['resolve']);
    $reply = trim($_POST['reply'] ?? '');
    $stmt  = $conn->prepare("UPDATE inquiries SET status='resolved', admin_reply=?, resolved_at=NOW() WHERE id=?");
    $stmt->bind_param("si", $reply, $id);
    $stmt->execute();
    header("Location: manage_inquiries.php?done=1"); exit();
}

$result = $conn->query("
    SELECT inquiries.*, users.name AS student_name, users.email
    FROM inquiries
    JOIN users ON inquiries.user_id = users.id
    ORDER BY inquiries.created_at DESC
");
?>
<?php include("includes/header.php"); ?>

<h3>Manage Student Inquiries</h3>

<?php if(isset($_GET['done'])): ?>
  <div class="alert alert-success alert-dismissible fade show">
    Inquiry marked as resolved.<button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
<?php endif; ?>

<div class="table-responsive">
<table class="table table-bordered mt-3">
  <thead class="table-dark">
    <tr><th>Student</th><th>Email</th><th>Subject</th><th>Message</th><th>Status</th><th>Date</th><th>Action</th></tr>
  </thead>
  <tbody>
  <?php if($result->num_rows > 0): ?>
    <?php while($row = $result->fetch_assoc()): ?>
    <tr class="<?php echo $row['status']=='resolved' ? 'table-success' : ''; ?>">
      <td><?php echo htmlspecialchars($row['student_name']); ?></td>
      <td><?php echo htmlspecialchars($row['email']); ?></td>
      <td><?php echo htmlspecialchars($row['subject']); ?></td>
      <td><?php echo htmlspecialchars($row['message']); ?></td>
      <td><span class="badge <?php echo $row['status']=='resolved' ? 'bg-success' : 'bg-warning text-dark'; ?>">
        <?php echo ucfirst($row['status']); ?>
      </span></td>
      <td><?php echo $row['created_at']; ?></td>
      <td>
        <?php if($row['status']=='pending'): ?>
          <button class="btn btn-sm btn-success" data-bs-toggle="modal"
                  data-bs-target="#replyModal<?php echo $row['id']; ?>">Reply & Resolve</button>
          <!-- Reply Modal -->
          <div class="modal fade" id="replyModal<?php echo $row['id']; ?>" tabindex="-1">
            <div class="modal-dialog">
              <div class="modal-content">
                <form method="POST" action="manage_inquiries.php?resolve=<?php echo $row['id']; ?>">
                  <?php csrf_field(); ?>
                  <div class="modal-header"><h5 class="modal-title">Reply to Inquiry</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                  <div class="modal-body">
                    <p><strong>Subject:</strong> <?php echo htmlspecialchars($row['subject']); ?></p>
                    <p><strong>Message:</strong> <?php echo htmlspecialchars($row['message']); ?></p>
                    <label>Your Reply</label>
                    <textarea name="reply" class="form-control" rows="4" required></textarea>
                  </div>
                  <div class="modal-footer">
                    <button type="submit" class="btn btn-success">Send & Resolve</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                  </div>
                </form>
              </div>
            </div>
          </div>
        <?php else: ?>
          <span class="text-muted small"><?php echo htmlspecialchars($row['admin_reply'] ?? 'Resolved'); ?></span>
        <?php endif; ?>
      </td>
    </tr>
    <?php endwhile; ?>
  <?php else: ?>
    <tr><td colspan="7" class="text-center">No inquiries found.</td></tr>
  <?php endif; ?>
  </tbody>
</table>
</div>
<a href="dashboard.php" class="btn btn-secondary">Back</a>

<?php include("includes/footer.php"); ?>
