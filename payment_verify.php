<?php
session_start();
include("config/db.php");
include("includes/auth.php");
include("includes/csrf.php");
require_once("config/razorpay.php");
require_role('student');

csrf_verify();

$user_id   = $_SESSION['user_id'];
$course_id = intval($_SESSION['rzp_course_id'] ?? 0);
$order_id  = $_SESSION['rzp_order_id'] ?? '';

$payment_id = trim($_POST['razorpay_payment_id'] ?? '');
$rzp_order  = trim($_POST['razorpay_order_id']   ?? '');
$signature  = trim($_POST['razorpay_signature']  ?? '');

// Validate all fields present
if (!$payment_id || !$rzp_order || !$signature || !$course_id || !$order_id) {
    header("Location: view_courses.php?error=invalid_payment");
    exit();
}

// Verify signature
if (!rzp_verify_signature($order_id, $payment_id, $signature)) {
    header("Location: view_courses.php?error=payment_failed");
    exit();
}

// Check not already enrolled
$chk = $conn->prepare("SELECT id FROM enrollments WHERE user_id=? AND course_id=?");
$chk->bind_param("ii", $user_id, $course_id);
$chk->execute();
$chk->store_result();

if ($chk->num_rows === 0) {
    // Enroll the student
    $enroll = $conn->prepare("INSERT INTO enrollments (user_id, course_id, progress) VALUES (?, ?, 0)");
    $enroll->bind_param("ii", $user_id, $course_id);
    $enroll->execute();
}

// Save payment record
$amount = floatval($_SESSION['rzp_amount'] ?? 0);
$ins = $conn->prepare("INSERT IGNORE INTO payments (user_id, course_id, razorpay_order_id, razorpay_payment_id, amount) VALUES (?,?,?,?,?)");
$ins->bind_param("iissd", $user_id, $course_id, $order_id, $payment_id, $amount);
$ins->execute();

// Clear session payment data
unset($_SESSION['rzp_order_id'], $_SESSION['rzp_course_id'], $_SESSION['rzp_amount']);

header("Location: payment_success.php?course_id=$course_id");
exit();
