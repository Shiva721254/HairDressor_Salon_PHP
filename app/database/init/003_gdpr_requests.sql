CREATE TABLE IF NOT EXISTS gdpr_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    type ENUM('export','deletion') NOT NULL,
    status ENUM('pending','processed') NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

ALTER TABLE gdpr_requests
    ADD COLUMN IF NOT EXISTS status ENUM('pending','processed') NOT NULL DEFAULT 'pending';
