<template>
  <div class="asset-upload-page">
    <div class="page-header">
      <h1>Upload Asset Inspection Data</h1>
      <p class="subtitle">Upload merged Excel or CSV file containing uninspected assets</p>
    </div>

    <div class="upload-section">
      <div class="upload-card">
        <div class="upload-icon">📁</div>
        <h3>File Requirements</h3>
        <ul class="requirements-list">
          <li>Format: Excel (.xlsx, .xls) or CSV</li>
          <li>Required columns (exact names): <strong>Label, Jenis Aset, Pegawai Penempatan, Bahagian, Lokasi Terkini</strong></li>
          <li>Label = Asset ID (unique, e.g., KPT/PKS/I/08/406)</li>
          <li>Jenis Aset = Asset Type</li>
          <li>Pegawai Penempatan = Supervisor/PIC</li>
          <li>Bahagian = Department</li>
          <li>Lokasi Terkini = Current Location</li>
          <li>First row must be headers</li>
          <li>Manually merge multiple department files before upload</li>
        </ul>

        <div class="file-input-wrapper">
          <input
            ref="fileInput"
            type="file"
            accept=".csv,.xlsx,.xls"
            @change="handleFileSelect"
            :disabled="uploading"
          />
          <button @click="triggerFileInput" class="btn-select-file" :disabled="uploading">
            {{ selectedFile ? 'Change File' : 'Select File' }}
          </button>
        </div>

        <div v-if="selectedFile" class="selected-file">
          <span class="file-name">📄 {{ selectedFile.name }}</span>
          <span class="file-size">({{ formatFileSize(selectedFile.size) }})</span>
        </div>

        <div class="form-group">
          <label for="notes">Notes (optional)</label>
          <textarea
            id="notes"
            v-model="notes"
            placeholder="Add any notes about this upload..."
            rows="3"
            :disabled="uploading"
          ></textarea>
        </div>

        <button
          @click="uploadFile"
          class="btn-upload"
          :disabled="!selectedFile || uploading"
        >
          {{ uploading ? 'Uploading...' : 'Upload File' }}
        </button>

        <div v-if="uploadProgress" class="upload-progress">
          <div class="progress-bar">
            <div class="progress-fill" :style="{ width: uploadProgress + '%' }"></div>
          </div>
          <p>{{ uploadProgress }}%</p>
        </div>

        <div v-if="uploadResult" class="upload-result" :class="uploadResult.success ? 'success' : 'error'">
          <p>{{ uploadResult.message }}</p>
          <div v-if="uploadResult.success">
            <p class="result-details">Total records uploaded: {{ uploadResult.total_records }}</p>
            <router-link to="/asset-inspection" class="btn-view-summary">View Summary</router-link>
          </div>
        </div>

        <div v-if="error" class="error-message">
          {{ error }}
        </div>
      </div>
    </div>

    <div class="upload-history">
      <h2>Upload History</h2>
      <div v-if="loadingHistory" class="loading">Loading...</div>
      <div v-else-if="uploadHistory.length === 0" class="no-data">
        No uploads yet
      </div>
      <table v-else class="history-table">
        <thead>
          <tr>
            <th>Date</th>
            <th>Filename</th>
            <th>Uploaded By</th>
            <th>Records</th>
            <th>Notes</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="batch in uploadHistory" :key="batch.id">
            <td>{{ formatDate(batch.upload_date) }}</td>
            <td>{{ batch.filename }}</td>
            <td>{{ batch.uploaded_by_name }}</td>
            <td>{{ batch.total_records }}</td>
            <td>{{ batch.notes || '-' }}</td>
            <td>
              <button @click="deleteBatch(batch.id)" class="btn-delete" title="Delete">
                🗑️
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import api from '../api';

const router = useRouter();
const fileInput = ref<HTMLInputElement | null>(null);
const selectedFile = ref<File | null>(null);
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
    selectedFile.value = target.files[0];
    uploadResult.value = null;
    error.value = '';
  }
}

function formatFileSize(bytes: number): string {
  if (bytes < 1024) return bytes + ' B';
  if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(2) + ' KB';
  return (bytes / (1024 * 1024)).toFixed(2) + ' MB';
}

async function uploadFile() {
  if (!selectedFile.value || !userId) return;

  error.value = '';
  uploadResult.value = null;
  uploading.value = true;
  uploadProgress.value = 0;

  try {
    const formData = new FormData();
    formData.append('file', selectedFile.value);
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
      message: response.data.message || 'File uploaded successfully!',
      total_records: response.data.total_records,
      batch_id: response.data.batch_id,
    };

    // Reset form
    selectedFile.value = null;
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

<style scoped>
.asset-upload-page {
  padding: 2rem;
  max-width: 1200px;
  margin: 0 auto;
}

.page-header {
  margin-bottom: 2rem;
}

.page-header h1 {
  font-size: 2rem;
  color: #1f2937;
  margin: 0 0 0.5rem 0;
}

.subtitle {
  color: #6b7280;
  margin: 0;
}

.upload-section {
  margin-bottom: 3rem;
}

.upload-card {
  background: white;
  border-radius: 8px;
  padding: 2rem;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.upload-icon {
  font-size: 3rem;
  text-align: center;
  margin-bottom: 1rem;
}

.upload-card h3 {
  text-align: center;
  color: #374151;
  margin: 0 0 1rem 0;
}

.requirements-list {
  background: #f9fafb;
  border-left: 4px solid var(--teal);
  padding: 1rem 1rem 1rem 2rem;
  margin: 0 0 2rem 0;
  color: #4b5563;
}

.requirements-list li {
  margin: 0.5rem 0;
}

.file-input-wrapper {
  display: flex;
  justify-content: center;
  margin-bottom: 1rem;
}

.file-input-wrapper input[type="file"] {
  display: none;
}

.btn-select-file {
  background: var(--teal);
  color: white;
  border: none;
  padding: 0.75rem 2rem;
  border-radius: 6px;
  font-weight: 600;
  cursor: pointer;
  transition: background 0.2s;
}

.btn-select-file:hover:not(:disabled) {
  background: var(--emerald);
}

.btn-select-file:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.selected-file {
  text-align: center;
  margin: 1rem 0;
  padding: 1rem;
  background: #f0fdfa;
  border-radius: 6px;
}

.file-name {
  font-weight: 600;
  color: #0d9488;
  margin-right: 0.5rem;
}

.file-size {
  color: #6b7280;
  font-size: 0.9rem;
}

.form-group {
  margin: 1.5rem 0;
}

.form-group label {
  display: block;
  font-weight: 600;
  color: #374151;
  margin-bottom: 0.5rem;
}

.form-group textarea {
  width: 100%;
  padding: 0.75rem;
  border: 1px solid #d1d5db;
  border-radius: 6px;
  font-family: inherit;
  resize: vertical;
}

.form-group textarea:focus {
  outline: none;
  border-color: var(--teal);
  box-shadow: 0 0 0 3px rgba(20, 184, 166, 0.1);
}

.btn-upload {
  width: 100%;
  background: var(--emerald);
  color: white;
  border: none;
  padding: 1rem;
  border-radius: 6px;
  font-weight: 600;
  font-size: 1rem;
  cursor: pointer;
  transition: background 0.2s;
}

.btn-upload:hover:not(:disabled) {
  background: #059669;
}

.btn-upload:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.upload-progress {
  margin-top: 1.5rem;
}

.progress-bar {
  width: 100%;
  height: 8px;
  background: #e5e7eb;
  border-radius: 4px;
  overflow: hidden;
  margin-bottom: 0.5rem;
}

.progress-fill {
  height: 100%;
  background: var(--teal);
  transition: width 0.3s ease;
}

.upload-progress p {
  text-align: center;
  color: #6b7280;
  font-size: 0.9rem;
}

.upload-result {
  margin-top: 1.5rem;
  padding: 1rem;
  border-radius: 6px;
  text-align: center;
}

.upload-result.success {
  background: #d1fae5;
  border: 1px solid #10b981;
  color: #065f46;
}

.upload-result.error {
  background: #fee2e2;
  border: 1px solid #ef4444;
  color: #991b1b;
}

.result-details {
  font-weight: 600;
  margin: 0.5rem 0;
}

.btn-view-summary {
  display: inline-block;
  margin-top: 1rem;
  padding: 0.5rem 1.5rem;
  background: var(--teal);
  color: white;
  text-decoration: none;
  border-radius: 6px;
  font-weight: 600;
}

.btn-view-summary:hover {
  background: var(--emerald);
}

.error-message {
  margin-top: 1rem;
  padding: 1rem;
  background: #fee2e2;
  border: 1px solid #ef4444;
  border-radius: 6px;
  color: #991b1b;
}

.upload-history h2 {
  font-size: 1.5rem;
  color: #1f2937;
  margin: 0 0 1rem 0;
}

.loading,
.no-data {
  text-align: center;
  padding: 2rem;
  color: #6b7280;
}

.history-table {
  width: 100%;
  background: white;
  border-radius: 8px;
  overflow: hidden;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.history-table thead {
  background: #f9fafb;
}

.history-table th {
  padding: 1rem;
  text-align: left;
  font-weight: 600;
  color: #374151;
  border-bottom: 1px solid #e5e7eb;
}

.history-table td {
  padding: 1rem;
  border-bottom: 1px solid #e5e7eb;
  color: #4b5563;
}

.history-table tbody tr:hover {
  background: #f9fafb;
}

.btn-delete {
  background: none;
  border: none;
  cursor: pointer;
  font-size: 1.2rem;
  padding: 0.25rem 0.5rem;
  transition: transform 0.2s;
}

.btn-delete:hover {
  transform: scale(1.2);
}
</style>
