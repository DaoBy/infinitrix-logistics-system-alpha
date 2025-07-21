<script setup>
import { computed } from 'vue'

const props = defineProps({
  id: { type: String, required: true },
  modelValue: { type: [Boolean, Array], default: false },
  value: { type: [String, Number, Boolean], default: null },
  label: { type: String, default: '' },
  name: { type: String, default: '' },
  disabled: { type: Boolean, default: false }
})

const emit = defineEmits(['update:modelValue'])

const isChecked = computed(() => {
  if (Array.isArray(props.modelValue)) {
    return props.modelValue.includes(props.value)
  }
  return props.modelValue
})

function onChange(event) {
  if (Array.isArray(props.modelValue)) {
    const newValue = [...props.modelValue]
    if (event.target.checked) {
      if (!newValue.includes(props.value)) newValue.push(props.value)
    } else {
      const idx = newValue.indexOf(props.value)
      if (idx !== -1) newValue.splice(idx, 1)
    }
    emit('update:modelValue', newValue)
  } else {
    emit('update:modelValue', event.target.checked)
  }
}
</script>

<template>
  <div class="flex items-center space-x-2">
    <input
      :id="id"
      :name="name"
      type="checkbox"
      :checked="isChecked"
      :value="value"
      :disabled="disabled"
      @change="onChange"
      class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 disabled:opacity-50"
    />
    <label v-if="label" :for="id" class="text-sm text-gray-700 select-none">
      {{ label }}
    </label>
  </div>
</template>
