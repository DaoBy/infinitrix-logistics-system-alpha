<template>
  <EmployeeLayout>
    <template #header>
      <div class="flex justify-between items-center px-2 md:px-8 py-4 max-w-5xl mx-auto w-full">
        <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
          Review & Verify Collection
        </h2>
        <Link 
          :href="route('staff.payments.dashboard')"  
          class="inline-flex items-center text-sm text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-200"
        >
          <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
          </svg>
          Back to payments
        </Link>
      </div>
    </template>

    <div class="px-1 md:px-8 py-4 max-w-5xl mx-auto">
      <!-- Reference & Amount Card -->
      <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg border border-gray-200 dark:border-gray-700 mb-6">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between p-4 bg-indigo-50 dark:bg-indigo-900 border-b border-gray-200 dark:border-gray-700">
          <div>
            <div class="flex items-center gap-2">
              <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-indigo-100 text-indigo-800 dark:bg-indigo-800 dark:text-indigo-100">
                Reference #
              </span>
              <span class="text-lg font-bold text-indigo-900 dark:text-indigo-100 tracking-wide">
                {{ payment.delivery_request?.reference_number || ('DR-' + String(payment.delivery_request_id).padStart(6, '0')) }}
              </span>
            </div>
            <div class="mt-1 text-xs text-gray-500 dark:text-gray-300">
              Delivery ID: DO-{{ String(payment.delivery_request_id).padStart(6, '0') }}
              <span v-if="payment.created_at">&nbsp;|&nbsp;Created: {{ formatDate(payment.created_at) }}</span>
            </div>
          </div>
          <div class="mt-3 md:mt-0 flex items-center gap-2">
            <span class="font-medium text-gray-900 dark:text-gray-100">
              ₱{{ Number(payment.amount).toFixed(2) }}
            </span>
            <span class="text-xs text-gray-500 dark:text-gray-300">Amount Collected</span>
          </div>
          <div v-if="payment.reference_number" class="flex justify-between mb-2">
            <span class="text-gray-600 dark:text-gray-400">Reference Number:</span>
            <span class="font-mono text-gray-900 dark:text-gray-100">{{ payment.reference_number }}</span>
          </div>
          <div class="flex justify-between mb-2">
            <span class="text-gray-600 dark:text-gray-400">Payment Source:</span>
            <span class="capitalize text-gray-900 dark:text-gray-100">
              {{ payment.source.replace('_', ' ') }}
            </span>
          </div>
          <div class="flex justify-between mb-2">
            <span class="text-gray-600 dark:text-gray-400">Submitted By:</span>
            <span class="text-gray-900 dark:text-gray-100">
              {{ payment.submitted_by?.name || 'N/A' }}
            </span>
          </div>
        </div>
        <div class="p-4 md:p-6">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4 md:gap-6">
            <!-- Sender Information -->
            <div class="bg-gray-50 dark:bg-gray-700/50 p-3 md:p-4 rounded-lg flex flex-col gap-1">
              <h3 class="text-base font-medium text-gray-900 dark:text-gray-100 mb-2">Sender</h3>
              <p class="font-medium truncate">{{ payment.delivery_request?.sender?.name }}</p>
              <p class="text-xs text-gray-600 dark:text-gray-400 truncate">{{ payment.delivery_request?.sender?.email }}</p>
              <p class="text-xs text-gray-600 dark:text-gray-400 truncate">{{ payment.delivery_request?.sender?.mobile }}</p>
            </div>
            <!-- Receiver Information -->
            <div class="bg-gray-50 dark:bg-gray-700/50 p-3 md:p-4 rounded-lg flex flex-col gap-1">
              <h3 class="text-base font-medium text-gray-900 dark:text-gray-100 mb-2">Receiver</h3>
              <p class="font-medium truncate">{{ payment.delivery_request?.receiver?.name }}</p>
              <p class="text-xs text-gray-600 dark:text-gray-400 truncate">{{ payment.delivery_request?.receiver?.email }}</p>
              <p class="text-xs text-gray-600 dark:text-gray-400 truncate">{{ payment.delivery_request?.receiver?.mobile }}</p>
            </div>
          </div>
        </div>
      </div>
      </div>
      <!-- Collection Details Card -->
      <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg border border-gray-200 dark:border-gray-700">
        <div class="p-3 md:p-4 bg-gray-50 dark:bg-gray-700 border-b border-gray-200 dark:border-gray-700">
          <h3 class="font-medium text-gray-900 dark:text-gray-100">
            Collection Details
          </h3>
        </div>
        <div class="p-4 md:p-6 space-y-4">
          <div class="flex flex-col gap-2">
            <div class="flex justify-between">
              <span class="text-gray-700 dark:text-gray-300">Method:</span>
              <span class="capitalize text-gray-900 dark:text-gray-100">{{ payment.method }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-gray-700 dark:text-gray-300">Collected By:</span>
              <span class="text-gray-900 dark:text-gray-100">{{ payment.collected_by?.name || 'N/A' }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-gray-700 dark:text-gray-300">Collected At:</span>
              <span class="text-gray-900 dark:text-gray-100">{{ payment.collected_at ? formatDateTime(payment.collected_at) : 'N/A' }}</span>
            </div>
            <div v-if="payment.receipt_image" class="flex flex-col mt-2">
              <span class="text-gray-700 dark:text-gray-300 mb-1">Receipt:</span>
              <img :src="'/storage/' + payment.receipt_image" alt="Receipt" class="max-w-xs rounded border border-gray-200" />
            </div>
            <div v-if="payment.notes" class="mt-2">
              <span class="text-gray-700 dark:text-gray-300">Notes:</span>
              <div class="bg-gray-50 dark:bg-gray-700/50 p-2 rounded mt-1 text-gray-900 dark:text-gray-100">
                {{ payment.notes }}
              </div>
            </div>
          </div>
        </div>
      </div>
         <!-- Action Button -->
    <div class="flex justify-end p-4 border-t border-gray-200 dark:border-gray-700">
      <PrimaryButton
        color="success"
        :disabled="verifying || payment.verified_by"
        @click="verifyPayment"
      >
        <span v-if="verifying">Verifying...</span>
        <span v-else-if="payment.verified_by">Already Verified</span>
        <span v-else>Verify Collection</span>
      </PrimaryButton>
    </div>
  </EmployeeLayout>
</template>

<script setup>
import EmployeeLayout from '@/Layouts/EmployeeLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
  payment: Object
});

const verifying = ref(false);

function verifyPayment() {
  if (props.payment.verified_by) return;
  verifying.value = true;
  router.post(route('staff.payments.verify.action', props.payment.id), {}, { 
    onFinish: () => { verifying.value = false; },
    onSuccess: () => {
      router.visit(route('staff.payments.dashboard')); 
    }
  });
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

function formatDateTime(dateString) {
  if (!dateString) return 'N/A';
  try {
    const date = new Date(dateString);
    return date.toLocaleString('en-US', {
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
</script>
