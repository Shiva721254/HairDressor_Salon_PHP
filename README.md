💇 Hairdresser Salon Appointment System

PHP MVC Application

📌 Project Overview

This web application is a Hairdresser Salon management system that allows:

Clients to browse hairdressers and services and book appointments

Administrators to manage hairdressers, services, availability, and appointments

The system to dynamically calculate availability and prevent double bookings

The project is built with scalability, maintainability, and security in mind and follows professional web-development practices.

🧱 Architecture (MVC)

This project follows the Model–View–Controller (MVC) architectural pattern.

Model

Handles database access and business logic

Implemented using Repository classes (AppointmentRepository, AvailabilityRepository, etc.)

Business rules such as slot calculation and overlap prevention are encapsulated here

View

PHP templates located in app/Views/

Rendered through a layout-based system for consistent UI

Contains JavaScript for dynamic UI updates (no page reloads)

Controller

Handles HTTP requests and responses

Performs validation and authorization

Calls repositories and returns HTML or JSON responses

Key Architectural Decisions

Single Front Controller (public/index.php)

FastRoute for routing

Views are not directly accessible from the browser

Controllers never contain raw SQL

Clear separation of concerns

🐳 Docker Setup

The application runs fully inside Docker containers.

Services Used

PHP 8 (FPM) – Application runtime

Nginx – Web server

MariaDB – Database

phpMyAdmin – Database management (development only)

Prerequisites

Docker Desktop (Windows/macOS) or Docker Engine (Linux)

▶️ Running the Project

From the project root:

docker compose up --build

Access Points

Web application:
👉 http://localhost

phpMyAdmin:
👉 http://localhost:8080

Database Credentials (Development)

Host: mariadb

Database: developmentdb

Username: developer

Password: secret123

📌 Database initialization scripts are located in:
app/database/init/
They are executed automatically on first run.

📁 Project Structure
app/
├── public/
│   ├── index.php            # Front controller
│   └── assets/
│       └── js/
│           └── app.js
│
├── src/
│   ├── Controllers/
│   ├── Core/
│   ├── Repositories/
│   ├── Services/
│
├── Views/
│   ├── layouts/
│   │   └── main.php
│   ├── appointments/
│   ├── admin/
│   ├── hairdressers/
│   └── home.php
│
├── database/
│   └── init/
│       ├── 001_schema.sql
│       └── 002_seed.sql
│
docker-compose.yml
nginx.conf
PHP.Dockerfile
README.md

🔐 Security Considerations

The application implements multiple security measures:

Centralized routing via front controller

PDO prepared statements (SQL injection prevention)

Output escaping using htmlspecialchars() (XSS prevention)

Server-side validation for all user input

Session-based authentication

Role-based authorization (admin vs client)

CSRF protection implemented for all state-changing POST forms

Passwords hashed using password_hash() and verified with password_verify()

🔌 API Endpoints (JSON)

The application exposes JSON API endpoints used by JavaScript.

Examples

GET /api/slots
Returns available appointment time slots in JSON format

GET /api/hairdressers/{id}/availability
Returns weekly working days for a hairdresser (0–6)

These endpoints are consumed asynchronously using fetch() and update the UI without page reloads.

🧠 JavaScript Functionality

JavaScript is used to enhance usability and interactivity:

Appointment slots are loaded dynamically via API calls

Time dropdown updates without page reload

Invalid dates (non-working days) are blocked in real time

API responses are processed as JSON

UI feedback is shown immediately for errors or availability

🎨 CSS & UI

Bootstrap 5 is used as the CSS framework

Responsive layout using Bootstrap grid system

Consistent styling via layout templates

Visual feedback via hover and focus states

Basic UI transitions improve usability

♿ Accessibility (WCAG)

Accessibility considerations include:

Semantic HTML (header, nav, main, form)

Proper <label> usage for all form fields

Keyboard-accessible navigation

Responsive design for different screen sizes

Dynamic updates use aria-live to notify screen readers

Clear error and success feedback messages

📜 GDPR Considerations

The application respects GDPR principles:

Only necessary user data is stored (email, appointments)

Passwords are securely hashed

No tracking or analytics cookies are used

Sessions are used strictly for functionality

Data can be extended to support deletion upon user request

Database access is restricted and secured

🚀 Current Status

✅ Docker setup complete
✅ MVC architecture implemented
✅ Authentication (login/register)
✅ Role-based access (admin / client)
✅ Hairdresser availability management
✅ Appointment booking with availability checks
✅ Admin CRUD management
✅ JSON API endpoints
✅ JavaScript-driven dynamic UI
✅ Security & accessibility considerations applied

📦 Technologies Used

PHP 8+

Nginx

MariaDB

Docker & Docker Compose

FastRoute

Bootstrap 5

JavaScript (Fetch API)

👤 Author

Shiva Lamichhane
Web Development Student
Project: Hairdresser Salon PHP MVC Application

✅ Rubric Status (Internal Check)

CSS: ✅

Sessions: ✅

Security: ✅

MVC: ✅

API: ✅

JavaScript: ✅

Accessibility & GDPR: ✅