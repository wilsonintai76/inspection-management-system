-- Quick Test User Setup
-- Run this in phpMyAdmin to create a test admin user

-- Create admin user with staff_id 1001
INSERT INTO users (id, staff_id, name, email, personal_email, phone, department_id, photo_url, password_hash, must_change_password, status) 
VALUES (
  '1001',
  '1001',
  'Admin Test',
  'admin@institution.edu',
  'admin@gmail.com',
  '012-3456789',
  3, -- Update this to match your department ID
  NULL,
  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- password: "password"
  1, -- Must change password on first login
  'Verified'
)
ON DUPLICATE KEY UPDATE 
  staff_id = '1001',
  password_hash = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
  must_change_password = 1;

-- Assign Admin role
INSERT INTO user_roles (user_id, role) VALUES ('1001', 'Admin')
ON DUPLICATE KEY UPDATE role = 'Admin';

-- Update existing user (your current user)
UPDATE users 
SET 
  staff_id = '0001',
  password_hash = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
  must_change_password = 1
WHERE id = 'wilsonintai76@gmail.com';

-- Test Login Credentials:
-- Staff ID: 1001 or 0001
-- Password: password
-- (You will be forced to change password on first login)
