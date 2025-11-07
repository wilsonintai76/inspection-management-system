<template>
  <div class="verify-container">
    <div class="verify-card">
      <div v-if="loading" class="loading">
        <div class="spinner"></div>
        <p>Verifying your email...</p>
      </div>

      <div v-else-if="success" class="success">
        <div class="icon-success">✓</div>
        <h2>Email Verified!</h2>
        <p>Welcome to Inspectable, {{ userName }}!</p>
        <div class="staff-id-box">
          <p class="label">Your Staff ID</p>
          <p class="staff-id">{{ staffId }}</p>
        </div>
        <p class="info">You can now log in using your Staff ID and password.</p>
        <router-link to="/login" class="btn-primary">Go to Login</router-link>
      </div>

      <div v-else-if="error" class="error">
        <div class="icon-error">✕</div>
        <h2>Verification Failed</h2>
        <p>{{ errorMessage }}</p>
        <div class="actions">
          <router-link to="/login" class="btn-secondary">Back to Login</router-link>
          <button v-if="canResend" @click="resendVerification" class="btn-primary" :disabled="resending">
            {{ resending ? 'Sending...' : 'Resend Verification Email' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { api } from '../lib/api'

const route = useRoute()

const loading = ref(true)
const success = ref(false)
const error = ref(false)
const errorMessage = ref('')
const userName = ref('')
const staffId = ref('')
const canResend = ref(false)
const resending = ref(false)
const userEmail = ref('')

onMounted(async () => {
  const token = route.query.token as string

  if (!token) {
    error.value = true
    errorMessage.value = 'Invalid verification link'
    loading.value = false
    return
  }

  try {
    const response = await api.post('/verify-email.php', { token })
    
    success.value = true
    userName.value = response.data.name
    staffId.value = response.data.staff_id
  } catch (err: any) {
    error.value = true
    
    if (err.response?.status === 410) {
      errorMessage.value = 'This verification link has expired. Please request a new one.'
      canResend.value = true
    } else if (err.response?.data?.error) {
      errorMessage.value = err.response.data.error
    } else {
      errorMessage.value = 'Failed to verify email. Please try again.'
    }
    
    console.error('Verification error:', err)
  } finally {
    loading.value = false
  }
})

async function resendVerification() {
  if (!userEmail.value) {
    const email = prompt('Please enter your email address:')
    if (!email) return
    userEmail.value = email
  }

  resending.value = true

  try {
    await api.post('/resend-verification.php', { email: userEmail.value })
    alert('Verification email sent! Please check your inbox.')
  } catch (err: any) {
    alert('Failed to send verification email. Please try again.')
    console.error('Resend error:', err)
  } finally {
    resending.value = false
  }
}
</script>

<style scoped>
.verify-container {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  padding: 20px;
}

.verify-card {
  background: white;
  border-radius: 10px;
  padding: 40px;
  max-width: 500px;
  width: 100%;
  box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
  text-align: center;
}

.loading {
  padding: 40px 0;
}

.spinner {
  width: 50px;
  height: 50px;
  border: 4px solid #f3f3f3;
  border-top: 4px solid #667eea;
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin: 0 auto 20px;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

.icon-success {
  width: 80px;
  height: 80px;
  border-radius: 50%;
  background: #d4edda;
  color: #155724;
  font-size: 50px;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 20px;
  font-weight: bold;
}

.icon-error {
  width: 80px;
  height: 80px;
  border-radius: 50%;
  background: #f8d7da;
  color: #721c24;
  font-size: 50px;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 20px;
  font-weight: bold;
}

h2 {
  margin: 0 0 10px 0;
  color: #333;
}

p {
  color: #666;
  margin: 10px 0;
}

.staff-id-box {
  background: #f8f9fa;
  border-radius: 8px;
  padding: 20px;
  margin: 30px 0;
}

.staff-id-box .label {
  margin: 0 0 10px 0;
  font-size: 14px;
  color: #666;
}

.staff-id-box .staff-id {
  margin: 0;
  font-size: 32px;
  font-weight: bold;
  color: #667eea;
  font-family: monospace;
}

.info {
  margin: 20px 0;
  font-size: 14px;
}

.btn-primary,
.btn-secondary {
  display: inline-block;
  padding: 12px 30px;
  border-radius: 5px;
  font-size: 16px;
  font-weight: 600;
  text-decoration: none;
  margin: 10px 5px;
  transition: transform 0.2s;
  border: none;
  cursor: pointer;
}

.btn-primary {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
}

.btn-primary:hover:not(:disabled) {
  transform: translateY(-2px);
}

.btn-primary:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.btn-secondary {
  background: #6c757d;
  color: white;
}

.btn-secondary:hover {
  transform: translateY(-2px);
}

.actions {
  margin-top: 30px;
  display: flex;
  flex-direction: column;
  gap: 10px;
  align-items: center;
}
</style>
