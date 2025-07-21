<template>
  <div class="w-full bg-gray-200 rounded-full h-2.5">
    <div 
      class="bg-blue-600 h-2.5 rounded-full" 
      :style="{ width: `${(value / max) * 100}%` }"
    ></div>
  </div>
</template>

<script setup>
defineProps({
  value: {
    type: Number,
    required: true
  },
  max: {
    type: Number,
    required: true
  }
});
</script>