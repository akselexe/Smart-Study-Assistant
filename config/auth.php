<?php
require_once __DIR__ . '/db.php';

class Auth {
    private $db;

    public function __construct() {
        $this->db = new Database();
        $this->startSession();
    }

    private function startSession() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public function register($username, $email, $password, $role = 'student') {
        // Validate input
        if (empty($username) || empty($email) || empty($password)) {
            return ['success' => false, 'error' => 'All fields are required'];
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'error' => 'Invalid email format'];
        }

        if (strlen($password) < 6) {
            return ['success' => false, 'error' => 'Password must be at least 6 characters'];
        }

        if (strlen($username) < 3) {
            return ['success' => false, 'error' => 'Username must be at least 3 characters'];
        }

        // Validate role
        if (!in_array($role, ['student', 'professor'])) {
            $role = 'student';
        }

        // Check if username or email already exists
        $stmt = $this->db->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->bind_param("ss", $username, $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            return ['success' => false, 'error' => 'Username or email already exists'];
        }

        // Hash password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        // Insert new user
        $stmt = $this->db->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $username, $email, $hashedPassword, $role);

        if ($stmt->execute()) {
            return ['success' => true, 'message' => 'Registration successful! You can now login.'];
        } else {
            return ['success' => false, 'error' => 'Registration failed. Please try again.'];
        }
    }

    public function login($username, $password) {
        if (empty($username) || empty($password)) {
            return ['success' => false, 'error' => 'Username and password are required'];
        }

        // Get user from database
        $stmt = $this->db->prepare("SELECT id, username, email, password, role FROM users WHERE username = ? OR email = ?");
        $stmt->bind_param("ss", $username, $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            return ['success' => false, 'error' => 'Invalid username or password'];
        }

        $user = $result->fetch_assoc();

        // Verify password
        if (password_verify($password, $user['password'])) {
            // Session is already started in constructor
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = $user['role'] ?? 'student';
            $_SESSION['logged_in'] = true;

            return ['success' => true, 'message' => 'Login successful!'];
        } else {
            return ['success' => false, 'error' => 'Invalid username or password'];
        }
    }

    public function logout() {
        // Session is already started in constructor
        session_unset();
        session_destroy();
        return ['success' => true, 'message' => 'Logged out successfully'];
    }

    public function isLoggedIn() {
        // Session is already started in constructor
        return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
    }

    public function getUserId() {
        // Session is already started in constructor
        return $_SESSION['user_id'] ?? null;
    }

    public function getUsername() {
        // Session is already started in constructor
        return $_SESSION['username'] ?? null;
    }

    public function requireLogin() {
        if (!$this->isLoggedIn()) {
            header('Location: login.php');
            exit;
        }
    }

    public function getUserRole() {
        // Session is already started in constructor
        return $_SESSION['role'] ?? 'student';
    }

    public function isProfessor() {
        return $this->isLoggedIn() && $this->getUserRole() === 'professor';
    }

    public function isStudent() {
        return $this->isLoggedIn() && $this->getUserRole() === 'student';
    }
}
?>

