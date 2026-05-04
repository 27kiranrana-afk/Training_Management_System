<?php
include("config/db.php");

// Drop and recreate admin
$conn->query("DELETE FROM users WHERE role='admin'");

$name     = "Admin";
$email    = "27kiranrana@gmail.com";
$password = password_hash("87654321", PASSWORD_DEFAULT);
$role     = "admin";

$stmt = $conn->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
$stmt->bind_param("ssss", $name, $email, $password, $role);

if($stmt->execute()){
    $verify = $conn->query("SELECT password FROM users WHERE email='27kiranrana@gmail.com'");
    $row    = $verify->fetch_assoc();
    $ok     = password_verify("87654321", $row['password']);

    echo "<h3 style='color:green'>✅ Admin created!</h3>";
    echo "<p>Email: 27kiranrana@gmail.com</p>";
    echo "<p>Password verify test: <b>" . ($ok ? "✅ PASS" : "❌ FAIL") . "</b></p>";

    // Self-delete
    @unlink(__FILE__);
    echo !file_exists(__FILE__)
        ? "<p style='color:green'>🗑️ create_admin.php deleted automatically.</p>"
        : "<p style='color:orange'>⚠️ Please delete create_admin.php manually.</p>";

    echo "<br><a href='login.php?role=admin' style='font-size:18px'>→ Go to Admin Login</a>";
} else {
    echo "<p style='color:red'>Insert failed: " . $conn->error . "</p>";
}
?>
