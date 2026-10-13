# Black-Box Test Cases - Milestone 2-A (Job Seeker Profile and Skills)

All test cases were executed manually in the browser. Precondition for most tests: the user is logged in as a job seeker. Evidence screenshots are documented in `BLACK_BOX_TESTING_DOCUMENTATION - M2.docx`.

| ID | Scenario | Steps / Input | Expected Result | Actual Result | Status | Evidence |
|----|----------|---------------|-----------------|---------------|--------|----------|
| P01 | Guest opens profile page | Log out, open /seeker/profile.php | Redirected to login | The guest was redirected to the login page | Pass | Figure P01 |
| P02 | Registration data prefilled | Log in, open My profile | Name and institution from registration are shown | The name and institution entered during registration were displayed in the form | Pass | Figure P02 |
| P03 | Email is read-only | Look at the Email field | Shown but disabled (cannot be edited) | The email was displayed in a disabled field and could not be edited | Pass | Figure P03 |
| P04 | Skill list loaded from database | Count the skill chips | 37 chips shown | 37 skill chips were displayed | Pass | Figure P04 |
| P05 | Dashboard before filling | Open dashboard with a new account | "0 of 5 optional details completed", 0% | The dashboard displayed "0 of 5 optional details completed" with 0% | Pass | Figure P05 |
| P06 | Save valid profile | Fill phone, city, bio, interests, select 3 skills, Save | Redirect to profile, green success message | The user was redirected to the profile page and a green success message was displayed | Pass | Figure P06 |
| P07 | Data persisted | Check table `job_seekers` in phpMyAdmin | phone and city saved | The phone and city values were stored in the `job_seekers` table | Pass | Figure P07 |
| P08 | Skills persisted | Check table `job_seekers_skills` for user ID 13 | 3 rows for this user | 3 rows were stored in `job_seeker_skills` for user ID 13 | Pass | Figure P08 |
| P09 | Success message shown | After P06 | "Your profile has been saved." | The message "Your profile has been saved." was displayed | Pass | Figure P09 |
| P10 | Saved skills pre-checked | Reload profile page | The 3 chosen skills are highlighted | The 3 previously chosen skills were highlighted after reload | Pass | Figure P10 |
| P11 | Message is one-time | Reload again | Success message gone | The success message no longer appeared after reloading | Pass | Figure P11 |
| P12 | Dashboard after filling | Open dashboard | "5 of 5", 100% | The dashboard displayed "5 of 5" with 100% | Pass | Figure P12 |
| P13 | Deselect skills | Uncheck 2 skills, Save | Only the remaining skill stays in `job_seeker_skills` | Only the remaining skill was kept in `job_seeker_skills` | Pass | Figure P13 |
| P14 | Empty optional fields | Clear phone, city, bio, interests, Save | Columns stored as NULL, not empty text | The cleared columns were stored as NULL, not as empty text | Pass | Figure P14 |
| P15 | Invalid phone | Phone = `abc` | Error "Phone number is not valid" | The system displayed "Phone number is not valid" and the save was rejected | Pass | Figure P15 |
| P16 | Bio too long | Paste 1001 characters in bio (DevTools: remove maxlength) | Error "must not exceed 1000" | The system displayed an error that the bio must not exceed 1000 characters; the save was rejected | Pass | Figure P16.1 & P16.2 |
| P17 | Tampered skill ID | DevTools: change a checkbox value to `99999`, Save | Error "skills are invalid"; nothing saved | The system displayed a "skills are invalid" error and nothing was saved | Pass | Figure P17.1 & P17.2 |
| P18 | Too many skills | Select 21 skills, Save | Error "at most 20 skills" | The system displayed an error that at most 20 skills can be selected; the save was rejected | Pass | Figure P18.1 & P18.2 |
| P19 | Rejected save changes nothing | After P17/P18 check `job_seeker_skills` | Previous skills unchanged | The previously saved skills in `job_seeker_skills` were unchanged | Pass | Figure P19 |
| P20 | Tampered education level | DevTools: change option value to `phd`, Save | Error "valid education level" | The system displayed an error that a valid education level is required; the save was rejected | Pass | Figure P20.1 & P20.2 |
| P21 | Missing CSRF token | DevTools: delete the hidden `csrf` input, Save | "Invalid form submission" | The system displayed "Invalid form submission" and the request was rejected | Pass | Figure P21.1 & P21.2 |
| P22 | Name too short | Full name = `A` | Error; typed values are kept in the form | The system displayed a validation error and the typed values were kept in the form | Pass | Figure P22 |
| P23 | XSS in bio | Bio = `<script>alert(1)</script>`, Save, reload | No alert; text shown as plain text | No alert appeared; the input was displayed as plain text | Pass | Figure P23.1 & P23.2 |
| P24 | Name change updates navbar | Change full name, Save | New name appears in the navbar | The new full name appeared in the navbar after saving | Pass | Figure P24 |
| P25 | Forged user_id | DevTools: add hidden input `user_id` = another user's id, Save | Other user's data unchanged | The other user's data was unchanged; only the logged-in user's profile was updated | Pass | Figure P25.1 & P25.2 |
| P26 | Forged role / cv_path | Add hidden inputs `role=admin`, `cv_path=hack.php`, Save | Role and cv_path unchanged | The role and cv_path values remained unchanged | Pass | Figure P26.1 & P26.2 |
| P27 | Users see only own data | Log in as another job seeker | Only that user's data is shown | Only the logged-in job seeker's own data was displayed | Pass | Figure P27.1 & P27.2 |
| P28 | Employer access | Log in as employer, open /seeker/profile.php | 403 Forbidden | The system returned a 403 Forbidden page | Pass | Figure P28 |

**Summary:** 28 test cases executed, 28 passed, 0 failed.