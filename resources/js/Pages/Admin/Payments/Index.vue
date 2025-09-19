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
                  Payment Method
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
                  <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium capitalize" 
                    :class="paymentMethodClass(row)">
                    {{ paymentMethodLabel(row) }}
                  </span>
                  <div v-if="isOnlinePayment(row) && row.payment?.reference_number" class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                    Ref: {{ row.payment?.reference_number }}
                  </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm">
                  ₱{{ amountValue(row) }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm">
                  <StatusBadge 
                    :status="getPaymentStatus(row)" 
                    :variant="statusVariant(row)"
                  >
                    {{ statusLabel(row) }}
                  </StatusBadge>
                  
                  <span
                    v-if="row.payment_status === 'paid' || row.payment_status === 'verified'"
                    class="block text-green-600 dark:text-green-400 text-xs mt-1"
                  >
                    Verified {{ row.payment?.verifiedBy?.name }}
                  </span>
                  <span
                    v-if="isOnlinePaymentPending(row)"
                    class="block text-yellow-600 dark:text-yellow-400 text-xs mt-1"
                  >
                    Awaiting verification
                  </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                  <div class="flex justify-end space-x-2">
                    <!-- Record Payment button for cash payments or unverified online payments -->
                    <PrimaryButton
                      v-if="showRecordAction(row)"
                      @click="goToRecordPayment(row)"
                      class="mr-2"
                    >
                      Record Payment
                    </PrimaryButton>
                    
                    <!-- Review Collection button for postpaid deliveries with collected payment -->
                  
                    
                    <!-- View Payment Details button for deliveries with existing payment -->
                    <SecondaryButton
                      v-if="hasPayment(row)"
                      @click="goToPaymentDetails(row)"
                    >
                      View Payment
                    </SecondaryButton>
                  </div>
                </td>
              </tr>
              <tr v-if="(requests?.data?.length ?? 0) === 0">
                <td colspan="7" class="px-6 py-4 text-center text-sm text-gray-500 dark:text-gray-400">
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
      { value: 'pending_verification', label: 'Pending Verification' },
      { value: 'paid', label: 'Paid' },
      { value: 'rejected', label: 'Rejected' }
    ];
  } else if (type.value === 'postpaid') {
    return [
      { value: '', label: 'All Statuses' },
      { value: 'pending', label: 'Pending Collection' },
      { value: 'collected', label: 'Collected' },
      { value: 'pending_verification', label: 'Pending Verification' },
      { value: 'verified', label: 'Verified' }
    ];
  } else {
    // All
    return [
      { value: '', label: 'All Statuses' },
      { value: 'pending', label: 'Pending' },
      { value: 'pending_verification', label: 'Pending Verification' },
      { value: 'paid', label: 'Paid' },
      { value: 'collected', label: 'Collected' },
      { value: 'verified', label: 'Verified' },
      { value: 'rejected', label: 'Rejected' }
    ];
  }
});

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

function paymentMethod(row) {
  if (isPostpaid(row)) {
    return row.delivery_request?.payment_method ?? row.payment_method ?? 'cash';
  }
  return row.payment_method ?? 'cash';
}

function paymentMethodLabel(row) {
  const method = paymentMethod(row);
  return method?.charAt(0).toUpperCase() + method?.slice(1) || 'Cash';
}

function paymentMethodClass(row) {
  const method = paymentMethod(row);
  switch (method) {
    case 'cash':
      return 'bg-gray-100 text-gray-800';
    case 'gcash':
      return 'bg-blue-100 text-blue-800';
    case 'bank':
      return 'bg-green-100 text-green-800';
    default:
      return 'bg-gray-100 text-gray-800';
  }
}

function isOnlinePayment(row) {
  const method = paymentMethod(row);
  return method === 'gcash' || method === 'bank';
}

function isOnlinePaymentPending(row) {
  return isOnlinePayment(row) && 
         (row.payment_status === 'pending_verification' || 
          (!row.payment?.verified_by && !row.payment?.rejected_by));
}

function amountValue(row) {
  if (isPostpaid(row)) {
    const val = row.delivery_request?.total_price;
    return (typeof val === 'number' ? val : parseFloat(val || 0)).toFixed(2);
  }
  const val = row.total_price;
  return (typeof val === 'number' ? val : parseFloat(val || 0)).toFixed(2);
}

function getPaymentStatus(row) {
  // For online payments, check if payment is missing
  if (isOnlinePayment(row) && !row.payment) {
    return 'awaiting_payment';
  }
  // For online payments, check the payment verification status
  if (isOnlinePayment(row) && row.payment) {
    if (row.payment.verified_by) return 'verified';
    if (row.payment.rejected_by) return 'rejected';
    return 'pending_verification';
  }
  return row.payment_status;
}

function statusVariant(row) {
  const status = getPaymentStatus(row);
  if (status === 'paid' || status === 'verified') return 'success';
  if (status === 'collected') return 'info';
  if (status === 'rejected') return 'danger';
  if (status === 'pending_verification') return 'warning';
  if (status === 'awaiting_payment') return 'secondary';
  return 'warning';
}

function statusLabel(row) {
  const status = getPaymentStatus(row);
  switch (status) {
    case 'paid': return 'Paid';
    case 'collected': return 'Collected';
    case 'verified': return 'Verified';
    case 'pending_verification': return 'Pending Verification';
    case 'rejected': return 'Rejected';
    case 'awaiting_payment': return 'Awaiting Payment';
    default: return 'Pending';
  }
}

function hasPayment(row) {
  if (isPostpaid(row)) {
    return row.payment?.id || row.delivery_request?.payment?.id;
  }
  return row.payment?.id;
}

function showRecordAction(row) {
  // Always show for cash payments that aren't paid
  if (!isOnlinePayment(row) && row.payment_status !== 'paid') {
    return true;
  }
  // Always show for Gcash/Bank (online payments), regardless of status
  if (isOnlinePayment(row)) {
    return true;
  }
  return false;
}

function showReviewCollection(row) {
  if (!isPostpaid(row)) return false;
  // If payment_status is 'paid' or 'verified', do not show
  if (row.payment_status === 'paid' || row.payment_status === 'verified') return false;
  // Only show if payment exists and status is collected
  return hasPayment(row) && row.payment_status === 'collected';
}

// Route navigation functions
function goToRecordPayment(row) {
  // Always allow staff to record payment for Gcash/Bank or cash
  const deliveryId = isPostpaid(row) ? row.delivery_request?.id : row.id;
  if (deliveryId) {
    router.visit(route('staff.payments.create', { delivery_id: deliveryId }));
  } else {
    router.visit(route('staff.payments.create'));
  }
}

function goToReviewCollection(row) {
  // For postpaid deliveries with collected payment
  const paymentId = row.payment?.id || row.delivery_request?.payment?.id;
  if (paymentId) {
    router.visit(route('staff.payments.verify.view', paymentId));
  }
}

function goToPaymentDetails(row) {
  // For deliveries with existing payment
  const paymentId = row.payment?.id || row.delivery_request?.payment?.id;
  if (paymentId) {
    router.visit(route('staff.payments.show', paymentId));
  }
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