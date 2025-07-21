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
      <template #transaction_number="{ row }">
        <span class="font-medium">#{{ row.id.toString().padStart(6, '0') }}</span>
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

      <template #status="{ row }">
        <span 
          :class="['inline-block px-3 py-0.5 rounded-full text-xs font-medium', getStatusClasses(row.status)]"
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
        <div class="flex items-center justify-start space-x-1 w-full overflow-hidden">
          <!-- Hide View Details button -->
          <!--
          <SecondaryButton class="!px-2 !py-1 text-xs" @click="$emit('view', row.id)">
            View Details
          </SecondaryButton>
          -->
          <SecondaryButton
            v-if="row.delivery_request"
            class="!px-2 !py-1 text-xs flex items-center gap-1"
            @click="$emit('view-request', row.delivery_request.id)"
          >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
            Track Delivery
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

defineEmits(['view', 'view-request']);

const sortField = ref('delivery_date');
const sortDirection = ref('desc');

const columns = [
  { field: 'transaction_number', header: 'Transaction #', sortable: true },
  { field: 'receiver', header: 'Receiver', sortable: true },
  { field: 'pickup', header: 'Pickup Branch', sortable: true },
  { field: 'dropoff', header: 'Dropoff Branch', sortable: true },
  { field: 'delivery_date', header: 'Delivery Date', sortable: true },
  { field: 'status', header: 'Status', sortable: true },
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

const filteredTransactions = computed(() => {
  if (!transactions?.data) return [];

  return transactions.data.slice().sort((a, b) => {
    if (!sortField.value) return 0;
    const modifier = sortDirection.value === 'asc' ? 1 : -1;

    if (sortField.value === 'transaction_number') {
      return (a.id - b.id) * modifier;
    }

    if (sortField.value === 'pickup') {
      return (a.delivery_request?.pick_up_region?.name || '').localeCompare(b.delivery_request?.pick_up_region?.name || '') * modifier;
    }

    if (sortField.value === 'dropoff') {
      return (a.delivery_request?.drop_off_region?.name || '').localeCompare(b.delivery_request?.drop_off_region?.name || '') * modifier;
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
  // Check for invalid date or Unix epoch (1/1/1970)
  if (isNaN(date.getTime()) || date.getFullYear() === 1970) return '—';
  return date.toLocaleDateString();
};

const formatStatus = (status) => {
  if (!status || typeof status !== 'string') return '';
  if (status === 'completed') return 'Completed';
  if (status === 'delivered') return 'Delivered';
  return status.charAt(0).toUpperCase() + status.slice(1);
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