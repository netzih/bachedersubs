-- Migration: Create email_settings table
-- Description: Stores SMTP configuration for weekly email reports

CREATE TABLE IF NOT EXISTS email_settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    smtp_host VARCHAR(255) NOT NULL,
    smtp_port INT NOT NULL DEFAULT 587,
    smtp_username VARCHAR(255) NULL,
    smtp_password VARCHAR(255) NULL,
    smtp_encryption VARCHAR(10) DEFAULT 'tls',
    recipient_email VARCHAR(255) NOT NULL,
    send_weekly_report BOOLEAN DEFAULT TRUE,
    report_day VARCHAR(10) DEFAULT 'Friday',
    report_time VARCHAR(5) DEFAULT '15:00',
    report_timezone VARCHAR(50) DEFAULT 'America/Los_Angeles',
    last_sent_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert default settings (not configured)
INSERT INTO email_settings (smtp_host, smtp_port, recipient_email, smtp_encryption)
VALUES ('smtp.gmail.com', 587, '', 'tls')
ON DUPLICATE KEY UPDATE smtp_host = smtp_host;
