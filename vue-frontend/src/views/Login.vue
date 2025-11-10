<template>
  <div class="min-h-screen bg-gradient-to-b from-indigo-50 to-white flex flex-col">
    <header class="sticky top-0 z-20 bg-white border-b border-gray-200">
      <div class="navbar max-w-7xl mx-auto px-6">
        <div class="navbar-start">
          <span class="text-2xl"></span>
          <span class="font-bold text-xl ml-2">INSPECTABLE</span>
        </div>
        <div class="navbar-end hidden md:flex">
          <ul class="menu menu-horizontal px-1">
            <li><a href="#features">Features</a></li>
            <li><a href="#about">About</a></li>
            <li><a href="#contact">Contact</a></li>
          </ul>
        </div>
      </div>
    </header>

    <section class="flex-1 py-16">
      <div class="max-w-7xl mx-auto grid grid-cols-1 lg:grid-cols-2 gap-12 items-center px-6">
        <div>
          <div class="badge badge-primary badge-lg gap-2">
            <span></span> Asset & Location Inspection System
          </div>
          <h1 class="mt-4 text-4xl sm:text-5xl font-extrabold text-gray-900 leading-tight">
            Streamline inspections with speed, accuracy, and accountability
          </h1>
          <p class="mt-4 text-gray-600 text-lg">
            Schedule, inspect, and track progress across departments. Gain real-time visibility and keep every audit on track.
          </p>
          <ul class="mt-6 space-y-3">
            <li class="flex items-start gap-3">
              <span class="text-success"></span>
              <span>Real-time progress by department and location</span>
            </li>
            <li class="flex items-start gap-3">
              <span class="text-success"></span>
              <span>Auditor-focused workflows with role-based access</span>
            </li>
            <li class="flex items-start gap-3">
              <span class="text-success"></span>
              <span>Fast asset uploads and smart mapping helpers</span>
            </li>
          </ul>
        </div>

        <div class="card bg-base-100 shadow-xl">
          <div class="card-body">
            <div class="flex items-center gap-2 mb-2">
              <span class="text-xl"></span>
              <span class="text-sm font-semibold opacity-70">Secure Access</span>
            </div>
            <h2 class="card-title text-2xl">Sign in</h2>
            <p class="text-sm opacity-70">
              Don't have an account?
              <router-link to="/register" class="link link-primary">Create now</router-link>
            </p>

            <form class="mt-4 space-y-4" @submit.prevent="handleLogin">
              <div class="form-control">
                <label class="label">
                  <span class="label-text">Staff ID</span>
                </label>
                <input
                  v-model="staffId"
                  type="text"
                  placeholder="Enter your 4-digit staff ID"
                  required
                  maxlength="4"
                  pattern="\d{4}"
                  class="input input-bordered w-full"
                />
              </div>

              <div class="form-control">
                <label class="label">
                  <span class="label-text">Password</span>
                </label>
                <input
                  v-model="password"
                  type="password"
                  placeholder=""
                  required
                  class="input input-bordered w-full"
                />
              </div>

              <div class="flex items-center justify-between">
                <label class="label cursor-pointer gap-2">
                  <input type="checkbox" v-model="rememberMe" class="checkbox checkbox-sm" />
                  <span class="label-text">Remember me</span>
                </label>
                <router-link to="/forgot-password" class="link link-primary text-sm">Forgot password?</router-link>
              </div>

              <button type="submit" :disabled="isLoading" class="btn btn-primary w-full">
                <span v-if="!isLoading">Sign in</span>
                <span v-else class="loading loading-spinner"></span>
              </button>

              <div v-if="error" class="alert alert-error">
                <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span>{{ error }}</span>
              </div>
            </form>
          </div>
        </div>
      </div>
    </section>

    <section id="features" class="py-16 bg-base-200">
      <div class="max-w-7xl mx-auto px-6">
        <h3 class="text-3xl font-bold text-center">Everything you need for inspections</h3>
        <p class="text-center mt-2 opacity-70">Powerful features to plan, execute, and monitor inspections with confidence.</p>

        <div class="mt-10 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
          <div class="card bg-base-100 shadow-sm">
            <div class="card-body">
              <div class="text-2xl"></div>
              <h4 class="card-title text-lg">Smart Scheduling</h4>
              <p class="text-sm">Assign auditors, set timelines, and keep everyone aligned.</p>
            </div>
          </div>
          <div class="card bg-base-100 shadow-sm">
            <div class="card-body">
              <div class="text-2xl"></div>
              <h4 class="card-title text-lg">Live Dashboards</h4>
              <p class="text-sm">See real-time progress across assets and locations.</p>
            </div>
          </div>
          <div class="card bg-base-100 shadow-sm">
            <div class="card-body">
              <div class="text-2xl"></div>
              <h4 class="card-title text-lg">Role-based Access</h4>
              <p class="text-sm">Auditor-first design with secure permissions.</p>
            </div>
          </div>
          <div class="card bg-base-100 shadow-sm">
            <div class="card-body">
              <div class="text-2xl"></div>
              <h4 class="card-title text-lg">Fast Imports</h4>
              <p class="text-sm">Upload assets and auto-map departments quickly.</p>
            </div>
          </div>
        </div>
      </div>
    </section>

    <footer class="footer footer-center p-10 bg-base-200">
      <div>
        <p class="text-sm opacity-70">© {{ new Date().getFullYear() }} Inspectable. All rights reserved.</p>
      </div>
      <div>
        <div class="grid grid-flow-col gap-4">
          <a href="#about" class="link link-hover">About</a>
          <a href="#features" class="link link-hover">Features</a>
          <a href="#contact" class="link link-hover">Contact</a>
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