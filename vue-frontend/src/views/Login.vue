<template>
  <div class="login-page">
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

    <!-- Right panel: Welcome image -->
    <div class="welcome-panel">
      <div class="welcome-overlay">
        <h2 class="welcome-title">Welcome Back</h2>
        <p class="welcome-text">
          Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt
          ut labore et dolore magna aliqua. Quis ipsum suspendisse ultrices gravida. Risus commodo
          viverra maecenas accumsan lacus vel facilisis.
        </p>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import api from '../api';

const router = useRouter();
const staffId = ref('');
const password = ref('');
const rememberMe = ref(false);
const isLoading = ref(false);
const error = ref('');

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
</script>

<style scoped>
.login-page {
  display: flex;
  min-height: 100vh;
}

/* Left panel - Login form */
.login-panel {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 2rem;
  background: white;
}

.login-content {
  width: 100%;
  max-width: 400px;
}

.logo-section {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin-bottom: 2rem;
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
  font-size: 2rem;
  font-weight: 700;
  color: #1f2937;
  margin: 0 0 0.5rem 0;
}

.signup-prompt {
  color: #6b7280;
  margin: 0 0 2rem 0;
  font-size: 0.9rem;
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
  gap: 1.25rem;
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

/* Right panel - Welcome background */
.welcome-panel {
  flex: 1;
  background: linear-gradient(135deg, rgba(99, 102, 241, 0.8), rgba(139, 92, 246, 0.8)),
              url('https://images.unsplash.com/photo-1506905925346-21bda4d32df4?q=80&w=2070') center/cover;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 3rem;
  position: relative;
}

.welcome-overlay {
  background: rgba(255, 255, 255, 0.15);
  backdrop-filter: blur(10px);
  border-radius: 16px;
  padding: 3rem;
  max-width: 500px;
  text-align: left;
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
}

.welcome-title {
  font-size: 2.5rem;
  font-weight: 700;
  color: white;
  margin: 0 0 1rem 0;
}

.welcome-text {
  color: rgba(255, 255, 255, 0.95);
  font-size: 1rem;
  line-height: 1.7;
  margin: 0;
}

/* Responsive */
@media (max-width: 968px) {
  .welcome-panel {
    display: none;
  }
  
  .login-panel {
    flex: 1;
  }
}
</style>
