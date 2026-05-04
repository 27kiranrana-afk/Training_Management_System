<?php
if(!defined('BASE_URL')) include_once __DIR__ . '/base.php';

function require_login(){
    $timeout = 1800; // 30 minutes

    if(!isset($_SESSION['user_id'])){
        header("Location: " . BASE_URL . "login.php"); exit();
    }

    if(isset($_SESSION['last_active']) && (time() - $_SESSION['last_active']) > $timeout){
        session_unset(); session_destroy();
        header("Location: " . BASE_URL . "login.php?timeout=1"); exit();
    }

    // Check is_active on every request
    global $conn;
    if(isset($conn)){
        $uid  = $_SESSION['user_id'];
        $stmt = $conn->prepare("SELECT is_active FROM users WHERE id = ?");
        $stmt->bind_param("i", $uid);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        if(!$row || !$row['is_active']){
            session_unset(); session_destroy();
            header("Location: " . BASE_URL . "login.php?disabled=1"); exit();
        }
    }

    $_SESSION['last_active'] = time();
}

function require_role(...$roles){
    require_login();
    if(!in_array($_SESSION['role'], $roles)){
        header("Location: " . BASE_URL . "dashboard.php"); exit();
    }
}
?>
