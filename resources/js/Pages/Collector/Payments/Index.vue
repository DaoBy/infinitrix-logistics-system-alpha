<!-- resources/js/Pages/Collector/Payments/Index.vue -->
<template>
  <EmployeeLayout>
    <template #header>
      <div class="flex justify-between items-center w-full px-4 md:px-6 lg:px-8">
        <!-- Left: Title & Subtitle -->
        <div>
          <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
            Postpaid Collections
          </h2>
          <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
            Payments collected from customers for completed deliveries.
          </p>
        </div>
      </div>
    </template>

    <!-- ZOOM CONTENT WRAPPER -->
    <div class="zoom-content">
      <!-- MAIN CONTENT CONTAINER WITH PROPER PADDING -->
      <div class="px-4 md:px-6 lg:px-8 py-4">
        <!-- Search and Filter Bar -->
        <div class="mb-4 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
          <div class="w-full sm:w-80">
            <SearchInput 
              v-model="search"
              placeholder="Search by reference, sender, or receiver..."
              class="w-full"
            />
          </div>
          <div class="flex items-center gap-3 w-full sm:w-auto">
            <div class="w-full sm:w-48">
              <SelectInput
                v-model="status"
                :options="statusOptions"
                class="w-full"
              />
            </div>
            <div class="text-sm text-gray-500 dark:text-gray-400 bg-blue-50 dark:bg-blue-900/30 px-3 py-1 rounded border border-blue-100 dark:border-blue-800">
              📋 Showing {{ payments?.data?.length ?? 0 }} of {{ payments?.total ?? 0 }} 
              {{ (payments?.total ?? 0) === 1 ? 'entry' : 'entries' }}
            </div>
          </div>
        </div>

        <!-- Mobile View - Card Layout -->
        <div class="sm:hidden space-y-4">
          <div v-for="payment in payments?.data || []" :key="payment.id" 
               class="rounded-lg border border-gray-200 dark:border-gray-700 shadow-sm p-4 bg-white dark:bg-gray-800">
            
            <!-- Header Section -->
            <div class="flex justify-between items-start mb-3">
              <div>
               <div class="flex items-center gap-2 mb-1">
  <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100">
    Ref
  </span>
  <span class="font-bold text-green-700 dark:text-green-300 tracking-wide">
    {{ payment.delivery_request?.reference_number || `DR-${String(payment.delivery_request_id).padStart(6, '0')}` }}
  </span>
</div>
                <div class="text-xs text-gray-500 dark:text-gray-400">
                  ID: DO-{{ String(payment.delivery_request_id).padStart(6, '0') }}
                  <span v-if="payment.created_at"> | Created: {{ formatDate(payment.created_at) }}</span>
                </div>
              </div>
              <!-- Status Badge -->
              <div class="text-right">
                <span :class="getStatusBadgeClass(payment)" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium">
                  {{ getStatusText(payment) }}
                </span>
                <div v-if="payment.verified_by || payment.rejected_by" class="text-xs text-gray-500 mt-1">
                  {{ (payment.verified_by || payment.rejected_by)?.name }}
                </div>
                <div v-if="(payment.verified_by || payment.rejected_by)?.employee_profile?.employee_id" class="text-xs text-gray-400">
                  ID: {{ (payment.verified_by || payment.rejected_by)?.employee_profile?.employee_id }}
                </div>
              </div>
            </div>

            <!-- Customer Info -->
            <div class="mb-3">
              <div class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-1">
                {{
                  payment.delivery_request?.sender?.name ||
                  payment.delivery_request?.sender?.company_name ||
                  payment.delivery_request?.receiver?.name ||
                  payment.delivery_request?.receiver?.company_name ||
                  'N/A'
                }}
              </div>
              <div class="text-xs text-gray-500 dark:text-gray-400 space-y-1">
                <div class="flex items-center gap-1">
                  <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                  </svg>
                  {{ getCustomerPhone(payment) || 'No phone' }}
                </div>
                <div class="flex items-center gap-1">
                  <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                  </svg>
                  {{ getCustomerEmail(payment) || 'No email' }}
                </div>
              </div>
            </div>

            <!-- Payment Details -->
            <div class="grid grid-cols-2 gap-4 mb-3">
              <div>
                <div class="text-xs text-gray-500 dark:text-gray-400">Amount</div>
                <div class="text-sm font-semibold text-gray-900 dark:text-gray-100">
                  ₱{{ Number(payment.amount).toFixed(2) }}
                </div>
              </div>
              <div>
                <div class="text-xs text-gray-500 dark:text-gray-400">Collected At</div>
                <div class="text-sm text-gray-900 dark:text-gray-100">
                  {{ payment.collected_at ? formatDate(payment.collected_at) : 'Not collected' }}
                </div>
              </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex justify-between gap-2">
              <PrimaryButton
                @click="router.visit(route('collector.payments.show', payment.id))"
                class="!px-3 !py-2 !text-xs flex-1 flex items-center justify-center gap-1"
              >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0zm6 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                Details
              </PrimaryButton>

              <PrimaryButton
                v-if="payment.rejected_by"
                @click="resubmitPayment(payment)"
                class="!px-3 !py-2 !text-xs flex-1 flex items-center justify-center gap-1 bg-orange-600 hover:bg-orange-700"
              >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
                Resubmit
              </PrimaryButton>

              <DangerButton
                v-if="payment.can_delete && !payment.verified_by && !payment.rejected_by"
                @click="deletePayment(payment.id)"
                class="!px-3 !py-2 !text-xs flex items-center justify-center gap-1"
              >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
                Delete
              </DangerButton>
            </div>
          </div>

          <!-- Mobile Empty State -->
          <div v-if="!payments?.data || payments.data.length === 0" class="text-center py-8">
            <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-6">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-gray-400 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25zM6.75 12h.008v.008H6.75V12zm0 3h.008v.008H6.75V15zm0 3h.008v.008H6.75V18z"/>
              </svg>
              <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-2">No collections found</h3>
              <p class="text-gray-500 dark:text-gray-400">
                {{ search ? 'Try adjusting your search terms' : 'No payment collections recorded yet' }}
              </p>
            </div>
          </div>

          <!-- Mobile Pagination -->
          <Pagination 
            v-if="payments?.last_page > 1"
            :pagination="payments" 
            @page-changed="handlePageChange" 
            class="mt-6"
          />
        </div>

        <!-- Desktop View - Custom Table -->
        <div class="hidden sm:block justify-center items-center">
          <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg border border-gray-200 dark:border-gray-700 w-full">
            <div class="overflow-x-auto">
              <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                  <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Reference</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Customer</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Amount</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Collected At</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Actions</th>
                  </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                  <tr
                    v-for="payment in payments?.data || []"
                    :key="payment.id"
                    class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors duration-150"
                  >
                    <!-- Reference Column -->
                    <td class="px-6 py-4 whitespace-nowrap">
                      <div class="flex flex-col">
                       <div class="flex items-center gap-2">
  <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100">
    Reference #
  </span>
  <span class="font-bold text-green-700 dark:text-green-300 tracking-wide">
    {{ payment.delivery_request?.reference_number || `DR-${String(payment.delivery_request_id).padStart(6, '0')}` }}
  </span>
</div>
                        <div class="mt-1 flex flex-wrap items-center gap-2 text-xs text-gray-500 dark:text-gray-300">
                          <span>
                            Delivery ID: DO-{{ String(payment.delivery_request_id).padStart(6, '0') }}
                          </span>
                          <span v-if="payment.created_at">
                            | Created: {{ formatDate(payment.created_at) }}
                          </span>
                        </div>
                      </div>
                    </td>

                    <!-- Status Column -->
                    <td class="px-6 py-4 whitespace-nowrap">
                      <!-- Custom Status Badge -->
                      <span :class="getStatusBadgeClass(payment)" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium">
                        {{ getStatusText(payment) }}
                      </span>
                      
                      <!-- Show verified/rejected by name and employee ID -->
                      <div v-if="payment.verified_by || payment.rejected_by" class="text-xs text-gray-500 mt-1">
                        {{ (payment.verified_by || payment.rejected_by)?.name }}
                      </div>
                      <div v-if="(payment.verified_by || payment.rejected_by)?.employee_profile?.employee_id" class="text-xs text-gray-400">
                        ID: {{ (payment.verified_by || payment.rejected_by)?.employee_profile?.employee_id }}
                      </div>
                    </td>

                    <!-- Customer Column -->
                    <td class="px-6 py-4 whitespace-nowrap">
                      <div class="text-sm text-gray-900 dark:text-gray-100 font-medium">
                        {{
                          payment.delivery_request?.sender?.name ||
                          payment.delivery_request?.sender?.company_name ||
                          payment.delivery_request?.receiver?.name ||
                          payment.delivery_request?.receiver?.company_name ||
                          'N/A'
                        }}
                      </div>
                      <div class="text-xs text-gray-500 dark:text-gray-400 flex items-center gap-1 mt-1">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                        </svg>
                        {{ getCustomerPhone(payment) || 'No phone' }}
                      </div>
                      <div class="text-xs text-gray-500 dark:text-gray-400 flex items-center gap-1 mt-1">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                        {{ getCustomerEmail(payment) || 'No email' }}
                      </div>
                    </td>

                    <!-- Amount Column -->
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900 dark:text-gray-100">
                      ₱{{ Number(payment.amount).toFixed(2) }}
                    </td>

                    <!-- Collected At Column -->
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                      {{ payment.collected_at ? formatDate(payment.collected_at) : 'Not collected' }}
                    </td>

                    <!-- Actions Column -->
                    <td class="px-6 py-4 whitespace-nowrap">
                      <div class="flex space-x-2 justify-end">
                        <!-- View Details Button -->
                        <PrimaryButton
                          @click="router.visit(route('collector.payments.show', payment.id))"
                          class="!px-3 !py-2 !text-xs flex items-center justify-center gap-1"
                          title="View Details"
                        >
                          <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0zm6 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                          </svg>
                          Details
                        </PrimaryButton>

                        <!-- Resubmit Button for Rejected Payments -->
                        <PrimaryButton
                          v-if="payment.rejected_by"
                          @click="resubmitPayment(payment)"
                          class="!px-3 !py-2 !text-xs flex items-center justify-center gap-1 bg-orange-600 hover:bg-orange-700"
                          title="Resubmit Payment"
                        >
                          <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                          </svg>
                          Resubmit
                        </PrimaryButton>

                        <!-- Delete Button -->
                        <DangerButton
                          v-if="payment.can_delete && !payment.verified_by && !payment.rejected_by"
                          @click="deletePayment(payment.id)"
                          class="!px-3 !py-2 !text-xs flex items-center justify-center gap-1"
                          title="Delete Payment"
                        >
                          <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                          </svg>
                          Delete
                        </DangerButton>
                      </div>
                    </td>
                  </tr>
                  
                  <!-- Empty State -->
                  <tr v-if="!payments?.data || payments.data.length === 0">
                    <td colspan="6" class="px-6 py-8 text-center">
                      <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-6 max-w-md mx-auto">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-gray-400 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25zM6.75 12h.008v.008H6.75V12zm0 3h.008v.008H6.75V15zm0 3h.008v.008H6.75V18z"/>
                        </svg>
                        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-2">No collections found</h3>
                        <p class="text-gray-500 dark:text-gray-400 mb-3">
                          {{ search ? 'Try adjusting your search terms' : 'No payment collections recorded yet' }}
                        </p>
                      </div>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <!-- Desktop Pagination -->
            <Pagination 
              v-if="payments?.last_page > 1"
              :pagination="payments" 
              @page-changed="handlePageChange" 
              class="mt-4 border-t border-gray-200 dark:border-gray-700 p-4"
            />
          </div>
        </div>
      </div>
    </div>
  </EmployeeLayout>
</template>

<script setup>
import EmployeeLayout from '@/Layouts/EmployeeLayout.vue';
import SearchInput from '@/Components/SearchInput.vue';
import SelectInput from '@/Components/SelectInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import Pagination from '@/Components/Pagination.vue';
import { router } from '@inertiajs/vue3';
import { ref, watch, computed } from 'vue';
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

const statusOptions = computed(() => [
  { value: '', label: 'All Statuses' },
  { value: 'pending', label: 'Pending Verification' },
  { value: 'verified', label: 'Verified' },
  { value: 'rejected', label: 'Rejected' }
]);

function getStatusBadgeClass(payment) {
  if (payment.verified_by) {
    return 'bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100'; // Green for verified
  }
  if (payment.rejected_by) {
    return 'bg-red-100 text-red-800 dark:bg-red-800 dark:text-red-100'; // Red for rejected
  }
  return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-800 dark:text-yellow-100'; // Yellow for pending verification
}

function getStatusText(payment) {
  if (payment.verified_by) return 'Verified';
  if (payment.rejected_by) return 'Rejected';
  return 'Pending Verification';
}

function getCustomerPhone(payment) {
  return payment.delivery_request?.sender?.mobile || 
         payment.delivery_request?.receiver?.mobile;
}

function getCustomerEmail(payment) {
  return payment.delivery_request?.sender?.email || 
         payment.delivery_request?.receiver?.email;
}

function resubmitPayment(payment) {
  router.visit(route('collector.payments.resubmit', {
    delivery: payment.delivery_request_id,
    payment: payment.id
  }));
}

function deletePayment(paymentId) {
  if (confirm('Are you sure you want to delete this payment? This action cannot be undone.')) {
    router.delete(route('collector.payments.destroy', paymentId), {
      preserveScroll: true,
      onSuccess: () => {
        // Success handled by Inertia
      }
    });
  }
}

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

<style>
.zoom-content {
  zoom: 0.9;
  transform-origin: top center;
}

/* Mobile optimizations */
@media (max-width: 640px) {
  .zoom-content {
    zoom: 1;
  }
}
</style>