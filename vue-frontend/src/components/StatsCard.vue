<template>
  <div class="stats shadow">
    <div class="stat">
      <div class="stat-figure text-primary text-3xl">
        <!-- Support FA classes or raw text/emoji -->
        <i v-if="isFaIcon" :class="icon" aria-hidden="true"></i>
        <span v-else>{{ icon }}</span>
      </div>
      <div class="stat-title">{{ title }}</div>
      <div class="stat-value" :class="valueClass">{{ value }}</div>
      <div v-if="description" class="stat-desc">{{ description }}</div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'

const props = defineProps<{
  icon: string
  title: string
  value: string | number
  description?: string
  valueClass?: string
}>()

const isFaIcon = computed(() => /(^fa[srlbd]?$|fa-)/.test(props.icon))
</script>
