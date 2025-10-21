<template>
  <EmployeeLayout>
    <template #header>
      <div class="flex justify-between items-center w-full px-6 md:px-8">
        <!-- Left: Title & Subtitle -->
        <div>
          <h2 class="text-xl font-semibold leading-tight text-gray-800">
            Component Details - {{ component.name }}
          </h2>
          <p class="mt-1 text-sm text-gray-500">
            View component information and maintenance history
          </p>
        </div>

        <!-- Right: Buttons -->
        <div class="flex gap-2">
          <SecondaryButton @click="router.get(route('admin.trucks.components.index', truck.id))">
            Back to Components
          </SecondaryButton>
          <PrimaryButton @click="editComponent">
            Edit
          </PrimaryButton>
          <DangerButton @click="confirmDeleteComponent">
            Delete
          </DangerButton>
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
              <!-- Component Information Card -->
              <div class="bg-white border border-gray-200 rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Component Information</h3>
                <div class="space-y-4">
                  <div class="flex justify-between items-center py-2 border-b border-gray-100">
                    <span class="text-sm font-medium text-gray-500">Name</span>
                    <span class="text-sm text-gray-900 font-semibold">{{ component.name }}</span>
                  </div>
                  <div class="flex justify-between items-center py-2 border-b border-gray-100">
                    <span class="text-sm font-medium text-gray-500">Type</span>
                    <span class="text-sm text-gray-900 capitalize">{{ component.type.replace('_', ' ') }}</span>
                  </div>
                  <div class="flex justify-between items-center py-2">
                    <span class="text-sm font-medium text-gray-500">Condition</span>
                    <span :class="`px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${getConditionColor(component.condition)}`">
                      {{ conditionLabels[component.condition] }}
                    </span>
                  </div>
                </div>
              </div>

              <!-- Technical Details Card -->
              <div class="bg-white border border-gray-200 rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Technical Details</h3>
                <div class="space-y-4">
                  <div v-if="component.serial_number" class="flex justify-between items-center py-2 border-b border-gray-100">
                    <span class="text-sm font-medium text-gray-500">Serial Number</span>
                    <span class="text-sm text-gray-900 font-mono">{{ component.serial_number }}</span>
                  </div>
                  <div class="flex justify-between items-center py-2 border-b border-gray-100">
                    <span class="text-sm font-medium text-gray-500">Installation Date</span>
                    <span class="text-sm text-gray-900">{{ formatDate(component.installation_date) }}</span>
                  </div>
                  <div v-if="component.last_maintenance_date" class="flex justify-between items-center py-2">
                    <span class="text-sm font-medium text-gray-500">Last Maintenance</span>
                    <span class="text-sm text-gray-900">{{ formatDate(component.last_maintenance_date) }}</span>
                  </div>
                </div>
              </div>
            </div>

            <!-- Notes Card -->
            <div v-if="component.notes" class="bg-white border border-gray-200 rounded-lg p-6 mb-6">
              <h3 class="text-lg font-medium text-gray-900 mb-4">Notes</h3>
              <p class="text-sm text-gray-700 whitespace-pre-line">{{ component.notes }}</p>
            </div>

            <!-- Maintenance History -->
            <div class="bg-white border border-gray-200 rounded-lg p-6">
              <h3 class="text-lg font-medium text-gray-900 mb-4">Maintenance History</h3>
              
              <DataTable
                v-if="component.maintenance_records && component.maintenance_records.length"
                :columns="maintenanceColumns"
                :data="component.maintenance_records"
                class="w-full"
              >
                <template #maintenance_date="{ row }">
                  {{ formatDate(row.maintenance_date) }}
                </template>
                <template #cost="{ row }">
                  ${{ Number(row.cost).toFixed(2) }}
                </template>
                <template #actions="{ row }">
                  <SecondaryButton @click="viewMaintenance(row)" class="text-xs py-1 px-2">View</SecondaryButton>
                </template>
                
                <template #empty>
                  <div class="text-center py-4 text-gray-500">
                    No maintenance records found for this component.
                  </div>
                </template>
              </DataTable>
              <div v-else class="text-center py-4 text-gray-500">
                No maintenance records found for this component.
              </div>
            </div>

            <div class="mt-6 flex justify-end space-x-3">
              <SecondaryButton @click="router.get(route('admin.trucks.components.index', truck.id))">
                Back to Components
              </SecondaryButton>
              <PrimaryButton @click="editComponent">
                Edit Component
              </PrimaryButton>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <Modal :show="showDeleteModal" @close="closeDeleteModal">
      <div class="p-5">
        <h2 class="text-lg font-medium text-gray-900">Delete Component?</h2>
        <p class="mt-1 text-sm text-gray-600">
          Are you sure you want to delete <strong>{{ component.name }}</strong>?
          This action cannot be undone.
        </p>
        <div class="mt-4 flex justify-end space-x-3">
          <SecondaryButton @click="closeDeleteModal">Cancel</SecondaryButton>
          <DangerButton @click="deleteComponent" :disabled="isDeleting">
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
import DataTable from '@/Components/DataTable.vue';
import Modal from '@/Components/Modal.vue';
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';

const props = defineProps({
  truck: {
    type: Object,
    required: true
  },
  component: {
    type: Object,
    required: true
  },
  status: String,
  success: String,
  error: String,
});

// State
const showDeleteModal = ref(false);
const isDeleting = ref(false);

// Constants
const conditionLabels = {
  new: 'New',
  good: 'Good',
  fair: 'Fair',
  poor: 'Poor',
  needs_replacement: 'Needs Replacement'
};

const maintenanceColumns = [
  { field: 'maintenance_date', header: 'Date', sortable: true },
  { field: 'service_provider', header: 'Provider', sortable: true },
  { field: 'service_details', header: 'Details', sortable: false },
  { field: 'cost', header: 'Cost', sortable: true },
  { field: 'actions', header: 'Actions', sortable: false },
];

// Methods
const formatDate = (dateString) => {
  if (!dateString) return 'N/A';
  return new Date(dateString).toLocaleDateString();
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

const editComponent = () => {
  router.get(route('admin.trucks.components.edit', {
    truck: props.truck.id,
    component: props.component.id
  }));
};

const viewMaintenance = (maintenance) => {
  router.get(route('admin.trucks.maintenance.index', props.truck.id), {
    search: props.component.name,
    component_id: props.component.id
  });
};

const confirmDeleteComponent = () => {
  showDeleteModal.value = true;
};

const closeDeleteModal = () => {
  showDeleteModal.value = false;
  isDeleting.value = false;
};

const deleteComponent = () => {
  isDeleting.value = true;
  
  router.delete(route('admin.trucks.components.destroy', {
    truck: props.truck.id,
    component: props.component.id
  }), {
    preserveScroll: true,
    onSuccess: () => closeDeleteModal(),
    onFinish: () => isDeleting.value = false
  }); 
};
</script>

<style scoped>
.zoom-content {
  zoom: 0.80;
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