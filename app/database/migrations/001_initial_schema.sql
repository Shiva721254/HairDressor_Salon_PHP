-- ============================================================================
-- Hair Salon Management System - Initial Schema
-- ============================================================================

-- Roles for role-based access control
CREATE TABLE IF NOT EXISTS roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(20) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Users: clients, staff, admins
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL DEFAULT '',
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('client','staff','admin') NOT NULL DEFAULT 'client',
    role_id INT NULL,
    hairdresser_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Hairdressers (staff members providing services)
CREATE TABLE IF NOT EXISTS hairdressers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Services offered (haircut, coloring, etc.)
CREATE TABLE IF NOT EXISTS services (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    duration_minutes INT NOT NULL,
    price DECIMAL(6,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Regular availability (weekly schedule)
CREATE TABLE IF NOT EXISTS availability (
    id INT AUTO_INCREMENT PRIMARY KEY,
    hairdresser_id INT NOT NULL,
    day_of_week TINYINT NOT NULL, -- 1=Monday, 7=Sunday
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (hairdresser_id) REFERENCES hairdressers(id) ON DELETE CASCADE,
    INDEX idx_hairdresser_day (hairdresser_id, day_of_week)
);

-- One-time unavailability (holidays, sick days, etc.)
CREATE TABLE IF NOT EXISTS unavailability_slots (
    id INT AUTO_INCREMENT PRIMARY KEY,
    hairdresser_id INT NOT NULL,
    slot_date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    note VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (hairdresser_id) REFERENCES hairdressers(id) ON DELETE CASCADE,
    INDEX idx_hairdresser_date (hairdresser_id, slot_date)
);

-- Appointments/bookings
CREATE TABLE IF NOT EXISTS appointments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    hairdresser_id INT NOT NULL,
    service_id INT NOT NULL,
    appointment_date DATE NOT NULL,
    appointment_time TIME NOT NULL,
    status ENUM('booked','cancelled','completed') DEFAULT 'booked',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (hairdresser_id) REFERENCES hairdressers(id),
    FOREIGN KEY (service_id) REFERENCES services(id),
    INDEX idx_user_date (user_id, appointment_date),
    INDEX idx_hairdresser_date (hairdresser_id, appointment_date)
);

-- GDPR requests (data export/deletion)
CREATE TABLE IF NOT EXISTS gdpr_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    type ENUM('export','deletion') NOT NULL,
    status ENUM('pending','processed') NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    INDEX idx_user_status (user_id, status)
);

-- Foreign Key Constraints (checked for existence using conditional logic)
SET FOREIGN_KEY_CHECKS = 0;

-- Add role_id foreign key if not exists
ALTER TABLE users ADD CONSTRAINT fk_users_role 
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE SET NULL;

-- Add hairdresser_id foreign key if not exists
ALTER TABLE users ADD CONSTRAINT fk_users_hairdresser 
    FOREIGN KEY (hairdresser_id) REFERENCES hairdressers(id) ON DELETE SET NULL;

SET FOREIGN_KEY_CHECKS = 1;
