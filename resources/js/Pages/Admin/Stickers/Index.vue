<template>
  <EmployeeLayout>
    <template #header>
      <div class="flex justify-between items-center">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">Sticker Management</h2>
        <div class="flex space-x-2">
          <PrimaryButton @click="fetchStatistics" v-if="!showStatistics">
            View Statistics
          </PrimaryButton>
          <SecondaryButton @click="showStatistics = false" v-else>
            Hide Statistics
          </SecondaryButton>
        </div>
      </div>
    </template>

    <div class="px-6 sm:px-8">
      <!-- Status Messages -->
      <div v-if="status || success || error" class="mb-6">
        <div v-if="status" class="p-4 bg-blue-100 text-blue-800 rounded">{{ status }}</div>
        <div v-if="success" class="p-4 bg-green-100 text-green-800 rounded">{{ success }}</div>
        <div v-if="error" class="p-4 bg-red-100 text-red-800 rounded">{{ error }}</div>
      </div>

      <!-- Statistics Panel -->
      <div v-if="showStatistics && statistics" class="mb-6 bg-white p-6 rounded-lg shadow-sm">
        <h3 class="text-lg font-semibold mb-4">Sticker Statistics</h3>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
          <div class="bg-blue-50 p-4 rounded-lg text-center">
            <div class="text-2xl font-bold text-blue-600">{{ statistics.total_packages }}</div>
            <div class="text-sm text-blue-800">Total Packages</div>
          </div>
          <div class="bg-green-50 p-4 rounded-lg text-center">
            <div class="text-2xl font-bold text-green-600">{{ statistics.printed_packages }}</div>
            <div class="text-sm text-green-800">Printed</div>
          </div>
          <div class="bg-yellow-50 p-4 rounded-lg text-center">
            <div class="text-2xl font-bold text-yellow-600">{{ statistics.not_printed_packages }}</div>
            <div class="text-sm text-yellow-800">Not Printed</div>
          </div>
          <div class="bg-purple-50 p-4 rounded-lg text-center">
            <div class="text-2xl font-bold text-purple-600">{{ statistics.print_rate }}%</div>
            <div class="text-sm text-purple-800">Print Rate</div>
          </div>
        </div>

        <h4 class="text-md font-semibold mb-3">Packages by Region</h4>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
          <div v-for="region in statistics.packages_by_region" :key="region.region_name" 
               class="flex items-center justify-between p-3 border rounded-lg">
            <div class="flex items-center">
              <div class="w-4 h-4 rounded-full mr-2 border" :style="{ backgroundColor: region.color_hex }"></div>
              <span class="text-sm">{{ region.region_name }}</span>
            </div>
            <span class="text-sm font-semibold">{{ region.package_count }}</span>
          </div>
        </div>
      </div>

      <!-- Filters -->
      <div class="mb-6 bg-white p-6 rounded-lg shadow-sm">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
          <div>
            <InputLabel for="search" value="Search Packages" />
            <TextInput
              id="search"
              v-model="filters.search"
              placeholder="Package ID, Item, Waybill, Receiver..."
              class="w-full mt-1"
              @input="debouncedSearch"
            />
          </div>

          <div>
            <InputLabel for="sticker_status" value="Sticker Status" />
            <select
              id="sticker_status"
              v-model="filters.sticker_status"
              class="w-full mt-1 border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
              @change="updateFilters"
            >
              <option value="all">All Status</option>
              <option value="not_printed">Not Printed</option>
              <option value="printed">Printed</option>
            </select>
          </div>

          <div>
            <InputLabel for="region_id" value="Destination Region" />
            <select
              id="region_id"
              v-model="filters.region_id"
              class="w-full mt-1 border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
              @change="updateFilters"
            >
              <option value="">All Regions</option>
              <option v-for="region in regions" :key="region.id" :value="region.id">
                {{ region.name }}
              </option>
            </select>
          </div>

          <div>
            <InputLabel for="per_page" value="Items per page" />
            <select
              id="per_page"
              v-model="filters.per_page"
              class="w-full mt-1 border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
              @change="updateFilters"
            >
              <option value="10">10 items</option>
              <option value="15">15 items</option>
              <option value="25">25 items</option>
              <option value="50">50 items</option>
            </select>
          </div>
        </div>

        <div class="flex justify-between items-center mt-4">
          <div class="text-sm text-gray-600">
            Showing {{ packages.from }} to {{ packages.to }} of {{ packages.total }} results
          </div>
          <div class="flex space-x-2">
            <SecondaryButton @click="clearFilters">Clear Filters</SecondaryButton>
            <PrimaryButton @click="applyFilters" :disabled="loading">
              Apply Filters
            </PrimaryButton>
          </div>
        </div>
      </div>

      <!-- Bulk Actions -->
      <div v-if="selectedPackages.length > 0" class="mb-4 bg-yellow-50 p-4 rounded-lg">
        <div class="flex items-center justify-between">
          <span class="text-yellow-800">
            {{ selectedPackages.length }} package(s) selected
          </span>
          <PrimaryButton @click="bulkPrint" :disabled="loading">
            Print Selected
          </PrimaryButton>
        </div>
      </div>

      <!-- Packages Table -->
      <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
        <div class="p-6 bg-white border-b border-gray-200">
          <DataTable
            :columns="columns"
            :data="packages.data"
            :sort-field="sortField"
            :sort-direction="sortDirection"
            @sort="handleSort"
            class="w-full"
            selectable
            @selection-change="handleSelectionChange"
          >
            <!-- Remove checkbox from row template and simplify -->
         <template #row="{ row, index }">
            <tr 
                :class="[
                'cursor-pointer transition-colors',
                selectedPackages.includes(row.id) 
                    ? 'bg-indigo-50 hover:bg-indigo-100' 
                    : index % 2 === 0 
                    ? 'bg-white hover:bg-gray-50' 
                    : 'bg-gray-50 hover:bg-gray-100'
                ]"
            >
                <td 
                v-for="column in columns" 
                :key="column.field"
                class="px-4 py-3 whitespace-nowrap"
                @click="column.field === 'actions' ? null : toggleRowSelection(row.id)"
                >
                <slot :name="column.field" :row="row"></slot>
                </td>
            </tr>
            </template>

            <template #item_code="{ row }">
              <span class="font-mono text-sm">{{ row.item_code }}</span>
            </template>

            <template #item_name="{ row }">
              <div class="flex items-center">
                <span class="truncate max-w-xs">{{ row.item_name }}</span>
              </div>
            </template>

            <template #destination_region="{ row }">
              <div class="flex items-center">
                <div
                  class="w-4 h-4 rounded-full mr-2 border border-gray-300"
                  :style="{ backgroundColor: row.destination_region.color_hex }"
                ></div>
                <span>{{ row.destination_region.name }}</span>
              </div>
            </template>

            <template #receiver="{ row }">
              <div class="text-sm">
                <div class="font-medium">{{ row.receiver.name }}</div>
                <div class="text-gray-500 text-xs truncate max-w-xs">
                  {{ row.receiver.address }}
                </div>
              </div>
            </template>

            <template #waybill_number="{ row }">
              <span class="text-sm text-gray-900">
                {{ row.waybill?.waybill_number || 'N/A' }}
              </span>
            </template>

            <template #sticker_status="{ row }">
              <span
                :class="[
                  row.sticker_printed_at
                    ? 'bg-green-100 text-green-800'
                    : 'bg-yellow-100 text-yellow-800'
                ]"
                class="px-2 py-1 text-xs font-medium rounded-full"
              >
                {{ row.sticker_printed_at ? 'Printed' : 'Not Printed' }}
              </span>
            </template>

            <template #sticker_printed_at="{ row }">
              <div v-if="row.sticker_printed_at" class="text-sm">
                <div>{{ formatDate(row.sticker_printed_at) }}</div>
                <div class="text-gray-500 text-xs">
                  by {{ row.sticker_printed_by || 'System' }}
                </div>
              </div>
              <span v-else class="text-gray-400">-</span>
            </template>

  <template #actions="{ row }">
  <div class="flex space-x-2 justify-end" @click.stop>
    <PrimaryButton
      @click="printSticker(row)"
      :disabled="loading"
      class="text-xs py-1 px-2 min-w-[60px]"
    >
      Print
    </PrimaryButton>
    
    <SecondaryButton
      v-if="row.sticker_printed_at"
      @click="resetSticker(row)"
      :disabled="loading"
      class="text-xs py-1 px-2 min-w-[60px]"
    >
      Reset
    </SecondaryButton>
  </div>
</template>
          </DataTable>

          <!-- Pagination -->
          <Pagination :links="packages.links" class="mt-6" />
        </div>
      </div>
    </div>

   <!-- Loading Overlay (Commented out for now) -->
<!--
<div v-if="loading" class="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center z-50">
  <div class="bg-white p-6 rounded-lg shadow-lg">
    <div class="flex items-center">
      <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-indigo-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
      </svg>
      <span>Loading...</span>
    </div>
  </div>
</div>
-->
  </EmployeeLayout>
</template>

<script setup>
import EmployeeLayout from '@/Layouts/EmployeeLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import DataTable from '@/Components/DataTable.vue';
import Pagination from '@/Components/Pagination.vue';
import { Link, router } from '@inertiajs/vue3';
import { ref, computed, watch, onMounted } from 'vue';
import { debounce } from 'lodash';

const props = defineProps({
  packages: Object,
  regions: Array,
  filters: Object,
  status: String,
  success: String,
  error: String,
});

const loading = ref(false);
const showStatistics = ref(false);
const statistics = ref(null);
const selectedPackages = ref([]);
const sortField = ref('created_at');
const sortDirection = ref('desc');

const filters = ref({
  search: props.filters.search || '',
  sticker_status: props.filters.sticker_status || 'not_printed',
  region_id: props.filters.region_id || '',
  per_page: props.filters.per_page || 15,
});

const columns = [
  { field: 'selection', header: '', sortable: false, width: 'w-12' },
  { field: 'item_code', header: 'Package ID', sortable: true },
  { field: 'item_name', header: 'Item Name', sortable: true },
  { field: 'destination_region', header: 'Destination', sortable: true },
  { field: 'receiver', header: 'Receiver', sortable: false },
  { field: 'waybill_number', header: 'Waybill', sortable: true },
  { field: 'sticker_status', header: 'Status', sortable: true },
  { field: 'sticker_printed_at', header: 'Printed At', sortable: true },
  { field: 'actions', header: 'Actions', sortable: false, width: 'w-48' },
];

// Debounced search
const debouncedSearch = debounce(() => {
  updateFilters();
}, 500);

function updateFilters() {
  router.get(route('stickers.index'), filters.value, {
    preserveState: true,
    preserveScroll: true,
    replace: true,
    onStart: () => loading.value = true,
    onFinish: () => loading.value = false,
  });
}

function applyFilters() {
  updateFilters();
}

function clearFilters() {
  filters.value = {
    search: '',
    sticker_status: 'not_printed',
    region_id: '',
    per_page: 15,
  };
  updateFilters();
}

function handleSort(field) {
  if (sortField.value === field) {
    sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc';
  } else {
    sortField.value = field;
    sortDirection.value = 'asc';
  }
  // You can implement sorting here if needed
}

async function fetchStatistics() {
  try {
    const response = await fetch(route('stickers.statistics'));
    statistics.value = await response.json();
    showStatistics.value = true;
  } catch (error) {
    console.error('Failed to fetch statistics:', error);
  }
}

async function printSticker(pkg) {
  loading.value = true;
  
  try {
    // Use bulk print endpoint even for single package
    const queryString = `package_ids[]=${pkg.id}`;
    
    // Open PDF in new tab using bulk print endpoint
    const printWindow = window.open(`${route('stickers.bulk-print')}?${queryString}`, '_blank');
    
    // Check if window opened successfully
    if (printWindow) {
      printWindow.focus();
      
      // Wait for the print operation to complete, then refresh data
      setTimeout(() => {
        // Refresh the page data from the server
        router.reload({ 
          only: ['packages'],
          preserveState: true,
          preserveScroll: true,
          onFinish: () => {
            loading.value = false;
          }
        });
      }, 1500); // Adjust timing as needed
    } else {
      // Fallback: redirect to bulk print URL
      window.location.href = `${route('stickers.bulk-print')}?${queryString}`;
      loading.value = false;
    }
  } catch (error) {
    console.error('Print error:', error);
    loading.value = false;
  }
}

async function bulkPrint() {
  if (selectedPackages.value.length === 0) return;
  
  loading.value = true;
  
  try {
    // Create a query string with the package IDs
    const queryString = selectedPackages.value.map(id => `package_ids[]=${id}`).join('&');
    
    // Open the bulk print URL in a new tab with the package IDs as query parameters
    const printWindow = window.open(`${route('stickers.bulk-print')}?${queryString}`, '_blank');
    
    // Check if window opened successfully
    if (printWindow) {
      printWindow.focus();
      
      // Wait for the print operation to complete, then refresh data and clear selection
      setTimeout(() => {
        // Refresh the page data from the server
        router.reload({ 
          only: ['packages'],
          preserveState: true,
          preserveScroll: true,
          onSuccess: () => {
            selectedPackages.value = [];
            loading.value = false;
          }
        });
      }, 1500);
    } else {
      // Fallback: redirect to the bulk print URL
      window.location.href = `${route('stickers.bulk-print')}?${queryString}`;
      loading.value = false;
      selectedPackages.value = [];
    }
  } catch (error) {
    console.error('Bulk print error:', error);
    loading.value = false;
  }
}

function resetSticker(pkg) {
  if (!confirm('Are you sure you want to reset this sticker? This will allow it to be printed again.')) {
    return;
  }
  
  loading.value = true;
  router.post(route('stickers.reset', pkg.id), {}, {
    preserveScroll: true,
    onSuccess: () => {
      // Refresh the page to update the status
      router.reload({ only: ['packages'] });
    },
    onFinish: () => loading.value = false,
  });
}

function toggleRowSelection(id) {
  const index = selectedPackages.value.indexOf(id);
  if (index === -1) {
    selectedPackages.value.push(id);
  } else {
    selectedPackages.value.splice(index, 1);
  }
}

function viewPackage(row) {
  router.visit(route('admin.packages.show', row.id));
}

function formatDate(dateString) {
  return new Date(dateString).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  });
}

// Add this function to handle selection changes
const handleSelectionChange = (selectedRows) => {
  selectedPackages.value = selectedRows.map(row => row.id);
}

// Watch for changes in filters and update URL
watch(filters, (newFilters) => {
  const queryParams = new URLSearchParams();
  Object.entries(newFilters).forEach(([key, value]) => {
    if (value) queryParams.set(key, value);
  });
  window.history.replaceState({}, '', `${window.location.pathname}?${queryParams}`);
}, { deep: true });

onMounted(() => {
  // Initialize filters from URL
  const urlParams = new URLSearchParams(window.location.search);
  Object.keys(filters.value).forEach(key => {
    if (urlParams.has(key)) {
      filters.value[key] = urlParams.get(key);
    }
  });
});
</script>

<style scoped>
:deep(.datatable-cell) {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

:deep(.datatable-row) {
  cursor: pointer;
}

:deep(.datatable-row:hover) {
  background-color: #f9fafb;
}

:deep(.datatable-row.selected) {
  background-color: #e0e7ff;
}
</style>