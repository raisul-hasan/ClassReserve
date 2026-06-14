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

| Path                  | Purpose                                    |
| --------------------- | ------------------------------------------ |
| `Prototype Design/`   | Main React/Vite frontend                   |
| `api/`                | PHP API endpoints                          |
| `db/classreserve.sql` | Complete fresh database install and seed   |
| `public/uploads/`     | Uploaded booking/issue attachments         |
| `config.php.example`  | Example backend database config            |

## Quick Setup

1. Start Apache and MySQL in XAMPP.
2. Copy `config.php.example` to `config.php`.
3. Keep the default config if XAMPP MySQL uses `root` with no password.
4. Import `db/classreserve.sql` in phpMyAdmin.
5. Start the frontend:

```powershell
cd "Prototype Design"
npm install
npm.cmd run dev -- --host 127.0.0.1 --port 5174
```

Open `http://127.0.0.1:5174`.

The default API base is `http://localhost/classreserve/api`. Override it in `Prototype Design/.env.local` with `VITE_API_BASE_URL` if your Apache path is different.

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

## Database

Use only `db/classreserve.sql` for a fresh install. It creates the database, tables, relationships, demo accounts, rooms, bookings, maintenance blocks, issues, comments, notifications, booking review fields, and audit logs.

Important tables added or upgraded:

- `bookings.reviewed_by`, `bookings.reviewed_at`, and `bookings.rejection_reason` track approval/rejection decisions.
- `audit_logs` records login, booking creation, approval, rejection, cancellation, room creation/update, maintenance creation, and issue status updates.

Re-importing `classreserve.sql` recreates the database from scratch, so export any local data first if you need to keep it.

## Current Behavior

- Students and clubs see approved bookings plus their own pending/rejected/cancelled bookings.
- Faculty and admin can review the booking queue.
- Approvals and rejections store reviewer name, review time, and rejection reason when applicable.
- Admin dashboard stats are loaded from `api/dashboard.php`.
- Faculty dashboard uses real room, booking, approval, and analytics data.
- Admin audit logs are available at `/admin/audit-logs`.
- Pending and approved bookings are checked for booking and maintenance conflicts.
- Notifications are created for booking decisions and issue updates.

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
