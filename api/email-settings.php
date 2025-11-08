<?php
/**
 * Email Settings API Endpoint
 * Handles SMTP configuration operations
 */

require_once '../config.php';
require_once '../includes/Database.php';
require_once '../includes/Auth.php';
require_once '../includes/EmailSettings.php';
require_once '../includes/EmailService.php';

header('Content-Type: application/json');

$auth = new Auth();
$auth->requireAdmin(); // Only admins can manage email settings

$method = $_SERVER['REQUEST_METHOD'];
$emailSettings = new EmailSettings();

try {
    switch ($method) {
        case 'GET':
            // Get email settings
            $settings = $emailSettings->getSettings();
            echo json_encode([
                'success' => true,
                'settings' => $settings
            ]);
            break;

        case 'POST':
            // Save email settings
            $data = json_decode(file_get_contents('php://input'), true);

            if (!isset($data['smtp_host']) || !isset($data['smtp_port']) || !isset($data['recipient_email'])) {
                throw new Exception('Missing required fields');
            }

            // Validate email
            if (!filter_var($data['recipient_email'], FILTER_VALIDATE_EMAIL)) {
                throw new Exception('Invalid recipient email address');
            }

            // Validate port
            if (!is_numeric($data['smtp_port']) || $data['smtp_port'] < 1 || $data['smtp_port'] > 65535) {
                throw new Exception('Invalid SMTP port');
            }

            if ($emailSettings->saveSettings($data)) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Email settings saved successfully'
                ]);
            } else {
                throw new Exception('Failed to save email settings');
            }
            break;

        case 'PUT':
            // Update email settings (same as POST)
            $data = json_decode(file_get_contents('php://input'), true);

            if ($emailSettings->saveSettings($data)) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Email settings updated successfully'
                ]);
            } else {
                throw new Exception('Failed to update email settings');
            }
            break;

        default:
            http_response_code(405);
            echo json_encode([
                'success' => false,
                'message' => 'Method not allowed'
            ]);
            break;
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
