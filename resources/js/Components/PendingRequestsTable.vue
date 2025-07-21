<template>
  <div>
    <DataTable
      :columns="columns"
      :data="filteredRequests"
      :sort-field="sortField"
      :sort-direction="sortDirection"
      @sort="handleSort"
      class="w-full"
    >
      <template #request_number="{ row }">
        <span class="font-medium">#{{ row.id.toString().padStart(6, '0') }}</span>
      </template>
      <template #receiver="{ row }">
        <span>
          {{ row.receiver?.name || row.receiver?.company_name || 'Not specified' }}
        </span>
      </template>
      <template #pickup="{ row }">
        <span>
          {{ row.pick_up_region?.name || 'Not specified' }}
        </span>
      </template>
      <template #dropoff="{ row }">
        <span>
          {{ row.drop_off_region?.name || 'Not specified' }}
        </span>
      </template>
      <template #created_at="{ row }">
        <span>
          {{ formatDate(row.created_at) }}
        </span>
      </template>
      <template #status="{ row }">
        <span 
          :class="['inline-block px-3 py-0.5 rounded-full text-xs font-medium', getStatusClasses(row.status)]"
        >
          {{ formatStatus(row.status) }}
        </span>
      </template>
      <template #total_price="{ row }">
        <span class="font-medium">
          ₱{{ formatCurrency(row.total_price ?? 0) }}
        </span>
      </template>
      <template #actions="{ row }">
        <div class="flex items-center justify-start space-x-1 w-full overflow-hidden">
          <SecondaryButton class="!px-2 !py-1 text-xs" @click="$emit('view', row.id)">
            View
          </SecondaryButton>
          <PrimaryButton 
            v-if="row.status === 'pending' || row.status === 'draft'"
            class="!px-2 !py-1 text-xs"
            @click="$emit('edit', row.id)"
          >
            Edit
          </PrimaryButton>
          <DangerButton
            v-if="row.status === 'pending' || row.status === 'draft'"
            class="!px-2 !py-1 text-xs"
            @click="$emit('cancel', row.id)"
          >
            Cancel
          </DangerButton>
        </div>
      </template>
    </DataTable>

    <Pagination 
      v-if="requests.links?.length > 3" 
      :links="requests.links" 
      class="mt-4"
    />
  </div>
</template>

<script setup>
import DataTable from '@/Components/DataTable.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import Pagination from '@/Components/Pagination.vue';
import { computed, ref } from 'vue';

const props = defineProps({
  requests: Object,
});

defineEmits(['view', 'edit', 'cancel']);

// Sorting
const sortField = ref('created_at');
const sortDirection = ref('desc');

const columns = [
  { field: 'request_number', header: 'Request #', sortable: true },
  { field: 'receiver', header: 'Receiver', sortable: true },
  { field: 'pickup', header: 'Pickup Branch', sortable: true },
  { field: 'dropoff', header: 'Dropoff Branch', sortable: true },
  { field: 'created_at', header: 'Date Created', sortable: true },
  { field: 'status', header: 'Status', sortable: true },
  { field: 'total_price', header: 'Total', sortable: true },
  { field: 'actions', header: 'Actions', sortable: false, style: 'width: 200px;' },
];

const getStatusClasses = (status) => {
  switch (status) {
    case 'draft':
      return 'bg-gray-100 text-gray-800';
    case 'pending':
      return 'bg-yellow-100 text-yellow-800';
    case 'approved':
      return 'bg-green-100 text-green-800';
    case 'rejected':
      return 'bg-red-100 text-red-800';
    default:
      return 'bg-gray-100 text-gray-800';
  }
};

const filteredRequests = computed(() => {
  if (!props.requests?.data) return [];

  return props.requests.data.sort((a, b) => {
    if (!sortField.value) return 0;
    const modifier = sortDirection.value === 'asc' ? 1 : -1;

    if (sortField.value === 'request_number') {
      return (a.id - b.id) * modifier;
    }

    if (sortField.value === 'pickup') {
      return (a.pick_up_region?.name || '').localeCompare(b.pick_up_region?.name || '') * modifier;
    }

    if (sortField.value === 'dropoff') {
      return (a.drop_off_region?.name || '').localeCompare(b.drop_off_region?.name || '') * modifier;
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

const formatDate = (dateString) => {
  return new Date(dateString).toLocaleDateString();
};

const formatCurrency = (value) => {
  return Number(value).toFixed(2);
};

const formatStatus = (status) => {
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