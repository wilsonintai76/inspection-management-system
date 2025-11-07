-- Migration script to add staff_id and password fields to existing users table
-- Run this in phpMyAdmin or MySQL client

-- Add new columns to users table
ALTER TABLE users 
ADD COLUMN staff_id VARCHAR(4) NULL AFTER id,
ADD COLUMN personal_email VARCHAR(191) NULL AFTER email,
ADD COLUMN password_hash VARCHAR(255) NULL AFTER photo_url,
ADD COLUMN must_change_password TINYINT(1) NOT NULL DEFAULT 0 AFTER password_hash;

-- For existing users, you can set staff_id manually or generate from a sequence
-- Example: Update existing user with staff_id
-- UPDATE users SET staff_id = '0001' WHERE id = 'wilsonintai76@gmail.com';

-- After setting staff_id for all existing users, make it NOT NULL and UNIQUE
-- ALTER TABLE users MODIFY COLUMN staff_id VARCHAR(4) NOT NULL UNIQUE;

-- Note: You'll need to set passwords for existing users
-- Option 1: Generate temp passwords via API (recommended)
-- Option 2: Manually set via SQL:
-- UPDATE users SET password_hash = '$2y$10$...hashed_password...', must_change_password = 1 WHERE id = 'user_id';
