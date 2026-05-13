-- ============================================================
--  Restore Sample Data for Training Management System
--  Password for ALL users: Password1
-- ============================================================

-- Trainers
INSERT IGNORE INTO users (name, email, password, role, is_active) VALUES
('John Trainer',   'trainer@tms.com',         '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'trainer', 1),
('Manisha Rawat',  'manisharawat@gmail.com',   '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'trainer', 1);

-- Students
INSERT IGNORE INTO users (name, email, password, role, is_active) VALUES
('Kiran Rana',     '27kiranrana@gmail.com',    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', 1),
('Butola',         'butola@gmail.com',          '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', 1),
('Dhairya Gautam', 'dhairygautam@gmail.com',   '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', 1),
('Gusain',         'gusain@gmail.com',          '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', 1),
('Hima Negi',      'himaninegi@gmail.com',      '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', 1),
('Mehra',          'mehra@gamil.com',           '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', 1),
('Negi',           'negi@gmail.com',            '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', 1),
('Priya Rawat',    'priyarawat@gmail.com',      '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', 1),
('Shalu',          'shalu@gmail.com',           '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student', 1);

-- Courses (created by trainer id=2)
INSERT IGNORE INTO courses (title, description, duration, fees, is_active, created_by) VALUES
('PHP & MySQL Basics',        'Learn PHP from scratch with MySQL database.',          '2 months',  2999.00, 1, 2),
('Web Design with Bootstrap', 'Build responsive websites using Bootstrap 5.',         '1 month',   1999.00, 1, 2),
('Python for Beginners',      'Introduction to Python programming.',                  '3 months',  3499.00, 1, 2),
('JavaScript Essentials',     'Master JavaScript fundamentals and DOM manipulation.', '2 months',  2499.00, 1, 2),
('React JS Fundamentals',     'Build modern web apps with React.',                    '3 months',  4999.00, 1, 3);

-- Update admin email to match login.php config
UPDATE users SET email='admin@tms.com' WHERE role='admin';
