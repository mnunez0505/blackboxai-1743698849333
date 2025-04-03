-- MySQL Schema for Vacation Management System
-- Compatible with MySQL 5.7+ and MariaDB 10+

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

-- Create Users Table
CREATE TABLE `users` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `username` varchar(50) NOT NULL,
    `password` varchar(255) NOT NULL,
    `email` varchar(100) NOT NULL,
    `phone` varchar(20) DEFAULT NULL,
    `fullname` varchar(100) NOT NULL,
    `role` enum('admin','supervisor','employee') NOT NULL DEFAULT 'employee',
    `supervisor_id` int(11) DEFAULT NULL,
    `date_hire` date NOT NULL,
    `vacation_days` int(11) NOT NULL DEFAULT '0',
    `reset_token` varchar(64) DEFAULT NULL,
    `reset_token_expiry` datetime DEFAULT NULL,
    `last_login` datetime DEFAULT NULL,
    `last_logout` datetime DEFAULT NULL,
    `email_notifications` tinyint(1) DEFAULT '1',
    `whatsapp_notifications` tinyint(1) DEFAULT '0',
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `username` (`username`),
    UNIQUE KEY `email` (`email`),
    KEY `supervisor_id` (`supervisor_id`),
    CONSTRAINT `fk_supervisor` FOREIGN KEY (`supervisor_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create Vacation Requests Table
CREATE TABLE `vacation_requests` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `employee_id` int(11) NOT NULL,
    `start_date` date NOT NULL,
    `end_date` date NOT NULL,
    `days_requested` int(11) NOT NULL,
    `reason` text NOT NULL,
    `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
    `supervisor_comment` text,
    `category_id` int(11) DEFAULT NULL,
    `request_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `employee_id` (`employee_id`),
    KEY `category_id` (`category_id`),
    KEY `idx_status` (`status`),
    KEY `idx_dates` (`start_date`, `end_date`),
    CONSTRAINT `fk_employee` FOREIGN KEY (`employee_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_category` FOREIGN KEY (`category_id`) REFERENCES `vacation_categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create System Logs Table
CREATE TABLE `system_logs` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `log_type` varchar(50) NOT NULL,
    `message` text NOT NULL,
    `user_id` int(11) DEFAULT NULL,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `user_id` (`user_id`),
    KEY `idx_log_type` (`log_type`),
    KEY `idx_created_at` (`created_at`),
    CONSTRAINT `fk_user_log` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create Departments Table
CREATE TABLE `departments` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `name` varchar(100) NOT NULL,
    `description` text,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create Vacation Categories Table
CREATE TABLE `vacation_categories` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `name` varchar(50) NOT NULL,
    `description` text,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create System Version Table
CREATE TABLE `system_version` (
    `version` varchar(20) NOT NULL,
    `installed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`version`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default vacation categories
INSERT INTO `vacation_categories` (`name`, `description`) VALUES
('Annual Leave', 'Regular vacation days'),
('Sick Leave', 'Medical-related absence'),
('Personal Leave', 'Personal matters and emergencies'),
('Unpaid Leave', 'Leave without pay');

-- Insert default admin user (password: Admin123!)
INSERT INTO `users` (
    `username`,
    `password`,
    `email`,
    `phone`,
    `fullname`,
    `role`,
    `date_hire`,
    `vacation_days`
) VALUES (
    'admin',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    'admin@yourdomain.com',
    '1234567890',
    'System Administrator',
    'admin',
    CURRENT_DATE,
    0
);

-- Create view for employee vacation summary
CREATE VIEW `employee_vacation_summary` AS
SELECT 
    u.id,
    u.fullname,
    u.email,
    u.vacation_days as available_days,
    COUNT(CASE WHEN vr.status = 'pending' THEN 1 END) as pending_requests,
    COUNT(CASE WHEN vr.status = 'approved' THEN 1 END) as approved_requests,
    SUM(CASE WHEN vr.status = 'approved' THEN vr.days_requested ELSE 0 END) as used_days
FROM users u
LEFT JOIN vacation_requests vr ON u.id = vr.employee_id
WHERE u.role != 'admin'
GROUP BY u.id, u.fullname, u.email, u.vacation_days;

-- Create view for supervisor dashboard
CREATE VIEW `supervisor_dashboard` AS
SELECT 
    s.id as supervisor_id,
    s.fullname as supervisor_name,
    COUNT(DISTINCT e.id) as total_employees,
    COUNT(CASE WHEN vr.status = 'pending' THEN 1 END) as pending_requests,
    COUNT(CASE WHEN vr.status = 'approved' THEN 1 END) as approved_requests,
    COUNT(CASE WHEN vr.status = 'rejected' THEN 1 END) as rejected_requests
FROM users s
LEFT JOIN users e ON s.id = e.supervisor_id
LEFT JOIN vacation_requests vr ON e.id = vr.employee_id
WHERE s.role = 'supervisor'
GROUP BY s.id, s.fullname;

-- Insert initial version
INSERT INTO `system_version` (`version`) VALUES ('1.0.0');

COMMIT;