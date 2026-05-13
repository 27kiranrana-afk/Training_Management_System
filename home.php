<?php
session_start();
include("config/db.php");
include("includes/base.php");

// If already logged in, go to dashboard
if(isset($_SESSION['user_id'])){
    header("Location: dashboard.php"); exit();
}

// Get stats for display
$total_courses  = $conn->query("SELECT COUNT(*) FROM courses WHERE is_active=1")->fetch_row()[0];
$total_students = $conn->query("SELECT COUNT(*) FROM users WHERE role='student'")->fetch_row()[0];
$total_trainers = $conn->query("SELECT COUNT(*) FROM users WHERE role='trainer'")->fetch_row()[0];

// Get featured courses
$featured = $conn->query("SELECT courses.*, users.name AS trainer_name FROM courses LEFT JOIN users ON courses.created_by=users.id WHERE courses.is_active=1 ORDER BY courses.id DESC LIMIT 3");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Training Management System — Learn. Grow. Succeed.</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: #0d6efd;
            --dark: #0a0a0a;
        }

        body { font-family: 'Segoe UI', sans-serif; }

        /* Hero */
        .hero {
            background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 50%, #084298 100%);
            color: white;
            padding: 100px 0 80px;
            position: relative;
            overflow: hidden;
        }
        .hero::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -10%;
            width: 600px;
            height: 600px;
            background: rgba(255,255,255,0.05);
            border-radius: 50%;
        }
        .hero::after {
            content: '';
            position: absolute;
            bottom: -30%;
            left: -5%;
            width: 400px;
            height: 400px;
            background: rgba(255,255,255,0.03);
            border-radius: 50%;
        }
        .hero h1 { font-size: 3.2rem; font-weight: 800; line-height: 1.2; }
        .hero p  { font-size: 1.2rem; opacity: 0.9; }

        /* Navbar */
        .navbar-brand { font-size: 1.4rem; font-weight: 700; }

        /* Stats */
        .stat-card {
            background: white;
            border-radius: 16px;
            padding: 30px;
            text-align: center;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            transition: transform 0.2s;
        }
        .stat-card:hover { transform: translateY(-4px); }
        .stat-number { font-size: 2.8rem; font-weight: 800; color: #0d6efd; }

        /* Features */
        .feature-icon {
            width: 64px; height: 64px;
            background: #e8f0fe;
            border-radius: 16px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.8rem;
            margin-bottom: 16px;
        }

        /* Course cards */
        .course-card {
            border: none;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            transition: transform 0.2s, box-shadow 0.2s;
            height: 100%;
        }
        .course-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 8px 30px rgba(0,0,0,0.15);
        }
        .course-badge {
            position: absolute; top: 16px; right: 16px;
        }

        /* Roles section */
        .role-card {
            border-radius: 20px;
            padding: 40px 30px;
            text-align: center;
            color: white;
            transition: transform 0.2s;
        }
        .role-card:hover { transform: translateY(-4px); }
        .role-icon { font-size: 3rem; margin-bottom: 16px; }

        /* CTA */
        .cta-section {
            background: linear-gradient(135deg, #0d6efd, #084298);
            color: white;
            padding: 80px 0;
        }

        /* Footer */
        footer { background: #0a0a0a; color: #aaa; padding: 40px 0 20px; }
        footer a { color: #aaa; text-decoration: none; }
        footer a:hover { color: white; }
    </style>
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top">
    <div class="container">
        <a class="navbar-brand fw-bold" href="home.php">🎓 TMS</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navMenu">
            <ul class="navbar-nav me-auto">
                <li class="nav-item"><a class="nav-link" href="#features">Features</a></li>
                <li class="nav-item"><a class="nav-link" href="#courses">Courses</a></li>
                <li class="nav-item"><a class="nav-link" href="#roles">Who It's For</a></li>
            </ul>
            <div class="d-flex gap-2">
                <a href="login.php" class="btn btn-outline-light px-4">Login</a>
                <a href="register.php" class="btn btn-primary px-4">Register</a>
            </div>
        </div>
    </div>
</nav>

<!-- Hero -->
<section class="hero">
    <div class="container position-relative" style="z-index:1">
        <div class="row align-items-center">
            <div class="col-lg-7">
                <h1>Learn Skills That<br>Shape Your Future</h1>
                <p class="mt-3 mb-4">A complete Training Management System for students, trainers, and administrators. Enroll in courses, track progress, earn certificates, and grow your career.</p>
                <div class="d-flex gap-3 flex-wrap">
                    <a href="register.php?role=student" class="btn btn-light btn-lg px-5 fw-bold">
                        🎓 Get Started Free
                    </a>
                    <a href="#courses" class="btn btn-outline-light btn-lg px-5">
                        Browse Courses
                    </a>
                </div>
                <div class="mt-4 d-flex gap-4 flex-wrap">
                    <small>✅ Free & Paid Courses</small>
                    <small>✅ Progress Tracking</small>
                    <small>✅ Certificates</small>
                    <small>✅ Secure Payments</small>
                </div>
            </div>
            <div class="col-lg-5 d-none d-lg-flex justify-content-center mt-4 mt-lg-0">
                <div style="font-size:10rem; opacity:0.15;">🎓</div>
            </div>
        </div>
    </div>
</section>

<!-- Stats -->
<section class="py-5 bg-light">
    <div class="container">
        <div class="row g-4 justify-content-center">
            <div class="col-md-3 col-6">
                <div class="stat-card">
                    <div class="stat-number"><?php echo $total_courses; ?>+</div>
                    <div class="text-muted fw-semibold">Courses Available</div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="stat-card">
                    <div class="stat-number"><?php echo $total_students; ?>+</div>
                    <div class="text-muted fw-semibold">Active Students</div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="stat-card">
                    <div class="stat-number"><?php echo $total_trainers; ?>+</div>
                    <div class="text-muted fw-semibold">Expert Trainers</div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="stat-card">
                    <div class="stat-number">100%</div>
                    <div class="text-muted fw-semibold">Satisfaction Rate</div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Features -->
<section class="py-5" id="features">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="fw-bold">Everything You Need to Learn</h2>
            <p class="text-muted">A powerful platform built for modern training needs</p>
        </div>
        <div class="row g-4">
            <div class="col-md-4">
                <div class="feature-icon">📚</div>
                <h5 class="fw-bold">Rich Course Content</h5>
                <p class="text-muted">Access video lectures, downloadable notes, and structured learning materials organized by your trainer.</p>
            </div>
            <div class="col-md-4">
                <div class="feature-icon">📈</div>
                <h5 class="fw-bold">Progress Tracking</h5>
                <p class="text-muted">Track your learning journey with real-time progress bars. Mark materials complete as you go.</p>
            </div>
            <div class="col-md-4">
                <div class="feature-icon">🎓</div>
                <h5 class="fw-bold">Digital Certificates</h5>
                <p class="text-muted">Earn a verifiable certificate automatically when you complete a course with 100% progress.</p>
            </div>
            <div class="col-md-4">
                <div class="feature-icon">💳</div>
                <h5 class="fw-bold">Secure Payments</h5>
                <p class="text-muted">Pay for premium courses securely via Razorpay. Supports cards, UPI, netbanking and wallets.</p>
            </div>
            <div class="col-md-4">
                <div class="feature-icon">💬</div>
                <h5 class="fw-bold">Inquiry System</h5>
                <p class="text-muted">Submit questions and get responses from admins directly in your inbox. Stay connected.</p>
            </div>
            <div class="col-md-4">
                <div class="feature-icon">🔒</div>
                <h5 class="fw-bold">Secure & Reliable</h5>
                <p class="text-muted">Role-based access control, CSRF protection, bcrypt passwords, and session management built in.</p>
            </div>
        </div>
    </div>
</section>

<!-- Featured Courses -->
<section class="py-5 bg-light" id="courses">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="fw-bold">Featured Courses</h2>
            <p class="text-muted">Start learning with our most popular courses</p>
        </div>
        <div class="row g-4">
            <?php while($course = $featured->fetch_assoc()): ?>
            <div class="col-md-4">
                <div class="card course-card position-relative">
                    <div class="card-body p-4">
                        <div class="mb-3" style="font-size:2.5rem;">
                            <?php
                            $icons = ['PHP'=>'🐘','Python'=>'🐍','JavaScript'=>'⚡','React'=>'⚛️','Web'=>'🌐','Java'=>'☕','Data'=>'📊','Machine'=>'🤖'];
                            $icon = '📚';
                            foreach($icons as $k=>$v) if(stripos($course['title'],$k)!==false){ $icon=$v; break; }
                            echo $icon;
                            ?>
                        </div>
                        <span class="badge course-badge <?php echo $course['fees']>0?'bg-primary':'bg-success'; ?>">
                            <?php echo $course['fees']>0 ? '₹'.number_format($course['fees'],0) : 'Free'; ?>
                        </span>
                        <h5 class="fw-bold"><?php echo htmlspecialchars($course['title']); ?></h5>
                        <p class="text-muted small"><?php echo htmlspecialchars(mb_strimwidth($course['description']??'No description.',0,80,'...')); ?></p>
                        <div class="d-flex justify-content-between align-items-center mt-3">
                            <small class="text-muted">⏱ <?php echo htmlspecialchars($course['duration']); ?></small>
                            <small class="text-muted">👨‍🏫 <?php echo htmlspecialchars($course['trainer_name']??'TMS'); ?></small>
                        </div>
                    </div>
                    <div class="card-footer bg-transparent border-0 pb-4 px-4">
                        <a href="register.php?role=student" class="btn btn-primary w-100">Enroll Now</a>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
        <div class="text-center mt-4">
            <a href="login.php" class="btn btn-outline-primary btn-lg px-5">View All Courses →</a>
        </div>
    </div>
</section>

<!-- Who It's For -->
<section class="py-5" id="roles">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="fw-bold">Built for Everyone</h2>
            <p class="text-muted">Whether you're learning, teaching, or managing — TMS has you covered</p>
        </div>
        <div class="row g-4">
            <div class="col-md-4">
                <div class="role-card bg-primary">
                    <div class="role-icon">🎓</div>
                    <h4 class="fw-bold">Students</h4>
                    <p class="opacity-75">Enroll in courses, track your progress, submit inquiries, and download certificates upon completion.</p>
                    <a href="register.php?role=student" class="btn btn-light mt-2 px-4">Join as Student</a>
                </div>
            </div>
            <div class="col-md-4">
                <div class="role-card bg-success">
                    <div class="role-icon">👨‍🏫</div>
                    <h4 class="fw-bold">Trainers</h4>
                    <p class="opacity-75">Create and manage courses, upload learning materials, and monitor student progress in real time.</p>
                    <a href="register.php?role=trainer" class="btn btn-light mt-2 px-4">Join as Trainer</a>
                </div>
            </div>
            <div class="col-md-4">
                <div class="role-card bg-dark">
                    <div class="role-icon">🛡️</div>
                    <h4 class="fw-bold">Administrators</h4>
                    <p class="opacity-75">Manage users, oversee all courses and enrollments, handle refunds, and view detailed analytics.</p>
                    <a href="login.php?role=admin" class="btn btn-light mt-2 px-4">Admin Login</a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- CTA -->
<section class="cta-section">
    <div class="container text-center">
        <h2 class="fw-bold mb-3">Ready to Start Learning?</h2>
        <p class="opacity-75 mb-4 fs-5">Join our platform today and take the first step towards your goals.</p>
        <div class="d-flex gap-3 justify-content-center flex-wrap">
            <a href="register.php?role=student" class="btn btn-light btn-lg px-5 fw-bold">Create Free Account</a>
            <a href="login.php" class="btn btn-outline-light btn-lg px-5">Sign In</a>
        </div>
    </div>
</section>

<!-- Footer -->
<footer>
    <div class="container">
        <div class="row g-4 mb-4">
            <div class="col-md-4">
                <h5 class="text-white fw-bold">🎓 TMS</h5>
                <p class="small">Training Management System — A complete platform for online learning, course management, and certification.</p>
            </div>
            <div class="col-md-2">
                <h6 class="text-white">Platform</h6>
                <ul class="list-unstyled small">
                    <li><a href="#features">Features</a></li>
                    <li><a href="#courses">Courses</a></li>
                    <li><a href="#roles">Roles</a></li>
                </ul>
            </div>
            <div class="col-md-2">
                <h6 class="text-white">Account</h6>
                <ul class="list-unstyled small">
                    <li><a href="login.php">Login</a></li>
                    <li><a href="register.php">Register</a></li>
                    <li><a href="forgot_password.php">Forgot Password</a></li>
                </ul>
            </div>
            <div class="col-md-4">
                <h6 class="text-white">Powered By</h6>
                <p class="small">
                    💳 Razorpay — Secure Payments<br>
                    ☁️ Salesforce — CRM Integration<br>
                    🔒 bcrypt — Password Security
                </p>
            </div>
        </div>
        <hr style="border-color:#333">
        <p class="text-center small mb-0">© <?php echo date('Y'); ?> Training Management System. All rights reserved.</p>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
