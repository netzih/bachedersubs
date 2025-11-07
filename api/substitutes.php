<?php
/**
 * Substitutes API
 * Manage substitutes and their rates (admin only)
 */

require_once '../config.php';
require_once '../includes/Database.php';
require_once '../includes/Auth.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

$auth = new Auth();
$db = Database::getInstance()->getConnection();
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'list':
            // Admin only
            if (!$auth->isAdmin()) {
                sendJson(['success' => false, 'message' => 'Admin access required'], 403);
            }

            $stmt = $db->prepare("
                SELECT
                    s.id,
                    s.user_id,
                    u.name,
                    u.email,
                    u.zelle_info,
                    s.hourly_rate,
                    s.created_at
                FROM substitutes s
                JOIN users u ON s.user_id = u.id
                ORDER BY u.name ASC
            ");
            $stmt->execute();
            $substitutes = $stmt->fetchAll();

            sendJson(['success' => true, 'substitutes' => $substitutes]);
            break;

        case 'create':
            // Admin only - create new substitute account
            if (!$auth->isAdmin()) {
                sendJson(['success' => false, 'message' => 'Admin access required'], 403);
            }

            if ($method !== 'POST') {
                sendJson(['success' => false, 'message' => 'Method not allowed'], 405);
            }

            $data = getJsonInput();
            $name = sanitize($data['name'] ?? '');
            $email = sanitize($data['email'] ?? '');
            $password = $data['password'] ?? '';
            $zelleInfo = sanitize($data['zelle_info'] ?? '');
            $hourlyRate = floatval($data['hourly_rate'] ?? 0);

            // Validate inputs
            if (empty($name) || empty($email) || empty($password)) {
                sendJson(['success' => false, 'message' => 'Name, email, and password are required']);
            }

            if (!isValidEmail($email)) {
                sendJson(['success' => false, 'message' => 'Invalid email address']);
            }

            if (strlen($password) < 6) {
                sendJson(['success' => false, 'message' => 'Password must be at least 6 characters']);
            }

            if ($hourlyRate < 0) {
                sendJson(['success' => false, 'message' => 'Hourly rate must be positive']);
            }

            // Check if email already exists
            $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                sendJson(['success' => false, 'message' => 'Email already registered']);
            }

            // Hash password
            $passwordHash = password_hash($password, PASSWORD_BCRYPT);

            // Begin transaction
            $db->beginTransaction();

            try {
                // Insert user
                $stmt = $db->prepare(
                    "INSERT INTO users (email, password_hash, name, role, zelle_info) VALUES (?, ?, ?, 'substitute', ?)"
                );
                $stmt->execute([$email, $passwordHash, $name, $zelleInfo]);
                $userId = $db->lastInsertId();

                // Create substitute record with hourly rate
                $stmt = $db->prepare("INSERT INTO substitutes (user_id, hourly_rate) VALUES (?, ?)");
                $stmt->execute([$userId, $hourlyRate]);
                $substituteId = $db->lastInsertId();

                $db->commit();

                sendJson(['success' => true, 'id' => $substituteId, 'message' => 'Substitute created successfully']);
            } catch (Exception $e) {
                $db->rollBack();
                sendJson(['success' => false, 'message' => 'Failed to create substitute: ' . $e->getMessage()]);
            }
            break;

        case 'update_rate':
            // Admin only
            if (!$auth->isAdmin()) {
                sendJson(['success' => false, 'message' => 'Admin access required'], 403);
            }

            if ($method !== 'POST') {
                sendJson(['success' => false, 'message' => 'Method not allowed'], 405);
            }

            $data = getJsonInput();
            $substituteId = intval($data['substitute_id'] ?? 0);
            $hourlyRate = floatval($data['hourly_rate'] ?? 0);

            if ($substituteId <= 0 || $hourlyRate < 0) {
                sendJson(['success' => false, 'message' => 'Invalid data']);
            }

            $stmt = $db->prepare("UPDATE substitutes SET hourly_rate = ? WHERE id = ?");
            $stmt->execute([$hourlyRate, $substituteId]);

            sendJson(['success' => true, 'message' => 'Hourly rate updated successfully']);
            break;

        case 'profile':
            // Get current user's profile
            if (!$auth->isLoggedIn()) {
                sendJson(['success' => false, 'message' => 'Unauthorized'], 401);
            }

            $userId = $auth->getUserId();
            $stmt = $db->prepare("
                SELECT
                    u.name,
                    u.email,
                    u.zelle_info
                FROM users u
                WHERE u.id = ?
            ");
            $stmt->execute([$userId]);
            $profile = $stmt->fetch();

            if ($profile) {
                sendJson(['success' => true, 'profile' => $profile]);
            } else {
                sendJson(['success' => false, 'message' => 'Profile not found']);
            }
            break;

        case 'update_profile':
            // Update current user's profile
            if (!$auth->isLoggedIn()) {
                sendJson(['success' => false, 'message' => 'Unauthorized'], 401);
            }

            if ($method !== 'POST') {
                sendJson(['success' => false, 'message' => 'Method not allowed'], 405);
            }

            $data = getJsonInput();
            $userId = $auth->getUserId();
            $name = sanitize($data['name'] ?? '');
            $zelleInfo = sanitize($data['zelle_info'] ?? '');

            if (empty($name)) {
                sendJson(['success' => false, 'message' => 'Name is required']);
            }

            $stmt = $db->prepare("UPDATE users SET name = ?, zelle_info = ? WHERE id = ?");
            $stmt->execute([$name, $zelleInfo, $userId]);

            // Update session
            $_SESSION['user_name'] = $name;

            sendJson(['success' => true, 'message' => 'Profile updated successfully']);
            break;

        default:
            sendJson(['success' => false, 'message' => 'Invalid action'], 400);
    }
} catch (Exception $e) {
    sendJson(['success' => false, 'message' => 'Server error: ' . $e->getMessage()], 500);
}
