<?php
session_start();
include("config/db.php");
include("includes/auth.php");

// ── Auto-setup: runs silently on every visit, safe to repeat ──

// 1. Add missing columns to existing tables (compatible with older MySQL)
$migrations = [
    ['users',       'phone',         "ALTER TABLE users ADD COLUMN phone VARCHAR(20) DEFAULT NULL"],
    ['users',       'address',       "ALTER TABLE users ADD COLUMN address TEXT DEFAULT NULL"],
    ['users',       'profile_pic',   "ALTER TABLE users ADD COLUMN profile_pic VARCHAR(255) DEFAULT NULL"],
    ['users',       'is_active',     "ALTER TABLE users ADD COLUMN is_active TINYINT(1) NOT NULL DEFAULT 1"],
    ['users',       'remember_token',"ALTER TABLE users ADD COLUMN remember_token VARCHAR(100) DEFAULT NULL"],
    ['users',       'gender',        "ALTER TABLE users ADD COLUMN gender ENUM('male','female','other') DEFAULT NULL"],
    ['users',       'updated_at',    "ALTER TABLE users ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP"],
    ['courses',     'description',   "ALTER TABLE courses ADD COLUMN description TEXT DEFAULT NULL"],
    ['courses',     'fees',          "ALTER TABLE courses ADD COLUMN fees DECIMAL(10,2) DEFAULT 0.00"],
    ['courses',     'is_active',     "ALTER TABLE courses ADD COLUMN is_active TINYINT(1) NOT NULL DEFAULT 1"],
    ['courses',     'created_by',    "ALTER TABLE courses ADD COLUMN created_by INT DEFAULT NULL"],
    ['enrollments', 'attendance',    "ALTER TABLE enrollments ADD COLUMN attendance INT NOT NULL DEFAULT 0"],
    ['enrollments', 'total_classes', "ALTER TABLE enrollments ADD COLUMN total_classes INT NOT NULL DEFAULT 0"],
    ['enrollments', 'status',        "ALTER TABLE enrollments ADD COLUMN status ENUM('enrolled','completed','dropped') DEFAULT 'enrolled'"],
    ['enrollments', 'completed_at',  "ALTER TABLE enrollments ADD COLUMN completed_at TIMESTAMP NULL DEFAULT NULL"],
    ['enrollments', 'enrolled_at',   "ALTER TABLE enrollments ADD COLUMN enrolled_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP"],
    ['inquiries',   'admin_reply',   "ALTER TABLE inquiries ADD COLUMN admin_reply TEXT DEFAULT NULL"],
    ['inquiries',   'resolved_at',   "ALTER TABLE inquiries ADD COLUMN resolved_at TIMESTAMP NULL DEFAULT NULL"],
];
foreach($migrations as [$table, $column, $sql]){
    $check = $conn->query("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='$table' AND COLUMN_NAME='$column'");
    if($check && $check->fetch_row()[0] == 0){ @$conn->query($sql); }
}

// 2. Create tables that might not exist yet
$tables = [
"CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin','trainer','student') NOT NULL DEFAULT 'student',
    phone VARCHAR(20) DEFAULT NULL,
    address TEXT DEFAULT NULL,
    profile_pic VARCHAR(255) DEFAULT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    remember_token VARCHAR(100) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)",
"CREATE TABLE IF NOT EXISTS courses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(150) NOT NULL,
    description TEXT DEFAULT NULL,
    duration VARCHAR(50) NOT NULL,
    fees DECIMAL(10,2) DEFAULT 0.00,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_by INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)",
"CREATE TABLE IF NOT EXISTS enrollments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    course_id INT NOT NULL,
    progress INT NOT NULL DEFAULT 0,
    attendance INT NOT NULL DEFAULT 0,
    total_classes INT NOT NULL DEFAULT 0,
    status ENUM('enrolled','completed','dropped') DEFAULT 'enrolled',
    completed_at TIMESTAMP NULL DEFAULT NULL,
    enrolled_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_enrollment (user_id, course_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
)",
"CREATE TABLE IF NOT EXISTS inquiries (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    subject VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    admin_reply TEXT DEFAULT NULL,
    status ENUM('pending','resolved') DEFAULT 'pending',
    resolved_at TIMESTAMP NULL DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)",
"CREATE TABLE IF NOT EXISTS progress_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    enrollment_id INT NOT NULL,
    updated_by INT NOT NULL,
    old_progress INT NOT NULL DEFAULT 0,
    new_progress INT NOT NULL DEFAULT 0,
    note VARCHAR(255) DEFAULT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)",
"CREATE TABLE IF NOT EXISTS certificates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    enrollment_id INT NOT NULL UNIQUE,
    user_id INT NOT NULL,
    course_id INT NOT NULL,
    certificate_no VARCHAR(50) NOT NULL UNIQUE,
    issued_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)",
"CREATE TABLE IF NOT EXISTS login_attempts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(150) NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    attempted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)",
"CREATE TABLE IF NOT EXISTS password_resets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(150) NOT NULL,
    token VARCHAR(100) NOT NULL UNIQUE,
    expires_at DATETIME NOT NULL,
    used TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)",
"CREATE TABLE IF NOT EXISTS activity_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    action VARCHAR(100) NOT NULL,
    detail VARCHAR(255) DEFAULT NULL,
    ip_address VARCHAR(45) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)",
"CREATE TABLE IF NOT EXISTS course_materials (
    id INT AUTO_INCREMENT PRIMARY KEY,
    course_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    type ENUM('video_url','video_file','notes','notes_url') NOT NULL,
    content TEXT NOT NULL,
    sort_order INT DEFAULT 0,
    uploaded_by INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
)",
"CREATE TABLE IF NOT EXISTS material_completions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    material_id INT NOT NULL,
    completed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_completion (user_id, material_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (material_id) REFERENCES course_materials(id) ON DELETE CASCADE
)",
];
foreach($tables as $sql){ @$conn->query($sql); }

// 3. Auto-clean orphaned enrollments (courses deleted without cascade)
@$conn->query("DELETE FROM enrollments WHERE course_id NOT IN (SELECT id FROM courses)");
@$conn->query("DELETE FROM enrollments WHERE user_id NOT IN (SELECT id FROM users)");

// 4. Seed admin if not exists — credentials read from environment variables
$check = $conn->query("SELECT id FROM users WHERE role='admin' LIMIT 1");
if($check->num_rows === 0){
    $admin_email    = getenv('ADMIN_EMAIL')    ?: 'admin@tms.com';
    $admin_password = getenv('ADMIN_PASSWORD') ?: 'ChangeMe@' . rand(1000,9999);
    $hash = password_hash($admin_password, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("INSERT INTO users (name, email, password, role) VALUES ('Admin',?,?,'admin')");
    $stmt->bind_param("ss", $admin_email, $hash);
    $stmt->execute();
    error_log("TMS: Admin account created. Email: $admin_email — Set ADMIN_EMAIL and ADMIN_PASSWORD env vars before first run.");
}

// 4. Redirect
if(isset($_SESSION['user_id'])){
    header("Location: dashboard.php");
} else {
    header("Location: home.php");
}
exit();
?>
