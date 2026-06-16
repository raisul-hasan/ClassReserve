# ClassReserve

ClassReserve is a classroom availability and reservation system for students, clubs, faculty, and admins.

## Tech Stack

- Frontend: HTML, CSS, JavaScript
- Backend: PHP with PDO
- Database: MySQL / MariaDB
- Local server: PHP built-in server or XAMPP Apache

## Local Run Guide

### 1. Start MySQL

Start MySQL from XAMPP, Laragon, or your local MySQL service.

Default database settings:

```php
host: localhost
database: classreserve
username: root
password: empty
```

The app reads these settings from:

- `config/database.php`
- `config.php`

If these files are missing, copy the examples and update the values:

```powershell
Copy-Item config/database.example.php config/database.php
Copy-Item config.php.example config.php
```

### 2. Prepare The Database

Create a MySQL database named `classreserve`.

If your local database is empty, import the project SQL from phpMyAdmin or the MySQL CLI. The project includes SQL files in the `db/` and `database/` folders.

### 3. Start The PHP Server

From the project root, run:

```powershell
php -S 127.0.0.1:8000 -t .
```

Then open:

```text
http://127.0.0.1:8000/public/login.php
```

The root URL also redirects to the login page:

```text
http://127.0.0.1:8000
```

## Sample Login Credentials

Use these demo accounts on the login page:

| Role | Email | Password |
| --- | --- | --- |
| Admin | `admin@classreserve.local` | `password` |
| Faculty | `faculty@classreserve.local` | `faculty123` |
| Club | `club@classreserve.local` | `student123` |
| Student | `student@classreserve.local` | `student123` |

## Common Problems

### PHP Command Not Found

Install XAMPP or PHP, then make sure PHP is available in your system `PATH`.

You can check with:

```powershell
php -v
```

### Database Connection Error

Check that:

- MySQL is running.
- The `classreserve` database exists.
- `config/database.php` has the correct username and password.
- The database tables have been imported.

### Port 8000 Already In Use

Use another port:

```powershell
php -S 127.0.0.1:8080 -t .
```

Then open:

```text
http://127.0.0.1:8080/public/login.php
```

## Main Folders

- `public/` - browser-facing pages and assets
- `api/` - PHP API endpoints
- `config/` - database configuration
- `includes/` - shared PHP helpers
- `db/` and `database/` - SQL schema files
- `admin/`, `faculty/`, `student/` - role-specific pages
