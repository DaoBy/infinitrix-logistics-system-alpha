<template>
  <EmployeeLayout>
    <template #header>
      <div class="flex justify-between items-center w-full px-6 md:px-8">
        <!-- Left: Title & Subtitle -->
        <div>
          <h2 class="text-xl font-semibold leading-tight text-gray-800">Approved Delivery Requests</h2>
          <p class="mt-1 text-sm text-gray-500">
            Review and manage approved delivery requests
          </p>
        </div>

        <!-- Right: Buttons -->
        <div class="flex gap-2">
          <PrimaryButton @click="viewPendingRequests">
            Pending Requests
          </PrimaryButton>
          <DangerButton @click="viewRejectedRequests">
            Rejected Requests
          </DangerButton>
        </div>
      </div>
    </template>

    <!-- ZOOM CONTENT WRAPPER -->
    <div class="zoom-content">
      <!-- MAIN CONTENT CONTAINER WITH PROPER PADDING -->
      <div class="px-6 py-4">
        <!-- Status Messages -->
        <div v-if="status || success || error" class="mb-6">
          <div v-if="status" class="p-4 bg-blue-100 text-blue-800 rounded">{{ status }}</div>
          <div v-if="success" class="p-4 bg-green-100 text-green-800 rounded">{{ success }}</div>
          <div v-if="error" class="p-4 bg-red-100 text-red-800 rounded">{{ error }}</div>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
          <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
            <div class="flex items-center">
              <div class="p-2 bg-green-100 rounded-lg">
                <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
              </div>
              <div class="ml-4">
                <p class="text-sm font-medium text-gray-600">Total Approved</p>
                <p class="text-2xl font-bold text-gray-900">{{ props.deliveries.length }}</p>
              </div>
            </div>
          </div>
          
          <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
            <div class="flex items-center">
              <div class="p-2 bg-blue-100 rounded-lg">
                <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                </svg>
              </div>
              <div class="ml-4">
                <p class="text-sm font-medium text-gray-600">Total Revenue</p>
                <p class="text-2xl font-bold text-gray-900">₱{{ totalRevenue.toLocaleString() }}</p>
              </div>
            </div>
          </div>
          
          <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
            <div class="flex items-center">
              <div class="p-2 bg-purple-100 rounded-lg">
                <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                </svg>
              </div>
              <div class="ml-4">
                <p class="text-sm font-medium text-gray-600">Avg. Package Value</p>
                <p class="text-2xl font-bold text-gray-900">₱{{ averagePackageValue.toLocaleString() }}</p>
              </div>
            </div>
          </div>
          
          <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
            <div class="flex items-center">
              <div class="p-2 bg-orange-100 rounded-lg">
                <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
              </div>
              <div class="ml-4">
                <p class="text-sm font-medium text-gray-600">This Month</p>
                <p class="text-2xl font-bold text-gray-900">{{ thisMonthCount }}</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Search & Filters -->
        <div class="mb-6 bg-white rounded-lg shadow-sm border border-gray-200 p-4">
          <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
            <SearchInput 
              v-model="search" 
              placeholder="Search by ID, sender, receiver..." 
              class="w-full max-w-md"
            />
            <div class="flex flex-wrap gap-2 w-full md:w-auto">
              <SelectInput 
                v-model="paymentTypeFilter"
                :options="paymentTypeOptions"
                option-value="value"
                option-label="text"
                placeholder="All Payment Types"
                class="w-full md:w-48"
              />
              <SelectInput 
                v-model="paymentMethodFilter" 
                :options="paymentMethodOptions" 
                option-value="value"
                option-label="text"
                placeholder="All Payment Methods"
                class="w-full md:w-48"
              />
              <SelectInput 
                v-model="dateFilter"
                :options="dateFilterOptions"
                option-value="value"
                option-label="text"
                placeholder="Filter by date"
                class="w-full md:w-48"
              />
              <SecondaryButton @click="clearFilters" class="whitespace-nowrap">
                Clear Filters
              </SecondaryButton>
            </div>
          </div>
          
          <!-- Active Filters Badges -->
          <div v-if="hasActiveFilters" class="mt-3 flex flex-wrap gap-2">
            <span class="text-sm text-gray-600">Active filters:</span>
            <span v-if="search" class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
              Search: "{{ search }}"
              <button @click="search = ''" class="ml-1 hover:text-blue-600">
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
              </button>
            </span>
            <span v-if="paymentMethodFilter" class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
              Method: {{ getPaymentMethodLabel(paymentMethodFilter) }}
              <button @click="paymentMethodFilter = ''" class="ml-1 hover:text-green-600">
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
              </button>
            </span>
            <span v-if="paymentTypeFilter" class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
              Type: {{ getPaymentTypeLabel(paymentTypeFilter) }}
              <button @click="paymentTypeFilter = ''" class="ml-1 hover:text-purple-600">
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
              </button>
            </span>
          </div>
        </div>

        <!-- Data Table Container -->
        <div class="bg-white shadow-sm sm:rounded-lg border border-gray-200">
          <div class="p-4 border-b border-gray-200 bg-gray-50">
            <div class="flex justify-between items-center">
              <h3 class="text-lg font-medium text-gray-900">
                Approved Requests ({{ filteredDeliveries.length }})
              </h3>
              <div class="text-sm text-gray-500">
                Sorted by: {{ sortField }} ({{ sortDirection }})
              </div>
            </div>
          </div>
          
          <div class="overflow-x-auto">
            <DataTable 
              :columns="columns" 
              :data="filteredDeliveries"
              :sort-field="sortField"
              :sort-direction="sortDirection"
              @sort="handleSort"
              class="w-full"
            >
              <template #id="{ row }">
                <div class="flex items-center">
                  <span class="font-mono text-sm text-gray-900 font-semibold">DR-{{ String(row.id).padStart(6, '0') }}</span>
                  <span v-if="row.reference_number" class="ml-2 text-xs text-gray-500 bg-gray-100 px-1 rounded">
                    {{ row.reference_number }}
                  </span>
                </div>
              </template>

              <template #status="{ row }">
                <span :class="getStatusClass(row.status)" class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full capitalize">
                  {{ row.status }}
                </span>
              </template>

              <template #sender="{ row }">
                <div>
                  <p class="font-medium text-gray-900">{{ row.sender || 'N/A' }}</p>
                  <p v-if="row.sender_company" class="text-xs text-gray-500">{{ row.sender_company }}</p>
                </div>
              </template>

              <template #receiver="{ row }">
                <div>
                  <p class="font-medium text-gray-900">{{ row.receiver || 'N/A' }}</p>
                  <p v-if="row.receiver_company" class="text-xs text-gray-500">{{ row.receiver_company }}</p>
                </div>
              </template>

              <template #pick_up_region="{ row }">
                <div class="flex items-center">
                  <div 
                    v-if="row.pick_up_region && row.pick_up_region_color"
                    class="w-3 h-3 rounded-full mr-2 border border-gray-300" 
                    :style="{ backgroundColor: row.pick_up_region_color }"
                  ></div>
                  <span class="text-gray-700">{{ row.pick_up_region || 'N/A' }}</span>
                </div>
              </template>

              <template #drop_off_region="{ row }">
                <div class="flex items-center">
                  <div 
                    v-if="row.drop_off_region && row.drop_off_region_color"
                    class="w-3 h-3 rounded-full mr-2 border border-gray-300" 
                    :style="{ backgroundColor: row.drop_off_region_color }"
                  ></div>
                  <span class="text-gray-700">{{ row.drop_off_region || 'N/A' }}</span>
                </div>
              </template>

              <template #total_price="{ row }">
                <div class="text-right">
                  <p class="font-semibold text-gray-900">₱{{ (row.total_price || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}</p>
                  <p v-if="row.payment_status" :class="getPaymentStatusClass(row.payment_status)" class="text-xs capitalize">
                    {{ row.payment_status }}
                  </p>
                </div>
              </template>

              <template #payment_method="{ row }">
                <div class="flex items-center">
                  <span class="capitalize font-medium">{{ row.payment_method || 'N/A' }}</span>
                  <span v-if="row.payment_terms" class="ml-1 text-xs text-gray-500 bg-gray-100 px-1 rounded">
                    {{ row.payment_terms }}
                  </span>
                </div>
              </template>

              <template #payment_type="{ row }">
                <span :class="getPaymentTypeClass(row.payment_type)" class="px-2 py-1 text-xs font-medium rounded-full capitalize">
                  {{ row.payment_type || 'N/A' }}
                </span>
              </template>

              <template #created_at="{ row }">
                <div>
                  <p class="text-sm text-gray-900">{{ row.created_at ? new Date(row.created_at).toLocaleDateString() : 'N/A' }}</p>
                  <p v-if="row.created_at" class="text-xs text-gray-500">
                    {{ new Date(row.created_at).toLocaleTimeString() }}
                  </p>
                </div>
              </template>

              <template #approved_at="{ row }">
                <div>
                  <p class="text-sm text-gray-900">{{ row.approved_at ? new Date(row.approved_at).toLocaleDateString() : 'N/A' }}</p>
                  <p v-if="row.approved_at" class="text-xs text-gray-500">
                    {{ new Date(row.approved_at).toLocaleTimeString() }}
                  </p>
                </div>
              </template>

              <template #approved_by="{ row }">
                <span class="text-gray-700">{{ row.approved_by || 'System' }}</span>
              </template>
<template #actions="{ row }">
  <div class="flex space-x-2 justify-end">
    <SecondaryButton @click="viewRequest(row.id)" class="text-xs">
      <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
      </svg>
      View
    </SecondaryButton>
    <PrimaryButton @click="editApprovedRequest(row.id)" class="text-xs bg-blue-600 hover:bg-blue-700">
      <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
      </svg>
      Edit Packages
    </PrimaryButton>
    <DangerButton @click="confirmCancel(row)" class="text-xs bg-red-600 hover:bg-red-700">
      <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
      </svg>
      Cancel
    </DangerButton>
  </div>
</template>
            </DataTable>
          </div>
          
          <!-- Empty State -->
          <div v-if="filteredDeliveries.length === 0" class="text-center py-12">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900">No approved requests</h3>
            <p class="mt-1 text-sm text-gray-500">
              {{ hasActiveFilters ? 'Try adjusting your filters' : 'All approved delivery requests will appear here' }}
            </p>
            <div v-if="hasActiveFilters" class="mt-6">
              <SecondaryButton @click="clearFilters">
                Clear Filters
              </SecondaryButton>
            </div>
          </div>
        </div>

        <!-- Pagination -->
        <div v-if="filteredDeliveries.length > 0" class="mt-6 flex justify-between items-center">
          <div class="text-sm text-gray-700">
            Showing <span class="font-medium">{{ (currentPage - 1) * 15 + 1 }}</span> to 
            <span class="font-medium">{{ Math.min(currentPage * 15, filteredDeliveries.length) }}</span> of 
            <span class="font-medium">{{ filteredDeliveries.length }}</span> results
          </div>
          <Pagination 
            :current-page="currentPage"
            :total-pages="totalPages"
            :pagination="pagination"
            @page-changed="handlePageChange"
          />
        </div>
      </div>
    </div>

      <!-- Cancel Confirmation Modal -->
<Modal :show="showCancelModal" @close="showCancelModal = false">
  <div class="p-6">
    <h2 class="text-lg font-medium text-gray-900 mb-4">
      Cancel Delivery Request
    </h2>

    <div class="bg-yellow-50 border border-yellow-200 rounded-md p-4 mb-4">
      <div class="flex">
        <div class="flex-shrink-0">
          <svg class="h-5 w-5 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
          </svg>
        </div>
        <div class="ml-3">
          <h3 class="text-sm font-medium text-yellow-800">Warning</h3>
          <div class="mt-1 text-sm text-yellow-700">
            <p>This action will mark the approved request as rejected. The customer will be notified about the cancellation.</p>
          </div>
        </div>
      </div>
    </div>

    <div class="mb-4">
      <p class="text-sm text-gray-600 mb-2">
        <strong>Request Details:</strong><br>
        ID: DR-{{ String(selectedRequest?.id).padStart(6, '0') }}<br>
        Sender: {{ selectedRequest?.sender }}<br>
        Receiver: {{ selectedRequest?.receiver }}<br>
        Amount: ₱{{ (selectedRequest?.total_price || 0).toLocaleString() }}
      </p>
    </div>

    <div class="mb-4">
      <label for="cancellation_reason" class="block text-sm font-medium text-gray-700 mb-2">
        Cancellation Reason <span class="text-red-500">*</span>
      </label>
      <textarea
        id="cancellation_reason"
        v-model="cancellationReason"
        rows="4"
        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-red-500"
        placeholder="Please provide a detailed reason for cancelling this approved request..."
        required
      ></textarea>
      <p class="mt-1 text-xs text-gray-500">
        This reason will be shared with the customer and recorded in the system.
      </p>
    </div>

    <div class="flex justify-end space-x-4">
      <SecondaryButton @click="showCancelModal = false">
        Keep Request
      </SecondaryButton>
      <DangerButton 
        @click="cancelRequest" 
        :disabled="!cancellationReason.trim() || isCancelling"
      >
        <svg v-if="isCancelling" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        {{ isCancelling ? 'Cancelling...' : 'Confirm Cancellation' }}
      </DangerButton>
    </div>
  </div>
</Modal>
  </EmployeeLayout>
</template>

<script setup>
import { computed, ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import EmployeeLayout from '@/Layouts/EmployeeLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import Pagination from '@/Components/Pagination.vue';
import SearchInput from '@/Components/SearchInput.vue';
import SelectInput from '@/Components/SelectInput.vue';
import DataTable from '@/Components/DataTable.vue';
import ConfirmationModal from '@/Components/ConfirmationModal.vue';
import Modal from '@/Components/Modal.vue';
import InputLabel from '@/Components/InputLabel.vue';

const props = defineProps({
  deliveries: {
    type: Array,
    required: true,
    default: () => []
  },
  pagination: {
    type: Object,
    default: () => ({
      current_page: 1,
      last_page: 1,
      links: []
    })
  },
  filters: {
    type: Object,
    default: () => ({
      search: '',
      payment_method: '',
      payment_type: '',
      date_range: 'all'
    })
  },
  status: String,
  success: String,
  error: String,
});

// Search and Filters
const search = ref(props.filters.search || '');
const paymentMethodFilter = ref(props.filters.payment_method || '');
const paymentTypeFilter = ref(props.filters.payment_type || '');
const dateFilter = ref(props.filters.date_range || 'all');
const sortField = ref('approved_at');
const sortDirection = ref('desc');

// Cancel modal state
const showCancelModal = ref(false);
const cancellationReason = ref('');
const selectedRequest = ref(null);
const isCancelling = ref(false);

// Computed properties
const currentPage = computed(() => Number(props.pagination?.current_page) || 1);
const totalPages = computed(() => Number(props.pagination?.last_page) || 1);

const pagination = computed(() => ({
  current_page: currentPage.value,
  last_page: totalPages.value,
  links: props.pagination?.links || []
}));

// Stats computations
const totalRevenue = computed(() => {
  return props.deliveries.reduce((sum, delivery) => sum + (delivery.total_price || 0), 0);
});

const averagePackageValue = computed(() => {
  const totalPackages = props.deliveries.reduce((sum, delivery) => sum + (delivery.package_count || 0), 0);
  return totalPackages > 0 ? totalRevenue.value / totalPackages : 0;
});

const thisMonthCount = computed(() => {
  const now = new Date();
  return props.deliveries.filter(delivery => {
    if (!delivery.approved_at) return false;
    const approvedDate = new Date(delivery.approved_at);
    return approvedDate.getMonth() === now.getMonth() && approvedDate.getFullYear() === now.getFullYear();
  }).length;
});

const hasActiveFilters = computed(() => {
  return search.value !== '' || paymentMethodFilter.value !== '' || paymentTypeFilter.value !== '' || dateFilter.value !== 'all';
});

// Options for filters
const paymentTypeOptions = [
  { value: '', text: 'All Payment Types' },
  { value: 'prepaid', text: 'Prepaid' },
  { value: 'postpaid', text: 'Postpaid' }
];

const paymentMethodOptions = [
  { value: '', text: 'All Methods' },
  { value: 'cash', text: 'Cash' },
  { value: 'gcash', text: 'GCash' },
  { value: 'bank', text: 'Bank Transfer' }
];

const dateFilterOptions = [
  { value: 'all', text: 'All Time' },
  { value: 'today', text: 'Today' },
  { value: 'week', text: 'This Week' },
  { value: 'month', text: 'This Month' },
  { value: 'year', text: 'This Year' }
];

// Table columns
const columns = [
  { field: 'id', header: 'Request ID', sortable: true, width: '140px' },
  { field: 'sender', header: 'Sender', sortable: true },
  { field: 'receiver', header: 'Receiver', sortable: true },
  { field: 'pick_up_region', header: 'Pick-up', sortable: true },
  { field: 'drop_off_region', header: 'Drop-off', sortable: true },
  { field: 'status', header: 'Status', sortable: true, width: '100px' },
  { field: 'total_price', header: 'Amount', sortable: true, width: '120px' },
  { field: 'payment_method', header: 'Payment Method', sortable: true },
  { field: 'payment_type', header: 'Payment Type', sortable: true, width: '110px' },
  { field: 'created_at', header: 'Request Date', sortable: true, width: '130px' },
  { field: 'approved_at', header: 'Approved At', sortable: true, width: '130px' },
  { field: 'approved_by', header: 'Approved By', sortable: true },
  { field: 'actions', header: 'Actions', sortable: false, width: '240px' },
];

// Methods
function editApprovedRequest(id) {
  router.get(route('deliveries.approved.edit', id));
}

function getStatusClass(status) {
  switch (status) {
    case 'pending': return 'bg-yellow-100 text-yellow-800';
    case 'approved': return 'bg-green-100 text-green-800';
    case 'rejected': return 'bg-red-100 text-red-800';
    case 'cancelled': return 'bg-gray-100 text-gray-800';
    default: return 'bg-gray-100 text-gray-800';
  }
}

function getPaymentTypeClass(type) {
  switch (type) {
    case 'prepaid': return 'bg-green-100 text-green-800';
    case 'postpaid': return 'bg-blue-100 text-blue-800';
    default: return 'bg-gray-100 text-gray-800';
  }
}

function getPaymentStatusClass(status) {
  switch (status) {
    case 'paid': return 'text-green-600';
    case 'pending': return 'text-yellow-600';
    case 'unpaid': return 'text-red-600';
    default: return 'text-gray-600';
  }
}

function getPaymentMethodLabel(value) {
  const option = paymentMethodOptions.find(opt => opt.value === value);
  return option ? option.text : value;
}

function getPaymentTypeLabel(value) {
  const option = paymentTypeOptions.find(opt => opt.value === value);
  return option ? option.text : value;
}

function clearFilters() {
  search.value = '';
  paymentMethodFilter.value = '';
  paymentTypeFilter.value = '';
  dateFilter.value = 'all';
}

// Computed properties for data
const mappedDeliveries = computed(() => {
  return (props.deliveries || []).map(delivery => ({
    ...delivery,
    payment_type: delivery.payment_type 
      ?? (['cash', 'gcash', 'bank'].includes(delivery.payment_method) ? 'prepaid' : 'postpaid')
  }));
});

const filteredDeliveries = computed(() => {
  if (!mappedDeliveries.value) return [];
  
  return mappedDeliveries.value.filter(delivery => {
    const matchesSearch = search.value === '' || 
      (delivery.sender?.toLowerCase().includes(search.value.toLowerCase()) ||
      delivery.receiver?.toLowerCase().includes(search.value.toLowerCase()) ||
      delivery.id.toString().includes(search.value) ||
      delivery.reference_number?.toLowerCase().includes(search.value.toLowerCase()));

    const matchesPayment = paymentMethodFilter.value === '' || 
                         delivery.payment_method === paymentMethodFilter.value;

    const matchesType = paymentTypeFilter.value === '' ||
      delivery.payment_type === paymentTypeFilter.value;

    return matchesSearch && matchesPayment && matchesType;
  }).sort((a, b) => {
    if (!sortField.value) return 0;
    const modifier = sortDirection.value === 'asc' ? 1 : -1;
    if (a[sortField.value] < b[sortField.value]) return -1 * modifier;
    if (a[sortField.value] > b[sortField.value]) return 1 * modifier;
    return 0;
  });
});

// Cancel request methods
function confirmCancel(request) {
  console.log('confirmCancel called with:', request);
  selectedRequest.value = request;
  cancellationReason.value = '';
  showCancelModal.value = true;
  isCancelling.value = false;
  console.log('showCancelModal set to:', showCancelModal.value);
}

function cancelRequest() {
  if (!selectedRequest.value || !cancellationReason.value.trim()) {
    return;
  }

  isCancelling.value = true;

  router.delete(route('deliveries.cancel', selectedRequest.value.id), {
    data: {
      cancellation_reason: cancellationReason.value
    },
    preserveScroll: true,
    onSuccess: () => {
      showCancelModal.value = false;
      selectedRequest.value = null;
      cancellationReason.value = '';
      isCancelling.value = false;
    },
    onError: (errors) => {
      console.error('Failed to cancel request:', errors);
      alert('Failed to cancel request: ' + (errors.message || 'Unknown error'));
      isCancelling.value = false;
    }
  });
}

// Watch filters and update URL
watch([search, paymentMethodFilter, dateFilter, paymentTypeFilter], () => {
  router.get(route('deliveries.index'), {
    search: search.value,
    payment_method: paymentMethodFilter.value,
    payment_type: paymentTypeFilter.value,
    date_range: dateFilter.value,
    page: 1
  }, {
    preserveState: true,
    replace: true
  });
});

// Other methods
function handleSort(field) {
  if (sortField.value === field) {
    sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc';
  } else {
    sortField.value = field;
    sortDirection.value = 'asc';
  }
}

function handlePageChange(page) {
  router.get(route('deliveries.index'), {
    page,
    search: search.value,
    payment_method: paymentMethodFilter.value,
    payment_type: paymentTypeFilter.value,
    date_range: dateFilter.value
  }, {
    preserveState: true
  });
}

function viewRequest(id) {
  router.get(route('deliveries.show', id));
}

function viewAssignment(id) {
  router.get(route('cargo-assignment.show', id));
}

function viewPendingRequests() {
  router.get(route('deliveries.pending'));
}

function viewRejectedRequests() {
  router.get(route('deliveries.rejected'));
}
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