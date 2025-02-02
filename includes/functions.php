<?php
session_start();

function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

function is_authenticated() {
    return isset($_SESSION['user_id']);
}

function is_admin() {
    return isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true;
}

function redirect($location) {
    header("Location: $location");
    exit();
}

function generate_csrf_token() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf_token($token) {
    if (!isset($_SESSION['csrf_token']) || $token !== $_SESSION['csrf_token']) {
        return false;
    }
    return true;
}

function display_error($message, $class = "") {
    return "<div class='alert alert-danger $class' role='alert'>$message</div>";
}


function display_success($message, $class = "") {
    return "<div class='alert alert-success $class' role='alert'>$message</div>";
}

function validate_password($password) {
    // password must be at least 8 characters long and contain at least one uppercase letter, one lowercase letter, one number, and one special character
    $pattern = "/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/";
    return preg_match($pattern, $password);
}

function validate_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}
?>