<template>
  <EmployeeLayout>
    <template #header>
      <div class="flex justify-between items-center">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">
          Truck Details: {{ truck.license_plate }}
        </h2>
        <div class="flex space-x-2">
          <SecondaryButton @click="$inertia.visit(route('admin.trucks.index'))">
            Back to List
          </SecondaryButton>
          <PrimaryButton @click="editTruck">
            Edit
          </PrimaryButton>
          <DangerButton 
            v-if="truck.is_active"
            @click="archiveTruck"
          >
            Archive
          </DangerButton>
          <PrimaryButton 
            v-else
            @click="restoreTruck"
          >
            Restore
          </PrimaryButton>
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
              <!-- Basic Information -->
              <div class="space-y-4">
                <h3 class="text-lg font-medium text-gray-900">Basic Information</h3>
                <div class="space-y-4">
                  <div>
                    <label class="block text-sm font-medium text-gray-500">License Plate</label>
                    <p class="mt-1 text-sm text-gray-900">{{ truck.license_plate }}</p>
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-500">Make & Model</label>
                    <p class="mt-1 text-sm text-gray-900">{{ truck.make }} {{ truck.model }}</p>
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-500">Capacity</label>
                    <p class="mt-1 text-sm text-gray-900">{{ truck.capacity }} kg</p>
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-500">Status</label>
                    <span 
                      :class="statusClasses[truck.status]" 
                      class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full capitalize"
                    >
                      {{ truck.status.replace('_', ' ') }}
                    </span>
                  </div>
                  <div v-if="truck.region">
                    <label class="block text-sm font-medium text-gray-500">Region</label>
                    <p class="mt-1 text-sm text-gray-900">{{ truck.region.name }}</p>
                  </div>
                </div>
              </div>

              <!-- Additional Information -->
              <div class="space-y-4">
                <h3 class="text-lg font-medium text-gray-900">Additional Information</h3>
                <div class="space-y-4">
                  <div v-if="truck.year">
                    <label class="block text-sm font-medium text-gray-500">Year</label>
                    <p class="mt-1 text-sm text-gray-900">{{ truck.year }}</p>
                  </div>
                  <div v-if="truck.vin">
                    <label class="block text-sm font-medium text-gray-500">VIN</label>
                    <p class="mt-1 text-sm text-gray-900">{{ truck.vin }}</p>
                  </div>
                  <div v-if="truck.purchase_date">
                    <label class="block text-sm font-medium text-gray-500">Purchase Date</label>
                    <p class="mt-1 text-sm text-gray-900">{{ formatDate(truck.purchase_date) }}</p>
                  </div>
                  <div v-if="truck.notes">
                    <label class="block text-sm font-medium text-gray-500">Notes</label>
                    <p class="mt-1 text-sm text-gray-900 whitespace-pre-line">{{ truck.notes }}</p>
                  </div>
                </div>
              </div>

              <!-- Maintenance History -->
              <div class="space-y-4 md:col-span-2">
                <div class="flex justify-between items-center">
                  <h3 class="text-lg font-medium text-gray-900">Maintenance History</h3>
                  <PrimaryButton @click="router.get(route('admin.trucks.maintenance.create', truck.id))">
                    Add Maintenance
                  </PrimaryButton>
                </div>
                
                <DataTable
                  v-if="truck.maintenance && truck.maintenance.length"
                  :columns="maintenanceColumns"
                  :data="truck.maintenance"
                >
                  <template #maintenance_date="{ row }">
                    {{ formatDate(row.maintenance_date) }}
                  </template>
                  <template #cost="{ row }">
                    ${{ Number(row.cost).toFixed(2) }}
                  </template>
                  <template #actions="{ row }">
                    <button 
                      @click="editMaintenance(row)"
                      class="text-blue-600 hover:text-blue-900 mr-3"
                    >
                      Edit
                    </button>
                    <button 
                      @click="confirmDeleteMaintenance(row)"
                      class="text-red-600 hover:text-red-900"
                    >
                      Delete
                    </button>
                  </template>
                </DataTable>
                <div v-else class="text-gray-500">
                  No maintenance records found.
                </div>
              </div>

              <!-- Components -->
              <div class="space-y-4 md:col-span-2">
                <div class="flex justify-between items-center">
                  <h3 class="text-lg font-medium text-gray-900">Components</h3>
                  <PrimaryButton @click="router.get(route('admin.trucks.components.index', truck.id))">
                    Manage Components
                  </PrimaryButton>
                </div>
                
                <DataTable
                  v-if="truck.components && truck.components.length"
                  :columns="componentColumns"
                  :data="truck.components"
                >
                  <template #condition="{ row }">
                    <span :class="`px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${getConditionColor(row.condition)}`">
                      {{ conditionLabels[row.condition] }}
                    </span>
                  </template>
                  <template #actions="{ row }">
                    <button 
                      @click="viewComponent(row)"
                      class="text-blue-600 hover:text-blue-900"
                    >
                      View
                    </button>
                  </template>
                </DataTable>
                <div v-else class="text-gray-500">
                  No components found.
                </div>
              </div>
            </div>

            <div class="mt-8 flex justify-end space-x-4">
              <SecondaryButton @click="$inertia.visit(route('admin.trucks.index'))">
                Back to List
              </SecondaryButton>
              <PrimaryButton @click="editTruck">
                Edit Truck
              </PrimaryButton>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Delete Maintenance Confirmation -->
    <Modal :show="showDeleteMaintenanceModal" @close="showDeleteMaintenanceModal = false">
      <div class="p-6">
        <h2 class="text-lg font-medium text-gray-900">
          Delete Maintenance Record?
        </h2>
        <p class="mt-1 text-sm text-gray-600">
          Are you sure you want to delete this maintenance record from 
          {{ maintenanceToDelete ? formatDate(maintenanceToDelete.maintenance_date) : '' }}?
        </p>
        <div class="mt-6 flex justify-end space-x-4">
          <SecondaryButton @click="showDeleteMaintenanceModal = false">
            Cancel
          </SecondaryButton>
          <DangerButton @click="deleteMaintenance" :disabled="isDeletingMaintenance">
            <span v-if="isDeletingMaintenance">Deleting...</span>
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
    required: true,
    default: () => ({
      maintenance: [],
      components: []
    })
  },
  status: String,
  success: String,
  error: String,
});

const statusClasses = {
  available: 'bg-green-100 text-green-800',
  in_use: 'bg-yellow-100 text-yellow-800',
  under_repair: 'bg-red-100 text-red-800'
};

const conditionLabels = {
  new: 'New',
  good: 'Good',
  fair: 'Fair',
  poor: 'Poor',
  needs_replacement: 'Needs Replacement'
};

const maintenanceColumns = [
  { field: 'maintenance_date', header: 'Date' },
  { field: 'type', header: 'Type', formatter: (val) => val ? val.replace('_', ' ') : 'N/A' },
  { field: 'service_provider', header: 'Provider' },
  { field: 'cost', header: 'Cost' },
  { field: 'actions', header: 'Actions' }
];

const componentColumns = [
  { field: 'name', header: 'Name' },
  { field: 'type', header: 'Type', formatter: (val) => val ? val.replace('_', ' ') : 'N/A' },
  { field: 'condition', header: 'Condition' },
  { field: 'installation_date', header: 'Installed', formatter: (val) => val ? formatDate(val) : 'N/A' },
  { field: 'actions', header: 'Actions' }
];

const showDeleteMaintenanceModal = ref(false);
const maintenanceToDelete = ref(null);
const isDeletingMaintenance = ref(false);

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
  if (confirm('Are you sure you want to archive this truck?')) {
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

const confirmDeleteMaintenance = (maintenance) => {
  maintenanceToDelete.value = maintenance;
  showDeleteMaintenanceModal.value = true;
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
      showDeleteMaintenanceModal.value = false;
      router.reload();
    },
    onFinish: () => {
      isDeletingMaintenance.value = false;
    }
  });
};

const viewComponent = (component) => {
  router.get(route('admin.trucks.components.show', { 
    truck: props.truck.id, 
    component: component.id 
  }));
};
</script>