<template>
  <div class="flex h-screen overflow-hidden">
    <!-- Sidebar navigation -->
    <aside v-if="isLoggedIn" class="w-60 bg-white border-r border-gray-200 flex flex-col">
      <!-- Logo Header -->
      <div class="flex items-center gap-3 p-5 border-b border-gray-200">
        <div class="text-2xl text-teal-600">🔍</div>
        <h1 class="text-xl font-bold text-gray-900">INSPECT</h1>
      </div>

      <!-- Navigation -->
      <nav class="flex-1 py-4 overflow-y-auto">
        <!-- Overview -->
        <RouterLink 
          v-if="auth.can('canViewDashboard')" 
          to="/dashboard" 
          class="flex items-center gap-3 px-5 py-3 text-gray-600 hover:bg-gray-50 hover:text-gray-900 transition-colors relative"
          active-class="bg-teal-50 text-teal-600 font-semibold before:absolute before:left-0 before:top-0 before:bottom-0 before:w-1 before:bg-teal-600"
        >
          <span class="text-lg w-5 text-center">⊞</span>
          <span>Overview</span>
        </RouterLink>
        
        <!-- Schedule -->
        <RouterLink 
          v-if="auth.can('canViewSchedule')" 
          to="/schedule" 
          class="flex items-center gap-3 px-5 py-3 text-gray-600 hover:bg-gray-50 hover:text-gray-900 transition-colors relative"
          active-class="bg-teal-50 text-teal-600 font-semibold before:absolute before:left-0 before:top-0 before:bottom-0 before:w-1 before:bg-teal-600"
        >
          <span class="text-lg w-5 text-center">🏠</span>
          <span>Schedule</span>
        </RouterLink>
        
        <!-- Reports Group -->
        <div v-if="auth.can('canViewAssetInspection')">
          <div 
            class="flex items-center gap-3 px-5 py-3 text-gray-600 hover:bg-gray-50 hover:text-gray-900 transition-colors cursor-pointer"
            @click="toggleReports"
          >
            <span class="text-lg w-5 text-center">📊</span>
            <span class="flex-1">Reports</span>
            <span class="text-xl text-gray-400 transition-transform" :class="{ 'rotate-90': reportsExpanded }">›</span>
          </div>
          <div v-if="reportsExpanded" class="bg-gray-50">
            <RouterLink 
              v-if="auth.can('canViewAssetInspection')" 
              to="/asset-inspection" 
              class="flex items-center gap-3 pl-12 pr-5 py-2 text-sm text-gray-600 hover:bg-gray-100 hover:text-gray-900 transition-colors relative"
              active-class="bg-teal-50 text-teal-600 font-medium"
            >
              <span class="text-base w-5 text-center">📦</span>
              <span>Asset Inspection</span>
            </RouterLink>
          </div>
        </div>
        
        <!-- Departments Group -->
        <div v-if="auth.can('canViewAssetInspection') || auth.can('canViewLocations')">
          <div 
            class="flex items-center gap-3 px-5 py-3 text-gray-600 hover:bg-gray-50 hover:text-gray-900 transition-colors cursor-pointer"
            @click="toggleDepartments"
          >
            <span class="text-lg w-5 text-center">🏢</span>
            <span class="flex-1">Departments</span>
            <span class="text-xl text-gray-400 transition-transform" :class="{ 'rotate-90': departmentsExpanded }">›</span>
          </div>
          <div v-if="departmentsExpanded" class="bg-gray-50">
            <RouterLink 
              v-if="auth.can('canViewLocations') || auth.can('canViewAssetInspection') || auth.can('canViewInspectionStatus')" 
              to="/departments" 
              class="flex items-center gap-3 pl-12 pr-5 py-2 text-sm text-gray-600 hover:bg-gray-100 hover:text-gray-900 transition-colors relative"
              active-class="bg-teal-50 text-teal-600 font-medium"
            >
              <span class="text-base w-5 text-center">🗂️</span>
              <span>Summary</span>
            </RouterLink>
            <RouterLink 
              v-if="auth.can('canViewLocations')" 
              to="/locations" 
              class="flex items-center gap-3 pl-12 pr-5 py-2 text-sm text-gray-600 hover:bg-gray-100 hover:text-gray-900 transition-colors relative"
              active-class="bg-teal-50 text-teal-600 font-medium"
            >
              <span class="text-base w-5 text-center">📍</span>
              <span>Locations</span>
            </RouterLink>
          </div>
        </div>

        <!-- Settings Group -->
        <div v-if="auth.can('canViewUsers')">
          <div 
            class="flex items-center gap-3 px-5 py-3 text-gray-600 hover:bg-gray-50 hover:text-gray-900 transition-colors cursor-pointer"
            @click="toggleSettings"
          >
            <span class="text-lg w-5 text-center">⚙️</span>
            <span class="flex-1">Settings</span>
            <span class="text-xl text-gray-400 transition-transform" :class="{ 'rotate-90': settingsExpanded }">›</span>
          </div>
          <div v-if="settingsExpanded" class="bg-gray-50">
            <RouterLink 
              v-if="auth.can('canViewUsers')" 
              to="/users" 
              class="flex items-center gap-3 pl-12 pr-5 py-2 text-sm text-gray-600 hover:bg-gray-100 hover:text-gray-900 transition-colors relative"
              active-class="bg-teal-50 text-teal-600 font-medium"
            >
              <span class="text-base w-5 text-center">👤</span>
              <span>Users</span>
            </RouterLink>
          </div>
        </div>
        
        <!-- Logout -->
        <div 
          class="flex items-center gap-3 px-5 py-3 text-gray-600 hover:bg-gray-50 hover:text-gray-900 transition-colors cursor-pointer"
          @click="handleLogout"
        >
          <span class="text-lg w-5 text-center">🚪</span>
          <span>Logout</span>
        </div>
      </nav>

      <!-- User Profile Footer -->
      <div class="border-t border-gray-200 p-4 flex items-center gap-2">
        <RouterLink to="/profile" class="flex items-center gap-3 flex-1 p-2 rounded-lg hover:bg-gray-50 transition-colors">
          <div class="w-9 h-9 rounded-full bg-teal-600 text-white flex items-center justify-center font-semibold flex-shrink-0">
            {{ getInitials(userEmail) }}
          </div>
          <div class="flex-1 min-w-0">
            <div class="font-semibold text-sm text-gray-900 truncate">{{ userName }}</div>
            <div class="text-xs text-teal-600 truncate">View Profile</div>
          </div>
        </RouterLink>
        <button 
          @click="handleLogout" 
          class="p-2 text-gray-600 hover:text-teal-600 transition-colors" 
          title="Logout"
        >
          ↗
        </button>
      </div>
    </aside>

    <!-- Main content area -->
    <main :class="isLoggedIn ? 'flex-1 bg-gray-50 overflow-auto' : 'flex-1'">
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

<style>
/* Global styles - not scoped */
*, *::before, *::after {
  box-sizing: border-box;
}

input, select, textarea {
  box-sizing: border-box;
}
</style>
