# Black-Box Test Cases - Milestone 1 (Authentication)
Use these as the basis for Chapter 5 (Testing). Record Actual Result + Pass/Fail yourself when you run them in your browser.

| ID | Scenario | Steps / Input | Expected Result |
|----|----------|---------------|-----------------|
| T01 | Seeker registers (valid data) | Fill all fields correctly | Redirected to login, success message; rows in `users` + `job_seekers` |
| T02 | Duplicate email | Register same email again (even with different letter case) | Error "already registered" |
| T03 | Weak password | Password `abc` | Error: at least 8 characters |
| T04 | Password mismatch | Different confirm password | Error: confirmation does not match |
| T05 | Invalid email | `not-an-email` | Error: valid email required |
| T06 | Tampered form value | Change education level option value via DevTools | Error: invalid education level |
| T07 | XSS in input | Name = `<script>alert(1)</script>` | No alert; text shown escaped |
| T08 | Missing CSRF token | Submit form without token | "Invalid form submission" |
| T09 | Employer registers | Valid company data | Success, rows in `users` + `employers` |
| T10 | Seeker login | Correct credentials | Redirect to seeker dashboard |
| T11 | Dashboard shows name | After T10 | Shows seeker's full name |
| T12 | Role guard (employer area) | Seeker opens /employer/dashboard.php | 403 Forbidden |
| T13 | Role guard (admin area) | Seeker opens /admin/dashboard.php | 403 Forbidden |
| T14 | Logged-in user opens login | Open /login.php while logged in | Redirected to own dashboard |
| T15 | Employer login | Correct credentials | Employer dashboard |
| T16 | Admin login | Seed admin credentials | Admin dashboard |
| T17 | Generic login error | Wrong password vs unknown email | Identical error message |
| T18 | Guest on protected page | Open /seeker/dashboard.php without login | Redirected to login |
| T19 | Session regeneration | Compare PHPSESSID before/after login | Value changes |
| T20 | Logout by URL | Open /logout.php directly (GET) | Still logged in |
| T21 | Logout by button | Click Logout (POST) | Session ended, redirected to login |
| T22 | SQL injection | Email `' OR '1'='1' --` | Login fails normally |

Automated run during development: 22/22 passed (PHP 8.3 + MariaDB 10.11).
