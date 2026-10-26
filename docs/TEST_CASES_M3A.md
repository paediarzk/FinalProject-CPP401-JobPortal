# Black-Box Test Cases - Milestone 3a (Administrator Module): CORE SET

All 31 test cases in this report were executed manually in the browser (Microsoft Edge) on 23 October 2026 by Paedia Rizky Maulana. They come from the core set of the Administrator module test plan. The tests were chosen by risk: every main function (happy path), the rules that protect data and access (access control, status rules, session handling), and one representative test for each kind of attack. The test IDs were renumbered AD01-AD31 in the order of this report; the mapping to the original IDs of the full 78-case suite is given at the end. The other 47 test cases of the full suite are not included: 45 were not executed manually because they are low risk or covered by a similar test in this core set, and 2 core tests (original AD22 and AD77) were not executed. Evidence screenshots are documented in `Test_Cases_Admin_M3A.docx`.

## Preparation

Accounts: the administrator (`admin@jobportal.local`), Employer 1, Employer 2, Job seeker 1, Job seeker 2.

As Employer 1, three vacancies were posted ("Alpha Pending One", "Beta Pending Two", "Gamma Pending Three"); as Employer 2, one vacancy was posted ("Delta From Employer Two"). All were Pending. A second browser session was used for the session test. Console tests were run from the browser Console (F12) on the page named in the test.

Bulk data for the pagination tests (phpMyAdmin, SQL tab, database `jobportal`; `<employer id>` is the id of Employer 1 from table `users`):

```sql
INSERT INTO vacancies (employer_id, title, position_type, description, location, deadline, status)
SELECT <employer id>, CONCAT('Bulk Vacancy ', id), 'internship', 'A bulk vacancy used to test pagination of the moderation list.', 'Bandung', CURDATE() + INTERVAL 30 DAY, 'pending' FROM skills LIMIT 20;

INSERT INTO users (email, password_hash, role)
SELECT CONCAT('bulk', id, '@example.com'), (SELECT password_hash FROM users WHERE role='admin' LIMIT 1), 'job_seeker' FROM skills LIMIT 20;
INSERT INTO job_seekers (user_id, full_name) SELECT id, CONCAT('Bulk Person ', id) FROM users WHERE email LIKE 'bulk%@example.com';
```

Clean-up after the tests:

```sql
DELETE FROM users WHERE email LIKE 'bulk%@example.com';
DELETE FROM vacancies WHERE title LIKE 'Bulk Vacancy %';
```

To expire a vacancy: `UPDATE vacancies SET deadline = CURDATE() - INTERVAL 2 DAY WHERE id = <vacancy id>;`

## A. Access control

| ID | Scenario | Steps / Input | Expected Result | Actual Result | Status | Evidence |
|----|----------|---------------|-----------------|---------------|--------|----------|
| AD01 | Guest | Logged out, open `/admin/dashboard.php`, `/admin/vacancies.php`, `/admin/users.php` | Redirected to login each time | While logged out, each of the three addresses redirected to the login page, which displayed the message "Please log in first." | Pass | Figure AD01 |
| AD02 | Job seeker | Logged in as job seeker, open the same three addresses | 403 Forbidden each time | While logged in as a job seeker, the admin pages returned the page "403 Forbidden - you do not have access to this page." | Pass | Figure AD02 |
| AD03 | Employer | Logged in as employer, open the same three addresses | 403 Forbidden each time | While logged in as an employer, the admin pages returned the same page "403 Forbidden - you do not have access to this page." | Pass | Figure AD03 |
| AD04 | Administrator | Log in as administrator | The three pages open | After logging in as the administrator, the dashboard ("Welcome, Administrator"), the Moderate vacancies page and the Manage accounts page all opened normally | Pass | Figure AD04.1, AD04.2 & AD04.3 |

## B. Dashboard

| ID | Scenario | Steps / Input | Expected Result | Actual Result | Status | Evidence |
|----|----------|---------------|-----------------|---------------|--------|----------|
| AD05 | Account figures | Compare the "Job seekers" and "Employers" cards with table `users` | The numbers are equal; "x suspended" matches the suspended accounts | The dashboard showed 27 Job seekers and 4 Employers, each with "0 suspended", and the numbers matched the data in table `users` | Pass | Figure AD05 |
| AD06 | Vacancy figures | Compare the four status cards with table `vacancies` | The numbers are equal per status | The status cards showed 32 Pending approval, 0 Published, 0 Rejected and 3 Closed (35 vacancies in total), and the numbers matched table `vacancies` | Pass | Figure AD06 |
| AD07 | Waiting list | Look at "Waiting for your review" | Pending vacancies, oldest first, at most 5, each with a Review button | The "Waiting for your review" list showed 5 pending vacancies, oldest first (posted 2026-10-19), each with a Review button, and a link "View all pending (32)" | Pass | Figure AD07 |

## C. Moderation list

| ID | Scenario | Steps / Input | Expected Result | Actual Result | Status | Evidence |
|----|----------|---------------|-----------------|---------------|--------|----------|
| AD08 | Default tab | Open Moderate vacancies | The Pending tab is active; the number on each tab equals the database | The Pending tab was active by default, and the tabs showed Pending 32, Published 0, Rejected 0, Closed 3 and All 35, equal to the database | Pass | Figure AD08 |
| AD09 | Tabs | Click Published, Rejected, Closed, All | Each tab shows only that status ("All" shows everything) | The tabs Pending, Published, Rejected, Closed and All each filtered the list by status, and each tab displayed the number of vacancies in that status | Pass | Figure AD09 |
| AD10 | Search title | Search `gamma` | Only "Gamma Pending Three" (case does not matter) | Searching for "GAMMA" (typed in capital letters) returned only "Gamma Pending Three", with the text "Showing 1 of 1 vacancies", so the search is not case sensitive | Pass | Figure AD10 |
| AD11 | Pagination | Bulk data loaded | 15 rows on page 1, the rest on page 2; "Showing x of y" is correct | With the bulk data loaded, page 1 of the Pending tab showed 15 rows, the text "Showing 15 of 32 vacancies · page 1 of 3" and the pagination links Previous, 1, 2, 3 and Next | Pass | Figure AD11 |
| AD12 | HTML in a title | Title `<script>alert(1)</script> Test` | No popup; shown as plain text | A vacancy with the title `<script>alert(1)</script> Test` was posted. No popup appeared, and the title was displayed as plain text in the vacancy list | Pass | Figure AD12 |

## D. Review page and decisions

| ID | Scenario | Steps / Input | Expected Result | Actual Result | Status | Evidence |
|----|----------|---------------|-----------------|---------------|--------|----------|
| AD13 | Details | Click Review on a vacancy | Company, sector, employer e-mail, type, location, allowance (like 2.500.000), deadline, description, skills | The Review page displayed the company and sector, position type, location, allowance ("Not stated" for this vacancy, which has no allowance), application deadline, employer e-mail, posted date, job description and required skills, together with the Approve and Reject decision section | Pass | Figure AD13 |
| AD14 | Approve | Approve a pending vacancy | Success message; back to the Pending list; status Published; no reason stored | After clicking "Approve and publish", the message "The vacancy has been approved and is now published." was displayed and the Pending list was shown again. The tabs changed to Pending 31 and Published 1 | Pass | Figure AD14 |
| AD15 | Reject, empty reason | Press Reject with an empty reason | "Please give a reason of 5 to 500 characters."; status unchanged | Pressing Reject with an empty reason displayed "Please give a reason of 5 to 500 characters.", and the vacancy "Frontend Developer Intern" stayed "Pending approval" | Pass | Figure AD15 |
| AD16 | Reject | Reason "The description is too short. Please list the tasks." | Status Rejected; the reason is stored | The vacancy was rejected with that reason. The message "The vacancy has been rejected. The employer can read your reason and submit it again." was displayed, and the tabs changed to Pending 29 and Rejected 3 | Pass | Figure AD16 |
| AD17 | Employer sees the reason | Log in as the employer | The reason is shown under the status on My vacancies and on the Edit page | After logging in as the employer, the Edit vacancy page of the rejected vacancy displayed the warning "This vacancy was rejected. Saving your changes submits it for approval again." together with "Reason given by the administrator: The description is too short. Please list the tasks" | Pass | Figure AD17 |
| AD18 | Resubmit | The employer edits the rejected vacancy and saves | Status Pending again; the old reason is gone | After the employer saved the edit, the message "Your changes have been saved. Because the vacancy was already reviewed, it is pending approval again." was displayed, so the vacancy returned to Pending and the old reason no longer applied | Pass | Figure AD18 |
| AD19 | Take down confirmed | Repeat and press OK | Status Rejected; the employer sees the reason | A published vacancy was taken down with the reason "TEST REJECT 2" and the confirmation dialog was accepted with OK. The vacancy became Rejected, and the employer's Edit page displayed the rejection warning with "Reason given by the administrator: TEST REJECT 2" | Pass | Figure AD19 |
| AD20 | Expired pending | Expire a pending vacancy, open it | A note says it cannot be approved; the Approve button is disabled; Reject still works | After the deadline was set to a past date, the Review page displayed the note "The deadline has passed, so it cannot be approved. You can still reject it with a reason.", the "Approve and publish" button was disabled (greyed out), and the Reject form with the reason field and the Reject button was still available | Pass | Figure AD20 |
| AD21 | Wrong state | On a Published vacancy: Console `document.querySelector('form input[value="takedown"]').value='approve'`, enter a reason, press Take down | "...already handled or is no longer in the right state..."; status unchanged | On the Published vacancy "Project Management Intern", the action value was changed to `approve` through the Console and the reason "Testing33333" was entered. After pressing Take down, the message "This vacancy was already handled or is no longer in the right state for that decision." was displayed, and the vacancy stayed Published | Pass | Figure AD21.1 & AD21.2 |
| AD22 | Forged id | On a Pending vacancy: Console `document.querySelector('form input[value="reject"]').form.querySelector('input[name=id]').value='999999'`, enter a reason, press Reject | 404 "Vacancy not found."; the real vacancy is unchanged | After the id was changed to 999999 through the Console and Reject was pressed, the page displayed "Vacancy not found.", the Console showed `POST .../admin/vacancy_action.php 404 (Not Found)`, and the real vacancy was unchanged | Pass | Figure AD22 |
| AD23 | Missing CSRF | Console `document.querySelector('form input[value="reject"]').form.querySelector('input[name=csrf]').remove()`, enter a reason, press Reject | "Invalid form submission"; unchanged | After the hidden `csrf` input was removed through the Console and Reject was pressed, the system returned to the vacancy list with the message "Invalid form submission. Please reload the page and try again.", and the numbers on the tabs stayed the same (Pending 29, Published 1, Rejected 3, Closed 3, All 36) | Pass | Figure AD23 |

## E. Account management

| ID | Scenario | Steps / Input | Expected Result | Actual Result | Status | Evidence |
|----|----------|---------------|-----------------|---------------|--------|----------|
| AD24 | List | Open Manage accounts (bulk data loaded) | 15 rows per page, pagination, "Showing x of y accounts" is correct | With the bulk data loaded, the Manage accounts list displayed the accounts (Bulk Person 34 and below) with name, e-mail, role, status Active, joined date and a Suspend button, in pages of 15 rows with pagination | Pass | Figure AD24 |
| AD25 | Search | Search by part of an e-mail, a name and a company | Only the matching accounts | Searching for "japfa" returned only the 4 matching employer accounts (matched on e-mail and company name), with the text "Showing 4 of 4 accounts · page 1 of 1" | Pass | Figure AD25 |
| AD26 | Suspend | Press Suspend, press Cancel in the dialog; repeat and press OK | Cancel: nothing. OK: status Suspended, success message, you stay on the same filtered list | After Suspend was pressed and confirmed with OK, the message "The account has been suspended." was displayed, the account "PT Japfa Comfeed" showed the status Suspended with an Activate button, and the list stayed filtered on "japfa" | Pass | Figure AD26 |
| AD27 | Activate | Press Activate on that account | Status Active | After Activate was pressed, the message "The account has been activated." was displayed and the account "PT Japfa Comfeed" showed the status Active with a Suspend button again, on the same filtered list | Pass | Figure AD27 |
| AD28 | Own account | Console `document.querySelector('form input[value="suspend"]').form.querySelector('input[name=id]').value='<administrator id>'`, press Suspend | "You cannot suspend your own account."; still active | After the id was replaced with the administrator's own id through the Console and Suspend was pressed, the message "You cannot suspend your own account." was displayed, and the account stayed active | Pass | Figure AD28 |

## F. Suspended accounts and sessions

| ID | Scenario | Steps / Input | Expected Result | Actual Result | Status | Evidence |
|----|----------|---------------|-----------------|---------------|--------|----------|
| AD29 | Cannot log in | Suspend Job seeker 1, then try to log in | "This account has been suspended..." | After the account (testing@japfa.com) was suspended, a login attempt was rejected and the login page displayed "This account has been suspended. Please contact the administrator." | Pass | Figure AD29 |
| AD30 | Already logged in | In a private window log in as Job seeker 2 and stay on the dashboard. As administrator suspend that account. In the private window click any link | You are sent to the login page with "Your account has been suspended. Please contact the administrator." | While the user was logged in, the administrator suspended the account (japfatesting2@gmail.com). Clicking a link in the user's session sent the user to the login page with the message "Your account has been suspended. Please contact the administrator." | Pass | Figure AD30 |
| AD31 | Logout message | Log out with the Logout button | The login page shows "You have been logged out." | After the Logout button was pressed, the login page displayed the green message "You have been logged out." | Pass | Figure AD31 |

## G. Regression: earlier tests affected by Milestone 3a

| Earlier test | What changed |
|---|---|
| T21 (Milestone 1) | After logging out, the login page now shows "You have been logged out." (it was never shown before because of a bug) |
| T16 (Milestone 1) | The administrator dashboard now shows statistics and the waiting list instead of a placeholder |
| VC34 (Milestone 2-C) | A rejected vacancy that is saved again becomes Pending, and the administrator's reason disappears |
| VC10, VC31 | The employer's list now shows the administrator's reason under "Rejected" |
| Everything else | No change |

Re-run T15, T16, T21, VC34 and a login of all three roles after applying Milestone 3a.

Automated run during development (PHP 8.3, MariaDB 10.11, time zone Europe/Berlin like XAMPP): administrator module 144/144 (server side) and 24/24 (real browser); the ten Console commands above 10/10; regression on the user's Milestone 2-C files: Milestone 1 22/22, 2-A 29/29, Languages 27/27, CV 28/28, Education 55/55, Vacancies 98/98.

**Summary:** 31 test cases executed, 31 passed, 0 failed.

## Mapping to the original test IDs

| New ID | Original ID | New ID | Original ID | New ID | Original ID |
|---|---|---|---|---|---|
| AD01 | AD01 | AD02 | AD02 | AD03 | AD03 |
| AD04 | AD04 | AD05 | AD05 | AD06 | AD06 |
| AD07 | AD07 | AD08 | AD10 | AD09 | AD11 |
| AD10 | AD12 | AD11 | AD17 | AD12 | AD23 |
| AD13 | AD26 | AD14 | AD29 | AD15 | AD30 |
| AD16 | AD32 | AD17 | AD33 | AD18 | AD35 |
| AD19 | AD37 | AD20 | AD40 | AD21 | AD43 |
| AD22 | AD44 | AD23 | AD46 | AD24 | AD51 |
| AD25 | AD53 | AD26 | AD58 | AD27 | AD59 |
| AD28 | AD61 | AD29 | AD68 | AD30 | AD69 |
| AD31 | AD75 | | | | |