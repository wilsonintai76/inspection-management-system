-- ============================================
-- Create Dummy Users with Different Roles
-- ============================================
-- This script creates test users for each role:
-- 1. Viewer (2001)
-- 2. Asset Officer (2002) - IT Department
-- 3. Auditor (2003)
-- 4. Admin (2004)
-- All passwords: "password123"
-- ============================================

-- First, ensure we have an IT department
INSERT INTO departments (name, created_at, updated_at) 
VALUES ('Information Technology', NOW(), NOW())
ON DUPLICATE KEY UPDATE name = name;

-- Get the IT department ID (assuming it's 1, but this will work even if different)
SET @it_dept_id = (SELECT id FROM departments WHERE name = 'Information Technology' LIMIT 1);

-- ============================================
-- 1. VIEWER USER
-- ============================================
-- Staff ID: 2001
-- Password: password123
-- Role: Viewer (can only see dashboard)

DELETE FROM user_roles WHERE user_id = '2001';
DELETE FROM users WHERE staff_id = '2001';

INSERT INTO users (
    id, 
    staff_id, 
    name, 
    email, 
    personal_email, 
    phone, 
    department_id, 
    password_hash, 
    must_change_password, 
    email_verified, 
    status, 
    created_at, 
    updated_at
) VALUES (
    '2001',
    '2001',
    'Ahmad Viewer',
    'ahmad.viewer@poliku.edu.my',
    'ahmad.viewer@gmail.com',
    '012-3456001',
    @it_dept_id,
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- password123
    0,
    1,
    'Verified',
    NOW(),
    NOW()
);

INSERT INTO user_roles (user_id, role) VALUES ('2001', 'Viewer');

-- ============================================
-- 2. ASSET OFFICER USER
-- ============================================
-- Staff ID: 2002
-- Password: password123
-- Role: Asset Officer (IT Department only)

DELETE FROM user_roles WHERE user_id = '2002';
DELETE FROM users WHERE staff_id = '2002';

INSERT INTO users (
    id, 
    staff_id, 
    name, 
    email, 
    personal_email, 
    phone, 
    department_id, 
    password_hash, 
    must_change_password, 
    email_verified, 
    status, 
    created_at, 
    updated_at
) VALUES (
    '2002',
    '2002',
    'Siti Asset Officer',
    'siti.asset@poliku.edu.my',
    'siti.asset@gmail.com',
    '012-3456002',
    @it_dept_id,
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- password123
    0,
    1,
    'Verified',
    NOW(),
    NOW()
);

INSERT INTO user_roles (user_id, role) VALUES ('2002', 'Asset Officer');

-- ============================================
-- 3. AUDITOR USER
-- ============================================
-- Staff ID: 2003
-- Password: password123
-- Role: Auditor (can manage schedule)

DELETE FROM user_roles WHERE user_id = '2003';
DELETE FROM users WHERE staff_id = '2003';

INSERT INTO users (
    id, 
    staff_id, 
    name, 
    email, 
    personal_email, 
    phone, 
    department_id, 
    password_hash, 
    must_change_password, 
    email_verified, 
    status, 
    created_at, 
    updated_at
) VALUES (
    '2003',
    '2003',
    'Kumar Auditor',
    'kumar.auditor@poliku.edu.my',
    'kumar.auditor@gmail.com',
    '012-3456003',
    @it_dept_id,
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- password123
    0,
    1,
    'Verified',
    NOW(),
    NOW()
);

INSERT INTO user_roles (user_id, role) VALUES ('2003', 'Auditor');

-- ============================================
-- 4. ADMIN USER
-- ============================================
-- Staff ID: 2004
-- Password: password123
-- Role: Admin (full access)

DELETE FROM user_roles WHERE user_id = '2004';
DELETE FROM users WHERE staff_id = '2004';

INSERT INTO users (
    id, 
    staff_id, 
    name, 
    email, 
    personal_email, 
    phone, 
    department_id, 
    password_hash, 
    must_change_password, 
    email_verified, 
    status, 
    created_at, 
    updated_at
) VALUES (
    '2004',
    '2004',
    'Admin SuperUser',
    'admin@poliku.edu.my',
    'admin.super@gmail.com',
    '012-3456004',
    @it_dept_id,
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- password123
    0,
    1,
    'Verified',
    NOW(),
    NOW()
);

INSERT INTO user_roles (user_id, role) VALUES ('2004', 'Admin');

-- ============================================
-- 5. MULTI-ROLE USER (Admin + Auditor)
-- ============================================
-- Staff ID: 2005
-- Password: password123
-- Roles: Admin AND Auditor (multi-role example)

DELETE FROM user_roles WHERE user_id = '2005';
DELETE FROM users WHERE staff_id = '2005';

INSERT INTO users (
    id, 
    staff_id, 
    name, 
    email, 
    personal_email, 
    phone, 
    department_id, 
    password_hash, 
    must_change_password, 
    email_verified, 
    status, 
    created_at, 
    updated_at
) VALUES (
    '2005',
    '2005',
    'Raja Multi-Role',
    'raja.multi@poliku.edu.my',
    'raja.multi@gmail.com',
    '012-3456005',
    @it_dept_id,
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- password123
    0,
    1,
    'Verified',
    NOW(),
    NOW()
);

-- Add both Admin and Auditor roles
INSERT INTO user_roles (user_id, role) VALUES ('2005', 'Admin');
INSERT INTO user_roles (user_id, role) VALUES ('2005', 'Auditor');

-- ============================================
-- 6. MULTI-ROLE USER (Asset Officer + Auditor)
-- ============================================
-- Staff ID: 2006
-- Password: password123
-- Roles: Asset Officer AND Auditor (multi-role example)

DELETE FROM user_roles WHERE user_id = '2006';
DELETE FROM users WHERE staff_id = '2006';

INSERT INTO users (
    id, 
    staff_id, 
    name, 
    email, 
    personal_email, 
    phone, 
    department_id, 
    password_hash, 
    must_change_password, 
    email_verified, 
    status, 
    created_at, 
    updated_at
) VALUES (
    '2006',
    '2006',
    'Lee AssetAuditor',
    'lee.assetauditor@poliku.edu.my',
    'lee.assetauditor@gmail.com',
    '012-3456006',
    @it_dept_id,
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- password123
    0,
    1,
    'Verified',
    NOW(),
    NOW()
);

-- Add both Asset Officer and Auditor roles
INSERT INTO user_roles (user_id, role) VALUES ('2006', 'Asset Officer');
INSERT INTO user_roles (user_id, role) VALUES ('2006', 'Auditor');

-- ============================================
-- VERIFICATION QUERY
-- ============================================
-- Run this to verify all users were created correctly:

SELECT 
    u.staff_id,
    u.name,
    u.email,
    u.status,
    GROUP_CONCAT(ur.role) as roles,
    d.name as department
FROM users u
LEFT JOIN user_roles ur ON u.id = ur.user_id
LEFT JOIN departments d ON u.department_id = d.id
WHERE u.staff_id IN ('2001', '2002', '2003', '2004', '2005', '2006')
GROUP BY u.id
ORDER BY u.staff_id;

-- ============================================
-- TEST CREDENTIALS
-- ============================================
-- Viewer:              Staff ID: 2001, Password: password123
-- Asset Officer:       Staff ID: 2002, Password: password123
-- Auditor:             Staff ID: 2003, Password: password123
-- Admin:               Staff ID: 2004, Password: password123
-- Admin+Auditor:       Staff ID: 2005, Password: password123 (Multi-role)
-- AssetOfficer+Auditor: Staff ID: 2006, Password: password123 (Multi-role)
-- ============================================
