# Black-Box Test Cases - Milestone 1 (Authentication)

Basis for Chapter 5 (Testing). All test cases were executed manually in the browser. Evidence screenshots are documented in `BLACK_BOX_TESTING_DOCUMENTATION.docx`.

| ID | Scenario | Steps / Input | Expected Result | Actual Result | Status | Evidence |
|----|----------|---------------|-----------------|---------------|--------|----------|
| T01 | Seeker registers (valid data) | Fill all fields correctly | Redirected to login, success message; rows in `users` + `job_seekers` | Registration succeeded and the user was redirected to the login page | Pass | Figure T01 |
| T02 | Duplicate email | Register same email again (even with different letter case) | Error "already registered" | The system displayed "This email address is already registered." and rejected the registration | Pass | Figure T02 |
| T03 | Weak password | Password `abc` | Error: at least 8 characters | The system displayed an error that the password must be at least 8 characters; registration rejected | Pass | Figure T03 |
| T04 | Password mismatch | Different confirm password | Error: confirmation does not match | The system displayed an error that the password confirmation does not match; registration rejected | Pass | Figure T04 |
| T05 | Invalid email | `not-an-email` | Error: valid email required | The system displayed an error that a valid email address is required; registration rejected | Pass | Figure T05 |
| T06 | Tampered form value | Change education level option value via DevTools | Error: invalid education level | The system displayed an invalid education level error; registration rejected | Pass | Figure T06 |
| T07 | XSS in input | Name = `<script>alert(1)</script>` | No alert; text shown escaped | No alert appeared; the input was displayed as escaped plain text | Pass | Figure T07 |
| T08 | Missing CSRF token | Submit form without token | "Invalid form submission" | The system displayed "Invalid form submission" and the request was rejected | Pass | Figure T08 |
| T09 | Employer registers | Valid company data | Success, rows in `users` + `employers` | Registration succeeded; rows were created in `users` and `employers` | Pass | Figure T09 |
| T10 | Seeker login | Correct credentials | Redirect to seeker dashboard | Login succeeded and the user was redirected to the seeker dashboard | Pass | Figure T10 |
| T11 | Dashboard shows name | After T10 | Shows seeker's full name | The seeker dashboard displayed the seeker's full name | Pass | Figure T11 |
| T12 | Role guard (employer area) | Seeker opens /employer/dashboard.php | 403 Forbidden | The system returned a 403 Forbidden page | Pass | Figure T12 |
| T13 | Role guard (admin area) | Seeker opens /admin/dashboard.php | 403 Forbidden | The system returned a 403 Forbidden page | Pass | Figure T13 |
| T14 | Logged-in user opens login | Open /login.php while logged in | Redirected to own dashboard | The user was redirected to their own dashboard | Pass | Figure T14 |
| T15 | Employer login | Correct credentials | Employer dashboard | Login succeeded and the employer dashboard was displayed | Pass | Figure T15 |
| T16 | Admin login | Seed admin credentials | Admin dashboard | Login succeeded and the admin dashboard was displayed | Pass | Figure T16 |
| T17 | Generic login error | Wrong password vs unknown email | Identical error message | Both cases displayed the same generic error message | Pass | Figure T17 |
| T18 | Guest on protected page | Open /seeker/dashboard.php without login | Redirected to login | The guest was redirected to the login page | Pass | Figure T18 |
| T19 | Logout by URL | Open /logout.php directly (GET) | Still logged in | The session remained active; the user was not logged out | Pass | Figure T19 |
| T20 | Logout by button | Click Logout (POST) | Session ended, redirected to login | The session ended and the user was redirected to the login page | Pass | Figure T20 |
| T21 | SQL injection | Email `' OR '1'='1' --` | Login fails normally | Login failed with the normal error message; no bypass occurred | Pass | Figure T21 |

**Summary:** 21 test cases executed, 21 passed, 0 failed.
Automated run during development: 22/22 passed (PHP 8.3 + MariaDB 10.11).
