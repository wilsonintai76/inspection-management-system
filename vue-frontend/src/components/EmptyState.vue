<template>
  <div class="flex flex-col items-center justify-center py-12 px-4 text-center">
    <div class="text-6xl mb-4">{{ icon || '📭' }}</div>
    <h3 class="text-xl font-semibold text-gray-900 mb-2">{{ title }}</h3>
    <p class="text-gray-600 mb-6 max-w-md">{{ message }}</p>
    <slot name="action"></slot>
  </div>
</template>

<script setup lang="ts">
defineProps<{
  icon?: string
  title: string
  message: string
}>()
</script>
