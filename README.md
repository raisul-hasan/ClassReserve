# ClassReserve

Minimal scaffold for ClassReserve, a campus room reservation web app using HTML/CSS/JS frontend and PHP + MySQL backend.

## Tech Stack

- Frontend: plain HTML, CSS, JavaScript
- Backend: PHP with PDO
- Database: MySQL

## Quick Setup With XAMPP

1. Install XAMPP, then start Apache and MySQL.
2. Create a database named `classreserve`.
3. Import `db/schema.sql` into the `classreserve` database.
4. Copy `config.php.example` to `config.php`.
5. Update `config.php` if your MySQL credentials are different.
6. Put this project in your XAMPP web root, for example:
   `C:/xampp/htdocs/classreserve`
7. Open:
   `http://localhost/classreserve/public/`

API endpoints are under `api/`, and the PDO helper is at `api/db.php`.

## Demo Login Accounts

Importing `db/schema.sql` creates these demo accounts.

All demo accounts use this password:

`ClassReserve123!`

| Role | Name | Email |
| --- | --- | --- |
| Admin | System Admin | `admin@classreserve.test` |
| Faculty | Dr. Sarah Johnson | `sarah.johnson@classreserve.test` |
| Faculty | Prof. David Lee | `david.lee@classreserve.test` |
| Faculty | Dr. Maria Garcia | `maria.garcia@classreserve.test` |

Public registration should be used for student or club accounts only. Faculty and admin accounts are seeded or created directly by an administrator.

## Next Steps

- Run the SQL in `db/schema.sql`.
- If you already imported an older schema, run `db/update_rooms_for_react.sql` once instead of recreating the database.
- If your database does not have forum/report tables yet, run `db/update_issues.sql` once.
- If you already ran `db/update_issues.sql` before issue attachments were added, run `db/update_issue_uploads.sql` once.
- If your database does not have notifications yet, run `db/update_notifications.sql` once.
- If your users table does not have account activation support yet, run `db/update_users_admin.sql` once.
- Log in with one of the demo faculty/admin accounts above.
- Add rooms, bookings, issue reports, and maintenance blocks for your local demo.
