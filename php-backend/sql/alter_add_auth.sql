-- Add authentication fields for staff login
ALTER TABLE users 
  ADD COLUMN password_hash VARCHAR(255) NULL AFTER photo_url,
  ADD COLUMN must_change_password TINYINT(1) NOT NULL DEFAULT 0 AFTER password_hash;

-- Ensure id is used as Staff ID (4-digit); cannot enforce by schema easily, enforce in application.
