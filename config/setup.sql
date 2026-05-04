-- ============================================================
--  Training Management System — Full Database Schema
--  Import this file in phpMyAdmin: Import > Choose File
-- ============================================================

CREATE DATABASE IF NOT EXISTS training_db
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE training_db;

-- ============================================================
-- 1. USERS
--    Stores login credentials and profile for all roles:
--    admin, trainer, student
-- ============================================================
CREATE TABLE IF NOT EXISTS users (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    name         VARCHAR(100)  NOT NULL,
    email        VARCHAR(150)  NOT NULL UNIQUE,
    password     VARCHAR(255)  NOT NULL,              -- bcrypt hashed
    role         ENUM('admin','trainer','student') NOT NULL DEFAULT 'student',
    phone        VARCHAR(20)   DEFAULT NULL,
    address      TEXT          DEFAULT NULL,
    profile_pic  VARCHAR(255)  DEFAULT NULL,          -- filename of uploaded photo
    is_active    TINYINT(1)    NOT NULL DEFAULT 1,    -- 0 = disabled by admin
    created_at   TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
    updated_at   TIMESTAMP     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- ============================================================
-- 2. COURSES
--    Created by trainers or admin.
--    Tracks title, duration, description, and who created it.
-- ============================================================
CREATE TABLE IF NOT EXISTS courses (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    title        VARCHAR(150)  NOT NULL,
    description  TEXT          DEFAULT NULL,
    duration     VARCHAR(50)   NOT NULL,              -- e.g. "3 months", "40 hours"
    fees         DECIMAL(10,2) DEFAULT 0.00,
    created_by   INT           DEFAULT NULL,          -- FK to users (trainer/admin)
    is_active    TINYINT(1)    NOT NULL DEFAULT 1,    -- 0 = archived
    created_at   TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
    updated_at   TIMESTAMP     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);

-- ============================================================
-- 3. ENROLLMENTS
--    Tracks which student is enrolled in which course,
--    their progress (0-100), attendance, and completion.
-- ============================================================
CREATE TABLE IF NOT EXISTS enrollments (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    user_id         INT           NOT NULL,           -- FK to users (student)
    course_id       INT           NOT NULL,           -- FK to courses
    progress        INT           NOT NULL DEFAULT 0, -- 0 to 100
    attendance      INT           NOT NULL DEFAULT 0, -- number of classes attended
    total_classes   INT           NOT NULL DEFAULT 0, -- total classes held
    status          ENUM('enrolled','completed','dropped') DEFAULT 'enrolled',
    enrolled_at     TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
    completed_at    TIMESTAMP     NULL DEFAULT NULL,  -- set when progress = 100
    UNIQUE KEY unique_enrollment (user_id, course_id),
    FOREIGN KEY (user_id)   REFERENCES users(id)   ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
);

-- ============================================================
-- 4. PROGRESS LOG
--    Every time a trainer updates a student's progress,
--    a record is saved here for audit/history.
-- ============================================================
CREATE TABLE IF NOT EXISTS progress_log (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    enrollment_id INT          NOT NULL,
    updated_by   INT           NOT NULL,              -- FK to users (trainer/admin)
    old_progress INT           NOT NULL DEFAULT 0,
    new_progress INT           NOT NULL DEFAULT 0,
    note         VARCHAR(255)  DEFAULT NULL,          -- optional trainer note
    updated_at   TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (enrollment_id) REFERENCES enrollments(id) ON DELETE CASCADE,
    FOREIGN KEY (updated_by)    REFERENCES users(id)       ON DELETE CASCADE
);

-- ============================================================
-- 5. CERTIFICATES
--    Generated when a student completes a course (progress=100).
--    Stores a unique certificate number for verification.
-- ============================================================
CREATE TABLE IF NOT EXISTS certificates (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    enrollment_id   INT           NOT NULL UNIQUE,    -- one cert per enrollment
    user_id         INT           NOT NULL,
    course_id       INT           NOT NULL,
    certificate_no  VARCHAR(50)   NOT NULL UNIQUE,    -- e.g. TMS-2026-00001
    issued_at       TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (enrollment_id) REFERENCES enrollments(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id)       REFERENCES users(id)       ON DELETE CASCADE,
    FOREIGN KEY (course_id)     REFERENCES courses(id)     ON DELETE CASCADE
);

-- ============================================================
-- 6. INQUIRIES  (CRM)
--    Students submit questions/inquiries.
--    Admin can view, respond, and mark as resolved.
-- ============================================================
CREATE TABLE IF NOT EXISTS inquiries (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    user_id      INT           NOT NULL,              -- FK to users (student)
    subject      VARCHAR(200)  NOT NULL,
    message      TEXT          NOT NULL,
    admin_reply  TEXT          DEFAULT NULL,          -- admin's response
    status       ENUM('pending','resolved') DEFAULT 'pending',
    created_at   TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
    resolved_at  TIMESTAMP     NULL DEFAULT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ============================================================
-- 7. SESSIONS / ACTIVITY LOG  (optional but useful)
--    Tracks last login time per user.
-- ============================================================
CREATE TABLE IF NOT EXISTS activity_log (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    user_id      INT           NOT NULL,
    action       VARCHAR(100)  NOT NULL,              -- e.g. 'login', 'enrolled', 'progress_updated'
    detail       VARCHAR(255)  DEFAULT NULL,
    ip_address   VARCHAR(45)   DEFAULT NULL,
    created_at   TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ============================================================
-- DEFAULT ADMIN ACCOUNT
--    Email:    admin@tms.com
--    Password: admin123  (change after first login!)
-- ============================================================
INSERT IGNORE INTO users (name, email, password, role)
VALUES (
    'Admin',
    'admin@tms.com',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- admin123
    'admin'
);

-- ============================================================
-- SAMPLE DATA (remove in production)
-- ============================================================

-- Sample trainer
INSERT IGNORE INTO users (name, email, password, role)
VALUES (
    'John Trainer',
    'trainer@tms.com',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- admin123
    'trainer'
);

-- Sample student
INSERT IGNORE INTO users (name, email, password, role)
VALUES (
    'Jane Student',
    'student@tms.com',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- admin123
    'student'
);

-- Sample courses (created_by will be set to trainer id = 2)
INSERT IGNORE INTO courses (title, description, duration, fees, created_by) VALUES
('PHP & MySQL Basics',     'Learn PHP from scratch with MySQL database.',      '2 months', 2999.00, 2),
('Web Design with Bootstrap', 'Build responsive websites using Bootstrap 5.', '1 month',  1999.00, 2),
('Python for Beginners',   'Introduction to Python programming.',              '3 months', 3499.00, 2);
