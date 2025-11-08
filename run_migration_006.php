<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/Database.php';

$db = Database::getInstance()->getConnection();
$sql = file_get_contents(__DIR__ . '/migrations/006_create_email_settings.sql');

try {
    $db->exec($sql);
    echo 'Migration 006_create_email_settings.sql executed successfully' . PHP_EOL;
} catch (PDOException $e) {
    echo 'Migration failed: ' . $e->getMessage() . PHP_EOL;
    exit(1);
}
