import { createRouter, createWebHistory } from 'vue-router';
import Login from '../views/Login.vue';
import Dashboard from '../views/Dashboard.vue';
import Schedule from '../views/Schedule.vue';
import Locations from '../views/Locations.vue';
import InspectionStatus from '../views/InspectionStatus.vue';
import Departments from '../views/Departments.vue';
import Users from '../views/Users.vue';
import Profile from '../views/Profile.vue';
const Inspections = { template: '<div style="padding: 2rem;">Inspections</div>' };

const router = createRouter({
  history: createWebHistory(),
  routes: [
    { path: '/', redirect: '/login' },
    { path: '/login', component: Login, meta: { public: true } },
    { path: '/dashboard', component: Dashboard, meta: { requiresAuth: true } },
    { path: '/schedule', component: Schedule, meta: { requiresAuth: true } },
    { path: '/departments', component: Departments, meta: { requiresAuth: true } },
    { path: '/locations', component: Locations, meta: { requiresAuth: true } },
    { path: '/inspections', component: Inspections, meta: { requiresAuth: true } },
    { path: '/users', component: Users, meta: { requiresAuth: true } },
    { path: '/profile', component: Profile, meta: { requiresAuth: true } },
    { path: '/report/inspection-status', component: InspectionStatus, meta: { requiresAuth: true } },
  ],
});

// Simple auth guard
router.beforeEach((to, from, next) => {
  const isLoggedIn = !!sessionStorage.getItem('isLoggedIn');
  
  if (to.meta.requiresAuth && !isLoggedIn) {
    next('/login');
  } else if (to.path === '/login' && isLoggedIn) {
    next('/dashboard');
  } else {
    next();
  }
});

export default router;
