<template>
  <EmployeeLayout>
    <template #header>
      <div class="flex justify-between items-center w-full px-6 md:px-8">
        <!-- Left: Title & Subtitle -->
        <div>
          <h2 class="text-xl font-semibold leading-tight text-gray-800">Truck Management</h2>
          <p class="mt-1 text-sm text-gray-500">
            Manage active trucks and their assignments
          </p>
        </div>

        <!-- Right: Buttons -->
        <div class="flex gap-2">
          <SecondaryButton @click="viewArchived">View Disabled Trucks</SecondaryButton>
          <PrimaryButton @click="createTruck">Add New Truck</PrimaryButton>
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

        <!-- Search and Filters -->
        <div class="mb-4 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
          <div class="w-full sm:w-96">
            <SearchInput 
              v-model="search" 
              placeholder="Search trucks by license plate, make, or model..." 
              class="w-full"
            />
          </div>
          <div class="flex items-center gap-3">
            <SelectInput 
              v-model="statusFilter" 
              :options="statusOptions" 
              option-value="value"
              option-label="text"
              placeholder="All Statuses"
              class="w-full sm:w-48"
            />
            <div class="text-sm text-gray-500 bg-blue-50 px-3 py-1 rounded border border-blue-100 whitespace-nowrap">
              📋 Showing {{ filteredTrucks.length }} active {{ filteredTrucks.length === 1 ? 'truck' : 'trucks' }}
              <span v-if="trucks.data && trucks.data.length < trucks.total" class="ml-1">
                (Page {{ trucks.current_page }} of {{ trucks.last_page }})
              </span>
            </div>
          </div>
        </div>

        <!-- Data Table Container -->
        <div class="justify-center flex items-center">
          <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg w-full max-w-[95vw]">
            <div class="p-4 bg-white border-b border-gray-200">
              <DataTable
                :columns="columns"
                :data="filteredTrucks"
                :sort-field="sortField"
                :sort-direction="sortDirection"
                @sort="handleSort"
                class="w-full"
              >
                <template #license_plate="{ row }">
                  <span class="font-medium text-gray-900">{{ row.license_plate || 'N/A' }}</span>
                </template>
                <template #make="{ row }">
                  <span class="text-gray-700">{{ row.make || 'N/A' }}</span>
                </template>
                <template #model="{ row }">
                  <span class="text-gray-700">{{ row.model || 'N/A' }}</span>
                </template>
                <template #volume_capacity="{ row }">
                  {{ row.volume_capacity ? `${row.volume_capacity} m³` : 'N/A' }}
                </template>
                <template #weight_capacity="{ row }">
                  {{ row.weight_capacity ? `${row.weight_capacity} kg` : 'N/A' }}
                </template>
                <template #region.name="{ row }">
                  <div class="flex items-center">
                    <div 
                      v-if="row.region && row.region.color_hex"
                      class="w-3 h-3 rounded-full mr-2 border border-gray-300" 
                      :style="{ backgroundColor: row.region.color_hex }"
                    ></div>
                    <span class="text-gray-700">{{ row.region && row.region.name ? row.region.name : 'N/A' }}</span>
                  </div>
                </template>
                <template #status="{ row }">
                  <span 
                    class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full capitalize"
                    :class="(row.status && !['available', 'unavailable', 'maintenance'].includes(row.status)) ? 'bg-yellow-100 text-yellow-800' : statusClasses[row.status]"
                  >
                    {{ getStatusText(row.status) }}
                  </span>
                </template>
                <template #last_maintenance_date="{ row }">
                  <span class="text-gray-600">{{ row.last_maintenance_date || 'Never' }}</span>
                </template>
                <template #actions="{ row }">
                  <div class="flex space-x-2">
                    <SecondaryButton @click="viewTruck(row.id)" class="text-xs py-1 px-2">View</SecondaryButton>
                    <PrimaryButton @click="editTruck(row.id)" class="text-xs py-1 px-2">Edit</PrimaryButton>
                    <DangerButton 
                      @click="confirmArchiveTruck(row)" 
                      class="text-xs py-1 px-2"
                      :disabled="isTruckInUse(row)"
                    >
                      Disable
                    </DangerButton>
                  </div>
                </template>
                
                <!-- Empty State Slot -->
                <template #empty>
                  <div class="text-center py-8">
                    <div class="bg-gray-50 rounded-lg p-6 max-w-md mx-auto">
                      <TruckIcon class="h-10 w-10 text-gray-400 mx-auto mb-3" />
                      <h3 class="text-lg font-medium text-gray-900 mb-2">No active trucks found</h3>
                      <p class="text-gray-500 mb-3">
                        {{ search ? 'Try adjusting your search terms' : 'Get started by adding your first truck' }}
                      </p>
                      <PrimaryButton @click="createTruck">Add New Truck</PrimaryButton>
                    </div>
                  </div>
                </template>
              </DataTable>

              <!-- Pagination Component -->
              <div v-if="trucks.links?.length > 3" class="mt-4">
                <Pagination 
                  :pagination="trucks" 
                  @page-changed="handlePageChange" 
                />
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Archive Confirmation Modal -->
    <Modal :show="showArchiveModal" @close="closeArchiveModal">
      <div class="p-5">
        <h2 class="text-lg font-medium text-gray-900">
          {{ isTruckInUse(truckToArchive) ? 'Cannot Disable Truck' : 'Disable Truck?' }}
        </h2>
        
        <div v-if="isTruckInUse(truckToArchive)" class="mt-1">
          <div class="p-3 bg-red-50 border border-red-200 rounded-md">
            <div class="flex items-center">
              <ExclamationTriangleIcon class="h-5 w-5 text-red-400 mr-2" />
              <p class="text-sm font-medium text-red-800">Truck is currently in use</p>
            </div>
            <p class="mt-2 text-sm text-red-600">
              <strong>{{ truckToArchive?.license_plate }}</strong> cannot be disabled because it is currently 
              <span class="font-semibold">{{ getStatusText(truckToArchive?.status) }}</span>.
              Please wait until the truck becomes available before disabling it.
            </p>
          </div>
        </div>
        
        <p v-else class="mt-1 text-sm text-gray-600">
          Are you sure you want to disable <strong>{{ truckToArchive?.license_plate }}</strong>?
          This will move it to the disabled trucks list.
        </p>
        
        <div class="mt-4 flex justify-end space-x-3">
          <SecondaryButton @click="closeArchiveModal">Cancel</SecondaryButton>
          <DangerButton 
            @click="archiveRegion" 
            :disabled="isProcessing || isTruckInUse(truckToArchive)"
          >
            <span v-if="isProcessing">Processing...</span>
            <span v-else>Disable Truck</span>
          </DangerButton>
        </div>
      </div>
    </Modal>
  </EmployeeLayout>
</template>

<script setup>
import { ref, onMounted, watch, computed } from 'vue';
import { router, Link } from '@inertiajs/vue3';
import EmployeeLayout from '@/Layouts/EmployeeLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import SearchInput from '@/Components/SearchInput.vue';
import SelectInput from '@/Components/SelectInput.vue';
import DataTable from '@/Components/DataTable.vue';
import Modal from '@/Components/Modal.vue';
import Pagination from '@/Components/Pagination.vue';
import { TruckIcon, ExclamationTriangleIcon } from '@heroicons/vue/24/outline';

const props = defineProps({ 
  trucks: Object, 
  status: String, 
  success: String, 
  error: String 
});

// State
const showArchiveModal = ref(false);
const truckToArchive = ref(null);
const isProcessing = ref(false);
const search = ref(props.trucks.filters?.search || '');
const statusFilter = ref(props.trucks.filters?.status || '');
const sortField = ref(props.trucks.filters?.sort_field || 'license_plate');
const sortDirection = ref(props.trucks.filters?.sort_direction || 'asc');

// Constants
const statusOptions = [
  { value: '', text: 'All Statuses' },
  { value: 'available', text: 'Available' },
  { value: 'unavailable', text: 'Unavailable' },
  { value: 'maintenance', text: 'Maintenance' }
];

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

const columns = [
  { field: 'license_plate', header: 'License Plate', sortable: true },
  { field: 'make', header: 'Make', sortable: true },
  { field: 'model', header: 'Model', sortable: true },
  { field: 'volume_capacity', header: 'Volume Capacity', sortable: true },
  { field: 'weight_capacity', header: 'Weight Capacity', sortable: true },
  { field: 'region.name', header: 'Region', sortable: true },
  { field: 'status', header: 'Status', sortable: true },
  { field: 'last_maintenance_date', header: 'Last Maintenance', sortable: true },
  { field: 'actions', header: 'Actions', sortable: false }
];

// Computed
const filteredTrucks = computed(() => {
  const trucksData = props.trucks.data || [];
  
  return trucksData.filter(truck => {
    const matchesSearch = search.value === '' || 
      truck.license_plate?.toLowerCase().includes(search.value.toLowerCase()) ||
      truck.make?.toLowerCase().includes(search.value.toLowerCase()) ||
      truck.model?.toLowerCase().includes(search.value.toLowerCase());
    
    const matchesStatus = !statusFilter.value || truck.status === statusFilter.value;
    
    return matchesSearch && matchesStatus;
  }).sort((a, b) => {
    const modifier = sortDirection.value === 'asc' ? 1 : -1;
    
    let aValue = a[sortField.value];
    let bValue = b[sortField.value];
    
    // Handle null/undefined values
    if (aValue == null) aValue = '';
    if (bValue == null) bValue = '';
    
    // Convert to string for case-insensitive comparison
    aValue = String(aValue).toLowerCase();
    bValue = String(bValue).toLowerCase();

    if (aValue < bValue) return -1 * modifier;
    if (aValue > bValue) return 1 * modifier;
    return 0;
  });
});

// Methods
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
  
  return statusMap[statusValue] || statusValue?.replace(/_/g, ' ') || 'N/A';
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

const handleSort = (sortParams) => {
  sortField.value = sortParams.field;
  sortDirection.value = sortParams.direction;
};

const handlePageChange = (page) => {
  router.get(route('admin.trucks.index'), { 
    page: page,
    search: search.value,
    status: statusFilter.value,
    sort_field: sortField.value,
    sort_direction: sortDirection.value
  }, {
    preserveState: true,
    preserveScroll: true,
    replace: true
  });
};

const confirmArchiveTruck = (truck) => {
  truckToArchive.value = truck;
  showArchiveModal.value = true;
};

const closeArchiveModal = () => {
  showArchiveModal.value = false;
  truckToArchive.value = null;
  isProcessing.value = false;
};

const archiveRegion = () => {
  if (!truckToArchive.value || isTruckInUse(truckToArchive.value)) return;
  
  isProcessing.value = true;
  router.put(route('admin.trucks.archive', truckToArchive.value.id), {}, {
    preserveScroll: true,
    onSuccess: () => router.reload({ only: ['trucks'] }),
    onError: () => alert('Failed to disable truck'),
    onFinish: () => closeArchiveModal()
  });
};

// Navigation
const createTruck = () => router.get(route('admin.trucks.create'));
const viewTruck = (id) => router.get(route('admin.trucks.show', id));
const editTruck = (id) => router.get(route('admin.trucks.edit', id));
const viewArchived = () => router.get(route('admin.trucks.archived'));

// Watchers
watch([search, statusFilter, sortField, sortDirection], () => {
  router.get(route('admin.trucks.index'), {
    search: search.value,
    status: statusFilter.value,
    sort_field: sortField.value,
    sort_direction: sortDirection.value,
    page: 1
  }, { 
    preserveState: true, 
    replace: true 
  });
}, { deep: true });
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

/* Style for disabled disable button */
:deep(.datatable-table .btn:disabled) {
  opacity: 0.5;
  cursor: not-allowed;
}
</style>