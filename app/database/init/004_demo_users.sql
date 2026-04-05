-- Demo Users
INSERT INTO users (name, email, password_hash, role, role_id)
SELECT 'Admin Demo', 'admin@salon.test', '$2y$12$yXZcnr71IV7dFZdcNDKIk.rFULnaRXNAG4QZkEzb87lcajRrcz3jm', 'admin', r.id
FROM roles r WHERE r.name = 'admin';

INSERT INTO users (name, email, password_hash, role, role_id)
SELECT 'Client Demo', 'client@salon.test', '$2y$12$3wTLyZ0230Fm9d3/cw8bM.pZUEwqtI7J7.gxTUlUM723YM3.mOtkm', 'client', r.id
FROM roles r WHERE r.name = 'client';

INSERT INTO users (name, email, password_hash, role, role_id, hairdresser_id)
SELECT 'Anna Staff', 'staff@salon.test', '$2y$10$OvXPQVNGzbHGrHh2FYtk1eIg37XcKUo3ORbBPnP0C.mMcOY22S3jK', 'staff', r.id, h.id
FROM roles r
JOIN hairdressers h ON h.name = 'Anna'
WHERE r.name = 'staff'
LIMIT 1;
