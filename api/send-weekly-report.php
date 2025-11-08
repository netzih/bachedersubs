<?php
/**
 * Send Weekly Report API Endpoint
 * Manually triggers weekly report email
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

    // Get date range (default to last week)
    $data = json_decode(file_get_contents('php://input'), true);

    if (isset($data['start_date']) && isset($data['end_date'])) {
        $startDate = $data['start_date'];
        $endDate = $data['end_date'];
    } else {
        // Default to last week (Monday to Sunday)
        $endDate = date('Y-m-d', strtotime('last Sunday'));
        $startDate = date('Y-m-d', strtotime($endDate . ' -6 days'));
    }

    // Get report data
    $reportData = generateWeeklyReportData($startDate, $endDate);

    // Send email
    $emailService = new EmailService();
    $result = $emailService->sendWeeklyReport($reportData);

    // Update last sent timestamp if successful
    if ($result['success']) {
        $emailSettings = new EmailSettings();
        $emailSettings->updateLastSent();
    }

    echo json_encode($result);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'log' => []
    ]);
}

/**
 * Generate weekly report data
 */
function generateWeeklyReportData($startDate, $endDate) {
    $db = Database::getInstance()->getConnection();

    // Get all time entries for the week
    $sql = "SELECT
                te.id,
                te.date,
                te.start_time,
                te.end_time,
                te.hours,
                te.hourly_rate,
                te.total_amount,
                te.is_paid,
                u.name as substitute_name,
                t.name as teacher_name
            FROM time_entries te
            JOIN users u ON te.user_id = u.id
            LEFT JOIN teachers t ON te.teacher_id = t.id
            WHERE te.date BETWEEN :start_date AND :end_date
            ORDER BY te.date ASC, te.start_time ASC";

    $stmt = $db->prepare($sql);
    $stmt->execute([
        'start_date' => $startDate,
        'end_date' => $endDate
    ]);
    $entries = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Calculate totals
    $totalHours = 0;
    $totalAmount = 0;
    $substituteSummary = [];

    foreach ($entries as $entry) {
        $totalHours += $entry['hours'];
        $totalAmount += $entry['total_amount'];

        $subName = $entry['substitute_name'];
        if (!isset($substituteSummary[$subName])) {
            $substituteSummary[$subName] = [
                'name' => $subName,
                'hours' => 0,
                'amount' => 0
            ];
        }
        $substituteSummary[$subName]['hours'] += $entry['hours'];
        $substituteSummary[$subName]['amount'] += $entry['total_amount'];
    }

    // Convert to indexed array
    $substituteSummary = array_values($substituteSummary);

    return [
        'start_date' => $startDate,
        'end_date' => $endDate,
        'entries' => $entries,
        'total_hours' => $totalHours,
        'total_amount' => $totalAmount,
        'substitute_summary' => $substituteSummary
    ];
}
