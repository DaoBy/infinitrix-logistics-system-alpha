<template>
  <EmployeeLayout>
    <template #header>
      <div class="flex justify-between items-center">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">
          Approved Delivery Requests
        </h2>
        <div class="flex space-x-2">
          <PrimaryButton @click="viewPendingRequests">
            Pending Requests
          </PrimaryButton>
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
        :data="filteredDeliveries"
        :sort-field="sortField"
        :sort-direction="sortDirection"
        @sort="handleSort"
      >
        <template #status="{ row }">
          <span :class="getStatusClass(row.status)" class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full">
            {{ row.status }}
          </span>
        </template>

        <template #sender="{ row }">
          {{ row.sender || 'N/A' }}
        </template>

        <template #receiver="{ row }">
          {{ row.receiver || 'N/A' }}
        </template>

        <template #pick_up_region="{ row }">
          {{ row.pick_up_region || 'N/A' }}
        </template>

        <template #drop_off_region="{ row }">
          {{ row.drop_off_region || 'N/A' }}
        </template>

        <template #total_price="{ row }">
          ₱{{ row.total_price || '0.00' }}
        </template>

        <template #approved_at="{ row }">
          {{ row.approved_at ? new Date(row.approved_at).toLocaleDateString() : 'N/A' }}
        </template>

        <template #actions="{ row }">
          <div class="flex space-x-2">
            <SecondaryButton @click="viewRequest(row.id)">
              View Details
            </SecondaryButton>
            <PrimaryButton 
              v-if="row.has_order" 
              @click="viewAssignment(row.deliveryOrder.id)"
            >
              View Assignment
            </PrimaryButton>
          </div>
        </template>
      </DataTable>
    </div>
  </EmployeeLayout>
</template>

<script setup>
import EmployeeLayout from '@/Layouts/EmployeeLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import SearchInput from '@/Components/SearchInput.vue';
import SelectInput from '@/Components/SelectInput.vue';
import DataTable from '@/Components/DataTable.vue';
import { router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
  deliveries: {
    type: Array,
    required: true,
    default: () => []
  },
  status: String,
  success: String,
  error: String,
});

// Search and Filters
const search = ref('');
const paymentMethodFilter = ref('');
const dateFilter = ref('all');
const sortField = ref('approved_at');
const sortDirection = ref('desc');

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
  { field: 'order_number', header: 'Order #', sortable: true },
  { field: 'sender', header: 'Sender', sortable: true },
  { field: 'receiver', header: 'Receiver', sortable: true },
  { field: 'pick_up_region', header: 'Pick-up', sortable: true },
  { field: 'drop_off_region', header: 'Drop-off', sortable: true },
  { field: 'status', header: 'Status', sortable: true },
  { field: 'total_price', header: 'Amount', sortable: true },
  { field: 'payment_method', header: 'Payment', sortable: true },
  { field: 'created_at', header: 'Request Date', sortable: true },
  { field: 'approved_at', header: 'Approved At', sortable: true },
  { field: 'approved_by', header: 'Approved By', sortable: true },
  { field: 'actions', header: 'Actions', sortable: false },
];

function getStatusClass(status) {
  switch (status) {
    case 'pending': return 'bg-yellow-100 text-yellow-800';
    case 'approved': return 'bg-green-100 text-green-800';
    case 'rejected': return 'bg-red-100 text-red-800';
    default: return 'bg-gray-100 text-gray-800';
  }
}

// Filtered deliveries
const filteredDeliveries = computed(() => {
  if (!props.deliveries) return [];
  
  return props.deliveries.filter(delivery => {
    const matchesSearch = search.value === '' || 
      (delivery.sender?.toLowerCase().includes(search.value.toLowerCase()) ||
      delivery.receiver?.toLowerCase().includes(search.value.toLowerCase()) ||
      delivery.order_number?.toLowerCase().includes(search.value.toLowerCase()));

    const matchesPayment = paymentMethodFilter.value === '' || 
                         delivery.payment_method === paymentMethodFilter.value;

    const matchesDate = dateFilter.value === 'all' || 
                       filterByDate(delivery.approved_at, dateFilter.value);

    return matchesSearch && matchesPayment && matchesDate;
  }).sort((a, b) => {
    if (!sortField.value) return 0;
    const modifier = sortDirection.value === 'asc' ? 1 : -1;
    if (a[sortField.value] < b[sortField.value]) return -1 * modifier;
    if (a[sortField.value] > b[sortField.value]) return 1 * modifier;
    return 0;
  });
});

function filterByDate(date, range) {
  if (!date) return false;
  const d = new Date(date);
  const now = new Date();
  
  switch (range) {
    case 'today':
      return d.toDateString() === now.toDateString();
    case 'week':
      const startOfWeek = new Date(now.setDate(now.getDate() - now.getDay()));
      const endOfWeek = new Date(startOfWeek);
      endOfWeek.setDate(startOfWeek.getDate() + 6);
      return d >= startOfWeek && d <= endOfWeek;
    case 'month':
      return d.getMonth() === now.getMonth() && d.getFullYear() === now.getFullYear();
    case 'year':
      return d.getFullYear() === now.getFullYear();
    default:
      return true;
  }
}

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