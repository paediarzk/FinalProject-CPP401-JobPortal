-- =====================================================================
-- Migration 005 (Milestone 2-B, Education section)
--   1) creates the educations table (one job seeker has 1..5 entries)
--   2) COPIES the old education_level + institution of every job seeker
--      into the new table as the first entry
--   3) only THEN removes the two old columns from job_seekers
-- Safe to run more than once (idempotent). Target: MariaDB (XAMPP).
-- Run it with the jobportal database selected, AFTER migration 004.
-- IMPORTANT: back up the database first - dropping columns cannot be undone.
-- =====================================================================
USE jobportal;

CREATE TABLE IF NOT EXISTS educations (
  id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  job_seeker_id  INT UNSIGNED NOT NULL,
  institution    VARCHAR(200) NOT NULL,
  degree         ENUM('diploma','bachelor','master','other') NOT NULL,
  field_of_study VARCHAR(150) NULL,
  gpa            DECIMAL(3,2) NULL,                  -- scale 0.00 - 4.00
  start_year     SMALLINT UNSIGNED NULL,
  end_year       SMALLINT UNSIGNED NULL,             -- actual OR expected graduation year
  CONSTRAINT fk_edu_js FOREIGN KEY (job_seeker_id) REFERENCES job_seekers(user_id) ON DELETE CASCADE,
  -- The database itself refuses impossible values, even if the application had a bug:
  CONSTRAINT chk_edu_gpa   CHECK (gpa IS NULL OR (gpa >= 0 AND gpa <= 4)),
  CONSTRAINT chk_edu_years CHECK (
        (start_year IS NULL OR start_year BETWEEN 1950 AND 2100)
    AND (end_year   IS NULL OR end_year   BETWEEN 1950 AND 2100)
    AND (start_year IS NULL OR end_year IS NULL OR end_year >= start_year))
) ENGINE=InnoDB;

-- Copy the old data only if the old columns still exist (so a second run does nothing),
-- and only for job seekers that have no education entry yet (so nothing is duplicated).
SET @old_columns := (SELECT COUNT(*) FROM information_schema.columns
                     WHERE table_schema = DATABASE() AND table_name = 'job_seekers'
                       AND column_name IN ('institution', 'education_level'));
SET @copy_sql := IF(@old_columns = 2,
  'INSERT INTO educations (job_seeker_id, institution, degree)
   SELECT js.user_id, js.institution, js.education_level
   FROM job_seekers js
   WHERE NOT EXISTS (SELECT 1 FROM educations e WHERE e.job_seeker_id = js.user_id)',
  'SELECT 1');
PREPARE copy_stmt FROM @copy_sql;
EXECUTE copy_stmt;
DEALLOCATE PREPARE copy_stmt;

-- If the copy above failed, the script stops before reaching this line.
ALTER TABLE job_seekers
  DROP COLUMN IF EXISTS education_level,
  DROP COLUMN IF EXISTS institution;
