<?php
session_start();
include("config/db.php");
include("includes/auth.php");
require_login();

// Auto-create messages table
$conn->query("CREATE TABLE IF NOT EXISTS messages (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    user_id     INT NOT NULL,
    title       VARCHAR(200) NOT NULL,
    body        TEXT NOT NULL,
    is_read     TINYINT(1) DEFAULT 0,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)");

$user_id = $_SESSION['user_id'];

// Mark message as read
if(isset($_GET['read'])){
    $mid = intval($_GET['read']);
    $upd = $conn->prepare("UPDATE messages SET is_read=1 WHERE id=? AND user_id=?");
    $upd->bind_param("ii", $mid, $user_id);
    $upd->execute();
}

// Mark all as read
if(isset($_GET['read_all'])){
    $upd = $conn->prepare("UPDATE messages SET is_read=1 WHERE user_id=?");
    $upd->bind_param("i", $user_id);
    $upd->execute();
    header("Location: messages.php"); exit();
}

// Fetch all messages for this user
$msgs = $conn->prepare("SELECT * FROM messages WHERE user_id=? ORDER BY created_at DESC");
$msgs->bind_param("i", $user_id);
$msgs->execute();
$messages = $msgs->get_result();

$unread = $conn->prepare("SELECT COUNT(*) FROM messages WHERE user_id=? AND is_read=0");
$unread->bind_param("i", $user_id);
$unread->execute();
$unread_count = $unread->get_result()->fetch_row()[0];
?>
<?php include("includes/header.php"); ?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <h3>📬 My Messages
    <?php if($unread_count > 0): ?>
      <span class="badge bg-danger ms-2"><?php echo $unread_count; ?> new</span>
    <?php endif; ?>
  </h3>
  <?php if($unread_count > 0): ?>
    <a href="messages.php?read_all=1" class="btn btn-outline-secondary btn-sm">Mark all as read</a>
  <?php endif; ?>
</div>

<?php if($messages->num_rows > 0): ?>
  <?php while($msg = $messages->fetch_assoc()): ?>
  <div class="card mb-3 <?php echo !$msg['is_read'] ? 'border-primary' : ''; ?>">
    <div class="card-header d-flex justify-content-between align-items-center
                <?php echo !$msg['is_read'] ? 'bg-primary text-white' : 'bg-light'; ?>">
      <span class="fw-bold">
        <?php if(!$msg['is_read']): ?>
          <span class="badge bg-warning text-dark me-2">New</span>
        <?php endif; ?>
        <?php echo htmlspecialchars($msg['title']); ?>
      </span>
      <small><?php echo date('d M Y, h:i A', strtotime($msg['created_at'])); ?></small>
    </div>
    <div class="card-body">
      <p class="mb-1" style="white-space: pre-line; line-height: 1.7;"><?php echo htmlspecialchars($msg['body']); ?></p>
      <?php if(!$msg['is_read']): ?>
        <a href="messages.php?read=<?php echo $msg['id']; ?>" class="btn btn-sm btn-outline-primary mt-2">Mark as Read</a>
      <?php endif; ?>
    </div>
  </div>
  <?php endwhile; ?>
<?php else: ?>
  <div class="alert alert-info">No messages yet. Messages from admin will appear here.</div>
<?php endif; ?>

<a href="dashboard.php" class="btn btn-secondary">← Back</a>

<?php include("includes/footer.php"); ?>
