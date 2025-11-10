<template>
  <div class="p-6 max-w-7xl mx-auto">
    <!-- Page Header -->
    <PageHeader 
      icon="📍" 
      title="Locations"
      subtitle="Manage location information and supervisors"
    >
      <template #actions>
        <button class="btn btn-primary" @click="openAddDialog" v-if="canManage">
          <span class="text-xl">+</span>
          Add Location
        </button>
      </template>
    </PageHeader>

    <!-- Locations Card -->
    <div class="card bg-base-100 shadow-xl">
      <div class="card-body">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-4">
          <h2 class="card-title text-xl">All Locations</h2>
          <div class="flex flex-col sm:flex-row gap-3 w-full md:w-auto">
            <input 
              type="text" 
              v-model="searchQuery" 
              placeholder="Search locations..." 
              class="input input-bordered w-full sm:w-64"
            />
            <select 
              v-if="!deptRestricted" 
              v-model="filterDepartment" 
              class="select select-bordered w-full sm:w-auto"
            >
              <option value="">All Departments</option>
              <option v-for="dept in departments" :key="dept.id" :value="dept.id">
                {{ dept.name }}
              </option>
            </select>
            <div v-else class="badge badge-lg badge-ghost opacity-70">
              {{ currentDepartmentName }}
            </div>
          </div>
        </div>

        <!-- Loading State -->
        <LoadingSpinner v-if="loading" message="Loading locations..." />

        <!-- Error State -->
        <div v-else-if="error" class="flex flex-col items-center justify-center py-16 gap-4">
          <div class="alert alert-error max-w-md">
            <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span>{{ error }}</span>
          </div>
          <button @click="fetchLocations" class="btn btn-primary">Retry</button>
        </div>

        <!-- Locations Table -->
        <div v-else class="overflow-x-auto">
          <EmptyState 
            v-if="filteredLocations.length === 0"
            icon="📭"
            title="No locations found"
            message="Create your first location or adjust your filters"
          />
          <table v-else class="table table-zebra w-full">
            <thead>
              <tr>
                <th class="text-xs font-semibold uppercase tracking-wider">Location Name</th>
                <th class="text-xs font-semibold uppercase tracking-wider">Department</th>
                <th class="text-xs font-semibold uppercase tracking-wider">Supervisor</th>
                <th class="text-xs font-semibold uppercase tracking-wider">Contact Number</th>
                <th class="text-xs font-semibold uppercase tracking-wider">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="location in filteredLocations" :key="location.id">
                <td>
                  <div class="flex items-center gap-2 font-semibold">
                    <span>📍</span>
                    {{ location.name }}
                  </div>
                </td>
                <td>
                  <span class="text-sm">{{ getDepartmentName(location.department_id) }}</span>
                </td>
                <td class="text-gray-600">
                  {{ location.supervisor || '—' }}
                </td>
                <td class="text-gray-600">
                  {{ location.contact_number || '—' }}
                </td>
                <td>
                  <div class="flex gap-2">
                    <button 
                      v-if="canManage"
                      @click="openEditDialog(location)" 
                      class="btn btn-ghost btn-sm"
                      title="Edit"
                    >
                      ✏️
                    </button>
                    <button 
                      v-if="canManage"
                      @click="confirmDelete(location)" 
                      class="btn btn-ghost btn-sm text-error"
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
    </div>

    <!-- Add/Edit Modal -->
    <Modal 
      v-model="showDialog"
      :title="editingLocation ? 'Edit Location' : 'Add New Location'"
    >
      <div class="space-y-4">
        <div class="form-control">
          <label class="label">
            <span class="label-text font-medium">Location Name *</span>
          </label>
          <input 
            id="location-name"
            v-model="formData.name" 
            type="text" 
            placeholder="e.g., HITECH Lab" 
            class="input input-bordered w-full"
          />
        </div>
        <div class="form-control">
          <label class="label">
            <span class="label-text font-medium">Department *</span>
          </label>
          <select id="department" v-model="formData.department_id" class="select select-bordered w-full" :disabled="deptRestricted">
            <option value="">Select department...</option>
            <option v-for="dept in departments" :key="dept.id" :value="dept.id">
              {{ dept.name }}
            </option>
          </select>
        </div>
        <div class="form-control">
          <label class="label">
            <span class="label-text font-medium">Supervisor</span>
          </label>
          <input 
            id="supervisor"
            v-model="formData.supervisor" 
            type="text" 
            placeholder="e.g., John Doe" 
            class="input input-bordered w-full"
          />
        </div>
        <div class="form-control">
          <label class="label">
            <span class="label-text font-medium">Contact Number</span>
          </label>
          <input 
            id="contact"
            v-model="formData.contact_number" 
            type="text" 
            placeholder="e.g., 012-3456789" 
            class="input input-bordered w-full"
          />
        </div>
      </div>
      <template #actions>
        <button @click="closeDialog" class="btn btn-ghost">Cancel</button>
        <button @click="saveLocation" class="btn btn-primary" :disabled="!isFormValid">
          {{ editingLocation ? 'Update' : 'Create' }}
        </button>
      </template>
    </Modal>

    <!-- Delete Confirmation Modal -->
    <Modal 
      v-model="showDeleteDialog"
      title="Delete Location"
      size="sm"
    >
      <div class="space-y-4">
        <p>Are you sure you want to delete <strong>{{ locationToDelete?.name }}</strong>?</p>
        <div class="alert alert-warning">
          <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
          </svg>
          <span class="text-sm">This action cannot be undone.</span>
        </div>
      </div>
      <template #actions>
        <button @click="closeDeleteDialog" class="btn btn-ghost">Cancel</button>
        <button @click="deleteLocation" class="btn btn-error">Delete</button>
      </template>
    </Modal>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import api from '../api';
import { useAuth } from '../composables/useAuth';
import { PageHeader, LoadingSpinner, EmptyState, Badge, Modal } from '../components';

interface Department {
  id: number;
  name: string;
  description?: string;
}

interface Location {
  id: number;
  name: string;
  department_id: number;
  supervisor?: string;
  contact_number?: string;
  created_at?: string;
  updated_at?: string;
}

interface FormData {
  name: string;
  department_id: number | string;
  supervisor: string;
  contact_number: string;
}

const locations = ref<Location[]>([]);
const departments = ref<Department[]>([]);
const loading = ref(false);
const error = ref('');
const searchQuery = ref('');
const filterDepartment = ref('');

// Auth and permissions
const auth = useAuth();
const deptRestricted = computed(() => auth.isDeptRestricted.value);
const userDeptId = computed(() => auth.userDepartmentId.value);
const canManage = computed(() => auth.can('canManageLocations'));
const currentDepartmentName = computed(() => {
  const id = Number(userDeptId.value);
  const dept = departments.value.find(d => d.id === id);
  return dept?.name || 'Your Department';
});

const showDialog = ref(false);
const showDeleteDialog = ref(false);
const editingLocation = ref<Location | null>(null);
const locationToDelete = ref<Location | null>(null);

const formData = ref<FormData>({
  name: '',
  department_id: '',
  supervisor: '',
  contact_number: ''
});

const filteredLocations = computed(() => {
  let result = locations.value;

  // Filter by search query
  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase();
    result = result.filter(loc => 
      loc.name.toLowerCase().includes(query) ||
      loc.supervisor?.toLowerCase().includes(query) ||
      loc.contact_number?.toLowerCase().includes(query)
    );
  }

  // Filter by department
  if (filterDepartment.value) {
    result = result.filter(loc => loc.department_id === Number(filterDepartment.value));
  }

  return result;
});

const isFormValid = computed(() => {
  return formData.value.name.trim() !== '' && formData.value.department_id !== '';
});

function getDepartmentName(departmentId: number): string {
  const dept = departments.value.find(d => d.id === departmentId);
  return dept ? dept.name : 'Unknown';
}

function openAddDialog() {
  editingLocation.value = null;
  formData.value = {
    name: '',
    department_id: deptRestricted.value ? Number(userDeptId.value) : '',
    supervisor: '',
    contact_number: ''
  };
  showDialog.value = true;
}

function openEditDialog(location: Location) {
  editingLocation.value = location;
  formData.value = {
    name: location.name,
    department_id: location.department_id,
    supervisor: location.supervisor || '',
    contact_number: location.contact_number || ''
  };
  showDialog.value = true;
}

function closeDialog() {
  showDialog.value = false;
  editingLocation.value = null;
}

function confirmDelete(location: Location) {
  locationToDelete.value = location;
  showDeleteDialog.value = true;
}

function closeDeleteDialog() {
  showDeleteDialog.value = false;
  locationToDelete.value = null;
}

async function fetchLocations() {
  loading.value = true;
  error.value = '';
  try {
    const q = deptRestricted.value && userDeptId.value ? `?department_id=${userDeptId.value}` : '';
    const response = await api.get(`/locations.php${q}`);
    locations.value = response.data;
  } catch (err) {
    error.value = 'Failed to load locations. Please try again.';
    console.error('Error fetching locations:', err);
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

async function saveLocation() {
  if (!isFormValid.value) return;

  try {
    const data = {
      name: formData.value.name.trim(),
      department_id: deptRestricted.value ? Number(userDeptId.value) : Number(formData.value.department_id),
      supervisor: formData.value.supervisor.trim() || null,
      contact_number: formData.value.contact_number.trim() || null
    };

    if (editingLocation.value) {
      // Update existing location
      await api.put(`/locations.php?id=${editingLocation.value.id}`, data);
    } else {
      // Create new location
      await api.post('/locations.php', data);
    }

    closeDialog();
    await fetchLocations();
  } catch (err) {
    console.error('Error saving location:', err);
    alert('Failed to save location. Please try again.');
  }
}

async function deleteLocation() {
  if (!locationToDelete.value) return;

  try {
    await api.delete(`/locations.php?id=${locationToDelete.value.id}`);
    closeDeleteDialog();
    await fetchLocations();
  } catch (err) {
    console.error('Error deleting location:', err);
    alert('Failed to delete location. Please try again.');
  }
}

onMounted(async () => {
  if (deptRestricted.value && userDeptId.value) {
    filterDepartment.value = String(userDeptId.value);
  }
  await Promise.all([fetchLocations(), fetchDepartments()]);
});
</script>
