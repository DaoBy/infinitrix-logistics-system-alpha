<template>
  <EmployeeLayout>
    <template #header>
      <div class="flex justify-between items-center px-6">
        <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
          Payments & Collections
        </h2>
        <div class="flex space-x-2 items-center">
          <SearchInput
            v-model="search"
            placeholder="Search by reference, name..."
            class="w-[42rem]"
          />
          <SelectInput
            v-model="type"
            :options="typeOptions"
            class="w-16"
          />
          <SelectInput
            v-model="status"
            :options="statusOptions"
            class="w-16"
          />
        </div>
      </div>
    </template>

    <div class="px-6 py-4">
      <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg overflow-hidden border border-gray-200 dark:border-gray-700">
        <div class="px-4 py-5 sm:px-6 border-b border-gray-200 dark:border-gray-700 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
          <div>
            <h3 class="text-lg font-medium leading-6 text-gray-900 dark:text-gray-100">
              {{ tableTitle }}
            </h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
              Manage and review all payment and collection transactions.
            </p>
          </div>
          <div class="text-sm text-gray-500 dark:text-gray-400 md:ml-auto">
            Showing {{ requests?.data?.length ?? 0 }} of {{ requests?.total ?? 0 }} entries
          </div>
        </div>

        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-700">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                  {{ type === 'postpaid' ? 'Order #' : 'Reference' }}
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                  Sender
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                  Receiver
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                  Amount
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                  Status
                </th>
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                  Actions
                </th>
              </tr>
            </thead>
            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
              <tr v-for="row in requests?.data || []" :key="row.id">
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100">
                  <template v-if="isPostpaid(row)">
                    {{ row.delivery_request?.reference_number ?? ('DR-' + String(row.delivery_request?.id ?? row.id).padStart(6, '0')) }}
                  </template>
                  <template v-else>
                    {{ row.reference_number ?? ('DR-' + String(row.id).padStart(6, '0')) }}
                  </template>
                  <div class="text-xs text-gray-500 dark:text-gray-400">
                    {{ row.created_at ? new Date(row.created_at).toLocaleString() : '' }}
                  </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                  {{ senderName(row) }}
                  <div class="text-xs text-gray-500 dark:text-gray-400">
                    {{ senderMobile(row) }}
                  </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                  {{ receiverName(row) }}
                  <div class="text-xs text-gray-500 dark:text-gray-400">
                    {{ receiverMobile(row) }}
                  </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm">
                  ₱{{ amountValue(row) }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm">
                  <StatusBadge 
                    :status="row.payment_status" 
                    :variant="statusVariant(row)"
                  >
                    {{ statusLabel(row) }}
                  </StatusBadge>
                  <span
                    v-if="isPostpaid(row) && row.payment_status === 'collected'"
                    class="block text-blue-600 dark:text-blue-400 text-xs mt-1"
                  >
                    Collected by: {{ row.payment?.collectedBy?.name }}
                  </span>
                  <span
                    v-if="row.payment_status === 'paid' || row.payment_status === 'verified'"
                    class="block text-green-600 dark:text-green-400 text-xs mt-1"
                  >
                    Verified {{ row.payment?.verifiedBy?.name }}
                  </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                  <div class="flex justify-end space-x-2">
                    <PrimaryButton
                      v-if="showRecordAction(row) && !isPostpaid(row)"
                      @click="goToRecord(row)"
                      class="mr-2"
                    >
                      Record Payment
                    </PrimaryButton>
                    <PrimaryButton
                      v-if="showReviewCollection(row)"
                      @click="goToReviewCollection(row)"
                      class="mr-2"
                    >
                      Review Collection
                    </PrimaryButton>
                    <SecondaryButton
                      @click="goToDetails(row)"
                    >
                      View Details
                    </SecondaryButton>
                  </div>
                </td>
              </tr>
              <tr v-if="(requests?.data?.length ?? 0) === 0">
                <td colspan="6" class="px-6 py-4 text-center text-sm text-gray-500 dark:text-gray-400">
                  No {{ type === 'postpaid' ? 'postpaid delivery orders' : type === 'prepaid' ? 'prepaid delivery requests' : 'delivery requests/orders' }} found
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <Pagination
          v-if="requests && requests.last_page > 1"
          :pagination="requests"
          @page-changed="handlePageChange"
        />
      </div>
    </div>
  </EmployeeLayout>
</template>

<script setup>
import EmployeeLayout from '@/Layouts/EmployeeLayout.vue';
import Pagination from '@/Components/Pagination.vue';
import SearchInput from '@/Components/SearchInput.vue';
import SelectInput from '@/Components/SelectInput.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import DataTable from '@/Components/DataTable.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { Link, router } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import debounce from 'lodash/debounce';

const props = defineProps({
  prepaidRequests: {
    type: Object,
    default: () => ({ data: [] })
  },
  postpaidRequests: {
    type: Object,
    default: () => ({ data: [] })
  },
  filters: {
    type: Object,
    default: () => ({})
  }
});

const type = ref(props.filters.type ?? '');
const search = ref(props.filters.search ?? '');
const status = ref(props.filters.status ?? '');

const typeOptions = [
  { value: '', label: 'All' },
  { value: 'prepaid', label: 'Prepaid' },
  { value: 'postpaid', label: 'Postpaid' }
];

const statusOptions = computed(() => {
  if (type.value === 'prepaid') {
    return [
      { value: '', label: 'All Statuses' },
      { value: 'pending', label: 'Pending Payment' },
      { value: 'paid', label: 'Paid' }
    ];
  } else if (type.value === 'postpaid') {
    return [
      { value: '', label: 'All Statuses' },
      { value: 'pending', label: 'Pending Collection' },
      { value: 'collected', label: 'Collected' },
      { value: 'verified', label: 'Verified' }
    ];
  } else {
    // All
    return [
      { value: '', label: 'All Statuses' },
      { value: 'pending', label: 'Pending' },
      { value: 'paid', label: 'Paid' },
      { value: 'collected', label: 'Collected' },
      { value: 'verified', label: 'Verified' }
    ];
  }
});

const columns = [
  { field: 'reference', header: type.value === 'postpaid' ? 'Order #' : 'Reference', sortable: false },
  { field: 'sender', header: 'Sender', sortable: false },
  { field: 'receiver', header: 'Receiver', sortable: false },
  { field: 'amount', header: 'Amount', sortable: false },
  { field: 'status', header: 'Status', sortable: false },
  { field: 'actions', header: 'Actions', sortable: false },
];

const requests = computed(() => {
  if (type.value === 'prepaid') {
    return props.prepaidRequests;
  } else if (type.value === 'postpaid') {
    return props.postpaidRequests;
  } else {
    // Show prepaid by default for "All"
    return props.prepaidRequests;
  }
});

const tableTitle = computed(() => {
  if (type.value === 'prepaid') return 'Prepaid Delivery Requests';
  if (type.value === 'postpaid') return 'Postpaid Delivery Orders';
  return 'All Payments & Collections';
});

// Helper functions for rendering row data
function isPostpaid(row) {
  return type.value === 'postpaid' || row._type === 'postpaid';
}
function senderName(row) {
  if (isPostpaid(row)) {
    return row.delivery_request?.sender?.name ?? 'N/A';
  }
  return row.sender?.name ?? 'N/A';
}
function senderMobile(row) {
  if (isPostpaid(row)) {
    return row.delivery_request?.sender?.mobile ?? '';
  }
  return row.sender?.mobile ?? '';
}
function receiverName(row) {
  if (isPostpaid(row)) {
    return row.delivery_request?.receiver?.name ?? 'N/A';
  }
  return row.receiver?.name ?? 'N/A';
}
function receiverMobile(row) {
  if (isPostpaid(row)) {
    return row.delivery_request?.receiver?.mobile ?? '';
  }
  return row.receiver?.mobile ?? '';
}
function amountValue(row) {
  if (isPostpaid(row)) {
    const val = row.delivery_request?.total_price;
    return (typeof val === 'number' ? val : parseFloat(val || 0)).toFixed(2);
  }
  const val = row.total_price;
  return (typeof val === 'number' ? val : parseFloat(val || 0)).toFixed(2);
}
function statusVariant(row) {
  const s = row.payment_status;
  if (s === 'paid' || s === 'verified') return 'success';
  if (s === 'collected') return 'info';
  return 'warning';
}
function statusLabel(row) {
  const s = row.payment_status;
  if (s === 'paid') return 'Paid';
  if (s === 'collected') return 'Collected';
  if (s === 'verified') return 'Verified';
  return 'Pending';
}
function showRecordAction(row) {
  if (isPostpaid(row)) {
    return row.payment_status !== 'verified';
  }
  return row.payment_status !== 'paid';
}

// Only show Review Collection for postpaid if not yet paid/verified
function showReviewCollection(row) {
  if (!isPostpaid(row)) return false;
  // If payment_status is 'paid' or 'verified', do not show
  if (row.payment_status === 'paid' || row.payment_status === 'verified') return false;
  // Only show if showRecordAction(row) is true and payment exists
  return showRecordAction(row) && (row.payment?.id || row.delivery_request?.payment?.id);
}

function goToRecord(row) {
  router.visit(route('staff.payments.show', row.id));
}
function goToReviewCollection(row) {
  // Try to get payment from row.payment or row.delivery_request.payment
  const paymentId =
    row.payment?.id ||
    row.delivery_request?.payment?.id;
  if (paymentId) {
    router.visit(route('staff.payments.verify', paymentId));
  } else {
    alert('No collection record found for this order. The collector may not have submitted a collection yet.');
  }
}
function goToDetails(row) {
  router.visit(route('deliveries.show', row.id));
}

function handlePageChange(page) {
  router.get(route('staff.payments.index'), {
    page: page,
    type: type.value,
    search: search.value,
    status: status.value,
  }, {
    preserveState: true,
    preserveScroll: true,
    only: ['prepaidRequests', 'postpaidRequests', 'filters']
  });
}

watch([type, search, status], debounce(([typeValue, searchValue, statusValue]) => {
  router.get(route('staff.payments.index'), {
    type: typeValue,
    search: searchValue,
    status: statusValue,
  }, {
    preserveState: true,
    preserveScroll: true,
    replace: true,
    only: ['prepaidRequests', 'postpaidRequests', 'filters']
  });
}, 300));
</script>