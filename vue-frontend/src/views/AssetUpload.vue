<template>
  <div class="p-6 max-w-6xl mx-auto">
    <PageHeader 
      icon="📁" 
      title="Upload Asset Inspection Data"
      subtitle="Upload merged Excel or CSV file containing uninspected assets"
    />

    <div class="mb-8">
      <div class="card bg-base-100 shadow-xl">
        <div class="card-body">
          <div class="text-center mb-6">
            <div class="text-6xl mb-4">📁</div>
            <h3 class="text-xl font-semibold text-gray-700">File Requirements</h3>
          </div>

          <div class="alert alert-info mb-6">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="stroke-current shrink-0 w-6 h-6">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <div class="text-sm">
              <ul class="list-disc list-inside space-y-1">
                <li>Format: Excel (.xlsx, .xls) or CSV</li>
                <li>Required columns (exact names): <strong>Label, Jenis Aset, Pegawai Penempatan, Bahagian, Lokasi Terkini</strong></li>
                <li>Label = Asset ID (unique, e.g., KPT/PKS/I/08/406)</li>
                <li>Jenis Aset = Asset Type</li>
                <li>Pegawai Penempatan = Supervisor/PIC</li>
                <li>Bahagian = Department</li>
                <li>Lokasi Terkini = Current Location</li>
                <li>First row must be headers</li>
                <li><strong>Multiple files supported:</strong> Select multiple department files and they will be merged automatically</li>
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
              <span class="text-sm font-medium">All files will be merged into a single batch upload</span>
            </div>
          </div>

          <div class="form-control mb-4">
            <label class="label">
              <span class="label-text font-medium">Notes (optional)</span>
            </label>
            <textarea
              id="notes"
              v-model="notes"
              placeholder="Add any notes about this upload..."
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
            {{ uploading ? 'Uploading...' : `Upload ${selectedFiles.length} File${selectedFiles.length > 1 ? 's' : ''}` }}
          </button>

          <div v-if="uploadProgress" class="mt-6">
            <div class="flex justify-between text-sm mb-2">
              <span class="font-medium">Upload Progress</span>
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
                  <div>Total records uploaded: {{ uploadResult.total_records }}</div>
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
              <router-link to="/asset-inspection" class="btn btn-primary">View Summary</router-link>
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

    <div class="card bg-base-100 shadow-xl">
      <div class="card-body">
        <h2 class="card-title text-xl mb-4">Upload History</h2>
        
        <LoadingSpinner v-if="loadingHistory" message="Loading history..." />
        
        <EmptyState 
          v-else-if="uploadHistory.length === 0"
          icon="📭"
          title="No uploads yet"
          message="Upload your first file to see history here"
        />
        
        <div v-else class="overflow-x-auto">
          <table class="table table-zebra w-full">
            <thead>
              <tr>
                <th class="text-xs font-semibold uppercase">Date</th>
                <th class="text-xs font-semibold uppercase">Filename</th>
                <th class="text-xs font-semibold uppercase">Uploaded By</th>
                <th class="text-xs font-semibold uppercase">Records</th>
                <th class="text-xs font-semibold uppercase">Notes</th>
                <th class="text-xs font-semibold uppercase">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="batch in uploadHistory" :key="batch.id">
                <td>{{ formatDate(batch.upload_date) }}</td>
                <td class="font-medium">{{ batch.filename }}</td>
                <td>{{ batch.uploaded_by_name }}</td>
                <td><Badge variant="info" :label="String(batch.total_records)" /></td>
                <td class="text-gray-600">{{ batch.notes || '-' }}</td>
                <td>
                  <button @click="deleteBatch(batch.id)" class="btn btn-ghost btn-sm text-error" title="Delete">
                    🗑️
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
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import api from '../api';
import { PageHeader, LoadingSpinner, EmptyState, Badge } from '../components';

const router = useRouter();
const fileInput = ref<HTMLInputElement | null>(null);
const selectedFiles = ref<File[]>([]);
const notes = ref('');
const uploading = ref(false);
const uploadProgress = ref(0);
const uploadResult = ref<any>(null);
const error = ref('');
const uploadHistory = ref<any[]>([]);
const loadingHistory = ref(false);

const userId = sessionStorage.getItem('userId');

function triggerFileInput() {
  fileInput.value?.click();
}

function handleFileSelect(event: Event) {
  const target = event.target as HTMLInputElement;
  if (target.files && target.files.length > 0) {
    // Add new files to existing selection
    const newFiles = Array.from(target.files);
    selectedFiles.value = [...selectedFiles.value, ...newFiles];
    uploadResult.value = null;
    error.value = '';
    // Reset input to allow selecting same files again
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
    
    // Append all files
    selectedFiles.value.forEach((file, index) => {
      formData.append(`files[]`, file);
    });
    
    formData.append('user_id', userId);
    formData.append('notes', notes.value);

    // Simulate progress (since we can't track real upload progress easily)
    const progressInterval = setInterval(() => {
      if (uploadProgress.value < 90) {
        uploadProgress.value += 10;
      }
    }, 200);

    const response = await api.post('/asset-uploads.php', formData, {
      headers: {
        'Content-Type': 'multipart/form-data',
      },
    });

    clearInterval(progressInterval);
    uploadProgress.value = 100;

    uploadResult.value = {
      success: true,
      message: response.data.message || 'Files uploaded and merged successfully!',
      total_records: response.data.total_records,
      batch_id: response.data.batch_id,
      files_processed: response.data.files_processed || selectedFiles.value.length,
    };

    // Reset form
    selectedFiles.value = [];
    notes.value = '';
    if (fileInput.value) fileInput.value.value = '';

    // Refresh history
    loadUploadHistory();
  } catch (err: any) {
    error.value = err.response?.data?.error || 'Upload failed. Please try again.';
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

async function loadUploadHistory() {
  loadingHistory.value = true;
  try {
    const response = await api.get('/asset-uploads.php');
    uploadHistory.value = response.data;
  } catch (err) {
    console.error('Failed to load upload history:', err);
  } finally {
    loadingHistory.value = false;
  }
}

async function deleteBatch(batchId: number) {
  if (!confirm('Are you sure you want to delete this upload batch? This will remove all associated asset records.')) {
    return;
  }

  try {
    await api.delete(`/asset-uploads.php?id=${batchId}`);
    loadUploadHistory();
  } catch (err: any) {
    error.value = err.response?.data?.error || 'Failed to delete batch';
  }
}

function formatDate(dateStr: string): string {
  return new Date(dateStr).toLocaleString('en-MY', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  });
}

onMounted(() => {
  loadUploadHistory();
});
</script>
