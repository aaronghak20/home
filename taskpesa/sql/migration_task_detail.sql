-- ============================================================
-- Migration: add task-detail support
-- Run this ONLY if you already imported schema.sql before this
-- update. In phpMyAdmin: select taskpesa_db -> SQL tab -> paste
-- this file's contents -> Go. Safe to run once.
-- ============================================================

USE taskpesa_db;

ALTER TABLE tasks
  ADD COLUMN completion_prompt VARCHAR(255) NOT NULL
    DEFAULT 'Briefly describe what you did to complete this task.'
    AFTER description;

ALTER TABLE earnings
  ADD COLUMN submission_note TEXT DEFAULT NULL AFTER amount;

-- Give the seeded demo tasks a more specific prompt (optional, cosmetic).
UPDATE tasks SET completion_prompt = 'In 1-2 sentences, what did you buy most often last month, and where?'
  WHERE title = 'Complete a 5-minute consumer survey';
UPDATE tasks SET completion_prompt = 'List your rating (1-5) for each of the 3 videos, e.g. "4, 3, 5".'
  WHERE title = 'Watch and rate 3 product videos';
UPDATE tasks SET completion_prompt = 'Describe one bug or one thing that worked well while testing.'
  WHERE title = 'Test a mobile app and report bugs';
UPDATE tasks SET completion_prompt = 'Paste your transcription of the clip here.'
  WHERE title = 'Transcribe a 2-minute audio clip';
UPDATE tasks SET completion_prompt = 'Paste the confirmation code or subject line from the confirmation email.'
  WHERE title = 'Sign up for a newsletter and confirm';
UPDATE tasks SET completion_prompt = 'Roughly how many GB of mobile data do you use per month?'
  WHERE title = 'Fill in a market-research questionnaire';
