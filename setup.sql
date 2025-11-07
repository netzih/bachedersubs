-- Bay Area Cheder Substitute Tracking System
-- Database Setup Script
-- Run this in your Plesk MySQL database

-- Create users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    name VARCHAR(255) NOT NULL,
    role ENUM('admin', 'substitute') NOT NULL,
    zelle_info VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_role (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create teachers table
CREATE TABLE IF NOT EXISTS teachers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_active (active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create substitutes table (stores hourly rate - admin only)
CREATE TABLE IF NOT EXISTS substitutes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    hourly_rate DECIMAL(10, 2) DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create time_entries table
CREATE TABLE IF NOT EXISTS time_entries (
    id INT AUTO_INCREMENT PRIMARY KEY,
    substitute_id INT NOT NULL,
    teacher_id INT NOT NULL,
    work_date DATE NOT NULL,
    hours DECIMAL(5, 2) NOT NULL,
    notes TEXT DEFAULT NULL,
    is_paid BOOLEAN DEFAULT FALSE,
    paid_at TIMESTAMP NULL DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (substitute_id) REFERENCES substitutes(id) ON DELETE CASCADE,
    FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE CASCADE,
    INDEX idx_substitute (substitute_id),
    INDEX idx_teacher (teacher_id),
    INDEX idx_work_date (work_date),
    INDEX idx_is_paid (is_paid),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default admin user (password: admin123 - CHANGE THIS AFTER FIRST LOGIN!)
-- Password hash for 'admin123'
INSERT INTO users (email, password_hash, name, role)
VALUES ('admin@bayareacheder.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrator', 'admin')
ON DUPLICATE KEY UPDATE email=email;

-- Sample teachers (optional - remove if you want to add your own)
INSERT INTO teachers (name) VALUES
('Rabbi Cohen'),
('Mrs. Goldstein'),
('Rabbi Levy'),
('Mrs. Friedman')
ON DUPLICATE KEY UPDATE name=name;
