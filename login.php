<?php
session_start();
include("config/db.php");
include("includes/csrf.php");

if(isset($_SESSION['user_id'])){ header("Location: dashboard.php"); exit(); }

// Handle "remember me" auto-login
if(!isset($_SESSION['user_id']) && isset($_COOKIE['remember_token'])){
    $token = $_COOKIE['remember_token'];
    $stmt  = $conn->prepare("SELECT * FROM users WHERE remember_token = ? AND is_active = 1");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    if($row){
        $_SESSION['user_id']     = $row['id'];
        $_SESSION['user']        = $row['name'];
        $_SESSION['role']        = $row['role'];
        $_SESSION['last_active'] = time();
        header("Location: dashboard.php"); exit();
    }
}

define('ADMIN_EMAIL',    getenv('ADMIN_EMAIL') ?: '27kiranrana@gmail.com');
define('MAX_ATTEMPTS',   5);
define('LOCKOUT_MINUTES', 15);

$error      = "";
$active_tab = $_GET['role'] ?? 'student';
$locked_until = null;

function get_attempts($conn, $email, $ip){
    $since = date('Y-m-d H:i:s', strtotime('-' . LOCKOUT_MINUTES . ' minutes'));
    $stmt  = $conn->prepare("SELECT COUNT(*) FROM login_attempts WHERE (email=? OR ip_address=?) AND attempted_at > ?");
    $stmt->bind_param("sss", $email, $ip, $since);
    $stmt->execute();
    return $stmt->get_result()->fetch_row()[0];
}

function log_attempt($conn, $email, $ip){
    $stmt = $conn->prepare("INSERT INTO login_attempts (email, ip_address) VALUES (?, ?)");
    $stmt->bind_param("ss", $email, $ip);
    $stmt->execute();
}

function clear_attempts($conn, $email, $ip){
    $stmt = $conn->prepare("DELETE FROM login_attempts WHERE email=? OR ip_address=?");
    $stmt->bind_param("ss", $email, $ip);
    $stmt->execute();
}

if(isset($_POST['login'])){
    csrf_verify();
    $email         = strtolower(trim($_POST['email']));
    $password      = $_POST['password'];
    $expected_role = $_POST['expected_role'];
    $remember      = isset($_POST['remember']);
    $active_tab    = $expected_role;
    $ip            = $_SERVER['REMOTE_ADDR'];

    // 1. Admin email guard
    if($expected_role === 'admin' && $email !== strtolower(ADMIN_EMAIL)){
        $error = "Unauthorized. Admin access is restricted.";
    }
    // 2. Brute force check
    elseif(get_attempts($conn, $email, $ip) >= MAX_ATTEMPTS){
        $error = "Too many failed attempts. Please wait " . LOCKOUT_MINUTES . " minutes before trying again.";
    }
    else {
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();

        if($row && password_verify($password, $row['password'])){
            // 3. Disabled account check
            if(!$row['is_active']){
                $error = "Your account has been disabled. Please contact the administrator.";
                log_attempt($conn, $email, $ip);
            }
            // 4. Role mismatch check
            elseif($row['role'] !== $expected_role){
                $error = "This account is not registered as a " . ucfirst($expected_role) . ".";
                log_attempt($conn, $email, $ip);
            }
            else {
                // ✅ Successful login
                clear_attempts($conn, $email, $ip);

                $_SESSION['user_id']     = $row['id'];
                $_SESSION['user']        = $row['name'];
                $_SESSION['role']        = $row['role'];
                $_SESSION['last_active'] = time();

                // Remember me — set cookie for 30 days
                if($remember){
                    $token = bin2hex(random_bytes(32));
                    $stmt2 = $conn->prepare("UPDATE users SET remember_token=? WHERE id=?");
                    $stmt2->bind_param("si", $token, $row['id']);
                    $stmt2->execute();
                    $is_https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
                    setcookie('remember_token', $token, time() + (30 * 24 * 3600), '/', '', $is_https, true);
                }

                header("Location: dashboard.php"); exit();
            }
        } else {
            log_attempt($conn, $email, $ip);
            $attempts_left = MAX_ATTEMPTS - get_attempts($conn, $email, $ip);
            $error = "Invalid email or password." . ($attempts_left > 0 ? " ($attempts_left attempts remaining)" : "");
        }
    }
}
?>
<?php include("includes/header.php"); ?>

<div class="row justify-content-center">
  <div class="col-md-5">
    <h3 class="text-center mb-4">Login to TMS</h3>

    <?php if(isset($_GET['timeout'])): ?>
      <div class="alert alert-warning">⏱ Session expired. Please log in again.</div>
    <?php endif; ?>
    <?php if(isset($_GET['disabled'])): ?>
      <div class="alert alert-danger">🚫 Your account has been disabled. Contact the administrator.</div>
    <?php endif; ?>
    <?php if(isset($_GET['reset'])): ?>
      <div class="alert alert-success">✅ Password reset successful. You can now log in.</div>
    <?php endif; ?>
    <?php if($error): ?>
      <div class="alert alert-danger alert-dismissible fade show">
        <?php echo htmlspecialchars($error); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    <?php endif; ?>

    <!-- Role Tabs -->
    <ul class="nav nav-pills nav-justified mb-4">
      <li class="nav-item">
        <a class="nav-link <?php echo $active_tab=='student'?'active':''; ?>" href="login.php?role=student">🎓 Student</a>
      </li>
      <li class="nav-item">
        <a class="nav-link <?php echo $active_tab=='trainer'?'active':''; ?>" href="login.php?role=trainer">👨‍🏫 Trainer</a>
      </li>
      <li class="nav-item">
        <a class="nav-link <?php echo $active_tab=='admin'?'active':''; ?>" href="login.php?role=admin">🛡️ Admin</a>
      </li>
    </ul>

    <div class="card shadow-sm">
      <div class="card-body p-4">
        <?php
          $labels = [
            'student' => ['color'=>'primary', 'title'=>'Student Login'],
            'trainer' => ['color'=>'success', 'title'=>'Trainer Login'],
            'admin'   => ['color'=>'danger',  'title'=>'Admin Login'],
          ];
          $tab = $labels[$active_tab] ?? $labels['student'];
        ?>
        <h5 class="card-title text-<?php echo $tab['color']; ?> mb-3"><?php echo $tab['title']; ?></h5>

        <form method="POST">
          <?php csrf_field(); ?>
          <input type="hidden" name="expected_role" value="<?php echo htmlspecialchars($active_tab); ?>">
          <div class="mb-3">
            <label class="form-label">Email</label>
            <input type="email" name="email" class="form-control" placeholder="Enter your email" required autofocus>
          </div>
          <div class="mb-3">
            <label class="form-label">Password</label>
            <input type="password" name="password" class="form-control" placeholder="Enter your password" required>
          </div>
          <div class="d-flex justify-content-between align-items-center mb-3">
            <div class="form-check">
              <input type="checkbox" name="remember" class="form-check-input" id="remember">
              <label class="form-check-label" for="remember">Remember me</label>
            </div>
            <?php if($active_tab != 'admin'): ?>
              <a href="forgot_password.php" class="text-muted small">Forgot password?</a>
            <?php endif; ?>
          </div>
          <button type="submit" name="login" class="btn btn-<?php echo $tab['color']; ?> w-100">
            Login as <?php echo ucfirst($active_tab); ?>
          </button>
        </form>

        <?php if($active_tab != 'admin'): ?>
          <p class="text-center mt-3 mb-0">
            Don't have an account?
            <a href="register.php?role=<?php echo $active_tab; ?>">Register as <?php echo ucfirst($active_tab); ?></a>
          </p>
        <?php else: ?>
          <p class="text-center mt-3 mb-0 text-muted small">Admin access is restricted.</p>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<?php include("includes/footer.php"); ?>
