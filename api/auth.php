<?php
/**
 * Authentication API
 * Handles login, register, logout
 */

require_once '../config.php';
require_once '../includes/Database.php';
require_once '../includes/Auth.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

$auth = new Auth();
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'register':
            if ($method !== 'POST') {
                sendJson(['success' => false, 'message' => 'Method not allowed'], 405);
            }

            $data = getJsonInput();
            $email = sanitize($data['email'] ?? '');
            $password = $data['password'] ?? '';
            $name = sanitize($data['name'] ?? '');
            $zelleInfo = sanitize($data['zelle_info'] ?? '');

            // Validate inputs
            if (empty($email) || empty($password) || empty($name)) {
                sendJson(['success' => false, 'message' => 'All fields are required']);
            }

            if (!isValidEmail($email)) {
                sendJson(['success' => false, 'message' => 'Invalid email address']);
            }

            if (strlen($password) < 6) {
                sendJson(['success' => false, 'message' => 'Password must be at least 6 characters']);
            }

            $result = $auth->register($email, $password, $name, $zelleInfo);
            sendJson($result);
            break;

        case 'login':
            if ($method !== 'POST') {
                sendJson(['success' => false, 'message' => 'Method not allowed'], 405);
            }

            $data = getJsonInput();
            $email = sanitize($data['email'] ?? '');
            $password = $data['password'] ?? '';

            if (empty($email) || empty($password)) {
                sendJson(['success' => false, 'message' => 'Email and password are required']);
            }

            $result = $auth->login($email, $password);
            sendJson($result);
            break;

        case 'logout':
            $result = $auth->logout();
            sendJson($result);
            break;

        case 'check':
            if ($auth->isLoggedIn()) {
                sendJson([
                    'success' => true,
                    'user' => $auth->getCurrentUser()
                ]);
            } else {
                sendJson(['success' => false, 'message' => 'Not logged in']);
            }
            break;

        default:
            sendJson(['success' => false, 'message' => 'Invalid action'], 400);
    }
} catch (Exception $e) {
    sendJson(['success' => false, 'message' => 'Server error'], 500);
}
