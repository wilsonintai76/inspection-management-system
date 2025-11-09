<template>
  <div class="tw-min-h-screen tw-bg-gradient-to-b tw-from-indigo-50 tw-to-white tw-flex tw-flex-col">
    <!-- Top Nav -->
    <header class="tw-sticky tw-top-0 tw-z-20 tw-bg-white tw-border-b tw-border-gray-200 tw-backdrop-blur">
      <nav class="navbar tw-max-w-7xl tw-mx-auto tw-px-6 tw-h-16">
        <div class="navbar-start tw-flex tw-items-center tw-gap-3">
          <div class="tw-text-2xl">🏢</div>
          <div class="tw-font-extrabold tw-text-gray-900">INSPECTABLE</div>
        </div>
        <ul class="navbar-end tw-hidden md:tw-flex tw-items-center tw-gap-6 tw-text-sm tw-text-gray-600">
          <li><a href="#features" class="tw-hover:tw-text-gray-900 tw-transition-colors">Features</a></li>
          <li><a href="#about" class="tw-hover:tw-text-gray-900 tw-transition-colors">About</a></li>
          <li><a href="#contact" class="tw-hover:tw-text-gray-900 tw-transition-colors">Contact</a></li>
        </ul>
      </nav>
    </header>

    <!-- Hero with login form -->
    <section class="tw-flex-1">
      <div class="tw-max-w-7xl tw-mx-auto tw-grid tw-grid-cols-1 lg:tw-grid-cols-2 tw-gap-12 tw-items-center tw-px-6 tw-pt-16 tw-pb-12">
        <!-- Marketing copy -->
        <div>
          <span class="tw-inline-flex tw-items-center tw-gap-2 tw-text-indigo-700 tw-bg-indigo-100 tw-px-3 tw-py-1 tw-rounded-full tw-text-sm tw-font-medium">
            <span class="tw-text-lg">✨</span> Asset & Location Inspection System
          </span>
          <h1 class="tw-mt-4 tw-text-4xl sm:tw-text-5xl tw-font-extrabold tw-text-gray-900 tw-leading-tight">
            Streamline inspections with speed, accuracy, and accountability
          </h1>
          <p class="tw-mt-4 tw-text-gray-600 tw-text-lg">
            Schedule, inspect, and track progress across departments. Gain real-time visibility and keep every audit on track.
          </p>

          <ul class="tw-mt-6 tw-space-y-3">
            <li class="tw-flex tw-items-start tw-gap-3">
              <span class="tw-text-green-600">✔</span>
              <span class="tw-text-gray-700">Real-time progress by department and location</span>
            </li>
            <li class="tw-flex tw-items-start tw-gap-3">
              <span class="tw-text-green-600">✔</span>
              <span class="tw-text-gray-700">Auditor-focused workflows with role-based access</span>
            </li>
            <li class="tw-flex tw-items-start tw-gap-3">
              <span class="tw-text-green-600">✔</span>
              <span class="tw-text-gray-700">Fast asset uploads and smart mapping helpers</span>
            </li>
          </ul>
        </div>

        <!-- Login card -->
        <div class="card tw-bg-white tw-shadow-xl tw-border tw-border-gray-100 tw-p-8 tw-relative">
          <div class="tw-flex tw-items-center tw-gap-2 tw-mb-4">
            <div class="tw-text-xl">🔐</div>
            <div class="tw-text-sm tw-font-semibold tw-text-gray-500">Secure Access</div>
          </div>

          <h2 class="tw-text-2xl tw-font-bold tw-text-gray-900">Sign in</h2>
          <p class="tw-text-sm tw-text-gray-600 tw-mt-1">
            Don’t have an account?
            <router-link to="/register" class="tw-text-indigo-600 hover:tw-text-indigo-700 tw-font-semibold">Create now</router-link>
          </p>

          <form class="tw-mt-6 tw-space-y-4" @submit.prevent="handleLogin">
            <div>
              <label for="staff-id" class="tw-block tw-text-sm tw-font-medium tw-text-gray-700">Staff ID</label>
              <input
                id="staff-id"
                v-model="staffId"
                type="text"
                placeholder="Enter your 4-digit staff ID"
                required
                maxlength="4"
                pattern="\\d{4}"
                autocomplete="username"
                class="input input-bordered tw-mt-1 tw-w-full"
              />
            </div>

            <div>
              <label for="password" class="tw-block tw-text-sm tw-font-medium tw-text-gray-700">Password</label>
              <input
                id="password"
                v-model="password"
                type="password"
                placeholder="••••••••"
                required
                autocomplete="current-password"
                class="input input-bordered tw-mt-1 tw-w-full"
              />
            </div>

            <div class="tw-flex tw-items-center tw-justify-between">
              <label class="tw-flex tw-items-center tw-gap-2 tw-text-sm tw-text-gray-700">
                <input type="checkbox" v-model="rememberMe" class="checkbox checkbox-primary" />
                <span>Remember me</span>
              </label>
              <router-link to="/forgot-password" class="tw-text-sm tw-text-indigo-600 hover:tw-text-indigo-700 tw-font-semibold">Forgot password?</router-link>
            </div>

            <button type="submit" :disabled="isLoading"
              class="btn btn-primary tw-w-full tw-inline-flex tw-justify-center tw-items-center tw-gap-2 tw-px-4 tw-py-2.5 tw-transition disabled:tw-opacity-60 disabled:tw-cursor-not-allowed">
              <span v-if="!isLoading">Sign in</span>
              <span v-else class="tw-inline-flex tw-items-center tw-gap-2">
                <svg class="tw-animate-spin tw-h-4 tw-w-4 tw-text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <circle class="tw-opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                  <path class="tw-opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z" />
                </svg>
                Signing in...
              </span>
            </button>

            <div v-if="error" class="alert alert-error tw-justify-center">
              <span>{{ error }}</span>
            </div>
          </form>
        </div>
      </div>
    </section>

    <!-- Features -->
    <section id="features" class="tw-py-16 tw-bg-white">
      <div class="tw-max-w-7xl tw-mx-auto tw-px-6">
        <h3 class="tw-text-3xl tw-font-bold tw-text-gray-900 tw-text-center">Everything you need for inspections</h3>
        <p class="tw-text-gray-600 tw-text-center tw-mt-2">Powerful features to plan, execute, and monitor inspections with confidence.</p>

        <div class="tw-mt-10 tw-grid tw-grid-cols-1 md:tw-grid-cols-2 lg:tw-grid-cols-4 tw-gap-6">
          <div class="tw-border tw-border-gray-200 tw-rounded-xl tw-p-6 tw-bg-white tw-shadow-sm">
            <div class="tw-text-2xl">📅</div>
            <h4 class="tw-mt-3 tw-font-semibold tw-text-gray-900">Smart Scheduling</h4>
            <p class="tw-text-gray-600 tw-mt-1">Assign auditors, set timelines, and keep everyone aligned.</p>
          </div>
          <div class="tw-border tw-border-gray-200 tw-rounded-xl tw-p-6 tw-bg-white tw-shadow-sm">
            <div class="tw-text-2xl">📊</div>
            <h4 class="tw-mt-3 tw-font-semibold tw-text-gray-900">Live Dashboards</h4>
            <p class="tw-text-gray-600 tw-mt-1">See real-time progress across assets and locations.</p>
          </div>
          <div class="tw-border tw-border-gray-200 tw-rounded-xl tw-p-6 tw-bg-white tw-shadow-sm">
            <div class="tw-text-2xl">🔒</div>
            <h4 class="tw-mt-3 tw-font-semibold tw-text-gray-900">Role-based Access</h4>
            <p class="tw-text-gray-600 tw-mt-1">Auditor-first design with secure permissions.</p>
          </div>
          <div class="tw-border tw-border-gray-200 tw-rounded-xl tw-p-6 tw-bg-white tw-shadow-sm">
            <div class="tw-text-2xl">⚡</div>
            <h4 class="tw-mt-3 tw-font-semibold tw-text-gray-900">Fast Imports</h4>
            <p class="tw-text-gray-600 tw-mt-1">Upload assets and auto-map departments quickly.</p>
          </div>
        </div>
      </div>
    </section>

    <!-- Footer -->
    <footer class="tw-bg-gray-50 tw-border-t tw-border-gray-200 tw-py-8">
      <div class="tw-max-w-7xl tw-mx-auto tw-px-6 tw-flex tw-flex-col sm:tw-flex-row tw-items-center tw-justify-between tw-gap-4">
        <div class="tw-text-gray-600 tw-text-sm">© {{ new Date().getFullYear() }} Inspectable. All rights reserved.</div>
        <div class="tw-flex tw-gap-6 tw-text-sm tw-text-gray-600">
          <a href="#about" class="hover:tw-text-gray-900">About</a>
          <a href="#features" class="hover:tw-text-gray-900">Features</a>
          <a href="#contact" class="hover:tw-text-gray-900">Contact</a>
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
/* Tailwind utility classes (prefixed with tw-) are used; minimal custom CSS */
</style>
