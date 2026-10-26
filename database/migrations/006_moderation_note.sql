-- =====================================================================
-- Migration 006 (Milestone 3-A, administrator moderation)
--   adds vacancies.moderation_note: the reason an administrator gives when
--   a vacancy is rejected or taken down, so the employer knows what to fix.
-- Safe to run more than once (idempotent). Target: MariaDB (XAMPP).
-- Run it with the jobportal database selected, AFTER migration 005.
-- =====================================================================
USE jobportal;

ALTER TABLE vacancies
  ADD COLUMN IF NOT EXISTS moderation_note VARCHAR(500) NULL AFTER status;
