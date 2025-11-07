/**
 * Role-Based Access Control (RBAC) Module
 * Defines permissions for each role in the system
 */

export type UserRole = 'Admin' | 'Asset Officer' | 'Auditor' | 'Viewer';

export interface Permission {
  canViewDashboard: boolean;
  canViewInspectionStatus: boolean;
  canToggleInspectionStatus: boolean;
  canViewSchedule: boolean;
  canManageSchedule: boolean; // Add/remove own name
  canViewDepartments: boolean;
  canManageDepartments: boolean; // CRUD departments
  canViewLocations: boolean;
  canManageLocations: boolean;
  canViewUsers: boolean;
  canManageUsers: boolean;
  canViewAssetInspection: boolean; // View asset inspection summary
  canUploadAssets: boolean; // Upload asset files (Admin only)
  departmentRestricted: boolean; // Limited to own department only
}

/**
 * Get permissions for a specific role
 */
export function getPermissions(role: UserRole): Permission {
  switch (role) {
    case 'Viewer':
      return {
        canViewDashboard: true,
        canViewInspectionStatus: false,
        canToggleInspectionStatus: false,
        canViewSchedule: false,
        canManageSchedule: false,
        canViewDepartments: false,
        canManageDepartments: false,
        canViewLocations: false,
        canManageLocations: false,
        canViewUsers: false,
        canManageUsers: false,
        canViewAssetInspection: false,
        canUploadAssets: false,
        departmentRestricted: false
      };

    case 'Asset Officer':
      return {
        canViewDashboard: true,
        canViewInspectionStatus: true,
        canToggleInspectionStatus: true,
        canViewSchedule: false,
        canManageSchedule: false,
        canViewDepartments: false,
        canManageDepartments: false,
        canViewLocations: true,
        canManageLocations: true,
        canViewUsers: false,
        canManageUsers: false,
        canViewAssetInspection: false,
        canUploadAssets: false,
        departmentRestricted: true // Only their department (IT)
      };

    case 'Auditor':
      return {
        canViewDashboard: true,
        canViewInspectionStatus: false,
        canToggleInspectionStatus: false,
        canViewSchedule: true,
        canManageSchedule: true, // Can add/remove themselves
        canViewDepartments: false,
        canManageDepartments: false,
        canViewLocations: false,
        canManageLocations: false,
        canViewUsers: false,
        canManageUsers: false,
        canViewAssetInspection: false,
        canUploadAssets: false,
        departmentRestricted: false
      };

    case 'Admin':
      return {
        canViewDashboard: true,
        canViewInspectionStatus: true,
        canToggleInspectionStatus: true,
        canViewSchedule: true,
        canManageSchedule: true, // Admin can assign auditors and set dates
        canViewDepartments: true,
        canManageDepartments: true,
        canViewLocations: true,
        canManageLocations: true,
        canViewUsers: true,
        canManageUsers: true,
        canViewAssetInspection: true,
        canUploadAssets: true,
        departmentRestricted: false // Access all departments
      };

    default:
      // Default to minimal permissions
      return {
        canViewDashboard: true,
        canViewInspectionStatus: false,
        canToggleInspectionStatus: false,
        canViewSchedule: false,
        canManageSchedule: false,
        canViewDepartments: false,
        canManageDepartments: false,
        canViewLocations: false,
        canManageLocations: false,
        canViewUsers: false,
        canManageUsers: false,
        canViewAssetInspection: false,
        canUploadAssets: false,
        departmentRestricted: false
      };
  }
}

/**
 * Check if user has specific permission
 */
export function hasPermission(roles: string[], permissionKey: keyof Permission): boolean {
  if (!roles || roles.length === 0) return false;

  // Check each role and return true if any role grants the permission
  for (const role of roles) {
    const permissions = getPermissions(role as UserRole);
    if (permissions[permissionKey]) {
      return true;
    }
  }

  return false;
}

/**
 * Get highest priority role (Admin > Auditor > Asset Officer > Viewer)
 */
export function getPrimaryRole(roles: string[]): UserRole {
  if (roles.includes('Admin')) return 'Admin';
  if (roles.includes('Auditor')) return 'Auditor';
  if (roles.includes('Asset Officer')) return 'Asset Officer';
  if (roles.includes('Viewer')) return 'Viewer';
  return 'Viewer'; // Default
}

/**
 * Check if user is restricted to their department
 */
export function isDepartmentRestricted(roles: string[]): boolean {
  // Admins are never department-restricted
  if (roles?.includes('Admin')) return false;

  // If the user has Asset Officer role, they are department-restricted for
  // domain areas like Locations and Inspection Status, even when combined
  // with other roles (e.g., Auditor). Schedule view can explicitly ignore
  // this restriction as needed.
  if (roles?.includes('Asset Officer')) return true;

  // Otherwise, default to the primary role's restriction setting
  const primaryRole = getPrimaryRole(roles);
  const permissions = getPermissions(primaryRole);
  return permissions.departmentRestricted;
}

/**
 * Get all permissions for current user's roles
 */
export function getUserPermissions(roles: string[]): Permission {
  const primaryRole = getPrimaryRole(roles);
  return getPermissions(primaryRole);
}
