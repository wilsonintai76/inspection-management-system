-- Migration: Add email verification fields to users table
-- Run this in phpMyAdmin to add email verification support

USE inspectable;

-- Version-safe migration using information_schema checks and prepared statements
-- Works on MySQL/MariaDB that do not support "ADD COLUMN IF NOT EXISTS"

SET @db := DATABASE();

-- 0) Ensure password_hash exists (some older schemas may miss this)
SET @col := 'password_hash';
SET @exists := (
	SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
	WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'users' AND COLUMN_NAME = @col
);
SET @sql := IF(@exists = 0,
	'ALTER TABLE users ADD COLUMN password_hash VARCHAR(255) NULL',
	'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 1) Ensure must_change_password exists
SET @col := 'must_change_password';
SET @exists := (
	SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
	WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'users' AND COLUMN_NAME = @col
);
SET @sql := IF(@exists = 0,
	'ALTER TABLE users ADD COLUMN must_change_password TINYINT(1) NOT NULL DEFAULT 0',
	'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 2) Add email_verified
SET @col := 'email_verified';
SET @exists := (
	SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
	WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'users' AND COLUMN_NAME = @col
);
SET @sql := IF(@exists = 0,
	'ALTER TABLE users ADD COLUMN email_verified TINYINT(1) NOT NULL DEFAULT 0',
	'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 3) Add verification_token
SET @col := 'verification_token';
SET @exists := (
	SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
	WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'users' AND COLUMN_NAME = @col
);
SET @sql := IF(@exists = 0,
	'ALTER TABLE users ADD COLUMN verification_token VARCHAR(64) NULL',
	'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 4) Add verification_token_expires
SET @col := 'verification_token_expires';
SET @exists := (
	SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
	WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'users' AND COLUMN_NAME = @col
);
SET @sql := IF(@exists = 0,
	'ALTER TABLE users ADD COLUMN verification_token_expires TIMESTAMP NULL',
	'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- For existing users created via admin panel, mark as verified
-- Only mark as verified if both columns exist
SET @has_mcp := (
	SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
	WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'users' AND COLUMN_NAME = 'must_change_password'
);
SET @has_ph := (
	SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
	WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'users' AND COLUMN_NAME = 'password_hash'
);
SET @sql := IF(@has_mcp > 0 AND @has_ph > 0,
	'UPDATE users SET email_verified = 1 WHERE password_hash IS NOT NULL AND must_change_password = 1',
	'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Create index for faster token lookups (add only if missing)
SET @idx_exists := (
	SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
	WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'users' AND INDEX_NAME = 'idx_verification_token'
);
SET @sql := IF(@idx_exists = 0,
	'ALTER TABLE users ADD INDEX idx_verification_token (verification_token)',
	'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SELECT 'Email verification fields added successfully' AS status;
