<?php
session_start();
include("config/db.php");
include("includes/csrf.php");

$success = $error = "";

if(isset($_POST['submit'])){
    csrf_verify();
    $name     = trim($_POST['name']);
    $email    = trim($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role     = $_POST['role'];

    // Prevent self-assigning admin role
    if($role === 'admin'){
        $error = "Admin accounts can only be created by an existing admin.";
    } else {
        $check = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $check->bind_param("s", $email);
        $check->execute();
        $check->store_result();

        if($check->num_rows > 0){
            $error = "Email already registered.";
        } else {
            $stmt = $conn->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $name, $email, $password, $role);
            $success = $stmt->execute() ? "Registered successfully! <a href='login.php'>Login here</a>." : "Registration failed. Try again.";
        }
    }
}
?>
<?php include("includes/header.php"); ?>

<div class="row justify-content-center">
  <div class="col-md-5">
    <h3 class="mb-3">Register</h3>

    <?php if($error): ?>
      <div class="alert alert-danger alert-dismissible fade show">
        <?php echo $error; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    <?php endif; ?>
    <?php if($success): ?>
      <div class="alert alert-success alert-dismissible fade show">
        <?php echo $success; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    <?php endif; ?>

    <form method="POST">
      <?php csrf_field(); ?>
      <div class="mb-3">
        <label>Name</label>
        <input type="text" name="name" class="form-control" required>
      </div>
      <div class="mb-3">
        <label>Email</label>
        <input type="email" name="email" class="form-control" required>
      </div>
      <div class="mb-3">
        <label>Password</label>
        <input type="password" name="password" class="form-control" required>
      </div>
      <div class="mb-3">
        <label>Role</label>
        <select name="role" class="form-select">
          <option value="student">Student</option>
          <option value="trainer">Trainer</option>
        </select>
        <small class="text-muted">Admin accounts are created internally.</small>
      </div>
      <button type="submit" name="submit" class="btn btn-success w-100">Register</button>
    </form>
    <p class="mt-3">Already have an account? <a href="login.php">Login</a></p>
  </div>
</div>

<?php include("includes/footer.php"); ?>
