-- Migration: Add from_email to email_settings
-- Description: Add configurable FROM email address

ALTER TABLE email_settings
ADD COLUMN from_email VARCHAR(255) NULL AFTER smtp_password,
ADD COLUMN from_name VARCHAR(255) NULL AFTER from_email;
