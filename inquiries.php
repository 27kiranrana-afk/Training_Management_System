<?php
session_start();
include("config/db.php");
include("includes/auth.php");
include("includes/csrf.php");
require_once("config/salesforce.php");
require_login();

$success = $error = "";

if(isset($_POST['submit'])){
    csrf_verify();
    $user_id = $_SESSION['user_id'];
    $subject = trim($_POST['subject']);
    $message = trim($_POST['message']);

    $stmt = $conn->prepare("INSERT INTO inquiries (user_id, subject, message) VALUES (?, ?, ?)");
    $stmt->bind_param("iss", $user_id, $subject, $message);
    if($stmt->execute()){
        $success = "Inquiry submitted! We'll get back to you soon.";

        // Push inquiry to Salesforce as a Case — fetch user email from DB
        $emailStmt = $conn->prepare("SELECT email FROM users WHERE id = ?");
        $emailStmt->bind_param("i", $user_id);
        $emailStmt->execute();
        $emailRow  = $emailStmt->get_result()->fetch_assoc();
        $userEmail = $emailRow['email'] ?? '';
        sf_create_case($subject, $message, $userEmail);

    } else {
        $error = "Failed to submit.";
    }
}
?>
<?php include("includes/header.php"); ?>

<div class="row">
  <div class="col-md-6">
    <h3>Submit an Inquiry</h3>

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

    <form method="POST">
      <?php csrf_field(); ?>
      <div class="mb-3">
        <label>Subject</label>
        <input type="text" name="subject" class="form-control" required>
      </div>
      <div class="mb-3">
        <label>Message</label>
        <textarea name="message" class="form-control" rows="5" required></textarea>
      </div>
      <button type="submit" name="submit" class="btn btn-warning">Submit Inquiry</button>
      <a href="dashboard.php" class="btn btn-secondary ms-2">Back</a>
    </form>
  </div>
</div>

<?php include("includes/footer.php"); ?>
