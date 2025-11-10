<template>
  <div class="min-h-screen bg-base-200 p-6">
    <!-- Page Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
      <PageHeader
        icon="fas fa-calendar-check"
        title="Schedule"
        subtitle="Manage inspection schedules and assign auditors"
      />
      <select v-model="filterDepartment" class="select select-bordered select-sm md:w-64">
        <option value="">All Departments</option>
        <option v-for="dept in departments" :key="dept.id" :value="dept.id">
          {{ dept.name }}
        </option>
      </select>
    </div>

    <!-- Schedule Card -->
    <div class="card bg-base-100 shadow-md">
      <div class="card-body">
        <h2 class="card-title text-lg mb-4">
          <i class="fas fa-calendar-alt text-primary"></i>
          Inspection Schedule
        </h2>

        <!-- Loading State -->
        <LoadingSpinner v-if="loading" message="Loading schedule..." />

        <!-- Error State -->
        <div v-else-if="error" class="alert alert-error">
          <i class="fas fa-exclamation-circle"></i>
          <div>
            <p>{{ error }}</p>
            <button @click="fetchData" class="btn btn-sm btn-outline mt-2">Retry</button>
          </div>
        </div>

        <!-- Schedule Table -->
        <div v-else class="overflow-x-auto">
          <table class="table table-zebra">
            <thead>
              <tr>
                <th class="cursor-pointer hover:bg-base-200">
                  Location <span class="text-xs ml-1">↕</span>
                </th>
                <th class="cursor-pointer hover:bg-base-200">
                  Date <span class="text-xs ml-1">↕</span>
                </th>
                <th>Auditors</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="filteredLocations.length === 0">
                <td colspan="4">
                  <EmptyState
                    icon="fas fa-calendar-times"
                    title="No locations found"
                    message="Try adjusting your filters"
                  />
                </td>
              </tr>
              <tr v-for="location in filteredLocations" :key="location.id">
                <td>
                  <div class="flex flex-col gap-1">
                    <div class="font-semibold">{{ location.name }}</div>
                    <div class="text-sm text-base-content/60">
                      {{ getDepartmentName(location.department_id) }}
                    </div>
                  </div>
                </td>
                <td>
                  <div class="flex items-center gap-2">
                    <i class="fas fa-calendar text-primary"></i>
                    <input 
                      type="date" 
                      :value="getInspectionDate(location.id)"
                      @change="updateInspectionDate(location.id, $event)"
                      class="input input-bordered input-sm"
                      :disabled="!canSetDate"
                    />
                  </div>
                </td>
                <td>
                  <div class="flex flex-col gap-2">
                    <div 
                      v-for="(auditor, index) in getLocationAuditors(location.id)" 
                      :key="index" 
                      class="flex items-center gap-2"
                    >
                      <i class="fas fa-user text-base-content/60"></i>
                      
                      <!-- Admin: Show dropdown to assign any auditor -->
                      <select 
                        v-if="isAdmin && auditor.isEmpty"
                        @change="assignAuditor(location.id, index, $event)"
                        class="select select-bordered select-sm"
                      >
                        <option value="">Select Auditor...</option>
                        <option 
                          v-for="user in auditorUsers" 
                          :key="user.id" 
                          :value="user.id"
                        >
                          {{ user.name }}
                        </option>
                      </select>
                      
                      <!-- Show label for assigned auditor -->
                      <span v-if="!auditor.isEmpty" class="text-sm">{{ auditor.name }}</span>
                      
                      <!-- Auditor (non-admin): Show label for unassigned slot -->
                      <span v-if="auditor.isEmpty && isAuditor && !isAdmin" class="text-sm text-base-content/60">{{ auditor.name }}</span>
                      
                      <!-- Add button - only for Auditors to add themselves -->
                      <button 
                        v-if="auditor.isEmpty && isAuditor" 
                        class="btn btn-xs btn-circle btn-success"
                        @click="assignSelfAsAuditor(location.id, index)"
                        title="Assign yourself"
                      >
                        <i class="fa-solid fa-plus" aria-hidden="true"></i>
                        <span class="sr-only">Add</span>
                      </button>
                      
                      <!-- Remove button - Auditors can remove themselves, Admin can remove anyone -->
                      <button 
                        v-if="!auditor.isEmpty && (auditor.isCurrentUser || isAdmin)" 
                        class="btn btn-xs btn-circle btn-error"
                        @click="removeAuditor(location.id, index)"
                        :title="auditor.isCurrentUser ? 'Remove yourself' : 'Remove auditor'"
                      >
                        <i class="fa-solid fa-minus" aria-hidden="true"></i>
                        <span class="sr-only">Remove</span>
                      </button>
                    </div>
                  </div>
                </td>
                <td>
                  <button 
                    @click="toggleInspectionStatus(location.id)" 
                    :class="[
                      'btn btn-xs',
                      getInspectionStatus(location.id) === 'Complete' ? 'btn-success' : 'btn-warning'
                    ]"
                    :disabled="!canToggleStatus"
                    :title="canToggleStatus ? 'Click to toggle status' : 'No permission to change status'"
                  >
                    {{ getInspectionStatus(location.id) || 'Pending' }}
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
import { PageHeader, LoadingSpinner, EmptyState } from '../components';

interface Department {
  id: number;
  name: string;
}

interface Location {
  id: number;
  name: string;
  department_id: number;
}

interface User {
  id: string;
  name: string;
  roles?: string[];
}

interface Inspection {
  id?: number;
  location_id: number;
  inspection_date: string;
  auditor1_id: string | null;
  auditor2_id: string | null;
  status: string;
}

interface InspectionMap {
  [locationId: number]: Inspection;
}

const departments = ref<Department[]>([]);
const locations = ref<Location[]>([]);
const inspections = ref<Inspection[]>([]);
const inspectionMap = ref<InspectionMap>({});
const users = ref<User[]>([]);
const auditorUsers = ref<User[]>([]); // Users with Auditor role
const currentUser = ref<User | null>(null);
const loading = ref(false);
const error = ref('');
const filterDepartment = ref('');

const isAuditor = computed(() => {
  if (!currentUser.value || !currentUser.value.roles) {
    console.log('isAuditor: false - no user or roles', currentUser.value);
    return false;
  }
  const result = currentUser.value.roles.includes('Auditor');
  console.log('isAuditor:', result, 'roles:', currentUser.value.roles);
  return result;
});

const isAdmin = computed(() => {
  if (!currentUser.value || !currentUser.value.roles) {
    console.log('isAdmin: false - no user or roles', currentUser.value);
    return false;
  }
  const result = currentUser.value.roles.includes('Admin');
  console.log('isAdmin:', result, 'roles:', currentUser.value.roles);
  return result;
});

const canSetDate = computed(() => {
  const result = isAuditor.value || isAdmin.value;
  console.log('canSetDate:', result, 'isAuditor:', isAuditor.value, 'isAdmin:', isAdmin.value);
  return result;
});

const canToggleStatus = computed(() => {
  return isAuditor.value;
});

const filteredLocations = computed(() => {
  if (!filterDepartment.value) {
    return locations.value;
  }
  return locations.value.filter(loc => loc.department_id === Number(filterDepartment.value));
});

function getDepartmentName(departmentId: number): string {
  const dept = departments.value.find(d => d.id === departmentId);
  return dept ? dept.name : 'Unknown';
}

function getInspectionDate(locationId: number): string {
  const inspection = inspectionMap.value[locationId];
  if (inspection && inspection.inspection_date) {
    return inspection.inspection_date;
  }
  // Default to today
  return new Date().toISOString().split('T')[0];
}

function getInspectionStatus(locationId: number): string {
  const inspection = inspectionMap.value[locationId];
  return inspection?.status || 'Pending';
}

async function toggleInspectionStatus(locationId: number) {
  if (!canToggleStatus.value) return;
  
  try {
    const inspection = inspectionMap.value[locationId];
    const currentStatus = inspection?.status || 'Pending';
    const newStatus = currentStatus === 'Complete' ? 'Pending' : 'Complete';
    
    if (inspection) {
      // Update existing inspection
      await api.put(`/inspections.php?id=${inspection.id}`, {
        ...inspection,
        status: newStatus
      });
    } else {
      // Create new inspection with status
      await api.post('/inspections.php', {
        location_id: locationId,
        status: newStatus,
        inspection_date: new Date().toISOString().split('T')[0]
      });
    }
    
    // Refresh data
    await fetchInspections();
  } catch (err) {
    console.error('Error toggling status:', err);
    alert('Failed to update status. Please try again.');
  }
}

function getLocationAuditors(locationId: number) {
  const inspection = inspectionMap.value[locationId];
  const auditors = [];
  
  // Auditor 1
  if (inspection?.auditor1_id) {
    const user = users.value.find(u => u.id === inspection.auditor1_id);
    auditors.push({
      name: user?.name || 'Unknown',
      isEmpty: false,
      isCurrentUser: currentUser.value?.id === inspection.auditor1_id
    });
  } else {
    auditors.push({
      name: 'Unassigned',
      isEmpty: true,
      isCurrentUser: false
    });
  }
  
  // Auditor 2
  if (inspection?.auditor2_id) {
    const user = users.value.find(u => u.id === inspection.auditor2_id);
    auditors.push({
      name: user?.name || 'Unknown',
      isEmpty: false,
      isCurrentUser: currentUser.value?.id === inspection.auditor2_id
    });
  } else {
    auditors.push({
      name: 'Unassigned',
      isEmpty: true,
      isCurrentUser: false
    });
  }
  
  return auditors;
}

async function updateInspectionDate(locationId: number, event: Event) {
  if (!canSetDate.value) {
    alert('Only auditors and admins can set inspection dates.');
    return;
  }
  
  const target = event.target as HTMLInputElement;
  const newDate = target.value;
  
  try {
    const inspection = inspectionMap.value[locationId];
    
    if (inspection && inspection.id) {
      // Update existing inspection
      await api.put(`/inspections.php?id=${inspection.id}`, {
        location_id: locationId,
        inspection_date: newDate,
        status: inspection.status,
        auditor1_id: inspection.auditor1_id,
        auditor2_id: inspection.auditor2_id
      });
      inspection.inspection_date = newDate;
    } else {
      // Create new inspection
      const response = await api.post('/inspections.php', {
        location_id: locationId,
        inspection_date: newDate,
        status: 'Pending',
        auditor1_id: null,
        auditor2_id: null
      });
      
      const newInspection: Inspection = {
        id: response.data.id,
        location_id: locationId,
        inspection_date: newDate,
        status: 'Pending',
        auditor1_id: null,
        auditor2_id: null
      };
      
      inspections.value.push(newInspection);
      inspectionMap.value[locationId] = newInspection;
    }
  } catch (err) {
    console.error('Error updating inspection date:', err);
    alert('Failed to update inspection date.');
  }
}

async function assignAuditor(locationId: number, slotIndex: number, event: Event) {
  if (!isAdmin.value) {
    alert('Only admins can assign auditors.');
    return;
  }
  
  const target = event.target as HTMLSelectElement;
  const userId = target.value;
  
  if (!userId) return;
  
  try {
    let inspection = inspectionMap.value[locationId];
    const auditorField = slotIndex === 0 ? 'auditor1_id' : 'auditor2_id';
    const otherField = slotIndex === 0 ? 'auditor2_id' : 'auditor1_id';
    
    // Check if user is already assigned
    if (inspection?.[auditorField] === userId || inspection?.[otherField] === userId) {
      alert('This auditor is already assigned to this inspection.');
      target.value = ''; // Reset select
      return;
    }
    
    const inspectionDate = inspection?.inspection_date || new Date().toISOString().split('T')[0];
    
    if (inspection && inspection.id) {
      // Update existing inspection
      const updateData: any = {
        location_id: locationId,
        inspection_date: inspectionDate,
        status: inspection.status
      };
      updateData[auditorField] = userId;
      updateData[otherField] = inspection[otherField];
      
      await api.put(`/inspections.php?id=${inspection.id}`, updateData);
      inspection[auditorField] = userId;
    } else {
      // Create new inspection with auditor
      const createData: any = {
        location_id: locationId,
        inspection_date: inspectionDate,
        status: 'Pending'
      };
      createData[auditorField] = userId;
      createData[otherField] = null;
      
      const response = await api.post('/inspections.php', createData);
      
      const newInspection: Inspection = {
        id: response.data.id,
        ...createData
      };
      
      inspections.value.push(newInspection);
      inspectionMap.value[locationId] = newInspection;
    }
    
    // Reset select
    target.value = '';
  } catch (err) {
    console.error('Error assigning auditor:', err);
    alert('Failed to assign auditor.');
  }
}

async function assignSelfAsAuditor(locationId: number, slotIndex: number) {
  if (!isAuditor.value) {
    alert('Only users with Auditor role can assign themselves to inspections.');
    return;
  }
  
  if (!currentUser.value) {
    alert('User not logged in.');
    return;
  }
  
  try {
    let inspection = inspectionMap.value[locationId];
    const auditorField = slotIndex === 0 ? 'auditor1_id' : 'auditor2_id';
    const otherField = slotIndex === 0 ? 'auditor2_id' : 'auditor1_id';
    
    // Check if user is already assigned
    if (inspection?.[auditorField] === currentUser.value.id || 
        inspection?.[otherField] === currentUser.value.id) {
      alert('You are already assigned to this inspection.');
      return;
    }
    
    const inspectionDate = inspection?.inspection_date || new Date().toISOString().split('T')[0];
    
    if (inspection && inspection.id) {
      // Update existing inspection
      const updateData: any = {
        location_id: locationId,
        inspection_date: inspectionDate,
        status: inspection.status
      };
      updateData[auditorField] = currentUser.value.id;
      updateData[otherField] = inspection[otherField];
      
      await api.put(`/inspections.php?id=${inspection.id}`, updateData);
      inspection[auditorField] = currentUser.value.id;
    } else {
      // Create new inspection with auditor
      const createData: any = {
        location_id: locationId,
        inspection_date: inspectionDate,
        status: 'Pending'
      };
      createData[auditorField] = currentUser.value.id;
      createData[otherField] = null;
      
      const response = await api.post('/inspections.php', createData);
      
      const newInspection: Inspection = {
        id: response.data.id,
        ...createData
      };
      
      inspections.value.push(newInspection);
      inspectionMap.value[locationId] = newInspection;
    }
  } catch (err) {
    console.error('Error assigning auditor:', err);
    alert('Failed to assign auditor.');
  }
}

async function removeAuditor(locationId: number, slotIndex: number) {
  const inspection = inspectionMap.value[locationId];
  if (!inspection || !inspection.id) return;
  
  const auditorField = slotIndex === 0 ? 'auditor1_id' : 'auditor2_id';
  const otherField = slotIndex === 0 ? 'auditor2_id' : 'auditor1_id';
  
  // Auditors can only remove themselves, Admins can remove anyone
  if (!isAdmin.value && inspection[auditorField] !== currentUser.value?.id) {
    alert('You can only remove your own assignment.');
    return;
  }
  
  try {
    const updateData: any = {
      location_id: locationId,
      inspection_date: inspection.inspection_date,
      status: inspection.status
    };
    updateData[auditorField] = null;
    updateData[otherField] = inspection[otherField];
    
    await api.put(`/inspections.php?id=${inspection.id}`, updateData);
    inspection[auditorField] = null;
  } catch (err) {
    console.error('Error removing auditor:', err);
    alert('Failed to remove auditor.');
  }
}

async function fetchDepartments() {
  const response = await api.get('/departments.php');
  departments.value = response.data;
}

async function fetchLocations() {
  const response = await api.get('/locations.php');
  locations.value = response.data;
}

async function fetchInspections() {
  const response = await api.get('/inspections.php');
  inspections.value = response.data;
  
  // Create a map for quick lookup
  inspectionMap.value = {};
  inspections.value.forEach(insp => {
    inspectionMap.value[insp.location_id] = insp;
  });
}

async function fetchUsers() {
  const response = await api.get('/users.php');
  users.value = response.data;
  
  // Filter users who have Auditor role
  auditorUsers.value = users.value.filter(user => 
    user.roles && user.roles.includes('Auditor')
  );
}

async function fetchCurrentUser() {
  // Get current user from session
  const userId = sessionStorage.getItem('userId');
  const userRolesStr = sessionStorage.getItem('userRoles');
  
  console.log('Fetching current user - userId:', userId, 'userRoles:', userRolesStr);
  
  if (userId) {
    try {
      // Try to get from API first
      const response = await api.get(`/users.php?id=${userId}`);
      if (response.data) {
        currentUser.value = response.data;
        console.log('Fetched current user from API:', currentUser.value);
      }
    } catch (err) {
      console.error('Error fetching current user from API:', err);
      
      // Fallback: construct from session storage
      if (userRolesStr) {
        let roles: string[] = [];
        try {
          roles = JSON.parse(userRolesStr);
        } catch {
          // last resort: split commas
          roles = userRolesStr.split(',').map(r => r.replace(/^[\[\]"]+|[\[\]"]+$/g, '').trim());
        }
        currentUser.value = {
          id: userId,
          name: sessionStorage.getItem('userName') || 'Unknown',
          roles
        };
        console.log('Using fallback user from session:', currentUser.value);
      }
    }
  }
}

async function fetchData() {
  loading.value = true;
  error.value = '';
  try {
    await Promise.all([
      fetchDepartments(),
      fetchLocations(),
      fetchInspections(),
      fetchUsers(),
      fetchCurrentUser()
    ]);
  } catch (err) {
    error.value = 'Failed to load schedule data. Please try again.';
    console.error('Error loading schedule:', err);
  } finally {
    loading.value = false;
  }
}

onMounted(async () => {
  await fetchData();
});
</script>
