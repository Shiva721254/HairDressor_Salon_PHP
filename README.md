Hairdresser Salon Appointment System

 Starting of the application
1. From project root, run: docker-compose up
2. Open http://localhost
3. Optional DB UI: http://localhost:8080 (phpMyAdmin)
4. SQL deliverable: `database_export.sql` is saved as UTF-8 and can be imported directly in MySQL/MariaDB tools.

Demo login credentials
- Admin: admin@salon.test / Admin123!
- Staff: staff@salon.test / Staff123!
- Client: client@salon.test / Client123!


Code patterns / framework enhancements
- Front controller and route table: app/public/index.php
- Dependency injection container: app/src/Core/Container.php
- Base controller utilities (render, json, csrf, auth guards): app/src/Core/Controller.php
- Repository pattern with interfaces: app/src/Repositories/
- Service layer for booking and availability logic: app/src/Services/BookingService.php, app/src/Services/AvailabilityService.php

Rubric mapping
- CSS framework and responsive UI:
  - Bootstrap is loaded in the main layout: app/views/layouts/main.php
  - Custom responsive styling and transitions: app/public/assets/css/app.css
- Sessions:
  - Session startup and auth/session state: app/public/index.php
  - Session-backed login state and CSRF token handling: app/src/Core/Controller.php, app/views/layouts/main.php
- Security:
  - Parameterized PDO queries: app/src/Repositories/UserRepository.php, app/src/Repositories/AppointmentRepository.php, app/src/Repositories/AvailabilityRepository.php
  - Password hashing and verification: app/src/Controllers/AuthController.php, app/src/Controllers/ProfileController.php, app/src/Controllers/Admin/StaffAdminController.php
  - Server-side input validation: app/src/Controllers/AuthController.php, app/src/Controllers/ProfileController.php, app/src/Controllers/Admin/StaffAdminController.php, app/src/Controllers/AppointmentController.php
  - Route protection by authentication/authorization: app/src/Core/Controller.php, app/public/index.php
- MVC / architecture:
  - Front controller and routing: app/public/index.php
  - Controllers: app/src/Controllers/
  - Views/templates: app/views/
  - Repositories and interfaces: app/src/Repositories/
  - Services: app/src/Services/
  - Dependency inversion/container wiring: app/src/Core/Container.php
- API and JavaScript:
  - JSON response helper: app/src/Core/Controller.php
  - Booking slots API: app/src/Controllers/AppointmentController.php
  - Hairdresser availability API: app/src/Controllers/HairdresserController.php
  - JavaScript fetch + partial page updates: app/public/assets/js/app.js, app/views/appointments/create.php, app/views/hairdressers/show.php
- Legal / accessibility:
  - GDPR export and deletion request flow: app/src/Controllers/ProfileController.php, app/src/Controllers/Admin/GdprAdminController.php, app/src/Repositories/GdprRequestRepository.php
  - GDPR schema support: app/database/migrations/001_initial_schema.sql
  - Skip link, semantic main content, accessible navigation: app/views/layouts/main.php
  - Accessible labels and form structure examples: app/views/auth/login.php, app/views/auth/register.php, app/views/appointments/create.php, app/views/staff/availability.php

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
- Styled error pages:
  - 404 and 405 errors render a proper styled view instead of plain text: app/public/index.php
  - 404 view: app/views/errors/404.php
  - 403 view: app/views/errors/403.php

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

JavaScript / API work
- Slot loading: JavaScript fetches /api/slots on the booking form and populates
  available times dynamically without page reload: app/public/assets/js/app.js
- Hairdresser availability: JavaScript fetches /api/hairdressers/{id}/availability
  on the hairdresser detail page and renders working days dynamically without
  page reload: app/views/hairdressers/show.php
  - API endpoint: app/src/Controllers/HairdresserController.php (availability method)

Project structure 
- docker-compose.yml, PHP.Dockerfile, nginx.conf
- app/public (entry + static assets)
- app/src (Controllers, Core, Repositories, Services)
- app/views (UI templates)
- app/database (migrations, seeds)


- Docker services: nginx, php-fpm, mariadb, phpmyadmin
- Database is initialized automatically by MariaDB entrypoint using ordered SQL mounts from docker-compose.yml.
