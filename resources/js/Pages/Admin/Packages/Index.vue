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

        <!-- Search and Filters - SERVER-SIDE FILTERING -->
        <div class="mb-4 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
          <div class="w-full sm:w-96">
            <SearchInput 
              v-model="filters.search" 
              placeholder="Search packages by item code, name, waybill, or reference..." 
              class="w-full"
              @input="handleDebouncedFilter"
            />
          </div>
          <div class="flex items-center gap-3">
            <SelectInput 
              v-model="filters.category" 
              :options="categoryOptions" 
              option-value="value"
              option-label="text"
              placeholder="All Categories"
              class="w-full sm:w-48"
              @change="handleFilterChange"
            />
            <SelectInput 
              v-model="filters.status" 
              :options="statusOptions" 
              option-value="value"
              option-label="text"
              placeholder="All Statuses"
              class="w-full sm:w-48"
              @change="handleFilterChange"
            />
            <div class="text-sm text-gray-500 bg-blue-50 px-3 py-1 rounded border border-blue-100 whitespace-nowrap">
              📦 Showing {{ packages.data ? packages.data.length : 0 }} {{ packages.data && packages.data.length === 1 ? 'package' : 'packages' }}
              <span v-if="packages.data && packages.total > packages.per_page" class="ml-1">
                (Page {{ packages.current_page }} of {{ packages.last_page }})
              </span>
            </div>
          </div>
        </div>

        <!-- Data Table Container with proper spacing -->
        <div class="justify-center flex items-center">
          <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg w-full max-w-[98vw]">
            <div class="p-4 bg-white border-b border-gray-200">
              <DataTable 
                :columns="columns" 
                :data="packages.data || []"
                :sort-field="sortField"
                :sort-direction="sortDirection"
                @sort="handleSort"
                class="w-full"
              >
                <!-- Delivery Request Reference -->
                <template #delivery_reference="{ row }">
                  <div class="flex items-center space-x-2">
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-green-100 text-green-800">
                      Ref
                    </span>
                    <span class="font-bold text-green-700 tracking-wide text-sm">
                      {{ row.delivery_request?.reference_number || `DR-${String(row.delivery_request_id || '').padStart(6, '0')}` }}
                    </span>
                  </div>
                  <div class="text-xs text-gray-500 mt-1">
                    ID: DO-{{ String(row.delivery_request_id || '').padStart(6, '0') }}
                    <span v-if="row.created_at"> | Created: {{ formatDate(row.created_at) }}</span>
                  </div>
                </template>

                <template #item_code="{ row }">
                  <div class="font-medium text-gray-900">{{ row.item_code }}</div>
                </template>

                <template #item_name="{ row }">
                  <div class="text-gray-900">{{ row.item_name }}</div>
                </template>

                <!-- Sender Information -->
                <template #sender="{ row }">
                  <div v-if="row.delivery_request?.sender">
                    <div class="text-sm font-medium text-gray-900 mb-1">
                      {{ 
                        row.delivery_request.sender.name ||
                        row.delivery_request.sender.company_name ||
                        'N/A'
                      }}
                    </div>
                    <div class="text-xs text-gray-500 space-y-1">
                      <div class="flex items-center gap-1" v-if="getCustomerPhone(row.delivery_request.sender)">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                        </svg>
                        {{ getCustomerPhone(row.delivery_request.sender) }}
                        <span v-if="row.delivery_request.sender.phone && row.delivery_request.sender.mobile" class="text-gray-400">
                          ({{ row.delivery_request.sender.mobile === getCustomerPhone(row.delivery_request.sender) ? 'Mobile' : 'Phone' }})
                        </span>
                      </div>
                      <div class="flex items-center gap-1" v-if="getCustomerEmail(row.delivery_request.sender)">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                        {{ getCustomerEmail(row.delivery_request.sender) }}
                      </div>
                      <div v-if="!getCustomerPhone(row.delivery_request.sender) && !getCustomerEmail(row.delivery_request.sender)" class="text-gray-400">
                        No contact info
                      </div>
                    </div>
                  </div>
                  <div v-else class="text-sm text-gray-500">No sender info</div>
                </template>

                <!-- Receiver Information -->
                <template #receiver="{ row }">
                  <div v-if="row.delivery_request?.receiver">
                    <div class="text-sm font-medium text-gray-900 mb-1">
                      {{ 
                        row.delivery_request.receiver.name ||
                        row.delivery_request.receiver.company_name ||
                        'N/A'
                      }}
                    </div>
                    <div class="text-xs text-gray-500 space-y-1">
                      <div class="flex items-center gap-1" v-if="getCustomerPhone(row.delivery_request.receiver)">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                        </svg>
                        {{ getCustomerPhone(row.delivery_request.receiver) }}
                        <span v-if="row.delivery_request.receiver.phone && row.delivery_request.receiver.mobile" class="text-gray-400">
                          ({{ row.delivery_request.receiver.mobile === getCustomerPhone(row.delivery_request.receiver) ? 'Mobile' : 'Phone' }})
                        </span>
                      </div>
                      <div class="flex items-center gap-1" v-if="getCustomerEmail(row.delivery_request.receiver)">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                        {{ getCustomerEmail(row.delivery_request.receiver) }}
                      </div>
                      <div v-if="!getCustomerPhone(row.delivery_request.receiver) && !getCustomerEmail(row.delivery_request.receiver)" class="text-gray-400">
                        No contact info
                      </div>
                    </div>
                  </div>
                  <div v-else class="text-sm text-gray-500">No receiver info</div>
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
                        {{ filters.search || filters.category || filters.status ? 'Try adjusting your search terms or filters' : 'No packages are currently being tracked' }}
                      </p>
                      <SecondaryButton 
                        v-if="filters.search || filters.category || filters.status" 
                        @click="resetFilters"
                        class="mt-2"
                      >
                        Clear Filters
                      </SecondaryButton>
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
import { ref, reactive, onMounted } from 'vue';
import { router } from '@inertiajs/vue3';

const props = defineProps({
  packages: Object,
  status: String,
  success: String,
  error: String,
  filters: Object
});

// Filters
const filters = reactive({
  search: props.filters?.search || '',
  category: props.filters?.category || '',
  status: props.filters?.status || ''
});

const sortField = ref('item_code');
const sortDirection = ref('asc');
const showStatusFilters = ref(false);
const showStatusModal = ref(false);
const currentPackage = ref(null);

// UPDATED: Added new columns for delivery reference, sender, and receiver
const columns = [
  { field: 'delivery_reference', header: 'Delivery Ref', sortable: true },
  { field: 'item_code', header: 'Item Code', sortable: true },
  { field: 'item_name', header: 'Item Name', sortable: true },
  { field: 'sender', header: 'Sender', sortable: false },
  { field: 'receiver', header: 'Receiver', sortable: false },
  { field: 'category', header: 'Category', sortable: true },
  { field: 'status', header: 'Status', sortable: true },
  { field: 'current_region', header: 'Current Location', sortable: true },
  { field: 'actions', header: 'Actions', sortable: false }
];

// Category options
const categoryOptions = [
  { value: '', text: 'All Categories' },
  { value: 'piece', text: 'Piece' },
  { value: 'carton', text: 'Carton' },
  { value: 'sack', text: 'Sack' },
  { value: 'roll', text: 'Roll' },
  { value: 'B/R', text: 'Bundle/Roll' },
  { value: 'C/S', text: 'Custom Size' },
];

// Status options
const statusOptions = [
  { value: '', text: 'All Statuses' },
  { value: 'preparing', text: 'Preparing' },
  { value: 'loaded', text: 'Loaded' },
  { value: 'in_transit', text: 'In Transit' },
  { value: 'delivered', text: 'Delivered' },
  { value: 'completed', text: 'Completed' },
  { value: 'returned', text: 'Returned' },
  { value: 'rejected', text: 'Rejected' },
  { value: 'damaged_in_transit', text: 'Damaged in Transit' },
  { value: 'lost_in_transit', text: 'Lost in Transit' }
];

// Available statuses for bulk actions and modal
const availableStatuses = [
  { value: 'preparing', title: 'Preparing' },
  { value: 'loaded', title: 'Loaded' },
  { value: 'in_transit', title: 'In Transit' },
  { value: 'delivered', title: 'Delivered' },
  { value: 'completed', title: 'Completed' },
  { value: 'returned', title: 'Returned' },
  { value: 'rejected', title: 'Rejected' },
  { value: 'damaged_in_transit', title: 'Damaged in Transit' },
  { value: 'lost_in_transit', title: 'Lost in Transit' }
];

// Status labels
const statusLabels = {
  preparing: 'Preparing',
  loaded: 'Loaded',
  in_transit: 'In Transit',
  delivered: 'Delivered',
  completed: 'Completed',
  returned: 'Returned',
  rejected: 'Rejected',
  damaged_in_transit: 'Damaged in Transit',
  lost_in_transit: 'Lost in Transit'
};

// Helper functions for customer data
function getCustomerPhone(customer) {
  if (!customer) return null;
  
  // Use 'mobile' field first (from your Customer model), then fallback to 'phone'
  return customer.mobile || customer.phone || null;
}

function getCustomerEmail(customer) {
  if (!customer) return null;
  
  // Use 'email' field (from your Customer model)
  return customer.email || null;
}

function formatDate(dateString) {
  if (!dateString) return 'N/A';
  return new Date(dateString).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  });
}

// Format category display
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

// Server-side filtering functions
function handleFilterChange() {
  const payload = {
    ...filters,
    page: 1
  };
  
  router.visit(route('admin.packages.index'), {
    data: payload,
    preserveState: true,
    preserveScroll: true,
    replace: true
  });
}

// Debounced search for better performance
let searchTimeout = null;
function handleDebouncedFilter() {
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => {
    handleFilterChange();
  }, 500);
}

function resetFilters() {
  filters.search = '';
  filters.category = '';
  filters.status = '';
  handleFilterChange();
}

const statusForm = useForm({
  package_id: null,
  status: '',
  remarks: ''
});

function handleSort(sortParams) {
  sortField.value = sortParams.field;
  sortDirection.value = sortParams.direction;
  
  const payload = {
    ...filters,
    sort: sortParams.field,
    direction: sortParams.direction
  };
  
  router.visit(route('admin.packages.index'), {
    data: payload,
    preserveState: true,
    preserveScroll: true,
    replace: true
  });
}

function handlePageChange(page) {
  const payload = {
    ...filters,
    page: page
  };
  
  router.visit(route('admin.packages.index'), {
    data: payload,
    preserveState: true,
    preserveScroll: true,
    replace: true
  });
}

function refreshData() {
  router.reload();
}

function viewPackage(id) {
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
          refreshData();
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
          onSuccess: () => {
            refreshData();
          }
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
  if (status === 'delivered' || status === 'completed') return 'bg-green-500 text-white hover:bg-green-600';
  if (status === 'returned' || status === 'rejected') return 'bg-red-500 text-white hover:bg-red-600';
  if (status === 'in_transit' || status === 'loaded' || status === 'ready_for_pickup') return 'bg-green-500 text-white hover:bg-green-600';
  if (status === 'preparing') return 'bg-yellow-500 text-white hover:bg-yellow-600';
  if (status === 'damaged_in_transit' || status === 'lost_in_transit') return 'bg-red-500 text-white hover:bg-red-600';
  return 'bg-gray-500 text-white hover:bg-gray-600';
}

function statusBadgeClass(status) {
  if (status === 'delivered' || status === 'completed') return 'bg-green-100 text-green-800';
  if (status === 'returned' || status === 'rejected') return 'bg-red-100 text-red-800';
  if (status === 'in_transit' || status === 'loaded' || status === 'ready_for_pickup') return 'bg-green-100 text-green-800';
  if (status === 'preparing') return 'bg-yellow-100 text-yellow-800';
  if (status === 'damaged_in_transit' || status === 'lost_in_transit') return 'bg-red-100 text-red-800';
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

const selectedPackages = ref([]);

onMounted(() => {
  console.log('Initial filters from server:', props.filters);
});
</script>

<style scoped>
.zoom-content {
  zoom: 0.85;
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