<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Training Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<?php
if(!defined('BASE_URL')) include_once __DIR__ . '/base.php';
?>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
    <div class="container">
        <a class="navbar-brand fw-bold" href="<?php echo BASE_URL; ?>">🎓 TMS</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navMenu">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item"><a class="nav-link" href="<?php echo BASE_URL; ?>">Home</a></li>
                <?php if(isset($_SESSION['user'])): ?>
                    <li class="nav-item"><a class="nav-link" href="<?php echo BASE_URL; ?>dashboard.php">Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?php echo BASE_URL; ?>profile.php">👤 Profile</a></li>
                    <li class="nav-item"><span class="nav-link text-white-50">
                        <?php echo htmlspecialchars($_SESSION['user']); ?> (<?php echo ucfirst($_SESSION['role']); ?>)
                    </span></li>
                    <li class="nav-item">
                        <a class="nav-link btn btn-outline-danger px-3 ms-1"
                           href="<?php echo BASE_URL; ?>logout.php">Logout</a>
                    </li>
                <?php else: ?>
                    <li class="nav-item"><a class="nav-link" href="<?php echo BASE_URL; ?>login.php">Login</a></li>
                    <li class="nav-item ms-2">
                        <a class="btn btn-primary px-4" href="<?php echo BASE_URL; ?>register.php">Register</a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>
<div class="container">
