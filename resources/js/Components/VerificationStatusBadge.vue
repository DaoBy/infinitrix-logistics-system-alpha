<template>
  <span :class="statusClasses" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium">
    {{ statusLabel }}
  </span>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  status: {
    type: String,
    required: true
  }
})

const statusLabel = computed(() => {
  const labels = {
    verified: 'Verified',
    missing: 'Missing',
    damaged: 'Damaged',
    wrong_branch: 'Wrong Branch'
  }
  return labels[props.status] || props.status
})

const statusClasses = computed(() => {
  const classes = {
    verified: 'bg-green-100 text-green-800',
    missing: 'bg-red-100 text-red-800',
    damaged: 'bg-orange-100 text-orange-800',
    wrong_branch: 'bg-yellow-100 text-yellow-800'
  }
  return classes[props.status] || 'bg-gray-100 text-gray-800'
})
</script>