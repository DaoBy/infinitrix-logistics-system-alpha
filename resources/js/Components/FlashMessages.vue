<template>
  <div v-if="flash.status || flash.success || flash.error" class="space-y-2">
    <div v-if="flash.status" class="p-3 text-sm rounded-md bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-300">
      {{ flash.status }}
    </div>
    <div v-if="flash.success" class="p-3 text-sm rounded-md bg-green-50 dark:bg-green-900/30 text-green-600 dark:text-green-300">
      {{ flash.success }}
    </div>
    <div v-if="flash.error" class="p-3 text-sm rounded-md bg-red-50 dark:bg-red-900/30 text-red-600 dark:text-red-300">
      {{ flash.error }}
    </div>
  </div>
</template>

<script setup>
defineProps({
  flash: {
    type: Object,
    default: () => ({})
  }
});
</script>