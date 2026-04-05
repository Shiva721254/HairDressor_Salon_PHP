-- Staff role + user/hairdresser link + specific unavailability slots (migration-safe)

-- Expand role enum only if needed
SET @stmt := (
  SELECT IF(
    (SELECT COUNT(*)
     FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE()
       AND TABLE_NAME = 'users'
       AND COLUMN_NAME = 'role'
       AND COLUMN_TYPE LIKE "%staff%") > 0,
    'SELECT 1',
    "ALTER TABLE users MODIFY role ENUM('client','staff','admin') NOT NULL DEFAULT 'client'"
  )
);
PREPARE s FROM @stmt;
EXECUTE s;
DEALLOCATE PREPARE s;

-- Create roles table + seed values
CREATE TABLE IF NOT EXISTS roles (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(20) NOT NULL UNIQUE
);

INSERT IGNORE INTO roles (name) VALUES ('client'), ('staff'), ('admin');

-- Add hairdresser_id to users if missing
SET @stmt := (
  SELECT IF(
    (SELECT COUNT(*)
     FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE()
       AND TABLE_NAME = 'users'
       AND COLUMN_NAME = 'hairdresser_id') > 0,
    'SELECT 1',
    'ALTER TABLE users ADD COLUMN hairdresser_id INT NULL AFTER role'
  )
);
PREPARE s FROM @stmt;
EXECUTE s;
DEALLOCATE PREPARE s;

-- Add role_id to users if missing and backfill from enum role
SET @stmt := (
  SELECT IF(
    (SELECT COUNT(*)
     FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE()
       AND TABLE_NAME = 'users'
       AND COLUMN_NAME = 'role_id') > 0,
    'SELECT 1',
    'ALTER TABLE users ADD COLUMN role_id INT NULL AFTER role'
  )
);
PREPARE s FROM @stmt;
EXECUTE s;
DEALLOCATE PREPARE s;

UPDATE users u
JOIN roles r ON r.name = u.role
SET u.role_id = r.id
WHERE u.role_id IS NULL;

-- Add FK users.role_id if missing
SET @stmt := (
  SELECT IF(
    (SELECT COUNT(*)
     FROM information_schema.KEY_COLUMN_USAGE
     WHERE TABLE_SCHEMA = DATABASE()
       AND TABLE_NAME = 'users'
       AND COLUMN_NAME = 'role_id'
       AND REFERENCED_TABLE_NAME = 'roles') > 0,
    'SELECT 1',
    'ALTER TABLE users ADD CONSTRAINT fk_users_role FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE SET NULL'
  )
);
PREPARE s FROM @stmt;
EXECUTE s;
DEALLOCATE PREPARE s;

-- Add FK users.hairdresser_id if missing
SET @stmt := (
  SELECT IF(
    (SELECT COUNT(*)
     FROM information_schema.KEY_COLUMN_USAGE
     WHERE TABLE_SCHEMA = DATABASE()
       AND TABLE_NAME = 'users'
       AND COLUMN_NAME = 'hairdresser_id'
       AND REFERENCED_TABLE_NAME = 'hairdressers') > 0,
    'SELECT 1',
    'ALTER TABLE users ADD CONSTRAINT fk_users_hairdresser FOREIGN KEY (hairdresser_id) REFERENCES hairdressers(id) ON DELETE SET NULL'
  )
);
PREPARE s FROM @stmt;
EXECUTE s;
DEALLOCATE PREPARE s;

-- Create specific-date/time unavailability table if missing
CREATE TABLE IF NOT EXISTS unavailability_slots (
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
