<template>
  <div class="schedule-page">
    <!-- Page Header -->
    <div class="page-header">
      <div class="header-left">
        <span class="breadcrumb-icon">📋</span>
        <h1 class="page-title">Schedule</h1>
      </div>
    </div>

    <!-- Schedule Card -->
    <div class="schedule-card">
      <div class="card-header">
        <h2 class="card-title">Inspection Schedule</h2>
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
        <p>Loading schedule...</p>
      </div>

      <!-- Error State -->
      <div v-else-if="error" class="error-state">
        <p class="error-message">{{ error }}</p>
        <button @click="fetchData" class="btn-retry">Retry</button>
      </div>

      <!-- Schedule Table -->
      <div v-else class="table-container">
        <table class="schedule-table">
          <thead>
            <tr>
              <th class="sortable">
                Location
                <span class="sort-icon">↕</span>
              </th>
              <th class="sortable">
                Date
                <span class="sort-icon">↕</span>
              </th>
              <th>Auditors</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="filteredLocations.length === 0">
              <td colspan="3" class="no-data">
                <span class="no-data-icon">📭</span>
                <p>No locations found</p>
              </td>
            </tr>
            <tr v-for="location in filteredLocations" :key="location.id">
              <td>
                <div class="location-cell">
                  <div class="location-name">{{ location.name }}</div>
                  <div class="location-meta">
                    <span class="department-name">{{ getDepartmentName(location.department_id) }}</span>
                  </div>
                </div>
              </td>
              <td>
                <div class="date-cell">
                  <span class="date-icon">📅</span>
                  <input 
                    type="date" 
                    :value="getInspectionDate(location.id)"
                    @change="updateInspectionDate(location.id, $event)"
                    class="date-input"
                    :disabled="!canSetDate"
                  />
                </div>
              </td>
              <td>
                <div class="auditors-cell">
                  <div 
                    v-for="(auditor, index) in getLocationAuditors(location.id)" 
                    :key="index" 
                    class="auditor-item"
                  >
                    <span class="auditor-icon">👤</span>
                    
                    <!-- Admin: Show dropdown to assign any auditor -->
                    <select 
                      v-if="isAdmin && auditor.isEmpty"
                      @change="assignAuditor(location.id, index, $event)"
                      class="auditor-select"
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
                    <span v-if="!auditor.isEmpty" class="auditor-label">{{ auditor.name }}</span>
                    
                    <!-- Auditor (non-admin): Show label for unassigned slot -->
                    <span v-if="auditor.isEmpty && isAuditor && !isAdmin" class="auditor-label">{{ auditor.name }}</span>
                    
                    <!-- Add button - only for Auditors to add themselves -->
                    <button 
                      v-if="auditor.isEmpty && isAuditor" 
                      class="btn-add-auditor"
                      @click="assignSelfAsAuditor(location.id, index)"
                      title="Assign yourself"
                    >
                      <span class="plus-icon">⊕</span>
                    </button>
                    
                    <!-- Remove button - Auditors can remove themselves, Admin can remove anyone -->
                    <button 
                      v-if="!auditor.isEmpty && (auditor.isCurrentUser || isAdmin)" 
                      class="btn-remove-auditor"
                      @click="removeAuditor(location.id, index)"
                      :title="auditor.isCurrentUser ? 'Remove yourself' : 'Remove auditor'"
                    >
                      <span class="remove-icon">⊖</span>
                    </button>
                  </div>
                </div>
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

<style scoped>
.schedule-page {
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

.schedule-card {
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
  padding: 1rem 1.5rem;
  text-align: left;
  font-size: 0.75rem;
  font-weight: 600;
  color: #6b7280;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.schedule-table th.sortable {
  cursor: pointer;
  user-select: none;
}

.schedule-table th.sortable:hover {
  background: #f3f4f6;
}

.sort-icon {
  margin-left: 0.5rem;
  color: #9ca3af;
  font-size: 0.875rem;
}

.schedule-table tbody tr {
  border-bottom: 1px solid #f3f4f6;
  transition: background-color 0.15s;
}

.schedule-table tbody tr:hover {
  background: #f9fafb;
}

.schedule-table td {
  padding: 1.25rem 1.5rem;
}

.location-cell {
  display: flex;
  flex-direction: column;
  gap: 0.375rem;
}

.location-name {
  font-weight: 600;
  color: #1f2937;
  font-size: 0.9375rem;
}

.location-meta {
  display: flex;
  gap: 0.5rem;
  font-size: 0.8125rem;
  color: #6b7280;
}

.department-name {
  color: #6b7280;
}

.date-cell {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  color: #374151;
}

.date-icon {
  font-size: 1rem;
  color: #14b8a6;
}

.date-input {
  padding: 0.375rem 0.625rem;
  border: 1px solid #d1d5db;
  border-radius: 0.375rem;
  font-size: 0.875rem;
  color: #374151;
  cursor: pointer;
  transition: border-color 0.15s, box-shadow 0.15s;
}

.date-input:focus {
  outline: none;
  border-color: #14b8a6;
  box-shadow: 0 0 0 3px rgba(20, 184, 166, 0.1);
}

.date-input:disabled {
  background: #f3f4f6;
  cursor: not-allowed;
  color: #9ca3af;
}

.auditors-cell {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.auditor-item {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.auditor-icon {
  font-size: 1rem;
  color: #6b7280;
}

.auditor-label {
  font-size: 0.875rem;
  color: #6b7280;
}

.auditor-label:not(:has(+ .btn-add-auditor)) {
  color: #374151;
}

.auditor-select {
  padding: 0.375rem 2rem 0.375rem 0.5rem;
  border: 1px solid #d1d5db;
  border-radius: 0.375rem;
  font-size: 0.875rem;
  color: #374151;
  background: white;
  cursor: pointer;
  appearance: none;
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%236B7280' d='M6 9L1 4h10z'/%3E%3C/svg%3E");
  background-repeat: no-repeat;
  background-position: right 0.5rem center;
  transition: border-color 0.15s, box-shadow 0.15s;
}

.auditor-select:focus {
  outline: none;
  border-color: #14b8a6;
  box-shadow: 0 0 0 3px rgba(20, 184, 166, 0.1);
}

.btn-add-auditor,
.btn-remove-auditor {
  padding: 0;
  width: 20px;
  height: 20px;
  border: none;
  background: transparent;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 0.25rem;
  transition: all 0.15s;
}

.btn-add-auditor:hover {
  background: #d1fae5;
}

.btn-remove-auditor:hover {
  background: #fee2e2;
}

.plus-icon {
  color: #10b981;
  font-size: 1.125rem;
}

.remove-icon {
  color: #ef4444;
  font-size: 1.125rem;
}

@media (max-width: 768px) {
  .schedule-page {
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
