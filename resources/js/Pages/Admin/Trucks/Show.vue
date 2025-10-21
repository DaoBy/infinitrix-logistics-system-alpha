<template>
  <EmployeeLayout>
    <template #header>
      <div class="flex justify-between items-center w-full px-6 md:px-8">
        <!-- Left: Title & Subtitle -->
        <div>
          <h2 class="text-xl font-semibold leading-tight text-gray-800">
            Truck Details: {{ truck.license_plate }}
          </h2>
          <p class="mt-1 text-sm text-gray-500">
            View and manage truck information, maintenance, and components
          </p>
        </div>

        <!-- Right: Buttons -->
        <div class="flex gap-2">
          <SecondaryButton @click="$inertia.visit(route('admin.trucks.index'))">
            Back to List
          </SecondaryButton>
          <PrimaryButton @click="editTruck">
            Edit Truck
          </PrimaryButton>
          <DangerButton 
            v-if="truck.is_active && !isTruckInUse(truck)"
            @click="archiveTruck"
          >
            Disable
          </DangerButton>
          <PrimaryButton 
            v-else-if="!truck.is_active"
            @click="restoreTruck"
          >
            Restore
          </PrimaryButton>
        </div>
      </div>
    </template>

    <!-- ZOOM CONTENT WRAPPER -->
    <div class="zoom-content">
      <!-- MAIN CONTENT CONTAINER WITH PROPER PADDING -->
      <div class="px-6 py-4">
        <div v-if="status || success || error" class="mb-4">
          <div v-if="status" class="p-3 bg-blue-100 text-blue-800 rounded">{{ status }}</div>
          <div v-if="success" class="p-3 bg-green-100 text-green-800 rounded">{{ success }}</div>
          <div v-if="error" class="p-3 bg-red-100 text-red-800 rounded">{{ error }}</div>
        </div>

        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
          <div class="p-6 bg-white border-b border-gray-200">
            <!-- Main Information Cards -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
              <!-- Basic Information Card -->
              <div class="bg-white border border-gray-200 rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Basic Information</h3>
                <div class="space-y-4">
                  <div class="flex justify-between items-center py-2 border-b border-gray-100">
                    <span class="text-sm font-medium text-gray-500">License Plate</span>
                    <span class="text-sm text-gray-900 font-semibold">{{ truck.license_plate }}</span>
                  </div>
                  <div class="flex justify-between items-center py-2 border-b border-gray-100">
                    <span class="text-sm font-medium text-gray-500">Make & Model</span>
                    <span class="text-sm text-gray-900">{{ truck.make }} {{ truck.model }}</span>
                  </div>
                  <div class="flex justify-between items-center py-2 border-b border-gray-100">
                    <span class="text-sm font-medium text-gray-500">Status</span>
                    <span 
                      :class="(truck.status && !['available', 'unavailable', 'maintenance'].includes(truck.status)) ? 'bg-yellow-100 text-yellow-800' : statusClasses[truck.status]"
                      class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full capitalize"
                    >
                      {{ getStatusText(truck.status) }}
                    </span>
                  </div>
                  <div v-if="truck.region" class="flex justify-between items-center py-2 border-b border-gray-100">
                    <span class="text-sm font-medium text-gray-500">Region</span>
                    <div class="flex items-center">
                      <div 
                        v-if="truck.region.color_hex"
                        class="w-3 h-3 rounded-full mr-2 border border-gray-300" 
                        :style="{ backgroundColor: truck.region.color_hex }"
                      ></div>
                      <span class="text-sm text-gray-900">{{ truck.region.name }}</span>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Capacity Information Card -->
              <div class="bg-white border border-gray-200 rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Capacity Information</h3>
                <div class="space-y-4">
                  <div class="flex justify-between items-center py-2 border-b border-gray-100">
                    <span class="text-sm font-medium text-gray-500">Volume Capacity</span>
                    <span class="text-sm text-gray-900">{{ truck.volume_capacity ? `${truck.volume_capacity} m³` : 'N/A' }}</span>
                  </div>
                  <div class="flex justify-between items-center py-2 border-b border-gray-100">
                    <span class="text-sm font-medium text-gray-500">Weight Capacity</span>
                    <span class="text-sm text-gray-900">{{ truck.weight_capacity ? `${truck.weight_capacity} kg` : 'N/A' }}</span>
                  </div>
                  <div class="flex justify-between items-center py-2 border-b border-gray-100">
                    <span class="text-sm font-medium text-gray-500">Current Volume</span>
                    <span class="text-sm text-gray-900">{{ truck.current_volume ? `${truck.current_volume} m³` : '0 m³' }}</span>
                  </div>
                  <div class="flex justify-between items-center py-2 border-b border-gray-100">
                    <span class="text-sm font-medium text-gray-500">Current Weight</span>
                    <span class="text-sm text-gray-900">{{ truck.current_weight ? `${truck.current_weight} kg` : '0 kg' }}</span>
                  </div>
                  <div class="flex justify-between items-center py-2">
                    <span class="text-sm font-medium text-gray-500">Available Capacity</span>
                    <span class="text-sm font-semibold text-green-600">
                      {{ availableVolumeCapacity }} m³ / {{ availableWeightCapacity }} kg
                    </span>
                  </div>
                </div>
              </div>
            </div>

            <!-- Additional Information Cards -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
              <!-- Vehicle Details Card -->
              <div class="bg-white border border-gray-200 rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Vehicle Details</h3>
                <div class="space-y-4">
                  <div v-if="truck.year" class="flex justify-between items-center py-2 border-b border-gray-100">
                    <span class="text-sm font-medium text-gray-500">Year</span>
                    <span class="text-sm text-gray-900">{{ truck.year }}</span>
                  </div>
                  <div v-if="truck.vin" class="flex justify-between items-center py-2 border-b border-gray-100">
                    <span class="text-sm font-medium text-gray-500">VIN</span>
                    <span class="text-sm text-gray-900 font-mono">{{ truck.vin }}</span>
                  </div>
                  <div v-if="truck.purchase_date" class="flex justify-between items-center py-2 border-b border-gray-100">
                    <span class="text-sm font-medium text-gray-500">Purchase Date</span>
                    <span class="text-sm text-gray-900">{{ formatDate(truck.purchase_date) }}</span>
                  </div>
                  <div v-if="truck.purchase_price" class="flex justify-between items-center py-2 border-b border-gray-100">
                    <span class="text-sm font-medium text-gray-500">Purchase Price</span>
                    <span class="text-sm text-gray-900">₱{{ Number(truck.purchase_price).toLocaleString() }}</span>
                  </div>
                  <div v-if="truck.current_value" class="flex justify-between items-center py-2">
                    <span class="text-sm font-medium text-gray-500">Current Value</span>
                    <span class="text-sm text-gray-900">₱{{ Number(truck.current_value).toLocaleString() }}</span>
                  </div>
                </div>
              </div>

              <!-- Notes Card -->
              <div v-if="truck.notes" class="bg-white border border-gray-200 rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Notes</h3>
                <p class="text-sm text-gray-700 whitespace-pre-line">{{ truck.notes }}</p>
              </div>
            </div>

            <!-- Components -->
            <div class="bg-white border border-gray-200 rounded-lg p-6 mb-6">
              <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-medium text-gray-900">Components</h3>
                <PrimaryButton @click="router.get(route('admin.trucks.components.index', truck.id))" class="text-xs py-1 px-2">
                  Manage Components
                </PrimaryButton>
              </div>
              
              <DataTable
                v-if="truck.components && truck.components.length"
                :columns="componentColumns"
                :data="truck.components"
                class="w-full"
              >
                <template #name="{ row }">
                  <span class="font-medium text-gray-900">{{ capitalizeWords(row.name) }}</span>
                </template>
                <template #type="{ row }">
                  <span class="text-gray-700 capitalize">{{ capitalizeWords(row.type.replace(/_/g, ' ')) }}</span>
                </template>
                <template #condition="{ row }">
                  <span :class="`px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${getConditionColor(row.condition)}`">
                    {{ capitalizeWords(conditionLabels[row.condition]) }}
                  </span>
                </template>
                <template #installation_date="{ row }">
                  <span class="text-gray-600">{{ formatDate(row.installation_date) }}</span>
                </template>
                <template #actions="{ row }">
                  <SecondaryButton @click="viewComponent(row)" class="text-xs py-1 px-2">View</SecondaryButton>
                </template>
                
                <template #empty>
                  <div class="text-center py-4 text-gray-500">
                    No components found.
                  </div>
                </template>
              </DataTable>
              <div v-else class="text-center py-4 text-gray-500">
                No components found.
              </div>
            </div>

            <!-- Maintenance History -->
            <div class="bg-white border border-gray-200 rounded-lg p-6">
              <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-medium text-gray-900">Maintenance History</h3>
                <PrimaryButton @click="router.get(route('admin.trucks.maintenance.create', truck.id))" class="text-xs py-1 px-2">
                  Add Maintenance
                </PrimaryButton>
              </div>
              
              <DataTable
                v-if="truck.maintenance && truck.maintenance.length"
                :columns="maintenanceColumns"
                :data="truck.maintenance"
                class="w-full"
              >
                <template #maintenance_date="{ row }">
                  {{ formatDate(row.maintenance_date) }}
                </template>
                <template #type="{ row }">
                  <span class="capitalize">{{ capitalizeWords(row.type.replace(/_/g, ' ')) }}</span>
                </template>
                <template #service_provider="{ row }">
                  <span class="text-gray-700">{{ capitalizeWords(row.service_provider) }}</span>
                </template>
                <template #service_details="{ row }">
                  <span class="line-clamp-2">{{ capitalizeWords(row.service_details) }}</span>
                </template>
                <template #cost="{ row }">
                  ₱{{ Number(row.cost).toFixed(2) }}
                </template>
                <template #actions="{ row }">
                  <div class="flex space-x-2">
                    <SecondaryButton @click="editMaintenance(row)" class="text-xs py-1 px-2">Edit</SecondaryButton>
                    <DangerButton @click="confirmDeleteMaintenance(row)" class="text-xs py-1 px-2">Delete</DangerButton>
                  </div>
                </template>
                
                <template #empty>
                  <div class="text-center py-4 text-gray-500">
                    No maintenance records found.
                  </div>
                </template>
              </DataTable>
              <div v-else class="text-center py-4 text-gray-500">
                No maintenance records found.
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Delete Maintenance Confirmation Modal -->
    <Modal :show="showDeleteMaintenanceModal" @close="closeDeleteMaintenanceModal">
      <div class="p-5">
        <h2 class="text-lg font-medium text-gray-900">Delete Maintenance Record?</h2>
        <p class="mt-1 text-sm text-gray-600">
          Are you sure you want to delete this maintenance record from 
          {{ maintenanceToDelete ? formatDate(maintenanceToDelete.maintenance_date) : '' }}?
        </p>
        <div class="mt-4 flex justify-end space-x-3">
          <SecondaryButton @click="closeDeleteMaintenanceModal">Cancel</SecondaryButton>
          <DangerButton @click="deleteMaintenance" :disabled="isDeletingMaintenance">
            <span v-if="isDeletingMaintenance">Processing...</span>
            <span v-else>Delete</span>
          </DangerButton>
        </div>
      </div>
    </Modal>
  </EmployeeLayout>
</template>

<script setup>
import EmployeeLayout from '@/Layouts/EmployeeLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import DataTable from '@/Components/DataTable.vue';
import Modal from '@/Components/Modal.vue';
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';

const props = defineProps({
  truck: {
    type: Object,
    required: true,
    default: () => ({
      maintenance: [],
      components: [],
      region: null
    })
  },
  status: String,
  success: String,
  error: String,
});

// State
const showDeleteMaintenanceModal = ref(false);
const maintenanceToDelete = ref(null);
const isDeletingMaintenance = ref(false);

// Constants
const statusClasses = {
  available: 'bg-green-100 text-green-800',
  unavailable: 'bg-gray-100 text-gray-800',
  maintenance: 'bg-red-100 text-red-800',
  assigned: 'bg-blue-100 text-blue-800',
  in_transit: 'bg-purple-100 text-purple-800',
  returning: 'bg-orange-100 text-orange-800',
  nearly_full: 'bg-yellow-100 text-yellow-800',
  available_for_backhaul: 'bg-green-100 text-green-800',
  cooldown: 'bg-blue-100 text-blue-800'
};

const conditionLabels = {
  new: 'New',
  good: 'Good',
  fair: 'Fair',
  poor: 'Poor',
  needs_replacement: 'Needs Replacement'
};

const maintenanceColumns = [
  { field: 'maintenance_date', header: 'Date', sortable: true },
  { field: 'type', header: 'Type', sortable: true },
  { field: 'service_provider', header: 'Provider', sortable: true },
  { field: 'service_details', header: 'Details', sortable: false },
  { field: 'cost', header: 'Cost', sortable: true },
  { field: 'actions', header: 'Actions', sortable: false }
];

const componentColumns = [
  { field: 'name', header: 'Name', sortable: true },
  { field: 'type', header: 'Type', sortable: true },
  { field: 'condition', header: 'Condition', sortable: true },
  { field: 'installation_date', header: 'Installed', sortable: true },
  { field: 'actions', header: 'Actions', sortable: false }
];

// Computed
const availableVolumeCapacity = computed(() => {
  return (props.truck.volume_capacity || 0) - (props.truck.current_volume || 0);
});

const availableWeightCapacity = computed(() => {
  return (props.truck.weight_capacity || 0) - (props.truck.current_weight || 0);
});

// Methods
// Helper function to capitalize words
const capitalizeWords = (str) => {
  if (!str) return '';
  return str.replace(/\b\w/g, char => char.toUpperCase());
};

const getStatusText = (statusValue) => {
  // Always show "In Use" for any status that's not the standard available/unavailable/maintenance
  if (statusValue && !['available', 'unavailable', 'maintenance'].includes(statusValue)) {
    return 'In Use';
  }
  
  // Map status values to display text
  const statusMap = {
    'available': 'Available',
    'unavailable': 'Unavailable',
    'maintenance': 'Maintenance',
    'assigned': 'Assigned',
    'in_transit': 'In Transit',
    'returning': 'Returning',
    'nearly_full': 'Nearly Full',
    'available_for_backhaul': 'Available for Backhaul',
    'cooldown': 'Cooldown'
  };
  
  return statusMap[statusValue] || capitalizeWords(statusValue?.replace(/_/g, ' ')) || 'N/A';
};

const isTruckInUse = (truck) => {
  if (!truck) return false;
  
  // Trucks that are considered "in use" - cannot be disabled
  const inUseStatuses = [
    'assigned',
    'in_transit', 
    'returning',
    'nearly_full',
    'available_for_backhaul',
    'cooldown'
  ];
  
  return inUseStatuses.includes(truck.status);
};

const formatDate = (dateString) => {
  if (!dateString) return 'N/A';
  try {
    return new Date(dateString).toLocaleDateString();
  } catch (e) {
    return 'Invalid Date';
  }
};

const getConditionColor = (condition) => {
  const colors = {
    new: 'bg-green-100 text-green-800',
    good: 'bg-blue-100 text-blue-800',
    fair: 'bg-yellow-100 text-yellow-800',
    poor: 'bg-orange-100 text-orange-800',
    needs_replacement: 'bg-red-100 text-red-800',
  };
  return colors[condition] || 'bg-gray-100 text-gray-800';
};

const editTruck = () => {
  router.get(route('admin.trucks.edit', props.truck.id));
};

const archiveTruck = () => {
  if (confirm('Are you sure you want to disable this truck?')) {
    router.put(route('admin.trucks.archive', props.truck.id), {
      preserveScroll: true,
      onSuccess: () => router.reload()
    });
  }
};

const restoreTruck = () => {
  if (confirm('Are you sure you want to restore this truck?')) {
    router.put(route('admin.trucks.restore', props.truck.id), {
      preserveScroll: true,
      onSuccess: () => router.reload()
    });
  }
};

const editMaintenance = (maintenance) => {
  router.get(route('admin.trucks.maintenance.edit', {
    truck: props.truck.id,
    maintenance: maintenance.id
  }));
};

const viewComponent = (component) => {
  router.get(route('admin.trucks.components.show', { 
    truck: props.truck.id, 
    component: component.id 
  }));
};

const confirmDeleteMaintenance = (maintenance) => {
  maintenanceToDelete.value = maintenance;
  showDeleteMaintenanceModal.value = true;
};

const closeDeleteMaintenanceModal = () => {
  showDeleteMaintenanceModal.value = false;
  maintenanceToDelete.value = null;
  isDeletingMaintenance.value = false;
};

const deleteMaintenance = () => {
  if (!maintenanceToDelete.value) return;
  
  isDeletingMaintenance.value = true;
  
  router.delete(route('admin.trucks.maintenance.destroy', { 
    truck: props.truck.id,
    maintenance: maintenanceToDelete.value.id 
  }), {
    preserveScroll: true,
    onSuccess: () => {
      closeDeleteMaintenanceModal();
      router.reload();
    },
    onFinish: () => {
      isDeletingMaintenance.value = false;
    }
  });
};
</script>

<style scoped>
.zoom-content {
  zoom: 0.80;
}

.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

/* DataTable styling adjustments */
:deep(.datatable-table) {
  width: 100%;
}

/* Reduce table row padding for more compact rows */
:deep(.datatable-table td) {
  padding-top: 0.375rem !important;
  padding-bottom: 0.375rem !important;
}

/* Reduce table header padding */
:deep(.datatable-table th) {
  padding-top: 0.5rem !important;
  padding-bottom: 0.5rem !important;
  font-size: 0.875rem !important;
}

/* Reduce button sizes in the table */
:deep(.datatable-table .btn) {
  padding: 0.25rem 0.5rem !important;
  font-size: 0.75rem !important;
}
</style>