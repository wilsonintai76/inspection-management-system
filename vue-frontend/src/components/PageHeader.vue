<template>
  <div class="flex items-center justify-between mb-6 px-6 pt-6">
    <div class="flex items-center gap-3">
      <!-- Render Font Awesome classes when provided, else render the raw text (e.g. emoji) -->
      <i v-if="isFaIcon" :class="['text-3xl', icon]" aria-hidden="true"></i>
      <span v-else class="text-3xl">{{ icon }}</span>
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
import { computed } from 'vue'

const props = defineProps<{
  icon: string
  title: string
  subtitle?: string
}>()

// Heuristic: treat strings containing 'fa-' or starting with common FA style prefixes as FA classes
const isFaIcon = computed(() => /(^fa[srlbd]?$|fa-)/.test(props.icon))
</script>
