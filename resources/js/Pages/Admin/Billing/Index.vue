<template>
  <EmployeeLayout>
    <template #header>
      <div class="flex justify-between items-center px-6">
        <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
          Waybills Management
        </h2>
        <div class="flex space-x-2">
          <SearchInput v-model="search" placeholder="Search waybills..." />
          <PrimaryButton @click="refreshWaybills" class="inline-flex items-center">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" viewBox="0 0 20 20" fill="currentColor">
              <path fill-rule="evenodd" d="M4 2a1 1 0 011 1v2.101a7.002 7.002 0 0111.601 2.566 1 1 0 11-1.885.666A5.002 5.002 0 005.999 7H9a1 1 0 010 2H4a1 1 0 01-1-1V3a1 1 0 011-1zm.008 9.057a1 1 0 011.276.61A5.002 5.002 0 0014.001 13H11a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0v-2.101a7.002 7.002 0 01-11.601-2.566 1 1 0 01.61-1.276z" clip-rule="evenodd" />
            </svg>
            Refresh
          </PrimaryButton>
        </div>
      </div>
    </template>

    <div class="px-6 py-4 space-y-6">
      <!-- Pending Waybills Section -->
      <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg overflow-hidden border border-gray-200 dark:border-gray-700">
        <div class="px-4 py-5 sm:px-6 border-b border-gray-200 dark:border-gray-700">
          <h3 class="text-lg font-medium leading-6 text-gray-900 dark:text-gray-100">
            Pending Waybill Generation
          </h3>
          <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
            Delivery requests with dispatched orders but no waybill yet
          </p>
        </div>
        
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-700">
              <tr>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                  Reference
                </th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                  Sender
                </th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                  Payment
                </th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                  Locations
                </th>
                <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                  Actions
                </th>
              </tr>
            </thead>
            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
              <tr v-for="request in limitedPendingWaybills" :key="request.id + '-row'">
                <!-- Reference Column -->
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="flex items-center space-x-2">
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-green-100 text-green-800">
                      Ref
                    </span>
                    <span class="font-bold text-green-700 tracking-wide text-sm">
                      {{ request.reference_number || `DR-${request.id.toString().padStart(6, '0')}` }}
                    </span>
                  </div>
                  <div class="text-xs text-gray-500 mt-1">
                    ID: DR-{{ String(request.id).padStart(6, '0') }}
                    <span v-if="request.created_at"> | Created: {{ formatDate(request.created_at) }}</span>
                  </div>
                </td>

                <!-- Sender Column -->
                <td class="px-6 py-4 whitespace-nowrap">
                  <div v-if="request.sender">
                    <div class="text-sm font-medium text-gray-900 mb-1">
                      {{ 
                        request.sender?.name ||
                        request.sender?.company_name ||
                        'N/A'
                      }}
                    </div>
                    <div class="text-xs text-gray-500 space-y-1">
                      <div class="flex items-center gap-1" v-if="getCustomerPhone(request.sender)">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                        </svg>
                        {{ getCustomerPhone(request.sender) }}
                      </div>
                      <div class="flex items-center gap-1" v-if="getCustomerEmail(request.sender)">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                        {{ getCustomerEmail(request.sender) }}
                      </div>
                      <div v-if="!getCustomerPhone(request.sender) && !getCustomerEmail(request.sender)" class="text-gray-400">
                        No contact info
                      </div>
                    </div>
                  </div>
                  <div v-else class="text-sm text-gray-500">No sender info</div>
                </td>

                <!-- Payment Column -->
                <td class="px-6 py-4 whitespace-nowrap">
                  <span :class="paymentStatusClasses(request)">
                    {{ formatPaymentType(request.payment_type) }} - {{ request.is_paid ? 'PAID' : 'PENDING' }}
                  </span>
                  <div class="text-xs text-gray-500 mt-1">
                    {{ formatCurrency(request.total_price) }}
                  </div>
                </td>

               <!-- Locations Column -->
<td class="px-6 py-4 whitespace-nowrap">
  <div class="text-sm space-y-1">
    <div>
      <span class="font-medium text-gray-700">Pick-Up:</span>
      <span class="text-gray-600 ml-1">{{ request.pick_up_region?.name || 'N/A' }}</span>
    </div>
    <div>
      <span class="font-medium text-gray-700">Drop-Off:</span>
      <span class="text-gray-600 ml-1">{{ request.drop_off_region?.name || 'N/A' }}</span>
    </div>
  </div>
</td>

                <!-- Actions Column -->
                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                  <div class="flex justify-end space-x-2">
                    <PrimaryButton 
                      @click="printInitialWaybill(request)" 
                      class="inline-flex items-center"
                      v-if="request.payment_type === 'prepaid' && request.is_paid && request.waybill && request.waybill.id"
                    >
                      <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M5 4v3H4a2 2 0 00-2 2v3a2 2 0 002 2h1v2a2 2 0 002 2h6a2 2 0 002-2v-2h1a2 2 0 002-2V9a2 2 0 00-2-2h-1V4a2 2 0 00-2-2H7a2 2 0 00-2 2zm8 0H7v3h6V4zm0 8H7v4h6v-4z" clip-rule="evenodd" />
                      </svg>
                      Print Initial
                    </PrimaryButton>
                    <PrimaryButton 
                      @click="generateWaybill(request.id)" 
                      class="inline-flex items-center"
                      v-if="!request.waybill"
                    >
                      <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 6a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1zm1 3a1 1 0 100 2h6a1 1 0 100-2H7z" clip-rule="evenodd" />
                      </svg>
                      Generate
                    </PrimaryButton>
                  </div>
                </td>
              </tr>
              <tr v-if="limitedPendingWaybills.length === 0">
                <td colspan="5" class="px-6 py-4 text-center text-sm text-gray-500 dark:text-gray-400">
                  No pending waybills found
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="bg-white dark:bg-gray-800 px-4 py-3 flex items-center justify-center border-t border-gray-200 dark:border-gray-700 sm:px-6">
          <div class="flex items-center space-x-2">
            <button
              @click="goToPendingPage(pendingWaybills.current_page - 1)"
              :disabled="pendingWaybills.current_page <= 1"
              class="px-3 py-1 rounded border text-sm"
            >Previous</button>
            <span>Page {{ pendingWaybills.current_page }} of {{ pendingWaybills.last_page }}</span>
            <button
              @click="goToPendingPage(pendingWaybills.current_page + 1)"
              :disabled="pendingWaybills.current_page >= pendingWaybills.last_page"
              class="px-3 py-1 rounded border text-sm"
            >Next</button>
          </div>
        </div>
      </div>

      <!-- Generated Waybills Section -->
      <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg overflow-hidden border border-gray-200 dark:border-gray-700">
        <div class="px-4 py-5 sm:px-6 border-b border-gray-200 dark:border-gray-700 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
          <div>
            <h3 class="text-lg font-medium leading-6 text-gray-900 dark:text-gray-100">
              Generated Waybills
            </h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
              Delivery requests with generated waybills
            </p>
          </div>
          <div class="flex items-center gap-2">
            <SelectInput
              v-model="paymentTypeFilter"
              :options="[
                { value: '', label: 'All Payment Types' },
                { value: 'prepaid', label: 'Prepaid' },
                { value: 'postpaid', label: 'Postpaid' }
              ]"
              placeholder="Filter by Payment Type"
              class="w-48"
            />
            <button
              @click="toggleSort('created_at')"
              class="ml-2 px-2 py-1 text-xs rounded bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200"
            >
              Sort: {{ sortDirection === 'asc' ? 'Oldest' : 'Newest' }}
            </button>
          </div>
        </div>
        
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-700">
              <tr>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                  Waybill #
                </th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                  Reference
                </th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                  Sender
                </th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                  Payment
                </th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                  Status
                </th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                  Locations
                </th>
                <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                  Actions
                </th>
              </tr>
            </thead>
            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
              <tr v-for="waybill in limitedFilteredWaybills" :key="waybill.id">
                <!-- Waybill Number -->
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100">
                  {{ waybill.waybill_number || 'N/A' }}
                  <div class="text-xs text-gray-500 mt-1">
                    {{ formatDate(waybill.created_at) }}
                  </div>
                </td>

                <!-- Reference Column -->
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="flex items-center space-x-2">
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-green-100 text-green-800">
                      Ref
                    </span>
                    <span class="font-bold text-green-700 tracking-wide text-sm">
                      {{ waybill.delivery_request?.reference_number || `DR-${waybill.delivery_request_id?.toString().padStart(6, '0')}` }}
                    </span>
                  </div>
                  <div class="text-xs text-gray-500 mt-1">
                    ID: DR-{{ String(waybill.delivery_request_id).padStart(6, '0') }}
                  </div>
                </td>

                <!-- Sender Column -->
                <td class="px-6 py-4 whitespace-nowrap">
                  <div v-if="waybill.delivery_request?.sender">
                    <div class="text-sm font-medium text-gray-900 mb-1">
                      {{ 
                        waybill.delivery_request.sender?.name ||
                        waybill.delivery_request.sender?.company_name ||
                        'N/A'
                      }}
                    </div>
                    <div class="text-xs text-gray-500 space-y-1">
                      <div class="flex items-center gap-1" v-if="getCustomerPhone(waybill.delivery_request.sender)">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                        </svg>
                        {{ getCustomerPhone(waybill.delivery_request.sender) }}
                      </div>
                      <div class="flex items-center gap-1" v-if="getCustomerEmail(waybill.delivery_request.sender)">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                        {{ getCustomerEmail(waybill.delivery_request.sender) }}
                      </div>
                      <div v-if="!getCustomerPhone(waybill.delivery_request.sender) && !getCustomerEmail(waybill.delivery_request.sender)" class="text-gray-400">
                        No contact info
                      </div>
                    </div>
                  </div>
                  <div v-else class="text-sm text-gray-500">No sender info</div>
                </td>

                <!-- Payment Column -->
                <td class="px-6 py-4 whitespace-nowrap">
                  <span :class="paymentStatusClasses(waybill.delivery_request)">
                    {{ formatPaymentType(waybill.delivery_request?.payment_type) || 'N/A' }}
                    <span v-if="waybill.delivery_request">
                      - {{ waybill.delivery_request.is_paid ? 'PAID' : 'PENDING' }}
                    </span>
                  </span>
                  <div class="text-xs text-gray-500 mt-1" v-if="waybill.delivery_request?.total_price">
                    {{ formatCurrency(waybill.delivery_request.total_price) }}
                  </div>
                </td>

                <!-- Status Column -->
                <td class="px-6 py-4 whitespace-nowrap">
                  <span :class="statusClasses(waybill.delivery_request?.delivery_order?.status)">
                    {{ formatOrderStatus(waybill.delivery_request?.delivery_order?.status) }}
                  </span>
                </td>

                <!-- Locations Column -->
               <!-- Locations Column -->
<td class="px-6 py-4 whitespace-nowrap">
  <div class="text-sm space-y-1">
    <div>
      <span class="font-medium text-gray-700">Pick-Up:</span>
      <span class="text-gray-600 ml-1">{{ waybill.delivery_request?.pick_up_region?.name || 'N/A' }}</span>
    </div>
    <div>
      <span class="font-medium text-gray-700">Drop-Off:</span>
      <span class="text-gray-600 ml-1">{{ waybill.delivery_request?.drop_off_region?.name || 'N/A' }}</span>
    </div>
  </div>
</td>

                <!-- Actions Column -->
                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                  <div class="flex justify-end space-x-2">
                    <PrimaryButton 
                      @click="printWaybill(waybill)" 
                      class="inline-flex items-center"
                    >
                      <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M5 4v3H4a2 2 0 00-2 2v3a2 2 0 002 2h1v2a2 2 0 002 2h6a2 2 0 002-2v-2h1a2 2 0 002-2V9a2 2 0 00-2-2h-1V4a2 2 0 00-2-2H7a2 2 0 00-2 2zm8 0H7v3h6V4zm0 8H7v4h6v-4z" clip-rule="evenodd" />
                    </svg>
                      <span v-if="waybill.delivery_request?.delivery_order?.status === 'completed'">Print Final</span>
                      <span v-else>Print Initial</span>
                    </PrimaryButton>
                    <SecondaryButton @click="viewWaybill(waybill)" class="inline-flex items-center">
                      <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" viewBox="0 0 20 20" fill="currentColor">
                        <path d="M10 12a2 2 0 100-4 2 2 0 000 4z" />
                        <path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd" />
                      </svg>
                      View
                    </SecondaryButton>
                  </div>
                </td>
              </tr>
              <tr v-if="limitedFilteredWaybills.length === 0">
                <td colspan="7" class="px-6 py-4 text-center text-sm text-gray-500 dark:text-gray-400">
                  No waybills found
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="bg-white dark:bg-gray-800 px-4 py-3 flex items-center justify-center border-t border-gray-200 dark:border-gray-700 sm:px-6">
          <div class="flex items-center space-x-2">
            <button
              @click="goToWaybillsPage(waybills.current_page - 1)"
              :disabled="waybills.current_page <= 1"
              class="px-3 py-1 rounded border text-sm"
            >Previous</button>
            <span>Page {{ waybills.current_page }} of {{ waybills.last_page }}</span>
            <button
              @click="goToWaybillsPage(waybills.current_page + 1)"
              :disabled="waybills.current_page >= waybills.last_page"
              class="px-3 py-1 rounded border text-sm"
            >Next</button>
          </div>
        </div>
      </div>
    </div>
  </EmployeeLayout>
</template>

<script setup>
import EmployeeLayout from '@/Layouts/EmployeeLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import SearchInput from '@/Components/SearchInput.vue';
import SelectInput from '@/Components/SelectInput.vue';
import { router } from '@inertiajs/vue3';
import { ref, watch, computed } from 'vue';
import { debounce } from 'lodash';

const props = defineProps({
  waybills: {
    type: Object,
    required: true,
    default: () => ({
      data: [],
      links: [],
      current_page: 1,
      last_page: 1
    })
  },
  pendingWaybills: {
    type: Object,
    required: true,
    default: () => ({
      data: [],
      links: [],
      current_page: 1,
      last_page: 1
    })
  },
  filters: {
    type: Object,
    default: () => ({})
  }
});

const search = ref(props.filters.search);
const paymentTypeFilter = ref('');
const sortDirection = ref('desc');
const sortField = ref('created_at');

// Add helper functions for customer contact info
const getCustomerPhone = (customer) => {
  if (!customer) return null;
  return customer.mobile || customer.phone || null;
};

const getCustomerEmail = (customer) => {
  if (!customer) return null;
  return customer.email || null;
};

const formatCurrency = (amount) => {
  if (!amount) return '₱0.00';
  return new Intl.NumberFormat('en-PH', {
    style: 'currency',
    currency: 'PHP'
  }).format(amount);
};

watch(search, debounce((value) => {
  router.get(route('waybills.index'), { search: value }, {
    preserveState: true,
    replace: true
  });
}, 300));

const filteredWaybills = computed(() => {
  let result = props.waybills.data || [];

  // Filter by payment type
  if (paymentTypeFilter.value) {
    result = result.filter(wb =>
      wb.delivery_request?.payment_type === paymentTypeFilter.value
    );
  }

  // Sort by created_at
  result = result.slice().sort((a, b) => {
    const aValue = a[sortField.value] || '';
    const bValue = b[sortField.value] || '';
    if (aValue < bValue) return sortDirection.value === 'asc' ? -1 : 1;
    if (aValue > bValue) return sortDirection.value === 'asc' ? 1 : -1;
    return 0;
  });

  return result;
});

function toggleSort(field) {
  if (sortField.value === field) {
    sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc';
  } else {
    sortField.value = field;
    sortDirection.value = 'asc';
  }
}

const formatDate = (dateString) => {
  if (!dateString) return 'N/A';
  try {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
      year: 'numeric',
      month: 'short',
      day: 'numeric'
    });
  } catch (e) {
    return 'Invalid date';
  }
};

const formatPaymentType = (paymentType) => {
  if (!paymentType) return 'N/A';
  return paymentType.charAt(0).toUpperCase() + paymentType.slice(1);
};

const formatOrderStatus = (status) => {
  if (!status) return 'N/A';
  
  const statusMap = {
    'pending': 'Pending',
    'ready': 'Ready',
    'assigned': 'Assigned',
    'in_transit': 'In Transit',
    'delivered': 'Delivered',
    'partially_delivered': 'Partially Delivered',
    'delivery_failed': 'Delivery Failed',
    'completed': 'Completed',
    'cancelled': 'Cancelled'
  };
  
  return statusMap[status] || status.split('_').map(word => 
    word.charAt(0).toUpperCase() + word.slice(1)
  ).join(' ');
};

const statusClasses = (status) => {
  const base = 'px-2 inline-flex text-xs leading-5 font-semibold rounded-full';
  
  switch (status) {
    case 'pending':
      return `${base} bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200`;
    case 'ready':
      return `${base} bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200`;
    case 'assigned':
      return `${base} bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-200`;
    case 'in_transit':
      return `${base} bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200`;
    case 'delivered':
      return `${base} bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200`;
    case 'partially_delivered':
      return `${base} bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200`;
    case 'delivery_failed':
      return `${base} bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200`;
    case 'completed':
      return `${base} bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200`;
    case 'cancelled':
      return `${base} bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200`;
    default:
      return `${base} bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300`;
  }
};

const paymentStatusClasses = (deliveryRequest) => {
  const base = 'px-2 inline-flex text-xs leading-5 font-semibold rounded-full';

  if (!deliveryRequest) return `${base} bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300`;

  if (deliveryRequest.payment_type === 'prepaid') {
    return deliveryRequest.is_paid
      ? `${base} bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200`
      : `${base} bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200`;
  } else {
    return deliveryRequest.delivery_order?.status === 'completed'
      ? `${base} bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200`
      : `${base} bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200`;
  }
};

const refreshWaybills = () => {
  router.get(route('waybills.index'), {}, {
    preserveState: true,
    replace: true,
    only: ['waybills', 'pendingWaybills']
  });
};

const viewWaybill = (waybill) => {
  router.visit(route('waybills.show', waybill.id));
};

const printWaybill = (waybill) => {
  // Print final if completed, otherwise print initial
  const isFinal = waybill.delivery_request?.delivery_order?.status === 'completed';
  window.open(route('waybills.preview', { id: waybill.id, final: isFinal }), '_blank');
};

const printInitialWaybill = (request) => {
  if (request.waybill && request.waybill.id) {
    window.open(route('waybills.preview', { id: request.waybill.id, final: false }), '_blank');
  }
};

const generateWaybill = (deliveryRequestId) => {
  router.post(route('waybills.generate', { deliveryRequest: deliveryRequestId }), {}, {
    preserveScroll: true,
    onSuccess: () => {
      refreshWaybills();
    }
  });
}

function goToWaybillsPage(page) {
  if (page >= 1 && page <= props.waybills.last_page) {
    router.get(route('waybills.index'), { ...props.filters, page }, {
      preserveState: true,
      replace: true,
    });
  }
}

function goToPendingPage(page) {
  if (page >= 1 && page <= props.pendingWaybills.last_page) {
    router.get(route('waybills.index'), { ...props.filters, pending_page: page }, {
      preserveState: true,
      replace: true,
    });
  }
}

const limitedPendingWaybills = computed(() => {
  return props.pendingWaybills.data ? props.pendingWaybills.data.slice(0, 5) : [];
});

const limitedFilteredWaybills = computed(() => {
  return filteredWaybills.value ? filteredWaybills.value.slice(0, 5) : [];
});
</script>