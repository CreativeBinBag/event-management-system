<?php
require_once 'includes/functions.php';
require_once 'includes/auth.php';

//start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_SESSION['user_id'])) {
    error_log(sprintf(
        "User logout - ID: %d, Username: %s, Time: %s",
        $_SESSION['user_id'],
        $_SESSION['username'],
        date('Y-m-d H:i:s')
    ));
}

$auth = new Auth();
$auth->logout();

$_SESSION['success_message'] = "You have been successfully logged out.";

redirect('login.php');
?>