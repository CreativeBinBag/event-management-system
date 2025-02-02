<?php
require_once 'functions.php';
require_once __DIR__ . '/../config/database.php';

class Auth {
    private $db;
    private $passwordOptions = [
        'memory_cost' => 65536,
        'time_cost' => 4,
        'threads' => 3,
    ];

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Authenticate user
     * @param string $email
     * @param string $password
     * @return array|bool
     */
    public function login($email, $password) {
        try {
            $stmt = $this->db->prepare("
                SELECT id, username, email, password, is_admin
                FROM users
                WHERE email = ?
            ");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                return false;
            }

            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['is_admin'] = $user['is_admin'];
                return $user;
            }
            return false;
        } catch (PDOException $e) {
            error_log("Login error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Register new user
     * @param string $username
     * @param string $email
     * @param string $password
     * @return bool
     */
    public function register($username, $email, $password) {
        try {
            $stmt = $this->db->prepare("
                SELECT id FROM users
                WHERE email = ? OR username = ?
            ");
            $stmt->execute([$email, $username]);

            if ($stmt->rowCount() > 0) {
                return false;
            }
            $hashed_password = password_hash($password, PASSWORD_ARGON2ID, $this->passwordOptions);

            $stmt = $this->db->prepare("
                INSERT INTO users (username, email, password)
                VALUES (?, ?, ?)
            ");

            return $stmt->execute([$username, $email, $hashed_password]);
        } catch (PDOException $e) {
            error_log("Registration error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Update user's last login time
     * @param int $user_id
     */
    private function updateLastLogin($user_id) {
        try {
            $stmt = $this->db->prepare("
                UPDATE users
                SET last_login = CURRENT_TIMESTAMP
                WHERE id = ?
            ");
            $stmt->execute([$user_id]);
        } catch (PDOException $e) {
            error_log("Update last login error: " . $e->getMessage());
        }
    }

    /**
     * Logout user
     */
    public function logout() {
        // unset all session variables
        $_SESSION = array();

        // destroy the session
        session_destroy();

        // delete the session cookie
        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time()-3600, '/');
        }
    }

    
   /* for later implementation */
   
    /**
     * Check if user's password needs rehashing
     * @param int $user_id
     * @param string $current_password
     * @return bool
     */
    public function needsRehash($user_id, $current_password) {
         try {
            $stmt = $this->db->prepare("SELECT password FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $hash = $stmt->fetchColumn();

             return password_needs_rehash($hash, PASSWORD_ARGON2ID, $this->passwordOptions);

        } catch (PDOException $e) {
            error_log("Password rehash check error: " . $e->getMessage());
            return false;
        }
    }
    /**
     * Update user's password
     * @param int $user_id
     * @param string $new_password
     * @return bool
     */
    public function updatePassword($user_id, $new_password) {
        try {
            $hashed_password = password_hash($new_password, PASSWORD_ARGON2ID,  $this->passwordOptions);

            $stmt = $this->db->prepare("
                UPDATE users
                SET password = ?
                WHERE id = ?
            ");

            return $stmt->execute([$hashed_password, $user_id]);
        } catch (PDOException $e) {
            error_log("Password update error: " . $e->getMessage());
            return false;
        }
    }

     /**
     * Get user details by ID
     * @param int $user_id
     * @return array|bool
     */
    public function getUserById($user_id) {
        try {
            $stmt = $this->db->prepare("
                SELECT id, username, email, is_admin, created_at, last_login
                FROM users
                WHERE id = ?
            ");
            $stmt->execute([$user_id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Get user error: " . $e->getMessage());
            return false;
        }
    } 
}
?>