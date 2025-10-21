<template>
  <button
    @click="$emit('click')"
    :class="[
      'flex flex-col items-center p-4 rounded-lg border-2 transition-all duration-200 w-32',
      active 
        ? 'border-blue-500 bg-blue-50 shadow-sm' 
        : 'border-gray-200 bg-white hover:border-gray-300 hover:bg-gray-50'
    ]"
  >
    <span class="text-2xl mb-2">{{ icon }}</span>
    <span class="font-medium text-gray-900 mb-1">{{ title }}</span>
    <span class="text-xs text-gray-500 text-center leading-tight">{{ description }}</span>
  </button>
</template>

<script setup>
defineProps({
  active: Boolean,
  icon: String,
  title: String,
  description: String
})

defineEmits(['click'])
</script>