<template>
  <div class="min-h-screen bg-base-200 p-6">
    <!-- Page Header -->
    <PageHeader
      icon="fas fa-users"
      title="Users"
      subtitle="Manage application users, roles, and status"
    />

    <!-- Users Card -->
    <div class="card bg-base-100 shadow-md mt-6">
      <div class="card-body">
        <div class="flex flex-col md:flex-row md:justify-between md:items-start gap-4 mb-4">
          <div>
            <h2 class="card-title">User Management</h2>
            <p class="text-sm text-base-content/60">Manage application users, roles, and status.</p>
          </div>
          <ActionButton 
            @click="openAddDialog"
            variant="success"
            icon="fas fa-plus"
          >
            Add User
          </ActionButton>
        </div>

        <!-- Loading State -->
        <LoadingSpinner v-if="loading" message="Loading users..." />

        <!-- Error State -->
        <div v-else-if="error" class="alert alert-error">
          <i class="fas fa-exclamation-circle"></i>
          <div>
            <p>{{ error }}</p>
            <button @click="fetchData" class="btn btn-sm btn-outline mt-2">Retry</button>
          </div>
        </div>

        <!-- Users Table -->
        <div v-else class="overflow-x-auto">
          <table class="table table-zebra">
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
                <td colspan="6">
                  <EmptyState
                    icon="fas fa-users"
                    title="No users found"
                    message="Get started by adding your first user"
                  />
                </td>
              </tr>
              <tr v-for="user in users" :key="user.id">
                <td>
                  <div class="font-semibold">{{ user.name }}</div>
                </td>
                <td>
                  <div class="flex flex-col gap-1">
                    <div class="text-sm">{{ user.email }}</div>
                    <div class="text-xs text-base-content/60">{{ user.phone || 'No phone' }}</div>
                  </div>
                </td>
                <td>
                  {{ getDepartmentName(user.department_id) }}
                </td>
                <td>
                  {{ formatRoles(user.roles) }}
                </td>
                <td>
                  <button 
                    @click="toggleStatus(user)" 
                    :class="[
                      'btn btn-xs',
                      user.status === 'Verified' ? 'btn-success' : 'btn-ghost'
                    ]"
                    :title="`Click to ${user.status === 'Verified' ? 'unverify' : 'verify'}`"
                  >
                    {{ user.status || 'Unverified' }}
                  </button>
                </td>
                <td>
                  <div class="flex gap-2">
                    <button 
                      @click="openEditDialog(user)" 
                      class="btn btn-xs btn-ghost"
                      title="Edit"
                    >
                      <i class="fas fa-edit"></i>
                    </button>
                    <button 
                      @click="openTransferDialog(user)" 
                      class="btn btn-xs btn-ghost"
                      title="Transfer Staff ID"
                    >
                      <i class="fas fa-exchange-alt"></i>
                    </button>
                    <button 
                      @click="confirmDelete(user)" 
                      class="btn btn-xs btn-ghost text-error"
                      title="Delete"
                    >
                      <i class="fas fa-trash"></i>
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Add/Edit User Modal -->
    <Modal
      v-model="showDialog"
      :title="editingUser ? 'Edit User' : 'Add New User'"
      size="lg"
    >
      <div class="space-y-4">
        <div class="form-control">
          <label class="label">
            <span class="label-text">Staff ID *</span>
          </label>
          <input 
            v-model="formData.staff_id" 
            type="text" 
            placeholder="4-digit staff ID" 
            class="input input-bordered"
            maxlength="4"
            pattern="\d{4}"
            :disabled="!!editingUser"
          />
          <label class="label">
            <span class="label-text-alt">Must be 4 digits. Cannot be changed after creation.</span>
          </label>
        </div>

        <div class="form-control">
          <label class="label">
            <span class="label-text">Full Name *</span>
          </label>
          <input 
            v-model="formData.name" 
            type="text" 
            placeholder="John Doe" 
            class="input input-bordered"
          />
        </div>

        <div class="form-control">
          <label class="label">
            <span class="label-text">Institution Email Address *</span>
          </label>
          <input 
            v-model="formData.email" 
            type="email" 
            placeholder="user@institution.edu" 
            class="input input-bordered"
          />
        </div>

        <div class="form-control">
          <label class="label">
            <span class="label-text">Personal Email Address</span>
          </label>
          <input 
            v-model="formData.personal_email" 
            type="email" 
            placeholder="user@gmail.com" 
            class="input input-bordered"
          />
        </div>

        <div class="form-control">
          <label class="label">
            <span class="label-text">Mobile Phone No.</span>
          </label>
          <input 
            v-model="formData.phone" 
            type="text" 
            placeholder="(555) 123-4567" 
            class="input input-bordered"
          />
        </div>

        <div class="form-control">
          <label class="label">
            <span class="label-text">Roles *</span>
          </label>
          <div class="space-y-2">
            <label class="label cursor-pointer justify-start gap-2">
              <input type="checkbox" class="checkbox checkbox-primary" value="Admin" v-model="formData.roles" />
              <span class="label-text">Admin</span>
            </label>
            <label class="label cursor-pointer justify-start gap-2">
              <input type="checkbox" class="checkbox checkbox-primary" value="Asset Officer" v-model="formData.roles" />
              <span class="label-text">Asset Officer</span>
            </label>
            <label class="label cursor-pointer justify-start gap-2">
              <input type="checkbox" class="checkbox checkbox-primary" value="Auditor" v-model="formData.roles" />
              <span class="label-text">Auditor</span>
            </label>
            <label class="label cursor-pointer justify-start gap-2">
              <input type="checkbox" class="checkbox checkbox-primary" value="Viewer" v-model="formData.roles" />
              <span class="label-text">Viewer</span>
            </label>
          </div>
        </div>

        <div class="form-control">
          <label class="label">
            <span class="label-text">Department *</span>
          </label>
          <select v-model="formData.department_id" class="select select-bordered">
            <option value="">Select a department</option>
            <option v-for="dept in departments" :key="dept.id" :value="dept.id">
              {{ dept.name }}
            </option>
          </select>
        </div>

        <div v-if="!editingUser" class="alert alert-info">
          <i class="fas fa-info-circle"></i>
          <div class="text-sm">
            <strong>Note:</strong> A temporary password will be automatically generated and sent to the user's email. The user will be required to change it on first login.
          </div>
        </div>
      </div>

      <template #actions>
        <button @click="closeDialog" class="btn">Cancel</button>
        <button @click="saveUser" class="btn btn-primary" :disabled="!isFormValid">
          {{ editingUser ? 'Update User' : 'Create User' }}
        </button>
      </template>
    </Modal>

    <!-- Delete Confirmation Modal -->
    <Modal
      v-model="showDeleteDialog"
      title="Delete User"
      size="sm"
    >
      <div class="space-y-4">
        <p>Are you sure you want to delete <strong>{{ userToDelete?.name }}</strong>?</p>
        <p class="text-error text-sm">This action cannot be undone.</p>
      </div>

      <template #actions>
        <button @click="closeDeleteDialog" class="btn">Cancel</button>
        <button @click="deleteUser" class="btn btn-error">Delete</button>
      </template>
    </Modal>

    <!-- Transfer Staff ID Modal -->
    <Modal
      v-model="showTransferDialog"
      title="Transfer Staff ID"
      size="sm"
    >
      <div class="space-y-4">
        <p>Transfer staff ID for <strong>{{ userToTransfer?.name }}</strong></p>
        
        <div class="bg-base-200 p-4 rounded-lg">
          <div class="flex items-center gap-2">
            <span class="font-semibold">Current Staff ID:</span>
            <Badge variant="primary">{{ userToTransfer?.staff_id }}</Badge>
          </div>
        </div>

        <div class="form-control">
          <label class="label">
            <span class="label-text">New Staff ID *</span>
          </label>
          <input 
            v-model="newStaffId" 
            type="text" 
            placeholder="Enter new 4-digit staff ID" 
            class="input input-bordered"
            maxlength="4"
            pattern="\d{4}"
          />
          <label class="label">
            <span class="label-text-alt">Must be 4 digits and not already in use.</span>
          </label>
        </div>

        <div class="alert alert-warning">
          <i class="fas fa-exclamation-triangle"></i>
          <div class="text-sm">
            This will update the staff ID across all database tables. The user will need to login with the new staff ID.
          </div>
        </div>
      </div>

      <template #actions>
        <button @click="closeTransferDialog" class="btn">Cancel</button>
        <button @click="transferStaffId" class="btn btn-primary" :disabled="!isNewStaffIdValid">Transfer</button>
      </template>
    </Modal>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import api from '../api';
import { PageHeader, ActionButton, LoadingSpinner, EmptyState, Modal, Badge } from '../components';

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
