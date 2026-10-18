-- =====================================================================
-- Migration 004 (Milestone 2-B, revised Languages section)
--   1) renames job_seeker_languages.level -> overall  (existing data is kept)
--   2) adds optional speaking, reading, writing levels
--   3) adds more international languages
-- Safe to run more than once (idempotent). Target: MariaDB (XAMPP).
-- Run it with the jobportal database selected, AFTER migration 003.
-- =====================================================================
USE jobportal;

ALTER TABLE job_seeker_languages
  CHANGE COLUMN IF EXISTS level overall ENUM('basic','conversational','fluent','native') NOT NULL,
  ADD COLUMN IF NOT EXISTS speaking ENUM('basic','conversational','fluent','native') NULL AFTER overall,
  ADD COLUMN IF NOT EXISTS reading  ENUM('basic','conversational','fluent','native') NULL AFTER speaking,
  ADD COLUMN IF NOT EXISTS writing  ENUM('basic','conversational','fluent','native') NULL AFTER reading;

-- International languages only (INSERT IGNORE skips names that already exist).
INSERT IGNORE INTO languages (name) VALUES
  ('Portuguese'), ('Italian'), ('Russian'), ('Turkish'), ('Hindi'), ('Bengali'),
  ('Thai'), ('Vietnamese'), ('Malay'), ('Filipino'), ('Swedish'), ('Polish');
