<template>
  <EmployeeLayout>
    <template #header>
      <div class="flex justify-between items-center w-full px-6 md:px-8">
        <!-- Left: Title & Subtitle -->
        <div>
          <h2 class="text-xl font-semibold leading-tight text-gray-800">Disabled Warehouses</h2>
          <p class="mt-1 text-sm text-gray-500">
            Manage disabled warehouses and restore or delete them permanently
          </p>
        </div>

        <!-- Right: Buttons -->
        <div class="flex gap-2">
          <SecondaryButton @click="backToActive">Back to Active Warehouses</SecondaryButton>
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

        <!-- Search Bar -->
        <div class="mb-4 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
          <div class="w-full sm:w-96">
            <SearchInput 
              v-model="search" 
              placeholder="Search disabled warehouses by name..." 
              class="w-full"
            />
          </div>
          <div class="text-sm text-gray-500 bg-blue-50 px-3 py-1 rounded border border-blue-100">
            📋 Showing {{ filteredRegions.length }} disabled {{ filteredRegions.length === 1 ? 'warehouse' : 'warehouses' }}
          </div>
        </div>

        <!-- Data Table Container with proper spacing -->
        <div class="justify-center flex items-center">
          <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg w-full max-w-[95vw]">
            <div class="p-4 bg-white border-b border-gray-200">
              <DataTable 
                :columns="columns" 
                :data="filteredRegions"
                :sort-field="sortField"
                :sort-direction="sortDirection"
                @sort="handleSort"
                class="w-full"
              >
                <template #name="{ row }">
                  <div class="flex items-center">
                    <div 
                      class="w-3 h-3 rounded-full mr-3 border border-gray-300" 
                      :style="{ backgroundColor: row.color_hex }"
                    ></div>
                    <span class="font-medium text-gray-900">{{ row.name }}</span>
                  </div>
                </template>
                <template #color_hex="{ row }">
                  <div class="flex items-center">
                    <div 
                      class="w-6 h-6 rounded border border-gray-300 mr-2" 
                      :style="{ backgroundColor: row.color_hex }"
                    ></div>
                    <span class="text-sm font-mono text-gray-600">{{ row.color_hex }}</span>
                  </div>
                </template>
                <template #warehouse_address="{ row }">
                  <div>
                    <span class="text-gray-700 line-clamp-2">{{ row.warehouse_address || 'No address provided' }}</span>
                  </div>
                </template>
                <template #geographic_location="{ row }">
                  <div class="flex items-center">
                    <MapPinIcon class="h-4 w-4 mr-2 text-gray-400" />
                    <span class="text-sm text-gray-600 font-mono">
                      <template v-if="typeof row.geographic_location === 'object'">
                        {{ row.geographic_location.latitude?.toFixed(4) }}, {{ row.geographic_location.longitude?.toFixed(4) }}
                      </template>
                      <template v-else>
                        {{ JSON.parse(row.geographic_location).latitude.toFixed(4) }}, {{ JSON.parse(row.geographic_location).longitude.toFixed(4) }}
                      </template>
                    </span>
                  </div>
                </template>
                <template #disabled_date="{ row }">
                  <span class="text-sm text-gray-600">
                    {{ new Date(row.updated_at).toLocaleDateString() }}
                  </span>
                </template>
                <template #actions="{ row }">
                  <div class="flex space-x-2">
                    <SecondaryButton @click="viewRegion(row.id)" class="text-xs py-1 px-2">View</SecondaryButton>
                    <PrimaryButton @click="restoreRegion(row.id)" class="text-xs py-1 px-2">Restore</PrimaryButton>
                    <DangerButton @click="confirmDelete(row)" class="text-xs py-1 px-2">Delete</DangerButton>
                  </div>
                </template>
                
                <!-- Empty State Slot -->
                <template #empty>
                  <div class="text-center py-8">
                    <div class="bg-gray-50 rounded-lg p-6 max-w-md mx-auto">
                      <ArchiveBoxIcon class="h-10 w-10 text-gray-400 mx-auto mb-3" />
                      <h3 class="text-lg font-medium text-gray-900 mb-2">No disabled warehouses found</h3>
                      <p class="text-gray-500 mb-3">
                        {{ search ? 'Try adjusting your search terms' : 'All warehouses are currently active' }}
                      </p>
                      <SecondaryButton @click="backToActive">View Active Warehouses</SecondaryButton>
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
        <h2 class="text-lg font-medium text-gray-900">Delete Warehouse Permanently?</h2>
        <p class="mt-1 text-sm text-gray-600">
          Are you sure you want to permanently delete <strong>{{ regionToDelete?.name }}</strong>? 
          This action cannot be undone and will remove all associated data.
        </p>
        <div class="mt-4 flex justify-end space-x-3">
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
import DataTable from '@/Components/DataTable.vue';
import Modal from '@/Components/Modal.vue';
import { MapPinIcon, ArchiveBoxIcon } from '@heroicons/vue/24/outline';
import { computed, ref } from 'vue';
import { router } from '@inertiajs/vue3';

const props = defineProps({
  regions: Array,
  status: String,
  success: String,
  error: String,
});

const search = ref('');
const sortField = ref('updated_at');
const sortDirection = ref('desc');
const showDeleteModal = ref(false);
const regionToDelete = ref(null);
const isProcessing = ref(false);

const columns = [
  { field: 'name', header: 'Warehouse Name', sortable: true },
  { field: 'color_hex', header: 'Region Color', sortable: false },
  { field: 'warehouse_address', header: 'Warehouse Address', sortable: false },
  { field: 'geographic_location', header: 'Coordinates', sortable: false },
  { field: 'disabled_date', header: 'Disabled Date', sortable: true },
  { field: 'actions', header: 'Actions', sortable: false }
];

const filteredRegions = computed(() => {
  return props.regions.filter(region => {
    const matchesSearch = search.value === '' || 
      region.name.toLowerCase().includes(search.value.toLowerCase()) ||
      (region.warehouse_address && region.warehouse_address.toLowerCase().includes(search.value.toLowerCase()));
    
    // Only show inactive (disabled) regions
    return matchesSearch && !region.is_active;
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
    onError: () => alert('Failed to delete warehouse'),
    onFinish: () => closeDeleteModal()
  });
}
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

/* Override DataTable's left padding if needed */
:deep(.datatable) {
  margin-left: 2rem;
}

:deep(.datatable-table) {
  width: 100%;
}

/* Further reduce table row padding for more compact rows */
:deep(.datatable-table td) {
  padding-top: 0.375rem !important;
  padding-bottom: 0.375rem !important;
}

/* Further reduce table header padding */
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