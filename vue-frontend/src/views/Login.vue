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
          Don't have an account? <a href="#" class="link-create">Create now</a>
        </p>

        <form @submit.prevent="handleLogin" class="login-form">
          <div class="form-group">
            <label for="email">Email</label>
            <input
              id="email"
              v-model="email"
              type="email"
              placeholder="admin@example.com"
              required
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
            <a href="#" class="link-forgot">Forgot Password?</a>
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

const router = useRouter();
const email = ref('');
const password = ref('');
const rememberMe = ref(false);
const isLoading = ref(false);
const error = ref('');

async function handleLogin() {
  error.value = '';
  isLoading.value = true;

  // TODO: Replace with real authentication (PHP session, JWT, etc.)
  // For now, mock login - in production, validate against API
  try {
    // Simulate API call
    await new Promise(resolve => setTimeout(resolve, 500));
    
    if (email.value && password.value) {
      // Mock success: store login state
      sessionStorage.setItem('isLoggedIn', 'true');
      sessionStorage.setItem('userEmail', email.value);
      
      // Try to fetch user name from API
      try {
        const response = await fetch(`http://inspectable.local/api/users.php?id=${email.value}`);
        if (response.ok) {
          const userData = await response.json();
          sessionStorage.setItem('userName', userData.name || 'User');
        } else {
          sessionStorage.setItem('userName', 'User');
        }
      } catch {
        sessionStorage.setItem('userName', 'User');
      }
      
      if (rememberMe.value) {
        localStorage.setItem('rememberEmail', email.value);
      }
      router.push('/dashboard');
    } else {
      error.value = 'Please enter valid credentials';
    }
  } catch (err) {
    error.value = 'Login failed. Please try again.';
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
