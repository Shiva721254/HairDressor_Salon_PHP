Hairdresser Salon Appointment System

This is my PHP MVC salon booking system for the module submission.

Quick start
1. From project root, run: docker-compose up
2. Open http://localhost
3. Optional DB UI: http://localhost:8080 (phpMyAdmin)

Demo login credentials
- Admin: admin@salon.test / Admin123!
- Staff: staff@salon.test / Staff123!
- Client: client@salon.test / Client123!

Submission checklist (required items)
- Database export included in root: database_export.sql
- Fully dockerized and runnable with docker-compose up
- Source code included in project zip: HairDressor_Salon_PHP_submission.zip
- Special instructions and credentials documented here

Code patterns / framework enhancements
- Front controller and route table: app/public/index.php
- Dependency injection container: app/src/Core/Container.php
- Base controller utilities (render, json, csrf, auth guards): app/src/Core/Controller.php
- Repository pattern with interfaces: app/src/Repositories/
- Service layer for booking and availability logic: app/src/Services/BookingService.php, app/src/Services/AvailabilityService.php

Security and compliance notes
- CSRF protection:
  - Token generation and validation: app/src/Core/Controller.php
  - CSRF fields used in forms (example): app/views/auth/login.php, app/views/layouts/main.php
- Password handling:
  - Verify on login: app/src/Controllers/AuthController.php
  - Hash on profile password update: app/src/Controllers/ProfileController.php
- SQL safety:
  - Parameterized PDO queries in repositories (example): app/src/Repositories/UserRepository.php, app/src/Repositories/AppointmentRepository.php
- Output escaping in templates:
  - Example layout and views use htmlspecialchars: app/views/layouts/main.php

GDPR work
- User data export endpoint: app/src/Controllers/ProfileController.php (export)
- Deletion request endpoint: app/src/Controllers/ProfileController.php (requestDeletion)
- GDPR admin processing: app/src/Controllers/Admin/GdprAdminController.php
- GDPR schema/table: app/database/migrations/001_initial_schema.sql

WCAG/accessibility work
- Skip link to main content: app/views/layouts/main.php
- Semantic main landmark: app/views/layouts/main.php
- Labels and accessible form structure across auth/booking/staff forms (examples):
  - app/views/auth/login.php
  - app/views/staff/availability.php

Project structure (short)
- docker-compose.yml, PHP.Dockerfile, nginx.conf
- app/public (entry + static assets)
- app/src (Controllers, Core, Repositories, Services)
- app/views (UI templates)
- app/database (migrations, seeds)

Notes for marker
- Docker services: nginx, php-fpm, mariadb, phpmyadmin
- Database is initialized automatically by MariaDB entrypoint using ordered SQL mounts from docker-compose.yml.
