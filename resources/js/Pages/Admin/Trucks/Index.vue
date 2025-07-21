<template>
  <EmployeeLayout>
    <template #header>
      <div class="flex justify-between items-center px-6">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">Truck Management</h2>
        <div class="flex space-x-2">
          <PrimaryButton @click="createTruck">Add New Truck</PrimaryButton>
          <SecondaryButton @click="viewArchived">View Disabled</SecondaryButton>
        </div>
      </div>
    </template>

    <div class="px-6 sm:px-8">
      <div v-if="status || success || error" class="mb-6">
        <div v-if="status" class="p-4 bg-blue-100 text-blue-800 rounded">{{ status }}</div>
        <div v-if="success" class="p-4 bg-green-100 text-green-800 rounded">{{ success }}</div>
        <div v-if="error" class="p-4 bg-red-100 text-red-800 rounded">{{ error }}</div>
      </div>

      <div class="mb-6 flex flex-col md:flex-row justify-between items-start md:items-center gap-4 w-full mx-auto">
        <SearchInput 
          v-model="search" 
          placeholder="Search trucks..." 
          class="w-full md:w-72"
        />
        <div class="flex flex-wrap gap-2 w-full md:w-auto">
          <SelectInput 
            v-model="statusFilter" 
            :options="statusOptions" 
            option-value="value"
            option-label="text"
            placeholder="All Statuses"
            class="w-full md:w-48"
          />
          <SelectInput 
            v-model="makeFilter" 
            :options="makeOptions" 
            option-value="value"
            option-label="text"
            placeholder="All Makes"
            class="w-full md:w-48"
          />
        </div>
      </div>

      <div class="justify-center flex items-center">
        <div class="p-5 bg-white border-b border-gray-200">
          <DataTable
            :columns="columns"
            :data="trucks.data"
            :sort-field="sortField"
            :sort-direction="sortDirection"
            @sort="handleSort"
            class="w-full"
          >
            <template #license_plate="{ row }">
              <span class="truncate block">{{ row.license_plate || 'N/A' }}</span>
            </template>
            <template #make="{ row }">
              <span class="truncate block">{{ row.make || 'N/A' }}</span>
            </template>
            <template #model="{ row }">
              <span class="truncate block">{{ row.model || 'N/A' }}</span>
            </template>
            <template #volume_capacity="{ row }">
              {{ row.volume_capacity ? `${row.volume_capacity} m³` : 'N/A' }}
            </template>
            <template #weight_capacity="{ row }">
              {{ row.weight_capacity ? `${row.weight_capacity} kg` : 'N/A' }}
            </template>
            <template #status="{ row }">
              <span 
                class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full capitalize"
                :class="statusClasses[row.status]"
              >
                {{ getStatusText(row.status) }}
              </span>
            </template>
            <template #last_maintenance_date="{ row }">
              {{ row.last_maintenance_date || 'Never' }}
            </template>
            <template #region.name="{ row }">
              {{ row.region && row.region.name ? row.region.name : 'N/A' }}
            </template>
            <template #actions="{ row }">
              <div class="flex space-x-2">
                <SecondaryButton @click="viewTruck(row.id)">View</SecondaryButton>
                <PrimaryButton @click="editTruck(row.id)">Edit</PrimaryButton>
                <DangerButton @click="confirmArchiveTruck(row)">Disable</DangerButton>
              </div>
            </template>
          </DataTable>

          <div v-if="trucks.links?.length > 3" class="mt-4 flex justify-center">
            <div class="flex flex-wrap -mb-1">
              <template v-for="(link, index) in trucks.links" :key="index">
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

    <Modal :show="showArchiveModal" @close="closeArchiveModal">
      <div class="p-6">
        <h2 class="text-lg font-medium text-gray-900">Disable Truck?</h2>
        <p class="mt-1 text-sm text-gray-600">
          Are you sure you want to disable <strong>{{ truckToArchive?.license_plate }}</strong>?
          This action can be undone later if needed.
        </p>
        <div class="mt-6 flex justify-end space-x-4">
          <SecondaryButton @click="closeArchiveModal">Cancel</SecondaryButton>
          <DangerButton @click="handleArchive" :disabled="isArchiving">
            <span v-if="isArchiving">Disabling...</span>
            <span v-else>Disable</span>
          </DangerButton>
        </div>
      </div>
    </Modal>
  </EmployeeLayout>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue';
import { computed } from 'vue';
import { router, Link } from '@inertiajs/vue3';
import EmployeeLayout from '@/Layouts/EmployeeLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import SearchInput from '@/Components/SearchInput.vue';
import SelectInput from '@/Components/SelectInput.vue';
import DataTable from '@/Components/DataTable.vue';
import Modal from '@/Components/Modal.vue';

const props = defineProps({ 
  trucks: Object, 
  status: String, 
  success: String, 
  error: String 
});

// State
const showArchiveModal = ref(false);
const truckToArchive = ref(null);
const isArchiving = ref(false);
const search = ref(props.trucks.filters?.search || '');
const statusFilter = ref(props.trucks.filters?.status || '');
const makeFilter = ref(props.trucks.filters?.make || '');
const sortField = ref(props.trucks.filters?.sort_field || 'license_plate');
const sortDirection = ref(props.trucks.filters?.sort_direction || 'asc');

// Constants
const statusOptions = [
  { value: '', text: 'All Statuses' },
  { value: 'available', text: 'Available' },
  { value: 'in_use', text: 'In Use' },
  { value: 'under_repair', text: 'Under Repair' }
];

const statusClasses = {
  available: 'bg-green-100 text-green-800',
  in_use: 'bg-yellow-100 text-yellow-800',
  under_repair: 'bg-red-100 text-red-800'
};

const columns = [
  { field: 'license_plate', header: 'License Plate', sortable: true },
  { field: 'make', header: 'Make', sortable: true },
  { field: 'model', header: 'Model', sortable: true },
  { field: 'volume_capacity', header: 'Volume Capacity', sortable: true },
  { field: 'weight_capacity', header: 'Weight Capacity', sortable: true },
  { field: 'region.name', header: 'Region', sortable: true }, // <-- Added Region column
  { field: 'status', header: 'Status', sortable: true },
  { field: 'last_maintenance_date', header: 'Last Maintenance', sortable: true },
  { field: 'actions', header: 'Actions', sortable: false }
];

// Computed
const makeOptions = computed(() => {
  const makes = [...new Set(props.trucks.data.map(truck => truck.make))];
  return [
    { value: '', text: 'All Makes' },
    ...makes.map(make => ({ value: make, text: make }))
  ];
});

// Methods
const getStatusText = (statusValue) => {
  const option = statusOptions.find(opt => opt.value === statusValue);
  return option ? option.text : statusValue.replace('_', ' ');
};

const handleSort = (field) => {
  if (sortField.value === field) {
    sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc';
  } else {
    sortField.value = field;
    sortDirection.value = 'asc';
  }
};

const confirmArchiveTruck = (truck) => {
  truckToArchive.value = truck;
  showArchiveModal.value = true;
};

const closeArchiveModal = () => {
  showArchiveModal.value = false;
  setTimeout(() => { 
    truckToArchive.value = null; 
    isArchiving.value = false; 
  }, 300);
};

const handleArchive = async () => {
  if (!truckToArchive.value) return;
  isArchiving.value = true;
  try {
    await router.put(route('admin.trucks.archive', truckToArchive.value.id), {}, { 
      preserveScroll: true 
    });
    closeArchiveModal();
    router.reload({ only: ['trucks'] });
  } catch {
    closeArchiveModal();
    alert('Failed to archive truck. Please try again.');
  } finally {
    isArchiving.value = false;
  }
};

// Navigation
const createTruck = () => router.get(route('admin.trucks.create'));
const viewTruck = (id) => router.get(route('admin.trucks.show', id));
const editTruck = (id) => router.get(route('admin.trucks.edit', id));
const viewArchived = () => router.get(route('admin.trucks.archived'));

// Watchers
watch([search, statusFilter, makeFilter, sortField, sortDirection], () => {
  router.get(route('admin.trucks.index'), {
    search: search.value,
    status: statusFilter.value,
    make: makeFilter.value,
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
:deep(.datatable-cell) {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
</style>