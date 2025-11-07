<?php
/**
 * Authentication Handler
 * Manages user sessions and authentication
 */

class Auth {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();

        // Start session if not already started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    /**
     * Register a new substitute user
     */
    public function register($email, $password, $name, $zelleInfo = null) {
        try {
            // Check if email already exists
            $stmt = $this->db->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);

            if ($stmt->fetch()) {
                return ['success' => false, 'message' => 'Email already registered'];
            }

            // Hash password
            $passwordHash = password_hash($password, PASSWORD_BCRYPT);

            // Begin transaction
            $this->db->beginTransaction();

            // Insert user
            $stmt = $this->db->prepare(
                "INSERT INTO users (email, password_hash, name, role, zelle_info) VALUES (?, ?, ?, 'substitute', ?)"
            );
            $stmt->execute([$email, $passwordHash, $name, $zelleInfo]);
            $userId = $this->db->lastInsertId();

            // Create substitute record
            $stmt = $this->db->prepare("INSERT INTO substitutes (user_id, hourly_rate) VALUES (?, 0.00)");
            $stmt->execute([$userId]);

            $this->db->commit();

            return ['success' => true, 'message' => 'Registration successful'];
        } catch (Exception $e) {
            $this->db->rollBack();
            return ['success' => false, 'message' => 'Registration failed: ' . $e->getMessage()];
        }
    }

    /**
     * Login user
     */
    public function login($email, $password) {
        try {
            $stmt = $this->db->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if (!$user || !password_verify($password, $user['password_hash'])) {
                return ['success' => false, 'message' => 'Invalid email or password'];
            }

            // Set session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['logged_in'] = true;

            return [
                'success' => true,
                'user' => [
                    'id' => $user['id'],
                    'email' => $user['email'],
                    'name' => $user['name'],
                    'role' => $user['role']
                ]
            ];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Login failed'];
        }
    }

    /**
     * Logout user
     */
    public function logout() {
        session_destroy();
        return ['success' => true];
    }

    /**
     * Check if user is logged in
     */
    public function isLoggedIn() {
        return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
    }

    /**
     * Check if user is admin
     */
    public function isAdmin() {
        return $this->isLoggedIn() && $_SESSION['user_role'] === 'admin';
    }

    /**
     * Get current user ID
     */
    public function getUserId() {
        return $_SESSION['user_id'] ?? null;
    }

    /**
     * Get current user role
     */
    public function getRole() {
        return $_SESSION['user_role'] ?? null;
    }

    /**
     * Get current user data
     */
    public function getCurrentUser() {
        if (!$this->isLoggedIn()) {
            return null;
        }

        return [
            'id' => $_SESSION['user_id'],
            'email' => $_SESSION['user_email'],
            'name' => $_SESSION['user_name'],
            'role' => $_SESSION['user_role']
        ];
    }

    /**
     * Require login (redirect if not logged in)
     */
    public function requireLogin() {
        if (!$this->isLoggedIn()) {
            header('Location: /index.html');
            exit;
        }
    }

    /**
     * Require admin (redirect if not admin)
     */
    public function requireAdmin() {
        $this->requireLogin();
        if (!$this->isAdmin()) {
            header('Location: /substitute/dashboard.php');
            exit;
        }
    }
}
