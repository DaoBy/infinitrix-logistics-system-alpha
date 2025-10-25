<template>
  <EmployeeLayout>
    <template #header>
      <div class="flex justify-between items-center px-4 sm:px-6">
        <div class="min-w-0 flex-1">
          <h2 class="text-lg sm:text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200 truncate">
            Review & Verify Collection
          </h2>
          <p class="mt-1 text-sm text-gray-600 dark:text-gray-400 hidden sm:block">
            Verify payment collection details
          </p>
        </div>
        <SecondaryButton 
          @click="router.visit(route('staff.payments.dashboard'))"
          class="inline-flex items-center text-sm whitespace-nowrap shrink-0 ml-2"
        >
          <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1 sm:mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
          </svg>
          <span class="hidden sm:inline">Back to Payments</span>
          <span class="sm:hidden">Back</span>
        </SecondaryButton>
      </div>
    </template>

    <div class="px-4 md:px-6 py-4 max-w-7xl mx-auto">
      <!-- MAIN CONTENT GRID -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- LEFT COLUMN: Payment Information -->
        <div class="lg:col-span-2 space-y-6">
          <!-- PAYMENT OVERVIEW CARD -->
          <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg border border-gray-200 dark:border-gray-700">
            <!-- Payment Header -->
            <div class="flex flex-col md:flex-row md:items-center md:justify-between p-4 bg-indigo-50 dark:bg-indigo-900/20 border-b border-indigo-200 dark:border-indigo-800">
              <div class="flex-1">
                <div class="flex flex-wrap items-center gap-2 mb-2">
                  <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-indigo-100 text-indigo-800 dark:bg-indigo-800 dark:text-indigo-100 border border-indigo-200 dark:border-indigo-700">
                    Reference#
                  </span>
                  <span class="text-lg font-bold text-indigo-600 dark:text-indigo-400 tracking-wide">
                    {{ payment.delivery_request?.reference_number || ('DR-' + String(payment.delivery_request_id).padStart(6, '0')) }}
                  </span>
                  <span v-if="payment.verified_by" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100">
                    Verified
                  </span>
                  <span v-else class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-800 dark:text-yellow-100">
                    Pending Verification
                  </span>
                </div>
                <div class="flex flex-wrap items-center gap-4 text-xs text-gray-600 dark:text-gray-300">
                  <span>Delivery ID: DO-{{ String(payment.delivery_request_id).padStart(6, '0') }}</span>
                  <span v-if="payment.created_at">Created: {{ formatDate(payment.created_at) }}</span>
                </div>
              </div>
              <div class="mt-3 md:mt-0">
                <div class="text-2xl font-bold text-indigo-900 dark:text-indigo-100 text-center md:text-right">
                  ₱{{ Number(payment.amount).toFixed(2) }}
                </div>
                <div class="text-xs text-gray-600 dark:text-gray-300 text-center md:text-right">
                  Amount Collected
                </div>
              </div>
            </div>

            <!-- Payment Details -->
            <div class="p-4 md:p-6">
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                <div class="bg-gray-50 dark:bg-gray-700/50 p-3 rounded-lg">
                  <h4 class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-3 flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Payment Information
                  </h4>
                  <div class="space-y-2 text-sm">
                    <div class="flex justify-between">
                      <span class="text-gray-600 dark:text-gray-400">Payment Source:</span>
                      <span class="capitalize font-medium text-gray-900 dark:text-gray-100">
                        {{ payment.source.replace('_', ' ') }}
                      </span>
                    </div>
                    <div class="flex justify-between">
                      <span class="text-gray-600 dark:text-gray-400">Payment Method:</span>
                      <span class="capitalize font-medium text-gray-900 dark:text-gray-100">
                        {{ payment.method }}
                      </span>
                    </div>
                    <div v-if="payment.reference_number" class="flex justify-between">
                      <span class="text-gray-600 dark:text-gray-400">Reference Number:</span>
                      <span class="font-mono text-gray-900 dark:text-gray-100">{{ payment.reference_number }}</span>
                    </div>
                  </div>
                </div>

                <!-- Moved Collection Information here -->
                <div class="bg-gray-50 dark:bg-gray-700/50 p-3 rounded-lg">
                  <h4 class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-3 flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                    Collection Information
                  </h4>
                  <div class="space-y-2 text-sm">
                    <div class="flex justify-between">
                      <span class="text-gray-600 dark:text-gray-400">Collected By:</span>
                      <span class="font-medium text-gray-900 dark:text-gray-100">{{ payment.collected_by?.name || 'N/A' }}</span>
                    </div>
                    <div class="flex justify-between">
                      <span class="text-gray-600 dark:text-gray-400">Collected At:</span>
                      <span class="font-medium text-gray-900 dark:text-gray-100">{{ payment.collected_at ? formatDateTime(payment.collected_at) : 'N/A' }}</span>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Customer Information -->
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4 md:gap-6">
                <!-- Sender Information -->
                <div class="bg-gray-50 dark:bg-gray-700/50 p-4 rounded-lg">
                  <h3 class="text-base font-medium text-gray-900 dark:text-gray-100 mb-3 flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                    Sender
                  </h3>
                  <div class="space-y-2">
                    <p class="font-medium truncate">{{ payment.delivery_request?.sender?.name || 'N/A' }}</p>
                    <p class="text-sm text-gray-600 dark:text-gray-400 flex items-center gap-1">
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                      </svg>
                      {{ payment.delivery_request?.sender?.email || 'No email' }}
                    </p>
                    <p class="text-sm text-gray-600 dark:text-gray-400 flex items-center gap-1">
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                      </svg>
                      {{ payment.delivery_request?.sender?.mobile || 'No phone' }}
                    </p>
                  </div>
                </div>

                <!-- Receiver Information -->
                <div class="bg-gray-50 dark:bg-gray-700/50 p-4 rounded-lg">
                  <h3 class="text-base font-medium text-gray-900 dark:text-gray-100 mb-3 flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                    Receiver
                  </h3>
                  <div class="space-y-2">
                    <p class="font-medium truncate">{{ payment.delivery_request?.receiver?.name || 'N/A' }}</p>
                    <p class="text-sm text-gray-600 dark:text-gray-400 flex items-center gap-1">
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                      </svg>
                      {{ payment.delivery_request?.receiver?.email || 'No email' }}
                    </p>
                    <p class="text-sm text-gray-600 dark:text-gray-400 flex items-center gap-1">
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                      </svg>
                      {{ payment.delivery_request?.receiver?.mobile || 'No phone' }}
                    </p>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- COLLECTION DETAILS CARD (Now only for Receipt) -->
          <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg border border-gray-200 dark:border-gray-700">
            <div class="p-4 bg-gray-50 dark:bg-gray-700 border-b border-gray-200 dark:border-gray-700">
              <h3 class="font-medium text-gray-900 dark:text-gray-100 flex items-center gap-2">
                <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
                Payment Receipt
              </h3>
            </div>
            <div class="p-4 md:p-6">
              <div v-if="payment.receipt_image" class="space-y-4">
                <!-- Receipt Image -->
                <div class="flex justify-center">
                  <img 
                    :src="'/storage/' + payment.receipt_image" 
                    alt="Payment receipt" 
                    class="max-w-full md:max-w-md rounded-lg border-2 border-gray-200 dark:border-gray-600 shadow-sm cursor-pointer hover:shadow-md transition-shadow"
                    @click="openImageModal('/storage/' + payment.receipt_image)"
                  />
                </div>
                
                <!-- Notes (if any) -->
                <div v-if="payment.notes" class="bg-gray-50 dark:bg-gray-700/50 p-4 rounded-lg">
                  <h4 class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-3 flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    </svg>
                    Collection Notes
                  </h4>
                  <p class="text-sm text-gray-700 dark:text-gray-300 whitespace-pre-wrap">{{ payment.notes }}</p>
                </div>
              </div>
              
              <div v-else class="text-center py-8">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-gray-100">No Receipt Available</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">No payment receipt was uploaded for this collection.</p>
              </div>
            </div>
          </div>
        </div>

        <!-- RIGHT COLUMN: Verification Actions -->
        <div class="lg:col-span-1">
          <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg border border-gray-200 dark:border-gray-700 sticky top-6">
            <div class="p-4 bg-gray-50 dark:bg-gray-700 border-b border-gray-200 dark:border-gray-700">
              <h3 class="font-medium text-gray-900 dark:text-gray-100 flex items-center gap-2">
                <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                Verification
              </h3>
            </div>
            <div class="p-4">
              <!-- Verification Status -->
              <div v-if="payment.verified_by" class="mb-6">
                <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg p-4">
                  <div class="flex items-center">
                    <svg class="h-5 w-5 text-green-400 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <div>
                      <h3 class="text-lg font-medium text-green-800 dark:text-green-300">Payment Verified</h3>
                      <p class="text-green-700 dark:text-green-400 text-sm mt-1">
                        Verified by {{ payment.verified_by?.name }} on {{ formatDateTime(payment.verified_at) }}
                      </p>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Verification Requirements -->
              <div v-else class="mb-6">
                <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
                  <div class="flex items-center">
                    <svg class="h-5 w-5 text-blue-400 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <div>
                      <h3 class="text-lg font-medium text-blue-800 dark:text-blue-300">Ready for Verification</h3>
                      <p class="text-blue-700 dark:text-blue-400 text-sm mt-1">
                        Review all details before verification
                      </p>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Action Buttons -->
              <div class="space-y-3">
                <PrimaryButton
                  v-if="!payment.verified_by"
                  @click="verifyPayment"
                  :disabled="verifying"
                  class="w-full justify-center"
                >
                  <span v-if="verifying">
                    <LoadingSpinner size="xs" class="mr-2" />
                    Verifying...
                  </span>
                  <span v-else class="flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    Verify Collection
                  </span>
                </PrimaryButton>

                <SecondaryButton
                  @click="router.visit(route('staff.payments.dashboard'))"
                  class="w-full justify-center"
                >
                  Back to Dashboard
                </SecondaryButton>
              </div>

              <!-- Verification Checklist -->
              <div v-if="!payment.verified_by" class="mt-6 p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                <h4 class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-3">Verification Checklist</h4>
                <ul class="space-y-2 text-xs text-gray-600 dark:text-gray-400">
                  <li class="flex items-center gap-2">
                    <svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    Payment amount matches delivery total
                  </li>
                  <li class="flex items-center gap-2">
                    <svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    Receipt image is clear and valid
                  </li>
                  <li class="flex items-center gap-2">
                    <svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    Collection details are accurate
                  </li>
                </ul>
              </div>
            </div>
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
import LoadingSpinner from '@/Components/LoadingSpinner.vue';
import { router } from '@inertiajs/vue3';
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

function openImageModal(imageUrl) {
  // You can implement a modal here if needed
  window.open(imageUrl, '_blank');
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