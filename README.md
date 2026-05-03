# Livestock Health and Vaccination Tracking System (LHVTS)

A web-based animal health management system for rural Namibia that supports livestock registration, vaccination tracking, disease reporting, user role access, and offline-capable operation.

## Key Features

- Role-based access for **farmers**, **veterinarians**, **extension officers**, and **administrators**
- Livestock registration and profile management
- Vaccination record entry and tracking
- Disease reporting and outbreak tracking
- Dashboard views tailored by user role
- PWA/offline support via service worker
- Secure database access using PDO and session-based authentication

## Technology Stack

- PHP (backend)
- MySQL / MariaDB (database)
- HTML, Bootstrap 5, CSS, JavaScript (frontend)
- XAMPP for local development
- Service Worker for offline support

## Repository Structure

- `index.php` — entry point and role-based routing
- `config/db.php` — database connection settings
- `api/` — backend endpoints for authentication, livestock, vaccination, disease reporting, dashboard, and profiles
- `pages/` — user-facing pages for login, dashboards, livestock registration, vaccination recording, and disease reporting
- `includes/` — shared header/footer templates
- `assets/` — custom styles and JavaScript files
- `service-worker.js` — offline caching and PWA support
- `sql/livestock_db.sql` — database schema and sample data

## Setup

1. Install **XAMPP** and start **Apache** and **MySQL**.
2. Copy the project folder into `htdocs`.
3. Import `sql/livestock_db.sql` into MySQL using phpMyAdmin or the MySQL CLI.
4. Update database credentials in `config/db.php` if needed.
5. Open the project in your browser, for example:
   - `http://localhost/VETERINARY_SERVICES_AND_LIVESTOCK HEALTH_SYSTEM/`

## Usage

- Visit `login.php` to sign in.
- `register.php` is available for new user registration.
- After login, users are redirected to dashboards based on role:
  - `admin_dashboard.php`
  - `farmer_dashboard.php`
  - `vet_dashboard.php`
  - `dashboard.php`
- Farmers can register livestock, track vaccinations, and view their animals.
- Vets and extension officers can review disease reports and vaccination data.

## Notes

- This project is designed for local development and testing in an XAMPP environment.
- For production use, enable HTTPS, secure session handling, and configure stronger password storage.

## Contribution

Feel free to extend the system by adding new API endpoints, improving offline sync, or implementing advanced reporting and analytics.

## License

This repository does not include a formal license. Add one if you want to clarify reuse terms.
