# Black-Box Test Cases - Milestone 2-C (Employer Vacancies)

Tester: Paedia Rizky Maulana  
Date: 19 October 2026  
Browser: Microsoft Edge



You need: two employer accounts (Employer 1 and Employer 2), one job seeker account, and the administrator account.

Until the administrator module exists (Milestone 3), status changes are made in phpMyAdmin (SQL tab, database `jobportal`):

```sql
UPDATE vacancies SET status='published' WHERE id=<vacancy id>;
UPDATE vacancies SET status='rejected' WHERE id=<vacancy id>;
UPDATE vacancies SET deadline = CURDATE() - INTERVAL 2 DAY WHERE id=<vacancy id>;
```

Console tests: press F12, open the Console tab (type `allow pasting` only if the browser blocks pasting). Run each command on the vacancy form page, then press Submit.


## A. Access control

| ID | Scenario | Steps / Input | Expected Result | Actual Result | Status | Evidence |
|---|---|---|---|---|---|---|
| VC01 | Guest | Logged out, open `/employer/vacancies.php` and `/employer/vacancy_form.php` | Redirected to login | The guest was redirected to the login page when attempting to open both employer vacancy pages. | Pass | Figure VC01 |
| VC02 | Job seeker | Logged in as job seeker, open both pages | 403 Forbidden | The system returned a 403 Forbidden response when a job seeker attempted to open both employer vacancy pages. | Pass | Figure VC02 |
| VC03 | Administrator | Logged in as administrator, open both pages | 403 Forbidden | The system returned a 403 Forbidden response when an administrator attempted to open both employer vacancy pages. | Pass | Figure VC03 |

## B. Dashboard and list

| ID | Scenario | Steps / Input | Expected Result | Actual Result | Status | Evidence |
|---|---|---|---|---|---|---|
| VC04 | New employer dashboard | Log in as an employer without vacancies | Four cards (Pending, Published, Rejected, Closed) all 0; text “You have not posted any vacancy yet” | The employer dashboard displayed four status cards (Pending, Published, Rejected and Closed), each showing 0, along with the message “You have not posted any vacancy yet”. | Pass | Figure VC04 |
| VC05 | Empty list | Open My vacancies | Message and a “Post your first vacancy” button | The My vacancies page displayed an empty-state message and a “Post your first vacancy” button. | Pass | Figure VC05 |
| VC06 | Counts | Create several vacancies, change some statuses with the SQL above | Dashboard numbers equal the number of vacancies per status in the database | The dashboard counts matched the number of vacancies in each status in the database after the vacancy statuses were changed. | Pass | Figures VC06.1 & VC06.2 |
| VC07 | Latest vacancies | Have more than 5 vacancies | The dashboard lists only the 5 newest | The dashboard displayed only the five newest vacancies. | Pass | Figure VC07 |
| VC08 | Status labels | Have one vacancy in each status | Labels “Pending approval”, “Published”, “Rejected”, “Closed” with different colours | The vacancies displayed the labels “Pending approval”, “Published”, “Rejected” and “Closed” with different colours. | Pass | Figure VC08 |
| VC09 | Deadline passed | Use the Expire SQL on a pending or published vacancy | The list shows “Deadline passed” under the date | The vacancy list displayed “Deadline passed” beneath the deadline date for the expired vacancy. | Pass | Figure VC09 |
| VC10 | Only my vacancies | Employer 2 opens My vacancies | Employer 1’s vacancies are not listed | Employer 2 could see only its own vacancies; Employer 1’s vacancies were not listed. | Pass | Figure VC10 |

## C. Posting a vacancy

| ID | Scenario | Steps / Input | Expected Result | Actual Result | Status | Evidence |
|---|---|---|---|---|---|---|
| VC11 | Valid vacancy | Title “Frontend Developer Intern”, Internship, Jakarta, 2500000, a deadline in 30 days, a description of at least 20 characters, 3 skills | Redirect to the list with “submitted for approval”; status Pending; vacancy row and 3 rows in `vacancy_skills` | The valid vacancy was submitted successfully, the list displayed “submitted for approval”, the vacancy status was Pending, and the vacancy and its three skill links were saved in the database. | Pass | Figure VC11 |
| VC12 | Status is always pending | Same as VC11, then use the console to add a hidden `status=published` field before submitting | The vacancy is still pending | After a forged status value of “published” was added through the browser console, the vacancy remained Pending. | Pass | Figures VC12.1 & VC12.2 |
| VC13 | Forged owner | Use the console to add a hidden `employer_id=2` field (another employer’s ID) before submitting | The vacancy belongs to the logged-in employer, not the other employer | After a forged employer_id value was submitted, the vacancy remained associated with the logged-in employer and was not assigned to the other employer. | Pass | Figures VC13.1 & VC13.2 |
| VC14 | Title length | Try title “ab”, then a title of 201 characters | Error “Position title must be between 3 and 200 characters.” | The system displayed “Position title must be between 3 and 200 characters.” for titles that were too short or too long. | Pass | Figure VC14 |
| VC15 | Position type | Leave “Select one”; alternatively add an invalid `full_time` option through the console | Error “Please choose a position type.” | The system displayed “Please choose a position type.” when no valid position type was selected. | Pass | Figures VC15.1 & VC15.2 |
| VC16 | Location | Try empty, 1 character, and 151 characters | Error “Location must be between 2 and 150 characters.” | The system displayed “Location must be between 2 and 150 characters.” for invalid location lengths. | Pass | Figure VC16 |
| VC17 | Allowance formats accepted | Try `2500000`, `2.500.000`, `2,500,000`, `0`, and empty | Accepted; stored as `2500000.00`, `0.00`, or empty (`NULL`) | The accepted allowance formats were processed correctly and stored as 2500000.00, 0.00 or NULL, as applicable. | Pass | Figure VC17 |
| VC18 | Allowance rejected | Try `2.5`, `abc`, `-1`, `1e6`, and `1.000.000.001` | Error about a whole number or “too large” | The system rejected decimal, alphabetic, negative, scientific-notation and excessively large allowance values with an error about whole numbers or the maximum allowed value. | Pass | Figures VC18.1 & VC18.2 |
| VC19 | Date picker limits | Click the deadline calendar | Dates before today and more than one year ahead cannot be chosen | The deadline date picker prevented selecting dates before today and dates more than one year ahead. | Pass | Figure VC19 |
| VC20 | Deadline accepted | Try today, tomorrow, and exactly 365 days ahead | Accepted | The system accepted today’s date, tomorrow’s date and a date exactly 365 days ahead as deadlines. | Pass | Figure VC20 |
| VC21 | Deadline in the past | Console: `document.getElementById('deadline').value='2020-01-01'` | Error “cannot be in the past” | The system displayed “cannot be in the past” when a past deadline was submitted. | Pass | Figures VC21.1 & VC21.2 |
| VC22 | Deadline too far | Set the deadline through the console to a date two years ahead | Error “within one year from today” | The system displayed “within one year from today” when a deadline two years ahead was submitted. | Pass | Figures VC22.1 & VC22.2 |
| VC23 | Description length | Try fewer than 20 characters and more than 5000 characters | Error stating the description must be between 20 and 5000 characters | The system displayed an error stating that the description must be between 20 and 5000 characters. | Pass | Figure VC23 |
| VC24 | Skills maximum | Console: `document.querySelectorAll('.skill-chips input').forEach((c,i)=>c.checked=i<11)` | Error “at most 10 skills”; exactly 10 is accepted | The system rejected more than 10 selected skills with an “at most 10 skills” error and accepted exactly 10 skills. | Pass | Figures VC24.1 & VC24.2 |
| VC25 | Tampered skill | Console: `var c=document.querySelector('.skill-chips input'); c.value='99999'; c.checked=true;` | Error “One or more selected skills are invalid.” | The system displayed “One or more selected skills are invalid.” after an invalid skill ID was submitted. | Pass | Figures VC25.1 & VC25.2 |
| VC26 | Array instead of text | Console: `document.getElementById('title').name='title[]'` | “Invalid form submission”; no PHP warning on the page | The system displayed “Invalid form submission” when the title field was changed to an array-style name, without showing a PHP warning. | Pass | Figures VC26.1 & VC26.2 |
| VC27 | Missing CSRF | Console: `document.querySelector('main form input[name=csrf]').remove()` | “Invalid form submission” | The system displayed “Invalid form submission” after the CSRF token was removed. | Pass | Figure VC27 |
| VC28 | Typed values kept | Cause any validation error | Everything entered remains in the form | After a validation error, the form retained the values that had been entered. | Pass | Figure VC28 |
| VC29 | HTML in text | Title `<script>alert(1)</script>`, description with `<img src=x onerror=alert(1)>` | No popup on list, dashboard or edit form; injected text is shown as plain text | The injected HTML and script content was displayed as plain text, and no JavaScript popup appeared on the list, dashboard or edit form. | Pass | Figure VC29 |

## D. Editing and status rules

| ID | Scenario | Steps / Input | Expected Result | Actual Result | Status | Evidence |
|---|---|---|---|---|---|---|
| VC30 | Edit form prefilled | Click Edit on a pending vacancy | All fields are filled; allowance is shown as a whole number; skills are ticked | The edit form was prefilled with the vacancy’s existing values, the allowance was shown as a whole number, and the saved skills were selected. | Pass | Figure VC30 |
| VC31 | Edit pending | Change title, location, deadline and skills, then Save | Changes saved; status stays Pending; old skills removed and new ones added | The pending vacancy was updated successfully; its status remained Pending, the old skill links were removed and the newly selected skills were saved. | Pass | Figure VC31 |
| VC32 | Edit published | Publish with SQL, then open Edit | Yellow warning says saving sends it back for approval | A yellow warning was displayed indicating that saving changes to a published vacancy would send it back for approval. | Pass | Figure VC32 |
| VC33 | Published becomes pending | Save the edit | Status is Pending again; message “pending approval again” | After the published vacancy was edited and saved, its status changed to Pending and the system displayed “pending approval again”. | Pass | Figure VC33 |
| VC34 | Edit rejected | Reject with SQL, open Edit, then Save | Warning says it was rejected; after saving, status is Pending (resubmitted) | The system displayed a warning that the vacancy had been rejected; after saving the edit, its status changed to Pending. | Pass | Figure VC34 |
| VC35 | Closed cannot be edited | Close a vacancy, then open its edit address directly | Redirect to the list with “Closed vacancies cannot be edited.” | The system redirected the user to the vacancy list and displayed “Closed vacancies cannot be edited.” when a closed vacancy’s edit page was opened directly. | Pass | Figure VC35 |
| VC36 | Other employer’s vacancy | Employer 2 opens `/employer/vacancy_form.php?id=<Employer 1's vacancy id>` | 404 “Vacancy not found.” and no data shown | Employer 2 received a 404 “Vacancy not found.” response when attempting to edit Employer 1’s vacancy, and no vacancy data was displayed. | Pass | Figure VC36 |
| VC37 | Invalid IDs | Try `?id=abc`, `?id=-1`, `?id=0`, and `?id=99999999` | 404 for each | Each invalid vacancy ID returned a 404 response. | Pass | Figure VC37 |
| VC38 | Forged owner on edit | Edit your own vacancy and run the console command from VC13 | Ownership does not change | Changing the employer_id field through the browser console did not change ownership of the vacancy. | Pass | Figure VC38 |

## E. Closing

| ID | Scenario | Steps / Input | Expected Result | Actual Result | Status | Evidence |
|---|---|---|---|---|---|---|
| VC39 | Confirmation | Click Close, then Cancel in the dialog | Nothing changes | Cancelling the close confirmation dialog left the vacancy unchanged. | Pass | Figure VC39 |
| VC40 | Close | Click Close, then OK | Status Closed; message “The vacancy has been closed.”; Edit and Close buttons disappear | After the close action was confirmed, the vacancy status changed to Closed, the message “The vacancy has been closed.” appeared, and the Edit and Close buttons disappeared. | Pass | Figure VC40 |
| VC41 | Close published/rejected | Repeat VC40 on a published and a rejected vacancy | Both can be closed | Both published and rejected vacancies could be closed successfully. | Pass | Figure VC41 |
| VC42 | Closed is final | Look for a way to reopen a closed vacancy | There is no reopen option; a new vacancy must be posted | No option to reopen a closed vacancy was available; a new vacancy would need to be posted. | Pass | No evidence label supplied |
| VC43 | Other employer’s vacancy | As Employer 2, change the ID in the close form to Employer 1’s vacancy ID, then click Close and OK | 404; the other vacancy stays unchanged | The system returned a 404 response when Employer 2 attempted to close Employer 1’s vacancy, and the other employer’s vacancy remained unchanged. | Pass | Figure VC43 |
| VC44 | Direct address | Open `/employer/vacancy_close.php` directly in the address bar | Nothing is closed; redirected back with an error message | Opening the vacancy-close endpoint directly did not close any vacancy; the user was redirected back with an error message. | Pass | Figure VC44 |
| VC45 | Missing CSRF | Remove the CSRF input from the close form, then click Close and OK | Nothing is closed | Removing the CSRF token prevented the vacancy from being closed. | Pass | Figure VC45 |

## F. Technical tests (optional)

| ID | Scenario | Expected Result | Actual Result | Status | Evidence |
|---|---|---|---|---|---|
| VC46 | Transaction rollback: a failure while saving the skills | The vacancy is not created / the edit is undone (automated PHP script only) | Not executed manually; this is an automated PHP technical test. | N/A | N/A |
| VC47 | Deleting an employer account | Its vacancies and their skill links are deleted too (foreign key cascade) | Not verified manually in this test run. | N/A | N/A |
| VC48 | Time zone | “Today” for the deadline follows Jakarta time regardless of the `php.ini` time zone (automated only) | Not executed manually; this is an automated PHP technical test. | N/A | N/A |

## G. Regression: earlier tests affected by Milestone 2-C

| Earlier test | What changed |
|---|---|
| T09, T15 (Milestone 1) | The employer dashboard now shows the status cards and “Post a vacancy” instead of the placeholder text; login and redirect behave the same. |
| T12 | No change: a job seeker is still blocked from the employer area. |
| Everything else (T01-T22, P01-P28, Languages, CV, Education) | No change. |

Re-run T09, T12, T15 and a quick login of all three roles after applying this update.

Automated run during development (PHP 8.3, MariaDB 10.11, time zone Europe/Berlin like XAMPP): vacancies 98/98 (server side) and 17/17 (real browser); regression on the Milestone 2-B files: Milestone 1 22/22, Milestone 2-A 29/29, Languages 27/27, CV 28/28, Education 55/55. No database migration is needed for this milestone.

**Summary:** 48 test cases listed; VC01–VC45 are marked Pass in the supplied document. VC46–VC48 are optional technical tests and are not recorded as manually executed.