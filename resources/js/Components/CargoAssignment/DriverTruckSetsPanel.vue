<!-- resources/js/Components/CargoAssignment/DriverTruckSetsPanel.vue -->
<template>
  <div class="bg-white shadow-sm rounded-lg border border-gray-200">
    <div class="p-4 bg-gray-50 border-b border-gray-200">
      <h3 class="font-medium text-gray-900">
        Available Driver-Truck Sets
      </h3>
    </div>
    <div class="p-4">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <template v-if="driverTruckSets?.length > 0">
          <div 
            v-for="set in driverTruckSets" 
            :key="set.id" 
            class="border rounded-lg p-4 hover:border-blue-500 transition-colors cursor-pointer"
            :class="{ 
              'border-blue-500 bg-blue-50': selectedSet?.id === set.id,
              'border-purple-500 bg-purple-50': set.available_for_backhaul,
              'opacity-50 cursor-not-allowed': !set.is_available,
              'cursor-pointer': set.is_available
            }"
            @click="set.is_available && $emit('select-set', set)"
          >
            <div class="flex items-start space-x-3">
              <!-- Driver Info -->
              <div class="flex-1">
                <div class="flex items-center space-x-2">
                  <div class="h-10 w-10 rounded-full bg-gray-200 flex items-center justify-center">
                    <span class="text-gray-600 font-medium">{{ set.driver?.initials ?? '' }}</span>
                  </div>
                  <div>
                    <p class="font-medium">{{ set.driver?.name ?? 'N/A' }}</p>
                    <p class="text-xs text-gray-500">{{ set.driver?.employee_id ?? '' }}</p>
                    <span 
                      v-if="set.available_for_backhaul" 
                      class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-purple-100 text-purple-800 mt-1"
                    >
                      <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 mr-1" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M12.586 4.586a2 2 0 112.828 2.828l-3 3a2 2 0 01-2.828 0 1 1 0 00-1.414 1.414 4 4 0 005.656 0l3-3a4 4 0 00-5.656-5.656l-1.5 1.5a1 1 0 101.414 1.414l1.5-1.5zm-5 5a2 2 0 012.828 0 1 1 0 101.414-1.414 4 4 0 00-5.656 0l-3 3a4 4 0 105.656 5.656l1.5-1.5a1 1 0 10-1.414-1.414l-1.5 1.5a2 2 0 11-2.828-2.828l3-3z" clip-rule="evenodd" />
                      </svg>
                      Backhaul Available
                    </span>
                  </div>
                </div>
                <div class="mt-2 text-xs">
                  <p>Current Assignments: {{ set.driver?.current_assignments ?? 0 }}</p>
                  <p>Available: 
                    <span :class="set.driver?.canAcceptNewAssignment ? 'text-green-600' : 'text-red-600'">
                      {{ set.driver?.canAcceptNewAssignment ? 'Yes' : 'No' }}
                    </span>
                  </p>
                  <p v-if="set.available_for_backhaul" class="text-purple-600">
                    Current: {{ set.current_region?.name ?? 'N/A' }}
                  </p>
                  <p v-else class="text-blue-600">
                    Home: {{ set.region?.name ?? 'N/A' }}
                  </p>
                </div>
              </div>
              <!-- Truck Info -->
              <div class="flex-1">
                <div class="flex items-center space-x-2">
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-indigo-500" viewBox="0 0 20 20" fill="currentColor">
                    <path d="M8 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM15 16.5a1 1 0 11-3 0 1 1 0 013 0z" />
                    <path d="M3 4a1 1 0 00-1 1v10a1 1 0 001 1h1.05a2.5 2.5 0 014.9 0H10a1 1 0 001-1v-1a1 1 0 011-1h2a1 1 0 011 1v1a1 1 0 001 1h1.05a2.5 2.5 0 014.9 0H19a1 1 0 001-1V5a1 1 0 00-1-1H3z" />
                  </svg>
                  <div>
                    <p class="font-medium">{{ set.truck?.license_plate ?? 'N/A' }}</p>
                    <p class="text-xs text-gray-500">{{ set.truck?.make ?? '' }} {{ set.truck?.model ?? '' }}</p>
                    <span 
                      v-if="set.truck?.status === 'available_for_backhaul'"
                      class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-purple-100 text-purple-800 mt-1"
                    >
                      Backhaul
                    </span>
                  </div>
                </div>
                <div class="mt-2 space-y-2">
                  <!-- Volume Capacity -->
                  <div>
                    <p class="text-xs">Volume: {{ (set.current_volume ?? 0).toFixed(2) }} / {{ set.truck?.volume_capacity ?? 0 }} m³</p>
                    <div class="w-full bg-gray-200 rounded-full h-1.5">
                      <div 
                        class="bg-blue-600 h-1.5 rounded-full" 
                        :style="{ width: `${Math.min(100, ((set.current_volume ?? 0) / (set.truck?.volume_capacity || 1)) * 100)}%` }"
                      ></div>
                    </div>
                  </div>
                  <!-- Weight Capacity -->
                  <div>
                    <p class="text-xs">Weight: {{ (set.current_weight ?? 0).toFixed(2) }} / {{ set.truck?.weight_capacity ?? 0 }} kg</p>
                    <div class="w-full bg-gray-200 rounded-full h-1.5">
                      <div 
                        class="bg-green-600 h-1.5 rounded-full" 
                        :style="{ width: `${Math.min(100, ((set.current_weight ?? 0) / (set.truck?.weight_capacity || 1)) * 100)}%` }"
                      ></div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Current Trips -->
            <div v-if="set.active_orders?.length > 0" class="mt-3 pt-3 border-t border-gray-200">
              <p class="text-xs font-medium mb-1">Current Trips:</p>
              <div v-for="order in set.active_orders" :key="order.id" class="text-xs">
                <p>DO-{{ order.id }}: {{ order.status }}</p>
              </div>
            </div>

            <!-- Backhaul Management Buttons -->
            <div v-if="set.is_available && !set.available_for_backhaul" class="mt-3 pt-3 border-t border-gray-200">
              <SecondaryButton 
                size="xs" 
                class="w-full"
                @click.stop="$emit('enable-backhaul', set)"
                :disabled="!canEnableBackhaul(set)"
              >
                Enable Backhaul
              </SecondaryButton>
            </div>

            <!-- Dispatch Button -->
            <PrimaryButton
              class="mt-4 w-full"
              :disabled="!set.is_available || dispatchingSetId === set.id"
              @click.stop="$emit('open-dispatch-modal', set)"
            >
              <span v-if="dispatchingSetId === set.id">
                <LoadingSpinner size="xs" class="mr-2" /> Dispatching...
              </span>
              <span v-else>
                Dispatch
              </span>
            </PrimaryButton>
          </div>
        </template>
        <div v-else class="p-4 text-center text-gray-500">
          No available driver-truck sets found
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
defineProps({
  driverTruckSets: Array,
  selectedSet: Object,
  dispatchingSetId: Number
})

defineEmits(['select-set', 'open-dispatch-modal', 'enable-backhaul'])

const canEnableBackhaul = (set) => {
  return set.is_available && 
         !set.available_for_backhaul && 
         set.driver?.canAcceptNewAssignment &&
         set.truck?.status === 'available'
}
</script>