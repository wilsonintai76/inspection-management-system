<template>
  <div class="users-page">
    <!-- Page Header -->
    <div class="page-header">
      <div class="header-left">
        <span class="breadcrumb-icon">👤</span>
        <h1 class="page-title">Users</h1>
      </div>
    </div>

    <!-- Users Card -->
    <div class="users-card">
      <div class="card-header">
        <div class="header-text">
          <h2 class="card-title">User Management</h2>
          <p class="card-subtitle">Manage application users, roles, and status.</p>
        </div>
        <button class="btn-add" @click="openAddDialog">
          <span class="btn-icon">⊕</span>
          Add User
        </button>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="loading-state">
        <div class="spinner"></div>
        <p>Loading users...</p>
      </div>

      <!-- Error State -->
      <div v-else-if="error" class="error-state">
        <p class="error-message">{{ error }}</p>
        <button @click="fetchData" class="btn-retry">Retry</button>
      </div>

      <!-- Users Table -->
      <div v-else class="table-container">
        <table class="users-table">
          <thead>
            <tr>
              <th>User</th>
              <th>Contact</th>
              <th>Department</th>
              <th>Assigned Role</th>
              <th>Status</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="users.length === 0">
              <td colspan="6" class="no-data">
                <span class="no-data-icon">📭</span>
                <p>No users found</p>
              </td>
            </tr>
            <tr v-for="user in users" :key="user.id">
              <td>
                <div class="user-name">{{ user.name }}</div>
              </td>
              <td>
                <div class="contact-info">
                  <div class="email">{{ user.email }}</div>
                  <div class="phone">{{ user.phone || 'No phone' }}</div>
                </div>
              </td>
              <td>
                <div class="department-name">{{ getDepartmentName(user.department_id) }}</div>
              </td>
              <td>
                <div class="roles">{{ formatRoles(user.roles) }}</div>
              </td>
              <td>
                <button 
                  @click="toggleStatus(user)" 
                  class="status-badge"
                  :class="user.status === 'Verified' ? 'status-verified' : 'status-unverified'"
                  :title="`Click to ${user.status === 'Verified' ? 'unverify' : 'verify'}`"
                >
                  {{ user.status || 'Unverified' }}
                </button>
              </td>
              <td>
                <div class="action-buttons">
                  <button 
                    @click="openEditDialog(user)" 
                    class="btn-edit"
                    title="Edit"
                  >
                    ✏️
                  </button>
                  <button 
                    @click="openTransferDialog(user)" 
                    class="btn-transfer"
                    title="Transfer Staff ID"
                  >
                    🔄
                  </button>
                  <button 
                    @click="confirmDelete(user)" 
                    class="btn-delete"
                    title="Delete"
                  >
                    🗑️
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Add/Edit User Dialog -->
    <div v-if="showDialog" class="dialog-overlay" @click="closeDialog">
      <div class="dialog dialog-large" @click.stop>
        <div class="dialog-header">
          <h3>{{ editingUser ? 'Edit User' : 'Add New User' }}</h3>
          <button @click="closeDialog" class="btn-close">✕</button>
        </div>
        <div class="dialog-body">
          <div class="form-group">
            <label for="user-staff-id">Staff ID *</label>
            <input 
              id="user-staff-id"
              v-model="formData.staff_id" 
              type="text" 
              placeholder="4-digit staff ID" 
              class="form-input"
              maxlength="4"
              pattern="\d{4}"
              :disabled="!!editingUser"
            />
            <small class="field-hint">Must be 4 digits. Cannot be changed after creation.</small>
          </div>
          <div class="form-group">
            <label for="user-name">Full Name *</label>
            <input 
              id="user-name"
              v-model="formData.name" 
              type="text" 
              placeholder="John Doe" 
              class="form-input"
            />
          </div>
          <div class="form-group">
            <label for="user-email">Institution Email Address *</label>
            <input 
              id="user-email"
              v-model="formData.email" 
              type="email" 
              placeholder="user@institution.edu" 
              class="form-input"
            />
          </div>
          <div class="form-group">
            <label for="user-personal-email">Personal Email Address</label>
            <input 
              id="user-personal-email"
              v-model="formData.personal_email" 
              type="email" 
              placeholder="user@gmail.com" 
              class="form-input"
            />
          </div>
          <div class="form-group">
            <label for="user-phone">Mobile Phone No.</label>
            <input 
              id="user-phone"
              v-model="formData.phone" 
              type="text" 
              placeholder="(555) 123-4567" 
              class="form-input"
            />
          </div>
          <div class="form-group">
            <label>Roles *</label>
            <div class="checkbox-group">
              <label class="checkbox-label">
                <input type="checkbox" value="Admin" v-model="formData.roles" />
                <span>Admin</span>
              </label>
              <label class="checkbox-label">
                <input type="checkbox" value="Asset Officer" v-model="formData.roles" />
                <span>Asset Officer</span>
              </label>
              <label class="checkbox-label">
                <input type="checkbox" value="Auditor" v-model="formData.roles" />
                <span>Auditor</span>
              </label>
              <label class="checkbox-label">
                <input type="checkbox" value="Viewer" v-model="formData.roles" />
                <span>Viewer</span>
              </label>
            </div>
          </div>
          <div class="form-group">
            <label for="user-department">Department *</label>
            <select id="user-department" v-model="formData.department_id" class="form-select">
              <option value="">Select a department</option>
              <option v-for="dept in departments" :key="dept.id" :value="dept.id">
                {{ dept.name }}
              </option>
            </select>
          </div>
          <div v-if="!editingUser" class="info-box">
            <p><strong>Note:</strong> A temporary password will be automatically generated and sent to the user's email. The user will be required to change it on first login.</p>
          </div>
        </div>
        <div class="dialog-footer">
          <button @click="closeDialog" class="btn-cancel">Cancel</button>
          <button @click="saveUser" class="btn-save" :disabled="!isFormValid">
            {{ editingUser ? 'Update User' : 'Create User' }}
          </button>
        </div>
      </div>
    </div>

    <!-- Delete Confirmation Dialog -->
    <div v-if="showDeleteDialog" class="dialog-overlay" @click="closeDeleteDialog">
      <div class="dialog dialog-small" @click.stop>
        <div class="dialog-header">
          <h3>Delete User</h3>
          <button @click="closeDeleteDialog" class="btn-close">✕</button>
        </div>
        <div class="dialog-body">
          <p>Are you sure you want to delete <strong>{{ userToDelete?.name }}</strong>?</p>
          <p class="warning-text">This action cannot be undone.</p>
        </div>
        <div class="dialog-footer">
          <button @click="closeDeleteDialog" class="btn-cancel">Cancel</button>
          <button @click="deleteUser" class="btn-delete-confirm">Delete</button>
        </div>
      </div>
    </div>

    <!-- Transfer Staff ID Dialog -->
    <div v-if="showTransferDialog" class="dialog-overlay" @click="closeTransferDialog">
      <div class="dialog dialog-small" @click.stop>
        <div class="dialog-header">
          <h3>Transfer Staff ID</h3>
          <button @click="closeTransferDialog" class="btn-close">✕</button>
        </div>
        <div class="dialog-body">
          <p>Transfer staff ID for <strong>{{ userToTransfer?.name }}</strong></p>
          <div class="transfer-info">
            <div class="current-id">
              <label>Current Staff ID:</label>
              <span class="id-badge">{{ userToTransfer?.staff_id }}</span>
            </div>
          </div>
          <div class="form-group">
            <label for="new-staff-id">New Staff ID *</label>
            <input 
              id="new-staff-id"
              v-model="newStaffId" 
              type="text" 
              placeholder="Enter new 4-digit staff ID" 
              class="form-input"
              maxlength="4"
              pattern="\d{4}"
            />
            <small class="field-hint">Must be 4 digits and not already in use.</small>
          </div>
          <p class="warning-text">⚠️ This will update the staff ID across all database tables. The user will need to login with the new staff ID.</p>
        </div>
        <div class="dialog-footer">
          <button @click="closeTransferDialog" class="btn-cancel">Cancel</button>
          <button @click="transferStaffId" class="btn-transfer-confirm" :disabled="!isNewStaffIdValid">Transfer</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import api from '../api';

interface Department {
  id: number;
  name: string;
}

interface UserRole {
  role: string;
}

interface User {
  id: string;
  staff_id: string;
  name: string;
  email: string;
  personal_email?: string;
  phone?: string;
  department_id?: number;
  roles?: UserRole[] | string[];
  status?: string;
  email_verified?: number;
}

interface FormData {
  staff_id: string;
  name: string;
  email: string;
  personal_email: string;
  phone: string;
  roles: string[];
  department_id: number | string;
}

const users = ref<User[]>([]);
const departments = ref<Department[]>([]);
const loading = ref(false);
const error = ref('');

const showDialog = ref(false);
const showDeleteDialog = ref(false);
const showTransferDialog = ref(false);
const editingUser = ref<User | null>(null);
const userToDelete = ref<User | null>(null);
const userToTransfer = ref<User | null>(null);
const newStaffId = ref('');

const formData = ref<FormData>({
  staff_id: '',
  name: '',
  email: '',
  personal_email: '',
  phone: '',
  roles: [],
  department_id: ''
});

const isNewStaffIdValid = computed(() => {
  return /^\d{4}$/.test(newStaffId.value) && newStaffId.value !== userToTransfer.value?.staff_id;
});

const isFormValid = computed(() => {
  return (
    formData.value.staff_id.trim() !== '' &&
    /^\d{4}$/.test(formData.value.staff_id) &&
    formData.value.name.trim() !== '' &&
    formData.value.email.trim() !== '' &&
    formData.value.roles.length > 0 &&
    formData.value.department_id !== ''
  );
});

function getDepartmentName(departmentId?: number): string {
  if (!departmentId) return '—';
  const dept = departments.value.find(d => d.id === departmentId);
  return dept ? dept.name : 'Unknown';
}

function formatRoles(roles?: UserRole[] | string[]): string {
  if (!roles || roles.length === 0) return '—';
  
  // Handle both UserRole[] and string[] formats
  const roleNames = roles.map(r => typeof r === 'string' ? r : r.role);
  return roleNames.join(', ');
}

function openAddDialog() {
  editingUser.value = null;
  formData.value = {
    staff_id: '',
    name: '',
    email: '',
    personal_email: '',
    phone: '',
    roles: [],
    department_id: ''
  };
  showDialog.value = true;
}

function openEditDialog(user: User) {
  editingUser.value = user;
  
  // Extract roles
  const userRoles = user.roles 
    ? user.roles.map(r => typeof r === 'string' ? r : r.role)
    : [];
  
  formData.value = {
    staff_id: user.staff_id || '',
    name: user.name,
    email: user.email,
    personal_email: user.personal_email || '',
    phone: user.phone || '',
    roles: userRoles,
    department_id: user.department_id || ''
  };
  showDialog.value = true;
}

function closeDialog() {
  showDialog.value = false;
  editingUser.value = null;
}

function confirmDelete(user: User) {
  userToDelete.value = user;
  showDeleteDialog.value = true;
}

function closeDeleteDialog() {
  showDeleteDialog.value = false;
  userToDelete.value = null;
}

function openTransferDialog(user: User) {
  userToTransfer.value = user;
  newStaffId.value = '';
  showTransferDialog.value = true;
}

function closeTransferDialog() {
  showTransferDialog.value = false;
  userToTransfer.value = null;
  newStaffId.value = '';
}

async function fetchUsers() {
  try {
    const response = await api.get('/users.php');
    users.value = response.data;
  } catch (err) {
    console.error('Error fetching users:', err);
    throw err;
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

async function fetchData() {
  loading.value = true;
  error.value = '';
  try {
    await Promise.all([fetchUsers(), fetchDepartments()]);
  } catch (err) {
    error.value = 'Failed to load users. Please try again.';
    console.error('Error loading data:', err);
  } finally {
    loading.value = false;
  }
}

async function saveUser() {
  if (!isFormValid.value) return;

  try {
    const data = {
      staff_id: formData.value.staff_id.trim(),
      name: formData.value.name.trim(),
      email: formData.value.email.trim(),
      personal_email: formData.value.personal_email.trim() || null,
      phone: formData.value.phone.trim() || null,
      department_id: Number(formData.value.department_id),
      roles: formData.value.roles
    };

    if (editingUser.value) {
      // Update existing user
      await api.put(`/users.php?id=${editingUser.value.id}`, data);
      alert('User updated successfully!');
    } else {
      // Create new user
      const response = await api.post('/users.php', data);
      const { generated_password, staff_id, email_sent } = response.data;
      
      // Show generated password to admin
      const emailStatus = email_sent ? 'Password has been sent to the user\'s email.' : 'Failed to send email.';
      alert(`User created successfully!\n\nStaff ID: ${staff_id}\nTemporary Password: ${generated_password}\n\n${emailStatus}\n\nPlease save this password and share it with the user securely.`);
    }

    closeDialog();
    await fetchUsers();
  } catch (err: any) {
    console.error('Error saving user:', err);
    const errorMsg = err.response?.data?.error || 'Failed to save user. Please try again.';
    alert(errorMsg);
  }
}

async function deleteUser() {
  if (!userToDelete.value) return;

  try {
    await api.delete(`/users.php?id=${userToDelete.value.id}`);
    closeDeleteDialog();
    await fetchUsers();
  } catch (err) {
    console.error('Error deleting user:', err);
    alert('Failed to delete user. Please try again.');
  }
}

async function transferStaffId() {
  if (!userToTransfer.value || !isNewStaffIdValid.value) return;

  try {
    const userRoles = JSON.parse(sessionStorage.getItem('userRoles') || '[]');
    
    const response = await api.post('/transfer-staff-id.php', {
      old_staff_id: userToTransfer.value.staff_id,
      new_staff_id: newStaffId.value.trim()
    }, {
      headers: {
        'X-User-Roles': JSON.stringify(userRoles)
      }
    });

    if (response.data && response.data.success) {
      alert(`Staff ID transferred successfully!\n\nOld: ${userToTransfer.value.staff_id}\nNew: ${newStaffId.value}\n\nThe user must login with the new staff ID.`);
      closeTransferDialog();
      await fetchUsers();
    }
  } catch (err: any) {
    console.error('Error transferring staff ID:', err);
    const errorMsg = err.response?.data?.error || 'Failed to transfer staff ID. Please try again.';
    alert(`Error: ${errorMsg}`);
  }
}

async function toggleStatus(user: User) {
  try {
    // Get current user roles from session storage for authentication
    const userRoles = JSON.parse(sessionStorage.getItem('userRoles') || '[]');
    
    // Check if user is admin
    if (!userRoles.includes('Admin')) {
      alert('Only administrators can verify/unverify users.');
      return;
    }
    
    // Toggle verified status
    const newVerified = user.status !== 'Verified';
    
    console.log('Toggling status for user:', user.staff_id, 'to', newVerified ? 'Verified' : 'Unverified');
    console.log('User roles:', userRoles);
    
    // Call verify-user API
    const response = await api.post('/verify-user.php', {
      staff_id: user.staff_id,
      verified: newVerified
    }, {
      headers: {
        'X-User-Roles': JSON.stringify(userRoles)
      }
    });

    console.log('API response:', response.data);

    // Update the local user object with the response
    if (response.data && response.data.updated) {
      const updatedUser = response.data.user;
      const index = users.value.findIndex(u => u.id === user.id);
      if (index !== -1) {
        users.value[index].status = updatedUser.status;
        users.value[index].email_verified = updatedUser.email_verified;
      }
      alert(`User ${user.staff_id} has been ${newVerified ? 'verified' : 'unverified'} successfully!`);
    }
  } catch (err: any) {
    console.error('Error toggling status:', err);
    console.error('Error response:', err.response);
    const errorMsg = err.response?.data?.error || 'Failed to update status. Please try again.';
    alert(`Error: ${errorMsg}`);
  }
}

onMounted(async () => {
  await fetchData();
});
</script>

<style scoped>
.users-page {
  padding: 2rem;
  max-width: 1600px;
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
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

.users-card {
  background: white;
  border-radius: 0.75rem;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  overflow: hidden;
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  padding: 1.5rem 2rem;
  border-bottom: 1px solid #e5e7eb;
}

.header-text {
  flex: 1;
}

.card-title {
  font-size: 1.25rem;
  font-weight: 600;
  color: #1f2937;
  margin: 0 0 0.25rem 0;
}

.card-subtitle {
  font-size: 0.875rem;
  color: #6b7280;
  margin: 0;
}

.btn-add {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.625rem 1.25rem;
  background: #10b981;
  color: white;
  border: none;
  border-radius: 0.5rem;
  font-weight: 500;
  cursor: pointer;
  transition: background-color 0.15s;
}

.btn-add:hover {
  background: #059669;
}

.btn-icon {
  font-size: 1.25rem;
}

.loading-state,
.error-state {
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
  border-top-color: #10b981;
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin-bottom: 1rem;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.error-message {
  color: #ef4444;
  margin-bottom: 1rem;
}

.btn-retry {
  padding: 0.5rem 1rem;
  background: #10b981;
  color: white;
  border: none;
  border-radius: 0.375rem;
  cursor: pointer;
}

.btn-retry:hover {
  background: #059669;
}

.table-container {
  overflow-x: auto;
}

.users-table {
  width: 100%;
  border-collapse: collapse;
}

.users-table thead {
  background: #f9fafb;
  border-bottom: 1px solid #e5e7eb;
}

.users-table th {
  padding: 1rem 1.5rem;
  text-align: left;
  font-size: 0.75rem;
  font-weight: 600;
  color: #6b7280;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.users-table tbody tr {
  border-bottom: 1px solid #f3f4f6;
  transition: background-color 0.15s;
}

.users-table tbody tr:hover {
  background: #f9fafb;
}

.users-table td {
  padding: 1.25rem 1.5rem;
  color: #374151;
}

.no-data {
  text-align: center;
  padding: 4rem 2rem !important;
  color: #9ca3af;
}

.no-data-icon {
  font-size: 3rem;
  display: block;
  margin-bottom: 1rem;
}

.user-name {
  font-weight: 600;
  color: #1f2937;
  font-size: 0.9375rem;
}

.contact-info {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.email {
  color: #374151;
  font-size: 0.875rem;
}

.phone {
  color: #6b7280;
  font-size: 0.8125rem;
}

.department-name {
  color: #374151;
  font-size: 0.9375rem;
}

.roles {
  color: #374151;
  font-size: 0.9375rem;
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

.action-buttons {
  display: flex;
  gap: 0.5rem;
}

.btn-edit,
.btn-delete {
  padding: 0.375rem 0.75rem;
  border: none;
  background: transparent;
  cursor: pointer;
  border-radius: 0.375rem;
  font-size: 1rem;
  transition: background-color 0.15s;
}

.btn-edit:hover {
  background: #dbeafe;
}

.btn-delete:hover {
  background: #fee2e2;
}

/* Dialog Styles */
.dialog-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  padding: 1rem;
}

.dialog {
  background: white;
  border-radius: 0.75rem;
  max-width: 500px;
  width: 100%;
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
  max-height: 90vh;
  overflow-y: auto;
}

.dialog-large {
  max-width: 600px;
}

.dialog-small {
  max-width: 400px;
}

.dialog-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1.5rem;
  border-bottom: 1px solid #e5e7eb;
}

.dialog-header h3 {
  margin: 0;
  font-size: 1.125rem;
  font-weight: 600;
  color: #1f2937;
}

.btn-close {
  padding: 0;
  width: 32px;
  height: 32px;
  border: none;
  background: transparent;
  color: #6b7280;
  cursor: pointer;
  border-radius: 0.375rem;
  font-size: 1.25rem;
  transition: background-color 0.15s;
}

.btn-close:hover {
  background: #f3f4f6;
}

.dialog-body {
  padding: 1.5rem;
}

.form-group {
  margin-bottom: 1.25rem;
}

.form-group:last-child {
  margin-bottom: 0;
}

.form-group label {
  display: block;
  margin-bottom: 0.5rem;
  font-size: 0.875rem;
  font-weight: 500;
  color: #374151;
}

.form-input,
.form-select {
  width: 100%;
  padding: 0.625rem 0.875rem;
  border: 1px solid #d1d5db;
  border-radius: 0.5rem;
  font-size: 0.875rem;
  color: #1f2937;
  transition: border-color 0.15s, box-shadow 0.15s;
}

.form-input:focus,
.form-select:focus {
  outline: none;
  border-color: #10b981;
  box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
}

.form-input:disabled {
  background: #f3f4f6;
  color: #6b7280;
  cursor: not-allowed;
}

.checkbox-group {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.checkbox-label {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  cursor: pointer;
  font-size: 0.875rem;
  color: #374151;
}

.checkbox-label input[type="checkbox"] {
  width: 1.125rem;
  height: 1.125rem;
  border: 1px solid #d1d5db;
  border-radius: 0.25rem;
  cursor: pointer;
  accent-color: #10b981;
}

.dialog-footer {
  display: flex;
  justify-content: flex-end;
  gap: 0.75rem;
  padding: 1.5rem;
  border-top: 1px solid #e5e7eb;
}

.btn-cancel,
.btn-save,
.btn-delete-confirm {
  padding: 0.625rem 1.25rem;
  border: none;
  border-radius: 0.5rem;
  font-weight: 500;
  cursor: pointer;
  transition: background-color 0.15s;
}

.btn-cancel {
  background: #f3f4f6;
  color: #374151;
}

.btn-cancel:hover {
  background: #e5e7eb;
}

.btn-save {
  background: #10b981;
  color: white;
}

.btn-save:hover:not(:disabled) {
  background: #059669;
}

.btn-save:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.btn-delete-confirm {
  background: #ef4444;
  color: white;
}

.btn-delete-confirm:hover {
  background: #dc2626;
}

.btn-transfer-confirm {
  background: #3b82f6;
  color: white;
}

.btn-transfer-confirm:hover:not(:disabled) {
  background: #2563eb;
}

.btn-transfer-confirm:disabled {
  background: #9ca3af;
  cursor: not-allowed;
  opacity: 0.5;
}

.btn-transfer {
  background: transparent;
  border: none;
  cursor: pointer;
  padding: 0.375rem 0.75rem;
  border-radius: 4px;
  font-size: 1rem;
}

.btn-transfer:hover {
  background: #dbeafe;
}

.transfer-info {
  margin-bottom: 1rem;
  padding: 1rem;
  background: #f3f4f6;
  border-radius: 6px;
}

.current-id {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.current-id label {
  font-weight: 600;
  color: #374151;
}

.id-badge {
  background: #3b82f6;
  color: white;
  padding: 0.25rem 0.75rem;
  border-radius: 12px;
  font-size: 0.875rem;
  font-weight: 600;
}

.warning-text {
  color: #ef4444;
  font-size: 0.875rem;
  margin-top: 0.5rem;
}

.field-hint {
  display: block;
  margin-top: 0.375rem;
  font-size: 0.8125rem;
  color: #6b7280;
}

.info-box {
  background: #eff6ff;
  border-left: 4px solid #3b82f6;
  padding: 1rem;
  border-radius: 0.375rem;
  margin-top: 1rem;
}

.info-box p {
  margin: 0;
  font-size: 0.875rem;
  color: #1e40af;
}

@media (max-width: 768px) {
  .users-page {
    padding: 1rem;
  }

  .page-header {
    flex-direction: column;
    align-items: flex-start;
    gap: 1rem;
  }

  .card-header {
    flex-direction: column;
    gap: 1rem;
  }

  .btn-add {
    width: 100%;
    justify-content: center;
  }
}
</style>
