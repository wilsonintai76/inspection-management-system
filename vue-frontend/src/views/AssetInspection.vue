<template>
  <div class="p-6 max-w-7xl mx-auto">
    <PageHeader 
      icon="📊" 
      title="Asset Inspection Summary"
      subtitle="Track inspection progress and manage asset status"
    >
      <template #actions>
        <router-link to="/asset-upload" class="btn btn-primary">
          📤 Upload New Data
        </router-link>
      </template>
    </PageHeader>

    <!-- Filters -->
    <div class="card bg-base-100 shadow-xl mb-6">
      <div class="card-body">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
          <div class="form-control">
            <label class="label">
              <span class="label-text font-medium">Department</span>
            </label>
            <select id="department-filter" v-model="selectedDepartment" @change="loadData" class="select select-bordered w-full">
              <option value="">All Departments</option>
              <option v-for="dept in departments" :key="dept.id" :value="dept.id">
                {{ dept.name }}
              </option>
            </select>
          </div>

          <div class="form-control">
            <label class="label">
              <span class="label-text font-medium">Upload Batch</span>
            </label>
            <select id="batch-filter" v-model="selectedBatch" @change="loadAssets" class="select select-bordered w-full">
              <option value="">All Batches</option>
              <option v-for="batch in batches" :key="batch.id" :value="batch.id">
                {{ batch.filename }} ({{ formatDate(batch.upload_date) }})
              </option>
            </select>
          </div>

          <div class="form-control">
            <label class="label">
              <span class="label-text font-medium">Status</span>
            </label>
            <select id="inspected-filter" v-model="inspectedFilter" @change="loadAssets" class="select select-bordered w-full">
              <option value="">All Assets</option>
              <option value="0">Not Inspected</option>
              <option value="1">Inspected</option>
            </select>
          </div>

          <div class="form-control">
            <label class="label">
              <span class="label-text font-medium">Search</span>
            </label>
            <input
              v-model="searchQuery"
              @input="debouncedSearch"
              type="text"
              placeholder="Search assets..."
              class="input input-bordered w-full"
            />
          </div>
        </div>
      </div>
    </div>

    <!-- Summary Statistics -->
    <LoadingSpinner v-if="loading" message="Loading summary..." />
    <div v-else class="mb-8">
      <h2 class="text-2xl font-semibold text-gray-900 mb-4">Department Summary</h2>
      
      <!-- Overall Stats Card -->
      <div class="card bg-base-100 shadow-xl mb-6">
        <div class="card-body">
          <h3 class="card-title text-lg mb-4">Overall Statistics</h3>
          <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <StatsCard 
              icon="📦"
              title="Total Assets"
              :value="String(overallStats.total_assets)"
              description="All uploaded assets"
            />
            <StatsCard 
              icon="✅"
              title="Inspected"
              :value="String(overallStats.assets_inspected)"
              description="Completed inspections"
              variant="success"
            />
            <StatsCard 
              icon="⚠️"
              title="Not Inspected"
              :value="String(overallStats.assets_not_inspected)"
              description="Pending inspections"
              variant="warning"
            />
            <StatsCard 
              icon="📈"
              title="Completion Rate"
              :value="`${overallStats.percentage_inspected}%`"
              description="Overall progress"
              variant="info"
            />
          </div>
        </div>
      </div>

      <!-- Department Table -->
      <div class="card bg-base-100 shadow-xl">
        <div class="card-body">
          <div class="overflow-x-auto">
            <EmptyState 
              v-if="summary.length === 0"
              icon="📊"
              title="No summary data"
              message="Upload assets to see department statistics"
            />
            <table v-else class="table table-zebra w-full">
              <thead>
                <tr>
                  <th class="text-xs font-semibold uppercase">Department</th>
                  <th class="text-xs font-semibold uppercase">Total Assets</th>
                  <th class="text-xs font-semibold uppercase">Inspected</th>
                  <th class="text-xs font-semibold uppercase">Not Inspected</th>
                  <th class="text-xs font-semibold uppercase">Percentage</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="row in summary" :key="row.department_id">
                  <td class="font-semibold">{{ row.department_name }}</td>
                  <td>{{ row.total_assets }}</td>
                  <td class="text-success font-semibold">{{ row.assets_inspected }}</td>
                  <td class="text-warning font-semibold">{{ row.assets_not_inspected }}</td>
                  <td>
                    <div class="flex items-center gap-3">
                      <progress 
                        class="progress progress-primary w-24" 
                        :value="row.percentage_inspected" 
                        max="100"
                      ></progress>
                      <span class="font-medium">{{ row.percentage_inspected }}%</span>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

    <!-- Asset Details List -->
    <div class="card bg-base-100 shadow-xl">
      <div class="card-body">
        <h2 class="card-title text-xl mb-4">Asset Details</h2>
        
        <LoadingSpinner v-if="loadingAssets" message="Loading assets..." />
        <div v-else class="overflow-x-auto">
          <EmptyState 
            v-if="assets.length === 0"
            icon="📦"
            title="No assets found"
            message="Try adjusting your filters or upload new data"
          />
          <div v-else class="max-h-[600px] overflow-y-auto">
            <table class="table table-zebra w-full">
              <thead class="sticky top-0 bg-base-200 z-10">
                <tr>
                  <th class="text-xs font-semibold uppercase">Asset ID</th>
                  <th class="text-xs font-semibold uppercase">Asset Type</th>
                  <th class="text-xs font-semibold uppercase">Supervisor</th>
                  <th class="text-xs font-semibold uppercase">Department</th>
                  <th class="text-xs font-semibold uppercase">Location</th>
                  <th class="text-xs font-semibold uppercase">Status</th>
                  <th class="text-xs font-semibold uppercase">Actions</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="asset in assets" :key="asset.id" :class="{ 'opacity-60': asset.is_inspected }">
                  <td class="font-semibold">{{ asset.label }}</td>
                  <td>{{ asset.jenis_aset }}</td>
                  <td>{{ asset.pegawai_penempatan }}</td>
                  <td>{{ asset.bahagian }}</td>
                  <td>{{ asset.lokasi_terkini }}</td>
                  <td>
                    <Badge 
                      v-if="asset.is_inspected"
                      variant="success"
                      label="✓ Inspected"
                    />
                    <Badge 
                      v-else
                      variant="warning"
                      label="⚠ Not Inspected"
                    />
                  </td>
                  <td>
                    <button
                      v-if="!asset.is_inspected"
                      @click="markAsInspected(asset.id)"
                      class="btn btn-success btn-sm"
                      title="Mark as inspected"
                    >
                      ✓ Mark
                    </button>
                    <button
                      v-else
                      @click="markAsNotInspected(asset.id)"
                      class="btn btn-warning btn-sm"
                      title="Mark as not inspected"
                    >
                      ✗ Unmark
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from 'vue';
import api from '../api';
import { PageHeader, LoadingSpinner, EmptyState, StatsCard, Badge } from '../components';

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
