-- Enforce staff business-hour windows to 08:00-17:00 for existing data
UPDATE availability
SET start_time = '08:00:00'
WHERE start_time < '08:00:00';

UPDATE availability
SET end_time = '17:00:00'
WHERE end_time > '17:00:00';

-- Remove rows that became invalid after clamping
DELETE FROM availability
WHERE start_time >= end_time;
