-- =====================================================================
-- Job Portal for Internship & Entry-Level Opportunities
-- Database schema (3NF) - created in Milestone 1 (25%)
-- Engine: InnoDB (required for foreign keys and transactions)
-- =====================================================================
CREATE DATABASE IF NOT EXISTS jobportal
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE jobportal;

-- 1. USERS: one table for ALL accounts (authentication data only).
--    The role decides which profile table is used.
CREATE TABLE users (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  email         VARCHAR(255) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,           -- NEVER store plain passwords
  role          ENUM('job_seeker','employer','admin') NOT NULL,
  status        ENUM('active','suspended') NOT NULL DEFAULT 'active',
  created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT uq_users_email UNIQUE (email)       -- also creates an index for login lookup
) ENGINE=InnoDB;

-- 2. JOB_SEEKERS: profile of a student / fresh graduate (1-to-1 with users).
--    PK = FK (user_id): shared-primary-key pattern for a 1-to-1 relationship.
CREATE TABLE job_seekers (
  user_id         INT UNSIGNED PRIMARY KEY,
  full_name       VARCHAR(150) NOT NULL,
  phone           VARCHAR(20)  NULL,
  city            VARCHAR(100) NULL,
  bio             TEXT NULL,
  interests       TEXT NULL,
  cv_path         VARCHAR(255) NULL,
  updated_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_js_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 2b. EDUCATIONS: one job seeker has 1..5 education entries (Milestone 2-B).
--     The first entry is created at registration from the "education level" and "institution" fields.
CREATE TABLE educations (
  id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  job_seeker_id  INT UNSIGNED NOT NULL,
  institution    VARCHAR(200) NOT NULL,
  degree         ENUM('diploma','bachelor','master','other') NOT NULL,
  field_of_study VARCHAR(150) NULL,
  gpa            DECIMAL(3,2) NULL,                  -- scale 0.00 - 4.00
  start_year     SMALLINT UNSIGNED NULL,
  end_year       SMALLINT UNSIGNED NULL,             -- actual OR expected graduation year
  CONSTRAINT fk_edu_js FOREIGN KEY (job_seeker_id) REFERENCES job_seekers(user_id) ON DELETE CASCADE,
  CONSTRAINT chk_edu_gpa   CHECK (gpa IS NULL OR (gpa >= 0 AND gpa <= 4)),
  CONSTRAINT chk_edu_years CHECK (
        (start_year IS NULL OR start_year BETWEEN 1950 AND 2100)
    AND (end_year   IS NULL OR end_year   BETWEEN 1950 AND 2100)
    AND (start_year IS NULL OR end_year IS NULL OR end_year >= start_year))
) ENGINE=InnoDB;

-- 3. EMPLOYERS: profile of a company / organisation (1-to-1 with users).
CREATE TABLE employers (
  user_id         INT UNSIGNED PRIMARY KEY,
  company_name    VARCHAR(200) NOT NULL,
  industry_sector VARCHAR(100) NOT NULL,
  description     TEXT NULL,
  CONSTRAINT fk_emp_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 4. SKILLS: master list. Replaces the non-normalised "skills (text/tags)" column.
CREATE TABLE skills (
  id   INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(80) NOT NULL,
  CONSTRAINT uq_skills_name UNIQUE (name)
) ENGINE=InnoDB;

-- 5. JOB_SEEKER_SKILLS: resolves many-to-many (job seeker <-> skill).
CREATE TABLE job_seeker_skills (
  job_seeker_id INT UNSIGNED NOT NULL,
  skill_id      INT UNSIGNED NOT NULL,
  PRIMARY KEY (job_seeker_id, skill_id),         -- composite PK also blocks duplicates
  CONSTRAINT fk_jss_js    FOREIGN KEY (job_seeker_id) REFERENCES job_seekers(user_id) ON DELETE CASCADE,
  CONSTRAINT fk_jss_skill FOREIGN KEY (skill_id)      REFERENCES skills(id)           ON DELETE CASCADE
) ENGINE=InnoDB;

-- 5b. LANGUAGES: master list of languages (added in Milestone 2-A+).
CREATE TABLE languages (
  id   INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(60) NOT NULL,
  CONSTRAINT uq_languages_name UNIQUE (name)
) ENGINE=InnoDB;

-- 5c. JOB_SEEKER_LANGUAGES: many-to-many (job seeker <-> language) with proficiency levels.
--     overall is required; speaking / reading / writing are optional.
CREATE TABLE job_seeker_languages (
  job_seeker_id INT UNSIGNED NOT NULL,
  language_id   INT UNSIGNED NOT NULL,
  overall       ENUM('basic','conversational','fluent','native') NOT NULL,
  speaking      ENUM('basic','conversational','fluent','native') NULL,
  reading       ENUM('basic','conversational','fluent','native') NULL,
  writing       ENUM('basic','conversational','fluent','native') NULL,
  PRIMARY KEY (job_seeker_id, language_id),
  CONSTRAINT fk_jsl_js   FOREIGN KEY (job_seeker_id) REFERENCES job_seekers(user_id) ON DELETE CASCADE,
  CONSTRAINT fk_jsl_lang FOREIGN KEY (language_id)   REFERENCES languages(id)        ON DELETE CASCADE
) ENGINE=InnoDB;

-- 6. VACANCIES: posted by employers.
CREATE TABLE vacancies (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  employer_id   INT UNSIGNED NOT NULL,
  title         VARCHAR(200) NOT NULL,
  position_type ENUM('internship','entry_level') NOT NULL,
  description   TEXT NOT NULL,
  location      VARCHAR(150) NOT NULL,
  allowance     DECIMAL(12,2) NULL,              -- optional (Table 3.1)
  deadline      DATE NOT NULL,
  status        ENUM('pending','published','closed','rejected') NOT NULL DEFAULT 'pending',
  created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_vac_emp FOREIGN KEY (employer_id) REFERENCES employers(user_id) ON DELETE CASCADE,
  -- Indexes support the search/filter feature (Milestone 3):
  INDEX idx_vac_status_type (status, position_type),
  INDEX idx_vac_location (location)
) ENGINE=InnoDB;

-- 7. VACANCY_SKILLS: many-to-many (vacancy <-> required skill).
CREATE TABLE vacancy_skills (
  vacancy_id INT UNSIGNED NOT NULL,
  skill_id   INT UNSIGNED NOT NULL,
  PRIMARY KEY (vacancy_id, skill_id),
  CONSTRAINT fk_vs_vac   FOREIGN KEY (vacancy_id) REFERENCES vacancies(id) ON DELETE CASCADE,
  CONSTRAINT fk_vs_skill FOREIGN KEY (skill_id)   REFERENCES skills(id)    ON DELETE CASCADE
) ENGINE=InnoDB;

-- 8. APPLICATIONS: associative entity (job seeker <-> vacancy) with its own attributes.
CREATE TABLE applications (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  job_seeker_id INT UNSIGNED NOT NULL,
  vacancy_id    INT UNSIGNED NOT NULL,
  status        ENUM('submitted','under_review','accepted','rejected') NOT NULL DEFAULT 'submitted',
  applied_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_app_js  FOREIGN KEY (job_seeker_id) REFERENCES job_seekers(user_id) ON DELETE CASCADE,
  CONSTRAINT fk_app_vac FOREIGN KEY (vacancy_id)    REFERENCES vacancies(id)        ON DELETE CASCADE,
  -- Business rule enforced by the DATABASE: one application per person per vacancy.
  CONSTRAINT uq_app_once UNIQUE (job_seeker_id, vacancy_id)
) ENGINE=InnoDB;
