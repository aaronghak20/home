-- ============================================================
-- Migration: lower minimum withdrawal threshold to KSh 100
-- Run in phpMyAdmin: taskpesa_db -> SQL tab -> paste -> Go.
-- Safe to run once.
-- ============================================================

USE taskpesa_db;

-- Change the column default so any NEW accounts registered from now on get 100.
ALTER TABLE users
  MODIFY min_withdrawal_threshold DECIMAL(10,2) NOT NULL DEFAULT 100.00;

-- Update EXISTING accounts (they already have 500 stored explicitly,
-- so changing the default above doesn't affect them on its own).
UPDATE users SET min_withdrawal_threshold = 100.00;
