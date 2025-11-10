<template>
  <div class="min-h-screen bg-gradient-to-b from-indigo-50 to-white flex flex-col">
    <!-- Top Nav -->
    <header class="sticky top-0 z-20 bg-white border-b border-gray-200 backdrop-blur">
      <nav class="navbar max-w-7xl mx-auto px-6 h-16">
        <div class="navbar-start flex items-center gap-3">
          <div class="text-2xl">🏢</div>
          <div class="font-extrabold text-gray-900">INSPECTABLE</div>
        </div>
        <ul class="navbar-end hidden md:flex items-center gap-6 text-sm text-gray-600">
          <li><a href="#features" class="hover:text-gray-900 transition-colors">Features</a></li>
          <li><a href="#about" class="hover:text-gray-900 transition-colors">About</a></li>
          <li><a href="#contact" class="hover:text-gray-900 transition-colors">Contact</a></li>
        </ul>
      </nav>
    </header>

    <!-- Hero with login form -->
    <section class="flex-1">
      <div class="max-w-7xl mx-auto grid grid-cols-1 lg:grid-cols-2 gap-12 items-center px-6 pt-16 pb-12">
        <!-- Marketing copy -->
        <div>
          <span class="inline-flex items-center gap-2 text-indigo-700 bg-indigo-100 px-3 py-1 rounded-full text-sm font-medium">
            <span class="text-lg">✨</span> Asset & Location Inspection System
          </span>
          <h1 class="mt-4 text-4xl sm:text-5xl font-extrabold text-gray-900 leading-tight">
            Streamline inspections with speed, accuracy, and accountability
          </h1>
          <p class="mt-4 text-gray-600 text-lg">
            Schedule, inspect, and track progress across departments. Gain real-time visibility and keep every audit on track.
          </p>

          <ul class="mt-6 space-y-3">
            <li class="flex items-start gap-3">
              <span class="text-green-600">✔</span>
              <span class="text-gray-700">Real-time progress by department and location</span>
            </li>
            <li class="flex items-start gap-3">
              <span class="text-green-600">✔</span>
              <span class="text-gray-700">Auditor-focused workflows with role-based access</span>
            </li>
            <li class="flex items-start gap-3">
              <span class="text-green-600">✔</span>
              <span class="text-gray-700">Fast asset uploads and smart mapping helpers</span>
            </li>
          </ul>
        </div>

        <!-- Login card -->
        <div class="card bg-white shadow-xl border border-gray-100 p-8 relative">
          <div class="flex items-center gap-2 mb-4">
            <div class="text-xl">🔐</div>
            <div class="text-sm font-semibold text-gray-500">Secure Access</div>
          </div>

          <h2 class="text-2xl font-bold text-gray-900">Sign in</h2>
          <p class="text-sm text-gray-600 mt-1">
            Don’t have an account?
            <router-link to="/register" class="text-indigo-600 hover:text-indigo-700 font-semibold">Create now</router-link>
          </p>

          <form class="mt-6 space-y-4" @submit.prevent="handleLogin">
            <div>
              <label for="staff-id" class="block text-sm font-medium text-gray-700">Staff ID</label>
              <input
                id="staff-id"
                v-model="staffId"
                type="text"
                placeholder="Enter your 4-digit staff ID"
                required
                maxlength="4"
                pattern="\\d{4}"
                autocomplete="username"
                class="input input-bordered mt-1 w-full"
              />
            </div>

            <div>
              <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
              <input
                id="password"
                v-model="password"
                type="password"
                placeholder="••••••••"
                required
                autocomplete="current-password"
                class="input input-bordered mt-1 w-full"
              />
            </div>

            <div class="flex items-center justify-between">
              <label class="flex items-center gap-2 text-sm text-gray-700">
                <input type="checkbox" v-model="rememberMe" class="checkbox checkbox-primary" />
                <span>Remember me</span>
              </label>
              <router-link to="/forgot-password" class="text-sm text-indigo-600 hover:text-indigo-700 font-semibold">Forgot password?</router-link>
            </div>

            <button type="submit" :disabled="isLoading"
              class="btn btn-primary w-full">
              <span v-if="!isLoading">Sign in</span>
              <span v-else class="inline-flex items-center gap-2">
                <svg class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z" />
                </svg>
                Signing in...
              </span>
            </button>

            <div v-if="error" class="alert alert-error">
              <span>{{ error }}</span>
            </div>
          </form>
        </div>
      </div>
    </section>

    <!-- Features -->
    <section id="features" class="py-16 bg-white">
      <div class="max-w-7xl mx-auto px-6">
        <h3 class="text-3xl font-bold text-gray-900 text-center">Everything you need for inspections</h3>
        <p class="text-gray-600 text-center mt-2">Powerful features to plan, execute, and monitor inspections with confidence.</p>

        <div class="mt-10 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
          <div class="border border-gray-200 rounded-xl p-6 bg-white shadow-sm">
            <div class="text-2xl">📅</div>
            <h4 class="mt-3 font-semibold text-gray-900">Smart Scheduling</h4>
            <p class="text-gray-600 mt-1">Assign auditors, set timelines, and keep everyone aligned.</p>
          </div>
          <div class="border border-gray-200 rounded-xl p-6 bg-white shadow-sm">
            <div class="text-2xl">📊</div>
            <h4 class="mt-3 font-semibold text-gray-900">Live Dashboards</h4>
            <p class="text-gray-600 mt-1">See real-time progress across assets and locations.</p>
          </div>
          <div class="border border-gray-200 rounded-xl p-6 bg-white shadow-sm">
            <div class="text-2xl">🔒</div>
            <h4 class="mt-3 font-semibold text-gray-900">Role-based Access</h4>
            <p class="text-gray-600 mt-1">Auditor-first design with secure permissions.</p>
          </div>
          <div class="border border-gray-200 rounded-xl p-6 bg-white shadow-sm">
            <div class="text-2xl">⚡</div>
            <h4 class="mt-3 font-semibold text-gray-900">Fast Imports</h4>
            <p class="text-gray-600 mt-1">Upload assets and auto-map departments quickly.</p>
          </div>
        </div>
      </div>
    </section>

    <!-- Footer -->
    <footer class="bg-gray-50 border-t border-gray-200 py-8">
      <div class="max-w-7xl mx-auto px-6 flex flex-col sm:flex-row items-center justify-between gap-4">
        <div class="text-gray-600 text-sm">© {{ new Date().getFullYear() }} Inspectable. All rights reserved.</div>
        <div class="flex gap-6 text-sm text-gray-600">
          <a href="#about" class="hover:text-gray-900">About</a>
          <a href="#features" class="hover:text-gray-900">Features</a>
          <a href="#contact" class="hover:text-gray-900">Contact</a>
        </div>
      </div>
    </footer>
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

async function handleLogin() {
  error.value = '';
  isLoading.value = true;
  try {
    const response = await api.post('/login.php', {
      staff_id: staffId.value,
      password: password.value,
    });
    if (response.data.success) {
      const { user, must_change_password } = response.data;
      sessionStorage.setItem('isLoggedIn', 'true');
      sessionStorage.setItem('userId', user.id);
      sessionStorage.setItem('staffId', user.staff_id);
      sessionStorage.setItem('userEmail', user.email);
      sessionStorage.setItem('userName', user.name);
      sessionStorage.setItem('userRoles', JSON.stringify(user.roles));
      sessionStorage.setItem('userDepartmentId', user.department_id || '');
      if (rememberMe.value) localStorage.setItem('rememberStaffId', staffId.value);
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
      if (err.response?.data?.requires_verification) {
        const email = err.response.data.email;
        if (confirm('Your email is not verified. Resend verification email?')) {
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

onMounted(() => {
  const remembered = localStorage.getItem('rememberStaffId');
  if (remembered) {
    staffId.value = remembered;
    rememberMe.value = true;
  }
});
</script>

<style scoped>
/* Tailwind utility classes (prefixed with ) are used; minimal custom CSS */
</style>
