# Cross-Audit System - Department-Level Assignment

## 📋 Overview

The cross-audit system now uses **department-to-department assignments** instead of individual auditor assignments. This simplifies management and automatically covers all auditors from the assigned department.

## 🎯 How It Works

### Old Way (Individual Auditors)
```
❌ Admin assigns: "Auditor John (Finance) → Engineering Department"
❌ Admin assigns: "Auditor Mary (Finance) → Engineering Department"  
❌ Admin assigns: "Auditor Bob (Finance) → Engineering Department"
❌ Tedious! Must assign each auditor individually
```

### New Way (Department-Level)
```
✅ Admin assigns: "Finance Department → Engineering Department"
✅ Automatic! All Finance auditors can now audit Engineering
✅ When new auditor joins Finance, they automatically get Engineering access
✅ When auditor leaves Finance, access automatically revoked
```

## 🔧 How to Use

### 1. Admin Creates Department Assignment

1. Go to **Users** page → Click **Cross-Audit** button
2. Click **+ Create Department Assignment**
3. Select:
   - **Auditor Department (FROM)**: Department whose auditors will conduct audits
   - **Target Department (TO AUDIT)**: Department that will be audited
4. Add optional notes
5. Click **Create Assignment**

### 2. System Validates Automatically

✅ **Allowed**:
- Finance → Engineering
- Engineering → Finance  
- IT → Operations

❌ **Blocked**:
- Finance → Finance (same department = conflict of interest)
- Engineering → Engineering (same department = conflict of interest)

### 3. Auditors Get Automatic Access

When you assign "Finance → Engineering":
- **All** auditors with `Finance` as their department can audit Engineering locations
- **New** Finance auditors automatically get access
- **Former** Finance auditors lose access when moved to another department

## 📊 Example Scenario

**Setup:**
```
Departments:
- Finance (5 auditors)
- Engineering (3 auditors)
- Operations (4 auditors)

Assignments:
- Finance → Engineering
- Engineering → Operations
- Operations → Finance
```

**Result:**
- All 5 Finance auditors can audit Engineering locations
- All 3 Engineering auditors can audit Operations locations
- All 4 Operations auditors can audit Finance locations
- Nobody can audit their own department ✅

## 🔍 Database Schema

```sql
CREATE TABLE cross_audit_assignments (
    id INT PRIMARY KEY,
    auditor_department_id INT NOT NULL,      -- Department whose auditors can audit
    target_department_id INT NOT NULL,       -- Department that will be audited
    assigned_by_admin_id VARCHAR(191),       -- Admin who created assignment
    notes TEXT,
    active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (auditor_department_id) REFERENCES departments(id),
    FOREIGN KEY (target_department_id) REFERENCES departments(id),
    UNIQUE (auditor_department_id, target_department_id)
);
```

## 🎛️ Admin Management

### View All Assignments
- See all department-to-department mappings
- Filter by active/inactive status
- View creation date and assigned by admin

### Activate/Deactivate
- **Pause** assignments without deleting (⏸️ button)
- **Resume** assignments (▶️ button)
- Inactive assignments stop auditor access immediately

### Edit Notes
- Add context about why assignment exists
- Document rotation schedules
- Track special conditions

### Delete Assignment
- Permanent removal
- All auditors from source department lose target department access
- Confirms before deletion

## 🚀 Benefits

### For Admins
1. **One Assignment** covers entire department
2. **No maintenance** when auditors join/leave
3. **Clear structure** of who audits whom
4. **Easy rotation** just swap department assignments

### For Auditors
5. **Automatic access** based on department membership
6. **Clear scope** know which departments you can audit
7. **No confusion** about individual permissions

### For Organization
8. **Audit independence** guaranteed (no own-department audits)
9. **Balanced workload** distributed across departments
10. **Transparent structure** easy to understand and audit

## 🛠️ Validation Rules

The system enforces these rules automatically:

1. **No Self-Auditing**: Department cannot audit itself
2. **No Duplicates**: Can't create same assignment twice
3. **Active Check**: Only active assignments grant access
4. **Department Membership**: Auditor must belong to source department
5. **Role Check**: User must have Auditor role

## 📝 API Changes

### Create Assignment
```http
POST /api/cross-audit.php
{
  "admin_id": "admin123",
  "auditor_department_id": 1,     // Finance
  "target_department_id": 2,      // Engineering
  "notes": "Quarterly rotation schedule"
}
```

### Check Eligibility (Inspection Scheduling)
The system automatically checks:
1. Gets auditor's `department_id` from users table
2. Checks if `auditor_department_id → target_department_id` assignment exists
3. Validates assignment is `active = 1`
4. Blocks if auditor's department = target department

## ⚠️ Migration from Old System

If you were using individual auditor assignments:

1. **Drop old table** (if exists):
   ```sql
   DROP TABLE IF EXISTS cross_audit_assignments;
   ```

2. **Run new schema**:
   ```sql
   -- From schema.sql
   CREATE TABLE cross_audit_assignments (...);
   ```

3. **Recreate assignments** at department level:
   - Old: 50 individual auditor assignments
   - New: 5-10 department-level assignments

## 🆘 Troubleshooting

**Q: Auditor can't schedule inspection for a location**
- A: Check if their department has active assignment to location's department

**Q: Assignment shows as active but doesn't work**
- A: Verify auditor's `department_id` matches `auditor_department_id` in assignment

**Q: Need to rotate audit responsibilities**
- A: Deactivate old assignments, create new department-level assignments

**Q: Auditor changed departments, still has old access**
- A: Update user's `department_id` - access updates automatically

## 📞 Support

For issues or questions:
1. Check assignment status in Cross-Audit page
2. Verify auditor's department in Users page  
3. Review enforcement rules in CROSS_AUDIT_GUIDE.md
4. Check validation logic in `inspections.php`
