
<template>
  <button
    :class="[
      'px-4 py-2 text-sm font-medium rounded-t-md border-b-2 transition-colors duration-200',
      active
        ? 'border-blue-500 text-blue-600 bg-blue-50'
        : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
    ]"
    v-bind="$attrs"
  >
    <slot />
  </button>
</template>

<script setup>
defineProps({
  active: {
    type: Boolean,
    default: false
  }
});
</script>
[file content end]