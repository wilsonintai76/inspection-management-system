<template>
  <div class="login-page">
    <!-- Top Header Bar -->
    <div class="top-header">
      <div class="header-content">
        <div class="logo-header">
          <div class="logo-icon-large">🏢</div>
          <div class="logo-text-group">
            <h1 class="system-name">INSPECTABLE</h1>
            <p class="system-subtitle">Asset & Location Inspection Management System</p>
          </div>
        </div>
      </div>
    </div>

    <div class="top-section">
      <!-- Left panel: Login form -->
      <div class="login-panel">
        <div class="login-content">
          <div class="logo-section">
            <div class="logo-icon">🏢</div>
            <span class="logo-text">LOGO HERE</span>
          </div>

          <h1 class="signin-title">Sign in</h1>
          <p class="signup-prompt">
            Don't have an account? <router-link to="/register" class="link-create">Create now</router-link>
          </p>

          <form @submit.prevent="handleLogin" class="login-form">
            <div class="form-group">
              <label for="staff-id">Staff ID</label>
              <input
                id="staff-id"
                v-model="staffId"
                type="text"
                placeholder="Enter your 4-digit staff ID"
                required
                maxlength="4"
                pattern="\d{4}"
                autocomplete="username"
              />
            </div>

            <div class="form-group">
              <label for="password">Password</label>
              <input
                id="password"
                v-model="password"
                type="password"
                placeholder="••••••••"
                required
                autocomplete="current-password"
              />
            </div>

            <div class="form-row">
              <label class="checkbox-label">
                <input type="checkbox" v-model="rememberMe" />
                <span>Remember me</span>
              </label>
              <router-link to="/forgot-password" class="link-forgot">Forgot Password?</router-link>
            </div>

            <button type="submit" class="btn-signin" :disabled="isLoading">
              {{ isLoading ? 'Signing in...' : 'Sign in' }}
            </button>

            <p v-if="error" class="error">{{ error }}</p>
          </form>
        </div>
      </div>

      <!-- Right panel: System Overview -->
      <div class="overview-panel">
        <div class="overview-content">
          <h2 class="overview-title">System Overview</h2>
          <div class="overview-description">
            <p class="overview-text">
              Welcome to the Inspectable Asset and Location Inspection Management System. This platform provides 
              comprehensive tools for managing and tracking asset inspections across multiple departments.
            </p>
            <p class="overview-text">
              Monitor inspection progress in real-time, assign auditors to locations, and maintain detailed 
              records of all assets. Our system ensures accountability and streamlines the inspection workflow 
              for enhanced operational efficiency.
            </p>
          </div>
        </div>
      </div>
    </div>

    <!-- Bottom Section: Statistics Footer -->
    <div class="stats-footer">
      <h3 class="stats-footer-title">Inspection Progress</h3>
      
      <div class="stats-grid">
        <!-- Asset Inspection Progress -->
        <div class="stat-card">
          <div class="stat-header">
            <div class="stat-icon">📦</div>
            <h4 class="stat-title">Asset Inspection</h4>
          </div>
          <div class="stat-body">
            <div v-if="loadingAssets" class="stat-loading">
              <div class="mini-spinner"></div>
            </div>
            <div v-else-if="assetSummary.length === 0" class="stat-empty">
              No data
            </div>
            <div v-else class="stat-list">
              <div v-for="row in assetSummary" :key="row.department_id" class="stat-item">
                <div class="stat-item-header">
                  <span class="stat-dept-name">{{ row.department_name }}</span>
                  <span class="stat-percentage" :class="getPercentageClass(row.percentage_inspected)">
                    {{ row.percentage_inspected }}%
                  </span>
                </div>
                <div class="stat-progress-bar">
                  <div 
                    class="stat-progress-fill asset-fill" 
                    :style="{ width: row.percentage_inspected + '%' }"
                  ></div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Location Inspection Status -->
        <div class="stat-card">
          <div class="stat-header">
            <div class="stat-icon">📍</div>
            <h4 class="stat-title">Location Inspection</h4>
          </div>
          <div class="stat-body">
            <div v-if="loadingLocations" class="stat-loading">
              <div class="mini-spinner"></div>
            </div>
            <div v-else-if="locationProgress.length === 0" class="stat-empty">
              No data
            </div>
            <div v-else class="stat-list">
              <div v-for="dept in locationProgress" :key="dept.name" class="stat-item">
                <div class="stat-item-header">
                  <span class="stat-dept-name">{{ dept.name }}</span>
                  <span class="stat-percentage" :class="getPercentageClass(dept.percentage)">
                    {{ dept.percentage }}%
                  </span>
                </div>
                <div class="stat-progress-bar">
                  <div 
                    class="stat-progress-fill location-fill" 
                    :style="{ width: dept.percentage + '%' }"
                  ></div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import api from '../api';

const router = useRouter();
const staffId = ref('');
const password = ref('');
const rememberMe = ref(false);
const isLoading = ref(false);
const error = ref('');

// Statistics data
const loadingAssets = ref(false);
const loadingLocations = ref(false);
const assetSummary = ref<any[]>([]);
const locationProgress = ref<any[]>([]);

async function handleLogin() {
  error.value = '';
  isLoading.value = true;

  try {
    // Call login API
    const response = await api.post('/login.php', {
      staff_id: staffId.value,
      password: password.value
    });

    if (response.data.success) {
      const { user, must_change_password } = response.data;
      
      // Store login state
      sessionStorage.setItem('isLoggedIn', 'true');
      sessionStorage.setItem('userId', user.id);
      sessionStorage.setItem('staffId', user.staff_id);
      sessionStorage.setItem('userEmail', user.email);
      sessionStorage.setItem('userName', user.name);
      sessionStorage.setItem('userRoles', JSON.stringify(user.roles));
      sessionStorage.setItem('userDepartmentId', user.department_id || '');
      
      if (rememberMe.value) {
        localStorage.setItem('rememberStaffId', staffId.value);
      }

      // Check if password change is required
      if (must_change_password) {
        sessionStorage.setItem('mustChangePassword', 'true');
        router.push('/profile?change-password=true');
      } else {
        router.push('/dashboard');
      }
    }
  } catch (err: any) {
    if (err.response?.data?.error) {
      error.value = err.response.data.error;
      
      // Check if email verification is required
      if (err.response?.data?.requires_verification) {
        const email = err.response.data.email;
        if (confirm('Your email is not verified. Would you like to resend the verification email?')) {
          try {
            await api.post('/resend-verification.php', { email });
            alert('Verification email sent! Please check your inbox.');
          } catch (resendErr) {
            console.error('Failed to resend verification:', resendErr);
          }
        }
      }
    } else {
      error.value = 'Login failed. Please check your credentials and try again.';
    }
  } finally {
    isLoading.value = false;
  }
}

async function loadAssetSummary() {
  loadingAssets.value = true;
  try {
    const params = { action: 'summary' };
    const response = await api.get('/asset-summary.php', { params });
    assetSummary.value = response.data.summary || [];
  } catch (err) {
    console.error('Failed to load asset summary:', err);
    assetSummary.value = [];
  } finally {
    loadingAssets.value = false;
  }
}

async function loadLocationProgress() {
  loadingLocations.value = true;
  try {
    const [deptResp, locResp, inspResp] = await Promise.all([
      api.get('/departments.php'),
      api.get('/locations.php'),
      api.get('/inspections.php'),
    ]);
    
    const departments = deptResp.data;
    const locations = locResp.data;
    const inspections = inspResp.data;
    
    // Build inspection map (latest per location)
    const inspectionByLocation: Record<number, any> = {};
    for (const insp of inspections) {
      if (!inspectionByLocation[insp.location_id]) {
        inspectionByLocation[insp.location_id] = insp;
      }
    }
    
    // Calculate progress per department
    const progress: any[] = [];
    for (const dept of departments) {
      const deptLocations = locations.filter((l: any) => l.department_id === dept.id);
      const total = deptLocations.length;
      let completed = 0;
      
      for (const loc of deptLocations) {
        const insp = inspectionByLocation[loc.id];
        if (insp?.status === 'Complete') completed += 1;
      }
      
      const percentage = total > 0 ? Math.round((completed / total) * 100) : 0;
      progress.push({ name: dept.name, total, completed, percentage });
    }
    
    locationProgress.value = progress;
  } catch (err) {
    console.error('Failed to load location progress:', err);
    locationProgress.value = [];
  } finally {
    loadingLocations.value = false;
  }
}

function getPercentageClass(percentage: number): string {
  if (percentage >= 75) return 'percentage-high';
  if (percentage >= 50) return 'percentage-medium';
  if (percentage >= 25) return 'percentage-low';
  return 'percentage-very-low';
}

onMounted(async () => {
  // Load statistics data when component mounts
  await Promise.all([loadAssetSummary(), loadLocationProgress()]);
  
  // Check if staff ID was remembered
  const remembered = localStorage.getItem('rememberStaffId');
  if (remembered) {
    staffId.value = remembered;
    rememberMe.value = true;
  }
});
</script>

<style scoped>
.login-page {
  display: flex;
  flex-direction: column;
  min-height: 100vh;
  background: linear-gradient(135deg, rgba(99, 102, 241, 0.8), rgba(139, 92, 246, 0.8)),
              url('https://images.unsplash.com/photo-1506905925346-21bda4d32df4?q=80&w=2070') center/cover;
}

/* Top Header */
.top-header {
  background: rgba(255, 255, 255, 0.98);
  backdrop-filter: blur(10px);
  border-bottom: 2px solid rgba(139, 92, 246, 0.2);
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
  z-index: 100;
}

.header-content {
  max-width: 1400px;
  margin: 0 auto;
  padding: 1.25rem 2rem;
}

.logo-header {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.logo-icon-large {
  font-size: 3rem;
  line-height: 1;
  filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.1));
}

.logo-text-group {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.system-name {
  font-size: 1.75rem;
  font-weight: 800;
  color: #1f2937;
  margin: 0;
  letter-spacing: 1px;
  background: linear-gradient(135deg, #6366f1, #8b5cf6);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}

.system-subtitle {
  font-size: 0.875rem;
  color: #6b7280;
  margin: 0;
  font-weight: 500;
  letter-spacing: 0.5px;
}

.top-section {
  display: flex;
  flex: 1;
  min-height: 0;
  gap: 2rem;
  padding: 2rem;
}

/* Left panel - Login form */
.login-panel {
  flex: 0 0 400px;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 2rem;
  background: white;
  border-radius: 12px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.login-content {
  width: 100%;
  max-width: 350px;
}

.logo-section {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin-bottom: 1.5rem;
}

.logo-icon {
  font-size: 1.5rem;
  color: var(--teal);
}

.logo-text {
  font-size: 0.875rem;
  font-weight: 600;
  color: #6b7280;
  text-transform: uppercase;
  letter-spacing: 1px;
}

.signin-title {
  font-size: 1.75rem;
  font-weight: 700;
  color: #1f2937;
  margin: 0 0 0.5rem 0;
}

.signup-prompt {
  color: #6b7280;
  margin: 0 0 1.5rem 0;
  font-size: 0.875rem;
}

.link-create {
  color: var(--teal);
  text-decoration: none;
  font-weight: 600;
}

.link-create:hover {
  text-decoration: underline;
}

.login-form {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.form-group label {
  font-weight: 600;
  color: #374151;
  font-size: 0.9rem;
}

.form-group input {
  padding: 0.75rem;
  border: 1px solid #d1d5db;
  border-radius: 6px;
  font-size: 1rem;
  transition: border-color 0.2s;
}

.form-group input:focus {
  outline: none;
  border-color: var(--teal);
  box-shadow: 0 0 0 3px rgba(20, 184, 166, 0.1);
}

.form-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-top: -0.5rem;
}

.checkbox-label {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  cursor: pointer;
  font-size: 0.9rem;
  color: #374151;
}

.checkbox-label input[type="checkbox"] {
  width: 16px;
  height: 16px;
  cursor: pointer;
}

.link-forgot {
  color: var(--teal);
  text-decoration: none;
  font-size: 0.9rem;
  font-weight: 600;
}

.link-forgot:hover {
  text-decoration: underline;
}

.btn-signin {
  background: var(--teal);
  color: white;
  border: none;
  padding: 0.875rem;
  border-radius: 6px;
  font-weight: 600;
  font-size: 1rem;
  cursor: pointer;
  transition: background 0.2s;
  margin-top: 0.5rem;
}

.btn-signin:hover:not(:disabled) {
  background: var(--emerald);
}

.btn-signin:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.error {
  color: #dc2626;
  font-size: 0.875rem;
  margin: -0.5rem 0 0 0;
  text-align: center;
}

/* Right panel - System Overview */
.overview-panel {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 2rem;
  position: relative;
}

.overview-content {
  background: rgba(255, 255, 255, 0.15);
  backdrop-filter: blur(10px);
  border-radius: 16px;
  padding: 2.5rem;
  max-width: 600px;
  width: 100%;
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
}

.overview-title {
  font-size: 2rem;
  font-weight: 700;
  color: white;
  margin: 0 0 1.5rem 0;
  text-align: center;
}

.overview-description {
  text-align: center;
}

.overview-text {
  color: rgba(255, 255, 255, 0.95);
  font-size: 0.95rem;
  line-height: 1.7;
  margin: 0 0 1rem 0;
}

.overview-text:last-child {
  margin-bottom: 0;
}

/* Statistics Footer */
.stats-footer {
  background: rgba(255, 255, 255, 0.2);
  backdrop-filter: blur(10px);
  padding: 2rem;
  margin: 0 2rem 2rem 2rem;
  border-radius: 16px;
  border-top: 1px solid rgba(255, 255, 255, 0.3);
}

.stats-footer-title {
  font-size: 1.5rem;
  font-weight: 600;
  color: white;
  margin: 0 0 1.5rem 0;
  text-align: center;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 1.5rem;
  max-width: 1200px;
  margin: 0 auto;
}

.stat-card {
  background: rgba(255, 255, 255, 0.95);
  border-radius: 12px;
  padding: 1.5rem;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.stat-header {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  margin-bottom: 1rem;
  padding-bottom: 0.75rem;
  border-bottom: 2px solid #e5e7eb;
}

.stat-icon {
  font-size: 1.5rem;
}

.stat-title {
  font-size: 1rem;
  font-weight: 600;
  color: #1f2937;
  margin: 0;
}

.stat-body {
  max-height: 200px;
  overflow-y: auto;
  padding-right: 0.5rem;
}

.stat-body::-webkit-scrollbar {
  width: 6px;
}

.stat-body::-webkit-scrollbar-track {
  background: #f1f5f9;
  border-radius: 2px;
}

.stat-body::-webkit-scrollbar-thumb {
  background: #cbd5e1;
  border-radius: 3px;
}

.stat-body::-webkit-scrollbar-thumb:hover {
  background: #94a3b8;
}

.stat-loading {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  padding: 1rem;
  color: #6b7280;
  font-size: 0.8rem;
}

.mini-spinner {
  border: 2px solid #f3f4f6;
  border-top: 2px solid #6366f1;
  border-radius: 50%;
  width: 16px;
  height: 16px;
  animation: spin 1s linear infinite;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

.stat-empty {
  text-align: center;
  padding: 1rem;
  color: #9ca3af;
  font-size: 0.8rem;
}

.stat-list {
  display: flex;
  flex-direction: column;
  gap: 0.875rem;
}

.stat-item {
  display: flex;
  flex-direction: column;
  gap: 0.4rem;
}

.stat-item-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.stat-dept-name {
  font-weight: 600;
  color: #1f2937;
  font-size: 0.875rem;
  flex: 1;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.stat-percentage {
  font-weight: 700;
  font-size: 0.8rem;
  padding: 0.2rem 0.6rem;
  border-radius: 10px;
  margin-left: 0.5rem;
}

.percentage-high {
  background: #d1fae5;
  color: #065f46;
}

.percentage-medium {
  background: #fef3c7;
  color: #92400e;
}

.percentage-low {
  background: #fed7aa;
  color: #9a3412;
}

.percentage-very-low {
  background: #fee2e2;
  color: #991b1b;
}

.stat-progress-bar {
  height: 8px;
  background: #e5e7eb;
  border-radius: 4px;
  overflow: hidden;
}

.stat-progress-fill {
  height: 100%;
  border-radius: 4px;
  transition: width 0.5s ease;
}

.asset-fill {
  background: linear-gradient(90deg, #0d9488 0%, #14b8a6 100%);
}

.location-fill {
  background: linear-gradient(90deg, #3b82f6 0%, #60a5fa 100%);
}

/* Responsive */
@media (max-width: 968px) {
  .top-section {
    flex-direction: column;
    padding: 1.5rem;
    gap: 1.5rem;
  }
  
  .login-panel {
    flex: 0 0 auto;
    width: 100%;
  }
  
  .overview-panel {
    display: none;
  }
  
  .login-content {
    max-width: 100%;
  }
  
  .stats-footer {
    margin: 0 1.5rem 1.5rem 1.5rem;
  }
}

@media (max-width: 768px) {
  .header-content {
    padding: 1rem 1.5rem;
  }
  
  .logo-icon-large {
    font-size: 2.5rem;
  }
  
  .system-name {
    font-size: 1.5rem;
  }
  
  .system-subtitle {
    font-size: 0.75rem;
  }
  
  .top-section {
    padding: 1rem;
    gap: 1rem;
  }
  
  .stats-grid {
    grid-template-columns: 1fr;
  }
  
  .stats-footer {
    padding: 1.5rem;
    margin: 0 1rem 1rem 1rem;
  }
  
  .stats-footer-title {
    font-size: 1.25rem;
  }
}

@media (max-width: 480px) {
  .system-subtitle {
    display: none;
  }
}
</style>
