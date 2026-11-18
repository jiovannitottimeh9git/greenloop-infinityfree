# GreenLoop — Waste Collection Management System

GreenLoop is a PHP/MySQL web application with an HTML, CSS and JavaScript frontend.

## Local setup with XAMPP

1. Extract the project folder as `greenloop` inside `C:\xampp\htdocs\`.
2. Start **Apache** and **MySQL** in XAMPP.
3. Open `http://localhost/phpmyadmin`.
4. Import `greenloop/database/schema.sql`.
5. The SQL file creates the database `wastewise_db`. The project still uses this database name internally, so do not rename it unless you also update `config/database.php` and the SQL file.
6. Open `http://localhost/greenloop/setup-admin.php` to create the first administrator account.
7. Sign in at `http://localhost/greenloop/admin-login.php`.
8. Open the public site at `http://localhost/greenloop/` or `http://localhost/greenloop/frontend/index.html`.

Use **Create new admin account** on the admin login page to open the protected account form. An existing administrator must sign in before creating another account. If no administrator exists yet, the link opens the initial setup form.

## Driver account setup

Driver accounts are created by an administrator; there is no public driver registration form.

1. Sign in as administrator.
2. On the admin dashboard, scroll to **Driver Management**.
3. Enter the driver's full name, email, phone, optional licence number and a temporary password of at least 8 characters.
4. Click **Create driver account**.
5. Give the driver their sign-in email and password securely.
6. The driver signs in at `http://localhost/greenloop/driver-login.php`.
7. Active drivers appear in the assignment dropdown when the administrator assigns a collection request and vehicle.

The Driver Management section also lists registered drivers and allows an administrator to activate or deactivate an account. A driver with an active collection assignment cannot be deactivated until that assignment is completed or reassigned.

## Driver workflow

1. An administrator creates a driver account and adds a vehicle.
2. A customer submits a collection request.
3. The administrator assigns the request, driver and vehicle.
4. The driver signs in and sees the assigned job in the driver dashboard.
5. The driver starts the collection and allows browser location access.
6. The driver's device sends real GPS coordinates to the PHP API while the page remains open.
7. The driver marks the pickup complete.

GPS access requires `localhost` during local testing or HTTPS on a deployed website. The driver should use a device with location services enabled and keep the dashboard open while sharing location.

## Technical notes

- Frontend: HTML, CSS and JavaScript.
- Backend: PHP (PDO).
- Database: MySQL/MariaDB.
- API: `api.php`.
- Database connection: `config/database.php`.
- Database schema: `database/schema.sql`.
- The database is currently named `wastewise_db` for compatibility with the existing SQL and PHP configuration, even though the product name is GreenLoop.

For production, use HTTPS, change default database credentials, remove `setup-admin.php` after creating the administrator, and configure secure backups.
