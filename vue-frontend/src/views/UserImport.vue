<template>
  <div class="p-6 max-w-6xl mx-auto">
    <PageHeader 
      icon="👥" 
      title="Bulk User Import"
      subtitle="Upload CSV or Excel file to add multiple users at once"
    >
      <template #actions>
        <router-link to="/users" class="btn btn-ghost">
          ← Back to Users
        </router-link>
      </template>
    </PageHeader>

    <div class="mb-8">
      <div class="card bg-base-100 shadow-xl">
        <div class="card-body">
          <div class="text-center mb-6">
            <div class="text-6xl mb-4">👥</div>
            <h3 class="text-xl font-semibold text-gray-700">File Requirements</h3>
          </div>

          <div class="alert alert-info mb-6">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="stroke-current shrink-0 w-6 h-6">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <div class="text-sm">
              <ul class="list-disc list-inside space-y-1">
                <li>Format: <strong>CSV recommended</strong> (faster), Excel (.xlsx, .xls) also supported</li>
                <li>Required columns (exact names): <strong>Staff ID, Name, Email, Department, Role</strong></li>
                <li>Staff ID = 4-digit staff number (e.g., 2001)</li>
                <li>Name = Full name of user</li>
                <li>Email = Institution email address</li>
                <li>Department = Department name (must match existing departments)</li>
                <li>Role = One of: Admin, Asset Officer, Auditor, Viewer</li>
                <li>Optional columns: Phone, Personal Email</li>
                <li>First row must be headers</li>
                <li><strong>Multiple files supported:</strong> Select multiple files to import all at once</li>
              </ul>
            </div>
          </div>

          <div class="flex justify-center mb-4">
            <input
              ref="fileInput"
              type="file"
              accept=".csv,.xlsx,.xls"
              multiple
              @change="handleFileSelect"
              :disabled="uploading"
              class="hidden"
            />
            <button @click="triggerFileInput" class="btn btn-primary" :disabled="uploading">
              {{ selectedFiles.length > 0 ? 'Add More Files' : 'Select Files' }}
            </button>
          </div>

          <div v-if="selectedFiles.length > 0" class="mb-4 space-y-2">
            <div class="flex justify-between items-center mb-2">
              <h4 class="font-semibold text-gray-700">Selected Files ({{ selectedFiles.length }})</h4>
              <button @click="clearAllFiles" class="btn btn-ghost btn-sm text-error" :disabled="uploading">
                Clear All
              </button>
            </div>
            <div v-for="(file, index) in selectedFiles" :key="index" class="alert alert-success py-2">
              <div class="flex-1 flex items-center justify-between">
                <div class="flex items-center gap-2">
                  <span class="text-xl">📄</span>
                  <div>
                    <span class="font-semibold">{{ file.name }}</span>
                    <span class="ml-2 text-sm opacity-70">({{ formatFileSize(file.size) }})</span>
                  </div>
                </div>
                <button @click="removeFile(index)" class="btn btn-ghost btn-xs text-error" :disabled="uploading">
                  ✕
                </button>
              </div>
            </div>
            <div class="alert alert-info">
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="stroke-current shrink-0 w-6 h-6">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
              </svg>
              <span class="text-sm font-medium">All files will be processed together. Duplicate staff IDs will be skipped.</span>
            </div>
          </div>

          <div class="form-control mb-4">
            <label class="label">
              <span class="label-text font-medium">Notes (optional)</span>
            </label>
            <textarea
              id="notes"
              v-model="notes"
              placeholder="Add any notes about this import..."
              rows="3"
              :disabled="uploading"
              class="textarea textarea-bordered w-full"
            ></textarea>
          </div>

          <button
            @click="uploadFile"
            class="btn btn-success w-full"
            :disabled="selectedFiles.length === 0 || uploading"
          >
            <span v-if="uploading" class="loading loading-spinner"></span>
            {{ uploading ? 'Importing...' : `Import ${selectedFiles.length} File${selectedFiles.length > 1 ? 's' : ''}` }}
          </button>

          <div v-if="uploadProgress" class="mt-6">
            <div class="flex justify-between text-sm mb-2">
              <span class="font-medium">Import Progress</span>
              <span>{{ uploadProgress }}%</span>
            </div>
            <progress class="progress progress-primary w-full" :value="uploadProgress" max="100"></progress>
          </div>

          <div v-if="uploadResult" class="mt-6">
            <div v-if="uploadResult.success" class="alert alert-success">
              <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
              <div>
                <div class="font-semibold">{{ uploadResult.message }}</div>
                <div class="text-sm mt-1">
                  <div v-if="uploadResult.files_processed > 1">Files processed: {{ uploadResult.files_processed }}</div>
                  <div>Users imported: {{ uploadResult.users_imported }}</div>
                  <div v-if="uploadResult.users_skipped > 0" class="text-warning">Users skipped (duplicates): {{ uploadResult.users_skipped }}</div>
                  <div v-if="uploadResult.errors && uploadResult.errors.length > 0" class="text-error mt-2">
                    <div class="font-semibold">Errors:</div>
                    <ul class="list-disc list-inside">
                      <li v-for="(err, idx) in uploadResult.errors.slice(0, 5)" :key="idx">{{ err }}</li>
                      <li v-if="uploadResult.errors.length > 5">... and {{ uploadResult.errors.length - 5 }} more errors</li>
                    </ul>
                  </div>
                </div>
              </div>
            </div>
            <div v-else class="alert alert-error">
              <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
              <span>{{ uploadResult.message }}</span>
            </div>
            <div v-if="uploadResult.success" class="text-center mt-4">
              <router-link to="/users" class="btn btn-primary">View Users</router-link>
            </div>
          </div>

          <div v-if="error" class="alert alert-error mt-6">
            <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span>{{ error }}</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Sample Data Template -->
    <div class="card bg-base-100 shadow-xl mb-8">
      <div class="card-body">
        <h2 class="card-title text-xl mb-4">Sample CSV Template</h2>
        
        <div class="mockup-code">
          <pre><code>Staff ID,Name,Email,Department,Role,Phone,Personal Email
2001,Ahmad bin Ali,ahmad@poliku.edu.my,JABATAN KEJURUTERAAN AWAM,Auditor,0123456789,ahmad@gmail.com
2002,Siti Aminah,siti@poliku.edu.my,JABATAN KEJURUTERAAN ELEKTRIK,Asset Officer,0198765432,
2003,Lee Wei Ming,lee@poliku.edu.my,JABATAN PERDAGANGAN,Viewer,0134567890,lee@yahoo.com</code></pre>
        </div>

        <div class="mt-4">
          <button @click="downloadTemplate" class="btn btn-outline btn-primary">
            <i class="fas fa-download"></i> Download CSV Template
          </button>
        </div>
      </div>
    </div>

    <!-- Import Tips -->
    <div class="card bg-base-100 shadow-xl">
      <div class="card-body">
        <h2 class="card-title text-xl mb-4">💡 Import Tips</h2>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div class="alert alert-info">
            <div>
              <div class="font-semibold mb-1">✅ Department Names</div>
              <div class="text-sm">Department names must exactly match existing departments in the system. Check Settings → Departments first.</div>
            </div>
          </div>

          <div class="alert alert-info">
            <div>
              <div class="font-semibold mb-1">✅ Valid Roles</div>
              <div class="text-sm">Role must be one of: <code class="bg-base-300 px-1">Admin</code>, <code class="bg-base-300 px-1">Asset Officer</code>, <code class="bg-base-300 px-1">Auditor</code>, <code class="bg-base-300 px-1">Viewer</code></div>
            </div>
          </div>

          <div class="alert alert-info">
            <div>
              <div class="font-semibold mb-1">✅ Duplicate Handling</div>
              <div class="text-sm">Users with existing Staff IDs or emails will be skipped. System shows count of skipped users.</div>
            </div>
          </div>

          <div class="alert alert-info">
            <div>
              <div class="font-semibold mb-1">✅ CSV vs Excel</div>
              <div class="text-sm">CSV is <strong>4x faster</strong> and more reliable. Recommended for bulk imports.</div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import api from '../api';
import { PageHeader, LoadingSpinner, EmptyState } from '../components';

const router = useRouter();
const fileInput = ref<HTMLInputElement | null>(null);
const selectedFiles = ref<File[]>([]);
const notes = ref('');
const uploading = ref(false);
const uploadProgress = ref(0);
const uploadResult = ref<any>(null);
const error = ref('');

const userId = sessionStorage.getItem('userId');

function triggerFileInput() {
  fileInput.value?.click();
}

function handleFileSelect(event: Event) {
  const target = event.target as HTMLInputElement;
  if (target.files && target.files.length > 0) {
    const newFiles = Array.from(target.files);
    selectedFiles.value = [...selectedFiles.value, ...newFiles];
    uploadResult.value = null;
    error.value = '';
    if (fileInput.value) fileInput.value.value = '';
  }
}

function removeFile(index: number) {
  selectedFiles.value.splice(index, 1);
}

function clearAllFiles() {
  selectedFiles.value = [];
  uploadResult.value = null;
  error.value = '';
}

function formatFileSize(bytes: number): string {
  if (bytes < 1024) return bytes + ' B';
  if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(2) + ' KB';
  return (bytes / (1024 * 1024)).toFixed(2) + ' MB';
}

async function uploadFile() {
  if (selectedFiles.value.length === 0 || !userId) return;

  error.value = '';
  uploadResult.value = null;
  uploading.value = true;
  uploadProgress.value = 0;

  try {
    const formData = new FormData();
    
    selectedFiles.value.forEach((file) => {
      formData.append(`files[]`, file);
    });
    
    formData.append('user_id', userId);
    formData.append('notes', notes.value);

    const progressInterval = setInterval(() => {
      if (uploadProgress.value < 90) {
        uploadProgress.value += 10;
      }
    }, 200);

    const response = await api.post('/user-import.php', formData, {
      headers: {
        'Content-Type': 'multipart/form-data',
      },
    });

    clearInterval(progressInterval);
    uploadProgress.value = 100;

    uploadResult.value = {
      success: true,
      message: response.data.message || 'Users imported successfully!',
      users_imported: response.data.users_imported,
      users_skipped: response.data.users_skipped || 0,
      files_processed: response.data.files_processed || selectedFiles.value.length,
      errors: response.data.errors || [],
    };

    selectedFiles.value = [];
    notes.value = '';
    if (fileInput.value) fileInput.value.value = '';
  } catch (err: any) {
    error.value = err.response?.data?.error || 'Import failed. Please try again.';
    uploadResult.value = {
      success: false,
      message: error.value,
    };
  } finally {
    uploading.value = false;
    setTimeout(() => {
      uploadProgress.value = 0;
    }, 2000);
  }
}

function downloadTemplate() {
  const csv = `Staff ID,Name,Email,Department,Role,Phone,Personal Email
2001,Ahmad bin Ali,ahmad@poliku.edu.my,JABATAN KEJURUTERAAN AWAM,Auditor,0123456789,ahmad@gmail.com
2002,Siti Aminah,siti@poliku.edu.my,JABATAN KEJURUTERAAN ELEKTRIK,Asset Officer,0198765432,
2003,Lee Wei Ming,lee@poliku.edu.my,JABATAN PERDAGANGAN,Viewer,0134567890,lee@yahoo.com`;

  const blob = new Blob([csv], { type: 'text/csv' });
  const url = window.URL.createObjectURL(blob);
  const link = document.createElement('a');
  link.href = url;
  link.download = 'user_import_template.csv';
  link.click();
  window.URL.revokeObjectURL(url);
}
</script>
