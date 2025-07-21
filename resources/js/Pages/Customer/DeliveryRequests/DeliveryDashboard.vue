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

      <!-- Pending Requests Section (DISABLED) -->
      <!--
      <div class="mb-12">
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
            <PendingRequestsTable 
              :requests="requests"
              @view="viewRequest"
              @edit="editRequest"
              @cancel="cancelRequest"
            />
          </div>
        </div>
      </div>
      -->

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
              :transactions="transactions"
              @view="viewTransaction"
              @view-request="viewRequest"
            />
          </div>
        </div>
      </div>
    </div>
  </GuestLayout>
</template>

<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SearchInput from '@/Components/SearchInput.vue';
import Alert from '@/Components/Alert.vue';
import PendingRequestsTable from '@/Components/PendingRequestsTable.vue';
import TransactionHistoryTable from '@/Components/TransactionHistoryTable.vue';
import { router } from '@inertiajs/vue3';
import { computed } from 'vue';
import { ref, watch } from 'vue';

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

const viewRequest = (id) => {
  router.visit(route('driver.delivery-tracking', id));
};

const editRequest = (id) => {
  router.visit(route('customer.delivery-requests.edit', id));
};

const viewTransaction = (id) => {
  // Use the correct route for viewing delivery order details
  router.visit(route('deliveries.show', id));
};

watch([requestSearch, transactionSearch], () => {
  router.get(route('customer.delivery-dashboard.index'), {
    request_search: requestSearch.value,
    transaction_search: transactionSearch.value,
  }, {
    preserveState: true,
    replace: true,
  });
});

const cancelRequest = (id) => {
  if (confirm('Are you sure you want to cancel this delivery request?')) {
    router.delete(route('customer.delivery-requests.destroy', id), {
      preserveScroll: true,
      onSuccess: () => {
        // Success handled by Inertia
      },
      onError: () => {
        alert('Failed to cancel request');
      }
    });
  }
};
</script>