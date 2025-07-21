<template>
  <div>
    <InputLabel for="truck_id" value="Truck *" />
    <select
      id="truck_id"
      v-model="selectedTruckId"
      class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm"
      :class="{ 'border-red-500 dark:border-red-500': error }"
    >
      <option value="">Select a truck</option>
      <option
        v-for="truck in trucks"
        :key="truck.id"
        :value="truck.id"
        :disabled="(requiredVolume > 0 && truck.available_volume_capacity < requiredVolume) || 
                   (requiredWeight > 0 && truck.available_weight_capacity < requiredWeight)"
      >
        {{ truck.license_plate }} - {{ truck.make }} {{ truck.model }}
        ({{ formatNumber(truck.available_volume_capacity) }}/{{ formatNumber(truck.volume_capacity) }} m³, 
        {{ formatNumber(truck.available_weight_capacity) }}/{{ formatNumber(truck.weight_capacity) }} kg)
        <span v-if="truck.capacity_status" class="ml-2">
          <!-- Capacity badge -->
          <span class="inline-flex items-center">
            <span class="text-xs mr-2">Capacity:</span>
            <span class="text-xs px-2 py-0.5 rounded-full"
              :class="{
                'bg-red-100 text-red-800': truck.capacity_status === 'critical',
                'bg-yellow-100 text-yellow-800': truck.capacity_status === 'warning',
                'bg-green-100 text-green-800': truck.capacity_status === 'normal'
              }"
            >
              {{ truck.capacity_status }}
            </span>
          </span>
        </span>
      </option>
    </select>

    <InputError :message="error" class="mt-2" />

    <p v-if="requiredVolume > 0" class="mt-1 text-xs text-gray-500 dark:text-gray-400">
      Required volume: {{ formatNumber(requiredVolume) }} m³
    </p>
    <p v-if="requiredWeight > 0" class="mt-1 text-xs text-gray-500 dark:text-gray-400">
      Required weight: {{ formatNumber(requiredWeight) }} kg
    </p>

    <div v-if="selectedTruckData" class="mt-3 space-y-2">
      <div>
        <p class="text-sm text-gray-700 dark:text-gray-300">
          Volume Capacity: {{ formatNumber(selectedTruckData.available_volume_capacity) }} / {{ formatNumber(selectedTruckData.volume_capacity) }} m³
        </p>
        <div class="w-full bg-gray-300 dark:bg-gray-700 rounded-full h-2.5 mt-1">
          <div
            class="bg-blue-600 h-2.5 rounded-full"
            :style="{ width: `${(selectedTruckData.available_volume_capacity / selectedTruckData.volume_capacity) * 100}%` }"
          ></div>
        </div>
      </div>
      <div>
        <p class="text-sm text-gray-700 dark:text-gray-300">
          Weight Capacity: {{ formatNumber(selectedTruckData.available_weight_capacity) }} / {{ formatNumber(selectedTruckData.weight_capacity) }} kg
        </p>
        <div class="w-full bg-gray-300 dark:bg-gray-700 rounded-full h-2.5 mt-1">
          <div
            class="bg-green-600 h-2.5 rounded-full"
            :style="{ width: `${(selectedTruckData.available_weight_capacity / selectedTruckData.weight_capacity) * 100}%` }"
          ></div>
        </div>
      </div>
      <div class="flex items-center mt-1">
        <span class="text-xs mr-2">Capacity:</span>
        <span class="text-xs px-2 py-0.5 rounded-full"
          :class="{
            'bg-red-100 text-red-800': selectedTruckData.capacity_status === 'critical',
            'bg-yellow-100 text-yellow-800': selectedTruckData.capacity_status === 'warning',
            'bg-green-100 text-green-800': selectedTruckData.capacity_status === 'normal'
          }"
        >
          {{ selectedTruckData.capacity_status }}
        </span>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import InputLabel from './InputLabel.vue';
import InputError from './InputError.vue';

const props = defineProps({
  modelValue: [String, Number, null],
  trucks: Array,
  requiredVolume: {
    type: Number,
    default: 0
  },
  requiredWeight: {
    type: Number,
    default: 0
  },
  error: String,
});

const emit = defineEmits(['update:modelValue']);

const selectedTruckId = computed({
  get: () => props.modelValue,
  set: (value) => emit('update:modelValue', value),
});

const selectedTruckData = computed(() =>
  props.trucks.find(t => t.id === selectedTruckId.value)
);

function formatNumber(value) {
  if (value === undefined || value === null || isNaN(value)) return 'N/A';
  return parseFloat(value).toFixed(2);
}
</script>
