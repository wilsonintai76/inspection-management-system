<template>
  <div class="inspection-status-page">
    <!-- Page Header -->
    <div class="page-header">
      <div class="header-left">
        <span class="breadcrumb-icon">📋</span>
        <h1 class="page-title">Inspection Status</h1>
      </div>
    </div>

    <!-- Inspection Status Card -->
    <div class="status-card">
      <div class="card-header">
        <h2 class="card-title">Inspection Status</h2>
        <select v-model="filterDepartment" class="department-filter">
          <option value="">All Departments</option>
          <option v-for="dept in departments" :key="dept.id" :value="dept.id">
            {{ dept.name }}
          </option>
        </select>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="loading-state">
        <div class="spinner"></div>
        <p>Loading inspection status...</p>
      </div>

      <!-- Error State -->
      <div v-else-if="error" class="error-state">
        <p class="error-message">{{ error }}</p>
        <button @click="fetchData" class="btn-retry">Retry</button>
      </div>

      <!-- Status Table -->
      <div v-else class="table-container">
        <table class="status-table">
          <thead>
            <tr>
              <th>Location</th>
              <th>Department</th>
              <th>Status</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="filteredInspections.length === 0">
              <td colspan="4" class="no-data">
                <span class="no-data-icon">📭</span>
                <p>No inspections found</p>
              </td>
            </tr>
            <tr v-for="inspection in filteredInspections" :key="inspection.id">
              <td>
                <div class="location-name">{{ inspection.locationName }}</div>
              </td>
              <td>
                <div class="department-name">{{ inspection.departmentName }}</div>
              </td>
              <td>
                <span 
                  class="status-badge" 
                  :class="getStatusClass(inspection.status)"
                >
                  {{ inspection.status }}
                </span>
              </td>
              <td>
                <button 
                  @click="toggleStatus(inspection)" 
                  class="btn-toggle"
                >
                  Toggle Status
                </button>
              </td>
            </tr>
          </tbody>
        </table>
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
}

interface Location {
  id: number;
  name: string;
  department_id: number;
}

interface Inspection {
  id: number;
  location_id: number;
  status: 'Pending' | 'Complete';
  locationName?: string;
  departmentName?: string;
  departmentId?: number;
}

const inspections = ref<Inspection[]>([]);
const departments = ref<Department[]>([]);
const locations = ref<Location[]>([]);
const loading = ref(false);
const error = ref('');
const filterDepartment = ref('');

const filteredInspections = computed(() => {
  if (!filterDepartment.value) {
    return inspections.value;
  }
  return inspections.value.filter(
    insp => insp.departmentId === Number(filterDepartment.value)
  );
});

function getStatusClass(status: string): string {
  return status === 'Complete' ? 'status-complete' : 'status-pending';
}

async function fetchDepartments() {
  try {
    const response = await api.get('/departments.php');
    departments.value = response.data;
  } catch (err) {
    console.error('Error fetching departments:', err);
  }
}

async function fetchLocations() {
  try {
    const response = await api.get('/locations.php');
    locations.value = response.data;
  } catch (err) {
    console.error('Error fetching locations:', err);
  }
}

async function fetchInspections() {
  try {
    const response = await api.get('/inspections.php');
    const data = response.data;
    
    // Enrich inspections with location and department names
    inspections.value = data.map((insp: Inspection) => {
      const location = locations.value.find(l => l.id === insp.location_id);
      const department = location 
        ? departments.value.find(d => d.id === location.department_id)
        : null;
      
      return {
        ...insp,
        locationName: location?.name || 'Unknown',
        departmentName: department?.name || 'Unknown',
        departmentId: location?.department_id
      };
    });
  } catch (err) {
    console.error('Error fetching inspections:', err);
    throw err;
  }
}

async function fetchData() {
  loading.value = true;
  error.value = '';
  try {
    await fetchDepartments();
    await fetchLocations();
    await fetchInspections();
  } catch (err) {
    error.value = 'Failed to load inspection status. Please try again.';
    console.error('Error loading data:', err);
  } finally {
    loading.value = false;
  }
}

async function toggleStatus(inspection: Inspection) {
  const newStatus = inspection.status === 'Complete' ? 'Pending' : 'Complete';
  
  try {
    await api.put(`/inspections.php?id=${inspection.id}`, {
      location_id: inspection.location_id,
      status: newStatus,
      inspection_date: new Date().toISOString().split('T')[0]
    });
    
    // Update local state
    inspection.status = newStatus;
  } catch (err) {
    console.error('Error toggling status:', err);
    alert('Failed to update status. Please try again.');
  }
}

onMounted(async () => {
  await fetchData();
});
</script>

<style scoped>
.inspection-status-page {
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

.status-card {
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
}

.department-filter:focus {
  outline: none;
  border-color: #14b8a6;
  box-shadow: 0 0 0 3px rgba(20, 184, 166, 0.1);
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
  border-top-color: #14b8a6;
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
  background: #14b8a6;
  color: white;
  border: none;
  border-radius: 0.375rem;
  cursor: pointer;
}

.btn-retry:hover {
  background: #0d9488;
}

.table-container {
  overflow-x: auto;
}

.status-table {
  width: 100%;
  border-collapse: collapse;
}

.status-table thead {
  background: #f9fafb;
  border-bottom: 1px solid #e5e7eb;
}

.status-table th {
  padding: 1rem 1.5rem;
  text-align: left;
  font-size: 0.75rem;
  font-weight: 600;
  color: #6b7280;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.status-table tbody tr {
  border-bottom: 1px solid #f3f4f6;
  transition: background-color 0.15s;
}

.status-table tbody tr:hover {
  background: #f9fafb;
}

.status-table td {
  padding: 1.25rem 1.5rem;
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

.location-name {
  font-weight: 600;
  color: #1f2937;
  font-size: 0.9375rem;
}

.department-name {
  color: #374151;
  font-size: 0.9375rem;
}

.status-badge {
  display: inline-block;
  padding: 0.375rem 0.875rem;
  border-radius: 9999px;
  font-size: 0.8125rem;
  font-weight: 600;
  text-transform: capitalize;
}

.status-complete {
  background: #d1fae5;
  color: #065f46;
}

.status-pending {
  background: #f3f4f6;
  color: #374151;
}

.btn-toggle {
  padding: 0.5rem 1rem;
  background: transparent;
  color: #374151;
  border: 1px solid #d1d5db;
  border-radius: 0.375rem;
  font-size: 0.875rem;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.15s;
}

.btn-toggle:hover {
  background: #f9fafb;
  border-color: #14b8a6;
  color: #14b8a6;
}

@media (max-width: 768px) {
  .inspection-status-page {
    padding: 1rem;
  }

  .card-header {
    flex-direction: column;
    align-items: flex-start;
    gap: 1rem;
  }

  .department-filter {
    width: 100%;
  }
}
</style>
