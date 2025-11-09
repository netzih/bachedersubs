<?php
/**
 * Reset Password API Endpoint
 * Resets user password using valid token
 */

require_once '../config.php';
require_once '../includes/Database.php';
require_once '../includes/PasswordReset.php';

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method === 'GET') {
        // Validate token (for checking if link is valid)
        if (!isset($_GET['token']) || empty($_GET['token'])) {
            throw new Exception('Token is required');
        }

        $passwordReset = new PasswordReset();
        $validation = $passwordReset->validateToken($_GET['token']);

        if (!$validation['valid']) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => $validation['message']
            ]);
        } else {
            echo json_encode([
                'success' => true,
                'email' => $validation['email'],
                'name' => $validation['name']
            ]);
        }

    } elseif ($method === 'POST') {
        // Reset password
        $data = json_decode(file_get_contents('php://input'), true);

        if (!isset($data['token']) || empty($data['token'])) {
            throw new Exception('Token is required');
        }

        if (!isset($data['password']) || empty($data['password'])) {
            throw new Exception('New password is required');
        }

        if (strlen($data['password']) < 6) {
            throw new Exception('Password must be at least 6 characters long');
        }

        $passwordReset = new PasswordReset();
        $result = $passwordReset->resetPassword($data['token'], $data['password']);

        if ($result['success']) {
            echo json_encode($result);
        } else {
            http_response_code(400);
            echo json_encode($result);
        }

    } else {
        throw new Exception('Method not allowed');
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
