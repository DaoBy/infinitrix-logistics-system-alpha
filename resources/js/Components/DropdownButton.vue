<script setup>
defineProps({
  onClick: {
    type: Function,
    required: true,
  },
});
</script>

<template>
  <button
    @click="onClick"
    type="button"
    class="block w-full text-left px-4 py-2 text-sm leading-5 text-red-700 transition duration-150 ease-in-out hover:bg-gray-100 focus:bg-gray-100 focus:outline-none dark:text-gray-300 dark:hover:bg-gray-800 dark:focus:bg-gray-800"
  >
    <slot />
  </button>
</template>
