<template>
  <EmployeeLayout>
    <template #header>
      <div class="flex justify-between items-center w-full px-6 md:px-8">
        <!-- Left: Title & Subtitle -->
        <div>
          <h2 class="text-xl font-semibold leading-tight text-gray-800">
            Maintenance History - {{ truck.make }} {{ truck.model }} ({{ truck.license_plate }})
          </h2>
          <p class="mt-1 text-sm text-gray-500">
            View and manage maintenance records for this truck
          </p>
        </div>

        <!-- Right: Buttons -->
        <div class="flex gap-2">
          <SecondaryButton @click="router.get(route('admin.trucks.show', truck.id))">
            Back to Truck
          </SecondaryButton>
          <PrimaryButton @click="router.get(route('admin.trucks.maintenance.create', truck.id))">
            Add Maintenance
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

        <!-- Search and Filters -->
        <div class="mb-4 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
          <div class="w-full sm:w-96">
            <SearchInput 
              v-model="search" 
              placeholder="Search maintenance records..." 
              class="w-full"
            />
          </div>
          <div class="flex items-center gap-3">
            <TextInput
              type="date"
              v-model="dateFrom"
              placeholder="From Date"
              class="w-full sm:w-48"
            />
            <TextInput
              type="date"
              v-model="dateTo"
              placeholder="To Date"
              class="w-full sm:w-48"
            />
            <div class="text-sm text-gray-500 bg-blue-50 px-3 py-1 rounded border border-blue-100 whitespace-nowrap">
              📋 Showing {{ maintenances.data?.length || 0 }} records
              <span v-if="maintenances.data && maintenances.data.length < maintenances.total" class="ml-1">
                (Page {{ maintenances.current_page }} of {{ maintenances.last_page }})
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
                :data="maintenances.data"
                :sort-field="sortField"
                :sort-direction="sortDirection"
                @sort="handleSort"
                class="w-full"
              >
                <template #maintenance_date="{ row }">
                  {{ formatDate(row.maintenance_date) }}
                </template>
                <template #type="{ row }">
                  <span class="capitalize">
                    {{ formatMaintenanceType(row.type) }}
                  </span>
                </template>
                <template #cost="{ row }">
                  ${{ Number(row.cost).toFixed(2) }}
                </template>
                <template #service_details="{ row }">
                  <span class="line-clamp-2">{{ row.service_details }}</span>
                </template>
                <template #actions="{ row }">
                  <div class="flex space-x-2">
                    <SecondaryButton @click="editMaintenance(row)" class="text-xs py-1 px-2">Edit</SecondaryButton>
                    <DangerButton @click="confirmDeleteMaintenance(row)" class="text-xs py-1 px-2">Delete</DangerButton>
                  </div>
                </template>
                
                <!-- Empty State Slot -->
                <template #empty>
                  <div class="text-center py-8">
                    <div class="bg-gray-50 rounded-lg p-6 max-w-md mx-auto">
                      <WrenchScrewdriverIcon class="h-10 w-10 text-gray-400 mx-auto mb-3" />
                      <h3 class="text-lg font-medium text-gray-900 mb-2">No maintenance records found</h3>
                      <p class="text-gray-500 mb-3">
                        {{ search ? 'Try adjusting your search terms' : 'Get started by adding your first maintenance record' }}
                      </p>
                      <PrimaryButton @click="router.get(route('admin.trucks.maintenance.create', truck.id))">
                        Add Maintenance
                      </PrimaryButton>
                    </div>
                  </div>
                </template>
              </DataTable>

              <!-- Pagination Component -->
              <div v-if="maintenances.links?.length > 3" class="mt-4">
                <Pagination 
                  :pagination="maintenances" 
                  @page-changed="handlePageChange" 
                />
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <Modal :show="showDeleteModal" @close="closeDeleteModal">
      <div class="p-5">
        <h2 class="text-lg font-medium text-gray-900">Delete Maintenance Record?</h2>
        <p class="mt-1 text-sm text-gray-600">
          Are you sure you want to delete the maintenance record from {{ maintenanceToDelete ? formatDate(maintenanceToDelete.maintenance_date) : '' }}?
        </p>
        <div class="mt-4 flex justify-end space-x-3">
          <SecondaryButton @click="closeDeleteModal">Cancel</SecondaryButton>
          <DangerButton @click="deleteMaintenance" :disabled="isDeleting">
            <span v-if="isDeleting">Processing...</span>
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
import SearchInput from '@/Components/SearchInput.vue';
import TextInput from '@/Components/TextInput.vue';
import DataTable from '@/Components/DataTable.vue';
import Modal from '@/Components/Modal.vue';
import Pagination from '@/Components/Pagination.vue';
import { WrenchScrewdriverIcon } from '@heroicons/vue/24/outline';
import { ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';

const MAINTENANCE_TYPES = {
  routine: 'Routine Maintenance',
  repair: 'Repair',
  component_replacement: 'Component Replacement',
  inspection: 'Inspection'
};

const props = defineProps({
  truck: Object,
  maintenances: Object,
  filters: Object,
  status: String,
  success: String,
  error: String,
});

// State
const search = ref(props.filters?.search || '');
const dateFrom = ref(props.filters?.date_from || '');
const dateTo = ref(props.filters?.date_to || '');
const sortField = ref(props.filters?.sort_field || 'maintenance_date');
const sortDirection = ref(props.filters?.sort_direction || 'desc');
const showDeleteModal = ref(false);
const maintenanceToDelete = ref(null);
const isDeleting = ref(false);

// Constants
const columns = [
  { field: 'maintenance_date', header: 'Date', sortable: true },
  { field: 'type', header: 'Type', sortable: true },
  { field: 'service_provider', header: 'Provider', sortable: true },
  { field: 'service_details', header: 'Details', sortable: false },
  { field: 'cost', header: 'Cost', sortable: true },
  { field: 'actions', header: 'Actions', sortable: false },
];

// Methods
const formatMaintenanceType = (type) => {
  return MAINTENANCE_TYPES[type] || type.replace('_', ' ');
};

const formatDate = (dateString) => {
  return new Date(dateString).toLocaleDateString();
};

const handleSort = (sortParams) => {
  sortField.value = sortParams.field;
  sortDirection.value = sortParams.direction;
};

const handlePageChange = (page) => {
  router.get(route('admin.trucks.maintenance.index', props.truck.id), {
    page: page,
    search: search.value,
    date_from: dateFrom.value,
    date_to: dateTo.value,
    sort_field: sortField.value,
    sort_direction: sortDirection.value,
  }, {
    preserveState: true,
    preserveScroll: true,
    replace: true
  });
};

const editMaintenance = (maintenance) => {
  router.get(route('admin.trucks.maintenance.edit', {
    truck: props.truck.id,
    maintenance: maintenance.id
  }));
};

const confirmDeleteMaintenance = (maintenance) => {
  maintenanceToDelete.value = maintenance;
  showDeleteModal.value = true;
};

const closeDeleteModal = () => {
  showDeleteModal.value = false;
  maintenanceToDelete.value = null;
  isDeleting.value = false;
};

const deleteMaintenance = () => {
  if (!maintenanceToDelete.value) return;
  
  isDeleting.value = true;
  
  router.delete(route('admin.trucks.maintenance.destroy', {
    truck: props.truck.id,
    maintenance: maintenanceToDelete.value.id
  }), {
    preserveScroll: true,
    onSuccess: () => closeDeleteModal(),
    onFinish: () => isDeleting.value = false
  });
};

// Watchers
watch([search, dateFrom, dateTo, sortField, sortDirection], () => {
  router.get(route('admin.trucks.maintenance.index', props.truck.id), {
    search: search.value,
    date_from: dateFrom.value,
    date_to: dateTo.value,
    sort_field: sortField.value,
    sort_direction: sortDirection.value,
    page: 1
  }, {
    preserveState: true,
    replace: true,
  });
});
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