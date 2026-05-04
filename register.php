<?php
session_start();
include("config/db.php");
include("includes/csrf.php");
require_once("config/salesforce.php");

$success = $error = "";
$preselect = in_array($_GET['role'] ?? '', ['student','trainer']) ? $_GET['role'] : 'student';

if(isset($_POST['submit'])){
    csrf_verify();
    $name     = trim($_POST['name']);
    $email    = trim($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role     = $_POST['role'];

    // Hard block admin self-registration
    if($role === 'admin'){
        $error = "Admin accounts cannot be self-registered.";
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
            if($stmt->execute()){
                $success = "Registered successfully! <a href='login.php?role=$role'>Login here</a>.";

                // Push new user to Salesforce as a Lead
                $nameParts = explode(' ', $name, 2);
                $firstName = $nameParts[0];
                $lastName  = isset($nameParts[1]) ? $nameParts[1] : $nameParts[0];
                $company   = ucfirst($role) . ' - Training Management System';
                sf_create_lead($firstName, $lastName, $email, '', $company);

            } else {
                $error = "Registration failed. Try again.";
            }
        }
    }
}
?>
<?php include("includes/header.php"); ?>

<div class="row justify-content-center">
  <div class="col-md-5">
    <h3 class="text-center mb-4">Create an Account</h3>

    <!-- Role Tabs -->
    <ul class="nav nav-pills nav-justified mb-4">
      <li class="nav-item">
        <a class="nav-link <?php echo $preselect=='student'?'active':''; ?>"
           href="register.php?role=student">🎓 Student</a>
      </li>
      <li class="nav-item">
        <a class="nav-link <?php echo $preselect=='trainer'?'active':''; ?>"
           href="register.php?role=trainer">👨‍🏫 Trainer</a>
      </li>
    </ul>

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

    <div class="card shadow-sm">
      <div class="card-body p-4">
        <form method="POST">
          <?php csrf_field(); ?>
          <input type="hidden" name="role" value="<?php echo htmlspecialchars($preselect); ?>">
          <div class="mb-3">
            <label>Full Name</label>
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
          <button type="submit" name="submit"
            class="btn btn-<?php echo $preselect=='trainer'?'success':'primary'; ?> w-100">
            Register as <?php echo ucfirst($preselect); ?>
          </button>
        </form>
      </div>
    </div>

    <p class="text-center mt-3">Already have an account?
      <a href="login.php?role=<?php echo $preselect; ?>">Login</a>
    </p>
  </div>
</div>

<?php include("includes/footer.php"); ?>
