<!-- resources/js/Components/CargoAssignment/BackhaulOptimizerPanel.vue -->
<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="bg-purple-50 border border-purple-200 rounded-lg p-4">
      <div class="flex items-center">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-purple-600 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
        </svg>
        <div>
          <h3 class="font-semibold text-purple-900">Backhaul Optimizer</h3>
          <p class="text-purple-700 text-sm">Maximize efficiency by utilizing return trips to home regions</p>
        </div>
      </div>
    </div>

    <!-- Backhaul Opportunities -->
    <div v-if="backhaulOpportunities.length > 0" class="space-y-4">
      <div v-for="opportunity in backhaulOpportunities" :key="opportunity.driverTruckSet.id" class="bg-white border border-purple-200 rounded-lg p-4">
        <div class="flex justify-between items-start mb-4">
          <div>
            <h4 class="font-semibold text-purple-900">{{ opportunity.driverTruckSet.driver.name }}</h4>
            <p class="text-sm text-purple-700">{{ opportunity.driverTruckSet.truck.license_plate }}</p>
            <p class="text-xs text-gray-500">
              Current: {{ opportunity.driverTruckSet.current_region.name }} → 
              Home: {{ opportunity.driverTruckSet.region.name }}
            </p>
          </div>
          <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-purple-100 text-purple-800">
            🔄 Backhaul Ready
          </span>
        </div>

        <!-- Compatible Deliveries -->
        <div v-if="opportunity.compatibleDeliveries.length > 0">
          <h5 class="font-medium text-gray-900 mb-2">Compatible Deliveries:</h5>
          <div class="space-y-2">
            <div 
              v-for="delivery in opportunity.compatibleDeliveries" 
              :key="delivery.id"
              class="flex items-center justify-between p-3 border border-gray-200 rounded-lg hover:bg-gray-50"
            >
              <div>
                <p class="font-medium">DO-{{ delivery.id.toString().padStart(6, '0') }}</p>
                <p class="text-sm text-gray-600">
                  {{ delivery.delivery_request.pick_up_region.name }} → 
                  {{ delivery.delivery_request.drop_off_region.name }}
                </p>
                <p class="text-xs text-gray-500">
                  {{ calculateTotalVolume(delivery.delivery_request.packages) }} m³ • 
                  {{ calculateTotalWeight(delivery.delivery_request.packages) }} kg
                </p>
              </div>
              <PrimaryButton size="sm" @click="assignBackhaulDelivery(opportunity.driverTruckSet, delivery)">
                Assign
              </PrimaryButton>
            </div>
          </div>

          <!-- Batch Assign -->
          <div class="mt-4 pt-4 border-t border-gray-200">
            <PrimaryButton 
              @click="assignBackhaulBatch(opportunity.driverTruckSet, opportunity.compatibleDeliveries)"
              class="w-full"
            >
              Assign All {{ opportunity.compatibleDeliveries.length }} Backhaul Deliveries
            </PrimaryButton>
          </div>
        </div>

        <div v-else class="text-center text-gray-500 py-4">
          No compatible backhaul deliveries found
        </div>
      </div>
    </div>

    <!-- No Opportunities -->
    <div v-else class="bg-yellow-50 border border-yellow-200 rounded-lg p-6 text-center">
      <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-yellow-400 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z" />
      </svg>
      <h3 class="font-semibold text-yellow-800 mb-1">No Backhaul Opportunities</h3>
      <p class="text-yellow-700">There are currently no driver-truck sets eligible for backhaul assignments.</p>
    </div>

    <!-- Backhaul Statistics -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
      <StatCard
        title="Backhaul Eligible Sets"
        :value="backhaulEligibleSets.length"
        icon="🚛"
        color="purple"
      />
      <StatCard
        title="Compatible Deliveries"
        :value="totalCompatibleDeliveries"
        icon="📦"
        color="green"
      />
      <StatCard
        title="Potential Savings"
        value="High"
        icon="💰"
        color="blue"
      />
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import StatCard from './StatCard.vue'

const props = defineProps({
  deliveries: Array,
  driverTruckSets: Array
})

const emit = defineEmits(['assign-backhaul'])

const backhaulEligibleSets = computed(() => {
  return props.driverTruckSets.filter(set => 
    set.current_status === 'backhaul_eligible' || set.available_for_backhaul
  )
})

const backhaulOpportunities = computed(() => {
  return backhaulEligibleSets.value.map(set => {
    const compatibleDeliveries = props.deliveries.filter(delivery => {
      return delivery.delivery_request.pick_up_region_id === set.current_region_id &&
             delivery.delivery_request.drop_off_region_id === set.region_id &&
             delivery.status === 'ready'
    })

    return {
      driverTruckSet: set,
      compatibleDeliveries
    }
  })
})

const totalCompatibleDeliveries = computed(() => {
  return backhaulOpportunities.value.reduce((total, opportunity) => {
    return total + opportunity.compatibleDeliveries.length
  }, 0)
})

const calculateTotalVolume = (packages) => {
  if (!packages || !Array.isArray(packages)) return 0
  return packages.reduce((total, pkg) => total + (Number(pkg.volume) || 0), 0)
}

const calculateTotalWeight = (packages) => {
  if (!packages || !Array.isArray(packages)) return 0
  return packages.reduce((total, pkg) => total + (Number(pkg.weight) || 0), 0)
}

const assignBackhaulDelivery = (driverTruckSet, delivery) => {
  emit('assign-backhaul', {
    driverTruckSet,
    deliveries: [delivery],
    type: 'backhaul'
  })
}

const assignBackhaulBatch = (driverTruckSet, deliveries) => {
  emit('assign-backhaul', {
    driverTruckSet,
    deliveries,
    type: 'backhaul'
  })
}
</script>