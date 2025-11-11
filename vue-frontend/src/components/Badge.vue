<template>
  <span :class="badgeClasses">
    <template v-if="label !== undefined && label !== null">
      {{ label }}
    </template>
    <slot v-else></slot>
  </span>
  
</template>

<script setup lang="ts">
import { computed } from 'vue'

const props = defineProps<{
  label?: string | number
  variant?: 'primary' | 'secondary' | 'success' | 'error' | 'warning' | 'info' | 'ghost'
  size?: 'xs' | 'sm' | 'md' | 'lg'
  outline?: boolean
}>()

const badgeClasses = computed(() => {
  const base = 'badge'
  const variant = props.variant ? `badge-${props.variant}` : 'badge-neutral'
  const size = props.size ? `badge-${props.size}` : ''
  const outline = props.outline ? 'badge-outline' : ''
  return [base, variant, size, outline].filter(Boolean).join(' ')
})
</script>
