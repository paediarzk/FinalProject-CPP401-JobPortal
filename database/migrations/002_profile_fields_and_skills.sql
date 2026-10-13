-- =====================================================================
-- Migration 002 (Milestone 2-A)
--   1) adds phone, city, bio to job_seekers
--   2) inserts the predefined skill list
-- Safe to run more than once (idempotent): it never fails on a repeat run.
-- Target: MariaDB (XAMPP). IMPORTANT: run it with the jobportal database selected.
-- =====================================================================
USE jobportal;

ALTER TABLE job_seekers
  ADD COLUMN IF NOT EXISTS phone VARCHAR(20)  NULL AFTER institution,
  ADD COLUMN IF NOT EXISTS city  VARCHAR(100) NULL AFTER phone,
  ADD COLUMN IF NOT EXISTS bio   TEXT         NULL AFTER city;

-- INSERT IGNORE skips names that already exist (skills.name is UNIQUE).
INSERT IGNORE INTO skills (name) VALUES
  ('PHP'), ('JavaScript'), ('Python'), ('Java'), ('SQL / Databases'),
  ('HTML & CSS'), ('Git'), ('Laravel'), ('React'), ('Node.js'),
  ('Mobile App Development'), ('Network Administration'), ('Cybersecurity Basics'),
  ('Software Testing'), ('Data Analysis'), ('Microsoft Excel'), ('Power BI / Tableau'),
  ('UI/UX Design'), ('Figma'), ('Graphic Design'), ('Video Editing'),
  ('Digital Marketing'), ('Content Writing'), ('Social Media Management'),
  ('Accounting Basics'), ('Customer Service'), ('Sales'), ('Project Management'),
  ('Microsoft Office'), ('Data Entry'), ('English'),
  ('Communication'), ('Teamwork'), ('Problem Solving'), ('Time Management'),
  ('Leadership'), ('Public Speaking');
