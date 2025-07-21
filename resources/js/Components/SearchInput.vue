<template>
  <div class="relative rounded-md shadow-sm">
    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
      <MagnifyingGlassIcon class="h-5 w-5 text-gray-400" aria-hidden="true" />
    </div>
    <input
      type="text"
      :value="modelValue"
      @input="$emit('update:modelValue', $event.target.value)"
      class="search-input-outline block w-full rounded-md border-2 border-gray-300 focus:border-gray-500 focus:ring-1 focus:ring-gray-400 py-2 pl-10 pr-3 transition-all duration-150 sm:text-sm"
      :placeholder="placeholder"
    />
  </div>
</template>

<script setup>
import { MagnifyingGlassIcon } from '@heroicons/vue/20/solid';

defineProps({
  modelValue: String,
  placeholder: {
    type: String,
    default: 'Search...',
  },
});

defineEmits(['update:modelValue']);
</script>