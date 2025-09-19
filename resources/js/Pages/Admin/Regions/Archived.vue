<template>
  <EmployeeLayout>
    <template #header>
      <div class="flex justify-between items-center px-6">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">Archived Regions</h2>
        <div class="flex space-x-2">
          <SecondaryButton @click="backToActive">Back to Active Regions</SecondaryButton>
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
          placeholder="Search archived regions..." 
          class="w-full md:w-72"
        />
        <div class="flex flex-wrap gap-2 w-full md:w-auto">
          <SelectInput 
            v-model="statusFilter" 
            :options="statusOptions" 
            option-value="value"
            option-label="text"
            placeholder="Filter by status"
            class="w-full md:w-48"
          />
        </div>
      </div>

       <div class="justify-center flex items-center">
      <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg w-center">
        <div class="p-5 bg-white border-b border-gray-200">
            <DataTable 
              :columns="columns" 
              :data="filteredRegions"
              :sort-field="sortField"
              :sort-direction="sortDirection"
              @sort="handleSort"
              class="w-full"
            >
              <template #name="{ row }">
                <span class="truncate block">{{ row.name }}</span>
              </template>
              <template #color_hex="{ row }">
                <div class="flex items-center">
                  <div 
                    class="w-4 h-4 rounded-full mr-2 border border-gray-300" 
                    :style="{ backgroundColor: row.color_hex }"
                  ></div>
                  <span class="text-sm">{{ row.color_hex }}</span>
                </div>
              </template>
              <template #warehouse_address="{ row }">
                <span class="truncate block">{{ row.warehouse_address || 'N/A' }}</span>
              </template>
              <template #status="{ row }">
                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                  Archived
                </span>
              </template>
              <template #geographic_location="{ row }">
                <div class="flex items-center">
                  <MapPinIcon class="h-4 w-4 mr-1 text-gray-400" />
                  <span v-if="typeof row.geographic_location === 'object'">
                    {{ row.geographic_location.latitude?.toFixed(4) }}, {{ row.geographic_location.longitude?.toFixed(4) }}
                  </span>
                  <span v-else>
                    {{ JSON.parse(row.geographic_location).latitude.toFixed(4) }}, {{ JSON.parse(row.geographic_location).longitude.toFixed(4) }}
                  </span>
                </div>
              </template>
              <template #updated_at="{ row }">
                {{ new Date(row.updated_at).toLocaleDateString() }}
              </template>
              <template #actions="{ row }">
                <div class="flex space-x-2 min-w-[200px]">
                  <SecondaryButton @click="viewRegion(row.id)">View</SecondaryButton>
                  <PrimaryButton @click="restoreRegion(row.id)">Restore</PrimaryButton>
                  <DangerButton @click="confirmDelete(row)">Delete</DangerButton>
                </div>
              </template>
            </DataTable>
          </div>
        </div>
      </div>
    </div>

    <Modal :show="showDeleteModal" @close="closeDeleteModal">
      <div class="p-6">
        <h2 class="text-lg font-medium text-gray-900">Delete Region Permanently?</h2>
        <p class="mt-1 text-sm text-gray-600">
          Are you sure you want to permanently delete <strong>{{ regionToDelete?.name }}</strong>?
          This action cannot be undone.
        </p>
        <div class="mt-6 flex justify-end space-x-4">
          <SecondaryButton @click="closeDeleteModal">Cancel</SecondaryButton>
          <DangerButton @click="deleteRegion" :disabled="isProcessing">
            <span v-if="isProcessing">Deleting...</span>
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
import SelectInput from '@/Components/SelectInput.vue';
import DataTable from '@/Components/DataTable.vue';
import Modal from '@/Components/Modal.vue';
import { MapPinIcon } from '@heroicons/vue/24/outline';
import { computed, ref } from 'vue';
import { router } from '@inertiajs/vue3';

const props = defineProps({
  regions: Array,
  status: String,
  success: String,
  error: String,
});

const search = ref('');
const statusFilter = ref('');
const sortField = ref('updated_at');
const sortDirection = ref('desc');
const showDeleteModal = ref(false);
const regionToDelete = ref(null);
const isProcessing = ref(false);

const statusOptions = [
  { value: '', text: 'All Status' },
  { value: 'active', text: 'Active' },
  { value: 'inactive', text: 'Inactive' }
];

const columns = [
  { field: 'name', header: 'Name', sortable: true },
  { field: 'color_hex', header: 'Color', sortable: false },
  { field: 'warehouse_address', header: 'Warehouse Address', sortable: false },
  { field: 'status', header: 'Status', sortable: true },
  { field: 'updated_at', header: 'Archived Date', sortable: true },
  { field: 'actions', header: 'Actions', sortable: false }
];

const filteredRegions = computed(() => {
  return props.regions.filter(region => {
    const matchesSearch = search.value === '' || 
      region.name.toLowerCase().includes(search.value.toLowerCase());
    const matchesStatus = statusFilter.value === '' || 
      (statusFilter.value === 'active' ? region.is_active : !region.is_active);
    return matchesSearch && matchesStatus;
  }).sort((a, b) => {
    const modifier = sortDirection.value === 'asc' ? 1 : -1;
    return a[sortField.value] < b[sortField.value] ? -1 * modifier : 1 * modifier;
  });
});

function handleSort(field) {
  if (sortField.value === field) {
    sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc';
  } else {
    sortField.value = field;
    sortDirection.value = 'asc';
  }
}

function backToActive() {
  router.get(route('admin.regions.index'));
}

function viewRegion(id) {
  router.get(route('admin.regions.show', id));
}

function restoreRegion(id) {
  router.put(route('admin.regions.restore', id), {
    preserveScroll: true,
    onSuccess: () => router.reload()
  });
}

function confirmDelete(region) {
  regionToDelete.value = region;
  showDeleteModal.value = true;
}

function closeDeleteModal() {
  showDeleteModal.value = false;
  regionToDelete.value = null;
  isProcessing.value = false;
}

function deleteRegion() {
  if (!regionToDelete.value) return;
  
  isProcessing.value = true;
  router.delete(route('admin.regions.destroy', regionToDelete.value.id), {
    preserveScroll: true,
    onSuccess: () => router.reload(),
    onError: () => alert('Failed to delete region'),
    onFinish: () => closeDeleteModal()
  });
}
</script>

<style scoped>
:deep(.datatable-cell) {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
</style>