<template>
  <button 
    :class="buttonClasses"
    :disabled="disabled || loading"
    @click="$emit('click')"
  >
    <span v-if="loading" class="loading loading-spinner loading-sm"></span>
  <!-- Font Awesome icon support -->
  <i v-if="icon && isFaIcon && !loading" :class="[icon]" aria-hidden="true"></i>
  <span v-else-if="icon && !loading">{{ icon }}</span>
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

const isFaIcon = computed(() => !!props.icon && /(^fa[srlbd]?$|fa-)/.test(props.icon))

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
