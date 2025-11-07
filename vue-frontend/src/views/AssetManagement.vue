<template>
  <div class="asset-management-page">
    <!-- Page Header -->
    <div class="page-header">
  <h1>🗂️ Departments</h1>
      <button v-if="canManageDepartments" @click="showUploadDialog = true" class="btn-upload">
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

    <!-- Upload Dialog -->
    <div v-if="showUploadDialog" class="dialog-overlay" @click.self="showUploadDialog = false">
      <div class="dialog">
        <div class="dialog-header">
          <h3>Upload Assets</h3>
          <button @click="showUploadDialog = false" class="btn-close">&times;</button>
        </div>
        <div class="dialog-body">
          <div class="info-box">
            <p><strong>CSV Format Requirements:</strong></p>
            <ul>
              <li>Columns: Label, Jenis Aset, Pegawai Penyelia, Jabatan, Lokasi</li>
              <li>Department and Location must match existing records</li>
              <li>Supervisor must be a valid staff ID</li>
            </ul>
          </div>

          <div class="upload-area">
            <input 
              type="file" 
              ref="fileInput" 
              accept=".csv" 
              @change="handleFileSelect" 
              style="display: none"
            />
            <button @click="fileInput?.click()" class="btn-select-file">
              📁 Select CSV File
            </button>
            <div v-if="selectedFile" class="file-info">
              Selected: {{ selectedFile.name }}
            </div>
          </div>

          <div class="form-group">
            <label>Notes (Optional)</label>
            <textarea 
              v-model="uploadNotes" 
              rows="3" 
              placeholder="Add any notes about this upload..."
            ></textarea>
          </div>
        </div>
        <div class="dialog-footer">
          <button @click="showUploadDialog = false" class="btn-cancel">Cancel</button>
          <button 
            @click="uploadAssets" 
            :disabled="!selectedFile || uploading" 
            class="btn-save"
          >
            {{ uploading ? 'Uploading...' : 'Upload' }}
          </button>
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
const uploadNotes = ref('');
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

function handleFileSelect(event: Event) {
  const target = event.target as HTMLInputElement;
  if (target.files && target.files.length > 0) {
    selectedFile.value = target.files[0];
  }
}

async function uploadAssets() {
  if (!selectedFile.value) return;
  
  uploading.value = true;
  const formData = new FormData();
  formData.append('file', selectedFile.value);
  formData.append('notes', uploadNotes.value);
  
  try {
    await api.post('/asset-uploads.php', formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    });
    
    alert('Assets uploaded successfully!');
    showUploadDialog.value = false;
    selectedFile.value = null;
    uploadNotes.value = '';
    loadSummary();
  } catch (err: any) {
    alert('Upload failed: ' + (err.response?.data?.message || err.message));
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
</style>
