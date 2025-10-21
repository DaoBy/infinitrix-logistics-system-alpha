<!-- resources/js/Pages/Collector/Payments/Create.vue -->
<template>
  <EmployeeLayout>
  <template #header>
  <div class="flex justify-between items-center px-4 sm:px-6">
    <div class="min-w-0 flex-1">
      <h2 class="text-lg sm:text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200 truncate">
        Record Postpaid Collection
      </h2>
      <p class="mt-1 text-sm text-gray-600 dark:text-gray-400 hidden sm:block">
        Collect payment for completed delivery
      </p>
    </div>
    <SecondaryButton 
      @click="router.visit(route('collector.payments.pending'))"
      class="inline-flex items-center text-sm whitespace-nowrap shrink-0 ml-2"
    >
      <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1 sm:mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
      </svg>
      <span class="hidden sm:inline">Back to Collections</span>
      <span class="sm:hidden">Back</span>
    </SecondaryButton>
  </div>
</template>

    <div class="px-4 md:px-6 py-4 max-w-7xl mx-auto">
      <!-- MAIN CONTENT GRID -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- LEFT COLUMN: Delivery Information -->
        <div class="lg:col-span-2 space-y-6">
          <!-- DELIVERY OVERVIEW CARD -->
          <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg border border-gray-200 dark:border-gray-700">
       <!-- Delivery Header -->
<div class="flex flex-col md:flex-row md:items-center md:justify-between p-4 bg-gray-50 dark:bg-gray-700 border-b border-gray-200 dark:border-gray-700">
  <div class="flex-1">
    <div class="flex flex-wrap items-center gap-2 mb-2">
      <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100 border border-green-200 dark:border-green-700">
        Reference#
      </span>
      <span class="text-lg font-bold text-green-600 dark:text-green-400 tracking-wide">
        {{ delivery.reference_number || ('DR-' + String(delivery.id).padStart(6, '0')) }}
      </span>
      <span :class="getPaymentStatusBadgeClass(delivery)" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium">
        {{ getPaymentStatusText(delivery) }}
      </span>
    </div>
    <div class="flex flex-wrap items-center gap-4 text-xs text-gray-600 dark:text-gray-300">
      <span>Delivery ID: DO-{{ String(delivery.id).padStart(6, '0') }}</span>
      <span v-if="delivery.created_at">Created: {{ formatDate(delivery.created_at) }}</span>
      <span v-if="delivery.payment_due_date" :class="isOverdue ? 'text-red-600 font-semibold' : ''">
        Due: {{ formatDate(delivery.payment_due_date) }}
      </span>
    </div>
  </div>
</div>

            <!-- Customer Information -->
            <div class="p-4 md:p-6">
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4 md:gap-6 mb-6">
                <!-- Sender Information -->
                <div class="bg-gray-50 dark:bg-gray-700/50 p-4 rounded-lg">
                  <h3 class="text-base font-medium text-gray-900 dark:text-gray-100 mb-3 flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                    Sender
                  </h3>
                  <div class="space-y-2">
                    <p class="font-medium truncate">{{ delivery.sender?.name || 'N/A' }}</p>
                    <p class="text-sm text-gray-600 dark:text-gray-400 flex items-center gap-1">
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                      </svg>
                      {{ delivery.sender?.email || 'No email' }}
                    </p>
                    <p class="text-sm text-gray-600 dark:text-gray-400 flex items-center gap-1">
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                      </svg>
                      {{ delivery.sender?.mobile || 'No phone' }}
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
                    <p class="font-medium truncate">{{ delivery.receiver?.name || 'N/A' }}</p>
                    <p class="text-sm text-gray-600 dark:text-gray-400 flex items-center gap-1">
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                      </svg>
                      {{ delivery.receiver?.email || 'No email' }}
                    </p>
                    <p class="text-sm text-gray-600 dark:text-gray-400 flex items-center gap-1">
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                      </svg>
                      {{ delivery.receiver?.mobile || 'No phone' }}
                    </p>
                  </div>
                </div>
              </div>

              <!-- Package Summary -->
              <div class="bg-gray-50 dark:bg-gray-700/50 p-4 rounded-lg">
                <h3 class="text-base font-medium text-gray-900 dark:text-gray-100 mb-3 flex items-center gap-2">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                  </svg>
                  Package Details
                </h3>
                <div class="grid grid-cols-2 md:grid-cols-3 gap-4 text-sm">
                  <div>
                    <span class="text-gray-500 dark:text-gray-400">Total Packages:</span>
                    <p class="font-medium">{{ delivery.packages?.length || 0 }}</p>
                  </div>
                  <div>
                    <span class="text-gray-500 dark:text-gray-400">Total Weight:</span>
                    <p class="font-medium">{{ calculateTotalWeight() }} kg</p>
                  </div>
                  <div>
                    <span class="text-gray-500 dark:text-gray-400">Total Volume:</span>
                    <p class="font-medium">{{ calculateTotalVolume() }} m³</p>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- PAYMENT CONTEXT ALERTS -->
          <div v-if="showPaymentAlerts" class="space-y-4">
            <!-- Overdue Alert -->
            <div v-if="isOverdue" class="bg-red-50 border border-red-200 rounded-lg p-4">
              <div class="flex items-center">
                <svg class="h-5 w-5 text-red-400 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                </svg>
                <div>
                  <h3 class="text-lg font-medium text-red-800">Payment Overdue</h3>
                  <p class="text-red-700">This payment was due on {{ formatDate(delivery.payment_due_date) }}</p>
                </div>
              </div>
            </div>

            <!-- Extended Due Date Alert -->
            <div v-if="delivery.payment_status === 'uncollectible'" class="bg-orange-50 border border-orange-200 rounded-lg p-4">
              <div class="flex items-center">
                <svg class="h-5 w-5 text-orange-400 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <div>
                  <h3 class="text-lg font-medium text-orange-800">Extended Due Date</h3>
                  <p class="text-orange-700 text-sm mt-1" v-if="delivery.non_payment_reason">
                    Reason: {{ delivery.non_payment_reason }}
                  </p>
                </div>
              </div>
            </div>

            <!-- Already Submitted Alert -->
            <div v-if="isPaymentAlreadySubmitted" class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
              <div class="flex items-center">
                <svg class="h-5 w-5 text-yellow-400 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                </svg>
                <div>
                  <h3 class="text-lg font-medium text-yellow-800">Payment Already Processed</h3>
                  <p class="text-yellow-700">
                    {{ delivery.payment_status === 'pending_verification' ? 
                      'Payment is pending verification' : 
                      'Payment has been verified' }}
                  </p>
                </div>
              </div>
            </div>
          </div>

          <!-- PAYMENT PROOF PREVIEW SECTION -->
          <div v-if="receiptPreview" class="bg-white dark:bg-gray-800 shadow-sm rounded-lg border border-gray-200 dark:border-gray-700 p-6">
            <div class="text-center">
              <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4 flex items-center justify-center gap-2">
                <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25zM6.75 12h.008v.008H6.75V12zm0 3h.008v.008H6.75V15zm0 3h.008v.008H6.75V18z"/>
                </svg>
                Payment Proof Preview
              </h3>
              <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                Preview of the uploaded payment proof
              </p>
              <div class="flex justify-center">
                <div class="relative">
                  <img 
                    :src="receiptPreview" 
                    class="max-h-64 rounded-lg border-2 border-blue-300 dark:border-blue-600 shadow-md" 
                    alt="Payment proof preview"
                  />
                  <div class="absolute top-2 right-2">
                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-800 dark:text-blue-100">
                      Preview
                    </span>
                  </div>
                </div>
              </div>
              <p class="text-xs text-gray-500 dark:text-gray-400 mt-3">
                This payment proof will be submitted for verification
              </p>
            </div>
          </div>
        </div>

        <!-- RIGHT COLUMN: Collection Form -->
        <div class="lg:col-span-1">
          <div v-if="!isPaymentAlreadySubmitted" class="bg-white dark:bg-gray-800 shadow-sm rounded-lg border border-gray-200 dark:border-gray-700 sticky top-6">
            <div class="p-4 bg-gray-50 dark:bg-gray-700 border-b border-gray-200 dark:border-gray-700">
              <h3 class="font-medium text-gray-900 dark:text-gray-100 flex items-center gap-2">
                <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                Collection Details
              </h3>
            </div>
            <div class="p-4">
              <form @submit.prevent="submit">
                <div class="space-y-4">
                  <!-- Payment Method - Fixed to Cash Only -->
                  <div>
                    <InputLabel value="Payment Method" />
                    <div class="mt-1 p-3 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-md">
                      <div class="flex items-center gap-3">
                        <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                        <div>
                          <p class="font-medium text-blue-900 dark:text-blue-100">Cash Payment</p>
                          <p class="text-xs text-blue-700 dark:text-blue-300">Collector payments are processed in cash only</p>
                        </div>
                      </div>
                    </div>
                    <input type="hidden" v-model="form.method" />
                  </div>

                  <!-- Payment Amount Group -->
                  <div class="bg-gray-50 dark:bg-gray-700/50 p-3 rounded-lg border border-gray-200 dark:border-gray-600">
                    <h4 class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-3 flex items-center gap-2">
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                      </svg>
                      Payment Amount
                    </h4>
                    
                    <div class="space-y-3">
                      <!-- Total Due -->
                      <div class="flex justify-between items-center p-2 bg-white dark:bg-gray-600 rounded border">
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Total Due:</span>
                        <span class="text-lg font-bold text-gray-900 dark:text-gray-100">
                          ₱{{ Number(delivery.total_price).toFixed(2) }}
                        </span>
                      </div>

                      <!-- Amount Received -->
                      <div>
                        <InputLabel value="Amount Received *" />
                        <div class="relative rounded-md shadow-sm">
                          <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <span class="text-gray-500 dark:text-gray-400 sm:text-sm">₱</span>
                          </div>
                          <TextInput
                            v-model="amountReceived"
                            type="number"
                            step="0.01"
                            min="0"
                            class="block w-full pl-7 text-base font-semibold"
                            placeholder="0.00"
                            :error="form.errors.amount"
                            @input="updateAmount"
                          />
                        </div>
                        <InputError :message="form.errors.amount" />
                        
                        <!-- Amount Validation Message -->
                        <div v-if="amountReceived && Number(amountReceived) < Number(delivery.total_price)" 
                             class="mt-1 text-xs text-red-600 flex items-center gap-1">
                          <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                          </svg>
                          Amount must cover the total due of ₱{{ Number(delivery.total_price).toFixed(2) }}
                        </div>
                      </div>

                      <!-- Change -->
                      <div v-if="calculateChange() > 0" class="flex justify-between items-center p-2 bg-green-50 dark:bg-green-900/20 rounded border border-green-200 dark:border-green-800">
                        <span class="text-sm font-medium text-green-800 dark:text-green-300">Change:</span>
                        <span class="text-lg font-bold text-green-900 dark:text-green-100">
                          ₱{{ calculateChange().toFixed(2) }}
                        </span>
                      </div>
                    </div>
                  </div>

                  <!-- Payment Proof Upload -->
                  <div>
                    <InputLabel value="Payment Proof *" />
                    <input
                      type="file"
                      @change="handleReceiptUpload"
                      accept="image/*"
                      class="block w-full text-sm text-gray-500 dark:text-gray-400 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-blue-50 dark:file:bg-blue-900/20 file:text-blue-700 dark:file:text-blue-400 hover:file:bg-blue-100 dark:hover:file:bg-blue-900/30"
                      :class="{ 'border-red-300': form.errors.receipt_image }"
                    />
                    <InputError :message="form.errors.receipt_image" />
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                      Upload a clear photo of the payment proof. Maximum file size: 2MB
                    </p>
                  </div>

                  <!-- Collection Notes -->
                  <div>
                    <InputLabel value="Collection Notes" />
                    <TextArea
                      v-model="form.notes"
                      :rows="3"
                      placeholder="Add any notes about the collection circumstances..."
                      class="resize-none text-sm"
                    />
                  </div>

                  <!-- Submit Button -->
                  <div class="flex flex-col gap-2 pt-2">
                    <PrimaryButton
                      type="submit"
                      :disabled="form.processing || !isFormValid"
                      class="w-full justify-center"
                    >
                      <span v-if="form.processing">
                        <LoadingSpinner size="xs" class="mr-2" />
                        Processing...
                      </span>
                      <span v-else class="flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        Record Collection
                      </span>
                    </PrimaryButton>
                  </div>
                </div>
              </form>
            </div>
          </div>
        </div>
      </div>
    </div>
  </EmployeeLayout>
</template>

<script setup>
import EmployeeLayout from '@/Layouts/EmployeeLayout.vue';
import { Link, useForm, router } from '@inertiajs/vue3';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import TextInput from '@/Components/TextInput.vue';
import TextArea from '@/Components/TextArea.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import LoadingSpinner from '@/Components/LoadingSpinner.vue';
import { computed, ref } from 'vue';

const props = defineProps({
  delivery: Object
});

const receiptPreview = ref(null);
const amountReceived = ref('');

const form = useForm({
  method: 'cash', // Fixed to cash only
  amount: Number(props.delivery.total_price) || 0,
  receipt_image: null,
  notes: '',
  type: 'postpaid'
});

// Computed properties
const isPaymentAlreadySubmitted = computed(() => {
  return ['pending_verification', 'paid', 'verified'].includes(props.delivery.payment_status);
});

const isOverdue = computed(() => {
  if (!props.delivery.payment_due_date) return false;
  return new Date(props.delivery.payment_due_date) < new Date();
});

const showPaymentAlerts = computed(() => {
  return isOverdue.value || 
         props.delivery.payment_status === 'uncollectible' || 
         isPaymentAlreadySubmitted.value;
});

const isFormValid = computed(() => {
  return form.method && 
         form.amount >= Number(props.delivery.total_price) && 
         form.receipt_image;
});

// Methods
function getPaymentStatusBadgeClass(delivery) {
  const status = delivery.payment_status?.toLowerCase();
  switch (status) {
    case 'paid':
    case 'verified':
      return 'bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100';
    case 'pending_verification':
      return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-800 dark:text-yellow-100';
    case 'uncollectible':
      return 'bg-orange-100 text-orange-800 dark:bg-orange-800 dark:text-orange-100';
    case 'overdue':
      return 'bg-red-100 text-red-800 dark:bg-red-800 dark:text-red-100';
    default:
      return 'bg-blue-100 text-blue-800 dark:bg-blue-800 dark:text-blue-100';
  }
}

function getPaymentStatusText(delivery) {
  const status = delivery.payment_status?.toLowerCase();
  switch (status) {
    case 'paid':
    case 'verified':
      return 'Paid';
    case 'pending_verification':
      return 'Pending Verification';
    case 'uncollectible':
      return 'Extended Due Date';
    case 'overdue':
      return 'Overdue';
    default:
      return 'Pending Collection';
  }
}

function calculateTotalWeight() {
  if (!props.delivery.packages || props.delivery.packages.length === 0) return '0';
  const total = props.delivery.packages.reduce((total, pkg) => total + (Number(pkg.weight) || 0), 0);
  return total > 0 ? total.toFixed(2) : '0';
}

function calculateTotalVolume() {
  if (!props.delivery.packages || props.delivery.packages.length === 0) return '0';
  const total = props.delivery.packages.reduce((total, pkg) => {
    const volume = (Number(pkg.height) * Number(pkg.width) * Number(pkg.length)) / 1000000;
    return total + (volume || 0);
  }, 0);
  return total > 0 ? total.toFixed(3) : '0';
}

function calculateChange() {
  const received = parseFloat(amountReceived.value) || 0;
  const total = Number(props.delivery.total_price) || 0;
  return Math.max(0, received - total);
}

function updateAmount() {
  const received = parseFloat(amountReceived.value) || 0;
  form.amount = received;
}

function handleReceiptUpload(event) {
  const file = event.target.files[0];
  form.receipt_image = file;
  
  // Create preview
  if (file) {
    const reader = new FileReader();
    reader.onload = (e) => {
      receiptPreview.value = e.target.result;
    };
    reader.readAsDataURL(file);
  } else {
    receiptPreview.value = null;
  }
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

function submit() {
  if (!isFormValid.value) return;
  
  form.post(route('collector.payments.store', props.delivery.id), {
    forceFormData: true,
    preserveScroll: true,
    onSuccess: () => {
      form.reset();
      receiptPreview.value = null;
      amountReceived.value = '';
    }
  });
}
</script>