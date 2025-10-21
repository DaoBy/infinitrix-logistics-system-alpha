<!-- resources/js/Components/CargoAssignment/BatchSuggestionsPanel.vue -->
<template>
  <div class="bg-white shadow-sm rounded-lg border border-gray-200">
    <div class="p-4 bg-gray-50 border-b border-gray-200">
      <h3 class="font-medium text-gray-900">
        Batch Assignment Suggestions
      </h3>
    </div>
    <div class="p-4">
      <div v-if="batchSuggestions.length > 0" class="space-y-4">
        <div v-for="suggestion in batchSuggestions" :key="suggestion.destination_region.id" 
             class="border rounded-lg p-4 hover:bg-gray-50">
          <div class="flex justify-between items-start">
            <div>
              <p class="font-medium">
                From: {{ suggestion.pickup_region?.name ?? 'N/A' }} → To: {{ suggestion.destination_region.name }}
              </p>
              <p class="text-sm text-gray-600">
                {{ suggestion.delivery_requests.length }} deliveries
              </p>
              <p class="text-sm mt-2">
                Total Volume: {{ (suggestion.total_volume || 0).toFixed(2) }} m³ | 
                Total Weight: {{ (suggestion.total_weight || 0).toFixed(2) }} kg
              </p>
              <p v-if="isBackhaulSuggestion(suggestion)" class="text-purple-600 text-sm mt-1">
                💡 Backhaul Opportunity: Deliveries from current region to home region
              </p>
              <p v-if="hasUnstickerizedPackagesInSuggestion(suggestion)" class="text-yellow-600 text-sm mt-1">
                ⚠️ Some deliveries have packages without stickers
              </p>
            </div>
            <PrimaryButton 
              size="sm" 
              @click="$emit('prepare-batch-assignment', suggestion)"
              :disabled="!suitableDriverTruckSets(suggestion).length || hasUnstickerizedPackagesInSuggestion(suggestion)"
            >
              Assign Batch
            </PrimaryButton>
          </div>
          
          <div v-if="suitableDriverTruckSets(suggestion).length" class="mt-4">
            <p class="text-sm font-medium">Suitable Driver-Truck Sets (Region: {{ suggestion.pickup_region?.name ?? 'N/A' }}):</p>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-2 mt-2">
              <div v-for="set in suitableDriverTruckSets(suggestion)" 
                   :key="set.id"
                   class="p-2 border rounded cursor-pointer"
                   :class="{ 
                     'border-blue-500': selectedSet?.id === set.id,
                     'border-purple-500': set.available_for_backhaul
                   }"
                   @click="$emit('select-set', set)">
                <p class="font-medium">{{ set.driver.name }}</p>
                <p class="text-xs">{{ set.truck.license_plate }}</p>
                <p class="text-xs">Available: {{ set.available_volume.toFixed(2) }}m³ {{ set.available_weight.toFixed(2) }}kg</p>
                <p class="text-xs text-gray-500">Region: {{ set.region?.name ?? 'N/A' }}</p>
                <span 
                  v-if="set.available_for_backhaul" 
                  class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-purple-100 text-purple-800 mt-1"
                >
                  Backhaul
                </span>
              </div>
            </div>
          </div>
          <div v-else class="mt-4 text-sm text-yellow-600">
            No suitable driver-truck sets available in region: {{ suggestion.pickup_region?.name ?? 'N/A' }}
          </div>
        </div>
      </div>
      <div v-else class="text-center text-gray-500">
        No batch assignment suggestions available
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  batchSuggestions: Array,
  driverTruckSets: Array
})

const emit = defineEmits(['prepare-batch-assignment', 'select-set'])

const isBackhaulSuggestion = (suggestion) => {
  const homeRegionId = suggestion.pickup_region?.id
  const currentRegionId = suggestion.destination_region?.id
  return suggestion.delivery_requests.every(delivery => {
    const pickupRegionId = delivery.delivery_request?.pick_up_region?.id
    const dropoffRegionId = delivery.delivery_request?.drop_off_region?.id
    return pickupRegionId === currentRegionId && dropoffRegionId === homeRegionId
  })
}

const hasUnstickerizedPackagesInSuggestion = (suggestion) => {
  return suggestion.delivery_requests.some(delivery => {
    const packages = delivery.delivery_request?.packages || []
    return packages.some(pkg => !pkg.sticker_printed_at)
  })
}

const suitableDriverTruckSets = (suggestion) => {
  if (!props.driverTruckSets) return []
  return props.driverTruckSets.filter(set => {
    const isInRegion = set.region?.id === suggestion.pickup_region?.id || 
                      (set.available_for_backhaul && set.current_region?.id === suggestion.pickup_region?.id)
    const availableVolume = set.truck?.volume_capacity - (set.current_volume || 0)
    const availableWeight = set.truck?.weight_capacity - (set.current_weight || 0)
    const totalVolume = Number(suggestion.total_volume) || 0
    const totalWeight = Number(suggestion.total_weight) || 0
    const hasCapacity = availableVolume >= totalVolume && availableWeight >= totalWeight
    return set.is_available && isInRegion && hasCapacity
  })
}
</script>