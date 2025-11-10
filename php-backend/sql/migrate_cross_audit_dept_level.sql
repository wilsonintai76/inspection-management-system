-- Migration Script: Convert to Department-Level Cross-Audit System
-- Run this in phpMyAdmin or MySQL command line

-- Step 1: Drop old table if it exists (backs up data first if needed)
-- WARNING: This will delete all existing cross-audit assignments!
-- If you need to preserve data, export the table first

DROP TABLE IF EXISTS cross_audit_assignments;

-- Step 2: Create new department-level cross_audit_assignments table

CREATE TABLE IF NOT EXISTS cross_audit_assignments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    auditor_department_id INT NOT NULL COMMENT 'Department whose auditors can audit target_department_id',
    target_department_id INT NOT NULL COMMENT 'Department that can be audited by auditor_department_id',
    assigned_by_admin_id VARCHAR(191) NOT NULL,
    notes TEXT,
    active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (auditor_department_id) REFERENCES departments(id) ON DELETE CASCADE,
    FOREIGN KEY (target_department_id) REFERENCES departments(id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_by_admin_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_dept_assignment (auditor_department_id, target_department_id),
    INDEX idx_auditor_dept (auditor_department_id),
    INDEX idx_target_dept (target_department_id),
    INDEX idx_active (active)
);

-- Step 3: Verify table was created
SHOW CREATE TABLE cross_audit_assignments;

-- Step 4: You can now create department-level assignments via the web UI
-- Go to: Users → Cross-Audit → + Create Department Assignment

-- Example manual insert (optional):
-- INSERT INTO cross_audit_assignments 
-- (auditor_department_id, target_department_id, assigned_by_admin_id, notes) 
-- VALUES 
-- (1, 2, 'admin_user_id', 'Finance audits Engineering'),
-- (2, 3, 'admin_user_id', 'Engineering audits Operations');

SELECT 'Migration completed successfully!' AS status;
