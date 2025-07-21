<template>
  <EmployeeLayout>
    <template #header>
      <div class="flex justify-between items-center">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">
          Pending Delivery Requests
        </h2>
        <div class="flex space-x-2">
          <SecondaryButton @click="viewApprovedRequests">
            Approved Requests
          </SecondaryButton>
          <DangerButton @click="viewRejectedRequests">
            Rejected Requests
          </DangerButton>
        </div>
      </div>
    </template>
  
    <!-- Status Messages -->
    <div v-if="status || success || error" class="mb-6 mx-4 sm:mx-0">
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

    <!-- Search & Filters -->
    <div class="mb-6 mx-4 sm:mx-0 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
      <SearchInput 
        v-model="search" 
        placeholder="Search requests..." 
        class="w-full md:w-64"
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
      </div>
    </div>

    <!-- Data Table -->
    <div class="mx-4 sm:mx-0">
      <DataTable 
        :columns="columns" 
        :data="filteredRequests"
        :sort-field="sortField"
        :sort-direction="sortDirection"
        @sort="handleSort"
      >
        <!-- REMOVE selection slot and CheckboxInput -->
        <template #sender="{ row }">
          {{ row.sender }}
        </template>
        <template #receiver="{ row }">
          {{ row.receiver }}
        </template>
        <template #pick_up_region="{ row }">
          {{ row.pick_up_region || 'N/A' }}
        </template>
        <template #drop_off_region="{ row }">
          {{ row.drop_off_region || 'N/A' }}
        </template>
        <template #total_price="{ row }">
          ₱{{ row.total_price }}
        </template>
        <template #payment_type="{ row }">
          {{ row.payment_type ? row.payment_type.charAt(0).toUpperCase() + row.payment_type.slice(1) : 'N/A' }}
        </template>
        <template #created_at="{ row }">
          {{ new Date(row.created_at).toLocaleDateString() }}
        </template>
        <template #payment_terms="{ row }">
          <span v-if="row.payment_terms">
            {{
              row.payment_terms === 'net_7' ? 'Net 7' :
              row.payment_terms === 'net_15' ? 'Net 15' :
              row.payment_terms === 'net_30' ? 'Net 30' :
              row.payment_terms === 'cnd' ? 'CND' : row.payment_terms
            }}
          </span>
          <span v-else>-</span>
        </template>
        <!-- Removed payment_due_date column/slot -->
        <template #actions="{ row }">
          <div class="flex space-x-2">
            <SecondaryButton @click="viewRequest(row.id)">
              View
            </SecondaryButton>
            <PrimaryButton @click="editRequest(row.id)">
              Edit
            </PrimaryButton>
            <PrimaryButton @click="openApproveModal(row)">
              Approve
            </PrimaryButton>
            <DangerButton @click="openRejectModal(row)">
              Reject
            </DangerButton>
          </div>
        </template>
      </DataTable>
    </div>

    <!-- Pagination -->
    <div class="mt-4 mx-4 sm:mx-0 flex justify-center">
      <Pagination 
        :current-page="currentPage"
        :total-pages="totalPages"
        :pagination="pagination"
        @page-changed="handlePageChange"
      />
    </div>

    <!-- Approve Confirmation Modal -->
    <Modal :show="showApproveModal" @close="closeApproveModal">
      <div class="p-6">
        <h2 class="text-lg font-medium text-gray-900">
          Approve Delivery Request?
        </h2>
        <p class="mt-1 text-sm text-gray-600">
          Are you sure you want to approve request #{{ requestToApprove?.id }} from
          <strong>{{ requestToApprove?.sender }}</strong> to
          <strong>{{ requestToApprove?.receiver }}</strong>?
        </p>

        <div v-if="requestToApprove" class="mt-4">
          <div class="mt-2 text-sm text-gray-700">
            <span class="font-semibold">Payment Terms:</span>
            <span>
              {{
                requestToApprove.payment_terms === 'net_7' ? 'Net 7' :
                requestToApprove.payment_terms === 'net_15' ? 'Net 15' :
                requestToApprove.payment_terms === 'net_30' ? 'Net 30' :
                requestToApprove.payment_terms === 'cnd' ? 'CND' : (requestToApprove.payment_terms || '-')
              }}
            </span>
          </div>
        </div>

        <div class="mt-6 flex justify-end space-x-4">
          <SecondaryButton @click="closeApproveModal">
            Cancel
          </SecondaryButton>
          <PrimaryButton @click="handleApprove" :disabled="isProcessing">
            <span v-if="isProcessing">Approving...</span>
            <span v-else>Approve</span>
          </PrimaryButton>
        </div>
      </div>
    </Modal>

    <!-- REMOVE Bulk Approve Modal -->
    <!--
    <Modal :show="showBulkApproveModal" @close="closeBulkApproveModal">
      <div class="p-6">
        <h2 class="text-lg font-medium text-gray-900">
          Approve {{ selectedRequests.length }} Requests?
        </h2>
        <p class="mt-1 text-sm text-gray-600">
          You are about to approve {{ selectedRequests.length }} delivery requests. This action cannot be undone.
        </p>
        <div class="mt-6 flex justify-end space-x-4">
          <SecondaryButton @click="closeBulkApproveModal">
            Cancel
          </SecondaryButton>
          <PrimaryButton @click="handleBulkApprove" :disabled="isProcessing">
            <span v-if="isProcessing">Approving...</span>
            <span v-else>Approve All</span>
          </PrimaryButton>
        </div>
      </div>
    </Modal>
    -->

    <!-- Reject Confirmation Modal -->
    <Modal :show="showRejectModal" @close="closeRejectModal">
      <div class="p-6">
        <h2 class="text-lg font-medium text-gray-900">
          Reject Delivery Request?
        </h2>
        <p class="mt-1 text-sm text-gray-600">
          Are you sure you want to reject request #{{ requestToReject?.id }} from
          <strong>{{ requestToReject?.sender }}</strong> to
          <strong>{{ requestToReject?.receiver }}</strong>?
        </p>

        <div class="mt-4">
          <InputLabel for="rejection_reason" value="Reason for Rejection" required />
          <select
            id="rejection_reason"
            v-model="selectedReason"
            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
            required
          >
            <option value="" disabled>Select a reason</option>
            <option value="Incomplete information">Incomplete information</option>
            <option value="Unserviceable location">Unserviceable location</option>
            <option value="Payment issues">Payment issues</option>
            <option value="Prohibited items">Prohibited items</option>
            <option value="other">Other (specify below)</option>
          </select>
        </div>

        <div v-if="selectedReason === 'other'" class="mt-4">
          <InputLabel for="custom_reason" value="Custom Reason" required />
          <TextArea
            id="custom_reason"
            class="mt-1 block w-full"
            v-model="customReason"
            :rows="3"
            placeholder="Please specify the reason for rejection..."
            required
          />
        </div>

        <div class="mt-6 flex justify-end space-x-4">
          <SecondaryButton @click="closeRejectModal">
            Cancel
          </SecondaryButton>
          <DangerButton @click="handleReject" :disabled="isProcessing || !rejectionReasonValid">
            <span v-if="isProcessing">Rejecting...</span>
            <span v-else>Reject</span>
          </DangerButton>
        </div>
      </div>
    </Modal>

    <!-- REMOVE Bulk Reject Modal -->
    <!--
    <Modal :show="showBulkRejectModal" @close="closeBulkRejectModal">
      <div class="p-6">
        <h2 class="text-lg font-medium text-gray-900">
          Reject {{ selectedRequests.length }} Requests?
        </h2>
        <p class="mt-1 text-sm text-gray-600">
          You are about to reject {{ selectedRequests.length }} delivery requests. This action cannot be undone.
        </p>

        <div class="mt-4">
          <InputLabel for="bulk_rejection_reason" value="Reason for Rejection" required />
          <select
            id="bulk_rejection_reason"
            v-model="bulkSelectedReason"
            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
            required
          >
            <option value="" disabled>Select a reason</option>
            <option value="Incomplete information">Incomplete information</option>
            <option value="Unserviceable location">Unserviceable location</option>
            <option value="Payment issues">Payment issues</option>
            <option value="Prohibited items">Prohibited items</option>
            <option value="other">Other (specify below)</option>
          </select>
        </div>

        <div v-if="bulkSelectedReason === 'other'" class="mt-4">
          <InputLabel for="bulk_custom_reason" value="Custom Reason" required />
          <TextArea
            id="bulk_custom_reason"
            class="mt-1 block w-full"
            v-model="bulkCustomReason"
            :rows="3"
            placeholder="Please specify the reason for rejection..."
            required
          />
        </div>

        <div class="mt-6 flex justify-end space-x-4">
          <SecondaryButton @click="closeBulkRejectModal">
            Cancel
          </SecondaryButton>
          <DangerButton @click="handleBulkReject" :disabled="isProcessing || !bulkRejectionReasonValid">
            <span v-if="isProcessing">Rejecting...</span>
            <span v-else>Reject All</span>
          </DangerButton>
        </div>
      </div>
    </Modal>
    -->
  </EmployeeLayout>
</template>

<script setup>
import { computed, ref, watch } from 'vue';
import { router } from '@inertiajs/vue3'; // <-- ADD THIS LINE
import EmployeeLayout from '@/Layouts/EmployeeLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import Pagination from '@/Components/Pagination.vue';
import SearchInput from '@/Components/SearchInput.vue';
import SelectInput from '@/Components/SelectInput.vue';
import DataTable from '@/Components/DataTable.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextArea from '@/Components/TextArea.vue';
import Modal from '@/Components/Modal.vue';
import CheckboxInput from '@/Components/CheckboxInput.vue';

const props = defineProps({
  requests: {
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
const dateFilter = ref(props.filters.date_range || 'all');
const sortField = ref('created_at');
const sortDirection = ref('desc');

// Modal states
const showApproveModal = ref(false);
const showRejectModal = ref(false);
const requestToApprove = ref(null);
const requestToReject = ref(null);
const isProcessing = ref(false);

// Rejection reasons
const selectedReason = ref('');
const customReason = ref('');

// Add payment type filter
const paymentTypeFilter = ref(''); // '' = all, 'prepaid', 'postpaid'
const paymentTypeOptions = [
  { value: '', text: 'All Payment Types' },
  { value: 'prepaid', text: 'Prepaid' },
  { value: 'postpaid', text: 'Postpaid' }
];

// Always use computed to ensure numbers are passed
const currentPage = computed(() => Number(props.pagination?.current_page) || 1);
const totalPages = computed(() => Number(props.pagination?.last_page) || 1);

const pagination = computed(() => ({
  current_page: currentPage.value,
  last_page: totalPages.value,
  links: props.pagination?.links || []
}));

// Options for filters
const paymentMethodOptions = [
  { value: '', text: 'All Methods' },
  { value: 'cash', text: 'Cash' },
  { value: 'card', text: 'Card' },
  { value: 'online', text: 'Online' }
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
  { field: 'id', header: 'ID', sortable: true },
  { field: 'sender', header: 'Sender', sortable: true },
  { field: 'receiver', header: 'Receiver', sortable: true },
  { field: 'pick_up_region', header: 'Pick-up', sortable: true },
  { field: 'drop_off_region', header: 'Drop-off', sortable: true },
  { field: 'total_price', header: 'Amount', sortable: true },
  { field: 'payment_type', header: 'Payment Type', sortable: true },
  { field: 'created_at', header: 'Date', sortable: true },
  { field: 'payment_terms', header: 'Payment Terms', sortable: false },
  // Removed { field: 'payment_due_date', ... }
  { field: 'actions', header: 'Actions', sortable: false },
];

// Computed properties
const mappedRequests = computed(() => {
  return (props.requests || []).map(request => ({
    ...request,
    payment_type: request.payment_type 
      // Use backend accessor if present
      ?? (
        // Fallback: infer from payment_method if not present
        ['cash', 'gcash', 'bank'].includes(request.payment_method)
          ? 'prepaid'
          : 'postpaid'
      )
  }));
});

const filteredRequests = computed(() => {
  if (!mappedRequests.value) return [];
  
  return mappedRequests.value.filter(request => {
    const matchesSearch = search.value === '' || 
      (request.sender?.toLowerCase().includes(search.value.toLowerCase()) ||
      request.receiver?.toLowerCase().includes(search.value.toLowerCase()) ||
      request.id.toString().includes(search.value));

    const matchesPayment = paymentMethodFilter.value === '' || 
                         request.payment_method === paymentMethodFilter.value;

    const matchesType = paymentTypeFilter.value === '' ||
      request.payment_type === paymentTypeFilter.value;

    return matchesSearch && matchesPayment && matchesType;
  }).sort((a, b) => {
    if (!sortField.value) return 0;
    const modifier = sortDirection.value === 'asc' ? 1 : -1;
    if (a[sortField.value] < b[sortField.value]) return -1 * modifier;
    if (a[sortField.value] > b[sortField.value]) return 1 * modifier;
    return 0;
  });
});

const rejectionReasonValid = computed(() => {
  if (selectedReason.value === 'other') {
    return customReason.value.trim().length > 0;
  }
  return selectedReason.value !== '';
});

// Watch filters and update URL
watch([search, paymentMethodFilter, dateFilter, paymentTypeFilter], () => {
  router.get(route('deliveries.pending'), {
    search: search.value,
    payment_method: paymentMethodFilter.value,
    date_range: dateFilter.value,
    payment_type: paymentTypeFilter.value,
    page: 1
  }, {
    preserveState: true,
    replace: true
  });
});

// Methods
function handleSort(field) {
  if (sortField.value === field) {
    sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc';
  } else {
    sortField.value = field;
    sortDirection.value = 'asc';
  }
}

function viewRequest(id) {
  router.get(route('deliveries.show', id));
}

function editRequest(id) {
  router.get(route('deliveries.edit', id));
}

// Navigation functions
function viewApprovedRequests() {
  router.get(route('deliveries.index'));
}

function viewRejectedRequests() {
  router.get(route('deliveries.rejected'));
}

function handlePageChange(page) {
  router.get(route('deliveries.pending'), {
    page,
    search: search.value,
    payment_method: paymentMethodFilter.value,
    date_range: dateFilter.value
  }, {
    preserveState: true
  });
}

// Approve/Reject modal functions
function openApproveModal(request) {
  requestToApprove.value = request;
  showApproveModal.value = true;
}

function closeApproveModal() {
  showApproveModal.value = false;
  requestToApprove.value = null;
}

function openRejectModal(request) {
  requestToReject.value = request;
  showRejectModal.value = true;
}

function closeRejectModal() {
  showRejectModal.value = false;
  requestToReject.value = null;
  selectedReason.value = '';
  customReason.value = '';
}

async function handleApprove() {
  if (!requestToApprove.value) {
    return;
  }

  isProcessing.value = true;
  try {
    await router.post(route('deliveries.approve', requestToApprove.value.id), {}, {
      preserveScroll: true,
      onSuccess: () => {
        closeApproveModal();
        // Refresh the page to update the list
        router.reload({ only: ['requests', 'pagination', 'status', 'success', 'error'] });
      },
      onError: (errors) => {
      }
    });
  } catch (error) {
  } finally {
    isProcessing.value = false;
  }
}

async function handleReject() {
  if (!requestToReject.value || !rejectionReasonValid.value) return;
  
  isProcessing.value = true;
  try {
    const reason = selectedReason.value === 'other' 
      ? customReason.value 
      : selectedReason.value;
    
    await router.post(route('deliveries.reject', requestToReject.value.id), {
      rejection_reason: reason
    }, {
      preserveScroll: true,
      onSuccess: () => {
        closeRejectModal();
      }
    });
  } catch (error) {
    console.error('Rejection failed:', error);
  } finally {
    isProcessing.value = false;
  }
}
</script>