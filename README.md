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
- Log in with one of the demo faculty/admin accounts above.
- Add rooms and bookings for your local demo.
