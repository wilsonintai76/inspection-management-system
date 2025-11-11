<template>
  <div class="p-6 max-w-7xl mx-auto">
    <!-- Page Header -->
    <PageHeader 
      icon="🏢" 
      :title="showBulkImportDialog ? 'Bulk Import Departments & Locations' : 'Departments'"
      :subtitle="showBulkImportDialog ? 'Upload merged Excel or CSV file containing departments and locations' : 'Manage department information and asset totals'"
    />

    <!-- Bulk Import Card (Expanded Modern UI) -->
    <div v-if="showBulkImportDialog" class="mb-8">
      <div class="card bg-base-100 shadow-xl">
        <div class="card-body">
          <div class="flex justify-between items-center mb-6">
            <button @click="closeBulkImportDialog" class="btn btn-ghost gap-2">
              ← Back to Departments
            </button>
          </div>

          <div class="text-center mb-6">
            <div class="text-6xl mb-4">📁</div>
            <h3 class="text-xl font-semibold text-gray-700">File Requirements</h3>
          </div>

          <div class="alert alert-info mb-6">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="stroke-current shrink-0 w-6 h-6">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <div class="text-sm">
              <ul class="list-disc list-inside space-y-1">
                <li>Format: Excel (.xlsx, .xls) or CSV</li>
                <li><strong>File size limit: Max 10MB per file, 50MB total</strong></li>
                <li>Required columns (exact names): <strong>Label, Jenis Aset, Pegawai Penyelia, Jabatan, Lokasi</strong></li>
                <li>Label = Asset ID (unique, e.g., KPT/PKS/I/08/406)</li>
                <li>Jenis Aset = Asset Type</li>
                <li>Pegawai Penyelia = Supervisor <strong>name</strong> (not Staff ID)</li>
                <li>Jabatan = Department</li>
                <li>Lokasi = Current Location</li>
                <li>First row must be headers</li>
                <li><strong>Multiple files supported:</strong> Select multiple department files and they will be merged automatically</li>
              </ul>
            </div>
          </div>

          <div class="alert alert-info mb-6">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="stroke-current shrink-0 w-6 h-6">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <div>
              <div class="font-semibold">Bulk Import - Initial Setup</div>
              <p class="text-sm mt-1">
                Upload CSV/Excel files to set up all departments and locations at once. After initial setup, use the "Add Department" or "Add Location" buttons for individual updates.
              </p>
            </div>
          </div>

          <div class="border border-base-300 rounded-lg p-4 mb-6">
            <h3 class="font-semibold mb-3">Import Mode</h3>
            
            <label class="label cursor-pointer justify-start gap-3 mb-2">
              <input 
                type="radio" 
                name="uploadMode"
                :value="'normal'"
                v-model="uploadMode"
                class="radio radio-primary" 
              />
              <div>
                <span class="label-text font-medium">📦 Initial Setup / Add New</span>
                <p class="text-xs text-base-content/60 mt-1">
                  Upload all departments and locations. Keeps existing data and adds new entries.
                </p>
              </div>
            </label>

            <label class="label cursor-pointer justify-start gap-3">
              <input 
                type="radio" 
                name="uploadMode"
                :value="'overwrite'"
                v-model="uploadMode"
                class="radio radio-warning" 
              />
              <div>
                <span class="label-text font-medium">⚠️ Replace All (Annual Reset)</span>
                <p class="text-xs text-base-content/60 mt-1">
                  DELETE everything and start fresh. Use for annual data refresh only.
                </p>
              </div>
            </label>
          </div>

          <div class="flex justify-center mb-4">
            <input
              ref="bulkFileInput"
              type="file"
              accept=".csv,.xlsx,.xls"
              multiple
              @change="handleBulkFileSelect"
              :disabled="uploadingBulk"
              class="hidden"
            />
            <button @click="triggerBulkFileInput" class="btn btn-primary" :disabled="uploadingBulk">
              {{ selectedBulkFiles.length > 0 ? 'Add More Files' : 'Select Files' }}
            </button>
          </div>

          <div v-if="selectedBulkFiles.length > 0" class="mb-4 space-y-2">
            <div class="flex justify-between items-center mb-2">
              <h4 class="font-semibold text-gray-700">Selected Files ({{ selectedBulkFiles.length }})</h4>
              <button @click="clearBulkFiles" class="btn btn-ghost btn-sm text-error" :disabled="uploadingBulk">
                Clear All
              </button>
            </div>
            <div v-for="(file, index) in selectedBulkFiles" :key="index" class="alert alert-success py-2">
              <div class="flex-1 flex items-center justify-between">
                <div class="flex items-center gap-2">
                  <span class="text-xl">📄</span>
                  <div>
                    <span class="font-semibold">{{ file.name }}</span>
                    <span class="ml-2 text-sm opacity-70">({{ formatFileSize(file.size) }})</span>
                  </div>
                </div>
                <button @click="removeBulkFile(index)" class="btn btn-ghost btn-xs text-error" :disabled="uploadingBulk">
                  ✕
                </button>
              </div>
            </div>
            <div class="alert alert-info">
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="stroke-current shrink-0 w-6 h-6">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
              </svg>
              <span class="text-sm font-medium">All files will be merged and imported together</span>
            </div>
          </div>

          <button
            @click="uploadBulkFiles"
            class="btn btn-success w-full mb-4"
            :disabled="selectedBulkFiles.length === 0 || uploadingBulk"
          >
            <span v-if="uploadingBulk" class="loading loading-spinner"></span>
            {{ uploadingBulk ? 'Uploading...' : `Upload ${selectedBulkFiles.length} File${selectedBulkFiles.length > 1 ? 's' : ''}` }}
          </button>

          <div v-if="bulkUploadResult" class="mt-6">
            <div v-if="bulkUploadResult.success" class="alert alert-success">
              <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
              <div>
                <div class="font-semibold">Import successful!</div>
                <div class="text-sm mt-1">
                  <div>Departments created: {{ bulkUploadResult.departments_created }}</div>
                  <div>Locations created: {{ bulkUploadResult.locations_created }}</div>
                  <div>Total rows processed: {{ bulkUploadResult.total_rows }}</div>
                </div>
              </div>
            </div>
            <div v-else class="alert alert-error">
              <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
              <span>{{ bulkUploadResult.error }}</span>
            </div>
            <div v-if="bulkUploadResult.success" class="text-center mt-4">
              <button @click="closeBulkImportDialog" class="btn btn-primary">View Departments</button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Departments Card (hidden when bulk import is open) -->
    <div v-else class="card bg-base-100 shadow-xl">
      <div class="card-body">
        <div class="flex justify-between items-center mb-4">
          <h2 class="card-title text-xl">Manage Departments</h2>
          <div class="flex gap-2">
            <button class="btn btn-info gap-2" @click="openBulkImportDialog">
              📁 Bulk Import
            </button>
            <button class="btn btn-success gap-2" @click="openAddDialog">
              <span class="text-xl">⊕</span>
              Add Department
            </button>
          </div>
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
                <th class="text-xs font-semibold uppercase tracking-wider">Summary</th>
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
                  <button 
                    @click="openSummaryDialog(dept)" 
                    class="btn btn-sm btn-outline btn-info gap-2"
                    title="Upload Summary Documents"
                  >
                    📄 {{ getSummaryCount(dept.id) }}
                  </button>
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

    <!-- Summary Documents Modal -->
    <Modal 
      v-model="showSummaryDialog"
      :title="`Summary Documents - ${currentDepartment?.name}`"
      size="lg"
    >
      <div class="space-y-4">
        <!-- Upload Section -->
        <div class="border-2 border-dashed border-base-300 rounded-lg p-6">
          <label class="label">
            <span class="label-text font-medium">Upload Documents (PDF, Images, Excel, Word)</span>
          </label>
          <input 
            type="file" 
            @change="handleFileSelect"
            multiple
            accept=".pdf,.jpg,.jpeg,.png,.xlsx,.xls,.doc,.docx"
            class="file-input file-input-bordered w-full"
          />
          <p class="text-sm text-base-content/60 mt-2">
            Select multiple files. Supported: PDF, JPG, PNG, Excel, Word. <strong>Max 10MB per file.</strong>
          </p>
          
          <button 
            v-if="selectedFiles.length > 0"
            @click="uploadSummaryFiles"
            :disabled="uploading"
            class="btn btn-primary w-full mt-4"
          >
            {{ uploading ? 'Uploading...' : `Upload ${selectedFiles.length} file(s)` }}
          </button>
        </div>

        <!-- Existing Files -->
        <div v-if="summaryFiles.length > 0" class="space-y-2">
          <h3 class="font-semibold">Uploaded Documents ({{ summaryFiles.length }})</h3>
          <div class="space-y-2">
            <div 
              v-for="file in summaryFiles" 
              :key="file.id"
              class="flex items-center justify-between p-3 bg-base-200 rounded-lg"
            >
              <div class="flex items-center gap-3">
                <span>{{ getFileIcon(file.filename) }}</span>
                <div>
                  <p class="font-medium">{{ file.filename }}</p>
                  <p class="text-xs text-base-content/60">
                    {{ formatFileSize(file.filesize) }} • {{ formatDate(file.uploaded_at) }}
                  </p>
                </div>
              </div>
              <div class="flex gap-2">
                <a 
                  :href="getFileUrl(file.filepath)" 
                  target="_blank"
                  class="btn btn-sm btn-ghost"
                  title="View/Download"
                >
                  👁️
                </a>
                <button 
                  @click="deleteSummaryFile(file.id)"
                  class="btn btn-sm btn-ghost text-error"
                  title="Delete"
                >
                  🗑️
                </button>
              </div>
            </div>
          </div>
        </div>
        <EmptyState 
          v-else
          icon="📄"
          title="No documents uploaded"
          message="Upload summary documents for this department"
        />
      </div>
      <template #actions>
        <button @click="closeSummaryDialog" class="btn btn-ghost">Close</button>
      </template>
    </Modal>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import api from '../api';
import { useAuth } from '../composables/useAuth';
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

interface SummaryFile {
  id: number;
  department_id: number;
  filename: string;
  filepath: string;
  filesize: number;
  uploaded_at: string;
}

const departments = ref<Department[]>([]);
const loading = ref(false);
const error = ref('');

const showDialog = ref(false);
const showDeleteDialog = ref(false);
const showSummaryDialog = ref(false);
const editingDepartment = ref<Department | null>(null);
const departmentToDelete = ref<Department | null>(null);
const currentDepartment = ref<Department | null>(null);

const summaryFiles = ref<SummaryFile[]>([]);
const selectedFiles = ref<File[]>([]);
const uploading = ref(false);

// Bulk import state
const showBulkImportDialog = ref(false);
const selectedBulkFiles = ref<File[]>([]);
const uploadingBulk = ref(false);
const bulkFileInput = ref<HTMLInputElement | null>(null);
const uploadMode = ref<'normal' | 'overwrite'>('normal');
const bulkUploadResult = ref<any>(null);

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

// Summary file management
function openSummaryDialog(dept: Department) {
  currentDepartment.value = dept;
  showSummaryDialog.value = true;
  loadSummaryFiles(dept.id);
}

function closeSummaryDialog() {
  showSummaryDialog.value = false;
  currentDepartment.value = null;
  selectedFiles.value = [];
  summaryFiles.value = [];
}

function handleFileSelect(event: Event) {
  const target = event.target as HTMLInputElement;
  if (target.files) {
    selectedFiles.value = Array.from(target.files);
  }
}

async function loadSummaryFiles(departmentId: number) {
  try {
    const response = await api.get(`/department-summary.php?department_id=${departmentId}`);
    summaryFiles.value = response.data.files || [];
  } catch (err) {
    console.error('Error loading summary files:', err);
    summaryFiles.value = [];
  }
}

async function uploadSummaryFiles() {
  if (!currentDepartment.value || selectedFiles.value.length === 0) return;
  
  uploading.value = true;
  try {
    const formData = new FormData();
    formData.append('department_id', currentDepartment.value.id.toString());
    
    selectedFiles.value.forEach(file => {
      formData.append('files[]', file);
    });
    
    await api.post('/department-summary.php', formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    });
    
    alert('Files uploaded successfully!');
    selectedFiles.value = [];
    await loadSummaryFiles(currentDepartment.value.id);
  } catch (err: any) {
    console.error('Error uploading files:', err);
    alert('Failed to upload files: ' + (err.response?.data?.error || err.message));
  } finally {
    uploading.value = false;
  }
}

async function deleteSummaryFile(fileId: number) {
  if (!confirm('Are you sure you want to delete this file?')) return;
  
  try {
    await api.delete(`/department-summary.php?id=${fileId}`);
    if (currentDepartment.value) {
      await loadSummaryFiles(currentDepartment.value.id);
    }
  } catch (err) {
    console.error('Error deleting file:', err);
    alert('Failed to delete file. Please try again.');
  }
}

function getSummaryCount(departmentId: number): string {
  // This will be populated when we fetch all summary counts
  // For now, return empty string
  return '';
}

function getFileIcon(filename: string): string {
  const ext = filename.split('.').pop()?.toLowerCase();
  switch (ext) {
    case 'pdf': return '📄';
    case 'jpg':
    case 'jpeg':
    case 'png': return '🖼️';
    case 'xlsx':
    case 'xls': return '📊';
    case 'doc':
    case 'docx': return '📝';
    default: return '📎';
  }
}

function formatFileSize(bytes: number): string {
  if (bytes < 1024) return bytes + ' B';
  if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
  return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
}

function formatDate(dateString: string): string {
  const date = new Date(dateString);
  return date.toLocaleDateString() + ' ' + date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
}

function getFileUrl(filepath: string): string {
  // Assuming files are served from a public uploads directory
  return `http://localhost/inspectable-api/${filepath}`;
}

// Bulk Import Functions
function openBulkImportDialog() {
  showBulkImportDialog.value = true;
  bulkUploadResult.value = null;
}

function closeBulkImportDialog() {
  showBulkImportDialog.value = false;
  selectedBulkFiles.value = [];
  uploadMode.value = 'normal';
  bulkUploadResult.value = null;
}

function triggerBulkFileInput() {
  bulkFileInput.value?.click();
}

function handleBulkFileSelect(event: Event) {
  const target = event.target as HTMLInputElement;
  if (target.files && target.files.length > 0) {
    // Add new files to existing selection
    const newFiles = Array.from(target.files);
    selectedBulkFiles.value = [...selectedBulkFiles.value, ...newFiles];
    // Reset input
    target.value = '';
  }
}

function removeBulkFile(index: number) {
  selectedBulkFiles.value.splice(index, 1);
}

function clearBulkFiles() {
  selectedBulkFiles.value = [];
  if (bulkFileInput.value) {
    bulkFileInput.value.value = '';
  }
}

async function uploadBulkFiles() {
  if (selectedBulkFiles.value.length === 0) return;

  // Confirm based on upload mode
  if (uploadMode.value === 'overwrite') {
    const confirmMsg = '⚠️ REPLACE ALL DATA - ANNUAL RESET\n\n' +
      'This will DELETE ALL:\n' +
      '• Departments\n' +
      '• Locations\n' +
      '• Asset Inspection Data\n\n' +
      'Are you sure you want to continue?';
    
    if (!confirm(confirmMsg)) {
      return;
    }
  } else {
    // Confirm initial setup mode
    const confirmMsg = '📦 BULK IMPORT - INITIAL SETUP\n\n' +
      'This will import departments and locations from your file.\n' +
      'Existing data will be preserved.\n\n' +
      'For individual updates, use the "Add Department" or "Edit" buttons instead.\n\n' +
      'Continue with bulk import?';
    
    if (!confirm(confirmMsg)) {
      return;
    }
  }

  uploadingBulk.value = true;
  try {
    const formData = new FormData();
    
    // Append all files
    selectedBulkFiles.value.forEach((file, index) => {
      formData.append(`files[]`, file);
    });
    
    // Add user info
    const { userId } = useAuth();
    formData.append('uploaded_by', userId.value || 'admin');
    formData.append('notes', `Bulk import from ${selectedBulkFiles.value.length} file(s)`);
    
    // Add mode flags
    if (uploadMode.value === 'overwrite') {
      formData.append('overwrite', 'true');
    } else {
      formData.append('append', 'true');
    }

    const response = await api.post('/department-bulk-import.php', formData, {
      headers: {
        'Content-Type': 'multipart/form-data'
      }
    });

    if (response.data.success) {
      const { assets_created, locations_created, departments_created, errors } = response.data;
      let message = `Import completed!\n\n`;
      if (uploadMode.value === 'overwrite') {
        message += `✓ Previous data cleared\n`;
      } else {
        message += `✓ New data added\n`;
      }
      message += `✓ Departments Created: ${departments_created}\n`;
      message += `✓ Locations Created: ${locations_created}\n`;
      if (assets_created > 0) {
        message += `✓ Assets Imported: ${assets_created}\n`;
      }
      if (errors && errors.length > 0) {
        message += `\n⚠ Errors: ${errors.length}\n`;
        errors.slice(0, 5).forEach((err: string) => {
          message += `  - ${err}\n`;
        });
        if (errors.length > 5) {
          message += `  ... and ${errors.length - 5} more`;
        }
      }
      
      // Set result for display
      bulkUploadResult.value = {
        success: true,
        departments_created,
        locations_created,
        total_rows: response.data.total_rows || 0,
        errors: errors || []
      };
      
      // Refresh departments list
      await fetchDepartments();
    }
  } catch (err: any) {
    console.error('Bulk import error:', err);
    const errorMsg = err.response?.data?.error || 'Failed to import department data';
    bulkUploadResult.value = {
      success: false,
      error: errorMsg
    };
  } finally {
    uploadingBulk.value = false;
  }
}

onMounted(async () => {
  await fetchDepartments();
});
</script>
