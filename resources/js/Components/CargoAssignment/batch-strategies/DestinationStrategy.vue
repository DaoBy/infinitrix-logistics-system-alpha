<!-- resources/js/Components/CargoAssignment/batch-strategies/DestinationStrategy.vue -->
<template>
  <div class="space-y-4">
    <h4 class="font-semibold text-blue-900">Group by Destination Region</h4>
    <p class="text-sm text-gray-600">Assign deliveries grouped by their destination regions for efficient routing.</p>
    
    <div v-for="group in destinationGroups" :key="group.region.id" class="border border-blue-200 rounded-lg p-4">
      <div class="flex justify-between items-center mb-3">
        <div>
          <h5 class="font-medium text-blue-900">{{ group.region.name }}</h5>
          <p class="text-sm text-blue-700">{{ group.deliveries.length }} deliveries</p>
        </div>
        <PrimaryButton 
          size="sm" 
          @click="assignGroup(group)"
          :disabled="!findSuitableSet(group)"
        >
          Assign Group
        </PrimaryButton>
      </div>
      
      <div class="space-y-2">
        <div v-for="delivery in group.deliveries" :key="delivery.id" class="text-sm">
          <p>DO-{{ delivery.id.toString().padStart(6, '0') }}: {{ delivery.delivery_request.pick_up_region.name }}</p>
        </div>
      </div>
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

const destinationGroups = computed(() => {
  const groups = {}
  props.deliveries.forEach(delivery => {
    const regionId = delivery.delivery_request.drop_off_region.id
    if (!groups[regionId]) {
      groups[regionId] = {
        region: delivery.delivery_request.drop_off_region,
        deliveries: []
      }
    }
    groups[regionId].deliveries.push(delivery)
  })
  return Object.values(groups)
})

const findSuitableSet = (group) => {
  return props.driverTruckSets.find(set => {
    const pickupRegion = group.deliveries[0]?.delivery_request.pick_up_region.id
    return set.region?.id === pickupRegion && set.is_available
  })
}

const assignGroup = (group) => {
  const suitableSet = findSuitableSet(group)
  if (suitableSet) {
    emit('assign-batch', {
      driverTruckSet: suitableSet,
      deliveries: group.deliveries,
      type: 'destination'
    })
  }
}
</script>