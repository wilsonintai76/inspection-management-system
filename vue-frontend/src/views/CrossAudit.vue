<template>
  <div class="p-6 max-w-7xl mx-auto">
    <PageHeader 
      icon="🔀" 
      title="Cross-Department Audit Management"
      subtitle="Assign departments to audit other departments (ensures all auditors from Dept A can audit Dept B)"
    >
      <template #actions>
        <button @click="showAssignModal = true" class="btn btn-primary">
          + Create Department Assignment
        </button>
      </template>
    </PageHeader>

    <!-- Info Alert -->
    <div class="alert alert-info mb-6">
      <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="stroke-current shrink-0 w-6 h-6">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
      </svg>
      <div>
        <div class="font-semibold">How It Works</div>
        <div class="text-sm mt-1">
          Departments cannot audit themselves (conflict of interest). This system assigns <strong>all auditors from Department A</strong> to audit <strong>Department B</strong>. No need to assign individual auditors - the assignment applies to everyone from that department with the Auditor role.
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
          <h2 class="card-title">Department Cross-Audit Assignments ({{ assignments.length }})</h2>
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
          <div class="text-sm mt-2">Click "Create Department Assignment" to set up department-level auditing</div>
        </div>

        <div v-else class="overflow-x-auto">
          <table class="table table-zebra">
            <thead>
              <tr>
                <th>Auditor Department</th>
                <th>→</th>
                <th>Target Department (Can Audit)</th>
                <th>Assigned By</th>
                <th>Notes</th>
                <th>Status</th>
                <th>Created</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="assignment in filteredAssignments" :key="assignment.id">
                <td>
                  <div class="font-medium">{{ assignment.auditor_department_name }}</div>
                  <div class="text-xs text-gray-500">{{ assignment.auditor_department_acronym }}</div>
                </td>
                <td>
                  <span class="text-2xl text-primary">→</span>
                </td>
                <td>
                  <div class="font-medium text-primary">
                    {{ assignment.target_department_name }}
                  </div>
                  <div class="text-xs text-gray-500">{{ assignment.target_department_acronym }}</div>
                </td>
                <td class="text-sm">{{ assignment.assigned_by_name }}</td>
                <td class="text-sm max-w-xs truncate">{{ assignment.notes || '-' }}</td>
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
                    <!-- Toggle Active/Inactive -->
                    <button 
                      @click="toggleActive(assignment)" 
                      :class="assignment.active ? 'btn-warning' : 'btn-success'"
                      class="btn btn-xs"
                      :title="assignment.active ? 'Deactivate' : 'Activate'"
                    >
                      {{ assignment.active ? '⏸️' : '▶️' }}
                    </button>
                    
                    <!-- Edit Notes -->
                    <button 
                      @click="openEditNotes(assignment)" 
                      class="btn btn-xs btn-info"
                      title="Edit Notes"
                    >
                      📝
                    </button>
                    
                    <!-- Delete -->
                    <button 
                      @click="deleteAssignment(assignment)" 
                      class="btn btn-xs btn-error"
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

    <!-- Create Assignment Modal -->
    <div v-if="showAssignModal" class="modal modal-open">
      <div class="modal-box">
        <h3 class="font-bold text-lg mb-4">Create Cross-Audit Assignment</h3>
        
        <div class="form-control mb-4">
          <label class="label">
            <span class="label-text">Auditor Department (FROM)</span>
          </label>
          <select v-model="newAssignment.auditorDeptId" class="select select-bordered">
            <option value="">Select department...</option>
            <option 
              v-for="dept in departments" 
              :key="dept.id" 
              :value="dept.id"
              :disabled="dept.id === newAssignment.targetDeptId"
            >
              {{ dept.name }} ({{ dept.acronym }})
            </option>
          </select>
          <label class="label">
            <span class="label-text-alt">All auditors from this department can audit the target</span>
          </label>
        </div>

        <div class="form-control mb-4">
          <label class="label">
            <span class="label-text">Target Department (TO AUDIT)</span>
          </label>
          <select v-model="newAssignment.targetDeptId" class="select select-bordered">
            <option value="">Select department...</option>
            <option 
              v-for="dept in departments" 
              :key="dept.id" 
              :value="dept.id"
              :disabled="dept.id === newAssignment.auditorDeptId"
            >
              {{ dept.name }} ({{ dept.acronym }})
            </option>
          </select>
          <label class="label">
            <span class="label-text-alt">Department that will be audited</span>
          </label>
        </div>

        <!-- Validation Warning -->
        <div v-if="isSameDepartment" class="alert alert-error mb-4">
          <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
          <span>Departments cannot audit themselves!</span>
        </div>

        <div class="form-control mb-4">
          <label class="label">
            <span class="label-text">Notes (Optional)</span>
          </label>
          <textarea 
            v-model="newAssignment.notes" 
            class="textarea textarea-bordered" 
            rows="3"
            placeholder="Add any notes about this assignment..."
          ></textarea>
        </div>

        <div class="modal-action">
          <button @click="showAssignModal = false" class="btn">Cancel</button>
          <button 
            @click="createAssignment" 
            class="btn btn-primary" 
            :disabled="!canCreate || creating"
          >
            {{ creating ? 'Creating...' : 'Create Assignment' }}
          </button>
        </div>
      </div>
    </div>

    <!-- Edit Notes Modal -->
    <div v-if="showEditNotesModal" class="modal modal-open">
      <div class="modal-box">
        <h3 class="font-bold text-lg mb-4">Edit Notes</h3>
        
        <div class="form-control mb-4">
          <label class="label">
            <span class="label-text">Notes</span>
          </label>
          <textarea 
            v-model="editingNotes" 
            class="textarea textarea-bordered" 
            rows="4"
          ></textarea>
        </div>

        <div class="modal-action">
          <button @click="showEditNotesModal = false" class="btn">Cancel</button>
          <button 
            @click="saveNotes" 
            class="btn btn-primary"
            :disabled="saving"
          >
            {{ saving ? 'Saving...' : 'Save' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { useAuth } from '../composables/useAuth';
import api from '../api';
import PageHeader from '../components/PageHeader.vue';
import LoadingSpinner from '../components/LoadingSpinner.vue';

const { userId } = useAuth();

const loading = ref(true);
const creating = ref(false);
const saving = ref(false);
const showInactive = ref(false);
const showAssignModal = ref(false);
const showEditNotesModal = ref(false);

const assignments = ref<any[]>([]);
const departments = ref<any[]>([]);

const newAssignment = ref({
  auditorDeptId: '',
  targetDeptId: '',
  notes: ''
});

const editingAssignment = ref<any>(null);
const editingNotes = ref('');

const filteredAssignments = computed(() => {
  return showInactive.value 
    ? assignments.value 
    : assignments.value.filter(a => a.active);
});

const isSameDepartment = computed(() => {
  return newAssignment.value.auditorDeptId && 
         newAssignment.value.targetDeptId && 
         newAssignment.value.auditorDeptId === newAssignment.value.targetDeptId;
});

const canCreate = computed(() => {
  return newAssignment.value.auditorDeptId && 
         newAssignment.value.targetDeptId && 
         !isSameDepartment.value;
});

onMounted(async () => {
  await Promise.all([
    loadAssignments(),
    loadDepartments()
  ]);
  loading.value = false;
});

async function loadAssignments() {
  try {
    const response = await api.get('/cross-audit.php', {
      params: { user_id: userId.value }
    });
    assignments.value = response.data.assignments || [];
  } catch (error: any) {
    console.error('Failed to load assignments:', error);
    alert('Error loading assignments: ' + (error.response?.data?.error || error.message));
  }
}

async function loadDepartments() {
  try {
    const response = await api.get('/departments.php');
    departments.value = response.data;
  } catch (error: any) {
    console.error('Failed to load departments:', error);
  }
}

async function createAssignment() {
  if (!canCreate.value) return;
  
  creating.value = true;
  try {
    await api.post('/cross-audit.php', {
      admin_id: userId.value,
      auditor_department_id: parseInt(newAssignment.value.auditorDeptId),
      target_department_id: parseInt(newAssignment.value.targetDeptId),
      notes: newAssignment.value.notes
    });
    
    alert('Cross-audit assignment created successfully!');
    showAssignModal.value = false;
    newAssignment.value = { auditorDeptId: '', targetDeptId: '', notes: '' };
    await loadAssignments();
  } catch (error: any) {
    console.error('Failed to create assignment:', error);
    alert('Error: ' + (error.response?.data?.error || error.message));
  } finally {
    creating.value = false;
  }
}

async function toggleActive(assignment: any) {
  try {
    await api.put('/cross-audit.php', {
      admin_id: userId.value,
      assignment_id: assignment.id,
      active: !assignment.active
    });
    
    await loadAssignments();
  } catch (error: any) {
    console.error('Failed to toggle active:', error);
    alert('Error: ' + (error.response?.data?.error || error.message));
  }
}

function openEditNotes(assignment: any) {
  editingAssignment.value = assignment;
  editingNotes.value = assignment.notes || '';
  showEditNotesModal.value = true;
}

async function saveNotes() {
  if (!editingAssignment.value) return;
  
  saving.value = true;
  try {
    await api.put('/cross-audit.php', {
      admin_id: userId.value,
      assignment_id: editingAssignment.value.id,
      notes: editingNotes.value
    });
    
    showEditNotesModal.value = false;
    await loadAssignments();
  } catch (error: any) {
    console.error('Failed to save notes:', error);
    alert('Error: ' + (error.response?.data?.error || error.message));
  } finally {
    saving.value = false;
  }
}

async function deleteAssignment(assignment: any) {
  if (!confirm(`Delete assignment:\n${assignment.auditor_department_name} → ${assignment.target_department_name}?\n\nThis cannot be undone.`)) {
    return;
  }
  
  try {
    await api.delete('/cross-audit.php', {
      data: {
        admin_id: userId.value,
        assignment_id: assignment.id
      }
    });
    
    await loadAssignments();
  } catch (error: any) {
    console.error('Failed to delete assignment:', error);
    alert('Error: ' + (error.response?.data?.error || error.message));
  }
}

function formatDate(dateString: string) {
  return new Date(dateString).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  });
}
</script>
