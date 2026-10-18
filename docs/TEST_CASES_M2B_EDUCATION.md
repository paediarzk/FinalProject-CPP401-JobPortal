# Black-Box Test Cases - Milestone 2-B (Education Section)

All test cases were executed manually in the browser (Microsoft Edge) on 18 October 2026. Precondition: the user is logged in as a job seeker, on the My profile page, with the Education section visible. Console tests were run from the browser Console (F12). Evidence screenshots are documented in `Test_Cases_Education_M2B.docx`.

## A. Registration and first entry

| ID | Scenario | Steps / Input | Expected Result | Actual Result | Status | Evidence |
|----|----------|---------------|-----------------|---------------|--------|----------|
| ED01 | Registration still asks the same two fields | Open the register page | "Education level" and "Institution" are shown, as before | The register page displayed the same two fields, "Education level" and "Institution", as before the update | Pass | Figure ED01 |
| ED02 | First education entry created | Register a new account, then open table `educations` in phpMyAdmin | One row for the new user with the chosen degree and institution | After registering a new account, table `educations` contained one row for the new user with the chosen degree and institution | Pass | Figure ED02 |
| ED03 | Old columns removed | Open table `job_seekers` > Structure | No `education_level` or `institution` column | The structure of table `job_seekers` no longer contained the `education_level` and `institution` columns | Pass | Figure ED03 |
| ED04 | Old data migrated | After migration 005, Browse table `educations` | Every existing job seeker has an entry equal to the earlier level and institution | After migration 005, every existing job seeker had a row in `educations` with the same level and institution as the old data (old data in Figure ED04.1, new data in Figure ED04.2) | Pass | Figure ED04.1 & ED04.2 |

## B. Cards and suggestions

| ID | Scenario | Steps / Input | Expected Result | Actual Result | Status | Evidence |
|----|----------|---------------|-----------------|---------------|--------|----------|
| ED05 | First view | Open My profile | One card "Education 1" with the registration data; no Delete button (minimum 1) | The profile page displayed one card "Education 1" filled with the registration data, and no Delete button was shown | Pass | Figure ED05 |
| ED06 | Labels | Look at the card | School or University *, Degree *, Field of Study, Overall Result (GPA), From, To (Actual or Expected) | The card displayed the labels School or University *, Degree *, Field of Study, Overall Result (GPA), From and To (Actual or Expected) | Pass | Same as Figure ED05 |
| ED07 | School suggestions | Type `Univ` in School or University | A list of suggested universities appears | Typing `Univ` displayed a list of suggested universities | Pass | Figure ED07 |
| ED08 | Field suggestions | Type `Info` in Field of Study | Suggestions such as Informatics, Information Systems appear | Typing `Info` displayed suggestions such as Informatics and Information Systems | Pass | Figure ED08 |
| ED09 | Any name allowed | Type a school that is not in the list, Save | It is saved as typed | A school name that is not in the suggestion list ("CCIT FT-UI") was accepted and saved exactly as typed | Pass | Figure ED09.1 & ED09.2 |
| ED10 | Add another | Click "+ Add another education" | "Education 2" appears and Delete shows on both cards | Clicking "+ Add another education" added the card "Education 2", and the Delete button appeared on both cards | Pass | Figure ED10 |
| ED11 | Maximum of 5 | Keep clicking Add | At 5 cards the Add button is disabled; deleting a card enables it again | After adding cards up to the 5th card, the Add button was disabled; deleting a card enabled it again | Pass | Figure ED11 |
| ED12 | Delete | Delete cards until one is left | The last card has no Delete button | After deleting cards until one was left, the last remaining card had no Delete button | Pass | Figure ED12 |

## C. Saving and validation

| ID | Scenario | Steps / Input | Expected Result | Actual Result | Status | Evidence |
|----|----------|---------------|-----------------|---------------|--------|----------|
| ED13 | Full entries | Entry 1: Universitas Indonesia, Master's, Computer Science, 3.75, 2022, 2024. Entry 2: Asia e University, Bachelor's, Informatics, 3.5, 2018, 2022. Save | Success message; both rows in `educations` with these values | A success message was displayed, and table `educations` contained both rows with the values that were entered | Pass | Figure ED13 |
| ED14 | Comma GPA | GPA = `3,75` | Accepted and stored as 3.75 | The GPA `3,75` was accepted and stored as 3.75 in the database (job_seeker_id 14) | Pass | Figure ED14 |
| ED15 | Boundary GPA | GPA = `4`, then `0` | Both accepted (stored as 4.00 and 0.00) | Both boundary values were accepted and stored as 4.00 and 0.00 (job_seeker_id 14) | Pass | Figure ED15 |
| ED16 | Invalid GPA | GPA = `4.01`, `5`, `-1`, `abc`, `3.755` (one at a time) | Error "GPA must be a number from 0.00 to 4.00"; nothing saved | Each invalid value (`4.01`, `5`, `-1`, `abc`, `3.755`) displayed the error "GPA must be a number from 0.00 to 4.00", and nothing was saved | Pass | Figure ED16 |
| ED17 | Invalid years | From = `20x4`; `1949`; a year more than 8 years ahead | Error about the year format or range; nothing saved | Each invalid year (`20x4`, `1949`, and a year more than 8 years ahead) displayed an error about the year format or range, and nothing was saved | Pass | Figure ED17 |
| ED18 | Year order | From = 2022, To = 2020 | Error "The end year cannot be earlier than the start year." | The system displayed "The end year cannot be earlier than the start year." and the save was rejected | Pass | Figure ED18 |
| ED19 | Expected graduation | To = a future year within 8 years | Accepted | A future end year within 8 years was accepted and the entry was saved | Pass | Figure ED19 |
| ED20 | Optional fields empty | Only school and degree filled | Saved; field of study, GPA and years are NULL in the database | The entry was saved with only school and degree filled, and field of study, GPA and years were stored as NULL in the database | Pass | Figure ED20 |
| ED21 | School missing | Fill field of study and degree but leave School empty | Error "Please enter the school or university..." | The system displayed the error "Please enter the school or university..." and the save was rejected | Pass | Figure ED21 |
| ED22 | Degree missing | Leave Degree on "Select one" | Error "Please choose the degree..." | The system displayed the error "Please choose the degree..." and the save was rejected | Pass | Figure ED22 |
| ED23 | Empty profile education | Clear all fields of the only card, Save | Error "Please add at least one education entry."; earlier entries unchanged | The system displayed "Please add at least one education entry." and the previously saved entries remained unchanged | Pass | Figure ED23 |
| ED24 | Blank extra card | Add a second card and leave it empty, Save | No error; only the filled card is saved | No error was displayed; the empty second card was ignored and only the filled card was saved | Pass | Figure ED24 |
| ED25 | Order | Save entries ending 2018, 2024 and one without an end year | Cards are shown with the one without an end year first, then 2024, then 2018 | The cards were displayed in this order: the entry without an end year first, then 2024, then 2018 | Pass | Figure ED25 |
| ED26 | Typed values kept | Cause any error (for example GPA = 5) | The error is shown and everything you typed is still in the cards | After GPA = 5 caused an error, the error message was displayed and all typed values were still in the cards | Pass | Figure ED26 |
| ED27 | HTML in text | School = `<script>alert(1)</script>`, Save | No popup; the text is shown as plain text | No alert popup appeared; the profile was saved (job_seeker_id 14) and the input was displayed as plain text | Pass | Figure ED27 |

## D. Manipulation and protection

| ID | Scenario | Steps / Input | Expected Result | Actual Result | Status | Evidence |
|----|----------|---------------|-----------------|---------------|--------|----------|
| ED28 | Tampered degree | Console: `var s=document.querySelector('#education-list select[name$="[degree]"]'); s.insertAdjacentHTML('beforeend','<option value="phd">PhD</option>'); s.value='phd';` then Save | Error "One or more education entries are invalid." | After a fake option `phd` was added and selected through the console, the system displayed "One or more education entries are invalid." and the save was rejected | Pass | Figure ED28.1 & ED28.2 |
| ED29 | More than 5 entries | Console: `var l=document.getElementById('education-list'),t=document.getElementById('education-template'); for(var i=20;i<26;i++){l.insertAdjacentHTML('beforeend',t.innerHTML.replace(/__INDEX__/g,i));}` then `document.querySelectorAll('#education-list [name$="[institution]"]').forEach(i=>i.value='X Univ'); document.querySelectorAll('#education-list [name$="[degree]"]').forEach(s=>s.value='bachelor');` then Save | Error "You can add at most 5 education entries."; nothing changed | After more than 5 entries were injected through the console, the system displayed "You can add at most 5 education entries." and nothing was changed | Pass | Figure ED29.1 & ED29.2 |
| ED30 | Forged owner | Console: `document.querySelector('main form[method=post]:not([action])').insertAdjacentHTML('beforeend','<input type=hidden name="educations[0][job_seeker_id]" value="2">')` (use another user's id), then Save | Your entries are saved; the other user's entries in `educations` are unchanged | The forged `job_seeker_id` input was ignored: "X Univ" was saved only in the logged-in user's account, and the other user's entries in `educations` were unchanged | Pass | Figure ED30.1 & ED30.2 |
| ED31 | Users independent | Log in as another job seeker | Only that user's education entries are shown | After logging in as another job seeker (Paedia), only that user's own education entries were displayed | Pass | Figure ED31 |
| ED32 | Database protection (technical) | In phpMyAdmin, SQL tab: `INSERT INTO educations (job_seeker_id, institution, degree, gpa) VALUES (<a real job seeker id>, 'X', 'bachelor', 4.50);` | Error: CONSTRAINT `chk_edu_gpa` failed | The database rejected the INSERT with GPA 4.50 and returned an error that CONSTRAINT `chk_edu_gpa` failed | Pass | Figure ED32 |
| ED33 | Database protection, years (technical) | Same, with `start_year, end_year` = 2020, 2018 | Error: CONSTRAINT `chk_edu_years` failed | The database rejected the INSERT with start_year 2020 and end_year 2018 and returned an error that CONSTRAINT `chk_edu_years` failed | Pass | Figure ED33 |

## E. Regression: earlier tests that changed because education moved to its own table

| Earlier test | What changed |
|---|---|
| T01-T06, T09-T22 (Milestone 1) | No change: registration and login look and behave the same |
| P02 | Name and institution are still prefilled; the institution is now shown inside the Education card (first entry) |
| P06-P14, P15-P19, P21-P24 | Saving the profile now needs at least one valid education entry (existing accounts already have one) |
| P20 | Old test changed the "education level" field. New command: see ED28. Expected: "One or more education entries are invalid." |
| P25 | Check the other user's row in table `educations` (and the name in `job_seekers`), because `institution` is no longer a column of `job_seekers` |
| P26, P27, P28 | No change |
| L01-L20, C01-C20 | No change (Languages and CV) |

**Summary:** 33 test cases executed, 33 passed, 0 failed.