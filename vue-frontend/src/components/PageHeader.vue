<template>
  <div class="flex items-center justify-between mb-6 px-6 pt-6">
    <div class="flex items-center gap-3">
      <span class="text-3xl">{{ icon }}</span>
      <div>
        <h1 class="text-2xl font-bold text-gray-900">{{ title }}</h1>
        <p v-if="subtitle" class="text-sm text-gray-600 mt-1">{{ subtitle }}</p>
      </div>
    </div>
    <div class="flex items-center gap-3">
      <slot name="actions"></slot>
    </div>
  </div>
</template>

<script setup lang="ts">
defineProps<{
  icon: string
  title: string
  subtitle?: string
}>()
</script>
