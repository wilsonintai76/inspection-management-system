<template>
  <div class="locations-page">
    <!-- Page Header -->
    <div class="page-header">
      <div class="header-left">
        <span class="breadcrumb-icon">📍</span>
        <h1 class="page-title">Locations</h1>
      </div>
      <button class="btn-primary" @click="openAddDialog" v-if="canManage">
        <span class="btn-icon">+</span>
        Add Location
      </button>
    </div>

    <!-- Locations Card -->
    <div class="locations-card">
      <div class="card-header">
        <h2 class="card-title">All Locations</h2>
        <div class="header-actions">
          <input 
            type="text" 
            v-model="searchQuery" 
            placeholder="Search locations..." 
            class="search-input"
          />
          <select v-if="!deptRestricted" v-model="filterDepartment" class="department-filter">
            <option value="">All Departments</option>
            <option v-for="dept in departments" :key="dept.id" :value="dept.id">
              {{ dept.name }}
            </option>
          </select>
          <div v-else class="department-filter" style="pointer-events:none; opacity:.7;">
            {{ currentDepartmentName }}
          </div>
        </div>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="loading-state">
        <div class="spinner"></div>
        <p>Loading locations...</p>
      </div>

      <!-- Error State -->
      <div v-else-if="error" class="error-state">
        <p class="error-message">{{ error }}</p>
        <button @click="fetchLocations" class="btn-retry">Retry</button>
      </div>

      <!-- Locations Table -->
      <div v-else class="table-container">
        <table class="locations-table">
          <thead>
            <tr>
              <th>Location Name</th>
              <th>Department</th>
              <th>Supervisor</th>
              <th>Contact Number</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="filteredLocations.length === 0">
              <td colspan="5" class="no-data">
                <span class="no-data-icon">📭</span>
                <p>No locations found</p>
              </td>
            </tr>
            <tr v-for="location in filteredLocations" :key="location.id">
              <td>
                <div class="location-name">
                  <span class="location-icon">📍</span>
                  {{ location.name }}
                </div>
              </td>
              <td>
                <span class="department-badge">
                  {{ getDepartmentName(location.department_id) }}
                </span>
              </td>
              <td>
                <span class="supervisor-name">
                  {{ location.supervisor || '—' }}
                </span>
              </td>
              <td>
                <span class="contact-number">
                  {{ location.contact_number || '—' }}
                </span>
              </td>
              <td>
                <div class="action-buttons">
                  <button 
                    v-if="canManage"
                    @click="openEditDialog(location)" 
                    class="btn-edit"
                    title="Edit"
                  >
                    ✏️
                  </button>
                  <button 
                    v-if="canManage"
                    @click="confirmDelete(location)" 
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

    <!-- Add/Edit Dialog -->
    <div v-if="showDialog" class="dialog-overlay" @click="closeDialog">
      <div class="dialog" @click.stop>
        <div class="dialog-header">
          <h3>{{ editingLocation ? 'Edit Location' : 'Add New Location' }}</h3>
          <button @click="closeDialog" class="btn-close">✕</button>
        </div>
        <div class="dialog-body">
          <div class="form-group">
            <label for="location-name">Location Name *</label>
            <input 
              id="location-name"
              v-model="formData.name" 
              type="text" 
              placeholder="e.g., HITECH Lab" 
              class="form-input"
            />
          </div>
          <div class="form-group">
            <label for="department">Department *</label>
            <select id="department" v-model="formData.department_id" class="form-select" :disabled="deptRestricted">
              <option value="">Select department...</option>
              <option v-for="dept in departments" :key="dept.id" :value="dept.id">
                {{ dept.name }}
              </option>
            </select>
          </div>
          <div class="form-group">
            <label for="supervisor">Supervisor</label>
            <input 
              id="supervisor"
              v-model="formData.supervisor" 
              type="text" 
              placeholder="e.g., John Doe" 
              class="form-input"
            />
          </div>
          <div class="form-group">
            <label for="contact">Contact Number</label>
            <input 
              id="contact"
              v-model="formData.contact_number" 
              type="text" 
              placeholder="e.g., 012-3456789" 
              class="form-input"
            />
          </div>
        </div>
        <div class="dialog-footer">
          <button @click="closeDialog" class="btn-cancel">Cancel</button>
          <button @click="saveLocation" class="btn-save" :disabled="!isFormValid">
            {{ editingLocation ? 'Update' : 'Create' }}
          </button>
        </div>
      </div>
    </div>

    <!-- Delete Confirmation Dialog -->
    <div v-if="showDeleteDialog" class="dialog-overlay" @click="closeDeleteDialog">
      <div class="dialog dialog-small" @click.stop>
        <div class="dialog-header">
          <h3>Delete Location</h3>
          <button @click="closeDeleteDialog" class="btn-close">✕</button>
        </div>
        <div class="dialog-body">
          <p>Are you sure you want to delete <strong>{{ locationToDelete?.name }}</strong>?</p>
          <p class="warning-text">This action cannot be undone.</p>
        </div>
        <div class="dialog-footer">
          <button @click="closeDeleteDialog" class="btn-cancel">Cancel</button>
          <button @click="deleteLocation" class="btn-delete-confirm">Delete</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import api from '../api';
import { useAuth } from '../composables/useAuth';

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

<style scoped>
.locations-page {
  padding: 2rem;
  max-width: 1400px;
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

.btn-primary {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.625rem 1.25rem;
  background: #14b8a6;
  color: white;
  border: none;
  border-radius: 0.5rem;
  font-weight: 500;
  cursor: pointer;
  transition: background-color 0.15s;
}

.btn-primary:hover {
  background: #0d9488;
}

.btn-icon {
  font-size: 1.25rem;
}

.locations-card {
  background: white;
  border-radius: 0.75rem;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  overflow: hidden;
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1.5rem 2rem;
  border-bottom: 1px solid #e5e7eb;
  flex-wrap: wrap;
  gap: 1rem;
}

.card-title {
  font-size: 1.25rem;
  font-weight: 600;
  color: #1f2937;
  margin: 0;
}

.header-actions {
  display: flex;
  gap: 1rem;
  align-items: center;
}

.search-input {
  padding: 0.5rem 1rem;
  border: 1px solid #d1d5db;
  border-radius: 0.5rem;
  font-size: 0.875rem;
  width: 250px;
}

.search-input:focus {
  outline: none;
  border-color: #14b8a6;
  box-shadow: 0 0 0 3px rgba(20, 184, 166, 0.1);
}

.department-filter {
  padding: 0.5rem 2.5rem 0.5rem 1rem;
  border: 1px solid #d1d5db;
  border-radius: 0.5rem;
  font-size: 0.875rem;
  color: #374151;
  background: white;
  cursor: pointer;
  appearance: none;
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%236B7280' d='M6 9L1 4h10z'/%3E%3C/svg%3E");
  background-repeat: no-repeat;
  background-position: right 0.75rem center;
}

.department-filter:focus {
  outline: none;
  border-color: #14b8a6;
  box-shadow: 0 0 0 3px rgba(20, 184, 166, 0.1);
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
  border-top-color: #14b8a6;
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
  background: #14b8a6;
  color: white;
  border: none;
  border-radius: 0.375rem;
  cursor: pointer;
}

.table-container {
  overflow-x: auto;
}

.locations-table {
  width: 100%;
  border-collapse: collapse;
}

.locations-table thead {
  background: #f9fafb;
  border-bottom: 1px solid #e5e7eb;
}

.locations-table th {
  padding: 1rem 1.5rem;
  text-align: left;
  font-size: 0.75rem;
  font-weight: 600;
  color: #6b7280;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.locations-table tbody tr {
  border-bottom: 1px solid #f3f4f6;
  transition: background-color 0.15s;
}

.locations-table tbody tr:hover {
  background: #f9fafb;
}

.locations-table td {
  padding: 1rem 1.5rem;
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

.location-name {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-weight: 600;
  color: #1f2937;
}

.location-icon {
  font-size: 1rem;
}

.department-badge {
  display: inline-block;
  padding: 0.25rem 0.75rem;
  background: #dbeafe;
  color: #1e40af;
  border-radius: 9999px;
  font-size: 0.8125rem;
  font-weight: 500;
}

.supervisor-name,
.contact-number {
  color: #6b7280;
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
  border-color: #14b8a6;
  box-shadow: 0 0 0 3px rgba(20, 184, 166, 0.1);
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
  background: #14b8a6;
  color: white;
}

.btn-save:hover:not(:disabled) {
  background: #0d9488;
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

.warning-text {
  color: #ef4444;
  font-size: 0.875rem;
  margin-top: 0.5rem;
}

@media (max-width: 768px) {
  .locations-page {
    padding: 1rem;
  }

  .page-header {
    flex-direction: column;
    align-items: flex-start;
    gap: 1rem;
  }

  .btn-primary {
    width: 100%;
    justify-content: center;
  }

  .card-header {
    flex-direction: column;
    align-items: flex-start;
  }

  .header-actions {
    width: 100%;
    flex-direction: column;
  }

  .search-input,
  .department-filter {
    width: 100%;
  }
}
</style>
