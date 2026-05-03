# ClassReserve

Minimal scaffold for ClassReserve — a campus room reservation web app using HTML/CSS/JS frontend and PHP + MySQL backend.

Tech stack
- Frontend: plain HTML, CSS, JavaScript
- Backend: PHP (PDO)
- Database: MySQL

Quick setup (Windows, XAMPP/Laragon)
1. Install XAMPP or Laragon, start Apache & MySQL.
2. Create a database `classreserve` and import `db/schema.sql`.
3. Copy `config.php.example` to `config.php` and update DB credentials.
4. Put the `public/` folder into your web root (e.g., `C:/xampp/htdocs/classreserve`).

API endpoints are under `api/` and a small PDO helper lives at `api/db.php`.

This repository contains minimal files only — no design assets. Teammates can clone and run locally.

Next steps
- Run the SQL in `db/schema.sql`.
- Seed initial rooms and an admin user if desired.
