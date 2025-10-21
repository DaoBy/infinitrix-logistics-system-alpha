<template>
  <EmployeeLayout>
    <template #header>
      <div class="flex justify-between items-center w-full px-6 md:px-8">
        <!-- Left: Title & Subtitle -->
        <div>
          <h2 class="text-xl font-semibold leading-tight text-gray-800">Customer Update Requests</h2>
          <p class="mt-1 text-sm text-gray-500">
            Review and manage customer profile update requests
          </p>
        </div>

        <!-- Right: Buttons -->
        <div class="flex gap-2">
          <SecondaryButton 
            @click="viewAuditLogs"
            class="flex items-center space-x-2"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            <span>View Audit Logs</span>
          </SecondaryButton>
          <PrimaryButton @click="refreshRequests">Refresh</PrimaryButton>
        </div>
      </div>
    </template>

    <!-- ZOOM CONTENT WRAPPER -->
    <div class="zoom-content">
      <!-- MAIN CONTENT CONTAINER WITH PROPER PADDING -->
      <div class="px-6 py-4">
        <!-- Status Messages -->
        <div v-if="status || success || error" class="mb-4">
          <div v-if="status" class="p-3 bg-blue-100 text-blue-800 rounded">{{ status }}</div>
          <div v-if="success" class="p-3 bg-green-100 text-green-800 rounded">{{ success }}</div>
          <div v-if="error" class="p-3 bg-red-100 text-red-800 rounded">{{ error }}</div>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
          <div class="bg-white p-4 rounded-lg shadow-sm border">
            <div class="flex items-center">
              <div class="p-2 bg-blue-100 rounded-lg">
                <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
              </div>
              <div class="ml-4">
                <p class="text-sm font-medium text-gray-600">Pending Requests</p>
                <p class="text-2xl font-semibold text-gray-900">{{ stats.pending || 0 }}</p>
              </div>
            </div>
          </div>
          <div class="bg-white p-4 rounded-lg shadow-sm border">
            <div class="flex items-center">
              <div class="p-2 bg-green-100 rounded-lg">
                <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
              </div>
              <div class="ml-4">
                <p class="text-sm font-medium text-gray-600">Approved</p>
                <p class="text-2xl font-semibold text-gray-900">{{ stats.approved || 0 }}</p>
              </div>
            </div>
          </div>
          <div class="bg-white p-4 rounded-lg shadow-sm border">
            <div class="flex items-center">
              <div class="p-2 bg-red-100 rounded-lg">
                <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
              </div>
              <div class="ml-4">
                <p class="text-sm font-medium text-gray-600">Rejected</p>
                <p class="text-2xl font-semibold text-gray-900">{{ stats.rejected || 0 }}</p>
              </div>
            </div>
          </div>
          <div class="bg-white p-4 rounded-lg shadow-sm border">
            <div class="flex items-center">
              <div class="p-2 bg-purple-100 rounded-lg">
                <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
              </div>
              <div class="ml-4">
                <p class="text-sm font-medium text-gray-600">Total Requests</p>
                <p class="text-2xl font-semibold text-gray-900">{{ stats.total || 0 }}</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Search and Filters -->
        <div class="mb-4 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
          <div class="w-full sm:w-96">
            <SearchInput 
              v-model="search" 
              placeholder="Search customers by name, email, or company..." 
              class="w-full"
            />
          </div>
          <div class="flex items-center gap-3">
            <SelectInput 
              v-model="statusFilter" 
              :options="statusOptions" 
              option-value="value"
              option-label="text"
              placeholder="All Statuses"
              class="w-full sm:w-48"
            />
            <div class="text-sm text-gray-500 bg-blue-50 px-3 py-1 rounded border border-blue-100 whitespace-nowrap">
              📋 Showing {{ requests.data?.length || 0 }} {{ requests.data?.length === 1 ? 'request' : 'requests' }}
              <span v-if="requests.data && requests.data.length < requests.total" class="ml-1">
                (Page {{ requests.current_page }} of {{ requests.last_page }})
              </span>
            </div>
          </div>
        </div>

        <!-- Data Table Container -->
        <div class="justify-center flex items-center">
          <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg w-full max-w-[95vw]">
            <div class="p-4 bg-white border-b border-gray-200">
              <DataTable
                :columns="columns"
                :data="requests.data || []"
                :sort-field="sortField"
                :sort-direction="sortDirection"
                @sort="handleSort"
                class="w-full"
              >
                <template #customer_name="{ row }">
                  <div class="flex items-center space-x-2">
                    <span class="font-medium text-gray-900">
                      {{ row.customer?.name || 'N/A' }}
                    </span>
                    <span v-if="row.customer?.has_delivery_history" 
                          class="px-1.5 py-0.5 text-xs bg-blue-100 text-blue-800 rounded-full"
                          title="Has delivery history">
                      📦
                    </span>
                    <span v-if="row.customer?.critical_fields_locked" 
                          class="px-1.5 py-0.5 text-xs bg-amber-100 text-amber-800 rounded-full"
                          title="Critical fields locked">
                      🔒
                    </span>
                  </div>
                </template>
                
                <template #email="{ row }">
                  <span class="text-gray-700">{{ row.customer?.email || 'N/A' }}</span>
                </template>
                
                <template #changed_fields="{ row }">
                  <div>
                    <span class="text-sm">{{ formatChangedFields(row) }}</span>
                    <div v-if="hasLockedFields(row)" class="text-xs text-amber-600 mt-1">
                      Includes locked fields
                    </div>
                  </div>
                </template>
                
                <template #status="{ row }">
                  <span 
                    class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full capitalize"
                    :class="statusClass(row.status)"
                  >
                    {{ formatStatus(row.status) }}
                  </span>
                </template>
                
                <template #created_at="{ row }">
                  <div class="text-sm text-gray-900">
                    {{ formatDate(row.created_at) }}
                  </div>
                </template>
                
                <template #actions="{ row }">
                  <div class="flex space-x-2">
                    <PrimaryButton 
                      @click="viewRequest(row.id)"
                      class="text-xs py-1 px-2"
                    >
                      Review
                    </PrimaryButton>
                    
                    <SecondaryButton 
                      v-if="row.customer"
                      @click="viewCustomerAuditLogs(row.customer.id)"
                      class="text-xs py-1 px-2"
                      title="View customer audit logs"
                    >
                      Logs
                    </SecondaryButton>
                  </div>
                </template>

                <!-- Empty State Slot -->
                <template #empty>
                  <div class="text-center py-8">
                    <div class="bg-gray-50 rounded-lg p-6 max-w-md mx-auto">
                      <svg class="h-10 w-10 text-gray-400 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                      </svg>
                      <h3 class="text-lg font-medium text-gray-900 mb-2">No update requests found</h3>
                      <p class="text-gray-500 mb-3">
                        {{ search ? 'Try adjusting your search terms' : 'All customer update requests are processed' }}
                      </p>
                    </div>
                  </div>
                </template>
              </DataTable>

              <!-- Pagination Component -->
              <div v-if="requests.links?.length > 3" class="mt-4">
                <Pagination 
                  :pagination="requests" 
                  @page-changed="handlePageChange" 
                />
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </EmployeeLayout>
</template>

<script setup>
import EmployeeLayout from '@/Layouts/EmployeeLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import SearchInput from '@/Components/SearchInput.vue';
import SelectInput from '@/Components/SelectInput.vue';
import DataTable from '@/Components/DataTable.vue';
import Pagination from '@/Components/Pagination.vue';
import { ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';

const props = defineProps({ 
  requests: Object,
  filters: Object,
  stats: Object,
  status: String,
  success: String,
  error: String
});

// State
const search = ref(props.filters?.search || '');
const statusFilter = ref(props.filters?.status || '');
const sortField = ref(props.filters?.sort_field || 'created_at');
const sortDirection = ref(props.filters?.sort_direction || 'desc');

// Constants
const statusOptions = [
  { value: '', text: 'All Statuses' },
  { value: 'pending', text: 'Pending' },
  { value: 'approved', text: 'Approved' },
  { value: 'rejected', text: 'Rejected' }
];

const columns = [
  { field: 'customer_name', header: 'Customer', sortable: true },
  { field: 'email', header: 'Email', sortable: true },
  { field: 'changed_fields', header: 'Changed Fields', sortable: false },
  { field: 'status', header: 'Status', sortable: true },
  { field: 'created_at', header: 'Submitted', sortable: true },
  { field: 'actions', header: 'Actions', sortable: false }
];

// Computed
const hasLockedFields = (request) => {
  if (!request.customer?.critical_fields_locked) return false;
  
  const lockedFields = ['first_name', 'last_name', 'email', 'mobile', 'building_number', 'street', 'barangay', 'city', 'province', 'zip_code'];
  return lockedFields.some(field => request[field] !== null);
};

// Methods
const handleSort = (sortParams) => {
  sortField.value = sortParams.field;
  sortDirection.value = sortParams.direction;
};

const handlePageChange = (page) => {
  router.get(route('admin.customer-update-requests.index'), { 
    page: page,
    search: search.value,
    status: statusFilter.value,
    sort_field: sortField.value,
    sort_direction: sortDirection.value
  }, {
    preserveState: true,
    preserveScroll: true,
    replace: true
  });
};

const formatChangedFields = (request) => {
  const fields = [];
  const profileFields = [
    'first_name', 'middle_name', 'last_name', 'email', 'mobile', 'phone',
    'building_number', 'street', 'barangay', 'city', 'province', 'zip_code'
  ];
  
  profileFields.forEach(field => {
    if (request[field] !== null) {
      fields.push(field.replace('_', ' '));
    }
  });
  
  return fields.join(', ') || 'No changes';
};

const formatStatus = (status) => {
  const statusMap = {
    pending: 'Pending Review',
    approved: 'Approved',
    rejected: 'Rejected'
  };
  return statusMap[status] || status;
};

const statusClass = (status) => {
  const classes = {
    pending: 'bg-yellow-100 text-yellow-800',
    approved: 'bg-green-100 text-green-800',
    rejected: 'bg-red-100 text-red-800'
  };
  return classes[status] || 'bg-gray-100 text-gray-800';
};

const formatDate = (date) => {
  return new Date(date).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  });
};

const viewRequest = (id) => {
  router.get(route('admin.customer-update-requests.show', id));
};

const viewAuditLogs = () => {
  router.get(route('admin.customer-audit-logs.index'));
};

const viewCustomerAuditLogs = (customerId) => {
  router.get(route('admin.customers.audit-logs', customerId));
};

const refreshRequests = () => {
  router.reload({ only: ['requests', 'stats'] });
};

// Watchers
watch([search, statusFilter, sortField, sortDirection], () => {
  router.get(route('admin.customer-update-requests.index'), {
    search: search.value,
    status: statusFilter.value,
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
.zoom-content {
  zoom: 0.90;
}

.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

/* DataTable styling adjustments */
:deep(.datatable-table) {
  width: 100%;
}

/* Reduce table row padding for more compact rows */
:deep(.datatable-table td) {
  padding-top: 0.375rem !important;
  padding-bottom: 0.375rem !important;
}

/* Reduce table header padding */
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