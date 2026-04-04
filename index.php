<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Training Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .hero {
            background: linear-gradient(135deg, #1a1a2e, #16213e, #0f3460);
            color: white;
            padding: 100px 0 80px;
        }
        .feature-card {
            border: none;
            border-radius: 12px;
            transition: transform 0.2s;
            box-shadow: 0 2px 12px rgba(0,0,0,0.08);
        }
        .feature-card:hover { transform: translateY(-5px); }
        .feature-icon { font-size: 2.5rem; }
    </style>
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
        <a class="navbar-brand fw-bold" href="index.php">🎓 TMS</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navMenu">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item"><a class="nav-link" href="#features">Features</a></li>
                <li class="nav-item"><a class="nav-link" href="#about">About</a></li>
                <?php if(isset($_SESSION['user_id'])): ?>
                    <li class="nav-item"><a class="nav-link btn btn-outline-light px-3 ms-2" href="dashboard.php">Dashboard</a></li>
                <?php else: ?>
                    <li class="nav-item"><a class="nav-link" href="login.php">Login</a></li>
                    <li class="nav-item ms-2"><a class="btn btn-primary px-4" href="register.php">Register</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<!-- Hero Section -->
<section class="hero text-center">
    <div class="container">
        <h1 class="display-4 fw-bold mb-3">Training Management System</h1>
        <p class="lead mb-4 text-white-50">Manage students, courses, enrollments, and track progress — all in one place.</p>
        <?php if(isset($_SESSION['user_id'])): ?>
            <a href="dashboard.php" class="btn btn-primary btn-lg px-5">Go to Dashboard</a>
        <?php else: ?>
            <a href="register.php" class="btn btn-primary btn-lg px-5 me-3">Get Started</a>
            <a href="login.php" class="btn btn-outline-light btn-lg px-5">Login</a>
        <?php endif; ?>
    </div>
</section>

<!-- Features Section -->
<section class="py-5 bg-light" id="features">
    <div class="container">
        <h2 class="text-center fw-bold mb-5">What You Can Do</h2>
        <div class="row g-4">
            <div class="col-md-4">
                <div class="card feature-card p-4 text-center h-100">
                    <div class="feature-icon mb-3">📚</div>
                    <h5 class="fw-bold">Course Management</h5>
                    <p class="text-muted">Admins and trainers can create and manage training courses with duration and fees.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card feature-card p-4 text-center h-100">
                    <div class="feature-icon mb-3">✅</div>
                    <h5 class="fw-bold">Easy Enrollment</h5>
                    <p class="text-muted">Students can browse available courses and enroll with a single click.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card feature-card p-4 text-center h-100">
                    <div class="feature-icon mb-3">📈</div>
                    <h5 class="fw-bold">Progress Tracking</h5>
                    <p class="text-muted">Trainers can update student progress and monitor completion status in real time.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card feature-card p-4 text-center h-100">
                    <div class="feature-icon mb-3">🎓</div>
                    <h5 class="fw-bold">Certificate Generation</h5>
                    <p class="text-muted">Certificates are automatically unlocked when a student completes a course.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card feature-card p-4 text-center h-100">
                    <div class="feature-icon mb-3">💬</div>
                    <h5 class="fw-bold">Student Inquiries (CRM)</h5>
                    <p class="text-muted">Students can submit inquiries and admins can track and resolve them efficiently.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card feature-card p-4 text-center h-100">
                    <div class="feature-icon mb-3">🔐</div>
                    <h5 class="fw-bold">Role-Based Access</h5>
                    <p class="text-muted">Separate panels for Admin, Trainer, and Student with appropriate permissions.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- About Section -->
<section class="py-5" id="about">
    <div class="container text-center" style="max-width:650px">
        <h2 class="fw-bold mb-3">About This Project</h2>
        <p class="text-muted">This Training Management System is a web-based application built with PHP, MySQL, and Bootstrap. It is designed to simplify and automate the daily operations of a training institute — from student registration to course completion.</p>
        <a href="register.php" class="btn btn-primary mt-3 px-5">Join Now</a>
    </div>
</section>

<!-- Footer -->
<footer class="bg-dark text-white-50 text-center py-3 mt-4">
    <small>© <?php echo date('Y'); ?> Training Management System &mdash; BCA Final Year Project</small>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
