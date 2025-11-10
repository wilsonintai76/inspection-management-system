<template>
  <div class="min-h-screen bg-gradient-to-br from-indigo-50 via-white to-purple-50 flex items-center justify-center p-4">
    <div class="card w-full max-w-2xl bg-base-100 shadow-xl">
      <div class="card-body">
        <h2 class="card-title text-3xl justify-center mb-2">Create Account</h2>
        <p class="text-center opacity-70 mb-6">Register for Inspectable</p>

        <div v-if="successMessage" class="alert alert-success mb-4">
          <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
          <span>{{ successMessage }}</span>
        </div>

        <div v-if="errorMessage" class="alert alert-error mb-4">
          <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
          <span>{{ errorMessage }}</span>
        </div>

        <form @submit.prevent="handleRegister" v-if="!successMessage" class="space-y-4">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="form-control">
              <label class="label" for="name">
                <span class="label-text">Full Name *</span>
              </label>
              <input
                type="text"
                id="name"
                v-model="formData.name"
                placeholder="Enter your full name"
                class="input input-bordered w-full"
                required
                autocomplete="name"
              />
            </div>

            <div class="form-control">
              <label class="label" for="staff_id">
                <span class="label-text">Staff ID *</span>
              </label>
              <input
                type="text"
                id="staff_id"
                v-model="formData.staff_id"
                placeholder="4-digit Staff ID"
                pattern="\d{4}"
                maxlength="4"
                class="input input-bordered w-full"
                required
                autocomplete="off"
              />
              <label class="label">
                <span class="label-text-alt">Must be exactly 4 digits</span>
              </label>
            </div>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="form-control">
              <label class="label" for="email">
                <span class="label-text">Institutional Email *</span>
              </label>
              <input
                type="email"
                id="email"
                v-model="formData.email"
                placeholder="your.name@institution.edu"
                class="input input-bordered w-full"
                required
                autocomplete="email"
              />
            </div>

            <div class="form-control">
              <label class="label" for="phone">
                <span class="label-text">Phone Number</span>
              </label>
              <input
                type="tel"
                id="phone"
                v-model="formData.phone"
                placeholder="+1234567890"
                class="input input-bordered w-full"
                autocomplete="tel"
              />
            </div>
          </div>

          <div class="form-control">
            <label class="label" for="department">
              <span class="label-text">Department</span>
            </label>
            <select id="department" v-model="formData.department_id" class="select select-bordered w-full">
              <option value="">Select Department (Optional)</option>
              <option v-for="dept in departments" :key="dept.id" :value="dept.id">
                {{ dept.name }}
              </option>
            </select>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="form-control">
              <label class="label" for="password">
                <span class="label-text">Password *</span>
              </label>
              <input
                type="password"
                id="password"
                v-model="formData.password"
                placeholder="Minimum 8 characters"
                class="input input-bordered w-full"
                required
                autocomplete="new-password"
              />
            </div>

            <div class="form-control">
              <label class="label" for="confirm_password">
                <span class="label-text">Confirm Password *</span>
              </label>
              <input
                type="password"
                id="confirm_password"
                v-model="formData.confirm_password"
                placeholder="Re-enter password"
                class="input input-bordered w-full"
                required
                autocomplete="new-password"
              />
            </div>
          </div>

          <button type="submit" class="btn btn-primary w-full" :disabled="loading">
            <span v-if="loading" class="loading loading-spinner"></span>
            {{ loading ? 'Creating Account...' : 'Register' }}
          </button>
        </form>

        <div class="divider"></div>
        
        <p class="text-center">
          Already have an account? 
          <router-link to="/login" class="link link-primary">Log in</router-link>
        </p>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import api from '../api'

interface FormData {
  name: string
  staff_id: string
  email: string
  phone: string
  department_id: string | number
  password: string
  confirm_password: string
}

interface Department {
  id: number
  name: string
}

const formData = ref<FormData>({
  name: '',
  staff_id: '',
  email: '',
  phone: '',
  department_id: '',
  password: '',
  confirm_password: ''
})

const departments = ref<Department[]>([])
const loading = ref(false)
const errorMessage = ref('')
const successMessage = ref('')

onMounted(async () => {
  try {
    const response = await api.get('/departments.php')
    departments.value = response.data
  } catch (error) {
    console.error('Failed to load departments:', error)
  }
})

async function handleRegister() {
  errorMessage.value = ''
  successMessage.value = ''

  // Validate passwords match
  if (formData.value.password !== formData.value.confirm_password) {
    errorMessage.value = 'Passwords do not match'
    return
  }

  // Validate password length
  if (formData.value.password.length < 8) {
    errorMessage.value = 'Password must be at least 8 characters'
    return
  }

  // Validate staff ID format
  if (!/^\d{4}$/.test(formData.value.staff_id)) {
    errorMessage.value = 'Staff ID must be exactly 4 digits'
    return
  }

  loading.value = true

  try {
    const payload = {
      name: formData.value.name,
      staff_id: formData.value.staff_id,
      email: formData.value.email,
      phone: formData.value.phone || undefined,
      department_id: formData.value.department_id || undefined,
      password: formData.value.password
    }

    const response = await api.post('/register.php', payload)
    
    successMessage.value = response.data.message || 'Registration successful! Please check your email to verify your account.'
    
    // Clear form
    formData.value = {
      name: '',
      staff_id: '',
      email: '',
      phone: '',
      department_id: '',
      password: '',
      confirm_password: ''
    }
  } catch (error: any) {
    if (error.response?.data?.error) {
      errorMessage.value = error.response.data.error
    } else {
      errorMessage.value = 'Registration failed. Please try again.'
    }
    console.error('Registration error:', error)
  } finally {
    loading.value = false
  }
}
</script>

<style scoped>
.auth-container {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  padding: 20px;
}

.auth-card {
  background: white;
  border-radius: 10px;
  padding: 40px;
  max-width: 500px;
  width: 100%;
  box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
}

h2 {
  margin: 0 0 10px 0;
  color: #333;
  text-align: center;
}

.subtitle {
  text-align: center;
  color: #666;
  margin: 0 0 30px 0;
}

.form-group {
  margin-bottom: 20px;
}

label {
  display: block;
  margin-bottom: 5px;
  color: #333;
  font-weight: 500;
}

input,
select {
  width: 100%;
  padding: 12px;
  border: 1px solid #ddd;
  border-radius: 5px;
  font-size: 14px;
  transition: border-color 0.3s;
  box-sizing: border-box;
}

input:focus,
select:focus {
  outline: none;
  border-color: #667eea;
}

small {
  display: block;
  margin-top: 5px;
  color: #666;
  font-size: 12px;
}

.btn-primary {
  width: 100%;
  padding: 12px;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  border: none;
  border-radius: 5px;
  font-size: 16px;
  font-weight: 600;
  cursor: pointer;
  transition: transform 0.2s;
}

.btn-primary:hover:not(:disabled) {
  transform: translateY(-2px);
}

.btn-primary:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.alert {
  padding: 12px;
  border-radius: 5px;
  margin-bottom: 20px;
}

.alert-success {
  background: #d4edda;
  color: #155724;
  border: 1px solid #c3e6cb;
}

.alert-error {
  background: #f8d7da;
  color: #721c24;
  border: 1px solid #f5c6cb;
}

.auth-footer {
  margin-top: 30px;
  text-align: center;
  color: #666;
}

.auth-footer a {
  color: #667eea;
  text-decoration: none;
  font-weight: 600;
}

.auth-footer a:hover {
  text-decoration: underline;
}
</style>
