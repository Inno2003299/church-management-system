-- Church Inventory Management System Database Schema
-- Created: 2025-06-22

CREATE DATABASE IF NOT EXISTS church_db;
USE church_db;

-- Members table with enhanced fields
CREATE TABLE IF NOT EXISTS members (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    email VARCHAR(255),
    gender ENUM('Male', 'Female') NOT NULL,
    date_of_birth DATE,
    address TEXT,
    join_date DATE DEFAULT CURRENT_DATE,
    is_active BOOLEAN DEFAULT TRUE,
    profile_image VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- WebAuthn credentials for fingerprint authentication
CREATE TABLE IF NOT EXISTS webauthn_credentials (
    id INT AUTO_INCREMENT PRIMARY KEY,
    member_id INT NOT NULL,
    credential_id VARCHAR(255) NOT NULL UNIQUE,
    public_key TEXT NOT NULL,
    counter INT DEFAULT 0,
    device_name VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_used TIMESTAMP NULL,
    FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE,
    INDEX idx_member_id (member_id),
    INDEX idx_credential_id (credential_id)
);

-- Services table for different church services
CREATE TABLE IF NOT EXISTS services (
    id INT AUTO_INCREMENT PRIMARY KEY,
    service_date DATE NOT NULL,
    service_type ENUM('Sunday Morning', 'Sunday Evening', 'Wednesday', 'Friday', 'Special Event') NOT NULL,
    service_title VARCHAR(255),
    start_time TIME,
    end_time TIME,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_service (service_date, service_type)
);

-- Attendance tracking
CREATE TABLE IF NOT EXISTS attendance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    member_id INT NOT NULL,
    service_id INT NOT NULL,
    present BOOLEAN DEFAULT TRUE,
    check_in_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    check_in_method ENUM('Manual', 'Fingerprint', 'QR Code') DEFAULT 'Manual',
    notes TEXT,
    FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE,
    FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE CASCADE,
    UNIQUE KEY unique_attendance (member_id, service_id),
    INDEX idx_service_date (service_id),
    INDEX idx_member_attendance (member_id)
);

-- Offering types
CREATE TABLE IF NOT EXISTS offering_types (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert default offering types
INSERT IGNORE INTO offering_types (name, description) VALUES
('Tithe', 'Regular tithe offerings'),
('Thanksgiving', 'Thanksgiving offerings'),
('Seed Offering', 'Seed/faith offerings'),
('Building Fund', 'Church building and infrastructure'),
('Mission', 'Mission and evangelism support'),
('Special Collection', 'Special purpose collections');

-- Offerings collection
CREATE TABLE IF NOT EXISTS offerings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    service_id INT NOT NULL,
    offering_type_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    currency VARCHAR(3) DEFAULT 'USD',
    notes TEXT,
    recorded_by VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE CASCADE,
    FOREIGN KEY (offering_type_id) REFERENCES offering_types(id),
    INDEX idx_service_offerings (service_id),
    INDEX idx_offering_type (offering_type_id)
);

-- Instrumentalists
CREATE TABLE IF NOT EXISTS instrumentalists (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    email VARCHAR(255),
    instrument VARCHAR(100) NOT NULL,
    skill_level ENUM('Beginner', 'Intermediate', 'Advanced', 'Professional') DEFAULT 'Intermediate',
    hourly_rate DECIMAL(8,2),
    per_service_rate DECIMAL(8,2),
    -- Mobile Money Details for Paystack Integration
    momo_provider ENUM('MTN', 'Vodafone', 'AirtelTigo', 'Other') NULL,
    momo_number VARCHAR(20) NULL,
    momo_name VARCHAR(255) NULL COMMENT 'Name registered with MoMo account',
    bank_account_number VARCHAR(50) NULL,
    bank_name VARCHAR(100) NULL,
    bank_account_name VARCHAR(255) NULL,
    preferred_payment_method ENUM('Mobile Money', 'Bank Transfer', 'Cash') DEFAULT 'Mobile Money',
    paystack_recipient_code VARCHAR(100) NULL COMMENT 'Paystack transfer recipient code',
    is_active BOOLEAN DEFAULT TRUE,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Instrumentalist payments
CREATE TABLE IF NOT EXISTS instrumentalist_payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    instrumentalist_id INT NOT NULL,
    service_id INT NOT NULL,
    amount DECIMAL(8,2) NOT NULL,
    payment_type ENUM('Per Service', 'Hourly', 'Fixed Amount') DEFAULT 'Per Service',
    hours_worked DECIMAL(4,2),
    payment_status ENUM('Pending', 'Approved', 'Paid', 'Failed', 'Cancelled') DEFAULT 'Pending',
    payment_date DATE,
    payment_method ENUM('Cash', 'Bank Transfer', 'Check', 'Mobile Money', 'Paystack Transfer', 'Other') DEFAULT 'Mobile Money',
    reference_number VARCHAR(100),
    -- Paystack Integration Fields
    paystack_transfer_code VARCHAR(100) NULL COMMENT 'Paystack transfer code',
    paystack_transfer_id VARCHAR(100) NULL COMMENT 'Paystack transfer ID',
    paystack_status VARCHAR(50) NULL COMMENT 'Paystack transfer status',
    paystack_failure_reason TEXT NULL COMMENT 'Reason for failed Paystack transfer',
    paystack_recipient_code VARCHAR(100) NULL COMMENT 'Paystack recipient code used',
    approved_by VARCHAR(255),
    approved_at TIMESTAMP NULL,
    paid_by VARCHAR(255),
    paid_at TIMESTAMP NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (instrumentalist_id) REFERENCES instrumentalists(id) ON DELETE CASCADE,
    FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE CASCADE,
    INDEX idx_instrumentalist_payments (instrumentalist_id),
    INDEX idx_service_payments (service_id),
    INDEX idx_payment_status (payment_status)
);

-- Payment batches for bulk processing
CREATE TABLE IF NOT EXISTS payment_batches (
    id INT AUTO_INCREMENT PRIMARY KEY,
    batch_name VARCHAR(255) NOT NULL,
    batch_date DATE NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL,
    payment_count INT NOT NULL,
    batch_status ENUM('Draft', 'Approved', 'Processing', 'Completed', 'Cancelled') DEFAULT 'Draft',
    created_by VARCHAR(255),
    approved_by VARCHAR(255),
    approved_at TIMESTAMP NULL,
    processed_by VARCHAR(255),
    processed_at TIMESTAMP NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Link payments to batches
CREATE TABLE IF NOT EXISTS payment_batch_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    batch_id INT NOT NULL,
    payment_id INT NOT NULL,
    amount DECIMAL(8,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (batch_id) REFERENCES payment_batches(id) ON DELETE CASCADE,
    FOREIGN KEY (payment_id) REFERENCES instrumentalist_payments(id) ON DELETE CASCADE,
    UNIQUE KEY unique_payment_batch (batch_id, payment_id)
);

-- Admin users table
CREATE TABLE IF NOT EXISTS admin_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(255) NOT NULL,
    email VARCHAR(255),
    role ENUM('Super Admin', 'Admin', 'Viewer') DEFAULT 'Admin',
    is_active BOOLEAN DEFAULT TRUE,
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert default admin user (password: admin123)
INSERT IGNORE INTO admin_users (username, password_hash, full_name, role) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Administrator', 'Super Admin');

-- System settings
CREATE TABLE IF NOT EXISTS system_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT,
    description TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert default system settings
INSERT IGNORE INTO system_settings (setting_key, setting_value, description) VALUES
('church_name', 'Your Church Name', 'Name of the church'),
('church_address', '', 'Church physical address'),
('church_phone', '', 'Church contact phone number'),
('church_email', '', 'Church contact email'),
('default_currency', 'USD', 'Default currency for offerings and payments'),
('enable_fingerprint', '1', 'Enable fingerprint authentication'),
('attendance_grace_period', '30', 'Minutes after service start to allow check-in');
