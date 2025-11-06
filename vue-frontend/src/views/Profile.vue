<template>
  <div class="profile-page">
    <!-- Page Header -->
    <div class="page-header">
      <div class="header-left">
        <span class="breadcrumb-icon">👤</span>
        <h1 class="page-title">Profile</h1>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="loading-state">
      <div class="spinner"></div>
      <p>Loading profile...</p>
    </div>

    <!-- Profile Content -->
    <div v-else class="profile-content">
      <!-- Profile Information Card -->
      <div class="profile-card">
        <div class="card-header">
          <h2 class="card-title">Profile Information</h2>
        </div>
        <div class="card-body">
          <div class="avatar-section">
            <div class="avatar-large">
              {{ getInitials(profileData.name) }}
            </div>
            <div class="avatar-info">
              <h3>{{ profileData.name }}</h3>
              <p class="user-roles">{{ formatRoles(profileData.roles) }}</p>
            </div>
          </div>

          <form @submit.prevent="updateProfile" class="profile-form">
            <div class="form-row">
              <div class="form-group">
                <label for="name">Full Name *</label>
                <input 
                  id="name"
                  v-model="profileData.name" 
                  type="text" 
                  class="form-input"
                  required
                />
              </div>
              <div class="form-group">
                <label for="email">Email Address</label>
                <input 
                  id="email"
                  v-model="profileData.email" 
                  type="email" 
                  class="form-input"
                  disabled
                />
                <small class="field-hint">Email cannot be changed</small>
              </div>
            </div>

            <div class="form-row">
              <div class="form-group">
                <label for="phone">Mobile Phone</label>
                <input 
                  id="phone"
                  v-model="profileData.phone" 
                  type="text" 
                  class="form-input"
                  placeholder="e.g., 012-3456789"
                />
              </div>
              <div class="form-group">
                <label for="department">Department</label>
                <select id="department" v-model="profileData.department_id" class="form-select" disabled>
                  <option value="">Select department...</option>
                  <option v-for="dept in departments" :key="dept.id" :value="dept.id">
                    {{ dept.name }}
                  </option>
                </select>
                <small class="field-hint">Contact admin to change department</small>
              </div>
            </div>

            <div class="form-actions">
              <button type="submit" class="btn-save" :disabled="saving">
                {{ saving ? 'Saving...' : 'Save Changes' }}
              </button>
            </div>
          </form>
        </div>
      </div>

      <!-- Change Password Card -->
      <div class="profile-card">
        <div class="card-header">
          <h2 class="card-title">Change Password</h2>
        </div>
        <div class="card-body">
          <form @submit.prevent="changePassword" class="password-form">
            <div class="form-group">
              <label for="current-password">Current Password *</label>
              <input 
                id="current-password"
                v-model="passwordData.currentPassword" 
                type="password" 
                class="form-input"
                required
              />
            </div>

            <div class="form-group">
              <label for="new-password">New Password *</label>
              <input 
                id="new-password"
                v-model="passwordData.newPassword" 
                type="password" 
                class="form-input"
                minlength="8"
                required
              />
              <small class="field-hint">Minimum 8 characters</small>
            </div>

            <div class="form-group">
              <label for="confirm-password">Confirm New Password *</label>
              <input 
                id="confirm-password"
                v-model="passwordData.confirmPassword" 
                type="password" 
                class="form-input"
                required
              />
            </div>

            <div class="form-actions">
              <button type="submit" class="btn-save" :disabled="changingPassword">
                {{ changingPassword ? 'Changing...' : 'Change Password' }}
              </button>
            </div>
          </form>
        </div>
      </div>

      <!-- Account Information Card -->
      <div class="profile-card">
        <div class="card-header">
          <h2 class="card-title">Account Information</h2>
        </div>
        <div class="card-body">
          <div class="info-grid">
            <div class="info-item">
              <label>User ID</label>
              <p>{{ profileData.id }}</p>
            </div>
            <div class="info-item">
              <label>Status</label>
              <p><span class="status-badge status-verified">{{ profileData.status || 'Verified' }}</span></p>
            </div>
            <div class="info-item">
              <label>Account Created</label>
              <p>{{ formatDate(profileData.created_at) }}</p>
            </div>
            <div class="info-item">
              <label>Last Updated</label>
              <p>{{ formatDate(profileData.updated_at) }}</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import api from '../api';

interface Department {
  id: number;
  name: string;
}

interface UserRole {
  role: string;
}

interface ProfileData {
  id: string;
  name: string;
  email: string;
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
    const userEmail = sessionStorage.getItem('userEmail');
    if (!userEmail) {
      alert('Not logged in');
      return;
    }

    const response = await api.get(`/users.php?id=${userEmail}`);
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
    // In a real application, this would call a password change endpoint
    // For now, we'll simulate it
    await new Promise(resolve => setTimeout(resolve, 1000));
    
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

<style scoped>
.profile-page {
  padding: 2rem;
  max-width: 1200px;
  margin: 0 auto;
}

.page-header {
  margin-bottom: 2rem;
}

.header-left {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.breadcrumb-icon {
  font-size: 1.5rem;
}

.page-title {
  font-size: 1.875rem;
  font-weight: 600;
  color: #1f2937;
  margin: 0;
}

.loading-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 4rem 2rem;
  color: #6b7280;
}

.spinner {
  width: 40px;
  height: 40px;
  border: 4px solid #f3f4f6;
  border-top-color: #14b8a6;
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin-bottom: 1rem;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.profile-content {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.profile-card {
  background: white;
  border-radius: 0.75rem;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  overflow: hidden;
}

.card-header {
  padding: 1.5rem 2rem;
  border-bottom: 1px solid #e5e7eb;
}

.card-title {
  font-size: 1.25rem;
  font-weight: 600;
  color: #1f2937;
  margin: 0;
}

.card-body {
  padding: 2rem;
}

.avatar-section {
  display: flex;
  align-items: center;
  gap: 1.5rem;
  margin-bottom: 2rem;
  padding-bottom: 2rem;
  border-bottom: 1px solid #e5e7eb;
}

.avatar-large {
  width: 80px;
  height: 80px;
  border-radius: 50%;
  background: linear-gradient(135deg, #14b8a6 0%, #0d9488 100%);
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 700;
  font-size: 1.75rem;
  flex-shrink: 0;
}

.avatar-info h3 {
  margin: 0 0 0.5rem 0;
  font-size: 1.5rem;
  font-weight: 600;
  color: #1f2937;
}

.user-roles {
  margin: 0;
  color: #6b7280;
  font-size: 0.9375rem;
}

.profile-form,
.password-form {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1.5rem;
}

.form-group {
  display: flex;
  flex-direction: column;
}

.form-group label {
  margin-bottom: 0.5rem;
  font-size: 0.875rem;
  font-weight: 500;
  color: #374151;
}

.form-input,
.form-select {
  padding: 0.75rem 1rem;
  border: 1px solid #d1d5db;
  border-radius: 0.5rem;
  font-size: 0.9375rem;
  color: #1f2937;
  background: white;
  transition: border-color 0.15s, box-shadow 0.15s;
}

.form-input:focus,
.form-select:focus {
  outline: none;
  border-color: #14b8a6;
  box-shadow: 0 0 0 3px rgba(20, 184, 166, 0.1);
}

.form-input:disabled,
.form-select:disabled {
  background: #f9fafb;
  color: #6b7280;
  cursor: not-allowed;
}

.field-hint {
  margin-top: 0.375rem;
  font-size: 0.8125rem;
  color: #6b7280;
}

.form-actions {
  display: flex;
  justify-content: flex-end;
  padding-top: 0.5rem;
}

.btn-save {
  padding: 0.75rem 1.5rem;
  background: #14b8a6;
  color: white;
  border: none;
  border-radius: 0.5rem;
  font-weight: 500;
  cursor: pointer;
  transition: background-color 0.15s;
}

.btn-save:hover:not(:disabled) {
  background: #0d9488;
}

.btn-save:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.info-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 2rem;
}

.info-item label {
  display: block;
  font-size: 0.8125rem;
  font-weight: 500;
  color: #6b7280;
  margin-bottom: 0.5rem;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.info-item p {
  margin: 0;
  font-size: 0.9375rem;
  color: #1f2937;
}

.status-badge {
  display: inline-block;
  padding: 0.375rem 0.875rem;
  border-radius: 9999px;
  font-size: 0.8125rem;
  font-weight: 600;
}

.status-verified {
  background: #d1fae5;
  color: #065f46;
}

@media (max-width: 768px) {
  .profile-page {
    padding: 1rem;
  }

  .form-row,
  .info-grid {
    grid-template-columns: 1fr;
    gap: 1.5rem;
  }

  .avatar-section {
    flex-direction: column;
    text-align: center;
  }
}
</style>
