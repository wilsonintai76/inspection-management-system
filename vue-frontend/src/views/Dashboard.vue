<template>
  <div class="min-h-screen bg-base-200 p-6">
    <!-- Page Header with Filter -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
      <PageHeader
        icon="fas fa-chart-line"
        title="Overview"
        subtitle="Dashboard with inspection schedules and progress"
      />
      <select 
        v-model="filterDepartment" 
        class="select select-bordered select-sm md:w-64"
      >
        <option value="">All Departments</option>
        <option v-for="dept in departments" :key="dept.id" :value="dept.id">
          {{ dept.name }}
        </option>
      </select>
    </div>

    <!-- Inspection Schedule Section -->
    <div class="card bg-base-100 shadow-md mb-6">
      <div class="card-body">
        <h2 class="card-title text-lg mb-4">
          <i class="fas fa-calendar-check text-primary"></i>
          Inspection Schedule
        </h2>
        <div class="overflow-x-auto">
          <table class="table table-zebra">
            <thead>
              <tr>
                <th class="cursor-pointer hover:bg-base-200">
                  Location <span class="ml-1 text-xs">⇅</span>
                </th>
                <th class="cursor-pointer hover:bg-base-200">
                  Date <span class="ml-1 text-xs">⇅</span>
                </th>
                <th>Auditors</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="row in scheduleRows" :key="row.id">
                <td>
                  <div class="flex flex-col gap-1">
                    <div class="font-semibold">{{ row.locationName }}</div>
                    <div class="flex gap-4 text-sm text-base-content/60">
                      <span>{{ row.supervisor || '—' }}</span>
                      <span>{{ row.contact || '—' }}</span>
                    </div>
                  </div>
                </td>
                <td>
                  <div class="flex items-center gap-2">
                    <span>📅</span>
                    {{ row.date ? formatDate(row.date) : '—' }}
                  </div>
                </td>
                <td>
                  <div class="flex flex-col gap-2">
                    <div 
                      v-for="(auditor, idx) in row.auditors" 
                      :key="idx" 
                      class="flex items-center gap-2 text-sm"
                    >
                      <span>{{ auditor.assigned ? '👤' : '🔹' }}</span>
                      <span :class="{ 'text-base-content/40': !auditor.assigned }">
                        {{ auditor.name }}
                      </span>
                      <span v-if="auditor.id" class="text-xs text-base-content/50">{{ auditor.id }}</span>
                    </div>
                  </div>
                </td>
              </tr>
              <tr v-if="scheduleRows.length === 0">
                <td colspan="3" class="text-center text-base-content/50">No locations found</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Analysis Row -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
      <!-- Auditor Analysis -->
      <div class="card bg-base-100 shadow-md">
        <div class="card-body">
          <h3 class="card-title text-lg mb-2">
            <i class="fas fa-user-friends text-primary"></i>
            Auditor Analysis
          </h3>
          <p class="text-sm text-base-content/60 mb-4">Number of locations each auditor is assigned to.</p>
          <table class="table">
            <thead>
              <tr>
                <th>Auditor Name</th>
                <th class="text-right">No. of Locations</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="auditor in auditorStats" :key="auditor.name">
                <td>{{ auditor.name }}</td>
                <td class="text-right font-semibold">{{ auditor.count }}</td>
              </tr>
              <tr v-if="auditorStats.length === 0">
                <td colspan="2" class="text-center text-base-content/50">No data available</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Inspection Status -->
      <div class="card bg-base-100 shadow-md">
        <div class="card-body">
          <h3 class="card-title text-lg mb-2">
            <i class="fas fa-map-marked-alt text-primary"></i>
            Location Inspection Status
          </h3>
          <p class="text-sm text-base-content/60 mb-4">Department inspection progress.</p>
          <div class="max-h-96 overflow-y-auto pr-2 space-y-4">
            <div v-for="dept in departmentProgress" :key="dept.name" class="space-y-2">
              <div class="flex justify-between items-center">
                <span class="font-medium text-sm">{{ dept.name }}</span>
                <span class="text-sm font-semibold text-base-content/70">
                  {{ dept.completed }} / {{ dept.total }}
                </span>
              </div>
              <progress 
                class="progress progress-primary w-full" 
                :value="dept.percentage" 
                max="100"
              ></progress>
            </div>
            <div v-if="departmentProgress.length === 0" class="text-center text-base-content/50 py-8">
              No inspection data
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Asset Inspection Progress Section -->
    <div class="card bg-base-100 shadow-md">
      <div class="card-body">
        <h2 class="card-title text-lg mb-2">
          <i class="fas fa-tasks text-primary"></i>
          Asset Inspection Progress
        </h2>
        <p class="text-sm text-base-content/60 mb-4">Department asset inspection completion status.</p>
        
        <LoadingSpinner v-if="loadingAssets" message="Loading asset data..." />

        <div v-else class="overflow-x-auto">
          <table class="table table-zebra">
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
              <tr v-for="row in assetSummary" :key="row.department_id">
                <td class="font-semibold">{{ row.department_name }}</td>
                <td>{{ row.total_assets }}</td>
                <td class="text-success font-semibold">{{ row.assets_inspected }}</td>
                <td class="text-warning font-semibold">{{ row.assets_not_inspected }}</td>
                <td>
                  <div class="flex items-center gap-3">
                    <progress 
                      class="progress progress-primary w-full max-w-xs" 
                      :value="row.percentage_inspected" 
                      max="100"
                    ></progress>
                    <span class="font-semibold text-sm min-w-[3rem] text-right">
                      {{ row.percentage_inspected }}%
                    </span>
                  </div>
                </td>
              </tr>
              <tr v-if="assetSummary.length === 0">
                <td colspan="5" class="text-center text-base-content/50">No asset data available</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed, watch } from 'vue';
import { useRouter } from 'vue-router';
import { api, type Department as DeptType, type Location as LocType, type User as UserType, type Inspection as InspType } from '../lib/api';
import { useAuth } from '../composables/useAuth';
import { PageHeader, LoadingSpinner } from '../components';

const router = useRouter();
const auth = useAuth();

// Department filter state
const departments = ref<DeptType[]>([]);
const filterDepartment = ref('');

// Raw data
const locations = ref<LocType[]>([]);
const inspections = ref<InspType[]>([]);
const users = ref<UserType[]>([]);

// Asset inspection data
const loadingAssets = ref(false);
const assetSummary = ref<any[]>([]);

// Derived maps
const inspectionByLocation = computed<Record<number, InspType>>(() => {
  const map: Record<number, InspType> = {};
  // inspections.php returns ordered by date desc; first one per location is latest
  for (const insp of inspections.value) {
    if (!map[insp.location_id]) {
      map[insp.location_id] = insp;
    }
  }
  return map;
});

const filteredLocationList = computed(() => {
  if (!filterDepartment.value) return locations.value;
  const deptId = Number(filterDepartment.value);
  return locations.value.filter(l => l.department_id === deptId);
});

// Schedule rows for UI
const scheduleRows = computed(() => {
  return filteredLocationList.value.map(loc => {
    const insp = inspectionByLocation.value[loc.id!];
    const a1 = insp?.auditor1_id ? users.value.find(u => u.id === insp.auditor1_id) : null;
    const a2 = insp?.auditor2_id ? users.value.find(u => u.id === insp.auditor2_id) : null;
    return {
      id: loc.id,
      locationName: loc.name,
      supervisor: loc.supervisor || '',
      contact: loc.contact_number || '',
      date: insp?.inspection_date || '',
      auditors: [
        a1 ? { name: a1.name, id: a1.id, assigned: true } : { name: 'Unassigned', assigned: false },
        a2 ? { name: a2.name, id: a2.id, assigned: true } : { name: 'Unassigned', assigned: false },
      ],
    };
  });
});

// Auditor analysis (count of locations assigned per auditor)
const auditorStats = computed(() => {
  const counts = new Map<string, { name: string; count: number }>();
  for (const loc of filteredLocationList.value) {
    const insp = inspectionByLocation.value[loc.id!];
    const add = (uid?: string | null) => {
      if (!uid) return;
      const user = users.value.find(u => u.id === uid);
      if (!user) return;
      const key = user.id;
      const curr = counts.get(key) || { name: user.name, count: 0 };
      curr.count += 1;
      counts.set(key, curr);
    };
    add(insp?.auditor1_id || null);
    add(insp?.auditor2_id || null);
  }
  return Array.from(counts.values()).sort((a, b) => b.count - a.count);
});

// Inspection status progress by department
const departmentProgress = computed(() => {
  const list: { name: string; total: number; completed: number; percentage: number }[] = [];
  const targetDepts = (() => {
    if (filterDepartment.value) {
      const filterId = Number(filterDepartment.value);
      const d = departments.value.find(x => x.id === filterId);
      return d ? [d] : [];
    }
    return departments.value;
  })();

  // Precompute latest status by location
  const latestByLoc = inspectionByLocation.value;

  for (const d of targetDepts) {
    const locs = locations.value.filter(l => l.department_id === d.id);
    const total = locs.length;
    let completed = 0;
    for (const l of locs) {
      const insp = latestByLoc[l.id!];
      if (insp?.status === 'Complete') completed += 1;
    }
    const percentage = total > 0 ? Math.round((completed / total) * 100) : 0;
    list.push({ name: d.name, total, completed, percentage });
  }
  return list;
});

function formatDate(dateStr: string) {
  const date = new Date(dateStr);
  if (isNaN(date.getTime())) return '';
  return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
}

function logout() {
  try {
    sessionStorage.removeItem('isLoggedIn');
    sessionStorage.removeItem('userId');
    sessionStorage.removeItem('staffId');
    sessionStorage.removeItem('userEmail');
    sessionStorage.removeItem('userName');
    sessionStorage.removeItem('userRoles');
    sessionStorage.removeItem('mustChangePassword');
    localStorage.removeItem('rememberStaffId');
  } catch {}
  router.push('/login');
}

async function loadData() {
  // Load all data - filtering will be done client-side via dropdown
  const [deptResp, locResp, inspResp, userResp] = await Promise.all([
    api.get('/departments.php'),
    api.get('/locations.php'),
    api.get('/inspections.php'),
    api.get('/users.php'),
  ]);
  departments.value = deptResp.data;
  locations.value = locResp.data;
  inspections.value = inspResp.data;
  users.value = userResp.data;
  
  // Load asset summary data
  await loadAssetSummary();
}

async function loadAssetSummary() {
  loadingAssets.value = true;
  try {
    const params: any = { action: 'summary' };
    if (filterDepartment.value) {
      params.department_id = filterDepartment.value;
    }

    const response = await api.get('/asset-summary.php', { params });
    assetSummary.value = response.data.summary || [];
  } catch (err) {
    console.error('Failed to load asset summary:', err);
    assetSummary.value = [];
  } finally {
    loadingAssets.value = false;
  }
}

onMounted(async () => {
  try {
    await loadData();
  } catch (err) {
    console.error('Failed to load dashboard data:', err);
  }
});

// Watch for department filter changes to reload asset summary
watch(filterDepartment, () => {
  loadAssetSummary();
});
</script>
