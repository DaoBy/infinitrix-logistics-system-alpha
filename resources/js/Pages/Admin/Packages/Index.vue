<template>
  <EmployeeLayout>
    <template #header>
      <div class="flex justify-between items-center w-full px-6 md:px-8">
        <!-- Left: Title & Subtitle -->
        <div>
          <h2 class="text-xl font-semibold leading-tight text-gray-800">Package Monitoring</h2>
          <p class="mt-1 text-sm text-gray-500">
            Track and manage package status and locations
          </p>
        </div>

        <!-- Right: Buttons -->
        <div class="flex gap-2">
          <SecondaryButton @click="refreshData">Refresh Data</SecondaryButton>
          <PrimaryButton 
            v-if="$page.props.auth.user.is_admin" 
            @click="showStatusFilters = !showStatusFilters"
          >
            {{ showStatusFilters ? 'Hide Tools' : 'Status Tools' }}
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

        <!-- Bulk Status Actions -->
        <div v-if="showStatusFilters && $page.props.auth.user.is_admin" class="bg-white shadow-sm rounded-lg overflow-hidden border border-gray-200 mb-4">
          <div class="p-4 bg-blue-50 border-b border-gray-200">
            <h3 class="font-medium text-gray-900 flex items-center">
              Bulk Status Actions
            </h3>
          </div>
          <div class="p-4">
            <div class="flex flex-wrap gap-2">
              <button
                v-for="status in availableStatuses"
                :key="status.value"
                @click="applyBulkStatus(status.value)"
                class="px-3 py-1.5 text-sm rounded-md"
                :class="statusButtonClass(status.value)"
              >
                Mark as {{ status.title }}
              </button>
            </div>
          </div>
        </div>

        <!-- Search and Filters - EXACT SAME STRUCTURE AS EMPLOYEE MANAGEMENT -->
        <div class="mb-4 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
          <div class="w-full sm:w-96">
            <SearchInput 
              v-model="search" 
              placeholder="Search packages by item code, name, or waybill..." 
              class="w-full"
            />
          </div>
          <div class="flex items-center gap-3">
            <SelectInput 
              v-model="categoryFilter" 
              :options="categoryOptions" 
              option-value="value"
              option-label="text"
              placeholder="All Categories"
              class="w-full sm:w-48"
            />
            <SelectInput 
              v-model="statusFilter" 
              :options="statusOptions" 
              option-value="value"
              option-label="text"
              placeholder="All Statuses"
              class="w-full sm:w-48"
            />
            <div class="text-sm text-gray-500 bg-blue-50 px-3 py-1 rounded border border-blue-100 whitespace-nowrap">
              📦 Showing {{ filteredPackages.length }} {{ filteredPackages.length === 1 ? 'package' : 'packages' }}
              <span v-if="packages.data && packages.data.length < packages.total" class="ml-1">
                (Page {{ packages.current_page }} of {{ packages.last_page }})
              </span>
            </div>
          </div>
        </div>

        <!-- Data Table Container with proper spacing -->
        <div class="justify-center flex items-center">
          <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg w-full max-w-[95vw]">
            <div class="p-4 bg-white border-b border-gray-200">
              <DataTable 
                :columns="columns" 
                :data="filteredPackages"
                :sort-field="sortField"
                :sort-direction="sortDirection"
                @sort="handleSort"
                class="w-full"
              >
                <template #item_code="{ row }">
                  <div class="font-medium text-gray-900">{{ row.item_code }}</div>
                </template>

                <template #item_name="{ row }">
                  <div class="text-gray-900">{{ row.item_name }}</div>
                </template>

                <template #category="{ row }">
                  <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full" :class="categoryBadgeClass(row.category)">
                    {{ formatCategory(row.category) }}
                  </span>
                </template>

                <template #status="{ row }">
                  <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full" :class="statusBadgeClass(row.status)">
                    {{ statusLabels[row.status] || 'N/A' }}
                  </span>
                </template>

                <template #current_region="{ row }">
                  <div class="flex items-center">
                    <div 
                      v-if="row.current_region && row.current_region.color_hex"
                      class="w-3 h-3 rounded-full mr-2 border border-gray-300" 
                      :style="{ backgroundColor: row.current_region.color_hex }"
                    ></div>
                    <span class="text-gray-700">{{ row.current_region?.name || 'N/A' }}</span>
                  </div>
                </template>

                <template #actions="{ row }">
                  <div class="flex space-x-2">
                    <SecondaryButton @click="viewPackage(row.id)" class="text-xs py-1 px-2">View</SecondaryButton>
                    <PrimaryButton 
                      v-if="$page.props.auth.user.is_admin" 
                      @click="openStatusModal(row)" 
                      class="text-xs py-1 px-2"
                    >
                      Update Status
                    </PrimaryButton>
                  </div>
                </template>
                
                <!-- Empty State Slot -->
                <template #empty>
                  <div class="text-center py-8">
                    <div class="bg-gray-50 rounded-lg p-6 max-w-md mx-auto">
                      <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-gray-400 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                      </svg>
                      <h3 class="text-lg font-medium text-gray-900 mb-2">No packages found</h3>
                      <p class="text-gray-500 mb-3">
                        {{ search ? 'Try adjusting your search terms' : 'No packages are currently being tracked' }}
                      </p>
                    </div>
                  </div>
                </template>
              </DataTable>

              <!-- Pagination Component -->
              <div class="mt-4" v-if="packages && packages.links && packages.links.length > 3">
                <Pagination 
                  :pagination="packages" 
                  @page-changed="handlePageChange" 
                />
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Status Update Modal -->
    <Modal :show="showStatusModal" @close="closeStatusModal" max-width="md">
      <div class="p-5">
        <h2 class="text-lg font-medium text-gray-900 mb-4">Update Package Status</h2>
        
        <div class="space-y-4">
          <div>
            <label for="status" class="block text-sm font-medium text-gray-700">New Status *</label>
            <select
              id="status"
              v-model="statusForm.status"
              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
              required
            >
              <option disabled value="">Select a status</option>
              <option v-for="status in availableStatuses" :key="status.value" :value="status.value">
                {{ status.title }}
              </option>
            </select>
          </div>

          <div>
            <label for="remarks" class="block text-sm font-medium text-gray-700">Remarks</label>
            <textarea
              id="remarks"
              rows="3"
              v-model="statusForm.remarks"
              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
              placeholder="Add any remarks about the status update..."
            ></textarea>
          </div>
        </div>

        <div class="mt-6 flex justify-end space-x-3">
          <SecondaryButton @click="closeStatusModal">
            Cancel
          </SecondaryButton>
          <PrimaryButton @click="submitStatusUpdate" :disabled="statusForm.processing">
            <span v-if="statusForm.processing">Updating...</span>
            <span v-else>Update Status</span>
          </PrimaryButton>
        </div>
      </div>
    </Modal>
  </EmployeeLayout>
</template>

<script setup>
import EmployeeLayout from '@/Layouts/EmployeeLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import SearchInput from '@/Components/SearchInput.vue';
import SelectInput from '@/Components/SelectInput.vue';
import DataTable from '@/Components/DataTable.vue';
import Modal from '@/Components/Modal.vue';
import Pagination from '@/Components/Pagination.vue';
import { useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { router } from '@inertiajs/vue3';

const props = defineProps({
  packages: Object,
  status: String,
  success: String,
  error: String,
});

const search = ref('');
const categoryFilter = ref('');
const statusFilter = ref('');
const sortField = ref('item_code');
const sortDirection = ref('asc');
const showStatusFilters = ref(false);
const showStatusModal = ref(false);
const currentPackage = ref(null);

const columns = [
  { field: 'item_code', header: 'Item Code', sortable: true },
  { field: 'item_name', header: 'Item Name', sortable: true },
  { field: 'category', header: 'Category', sortable: true },
  { field: 'status', header: 'Status', sortable: true },
  { field: 'current_region', header: 'Current Location', sortable: true },
  { field: 'actions', header: 'Actions', sortable: false }
];

// UPDATED: Correct categories from container presets
const categoryOptions = [
  { value: '', text: 'All Categories' },
  { value: 'piece', text: 'Piece' },
  { value: 'carton', text: 'Carton' },
  { value: 'sack', text: 'Sack' },
  { value: 'roll', text: 'Roll' },
  { value: 'B/R', text: 'Bundle/Roll' },
  { value: 'C/S', text: 'Custom Size' },
];

// COMPLETE status options from Package model
const statusOptions = [
  { value: '', text: 'All Statuses' },
  { value: 'preparing', text: 'Preparing' },
  { value: 'ready_for_pickup', text: 'Ready for Pickup' },
  { value: 'loaded', text: 'Loaded' },
  { value: 'in_transit', text: 'In Transit' },
  { value: 'delivered', text: 'Delivered' },
  { value: 'completed', text: 'Completed' },
  { value: 'returned', text: 'Returned' },
  { value: 'rejected', text: 'Rejected' },
  { value: 'damaged_in_transit', text: 'Damaged in Transit' },
  { value: 'lost_in_transit', text: 'Lost in Transit' }
];

// COMPLETE available statuses for bulk actions and modal
const availableStatuses = [
  { value: 'preparing', title: 'Preparing' },
  { value: 'ready_for_pickup', title: 'Ready for Pickup' },
  { value: 'loaded', title: 'Loaded' },
  { value: 'in_transit', title: 'In Transit' },
  { value: 'delivered', title: 'Delivered' },
  { value: 'completed', title: 'Completed' },
  { value: 'returned', title: 'Returned' },
  { value: 'rejected', title: 'Rejected' },
  { value: 'damaged_in_transit', title: 'Damaged in Transit' },
  { value: 'lost_in_transit', title: 'Lost in Transit' }
];

// COMPLETE status labels
const statusLabels = {
  preparing: 'Preparing',
  ready_for_pickup: 'Ready for Pickup',
  loaded: 'Loaded',
  in_transit: 'In Transit',
  delivered: 'Delivered',
  completed: 'Completed',
  returned: 'Returned',
  rejected: 'Rejected',
  damaged_in_transit: 'Damaged in Transit',
  lost_in_transit: 'Lost in Transit'
};

// NEW: Function to format category display with capitalization
function formatCategory(category) {
  if (!category) return 'N/A';
  
  const categoryMap = {
    'piece': 'Piece',
    'carton': 'Carton',
    'sack': 'Sack',
    'roll': 'Roll',
    'B/R': 'Bundle/Roll',
    'C/S': 'Carton/Sack'
  };
  
  return categoryMap[category] || category;
}

const filteredPackages = computed(() => {
  // Handle both array and paginated data
  const packagesData = Array.isArray(props.packages) 
    ? props.packages 
    : (props.packages.data || []);
  
  return packagesData.filter(pkg => {
    const matchesSearch = search.value === '' || 
      pkg.item_code.toLowerCase().includes(search.value.toLowerCase()) ||
      pkg.item_name.toLowerCase().includes(search.value.toLowerCase()) ||
      (pkg.waybill_number && pkg.waybill_number.toLowerCase().includes(search.value.toLowerCase()));
    
    const matchesCategory = categoryFilter.value === '' || 
      pkg.category === categoryFilter.value;
    
    const matchesStatus = statusFilter.value === '' || 
      pkg.status === statusFilter.value;
    
    return matchesSearch && matchesCategory && matchesStatus;
  }).sort((a, b) => {
    const modifier = sortDirection.value === 'asc' ? 1 : -1;
    
    let aValue = a[sortField.value];
    let bValue = b[sortField.value];
    
    if (aValue == null) aValue = '';
    if (bValue == null) bValue = '';
    
    aValue = String(aValue).toLowerCase();
    bValue = String(bValue).toLowerCase();

    if (aValue < bValue) return -1 * modifier;
    if (aValue > bValue) return 1 * modifier;
    return 0;
  });
});

const statusForm = useForm({
  package_id: null,
  status: '',
  remarks: ''
});

function handleSort(sortParams) {
  sortField.value = sortParams.field;
  sortDirection.value = sortParams.direction;
}

function handlePageChange(page) {
  // Use the correct route name we now know exists
  router.get(route('admin.packages.index', { page: page }), {
    preserveState: true,
    preserveScroll: true,
    replace: true
  });
}

function refreshData() {
  router.reload();
}

function viewPackage(id) {
  // Try different possible route names
  const possibleRoutes = [
    'packages.track',
    'packages.show',
    'admin.packages.show',
    'employee.packages.show'
  ];
  
  for (const routeName of possibleRoutes) {
    if (route().has(routeName)) {
      router.get(route(routeName, id));
      return;
    }
  }
  
  // Fallback to direct URL
  router.get(`/packages/${id}`);
}

function openStatusModal(pkg) {
  currentPackage.value = pkg;
  statusForm.package_id = pkg.id;
  statusForm.status = pkg.status;
  statusForm.remarks = '';
  showStatusModal.value = true;
}

function closeStatusModal() {
  showStatusModal.value = false;
  statusForm.reset();
  currentPackage.value = null;
}

function submitStatusUpdate() {
  // Try different possible route names for status update
  const possibleRoutes = [
    'admin.packages.mark-status',
    'packages.update-status',
    'employee.packages.update-status'
  ];
  
  let routeFound = false;
  for (const routeName of possibleRoutes) {
    if (route().has(routeName)) {
      statusForm.put(route(routeName, currentPackage.value.id), {
        preserveScroll: true,
        onSuccess: () => {
          closeStatusModal();
        }
      });
      routeFound = true;
      break;
    }
  }
  
  if (!routeFound) {
    console.error('No valid status update route found');
    alert('Error: Could not find update route');
  }
}

function applyBulkStatus(status) {
  if (selectedPackages.value.length === 0) {
    alert('Please select at least one package');
    return;
  }

  if (confirm(`Are you sure you want to update ${selectedPackages.value.length} package(s) to ${status}?`)) {
    // Try different possible route names for bulk status
    const possibleRoutes = [
      'admin.packages.bulk-status',
      'packages.bulk-status',
      'employee.packages.bulk-status'
    ];
    
    let routeFound = false;
    for (const routeName of possibleRoutes) {
      if (route().has(routeName)) {
        router.post(route(routeName), {
          package_ids: selectedPackages.value.map(p => p.id),
          status: status
        }, {
          preserveScroll: true,
        });
        routeFound = true;
        break;
      }
    }
    
    if (!routeFound) {
      console.error('No valid bulk status route found');
      alert('Error: Could not find bulk update route');
    }
  }
}

function statusButtonClass(status) {
  // Using your requested color scheme
  if (status === 'delivered' || status === 'completed') return 'bg-green-500 text-white hover:bg-green-600';
  if (status === 'returned' || status === 'rejected') return 'bg-red-500 text-white hover:bg-red-600';
  if (status === 'in_transit' || status === 'loaded' || status === 'ready_for_pickup') return 'bg-green-500 text-white hover:bg-green-600';
  if (status === 'preparing') return 'bg-yellow-500 text-white hover:bg-yellow-600';
  if (status === 'damaged_in_transit' || status === 'lost_in_transit') return 'bg-red-500 text-white hover:bg-red-600';
  // Default for other statuses
  return 'bg-gray-500 text-white hover:bg-gray-600';
}

function statusBadgeClass(status) {
  // Using your requested color scheme
  if (status === 'delivered' || status === 'completed') return 'bg-green-100 text-green-800';
  if (status === 'returned' || status === 'rejected') return 'bg-red-100 text-red-800';
  if (status === 'in_transit' || status === 'loaded' || status === 'ready_for_pickup') return 'bg-green-100 text-green-800';
  if (status === 'preparing') return 'bg-yellow-100 text-yellow-800';
  if (status === 'damaged_in_transit' || status === 'lost_in_transit') return 'bg-red-100 text-red-800';
  // Default for other statuses
  return 'bg-gray-100 text-gray-800';
}

function categoryBadgeClass(category) {
  switch (category) {
    case 'piece': return 'bg-blue-100 text-blue-800';
    case 'carton': return 'bg-green-100 text-green-800';
    case 'sack': return 'bg-amber-100 text-amber-800';
    case 'roll': return 'bg-red-100 text-red-800';
    case 'B/R': return 'bg-indigo-100 text-indigo-800';
    case 'C/S': return 'bg-pink-100 text-pink-800';
    default: return 'bg-gray-100 text-gray-800';
  }
}

// Note: Removed selectedPackages functionality since it wasn't implemented in the original
const selectedPackages = ref([]);
</script>

<style scoped>
.zoom-content {
  zoom: 0.80;
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