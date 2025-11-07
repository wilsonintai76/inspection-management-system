<template>
  <div class="auth-page">
    <div class="card">
      <h1 class="title">Forgot Password</h1>
      <p class="subtitle">Enter your Staff ID or Email and we'll send you a reset link.</p>

      <form @submit.prevent="submit" class="form">
        <div class="form-group">
          <label>Staff ID (4 digits)</label>
          <input v-model="staffId" type="text" maxlength="4" pattern="\d{4}" placeholder="e.g. 2006" />
        </div>
        <div class="divider">or</div>
        <div class="form-group">
          <label>Email</label>
          <input v-model="email" type="email" placeholder="name@example.com" />
        </div>

        <button class="btn-primary" :disabled="isSubmitting">{{ isSubmitting ? 'Sending…' : 'Send Reset Link' }}</button>
        <p v-if="message" class="message">{{ message }}</p>
        <p v-if="error" class="error">{{ error }}</p>
      </form>

      <router-link to="/login" class="back-link">Back to Sign in</router-link>
    </div>
  </div>
  
</template>

<script setup lang="ts">
import { ref } from 'vue'
import api from '../api'

const staffId = ref('')
const email = ref('')
const isSubmitting = ref(false)
const message = ref('')
const error = ref('')

async function submit() {
  message.value = ''
  error.value = ''
  if (!staffId.value && !email.value) {
    error.value = 'Provide either Staff ID or Email.'
    return
  }
  isSubmitting.value = true
  try {
    await api.post('/forgot-password.php', {
      staff_id: staffId.value || undefined,
      email: email.value || undefined
    })
    message.value = 'If the account exists, we\'ve sent a reset link to the email on file.'
  } catch (e: any) {
    // Still show generic success to avoid enumeration
    message.value = 'If the account exists, we\'ve sent a reset link to the email on file.'
  } finally {
    isSubmitting.value = false
  }
}
</script>

<style scoped>
.auth-page{display:flex;min-height:100vh;align-items:center;justify-content:center;background:#f9fafb;padding:2rem}
.card{background:#fff;padding:2rem;border-radius:12px;box-shadow:0 10px 25px rgba(0,0,0,.06);width:100%;max-width:420px}
.title{margin:0 0 .5rem 0;font-size:1.5rem;color:#111827}
.subtitle{margin:0 0 1.25rem 0;color:#6b7280;font-size:.95rem}
.form{display:flex;flex-direction:column;gap:1rem}
.form-group{display:flex;flex-direction:column;gap:.5rem}
label{font-weight:600;color:#374151}
input{padding:.75rem;border:1px solid #d1d5db;border-radius:8px;font-size:1rem}
.divider{display:flex;align-items:center;justify-content:center;color:#9ca3af;font-size:.8rem}
.btn-primary{background:#14b8a6;color:#fff;border:none;padding:.8rem;border-radius:8px;font-weight:600;cursor:pointer}
.btn-primary:disabled{opacity:.6;cursor:not-allowed}
.message{color:#065f46;font-size:.9rem;margin:.25rem 0 0}
.error{color:#b91c1c;font-size:.9rem;margin:.25rem 0 0}
.back-link{display:inline-block;margin-top:1rem;color:#14b8a6;text-decoration:none}
.back-link:hover{text-decoration:underline}
</style>