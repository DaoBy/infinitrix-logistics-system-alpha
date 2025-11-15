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

        <!-- Search & Filters -->
        <div class="mb-6 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
          <SearchInput 
          v-model="search" 
  placeholder="Search requests..." 
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
          </div>
        </div>

        <!-- Data Table Container -->
        <div class="bg-white shadow-sm sm:rounded-lg">
          <div class="p-4 bg-white border-b border-gray-200">
            <DataTable 
              :columns="columns" 
              :data="filteredDeliveries"
              :sort-field="sortField"
              :sort-direction="sortDirection"
              @sort="handleSort"
              class="w-full"
            >
              <template #id="{ row }">
                <span class="font-mono text-sm text-gray-600">DR-{{ String(row.id).padStart(6, '0') }}</span>
              </template>

   <template #status="{ row }">
  <span :class="getStatusClass(row.status)" class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full">
    {{ row.status.charAt(0).toUpperCase() + row.status.slice(1) }}
  </span>
</template>

              <template #sender="{ row }">
                {{ row.sender || 'N/A' }}
              </template>

              <template #receiver="{ row }">
                {{ row.receiver || 'N/A' }}
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
                ₱{{ row.total_price || '0.00' }}
              </template>

              <template #payment_method="{ row }">
                <span class="capitalize">{{ row.payment_method || 'N/A' }}</span>
              </template>

              <template #payment_type="{ row }">
                {{ row.payment_type ? row.payment_type.charAt(0).toUpperCase() + row.payment_type.slice(1) : 'N/A' }}
              </template>

              <template #created_at="{ row }">
                {{ row.created_at ? new Date(row.created_at).toLocaleDateString() : 'N/A' }}
              </template>

              <template #approved_at="{ row }">
                {{ row.approved_at ? new Date(row.approved_at).toLocaleDateString() : 'N/A' }}
              </template>

             <template #actions="{ row }">
  <div class="flex space-x-2">
    <SecondaryButton @click="viewRequest(row.id)">
      View
    </SecondaryButton>
    <!-- ADD EDIT BUTTON FOR APPROVED REQUESTS -->
    <PrimaryButton @click="editApprovedRequest(row.id)" class="bg-blue-600 hover:bg-blue-700">
      Edit Packages
    </PrimaryButton>
  </div>
</template>
            </DataTable>
          </div>
        </div>

        <!-- Pagination -->
        <div class="mt-4 flex justify-center">
          <Pagination 
            :current-page="currentPage"
            :total-pages="totalPages"
            :pagination="pagination"
            @page-changed="handlePageChange"
          />
        </div>
      </div>
    </div>
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

// Always use computed to ensure numbers are passed
const currentPage = computed(() => Number(props.pagination?.current_page) || 1);
const totalPages = computed(() => Number(props.pagination?.last_page) || 1);

const pagination = computed(() => ({
  current_page: currentPage.value,
  last_page: totalPages.value,
  links: props.pagination?.links || []
}));

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
  { field: 'id', header: 'Request ID', sortable: true },
  { field: 'sender', header: 'Sender', sortable: true },
  { field: 'receiver', header: 'Receiver', sortable: true },
  { field: 'pick_up_region', header: 'Pick-up', sortable: true },
  { field: 'drop_off_region', header: 'Drop-off', sortable: true },
  { field: 'status', header: 'Status', sortable: true },
  { field: 'total_price', header: 'Amount', sortable: true },
  { field: 'payment_method', header: 'Payment Method', sortable: true },
  { field: 'payment_type', header: 'Payment Type', sortable: true },
  { field: 'created_at', header: 'Request Date', sortable: true },
  { field: 'approved_at', header: 'Approved At', sortable: true },
  { field: 'approved_by', header: 'Approved By', sortable: true },
  { field: 'actions', header: 'Actions', sortable: false },
];

function editApprovedRequest(id) {
  router.get(route('deliveries.approved.edit', id));
}

function getStatusClass(status) {
  switch (status) {
    case 'pending': return 'bg-yellow-100 text-yellow-800';
    case 'approved': return 'bg-green-100 text-green-800';
    case 'rejected': return 'bg-red-100 text-red-800';
    default: return 'bg-gray-100 text-gray-800';
  }
}

// Computed properties
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
      delivery.id.toString().includes(search.value));

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

// Methods
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