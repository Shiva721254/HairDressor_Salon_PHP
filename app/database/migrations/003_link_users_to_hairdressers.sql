-- ============================================================================
-- Migration: Ensure staff users are linked to hairdressers
-- ============================================================================

-- Backfill role_id from role enum if not already set
UPDATE users
SET role_id = (SELECT id FROM roles WHERE name = users.role)
WHERE role_id IS NULL;

-- Ensure hairdresser_id is set for staff users
UPDATE users u
SET hairdresser_id = (SELECT id FROM hairdressers LIMIT 1)
WHERE u.role = 'staff' AND u.hairdresser_id IS NULL;
