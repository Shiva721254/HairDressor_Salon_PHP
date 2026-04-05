-- ============================================================================
-- Seed: Hairdressers and Base Services
-- ============================================================================

-- Hairdressers
INSERT INTO hairdressers (name) VALUES
    ('Anna'),
    ('John'),
    ('David'),
    ('Maria');

-- Services
INSERT INTO services (name, duration_minutes, price) VALUES
    ('Haircut', 30, 25.00),
    ('Hair coloring', 90, 80.00);
