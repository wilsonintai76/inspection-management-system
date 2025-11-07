/**
 * Authentication Composable
 * Provides auth state and permission checking
 */

import { computed } from 'vue';
import { getUserPermissions, hasPermission, isDepartmentRestricted, getPrimaryRole } from '../lib/permissions';
import type { Permission } from '../lib/permissions';

export function useAuth() {
  const isLoggedIn = computed(() => sessionStorage.getItem('isLoggedIn') === 'true');
  const userId = computed(() => sessionStorage.getItem('userId') || '');
  const staffId = computed(() => sessionStorage.getItem('staffId') || '');
  const userEmail = computed(() => sessionStorage.getItem('userEmail') || '');
  const userName = computed(() => sessionStorage.getItem('userName') || '');
  const userDepartmentId = computed(() => sessionStorage.getItem('userDepartmentId') || '');
  
  const userRoles = computed(() => {
    const rolesJson = sessionStorage.getItem('userRoles');
    if (!rolesJson) return [];
    try {
      return JSON.parse(rolesJson);
    } catch {
      return [];
    }
  });

  const primaryRole = computed(() => getPrimaryRole(userRoles.value));
  const permissions = computed<Permission>(() => getUserPermissions(userRoles.value));
  const isAdmin = computed(() => userRoles.value.includes('Admin'));
  const isAssetOfficer = computed(() => userRoles.value.includes('Asset Officer'));
  const isAuditor = computed(() => userRoles.value.includes('Auditor'));
  const isViewer = computed(() => userRoles.value.includes('Viewer'));
  const isDeptRestricted = computed(() => isDepartmentRestricted(userRoles.value));

  /**
   * Check if user has a specific permission
   */
  function can(permissionKey: keyof Permission): boolean {
    return hasPermission(userRoles.value, permissionKey);
  }

  /**
   * Check if user can access a specific department
   */
  function canAccessDepartment(departmentId: number | string): boolean {
    // Admin can access all departments
    if (isAdmin.value) return true;

    // If not department restricted, allow all
    if (!isDeptRestricted.value) return true;

    // Check if it's their department
    return userDepartmentId.value === String(departmentId);
  }

  return {
    // State
    isLoggedIn,
    userId,
    staffId,
    userEmail,
    userName,
    userDepartmentId,
    userRoles,
    primaryRole,
    permissions,
    
    // Role checks
    isAdmin,
    isAssetOfficer,
    isAuditor,
    isViewer,
    isDeptRestricted,
    
    // Permission checks
    can,
    canAccessDepartment
  };
}
