<template>
  <div class="asset-management-page">
    <!-- Upload Assets Full Page Interface (Modern UI) -->
    <div v-if="showUploadDialog" class="upload-interface-wrapper">
      <div class="page-header">
        <h1>� Upload Asset Inspection Data</h1>
        <button @click="closeUploadDialog" class="btn-back">
          ← Back to Departments
        </button>
      </div>

      <div class="modern-upload-card">
        <div class="text-center mb-6">
          <div class="file-icon-large">📁</div>
          <h3 class="requirements-title">File Requirements</h3>
        </div>

        <div class="info-alert">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="alert-icon">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
          </svg>
          <div class="info-content">
            <ul class="requirements-list">
              <li>Format: Excel (.xlsx, .xls) or CSV</li>
              <li><strong>File size limit: Max 10MB per file, 50MB total</strong></li>
              <li>Required columns (exact names): <strong>Label, Jenis Aset, Pegawai Penyelia, Jabatan, Lokasi</strong></li>
              <li>Label = Asset ID (unique, e.g., KPT/PKS/I/08/406)</li>
              <li>Jenis Aset = Asset Type</li>
              <li>Pegawai Penyelia = Supervisor <strong>name</strong> (not Staff ID)</li>
              <li>Jabatan = Department</li>
              <li>Lokasi = Current Location</li>
              <li>First row must be headers</li>
              <li><strong>Multiple files supported:</strong> Select multiple files and they will be merged automatically</li>
            </ul>
          </div>
        </div>

        <div class="overwrite-section">
          <label class="overwrite-checkbox">
            <input 
              type="checkbox" 
              v-model="overwriteMode"
              class="checkbox-input"
            />
            <div class="overwrite-label">
              <span class="overwrite-title">⚠️ Replace All Data</span>
              <p class="overwrite-description">
                Remove EVERYTHING: All departments, locations, asset inspections, and upload history. Complete database reset.
              </p>
            </div>
          </label>
        </div>

        <div class="upload-controls">
          <input 
            type="file" 
            ref="fileInput" 
            accept=".csv,.xlsx,.xls"
            multiple
            @change="handleFileSelect" 
            class="file-input-hidden"
          />
          <button @click="fileInput?.click()" class="btn-select-modern" :disabled="uploading">
            {{ selectedFiles.length > 0 ? 'Add More Files' : 'Select Files' }}
          </button>
        </div>

        <div v-if="selectedFiles.length > 0" class="selected-files-section">
          <div class="files-header">
            <h4 class="files-count">Selected Files ({{ selectedFiles.length }})</h4>
            <button @click="clearFiles" class="btn-clear-files" :disabled="uploading">
              Clear All
            </button>
          </div>
          <div v-for="(file, index) in selectedFiles" :key="index" class="file-card">
            <div class="file-info-row">
              <div class="file-details">
                <span class="file-emoji">📄</span>
                <div>
                  <span class="file-name">{{ file.name }}</span>
                  <span class="file-size">({{ formatFileSize(file.size) }})</span>
                </div>
              </div>
              <button @click="removeFile(index)" class="btn-remove-file" :disabled="uploading">
                ✕
              </button>
            </div>
          </div>
          <div class="merge-notice">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="notice-icon">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <span>All files will be merged into a single batch upload</span>
          </div>
        </div>

        <div class="notes-section">
          <label class="notes-label">Notes (optional)</label>
          <textarea 
            v-model="uploadNotes" 
            rows="3" 
            placeholder="Add any notes about this upload..."
            :disabled="uploading"
            class="notes-textarea"
          ></textarea>
        </div>

        <button 
          @click="uploadAssets" 
          class="btn-upload-modern"
          :disabled="selectedFiles.length === 0 || uploading"
        >
          <span v-if="uploading" class="loading-spinner"></span>
          {{ uploading ? 'Uploading...' : `Upload ${selectedFiles.length} File${selectedFiles.length > 1 ? 's' : ''}` }}
        </button>

        <div v-if="uploadResult" class="upload-result">
          <div v-if="uploadResult.success" class="result-success">
            <svg xmlns="http://www.w3.org/2000/svg" class="result-icon" fill="none" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <div>
              <div class="result-title">{{ uploadResult.message }}</div>
              <div class="result-details">
                <div v-if="uploadResult.files_processed > 1">Files processed: {{ uploadResult.files_processed }}</div>
                <div>Total records uploaded: {{ uploadResult.total_records }}</div>
              </div>
            </div>
          </div>
          <div v-else class="result-error">
            <svg xmlns="http://www.w3.org/2000/svg" class="result-icon" fill="none" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span>{{ uploadResult.message }}</span>
          </div>
          <div v-if="uploadResult.success" class="result-actions">
            <button @click="closeUploadDialog" class="btn-view-summary">View Departments</button>
          </div>
        </div>

        <div v-if="uploadError" class="upload-error">
          <svg xmlns="http://www.w3.org/2000/svg" class="error-icon" fill="none" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
          <span>{{ uploadError }}</span>
        </div>
      </div>
    </div>

    <!-- Original Departments View (hidden when upload dialog is open) -->
    <div v-else>
    <!-- Page Header -->
    <div class="page-header">
  <h1>🗂️ Departments</h1>
      <button v-if="canManageDepartments" @click="openUploadDialog" class="btn-upload">
        📤 Upload Assets
      </button>
    </div>

    <!-- Stats Section (Totals) -->
    <div class="stats-section">
      <div class="stat-card">
        <div class="stat-value">{{ totalAssets.toLocaleString() }}</div>
        <div class="stat-label">Total Assets</div>
      </div>
      <div class="stat-card">
        <div class="stat-value">{{ totalLocations.toLocaleString() }}</div>
        <div class="stat-label">Total Locations</div>
      </div>
    </div>

    <!-- Filters Section (Department) -->
    <div class="filters-section">
      <div class="filter-group">
        <label>Department</label>
        <select v-model="filters.department">
          <option value="">All Departments</option>
          <option v-for="dept in departments" :key="dept.id" :value="dept.id">
            {{ dept.name }}
          </option>
        </select>
      </div>
    </div>

    <!-- Department Summary Table (Acronym, Name, Totals, Actions) -->
    <div class="assets-section">
      <div class="section-header">
  <h2>Summary by Department</h2>
        <div class="section-actions">
          <span class="table-info">{{ filteredRows.length }} departments</span>
          <button v-if="canManageDepartments" class="btn-add" @click="openAddDialog">
            <span class="btn-icon">⊕</span>
            Add Department
          </button>
        </div>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="loading">
        Loading data...
      </div>

      <!-- Summary Table -->
      <div v-else class="assets-table-container">
        <table class="assets-table">
          <thead>
            <tr>
              <th>Acronym</th>
              <th>Department Name</th>
              <th>Asset Officer(s)</th>
              <th>Total Assets</th>
              <th>Total Locations</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="filteredRows.length === 0">
              <td colspan="5" class="no-data">
                No data available
              </td>
            </tr>
            <tr v-for="row in filteredRows" :key="row.id">
              <td><div class="acronym">{{ row.acronym || '—' }}</div></td>
              <td><div class="department-name">{{ row.name }}</div></td>
              <td class="officers-cell">
                <div v-if="row.asset_officers.length" class="officers-list">
                  <div v-for="off in row.asset_officers" :key="off.id" class="officer-item">
                    <span class="officer-name">{{ off.name }}</span>
                    <span v-if="off.phone" class="officer-phone">{{ off.phone }}</span>
                    <span v-else class="officer-phone no-phone">No phone</span>
                  </div>
                </div>
                <span v-else class="no-officer">—</span>
              </td>
              <td class="total-assets">{{ row.total_assets.toLocaleString() }}</td>
              <td class="uploaded-assets">{{ row.total_locations.toLocaleString() }}</td>
              <td>
                <div v-if="canManageDepartments" class="action-buttons">
                  <button 
                    @click="openEditDialog(row)" 
                    class="btn-edit" title="Edit">✏️</button>
                  <button 
                    @click="confirmDelete(row)" 
                    class="btn-delete" title="Delete">🗑️</button>
                </div>
                <div v-else class="view-only">View Only</div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
    </div>

    <!-- Add/Edit Department Dialog -->
    <div v-if="showDeptDialog" class="dialog-overlay" @click="closeDeptDialog">
      <div class="dialog" @click.stop>
        <div class="dialog-header">
          <h3>{{ editingDepartment ? 'Edit Department' : 'Add New Department' }}</h3>
          <button @click="closeDeptDialog" class="btn-close">✕</button>
        </div>
        <div class="dialog-body">
          <div class="form-group">
            <label for="dept-acronym">Acronym</label>
            <input id="dept-acronym" v-model="deptForm.acronym" type="text" placeholder="e.g., JKM" class="form-input" maxlength="20" />
          </div>
          <div class="form-group">
            <label for="dept-name">Department Name *</label>
            <input id="dept-name" v-model="deptForm.name" type="text" placeholder="e.g., Kejuruteraan Mekanikal" class="form-input" />
          </div>
          <div class="form-group">
            <label for="dept-total-assets">Total Assets *</label>
            <input id="dept-total-assets" v-model.number="deptForm.total_assets" type="number" min="0" placeholder="e.g., 150" class="form-input" />
            <small class="form-hint">Number of assets in this department</small>
          </div>
        </div>
        <div class="dialog-footer">
          <button @click="closeDeptDialog" class="btn-cancel">Cancel</button>
          <button @click="saveDepartment" class="btn-save" :disabled="!isDeptFormValid">{{ editingDepartment ? 'Update' : 'Create' }}</button>
        </div>
      </div>
    </div>

    <!-- Delete Department Dialog -->
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
import { hasPermission } from '../lib/permissions';

const loading = ref(false);
// Permissions / role-based view-only vs CRUD
const userRoles = ref<string[]>([]);
const canManageDepartments = computed(() => hasPermission(userRoles.value, 'canManageDepartments'));
const uploading = ref(false);
const showUploadDialog = ref(false);
const selectedFile = ref<File | null>(null);
const selectedFiles = ref<File[]>([]);
const uploadNotes = ref('');
const uploadResult = ref<any>(null);
const uploadError = ref('');
const overwriteMode = ref(false);
const fileInput = ref<HTMLInputElement | null>(null);

const summary = ref<any[]>([]); // asset summary by department
const departments = ref<any[]>([]);
const users = ref<any[]>([]); // users for role filtering
const locations = ref<any[]>([]);

const filters = ref({
  department: ''
});

// Build a map for quick lookups
const summaryByDept = computed<Record<number, any>>(() => {
  const map: Record<number, any> = {};
  for (const item of summary.value) {
    map[item.department_id] = item;
  }
  return map;
});

// Rows merged from departments + counts
const rows = computed(() => {
  return departments.value.map((d: any) => {
    const s = summaryByDept.value[d.id] || { total_assets: 0 };
    const locCount = locations.value.filter((l: any) => l.department_id === d.id).length;
    // Asset officers: users with role 'Asset Officer' matching department_id
    const officers = users.value.filter((u: any) => {
      return u.department_id === d.id && Array.isArray(u.roles) && u.roles.includes('Asset Officer');
    });
    return {
      id: d.id,
      name: d.name,
      acronym: d.acronym,
      total_assets: Number(s.total_assets) || 0,
      total_locations: locCount,
      asset_officers: officers.map((o: any) => ({ id: o.id, staff_id: o.staff_id, name: o.name, phone: o.phone }))
    };
  });
});

// Apply filters
const filteredRows = computed(() => {
  let list = rows.value;
  if (filters.value.department) {
    const id = parseInt(filters.value.department);
    list = list.filter(r => r.id === id);
  }
  return list;
});

// Top totals
const totalAssets = computed(() => filteredRows.value.reduce((s, r) => s + r.total_assets, 0));
const totalLocations = computed(() => {
  const deptIds = new Set(filteredRows.value.map(r => r.id));
  return locations.value.filter((l: any) => deptIds.has(l.department_id)).length;
});

// Upload Dialog Functions
function openUploadDialog() {
  showUploadDialog.value = true;
  uploadResult.value = null;
  uploadError.value = '';
}

function closeUploadDialog() {
  showUploadDialog.value = false;
  selectedFiles.value = [];
  selectedFile.value = null;
  uploadNotes.value = '';
  uploadResult.value = null;
  uploadError.value = '';
  overwriteMode.value = false;
}

function handleFileSelect(event: Event) {
  const target = event.target as HTMLInputElement;
  if (target.files && target.files.length > 0) {
    // Add new files to existing selection (multiple file support)
    const newFiles = Array.from(target.files);
    selectedFiles.value = [...selectedFiles.value, ...newFiles];
    // Also set selectedFile for backward compatibility
    selectedFile.value = target.files[0];
    // Reset input
    target.value = '';
  }
}

function removeFile(index: number) {
  selectedFiles.value.splice(index, 1);
  if (selectedFiles.value.length === 0) {
    selectedFile.value = null;
  }
}

function clearFiles() {
  selectedFiles.value = [];
  selectedFile.value = null;
  if (fileInput.value) {
    fileInput.value.value = '';
  }
}

function formatFileSize(bytes: number): string {
  if (bytes === 0) return '0 Bytes';
  const k = 1024;
  const sizes = ['Bytes', 'KB', 'MB', 'GB'];
  const i = Math.floor(Math.log(bytes) / Math.log(k));
  return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
}

async function uploadAssets() {
  if (selectedFiles.value.length === 0) return;
  
  // Confirm overwrite if enabled
  if (overwriteMode.value) {
    const confirmMsg = '⚠️ REPLACE ALL DATA - COMPLETE RESET\n\n' +
      'This will DELETE EVERYTHING:\n' +
      '• All Departments\n' +
      '• All Locations\n' +
      '• All Asset Inspection Data\n' +
      '• All Upload History\n\n' +
      'The entire database will be cleared and replaced with your new files.\n\n' +
      'Are you sure you want to continue?';
    
    if (!confirm(confirmMsg)) {
      return;
    }
  }
  
  uploading.value = true;
  uploadError.value = '';
  uploadResult.value = null;
  
  const formData = new FormData();
  
  // Append all files
  selectedFiles.value.forEach((file) => {
    formData.append('files[]', file);
  });
  
  formData.append('notes', uploadNotes.value || `Batch upload of ${selectedFiles.value.length} file(s)`);
  formData.append('user_id', sessionStorage.getItem('userId') || '');
  
  // Add overwrite flag if enabled
  if (overwriteMode.value) {
    formData.append('overwrite', 'true');
  }
  
  try {
    const response = await api.post('/asset-uploads.php', formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    });
    
    uploadResult.value = {
      success: true,
      message: overwriteMode.value ? 'All previous data cleared and new data uploaded successfully!' : 'Upload successful!',
      files_processed: selectedFiles.value.length,
      total_records: response.data.assets_created || 0
    };
    
    // Clear form
    selectedFiles.value = [];
    selectedFile.value = null;
    uploadNotes.value = '';
    overwriteMode.value = false;
    
    // Refresh data
    loadSummary();
  } catch (err: any) {
    uploadResult.value = {
      success: false,
      message: err.response?.data?.error || err.message || 'Upload failed'
    };
  } finally {
    uploading.value = false;
  }
}

// Load departments, locations, and asset summary
async function loadSummary() {
  loading.value = true;
  try {
    const [summaryResponse, deptResponse, locResponse, usersResponse] = await Promise.all([
      api.get('/asset-summary.php', { params: { action: 'summary' } }),
      api.get('/departments.php'),
      api.get('/locations.php'),
      api.get('/users.php')
    ]);
    
    departments.value = deptResponse.data;
    locations.value = locResponse.data;
    users.value = usersResponse.data;
    
    summary.value = summaryResponse.data.summary || [];
  } catch (err: any) {
    console.error('Failed to load data:', err);
  } finally {
    loading.value = false;
  }
}

onMounted(() => {
  // Load roles from session storage
  try {
    const stored = sessionStorage.getItem('userRoles');
    userRoles.value = stored ? JSON.parse(stored) : [];
  } catch {
    userRoles.value = [];
  }
  loadSummary();
});

// ---- Department CRUD (Add/Edit/Delete) ----
interface DeptRow {
  id: number;
  name: string;
  acronym?: string;
  total_assets?: number;
  total_locations?: number;
  asset_officers?: any[];
}

const showDeptDialog = ref(false);
const showDeleteDialog = ref(false);
const editingDepartment = ref<DeptRow | null>(null);
const departmentToDelete = ref<DeptRow | null>(null);

const deptForm = ref<{ name: string; acronym: string; total_assets: number }>({ name: '', acronym: '', total_assets: 0 });
const isDeptFormValid = computed(() => deptForm.value.name.trim().length > 0);

function openAddDialog() {
  if (!canManageDepartments.value) return; // guard
  editingDepartment.value = null;
  deptForm.value = { name: '', acronym: '', total_assets: 0 };
  showDeptDialog.value = true;
}

function openEditDialog(row: DeptRow) {
  if (!canManageDepartments.value) return; // guard
  editingDepartment.value = { id: row.id, name: row.name, acronym: row.acronym } as DeptRow;
  deptForm.value = { name: row.name, acronym: row.acronym || '', total_assets: row.total_assets || 0 };
  showDeptDialog.value = true;
}

function closeDeptDialog() {
  showDeptDialog.value = false;
  editingDepartment.value = null;
}

function confirmDelete(row: DeptRow) {
  if (!canManageDepartments.value) return; // guard
  departmentToDelete.value = row;
  showDeleteDialog.value = true;
}

function closeDeleteDialog() {
  showDeleteDialog.value = false;
  departmentToDelete.value = null;
}

async function saveDepartment() {
  if (!canManageDepartments.value) return;
  if (!isDeptFormValid.value) return;
  const data = { 
    name: deptForm.value.name.trim(), 
    acronym: deptForm.value.acronym.trim() || null,
    total_assets: deptForm.value.total_assets || 0
  };
  try {
    if (editingDepartment.value) {
      await api.put(`/departments.php?id=${editingDepartment.value.id}`, data);
    } else {
      await api.post('/departments.php', data);
    }
    closeDeptDialog();
    await loadSummary();
  } catch (err) {
    console.error('Error saving department', err);
    alert('Failed to save department.');
  }
}

async function deleteDepartment() {
  if (!canManageDepartments.value) return;
  if (!departmentToDelete.value) return;
  try {
    await api.delete(`/departments.php?id=${departmentToDelete.value.id}`);
    closeDeleteDialog();
    await loadSummary();
  } catch (err) {
    console.error('Error deleting department', err);
    alert('Failed to delete department.');
  }
}
</script>

<style scoped>
.asset-management-page {
  padding: 2rem;
  max-width: 1400px;
  margin: 0 auto;
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 2rem;
}

.page-header h1 {
  font-size: 2rem;
  color: #1f2937;
  margin: 0;
}

.btn-upload {
  padding: 0.75rem 1.5rem;
  background: var(--teal);
  color: white;
  border: none;
  border-radius: 6px;
  font-weight: 600;
  cursor: pointer;
  transition: background 0.2s;
}

.btn-upload:hover {
  background: var(--emerald);
}

.stats-section {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1.5rem;
  margin-bottom: 2rem;
}

.stat-card {
  background: white;
  padding: 1.5rem;
  border-radius: 8px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  text-align: center;
  border-left: 4px solid var(--teal);
}

.stat-value {
  font-size: 2.5rem;
  font-weight: 700;
  color: #1f2937;
}

.stat-label {
  font-size: 0.9rem;
  color: #6b7280;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  margin-top: 0.5rem;
}

.filters-section {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1rem;
  margin-bottom: 2rem;
  padding: 1.5rem;
  background: white;
  border-radius: 8px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.filter-group,
.search-group {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.filter-group label {
  font-weight: 600;
  color: #374151;
  font-size: 0.9rem;
}

.filter-group select,
.search-group input {
  padding: 0.5rem;
  border: 1px solid #d1d5db;
  border-radius: 6px;
  font-size: 0.9rem;
}

.assets-section {
  background: white;
  border-radius: 8px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  padding: 1.5rem;
}

.section-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.5rem;
}

.section-header h2 {
  font-size: 1.5rem;
  color: #1f2937;
  margin: 0;
}

.section-actions {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.btn-add {
  padding: 0.5rem 1rem;
  background: var(--teal);
  color: #fff;
  border: none;
  border-radius: 6px;
  font-weight: 600;
  cursor: pointer;
}

.btn-add:hover { background: var(--emerald); }

.table-info {
  color: #6b7280;
  font-size: 0.9rem;
}

.loading {
  text-align: center;
  padding: 3rem;
  color: #6b7280;
}

.assets-table-container {
  overflow-x: auto;
  max-height: 600px;
  overflow-y: auto;
}

.assets-table {
  width: 100%;
  border-collapse: collapse;
}

.assets-table thead {
  position: sticky;
  top: 0;
  background: #f9fafb;
  z-index: 10;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
}

.assets-table th {
  padding: 1rem;
  text-align: left;
  font-weight: 600;
  color: #374151;
  border-bottom: 2px solid #e5e7eb;
}

.assets-table td {
  padding: 1rem;
  border-bottom: 1px solid #e5e7eb;
  color: #4b5563;
}

.assets-table tbody tr:hover {
  background: #f9fafb;
}

.no-data {
  text-align: center;
  color: #6b7280;
  font-style: italic;
}

.department-name {
  font-weight: 600;
  color: #1f2937;
  font-size: 0.9375rem;
}

.acronym { font-weight: 600; color: #374151; }

.officers-cell { min-width: 200px; }
.officers-list { display: flex; flex-direction: column; gap: 8px; }
.officer-item { display: flex; flex-direction: column; gap: 2px; }
.officer-name { font-weight: 600; color: #374151; font-size: 0.875rem; }
.officer-phone { color: #6b7280; font-size: 0.75rem; }
.officer-phone.no-phone { font-style: italic; color: #9ca3af; }
.no-officer { color: #9ca3af; font-style: italic; }

.action-buttons { display: flex; gap: 0.5rem; }
.btn-edit, .btn-delete { padding: 0.375rem 0.75rem; border: none; background: transparent; cursor: pointer; border-radius: 4px; }
.btn-edit:hover { background: #dbeafe; }
.btn-delete:hover { background: #fee2e2; }

.total-assets,
.uploaded-assets {
  font-weight: 500;
  color: #374151;
}

.text-success {
  color: #10b981;
  font-weight: 600;
}

.text-warning {
  color: #f59e0b;
  font-weight: 600;
}

.progress-cell {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.progress-bar {
  flex: 1;
  height: 8px;
  background: #e5e7eb;
  border-radius: 4px;
  overflow: hidden;
  min-width: 100px;
}

.progress-fill {
  height: 100%;
  background: linear-gradient(90deg, var(--teal), var(--emerald));
  transition: width 0.3s ease;
}

.progress-text {
  font-size: 0.875rem;
  font-weight: 600;
  color: #374151;
  min-width: 45px;
  text-align: right;
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
}

.dialog {
  background: white;
  border-radius: 8px;
  width: 90%;
  max-width: 600px;
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
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
  font-size: 1.25rem;
  color: #1f2937;
}

.btn-close {
  background: none;
  border: none;
  font-size: 1.5rem;
  cursor: pointer;
  color: #6b7280;
  padding: 0.25rem;
  line-height: 1;
}

.btn-close:hover {
  color: #1f2937;
}

.dialog-body {
  padding: 1.5rem;
}

.info-box {
  background: #eff6ff;
  border: 1px solid #bfdbfe;
  border-radius: 6px;
  padding: 1rem;
  margin-bottom: 1.5rem;
}

.info-box p {
  margin: 0.5rem 0;
  color: #1e40af;
}

.info-box ul {
  margin: 0.5rem 0;
  padding-left: 1.5rem;
  color: #1e40af;
}

.upload-area {
  text-align: center;
  margin-bottom: 1.5rem;
}

.btn-select-file {
  padding: 0.75rem 1.5rem;
  background: #f3f4f6;
  color: #374151;
  border: 2px dashed #d1d5db;
  border-radius: 6px;
  cursor: pointer;
  font-weight: 600;
  transition: all 0.2s;
}

.btn-select-file:hover {
  background: #e5e7eb;
  border-color: var(--teal);
}

.file-info {
  margin-top: 1rem;
  padding: 0.75rem;
  background: #f9fafb;
  border-radius: 6px;
  color: #374151;
}

.form-group {
  margin-bottom: 1rem;
}

.form-group label {
  display: block;
  font-weight: 600;
  color: #374151;
  margin-bottom: 0.5rem;
}

.form-group textarea {
  width: 100%;
  padding: 0.5rem;
  border: 1px solid #d1d5db;
  border-radius: 6px;
  font-family: inherit;
  resize: vertical;
}

.form-hint {
  display: block;
  margin-top: 0.25rem;
  font-size: 0.75rem;
  color: #6b7280;
  font-style: italic;
}

.dialog-footer {
  display: flex;
  justify-content: flex-end;
  gap: 0.75rem;
  padding: 1.5rem;
  border-top: 1px solid #e5e7eb;
}

.btn-cancel {
  padding: 0.625rem 1.25rem;
  background: white;
  color: #374151;
  border: 1px solid #d1d5db;
  border-radius: 6px;
  cursor: pointer;
  font-weight: 500;
}

.btn-cancel:hover {
  background: #f9fafb;
}

.btn-save {
  padding: 0.625rem 1.25rem;
  background: var(--teal);
  color: white;
  border: none;
  border-radius: 6px;
  cursor: pointer;
  font-weight: 600;
}

.btn-save:hover:not(:disabled) {
  background: var(--emerald);
}

.btn-save:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

/* Modern Upload Interface Styles */
.upload-interface-wrapper {
  padding: 2rem;
  max-width: 1200px;
  margin: 0 auto;
}

.modern-upload-card {
  background: white;
  padding: 3rem;
  border-radius: 12px;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.file-icon-large {
  font-size: 4rem;
  margin-bottom: 1rem;
}

.requirements-title {
  font-size: 1.5rem;
  font-weight: 600;
  color: #374151;
  margin-bottom: 2rem;
}

.info-alert {
  background: #dbeafe;
  border-left: 4px solid #3b82f6;
  padding: 1.5rem;
  border-radius: 8px;
  margin-bottom: 2rem;
  display: flex;
  gap: 1rem;
}

.alert-icon {
  width: 24px;
  height: 24px;
  flex-shrink: 0;
  stroke: #3b82f6;
}

.info-content {
  flex: 1;
}

.requirements-list {
  list-style: disc;
  padding-left: 1.5rem;
  line-height: 1.8;
}

.requirements-list li {
  margin-bottom: 0.5rem;
}

.overwrite-section {
  margin-bottom: 2rem;
  padding: 1rem;
  background: #fef3c7;
  border-left: 4px solid #f59e0b;
  border-radius: 8px;
}

.overwrite-checkbox {
  display: flex;
  align-items: start;
  gap: 1rem;
  cursor: pointer;
}

.checkbox-input {
  margin-top: 0.25rem;
  width: 20px;
  height: 20px;
  cursor: pointer;
  accent-color: #f59e0b;
}

.overwrite-label {
  flex: 1;
}

.overwrite-title {
  font-weight: 600;
  color: #92400e;
  font-size: 1rem;
}

.overwrite-description {
  margin-top: 0.25rem;
  font-size: 0.875rem;
  color: #78350f;
  line-height: 1.5;
}

.upload-controls {
  text-align: center;
  margin-bottom: 2rem;
}

.file-input-hidden {
  display: none;
}

.btn-select-modern {
  padding: 1rem 2rem;
  background: #0d9488;
  color: white;
  border: none;
  border-radius: 8px;
  font-size: 1rem;
  font-weight: 600;
  cursor: pointer;
  transition: background 0.2s;
}

.btn-select-modern:hover:not(:disabled) {
  background: #0f766e;
}

.btn-select-modern:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.selected-files-section {
  margin-bottom: 2rem;
}

.files-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1rem;
}

.files-count {
  font-size: 1rem;
  font-weight: 600;
  color: #374151;
}

.btn-clear-files {
  padding: 0.5rem 1rem;
  background: transparent;
  color: #ef4444;
  border: none;
  cursor: pointer;
  font-weight: 600;
}

.btn-clear-files:hover:not(:disabled) {
  text-decoration: underline;
}

.file-card {
  background: #d1fae5;
  border-radius: 8px;
  padding: 0.75rem 1rem;
  margin-bottom: 0.5rem;
}

.file-info-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.file-details {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  flex: 1;
}

.file-emoji {
  font-size: 1.5rem;
}

.file-name {
  font-weight: 600;
  color: #065f46;
}

.file-size {
  color: #059669;
  font-size: 0.875rem;
}

.btn-remove-file {
  padding: 0.25rem 0.5rem;
  background: transparent;
  color: #ef4444;
  border: none;
  cursor: pointer;
  font-size: 1.25rem;
  font-weight: 700;
}

.btn-remove-file:hover:not(:disabled) {
  color: #dc2626;
}

.merge-notice {
  background: #dbeafe;
  padding: 0.75rem 1rem;
  border-radius: 8px;
  display: flex;
  align-items: center;
  gap: 0.75rem;
  margin-top: 1rem;
}

.notice-icon {
  width: 20px;
  height: 20px;
  flex-shrink: 0;
  stroke: #3b82f6;
}

.notes-section {
  margin-bottom: 2rem;
}

.notes-label {
  display: block;
  font-weight: 600;
  color: #374151;
  margin-bottom: 0.5rem;
}

.notes-textarea {
  width: 100%;
  padding: 0.75rem;
  border: 1px solid #d1d5db;
  border-radius: 8px;
  font-family: inherit;
  resize: vertical;
}

.notes-textarea:focus {
  outline: none;
  border-color: #0d9488;
  box-shadow: 0 0 0 2px rgba(13, 148, 136, 0.2);
}

.btn-upload-modern {
  width: 100%;
  padding: 1rem;
  background: #10b981;
  color: white;
  border: none;
  border-radius: 8px;
  font-size: 1rem;
  font-weight: 600;
  cursor: pointer;
  transition: background 0.2s;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
}

.btn-upload-modern:hover:not(:disabled) {
  background: #059669;
}

.btn-upload-modern:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.loading-spinner {
  width: 20px;
  height: 20px;
  border: 2px solid rgba(255, 255, 255, 0.3);
  border-top-color: white;
  border-radius: 50%;
  animation: spin 1s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.upload-result {
  margin-top: 2rem;
}

.result-success {
  background: #d1fae5;
  border-left: 4px solid #10b981;
  padding: 1.5rem;
  border-radius: 8px;
  display: flex;
  gap: 1rem;
}

.result-error {
  background: #fee2e2;
  border-left: 4px solid #ef4444;
  padding: 1.5rem;
  border-radius: 8px;
  display: flex;
  gap: 1rem;
  align-items: center;
}

.result-icon {
  width: 24px;
  height: 24px;
  flex-shrink: 0;
  stroke: currentColor;
}

.result-title {
  font-weight: 600;
  font-size: 1.125rem;
  margin-bottom: 0.5rem;
}

.result-details {
  font-size: 0.875rem;
  line-height: 1.6;
}

.result-actions {
  margin-top: 1rem;
  text-align: center;
}

.btn-view-summary {
  padding: 0.75rem 2rem;
  background: #0d9488;
  color: white;
  border: none;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
}

.btn-view-summary:hover {
  background: #0f766e;
}

.upload-error {
  background: #fee2e2;
  border-left: 4px solid #ef4444;
  padding: 1.5rem;
  border-radius: 8px;
  display: flex;
  gap: 1rem;
  align-items: center;
  margin-top: 2rem;
}

.error-icon {
  width: 24px;
  height: 24px;
  flex-shrink: 0;
  stroke: #ef4444;
}

.btn-back {
  padding: 0.75rem 1.5rem;
  background: transparent;
  color: #0d9488;
  border: 1px solid #0d9488;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-back:hover {
  background: #0d9488;
  color: white;
}
</style>
