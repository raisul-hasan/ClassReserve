# ClassReserve

<<<<<<< HEAD
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
=======
ClassReserve is a university classroom booking system built with PHP, MySQL, HTML, CSS, and JavaScript. It supports student room searches and booking requests, faculty/admin approval workflows, issue reporting, notifications, and role-based dashboards.

## Features

- Role-based login for Admin, Faculty, Club, and Student users
- Student dashboard with:
  - Available room summary
  - Search Available Room
  - Upcoming Booking
  - Today's Available Rooms
  - Recent bookings and quick actions
- Room search by date, time, capacity, and facilities
- Booking request creation with pending/approved/rejected/cancelled statuses
- Admin/faculty booking approval and rejection
- Notification bell with unread count and mark-as-read support
- Notices page for booking/account updates
- Calendar view for bookings and maintenance
- Forum/issues page for classroom problems
- Collapsible sidebar navigation
- Profile and settings pages

## Requirements

- PHP 8.x
- MySQL or MariaDB
- XAMPP recommended on Windows
- A browser

## Database Setup

1. Start MySQL from XAMPP.
2. Make sure `config/database.php` exists.
3. Default local database config:

```php
return [
    'host'     => 'localhost',
    'dbname'   => 'classreserve',
    'username' => 'root',
    'password' => '',
    'charset'  => 'utf8mb4',
];
```

4. Import the database:

```bash
php install.php
```

Or import manually:

```bash
mysql -u root < db/classreserve.sql
```

If the database already exists, you may see a message that tables already exist. That means the database is already installed.

## How To Run

From the project folder:

```bash
php -S 127.0.0.1:8001 -t .
```

On this machine with XAMPP PHP, the working command is:

```powershell
& "C:\Program Files\Xamp\php\php.exe" -d session.save_path="$env:TEMP" -S 127.0.0.1:8001 -t .
>>>>>>> origin/Riche01
```

Then open:

```text
<<<<<<< HEAD
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
=======
http://127.0.0.1:8001/
```

The app redirects to:

```text
http://127.0.0.1:8001/public/login.php
```

## Demo Accounts

| Role | Email | Password |
|---|---|---|
>>>>>>> origin/Riche01
| Admin | `admin@classreserve.local` | `password` |
| Faculty | `faculty@classreserve.local` | `faculty123` |
| Club | `club@classreserve.local` | `student123` |
| Student | `student@classreserve.local` | `student123` |

<<<<<<< HEAD
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
=======
## Main Pages

- Login: `/public/login.php`
- Dashboard: `/public/dashboard.php`
- New Booking: `/public/new-booking.php`
- Search Rooms: `/public/search-rooms.php`
- My Bookings: `/public/my-bookings.php`
- Calendar: `/public/calendar.php`
- Notices: `/public/notices.php`
- Forum: `/public/forum.php`
- Profile: `/public/profile.php`
- Settings: `/public/settings.php`
- Admin Panel: `/public/admin.php`

## Project Structure

```text
api/                 Backend API endpoints
config/              Database configuration
db/                  SQL database file
public/              Main modern portal pages
public/assets/       CSS and JavaScript for the portal
public/includes/     Shared public layout and bootstrap helpers
admin/               Legacy/admin PHP pages
faculty/             Faculty pages
student/             Student pages
includes/            Shared legacy helpers
install.php          Database installer
index.php            Redirects to login
```

## Notes

- Keep `config/database.php` local and do not commit real production credentials.
- If sessions fail under `C:\Program Files\Xamp\tmp`, run PHP with `-d session.save_path="$env:TEMP"` as shown above.
- Use `db/classreserve.sql` as the main database schema/seed file.
>>>>>>> origin/Riche01
