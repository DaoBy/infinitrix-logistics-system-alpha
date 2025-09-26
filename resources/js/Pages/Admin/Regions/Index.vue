<template>
  <EmployeeLayout>
    <template #header>
      <div class="flex justify-between items-center w-full px-6 md:px-8">
        <!-- Left: Title & Subtitle -->
        <div>
          <h2 class="text-xl font-semibold leading-tight text-gray-800">Warehouse Management</h2>
          <p class="mt-1 text-sm text-gray-500">
            Manage active warehouses and their regional assignments
          </p>
        </div>

        <!-- Right: Buttons -->
        <div class="flex gap-2">
          <SecondaryButton @click="viewArchived">View Disabled Warehouses</SecondaryButton>
          <PrimaryButton @click="createRegion">Add New Warehouse</PrimaryButton>
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
              placeholder="Search active warehouses by name..." 
              class="w-full"
            />
          </div>
          <div class="text-sm text-gray-500 bg-blue-50 px-3 py-1 rounded border border-blue-100">
            📋 Showing {{ filteredRegions.length }} active {{ filteredRegions.length === 1 ? 'warehouse' : 'warehouses' }}
            <span v-if="regions.data && regions.data.length < regions.total" class="ml-1">
              (Page {{ regions.current_page }} of {{ regions.last_page }})
            </span>
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
                <template #actions="{ row }">
                  <div class="flex space-x-2">
                    <SecondaryButton @click="viewRegion(row.id)" class="text-xs py-1 px-2">View</SecondaryButton>
                    <PrimaryButton @click="editRegion(row.id)" class="text-xs py-1 px-2">Edit</PrimaryButton>
                    <DangerButton @click="confirmArchive(row)" class="text-xs py-1 px-2">Disable</DangerButton>
                  </div>
                </template>
                
                <!-- Empty State Slot -->
                <template #empty>
                  <div class="text-center py-8">
                    <div class="bg-gray-50 rounded-lg p-6 max-w-md mx-auto">
                      <MapPinIcon class="h-10 w-10 text-gray-400 mx-auto mb-3" />
                      <h3 class="text-lg font-medium text-gray-900 mb-2">No active warehouses found</h3>
                      <p class="text-gray-500 mb-3">
                        {{ search ? 'Try adjusting your search terms' : 'Get started by adding your first warehouse' }}
                      </p>
                      <PrimaryButton @click="createRegion">Add New Warehouse</PrimaryButton>
                    </div>
                  </div>
                </template>
              </DataTable>

              <!-- Pagination Component -->
              <div class="mt-4">
                <Pagination 
                  :pagination="regions" 
                  @page-changed="handlePageChange" 
                />
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Archive Confirmation Modal -->
    <Modal :show="showArchiveModal" @close="closeArchiveModal">
      <div class="p-5">
        <h2 class="text-lg font-medium text-gray-900">Disable Warehouse?</h2>
        <p class="mt-1 text-sm text-gray-600">
          Are you sure you want to disable <strong>{{ regionToArchive?.name }}</strong>? 
          This will move it to the disabled warehouses list.
        </p>
        <div class="mt-4 flex justify-end space-x-3">
          <SecondaryButton @click="closeArchiveModal">Cancel</SecondaryButton>
          <DangerButton @click="archiveRegion" :disabled="isProcessing">
            <span v-if="isProcessing">Processing...</span>
            <span v-else>Disable Warehouse</span>
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
import Pagination from '@/Components/Pagination.vue';
import { MapPinIcon } from '@heroicons/vue/24/outline';
import { computed, onMounted, ref } from 'vue';
import { router } from '@inertiajs/vue3';

const props = defineProps({
  regions: Object,
  status: String,
  success: String,
  error: String,
});

const search = ref('');
const sortField = ref('name');
const sortDirection = ref('asc');
const showArchiveModal = ref(false);
const regionToArchive = ref(null);
const isProcessing = ref(false);

const columns = [
  { field: 'name', header: 'Warehouse Name', sortable: true },
  { field: 'color_hex', header: 'Region Color', sortable: false },
  { field: 'warehouse_address', header: 'Warehouse Address', sortable: false },
  { field: 'geographic_location', header: 'Coordinates', sortable: false },
  { field: 'actions', header: 'Actions', sortable: false }
];

// Update filteredRegions to work with paginated data
const filteredRegions = computed(() => {
  const regionsData = props.regions.data || [];
  
  return regionsData.filter(region => {
    const matchesSearch = search.value === '' || 
      region.name.toLowerCase().includes(search.value.toLowerCase()) ||
      (region.warehouse_address && region.warehouse_address.toLowerCase().includes(search.value.toLowerCase()));
    
    // Only show active regions
    return matchesSearch && region.is_active;
  }).sort((a, b) => {
    const modifier = sortDirection.value === 'asc' ? 1 : -1;
    
    let aValue = a[sortField.value];
    let bValue = b[sortField.value];
    
    // Handle null/undefined values
    if (aValue == null) aValue = '';
    if (bValue == null) bValue = '';
    
    // Convert to string for case-insensitive comparison
    aValue = String(aValue).toLowerCase();
    bValue = String(bValue).toLowerCase();

    if (aValue < bValue) return -1 * modifier;
    if (aValue > bValue) return 1 * modifier;
    return 0;
  });
});

// Fixed handleSort function to properly handle the sort event
function handleSort(sortParams) {
  // The DataTable emits an object with field and direction properties
  sortField.value = sortParams.field;
  sortDirection.value = sortParams.direction;
}

function handlePageChange(page) {
  router.get(route('admin.regions.index'), { page: page }, {
    preserveState: true,
    preserveScroll: true,
    replace: true
  });
}

function createRegion() {
  router.get(route('admin.regions.create'));
}

function viewRegion(id) {
  router.get(route('admin.regions.show', id));
}

function editRegion(id) {
  router.get(route('admin.regions.edit', id));
}

function viewArchived() {
  router.get(route('admin.regions.archived'));
}

function confirmArchive(region) {
  regionToArchive.value = region;
  showArchiveModal.value = true;
}

function closeArchiveModal() {
  showArchiveModal.value = false;
  regionToArchive.value = null;
  isProcessing.value = false;
}

function archiveRegion() {
  if (!regionToArchive.value) return;
  
  isProcessing.value = true;
  router.put(route('admin.regions.archive', regionToArchive.value.id), {}, {
    preserveScroll: true,
    onSuccess: () => router.reload({ only: ['regions'] }),
    onError: () => alert('Failed to disable warehouse'),
    onFinish: () => closeArchiveModal()
  });
}

onMounted(() => {
  if (typeof $page !== 'undefined') {
    console.log('Vue got key:', $page.props.googleMapsApiKey);
  }
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