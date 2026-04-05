-- Add user name support for profile editing and staff appointment context

SET @stmt := (
  SELECT IF(
    (SELECT COUNT(*)
     FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE()
       AND TABLE_NAME = 'users'
       AND COLUMN_NAME = 'name') > 0,
    'SELECT 1',
    'ALTER TABLE users ADD COLUMN name VARCHAR(100) NOT NULL DEFAULT "" FIRST'
  )
);
PREPARE s FROM @stmt;
EXECUTE s;
DEALLOCATE PREPARE s;

-- Backfill empty names from email local-part when possible
UPDATE users
SET name = SUBSTRING_INDEX(email, '@', 1)
WHERE (name IS NULL OR name = '');
