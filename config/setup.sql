-- ============================================================
--  Training Management System — Full Database Schema
--  Import this file in phpMyAdmin: Import > Choose File
-- ============================================================

-- NOTE: Do NOT run CREATE DATABASE here on shared hosting (e.g. InfinityFree).
-- The database is already created via the hosting control panel.
-- Just import this file directly into your existing database.

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
-- 8. PERFORMANCE INDEXES
-- ============================================================
CREATE INDEX IF NOT EXISTS idx_users_email       ON users(email);
CREATE INDEX IF NOT EXISTS idx_users_role        ON users(role);
CREATE INDEX IF NOT EXISTS idx_courses_created_by ON courses(created_by);
CREATE INDEX IF NOT EXISTS idx_enrollments_user  ON enrollments(user_id);
CREATE INDEX IF NOT EXISTS idx_enrollments_course ON enrollments(course_id);
CREATE INDEX IF NOT EXISTS idx_enrollments_progress ON enrollments(progress);
CREATE INDEX IF NOT EXISTS idx_inquiries_user    ON inquiries(user_id);
CREATE INDEX IF NOT EXISTS idx_inquiries_status  ON inquiries(status);
CREATE INDEX IF NOT EXISTS idx_activity_user     ON activity_log(user_id);

-- ============================================================
-- DEFAULT ADMIN ACCOUNT
--    IMPORTANT: Change the password hash before importing!
--    Generate a new hash by running this PHP snippet:
--      php -r "echo password_hash('YourStrongPassword', PASSWORD_DEFAULT);"
--    Then replace the hash below with your output.
--
--    Default credentials (CHANGE BEFORE GOING LIVE):
--    Email:    admin@tms.com
--    Password: Change_Me_Now!
-- ============================================================
INSERT IGNORE INTO users (name, email, password, role, is_active)
VALUES (
    'Admin',
    'admin@tms.com',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- REPLACE THIS HASH
    'admin',
    1
);

-- ============================================================
-- SAMPLE DATA — REMOVE BEFORE PRODUCTION DEPLOYMENT
-- Uncomment only for local development/testing
-- ============================================================

-- Sample trainer
-- INSERT IGNORE INTO users (name, email, password, role) VALUES ('John Trainer', 'trainer@tms.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'trainer');
-- Sample student
-- INSERT IGNORE INTO users (name, email, password, role) VALUES ('Jane Student', 'student@tms.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student');
-- Sample courses
-- INSERT IGNORE INTO courses (title, description, duration, fees, created_by) VALUES ('PHP & MySQL Basics', 'Learn PHP from scratch with MySQL database.', '2 months', 2999.00, 2), ('Web Design with Bootstrap', 'Build responsive websites using Bootstrap 5.', '1 month', 1999.00, 2), ('Python for Beginners', 'Introduction to Python programming.', '3 months', 3499.00, 2);
