<!-- resources/js/Components/CargoAssignment/batch-strategies/BackhaulStrategy.vue -->
<template>
  <div class="space-y-4">
    <h4 class="font-semibold text-purple-900">Backhaul Optimization</h4>
    <p class="text-sm text-gray-600">Maximize efficiency by utilizing driver return trips to home regions.</p>
    
    <div v-if="backhaulGroups.length > 0" class="space-y-4">
      <div v-for="group in backhaulGroups" :key="group.driverTruckSet.id" class="border border-purple-200 rounded-lg p-4">
        <div class="flex justify-between items-center mb-3">
          <div>
            <h5 class="font-medium text-purple-900">{{ group.driverTruckSet.driver.name }}</h5>
            <p class="text-sm text-purple-700">
              {{ group.driverTruckSet.current_region.name }} → {{ group.driverTruckSet.region.name }}
            </p>
            <p class="text-xs text-gray-500">{{ group.compatibleDeliveries.length }} compatible deliveries</p>
          </div>
          <PrimaryButton 
            size="sm" 
            @click="assignBackhaulGroup(group)"
            :disabled="group.compatibleDeliveries.length === 0"
          >
            Assign Backhaul
          </PrimaryButton>
        </div>
        
        <div v-if="group.compatibleDeliveries.length > 0" class="space-y-2">
          <div v-for="delivery in group.compatibleDeliveries" :key="delivery.id" class="text-sm">
            <p>DO-{{ delivery.id.toString().padStart(6, '0') }}</p>
          </div>
        </div>
        <div v-else class="text-sm text-gray-500">
          No compatible backhaul deliveries found
        </div>
      </div>
    </div>
    
    <div v-else class="text-center text-gray-500 py-8">
      <p>No backhaul opportunities available at the moment.</p>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  deliveries: Array,
  driverTruckSets: Array
})

const emit = defineEmits(['assign-batch'])

const backhaulGroups = computed(() => {
  const backhaulSets = props.driverTruckSets.filter(set => 
    set.current_status === 'backhaul_eligible' || set.available_for_backhaul
  )
  
  return backhaulSets.map(set => {
    const compatibleDeliveries = props.deliveries.filter(delivery => 
      delivery.delivery_request.pick_up_region_id === set.current_region_id &&
      delivery.delivery_request.drop_off_region_id === set.region_id
    )
    
    return {
      driverTruckSet: set,
      compatibleDeliveries
    }
  })
})

const assignBackhaulGroup = (group) => {
  emit('assign-batch', {
    driverTruckSet: group.driverTruckSet,
    deliveries: group.compatibleDeliveries,
    type: 'backhaul'
  })
}
</script>