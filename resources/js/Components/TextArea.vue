<template>
  <div>
    <label v-if="label" class="block text-sm font-medium text-gray-700 mb-1">
      {{ label }}
      <span v-if="required" class="text-red-600">*</span>
    </label>
    <v-textarea
      :model-value="modelValue"
      @update:model-value="$emit('update:modelValue', $event)"
      :rows="rows"
      :label="label"
      :error="!!error"
      :error-messages="error"
      :required="required"
      outlined
      auto-grow
      hide-details="auto"
      class="w-full"
    ></v-textarea>
  </div>
</template>

<script setup>
defineProps({
  modelValue: String,
  label: String,
  rows: {
    type: Number,
    default: 3,
  },
  required: {
    type: Boolean,
    default: false,
  },
  error: String,
});

defineEmits(['update:modelValue']);
</script>
