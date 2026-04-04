<?php
// Generate CSRF token and store in session
function csrf_token() {
    if(empty($_SESSION['csrf_token'])){
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Verify submitted token matches session token
function csrf_verify() {
    if(!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']){
        http_response_code(403);
        die("Invalid request. <a href='javascript:history.back()'>Go back</a>");
    }
}

// Output hidden input field
function csrf_field() {
    echo '<input type="hidden" name="csrf_token" value="' . csrf_token() . '">';
}
?>
