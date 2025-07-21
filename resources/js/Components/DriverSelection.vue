<template>
  <div>
    <InputLabel for="driver_id" value="Driver *" />
    <select
      id="driver_id"
      v-model="selectedDriver"
      class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm"
      :class="{ 'border-red-500 dark:border-red-500': error }"
    >
      <option value="">Select a driver</option>
      <option 
        v-for="driver in drivers" 
        :key="driver.id" 
        :value="driver.id"
        :disabled="!driver.available"
      >
        {{ driver.name }} ({{ driver.employee_id }}) 
        <template v-if="!driver.available">- Currently assigned to {{ driver.current_assignment }} deliveries</template>
      </option>
    </select>
    <InputError :message="error" class="mt-2" />
  </div>
</template>

<script setup>
import { computed } from 'vue';
import InputLabel from './InputLabel.vue';
import InputError from './InputError.vue';

const props = defineProps({
  modelValue: [String, Number],
  drivers: {
    type: Array,
    default: () => []
  },
  error: String
});

const emit = defineEmits(['update:modelValue']);

const selectedDriver = computed({
  get: () => props.modelValue,
  set: (value) => emit('update:modelValue', value),
});
</script>