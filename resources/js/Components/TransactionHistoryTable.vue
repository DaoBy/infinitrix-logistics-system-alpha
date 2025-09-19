<template>
  <div>
    <DataTable
      :columns="columns"
      :data="filteredTransactions"
      :sort-field="sortField"
      :sort-direction="sortDirection"
      @sort="handleSort"
      class="w-full"
    >
      <template #reference_number="{ row }">
        <span class="font-medium">{{ row.reference_number || `#${row.id.toString().padStart(6, '0')}` }}</span>
      </template>

      <template #receiver="{ row }">
        <span>
          {{ row.delivery_request?.receiver?.name || row.delivery_request?.receiver?.company_name || 'Not specified' }}
        </span>
      </template>

      <template #pickup="{ row }">
        <span>
          {{ row.delivery_request?.pick_up_region?.name || 'Not specified' }}
        </span>
      </template>

      <template #dropoff="{ row }">
        <span>
          {{ row.delivery_request?.drop_off_region?.name || 'Not specified' }}
        </span>
      </template>

      <template #delivery_date="{ row }">
        <span>
          {{ formatDate(row.delivery_date) }}
        </span>
      </template>

      <template #payment_status="{ row }">
        <span v-if="getDisplayPaymentStatus(row)" :class="['inline-block px-3 py-1 rounded-full text-xs font-medium', getPaymentStatusClasses(getDisplayPaymentStatus(row))]">
          Payment: {{ formatPaymentStatus(getDisplayPaymentStatus(row)) }}
        </span>
        <span v-else class="inline-block px-3 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800">N/A</span>
      </template>

      <template #status="{ row }">
        <span 
          :class="['inline-block px-3 py-1 rounded-full text-xs font-medium', getStatusClasses(row.status)]"
        >
          {{ formatStatus(row.status) }}
        </span>
      </template>

      <template #total_amount="{ row }">
        <span class="font-medium">
          ₱{{ formatCurrency(row.total_amount) }}
        </span>
      </template>

      <template #actions="{ row }">
      <div class="flex items-center justify-start space-x-2 w-full overflow-hidden">
        <SecondaryButton
          class="!px-3 !py-1.5 text-xs"
          @click="$emit('view-request', row.delivery_request.id)"
        >
          View Delivery
        </SecondaryButton>
        <SecondaryButton
          v-if="shouldShowViewPaymentButton(row)"
          class="!px-3 !py-1.5 text-xs"
          @click="$emit('view-payment', getPaymentId(row))"
        >
          View Payment
        </SecondaryButton>
      </div>
    </template>
    </DataTable>

    <Pagination
      v-if="transactions.links?.length > 3"
      :links="transactions.links"
      class="mt-4"
    />
  </div>
</template>

<script setup>
import DataTable from '@/Components/DataTable.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import Pagination from '@/Components/Pagination.vue';
import { computed, ref } from 'vue';

const { transactions } = defineProps({
  transactions: Object,
});

defineEmits(['view-request', 'view-payment']);

const sortField = ref('delivery_date');
const sortDirection = ref('desc');

const columns = [
  { field: 'reference_number', header: 'Reference #', sortable: true },
  { field: 'receiver', header: 'Receiver', sortable: true },
  { field: 'pickup', header: 'Pickup Branch', sortable: true },
  { field: 'dropoff', header: 'Dropoff Branch', sortable: true },
  { field: 'delivery_date', header: 'Delivery Date', sortable: true },
  { field: 'payment_status', header: 'Payment Status', sortable: true },
  { field: 'status', header: 'Delivery Status', sortable: true },
  { field: 'total_amount', header: 'Total', sortable: true },
  { field: 'actions', header: 'Actions', sortable: false, style: 'width: 200px;' },
];

const getStatusClasses = (status) => {
  switch (status) {
    case 'pending':
      return 'bg-yellow-100 text-yellow-800';
    case 'completed':
    case 'delivered':
      return 'bg-green-100 text-green-800';
    case 'failed':
      return 'bg-red-100 text-red-800';
    case 'in_transit':
      return 'bg-blue-100 text-blue-800';
    default:
      return 'bg-gray-100 text-gray-800';
  }
};

const getPaymentStatusClasses = (status) => {
  if (status === 'paid') return 'text-green-800 bg-green-100';
  if (status === 'rejected') return 'text-red-800 bg-red-100';
  if (status === 'pending_verification') return 'text-blue-800 bg-blue-100';
  if (status === 'pending_payment') return 'text-yellow-800 bg-yellow-100';
  return 'text-gray-800 bg-gray-100';
};

const filteredTransactions = computed(() => {
  if (!transactions?.data) return [];

  return transactions.data.slice().sort((a, b) => {
    if (!sortField.value) return 0;
    const modifier = sortDirection.value === 'asc' ? 1 : -1;

    if (sortField.value === 'reference_number') {
      return (a.reference_number || '').localeCompare(b.reference_number || '') * modifier;
    }

    if (sortField.value === 'pickup') {
      return (a.delivery_request?.pick_up_region?.name || '').localeCompare(b.delivery_request?.pick_up_region?.name || '') * modifier;
    }

    if (sortField.value === 'dropoff') {
      return (a.delivery_request?.drop_off_region?.name || '').localeCompare(b.delivery_request?.drop_off_region?.name || '') * modifier;
    }

    if (sortField.value === 'payment_status') {
      return (getDisplayPaymentStatus(a) || '').localeCompare(getDisplayPaymentStatus(b) || '') * modifier;
    }

    if (a[sortField.value] < b[sortField.value]) return -1 * modifier;
    if (a[sortField.value] > b[sortField.value]) return 1 * modifier;
    return 0;
  });
});

function handleSort(field) {
  if (sortField.value === field) {
    sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc';
  } else {
    sortField.value = field;
    sortDirection.value = 'asc';
  }
}

const formatCurrency = (value) => {
  if (!value || Number(value) === 0) return '0.00';
  return Number(value).toFixed(2);
};

const formatDate = (dateString) => {
  if (!dateString) return '—';
  const date = new Date(dateString);
  if (isNaN(date.getTime()) || date.getFullYear() === 1970) return '—';
  return date.toLocaleDateString();
};

const formatStatus = (status) => {
  if (!status || typeof status !== 'string') return '';
  if (status === 'completed') return 'Completed';
  if (status === 'delivered') return 'Delivered';
  return status.charAt(0).toUpperCase() + status.slice(1);
};

const formatPaymentStatus = (status) => {
  if (!status || typeof status !== 'string') return '';
  return status.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
};

// Updated function to get payment status from the row
function getDisplayPaymentStatus(row) {
  // First check if payment_status is directly on the row
  if (row.payment_status) return row.payment_status;
  
  // Then check if there's a payment object with status
  if (row.payment && row.payment.status) return row.payment.status;
  
  // Check if delivery_request has payment_status
  if (row.delivery_request && row.delivery_request.payment_status) {
    return row.delivery_request.payment_status;
  }
  
  // Check if delivery_request has a payment object with status
  if (row.delivery_request && row.delivery_request.payment && row.delivery_request.payment.status) {
    return row.delivery_request.payment.status;
  }
  
  // Check if the payment was rejected (legacy field check)
  if (row.payment && row.payment.rejected_by) {
    return 'rejected';
  }
  
  return '';
}

// New function to determine if View Payment button should be shown
const shouldShowViewPaymentButton = (row) => {
  const paymentStatus = getDisplayPaymentStatus(row);
  const paymentId = getPaymentId(row);
  // Show button for both pending_verification and paid statuses
  return paymentId && ['pending_verification', 'paid'].includes(paymentStatus);
};

// New function to get payment ID from various possible locations
const getPaymentId = (row) => {
  // Check if payment ID is directly on the row
  if (row.payment && row.payment.id) return row.payment.id;
  // Check if payment ID is in delivery_request.payment
  if (row.delivery_request && row.delivery_request.payment && row.delivery_request.payment.id) {
    return row.delivery_request.payment.id;
  }
  return null;
};
</script>

<style scoped>
:deep(.datatable-cell) {
  font-size: 0.95rem;
  padding-left: 0.5rem;
  padding-right: 0.5rem;
  white-space: normal;
  overflow: visible;
  text-overflow: initial;
}
</style>