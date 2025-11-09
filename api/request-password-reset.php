<?php
/**
 * Request Password Reset API Endpoint
 * Sends password reset email to user
 */

require_once '../config.php';
require_once '../includes/Database.php';
require_once '../includes/PasswordReset.php';
require_once '../includes/EmailSettings.php';
require_once '../includes/EmailService.php';

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method !== 'POST') {
        throw new Exception('Method not allowed');
    }

    $data = json_decode(file_get_contents('php://input'), true);

    if (!isset($data['email']) || empty($data['email'])) {
        throw new Exception('Email address is required');
    }

    $email = filter_var($data['email'], FILTER_VALIDATE_EMAIL);
    if (!$email) {
        throw new Exception('Invalid email address');
    }

    // Create reset token
    $passwordReset = new PasswordReset();
    $result = $passwordReset->createResetToken($email);

    // If user exists, send email
    if ($result['token']) {
        $emailService = new EmailService();
        $emailResult = $emailService->sendPasswordReset($result, $result['token']);

        if (!$emailResult['success']) {
            // Log error but don't reveal to user
            error_log("Failed to send password reset email: " . $emailResult['message']);
        }
    }

    // Always return success to prevent email enumeration
    echo json_encode([
        'success' => true,
        'message' => 'If an account exists with that email, a password reset link has been sent.'
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
