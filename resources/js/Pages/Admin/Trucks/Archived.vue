<template>
  <EmployeeLayout>
    <template #header>
      <div class="flex justify-between items-center w-full px-6 md:px-8">
        <!-- Left: Title & Subtitle -->
        <div>
          <h2 class="text-xl font-semibold leading-tight text-gray-800">Archived Trucks</h2>
          <p class="mt-1 text-sm text-gray-500">
            Manage disabled trucks and restore or permanently delete them
          </p>
        </div>

        <!-- Right: Buttons -->
        <div class="flex gap-2">
          <SecondaryButton @click="viewActive">View Active Trucks</SecondaryButton>
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

        <!-- Search and Results Counter -->
        <div class="mb-4 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
          <div class="w-full sm:w-96">
            <SearchInput 
              v-model="search" 
              placeholder="Search archived trucks by license plate, make, or model..." 
              class="w-full"
            />
          </div>
          <div class="text-sm text-gray-500 bg-blue-50 px-3 py-1 rounded border border-blue-100 whitespace-nowrap">
            📋 Showing {{ trucks.data?.length || 0 }} archived {{ trucks.data?.length === 1 ? 'truck' : 'trucks' }}
            <span v-if="trucks.data && trucks.data.length < trucks.total" class="ml-1">
              (Page {{ trucks.current_page }} of {{ trucks.last_page }})
            </span>
          </div>
        </div>

        <!-- Data Table Container -->
        <div class="justify-center flex items-center">
          <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg w-full max-w-[95vw]">
            <div class="p-4 bg-white border-b border-gray-200">
              <DataTable
                :columns="columns"
                :data="trucks.data"
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
                    :class="(row.status && !['available', 'in_use', 'under_repair'].includes(row.status)) ? 'bg-yellow-100 text-yellow-800' : statusClasses[row.status]"
                  >
                    {{ getStatusText(row.status) }}
                  </span>
                </template>
                <template #archived_at="{ row }">
                  <span class="text-gray-600">{{ row.archived_at || 'N/A' }}</span>
                </template>
                <template #actions="{ row }">
                  <div class="flex space-x-2">
                    <SecondaryButton @click="viewTruck(row.id)" class="text-xs py-1 px-2">View</SecondaryButton>
                    <PrimaryButton @click="openRestoreModal(row)" class="text-xs py-1 px-2">Restore</PrimaryButton>
                    <DangerButton @click="openDeleteModal(row)" class="text-xs py-1 px-2">Delete</DangerButton>
                  </div>
                </template>
                
                <!-- Empty State Slot -->
                <template #empty>
                  <div class="text-center py-8">
                    <div class="bg-gray-50 rounded-lg p-6 max-w-md mx-auto">
                      <TruckIcon class="h-10 w-10 text-gray-400 mx-auto mb-3" />
                      <h3 class="text-lg font-medium text-gray-900 mb-2">No archived trucks found</h3>
                      <p class="text-gray-500 mb-3">
                        {{ search ? 'Try adjusting your search terms' : 'All trucks are currently active' }}
                      </p>
                      <SecondaryButton @click="viewActive">View Active Trucks</SecondaryButton>
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

    <!-- Restore Confirmation Modal -->
    <Modal :show="showRestoreModal" @close="closeRestoreModal">
      <div class="p-5">
        <h2 class="text-lg font-medium text-gray-900">Restore Truck?</h2>
        <p class="mt-1 text-sm text-gray-600">
          Are you sure you want to restore <strong>{{ truckToRestore?.license_plate }}</strong>?
          This will move it back to active trucks.
        </p>
        <div class="mt-4 flex justify-end space-x-3">
          <SecondaryButton @click="closeRestoreModal">Cancel</SecondaryButton>
          <PrimaryButton @click="handleRestore" :disabled="isProcessing">
            <span v-if="isProcessing">Processing...</span>
            <span v-else>Restore Truck</span>
          </PrimaryButton>
        </div>
      </div>
    </Modal>

    <!-- Delete Confirmation Modal -->
    <Modal :show="showDeleteModal" @close="closeDeleteModal">
      <div class="p-5">
        <h2 class="text-lg font-medium text-gray-900">Permanently Delete Truck?</h2>
        <p class="mt-1 text-sm text-gray-600">
          Are you sure you want to permanently delete <strong>{{ truckToDelete?.license_plate }}</strong>?
          This action cannot be undone.
        </p>
        <div class="mt-4 flex justify-end space-x-3">
          <SecondaryButton @click="closeDeleteModal">Cancel</SecondaryButton>
          <DangerButton @click="handleDelete" :disabled="isProcessing">
            <span v-if="isProcessing">Processing...</span>
            <span v-else>Delete Permanently</span>
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
import SearchInput from '@/Components/SearchInput.vue';
import DataTable from '@/Components/DataTable.vue';
import Modal from '@/Components/Modal.vue';
import Pagination from '@/Components/Pagination.vue';
import { TruckIcon } from '@heroicons/vue/24/outline';
import { ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';

const props = defineProps({
  trucks: Object,
  status: String,
  success: String,
  error: String,
});

// State
const showRestoreModal = ref(false);
const showDeleteModal = ref(false);
const truckToRestore = ref(null);
const truckToDelete = ref(null);
const isProcessing = ref(false);
const search = ref(props.trucks.filters?.search || '');
const sortField = ref(props.trucks.filters?.sort_field || 'archived_at');
const sortDirection = ref(props.trucks.filters?.sort_direction || 'desc');

// Constants
const statusClasses = {
  available: 'bg-green-100 text-green-800',
  in_use: 'bg-yellow-100 text-yellow-800',
  under_repair: 'bg-red-100 text-red-800',
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
  { field: 'region.name', header: 'Region', sortable: true },
  { field: 'status', header: 'Status', sortable: true },
  { field: 'archived_at', header: 'Archived Date', sortable: true },
  { field: 'actions', header: 'Actions', sortable: false }
];

// Methods
const getStatusText = (statusValue) => {
  // Always show "In Use" for any status that's not the standard ones
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

const handleSort = (sortParams) => {
  sortField.value = sortParams.field;
  sortDirection.value = sortParams.direction;
};

const handlePageChange = (page) => {
  router.get(route('admin.trucks.archived'), { 
    page: page,
    search: search.value,
    sort_field: sortField.value,
    sort_direction: sortDirection.value
  }, {
    preserveState: true,
    preserveScroll: true,
    replace: true
  });
};

const viewTruck = (id) => router.get(route('admin.trucks.show', id));

// Restore Modal Functions
const openRestoreModal = (truck) => {
  truckToRestore.value = truck;
  showRestoreModal.value = true;
};

const closeRestoreModal = () => {
  showRestoreModal.value = false;
  setTimeout(() => {
    truckToRestore.value = null;
    isProcessing.value = false;
  }, 300);
};

const handleRestore = async () => {
  if (!truckToRestore.value) return;
  isProcessing.value = true;
  try {
    await router.put(route('admin.trucks.restore', truckToRestore.value.id), {}, {
      preserveScroll: true,
    });
    closeRestoreModal();
    router.reload();
  } catch (error) {
    closeRestoreModal();
    alert('Failed to restore truck. Please try again.');
  }
};

// Delete Modal Functions
const openDeleteModal = (truck) => {
  truckToDelete.value = truck;
  showDeleteModal.value = true;
};

const closeDeleteModal = () => {
  showDeleteModal.value = false;
  setTimeout(() => {
    truckToDelete.value = null;
    isProcessing.value = false;
  }, 300);
};

const handleDelete = async () => {
  if (!truckToDelete.value) return;
  isProcessing.value = true;
  try {
    await router.delete(route('admin.trucks.destroy', truckToDelete.value.id), {
      preserveScroll: true,
    });
    closeDeleteModal();
    router.reload();
  } catch (error) {
    closeDeleteModal();
    alert('Failed to delete truck. Please try again.');
  }
};

const viewActive = () => router.get(route('admin.trucks.index'));

// Watchers
watch([search, sortField, sortDirection], () => {
  router.get(route('admin.trucks.archived'), {
    search: search.value,
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
</style>