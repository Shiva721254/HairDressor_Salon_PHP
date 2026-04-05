-- ============================================================================
-- Migration: Normalize availability times to business hours (08:00-17:00)
-- ============================================================================

-- Clamp start times to minimum business hour
UPDATE availability
SET start_time = '08:00:00'
WHERE start_time < '08:00:00';

-- Clamp end times to maximum business hour
UPDATE availability
SET end_time = '17:00:00'
WHERE end_time > '17:00:00';

-- Remove invalid slots where start >= end
DELETE FROM availability
WHERE start_time >= end_time;
