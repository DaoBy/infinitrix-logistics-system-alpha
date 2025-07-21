<template>
  <EmployeeLayout>
    <template #header>
      <div class="flex justify-between items-center">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">
          Maintenance History - {{ truck.make }} {{ truck.model }} ({{ truck.license_plate }})
        </h2>
        <div class="flex space-x-2">
          <SecondaryButton @click="router.get(route('admin.trucks.show', truck.id))">
            Back to Truck
          </SecondaryButton>
          <PrimaryButton @click="router.get(route('admin.trucks.maintenance.create', truck.id))">
            Add Maintenance
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

        <div class="mb-6 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
          <SearchInput 
            v-model="search" 
            placeholder="Search maintenance..." 
            class="w-full md:w-72"
          />
          <div class="flex flex-wrap gap-2 w-full md:w-auto">
            <TextInput
              type="date"
              v-model="dateFrom"
              placeholder="From Date"
              class="w-full md:w-48"
            />
            <TextInput
              type="date"
              v-model="dateTo"
              placeholder="To Date"
              class="w-full md:w-48"
            />
          </div>
        </div>

        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
          <div class="p-6 bg-white border-b border-gray-200">
            <DataTable
              :columns="columns"
              :data="maintenances.data"
              :sort-field="sortField"
              :sort-direction="sortDirection"
              @sort="handleSort"
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
              <template #actions="{ row }">
                <div class="flex space-x-2">
                  <SecondaryButton @click="editMaintenance(row)">
                    Edit
                  </SecondaryButton>
                  <DangerButton @click="confirmDeleteMaintenance(row)">
                    Delete
                  </DangerButton>
                </div>
              </template>
            </DataTable>

            <div v-if="maintenances.links?.length > 3" class="mt-4 flex justify-center">
              <div class="flex flex-wrap -mb-1">
                <template v-for="(link, index) in maintenances.links" :key="index">
                  <div v-if="!link.url" class="mr-1 mb-1 px-4 py-3 text-sm leading-4 text-gray-400 border rounded" 
                      v-html="link.label" />
                  <Link v-else class="mr-1 mb-1 px-4 py-3 text-sm leading-4 border rounded hover:bg-white focus:border-indigo-500 focus:text-indigo-500" 
                        :class="{ 'bg-blue-700 text-white': link.active }" :href="link.url" v-html="link.label" />
                </template>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <Modal :show="showDeleteModal" @close="showDeleteModal = false">
      <div class="p-6">
        <h2 class="text-lg font-medium text-gray-900">Delete Maintenance Record?</h2>
        <p class="mt-1 text-sm text-gray-600">
          Are you sure you want to delete the maintenance record from {{ maintenanceToDelete ? formatDate(maintenanceToDelete.maintenance_date) : '' }}?
        </p>
        <div class="mt-6 flex justify-end space-x-4">
          <SecondaryButton @click="showDeleteModal = false">
            Cancel
          </SecondaryButton>
          <DangerButton @click="deleteMaintenance" :disabled="isDeleting">
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
import SearchInput from '@/Components/SearchInput.vue';
import TextInput from '@/Components/TextInput.vue';
import DataTable from '@/Components/DataTable.vue';
import Modal from '@/Components/Modal.vue';
import { ref, watch } from 'vue';
import { router, Link } from '@inertiajs/vue3';

const MAINTENANCE_TYPES = {
  routine: 'Routine Maintenance',
  repair: 'Repair',
  component_replacement: 'Component Replacement',
  inspection: 'Inspection'
};

const formatMaintenanceType = (type) => {
  return MAINTENANCE_TYPES[type] || type.replace('_', ' ');
};


const props = defineProps({
  truck: Object,
  maintenances: Object,
  filters: Object,
  status: String,
  success: String,
  error: String,
});

const columns = [
  { field: 'maintenance_date', header: 'Date', sortable: true },
  { field: 'type', header: 'Type', sortable: true },
  { field: 'service_provider', header: 'Provider', sortable: true },
  { field: 'service_details', header: 'Details', sortable: false },
  { field: 'cost', header: 'Cost', sortable: true },
  { field: 'actions', header: 'Actions', sortable: false },
];

const search = ref(props.filters?.search || '');
const dateFrom = ref(props.filters?.date_from || '');
const dateTo = ref(props.filters?.date_to || '');
const sortField = ref(props.filters?.sort_field || 'maintenance_date');
const sortDirection = ref(props.filters?.sort_direction || 'desc');
const showDeleteModal = ref(false);
const maintenanceToDelete = ref(null);
const isDeleting = ref(false);

const formatDate = (dateString) => {
  return new Date(dateString).toLocaleDateString();
};

const handleSort = (field) => {
  if (sortField.value === field) {
    sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc';
  } else {
    sortField.value = field;
    sortDirection.value = 'asc';
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
  showDeleteModal.value = true;
};

const deleteMaintenance = () => {
  if (!maintenanceToDelete.value) return;
  
  isDeleting.value = true;
  
  router.delete(route('admin.trucks.maintenance.destroy', {
    truck: props.truck.id,
    maintenance: maintenanceToDelete.value.id
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

watch([search, dateFrom, dateTo, sortField, sortDirection], () => {
  router.get(route('admin.trucks.maintenance.index', props.truck.id), {
    search: search.value,
    date_from: dateFrom.value,
    date_to: dateTo.value,
    sort_field: sortField.value,
    sort_direction: sortDirection.value,
  }, {
    preserveState: true,
    replace: true,
  });
});
</script>