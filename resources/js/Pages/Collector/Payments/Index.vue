<!-- resources/js/Pages/Collector/Payments/Index.vue -->
<template>
  <EmployeeLayout>
    <template #header>
      <div class="flex flex-col md:flex-row justify-between items-start md:items-center px-2 md:px-8 py-4 max-w-7xl mx-auto w-full">
        <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200 mb-2 md:mb-0">
          Postpaid Collections
        </h2>
        <div class="flex flex-col md:flex-row gap-2 w-full md:w-auto">
          <SearchInput 
            v-model="search" 
            placeholder="Search by reference..." 
            class="w-full md:w-64"
          />
          <SelectInput
            v-model="status"
            :options=" [
              { value: '', label: 'All Statuses' },
              { value: 'pending', label: 'Pending Verification' },
              { value: 'verified', label: 'Verified' }
            ]"
            class="w-full md:w-44"
          />
        </div>
      </div>
    </template>

    <div class="px-1 md:px-8 py-4 flex justify-center">
      <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg border border-gray-200 dark:border-gray-700 w-full max-w-7xl">
        <div class="p-2 bg-gray-50 dark:bg-gray-700 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
          <div>
            <h3 class="text-base font-medium leading-6 text-gray-900 dark:text-gray-100">
              Postpaid Collections
            </h3>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
              Payments collected from customers for completed deliveries.
            </p>
          </div>
          <div class="text-xs text-gray-500 dark:text-gray-400">
            Showing {{ payments?.data?.length ?? 0 }} of {{ payments?.total ?? 0 }} entries
          </div>
        </div>
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-700">
              <tr>
                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Reference #</th>
                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Customer</th>
                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Amount</th>
                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Method</th>
                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</th>
                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Verified By</th>
                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Paid At</th>
                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Actions</th>
              </tr>
            </thead>
            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
              <tr v-for="payment in payments?.data || []" :key="payment.id">
                <td class="px-3 py-2 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100">
                  <div class="flex flex-col">
                    <div class="flex items-center gap-2">
                      <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-indigo-100 text-indigo-800 dark:bg-indigo-800 dark:text-indigo-100">
                        Reference #
                      </span>
                      <span class="font-bold text-indigo-900 dark:text-indigo-100 tracking-wide">
                        {{ payment.delivery_request?.reference_number || 'DR-' + String(payment.delivery_request_id).padStart(6, '0') }}
                      </span>
                    </div>
                    <div class="mt-1 flex flex-wrap items-center gap-2 text-xs text-gray-500 dark:text-gray-300">
                      <span>
                        Delivery ID: DO-{{ String(payment.delivery_request_id).padStart(6, '0') }}
                      </span>
                      <span v-if="payment.created_at">
                        | Created: {{ formatDate(payment.created_at) }}
                      </span>
                      <span v-if="payment.verified_by" class="flex items-center gap-1">
                      </span>
                    </div>
                  </div>
                </td>
                <td class="px-3 py-2 whitespace-nowrap text-gray-900 dark:text-gray-100">
                  {{
                    payment.delivery_request?.sender?.name ||
                    payment.delivery_request?.sender?.company_name ||
                    payment.delivery_request?.receiver?.name ||
                    payment.delivery_request?.receiver?.company_name ||
                    'N/A'
                  }}
                </td>
                <td class="px-3 py-2 whitespace-nowrap text-gray-900 dark:text-gray-100">
                  ₱{{ Number(payment.amount).toFixed(2) }}
                </td>
                <td class="px-3 py-2 whitespace-nowrap">
                  <span class="capitalize">{{ payment.method }}</span>
                </td>
                <td class="px-3 py-2 whitespace-nowrap">
                  <StatusBadge 
                    :status="payment.verified_by ? 'verified' : 'pending'"
                    :variant="payment.verified_by ? 'success' : 'warning'"
                  >
                    {{ payment.verified_by ? 'Verified' : 'Pending' }}
                  </StatusBadge>
                </td>
                <td class="px-3 py-2 whitespace-nowrap">
                  <div v-if="payment.verified_by" class="text-sm text-gray-500 dark:text-gray-400">
                    {{ payment.verified_by?.name }}
                  </div>
                  <div v-else class="text-sm text-gray-400 dark:text-gray-500">
                    Not verified
                  </div>
                </td>
                <td class="px-3 py-2 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                  {{ payment.paid_at ? formatDateTime(payment.paid_at) : 'Not paid' }}
                </td>
                <td class="px-3 py-2 whitespace-nowrap flex gap-2">
                  <PrimaryButton
                    type="button"
                    @click="router.visit(route('collector.payments.show', payment.id))"
                    class="!px-2 !py-1.5 !text-xs flex items-center justify-center"
                    :title="'View Details'"
                  >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0zm6 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                  </PrimaryButton>
                  <DangerButton
                    v-if="payment.can_delete"
                    type="button"
                    @click="deletePayment(payment.id)"
                    class="!px-2 !py-1.5 !text-xs flex items-center justify-center"
                    :title="'Delete Payment'"
                  >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                  </DangerButton>
                </td>
              </tr>
              <tr v-if="!payments?.data || payments.data.length === 0">
                <td colspan="8" class="px-3 py-2 text-center text-sm text-gray-500 dark:text-gray-400">
                  No postpaid collections found
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        <!-- aaaa built in pagination -->
        <div v-if="payments?.last_page > 1" class="flex justify-center items-center gap-2 p-2 border-t border-gray-200 dark:border-gray-700">
          <button
            class="px-3 py-1 rounded bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200"
            :disabled="payments.current_page === 1"
            @click="handlePageChange(payments.current_page - 1)"
          >
            Prev
          </button>
          <button
            v-for="page in payments.last_page"
            :key="page"
            class="px-3 py-1 rounded"
            :class="{
              'bg-indigo-600 text-white': payments.current_page === page,
              'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200': payments.current_page !== page
            }"
            @click="handlePageChange(page)"
          >
            {{ page }}
          </button>
          <button
            class="px-3 py-1 rounded bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200"
            :disabled="payments.current_page === payments.last_page"
            @click="handlePageChange(payments.current_page + 1)"
          >
            Next
          </button>
        </div>
      </div>
    </div>
  </EmployeeLayout>
</template>

<script setup>
import EmployeeLayout from '@/Layouts/EmployeeLayout.vue';
import SearchInput from '@/Components/SearchInput.vue';
import SelectInput from '@/Components/SelectInput.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import { router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import debounce from 'lodash/debounce';

const props = defineProps({
  payments: {
    type: Object,
    default: () => ({ data: [] })
  },
  filters: {
    type: Object,
    default: () => ({})
  }
});

const search = ref(props.filters.search ?? '');
const status = ref(props.filters.status ?? '');

function handlePageChange(page) {
  router.get(route('collector.payments.index', { 
    page,
    search: search.value,
    status: status.value
  }), {
    preserveState: true,
    preserveScroll: true,
    only: ['payments', 'filters']
  });
}

watch([search, status], debounce(([searchValue, statusValue]) => {
  router.get(route('collector.payments.index'), { 
    search: searchValue,
    status: statusValue
  }, {
    preserveState: true,
    preserveScroll: true,
    replace: true,
    only: ['payments', 'filters']
  });
}, 300));

function formatDate(dateString) {
  if (!dateString) return 'N/A';
  const date = new Date(dateString);
  return date.toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  });
}

function formatDateTime(dateString) {
  if (!dateString) return 'N/A';
  const date = new Date(dateString);
  return date.toLocaleString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  });
}
</script>