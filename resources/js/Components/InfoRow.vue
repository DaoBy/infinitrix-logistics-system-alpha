<template>
  <div class="flex justify-between py-1 border-b">
    <span class="text-gray-500">{{ label }}</span>
    <span class="font-medium">{{ value || 'N/A' }}</span>
  </div>
</template>

<script setup>
defineProps({
  label: String,
  value: [String, Number]
});
</script>