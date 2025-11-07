-- Clear users table for fresh registration testing
-- This will delete all users and their roles

USE inspectable;

-- Delete all user roles first (foreign key constraint)
DELETE FROM user_roles;

-- Delete all users
DELETE FROM users;

-- Reset any auto-increment if needed
ALTER TABLE users AUTO_INCREMENT = 1;

SELECT 'Users table cleared successfully' AS status;
