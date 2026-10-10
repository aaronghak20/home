-- ============================================================
-- TaskPesa — Database Schema
-- Matches the ERD and table designs in Chapter 3 (Section 3.3.5)
-- Import this file in phpMyAdmin, or run:
--   mysql -u root -p < schema.sql
-- ============================================================

CREATE DATABASE IF NOT EXISTS taskpesa_db
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE taskpesa_db;

-- ------------------------------------------------------------
-- Table 1: users
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
  id              INT AUTO_INCREMENT PRIMARY KEY,
  full_name       VARCHAR(100) NOT NULL,
  email           VARCHAR(100) NOT NULL UNIQUE,
  phone_number    VARCHAR(15)  NOT NULL UNIQUE,
  national_id     VARCHAR(20)  NOT NULL UNIQUE,
  password_hash   VARCHAR(200) NOT NULL,
  min_withdrawal_threshold DECIMAL(10,2) NOT NULL DEFAULT 100.00,
  registered_on   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Table 2: tasks
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS tasks (
  id                 INT AUTO_INCREMENT PRIMARY KEY,
  title              VARCHAR(150) NOT NULL,
  description        TEXT NOT NULL,
  -- What the user is actually asked to do/submit on the task detail page,
  -- e.g. "Paste the confirmation code you received." Shown as the form
  -- label on task_detail.php.
  completion_prompt  VARCHAR(255) NOT NULL DEFAULT 'Briefly describe what you did to complete this task.',
  reward_amount      DECIMAL(10,2) NOT NULL,
  status             ENUM('active','closed') NOT NULL DEFAULT 'active',
  created_on         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Table 3: earnings
-- Records a completed task for a user (a user may complete a
-- given task only once — enforced with a unique constraint).
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS earnings (
  id              INT AUTO_INCREMENT PRIMARY KEY,
  user_id         INT NOT NULL,
  task_id         INT NOT NULL,
  amount          DECIMAL(10,2) NOT NULL,
  -- What the user submitted on the task detail page as proof of completion.
  submission_note TEXT DEFAULT NULL,
  earned_on       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_earnings_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_earnings_task FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE,
  UNIQUE KEY uq_user_task (user_id, task_id)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Table 4: withdrawals
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS withdrawals (
  id                    INT AUTO_INCREMENT PRIMARY KEY,
  user_id               INT NOT NULL,
  method                VARCHAR(20) NOT NULL DEFAULT 'M-Pesa',
  amount                DECIMAL(10,2) NOT NULL,
  status                ENUM('pending','completed','failed') NOT NULL DEFAULT 'pending',
  mpesa_conversation_id VARCHAR(100) DEFAULT NULL,
  mpesa_receipt         VARCHAR(100) DEFAULT NULL,
  requested_on          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  completed_on          DATETIME DEFAULT NULL,
  CONSTRAINT fk_withdrawals_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Seed data: a handful of sample tasks so the platform is not
-- empty on first run. Safe to remove in production.
-- ------------------------------------------------------------
INSERT INTO tasks (title, description, completion_prompt, reward_amount, status) VALUES
('Complete a 5-minute consumer survey', 'Answer the questions below about your shopping habits over the last month. Takes about 5 minutes.', 'In 1-2 sentences, what did you buy most often last month, and where?', 25.00, 'active'),
('Watch and rate 3 product videos', 'Watch three short product demo videos and rate each one out of 5 for clarity.', 'List your rating (1-5) for each of the 3 videos, e.g. "4, 3, 5".', 15.00, 'active'),
('Test a mobile app and report bugs', 'Install a beta app, use it for 10 minutes, and submit a short bug report.', 'Describe one bug or one thing that worked well while testing.', 60.00, 'active'),
('Transcribe a 2-minute audio clip', 'Listen to the short audio clip below and type out what is said.', 'Paste your transcription of the clip here.', 40.00, 'active'),
('Sign up for a newsletter and confirm', 'Subscribe to the listed newsletter using your email and confirm the subscription.', 'Paste the confirmation code or subject line from the confirmation email.', 10.00, 'active'),
('Fill in a market-research questionnaire', 'Answer the 10 questions below about your internet and mobile data usage.', 'Roughly how many GB of mobile data do you use per month?', 30.00, 'active');
