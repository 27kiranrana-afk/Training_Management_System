<?php
session_start();
include("config/db.php");
include("includes/csrf.php");

$error = "";

if(isset($_POST['login'])){
    csrf_verify();
    $email    = trim($_POST['email']);
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if($result->num_rows > 0){
        $row = $result->fetch_assoc();
        if(password_verify($password, $row['password'])){
            $_SESSION['user_id']    = $row['id'];
            $_SESSION['user']       = $row['name'];
            $_SESSION['role']       = $row['role'];
            $_SESSION['last_active'] = time();
            header("Location: dashboard.php");
            exit();
        }
    }
    $error = "Invalid email or password.";
}
?>
<?php include("includes/header.php"); ?>

<div class="row justify-content-center">
  <div class="col-md-4">
    <h3 class="mb-3">Login</h3>

    <?php if(isset($_GET['timeout'])): ?>
      <div class="alert alert-warning">Session expired. Please log in again.</div>
    <?php endif; ?>

    <?php if($error): ?>
      <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?php echo $error; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    <?php endif; ?>

    <form method="POST">
      <?php csrf_field(); ?>
      <div class="mb-3">
        <label>Email</label>
        <input type="email" name="email" class="form-control" required>
      </div>
      <div class="mb-3">
        <label>Password</label>
        <input type="password" name="password" class="form-control" required>
      </div>
      <button type="submit" name="login" class="btn btn-primary w-100">Login</button>
    </form>
    <p class="mt-3">Don't have an account? <a href="register.php">Register</a></p>
  </div>
</div>

<?php include("includes/footer.php"); ?>
