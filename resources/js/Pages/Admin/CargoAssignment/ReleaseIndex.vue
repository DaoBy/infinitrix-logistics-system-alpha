<template>

  <EmployeeLayout>

    <template #header>

      <div class="flex justify-between items-center px-6">

        <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">

          Ready for Release

        </h2>

        <div class="flex space-x-2 items-center">

          <SearchInput 

            v-model="search" 

            placeholder="Search by DO#, Reference #, customer..." 

            class="w-[28rem]"

          />

          <SelectInput

            v-model="paymentType"

            :options="[

              { value: '', label: 'All Payment Types' },

              { value: 'prepaid', label: 'Prepaid' },

              { value: 'postpaid', label: 'Postpaid' }

            ]"

            placeholder="Payment Type"

            class="w-60"

          />

          <PrimaryButton @click="refreshOrders" class="inline-flex items-center">

            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" viewBox="0 0 20 20" fill="currentColor">

              <path fill-rule="evenodd" d="M4 2a1 1 0 011 1v2.101a7.002 7.002 0 0111.601 2.566 1 1 0 11-1.885.666A5.002 5.002 0 005.999 7H9a1 1 0 010 2H4a1 1 0 01-1-1V3a1 1 0 011-1zm.008 9.057a1 1 0 011.276.61A5.002 5.002 0 0014.001 13H11a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0v-2.101a7.002 7.002 0 01-11.601-2.566 1 1 0 01.61-1.276z" clip-rule="evenodd" />

            </svg>

            Refresh

          </PrimaryButton>

        </div>

      </div>

    </template>



    <div class="px-2 py-4 max-w-5xl mx-auto">

      <!-- Pending Release Table -->

      <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg overflow-hidden border border-gray-200 dark:border-gray-700">

        <div class="px-4 py-5 sm:px-6 border-b border-gray-200 dark:border-gray-700 flex flex-col md:flex-row md:items-center md:justify-between gap-4">

          <div>

            <h3 class="text-lg font-medium leading-6 text-gray-900 dark:text-gray-100">

              Pending Release of Packages

            </h3>

            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">

              These are delivery orders that have been delivered and are now ready for package release. Use the release action to confirm and complete the delivery process.

            </p>

          </div>

        </div>

        <div class="overflow-x-auto">

          <table class="min-w-[900px] w-full divide-y divide-gray-200 dark:divide-gray-700 text-xs md:text-sm">

            <thead class="bg-gray-50 dark:bg-gray-700">

              <tr>

                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">

                  Reference #

                </th>

                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">

                  Customer

                </th>

                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">

                  Payment

                </th>

                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">

                  Destination

                </th>

                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">

                  Package Status

                </th>

                <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">

                  Actions

                </th>

              </tr>

            </thead>

            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">

              <tr v-for="order in orders?.data || []" :key="order.id">

                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100">

                  <div class="flex flex-col">

                    <div class="flex items-center gap-2">

                      <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-indigo-100 text-indigo-800 dark:bg-indigo-800 dark:text-indigo-100">

                        Reference #

                      </span>

                      <span class="font-bold text-indigo-900 dark:text-indigo-100 tracking-wide">

                        {{

                          order.delivery_request?.reference_number

                            || ('DR-' + (order.delivery_request?.id ?? order.id).toString().padStart(6, '0'))

                        }}

                      </span>

                    </div>

                    <div class="mt-1 flex flex-wrap items-center gap-2 text-xs text-gray-500 dark:text-gray-300">

                      <span>

                        Order ID: DO-{{ order.id.toString().padStart(6, '0') }}

                      </span>

                      <span v-if="order.delivery_request?.created_at">

                        | Created: {{ formatDate(order.delivery_request.created_at) }}

                      </span>

                    </div>

                  </div>

                </td>

                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">

                  {{

                    (order.delivery_request?.sender?.name || order.delivery_request?.sender?.company_name)

                    || (order.delivery_request?.receiver?.name || order.delivery_request?.receiver?.company_name)

                    || 'N/A'

                  }}

                </td>

                <td class="px-6 py-4 whitespace-nowrap">

                  <span

                    :class="(['cash','gcash','bank'].includes((order.delivery_request?.payment_method || order.delivery_request?.payment_type || '').toLowerCase())

                      ? 'px-2 py-0.5 inline-flex items-center text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200'

                      : 'px-2 py-0.5 inline-flex items-center text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200'

                    )"

                  >

                    {{ (order.delivery_request?.payment_type || order.delivery_request?.payment_method || 'N/A').toUpperCase() }}

                  </span>

                  <span v-if="order.delivery_request?.payment_status" class="ml-2">

                    <PaymentStatusBadge :payment="order.delivery_request.payment" :delivery="order.delivery_request" />

                  </span>

                </td>

                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">

                  {{ order.delivery_request?.drop_off_region?.name || 'N/A' }}

                </td>

                <td class="px-6 py-4 whitespace-nowrap">

                  <!-- Package Status Summary -->

                  <div class="flex flex-col gap-1">

                    <div v-if="hasIncidentPackages(order)" class="flex items-center gap-1 text-red-600 dark:text-red-400">

                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">

                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/>

                      </svg>

                      <span class="text-xs font-medium">Has Issues: {{ countIncidentPackages(order) }}</span>

                    </div>

                    <div class="text-xs text-gray-500 dark:text-gray-400">

                      Delivered: {{ countDeliveredPackages(order) }}/{{ order.delivery_request?.packages?.length || 0 }}

                    </div>

                  </div>

                </td>

                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">

                  <div class="flex justify-end space-x-2">

                    <!-- Regular Release Button -->

                    <PrimaryButton

                      v-if="!hasIncidentPackages(order)"

                      @click="goToRelease(order.id)"

                      class="inline-flex items-center"

                      title="Release Packages"

                      style="padding-left: 0.75rem; padding-right: 0.75rem;"

                    >

                      <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">

                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12V7a2 2 0 00-2-2H6a2 2 0 00-2 2v5m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0H4" />

                      </svg>

                      Release

                    </PrimaryButton>

                    

                    <!-- Release with Issues Button -->

                    <button

                      v-if="hasIncidentPackages(order)"

                      @click="goToReleaseWithIssues(order.id)"

                      class="inline-flex items-center px-3 py-2 border border-red-300 text-red-700 bg-red-50 rounded-md text-xs font-medium hover:bg-red-100 hover:text-red-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 dark:border-red-600 dark:bg-red-900/30 dark:text-red-300 dark:hover:bg-red-900/50"

                      title="Release with Issues"

                    >

                      <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">

                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/>

                      </svg>

                      Release with Issues

                    </button>

                  </div>

                </td>

              </tr>

              <tr v-if="!orders?.data || orders.data.length === 0">

                <td colspan="6" class="px-6 py-4 text-center text-sm text-gray-500 dark:text-gray-400">

                  No orders ready for release

                </td>

              </tr>

            </tbody>

          </table>

        </div>

        <Pagination

          :pagination="orders"

          @page-changed="handlePageChange"

        />

      </div>



      <!-- Completed Releases Table -->

      <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg overflow-hidden border border-gray-200 dark:border-gray-700 mt-6">

        <div class="px-4 py-5 sm:px-6 border-b border-gray-200 dark:border-gray-700 flex flex-col md:flex-row md:items-center md:justify-between gap-4">

          <div>

            <h3 class="text-lg font-medium leading-6 text-gray-900 dark:text-gray-100">

              Completed Releases

            </h3>

            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">

              All delivery orders that have been fully released and marked as completed. You can view the details of each completed release.

            </p>

          </div>

        </div>

        <div class="overflow-x-auto">

          <table class="min-w-[900px] w-full divide-y divide-gray-200 dark:divide-gray-700 text-xs md:text-sm">

            <thead class="bg-gray-50 dark:bg-gray-700">

              <tr>

                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">

                  Reference #

                </th>

                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">

                  Customer

                </th>

                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">

                  Payment

                </th>

                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">

                  Destination

                </th>

                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">

                  Completion Status

                </th>

                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">

                  Completed At

                </th>

                <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">

                  Actions

                </th>

              </tr>

            </thead>

            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">

              <tr v-for="order in completedOrders?.data || []" :key="order.id">

                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100">

                  <div class="flex flex-col">

                    <div class="flex items-center gap-2">

                      <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-100">

                        Reference #

                      </span>

                      <span class="font-bold text-gray-900 dark:text-gray-100 tracking-wide">

                        {{

                          order.delivery_request?.reference_number

                            || ('DR-' + (order.delivery_request?.id ?? order.id).toString().padStart(6, '0'))

                        }}

                      </span>

                    </div>

                    <div class="mt-1 flex flex-wrap items-center gap-2 text-xs text-gray-500 dark:text-gray-300">

                      <span>

                        Order ID: DO-{{ order.id.toString().padStart(6, '0') }}

                      </span>

                      <span v-if="order.delivery_request?.created_at">

                        | Created: {{ formatDate(order.delivery_request.created_at) }}

                      </span>

                    </div>

                  </div>

                </td>

                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">

                  {{

                    (order.delivery_request?.sender?.name || order.delivery_request?.sender?.company_name)

                    || (order.delivery_request?.receiver?.name || order.delivery_request?.receiver?.company_name)

                    || 'N/A'

                  }}

                </td>

                <td class="px-6 py-4 whitespace-nowrap">

                  <span

                    :class="(['cash','gcash','bank'].includes((order.delivery_request?.payment_method || order.delivery_request?.payment_type || '').toLowerCase())

                      ? 'px-2 py-0.5 inline-flex items-center text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200'

                      : 'px-2 py-0.5 inline-flex items-center text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200'

                    )"

                  >

                    {{ (order.delivery_request?.payment_type || order.delivery_request?.payment_method || 'N/A').toUpperCase() }}

                  </span>

                  <span v-if="order.delivery_request?.payment_status" class="ml-2">

                    <PaymentStatusBadge :payment="order.delivery_request.payment" :delivery="order.delivery_request" />

                  </span>

                </td>

                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">

                  {{ order.delivery_request?.drop_off_region?.name || 'N/A' }}

                </td>

                <td class="px-6 py-4 whitespace-nowrap">

                  <span 

                    v-if="order.status === 'partially_delivered' || order.status === 'delivery_failed'"

                    class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium"

                    :class="{

                      'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200': order.status === 'partially_delivered',

                      'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200': order.status === 'delivery_failed'

                    }"

                  >

                    <svg v-if="order.status === 'partially_delivered'" class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">

                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/>

                    </svg>

                    {{ order.status === 'partially_delivered' ? 'Partially Delivered' : 'Delivery Failed' }}

                  </span>

                  <span 

                    v-else

                    class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200"

                  >

                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">

                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>

                    </svg>

                    Completed

                  </span>

                </td>

                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">

                  {{ formatDateTime(order.completed_at) }}

                </td>

                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">

                  <div class="flex justify-end space-x-2">

                    <SecondaryButton

                      @click="viewOrderDetails(order.id)"

                      class="inline-flex items-center"

                      title="View Details"

                      style="padding-left: 0.75rem; padding-right: 0.75rem;"

                    >

                      <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">

                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />

                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />

                      </svg>

                      View

                    </SecondaryButton>

                  </div>

                </td>

              </tr>

              <tr v-if="!completedOrders?.data || completedOrders.data.length === 0">

                <td colspan="7" class="px-6 py-4 text-center text-sm text-gray-500 dark:text-gray-400">

                  No completed releases

                </td>

              </tr>

            </tbody>

          </table>

        </div>

        <Pagination

          :pagination="completedOrders"

          @page-changed="handleCompletedPageChange"

        />

      </div>

    </div>

  </EmployeeLayout>

</template>



<script setup>

import { ref, watch, computed } from 'vue';

import { router } from '@inertiajs/vue3';

import EmployeeLayout from '@/Layouts/EmployeeLayout.vue';

import PrimaryButton from '@/Components/PrimaryButton.vue';

import SecondaryButton from '@/Components/SecondaryButton.vue';

import SearchInput from '@/Components/SearchInput.vue';

import SelectInput from '@/Components/SelectInput.vue';

import Pagination from '@/Components/Pagination.vue';

import PaymentStatusBadge from '@/Components/PaymentStatusBadge.vue';

import { debounce } from 'lodash';



const props = defineProps({

  orders: Object,

  completedOrders: Object,

  filters: Object

});



const search = ref(props.filters?.search || '');

const paymentType = ref(props.filters?.payment_type || '');



// Helper methods for incident detection

const hasIncidentPackages = (order) => {

  return order.delivery_request?.packages?.some(pkg => 

    pkg.status === 'damaged_in_transit' || pkg.status === 'lost_in_transit'

  ) || false;

};



const countIncidentPackages = (order) => {

  return order.delivery_request?.packages?.filter(pkg => 

    pkg.status === 'damaged_in_transit' || pkg.status === 'lost_in_transit'

  ).length || 0;

};



const countDeliveredPackages = (order) => {

  return order.delivery_request?.packages?.filter(pkg => 

    pkg.status === 'delivered'

  ).length || 0;

};



const refreshOrders = () => {

  router.reload();

};



const goToRelease = (orderId) => {

  router.visit(route('cargo-assignments.delivery-completion.release', orderId));

};



const goToReleaseWithIssues = (orderId) => {

  router.visit(route('cargo-assignments.delivery-completion.release', orderId));

};



const viewOrderDetails = (orderId) => {

  router.visit(route('cargo-assignments.show', orderId));

};



const handlePageChange = (page) => {

  router.get(route('cargo-assignments.delivery-completion.ready-for-release'), {

    ...props.filters,

    page

  }, {

    preserveState: true,

    replace: true

  });

};



const handleCompletedPageChange = (page) => {

  router.get(route('cargo-assignments.delivery-completion.ready-for-release'), {

    ...props.filters,

    completed_page: page

  }, {

    preserveState: true,

    replace: true

  });

};



function formatDate(dateString) {

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

}



function formatDateTime(dateTimeString) {

  if (!dateTimeString) return 'N/A';

  try {

    const date = new Date(dateTimeString);

    return date.toLocaleDateString('en-US', {

      year: 'numeric',

      month: 'short',

      day: 'numeric',

      hour: '2-digit',

      minute: '2-digit'

    });

  } catch (e) {

    return 'Invalid date';

  }

}



// Debounced search

const debouncedSearch = debounce(() => {

  router.get(route('cargo-assignments.delivery-completion.ready-for-release'), {

    search: search.value,

    payment_type: paymentType.value

  }, {

    preserveState: true,

    replace: true

  });

}, 300);



watch([search, paymentType], debouncedSearch);

</script>