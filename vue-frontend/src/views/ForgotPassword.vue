<template>
  <div class="min-h-screen bg-gradient-to-br from-indigo-50 via-white to-purple-50 flex items-center justify-center p-4">
    <div class="card w-full max-w-md bg-base-100 shadow-xl">
      <div class="card-body">
        <h2 class="card-title text-2xl justify-center">Forgot Password</h2>
        <p class="text-center opacity-70 mb-4">Enter your Staff ID or Email and we'll send you a reset link.</p>

        <form @submit.prevent="submit" class="space-y-4">
          <div class="form-control">
            <label class="label">
              <span class="label-text">Staff ID (4 digits)</span>
            </label>
            <input 
              v-model="staffId" 
              type="text" 
              maxlength="4" 
              pattern="\d{4}" 
              placeholder="e.g. 2006"
              class="input input-bordered w-full"
            />
          </div>

          <div class="divider">OR</div>

          <div class="form-control">
            <label class="label">
              <span class="label-text">Email</span>
            </label>
            <input 
              v-model="email" 
              type="email" 
              placeholder="name@example.com"
              class="input input-bordered w-full"
            />
          </div>

          <button class="btn btn-primary w-full" :disabled="isSubmitting">
            <span v-if="isSubmitting" class="loading loading-spinner"></span>
            {{ isSubmitting ? 'Sending…' : 'Send Reset Link' }}
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