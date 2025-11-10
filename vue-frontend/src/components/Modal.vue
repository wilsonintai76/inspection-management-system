<template>
  <dialog :id="id" class="modal" :class="{ 'modal-open': modelValue }">
    <div class="modal-box" :class="sizeClass">
      <form method="dialog">
        <button 
          class="btn btn-sm btn-circle btn-ghost absolute right-2 top-2"
          @click="close"
        >
          ✕
        </button>
      </form>
      <h3 v-if="title" class="font-bold text-lg mb-4">{{ title }}</h3>
      <div class="py-4">
        <slot></slot>
      </div>
      <div v-if="$slots.actions" class="modal-action">
        <slot name="actions"></slot>
      </div>
    </div>
    <form method="dialog" class="modal-backdrop" @click="close">
      <button>close</button>
    </form>
  </dialog>
</template>

<script setup lang="ts">
import { computed } from 'vue'

const props = defineProps<{
  id?: string
  modelValue: boolean
  title?: string
  size?: 'sm' | 'md' | 'lg' | 'xl'
}>()

const emit = defineEmits<{
  (e: 'update:modelValue', value: boolean): void
}>()

const sizeClass = computed(() => {
  if (!props.size || props.size === 'md') return ''
  return `w-11/12 max-w-${props.size === 'sm' ? 'md' : props.size === 'lg' ? '3xl' : '5xl'}`
})

const close = () => {
  emit('update:modelValue', false)
}
</script>
