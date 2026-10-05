# JobPortal - Internship & Entry-Level Job Portal (Final Project CPP401)

## Milestone 1 (25%) - Foundation & Authentication
Includes: database schema (8 tables, 3NF), registration (job seeker / employer),
login/logout, role-based access control, security basics (PDO prepared statements,
password hashing, CSRF tokens, output escaping, session regeneration).

## Setup (Windows + XAMPP)
1. Install XAMPP. Open the XAMPP Control Panel, Start **Apache** and **MySQL**.
2. Copy this whole folder to `C:\xampp\htdocs\jobportal`.
3. Open http://localhost/phpmyadmin -> tab **Import** -> choose `database/schema.sql` -> Go.
4. Import `database/seed.sql` the same way (creates the admin account).
5. Open http://localhost/jobportal/
6. Admin login: `admin@jobportal.local` / `Admin#12345` (change after first login).

If your folder name differs, edit `BASE_URL` in `config/config.php`.

## Folder structure
```
jobportal/
  config/      settings + database connection (PDO)
  includes/    bootstrap, helpers, auth logic, header/footer layout
  database/    schema.sql, seed.sql
  seeker/ employer/ admin/   role-specific pages (dashboards for now)
  assets/      css (js/images later)
  docs/        test cases, notes for the report
  *.php        public pages (index, login, register_*, logout)
```

## Git (first time)
```
git init
git add .
git commit -m "Milestone 1: foundation and authentication"
```
