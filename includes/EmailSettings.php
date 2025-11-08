<?php
/**
 * EmailSettings Class
 * Handles email configuration CRUD operations
 */

class EmailSettings {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Get email settings
     */
    public function getSettings() {
        $stmt = $this->db->query("SELECT * FROM email_settings ORDER BY id DESC LIMIT 1");
        $settings = $stmt->fetch(PDO::FETCH_ASSOC);

        // Don't expose password in response
        if ($settings && isset($settings['smtp_password'])) {
            $settings['smtp_password'] = $settings['smtp_password'] ? '********' : '';
            $settings['has_password'] = !empty($settings['smtp_password']);
        }

        return $settings;
    }

    /**
     * Save or update email settings
     */
    public function saveSettings($data) {
        // Check if settings exist
        $existing = $this->db->query("SELECT id FROM email_settings LIMIT 1")->fetch();

        if ($existing) {
            return $this->updateSettings($existing['id'], $data);
        } else {
            return $this->createSettings($data);
        }
    }

    /**
     * Create new settings
     */
    private function createSettings($data) {
        $sql = "INSERT INTO email_settings (
            smtp_host, smtp_port, smtp_username, smtp_password, smtp_encryption,
            from_email, from_name, recipient_email, send_weekly_report, report_day, report_time, report_timezone
        ) VALUES (
            :smtp_host, :smtp_port, :smtp_username, :smtp_password, :smtp_encryption,
            :from_email, :from_name, :recipient_email, :send_weekly_report, :report_day, :report_time, :report_timezone
        )";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'smtp_host' => $data['smtp_host'],
            'smtp_port' => $data['smtp_port'],
            'smtp_username' => $data['smtp_username'] ?? null,
            'smtp_password' => $data['smtp_password'] ?? null,
            'smtp_encryption' => $data['smtp_encryption'] ?? 'tls',
            'from_email' => $data['from_email'] ?? null,
            'from_name' => $data['from_name'] ?? null,
            'recipient_email' => $data['recipient_email'],
            'send_weekly_report' => $data['send_weekly_report'] ?? true,
            'report_day' => $data['report_day'] ?? 'Friday',
            'report_time' => $data['report_time'] ?? '15:00',
            'report_timezone' => $data['report_timezone'] ?? 'America/Los_Angeles'
        ]);
    }

    /**
     * Update existing settings
     */
    private function updateSettings($id, $data) {
        // Build dynamic update query
        $fields = [];
        $params = ['id' => $id];

        $allowedFields = [
            'smtp_host', 'smtp_port', 'smtp_username', 'smtp_encryption',
            'from_email', 'from_name', 'recipient_email', 'send_weekly_report',
            'report_day', 'report_time', 'report_timezone'
        ];

        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $fields[] = "$field = :$field";
                $params[$field] = $data[$field];
            }
        }

        // Handle password separately - only update if provided and not the masked value
        if (isset($data['smtp_password']) && $data['smtp_password'] !== '' && $data['smtp_password'] !== '********') {
            $fields[] = "smtp_password = :smtp_password";
            $params['smtp_password'] = $data['smtp_password'];
        }

        if (empty($fields)) {
            return true; // Nothing to update
        }

        $sql = "UPDATE email_settings SET " . implode(', ', $fields) . " WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * Update last sent timestamp
     */
    public function updateLastSent() {
        $sql = "UPDATE email_settings SET last_sent_at = NOW() ORDER BY id DESC LIMIT 1";
        return $this->db->exec($sql);
    }

    /**
     * Get raw settings (including password) - for internal use only
     */
    public function getRawSettings() {
        $stmt = $this->db->query("SELECT * FROM email_settings ORDER BY id DESC LIMIT 1");
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
