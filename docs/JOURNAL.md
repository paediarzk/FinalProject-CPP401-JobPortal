# Implementation Journal – FirstStep (CPP401)

This journal records problems encountered during implementation, their causes, and how they were resolved. It is used as source material for Chapter 6 (Problems Faced and Critical Evaluation).

---

## Entry 1 – Project tables imported into the wrong database

- **Date:** 2026-10-03
- **Phase:** Milestone 1 – Environment and database setup
- **Problem:** After importing `schema.sql` through phpMyAdmin, the eight project tables (`users`, `job_seekers`, `employers`, `skills`, `job_seeker_skills`, `vacancies`, `vacancy_skills`, `applications`) appeared inside the MySQL system database named `mysql`, while the `jobportal` database remained empty.
- **Probable cause:** The import was started while the system database `mysql` was selected in the phpMyAdmin sidebar. The exact mechanism was not investigated further.
- **Solution:** The eight project tables were dropped from the `mysql` database using a `DROP TABLE` statement that named each table explicitly, so that no system table was affected. The result was verified with a query on `information_schema.tables`. The phpMyAdmin sidebar was then refreshed, because it does not update automatically after SQL commands. Finally, `schema.sql` and `seed.sql` were imported again with the `jobportal` database selected.
- **Result:** The `jobportal` database contained the eight tables and the administrator account. The application ran correctly and the black-box tests were executed.
- **Lesson learned:** Always confirm the target database before importing. Never modify or delete tables in system databases such as `mysql`. Verify the result with a query instead of relying only on the sidebar display.

---

## Entry 2 – First push to GitHub failed (authentication error)

- **Date:** 2026-10-05
- **Phase:** Milestone 1 – Version control setup
- **Problem:** The first `git push -u origin main` failed with the message "Password authentication is not supported for Git operations".
- **Cause:** No Git credential helper was configured (`git config --global credential.helper` returned an empty value), so no sign-in prompt appeared. In addition, GitHub no longer accepts account passwords for Git operations over HTTPS.
- **Solution:** The credential helper was configured with `git config --global credential.helper manager`, and the account was then authenticated using browser sign-in.
- **Result:** The push succeeded and the Milestone 1 source code became available in the private repository.
- **Lesson learned:** HTTPS access to GitHub requires token-based or browser-based authentication. Access tokens must never be shared, pasted into code, or committed to the repository.

---

## Entry 3 – Navbar logo too large and buttons unchanged after styling

- **Date:** 2026-10-05
- **Phase:** Milestone 1 – User interface customisation
- **Problem:** After replacing the text brand with the FirstStep logo image, the logo was displayed too large and the navbar buttons kept their previous light colour.
- **Probable cause:** The logo-size CSS rule and the button class changes were not applied completely (partial find-and-replace and/or cached stylesheet in the browser).
- **Solution:** The whole `<nav>` block in `includes/header.php` was replaced in a single step, with the logo height set directly on the `<img>` element and the button classes updated together. The page was reloaded with a hard refresh (Ctrl+F5).
- **Result:** The logo displayed at the intended size and the button colours matched the navbar theme.
- **Lesson learned:** Keeping the layout in one shared file (`header.php`) allows a change to be made in a single place. Browser cache must be bypassed when testing style changes, and images should have an explicit height.

---

## Entry 4 – Misspelled application name and incomplete shared page header

- **Date:** 2026-10-13
- **Phase:** Milestone 2-A – Review of the Milestone 1 files before implementation
- **Problem:** A review of the final Milestone 1 files found three defects. (1) The constant `APP_NAME` in `config/config.php` was misspelled ("FisrtStep"); the name appeared in the browser tab titles, the footer, and the logo alternative text. (2) `includes/header.php` contained two `<title>` elements. (3) The header did not load the shared theme stylesheet (`theme.css`), the web font, or the component stylesheet (`app.css`); pages shown after login therefore used default styling and the `btn-brand` button class was undefined.
- **Probable cause:** The header was customised only in its navigation block when the logo was replaced, and its `<head>` section was not updated when the shared theme was introduced for the login and register pages. The misspelling was a typing error that was not noticed because the name is displayed in small text.
- **Solution:** The constant was corrected to "FirstStep", and the `<head>` block of `header.php` was replaced with one block containing a single title, the web font, Bootstrap, `theme.css`, `app.css`, and `style.css`, in that order.
- **Result:** The correct name is shown in the tab titles and footer, the "Save changes" button and the skill chips are styled, and the page contains a single `<title>`.
- **Lesson learned:** Shared layout files must be reviewed whenever a new stylesheet is introduced. A short review of the previous milestone's files before starting a new milestone detects inconsistencies early.

---

## Entry 5 – Browser console rejected the "allow pasting" command

- **Date:** 2026-10-13
- **Phase:** Milestone 2-A – Black-box and security testing
- **Problem:** While preparing the security test cases that modify the submitted form through the browser developer console, typing `allow paste` and then `allow pasting` returned "Uncaught SyntaxError: Unexpected identifier".
- **Cause:** The first attempt contained a misspelling. In the second attempt the phrase was typed before the browser had blocked any paste; the console recognises `allow pasting` only after a blocked paste attempt, and otherwise treats it as ordinary JavaScript.
- **Solution:** the test command was pasted first and it was accepted without the phrase / the phrase was typed after the browser warning / short commands were typed manually.
- **Result:** The test commands could be executed and the corresponding test cases were carried out.
- **Lesson learned:** Verify the behaviour of testing tools before the test session, and document the exact commands in the test plan so that they can be pasted instead of retyped.

---

## Entry 6 – Error markers shown by the phpMyAdmin SQL editor (migration 005)

- **Date:** 2026-10-18
- **Phase:** Milestone 2-B – Education migration
- **Problem:** After pasting migration 005 into the SQL tab of phpMyAdmin, the editor showed several red error markers (for example "Unexpected token"), before the query was executed.
- **Cause:** The markers come from the editor's built-in syntax checker, not from the database server. The checker does not recognise some MariaDB syntax used in the script (CHECK constraints and SQL written inside quoted text for a prepared statement).
- **Solution:** The database was exported first and the old data was photographed; the script was then executed with the "Go" button. The result was verified by browsing the new table and the structure of `job_seekers`.
- **Result:** [Confirm what you saw after pressing Go.]
- **Lesson learned:** Warnings of the editor must be distinguished from errors returned by the server. Back up before destructive changes and verify the result with queries.


## Entry template (copy for new entries)
## Entry N – Short title of the problem

- **Date:** YYYY-MM-DD
- **Phase:** Milestone X – module or activity
- **Problem:** What happened, including the exact error message if there was one.
- **Cause:** Why it happened. Write "Probable cause" if it was not confirmed.
- **Solution:** What was done to fix it, in order.
- **Result:** How it was verified that the problem was solved.
- **Lesson learned:** What to do differently next time.

## Milestone Summary

| Milestone | Start | End | Approx. hours per week | Notes |
|---|---|---|---|---|
| M1 – Foundation and authentication | 2026-10-01 | 2026-10-06 | | Tag m1-foundation |
| M2-A – Job seeker profile | 2026-10-09 | 2026-10-14| | |
| M2-B – Languages, CV, Education | 2026-10-14 | 2026-10-18 | | |
| M2-C – Employer vacancies | 2026-10-18 | 2026-10-20 | | Tag m2-profile-vacancy |

**Software versions:** PHP 8.2.4 · MariaDB 10.4.28 · Apache 2.4.56 · XAMPP 8.2.4 (Control Panel v3.3.0) · Bootstrap 5.3.3 (via CDN)
**Operating system:** Windows 11 Home · **Browser used for testing:** Microsoft Edge 155.0.4283.45 · **Editor:** Visual Studio Code v1.141.0 · **Version control:** Git version 2.56.0.windows.1

## Decision Log

### Decision 1 – Skills are selected from a predefined list
- **Decision:** Job seekers and (later) employers choose skills from a predefined list of 37 skills stored in the `skills` table.
- **Alternatives considered:** Free-text skills entered by users; user-created skills approved by an administrator.
- **Rationale:** Prevents duplicates and inconsistent spelling (supports normalisation), matches Table 3.1 ("selected from a skill list"), and avoids moderation work before the administrator module (Milestone 3).
- **Status:** Implemented in Milestone 2-A (migration 002).

### Decision 2 – Only a Languages section is added to the profile
- **Decision:** The profile gets a Languages section with dynamic cards: language and overall level are required; speaking, reading and writing are optional. Languages come from a predefined list of 22 international languages, up to 8 per profile. The "I am fluent" checkbox only fills the four levels in the browser and is not stored. "Other skills" is not added.
- **Alternatives considered:** Free-text or user-created skills (duplicates and moderation workload).
- **Status:** Implemented (migrations 003 and 004).

### Decision 3 – New vacancies start as "pending"
- **Decision:** A vacancy posted by an employer has the status "pending" until an administrator approves it (Milestone 3).
- **Rationale:** Reduces fake or inappropriate postings before they become visible to job seekers.
- **Status:** To be implemented in Milestone 2-C (status field) and Milestone 3 (approval).

### Decision 4 – Education stored as multiple entries in its own table
- **Decision:** Education is stored in the table `educations` (1 to 5 entries per job seeker: school or university, degree, field of study, GPA on a 4.00 scale, start and end year). The education level and institution entered at registration become the first entry; the two old columns of `job_seekers` were removed after their data was copied.
- **Alternatives considered:** Keeping both the old columns and the new table (duplicated data); a single education entry only; an official list of institutions (needs an external dataset and maintenance).
- **Rationale:** Employers filter fresh graduates by university, field of study, GPA and graduation year; a separate table avoids repeating groups and keeps the schema normalised.
- **Status:** Implemented (migration 005).

### Decision 5 – Vacancy status rules
- **Decision:** A new vacancy is "pending" until an administrator approves it. Editing a published or rejected vacancy returns it to "pending". Closing is final: a closed vacancy cannot be edited or reopened, and vacancies are never deleted.
- **Rationale:** Approved content must not change without a new review, and deleting a vacancy would remove the application history that Milestone 3 will record.
- **Status:** Implemented (Milestone 2-C); approval and rejection by the administrator follow in Milestone 3.

### Decision 6 – Vacancy input rules
- **Decision:** Allowance is a whole amount in IDR per month (optional); required skills are chosen from the predefined list (up to 10, optional); the deadline must lie between today and one year ahead, using Jakarta time.
- **Rationale:** Prevents typing errors and keeps data comparable; Jakarta time avoids dependence on the server's time zone setting.
- **Status:** Implemented (Milestone 2-C).