<template>
  <div class="dashboard-page">
    <div class="page-header">
      <div class="breadcrumb">
        <span class="breadcrumb-icon">📋</span>
        <h1 class="page-title">Overview</h1>
      </div>
      <div class="header-right">
        <!-- Department Filter -->
        <select v-if="!deptRestricted" v-model="filterDepartment" class="department-filter">
          <option value="">All Departments</option>
          <option v-for="dept in departments" :key="dept.id" :value="dept.id">{{ dept.name }}</option>
        </select>
        <div v-else class="department-filter" style="pointer-events:none; opacity:.7;">
          {{ currentDepartmentName }}
        </div>
        <button class="btn-logout" @click="logout">Logout</button>
      </div>
    </div>

    <div class="dashboard-content">
      <!-- Inspection Schedule Section -->
      <section class="schedule-section">
        <h2 class="section-title">Inspection Schedule</h2>
        <div class="table-container">
          <table class="schedule-table">
            <thead>
              <tr>
                <th class="sortable">
                  Location <span class="sort-icon">⇅</span>
                </th>
                <th class="sortable">
                  Date <span class="sort-icon">⇅</span>
                </th>
                <th>Auditors</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="row in scheduleRows" :key="row.id">
                <td>
                  <div class="location-cell">
                    <div class="location-name">{{ row.locationName }}</div>
                    <div class="location-meta">
                      <span class="location-supervisor">{{ row.supervisor || '—' }}</span>
                      <span class="location-contact">{{ row.contact || '—' }}</span>
                    </div>
                  </div>
                </td>
                <td>
                  <div class="date-cell">
                    <span class="date-icon">📅</span>
                    {{ row.date ? formatDate(row.date) : '—' }}
                  </div>
                </td>
                <td>
                  <div class="auditors-cell">
                    <div v-for="(auditor, idx) in row.auditors" :key="idx" class="auditor-row">
                      <span class="auditor-icon">{{ auditor.assigned ? '👤' : '🔹' }}</span>
                      <span :class="{ 'auditor-unassigned': !auditor.assigned }">
                        {{ auditor.name }}
                      </span>
                      <span v-if="auditor.id" class="auditor-id">{{ auditor.id }}</span>
                    </div>
                  </div>
                </td>
              </tr>
              <tr v-if="scheduleRows.length === 0">
                <td colspan="3" class="text-center text-muted">No locations found</td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>

      <!-- Analysis Row -->
      <div class="analysis-row">
        <!-- Auditor Analysis -->
        <section class="analysis-card">
          <h2 class="section-title">Auditor Analysis</h2>
          <p class="section-subtitle">Number of locations each auditor is assigned to.</p>
          <div class="table-container">
            <table class="simple-table">
              <thead>
                <tr>
                  <th>Auditor Name</th>
                  <th class="text-right">No. of Locations</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="auditor in auditorStats" :key="auditor.name">
                  <td>{{ auditor.name }}</td>
                  <td class="text-right">{{ auditor.count }}</td>
                </tr>
                <tr v-if="auditorStats.length === 0">
                  <td colspan="2" class="text-center text-muted">No data available</td>
                </tr>
              </tbody>
            </table>
          </div>
        </section>

        <!-- Inspection Status -->
        <section class="analysis-card">
          <h2 class="section-title">Inspection Status</h2>
          <p class="section-subtitle">Department inspection progress.</p>
          <div class="status-list">
            <div v-for="dept in departmentProgress" :key="dept.name" class="status-item">
              <div class="status-header">
                <span class="status-name">{{ dept.name }}</span>
                <span class="status-count">{{ dept.completed }} / {{ dept.total }}</span>
              </div>
              <div class="status-bar">
                <div class="status-fill" :style="{ width: dept.percentage + '%' }"></div>
              </div>
            </div>
            <div v-if="departmentProgress.length === 0" class="text-center text-muted">
              No inspection data
            </div>
          </div>
        </section>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from 'vue';
import { useRouter } from 'vue-router';
import { api, type Department as DeptType, type Location as LocType, type User as UserType, type Inspection as InspType } from '../lib/api';
import { useAuth } from '../composables/useAuth';

const router = useRouter();
const auth = useAuth();

// Department filter state
const departments = ref<DeptType[]>([]);
const filterDepartment = ref('');
const deptRestricted = computed(() => auth.isDeptRestricted.value);
const userDeptId = computed(() => auth.userDepartmentId.value);
const currentDepartmentName = computed(() => {
  const id = Number(userDeptId.value);
  const d = departments.value.find(x => x.id === id);
  return d?.name || 'Your Department';
});

// Raw data
const locations = ref<LocType[]>([]);
const inspections = ref<InspType[]>([]);
const users = ref<UserType[]>([]);

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
  const deptFilter = deptRestricted.value ? String(userDeptId.value) : filterDepartment.value;
  if (!deptFilter) return locations.value;
  const deptId = Number(deptFilter);
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
    const deptFilter = deptRestricted.value ? String(userDeptId.value) : filterDepartment.value;
    if (deptFilter) {
      const d = departments.value.find(x => String(x.id) === deptFilter);
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
  const deptQ = deptRestricted.value && userDeptId.value ? `?department_id=${userDeptId.value}` : '';
  const [deptResp, locResp, inspResp, userResp] = await Promise.all([
    api.get('/departments.php'),
    api.get(`/locations.php${deptQ}`),
    api.get(`/inspections.php${deptQ}`),
    api.get('/users.php'),
  ]);
  departments.value = deptResp.data;
  locations.value = locResp.data;
  inspections.value = inspResp.data;
  users.value = userResp.data;
}

onMounted(async () => {
  try {
    await loadData();
  } catch (err) {
    console.error('Failed to load dashboard data:', err);
  }
});
</script>

<style scoped>
.dashboard-page {
  background: #f9fafb;
  min-height: 100vh;
}

.page-header {
  background: white;
  padding: 1.5rem 2rem;
  border-bottom: 1px solid #e5e7eb;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.breadcrumb {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.breadcrumb-icon {
  font-size: 1.25rem;
}

.page-title {
  font-size: 1.5rem;
  font-weight: 600;
  color: #1f2937;
  margin: 0;
}

.btn-logout {
  padding: 0.5rem 1rem;
  border: 1px solid #e5e7eb;
  background: #ffffff;
  color: #ef4444;
  border-radius: 6px;
  cursor: pointer;
  font-weight: 600;
}

.btn-logout:hover {
  background: #fef2f2;
  border-color: #fecaca;
}

.dashboard-content {
  padding: 2rem;
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
  margin-right: 1rem;
}

.section-title {
  font-size: 1.25rem;
  font-weight: 600;
  color: #1f2937;
  margin: 0 0 0.5rem 0;
}

.section-subtitle {
  font-size: 0.875rem;
  color: #6b7280;
  margin: 0 0 1.5rem 0;
}

/* Schedule Section */
.schedule-section {
  background: white;
  border-radius: 8px;
  padding: 1.5rem;
  margin-bottom: 2rem;
  box-shadow: 0 1px 3px rgba(0,0,0,0.05);
}

.table-container {
  overflow-x: auto;
}

.schedule-table {
  width: 100%;
  border-collapse: collapse;
}

.schedule-table thead {
  background: #f9fafb;
  border-bottom: 1px solid #e5e7eb;
}

.schedule-table th {
  text-align: left;
  padding: 0.75rem 1rem;
  font-weight: 600;
  font-size: 0.875rem;
  color: #6b7280;
}

.schedule-table th.sortable {
  cursor: pointer;
}

.sort-icon {
  margin-left: 0.25rem;
  font-size: 0.75rem;
  color: #9ca3af;
}

.schedule-table tbody tr {
  border-bottom: 1px solid #f3f4f6;
}

.schedule-table tbody tr:last-child {
  border-bottom: none;
}

.schedule-table td {
  padding: 1rem;
}

.location-cell {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.location-name {
  font-weight: 600;
  color: #1f2937;
}

.location-meta {
  display: flex;
  gap: 1rem;
  font-size: 0.875rem;
  color: #6b7280;
}

.date-cell {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  color: #1f2937;
}

.date-icon {
  color: var(--teal);
}

.auditors-cell {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.auditor-row {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.875rem;
}

.auditor-icon {
  font-size: 1rem;
}

.auditor-unassigned {
  color: #9ca3af;
}

.auditor-id {
  color: #6b7280;
  font-size: 0.8rem;
}

/* Analysis Row */
.analysis-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 2rem;
}

.analysis-card {
  background: white;
  border-radius: 8px;
  padding: 1.5rem;
  box-shadow: 0 1px 3px rgba(0,0,0,0.05);
}

.simple-table {
  width: 100%;
  border-collapse: collapse;
}

.simple-table thead {
  border-bottom: 1px solid #e5e7eb;
}

.simple-table th {
  text-align: left;
  padding: 0.75rem 0;
  font-weight: 600;
  font-size: 0.875rem;
  color: #6b7280;
}

.simple-table th.text-right {
  text-align: right;
}

.simple-table tbody tr {
  border-bottom: 1px solid #f3f4f6;
}

.simple-table tbody tr:last-child {
  border-bottom: none;
}

.simple-table td {
  padding: 0.75rem 0;
  color: #1f2937;
}

.simple-table td.text-right {
  text-align: right;
}

.simple-table td.text-center {
  text-align: center;
}

.text-muted {
  color: #9ca3af;
}

/* Status List */
.status-list {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.status-item {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.status-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.status-name {
  font-weight: 500;
  color: #1f2937;
  font-size: 0.9rem;
}

.status-count {
  font-size: 0.875rem;
  color: #6b7280;
  font-weight: 600;
}

.status-bar {
  height: 8px;
  background: #e5e7eb;
  border-radius: 4px;
  overflow: hidden;
}

.status-fill {
  height: 100%;
  background: var(--teal);
  transition: width 0.3s ease;
  border-radius: 4px;
}

@media (max-width: 1024px) {
  .analysis-row {
    grid-template-columns: 1fr;
  }
}
</style>
