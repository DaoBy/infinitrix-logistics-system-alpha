<script setup>
import { computed } from 'vue'

const props = defineProps({
  modelValue: [String, Number, Boolean, Object, null],
  options: {
    type: Array,
    default: () => [],
  },
  optionValue: {
    type: String,
    default: 'value',
  },
  optionLabel: {
    type: String,
    default: 'label',
  },
  placeholder: {
    type: String,
    default: 'Select an option',
  },
  disabled: {
    type: Boolean,
    default: false,
  },
  error: {
    type: Boolean,
    default: false,
  },
  errorMessage: {
    type: String,
    default: '',
  },
})

const emit = defineEmits(['update:modelValue'])

const computedItems = computed(() => {
  return props.options.map(option => {
    if (typeof option === 'object' && option !== null) {
      return {
        title: option[props.optionLabel] ?? option[props.optionValue] ?? '',
        value: option[props.optionValue],
      }
    }
    return { title: option, value: option }
  })
})
</script>

<template>
  <v-select
    :model-value="modelValue ?? ''"
    @update:model-value="emit('update:modelValue', $event)"
    :items="computedItems"
    :label="placeholder"
    item-title="title"
    item-value="value"
    :disabled="disabled"
    :error="error"
    :error-messages="errorMessage"
    hide-details="auto"
    density="comfortable"
    class="w-full"
    :menu-props="{
      attach: 'body',
      contentClass: 'select-dropdown-menu',
      zIndex: 9999,
      maxHeight: 304,
      offset: 8,
      eager: true
    }"
  />
</template>

<style scoped>
:deep(.select-dropdown-menu) {
  z-index: 9999 !important;
  position: absolute !important;
}
</style>
