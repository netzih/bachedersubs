<?php
/**
 * Teachers API
 * Manage teachers (admin only for create/update/delete, public for list)
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
            // Anyone logged in can view teachers list
            if (!$auth->isLoggedIn()) {
                sendJson(['success' => false, 'message' => 'Unauthorized'], 401);
            }

            $stmt = $db->prepare("SELECT id, name, active FROM teachers WHERE active = 1 ORDER BY name ASC");
            $stmt->execute();
            $teachers = $stmt->fetchAll();

            sendJson(['success' => true, 'teachers' => $teachers]);
            break;

        case 'create':
            // Admin only
            if (!$auth->isAdmin()) {
                sendJson(['success' => false, 'message' => 'Admin access required'], 403);
            }

            if ($method !== 'POST') {
                sendJson(['success' => false, 'message' => 'Method not allowed'], 405);
            }

            $data = getJsonInput();
            $name = sanitize($data['name'] ?? '');

            if (empty($name)) {
                sendJson(['success' => false, 'message' => 'Teacher name is required']);
            }

            $stmt = $db->prepare("INSERT INTO teachers (name) VALUES (?)");
            $stmt->execute([$name]);
            $teacherId = $db->lastInsertId();

            sendJson(['success' => true, 'id' => $teacherId, 'message' => 'Teacher added successfully']);
            break;

        case 'update':
            // Admin only
            if (!$auth->isAdmin()) {
                sendJson(['success' => false, 'message' => 'Admin access required'], 403);
            }

            if ($method !== 'POST') {
                sendJson(['success' => false, 'message' => 'Method not allowed'], 405);
            }

            $data = getJsonInput();
            $id = intval($data['id'] ?? 0);
            $name = sanitize($data['name'] ?? '');

            if ($id <= 0 || empty($name)) {
                sendJson(['success' => false, 'message' => 'Invalid data']);
            }

            $stmt = $db->prepare("UPDATE teachers SET name = ? WHERE id = ?");
            $stmt->execute([$name, $id]);

            sendJson(['success' => true, 'message' => 'Teacher updated successfully']);
            break;

        case 'delete':
            // Admin only
            if (!$auth->isAdmin()) {
                sendJson(['success' => false, 'message' => 'Admin access required'], 403);
            }

            if ($method !== 'POST') {
                sendJson(['success' => false, 'message' => 'Method not allowed'], 405);
            }

            $data = getJsonInput();
            $id = intval($data['id'] ?? 0);

            if ($id <= 0) {
                sendJson(['success' => false, 'message' => 'Invalid ID']);
            }

            // Soft delete - mark as inactive
            $stmt = $db->prepare("UPDATE teachers SET active = 0 WHERE id = ?");
            $stmt->execute([$id]);

            sendJson(['success' => true, 'message' => 'Teacher deleted successfully']);
            break;

        default:
            sendJson(['success' => false, 'message' => 'Invalid action'], 400);
    }
} catch (Exception $e) {
    sendJson(['success' => false, 'message' => 'Server error: ' . $e->getMessage()], 500);
}
