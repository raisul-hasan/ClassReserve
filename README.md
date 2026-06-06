# ClassReserve

ClassReserve is a campus room reservation system with a React/Vite frontend, PHP API backend, and MySQL/MariaDB database.

The current repo uses one database install file:

`db/classreserve.sql`

That file creates the full database, tables, relationships, demo users, rooms, bookings, maintenance blocks, issue posts, comments, and notifications.

## Tech Stack

- Frontend: React, Vite, TypeScript
- Backend: PHP with PDO sessions
- Database: MySQL or MariaDB
- Local server: XAMPP is recommended on Windows

## Project Structure

| Path                  | Purpose                            |
| --------------------- | ---------------------------------- |
| `Prototype Design/`   | Main React/Vite frontend           |
| `api/`                | PHP API endpoints                  |
| `db/classreserve.sql` | Complete fresh database install    |
| `public/uploads/`     | Uploaded booking/issue attachments |
| `config.php.example`  | Example backend database config    |

## Quick Setup

1. Start Apache and MySQL in XAMPP.
2. Copy `config.php.example` to `config.php`.
3. Keep the default config if your XAMPP MySQL user is `root` with no password.
4. Import `db/classreserve.sql` in phpMyAdmin.
5. From `Prototype Design/`, install dependencies if needed:

```powershell
npm install
```

6. Run the frontend:

```powershell
npm run dev -- --host 127.0.0.1 --port 5174
```

7. Open:

`http://127.0.0.1:5174`

The PHP API is served by Apache from the repo/XAMPP location and reads database settings from `config.php`.

## API Notes

Important endpoints:

| Endpoint                | Purpose                                         |
| ----------------------- | ----------------------------------------------- |
| `api/auth.php`          | Login, logout, session, registration            |
| `api/rooms.php`         | Room list and admin room management             |
| `api/bookings.php`      | Booking list, create, approve, reject, cancel   |
| `api/maintenance.php`   | Maintenance blocks                              |
| `api/issues.php`        | Classroom issue posts, comments, status updates |
| `api/notifications.php` | User notifications                              |
| `api/profile.php`       | Profile and password updates                    |
| `api/users.php`         | Admin user management                           |

Registration is enabled for `student`, `club`, and `faculty` accounts. Admin accounts are system-managed and must be seeded or created by an existing admin.

## Demo Accounts

Admin demo accounts use:

`Admin@123!`

Faculty, club, and student demo accounts use:

`ClassReserve123!`

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

The seeded database also includes approved, pending, rejected, and cancelled bookings, plus maintenance blocks, issue posts, comments, and notifications.

## Database

Use only:

`db/classreserve.sql`

Older split files like `schema.sql`, `seed.sql`, and `update_*.sql` were merged and removed. Re-importing `classreserve.sql` recreates the database from scratch, so export any local data first if you need to keep it.

## Validation

Frontend build:

```powershell
cd "Prototype Design"
npm run build
```

PHP lint on this machine uses XAMPP PHP directly:

```powershell
C:\xampp\php\php.exe -l api\auth.php
C:\xampp\php\php.exe -l api\bookings.php
```

To make `php` work without the full path, add this directory to your Windows PATH:

`C:\xampp\php`

After opening a new terminal, this should work:

```powershell
php -v
php -l api\auth.php
```

## Current Backend Behavior

- Students, clubs, and faculty can create accounts manually.
- Admin self-registration is blocked.
- Student and club users can see approved bookings from everyone and their own non-approved bookings.
- Faculty and admin users can see the full booking queue.
- Pending and approved bookings are checked for time conflicts.
- Maintenance blocks prevent overlapping bookings.
- Booking approvals/rejections create notifications.
- Issue comments and issue status changes create notifications.
