<?php
session_start();
include("config/db.php");
include("includes/auth.php");
include("includes/csrf.php");
require_login();

$uid = $_SESSION['user_id'];
$success = $error = "";

// Fetch current data
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $uid);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// Handle profile update
if(isset($_POST['update_profile'])){
    csrf_verify();
    $name    = trim($_POST['name']);
    $phone   = trim($_POST['phone']);
    $address = trim($_POST['address']);
    $gender  = $_POST['gender'] ?? '';

    // Validate input lengths
    if(strlen($name) < 2 || strlen($name) > 100){
        $error = "Name must be between 2 and 100 characters.";
    } elseif(strlen($phone) > 20){
        $error = "Phone number is too long.";
    } elseif(!in_array($gender, ['', 'male', 'female', 'other'])){
        $error = "Invalid gender selection.";
    } else {
        $stmt = $conn->prepare("UPDATE users SET name=?, phone=?, address=?, gender=? WHERE id=?");
        $stmt->bind_param("ssssi", $name, $phone, $address, $gender, $uid);
        if($stmt->execute()){
            $_SESSION['user'] = $name;
            $success = "Profile updated successfully!";
            $stmt2 = $conn->prepare("SELECT * FROM users WHERE id=?");
            $stmt2->bind_param("i", $uid);
            $stmt2->execute();
            $user = $stmt2->get_result()->fetch_assoc();
        } else {
            $error = "Update failed.";
        }
    }
}

// Handle password change
if(isset($_POST['change_password'])){
    csrf_verify();
    $current  = $_POST['current_password'];
    $new      = $_POST['new_password'];
    $confirm  = $_POST['confirm_password'];

    if(!password_verify($current, $user['password'])){
        $error = "Current password is incorrect.";
    } elseif($new !== $confirm){
        $error = "New passwords do not match.";
    } elseif(strlen($new) < 8){
        $error = "Password must be at least 8 characters.";
    } elseif(!preg_match('/[A-Z]/', $new) || !preg_match('/[0-9]/', $new)){
        $error = "Password must contain at least one uppercase letter and one number.";
    } else {
        $hash = password_hash($new, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET password=? WHERE id=?");
        $stmt->bind_param("si", $hash, $uid);
        $success = $stmt->execute() ? "Password changed successfully!" : "Failed to change password.";
    }
}
?>
<?php include("includes/header.php"); ?>

<div class="row justify-content-center">
  <div class="col-md-7">
    <h3 class="mb-4">👤 My Profile</h3>

    <?php if($success): ?>
      <div class="alert alert-success alert-dismissible fade show"><?php echo $success; ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    <?php endif; ?>
    <?php if($error): ?>
      <div class="alert alert-danger alert-dismissible fade show"><?php echo $error; ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    <?php endif; ?>

    <!-- Profile Info -->
    <div class="card mb-4">
      <div class="card-header bg-dark text-white">Profile Information</div>
      <div class="card-body">
        <form method="POST">
          <?php csrf_field(); ?>
          <div class="mb-3">
            <label>Full Name</label>
            <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($user['name']); ?>" required>
          </div>
          <div class="mb-3">
            <label>Email <span class="text-muted small">(cannot be changed)</span></label>
            <input type="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" disabled>
          </div>
          <div class="mb-3">
            <label>Role</label>
            <input type="text" class="form-control" value="<?php echo ucfirst($user['role']); ?>" disabled>
          </div>
          <div class="mb-3">
            <label>Phone</label>
            <input type="text" name="phone" class="form-control" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
          </div>
          <div class="mb-3">
            <label>Gender</label>
            <select name="gender" class="form-select">
              <option value="">-- Select --</option>
              <option value="male"   <?php echo ($user['gender']??'')==='male'   ? 'selected':''; ?>>Male</option>
              <option value="female" <?php echo ($user['gender']??'')==='female' ? 'selected':''; ?>>Female</option>
              <option value="other"  <?php echo ($user['gender']??'')==='other'  ? 'selected':''; ?>>Other</option>
            </select>
          </div>
          <div class="mb-3">
            <label>Address</label>
            <textarea name="address" class="form-control" rows="2"><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
          </div>
          <button type="submit" name="update_profile" class="btn btn-primary">Save Changes</button>
          <a href="dashboard.php" class="btn btn-secondary ms-2">Back</a>
        </form>
      </div>
    </div>

    <!-- Change Password -->
    <div class="card">
      <div class="card-header bg-dark text-white">Change Password</div>
      <div class="card-body">
        <form method="POST">
          <?php csrf_field(); ?>
          <div class="mb-3">
            <label>Current Password</label>
            <input type="password" name="current_password" class="form-control" required>
          </div>
          <div class="mb-3">
            <label>New Password</label>
            <input type="password" name="new_password" class="form-control" required>
          </div>
          <div class="mb-3">
            <label>Confirm New Password</label>
            <input type="password" name="confirm_password" class="form-control" required>
          </div>
          <button type="submit" name="change_password" class="btn btn-warning">Change Password</button>
        </form>
      </div>
    </div>

  </div>
</div>

<?php include("includes/footer.php"); ?>
