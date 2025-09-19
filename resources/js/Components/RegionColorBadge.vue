<template>
  <div class="flex items-center">
    <div
      class="w-4 h-4 rounded-full mr-2 border border-gray-300"
      :style="{ backgroundColor: color }"
    ></div>
    <span v-if="showText">{{ color }}</span>
  </div>
</template>

<script setup>
defineProps({
  color: {
    type: String,
    default: '#CCCCCC'
  },
  showText: {
    type: Boolean,
    default: false
  }
});
</script>