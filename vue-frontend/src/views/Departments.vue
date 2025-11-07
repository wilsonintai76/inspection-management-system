<template>
  <div class="departments-page">
    <!-- Page Header -->
    <div class="page-header">
      <div class="header-left">
        <span class="breadcrumb-icon">🏢</span>
        <h1 class="page-title">Departments</h1>
      </div>
    </div>

    <!-- Departments Card -->
    <div class="departments-card">
      <div class="card-header">
        <h2 class="card-title">Manage Departments</h2>
        <button class="btn-add" @click="openAddDialog">
          <span class="btn-icon">⊕</span>
          Add Department
        </button>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="loading-state">
        <div class="spinner"></div>
        <p>Loading departments...</p>
      </div>

      <!-- Error State -->
      <div v-else-if="error" class="error-state">
        <p class="error-message">{{ error }}</p>
        <button @click="fetchDepartments" class="btn-retry">Retry</button>
      </div>

      <!-- Departments Table -->
      <div v-else class="table-container">
        <table class="departments-table">
          <thead>
            <tr>
              <th>Acronym</th>
              <th>Department Name</th>
              <th>Total Assets</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="departments.length === 0">
              <td colspan="4" class="no-data">
                <span class="no-data-icon">📭</span>
                <p>No departments found</p>
              </td>
            </tr>
            <tr v-for="dept in departments" :key="dept.id">
              <td>
                <div class="acronym">{{ dept.acronym || '—' }}</div>
              </td>
              <td>
                <div class="department-name">{{ dept.name }}</div>
              </td>
              <td>
                <input 
                  v-model.number="dept.total_assets" 
                  @blur="updateTotalAssets(dept)"
                  type="number" 
                  class="total-assets-input"
                  min="0"
                  placeholder="0"
                />
              </td>
              <td>
                <div class="action-buttons">
                  <button 
                    @click="openEditDialog(dept)" 
                    class="btn-edit"
                    title="Edit"
                  >
                    ✏️
                  </button>
                  <button 
                    @click="confirmDelete(dept)" 
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
          <h3>{{ editingDepartment ? 'Edit Department' : 'Add New Department' }}</h3>
          <button @click="closeDialog" class="btn-close">✕</button>
        </div>
        <div class="dialog-body">
          <div class="form-group">
            <label for="dept-acronym">Acronym</label>
            <input 
              id="dept-acronym"
              v-model="formData.acronym" 
              type="text" 
              placeholder="e.g., JKM" 
              class="form-input"
              maxlength="20"
            />
          </div>
          <div class="form-group">
            <label for="dept-name">Department Name *</label>
            <input 
              id="dept-name"
              v-model="formData.name" 
              type="text" 
              placeholder="e.g., Kejuruteraan Mekanikal" 
              class="form-input"
            />
          </div>
        </div>
        <div class="dialog-footer">
          <button @click="closeDialog" class="btn-cancel">Cancel</button>
          <button @click="saveDepartment" class="btn-save" :disabled="!isFormValid">
            {{ editingDepartment ? 'Update' : 'Create' }}
          </button>
        </div>
      </div>
    </div>

    <!-- Delete Confirmation Dialog -->
    <div v-if="showDeleteDialog" class="dialog-overlay" @click="closeDeleteDialog">
      <div class="dialog dialog-small" @click.stop>
        <div class="dialog-header">
          <h3>Delete Department</h3>
          <button @click="closeDeleteDialog" class="btn-close">✕</button>
        </div>
        <div class="dialog-body">
          <p>Are you sure you want to delete <strong>{{ departmentToDelete?.name }}</strong>?</p>
          <p class="warning-text">This will also delete all locations and inspections associated with this department.</p>
        </div>
        <div class="dialog-footer">
          <button @click="closeDeleteDialog" class="btn-cancel">Cancel</button>
          <button @click="deleteDepartment" class="btn-delete-confirm">Delete</button>
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
  acronym?: string;
  total_assets?: number;
  created_at?: string;
  updated_at?: string;
}

interface FormData {
  name: string;
  acronym: string;
}

const departments = ref<Department[]>([]);
const loading = ref(false);
const error = ref('');

const showDialog = ref(false);
const showDeleteDialog = ref(false);
const editingDepartment = ref<Department | null>(null);
const departmentToDelete = ref<Department | null>(null);

const formData = ref<FormData>({
  name: '',
  acronym: ''
});

const isFormValid = computed(() => {
  return formData.value.name.trim() !== '';
});

function openAddDialog() {
  editingDepartment.value = null;
  formData.value = {
    name: '',
    acronym: ''
  };
  showDialog.value = true;
}

function openEditDialog(dept: Department) {
  editingDepartment.value = dept;
  formData.value = {
    name: dept.name,
    acronym: dept.acronym || ''
  };
  showDialog.value = true;
}

function closeDialog() {
  showDialog.value = false;
  editingDepartment.value = null;
}

function confirmDelete(dept: Department) {
  departmentToDelete.value = dept;
  showDeleteDialog.value = true;
}

function closeDeleteDialog() {
  showDeleteDialog.value = false;
  departmentToDelete.value = null;
}

async function fetchDepartments() {
  loading.value = true;
  error.value = '';
  try {
    const response = await api.get('/departments.php');
    departments.value = response.data;
  } catch (err) {
    error.value = 'Failed to load departments. Please try again.';
    console.error('Error fetching departments:', err);
  } finally {
    loading.value = false;
  }
}

async function saveDepartment() {
  if (!isFormValid.value) return;

  try {
    const data = {
      name: formData.value.name.trim(),
      acronym: formData.value.acronym.trim() || null
    };

    if (editingDepartment.value) {
      // Update existing department
      await api.put(`/departments.php?id=${editingDepartment.value.id}`, data);
    } else {
      // Create new department
      await api.post('/departments.php', data);
    }

    closeDialog();
    await fetchDepartments();
  } catch (err) {
    console.error('Error saving department:', err);
    alert('Failed to save department. Please try again.');
  }
}

async function deleteDepartment() {
  if (!departmentToDelete.value) return;

  try {
    await api.delete(`/departments.php?id=${departmentToDelete.value.id}`);
    closeDeleteDialog();
    await fetchDepartments();
  } catch (err) {
    console.error('Error deleting department:', err);
    alert('Failed to delete department. Please try again.');
  }
}

async function updateTotalAssets(dept: Department) {
  try {
    const data = {
      name: dept.name,
      acronym: dept.acronym || null,
      total_assets: dept.total_assets || 0
    };
    
    await api.put(`/departments.php?id=${dept.id}`, data);
  } catch (err) {
    console.error('Error updating total assets:', err);
    alert('Failed to update total assets. Please try again.');
    // Refresh to get the correct value back
    await fetchDepartments();
  }
}

onMounted(async () => {
  await fetchDepartments();
});
</script>

<style scoped>
.departments-page {
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

.departments-card {
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
}

.card-title {
  font-size: 1.25rem;
  font-weight: 600;
  color: #1f2937;
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

.departments-table {
  width: 100%;
  border-collapse: collapse;
}

.departments-table thead {
  background: #f9fafb;
  border-bottom: 1px solid #e5e7eb;
}

.departments-table th {
  padding: 1rem 1.5rem;
  text-align: left;
  font-size: 0.75rem;
  font-weight: 600;
  color: #6b7280;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.departments-table tbody tr {
  border-bottom: 1px solid #f3f4f6;
  transition: background-color 0.15s;
}

.departments-table tbody tr:hover {
  background: #f9fafb;
}

.departments-table td {
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

.acronym {
  font-weight: 600;
  color: #1f2937;
  font-size: 0.9375rem;
}

.department-name {
  color: #374151;
  font-size: 0.9375rem;
}

.total-assets-input {
  width: 120px;
  padding: 0.5rem 0.75rem;
  border: 1px solid #d1d5db;
  border-radius: 0.375rem;
  font-size: 0.9375rem;
  font-weight: 500;
  color: #374151;
  text-align: center;
  transition: border-color 0.15s, box-shadow 0.15s;
}

.total-assets-input:hover {
  border-color: var(--teal);
}

.total-assets-input:focus {
  outline: none;
  border-color: var(--teal);
  box-shadow: 0 0 0 3px rgba(20, 184, 166, 0.1);
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
  margin-bottom: 1.5rem;
}

.form-group:last-child {
  margin-bottom: 0;
}

.form-group label {
  display: block;
  margin-bottom: 0.5rem;
  font-size: 0.875rem;
  font-weight: 500;
  color: #1f2937;
}

.form-input {
  width: 100%;
  padding: 0.75rem 1rem;
  border: 1px solid #d1d5db;
  border-radius: 0.5rem;
  font-size: 0.9375rem;
  color: #1f2937;
  background: white;
  box-sizing: border-box;
  transition: border-color 0.15s, box-shadow 0.15s;
}

.form-input::placeholder {
  color: #9ca3af;
}

.form-input:focus {
  outline: none;
  border-color: #10b981;
  box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
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

.warning-text {
  color: #ef4444;
  font-size: 0.875rem;
  margin-top: 0.5rem;
}

@media (max-width: 768px) {
  .departments-page {
    padding: 1rem;
  }

  .page-header {
    flex-direction: column;
    align-items: flex-start;
    gap: 1rem;
  }

  .card-header {
    flex-direction: column;
    align-items: flex-start;
    gap: 1rem;
  }

  .btn-add {
    width: 100%;
    justify-content: center;
  }
}
</style>
