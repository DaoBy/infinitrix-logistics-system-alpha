<template>
  <GuestLayout>
    <template #header>
      <div class="flex justify-between items-center px-6">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">
          My Deliveries
        </h2>
        <PrimaryButton @click="router.visit(route('customer.delivery-requests.create'))">
          + New Request
        </PrimaryButton>
      </div>
    </template>

    <div class="px-6 sm:px-8">
      <!-- Status Messages -->
      <div v-if="status || success || error" class="mb-6">
        <Alert v-if="status" type="info">{{ status }}</Alert>
        <Alert v-if="success" type="success">{{ success }}</Alert>
        <Alert v-if="error" type="danger">{{ error }}</Alert>
      </div>

      <!-- Pending Delivery Requests Section -->
      <div class="mb-8">
        <div class="flex justify-between items-center mb-4">
          <h3 class="text-lg font-medium text-gray-900">Pending Delivery Requests</h3>
          <SearchInput 
            v-model="requestSearch" 
            placeholder="Search requests..." 
            class="w-64"
          />
        </div>

        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
          <div class="p-5 bg-white border-b border-gray-200">
            <DeliveryRequestTable 
              :requests="filteredRequests"
              @view="viewRequest"
              @edit="editRequest"
            />
          </div>
        </div>
        
        <!-- Pagination for requests -->
        <Pagination 
          v-if="requests && requests.meta"
          :links="requests.links" 
          :meta="requests.meta"
          class="mt-4"
        />
      </div>

      <!-- Transaction History Section -->
      <div>
        <div class="flex justify-between items-center mb-4">
          <h3 class="text-lg font-medium text-gray-900">Transaction History</h3>
          <SearchInput 
            v-model="transactionSearch" 
            placeholder="Search transactions..." 
            class="w-64"
          />
        </div>

        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
          <div class="p-5 bg-white border-b border-gray-200">
            <TransactionHistoryTable 
              :transactions="filteredTransactions"
              @view-request="viewRequest"
              @view-payment="viewPayment"
            />
          </div>
        </div>
        
        <!-- Pagination for transactions -->
        <Pagination 
          v-if="transactions && transactions.meta"
          :links="transactions.links" 
          :meta="transactions.meta"
          class="mt-4"
        />
      </div>
    </div>
  </GuestLayout>
</template>

<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SearchInput from '@/Components/SearchInput.vue';
import Alert from '@/Components/Alert.vue';
import TransactionHistoryTable from '@/Components/TransactionHistoryTable.vue';
import DeliveryRequestTable from '@/Components/DeliveryRequestTable.vue';
import Pagination from '@/Components/Pagination.vue';
import { router } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

const props = defineProps({
  requests: Object,
  transactions: Object,
  filters: Object,
  status: String,
  success: String,
  error: String,
});

const requestSearch = ref(props.filters?.request_search || '');
const transactionSearch = ref(props.filters?.transaction_search || '');

// Filter requests based on search term
const filteredRequests = computed(() => {
  if (!props.requests?.data) return { data: [], links: [] };
  
  if (!requestSearch.value) return props.requests;
  
  const searchTerm = requestSearch.value.toLowerCase();
  const filteredData = props.requests.data.filter(request => {
    return (
      request.id.toString().includes(searchTerm) ||
      (request.receiver?.name || '').toLowerCase().includes(searchTerm) ||
      (request.receiver?.company_name || '').toLowerCase().includes(searchTerm) ||
      (request.pick_up_region?.name || '').toLowerCase().includes(searchTerm) ||
      (request.drop_off_region?.name || '').toLowerCase().includes(searchTerm) ||
      request.status.toLowerCase().includes(searchTerm) ||
      request.total_price.toString().includes(searchTerm)
    );
  });
  
  return {
    ...props.requests,
    data: filteredData
  };
});

// Filter transactions based on search term
const filteredTransactions = computed(() => {
  if (!props.transactions?.data) return { data: [], links: [] };
  
  if (!transactionSearch.value) return props.transactions;
  
  const searchTerm = transactionSearch.value.toLowerCase();
  const filteredData = props.transactions.data.filter(transaction => {
    return (
      transaction.id.toString().includes(searchTerm) ||
      (transaction.delivery_request?.receiver?.name || '').toLowerCase().includes(searchTerm) ||
      (transaction.delivery_request?.receiver?.company_name || '').toLowerCase().includes(searchTerm) ||
      (transaction.delivery_request?.pick_up_region?.name || '').toLowerCase().includes(searchTerm) ||
      (transaction.delivery_request?.drop_off_region?.name || '').toLowerCase().includes(searchTerm) ||
      transaction.status.toLowerCase().includes(searchTerm) ||
      transaction.total_amount.toString().includes(searchTerm)
    );
  });
  
  return {
    ...props.transactions,
    data: filteredData
  };
});

const viewRequest = (id) => {
  router.visit(route('customer.delivery-requests.show', id));
};

const viewPayment = (id) => {
  router.visit(route('customer.payments.show', id));
};

const editRequest = (id) => {
  router.visit(route('customer.delivery-requests.edit', id));
};

watch(requestSearch, () => {
  router.get(route('customer.delivery-dashboard.index'), {
    request_search: requestSearch.value,
    transaction_search: transactionSearch.value,
  }, {
    preserveState: true,
    replace: true,
  });
});

watch(transactionSearch, () => {
  router.get(route('customer.delivery-dashboard.index'), {
    request_search: requestSearch.value,
    transaction_search: transactionSearch.value,
  }, {
    preserveState: true,
    replace: true,
  });
});
</script>