CREATE TABLE roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(20) NOT NULL UNIQUE
);

INSERT INTO roles (name) VALUES ('client'), ('staff'), ('admin');

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('client','staff','admin') NOT NULL DEFAULT 'client',
    role_id INT NULL,
    hairdresser_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE hairdressers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE services (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    duration_minutes INT NOT NULL,
    price DECIMAL(6,2) NOT NULL
);

CREATE TABLE availability (
    id INT AUTO_INCREMENT PRIMARY KEY,
    hairdresser_id INT NOT NULL,
    day_of_week TINYINT NOT NULL, -- 1=Mon ... 7=Sun
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    FOREIGN KEY (hairdresser_id) REFERENCES hairdressers(id)
        ON DELETE CASCADE
);

CREATE TABLE appointments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    hairdresser_id INT NOT NULL,
    service_id INT NOT NULL,
    appointment_date DATE NOT NULL,
    appointment_time TIME NOT NULL,
    status ENUM('booked','cancelled','completed') DEFAULT 'booked',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (hairdresser_id) REFERENCES hairdressers(id),
    FOREIGN KEY (service_id) REFERENCES services(id)
);

CREATE TABLE gdpr_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    type ENUM('export','deletion') NOT NULL,
    status ENUM('pending','processed') NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

ALTER TABLE users
    ADD CONSTRAINT fk_users_hairdresser
    FOREIGN KEY (hairdresser_id) REFERENCES hairdressers(id)
    ON DELETE SET NULL;

ALTER TABLE users
    ADD CONSTRAINT fk_users_role
    FOREIGN KEY (role_id) REFERENCES roles(id)
    ON DELETE SET NULL;

CREATE TABLE unavailability_slots (
    id INT AUTO_INCREMENT PRIMARY KEY,
    hairdresser_id INT NOT NULL,
    slot_date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    note VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (hairdresser_id) REFERENCES hairdressers(id)
        ON DELETE CASCADE
);
