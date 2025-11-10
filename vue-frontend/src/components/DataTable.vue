<template>
  <div class="overflow-x-auto">
    <table class="table table-zebra">
      <thead>
        <tr>
          <th v-for="column in columns" :key="column.key" :class="column.headerClass">
            {{ column.label }}
          </th>
        </tr>
      </thead>
      <tbody>
        <slot></slot>
        <tr v-if="!hasContent && emptyMessage">
          <td :colspan="columns.length" class="text-center py-8 text-gray-500">
            {{ emptyMessage }}
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<script setup lang="ts">
import { useSlots } from 'vue'

interface Column {
  key: string
  label: string
  headerClass?: string
}

defineProps<{
  columns: Column[]
  emptyMessage?: string
}>()

const slots = useSlots()
const hasContent = !!slots.default
</script>
