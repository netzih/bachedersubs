<?php
/**
 * Bay Area Cheder Substitute Tracking System
 * Configuration File Example
 *
 * INSTRUCTIONS:
 * 1. Copy this file to config.php
 * 2. Update the database credentials below with your Plesk MySQL details
 * 3. Change the JWT_SECRET to a random string
 */

// Database Configuration
define('DB_HOST', 'localhost');           // Usually 'localhost' on Plesk
define('DB_NAME', 'your_database_name');  // Database name from Plesk
define('DB_USER', 'your_database_user');  // Database username from Plesk
define('DB_PASS', 'your_database_pass');  // Database password from Plesk
define('DB_CHARSET', 'utf8mb4');

// Application Configuration
define('TIMEZONE', 'America/Los_Angeles'); // California timezone
define('JWT_SECRET', 'change-this-to-a-random-secret-key-min-32-chars'); // Change this!
define('SESSION_LIFETIME', 86400); // 24 hours in seconds

// Environment
define('ENVIRONMENT', 'production'); // 'development' or 'production'
define('DEBUG_MODE', false); // Set to false in production

// Site Configuration
define('SITE_URL', 'https://yourdomain.com'); // Your site URL
define('SITE_NAME', 'Bay Area Cheder - Substitute Tracking');

// Error Reporting
if (ENVIRONMENT === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Set timezone
date_default_timezone_set(TIMEZONE);
