<template>
  <div class="p-6 max-w-7xl mx-auto">
    <!-- Page Header -->
    <PageHeader 
      icon="🏢" 
      title="Departments"
      subtitle="Manage department information and asset totals"
    />

    <!-- Departments Card -->
    <div class="card bg-base-100 shadow-xl">
      <div class="card-body">
        <div class="flex justify-between items-center mb-4">
          <h2 class="card-title text-xl">Manage Departments</h2>
          <button class="btn btn-success gap-2" @click="openAddDialog">
            <span class="text-xl">⊕</span>
            Add Department
          </button>
        </div>

        <!-- Loading State -->
        <LoadingSpinner v-if="loading" message="Loading departments..." />

        <!-- Error State -->
        <div v-else-if="error" class="flex flex-col items-center justify-center py-16 gap-4">
          <div class="alert alert-error max-w-md">
            <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span>{{ error }}</span>
          </div>
          <button @click="fetchDepartments" class="btn btn-success">Retry</button>
        </div>

        <!-- Departments Table -->
        <div v-else class="overflow-x-auto">
          <EmptyState 
            v-if="departments.length === 0"
            icon="📭"
            title="No departments found"
            message="Create your first department to get started"
          />
          <table v-else class="table table-zebra w-full">
            <thead>
              <tr>
                <th class="text-xs font-semibold uppercase tracking-wider">Acronym</th>
                <th class="text-xs font-semibold uppercase tracking-wider">Department Name</th>
                <th class="text-xs font-semibold uppercase tracking-wider">Total Assets</th>
                <th class="text-xs font-semibold uppercase tracking-wider">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="dept in departments" :key="dept.id">
                <td class="font-semibold">{{ dept.acronym || '—' }}</td>
                <td>{{ dept.name }}</td>
                <td>
                  <input 
                    v-model.number="dept.total_assets" 
                    @blur="updateTotalAssets(dept)"
                    type="number" 
                    class="input input-bordered input-sm w-32 text-center"
                    min="0"
                    placeholder="0"
                  />
                </td>
                <td>
                  <div class="flex gap-2">
                    <button 
                      @click="openEditDialog(dept)" 
                      class="btn btn-ghost btn-sm"
                      title="Edit"
                    >
                      ✏️
                    </button>
                    <button 
                      @click="confirmDelete(dept)" 
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
      :title="editingDepartment ? 'Edit Department' : 'Add New Department'"
    >
      <div class="space-y-4">
        <div class="form-control">
          <label class="label">
            <span class="label-text font-medium">Acronym</span>
          </label>
          <input 
            id="dept-acronym"
            v-model="formData.acronym" 
            type="text" 
            placeholder="e.g., JKM" 
            class="input input-bordered w-full"
            maxlength="20"
          />
        </div>
        <div class="form-control">
          <label class="label">
            <span class="label-text font-medium">Department Name *</span>
          </label>
          <input 
            id="dept-name"
            v-model="formData.name" 
            type="text" 
            placeholder="e.g., Kejuruteraan Mekanikal" 
            class="input input-bordered w-full"
          />
        </div>
      </div>
      <template #actions>
        <button @click="closeDialog" class="btn btn-ghost">Cancel</button>
        <button @click="saveDepartment" class="btn btn-success" :disabled="!isFormValid">
          {{ editingDepartment ? 'Update' : 'Create' }}
        </button>
      </template>
    </Modal>

    <!-- Delete Confirmation Modal -->
    <Modal 
      v-model="showDeleteDialog"
      title="Delete Department"
      size="sm"
    >
      <div class="space-y-4">
        <p>Are you sure you want to delete <strong>{{ departmentToDelete?.name }}</strong>?</p>
        <div class="alert alert-warning">
          <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
          </svg>
          <span class="text-sm">This will also delete all locations and inspections associated with this department.</span>
        </div>
      </div>
      <template #actions>
        <button @click="closeDeleteDialog" class="btn btn-ghost">Cancel</button>
        <button @click="deleteDepartment" class="btn btn-error">Delete</button>
      </template>
    </Modal>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import api from '../api';
import { PageHeader, LoadingSpinner, EmptyState, Modal } from '../components';

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
