-- =====================================================================
-- Migration 003 (Milestone 2-A+ / 2-B)
--   1) creates languages and job_seeker_languages
--   2) inserts the predefined language list
--   3) removes "English" from skills (it now lives in Languages, with a level)
-- Safe to run more than once (idempotent).
-- Target: MariaDB (XAMPP). Run it with the jobportal database selected.
-- =====================================================================
USE jobportal;

CREATE TABLE IF NOT EXISTS languages (
  id   INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(60) NOT NULL,
  CONSTRAINT uq_languages_name UNIQUE (name)
) ENGINE=InnoDB;

-- Many-to-many (job seeker <-> language) with its own attribute: the proficiency level.
CREATE TABLE IF NOT EXISTS job_seeker_languages (
  job_seeker_id INT UNSIGNED NOT NULL,
  language_id   INT UNSIGNED NOT NULL,
  level         ENUM('basic','conversational','fluent','native') NOT NULL,
  PRIMARY KEY (job_seeker_id, language_id),
  CONSTRAINT fk_jsl_js   FOREIGN KEY (job_seeker_id) REFERENCES job_seekers(user_id) ON DELETE CASCADE,
  CONSTRAINT fk_jsl_lang FOREIGN KEY (language_id)   REFERENCES languages(id)        ON DELETE CASCADE
) ENGINE=InnoDB;

INSERT IGNORE INTO languages (name) VALUES
  ('Indonesian'), ('English'), ('Mandarin'), ('Japanese'), ('Korean'),
  ('Arabic'), ('German'), ('French'), ('Spanish'), ('Dutch');

-- ON DELETE CASCADE also removes any job_seeker_skills / vacancy_skills rows
-- that pointed to this skill, so no orphan rows remain.
DELETE FROM skills WHERE name = 'English';
