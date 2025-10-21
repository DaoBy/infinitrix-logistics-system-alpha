<template>
  <EmployeeLayout>
    <template #header>
      <div class="flex justify-between items-center w-full px-6 md:px-8">
        <!-- Left: Title & Subtitle -->
        <div>
          <h2 class="text-xl font-semibold leading-tight text-gray-800">
            Components - {{ truck.make }} {{ truck.model }} ({{ truck.license_plate }})
          </h2>
          <p class="mt-1 text-sm text-gray-500">
            Manage truck components and their maintenance
          </p>
        </div>

        <!-- Right: Buttons -->
        <div class="flex gap-2">
          <SecondaryButton @click="router.get(route('admin.trucks.show', truck.id))">
            Back to Truck
          </SecondaryButton>
          <PrimaryButton @click="createComponent">
            Add New Component
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

        <!-- Results Counter -->
        <div class="mb-4 flex justify-between items-center">
          <div class="text-sm text-gray-500 bg-blue-50 px-3 py-1 rounded border border-blue-100">
            📋 Showing {{ components.length }} {{ components.length === 1 ? 'component' : 'components' }}
          </div>
        </div>

        <!-- Data Table Container -->
        <div class="justify-center flex items-center">
          <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg w-full max-w-[95vw]">
            <div class="p-4 bg-white border-b border-gray-200">
              <DataTable
                :columns="columns"
                :data="components"
                class="w-full"
              >
                <template #name="{ row }">
                  <span class="font-medium text-gray-900">{{ row.name || 'N/A' }}</span>
                </template>
                <template #type="{ row }">
                  <span class="text-gray-700 capitalize">{{ row.type.replace('_', ' ') || 'N/A' }}</span>
                </template>
                <template #serial_number="{ row }">
                  <span class="text-gray-700">{{ row.serial_number || 'N/A' }}</span>
                </template>
                <template #condition="{ row }">
                  <span 
                    class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full capitalize"
                    :class="conditionClasses[row.condition]"
                  >
                    {{ conditionLabels[row.condition] }}
                  </span>
                </template>
                <template #installation_date="{ row }">
                  <span class="text-gray-600">{{ formatDate(row.installation_date) || 'N/A' }}</span>
                </template>
                <template #actions="{ row }">
                  <div class="flex space-x-2">
                    <SecondaryButton @click="viewComponent(row.id)" class="text-xs py-1 px-2">View</SecondaryButton>
                    <PrimaryButton @click="editComponent(row.id)" class="text-xs py-1 px-2">Edit</PrimaryButton>
                    <DangerButton @click="confirmDeleteComponent(row)" class="text-xs py-1 px-2">Delete</DangerButton>
                  </div>
                </template>
                
                <!-- Empty State Slot -->
                <template #empty>
                  <div class="text-center py-8">
                    <div class="bg-gray-50 rounded-lg p-6 max-w-md mx-auto">
                      <CogIcon class="h-10 w-10 text-gray-400 mx-auto mb-3" />
                      <h3 class="text-lg font-medium text-gray-900 mb-2">No components found</h3>
                      <p class="text-gray-500 mb-3">
                        Get started by adding your first component
                      </p>
                      <PrimaryButton @click="createComponent">Add New Component</PrimaryButton>
                    </div>
                  </div>
                </template>
              </DataTable>
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
          Are you sure you want to delete <strong>{{ componentToDelete?.name }}</strong>?
          This action cannot be undone.
        </p>
        <div class="mt-4 flex justify-end space-x-3">
          <SecondaryButton @click="closeDeleteModal">Cancel</SecondaryButton>
          <DangerButton @click="handleDelete" :disabled="isDeleting">
            <span v-if="isDeleting">Processing...</span>
            <span v-else>Delete</span>
          </DangerButton>
        </div>
      </div>
    </Modal>
  </EmployeeLayout>
</template>

<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import EmployeeLayout from '@/Layouts/EmployeeLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import DataTable from '@/Components/DataTable.vue';
import Modal from '@/Components/Modal.vue';
import { CogIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
  truck: Object,
  components: Array,
  status: String,
  success: String,
  error: String,
});

// State
const showDeleteModal = ref(false);
const componentToDelete = ref(null);
const isDeleting = ref(false);

// Constants
const columns = [
  { field: 'name', header: 'Name', sortable: true },
  { field: 'type', header: 'Type', sortable: true },
  { field: 'serial_number', header: 'Serial Number', sortable: true },
  { field: 'condition', header: 'Condition', sortable: true },
  { field: 'installation_date', header: 'Installation Date', sortable: true },
  { field: 'actions', header: 'Actions', sortable: false }
];

const conditionLabels = {
  new: 'New',
  good: 'Good',
  fair: 'Fair',
  poor: 'Poor',
  needs_replacement: 'Needs Replacement'
};

const conditionClasses = {
  new: 'bg-green-100 text-green-800',
  good: 'bg-blue-100 text-blue-800',
  fair: 'bg-yellow-100 text-yellow-800',
  poor: 'bg-orange-100 text-orange-800',
  needs_replacement: 'bg-red-100 text-red-800'
};

// Methods
const formatDate = (dateString) => {
  if (!dateString) return 'N/A';
  return new Date(dateString).toLocaleDateString();
};

const createComponent = () => {
  router.get(route('admin.trucks.components.create', props.truck.id));
};

const viewComponent = (id) => {
  router.get(route('admin.trucks.components.show', {
    truck: props.truck.id,
    component: id
  }));
};

const editComponent = (id) => {
  router.get(route('admin.trucks.components.edit', {
    truck: props.truck.id,
    component: id
  }));
};

const confirmDeleteComponent = (component) => {
  componentToDelete.value = component;
  showDeleteModal.value = true;
};

const closeDeleteModal = () => {
  showDeleteModal.value = false;
  componentToDelete.value = null;
  isDeleting.value = false;
};

const handleDelete = async () => {
  if (!componentToDelete.value) return;
  isDeleting.value = true;
  try {
    await router.delete(route('admin.trucks.components.destroy', {
      truck: props.truck.id,
      component: componentToDelete.value.id
    }), {
      preserveScroll: true
    });
    closeDeleteModal();
  } catch {
    closeDeleteModal();
    alert('Failed to delete component. Please try again.');
  } finally {
    isDeleting.value = false;
  }
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