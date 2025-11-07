<?php
/**
 * Helper Functions
 */

/**
 * Send JSON response
 */
function sendJson($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

/**
 * Get JSON input from request body
 */
function getJsonInput() {
    $input = file_get_contents('php://input');
    return json_decode($input, true);
}

/**
 * Sanitize input
 */
function sanitize($input) {
    return htmlspecialchars(strip_tags(trim($input)));
}

/**
 * Validate email
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Format currency
 */
function formatCurrency($amount) {
    return '$' . number_format($amount, 2);
}

/**
 * Format date for display
 */
function formatDate($date) {
    $dt = new DateTime($date, new DateTimeZone(TIMEZONE));
    return $dt->format('M j, Y');
}

/**
 * Format datetime for display
 */
function formatDateTime($datetime) {
    $dt = new DateTime($datetime, new DateTimeZone(TIMEZONE));
    return $dt->format('M j, Y g:i A');
}

/**
 * Get California date/time now
 */
function getNow() {
    $dt = new DateTime('now', new DateTimeZone(TIMEZONE));
    return $dt->format('Y-m-d H:i:s');
}

/**
 * Get California date today
 */
function getToday() {
    $dt = new DateTime('now', new DateTimeZone(TIMEZONE));
    return $dt->format('Y-m-d');
}

/**
 * Validate date format
 */
function isValidDate($date, $format = 'Y-m-d') {
    $d = DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) === $date;
}
