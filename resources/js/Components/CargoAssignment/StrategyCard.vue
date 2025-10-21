<!-- resources/js/Components/CargoAssignment/StrategyCard.vue -->
<template>
  <button
    @click="$emit('select')"
    :class="[
      'p-4 rounded-lg border-2 text-left transition-all duration-200 hover:shadow-md',
      active 
        ? `border-${color}-500 bg-${color}-50` 
        : 'border-gray-200 bg-white hover:border-gray-300'
    ]"
  >
    <div class="text-2xl mb-2">{{ strategy.icon }}</div>
    <h4 class="font-semibold text-gray-900 mb-1">{{ strategy.title }}</h4>
    <p class="text-sm text-gray-600 leading-tight">{{ strategy.description }}</p>
    <div v-if="active" :class="`mt-2 text-${color}-600 text-xs font-medium`">
      ✓ Selected
    </div>
  </button>
</template>

<script setup>
defineProps({
  strategy: Object,
  active: Boolean
})

defineEmits(['select'])

// Color mapping for Tailwind
const colorMap = {
  blue: 'blue',
  purple: 'purple', 
  green: 'green',
  orange: 'orange'
}
</script>