<template>
  <div class="grid grid-cols-3 gap-4 py-1">
    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">
      {{ label }}
    </dt>
    <dd class="col-span-2 text-sm text-gray-900 dark:text-gray-100">
      <slot name="value">
        {{ value || 'N/A' }}
      </slot>
    </dd>
  </div>
</template>

<script setup>
defineProps({
  label: {
    type: String,
    required: true
  },
  value: {
    type: [String, Number],
    default: null
  }
});
</script>