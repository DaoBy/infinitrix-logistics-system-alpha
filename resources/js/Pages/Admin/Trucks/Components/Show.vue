<template>
  <EmployeeLayout>
    <template #header>
      <div class="flex justify-between items-center">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">
          Component Details - {{ component.name }}
        </h2>
        <div class="flex space-x-2">
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

    <div class="py-6">
      <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
        <div v-if="status || success || error" class="mb-6">
          <div v-if="status" class="p-4 bg-blue-100 text-blue-800 rounded">
            {{ status }}
          </div>
          <div v-if="success" class="p-4 bg-green-100 text-green-800 rounded">
            {{ success }}
          </div>
          <div v-if="error" class="p-4 bg-red-100 text-red-800 rounded">
            {{ error }}
          </div>
        </div>

        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
          <div class="p-6 bg-white border-b border-gray-200">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div class="space-y-4">
                <h3 class="text-lg font-medium text-gray-900">Component Information</h3>
                <div class="space-y-4">
                  <div>
                    <label class="block text-sm font-medium text-gray-500">Name</label>
                    <p class="mt-1 text-sm text-gray-900">{{ component.name }}</p>
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-500">Type</label>
                    <p class="mt-1 text-sm text-gray-900 capitalize">{{ component.type.replace('_', ' ') }}</p>
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-500">Condition</label>
                    <span :class="`px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${getConditionColor(component.condition)}`">
                      {{ conditionLabels[component.condition] }}
                    </span>
                  </div>
                </div>
              </div>

              <div class="space-y-4">
                <h3 class="text-lg font-medium text-gray-900">Technical Details</h3>
                <div class="space-y-4">
                  <div v-if="component.serial_number">
                    <label class="block text-sm font-medium text-gray-500">Serial Number</label>
                    <p class="mt-1 text-sm text-gray-900">{{ component.serial_number }}</p>
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-500">Installation Date</label>
                    <p class="mt-1 text-sm text-gray-900">{{ formatDate(component.installation_date) }}</p>
                  </div>
                  <div v-if="component.last_maintenance_date">
                    <label class="block text-sm font-medium text-gray-500">Last Maintenance</label>
                    <p class="mt-1 text-sm text-gray-900">{{ formatDate(component.last_maintenance_date) }}</p>
                  </div>
                </div>
              </div>

              <div class="md:col-span-2 space-y-4" v-if="component.notes">
                <h3 class="text-lg font-medium text-gray-900">Notes</h3>
                <p class="mt-1 text-sm text-gray-900 whitespace-pre-line">{{ component.notes }}</p>
              </div>

              <div class="md:col-span-2 space-y-4">
                <div class="flex justify-between items-center">
                  <h3 class="text-lg font-medium text-gray-900">Maintenance History</h3>
                </div>
                
                <DataTable
                  v-if="component.maintenance_records && component.maintenance_records.length"
                  :columns="maintenanceColumns"
                  :data="component.maintenance_records"
                >
                  <template #maintenance_date="{ row }">
                    {{ formatDate(row.maintenance_date) }}
                  </template>
                  <template #cost="{ row }">
                    ${{ Number(row.cost).toFixed(2) }}
                  </template>
                  <template #actions="{ row }">
                    <button 
                      @click="viewMaintenance(row)"
                      class="text-blue-600 hover:text-blue-900 mr-3"
                    >
                      View
                    </button>
                  </template>
                </DataTable>
                <div v-else class="text-gray-500">
                  No maintenance records found for this component.
                </div>
              </div>
            </div>

            <div class="mt-6 flex justify-end space-x-4">
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

    <Modal :show="showDeleteModal" @close="showDeleteModal = false">
      <div class="p-6">
        <h2 class="text-lg font-medium text-gray-900">Delete Component?</h2>
        <p class="mt-1 text-sm text-gray-600">
          Are you sure you want to delete <strong>{{ component.name }}</strong>?
          This action cannot be undone.
        </p>
        <div class="mt-6 flex justify-end space-x-4">
          <SecondaryButton @click="showDeleteModal = false">
            Cancel
          </SecondaryButton>
          <DangerButton @click="deleteComponent" :disabled="isDeleting">
            <span v-if="isDeleting">Deleting...</span>
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
  status: {
    type: String,
    default: ''
  },
  success: {
    type: String,
    default: ''
  },
  error: {
    type: String,
    default: ''
  },
});

const showDeleteModal = ref(false);
const isDeleting = ref(false);

const conditionLabels = {
  new: 'New',
  good: 'Good',
  fair: 'Fair',
  poor: 'Poor',
  needs_replacement: 'Needs Replacement'
};

const maintenanceColumns = [
  { field: 'maintenance_date', header: 'Date' },
  { field: 'service_provider', header: 'Provider' },
  { field: 'service_details', header: 'Details' },
  { field: 'cost', header: 'Cost' },
  { field: 'actions', header: 'Actions' },
];

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

const deleteComponent = () => {
  isDeleting.value = true;
  
  router.delete(route('admin.trucks.components.destroy', {
    truck: props.truck.id,
    component: props.component.id
  }), {
    preserveScroll: true,
    onSuccess: () => {
      showDeleteModal.value = false;
    },
    onFinish: () => {
      isDeleting.value = false;
    }
  }); 
};
</script>