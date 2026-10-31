# Black-Box Test Cases - Milestone 3c (Applications, Tracking, Employer Review, CV Access): CORE SET

All 25 test cases in this report were executed manually in the browser. The tests were chosen by risk: the main functions (apply, track, review, change status), the rules that protect data and access (one application per vacancy, ownership checks, CV access control, final statuses), and one representative test for each kind of tampering and injection. The expected behavior was taken from the Milestone 3c rules below.

## Preparation

Accounts: administrator, Employer 1, Employer 2, Job seeker 1 (with a CV uploaded, PDF), Job seeker 2 (without a CV at first). Employer 1 and Employer 2 each had at least one Published vacancy with a deadline in the future. For TR04, 16 or more Published vacancies were created (bulk SQL from `TEST_CASES_M3B.md`) and the job seeker applied for all of them. Tests that need the browser Console were run from the Console (F12) on the page named in the test; a private window was used as a second browser session.

Rules under test:
- Only a job seeker with a CV can apply, only for a public vacancy (Published, deadline today or later), and only once per vacancy.
- New application = Submitted. The employer may move it to Under review, Accepted or Rejected. Accepted and Rejected are final.
- An employer sees only applications for their own vacancies, and only they can open the applicant's CV.

## Test cases

| ID | Scenario | Steps / Input | Expected Result | Actual Result | Status | Evidence |
|---|---|---|---|---|---|---|
| AP01 | Guest on the detail page | Open `vacancy.php?id=<id>` without logging in | Prompt to log in or register; no Apply button | A prompt to log in or register was shown; there was no Apply button | Pass | FIGURE AP01 |
| AP02 | Job seeker without CV | Log in as Job seeker 2, open the detail page, then try to apply | Asked to upload a CV on the profile; no application is created | The page asked for a CV upload on the profile; no application was created | Pass | FIGURE AP02 |
| AP03 | Apply | Log in as Job seeker 1, click "Apply for this vacancy" | Redirected to My applications; the application has status Submitted | Redirected to My applications; the application was listed with status Submitted | Pass | FIGURE AP03 |
| AP04 | Detail page after applying | Open the same vacancy again | "You applied on ..." with the status; the Apply button is gone | "You applied on ..." and the status were shown; the Apply button was gone | Pass | FIGURE AP04 |
| AP05 | Duplicate application | Apply to the same vacancy again (second tab or the browser Back button) | No second application; message that you already applied | No second application was created; the message that the vacancy was already applied for was shown | Pass | FIGURE AP05 |
| AP06 | Employer and administrator | Open a detail page as Employer 1 and as the administrator | Only "Only job seekers can apply"; no form | Only "Only job seekers can apply" was shown for both roles; there was no form | Pass | FIGURE AP06 |
| AP07 | Tampered form (Console) | As Job seeker 1, send a POST to `seeker/apply.php` with `vacancy_id`, a valid `csrf`, plus `status=accepted` and `job_seeker_id=<another user>` | Extra fields ignored: the application belongs to you with status Submitted | The extra fields were ignored; the application belonged to Job seeker 1 with status Submitted | Pass | FIGURE AP07 |
| AP08 | Vacancy no longer open | Open the detail page, then (as Employer) close the vacancy or let its deadline pass, then click Apply | Refused with "no longer open"; no application created | Applying was refused with the "no longer open" message; no application was created | Pass | FIGURE AP08 |
| TR01 | My applications | Job seeker 1 and Job seeker 2 each open My applications | Each sees only their own applications, with title, company, date and status badge | Each job seeker saw only their own applications, with title, company, date and status badge | Pass | FIGURE TR01 |
| TR02 | Status follows the employer | Employer changes a status (see ST01), job seeker reloads My applications and the dashboard | New status is shown; dashboard counts match | The new status was shown in My applications and the dashboard counts matched | Pass | FIGURE TR02 |
| TR03 | Vacancy closed after applying | Employer closes a vacancy that has applications | The application stays in the list, flagged "Vacancy no longer open"; the title is not a link | The application stayed in the list with the "Vacancy no longer open" flag; the title was not a link | Pass | FIGURE TR03 |
| TR04 | Pagination | A job seeker with 16 or more applications | 15 per page, 2 pages; `page=abc` shows page 1, `page=999` shows the last page; no error | 15 applications were shown per page over 2 pages; `page=abc` showed page 1 and `page=999` showed the last page; there was no error | Pass | FIGURE TR04 |
| EM01 | Applications received | Employer 1 opens Applications received | Only applicants for Employer 1's vacancies; status tabs and the vacancy filter work; counts are correct | Only applicants for Employer 1's vacancies were listed; the status tabs and the vacancy filter worked and the counts were correct | Pass | FIGURE EM01 |
| EM02 | Other employer's application page | As Employer 2, open `employer/application_view.php?id=<Employer 1's application id>` | "Application not found." (404) | "Application not found." was shown | Pass | FIGURE EM02 |
| EM03 | Applicant details | Employer 1 opens an application | E-mail, phone, city, about, education, skills, languages and "Open CV (PDF)" are shown | E-mail, phone, city, about, education, skills, languages and "Open CV (PDF)" were shown | Pass | FIGURE EM03 |
| EM04 | Open the CV | Click "Open CV (PDF)" | The PDF opens in a new tab | The PDF opened in a new tab | Pass | FIGURE EM04 |
| EM05 | CV access control | Open `employer/application_cv.php?id=<id>` as Employer 2, as a guest, and as a job seeker | 404, login page, and 403 | Employer 2 received "CV not found." (404), the guest was redirected to the login page, and the job seeker received 403 Forbidden | Pass | FIGURE EM05 |
| EM06 | CV file is not public | Try to open the CV file by guessing a URL under `/jobportal/` | Not reachable (the file is stored outside `htdocs`) | The CV file could not be reached by URL ("Not Found"); it is stored outside `htdocs` | Pass | FIGURE EM06 |
| EM07 | Escaping | Give a job seeker the bio `<script>alert(1)</script>`, apply, and review as employer | Shown as text; no alert | The bio was shown as plain text and no alert appeared | Pass | FIGURE EM07 |
| ST01 | Normal flow | Submitted, then "Start reviewing", then "Accept" | Each step succeeds; after Accept the buttons are gone and the page says the decision is final | Each step succeeded; after Accept the buttons were gone and the page said the decision is final | Pass | FIGURE ST01 |
| ST02 | Final status | In the Console, send POST `status=rejected` (and `under_review`) for an Accepted application; also `status=bogus` | Refused; the status does not change | All requests were refused; the status stayed Accepted | Pass | FIGURE ST02 |
| ST03 | CSRF and method | POST to `employer/application_action.php` without a `csrf` value; open the same URL with GET | No change | The status did not change; "Invalid form submission" was shown | Pass | FIGURE ST03 |
| ST04 | Job seeker deletes the CV later | After applying, the job seeker deletes the CV; the employer opens the application | "The applicant has removed the CV."; no CV link; the CV URL gives 404 | "The applicant has removed the CV." was shown without a CV link; the CV URL returned "CV not found." (404) | Pass | FIGURE ST04 |
| RG01 | Regression M3a | Administrator dashboard (Applications count), moderation, accounts | Unchanged behavior; the Applications number matches reality | The behavior was unchanged; the Applications number matched the real count | Pass | FIGURE RG01 |
| RG02 | Regression M3b and earlier | Search and filters, detail page for a non-public id (404), login of all three roles, employer vacancy form, CV upload and delete | Unchanged behavior | The behavior was unchanged | Pass | FIGURE RG02 |

**Summary:** 25 test cases executed, 25 passed, 0 failed.