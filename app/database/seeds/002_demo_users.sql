-- ============================================================================
-- Seed: Demo Users (Admin, Client, Staff)
-- ============================================================================

-- Admin user
INSERT INTO users (name, email, password_hash, role, role_id) VALUES (
    'Admin Demo',
    'admin@salon.test',
    '$2y$12$yXZcnr71IV7dFZdcNDKIk.rFULnaRXNAG4QZkEzb87lcajRrcz3jm',
    'admin',
    (SELECT id FROM roles WHERE name = 'admin')
) ON DUPLICATE KEY UPDATE role_id = (SELECT id FROM roles WHERE name = 'admin');

-- Client demo user
INSERT INTO users (name, email, password_hash, role, role_id) VALUES (
    'Client Demo',
    'client@salon.test',
    '$2y$12$3wTLyZ0230Fm9d3/cw8bM.pZUEwqtI7J7.gxTUlUM723YM3.mOtkm',
    'client',
    (SELECT id FROM roles WHERE name = 'client')
) ON DUPLICATE KEY UPDATE role_id = (SELECT id FROM roles WHERE name = 'client');

-- Staff demo user (linked to Anna)
INSERT INTO users (name, email, password_hash, role, role_id, hairdresser_id) VALUES (
    'Anna Staff',
    'staff@salon.test',
    '$2y$10$OvXPQVNGzbHGrHh2FYtk1eIg37XcKUo3ORbBPnP0C.mMcOY22S3jK',
    'staff',
    (SELECT id FROM roles WHERE name = 'staff'),
    (SELECT id FROM hairdressers WHERE name = 'Anna')
) ON DUPLICATE KEY UPDATE 
    role_id = (SELECT id FROM roles WHERE name = 'staff'),
    hairdresser_id = (SELECT id FROM hairdressers WHERE name = 'Anna');
