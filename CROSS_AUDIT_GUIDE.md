# Cross-Department Audit System

## Overview
The Cross-Department Audit System ensures audit independence by preventing auditors from auditing their own departments. Instead, admin assigns auditors to audit other departments, maintaining objectivity and eliminating conflicts of interest.

## Table of Contents
- [Why Cross-Audit?](#why-cross-audit)
- [How It Works](#how-it-works)
- [User Roles](#user-roles)
- [Quick Start Guide](#quick-start-guide)
- [Admin Workflow](#admin-workflow)
- [Auditor Workflow](#auditor-workflow)
- [Enforcement Rules](#enforcement-rules)
- [Best Practices](#best-practices)
- [Troubleshooting](#troubleshooting)

---

## Why Cross-Audit?

### Problem: Conflict of Interest
When auditors audit their own department's assets:
- ❌ Lack of independence
- ❌ Potential bias in findings
- ❌ Reduced credibility of audit results
- ❌ Non-compliance with audit standards

### Solution: Cross-Department Assignment
Auditors are assigned to audit OTHER departments:
- ✅ Complete independence
- ✅ Objective findings
- ✅ Credible audit process
- ✅ Meets professional audit standards

### Real-World Scenario

**Before Cross-Audit:**
- Ahmad (JKA Auditor) audits JKA assets → Conflict of interest

**After Cross-Audit:**
- Ahmad (JKA Auditor) → Assigned to audit JKE & JPH
- Siti (JKE Auditor) → Assigned to audit JKA & JADUAL
- Lee (JPH Auditor) → Assigned to audit JKE & JPA

Result: Everyone audits departments they're NOT part of ✅

---

## How It Works

### 1. Admin Creates Assignments
Admin logs in and navigates to:
```
Users → Cross-Audit → + Assign Auditor to Department
```

Admin pairs:
- **Auditor:** User with Auditor role
- **Department:** Department to audit (NOT auditor's own)

### 2. System Enforces Rules
When scheduling inspections:
- System checks if auditor has active assignment for target department
- **Blocks** auditors from their own department
- **Allows** only auditors with valid cross-audit assignments

### 3. Auditors See Only Assigned Departments
Auditor dashboard filters:
- Shows only departments they're assigned to audit
- Hides their own department
- Displays clear assignment information

---

## User Roles

### Admin
**Permissions:**
- Create cross-audit assignments
- View all assignments
- Activate/deactivate assignments
- Delete assignments
- Modify assignment notes

**Responsibilities:**
- Plan cross-audit distribution
- Balance workload across auditors
- Review assignment effectiveness
- Update assignments as staff changes

### Auditor
**Permissions:**
- View own assignments
- Conduct audits only in assigned departments
- See filtered department list

**Responsibilities:**
- Audit assigned departments objectively
- Complete scheduled inspections
- Report findings accurately

---

## Quick Start Guide

### Step 1: Navigate to Cross-Audit Management
1. Login as **Admin**
2. Go to **Users** page (👥 icon)
3. Click **"Cross-Audit"** button

### Step 2: Create First Assignment
1. Click **"+ Assign Auditor to Department"**
2. Select **Auditor** from dropdown
3. Select **Department** to audit
   - System prevents selecting auditor's own department
4. Add optional notes
5. Click **"Assign"**

### Step 3: Verify Assignment
- Check assignment appears in table
- Status shows **Active** (green badge)
- Auditor name → Assigned Department visible

### Step 4: Create Balanced Distribution
Best practice: Each department audited by 2-3 auditors from other departments

**Example for 4 Departments:**

| Department | Audited By |
|------------|------------|
| JKA | Siti (JKE), Lee (JPH) |
| JKE | Ahmad (JKA), Nurul (JADUAL) |
| JPH | Ahmad (JKA), Siti (JKE) |
| JADUAL | Lee (JPH), Nurul (JKA) |

### Step 5: Schedule Inspections
Go to Schedule page and assign auditors:
- Only eligible auditors appear in dropdown
- System validates assignments automatically

---

## Admin Workflow

### Creating Assignments

#### Single Assignment
```
1. Cross-Audit page
2. + Assign Auditor to Department
3. Fill form:
   - Auditor: Ahmad (2001) - JKA
   - Department: JABATAN KEJURUTERAAN ELEKTRIK
   - Notes: Quarterly cross-audit Q4 2025
4. Assign
```

#### Bulk Planning
For multiple departments, create balanced matrix:

**Planning Table:**
| Auditor | Own Dept | Can Audit | Cannot Audit |
|---------|----------|-----------|--------------|
| Ahmad | JKA | JKE, JPH, JADUAL, JPA | JKA |
| Siti | JKE | JKA, JPH, JADUAL, JPA | JKE |
| Lee | JPH | JKA, JKE, JADUAL, JPA | JPH |

Create 2-3 assignments per auditor for balanced workload.

### Managing Assignments

#### View All Assignments
- Table shows all current assignments
- Filter: Toggle "Show Inactive" to see deactivated

#### Edit Assignment
Click **📝** (Edit Notes) to add/update notes:
- Audit cycle information
- Special instructions
- Performance notes

#### Deactivate Assignment
Click **⏸️** (Pause) to temporarily disable:
- Auditor on leave
- Department restructuring
- End of audit cycle

#### Reactivate Assignment
Click **▶️** (Play) to re-enable deactivated assignment

#### Delete Assignment
Click **🗑️** (Delete) to permanently remove:
- ⚠️ Use carefully - cannot undo
- Consider deactivating instead

### Monitoring Effectiveness

**Dashboard Metrics to Track:**
- Number of active assignments per auditor
- Departments with insufficient auditor coverage
- Audit completion rates by auditor
- Finding severity by cross-audit vs. same-department (historical)

---

## Auditor Workflow

### Viewing Your Assignments

1. Login as Auditor
2. Go to Dashboard or Schedule
3. System automatically filters:
   - Shows only departments you're assigned to audit
   - Hides your own department

### Conducting Cross-Audit

1. Navigate to assigned department
2. Schedule page shows eligible locations
3. Conduct asset inspection
4. Submit findings objectively

### Benefits for Auditors

- ✅ Clear scope of responsibility
- ✅ No conflict of interest concerns
- ✅ Professional credibility maintained
- ✅ Learn from other department practices

---

## Enforcement Rules

### Rule 1: Own Department Block
**Rule:** Auditor CANNOT be assigned to their own department

**Example:**
- Ahmad belongs to JKA
- System prevents assigning Ahmad → JKA
- Dropdown shows: "JKA (Own Dept - Not Allowed)"

### Rule 2: Active Assignment Required
**Rule:** Auditor can ONLY audit departments with active assignments

**Example:**
- Ahmad assigned to JKE (Active)
- Ahmad assigned to JPH (Inactive)
- Result: Ahmad can audit JKE only

### Rule 3: Inspection Validation
**Rule:** System validates auditors during inspection scheduling

**Scenario:**
- Inspection scheduled for Location X in JKE
- Assigned Auditor: Ahmad
- System checks:
  1. Is Ahmad an Auditor? ✅
  2. Does Ahmad have active JKE assignment? ✅
  3. Allow scheduling ✅

**Blocked Scenario:**
- Assigned Auditor: Ahmad
- Location Y in JKA
- System checks:
  1. Is Ahmad an Auditor? ✅
  2. Does Ahmad have active JKA assignment? ❌ (Own department)
  3. **Block with error:** "Auditor cannot audit this department"

### Rule 4: Dual Auditor Validation
**Rule:** Both Auditor 1 and Auditor 2 must have valid assignments

**Example:**
- Auditor 1: Ahmad (JKA) → Assigned to JKE ✅
- Auditor 2: Siti (JKE) → Own department ❌
- **Result:** System rejects scheduling

---

## Best Practices

### 1. Balanced Distribution

**Goal:** Each department audited by 2-3 external auditors

**Planning Matrix Example:**
```
Departments: JKA, JKE, JPH, JADUAL (4 total)
Auditors: Ahmad (JKA), Siti (JKE), Lee (JPH), Nurul (JADUAL)

Assignments:
- Ahmad → JKE, JPH, JADUAL (3 depts)
- Siti → JKA, JPH, JADUAL (3 depts)
- Lee → JKA, JKE, JADUAL (3 depts)
- Nurul → JKA, JKE, JPH (3 depts)

Result: Each dept has 3 external auditors ✅
```

### 2. Rotation Strategy

**Annual Rotation:**
- Rotate assignments every audit cycle (e.g., yearly)
- Prevents complacency
- Fresh perspective on each department

**Example:**
- **Year 1:** Ahmad audits JKE, JPH
- **Year 2:** Ahmad audits JADUAL, JPA
- **Year 3:** Ahmad audits JKE, JADUAL

### 3. Workload Balance

**Monitor:**
- Number of departments per auditor
- Size/complexity of assigned departments
- Geographic distribution (if relevant)

**Adjust:**
- Redistribute if one auditor overloaded
- Consider department asset counts
- Balance experienced vs. new auditors

### 4. Documentation

**Record in Notes Field:**
- Audit cycle: "Q1 2025 Cross-Audit"
- Special focus: "Priority: IT asset verification"
- Training: "Auditor completed department-specific training"

### 5. Periodic Review

**Quarterly:**
- Review active assignments
- Update for staff changes
- Deactivate assignments for absent staff

**Annually:**
- Full rotation planning
- Assess effectiveness
- Gather auditor feedback

---

## Troubleshooting

### Problem: Cannot Assign Auditor to Department

**Error:** "Auditor cannot audit their own department"

**Cause:** Selected department is auditor's own department

**Solution:**
1. Check auditor's department in Users page
2. Select a DIFFERENT department
3. If auditor must audit that dept, reassign auditor to different department first

---

### Problem: Assignment Already Exists

**Error:** "Assignment already exists"

**Cause:** Duplicate assignment for same auditor-department pair

**Solution:**
1. Check existing assignments table
2. If inactive, activate existing assignment instead
3. If active, no action needed

---

### Problem: Auditor Not Appearing in Eligible List

**Cause 1:** Auditor doesn't have Auditor role

**Solution:**
1. Go to Users page
2. Edit user
3. Add "Auditor" role

**Cause 2:** No active assignment for that department

**Solution:**
1. Go to Cross-Audit page
2. Create assignment for auditor → department

**Cause 3:** Auditor is from that department

**Solution:**
- Cannot fix - by design
- Assign different auditor

---

### Problem: Inspection Scheduling Rejected

**Error:** "Auditor X cannot audit this department"

**Cause:** Auditor lacks active assignment or is from that department

**Solution:**
1. Verify auditor's assignments in Cross-Audit page
2. If no assignment exists, create one
3. If assignment inactive, activate it
4. If auditor is from that dept, choose different auditor

---

### Problem: Too Many/Few Assignments

**Symptom:** Unbalanced workload

**Solution:**
1. Review all assignments in table
2. Count assignments per auditor
3. Count auditors per department
4. Add/remove assignments to balance

**Target Ratios:**
- **Per Auditor:** 2-4 departments
- **Per Department:** 2-3 auditors

---

## API Reference

### Endpoints

#### GET `/api/cross-audit.php`
Get assignments (filtered by role)

**Parameters:**
- `user_id` (required) - Current user ID
- `auditor_id` (optional) - Filter by specific auditor
- `department_id` (optional) - Filter by department

**Response:**
```json
{
  "assignments": [
    {
      "id": 1,
      "auditor_id": "ahmad_2001",
      "auditor_name": "Ahmad bin Ali",
      "auditor_staff_id": "2001",
      "auditor_own_department_id": 1,
      "auditor_own_department_name": "JKA",
      "assigned_department_id": 2,
      "assigned_department_name": "JKE",
      "assigned_department_acronym": "JKE",
      "assigned_by_admin_id": "admin_1001",
      "assigned_by_name": "Admin User",
      "notes": "Q4 2025 cross-audit",
      "active": 1,
      "created_at": "2025-11-10 10:00:00",
      "updated_at": "2025-11-10 10:00:00"
    }
  ]
}
```

#### POST `/api/cross-audit.php`
Create new assignment

**Body:**
```json
{
  "admin_id": "admin_1001",
  "auditor_id": "ahmad_2001",
  "department_id": 2,
  "notes": "Q4 2025 cross-audit"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Cross-audit assignment created",
  "assignment": { ... }
}
```

#### PUT `/api/cross-audit.php`
Update assignment (activate/deactivate/notes)

**Body:**
```json
{
  "admin_id": "admin_1001",
  "assignment_id": 1,
  "active": 0,
  "notes": "Updated notes"
}
```

#### DELETE `/api/cross-audit.php`
Delete assignment

**Body:**
```json
{
  "admin_id": "admin_1001",
  "assignment_id": 1
}
```

#### GET `/api/eligible-auditors.php`
Get auditors eligible for specific department

**Parameters:**
- `department_id` (required)

**Response:**
```json
{
  "eligible_auditors": [
    {
      "id": "ahmad_2001",
      "staff_id": "2001",
      "name": "Ahmad bin Ali",
      "email": "ahmad@poliku.edu.my",
      "department_id": 1,
      "own_department_name": "JKA",
      "assignment_id": 5
    }
  ],
  "count": 1
}
```

---

## Database Schema

### `cross_audit_assignments` Table

```sql
CREATE TABLE cross_audit_assignments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    auditor_id VARCHAR(191) NOT NULL,
    assigned_department_id INT NOT NULL,
    assigned_by_admin_id VARCHAR(191) NOT NULL,
    notes TEXT,
    active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (auditor_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_department_id) REFERENCES departments(id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_by_admin_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_assignment (auditor_id, assigned_department_id)
);
```

**Indexes:**
- `idx_auditor` on `auditor_id` (fast auditor lookup)
- `idx_department` on `assigned_department_id` (fast dept lookup)
- `idx_active` on `active` (filter active assignments)

---

## Compliance & Audit Trail

### What's Tracked
- ✅ Assignment creator (assigned_by_admin_id)
- ✅ Creation timestamp
- ✅ Last update timestamp
- ✅ Assignment status (active/inactive)
- ✅ Assignment notes

### Audit Questions Answered
- Who assigned auditors to departments?
- When were assignments created/changed?
- Which auditors were active during specific period?
- What departments did each auditor cover?

### Future Enhancements
- 📊 Audit trail log table (detailed change history)
- 📧 Email notifications on assignment changes
- 📈 Analytics dashboard (coverage metrics)
- 🔄 Auto-rotation scheduling
- 📋 Bulk assignment import (CSV)

---

## FAQ

**Q: Can an auditor audit their own department in emergencies?**
A: No, by design. If truly necessary, temporarily reassign the auditor to a different department in Users page, then create the assignment.

**Q: What if we have only one auditor per department?**
A: Hire external auditors or temporarily assign staff from other departments as guest auditors. The system enforces independence.

**Q: Can we disable cross-audit enforcement?**
A: Not currently. It's a core audit integrity feature. Contact support if you have specific requirements.

**Q: How do we handle auditor leave/absence?**
A: Deactivate their assignments (⏸️ button). Assign backup auditors to cover. Reactivate when they return.

**Q: Can Asset Officers perform cross-audits?**
A: No, only users with Auditor role. Assign Auditor role to Asset Officers if they should audit other departments.

---

## Summary

The Cross-Department Audit System:
- ✅ Ensures audit independence
- ✅ Prevents conflicts of interest
- ✅ Maintains professional standards
- ✅ Provides clear assignment tracking
- ✅ Enforces rules automatically
- ✅ Supports balanced workload distribution

For questions or support, contact your system administrator.
