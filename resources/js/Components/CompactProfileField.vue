<!-- components/CompactProfileField.vue -->
<template>
  <div class="text-xs">
    <label class="block font-medium text-gray-700 mb-0.5">{{ label }}</label>
    <div class="flex items-center space-x-1">
      <span v-if="hasChange" class="text-red-500 line-through flex-shrink-0 text-[10px]">
        {{ currentValue || 'Empty' }}
      </span>
      <span v-if="hasChange" class="text-gray-400 text-[10px]">→</span>
      <span :class="{
        'text-green-600': hasChange,
        'text-gray-600': !hasChange
      }" class="font-medium truncate">
        {{ value || 'Empty' }}
      </span>
      <span v-if="hasChange" class="ml-1 px-1 py-0.5 bg-blue-100 text-blue-700 rounded text-[10px] font-medium">
        CHANGED
      </span>
    </div>
  </div>
</template>

<script setup>
defineProps({
  label: String,
  value: [String, Number],
  currentValue: [String, Number],
  hasChange: Boolean
});
</script>