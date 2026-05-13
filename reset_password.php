<?php
session_start();
include("config/db.php");
include("includes/csrf.php");

$token   = trim($_GET['token'] ?? '');
$success = $error = "";
$valid   = false;
$email   = '';

if($token){
    $stmt = $conn->prepare("SELECT * FROM password_resets WHERE token=? AND used=0 AND expires_at > NOW()");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $reset = $stmt->get_result()->fetch_assoc();
    if($reset){
        $valid = true;
        $email = $reset['email'];
    } else {
        $error = "This reset link is invalid or has expired.";
    }
}

if(isset($_POST['reset']) && $valid){
    csrf_verify();
    $new     = $_POST['new_password'];
    $confirm = $_POST['confirm_password'];

    if(strlen($new) < 8){
        $error = "Password must be at least 8 characters.";
    } elseif(!preg_match('/[A-Z]/', $new) || !preg_match('/[0-9]/', $new)){
        $error = "Password must contain at least one uppercase letter and one number.";
    } elseif($new !== $confirm){
        $error = "Passwords do not match.";
    } else {
        $hash = password_hash($new, PASSWORD_DEFAULT);
        $upd  = $conn->prepare("UPDATE users SET password=?, remember_token=NULL WHERE email=?");
        $upd->bind_param("ss", $hash, $email);
        $upd->execute();

        // Mark token as used
        $mark = $conn->prepare("UPDATE password_resets SET used=1 WHERE token=?");
        $mark->bind_param("s", $token);
        $mark->execute();

        header("Location: login.php?reset=1"); exit();
    }
}
?>
<?php include("includes/header.php"); ?>

<div class="row justify-content-center">
  <div class="col-md-5">
    <h3 class="mb-4">Reset Password</h3>

    <?php if($error): ?>
      <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>

    <?php if($valid): ?>
    <div class="card shadow-sm">
      <div class="card-body p-4">
        <p class="text-muted">Resetting password for: <strong><?php echo htmlspecialchars($email); ?></strong></p>
        <form method="POST">
          <?php csrf_field(); ?>
          <div class="mb-3">
            <label>New Password</label>
            <input type="password" name="new_password" class="form-control" minlength="8" required>
          </div>
          <div class="mb-3">
            <label>Confirm New Password</label>
            <input type="password" name="confirm_password" class="form-control" minlength="8" required>
          </div>
          <button type="submit" name="reset" class="btn btn-success w-100">Reset Password</button>
        </form>
      </div>
    </div>
    <?php else: ?>
      <div class="alert alert-danger"><?php echo $error ?: "Invalid reset link."; ?></div>
      <a href="forgot_password.php" class="btn btn-primary">Request New Link</a>
    <?php endif; ?>
  </div>
</div>

<?php include("includes/footer.php"); ?>
