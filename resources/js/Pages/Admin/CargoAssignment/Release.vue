<template>

  <EmployeeLayout>

    <template #header>

      <div class="flex justify-between items-center px-6">

        <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">

          Package Release

        </h2>

        <PrimaryButton

          @click="goToReleases"

          class="inline-flex items-center"

        >

          <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">

            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />

          </svg>

          Back to releases

        </PrimaryButton>

      </div>

    </template>



    <div class="px-2 md:px-6 py-4 max-w-5xl mx-auto">

      <!-- Order Info -->

      <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg border border-gray-200 dark:border-gray-700 mb-6">

        <div class="flex flex-col md:flex-row md:items-center md:justify-between p-4 bg-indigo-50 dark:bg-indigo-900 border-b border-gray-200 dark:border-gray-700">

          <div>

            <div class="flex items-center gap-2">

              <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-indigo-100 text-indigo-800 dark:bg-indigo-800 dark:text-indigo-100">

                Reference #

              </span>

              <span class="text-lg font-bold text-indigo-900 dark:text-indigo-100 tracking-wide">

                {{ order?.deliveryRequest?.reference_number || ('DR-' + (order?.deliveryRequest?.id ?? order?.id).toString().padStart(6, '0')) }}

              </span>

            </div>

            <div class="mt-1 text-xs text-gray-500 dark:text-gray-300">

              Order ID: DO-{{ order?.id?.toString().padStart(6, '0') || '' }}

              <span v-if="order?.deliveryRequest?.created_at">&nbsp;|&nbsp;Created: {{ formatDate(order?.deliveryRequest?.created_at) }}</span>

            </div>

          </div>

          <div class="mt-3 md:mt-0 flex items-center gap-2">

            <!-- Incident Warning Badge -->

            <span 

              v-if="hasIncidentPackages"

              class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-red-100 text-red-800 dark:bg-red-800 dark:text-red-100"

            >

              <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">

                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z" />

              </svg>

              Has Package Issues

            </span>

            <span 

              class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100"

            >

              <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">

                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />

              </svg>

              Ready for Release

            </span>

          </div>

        </div>

        <div class="p-4 md:p-6">

          <!-- Incident Alert -->

          <div v-if="hasIncidentPackages" class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg dark:bg-red-900/20 dark:border-red-700">

            <div class="flex items-start">

              <svg class="w-5 h-5 text-red-400 mt-0.5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">

                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/>

              </svg>

              <div>

                <h4 class="text-sm font-medium text-red-800 dark:text-red-200">

                  Delivery Has Package Issues

                </h4>

                <p class="mt-1 text-sm text-red-700 dark:text-red-300">

                  This delivery contains {{ incidentPackagesCount }} package(s) with reported issues. 

                  You can still complete the delivery for the successfully delivered packages.

                </p>

                <div class="mt-2 text-xs text-red-600 dark:text-red-400">

                  <p><strong>Successfully Delivered:</strong> {{ deliveredPackagesCount }} package(s)</p>

                  <p><strong>With Issues:</strong> {{ incidentPackagesCount }} package(s)</p>

                </div>

              </div>

            </div>

          </div>



          <div class="grid grid-cols-1 md:grid-cols-3 gap-4 md:gap-6">

            <!-- Sender Information -->

            <div class="bg-gray-50 dark:bg-gray-700/50 p-3 md:p-4 rounded-lg flex flex-col gap-1">

              <h3 class="text-base font-medium text-gray-900 dark:text-gray-100 mb-2">Sender</h3>

              <p class="font-medium truncate">{{ order?.deliveryRequest?.sender?.name ?? 'N/A' }}</p>

              <p class="text-xs text-gray-600 dark:text-gray-400 truncate">{{ order?.deliveryRequest?.sender?.mobile ?? '' }}</p>

              <p class="text-xs text-gray-600 dark:text-gray-400 truncate">{{ order?.deliveryRequest?.sender?.address ?? '' }}</p>

            </div>

            <!-- Receiver Information -->

            <div class="bg-gray-50 dark:bg-gray-700/50 p-3 md:p-4 rounded-lg flex flex-col gap-1">

              <h3 class="text-base font-medium text-gray-900 dark:text-gray-100 mb-2">Receiver</h3>

              <p class="font-medium truncate">{{ order?.deliveryRequest?.receiver?.name ?? 'N/A' }}</p>

              <p class="text-xs text-gray-600 dark:text-gray-400 truncate">{{ order?.deliveryRequest?.receiver?.mobile ?? '' }}</p>

              <p class="text-xs text-gray-600 dark:text-gray-400 truncate">{{ order?.deliveryRequest?.receiver?.address ?? '' }}</p>

            </div>

            <!-- Payment Status & Terms -->

            <div class="bg-gray-50 dark:bg-gray-700/50 p-3 md:p-4 rounded-lg flex flex-col gap-1">

              <h3 class="text-base font-medium text-gray-900 dark:text-gray-100 mb-2">Payment</h3>

              <div class="flex items-center gap-2 mb-1">

                <PaymentStatusBadge 

                  :payment="order?.deliveryRequest?.payment" 

                  :delivery="order?.deliveryRequest"

                />

                <span class="text-xs text-gray-600 dark:text-gray-400">

                  {{ order?.deliveryRequest?.payment_type?.toUpperCase() || '' }} - 

                  {{ order?.deliveryRequest?.payment_method?.toUpperCase() || '' }}

                </span>

              </div>

              <p class="text-xs text-gray-600 dark:text-gray-400" v-if="order?.deliveryRequest?.payment_type === 'postpaid'">

                <span class="font-semibold">Terms:</span>

                <span>

                  {{

                    order?.deliveryRequest?.payment_terms === 'net_7' ? 'Net 7' :

                    order?.deliveryRequest?.payment_terms === 'net_15' ? 'Net 15' :

                    order?.deliveryRequest?.payment_terms === 'net_30' ? 'Net 30' :

                    order?.deliveryRequest?.payment_terms === 'cnd' ? 'CND' :

                    (order?.deliveryRequest?.payment_terms || 'N/A')

                  }}

                </span>

                <span v-if="order?.deliveryRequest?.payment_due_date">

                  &nbsp;|&nbsp;<span class="font-semibold">Due:</span>

                  {{ formatDate(order?.deliveryRequest?.payment_due_date) }}

                </span>

              </p>

              <p class="font-medium text-gray-900 dark:text-gray-100 mt-1">

                ₱{{ toCurrency(order?.deliveryRequest?.total_price) }}

              </p>

            </div>

          </div>

        </div>

      </div>



      <!-- Packages List -->

      <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg border border-gray-200 dark:border-gray-700 mb-6">

        <div class="p-3 md:p-4 bg-gray-50 dark:bg-gray-700 border-b border-gray-200 dark:border-gray-700">

          <h3 class="font-medium text-gray-900 dark:text-gray-100">

            Packages ({{ order?.deliveryRequest?.packages?.length || 0 }})

          </h3>

          <div class="mt-1 text-sm text-gray-600 dark:text-gray-400">

            <span class="text-green-600 dark:text-green-400">✓ Delivered: {{ deliveredPackagesCount }}</span>

            <span v-if="hasIncidentPackages" class="ml-4 text-red-600 dark:text-red-400">⚠ Issues: {{ incidentPackagesCount }}</span>

          </div>

        </div>

        <div class="p-3 md:p-6 overflow-x-auto">

          <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">

            <thead class="bg-gray-50 dark:bg-gray-700">

              <tr>

                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Item</th>

                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Category</th>

                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Dimensions</th>

                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Weight</th>

                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Declared Value</th>

                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Package Status</th>

                <th v-if="hasIncidentPackages" class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Issue Details</th>

              </tr>

            </thead>

            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">

              <tr v-for="pkg in order?.deliveryRequest?.packages || []" :key="pkg.id" 

                  :class="{

                    'bg-red-50 dark:bg-red-900/20': pkg.status === 'damaged_in_transit' || pkg.status === 'lost_in_transit',

                    'bg-green-50 dark:bg-green-900/20': pkg.status === 'delivered'

                  }">

                <td class="px-3 py-2 whitespace-nowrap font-medium text-gray-900 dark:text-gray-100">

                  {{ pkg.item_name }}

                </td>

                <td class="px-3 py-2 whitespace-nowrap font-medium text-gray-900 dark:text-gray-100">

                  {{ pkg.category || 'N/A' }}

                </td>

                <td class="px-3 py-2 whitespace-nowrap text-gray-900 dark:text-gray-100">

                  {{ pkg.length }}×{{ pkg.width }}×{{ pkg.height }} cm

                </td>

                <td class="px-3 py-2 whitespace-nowrap text-gray-900 dark:text-gray-100">

                  {{ pkg.weight }} kg

                </td>

                <td class="px-3 py-2 whitespace-nowrap text-gray-900 dark:text-gray-100">

                  ₱{{ toCurrency(pkg.value) }}

                </td>

                <td class="px-3 py-2 whitespace-nowrap">

                  <StatusBadge 

                    :status="pkg.status" 

                    :variant="getStatusVariant(pkg.status)"

                  >

                    {{ formatPackageStatus(pkg.status) }}

                  </StatusBadge>

                </td>

                <td v-if="hasIncidentPackages" class="px-3 py-2 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">

                  <div v-if="pkg.status === 'damaged_in_transit' || pkg.status === 'lost_in_transit'">

                    <p class="text-xs font-medium text-red-600 dark:text-red-400">

                      {{ pkg.status === 'damaged_in_transit' ? 'Damaged' : 'Lost' }}

                    </p>

                    <p v-if="pkg.incident_description" class="text-xs text-gray-600 dark:text-gray-400 mt-1">

                      {{ pkg.incident_description }}

                    </p>

                    <p v-if="pkg.incident_reported_at" class="text-xs text-gray-500 dark:text-gray-500 mt-1">

                      Reported: {{ formatDateTime(pkg.incident_reported_at) }}

                    </p>

                  </div>

                  <span v-else class="text-xs text-gray-500 dark:text-gray-400">-</span>

                </td>

              </tr>

            </tbody>

          </table>

        </div>

      </div>



      <!-- Release Form -->

      <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg border border-gray-200 dark:border-gray-700">

        <div class="p-3 md:p-4 bg-gray-50 dark:bg-gray-700 border-b border-gray-200 dark:border-gray-700">

          <h3 class="font-medium text-gray-900 dark:text-gray-100">

            {{ hasIncidentPackages ? 'Complete Delivery with Issues' : 'Confirm Package Release' }}

          </h3>

          <p v-if="hasIncidentPackages" class="text-sm text-gray-600 dark:text-gray-400 mt-1">

            Complete delivery for successfully delivered packages. Packages with issues will remain in their current status.

          </p>

        </div>

        <div class="p-4 md:p-6">

          <!-- Prepaid Warning -->

          <div v-if="isPrepaid && !isPaymentVerified" class="bg-yellow-50 dark:bg-yellow-900/20 border-l-4 border-yellow-400 dark:border-yellow-600 p-3 md:p-4 mb-4 md:mb-6">

            <div class="flex">

              <div class="flex-shrink-0">

                <ExclamationTriangleIcon class="h-5 w-5 text-yellow-400 dark:text-yellow-500" />

              </div>

              <div class="ml-3">

                <p class="text-sm text-yellow-700 dark:text-yellow-300">

                  Prepaid payment must be verified before releasing packages.

                </p>

              </div>

            </div>

          </div>



          <!-- Incident Completion Warning -->

          <div v-if="hasIncidentPackages" class="bg-blue-50 dark:bg-blue-900/20 border-l-4 border-blue-400 dark:border-blue-600 p-3 md:p-4 mb-4 md:mb-6">

            <div class="flex">

              <div class="flex-shrink-0">

                <svg class="h-5 w-5 text-blue-400 dark:text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">

                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>

                </svg>

              </div>

              <div class="ml-3">

                <p class="text-sm text-blue-700 dark:text-blue-300">

                  <strong>Note:</strong> This delivery will be marked as 

                  <span v-if="deliveredPackagesCount > 0" class="font-semibold">"Partially Delivered"</span>

                  <span v-else class="font-semibold">"Delivery Failed"</span>

                  due to package issues. Only successfully delivered packages will be released.

                </p>

              </div>

            </div>

          </div>



          <form @submit.prevent="releasePackages">

            <!-- Receiver Confirmation (for postpaid) -->

            <div v-if="isPostpaid" class="grid grid-cols-1 md:grid-cols-2 gap-4 md:gap-6 mb-4 md:mb-6">

              <div>

                <div class="mb-1">

                  <span class="font-semibold">Receiver Name:</span>

                  <span>{{ order.deliveryRequest?.receiver?.name || 'N/A' }}</span>

                </div>

                <div>

                  <span class="font-semibold">Receiver Contact:</span>

                  <span>{{ order.deliveryRequest?.receiver?.mobile || 'N/A' }}</span>

                </div>

              </div>

            </div>



            <!-- Notes -->

            <TextArea

              v-model="form.notes"

              :label="hasIncidentPackages ? 'Completion Notes' : 'Release Notes'"

              :placeholder="hasIncidentPackages ? 'Add any notes about the partial delivery completion...' : 'Any special instructions or notes about this release...'"

              :error="form.errors.notes"

              class="mb-4"

            />



            <!-- Submit Button -->

            <div class="flex justify-end mt-4 md:mt-6">

              <PrimaryButton

                v-if="!hasIncidentPackages"

                type="submit"

                :disabled="!canRelease || form.processing"

              >

                <span v-if="form.processing">

                  <LoadingSpinner size="xs" class="mr-2" /> Processing...

                </span>

                <span v-else>

                  {{ isPrepaid ? 'Release Packages' : 'Complete Delivery' }}

                </span>

              </PrimaryButton>

              

              <!-- Complete with Issues Button -->

              <button

                v-else

                type="button"

                @click="completeWithIssues"

                :disabled="!canRelease || form.processing"

                class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700 focus:bg-red-700 active:bg-red-800 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition ease-in-out duration-150 disabled:opacity-50 disabled:cursor-not-allowed"

              >

                <span v-if="form.processing">

                  <LoadingSpinner size="xs" class="mr-2" /> Processing...

                </span>

                <span v-else>

                  <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">

                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/>

                  </svg>

                  Complete with Issues

                </span>

              </button>

            </div>

          </form>

        </div>

      </div>

    </div>

  </EmployeeLayout>

</template>



<script setup>

import { computed } from 'vue';

import { router } from '@inertiajs/vue3';

import EmployeeLayout from '@/Layouts/EmployeeLayout.vue';

import PrimaryButton from '@/Components/PrimaryButton.vue';

import SecondaryButton from '@/Components/SecondaryButton.vue';

import TextInput from '@/Components/TextInput.vue';

import TextArea from '@/Components/TextArea.vue';

import InputLabel from '@/Components/InputLabel.vue';

import InputError from '@/Components/InputError.vue';

import PaymentStatusBadge from '@/Components/PaymentStatusBadge.vue';

import StatusBadge from '@/Components/StatusBadge.vue';

import LoadingSpinner from '@/Components/LoadingSpinner.vue';

import { ExclamationTriangleIcon } from '@heroicons/vue/24/outline';

import { useForm } from '@inertiajs/vue3';

import { useToast } from 'vue-toastification';



const toast = useToast();



const props = defineProps({

  order: {

    type: Object,

    default: () => ({

      id: null,

      status: '',

      deliveryRequest: null

    })

  }

});



const order = computed(() => ({

  ...props.order,

  deliveryRequest: props.order.deliveryRequest || {

    sender: {},

    receiver: {},

    packages: [],

    payment: null

  }

}));



const form = useForm({

  receiver_name: order.value.deliveryRequest?.receiver?.name || '',

  receiver_contact: order.value.deliveryRequest?.receiver?.mobile || '',

  notes: ''

});



// Package status helpers

const hasIncidentPackages = computed(() => {

  return order.value.deliveryRequest?.packages?.some(pkg => 

    pkg.status === 'damaged_in_transit' || pkg.status === 'lost_in_transit'

  ) || false;

});



const incidentPackagesCount = computed(() => {

  return order.value.deliveryRequest?.packages?.filter(pkg => 

    pkg.status === 'damaged_in_transit' || pkg.status === 'lost_in_transit'

  ).length || 0;

});



const deliveredPackagesCount = computed(() => {

  return order.value.deliveryRequest?.packages?.filter(pkg => 

    pkg.status === 'delivered'

  ).length || 0;

});



// Payment and status checks

const isPrepaid = computed(() => order.value.deliveryRequest?.payment_type === 'prepaid');

const isPostpaid = computed(() => order.value.deliveryRequest?.payment_type === 'postpaid');

const isPaymentVerified = computed(() => 

  order.value.deliveryRequest?.payment?.verified_by !== null &&

  order.value.deliveryRequest?.payment?.verified_by !== undefined

);

const isDelivered = computed(() => order.value.status === 'delivered');



const canRelease = computed(() => {

  if (!isDelivered.value) return false;

  if (isPrepaid.value && !isPaymentVerified.value) return false;

  return true;

});



const releasePackages = async () => {

  if (!canRelease.value) {

    toast.error('Cannot release packages - validation failed');

    return;

  }



  form.post(route('cargo-assignments.delivery-completion.complete', order.value.id), {

    onSuccess: () => {

      toast.success('Delivery completed successfully');

      router.visit(route('cargo-assignments.delivery-completion.ready-for-release'));

    },

    onError: () => {

      toast.error('Failed to complete delivery');

    }

  });

};



const completeWithIssues = async () => {

  if (!canRelease.value) {

    toast.error('Cannot complete delivery - validation failed');

    return;

  }



  form.post(route('delivery-orders.complete-with-issues', order.value.id), {

    onSuccess: () => {

      toast.success('Delivery completed with issues');

      router.visit(route('cargo-assignments.delivery-completion.ready-for-release'));

    },

    onError: () => {

      toast.error('Failed to complete delivery with issues');

    }

  });

};



function getStatusVariant(status) {

  if (status === 'delivered') return 'success';

  if (status === 'damaged_in_transit' || status === 'lost_in_transit') return 'danger';

  return 'info';

}



function formatPackageStatus(status) {

  const map = {

    'delivered': 'Delivered',

    'damaged_in_transit': 'Damaged',

    'lost_in_transit': 'Lost',

    'in_transit': 'In Transit'

  };

  return map[status] || status.replace('_', ' ');

}



function toCurrency(val) {

  if (val === null || val === undefined || val === '') {

    return '0.00';

  }

  const num = typeof val === 'number' ? val : parseFloat(val);

  return isNaN(num) ? '0.00' : num.toFixed(2);

}



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



function goToReleases() {

  router.visit(route('cargo-assignments.delivery-completion.ready-for-release'));

}

</script>