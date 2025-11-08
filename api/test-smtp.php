<?php
/**
 * Test SMTP Connection API Endpoint
 * Tests SMTP configuration and returns detailed log
 */

require_once '../config.php';
require_once '../includes/Database.php';
require_once '../includes/Auth.php';
require_once '../includes/EmailSettings.php';
require_once '../includes/EmailService.php';

header('Content-Type: application/json');

$auth = new Auth();
$auth->requireAdmin();

$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method !== 'POST') {
        throw new Exception('Method not allowed');
    }

    $data = json_decode(file_get_contents('php://input'), true);

    // If settings provided in request, use those for testing
    // Otherwise, use saved settings
    if (isset($data['smtp_host'])) {
        $settings = [
            'smtp_host' => $data['smtp_host'],
            'smtp_port' => $data['smtp_port'] ?? 587,
            'smtp_username' => $data['smtp_username'] ?? null,
            'smtp_password' => $data['smtp_password'] ?? null,
            'smtp_encryption' => $data['smtp_encryption'] ?? 'tls',
            'recipient_email' => $data['recipient_email'] ?? ''
        ];
        $emailService = new EmailService($settings);
    } else {
        $emailService = new EmailService();
    }

    $result = $emailService->testConnection();

    echo json_encode($result);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'log' => []
    ]);
}
