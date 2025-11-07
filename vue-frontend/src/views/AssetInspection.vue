<template>
  <div class="asset-inspection-page">
    <div class="page-header">
      <h1>Asset Inspection Summary</h1>
      <div class="header-actions">
        <router-link to="/asset-upload" class="btn-upload">
          📤 Upload New Data
        </router-link>
      </div>
    </div>

    <!-- Filters -->
    <div class="filters-section">
      <div class="filter-group">
        <label for="department-filter">Department:</label>
        <select id="department-filter" v-model="selectedDepartment" @change="loadData">
          <option value="">All Departments</option>
          <option v-for="dept in departments" :key="dept.id" :value="dept.id">
            {{ dept.name }}
          </option>
        </select>
      </div>

      <div class="filter-group">
        <label for="batch-filter">Upload Batch:</label>
        <select id="batch-filter" v-model="selectedBatch" @change="loadAssets">
          <option value="">All Batches</option>
          <option v-for="batch in batches" :key="batch.id" :value="batch.id">
            {{ batch.filename }} ({{ formatDate(batch.upload_date) }})
          </option>
        </select>
      </div>

      <div class="filter-group">
        <label for="inspected-filter">Status:</label>
        <select id="inspected-filter" v-model="inspectedFilter" @change="loadAssets">
          <option value="">All Assets</option>
          <option value="0">Not Inspected</option>
          <option value="1">Inspected</option>
        </select>
      </div>

      <div class="search-group">
        <input
          v-model="searchQuery"
          @input="debouncedSearch"
          type="text"
          placeholder="Search by label, asset type, officer, location..."
        />
      </div>
    </div>

    <!-- Summary Statistics -->
    <div v-if="loading" class="loading">Loading summary...</div>
    <div v-else class="summary-section">
      <h2>Department Summary</h2>
      
      <!-- Overall Stats Card -->
      <div class="stats-card overall-stats">
        <h3>Overall Statistics</h3>
        <div class="stats-grid">
          <div class="stat-item">
            <div class="stat-value">{{ overallStats.total_assets }}</div>
            <div class="stat-label">Total Assets</div>
          </div>
          <div class="stat-item success">
            <div class="stat-value">{{ overallStats.assets_inspected }}</div>
            <div class="stat-label">Inspected</div>
          </div>
          <div class="stat-item warning">
            <div class="stat-value">{{ overallStats.assets_not_inspected }}</div>
            <div class="stat-label">Not Inspected</div>
          </div>
          <div class="stat-item info">
            <div class="stat-value">{{ overallStats.percentage_inspected }}%</div>
            <div class="stat-label">Completion Rate</div>
          </div>
        </div>
      </div>

      <!-- Department Table -->
      <div class="table-wrapper">
        <table class="summary-table">
          <thead>
            <tr>
              <th>Department</th>
              <th>Total Assets</th>
              <th>Inspected</th>
              <th>Not Inspected</th>
              <th>Percentage</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="row in summary" :key="row.department_id">
              <td><strong>{{ row.department_name }}</strong></td>
              <td>{{ row.total_assets }}</td>
              <td class="text-success">{{ row.assets_inspected }}</td>
              <td class="text-warning">{{ row.assets_not_inspected }}</td>
              <td>
                <div class="percentage-cell">
                  <div class="percentage-bar">
                    <div 
                      class="percentage-fill" 
                      :style="{ width: row.percentage_inspected + '%' }"
                    ></div>
                  </div>
                  <span>{{ row.percentage_inspected }}%</span>
                </div>
              </td>
            </tr>
            <tr v-if="summary.length === 0">
              <td colspan="5" class="no-data">No data available</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Asset Details List -->
    <div class="assets-section">
      <h2>Asset Details</h2>
      <div v-if="loadingAssets" class="loading">Loading assets...</div>
      <div v-else class="assets-table-container">
        <table class="assets-table">
          <thead>
            <tr>
              <th>Asset ID</th>
              <th>Asset Type</th>
              <th>Supervisor</th>
              <th>Department</th>
              <th>Location</th>
              <th>Status</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="asset in assets" :key="asset.id" :class="{ inspected: asset.is_inspected }">
              <td><strong>{{ asset.label }}</strong></td>
              <td>{{ asset.jenis_aset }}</td>
              <td>{{ asset.pegawai_penempatan }}</td>
              <td>{{ asset.bahagian }}</td>
              <td>{{ asset.lokasi_terkini }}</td>
              <td>
                <span v-if="asset.is_inspected" class="badge badge-success">
                  ✓ Inspected
                </span>
                <span v-else class="badge badge-warning">
                  ⚠ Not Inspected
                </span>
              </td>
              <td>
                <button
                  v-if="!asset.is_inspected"
                  @click="markAsInspected(asset.id)"
                  class="btn-mark"
                  title="Mark as inspected"
                >
                  ✓ Mark
                </button>
                <button
                  v-else
                  @click="markAsNotInspected(asset.id)"
                  class="btn-unmark"
                  title="Mark as not inspected"
                >
                  ✗ Unmark
                </button>
              </td>
            </tr>
            <tr v-if="assets.length === 0">
              <td colspan="7" class="no-data">No assets found</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from 'vue';
import api from '../api';

const loading = ref(false);
const loadingAssets = ref(false);
const summary = ref<any[]>([]);
const overallStats = ref({
  total_assets: 0,
  assets_inspected: 0,
  assets_not_inspected: 0,
  percentage_inspected: 0,
});
const assets = ref<any[]>([]);
const departments = ref<any[]>([]);
const batches = ref<any[]>([]);
const selectedDepartment = ref('');
const selectedBatch = ref('');
const inspectedFilter = ref('');
const searchQuery = ref('');

let searchTimeout: any = null;

const userId = sessionStorage.getItem('userId');

async function loadDepartments() {
  try {
    const response = await api.get('/departments.php');
    departments.value = response.data;
  } catch (err) {
    console.error('Failed to load departments:', err);
  }
}

async function loadBatches() {
  try {
    const response = await api.get('/asset-uploads.php');
    batches.value = response.data;
  } catch (err) {
    console.error('Failed to load batches:', err);
  }
}

async function loadSummary() {
  loading.value = true;
  try {
    const params: any = { action: 'summary' };
    if (selectedDepartment.value) {
      params.department_id = selectedDepartment.value;
    }

    const response = await api.get('/asset-summary.php', { params });
    summary.value = response.data.summary;
    overallStats.value = response.data.overall;
  } catch (err) {
    console.error('Failed to load summary:', err);
  } finally {
    loading.value = false;
  }
}

async function loadAssets() {
  loadingAssets.value = true;
  try {
    const params: any = { action: 'assets' };
    if (selectedDepartment.value) params.department_id = selectedDepartment.value;
    if (selectedBatch.value) params.batch_id = selectedBatch.value;
    if (inspectedFilter.value !== '') params.inspected = inspectedFilter.value;
    if (searchQuery.value) params.search = searchQuery.value;

    const response = await api.get('/asset-summary.php', { params });
    assets.value = response.data;
  } catch (err) {
    console.error('Failed to load assets:', err);
  } finally {
    loadingAssets.value = false;
  }
}

async function loadData() {
  await Promise.all([loadSummary(), loadAssets()]);
}

function debouncedSearch() {
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => {
    loadAssets();
  }, 500);
}

async function markAsInspected(assetId: number) {
  try {
    await api.put(`/asset-summary.php?id=${assetId}`, {
      is_inspected: 1,
      inspected_by: userId,
      inspected_date: new Date().toISOString().split('T')[0],
    });
    await loadData();
  } catch (err: any) {
    alert(err.response?.data?.error || 'Failed to update asset');
  }
}

async function markAsNotInspected(assetId: number) {
  try {
    await api.put(`/asset-summary.php?id=${assetId}`, {
      is_inspected: 0,
      inspected_by: null,
      inspected_date: null,
    });
    await loadData();
  } catch (err: any) {
    alert(err.response?.data?.error || 'Failed to update asset');
  }
}

function formatDate(dateStr: string): string {
  return new Date(dateStr).toLocaleDateString('en-MY', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
  });
}

onMounted(async () => {
  await Promise.all([loadDepartments(), loadBatches(), loadData()]);
});
</script>

<style scoped>
.asset-inspection-page {
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
  text-decoration: none;
  border-radius: 6px;
  font-weight: 600;
  transition: background 0.2s;
}

.btn-upload:hover {
  background: var(--emerald);
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

.filter-group {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.filter-group label {
  font-weight: 600;
  color: #374151;
  font-size: 0.9rem;
}

.filter-group select {
  padding: 0.5rem;
  border: 1px solid #d1d5db;
  border-radius: 6px;
  font-size: 0.9rem;
}

.search-group {
  display: flex;
  align-items: flex-end;
}

.search-group input {
  width: 100%;
  padding: 0.5rem;
  border: 1px solid #d1d5db;
  border-radius: 6px;
}

.loading {
  text-align: center;
  padding: 3rem;
  color: #6b7280;
  font-size: 1.1rem;
}

.summary-section {
  margin-bottom: 3rem;
}

.summary-section h2 {
  font-size: 1.5rem;
  color: #1f2937;
  margin: 0 0 1.5rem 0;
}

.stats-card {
  background: white;
  border-radius: 8px;
  padding: 2rem;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  margin-bottom: 2rem;
}

.stats-card h3 {
  margin: 0 0 1.5rem 0;
  color: #374151;
  font-size: 1.2rem;
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1.5rem;
}

.stat-item {
  text-align: center;
  padding: 1.5rem;
  background: #f9fafb;
  border-radius: 8px;
  border-left: 4px solid #6b7280;
}

.stat-item.success {
  border-left-color: #10b981;
}

.stat-item.warning {
  border-left-color: #f59e0b;
}

.stat-item.info {
  border-left-color: var(--teal);
}

.stat-value {
  font-size: 2.5rem;
  font-weight: 700;
  color: #1f2937;
  margin-bottom: 0.5rem;
}

.stat-label {
  font-size: 0.9rem;
  color: #6b7280;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.table-wrapper {
  overflow-x: auto;
  background: white;
  border-radius: 8px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.summary-table,
.assets-table {
  width: 100%;
  border-collapse: collapse;
}

.summary-table thead,
.assets-table thead {
  background: #f9fafb;
}

.summary-table th,
.assets-table th {
  padding: 1rem;
  text-align: left;
  font-weight: 600;
  color: #374151;
  border-bottom: 2px solid #e5e7eb;
}

.summary-table td,
.assets-table td {
  padding: 1rem;
  border-bottom: 1px solid #e5e7eb;
  color: #4b5563;
}

.summary-table tbody tr:hover,
.assets-table tbody tr:hover {
  background: #f9fafb;
}

.text-success {
  color: #10b981;
  font-weight: 600;
}

.text-warning {
  color: #f59e0b;
  font-weight: 600;
}

.percentage-cell {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.percentage-bar {
  flex: 1;
  height: 8px;
  background: #e5e7eb;
  border-radius: 4px;
  overflow: hidden;
}

.percentage-fill {
  height: 100%;
  background: linear-gradient(90deg, var(--teal), var(--emerald));
  transition: width 0.3s ease;
}

.no-data {
  text-align: center;
  color: #6b7280;
  font-style: italic;
}

.assets-section {
  margin-top: 3rem;
}

.assets-section h2 {
  font-size: 1.5rem;
  color: #1f2937;
  margin: 0 0 1.5rem 0;
}

.assets-table-container {
  overflow-x: auto;
  background: white;
  border-radius: 8px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  max-height: 600px;
  overflow-y: auto;
  position: relative;
}

.assets-table thead {
  position: sticky;
  top: 0;
  background: #f9fafb;
  z-index: 10;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
}

.assets-table tr.inspected {
  opacity: 0.6;
}

.badge {
  display: inline-block;
  padding: 0.25rem 0.75rem;
  border-radius: 12px;
  font-size: 0.85rem;
  font-weight: 600;
}

.badge-success {
  background: #d1fae5;
  color: #065f46;
}

.badge-warning {
  background: #fef3c7;
  color: #92400e;
}

.btn-mark,
.btn-unmark {
  padding: 0.4rem 0.8rem;
  border: none;
  border-radius: 4px;
  font-size: 0.85rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-mark {
  background: #10b981;
  color: white;
}

.btn-mark:hover {
  background: #059669;
}

.btn-unmark {
  background: #f59e0b;
  color: white;
}

.btn-unmark:hover {
  background: #d97706;
}
</style>
