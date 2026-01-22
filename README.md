📌 Project Overview

The application allows:

Clients to view pages and (later) book appointments

The system to be extended with:

Authentication (login/register)

Hairdresser availability

Appointment booking

Admin management

The project is designed with scalability and maintainability in mind.

🧱 Architecture

This project follows the MVC (Model–View–Controller) pattern:

Model
Handles database logic (repositories, entities)

View
PHP templates located in app/views/, rendered through a layout

Controller
Handles HTTP requests, validation, and response rendering

Key Architectural Decisions

Single Front Controller (public/index.php)

Routing via FastRoute

Views are not directly accessible from the browser

Layout-based rendering for consistent UI

🐳 Docker Setup

The application runs entirely inside Docker containers.

Services Used

PHP (FPM) – Application runtime

Nginx – Web server

MariaDB – Database

PHPMyAdmin – Database management (optional)

Prerequisites

Docker Desktop (Windows / macOS) or Docker Engine (Linux)

▶️ How to Run the Project

From the project root (where docker-compose.yml is located):

docker compose up --build

Access the application

Web application:
👉 http://localhost

PHPMyAdmin:
👉 http://localhost:8080

Database credentials (development)
Host: mariadb
Database: developmentdb
Username: developer
Password: secret123


⚠️ Database initialization scripts are located in app/database/init/ and are executed automatically on first run.

📁 Project Structure
app/
├── public/
│   ├── index.php        # Front controller
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
├── views/
│   ├── layouts/
│   │   └── main.php
│   ├── home.php
│   ├── contact.php
│   └── hello.php
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

The application implements and/or prepares for the following security measures:

Centralized routing via front controller

Output escaping using htmlspecialchars (XSS prevention)

Server-side input validation

Session-based handling

CSRF protection ready to be added for forms

PDO prepared statements (for database access)

♿ Accessibility (WCAG)

Accessibility is considered through:

Semantic HTML (nav, main, header)

Proper form labels

Keyboard-accessible navigation

Responsive design via Bootstrap

Clear error and success feedback messages

📜 GDPR Considerations

Only necessary user data will be stored

Passwords will be securely hashed

No tracking cookies are used

Sessions are used only for functional purposes

User data can be extended to support deletion on request

📦 Technologies Used

PHP 8+

Nginx

MariaDB

Docker & Docker Compose

FastRoute (routing)

Bootstrap 5 (UI framework)

🚧 Current Status

✅ Docker setup complete
✅ MVC foundation implemented
✅ Routing and layout rendering complete
🚧 Database layer (PDO + repositories) – next step
🚧 Authentication
🚧 Appointment booking system

👤 Author

Shiva Lamichhane
Web Development Student
Project: HairDressor Salon PHP Application

