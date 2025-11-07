<template>
  <div class="app" :class="{ 'app-with-sidebar': isLoggedIn }">
    <!-- Sidebar navigation -->
    <aside v-if="isLoggedIn" class="sidebar">
      <div class="sidebar-header">
        <div class="logo-icon">🔍</div>
        <h1 class="logo-text">INSPECT</h1>
      </div>
      <nav class="sidebar-nav">
        <!-- Overview - Everyone can see -->
        <RouterLink v-if="auth.can('canViewDashboard')" to="/dashboard" class="nav-item">
          <span class="nav-icon">⊞</span>
          <span class="nav-label">Overview</span>
        </RouterLink>
        
        <!-- Schedule - Auditor and Admin only -->
        <RouterLink v-if="auth.can('canViewSchedule')" to="/schedule" class="nav-item">
          <span class="nav-icon">🏠</span>
          <span class="nav-label">Schedule</span>
        </RouterLink>
        
        <!-- Reports Group - Asset Officer and Admin only -->
        <div v-if="auth.can('canViewAssetInspection')" class="nav-group">
          <div class="nav-item nav-group-header" @click="toggleReports">
            <span class="nav-icon">📊</span>
            <span class="nav-label">Reports</span>
            <span class="nav-arrow" :class="{ expanded: reportsExpanded }">›</span>
          </div>
          <div v-if="reportsExpanded" class="nav-submenu">
            <RouterLink v-if="auth.can('canViewAssetInspection')" to="/asset-inspection" class="nav-subitem">
              <span class="nav-icon">📦</span>
              <span class="nav-label">Asset Inspection</span>
            </RouterLink>
          </div>
        </div>
        
        <!-- Departments Management Group -->
        <div v-if="auth.can('canViewAssetInspection') || auth.can('canViewLocations')" class="nav-group">
          <div class="nav-item nav-group-header" @click="toggleDepartments">
            <span class="nav-icon">🏢</span>
            <span class="nav-label">Departments</span>
            <span class="nav-arrow" :class="{ expanded: departmentsExpanded }">›</span>
          </div>
          <div v-if="departmentsExpanded" class="nav-submenu">
            <RouterLink v-if="auth.can('canViewLocations') || auth.can('canViewAssetInspection') || auth.can('canViewInspectionStatus')" to="/departments" class="nav-subitem">
              <span class="nav-icon">🗂️</span>
              <span class="nav-label">Summary</span>
            </RouterLink>
            <RouterLink v-if="auth.can('canViewLocations')" to="/locations" class="nav-subitem">
              <span class="nav-icon">📍</span>
              <span class="nav-label">Locations</span>
            </RouterLink>
          </div>
        </div>

        <!-- Settings Group - Admin only -->
        <div v-if="auth.can('canViewUsers')" class="nav-group">
          <div class="nav-item nav-group-header" @click="toggleSettings">
            <span class="nav-icon">⚙️</span>
            <span class="nav-label">Settings</span>
            <span class="nav-arrow" :class="{ expanded: settingsExpanded }">›</span>
          </div>
          <div v-if="settingsExpanded" class="nav-submenu">
            <RouterLink v-if="auth.can('canViewUsers')" to="/users" class="nav-subitem">
              <span class="nav-icon">👤</span>
              <span class="nav-label">Users</span>
            </RouterLink>
          </div>
        </div>
        
        <!-- Logout - quick access in menu -->
        <div class="nav-item" @click="handleLogout">
          <span class="nav-icon">🚪</span>
          <span class="nav-label">Logout</span>
        </div>
      </nav>
      <div class="sidebar-footer">
        <RouterLink to="/profile" class="user-profile-link">
          <div class="user-profile">
            <div class="user-avatar">{{ getInitials(userEmail) }}</div>
            <div class="user-info">
              <div class="user-name">{{ userName }}</div>
              <div class="user-role">View Profile</div>
            </div>
          </div>
        </RouterLink>
        <button @click="handleLogout" class="btn-logout-icon" title="Logout">↗</button>
      </div>
    </aside>

    <!-- Main content area -->
    <main :class="{ 'main': isLoggedIn, 'main-full': !isLoggedIn }">
      <RouterView />
    </main>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch, onMounted } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { useAuth } from './composables/useAuth';

const router = useRouter();
const route = useRoute();
const auth = useAuth();

const userEmail = ref(sessionStorage.getItem('userEmail') || '');
const userName = ref(sessionStorage.getItem('userName') || 'User');
const isLoggedIn = ref(!!sessionStorage.getItem('isLoggedIn'));

const reportsExpanded = ref(false);
const settingsExpanded = ref(false);
const departmentsExpanded = ref(false);

// Update isLoggedIn when route changes
watch(() => route.path, () => {
  isLoggedIn.value = !!sessionStorage.getItem('isLoggedIn');
  userEmail.value = sessionStorage.getItem('userEmail') || '';
  userName.value = sessionStorage.getItem('userName') || 'User';
});

// Check on mount
onMounted(() => {
  isLoggedIn.value = !!sessionStorage.getItem('isLoggedIn');
  userEmail.value = sessionStorage.getItem('userEmail') || '';
  userName.value = sessionStorage.getItem('userName') || 'User';
});

function getInitials(email: string): string {
  if (!email) return 'U';
  const name = userName.value;
  if (name && name !== 'User') {
    const parts = name.split(' ');
    if (parts.length >= 2) {
      return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase();
    }
    return name.substring(0, 1).toUpperCase();
  }
  return email.substring(0, 1).toUpperCase();
}

function toggleReports() {
  reportsExpanded.value = !reportsExpanded.value;
}

function toggleSettings() {
  settingsExpanded.value = !settingsExpanded.value;
}

function toggleDepartments() {
  departmentsExpanded.value = !departmentsExpanded.value;
}

function handleLogout() {
  // Clear all auth/session keys
  sessionStorage.removeItem('isLoggedIn');
  sessionStorage.removeItem('userId');
  sessionStorage.removeItem('staffId');
  sessionStorage.removeItem('userEmail');
  sessionStorage.removeItem('userName');
  sessionStorage.removeItem('userRoles');
  sessionStorage.removeItem('userDepartmentId');
  sessionStorage.removeItem('mustChangePassword');
  isLoggedIn.value = false;
  router.push('/login');
}
</script>

<style scoped>
.app {
  min-height: 100vh;
  background: #f9fafb;
}

.app-with-sidebar {
  display: flex;
}

/* Sidebar */
.sidebar {
  width: 240px;
  background: white;
  border-right: 1px solid #e5e7eb;
  display: flex;
  flex-direction: column;
  position: fixed;
  left: 0;
  top: 0;
  bottom: 0;
  z-index: 100;
}

.sidebar-header {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 1.5rem 1.25rem;
  border-bottom: 1px solid #e5e7eb;
}

.logo-icon {
  font-size: 1.5rem;
  color: var(--teal);
}

.logo-text {
  font-size: 1.25rem;
  font-weight: 700;
  color: #1f2937;
  margin: 0;
}

.sidebar-nav {
  flex: 1;
  padding: 1rem 0;
  overflow-y: auto;
}

.nav-item {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.75rem 1.25rem;
  color: #6b7280;
  text-decoration: none;
  cursor: pointer;
  transition: all 0.2s;
  position: relative;
}

.nav-item:hover {
  background: #f3f4f6;
  color: #1f2937;
}

.nav-item.router-link-active {
  background: #f0fdfa;
  color: var(--teal);
  font-weight: 600;
}

.nav-item.router-link-active::before {
  content: '';
  position: absolute;
  left: 0;
  top: 0;
  bottom: 0;
  width: 3px;
  background: var(--teal);
}

.nav-icon {
  font-size: 1.1rem;
  width: 20px;
  text-align: center;
}

.nav-label {
  flex: 1;
  font-size: 0.95rem;
}

.nav-group {
  position: relative;
}

.nav-group-header {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.75rem 1.25rem;
  color: #6b7280;
  cursor: pointer;
  transition: all 0.2s;
}

.nav-group-header:hover {
  background: #f3f4f6;
  color: #1f2937;
}

.nav-arrow {
  font-size: 1.2rem;
  color: #9ca3af;
  transition: transform 0.2s;
}

.nav-arrow.expanded {
  transform: rotate(90deg);
}

.nav-submenu {
  background: #f9fafb;
}

.nav-subitem {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.625rem 1.25rem 0.625rem 3rem;
  color: #6b7280;
  text-decoration: none;
  cursor: pointer;
  transition: all 0.2s;
  font-size: 0.9rem;
}

.nav-subitem:hover {
  background: #f3f4f6;
  color: #1f2937;
}

.nav-subitem.router-link-active {
  background: #f0fdfa;
  color: var(--teal);
  font-weight: 500;
}

.sidebar-footer {
  border-top: 1px solid #e5e7eb;
  padding: 1rem;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.user-profile-link {
  flex: 1;
  text-decoration: none;
  color: inherit;
}

.user-profile {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.5rem;
  border-radius: 0.5rem;
  transition: background-color 0.15s;
  cursor: pointer;
}

.user-profile-link:hover .user-profile {
  background: #f3f4f6;
}

.user-avatar {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  background: var(--teal);
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 600;
  flex-shrink: 0;
}

.user-info {
  flex: 1;
  min-width: 0;
}

.user-name {
  font-weight: 600;
  font-size: 0.9rem;
  color: #1f2937;
}

.user-role {
  font-size: 0.75rem;
  color: #14b8a6;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.btn-logout-icon {
  background: none;
  border: none;
  color: #6b7280;
  cursor: pointer;
  font-size: 1.2rem;
  padding: 0.25rem;
  transition: color 0.2s;
}

.btn-logout-icon:hover {
  color: var(--teal);
}

.main {
  flex: 1;
  margin-left: 240px;
  padding: 0;
  background: #f9fafb;
  min-height: 100vh;
}

.main-full {
  padding: 0;
  min-height: 100vh;
}

@media (max-width: 768px) {
  .sidebar {
    width: 200px;
  }
  
  .main {
    margin-left: 200px;
  }
}
</style>

<style>
/* Global styles - not scoped */
*, *::before, *::after {
  box-sizing: border-box;
}

input, select, textarea {
  box-sizing: border-box;
}
</style>
