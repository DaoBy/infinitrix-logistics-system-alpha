<!-- resources/js/Components/CargoAssignment/BatchPlanningPanel.vue -->
<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
      <div class="flex items-center">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-blue-600 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
        </svg>
        <div>
          <h3 class="font-semibold text-blue-900">Batch Planning Mode</h3>
          <p class="text-blue-700 text-sm">Efficiently assign multiple deliveries with optimized routing</p>
        </div>
      </div>
    </div>

    <!-- Batch Strategies -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
      <StrategyCard
        v-for="strategy in batchStrategies"
        :key="strategy.id"
        :strategy="strategy"
        :active="selectedStrategy === strategy.id"
        @select="selectedStrategy = strategy.id"
      />
    </div>

    <!-- Strategy Content -->
    <div v-if="selectedStrategy" class="bg-white rounded-lg border border-gray-200 p-6">
      <component 
        :is="strategyComponents[selectedStrategy]"
        :deliveries="filteredDeliveries"
        :driver-truck-sets="driverTruckSets"
        @assign-batch="handleBatchAssign"
      />
    </div>

    <!-- Quick Actions -->
    <div class="bg-gray-50 rounded-lg p-4">
      <h4 class="font-medium text-gray-900 mb-3">Quick Batch Actions</h4>
      <div class="flex space-x-3">
        <PrimaryButton @click="assignAllReady" :disabled="!readyDeliveries.length">
          Assign All Ready Deliveries
        </PrimaryButton>
        <SecondaryButton @click="optimizeByRegion">
          Optimize by Region Groups
        </SecondaryButton>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import StrategyCard from './StrategyCard.vue'
import DestinationStrategy from './batch-strategies/DestinationStrategy.vue'
import BackhaulStrategy from './batch-strategies/BackhaulStrategy.vue'
import TimeBasedStrategy from './batch-strategies/TimeBasedStrategy.vue'
import CapacityStrategy from './batch-strategies/CapacityStrategy.vue'

const props = defineProps({
  deliveries: Array,
  driverTruckSets: Array,
  filteredDeliveries: Array
})

const emit = defineEmits(['assign-batch'])

const selectedStrategy = ref('destination')

const batchStrategies = [
  {
    id: 'destination',
    title: 'By Destination',
    description: 'Group deliveries by destination region',
    icon: '📍',
    color: 'blue'
  },
  {
    id: 'backhaul',
    title: 'Backhaul Optimization',
    description: 'Maximize return trip efficiency',
    icon: '🔄',
    color: 'purple'
  },
  {
    id: 'time',
    title: 'Time-Based',
    description: 'Group by delivery time windows',
    icon: '⏰',
    color: 'green'
  },
  {
    id: 'capacity',
    title: 'Capacity Optimization',
    description: 'Fill trucks efficiently',
    icon: '📦',
    color: 'orange'
  }
]

const strategyComponents = {
  destination: DestinationStrategy,
  backhaul: BackhaulStrategy,
  time: TimeBasedStrategy,
  capacity: CapacityStrategy
}

const readyDeliveries = computed(() => {
  return props.filteredDeliveries.filter(d => d.status === 'ready')
})

const handleBatchAssign = (assignmentData) => {
  emit('assign-batch', assignmentData)
}

const assignAllReady = () => {
  // Implementation for assigning all ready deliveries
  console.log('Assign all ready deliveries')
}

const optimizeByRegion = () => {
  // Implementation for region optimization
  console.log('Optimize by region')
}
</script>