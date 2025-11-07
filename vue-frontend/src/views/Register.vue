<template>
  <div class="auth-container">
    <div class="auth-card">
      <h2>Create Account</h2>
      <p class="subtitle">Register for Inspectable</p>

      <div v-if="successMessage" class="alert alert-success">
        {{ successMessage }}
      </div>

      <div v-if="errorMessage" class="alert alert-error">
        {{ errorMessage }}
      </div>

      <form @submit.prevent="handleRegister" v-if="!successMessage">
        <div class="form-group">
          <label for="name">Full Name *</label>
          <input
            type="text"
            id="name"
            v-model="formData.name"
            placeholder="Enter your full name"
            required
            autocomplete="name"
          />
        </div>

        <div class="form-group">
          <label for="staff_id">Staff ID *</label>
          <input
            type="text"
            id="staff_id"
            v-model="formData.staff_id"
            placeholder="4-digit Staff ID"
            pattern="\d{4}"
            maxlength="4"
            required
            autocomplete="off"
          />
          <small>Must be exactly 4 digits</small>
        </div>

        <div class="form-group">
          <label for="email">Institutional Email *</label>
          <input
            type="email"
            id="email"
            v-model="formData.email"
            placeholder="your.name@institution.edu"
            required
            autocomplete="email"
          />
        </div>

        <div class="form-group">
          <label for="phone">Phone Number</label>
          <input
            type="tel"
            id="phone"
            v-model="formData.phone"
            placeholder="+1234567890"
            autocomplete="tel"
          />
        </div>

        <div class="form-group">
          <label for="department">Department</label>
          <select id="department" v-model="formData.department_id">
            <option value="">Select Department (Optional)</option>
            <option v-for="dept in departments" :key="dept.id" :value="dept.id">
              {{ dept.name }}
            </option>
          </select>
        </div>

        <div class="form-group">
          <label for="password">Password *</label>
          <input
            type="password"
            id="password"
            v-model="formData.password"
            placeholder="Minimum 8 characters"
            required
            autocomplete="new-password"
          />
        </div>

        <div class="form-group">
          <label for="confirm_password">Confirm Password *</label>
          <input
            type="password"
            id="confirm_password"
            v-model="formData.confirm_password"
            placeholder="Re-enter password"
            required
            autocomplete="new-password"
          />
        </div>

        <button type="submit" class="btn-primary" :disabled="loading">
          {{ loading ? 'Creating Account...' : 'Register' }}
        </button>
      </form>

      <div class="auth-footer">
        <p>Already have an account? <router-link to="/login">Log in</router-link></p>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { api } from '../lib/api'

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
