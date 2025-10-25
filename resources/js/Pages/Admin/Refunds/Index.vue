<template>
  <EmployeeLayout>
    <template #header>
      <div class="flex justify-between items-center w-full px-6 md:px-8">
        <!-- Left: Title & Subtitle -->
        <div>
          <h2 class="text-xl font-semibold leading-tight text-gray-800">Refunds & Adjustments</h2>
          <p class="mt-1 text-sm text-gray-500">
            Manage customer refunds and invoice adjustments for delivery issues.
          </p>
        </div>

        <!-- Right: Buttons -->
        <div class="flex gap-2">
          <SecondaryButton @click="refreshData">Refresh Data</SecondaryButton>
          <Link :href="route('refunds.create')">
            <PrimaryButton>
              Create Refund/Adjustment
            </PrimaryButton>
          </Link>
        </div>
      </div>
    </template>

    <!-- MAIN CONTENT CONTAINER -->
    <div class="px-6 py-4">
      <!-- Stats -->
      <div class="grid grid-cols-1 gap-5 sm:grid-cols-3 mb-6">
        <div class="bg-white overflow-hidden shadow rounded-lg">
          <div class="px-4 py-5 sm:p-6">
            <dt class="text-sm font-medium text-gray-500 truncate">Pending Refunds</dt>
            <dd class="mt-1 text-3xl font-semibold text-gray-900">{{ stats?.pending_refunds || 0 }}</dd>
          </div>
        </div>
        <div class="bg-white overflow-hidden shadow rounded-lg">
          <div class="px-4 py-5 sm:p-6">
            <dt class="text-sm font-medium text-gray-500 truncate">Pending Adjustments</dt>
            <dd class="mt-1 text-3xl font-semibold text-gray-900">{{ stats?.pending_adjustments || 0 }}</dd>
          </div>
        </div>
        <div class="bg-white overflow-hidden shadow rounded-lg">
          <div class="px-4 py-5 sm:p-6">
            <dt class="text-sm font-medium text-gray-500 truncate">Needs Adjustment</dt>
            <dd class="mt-1 text-3xl font-semibold text-gray-900">{{ stats?.needs_adjustment || 0 }}</dd>
          </div>
        </div>
      </div>

      <!-- Search and Filters -->
      <div class="mb-4 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
        <div class="w-full sm:w-96">
          <SearchInput 
            v-model="filters.search" 
            placeholder="Search by reference number or sender..." 
            class="w-full"
            @input="handleDebouncedFilter"
          />
        </div>
        <div class="flex items-center gap-3">
          <SelectInput 
            v-model="filters.type" 
            :options="typeOptions" 
            option-value="value"
            option-label="text"
            placeholder="All Types"
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
            📦 Showing {{ totalItems }} {{ totalItems === 1 ? 'item' : 'items' }}
            <span v-if="hasPagination" class="ml-1">
              (Page {{ currentPage }} of {{ totalPages }})
            </span>
          </div>
        </div>
      </div>

      <!-- Data Table Container -->
      <div class="justify-center flex items-center">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg w-full max-w-[98vw]">
          <div class="p-4 bg-white border-b border-gray-200">
            <DataTable 
              :columns="columns" 
              :data="combinedData"
              :sort-field="sortField"
              :sort-direction="sortDirection"
              @sort="handleSort"
              class="w-full"
            >
              <!-- Type Column -->
              <template #type="{ row }">
                <span :class="typeBadgeClass(row.type)" class="inline-flex px-2 text-xs font-semibold leading-5 rounded-full">
                  {{ row.type_label || 'Needs Adjustment' }}
                </span>
              </template>

              <!-- Reference Column -->
              <template #reference="{ row }">
                <div class="flex items-center space-x-2">
                  <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-green-100 text-green-800">
                    Ref
                  </span>
                  <span class="font-bold text-green-700 tracking-wide text-sm">
                    {{ row.delivery_request?.reference_number || row.reference_number }}
                  </span>
                </div>
                <div class="text-xs text-gray-500 mt-1">
                  ID: DR-{{ String(row.delivery_request_id || row.id).padStart(6, '0') }}
                  <span v-if="row.created_at || row.updated_at"> | {{ row.type ? 'Created' : 'Updated' }}: {{ formatDate(row.created_at || row.updated_at) }}</span>
                </div>
              </template>

              <!-- Sender Column -->
              <template #sender="{ row }">
                <div v-if="row.delivery_request?.sender || row.sender">
                  <div class="text-sm font-medium text-gray-900 mb-1">
                    {{ 
                      row.delivery_request?.sender?.name ||
                      row.sender?.name ||
                      'N/A'
                    }}
                  </div>
                  <div class="text-xs text-gray-500 space-y-1">
                    <div class="flex items-center gap-1" v-if="getCustomerPhone(row.delivery_request?.sender || row.sender)">
                      <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                      </svg>
                      {{ getCustomerPhone(row.delivery_request?.sender || row.sender) }}
                    </div>
                    <div class="flex items-center gap-1" v-if="getCustomerEmail(row.delivery_request?.sender || row.sender)">
                      <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                      </svg>
                      {{ getCustomerEmail(row.delivery_request?.sender || row.sender) }}
                    </div>
                    <div v-if="!getCustomerPhone(row.delivery_request?.sender || row.sender) && !getCustomerEmail(row.delivery_request?.sender || row.sender)" class="text-gray-400">
                      No contact info
                    </div>
                  </div>
                </div>
                <div v-else class="text-sm text-gray-500">No sender info</div>
              </template>

              <!-- Amount Columns -->
              <template #original_amount="{ row }">
                <span class="text-sm text-gray-900">
                  ₱{{ row.original_amount || row.total_price }}
                </span>
              </template>

              <template #refund_amount="{ row }">
                <span class="text-sm text-gray-900">
                  <span v-if="row.refund_amount">₱{{ row.refund_amount }}</span>
                  <span v-else class="text-gray-400">—</span>
                </span>
              </template>

              <template #adjusted_amount="{ row }">
                <span class="text-sm text-gray-900">
                  <span v-if="row.adjusted_amount">₱{{ row.adjusted_amount }}</span>
                  <span v-else class="text-gray-400">—</span>
                </span>
              </template>

              <!-- Status Column -->
              <template #status="{ row }">
                <span :class="statusBadgeClass(row.status)" class="inline-flex px-2 text-xs font-semibold leading-5 rounded-full">
                  {{ row.status_label || 'Awaiting Action' }}
                </span>
              </template>

              <!-- Created Date Column -->
              <template #created_at="{ row }">
                <span class="text-sm text-gray-500">
                  {{ formatDate(row.created_at || row.updated_at) }}
                </span>
              </template>

              <!-- Actions Column -->
              <template #actions="{ row }">
                <div class="flex space-x-2">
                  <Link v-if="row.type" :href="route('refunds.show', { refund: row.id })">
                    <SecondaryButton class="text-xs py-1 px-2">
                      View
                    </SecondaryButton>
                  </Link>
                  <Link 
                    v-if="row.type && (row.status === 'pending' || row.status === 'pending_adjustment')"
                    :href="route('refunds.edit', { refund: row.id })"
                  >
                    <PrimaryButton class="text-xs py-1 px-2">
                      {{ row.type === 'adjustment' ? 'Adjust' : 'Negotiate' }}
                    </PrimaryButton>
                  </Link>
                  <Link 
                    v-if="!row.type" 
                    :href="route('refunds.create', { delivery_request_id: row.id })"
                  >
                    <PrimaryButton class="text-xs py-1 px-2">
                      Create Adjustment
                    </PrimaryButton>
                  </Link>
                </div>
              </template>
              
              <!-- Empty State Slot -->
              <template #empty>
                <div class="text-center py-8">
                  <div class="bg-gray-50 rounded-lg p-6 max-w-md mx-auto">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-gray-400 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                    </svg>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">No refunds or adjustments found</h3>
                    <p class="text-gray-500 mb-3">
                      {{ filters.search || filters.type || filters.status ? 'Try adjusting your search terms or filters' : 'No refunds or adjustments are currently being processed' }}
                    </p>
                    <SecondaryButton 
                      v-if="filters.search || filters.type || filters.status" 
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
            <div class="mt-4 flex justify-between items-center">
              <Pagination 
                :pagination="refunds" 
                @page-changed="handlePageChange" 
                v-if="refunds.data && refunds.data.length > 0"
              />
              <Pagination 
                :pagination="needsAdjustment" 
                @page-changed="handleAdjustmentPageChange" 
                v-if="needsAdjustment.data && needsAdjustment.data.length > 0"
              />
            </div>
          </div>
        </div>
      </div>
    </div>
  </EmployeeLayout>
</template>

<script setup>
import EmployeeLayout from '@/Layouts/EmployeeLayout.vue'
import PrimaryButton from '@/Components/PrimaryButton.vue'
import SecondaryButton from '@/Components/SecondaryButton.vue'
import SearchInput from '@/Components/SearchInput.vue'
import SelectInput from '@/Components/SelectInput.vue'
import DataTable from '@/Components/DataTable.vue'
import Pagination from '@/Components/Pagination.vue'
import { Link, router } from '@inertiajs/vue3'
import { reactive, computed, ref } from 'vue'

const props = defineProps({
  refunds: Object,
  needsAdjustment: Object,
  stats: {
    type: Object,
    default: () => ({
      pending_refunds: 0,
      pending_adjustments: 0,
      needs_adjustment: 0
    })
  },
  filters: Object
})

// Filters
const filters = reactive({
  search: props.filters?.search || '',
  type: props.filters?.type || '',
  status: props.filters?.status || '',
})

const sortField = ref('created_at')
const sortDirection = ref('desc')

// DataTable columns - matching the structure from Package Index
const columns = [
  { field: 'type', header: 'Type', sortable: true },
  { field: 'reference', header: 'Reference', sortable: true },
  { field: 'sender', header: 'Sender', sortable: false },
  { field: 'original_amount', header: 'Original Amount', sortable: true },
  { field: 'refund_amount', header: 'Adjustment/Refund', sortable: true },
  { field: 'adjusted_amount', header: 'New Amount Due', sortable: true },
  { field: 'status', header: 'Status', sortable: true },
  { field: 'created_at', header: 'Created', sortable: true },
  { field: 'actions', header: 'Actions', sortable: false }
]

// Options for SelectInput components
const typeOptions = [
  { value: '', text: 'All Types' },
  { value: 'refund', text: 'Refund' },
  { value: 'adjustment', text: 'Adjustment' },
  { value: 'needs_adjustment', text: 'Needs Adjustment' }
]

const statusOptions = [
  { value: '', text: 'All Statuses' },
  { value: 'pending', text: 'Pending Refund' },
  { value: 'pending_adjustment', text: 'Pending Adjustment' },
  { value: 'processed', text: 'Processed Refund' },
  { value: 'adjusted', text: 'Adjusted Invoice' },
  { value: 'needs_adjustment', text: 'Needs Adjustment' }
]

// Computed properties
const totalItems = computed(() => {
  return (props.refunds?.data?.length || 0) + (props.needsAdjustment?.data?.length || 0)
})

const currentPage = computed(() => {
  return props.refunds?.current_page || 1
})

const totalPages = computed(() => {
  return props.refunds?.last_page || 1
})

const hasPagination = computed(() => {
  return props.refunds?.total > props.refunds?.per_page
})

// Combined data for DataTable
const combinedData = computed(() => {
  const refundsData = props.refunds?.data?.map(item => ({ ...item, isRefund: true })) || []
  const needsAdjustmentData = props.needsAdjustment?.data?.map(item => ({ 
    ...item, 
    type: null, // This indicates it's a "needs adjustment" item
    status: 'needs_adjustment',
    status_label: 'Awaiting Action'
  })) || []
  
  return [...refundsData, ...needsAdjustmentData]
})

// Helper functions for customer data (from Package Index)
function getCustomerPhone(customer) {
  if (!customer) return null
  return customer.mobile || customer.phone || null
}

function getCustomerEmail(customer) {
  if (!customer) return null
  return customer.email || null
}

function formatDate(dateString) {
  if (!dateString) return 'N/A'
  return new Date(dateString).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  })
}

// Server-side filtering functions
function handleFilterChange() {
  const payload = {
    ...filters,
    page: 1
  }
  
  router.visit(route('refunds.index'), {
    data: payload,
    preserveState: true,
    preserveScroll: true,
    replace: true
  })
}

// Debounced search for better performance
let searchTimeout = null
function handleDebouncedFilter() {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    handleFilterChange()
  }, 500)
}

function resetFilters() {
  filters.search = ''
  filters.type = ''
  filters.status = ''
  handleFilterChange()
}

function handleSort(sortParams) {
  sortField.value = sortParams.field
  sortDirection.value = sortParams.direction
  
  const payload = {
    ...filters,
    sort: sortParams.field,
    direction: sortParams.direction
  }
  
  router.visit(route('refunds.index'), {
    data: payload,
    preserveState: true,
    preserveScroll: true,
    replace: true
  })
}

function handlePageChange(page) {
  const payload = {
    ...filters,
    page: page
  }
  
  router.visit(route('refunds.index'), {
    data: payload,
    preserveState: true,
    preserveScroll: true,
    replace: true
  })
}

function handleAdjustmentPageChange(page) {
  const payload = {
    ...filters,
    adjustment_page: page
  }
  
  router.visit(route('refunds.index'), {
    data: payload,
    preserveState: true,
    preserveScroll: true,
    replace: true
  })
}

function refreshData() {
  router.reload()
}

function typeBadgeClass(type) {
  const classes = {
    refund: 'bg-purple-100 text-purple-800',
    adjustment: 'bg-orange-100 text-orange-800'
  }
  return classes[type] || 'bg-orange-100 text-orange-800' // Default for needs adjustment
}

function statusBadgeClass(status) {
  const classes = {
    pending: 'bg-yellow-100 text-yellow-800',
    pending_adjustment: 'bg-yellow-100 text-yellow-800',
    processed: 'bg-green-100 text-green-800',
    adjusted: 'bg-green-100 text-green-800',
    needs_adjustment: 'bg-yellow-100 text-yellow-800'
  }
  return classes[status] || 'bg-gray-100 text-gray-800'
}
</script>

<style scoped>
/* Apply the same zoom and styling as Package Index */
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