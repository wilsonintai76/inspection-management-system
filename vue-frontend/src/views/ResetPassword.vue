<template>
  <div class="min-h-screen bg-gradient-to-br from-indigo-50 via-white to-purple-50 flex items-center justify-center p-4">
    <div class="card w-full max-w-md bg-base-100 shadow-xl">
      <div class="card-body">
        <h2 class="card-title text-2xl justify-center">Reset Password</h2>
        <p class="text-center opacity-70 mb-4">Create a new password for your account.</p>

        <form @submit.prevent="submit" class="space-y-4">
          <div class="form-control">
            <label class="label">
              <span class="label-text">New Password</span>
            </label>
            <input 
              v-model="password" 
              type="password" 
              placeholder="••••••••" 
              class="input input-bordered w-full"
              required 
            />
          </div>

          <div class="form-control">
            <label class="label">
              <span class="label-text">Confirm Password</span>
            </label>
            <input 
              v-model="confirm" 
              type="password" 
              placeholder="••••••••" 
              class="input input-bordered w-full"
              required 
            />
          </div>

          <button class="btn btn-primary w-full" :disabled="isSubmitting">
            <span v-if="isSubmitting" class="loading loading-spinner"></span>
            {{ isSubmitting ? 'Saving…' : 'Set Password' }}
          </button>

          <div v-if="message" class="alert alert-success">
            <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span>{{ message }}</span>
          </div>

          <div v-if="error" class="alert alert-error">
            <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span>{{ error }}</span>
          </div>
        </form>

        <div class="divider"></div>
        
        <router-link to="/login" class="link link-primary text-center block">
          ← Back to Sign in
        </router-link>
      </div>
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