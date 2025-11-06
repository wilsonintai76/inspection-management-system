<template>
  <div class="dashboard-page">
    <div class="page-header">
      <div class="breadcrumb">
        <span class="breadcrumb-icon">📋</span>
        <h1 class="page-title">Overview</h1>
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
              <tr v-for="inspection in inspections" :key="inspection.id">
                <td>
                  <div class="location-cell">
                    <div class="location-name">{{ inspection.locationName }}</div>
                    <div class="location-meta">
                      <span class="location-supervisor">{{ inspection.supervisor }}</span>
                      <span class="location-contact">{{ inspection.contact }}</span>
                    </div>
                  </div>
                </td>
                <td>
                  <div class="date-cell">
                    <span class="date-icon">�</span>
                    {{ formatDate(inspection.date) }}
                  </div>
                </td>
                <td>
                  <div class="auditors-cell">
                    <div v-for="(auditor, idx) in inspection.auditors" :key="idx" class="auditor-row">
                      <span class="auditor-icon">{{ auditor.assigned ? '👤' : '🔹' }}</span>
                      <span :class="{ 'auditor-unassigned': !auditor.assigned }">
                        {{ auditor.name }}
                      </span>
                      <span v-if="auditor.id" class="auditor-id">{{ auditor.id }}</span>
                    </div>
                  </div>
                </td>
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
          <p class="section-subtitle">Top auditors by number of scheduled locations.</p>
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
import { api } from '../lib/api';

// Mock inspection data (will be replaced with API calls)
const inspections = ref([
  {
    id: 1,
    locationName: 'HITECH',
    supervisor: 'Wilson Intai',
    contact: '0111225369',
    date: '2025-10-07',
    auditors: [
      { name: 'Wilson Intai', id: '0111225369', assigned: true },
      { name: 'Unassigned', assigned: false }
    ]
  },
  {
    id: 2,
    locationName: 'Bengkel Mesin',
    supervisor: 'Ahmad Nasir',
    contact: '1234567890',
    date: '2025-10-07',
    auditors: [
      { name: 'Unassigned', assigned: false },
      { name: 'Unassigned', assigned: false }
    ]
  },
  {
    id: 3,
    locationName: 'Bengkel Kimpalan',
    supervisor: 'Dino Sebastian',
    contact: '555-2222',
    date: '2025-10-07',
    auditors: [
      { name: 'Unassigned', assigned: false },
      { name: 'Unassigned', assigned: false }
    ]
  },
  {
    id: 4,
    locationName: 'Bengkel Kayu',
    supervisor: 'Ahmad bin Saleh',
    contact: '555-4444',
    date: '2025-10-07',
    auditors: [
      { name: 'Unassigned', assigned: false },
      { name: 'Unassigned', assigned: false }
    ]
  }
]);

const auditorStats = ref([
  { name: 'Wilson Intai', count: 1 }
]);

const departmentProgress = ref([
  { name: 'Kejuruteraan Elektrik', completed: 0, total: 0, percentage: 0 },
  { name: 'Kejuruteraan Mekanikal', completed: 2, total: 3, percentage: 67 },
  { name: 'Kejuruteraan Awam', completed: 1, total: 1, percentage: 100 }
]);

function formatDate(dateStr: string) {
  const date = new Date(dateStr);
  return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
}

onMounted(async () => {
  try {
    // Fetch real data from API
    const [inspectionsResp, locationsResp, usersResp] = await Promise.all([
      api.get('/inspections.php'),
      api.get('/locations.php'),
      api.get('/users.php'),
    ]);
    
    // Process real data here when API returns actual data
    console.log('Loaded data:', { inspectionsResp, locationsResp, usersResp });
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

.dashboard-content {
  padding: 2rem;
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
