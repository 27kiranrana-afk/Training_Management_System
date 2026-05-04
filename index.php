<?php
session_start();
include("config/db.php");

// ── Auto-setup: runs silently on every visit, safe to repeat ──

// 1. Add missing columns to existing tables
$migrations = [
    "ALTER TABLE users ADD COLUMN IF NOT EXISTS phone VARCHAR(20) DEFAULT NULL",
    "ALTER TABLE users ADD COLUMN IF NOT EXISTS address TEXT DEFAULT NULL",
    "ALTER TABLE users ADD COLUMN IF NOT EXISTS profile_pic VARCHAR(255) DEFAULT NULL",
    "ALTER TABLE users ADD COLUMN IF NOT EXISTS is_active TINYINT(1) NOT NULL DEFAULT 1",
    "ALTER TABLE users ADD COLUMN IF NOT EXISTS remember_token VARCHAR(100) DEFAULT NULL",
    "ALTER TABLE users ADD COLUMN IF NOT EXISTS gender ENUM('male','female','other') DEFAULT NULL",
    "ALTER TABLE users ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP",
    "ALTER TABLE courses ADD COLUMN IF NOT EXISTS description TEXT DEFAULT NULL",
    "ALTER TABLE courses ADD COLUMN IF NOT EXISTS fees DECIMAL(10,2) DEFAULT 0.00",
    "ALTER TABLE courses ADD COLUMN IF NOT EXISTS is_active TINYINT(1) NOT NULL DEFAULT 1",
    "ALTER TABLE courses ADD COLUMN IF NOT EXISTS created_by INT DEFAULT NULL",
    "ALTER TABLE enrollments ADD COLUMN IF NOT EXISTS attendance INT NOT NULL DEFAULT 0",
    "ALTER TABLE enrollments ADD COLUMN IF NOT EXISTS total_classes INT NOT NULL DEFAULT 0",
    "ALTER TABLE enrollments ADD COLUMN IF NOT EXISTS status ENUM('enrolled','completed','dropped') DEFAULT 'enrolled'",
    "ALTER TABLE enrollments ADD COLUMN IF NOT EXISTS completed_at TIMESTAMP NULL DEFAULT NULL",
    "ALTER TABLE enrollments ADD COLUMN IF NOT EXISTS enrolled_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP",
    "ALTER TABLE inquiries ADD COLUMN IF NOT EXISTS admin_reply TEXT DEFAULT NULL",
    "ALTER TABLE inquiries ADD COLUMN IF NOT EXISTS resolved_at TIMESTAMP NULL DEFAULT NULL",
];
foreach($migrations as $sql){ @$conn->query($sql); }

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

// 4. Seed admin if not exists
$check = $conn->query("SELECT id FROM users WHERE role='admin' LIMIT 1");
if($check->num_rows === 0){
    $hash = password_hash("87654321", PASSWORD_DEFAULT);
    $stmt = $conn->prepare("INSERT INTO users (name, email, password, role) VALUES ('Admin','27kiranrana@gmail.com',?,'admin')");
    $stmt->bind_param("s", $hash);
    $stmt->execute();
}

// 4. Redirect
if(isset($_SESSION['user_id'])){
    header("Location: dashboard.php");
} else {
    header("Location: login.php");
}
exit();
?>
