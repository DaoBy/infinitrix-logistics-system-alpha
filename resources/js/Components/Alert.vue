<!-- resources/js/Components/Alert.vue -->
<template>
  <div 
    class="p-4 mb-4 rounded"
    :class="{
      'bg-blue-100 text-blue-800': type === 'info',
      'bg-green-100 text-green-800': type === 'success',
      'bg-red-100 text-red-800': type === 'danger',
      'bg-yellow-100 text-yellow-800': type === 'warning',
      'bg-gray-100 text-gray-800': type === 'neutral'
    }"
  >
    <div class="flex items-center">
      <svg v-if="type === 'info'" class="w-5 h-5 mr-2 text-blue-800" fill="currentColor" viewBox="0 0 20 20">
        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2h-1V9z" clip-rule="evenodd" />
      </svg>
      <svg v-if="type === 'success'" class="w-5 h-5 mr-2 text-green-800" fill="currentColor" viewBox="0 0 20 20">
        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
      </svg>
      <svg v-if="type === 'danger'" class="w-5 h-5 mr-2 text-red-800" fill="currentColor" viewBox="0 0 20 20">
        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
      </svg>
      <svg v-if="type === 'warning'" class="w-5 h-5 mr-2 text-yellow-800" fill="currentColor" viewBox="0 0 20 20">
        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
      </svg>
      <span>{{ message }}</span>
    </div>
  </div>
</template>

<script setup>
defineProps({
  type: {
    type: String,
    default: 'info',
    validator: (value) => ['info', 'success', 'danger', 'warning', 'neutral'].includes(value)
  },
  message: {
    type: String,
    required: true
  }
});
</script>