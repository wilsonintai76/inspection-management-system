-- Complete database schema update for email verification
-- This adds ALL missing columns needed for the registration system

USE inspectable;

SET @db := DATABASE();

-- 1) Add staff_id if missing
SET @col := 'staff_id';
SET @exists := (
  SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
  WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'users' AND COLUMN_NAME = @col
);
SET @sql := IF(@exists = 0,
  'ALTER TABLE users ADD COLUMN staff_id VARCHAR(4) NULL',
  'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 2) Make staff_id unique if it exists and is not unique
SET @idx_exists := (
  SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
  WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'users' AND INDEX_NAME = 'staff_id'
);
SET @sql := IF(@idx_exists = 0,
  'ALTER TABLE users ADD UNIQUE KEY staff_id (staff_id)',
  'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 3) Add password_hash if missing
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

-- 4) Add must_change_password if missing
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

-- 5) Add email_verified if missing
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

-- 6) Add verification_token if missing
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

-- 7) Add verification_token_expires if missing
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

-- 8) Add personal_email if missing
SET @col := 'personal_email';
SET @exists := (
  SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
  WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'users' AND COLUMN_NAME = @col
);
SET @sql := IF(@exists = 0,
  'ALTER TABLE users ADD COLUMN personal_email VARCHAR(191) NULL',
  'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 9) Create index for faster token lookups (only if missing)
SET @idx_exists := (
  SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
  WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'users' AND INDEX_NAME = 'idx_verification_token'
);
SET @sql := IF(@idx_exists = 0,
  'ALTER TABLE users ADD INDEX idx_verification_token (verification_token)',
  'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Show final structure
SHOW COLUMNS FROM users;

SELECT 'All columns added successfully!' AS status;
