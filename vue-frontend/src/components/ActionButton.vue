<template>
  <button 
    :class="buttonClasses"
    :disabled="disabled || loading"
    @click="$emit('click')"
  >
    <span v-if="loading" class="loading loading-spinner loading-sm"></span>
    <span v-if="icon && !loading">{{ icon }}</span>
    <span v-if="$slots.default"><slot></slot></span>
  </button>
</template>

<script setup lang="ts">
import { computed } from 'vue'

const props = defineProps<{
  variant?: 'primary' | 'secondary' | 'success' | 'error' | 'warning' | 'ghost' | 'outline'
  size?: 'xs' | 'sm' | 'md' | 'lg'
  icon?: string
  loading?: boolean
  disabled?: boolean
}>()

defineEmits<{
  (e: 'click'): void
}>()

const buttonClasses = computed(() => {
  const base = 'btn'
  const variant = props.variant ? `btn-${props.variant}` : 'btn-primary'
  const size = props.size ? `btn-${props.size}` : ''
  return [base, variant, size].filter(Boolean).join(' ')
})
</script>
