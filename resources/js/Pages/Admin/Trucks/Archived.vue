<template>
  <EmployeeLayout>
    <template #header>
      <div class="flex justify-between items-center px-6">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">Archived Trucks</h2>
        <div class="flex space-x-2">
          <SecondaryButton @click="viewActive">View Active Trucks</SecondaryButton>
        </div>
      </div>
    </template>

    <div class="px-6 sm:px-8">
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

      <div class="mb-6 flex flex-col md:flex-row justify-between items-start md:items-center gap-4 w-full mx-auto">
        <SearchInput 
          v-model="search" 
          placeholder="Search archived trucks..." 
          class="w-full md:w-72"
        />
        <div class="flex flex-wrap gap-2 w-full md:w-auto">
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
      <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg w-center">
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
                <span class="truncate block w-32">{{ row.license_plate || 'N/A' }}</span>
              </template>
              <template #make="{ row }">
                <span class="truncate block w-32">{{ row.make || 'N/A' }}</span>
              </template>
              <template #model="{ row }">
                <span class="truncate block w-32">{{ row.model || 'N/A' }}</span>
              </template>
              <template #status="{ row }">
                <span 
                  class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full capitalize"
                  :class="statusClasses[row.status]"
                >
                  {{ getStatusText(row.status) }}
                </span>
              </template>
              <template #archived_at="{ row }">
                {{ row.archived_at ? new Date(row.archived_at).toLocaleDateString() : 'N/A' }}
              </template>
              <template #actions="{ row }">
                <div class="flex space-x-2 min-w-[200px]">
                  <SecondaryButton @click="viewTruck(row.id)">View</SecondaryButton>
                  <PrimaryButton @click="openRestoreModal(row)">Restore</PrimaryButton>
                  <DangerButton @click="openDeleteModal(row)">Delete</DangerButton>
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
    </div>

    <!-- Restore Confirmation Modal -->
    <Modal :show="showRestoreModal" @close="closeRestoreModal">
      <div class="p-6">
        <h2 class="text-lg font-medium text-gray-900">Restore Truck?</h2>
        <p class="mt-1 text-sm text-gray-600">
          Are you sure you want to restore <strong>{{ truckToRestore?.license_plate }}</strong>?
        </p>
        <div class="mt-6 flex justify-end space-x-4">
          <SecondaryButton @click="closeRestoreModal">Cancel</SecondaryButton>
          <PrimaryButton @click="handleRestore" :disabled="isProcessing">
            <span v-if="isProcessing">Restoring...</span>
            <span v-else>Restore</span>
          </PrimaryButton>
        </div>
      </div>
    </Modal>

    <!-- Delete Confirmation Modal -->
    <Modal :show="showDeleteModal" @close="closeDeleteModal">
      <div class="p-6">
        <h2 class="text-lg font-medium text-gray-900">Permanently Delete Truck?</h2>
        <p class="mt-1 text-sm text-gray-600">
          Are you sure you want to permanently delete <strong>{{ truckToDelete?.license_plate }}</strong>?
          This action cannot be undone.
        </p>
        <div class="mt-6 flex justify-end space-x-4">
          <SecondaryButton @click="closeDeleteModal">Cancel</SecondaryButton>
          <DangerButton @click="handleDelete" :disabled="isProcessing">
            <span v-if="isProcessing">Deleting...</span>
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
import SelectInput from '@/Components/SelectInput.vue';
import DataTable from '@/Components/DataTable.vue';
import Modal from '@/Components/Modal.vue';
import { computed, ref, watch } from 'vue';
import { router, Link } from '@inertiajs/vue3';

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
const makeFilter = ref(props.trucks.filters?.make || '');
const sortField = ref(props.trucks.filters?.sort_field || 'archived_at');
const sortDirection = ref(props.trucks.filters?.sort_direction || 'desc');

// Constants
const statusOptions = [
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
  { field: 'license_plate', header: 'License Plate', sortable: true, width: '150px' },
  { field: 'make', header: 'Make', sortable: true, width: '150px' },
  { field: 'model', header: 'Model', sortable: true, width: '150px' },
  { field: 'region.name', header: 'Region', sortable: true, width: '150px' }, // <-- Added Region column
  { field: 'status', header: 'Status', sortable: true, width: '120px' },
  { field: 'archived_at', header: 'Archived Date', sortable: true, width: '150px' },
  { field: 'actions', header: 'Actions', sortable: false, width: '250px' }
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
watch([search, makeFilter, sortField, sortDirection], () => {
  router.get(route('admin.trucks.archived'), {
    search: search.value,
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