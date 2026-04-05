Hairdresser Salon Appointment System

PHP MVC Application

Project Overview

This project is a Hairdresser Salon booking and management system built with PHP (MVC), MariaDB, JavaScript, and Docker.

It supports 3 roles:
- Admin
- Staff (Hairdresser)
- Client

Main features:
- User authentication and role-based access
- Service and hairdresser management
- Appointment booking with duration-based conflict prevention
- Weekly availability and date-specific unavailability management
- Profile updates and GDPR request handling

Run With Docker

From project root:

```bash
docker compose up --build
```

Access:
- App: http://localhost
- phpMyAdmin: http://localhost:8080

Demo Accounts

- Admin: admin@salon.test / Admin123!
- Staff: staff@salon.test / Staff123!
- Client: client@salon.test / Client123!

Tech Stack

- PHP 8 (FPM)
- Nginx
- MariaDB
- phpMyAdmin (dev)
- JavaScript (vanilla)
- FastRoute

Project Structure

```text
.
|-- docker-compose.yml
|-- nginx.conf
|-- PHP.Dockerfile
|-- README.md
`-- app/
    |-- composer.json
    |-- database/
    |   `-- init/
    |       |-- 001_schema.sql
    |       |-- 002_seed.sql
    |       |-- 003_gdpr_requests.sql
    |       |-- 004_demo_users.sql
    |       |-- 005_staff_role_unavailability.sql
    |       |-- 006_user_name.sql
    |       `-- 007_business_hours_availability.sql
    |-- public/
    |   |-- index.php
    |   `-- assets/
    |-- src/
    |   |-- Controllers/
    |   |-- Core/
    |   `-- Repositories/
    `-- views/
```

Database Notes

Database scripts are in app/database/init/.
They run automatically on first container startup.

Rubric Evidence (Marker Checklist)

1. CSS
- Bootstrap integration: app/views/layouts/main.php
- Custom styling and responsive UI: app/public/assets/css/app.css

2. Sessions
- Session bootstrap: app/public/index.php
- Session user and role guards: app/src/Core/Controller.php
- Login/logout session handling: app/src/Controllers/AuthController.php

3. Security
- CSRF helpers and validation: app/src/Core/Controller.php
- CSRF-protected forms: app/views/auth/login.php, app/views/auth/profile.php, app/views/layouts/main.php
- Password hash/verify: app/src/Controllers/AuthController.php, app/src/Controllers/ProfileController.php
- Prepared statements (PDO): app/src/Repositories/UserRepository.php, app/src/Repositories/AppointmentRepository.php, app/src/Repositories/GdprRequestRepository.php
- Escaped output in templates: app/views/layouts/main.php, app/views/appointments/index.php

4. MVC and Architecture
- Front controller + routing: app/public/index.php
- Controllers layer: app/src/Controllers/
- Repository layer: app/src/Repositories/
- Views layer: app/views/
- Dependency injection container: app/src/Core/Container.php

5. API
- API routes: app/public/index.php
- JSON endpoint implementation: app/src/Controllers/AppointmentController.php
- JSON hairdresser dates endpoint: app/src/Controllers/HairdresserController.php

6. JavaScript
- Async slot loading and interactive forms: app/public/assets/js/app.js
- Booking page JS usage: app/views/appointments/create.php

7. Legal and Accessibility
- GDPR export and deletion request flow: app/src/Controllers/ProfileController.php
- GDPR admin processing: app/src/Controllers/Admin/GdprAdminController.php
- Accessibility skip link and semantic main landmark: app/views/layouts/main.php

Submission Notes

This repository is prepared for submission:
- Temporary local helper/debug files removed
- Dockerized setup included
- README cleaned and simplified with clear evidence mapping
