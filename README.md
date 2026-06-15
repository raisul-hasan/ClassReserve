# ClassReserve

ClassReserve is a campus room reservation system with a React/Vite/TypeScript frontend, a PHP API backend using PDO sessions, and a MySQL/MariaDB database.

The app is now database-driven. Rooms, bookings, calendar events, maintenance blocks, issues, notifications, users, admin dashboard stats, approval metadata, and audit logs come from the PHP API and `db/classreserve.sql`. The frontend no longer falls back to mock/localStorage data when the API fails; it shows an error message instead.

## Tech Stack

| Layer    | Stack                    |
| -------- | ------------------------ |
| Frontend | React, Vite, TypeScript  |
| Backend  | PHP API with PDO         |
| Database | MySQL or MariaDB         |
| Local    | XAMPP recommended        |

## Project Structure

| Path                        | Purpose                                           |
| --------------------------- | ------------------------------------------------- |
| `Prototype Design/`         | Main React/Vite frontend                          |
| `public/`                   | PHP-based frontend pages and shared CSS/JS        |
| `api/`                      | PHP API endpoints                                 |
| `db/classreserve.sql`       | Complete fresh database install and seed          |
| `public/uploads/`           | Uploaded booking/issue attachments                |
| `config.php.example`        | Example backend database config                   |

## Quick Setup

1. Start Apache and MySQL in XAMPP.
2. Copy `config.php.example` to `config.php`.
3. Keep the default config if XAMPP MySQL uses `root` with no password.
4. Import `db/classreserve.sql` in phpMyAdmin.

### Option 1: Run the React/Vite frontend

```powershell
cd "Prototype Design"
npm install
npm.cmd run dev -- --host 127.0.0.1 --port 5174
```

Open `http://127.0.0.1:5174`.

The default API base is `http://localhost/classreserve/api`. Override it in `Prototype Design/.env.local` with `VITE_API_BASE_URL` if your Apache path is different.

### Option 2: Run the new PHP HTML/CSS frontend

Place the repository root inside your Apache web directory (for example, `C:\xampp\htdocs\classreserve`).

Open `http://localhost/classreserve/public/`.

This app uses the same backend API under `http://localhost/classreserve/api/`.

### Optional: Import seed data

`db/classreserve.sql` already includes a full set of demo data (users, rooms, equipment links, bookings, maintenance, issues, notifications). To import the canonical seed data use:

```powershell
# From the repository root (Windows):
mysql -u root -p classreserve < db/classreserve.sql

# Or import `db/classreserve.sql` using phpMyAdmin (choose the `classreserve` database).
```

If you previously downloaded `db/sample_data.sql`, it is redundant — the canonical seed is `db/classreserve.sql`. Adjust `user_id` values only if your local user rows differ.

## API Endpoints

| Endpoint                | Purpose                                             |
| ----------------------- | --------------------------------------------------- |
| `api/auth.php`          | Session, login, logout, student/club/faculty signup |
| `api/rooms.php`         | Room list and admin room management                 |
| `api/bookings.php`      | Booking list, create, approve, reject, cancel       |
| `api/calendar/events`   | Future backend route planned for calendar events    |
| `api/dashboard.php`     | Admin/faculty dashboard analytics                   |
| `api/audit_logs.php`    | Admin-only audit log feed                           |
| `api/maintenance.php`   | Maintenance blocks                                  |
| `api/issues.php`        | Issue posts, comments, status, maintenance creation |
| `api/notifications.php` | User notifications                                  |
| `api/profile.php`       | Profile and password updates                        |
| `api/users.php`         | Admin user management                               |

Calendar data is currently assembled in the frontend from `bookings.php` and `maintenance.php`. The service is structured so it can later switch to `GET /calendar/events`.

## Demo Accounts

Admin demo accounts use `Admin@123!`.

Faculty, club, and student demo accounts use `ClassReserve123!`.

| Role    | Name              | Email                      | Password           |
| ------- | ----------------- | -------------------------- | ------------------ |
| Admin   | System Admin      | `admin@classreserve.local` | `Admin@123!`       |
| Admin   | Administrator     | `admin@uni.edu`            | `Admin@123!`       |
| Faculty | Dr. Alice Johnson | `alice.johnson@uni.edu`    | `ClassReserve123!` |
| Faculty | Prof. Motaharul   | `motaharul@uni.edu`        | `ClassReserve123!` |
| Faculty | Anika Tasmin      | `anika.tasnim@uni.edu`     | `ClassReserve123!` |
| Club    | Computing Club    | `computerclub@uni.edu`     | `ClassReserve123!` |
| Student | Farha Rahman      | `f@uni.edu`                | `ClassReserve123!` |
| Student | Ayesha Khan       | `ayesha.khan@uni.edu`      | `ClassReserve123!` |
| Student | Rahul Sen         | `rahul.sen@uni.edu`        | `ClassReserve123!` |
| Student | Nadia Islam       | `nadia.islam@uni.edu`      | `ClassReserve123!` |
| Student | Tanvir Ahmed      | `tanvir.ahmed@uni.edu`     | `ClassReserve123!` |

Admin accounts are system-managed. Student, club, and faculty users can register from the frontend.

## Database & Migrations

Use `db/classreserve.sql` for a fresh installation. The database features a fully relational model.

### Schema & Relational Tables
- **Rooms & Equipment**: Transitioned from comma-separated text lists to a fully relational structure (`equipment` and `room_equipment` tables). The backend dynamically joins and processes this relationally while keeping full interface compatibility.
- **Check-ins**: `bookings` contains `checkin_code`, `checked_in_at`, and `no_show` to track check-in status and automate no-show expiries.
- **Brute-Force Rate Limiting**: The `failed_logins` table stores login failures per IP and email to enforce rate-limiting.
- **Approvals & Audit**: `bookings.reviewed_by`, `bookings.reviewed_at`, and `bookings.rejection_reason` track approvals, and the `audit_logs` table logs all major events.

> [!NOTE]
> **Self-Healing Migrations**: When any API endpoint loads, `api/db.php` checks the database schema and automatically creates any missing tables/columns and migrates existing room equipment data. You do not need to re-import the database if upgrading from an older version!

## Advanced Upgrades Implemented

### 1. Calendar Conflict Visualization
- **Overlap Validation**: Bookings are checked in SQL against other approved bookings and active maintenance blocks for the same room on the same day/time.
- **Room-Wise Daily Schedule**: The calendar's Day view groups events dynamically by room (e.g. `Room A-301`) for clear scheduling.
- **Visual Alert Badges**: Renders soft red `⚠️ Conflict detected` and soft yellow `⚠️ Maintenance conflict` badges for conflicting items.
- **Status Distinction**: Unique styling distinguishes Student, Club, Faculty, and Maintenance bookings.

### 2. QR Code Check-in & No-Show Expiry
- **Check-in Codes**: Generation of a secure check-in code (`CR-XXXXXX`) upon booking approval.
- **QR Code Rendering**: Embeds a dynamic QR code in the booking details panel using `api.qrserver.com`.
- **Manual & QR Check-in**: A "Check In Now" action lets booking owners check in directly.
- **15-Min No-Show Sweep**: Automatically sweeps the database on request, flagging approved bookings that are unchecked 15 minutes past their start time as `✗ Marked as No-Show` and freeing the room.

### 3. Pure-PHP Socket SMTP Mailer
- **Dependency-Free SMTP Client**: Built directly in `api/mail.php` using TCP/IP sockets (`fsockopen`), avoiding heavy external mailer packages.
- **Notifications on Action**: Dispatches email alerts when a booking is created, approved, rejected, maintenance is scheduled, or issue statuses are updated.
- **Robust Failures**: Gracefully logs failures and falls back without breaking client API requests if SMTP is disabled or unreachable.

### 4. Advanced Security Features
- **Brute-force protection**: Limits failed logins to 5 attempts per 15 minutes, blocking further tries with a `429 Too Many Requests` code.
- **Strong password verification**: Validates that all passwords at registration and profile update contain uppercase, lowercase, numbers, and special characters.
- **Double-Submit Cookie CSRF Protection**: Generates a CSRF cookie on load and enforces header validation (`X-CSRF-Token`) for all writing endpoints (POST, PUT, DELETE).
- **File Upload Scan**: Scan text file uploads to verify no PHP (`<?php`) or JavaScript (`<script`) tags exist in the file.
- **Global Exception Safety**: Suppresses PHP stack traces on server exceptions, returning a clean 500 JSON response and logging errors to PHP's error log.

## Testing & Verification
Refer to [TESTING.md](file:///i:/GitHub/ClassReserve/docs/TESTING.md) for a comprehensive list of step-by-step verification procedures for each of these features.

## Validation

Frontend build:

```powershell
cd "Prototype Design"
npm.cmd run build
```

PHP lint with XAMPP PHP:

```powershell
C:\xampp\php\php.exe -l api\auth.php
C:\xampp\php\php.exe -l api\bookings.php
C:\xampp\php\php.exe -l api\dashboard.php
C:\xampp\php\php.exe -l api\audit_logs.php
```

To use `php` directly, add `C:\xampp\php` to your Windows PATH and open a new terminal.
