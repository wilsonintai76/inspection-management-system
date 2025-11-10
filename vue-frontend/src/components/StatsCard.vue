<template>
  <div class="stats shadow">
    <div class="stat">
      <div class="stat-figure text-primary text-3xl">
        {{ icon }}
      </div>
      <div class="stat-title">{{ title }}</div>
      <div class="stat-value" :class="valueClass">{{ value }}</div>
      <div v-if="description" class="stat-desc">{{ description }}</div>
    </div>
  </div>
</template>

<script setup lang="ts">
defineProps<{
  icon: string
  title: string
  value: string | number
  description?: string
  valueClass?: string
}>()
</script>
