INSERT INTO hairdressers (name) VALUES
('Anna'),
('John');

INSERT INTO services (name, duration_minutes, price) VALUES
('Haircut', 30, 25.00),
('Hair coloring', 90, 80.00);

INSERT INTO availability (hairdresser_id, day_of_week, start_time, end_time) VALUES
(1, 1, '09:00', '17:00'),
(1, 2, '09:00', '17:00'),
(2, 3, '10:00', '18:00');
