<template>
  <div class="flex flex-col items-center justify-center py-12 gap-4">
    <span class="loading loading-spinner loading-lg text-primary"></span>
    <p v-if="message" class="text-gray-600">{{ message }}</p>
  </div>
</template>

<script setup lang="ts">
defineProps<{
  message?: string
}>()
</script>
