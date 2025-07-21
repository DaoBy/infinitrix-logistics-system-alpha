<template>
  <div class="space-y-3">
    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">
      {{ title }}
    </h3>
    <div class="space-y-4">
      <slot />
    </div>
  </div>
</template>

<script setup>
defineProps({
  title: {
    type: String,
    required: true
  }
});
</script>