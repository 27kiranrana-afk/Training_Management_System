<?php
// Call this at the top of any protected page
function require_login() {
    $timeout = 1800; // 30 minutes
    if(!isset($_SESSION['user_id'])){
        header("Location: login.php"); exit();
    }
    if(isset($_SESSION['last_active']) && (time() - $_SESSION['last_active']) > $timeout){
        session_unset();
        session_destroy();
        header("Location: login.php?timeout=1"); exit();
    }
    $_SESSION['last_active'] = time();
}

function require_role(...$roles) {
    require_login();
    if(!in_array($_SESSION['role'], $roles)){
        header("Location: dashboard.php"); exit();
    }
}
?>
