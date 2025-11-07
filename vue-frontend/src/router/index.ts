import { createRouter, createWebHistory } from 'vue-router';
import Login from '../views/Login.vue';
import Register from '../views/Register.vue';
import VerifyEmail from '../views/VerifyEmail.vue';
import ForgotPassword from '../views/ForgotPassword.vue';
import ResetPassword from '../views/ResetPassword.vue';
import Dashboard from '../views/Dashboard.vue';
import Schedule from '../views/Schedule.vue';
import Locations from '../views/Locations.vue';
import InspectionStatus from '../views/InspectionStatus.vue';
import Departments from '../views/Departments.vue';
import Users from '../views/Users.vue';
import Profile from '../views/Profile.vue';
import { hasPermission } from '../lib/permissions';
const Inspections = { template: '<div style="padding: 2rem;">Inspections</div>' };

const router = createRouter({
  history: createWebHistory(),
  routes: [
    { path: '/', redirect: '/login' },
    { path: '/login', component: Login, meta: { public: true } },
    { path: '/register', component: Register, meta: { public: true } },
    { path: '/verify-email', component: VerifyEmail, meta: { public: true } },
  { path: '/forgot-password', component: ForgotPassword, meta: { public: true } },
  { path: '/reset-password', component: ResetPassword, meta: { public: true } },
    {
      path: '/logout',
      redirect: '/login',
      meta: { public: true },
      // Clear auth state and then redirect to login
      beforeEnter: () => {
        try {
          sessionStorage.removeItem('isLoggedIn');
          sessionStorage.removeItem('userId');
          sessionStorage.removeItem('staffId');
          sessionStorage.removeItem('userEmail');
          sessionStorage.removeItem('userName');
          sessionStorage.removeItem('userRoles');
          sessionStorage.removeItem('mustChangePassword');
          localStorage.removeItem('rememberStaffId');
        } catch {}
      }
    },
    { path: '/dashboard', component: Dashboard, meta: { requiresAuth: true, permission: 'canViewDashboard' } },
    { path: '/schedule', component: Schedule, meta: { requiresAuth: true, permission: 'canViewSchedule' } },
    { path: '/departments', component: Departments, meta: { requiresAuth: true, permission: 'canViewDepartments' } },
    { path: '/locations', component: Locations, meta: { requiresAuth: true, permission: 'canViewLocations' } },
    { path: '/inspections', component: Inspections, meta: { requiresAuth: true } },
    { path: '/users', component: Users, meta: { requiresAuth: true, permission: 'canViewUsers' } },
    { path: '/profile', component: Profile, meta: { requiresAuth: true } },
    { path: '/report/inspection-status', component: InspectionStatus, meta: { requiresAuth: true, permission: 'canViewInspectionStatus' } },
  ],
});

// Auth guard with permission checking
router.beforeEach((to, from, next) => {
  const isLoggedIn = !!sessionStorage.getItem('isLoggedIn');
  
  if (to.meta.requiresAuth && !isLoggedIn) {
    next('/login');
  } else if (to.path === '/login' && isLoggedIn) {
    next('/dashboard');
  } else if (to.meta.permission) {
    // Check if user has required permission
    const rolesJson = sessionStorage.getItem('userRoles');
    const roles = rolesJson ? JSON.parse(rolesJson) : [];
    const permissionKey = to.meta.permission as any;
    
    if (!hasPermission(roles, permissionKey)) {
      // User doesn't have permission, silently redirect to dashboard
      next('/dashboard');
    } else {
      next();
    }
  } else {
    next();
  }
});

export default router;
