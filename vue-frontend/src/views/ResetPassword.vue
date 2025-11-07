<template>
  <div class="auth-page">
    <div class="card">
      <h1 class="title">Reset Password</h1>
      <p class="subtitle">Create a new password for your account.</p>

      <form @submit.prevent="submit" class="form">
        <div class="form-group">
          <label>New Password</label>
          <input v-model="password" type="password" placeholder="••••••••" required />
        </div>
        <div class="form-group">
          <label>Confirm Password</label>
          <input v-model="confirm" type="password" placeholder="••••••••" required />
        </div>

        <button class="btn-primary" :disabled="isSubmitting">{{ isSubmitting ? 'Saving…' : 'Set Password' }}</button>
        <p v-if="message" class="message">{{ message }}</p>
        <p v-if="error" class="error">{{ error }}</p>
      </form>

      <router-link to="/login" class="back-link">Back to Sign in</router-link>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import api from '../api'

const route = useRoute()
const router = useRouter()
const token = ref<string>('')
const password = ref('')
const confirm = ref('')
const isSubmitting = ref(false)
const message = ref('')
const error = ref('')

onMounted(() => {
  token.value = (route.query.token as string) || ''
  if (!token.value) {
    error.value = 'Missing or invalid reset link.'
  }
})

async function submit() {
  error.value = ''
  message.value = ''
  if (!token.value) { error.value = 'Missing or invalid reset link.'; return }
  if (password.value.length < 8) { error.value = 'Password must be at least 8 characters.'; return }
  if (password.value !== confirm.value) { error.value = 'Passwords do not match.'; return }

  isSubmitting.value = true
  try {
    await api.post('/reset-password.php', { token: token.value, password: password.value })
    message.value = 'Password updated. You can now sign in.'
    setTimeout(() => router.push('/login'), 1200)
  } catch (e: any) {
    error.value = e?.response?.data?.error || 'Failed to reset password. The link may have expired.'
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
.btn-primary{background:#14b8a6;color:#fff;border:none;padding:.8rem;border-radius:8px;font-weight:600;cursor:pointer}
.btn-primary:disabled{opacity:.6;cursor:not-allowed}
.message{color:#065f46;font-size:.9rem;margin:.25rem 0 0}
.error{color:#b91c1c;font-size:.9rem;margin:.25rem 0 0}
.back-link{display:inline-block;margin-top:1rem;color:#14b8a6;text-decoration:none}
.back-link:hover{text-decoration:underline}
</style>