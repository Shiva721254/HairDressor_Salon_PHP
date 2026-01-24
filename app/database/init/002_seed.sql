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

-- Availability (use name lookup so IDs never matter)

-- Anna: Mon–Fri 09:00–17:00, Sat–Sun 10:00–14:00
INSERT INTO availability (hairdresser_id, day_of_week, start_time, end_time)
SELECT h.id, a.day_of_week, a.start_time, a.end_time
FROM hairdressers h
JOIN (
  SELECT 1 AS day_of_week, '09:00:00' AS start_time, '17:00:00' AS end_time UNION ALL
  SELECT 2, '09:00:00', '17:00:00' UNION ALL
  SELECT 3, '09:00:00', '17:00:00' UNION ALL
  SELECT 4, '09:00:00', '17:00:00' UNION ALL
  SELECT 5, '09:00:00', '17:00:00' UNION ALL
  SELECT 6, '10:00:00', '14:00:00' UNION ALL
  SELECT 7, '10:00:00', '14:00:00'
) a
WHERE h.name = 'Anna';

-- John: Tue–Sat 10:00–18:00
INSERT INTO availability (hairdresser_id, day_of_week, start_time, end_time)
SELECT h.id, a.day_of_week, a.start_time, a.end_time
FROM hairdressers h
JOIN (
  SELECT 2 AS day_of_week, '10:00:00' AS start_time, '18:00:00' AS end_time UNION ALL
  SELECT 3, '10:00:00', '18:00:00' UNION ALL
  SELECT 4, '10:00:00', '18:00:00' UNION ALL
  SELECT 5, '10:00:00', '18:00:00' UNION ALL
  SELECT 6, '10:00:00', '18:00:00'
) a
WHERE h.name = 'John';

-- David: Mon–Thu 12:00–20:00
INSERT INTO availability (hairdresser_id, day_of_week, start_time, end_time)
SELECT h.id, a.day_of_week, a.start_time, a.end_time
FROM hairdressers h
JOIN (
  SELECT 1 AS day_of_week, '12:00:00' AS start_time, '20:00:00' AS end_time UNION ALL
  SELECT 2, '12:00:00', '20:00:00' UNION ALL
  SELECT 3, '12:00:00', '20:00:00' UNION ALL
  SELECT 4, '12:00:00', '20:00:00'
) a
WHERE h.name = 'David';

-- Maria: Fri–Sun 09:00–15:00
INSERT INTO availability (hairdresser_id, day_of_week, start_time, end_time)
SELECT h.id, a.day_of_week, a.start_time, a.end_time
FROM hairdressers h
JOIN (
  SELECT 5 AS day_of_week, '09:00:00' AS start_time, '15:00:00' AS end_time UNION ALL
  SELECT 6, '09:00:00', '15:00:00' UNION ALL
  SELECT 7, '09:00:00', '15:00:00'
) a
WHERE h.name = 'Maria';
