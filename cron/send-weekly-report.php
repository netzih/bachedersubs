<?php
/**
 * Weekly Report Cron Job
 *
 * This script should be run via cron every Friday at 3 PM Pacific Time
 *
 * Crontab entry:
 * 0 15 * * 5 cd /path/to/bachedersubs && /usr/bin/php cron/send-weekly-report.php >> logs/weekly-report.log 2>&1
 *
 * This runs at 3:00 PM every Friday (5 = Friday in cron)
 */

// Set timezone
date_default_timezone_set('America/Los_Angeles');

// Include required files
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/EmailSettings.php';
require_once __DIR__ . '/../includes/EmailService.php';

echo "[" . date('Y-m-d H:i:s') . "] Starting weekly report cron job\n";

try {
    // Get email settings
    $emailSettings = new EmailSettings();
    $settings = $emailSettings->getRawSettings();

    // Check if settings exist and weekly reports are enabled
    if (!$settings) {
        echo "[" . date('Y-m-d H:i:s') . "] No email settings configured. Exiting.\n";
        exit(0);
    }

    if (!$settings['send_weekly_report']) {
        echo "[" . date('Y-m-d H:i:s') . "] Weekly reports are disabled. Exiting.\n";
        exit(0);
    }

    // Check if recipient email is set
    if (empty($settings['recipient_email'])) {
        echo "[" . date('Y-m-d H:i:s') . "] No recipient email configured. Exiting.\n";
        exit(0);
    }

    // Verify it's the correct day and time
    $currentDay = date('l'); // e.g., "Friday"
    $currentHour = (int) date('G'); // 24-hour format
    $reportHour = (int) substr($settings['report_time'], 0, 2);

    echo "[" . date('Y-m-d H:i:s') . "] Current day: {$currentDay}, Current hour: {$currentHour}\n";
    echo "[" . date('Y-m-d H:i:s') . "] Configured day: {$settings['report_day']}, Configured hour: {$reportHour}\n";

    // If this is being run manually or via different schedule, skip the day/time check
    // You can comment out these checks if running manually:
    if ($currentDay !== $settings['report_day']) {
        echo "[" . date('Y-m-d H:i:s') . "] Not the configured report day ({$settings['report_day']}). Exiting.\n";
        exit(0);
    }

    if ($currentHour !== $reportHour) {
        echo "[" . date('Y-m-d H:i:s') . "] Not the configured report hour ({$reportHour}:00). Exiting.\n";
        exit(0);
    }

    // Calculate date range (current week: Monday to Sunday)
    $startDate = date('Y-m-d', strtotime('monday this week'));
    $endDate = date('Y-m-d', strtotime('sunday this week'));

    echo "[" . date('Y-m-d H:i:s') . "] Report period: {$startDate} to {$endDate}\n";

    // Generate report data
    $reportData = generateWeeklyReportData($startDate, $endDate);

    echo "[" . date('Y-m-d H:i:s') . "] Report data generated: {$reportData['total_entries']} entries, {$reportData['total_hours']} hours, \${$reportData['total_amount']}\n";

    // Send email
    $emailService = new EmailService($settings);
    $result = $emailService->sendWeeklyReport($reportData);

    if ($result['success']) {
        echo "[" . date('Y-m-d H:i:s') . "] ✓ Email sent successfully to {$settings['recipient_email']}\n";

        // Update last sent timestamp
        $emailSettings->updateLastSent();

        // Log success
        if (isset($result['log']) && is_array($result['log'])) {
            foreach ($result['log'] as $line) {
                echo "[" . date('Y-m-d H:i:s') . "] {$line}\n";
            }
        }

        exit(0);
    } else {
        echo "[" . date('Y-m-d H:i:s') . "] ✗ Failed to send email: {$result['message']}\n";

        // Log errors
        if (isset($result['log']) && is_array($result['log'])) {
            foreach ($result['log'] as $line) {
                echo "[" . date('Y-m-d H:i:s') . "] {$line}\n";
            }
        }

        exit(1);
    }

} catch (Exception $e) {
    echo "[" . date('Y-m-d H:i:s') . "] ERROR: " . $e->getMessage() . "\n";
    echo "[" . date('Y-m-d H:i:s') . "] Stack trace: " . $e->getTraceAsString() . "\n";
    exit(1);
}

/**
 * Generate weekly report data
 */
function generateWeeklyReportData($startDate, $endDate) {
    $db = Database::getInstance()->getConnection();

    // Get all time entries for the week
    $sql = "SELECT
                te.id,
                te.work_date as date,
                te.start_time,
                te.end_time,
                te.hours,
                te.is_paid,
                s.hourly_rate,
                (te.hours * s.hourly_rate) as amount,
                u.name as substitute_name,
                t.name as teacher_name
            FROM time_entries te
            JOIN substitutes s ON te.substitute_id = s.id
            JOIN users u ON s.user_id = u.id
            LEFT JOIN teachers t ON te.teacher_id = t.id
            WHERE te.work_date BETWEEN :start_date AND :end_date
            ORDER BY te.work_date ASC, te.start_time ASC";

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
        $totalAmount += $entry['amount'];

        $subName = $entry['substitute_name'];
        if (!isset($substituteSummary[$subName])) {
            $substituteSummary[$subName] = [
                'name' => $subName,
                'hours' => 0,
                'amount' => 0
            ];
        }
        $substituteSummary[$subName]['hours'] += $entry['hours'];
        $substituteSummary[$subName]['amount'] += $entry['amount'];
    }

    // Convert to indexed array
    $substituteSummary = array_values($substituteSummary);

    return [
        'start_date' => $startDate,
        'end_date' => $endDate,
        'entries' => $entries,
        'total_entries' => count($entries),
        'total_hours' => $totalHours,
        'total_amount' => $totalAmount,
        'substitute_summary' => $substituteSummary
    ];
}
