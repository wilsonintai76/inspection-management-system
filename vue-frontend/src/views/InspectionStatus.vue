<template>
  <div class="p-6 max-w-7xl mx-auto">
    <!-- Page Header -->
    <PageHeader 
      icon="📋" 
      title="Inspection Status"
      subtitle="Monitor and manage inspection completion status"
    />

    <!-- Inspection Status Card -->
    <div class="card bg-base-100 shadow-xl">
      <div class="card-body">
        <div class="flex justify-between items-center mb-6">
          <h2 class="card-title text-xl">Inspection Status</h2>
          <select 
            v-if="!deptRestricted" 
            v-model="filterDepartment" 
            class="select select-bordered w-64"
          >
            <option value="">All Departments</option>
            <option v-for="dept in departments" :key="dept.id" :value="dept.id">
              {{ dept.name }}
            </option>
          </select>
          <div v-else class="badge badge-lg badge-ghost opacity-70">
            {{ currentDepartmentName }}
          </div>
        </div>

        <!-- Loading State -->
        <LoadingSpinner v-if="loading" message="Loading inspection status..." />

        <!-- Error State -->
        <div v-else-if="error" class="flex flex-col items-center justify-center py-16 gap-4">
          <div class="alert alert-error max-w-md">
            <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span>{{ error }}</span>
          </div>
          <button @click="fetchData" class="btn btn-primary">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
              <path fill-rule="evenodd" d="M4 2a1 1 0 011 1v2.101a7.002 7.002 0 0111.601 2.566 1 1 0 11-1.885.666A5.002 5.002 0 005.999 7H9a1 1 0 010 2H4a1 1 0 01-1-1V3a1 1 0 011-1zm.008 9.057a1 1 0 011.276.61A5.002 5.002 0 0014.001 13H11a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0v-2.101a7.002 7.002 0 01-11.601-2.566 1 1 0 01.61-1.276z" clip-rule="evenodd" />
            </svg>
            Retry
          </button>
        </div>

        <!-- Status Table -->
        <div v-else class="overflow-x-auto">
          <EmptyState 
            v-if="filteredInspections.length === 0"
            icon="📭"
            title="No inspections found"
            message="There are no inspections matching your current filters."
          />
          
          <table v-else class="table table-zebra w-full">
            <thead>
              <tr>
                <th class="text-xs font-semibold uppercase tracking-wider">Location</th>
                <th class="text-xs font-semibold uppercase tracking-wider">Department</th>
                <th class="text-xs font-semibold uppercase tracking-wider">Status</th>
                <th class="text-xs font-semibold uppercase tracking-wider">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="inspection in filteredInspections" :key="inspection.id">
                <td>
                  <div class="font-semibold text-gray-900">{{ inspection.locationName }}</div>
                </td>
                <td>
                  <div class="text-gray-700">{{ inspection.departmentName }}</div>
                </td>
                <td>
                  <Badge 
                    :variant="inspection.status === 'Complete' ? 'success' : 'ghost'"
                    :label="inspection.status"
                  />
                </td>
                <td>
                  <button 
                    v-if="canToggle"
                    @click="toggleStatus(inspection)" 
                    class="btn btn-sm btn-outline"
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
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import api from '../api';
import { useAuth } from '../composables/useAuth';
import { PageHeader, LoadingSpinner, EmptyState, Badge } from '../components';

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
const auth = useAuth();
const deptRestricted = computed(() => auth.isDeptRestricted.value);
const userDeptId = computed(() => auth.userDepartmentId.value);
const canToggle = computed(() => auth.can('canToggleInspectionStatus'));
const currentDepartmentName = computed(() => {
  const id = Number(userDeptId.value);
  const dept = departments.value.find(d => d.id === id);
  return dept?.name || 'Your Department';
});

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
    const q = deptRestricted.value && userDeptId.value ? `?department_id=${userDeptId.value}` : '';
    const response = await api.get(`/locations.php${q}`);
    locations.value = response.data;
  } catch (err) {
    console.error('Error fetching locations:', err);
  }
}

async function fetchInspections() {
  try {
    const q = deptRestricted.value && userDeptId.value ? `?department_id=${userDeptId.value}` : '';
    const response = await api.get(`/inspections.php${q}`);
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
  // If department-restricted, force filter to user's department
  if (deptRestricted.value && userDeptId.value) {
    filterDepartment.value = String(userDeptId.value);
  }
  await fetchData();
});
</script>
