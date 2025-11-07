<?php
/**
 * Time Entries API
 * Manage time entries for substitutes
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
        case 'create':
            // Substitutes can create their own entries
            if (!$auth->isLoggedIn()) {
                sendJson(['success' => false, 'message' => 'Unauthorized'], 401);
            }

            if ($method !== 'POST') {
                sendJson(['success' => false, 'message' => 'Method not allowed'], 405);
            }

            $data = getJsonInput();
            $teacherId = intval($data['teacher_id'] ?? 0);
            $workDate = $data['work_date'] ?? '';
            $startTime = $data['start_time'] ?? '';
            $endTime = $data['end_time'] ?? '';
            $notes = sanitize($data['notes'] ?? '');

            // Validate inputs
            if ($teacherId <= 0 || empty($workDate) || empty($startTime) || empty($endTime)) {
                sendJson(['success' => false, 'message' => 'Invalid data']);
            }

            if (!isValidDate($workDate)) {
                sendJson(['success' => false, 'message' => 'Invalid date format']);
            }

            // Validate time format (HH:MM)
            if (!preg_match('/^([0-1][0-9]|2[0-3]):[0-5][0-9]$/', $startTime) ||
                !preg_match('/^([0-1][0-9]|2[0-3]):[0-5][0-9]$/', $endTime)) {
                sendJson(['success' => false, 'message' => 'Invalid time format. Use HH:MM']);
            }

            // Calculate hours from start and end time
            $start = new DateTime($startTime);
            $end = new DateTime($endTime);

            // If end time is before start time, it means it crosses midnight
            if ($end < $start) {
                $end->modify('+1 day');
            }

            $interval = $start->diff($end);
            $hours = $interval->h + ($interval->i / 60);
            $hours = round($hours, 2);

            if ($hours <= 0) {
                sendJson(['success' => false, 'message' => 'End time must be after start time']);
            }

            // Get substitute ID for current user
            $userId = $auth->getUserId();
            $stmt = $db->prepare("SELECT id FROM substitutes WHERE user_id = ?");
            $stmt->execute([$userId]);
            $substitute = $stmt->fetch();

            if (!$substitute) {
                sendJson(['success' => false, 'message' => 'Substitute record not found']);
            }

            $substituteId = $substitute['id'];

            // Insert time entry
            $stmt = $db->prepare("
                INSERT INTO time_entries (substitute_id, teacher_id, work_date, start_time, end_time, hours, notes)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$substituteId, $teacherId, $workDate, $startTime, $endTime, $hours, $notes]);
            $entryId = $db->lastInsertId();

            sendJson(['success' => true, 'id' => $entryId, 'message' => 'Hours logged successfully']);
            break;

        case 'admin_create':
            // Admin can manually create time entries for any substitute
            if (!$auth->isAdmin()) {
                sendJson(['success' => false, 'message' => 'Admin access required'], 403);
            }

            if ($method !== 'POST') {
                sendJson(['success' => false, 'message' => 'Method not allowed'], 405);
            }

            $data = getJsonInput();
            $substituteId = intval($data['substitute_id'] ?? 0);
            $teacherId = intval($data['teacher_id'] ?? 0);
            $workDate = $data['work_date'] ?? '';
            $startTime = $data['start_time'] ?? '';
            $endTime = $data['end_time'] ?? '';
            $notes = sanitize($data['notes'] ?? '');

            // Validate inputs
            if ($substituteId <= 0 || $teacherId <= 0 || empty($workDate) || empty($startTime) || empty($endTime)) {
                sendJson(['success' => false, 'message' => 'All fields are required']);
            }

            if (!isValidDate($workDate)) {
                sendJson(['success' => false, 'message' => 'Invalid date format']);
            }

            // Validate time format (HH:MM)
            if (!preg_match('/^([0-1][0-9]|2[0-3]):[0-5][0-9]$/', $startTime) ||
                !preg_match('/^([0-1][0-9]|2[0-3]):[0-5][0-9]$/', $endTime)) {
                sendJson(['success' => false, 'message' => 'Invalid time format. Use HH:MM']);
            }

            // Calculate hours from start and end time
            $start = new DateTime($startTime);
            $end = new DateTime($endTime);

            // If end time is before start time, it means it crosses midnight
            if ($end < $start) {
                $end->modify('+1 day');
            }

            $interval = $start->diff($end);
            $hours = $interval->h + ($interval->i / 60);
            $hours = round($hours, 2);

            if ($hours <= 0) {
                sendJson(['success' => false, 'message' => 'End time must be after start time']);
            }

            // Verify substitute exists
            $stmt = $db->prepare("SELECT id FROM substitutes WHERE id = ?");
            $stmt->execute([$substituteId]);
            if (!$stmt->fetch()) {
                sendJson(['success' => false, 'message' => 'Substitute not found']);
            }

            // Insert time entry
            $stmt = $db->prepare("
                INSERT INTO time_entries (substitute_id, teacher_id, work_date, start_time, end_time, hours, notes)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$substituteId, $teacherId, $workDate, $startTime, $endTime, $hours, $notes]);
            $entryId = $db->lastInsertId();

            sendJson(['success' => true, 'id' => $entryId, 'message' => 'Time entry created successfully']);
            break;

        case 'update':
            // Admin can edit any entry, substitutes can only edit their own unpaid entries
            if (!$auth->isLoggedIn()) {
                sendJson(['success' => false, 'message' => 'Unauthorized'], 401);
            }

            if ($method !== 'POST') {
                sendJson(['success' => false, 'message' => 'Method not allowed'], 405);
            }

            $data = getJsonInput();
            $entryId = intval($data['entry_id'] ?? 0);
            $teacherId = intval($data['teacher_id'] ?? 0);
            $workDate = $data['work_date'] ?? '';
            $startTime = $data['start_time'] ?? '';
            $endTime = $data['end_time'] ?? '';
            $notes = sanitize($data['notes'] ?? '');

            // Validate inputs
            if ($entryId <= 0 || $teacherId <= 0 || empty($workDate) || empty($startTime) || empty($endTime)) {
                sendJson(['success' => false, 'message' => 'All fields are required']);
            }

            if (!isValidDate($workDate)) {
                sendJson(['success' => false, 'message' => 'Invalid date format']);
            }

            // Validate time format (HH:MM)
            if (!preg_match('/^([0-1][0-9]|2[0-3]):[0-5][0-9]$/', $startTime) ||
                !preg_match('/^([0-1][0-9]|2[0-3]):[0-5][0-9]$/', $endTime)) {
                sendJson(['success' => false, 'message' => 'Invalid time format. Use HH:MM']);
            }

            // Calculate hours from start and end time
            $start = new DateTime($startTime);
            $end = new DateTime($endTime);

            // If end time is before start time, it means it crosses midnight
            if ($end < $start) {
                $end->modify('+1 day');
            }

            $interval = $start->diff($end);
            $hours = $interval->h + ($interval->i / 60);
            $hours = round($hours, 2);

            if ($hours <= 0) {
                sendJson(['success' => false, 'message' => 'End time must be after start time']);
            }

            $userId = $auth->getUserId();
            $isAdmin = $auth->isAdmin();

            // Check permissions and get entry
            $stmt = $db->prepare("
                SELECT te.*, s.user_id as substitute_user_id
                FROM time_entries te
                JOIN substitutes s ON te.substitute_id = s.id
                WHERE te.id = ?
            ");
            $stmt->execute([$entryId]);
            $entry = $stmt->fetch();

            if (!$entry) {
                sendJson(['success' => false, 'message' => 'Entry not found']);
            }

            // If not admin, check ownership and paid status
            if (!$isAdmin) {
                if ($entry['substitute_user_id'] != $userId) {
                    sendJson(['success' => false, 'message' => 'Unauthorized to edit this entry'], 403);
                }
                if ($entry['is_paid']) {
                    sendJson(['success' => false, 'message' => 'Cannot edit paid entries'], 403);
                }
            }

            // Update time entry
            $stmt = $db->prepare("
                UPDATE time_entries
                SET teacher_id = ?, work_date = ?, start_time = ?, end_time = ?, hours = ?, notes = ?
                WHERE id = ?
            ");
            $stmt->execute([$teacherId, $workDate, $startTime, $endTime, $hours, $notes, $entryId]);

            sendJson(['success' => true, 'message' => 'Entry updated successfully']);
            break;

        case 'list':
            // List entries for current user (substitute) or all (admin)
            if (!$auth->isLoggedIn()) {
                sendJson(['success' => false, 'message' => 'Unauthorized'], 401);
            }

            $userId = $auth->getUserId();
            $isAdmin = $auth->isAdmin();

            // Optional filters
            $startDate = $_GET['start_date'] ?? null;
            $endDate = $_GET['end_date'] ?? null;
            $teacherId = intval($_GET['teacher_id'] ?? 0);
            $isPaid = $_GET['is_paid'] ?? null;

            $sql = "
                SELECT
                    te.id,
                    te.work_date,
                    te.start_time,
                    te.end_time,
                    te.hours,
                    te.notes,
                    te.is_paid,
                    te.paid_at,
                    te.created_at,
                    t.name as teacher_name,
                    u.name as substitute_name,
                    u.email as substitute_email,
                    s.hourly_rate,
                    (te.hours * s.hourly_rate) as amount
                FROM time_entries te
                JOIN teachers t ON te.teacher_id = t.id
                JOIN substitutes s ON te.substitute_id = s.id
                JOIN users u ON s.user_id = u.id
                WHERE 1=1
            ";

            $params = [];

            // If not admin, only show their own entries
            if (!$isAdmin) {
                $sql .= " AND s.user_id = ?";
                $params[] = $userId;
            }

            // Apply filters
            if ($startDate && isValidDate($startDate)) {
                $sql .= " AND te.work_date >= ?";
                $params[] = $startDate;
            }

            if ($endDate && isValidDate($endDate)) {
                $sql .= " AND te.work_date <= ?";
                $params[] = $endDate;
            }

            if ($teacherId > 0) {
                $sql .= " AND te.teacher_id = ?";
                $params[] = $teacherId;
            }

            if ($isPaid !== null) {
                $sql .= " AND te.is_paid = ?";
                $params[] = $isPaid === 'true' ? 1 : 0;
            }

            $sql .= " ORDER BY te.work_date DESC, te.created_at DESC";

            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            $entries = $stmt->fetchAll();

            sendJson(['success' => true, 'entries' => $entries]);
            break;

        case 'mark_paid':
            // Admin only
            if (!$auth->isAdmin()) {
                sendJson(['success' => false, 'message' => 'Admin access required'], 403);
            }

            if ($method !== 'POST') {
                sendJson(['success' => false, 'message' => 'Method not allowed'], 405);
            }

            $data = getJsonInput();
            $entryId = intval($data['entry_id'] ?? 0);
            $isPaid = boolval($data['is_paid'] ?? false);

            if ($entryId <= 0) {
                sendJson(['success' => false, 'message' => 'Invalid entry ID']);
            }

            $paidAt = $isPaid ? getNow() : null;

            $stmt = $db->prepare("UPDATE time_entries SET is_paid = ?, paid_at = ? WHERE id = ?");
            $stmt->execute([$isPaid ? 1 : 0, $paidAt, $entryId]);

            sendJson(['success' => true, 'message' => 'Payment status updated']);
            break;

        case 'delete':
            // Admin or entry owner can delete
            if (!$auth->isLoggedIn()) {
                sendJson(['success' => false, 'message' => 'Unauthorized'], 401);
            }

            if ($method !== 'POST') {
                sendJson(['success' => false, 'message' => 'Method not allowed'], 405);
            }

            $data = getJsonInput();
            $entryId = intval($data['entry_id'] ?? 0);

            if ($entryId <= 0) {
                sendJson(['success' => false, 'message' => 'Invalid entry ID']);
            }

            $userId = $auth->getUserId();
            $isAdmin = $auth->isAdmin();

            // Check ownership if not admin
            if (!$isAdmin) {
                $stmt = $db->prepare("
                    SELECT te.id
                    FROM time_entries te
                    JOIN substitutes s ON te.substitute_id = s.id
                    WHERE te.id = ? AND s.user_id = ?
                ");
                $stmt->execute([$entryId, $userId]);

                if (!$stmt->fetch()) {
                    sendJson(['success' => false, 'message' => 'Unauthorized to delete this entry'], 403);
                }
            }

            $stmt = $db->prepare("DELETE FROM time_entries WHERE id = ?");
            $stmt->execute([$entryId]);

            sendJson(['success' => true, 'message' => 'Entry deleted successfully']);
            break;

        case 'stats':
            // Get statistics (admin sees all, substitute sees their own)
            if (!$auth->isLoggedIn()) {
                sendJson(['success' => false, 'message' => 'Unauthorized'], 401);
            }

            $userId = $auth->getUserId();
            $isAdmin = $auth->isAdmin();

            $sql = "
                SELECT
                    COUNT(*) as total_entries,
                    SUM(te.hours) as total_hours,
                    SUM(te.hours * s.hourly_rate) as total_amount,
                    SUM(CASE WHEN te.is_paid = 1 THEN te.hours * s.hourly_rate ELSE 0 END) as paid_amount,
                    SUM(CASE WHEN te.is_paid = 0 THEN te.hours * s.hourly_rate ELSE 0 END) as unpaid_amount,
                    SUM(CASE WHEN te.is_paid = 1 THEN te.hours ELSE 0 END) as paid_hours,
                    SUM(CASE WHEN te.is_paid = 0 THEN te.hours ELSE 0 END) as unpaid_hours,
                    COUNT(CASE WHEN te.is_paid = 0 THEN 1 END) as unpaid_entries
                FROM time_entries te
                JOIN substitutes s ON te.substitute_id = s.id
            ";

            $params = [];

            if (!$isAdmin) {
                $sql .= " WHERE s.user_id = ?";
                $params[] = $userId;
            }

            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            $stats = $stmt->fetch();

            sendJson(['success' => true, 'stats' => $stats]);
            break;

        default:
            sendJson(['success' => false, 'message' => 'Invalid action'], 400);
    }
} catch (Exception $e) {
    sendJson(['success' => false, 'message' => 'Server error: ' . $e->getMessage()], 500);
}
