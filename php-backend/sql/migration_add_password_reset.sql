-- Migration: Add password reset fields to users

ALTER TABLE users
  ADD COLUMN IF NOT EXISTS reset_token VARCHAR(64) NULL AFTER verification_token_expires,
  ADD COLUMN IF NOT EXISTS reset_token_expires TIMESTAMP NULL AFTER reset_token;

-- Index for fast token lookup
CREATE INDEX IF NOT EXISTS idx_reset_token ON users(reset_token);
