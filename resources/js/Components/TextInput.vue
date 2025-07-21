<script setup>
import { onMounted, ref } from 'vue';

// Define the input model (two-way binding)
const model = defineModel({
  type: [String, Number],
  required: true,
});

// Props for flexibility
defineProps({
  autofocus: { type: Boolean, default: false }, // Autofocus support
  placeholder: { type: String, default: '' },   // Placeholder text
});

// Input reference
const input = ref(null);

// Focus on mount if autofocus is true
onMounted(() => {
  if (input.value && input.value.hasAttribute('autofocus')) {
    input.value.focus();
  }
});

// Expose focus method to parent components
defineExpose({
  focus: () => input.value?.focus(),
});
</script>

<template>
  <input
    ref="input"
    v-model="model"
    :placeholder="placeholder"
    class="custom-input"
    :autofocus="autofocus"
  />
</template>

<style scoped>
.custom-input {
  border: 1px solid #4a4a4a; /* Thicker and darker outline */
  border-radius: 8px; /* Rounded corners */
  padding: 10px 14px; /* Comfortable padding */
  outline: none; /* Remove default outline */
  background: var(--input-bg, #fff);
  color: var(--input-text, #333);
  transition: border-color 0.3s ease, box-shadow 0.3s ease;
}

/* Focus (blue glow effect) */
.custom-input:focus {
  border-color: #1976D2;
  box-shadow: 0 0 6px rgba(25, 118, 210, 0.5);
}

/* Dark mode styling */
.dark .custom-input {
  --input-bg: #1a1a1a;
  --input-text: #e0e0e0;
  border-color: #666;
}

.dark .custom-input:focus {
  border-color: #42a5f5;
  box-shadow: 0 0 6px rgba(66, 165, 245, 0.7);
}
</style>
