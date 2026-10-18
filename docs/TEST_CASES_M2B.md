# Black-Box Test Cases - Milestone 2-B (Languages and CV Upload)

All test cases were executed manually in the browser (Microsoft Edge) on 18 October 2026 by Paedia Rizky Maulana, except L20 (technical test, automated PHP script only). Precondition for most tests: the user is logged in as a job seeker and is on the My profile page. Evidence screenshots are documented in `Test_Cases_Languages_CV_M2B.docx`.

## Test files to prepare (put them in one folder, e.g. Desktop\cv-tests)

| File | How to create it |
|---|---|
| `valid_cv.pdf`, `valid_cv_2.pdf` | Two real PDF files below 2 MB (for example, export your CV from Word as PDF) |
| `fake.pdf` | Notepad: type any sentence, Save as, file type "All files", name `fake.pdf` |
| `shell.pdf` | Notepad: type `<?php echo "x"; ?>`, save as `shell.pdf` |
| `webpage.pdf` | Notepad: type `<script>alert(1)</script>`, save as `webpage.pdf` |
| `cv.txt` | Copy `valid_cv.pdf`, rename the copy to `cv.txt` (confirm the extension warning) |
| `big.pdf` (3 MB) | PowerShell in the folder: `$f=[IO.File]::Create("$PWD\big.pdf"); $f.SetLength(3MB); $f.Close()` |
| `empty.pdf` | PowerShell in the folder: `New-Item empty.pdf -ItemType File` |
| `huge.pdf` (41 MB) | PowerShell: `$f=[IO.File]::Create("$PWD\huge.pdf"); $f.SetLength(41MB); $f.Close()` (only for C16) |

## A. Languages (cards: Language and Overall are required; Speaking, Reading, Writing are optional)

| ID | Scenario | Steps / Input | Expected Result | Actual Result | Status | Evidence |
|----|----------|---------------|-----------------|---------------|--------|----------|
| L01 | First view | Open My profile with an account that has no languages | One empty card titled "Language 1" | The profile page of an account without languages displayed one empty card titled "Language 1" | Pass | Figure L01 |
| L02 | Language list | Open the Language dropdown | 22 international languages, in alphabetical order, no regional languages | The Language dropdown listed 22 international languages in alphabetical order, with no regional languages | Pass | Figure L02 |
| L03 | Add another | Click "+ Add another language" | A second card "Language 2" appears | Clicking "+ Add another language" added a second card titled "Language 2" | Pass | Figure L03 |
| L04 | No duplicates in the UI | Choose English in card 1, open the dropdown of card 2 | English is greyed out (disabled) in card 2 | After English was chosen in card 1, English was greyed out (disabled) in the dropdown of card 2 | Pass | Figure L04 |
| L05 | Fluent shortcut | Tick "I am fluent in this language" in a card | Overall, Speaking, Reading, Writing all become "Fluent"; each can still be changed | Ticking "I am fluent in this language" set Overall, Speaking, Reading and Writing to "Fluent", and each field could still be changed afterwards | Pass | Figure L05 |
| L06 | Minimal save | Card 1: English + Overall = Fluent only, Save | Success message; in `job_seeker_languages` the columns speaking, reading, writing are NULL | A success message was displayed, and in `job_seeker_languages` the columns speaking, reading and writing were stored as NULL (job_seeker_id 14) | Pass | Figure L06 |
| L07 | Detailed save | Add Indonesian: Overall Native, Speaking Native, Reading Fluent, Writing Conversational, Save | Both rows saved with exactly these levels | Both languages were saved, and the Indonesian row contained exactly the levels that were entered: Overall Native, Speaking Native, Reading Fluent, Writing Conversational (job_seeker_id 14) | Pass | Figure L07 |
| L08 | Saved cards shown again | Reload the page | The saved cards are displayed with their values; the "fluent" tick is not remembered (it is only a shortcut and has no database column) | After reloading, the saved cards were displayed with their values, and the "fluent" tick was not remembered | Pass | Figure L08 |
| L09 | Delete a card | Click Delete on one card, Save | The language is removed from `job_seeker_languages` | After Delete was clicked on one card and the form was saved, the language was removed from `job_seeker_languages` | Pass | Figure L09 |
| L10 | Maximum of 8 | Click Add until no more cards can be added | At 8 cards the Add button is disabled; deleting a card enables it again | Cards could be added until the 8th card, where the Add button was disabled; deleting a card enabled it again | Pass | Figure L10 |
| L11 | Overall missing | Choose a language but leave Overall on "Select one", Save | Error "Please choose the overall level for every language you add."; nothing saved | The system displayed the error "Please choose the overall level for every language you add." and nothing was saved | Pass | Figure L11 |
| L12 | Language missing | Leave Language empty but choose Overall = Fluent, Save | Error "Please choose a language for every language card you fill in." | The system displayed the error "Please choose a language for every language card you fill in." and the save was rejected | Pass | Figure L12 |
| L13 | Untouched card ignored | Keep a completely empty second card, Save | No error; only the filled card is saved | No error was displayed; the empty second card was ignored and only the filled card was saved | Pass | Figure L13 |
| L14 | Tampered optional level | Fill card 1 (English, Overall Fluent). Console: `var s=document.querySelector('#language-list select[name$="[speaking]"]'); s.insertAdjacentHTML('beforeend','<option value="god-mode">x</option>'); s.value='god-mode';` then Save | Error "One or more language entries are invalid."; nothing changed | After a fake option `god-mode` was added and selected through the console, the system displayed "One or more language entries are invalid." and nothing was changed | Pass | Figure L14.1 & L14.2 |
| L15 | Tampered language ID | Choose Overall = Fluent in card 1. Console: `var s=document.querySelector('#language-list [data-language-select]'); s.insertAdjacentHTML('beforeend','<option value="99999">x</option>'); s.value='99999';` then Save | Error "One or more language entries are invalid." | After a fake language option with ID `99999` was added and selected through the console, the system displayed "One or more language entries are invalid." and the save was rejected | Pass | Figure L15.1 & L15.2 |
| L16 | Duplicate language bypassing the UI | Add a second card. Console: `document.querySelectorAll('#language-list [data-language-select]').forEach(s=>{s.querySelectorAll('option').forEach(o=>o.disabled=false); s.value=s.options[1].value;}); document.querySelectorAll('#language-list select[name$="[overall]"]').forEach(s=>s.value='fluent');` then Save | Error "Each language can be added only once."; nothing changed | After the disabled options were re-enabled through the console and the same language was selected in both cards, the system displayed "Each language can be added only once." and nothing was changed | Pass | Figure L16.1 & L16.2 |
| L17 | Typed cards kept after an error | Repeat L11 | The error is shown and the cards you typed are still in the form | After L11 was repeated, the error was displayed and the cards that were typed were still in the form | Pass | Figure L17 |
| L18 | English moved out of Skills | Count the skill chips | 36 chips; "English" is not among them | 36 skill chips were displayed, and "English" was not among them | Pass | Figure L18 |
| L19 | Users are independent | Log in as another job seeker | Their languages are not affected by yours | After logging in as another job seeker (Paedia), that user's languages were displayed and were not affected by the languages of the first user | Pass | Figure L19 |
| L20 | Transaction rollback (technical, optional) | Automated PHP script only | If saving fails in the last language row, name, skills and languages all stay unchanged | Not executed manually: this is a technical test that runs only as an automated PHP script (covered by the automated run during development) | - | - |

## B. CV access control

| ID | Scenario | Steps / Input | Expected Result | Actual Result | Status | Evidence |
|----|----------|---------------|-----------------|---------------|--------|----------|
| C01 | Guest access | Logged out, open `/seeker/cv_view.php` | Redirected to login | The guest was redirected to the login page | Pass | Figure C01 |
| C02 | Employer access | Logged in as employer, open `/seeker/cv_view.php` | 403 Forbidden | The system returned a 403 Forbidden page | Pass | Figure C02 |

## C. CV upload, view, replace, delete

| ID | Scenario | Steps / Input | Expected Result | Actual Result | Status | Evidence |
|----|----------|---------------|-----------------|---------------|--------|----------|
| C03 | Valid upload | Upload `valid_cv.pdf` | "Your CV has been uploaded."; `cv_path` in `job_seekers` is a 32-character random name ending in .pdf | The message "Your CV has been uploaded." was displayed, and `cv_path` in `job_seekers` contained a 32-character random name ending in .pdf | Pass | Figure C03 |
| C04 | Stored safely | Open `C:\xampp\jobportal_storage\cv\` | A file with that random name exists; the original name is not used | The storage folder contained a file with the random name stored in `cv_path`, and the original file name was not used | Pass | Figure C04 |
| C05 | Profile reflects upload | Look at the CV card | "Uploaded" badge, View CV and Delete CV buttons, upload button reads "Replace CV" | The CV card displayed the "Uploaded" badge, the View CV and Delete CV buttons, and the upload button read "Replace CV" | Pass | Figure C05 |
| C06 | View CV | Click View CV | The PDF opens in a new tab | Clicking View CV opened the PDF in a new tab | Pass | Figure C06 |
| C07 | Replace CV | Upload `valid_cv_2.pdf` | New random name in DB; the old file is gone from the storage folder; View CV shows the new file | After `valid_cv_2.pdf` was uploaded, `cv_path` contained a new random name (job_seeker_id 14), the old file was removed from the storage folder, and View CV showed the new file | Pass | Figure C07 |
| C08 | Delete CV | Click Delete CV, confirm | "Your CV has been deleted."; `cv_path` is NULL; file removed from storage; View CV URL returns "You have not uploaded a CV yet." | After Delete CV was clicked and confirmed, the message "Your CV has been deleted." was displayed, `cv_path` became NULL (job_seeker_id 14), the file was removed from storage, and the View CV URL returned "You have not uploaded a CV yet." | Pass | Figure C08.1 & C08.2 |

## D. Hostile or invalid uploads (every one must be rejected and the existing CV must stay)

| ID | Scenario | Steps / Input | Expected Result | Actual Result | Status | Evidence |
|----|----------|---------------|-----------------|---------------|--------|----------|
| C09 | Text disguised as PDF | Upload `fake.pdf` | Error "not a real PDF" | The system displayed an error that the file is not a real PDF, and the upload was rejected | Pass | Figure C09 |
| C10 | Real PDF with wrong extension | Upload `cv.txt` (pick "All files" in the dialog) | Error "Only PDF files are allowed" | The system displayed the error "Only PDF files are allowed", and the upload was rejected | Pass | Figure C10 |
| C11 | PHP code disguised as PDF | Upload `shell.pdf` | Error "not a real PDF" | The system displayed an error that the file is not a real PDF, and the PHP code was not stored | Pass | Figure C11 |
| C11b | Script disguised as PDF | Upload `webpage.pdf` | Error "not a real PDF" | The system displayed an error that the file is not a real PDF, and the upload was rejected | Pass | Figure C11b |
| C12 | File too large | Upload `big.pdf` | Error "larger than 2 MB" | The system displayed an error that the file is larger than 2 MB, and the upload was rejected | Pass | Figure C12 |
| C13 | Empty file | Upload `empty.pdf` | Error "The file is empty." | The system displayed the error "The file is empty.", and the upload was rejected | Pass | Figure C13 |
| C14 | No file chosen | Console: `document.getElementById('cv').removeAttribute('required')`, click Upload without choosing a file | Error "Please choose a PDF file" | After the `required` attribute was removed through the console and Upload was clicked without a file, the system displayed "Please choose a PDF file" | Pass | Figure C14.1 & C14.2 |
| C15 | Missing CSRF token | Console: `document.querySelector('form[action*="cv_upload"] input[name=csrf]').remove()`, choose `valid_cv.pdf`, Upload | "Invalid form submission" | After the hidden `csrf` input was removed through the console, the system displayed "Invalid form submission" and the request was rejected | Pass | Figure C15.1 & C15.2 |
| C16 | Larger than the server limit | Upload `huge.pdf` (41 MB) | Message "The file is too large" (not a CSRF error) | The system displayed the message "The file is too large", not a CSRF error | Pass | Figure C16 |

## E. Storage and cross-user protection

| ID | Scenario | Steps / Input | Expected Result | Actual Result | Status | Evidence |
|----|----------|---------------|-----------------|---------------|--------|----------|
| C17 | No public URL | Try `http://localhost/jobportal_storage/cv/<stored name>` and `http://localhost/jobportal/seeker/<stored name>` | 404 Not Found: the file is outside the web root | Both URLs returned 404 Not Found, confirming that the file is outside the web root | Pass | Figure C17 |
| C18 | Another user's CV | Account B (no CV) opens `/seeker/cv_view.php?id=<A's user id>` | "You have not uploaded a CV yet." Account A's file is never shown | Account B (without a CV) saw "You have not uploaded a CV yet.", and the file of Account A was never shown | Pass | Figure C18 |
| C19 | Delete needs POST and token | Open `/seeker/cv_delete.php` directly in the address bar | Nothing is deleted; you are redirected to the profile page | Opening `/seeker/cv_delete.php` directly in the address bar deleted nothing, and the user was redirected to the profile page | Pass | Figure C19 |
| C20 | Profile completeness | Dashboard of a new account, then after filling all 7 details (phone, city, bio, interests, one skill, one language, CV) | "0 of 7", then "7 of 7" with 100% | The dashboard of a new account displayed "0 of 7", and after all 7 details were filled it displayed "7 of 7" with 100% | Pass | Figure C20 |

## F. Regression: expectations that changed in Milestone 2-B

| Earlier test | Old expectation | New expectation (because English moved to Languages and Languages/CV count toward completeness) |
|---|---|---|
| P04 | 37 skill chips | 36 skill chips |
| P05 | "0 of 5 optional details" | "0 of 7 optional details" |
| P12 | "5 of 5", 100% | After filling only the five Milestone 2-A details: "5 of 7", 71% |

Re-run P01-P28 and the Milestone 1 login/register tests after applying Milestone 2-B and record any difference.

Automated run during development (PHP 8.3, MariaDB 10.11, upload limit 40 MB like XAMPP): languages (server side) 27/27, language cards in a real browser 17/17, CV 28/28, Milestone 2-A regression 29/29, Milestone 1 regression 22/22. Migration 004 was checked with existing data: levels were preserved after the column rename, and the migration is safe to run twice.

**Summary:** 41 test cases listed, 40 executed manually, 40 passed, 0 failed. L20 is a technical test that runs only as an automated PHP script and was not executed manually.