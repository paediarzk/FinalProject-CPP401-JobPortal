# FirstStep – Internship & Entry-Level Job Portal (CPP401 Final Project)

FirstStep is a web-based job portal that connects job seekers with internship and
entry-level vacancies. It is written in plain procedural PHP with a MariaDB database
and Bootstrap 5.

## Features

| Role | Main functions |
|---|---|
| Job seeker | Register, manage profile, education, skills, languages and CV (PDF); search and filter vacancies; apply; track application status |
| Employer | Register, manage company profile; post, edit and close vacancies; review applications and applicant CVs; update application status |
| Administrator | Dashboard statistics; approve, reject or take down vacancies (with reason); search, suspend and reactivate accounts |

Public pages: homepage, vacancy search (keyword, position type, location, pagination)
and vacancy detail. Only vacancies that are **published** and whose deadline has not
passed are visible to the public.

## Technology

PHP 8.2.4 (minimum 8.1) · MariaDB 10.4.28 (PDO, prepared statements) · Apache 2.4.56 ·
XAMPP 8.2.4 · Bootstrap 5.3.3 (CDN) · Plus Jakarta Sans

## Setup (Windows + XAMPP)

1. Install XAMPP, open the Control Panel and start **Apache** and **MySQL**.
2. Copy this folder to `C:\xampp\htdocs\jobportal`.
   If the folder name differs, change `BASE_URL` in `config/config.php`.
3. Open http://localhost/phpmyadmin and use the **Import** tab (do not select any
   system database such as `mysql` first).
4. Import `database/schema.sql` (it creates the database `jobportal` with collation
   `utf8mb4_unicode_ci`), then import `database/seed.sql` (creates the administrator).
5. Open http://localhost/jobportal/
6. Administrator login: `admin@jobportal.local` / `Admin#12345` (change after first login).

`schema.sql` already contains every table and column up to the final version
(including `educations`, `languages`, `moderation_note` and `applications`).
The files in `database/migrations/` (002–006) are only needed to upgrade an
older database that was created before those changes.

**CV storage.** Uploaded CVs are stored outside the web root, by default in
`C:\xampp\jobportal_storage\cv`, and are delivered only after an ownership check.
The folder is created automatically on the first upload.

## Folder structure

```
jobportal/
  index.php, vacancies.php, vacancy.php      public pages
  login.php, logout.php, register_*.php      authentication
  seeker/      job seeker pages (profile, CV, apply, applications)
  employer/    employer pages (vacancies, received applications, CV access)
  admin/       administrator pages (dashboard, moderation, accounts)
  includes/    shared logic: auth, helpers, vacancy, application, cv, pagination, layout
  config/      settings and PDO connection
  database/    schema.sql, seed.sql, migrations/
  assets/      css, js, images
  docs/        JOURNAL.md and test-case documents (TEST_CASES_*.md)
```

## Security overview

Password hashing · session authentication with role checks on every request ·
suspended accounts rejected on the next request · PDO prepared statements ·
escaped output · CSRF tokens on state-changing forms · CV validation and
protected delivery.

## Documentation

- `docs/JOURNAL.md` – implementation journal, decision log and milestone summary.
- `docs/TEST_CASES_*.md` – test cases and results per milestone
  (M1, M2-A, M2-B, M2-B Education, M2-C, M3-A, M3-B, M3-C).

## Milestones and Git tags

| Milestone | Scope | Tag |
|---|---|---|
| M1 | Foundation, authentication | `m1-foundation` |
| M2-A to M2-C | Profiles, languages, CV, education, vacancies | `m2-profile-vacancy` |
| M3-A | Administrator module | `m3a-admin` |
| M3-B | Homepage, search, filters, detail, pagination | `m3b-search` |
| M3-C | Applications, tracking, employer review | `m3-search-applications` |
| Final | Final submission | `v1.0-final` |
