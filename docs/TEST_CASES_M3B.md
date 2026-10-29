# Black-Box Test Cases - Milestone 3b (Homepage, Search, Filters, Vacancy Detail, Pagination)

**Status of this document:** the manual run has been executed in the browser (localhost). *Actual Result*, *Status* and *Evidence* are filled in for every executed case. The automated run described at the end is a separate result and does not replace the manual run.

**Summary of the manual run:** 33 test cases executed, 33 PASS, 0 FAIL.

## Preparation

**Accounts:** administrator (`admin@jobportal.local`), Employer 1, Employer 2, Job seeker 1.

**Vacancies:** use the existing ones and add the bulk data below (replace `<employer id>` with the id of Employer 1 in table `users`; the employer must have a row in `employers`). Approve some vacancies as the administrator so that they are Published.

```sql
-- 26 published vacancies: alternating position type, two locations, some without allowance
INSERT INTO vacancies (employer_id, title, position_type, description, location, allowance, deadline, status, created_at)
SELECT <employer id>, CONCAT('Bulk Role ', id), IF(id % 2 = 0, 'internship', 'entry_level'),
       'Bulk description for pagination testing purposes.', IF(id % 3 = 0, 'Jakarta Selatan', 'Bandung'),
       IF(id % 4 = 0, NULL, 1500000), CURDATE() + INTERVAL 20 DAY, 'published', NOW() - INTERVAL id MINUTE
FROM skills LIMIT 26;
```

**Clean-up:** `DELETE FROM vacancies WHERE title LIKE 'Bulk Role %';`

**Public rule under test:** a vacancy is public only when its status is **Published** and its deadline is **today or later** (Jakarta time).

**Note on totals:** besides the 26 bulk vacancies, 3 other vacancies were Published during part of the run (Data Analyst Intern, Frontend Developer Intern, Junior Software Developer). Counts therefore read 26 in some screenshots and 29 in others.

## Test cases

| ID | Scenario | Steps / Input | Expected Result | Actual Result | Status | Evidence |
|---|---|---|---|---|---|---|
| HP01 | Home page as guest | Open `/jobportal/index.php` | Hero text, register buttons, search form (Keyword, Position type, Location), "Latest vacancies" with up to 6 cards, newest first | Navbar shows Browse vacancies, Login and Register. Hero text "Find your internship or first job here" with the buttons "I'm a student / graduate" and "I'm an employer". The search form has Keyword, Position type and Location fields with a Search button. "Latest vacancies" lists cards newest first, starting with Bulk Role 1 and Bulk Role 2. | PASS | FIGURE HP01 |
| HP02 | Home page when logged in | Log in as each role, open the home page | No register buttons; navbar shows Dashboard and Logout | For administrator, employer and job seeker, the Login and Register buttons are gone. The navbar shows Browse vacancies, the user name, Dashboard and Logout. Each role reaches its own dashboard. | PASS | FIGURE HP02.1, FIGURE HP02.2, FIGURE HP02.3 |
| HP03 | Home page without public vacancies | Make no vacancy Published | Message "There are no open vacancies right now." | "Latest vacancies" shows the message "There are no open vacancies right now. Please check again later." The hero and search form are still displayed. | PASS | FIGURE HP03 |
| HP04 | Search from home page | Type a keyword, click Search | Opens the vacancy list with that keyword applied | Submitting the search form opens `vacancies.php` with the chosen filters in the URL (`type=internship`, `location=Jakarta`) and pre-filled in the form. The list shows "Showing 4 of 4 vacancies · page 1 of 1". | PASS | FIGURE HP04 |
| LS01 | List shows only public vacancies | Open `vacancies.php` with vacancies in every status | Only Published, unexpired vacancies; Pending, Rejected, Closed and expired ones never appear | `vacancies.php` lists only public vacancies ("Showing 12 of 26 vacancies · page 1 of 3"). Pending, Rejected, Closed and expired vacancies do not appear. | PASS | FIGURE LS01 |
| LS02 | Card content | Look at one card | Title (link), company, position type, location, allowance or "Allowance not stated", deadline, skills, "View details" | The card shows the title as a link, company name, position type badge, location, allowance ("IDR 1.500.000 / month"), deadline ("Apply before 2026-11-18") and a "View details" button. Vacancies without allowance show "Allowance not stated" and vacancies with required skills show skill tags (see KW04, CB01). | PASS | FIGURE LS02 |
| KW01 | Keyword in title | `bulk` | Only vacancies whose title contains "bulk" | Only vacancies titled "Bulk Role N" are listed ("Showing 12 of 26 vacancies · page 1 of 3"). The keyword stays in the Keyword field. | PASS | FIGURE KW01 |
| KW02 | Keyword is not case-sensitive | `BULK role` | Same result as `bulk role` | Upper-case input gives the same result set as lower-case `bulk`: "Showing 12 of 26 vacancies" with the Bulk Role vacancies. Matching is not case-sensitive. | PASS | FIGURE KW02 |
| KW03 | Keyword in company name | Company name of Employer 1 | Vacancies of that company | Keyword `japfa` (part of the company name PT JAPFA FOOD INDONESIA (Testing 02)) lists that company's vacancies (Bulk Role 1, 2, 3, ...; "Showing 12 of 26 vacancies · page 1 of 3"). | PASS | FIGURE KW03 |
| KW04 | Keyword in required skill | A skill name, e.g. `PHP` | Vacancies that require that skill | Keyword `GIT` (skill Git, typed in capitals) lists the 2 vacancies that require Git: Frontend Developer Intern and Junior Software Developer, both showing the Git skill tag ("Showing 2 of 2 vacancies · page 1 of 1"). | PASS | FIGURE KW04 |
| KW05 | No result | `zzzznomatch` | "No vacancies match your search." with a hint | The list shows "No vacancies match your search." with the hint "Try a different keyword, another position type or a broader location." No vacancy cards are displayed. | PASS | FIGURE KW05 |
| KW06 | Empty / spaces keyword | Submit with spaces only | Treated as no keyword | A spaces-only keyword (`q=+`) is ignored. The Keyword field is shown empty and all public vacancies are listed ("Showing 12 of 29 vacancies · page 1 of 3"). | PASS | FIGURE KW06 |
| KW07 | Special characters are literal | `%` and `_` | Only titles that really contain `%` / `_`; not "everything" | Keyword `%` (`q=%25`) is treated literally. No title contains `%`, so "No vacancies match your search." is shown instead of all vacancies. | PASS | FIGURE KW07 |
| KW08 | SQL injection text | `' OR 1=1 --` | No error, no result | The SQL-like text (`1=1 --`) causes no error and no data leak. The page shows "No vacancies match your search." | PASS | FIGURE KW08 |
| KW09 | Script text | `<script>alert(1)</script>` | No alert; text shown escaped in the box | No alert appears. The text `<script>alert(1)</script>` is shown as plain text in the Keyword box and the page shows "No vacancies match your search." | PASS | FIGURE KW09 |
| TY01 | Filter internship | Position type = Internship | Only internships | Position type "Internship" (`type=internship`) lists only vacancies with the Internship badge ("Showing 12 of 15 vacancies · page 1 of 2"). | PASS | FIGURE TY01 |
| TY02 | Filter entry-level | Position type = Entry-level | Only entry-level | Position type "Entry-level" (`type=entry_level`) lists only vacancies with the Entry-level badge ("Showing 12 of 14 vacancies · page 1 of 2"). | PASS | FIGURE TY02 |
| TY03 | Invalid type in URL | `vacancies.php?type=hacker` | Ignored: all vacancies shown | The invalid type is ignored. Position type shows "All position types" and all public vacancies are listed ("Showing 12 of 29 vacancies · page 1 of 3"). | PASS | FIGURE TY03 |
| LO01 | Location partial match | `jakarta` | Locations containing "jakarta" (also "Jakarta Selatan"), any letter case | Lower-case `jakarta` returns vacancies located in "Jakarta Selatan" and "Jakarta" ("Showing 10 of 10 vacancies · page 1 of 1"). | PASS | FIGURE LO01 |
| LO02 | Location no match | `Atlantis` | No-result message | The list shows "No vacancies match your search." with the hint. No vacancy cards are displayed. | PASS | FIGURE LO02 |
| CB01 | Keyword + type + location together | `bulk` + Internship + `jakarta` | Only vacancies meeting all three | Only vacancies matching all three filters are listed ("Showing 4 of 4 vacancies · page 1 of 1"): Internship vacancies in Jakarta Selatan such as Bulk Role 6, Bulk Role 12 and Bulk Role 18. | PASS | FIGURE CB01 |
| CB02 | Filters do not overwrite each other | Set keyword, then change only the type | Keyword and location stay in the form and in the result | After changing the filters, keyword `data`, type Internship and location `jakarta` all stay in the form. The result matches all three: Data Analyst Intern ("Showing 1 of 1 vacancies · page 1 of 1"). | PASS | FIGURE CB02 |
| CB03 | Clear | Click "Clear" | All filters removed | After clicking Clear, the page returns to `vacancies.php` with Keyword, Position type and Location reset. All public vacancies are listed ("Showing 12 of 29 vacancies · page 1 of 3") and the Clear button is gone. | PASS | FIGURE CB03 |
| PG01 | Page size | 26 public vacancies | 12 per page, 3 pages, text "Showing 12 of 26 vacancies - page 1 of 3" | Page 1 shows 12 vacancies (Bulk Role 1 to Bulk Role 12) with the text "Showing 12 of N vacancies · page 1 of 3". The pagination shows Previous, 1, 2, 3 and Next, with page 1 active. N is 29 here because 3 other vacancies were also Published. | PASS | FIGURE PG01 |
| PG02 | Next / Previous / page numbers | Use the links | Correct pages; last page has 2 cards; no repeated or missing vacancy | The pagination links open the correct pages. On the last page (page 3) Previous is active and Next is disabled. With 29 public vacancies the last page holds 5 vacancies (12 + 12 + 5): Bulk Role 25, Bulk Role 26, Data Analyst Intern, Frontend Developer Intern and Junior Software Developer, with none repeated. | PASS | FIGURE PG02 |
| PG03 | Filters kept in page links | Search `bulk`, go to page 2 and 3 | Keyword (and type, location) still applied and shown in the form | On `vacancies.php?q=BULK&page=2` the keyword is still applied and shown in the form. The page shows "Showing 12 of 26 vacancies · page 2 of 3", starting with Bulk Role 13, 14 and 15. | PASS | FIGURE PG03 |
| PG04 | Invalid page numbers | `page=0`, `page=abc`, `page=-1`, `page=999` | Page 1 for invalid values, the last page for a too-large number; no error | `page=999` loads the last page without any error ("Showing 5 of 29 vacancies · page 3 of 3"), starting with Bulk Role 25. | PASS | FIGURE PG04 |
| PG05 | Only one page | A search with 12 results or fewer | No pagination links | A search with a single result (`q=data`, Internship, `jakarta`: "Showing 1 of 1 vacancies · page 1 of 1") shows no pagination links below the cards. | PASS | FIGURE PG05 |
| DT01 | Detail page | Click "View details" | Title, company, industry, type, location, allowance, deadline, posted date, description, skills, about the company; no Apply button | The detail page shows the title, company and industry (PT JAPFA FOOD INDONESIA (Testing 02) · food), position type, location (Bandung, Indonesia), allowance (6.500.000), deadline (2026-12-20), posted date (2026-10-19) and the job description. A "Back to vacancies" link is present and there is no Apply button. The Required skills and company sections follow below the description (see DT04). | PASS | FIGURE DT01 |
| DT02 | Description is escaped | A description containing `<b>x</b>` | Shown as text, not as bold | The description line `<b>x</b>` is displayed literally as text, not as bold. | PASS | FIGURE DT02 |
| DT03 | Pending / Rejected / Closed vacancy id | Open `vacancy.php?id=<id>` for each | "Vacancy not found." (HTTP 404), also when logged in as administrator or the owner | `vacancy.php?id=14` shows only the text "Vacancy not found." and no vacancy data. | PASS | FIGURE DT03 |
| DT04 | Deadline today | Published vacancy with deadline = today | Still listed and openable | A Published vacancy with deadline 2026-10-29 (today) still opens: Bulk Role 1, deadline 2026-10-29, posted 2026-10-29. Required skills shows "None specified." and the "About PT JAPFA FOOD INDONESIA (Testing 02)" section is displayed. | PASS | FIGURE DT04 |
| DT05 | Invalid id | `id=99999`, `id=abc`, `id=-1`, `id=0`, `id=`, no id | "Vacancy not found." (404) | `vacancy.php?id=abc` shows only the text "Vacancy not found." with no error message. | PASS | FIGURE DT05 |

**Test cases not included in this manual run:** the expired-vacancy case (formerly DT04), MD01-MD03, AC01, RG01, RG02 and UI01. The remaining IDs were renumbered, so the old DT05 and DT06 are now DT04 and DT05.

## Automated run during development (not a manual test)

**Environment:** PHP 8.3.6 built-in server, MariaDB 10.11 (`schema.sql` + `seed.sql`), Chromium. A script sent real HTTP requests as guest, administrator, employer and job seeker, and compared the counts with independent SQL queries. Test data: 28 public vacancies plus one Pending, Rejected, Closed and expired-Published vacancy.

**Result: 110 checks, 110 passed, 0 failed.** They cover the home page, list, keyword (title, company, skill, case, `%`, `_`, SQL text, script text, long text), type, location, combined filters, pagination (page sizes, invalid pages, filters kept, ordering without duplicates), detail pages (valid, escaping, hidden statuses, invalid ids), the three roles, suspended-user handling, approve / take down / close effects, empty state, and a regression pass over the M3a and earlier pages that were reachable. Rendering was checked in Chromium at 1280 px and 390 px (no horizontal overflow). The Bootstrap stylesheet was served from a local copy during this check because the sandbox cannot reach the CDN.

**Not covered by the automated run:** the test cases above must still be executed in the real XAMPP environment; browsers other than Chromium were not tried.