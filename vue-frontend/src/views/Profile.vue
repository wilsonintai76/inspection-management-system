<template>
  <div class="p-6 max-w-6xl mx-auto">
    <!-- Page Header -->
    <PageHeader 
      icon="👤" 
      title="Profile"
      subtitle="Manage your account information and preferences"
    />

    <!-- Loading State -->
    <LoadingSpinner v-if="loading" message="Loading profile..." />

    <!-- Profile Content -->
    <div v-else class="flex flex-col gap-6">
      <!-- Profile Information Card -->
      <div class="card bg-base-100 shadow-xl">
        <div class="card-body">
          <h2 class="card-title text-xl mb-6">Profile Information</h2>
          
          <div class="flex items-center gap-6 pb-6 mb-6 border-b border-gray-200">
            <div class="avatar placeholder">
              <div class="bg-gradient-to-br from-teal-500 to-teal-600 text-white rounded-full w-20">
                <span class="text-2xl font-bold">{{ getInitials(profileData.name) }}</span>
              </div>
            </div>
            <div>
              <h3 class="text-2xl font-semibold text-gray-900">{{ profileData.name }}</h3>
              <p class="text-gray-600">{{ formatRoles(profileData.roles) }}</p>
            </div>
          </div>

          <form @submit.prevent="updateProfile" class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div class="form-control">
                <label class="label">
                  <span class="label-text font-medium">Full Name *</span>
                </label>
                <input 
                  id="name"
                  v-model="profileData.name" 
                  type="text" 
                  class="input input-bordered w-full"
                  required
                />
              </div>
              
              <div class="form-control">
                <label class="label">
                  <span class="label-text font-medium">Institution Email</span>
                </label>
                <input 
                  id="email"
                  v-model="profileData.email" 
                  type="email" 
                  class="input input-bordered w-full"
                  disabled
                />
                <label class="label">
                  <span class="label-text-alt opacity-70">Email cannot be changed</span>
                </label>
              </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div class="form-control">
                <label class="label">
                  <span class="label-text font-medium">Personal Email</span>
                </label>
                <input 
                  id="personal-email"
                  v-model="profileData.personal_email" 
                  type="email" 
                  class="input input-bordered w-full"
                  placeholder="your.email@gmail.com"
                />
              </div>
              
              <div class="form-control">
                <label class="label">
                  <span class="label-text font-medium">Mobile Phone</span>
                </label>
                <input 
                  id="phone"
                  v-model="profileData.phone" 
                  type="text" 
                  class="input input-bordered w-full"
                  placeholder="e.g., 012-3456789"
                />
              </div>
            </div>

            <div class="form-control">
              <label class="label">
                <span class="label-text font-medium">Department</span>
              </label>
              <select id="department" v-model="profileData.department_id" class="select select-bordered w-full" disabled>
                <option value="">Select department...</option>
                <option v-for="dept in departments" :key="dept.id" :value="dept.id">
                  {{ dept.name }}
                </option>
              </select>
              <label class="label">
                <span class="label-text-alt opacity-70">Contact admin to change department</span>
              </label>
            </div>

            <div class="flex justify-end">
              <button type="submit" class="btn btn-primary" :disabled="saving">
                <span v-if="saving" class="loading loading-spinner"></span>
                {{ saving ? 'Saving...' : 'Save Changes' }}
              </button>
            </div>
          </form>
        </div>
      </div>

      <!-- Change Password Card -->
      <div class="card bg-base-100 shadow-xl">
        <div class="card-body">
          <h2 class="card-title text-xl mb-6">Change Password</h2>
          
          <form @submit.prevent="changePassword" class="space-y-4">
            <div class="form-control">
              <label class="label">
                <span class="label-text font-medium">Current Password *</span>
              </label>
              <input 
                id="current-password"
                v-model="passwordData.currentPassword" 
                type="password" 
                class="input input-bordered w-full"
                required
              />
            </div>

            <div class="form-control">
              <label class="label">
                <span class="label-text font-medium">New Password *</span>
              </label>
              <input 
                id="new-password"
                v-model="passwordData.newPassword" 
                type="password" 
                class="input input-bordered w-full"
                minlength="8"
                required
              />
              <label class="label">
                <span class="label-text-alt opacity-70">Minimum 8 characters</span>
              </label>
            </div>

            <div class="form-control">
              <label class="label">
                <span class="label-text font-medium">Confirm New Password *</span>
              </label>
              <input 
                id="confirm-password"
                v-model="passwordData.confirmPassword" 
                type="password" 
                class="input input-bordered w-full"
                required
              />
            </div>

            <div class="flex justify-end pt-2">
              <button type="submit" class="btn btn-primary" :disabled="changingPassword">
                <span v-if="changingPassword" class="loading loading-spinner"></span>
                {{ changingPassword ? 'Changing...' : 'Change Password' }}
              </button>
            </div>
          </form>
        </div>
      </div>

      <!-- Account Information Card -->
      <div class="card bg-base-100 shadow-xl">
        <div class="card-body">
          <h2 class="card-title text-xl mb-6">Account Information</h2>
          
          <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            <div>
              <label class="text-xs font-medium text-gray-500 uppercase tracking-wider block mb-2">Staff ID</label>
              <p class="text-base text-gray-900 font-semibold">{{ profileData.staff_id || '—' }}</p>
            </div>
            <div>
              <label class="text-xs font-medium text-gray-500 uppercase tracking-wider block mb-2">User ID</label>
              <p class="text-base text-gray-900">{{ profileData.id }}</p>
            </div>
            <div>
              <label class="text-xs font-medium text-gray-500 uppercase tracking-wider block mb-2">Status</label>
              <Badge variant="success" :label="profileData.status || 'Verified'" />
            </div>
            <div>
              <label class="text-xs font-medium text-gray-500 uppercase tracking-wider block mb-2">Account Created</label>
              <p class="text-base text-gray-900">{{ formatDate(profileData.created_at) }}</p>
            </div>
            <div>
              <label class="text-xs font-medium text-gray-500 uppercase tracking-wider block mb-2">Last Updated</label>
              <p class="text-base text-gray-900">{{ formatDate(profileData.updated_at) }}</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import api from '../api';
import { PageHeader, LoadingSpinner, Badge } from '../components';

interface Department {
  id: number;
  name: string;
}

interface UserRole {
  role: string;
}

interface ProfileData {
  id: string;
  staff_id?: string;
  name: string;
  email: string;
  personal_email?: string;
  phone?: string;
  department_id?: number;
  status?: string;
  created_at?: string;
  updated_at?: string;
  roles?: UserRole[] | string[];
}

interface PasswordData {
  currentPassword: string;
  newPassword: string;
  confirmPassword: string;
}

const profileData = ref<ProfileData>({
  id: '',
  name: '',
  email: '',
  phone: '',
  department_id: undefined,
  roles: []
});

const passwordData = ref<PasswordData>({
  currentPassword: '',
  newPassword: '',
  confirmPassword: ''
});

const departments = ref<Department[]>([]);
const loading = ref(false);
const saving = ref(false);
const changingPassword = ref(false);
const router = useRouter();

function logout() {
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
  router.push('/login');
}

function getInitials(name: string): string {
  if (!name) return '?';
  const parts = name.split(' ');
  if (parts.length >= 2) {
    return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase();
  }
  return name.substring(0, 2).toUpperCase();
}

function formatRoles(roles?: UserRole[] | string[]): string {
  if (!roles || roles.length === 0) return 'No roles assigned';
  const roleNames = roles.map(r => typeof r === 'string' ? r : r.role);
  return roleNames.join(', ');
}

function formatDate(dateStr?: string): string {
  if (!dateStr) return '—';
  const date = new Date(dateStr);
  return date.toLocaleDateString('en-US', { 
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  });
}

async function fetchProfile() {
  loading.value = true;
  try {
    const userId = sessionStorage.getItem('userId');
    if (!userId) {
      alert('Not logged in');
      return;
    }

    const response = await api.get(`/users.php?id=${userId}`);
    profileData.value = response.data;
  } catch (err) {
    console.error('Error fetching profile:', err);
    alert('Failed to load profile.');
  } finally {
    loading.value = false;
  }
}

async function fetchDepartments() {
  try {
    const response = await api.get('/departments.php');
    departments.value = response.data;
  } catch (err) {
    console.error('Error fetching departments:', err);
  }
}

async function updateProfile() {
  saving.value = true;
  try {
    await api.put(`/users.php?id=${profileData.value.id}`, {
      name: profileData.value.name,
      email: profileData.value.email,
      personal_email: profileData.value.personal_email || null,
      phone: profileData.value.phone || null,
      department_id: profileData.value.department_id,
      status: profileData.value.status,
      roles: profileData.value.roles
    });

    // Update session storage
    sessionStorage.setItem('userName', profileData.value.name);

    alert('Profile updated successfully!');
    await fetchProfile();
  } catch (err) {
    console.error('Error updating profile:', err);
    alert('Failed to update profile. Please try again.');
  } finally {
    saving.value = false;
  }
}

async function changePassword() {
  if (passwordData.value.newPassword !== passwordData.value.confirmPassword) {
    alert('New passwords do not match!');
    return;
  }

  if (passwordData.value.newPassword.length < 8) {
    alert('Password must be at least 8 characters long.');
    return;
  }

  changingPassword.value = true;
  try {
    // Call API to change password
    await api.put(`/users.php?id=${profileData.value.id}`, {
      password: passwordData.value.newPassword
    });
    
    // Clear must change password flag in session
    sessionStorage.removeItem('mustChangePassword');
    
    alert('Password changed successfully!');
    
    // Clear password fields
    passwordData.value = {
      currentPassword: '',
      newPassword: '',
      confirmPassword: ''
    };
  } catch (err) {
    console.error('Error changing password:', err);
    alert('Failed to change password. Please try again.');
  } finally {
    changingPassword.value = false;
  }
}

onMounted(async () => {
  await Promise.all([fetchProfile(), fetchDepartments()]);
});
</script>
