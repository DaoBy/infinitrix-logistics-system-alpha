<!-- resources/js/Components/CargoAssignment/SelectedDeliveriesCard.vue -->
<template>
  <div class="bg-white shadow-sm rounded-lg border border-gray-200">
    <div class="p-4 bg-gray-50 border-b border-gray-200">
      <div class="flex justify-between items-center">
        <h3 class="font-medium text-gray-900">
          Selected Deliveries ({{ selectedDeliveries?.length ?? 0 }})
        </h3>
        <button 
          v-if="selectedDeliveries?.length > 0"
          @click="$emit('clear-selection')"
          class="text-xs text-red-500 hover:text-red-700"
        >
          Clear All
        </button>
      </div>
    </div>
    <div class="divide-y divide-gray-200 max-h-96 overflow-y-auto">
      <template v-if="selectedDeliveries?.length > 0">
        <div 
          v-for="delivery in selectedDeliveries" 
          :key="delivery.id" 
          class="p-4 hover:bg-gray-50"
        >
          <div class="flex justify-between items-start">
            <div>
              <p class="text-sm font-medium text-gray-900">
                DO-{{ delivery.id?.toString()?.padStart(6, '0') }}
                <span 
                  v-if="delivery.is_backhaul" 
                  class="ml-1 inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-purple-100 text-purple-800"
                >
                  Backhaul
                </span>
              </p>
              <p class="text-xs text-gray-500">
                {{ delivery.delivery_request?.pick_up_region?.name ?? 'N/A' }} → 
                {{ delivery.delivery_request?.drop_off_region?.name ?? 'N/A' }}
              </p>
              <div class="mt-1 text-xs">
                <p>Volume: {{ calculateTotalVolume(delivery.delivery_request?.packages ?? []) }} m³</p>
                <p>Weight: {{ calculateTotalWeight(delivery.delivery_request?.packages ?? []) }} kg</p>
                <p v-if="hasUnstickerizedPackages(delivery)" class="text-yellow-600 flex items-center">
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 mr-1" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                  </svg>
                  Missing stickers
                </p>
              </div>
            </div>
            <button 
              @click="$emit('remove-delivery', delivery.id)"
              class="text-red-500 hover:text-red-700"
              title="Remove"
            >
              <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd"/>
              </svg>
            </button>
          </div>
        </div>
      </template>
      <div v-else class="p-4 text-center text-gray-500">
        No deliveries selected
      </div>
    </div>
    <div v-if="selectedDeliveries?.length > 0" class="p-4 border-t border-gray-200">
      <div class="text-sm">
        <p class="font-medium">Total Selected:</p>
        <p>Volume: {{ totalVolume }} m³</p>
        <p>Weight: {{ totalWeight }} kg</p>
        <p v-if="hasAnyUnstickerizedPackages" class="text-yellow-600 font-medium">
          ⚠️ Some packages are missing stickers
        </p>
        <p v-if="hasMixedAssignmentTypes" class="text-red-600 font-medium">
          ⚠️ Mixed assignment types (Regular + Backhaul)
        </p>
        <p v-if="hasBackhaulRulesViolation" class="text-red-600 font-medium">
          ⚠️ Backhaul assignment rules violated
        </p>
      </div>
      <PrimaryButton 
        class="mt-4 w-full"
        :disabled="!selectedSet || !selectedDeliveries.length || hasAnyUnstickerizedPackages || hasMixedAssignmentTypes || hasBackhaulRulesViolation"
        @click="$emit('assign')"
      >
        Assign Selected
      </PrimaryButton>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  selectedDeliveries: Array,
  selectedSet: Object,
  totalVolume: Number,
  totalWeight: Number,
  hasAnyUnstickerizedPackages: Boolean,
  hasMixedAssignmentTypes: Boolean,
  hasBackhaulRulesViolation: Boolean
})

const emit = defineEmits(['remove-delivery', 'clear-selection', 'assign'])

const calculateTotalVolume = (packages) => {
  if (!packages || !Array.isArray(packages)) return 0
  return packages.reduce((total, pkg) => total + (Number(pkg.volume) || 0), 0)
}

const calculateTotalWeight = (packages) => {
  if (!packages || !Array.isArray(packages)) return 0
  return packages.reduce((total, pkg) => total + (Number(pkg.weight) || 0), 0)
}

const hasUnstickerizedPackages = (delivery) => {
  const packages = delivery.delivery_request?.packages || []
  return packages.some(pkg => !pkg.sticker_printed_at)
}
</script>