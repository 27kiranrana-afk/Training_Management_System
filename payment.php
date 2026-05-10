<?php
session_start();
include("config/db.php");
include("includes/auth.php");
include("includes/csrf.php");
require_once("config/razorpay.php");
require_role('student');

$user_id   = $_SESSION['user_id'];
$course_id = intval($_GET['course_id'] ?? 0);

if ($course_id <= 0) { header("Location: view_courses.php"); exit(); }

// Fetch course
$stmt = $conn->prepare("SELECT * FROM courses WHERE id = ? AND is_active = 1");
$stmt->bind_param("i", $course_id);
$stmt->execute();
$course = $stmt->get_result()->fetch_assoc();
if (!$course) { header("Location: view_courses.php"); exit(); }

// Already enrolled?
$chk = $conn->prepare("SELECT id FROM enrollments WHERE user_id=? AND course_id=?");
$chk->bind_param("ii", $user_id, $course_id);
$chk->execute();
$chk->store_result();
if ($chk->num_rows > 0) { header("Location: my_courses.php"); exit(); }

// Free course — enroll directly
if ($course['fees'] <= 0) { header("Location: enroll.php?course_id=$course_id"); exit(); }

// Fetch student info
$uStmt = $conn->prepare("SELECT name, email FROM users WHERE id=?");
$uStmt->bind_param("i", $user_id);
$uStmt->execute();
$user = $uStmt->get_result()->fetch_assoc();

// Create Razorpay order
$receipt = 'TMS_' . $user_id . '_' . $course_id . '_' . time();
$order   = rzp_create_order($course['fees'], $receipt);

if (!$order) {
    die('<div class="alert alert-danger m-4">Payment gateway error. Please try again later.</div>');
}

// Store order in session for verification
$_SESSION['rzp_order_id']  = $order['id'];
$_SESSION['rzp_course_id'] = $course_id;
$_SESSION['rzp_amount']    = $course['fees'];
?>
<?php include("includes/header.php"); ?>

<div class="row justify-content-center mt-4">
  <div class="col-md-6">
    <div class="card shadow-sm p-4">
      <h4 class="mb-3">💳 Complete Payment</h4>

      <div class="mb-3 p-3 bg-light rounded">
        <strong><?php echo htmlspecialchars($course['title']); ?></strong><br>
        <span class="text-muted">Duration: <?php echo htmlspecialchars($course['duration']); ?></span><br>
        <span class="fs-5 fw-bold text-success mt-2 d-block">₹<?php echo number_format($course['fees'], 2); ?></span>
      </div>

      <div class="alert alert-info py-2">
        <small>🔒 Secure payment powered by Razorpay.</small>
      </div>

      <button id="rzp-pay-btn" class="btn btn-success w-100 btn-lg">
        Pay ₹<?php echo number_format($course['fees'], 2); ?> & Enroll
      </button>

      <a href="course_detail.php?id=<?php echo $course_id; ?>" class="btn btn-outline-secondary w-100 mt-2">
        Cancel
      </a>
    </div>

    <!-- Test card info — remove this block in production -->
    <?php if(strpos(RZP_KEY_ID, 'rzp_test_') === 0): ?>
    <div class="card mt-3 p-3 border-warning">
      <h6 class="text-warning">🧪 Test Mode — Use these details:</h6>
      <small>
        <strong>Card:</strong> 4111 1111 1111 1111<br>
        <strong>Expiry:</strong> Any future date (e.g. 12/26)<br>
        <strong>CVV:</strong> Any 3 digits<br>
        <strong>OTP:</strong> 1234
      </small>
    </div>
    <?php endif; ?>
  </div>
</div>

<!-- Hidden form for payment verification -->
<form id="payment-form" method="POST" action="payment_verify.php">
  <?php csrf_field(); ?>
  <input type="hidden" name="razorpay_payment_id" id="razorpay_payment_id">
  <input type="hidden" name="razorpay_order_id"   id="razorpay_order_id">
  <input type="hidden" name="razorpay_signature"  id="razorpay_signature">
</form>

<!-- Razorpay Checkout JS -->
<script src="https://checkout.razorpay.com/v1/checkout.js"></script>
<script>
var options = {
    key:         '<?php echo RZP_KEY_ID; ?>',
    amount:      '<?php echo intval($course['fees'] * 100); ?>',
    currency:    'INR',
    name:        'Training Management System',
    description: '<?php echo htmlspecialchars($course['title']); ?>',
    order_id:    '<?php echo $order['id']; ?>',
    prefill: {
        name:  '<?php echo htmlspecialchars($user['name']); ?>',
        email: '<?php echo htmlspecialchars($user['email']); ?>'
    },
    theme: { color: '#0d6efd' },
    handler: function(response) {
        document.getElementById('razorpay_payment_id').value = response.razorpay_payment_id;
        document.getElementById('razorpay_order_id').value   = response.razorpay_order_id;
        document.getElementById('razorpay_signature').value  = response.razorpay_signature;
        document.getElementById('payment-form').submit();
    },
    modal: {
        ondismiss: function() {
            alert('Payment cancelled. You can try again.');
        }
    }
};

document.getElementById('rzp-pay-btn').onclick = function(e) {
    var rzp = new Razorpay(options);
    rzp.open();
    e.preventDefault();
};
</script>

<?php include("includes/footer.php"); ?>
