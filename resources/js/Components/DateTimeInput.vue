<template>
  <div>
    <InputLabel :for="id" :value="label" />
    <input
      :id="id"
      type="datetime-local"
      v-model="modelValue"
      class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm"
      :class="{ 'border-red-500 dark:border-red-500': error }"
      :min="minDate"
    />
    <InputError :message="error" class="mt-2" />
  </div>
</template>

<script setup>
import { computed } from 'vue';
import InputLabel from './InputLabel.vue';
import InputError from './InputError.vue';

const props = defineProps({
  modelValue: String,
  label: String,
  error: String,
  minDate: String,
  id: {
    type: String,
    default: () => `datetime-${Math.random().toString(36).substring(2, 9)}`
  }
});

const emit = defineEmits(['update:modelValue']);

const modelValue = computed({
  get() {
    return props.modelValue;
  },
  set(value) {
    emit('update:modelValue', value);
  }
});
</script>