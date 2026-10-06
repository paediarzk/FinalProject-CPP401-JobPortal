# Implementation Journal – FirstStep (CPP401)

This journal records problems encountered during implementation, their causes, and how they were resolved. It is used as source material for Chapter 6 (Problems Faced and Critical Evaluation).

---

## Entry 1 – Project tables imported into the wrong database

- **Date:** 2026-10-06
- **Phase:** Milestone 1 – Environment and database setup
- **Problem:** After importing `schema.sql` through phpMyAdmin, the eight project tables (`users`, `job_seekers`, `employers`, `skills`, `job_seeker_skills`, `vacancies`, `vacancy_skills`, `applications`) appeared inside the MySQL system database named `mysql`, while the `jobportal` database remained empty.
- **Probable cause:** The import was started while the system database `mysql` was selected in the phpMyAdmin sidebar. The exact mechanism was not investigated further.
- **Solution:** The eight project tables were dropped from the `mysql` database using a `DROP TABLE` statement that named each table explicitly, so that no system table was affected. The result was verified with a query on `information_schema.tables`. The phpMyAdmin sidebar was then refreshed, because it does not update automatically after SQL commands. Finally, `schema.sql` and `seed.sql` were imported again with the `jobportal` database selected.
- **Result:** The `jobportal` database contained the eight tables and the administrator account. The application ran correctly and the black-box tests were executed.
- **Lesson learned:** Always confirm the target database before importing. Never modify or delete tables in system databases such as `mysql`. Verify the result with a query instead of relying only on the sidebar display.

---

## Entry 2 – First push to GitHub failed (authentication error)

- **Date:** 2026-10-03
- **Phase:** Milestone 1 – Version control setup
- **Problem:** The first `git push -u origin main` failed with the message "Password authentication is not supported for Git operations".
- **Cause:** No Git credential helper was configured (`git config --global credential.helper` returned an empty value), so no sign-in prompt appeared. In addition, GitHub no longer accepts account passwords for Git operations over HTTPS.
- **Solution:** The credential helper was configured with `git config --global credential.helper manager`, and the account was then authenticated using [browser sign-in / personal access token – keep the one you actually used].
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

## Entry template (copy for new entries)

## Entry N – Short title of the problem

- **Date:** YYYY-MM-DD
- **Phase:** Milestone X – module or activity
- **Problem:** What happened, including the exact error message if there was one.
- **Cause:** Why it happened. Write "Probable cause" if it was not confirmed.
- **Solution:** What was done to fix it, in order.
- **Result:** How it was verified that the problem was solved.
- **Lesson learned:** What to do differently next time.