<!-- resources/js/Components/CargoAssignment/QuickAssignPanel.vue -->
<template>
  <div class="space-y-6">
    <!-- Your existing delivery orders table -->
    <div class="bg-white shadow-sm rounded-lg border border-gray-200">
      <div class="p-4 bg-gray-50 border-b border-gray-200 flex justify-between items-center">
        <h3 class="font-medium text-gray-900">
          Delivery Orders - Quick Assign
        </h3>
        <div class="text-sm text-gray-500">
          Showing {{ deliveries?.from ?? 0 }} to {{ deliveries?.to ?? 0 }} of {{ deliveries?.total ?? 0 }} entries
        </div>
      </div>
      
      <DataTable
        :columns="deliveryColumns"
        :data="filteredDeliveries"
        :loading="loading"
        selectable
        @selection-change="$emit('selection-change', $event)"
      >
        <!-- Your existing table templates -->
        <template #status="{ row }">
          <div class="flex items-center space-x-2">
            <StatusBadge :status="row.status" :class="statusBadgeClass(row.status)">
              {{ formatStatusText(row.status) }}
            </StatusBadge>
            <span 
              v-if="row.is_backhaul" 
              class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-purple-100 text-purple-800"
            >
              <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 mr-1" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M12.586 4.586a2 2 0 112.828 2.828l-3 3a2 2 0 01-2.828 0 1 1 0 00-1.414 1.414 4 4 0 005.656 0l3-3a4 4 0 00-5.656-5.656l-1.5 1.5a1 1 0 101.414 1.414l1.5-1.5zm-5 5a2 2 0 012.828 0 1 1 0 101.414-1.414 4 4 0 00-5.656 0l-3 3a4 4 0 105.656 5.656l1.5-1.5a1 1 0 10-1.414-1.414l-1.5 1.5a2 2 0 11-2.828-2.828l3-3z" clip-rule="evenodd" />
              </svg>
              Backhaul
            </span>
          </div>
        </template>
        
        <template #actions="{ row }">
          <div class="flex space-x-1">
            <SecondaryButton @click.stop="$emit('view-details', row.id)" size="xs" title="Details">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
              </svg>
            </SecondaryButton>
            <DangerButton 
              v-if="row.status !== 'completed' && row.status !== 'cancelled'"
              @click.stop="$emit('cancel-delivery', row)" 
              size="xs" 
              title="Cancel"
            >
              <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
              </svg>
            </DangerButton>
          </div>
        </template>
      </DataTable>
      
      <Pagination
        v-if="deliveries?.last_page > 1"
        :pagination="deliveries"
        @page-changed="$emit('page-changed', $event)"
      />
    </div>

    <!-- Assignment Section -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <!-- Selected Deliveries Card -->
      <SelectedDeliveriesCard
        :selected-deliveries="selectedDeliveries"
        :selected-set="selectedSet"
        :total-volume="totalSelectedVolume"
        :total-weight="totalSelectedWeight"
        :has-any-unstickerized-packages="hasAnyUnstickerizedPackages"
        :has-mixed-assignment-types="hasMixedAssignmentTypes"
        :has-backhaul-rules-violation="hasBackhaulAssignmentRulesViolation"
        @remove-delivery="$emit('remove-delivery', $event)"
        @clear-selection="$emit('clear-selection')"
        @assign="$emit('open-assignment-modal')"
      />

      <!-- Driver-Truck Sets -->
      <DriverTruckSetsPanel
        :driver-truck-sets="driverTruckSets"
        :selected-set="selectedSet"
        :dispatching-set-id="dispatchingSetId"
        @select-set="$emit('select-set', $event)"
        @open-dispatch-modal="$emit('open-dispatch-modal', $event)"
        @enable-backhaul="$emit('enable-backhaul', $event)"
      />
    </div>

    <!-- Batch Suggestions -->
    <BatchSuggestionsPanel
      :batch-suggestions="batchSuggestions"
      :driver-truck-sets="driverTruckSets"
      @prepare-batch-assignment="$emit('prepare-batch-assignment', $event)"
    />
  </div>
</template>

<script setup>
// Props
defineProps({
  deliveries: Object,
  filteredDeliveries: Array,
  loading: Boolean,
  selectedDeliveries: Array,
  selectedSet: Object,
  driverTruckSets: Array,
  batchSuggestions: Array,
  dispatchingSetId: Number,
  totalSelectedVolume: Number,
  totalSelectedWeight: Number,
  hasAnyUnstickerizedPackages: Boolean,
  hasMixedAssignmentTypes: Boolean,
  hasBackhaulAssignmentRulesViolation: Boolean,
})

// Emits
defineEmits([
  'selection-change',
  'view-details', 
  'cancel-delivery',
  'page-changed',
  'remove-delivery',
  'clear-selection',
  'open-assignment-modal',
  'select-set',
  'open-dispatch-modal',
  'enable-backhaul',
  'prepare-batch-assignment'
])

// Your existing helper functions
const deliveryColumns = [
  { field: 'id', header: 'DO #', sortable: true, formatter: (value) => value ? `DO-${value.toString().padStart(6, '0')}` : 'N/A' },
  { field: 'delivery_request.pick_up_region.name', header: 'Pickup', sortable: true },
  { field: 'delivery_request.drop_off_region.name', header: 'Dropoff', sortable: true },
  { field: 'status', header: 'Status', sortable: true },
  { field: 'packages_count', header: 'Packages', sortable: false },
  { field: 'assignment_type', header: 'Type', sortable: true },
  { field: 'estimated_departure', header: 'Est. Departure', sortable: true, formatter: (value) => value ? formatDate(value) : 'N/A' },
  { field: 'actions', header: 'Actions', sortable: false }
]

const formatDate = (dateString) => {
  if (!dateString) return 'N/A'
  const date = new Date(dateString)
  return date.toLocaleDateString() + ' ' + date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
}

const statusBadgeClass = (status) => {
  const classes = {
    pending: 'bg-yellow-100 text-yellow-800',
    assigned: 'bg-blue-100 text-blue-800',
    picked_up: 'bg-indigo-100 text-indigo-800',
    in_transit: 'bg-purple-100 text-purple-800',
    delivered: 'bg-green-100 text-green-800',
    completed: 'bg-gray-100 text-gray-800',
    cancelled: 'bg-red-100 text-red-800'
  }
  return classes[status] || 'bg-gray-100 text-gray-800'
}

const formatStatusText = (status) => {
  const statusMap = {
    pending: 'Pending',
    assigned: 'Assigned',
    picked_up: 'Picked Up',
    in_transit: 'In Transit',
    delivered: 'Delivered',
    completed: 'Completed',
    cancelled: 'Cancelled'
  }
  return statusMap[status] || status
}
</script>