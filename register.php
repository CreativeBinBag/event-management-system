<?php
require_once 'includes/functions.php';
require_once 'config/database.php';

if (is_authenticated()) {
    redirect('index.php');
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['register'])) {
    if (!verify_csrf_token($_POST['csrf_token'])) {
        $error = "Invalid request";
    } else {
        $username = sanitize_input($_POST['username']);
        $email = sanitize_input($_POST['email']);
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];

        // Validation
        if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
            $error = "All fields are required";
        } elseif (!validate_email($email)) {
            $error = "Invalid email format";
        } elseif (!validate_password($password)) {
            $error = "Password must be at least 8 characters long and contain at least one uppercase letter, one lowercase letter, one number, and one special character";
        } elseif ($password !== $confirm_password) {
            $error = "Passwords do not match";
        } else {
            try {
                $db = Database::getInstance()->getConnection();

                //check if email or username already exists
                $stmt = $db->prepare("SELECT id FROM users WHERE email = ? OR username = ?");
                $stmt->execute([$email, $username]);

                if ($stmt->rowCount() > 0) {
                    $error = "Email or username already exists";
                } else {
                    //hash password
                    $hashed_password = password_hash($password, PASSWORD_ARGON2ID, [
                        'memory_cost' => 65536,
                        'time_cost' => 4,
                        'threads' => 3
                    ]);

                    $stmt = $db->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
                    $stmt->execute([$username, $email, $hashed_password]);

                    $success = "Registration successful! Please <a href='login.php'>login here</a>."; 
                }
            } catch (PDOException $e) {
                $error = "An error occurred. Please try again later.";
                error_log("Registration error: " . $e->getMessage());
            }
        }
    }
}
?>

<?php require_once 'includes/header.php'; ?>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h4>Register</h4>
            </div>
            <div class="card-body">
                <?php
                if ($error) echo display_error($error);
                if ($success) echo display_success($success);
                ?>
                <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" id="registerForm">
                    <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">

                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <input type="text" class="form-control" id="username" name="username"
                               pattern="[A-Za-z0-9_]{3,20}" title="Username must be 3-20 characters long and can only contain letters, numbers, and underscores"
                               required>
                    </div>

                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password"
                               pattern="^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$"
                               title="Password must be at least 8 characters long and contain at least one uppercase letter, one lowercase letter, one number, and one special character"
                               required>
                    </div>

                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">Confirm Password</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                    </div>

                    <button type="submit" name="register" class="btn btn-primary">Register</button>
                </form>
                <p class="mt-3">
                    Already have an account? <a href="login.php">Login here</a>
                </p>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>