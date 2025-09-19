<template>
  <div class="flex justify-between items-start py-2">
    <div class="flex-1">
      <span class="text-sm font-medium text-gray-700">{{ label }}:</span>
      <div v-if="hasChange" class="mt-1">
        <p class="text-sm text-gray-500 line-through">{{ currentValue || 'Not set' }}</p>
        <p class="text-sm text-green-600 font-medium">{{ value || 'Not set' }}</p>
      </div>
      <p v-else class="text-sm text-gray-900 mt-1">{{ value || 'Not set' }}</p>
    </div>
  </div>
</template>

<script>
export default {
  props: {
    label: String,
    value: [String, Number],
    currentValue: [String, Number],
    hasChange: Boolean
  }
};
</script>