<?php
session_start();
include("config/db.php");
include("includes/base.php");
include("includes/csrf.php");

$success = $error = "";

if(isset($_POST['send'])){
    csrf_verify();
    $email = strtolower(trim($_POST['email']));

    $stmt = $conn->prepare("SELECT id, name, role FROM users WHERE email = ? AND is_active = 1");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();

    // Always show the same message to prevent email enumeration
    $success = "If that email exists, a reset link has been sent.";

    if($user && $user['role'] !== 'admin'){
        // Remove any existing unused tokens for this email
        $del = $conn->prepare("DELETE FROM password_resets WHERE email = ?");
        $del->bind_param("s", $email);
        $del->execute();

        $token   = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

        $ins = $conn->prepare("INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?)");
        $ins->bind_param("sss", $email, $token, $expires);
        $ins->execute();

        // Build reset link using auto-detected BASE_URL (works on localhost and live)
        $reset_link = BASE_URL . 'reset_password.php?token=' . urlencode($token);

        // TODO: Wire up PHPMailer here to email $reset_link to $email
        // The link is intentionally NOT shown to the user or logged for security.
    }
}
?>
<?php include("includes/header.php"); ?>

<div class="row justify-content-center">
  <div class="col-md-5">
    <h3 class="mb-4">Forgot Password</h3>

    <?php if($success): ?>
      <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>
    <?php if($error): ?>
      <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <div class="card shadow-sm">
      <div class="card-body p-4">
        <p class="text-muted">Enter your registered email and we'll send you a password reset link.</p>
        <form method="POST">
          <?php csrf_field(); ?>
          <div class="mb-3">
            <label>Email Address</label>
            <input type="email" name="email" class="form-control" required>
          </div>
          <button type="submit" name="send" class="btn btn-primary w-100">Send Reset Link</button>
        </form>
        <p class="text-center mt-3"><a href="login.php">← Back to Login</a></p>
      </div>
    </div>
  </div>
</div>

<?php include("includes/footer.php"); ?>
