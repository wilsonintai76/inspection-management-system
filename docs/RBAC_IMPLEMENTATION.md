# Role-Based Access Control (RBAC) Implementation

## Overview
The system now implements comprehensive role-based access control with four user roles, each with specific permissions.

## Roles and Permissions

### 1. **Viewer** (Read-Only)
- ✅ Can view: Dashboard overview only
- ❌ Cannot: Access any other features
- **Use Case**: Staff who only need to see general statistics

### 2. **Asset Officer** (Department-Specific Management)
- ✅ Can view: Dashboard overview, Inspection Status, Departments
- ✅ Can toggle: Inspection status (for their department only)
- ✅ Can CRUD: Departments (for their department only - IT department)
- ❌ Cannot: Access Schedule, Locations, Users
- **Restriction**: **Department-restricted** - can only manage their own department's data
- **Use Case**: IT department staff managing their department's inspections

### 3. **Auditor** (Schedule Management)
- ✅ Can view: Dashboard overview, Schedule
- ✅ Can manage: Schedule (can add/remove themselves as auditor)
- ✅ Restriction: Can only add themselves once per location, but can add for multiple locations
- ❌ Cannot: Toggle inspection status, access Departments, Locations, Users
- **Use Case**: Audit staff scheduling their own inspections

### 4. **Admin** (Full Access)
- ✅ Can access: All features
- ✅ Can CRUD: All entities (Departments, Locations, Users, Schedule, Inspections)
- ✅ No restrictions: Can manage all departments and locations
- **Use Case**: System administrators with full control

## Permission Matrix

| Feature | Viewer | Asset Officer | Auditor | Admin |
|---------|--------|---------------|---------|-------|
| Dashboard Overview | ✅ | ✅ | ✅ | ✅ |
| Inspection Status (View) | ❌ | ✅ (Own Dept) | ❌ | ✅ |
| Inspection Status (Toggle) | ❌ | ✅ (Own Dept) | ❌ | ✅ |
| Schedule (View) | ❌ | ❌ | ✅ | ✅ |
| Schedule (Manage) | ❌ | ❌ | ✅ (Self Only) | ✅ |
| Departments (View) | ❌ | ✅ (Own Dept) | ❌ | ✅ |
| Departments (CRUD) | ❌ | ✅ (Own Dept) | ❌ | ✅ |
| Locations (View) | ❌ | ❌ | ❌ | ✅ |
| Locations (Manage) | ❌ | ❌ | ❌ | ✅ |
| Users (View) | ❌ | ❌ | ❌ | ✅ |
| Users (Manage) | ❌ | ❌ | ❌ | ✅ |

## Implementation Files

### Frontend

#### 1. `vue-frontend/src/lib/permissions.ts`
- Defines all role permissions
- Permission checking functions
- Role priority logic

#### 2. `vue-frontend/src/composables/useAuth.ts`
- Authentication composable
- Permission checking helpers
- Department access validation

#### 3. `vue-frontend/src/router/index.ts`
- Route-level permission guards
- Redirects unauthorized access
- Meta fields for required permissions

#### 4. `vue-frontend/src/App.vue`
- Conditional sidebar menu items based on permissions
- Only shows menu items user can access

#### 5. `vue-frontend/src/views/Login.vue`
- Stores `userDepartmentId` in session storage
- Used for department-restricted access

### Backend (To Be Implemented)

#### Required API Updates:

1. **Inspection Status API** (`php-backend/public/api/inspections.php`)
   ```php
   // For Asset Officer: Filter by department
   if ($userRole === 'Asset Officer') {
       $stmt = $pdo->prepare('
           SELECT i.* FROM inspections i
           INNER JOIN locations l ON i.location_id = l.id
           WHERE l.department_id = ?
       ');
       $stmt->execute([$userDepartmentId]);
   }
   ```

2. **Departments API** (`php-backend/public/api/departments.php`)
   ```php
   // For Asset Officer: Only their department
   if ($userRole === 'Asset Officer') {
       $stmt = $pdo->prepare('SELECT * FROM departments WHERE id = ?');
       $stmt->execute([$userDepartmentId]);
   }
   ```

3. **Schedule API** (`php-backend/public/api/schedule.php`)
   ```php
   // For Auditor: Can only add/remove themselves
   if ($userRole === 'Auditor') {
       // Check if already assigned to this location
       $stmt = $pdo->prepare('
           SELECT COUNT(*) FROM inspections 
           WHERE location_id = ? AND auditor_id = ?
       ');
       $stmt->execute([$locationId, $userId]);
       if ($stmt->fetchColumn() > 0) {
           http_response_code(400);
           echo json_encode(['error' => 'Already assigned to this location']);
           exit;
       }
   }
   ```

## Usage Examples

### Check Permission in Vue Component
```typescript
<script setup>
import { useAuth } from '@/composables/useAuth';

const auth = useAuth();

// Check specific permission
if (auth.can('canToggleInspectionStatus')) {
  // Show toggle button
}

// Check role
if (auth.isAdmin) {
  // Show admin features
}

// Check department access
if (auth.canAccessDepartment(departmentId)) {
  // Allow access
}
</script>

<template>
  <button v-if="auth.can('canToggleInspectionStatus')" @click="toggle">
    Toggle Status
  </button>
  
  <div v-if="auth.isAdmin">
    Admin Panel
  </div>
</template>
```

### Filter Data by Department (Asset Officer)
```typescript
const filteredDepartments = computed(() => {
  if (auth.isDeptRestricted) {
    return departments.value.filter(d => 
      auth.canAccessDepartment(d.id)
    );
  }
  return departments.value;
});
```

### Prevent Unauthorized Actions
```typescript
async function toggleInspectionStatus(inspectionId) {
  if (!auth.can('canToggleInspectionStatus')) {
    alert('You do not have permission to toggle inspection status');
    return;
  }
  
  // Proceed with toggle
  await api.put(`/inspections.php?id=${inspectionId}`, {
    status: newStatus
  });
}
```

## Testing Scenarios

### Test User Roles

1. **Create Viewer User**
   - Staff ID: 1001
   - Role: Viewer
   - Expected: Can only see dashboard overview

2. **Create Asset Officer User**
   - Staff ID: 1002
   - Role: Asset Officer
   - Department: IT (department_id = 1)
   - Expected: Can see dashboard, inspection status (IT dept only), departments (IT only)

3. **Create Auditor User**
   - Staff ID: 1003
   - Role: Auditor
   - Expected: Can see dashboard, schedule; can add self to schedule

4. **Create Admin User**
   - Staff ID: 1004
   - Role: Admin
   - Expected: Can access everything

### Test Cases

#### Viewer Role
- ✅ Login and see dashboard
- ❌ Try to access /schedule → redirect to dashboard with error
- ❌ Try to access /departments → redirect to dashboard with error
- ❌ Sidebar should only show "Overview"

#### Asset Officer Role
- ✅ Login and see dashboard
- ✅ Access inspection status
- ✅ See only IT department inspections
- ✅ Toggle inspection status for IT department
- ❌ Cannot see other departments
- ❌ Cannot access schedule, locations, users

#### Auditor Role
- ✅ Login and see dashboard
- ✅ Access schedule
- ✅ Add self to location (if not already assigned)
- ❌ Cannot add self twice to same location
- ✅ Can add self to different locations
- ❌ Cannot access departments, locations, users

#### Admin Role
- ✅ Full access to all features
- ✅ Can CRUD all entities
- ✅ Can see all departments
- ✅ No restrictions

## Session Storage

After login, these values are stored:
```javascript
sessionStorage.setItem('isLoggedIn', 'true');
sessionStorage.setItem('userId', user.id);
sessionStorage.setItem('staffId', user.staff_id);
sessionStorage.setItem('userEmail', user.email);
sessionStorage.setItem('userName', user.name);
sessionStorage.setItem('userRoles', JSON.stringify(user.roles)); // ['Admin', 'Auditor']
sessionStorage.setItem('userDepartmentId', user.department_id); // For department restriction
```

## Next Steps

1. ✅ Frontend RBAC implemented
2. ⏳ Backend API filtering by role and department
3. ⏳ Schedule constraint (auditor can only add self once per location)
4. ⏳ Department CRUD restriction for Asset Officer
5. ⏳ Inspection status toggle restriction for Asset Officer
6. ⏳ Create test users with different roles in database
7. ⏳ End-to-end testing of all role scenarios

## Database Setup

To assign roles to users, insert into `user_roles` table:

```sql
-- Viewer
INSERT INTO user_roles (user_id, role) VALUES ('1001', 'Viewer');

-- Asset Officer (IT Department)
INSERT INTO user_roles (user_id, role) VALUES ('1002', 'Asset Officer');
UPDATE users SET department_id = 1 WHERE staff_id = '1002'; -- IT dept

-- Auditor
INSERT INTO user_roles (user_id, role) VALUES ('1003', 'Auditor');

-- Admin
INSERT INTO user_roles (user_id, role) VALUES ('1004', 'Admin');
```
