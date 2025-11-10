<template>
  <div class="p-6 max-w-7xl mx-auto">
    <PageHeader 
      icon="🔀" 
      title="Cross-Department Audit Management"
      subtitle="Assign auditors to audit departments they don't belong to (ensures audit independence)"
    >
      <template #actions>
        <button @click="showAssignModal = true" class="btn btn-primary">
          + Assign Auditor to Department
        </button>
      </template>
    </PageHeader>

    <!-- Info Alert -->
    <div class="alert alert-info mb-6">
      <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="stroke-current shrink-0 w-6 h-6">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
      </svg>
      <div>
        <div class="font-semibold">Why Cross-Department Auditing?</div>
        <div class="text-sm mt-1">
          Auditors should NOT audit their own department (conflict of interest). Cross-audit assignments ensure independence and objectivity by assigning auditors to check other departments.
        </div>
      </div>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="flex justify-center py-12">
      <LoadingSpinner />
    </div>

    <!-- Assignments Table -->
    <div v-else class="card bg-base-100 shadow-xl">
      <div class="card-body">
        <div class="flex justify-between items-center mb-4">
          <h2 class="card-title">Current Assignments ({{ assignments.length }})</h2>
          <div class="form-control">
            <label class="label cursor-pointer gap-2">
              <span class="label-text">Show Inactive</span>
              <input 
                type="checkbox" 
                v-model="showInactive" 
                class="checkbox checkbox-sm"
              />
            </label>
          </div>
        </div>

        <div v-if="assignments.length === 0" class="text-center py-8 text-gray-500">
          <div class="text-6xl mb-4">🔀</div>
          <div class="text-lg font-medium">No cross-audit assignments yet</div>
          <div class="text-sm mt-2">Click "Assign Auditor to Department" to create the first assignment</div>
        </div>

        <div v-else class="overflow-x-auto">
          <table class="table table-zebra">
            <thead>
              <tr>
                <th>Auditor</th>
                <th>Own Dept</th>
                <th>→</th>
                <th>Assigned to Audit</th>
                <th>Assigned By</th>
                <th>Status</th>
                <th>Date</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="assignment in filteredAssignments" :key="assignment.id">
                <td>
                  <div class="font-medium">{{ assignment.auditor_name }}</div>
                  <div class="text-xs text-gray-500">{{ assignment.auditor_staff_id }}</div>
                </td>
                <td>
                  <div class="badge badge-ghost badge-sm">
                    {{ assignment.auditor_own_department_name || 'No Dept' }}
                  </div>
                </td>
                <td>
                  <span class="text-2xl">→</span>
                </td>
                <td>
                  <div class="font-medium text-primary">
                    {{ assignment.assigned_department_name }}
                  </div>
                  <div class="text-xs text-gray-500">{{ assignment.assigned_department_acronym }}</div>
                </td>
                <td class="text-sm">{{ assignment.assigned_by_name }}</td>
                <td>
                  <div class="badge" :class="assignment.active ? 'badge-success' : 'badge-error'">
                    {{ assignment.active ? 'Active' : 'Inactive' }}
                  </div>
                </td>
                <td class="text-xs text-gray-500">
                  {{ formatDate(assignment.created_at) }}
                </td>
                <td>
                  <div class="flex gap-1">
                    <button 
                      v-if="assignment.active"
                      @click="deactivateAssignment(assignment.id)"
                      class="btn btn-ghost btn-xs text-warning"
                      title="Deactivate"
                    >
                      ⏸️
                    </button>
                    <button 
                      v-else
                      @click="activateAssignment(assignment.id)"
                      class="btn btn-ghost btn-xs text-success"
                      title="Activate"
                    >
                      ▶️
                    </button>
                    <button 
                      @click="editNotes(assignment)"
                      class="btn btn-ghost btn-xs"
                      title="Edit Notes"
                    >
                      📝
                    </button>
                    <button 
                      @click="deleteAssignment(assignment.id)"
                      class="btn btn-ghost btn-xs text-error"
                      title="Delete"
                    >
                      🗑️
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Assignment Modal -->
    <dialog :class="{'modal modal-open': showAssignModal}" class="modal">
      <div class="modal-box max-w-2xl">
        <h3 class="font-bold text-lg mb-4">Assign Auditor to Department</h3>
        
        <div class="form-control mb-4">
          <label class="label">
            <span class="label-text font-medium">Select Auditor</span>
          </label>
          <select v-model="newAssignment.auditorId" class="select select-bordered w-full">
            <option value="">-- Choose Auditor --</option>
            <option v-for="auditor in auditors" :key="auditor.id" :value="auditor.id">
              {{ auditor.name }} ({{ auditor.staff_id }}) - {{ auditor.department_name || 'No Dept' }}
            </option>
          </select>
        </div>

        <div class="form-control mb-4">
          <label class="label">
            <span class="label-text font-medium">Assign to Department</span>
          </label>
          <select v-model="newAssignment.departmentId" class="select select-bordered w-full">
            <option value="">-- Choose Department --</option>
            <option 
              v-for="dept in availableDepartments" 
              :key="dept.id" 
              :value="dept.id"
              :disabled="isOwnDepartment(dept.id)"
            >
              {{ dept.name }} {{ isOwnDepartment(dept.id) ? '(Own Dept - Not Allowed)' : '' }}
            </option>
          </select>
          <label class="label">
            <span class="label-text-alt text-info">Auditor cannot audit their own department</span>
          </label>
        </div>

        <div class="form-control mb-4">
          <label class="label">
            <span class="label-text font-medium">Notes (Optional)</span>
          </label>
          <textarea 
            v-model="newAssignment.notes" 
            class="textarea textarea-bordered" 
            rows="3"
            placeholder="Any special instructions or notes..."
          ></textarea>
        </div>

        <div v-if="assignError" class="alert alert-error mb-4">
          <span>{{ assignError }}</span>
        </div>

        <div class="modal-action">
          <button @click="closeAssignModal" class="btn btn-ghost">Cancel</button>
          <button 
            @click="createAssignment" 
            class="btn btn-primary"
            :disabled="!newAssignment.auditorId || !newAssignment.departmentId || assigning"
          >
            <span v-if="assigning" class="loading loading-spinner"></span>
            {{ assigning ? 'Assigning...' : 'Assign' }}
          </button>
        </div>
      </div>
      <form method="dialog" class="modal-backdrop" @click="closeAssignModal"></form>
    </dialog>

    <!-- Edit Notes Modal -->
    <dialog :class="{'modal modal-open': showNotesModal}" class="modal">
      <div class="modal-box">
        <h3 class="font-bold text-lg mb-4">Edit Assignment Notes</h3>
        
        <div class="form-control mb-4">
          <label class="label">
            <span class="label-text font-medium">Notes</span>
          </label>
          <textarea 
            v-model="editingNotes" 
            class="textarea textarea-bordered" 
            rows="4"
          ></textarea>
        </div>

        <div class="modal-action">
          <button @click="showNotesModal = false" class="btn btn-ghost">Cancel</button>
          <button @click="updateNotes" class="btn btn-primary">Save</button>
        </div>
      </div>
      <form method="dialog" class="modal-backdrop" @click="showNotesModal = false"></form>
    </dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import api from '../api';
import { PageHeader, LoadingSpinner } from '../components';

const loading = ref(true);
const assignments = ref<any[]>([]);
const auditors = ref<any[]>([]);
const departments = ref<any[]>([]);
const showInactive = ref(false);
const showAssignModal = ref(false);
const showNotesModal = ref(false);
const assigning = ref(false);
const assignError = ref('');
const editingAssignment = ref<any>(null);
const editingNotes = ref('');

const newAssignment = ref({
  auditorId: '',
  departmentId: '',
  notes: ''
});

const userId = sessionStorage.getItem('userId');

const filteredAssignments = computed(() => {
  if (showInactive.value) {
    return assignments.value;
  }
  return assignments.value.filter(a => a.active);
});

const availableDepartments = computed(() => {
  return departments.value;
});

function isOwnDepartment(deptId: number): boolean {
  if (!newAssignment.value.auditorId) return false;
  const auditor = auditors.value.find(a => a.id === newAssignment.value.auditorId);
  return auditor && auditor.department_id === deptId;
}

async function fetchData() {
  loading.value = true;
  try {
    // Fetch assignments
    const assignmentsRes = await api.get(`/cross-audit.php?user_id=${userId}`);
    assignments.value = assignmentsRes.data.assignments || [];

    // Fetch auditors
    const auditorsRes = await api.get('/users.php');
    const allUsers = auditorsRes.data.users || [];
    auditors.value = allUsers.filter((u: any) => 
      u.roles && u.roles.includes('Auditor')
    );

    // Fetch departments
    const deptsRes = await api.get('/departments.php');
    departments.value = deptsRes.data.departments || [];
  } catch (err: any) {
    console.error('Failed to fetch data:', err);
  } finally {
    loading.value = false;
  }
}

async function createAssignment() {
  if (!newAssignment.value.auditorId || !newAssignment.value.departmentId) return;
  
  assigning.value = true;
  assignError.value = '';
  
  try {
    await api.post('/cross-audit.php', {
      admin_id: userId,
      auditor_id: newAssignment.value.auditorId,
      department_id: parseInt(newAssignment.value.departmentId),
      notes: newAssignment.value.notes
    });
    
    closeAssignModal();
    fetchData();
  } catch (err: any) {
    assignError.value = err.response?.data?.error || 'Failed to create assignment';
  } finally {
    assigning.value = false;
  }
}

async function activateAssignment(id: number) {
  try {
    await api.put('/cross-audit.php', {
      admin_id: userId,
      assignment_id: id,
      active: true
    });
    fetchData();
  } catch (err: any) {
    alert('Failed to activate: ' + (err.response?.data?.error || 'Unknown error'));
  }
}

async function deactivateAssignment(id: number) {
  try {
    await api.put('/cross-audit.php', {
      admin_id: userId,
      assignment_id: id,
      active: false
    });
    fetchData();
  } catch (err: any) {
    alert('Failed to deactivate: ' + (err.response?.data?.error || 'Unknown error'));
  }
}

function editNotes(assignment: any) {
  editingAssignment.value = assignment;
  editingNotes.value = assignment.notes || '';
  showNotesModal.value = true;
}

async function updateNotes() {
  if (!editingAssignment.value) return;
  
  try {
    await api.put('/cross-audit.php', {
      admin_id: userId,
      assignment_id: editingAssignment.value.id,
      notes: editingNotes.value
    });
    showNotesModal.value = false;
    fetchData();
  } catch (err: any) {
    alert('Failed to update notes: ' + (err.response?.data?.error || 'Unknown error'));
  }
}

async function deleteAssignment(id: number) {
  if (!confirm('Are you sure you want to delete this assignment?')) return;
  
  try {
    await api.delete('/cross-audit.php', {
      data: {
        admin_id: userId,
        assignment_id: id
      }
    });
    fetchData();
  } catch (err: any) {
    alert('Failed to delete: ' + (err.response?.data?.error || 'Unknown error'));
  }
}

function closeAssignModal() {
  showAssignModal.value = false;
  newAssignment.value = {
    auditorId: '',
    departmentId: '',
    notes: ''
  };
  assignError.value = '';
}

function formatDate(dateStr: string): string {
  return new Date(dateStr).toLocaleDateString('en-MY', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  });
}

onMounted(() => {
  fetchData();
});
</script>
