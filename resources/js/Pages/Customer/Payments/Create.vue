<template>
  <GuestLayout>
    <template #header>
      <div class="flex justify-between items-center px-4 sm:px-6">
        <div class="min-w-0 flex-1">
          <h2 class="text-lg sm:text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200 truncate">
            {{ paymentType === 'postpaid' ? 'Settle Postpaid Payment' : 'Make Payment' }} - {{ delivery.reference_number }}
          </h2>
          <p class="mt-1 text-sm text-gray-600 dark:text-gray-400 hidden sm:block">
            {{ paymentType === 'postpaid' ? 'Your delivery is complete. Please settle your payment online.' : 'Complete your payment for this delivery' }}
          </p>
        </div>
        <SecondaryButton 
          @click="router.visit(route('customer.delivery-requests.show', delivery.id))"
          class="inline-flex items-center text-sm whitespace-nowrap shrink-0 ml-2"
        >
          <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1 sm:mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
          </svg>
          <span class="hidden sm:inline">Back to Delivery</span>
          <span class="sm:hidden">Back</span>
        </SecondaryButton>
      </div>
    </template>

    <div class="px-4 md:px-6 py-4 max-w-7xl mx-auto">
      <!-- MAIN CONTENT GRID -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- LEFT COLUMN: Delivery Information -->
        <div class="lg:col-span-2 space-y-6">
          <!-- DELIVERY SUMMARY CARD -->
          <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg border border-gray-200 dark:border-gray-700">
            <div class="p-4 bg-gray-50 dark:bg-gray-700 border-b border-gray-200 dark:border-gray-700">
              <h3 class="font-medium text-gray-900 dark:text-gray-100 flex items-center gap-2">
                <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                Delivery Summary
              </h3>
            </div>
            <div class="p-4 md:p-6">
              <!-- Customer Information -->
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4 md:gap-6 mb-6">
                <!-- Sender Information -->
                <div class="bg-gray-50 dark:bg-gray-700/50 p-4 rounded-lg">
                  <h4 class="text-base font-medium text-gray-900 dark:text-gray-100 mb-3 flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                    Sender
                  </h4>
                  <div class="space-y-2">
                    <p class="font-medium text-gray-900 dark:text-gray-100">{{ delivery.sender.name }}</p>
                    <p class="text-sm text-gray-600 dark:text-gray-400">{{ delivery.sender.email }}</p>
                    <p class="text-sm text-gray-600 dark:text-gray-400">{{ delivery.sender.mobile }}</p>
                  </div>
                </div>

                <!-- Receiver Information -->
                <div class="bg-gray-50 dark:bg-gray-700/50 p-4 rounded-lg">
                  <h4 class="text-base font-medium text-gray-900 dark:text-gray-100 mb-3 flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                    Receiver
                  </h4>
                  <div class="space-y-2">
                    <p class="font-medium text-gray-900 dark:text-gray-100">{{ delivery.receiver.name }}</p>
                    <p class="text-sm text-gray-600 dark:text-gray-400">{{ delivery.receiver.email }}</p>
                    <p class="text-sm text-gray-600 dark:text-gray-400">{{ delivery.receiver.mobile }}</p>
                  </div>
                </div>
              </div>

              <!-- Package Details -->
              <div class="mb-6">
                <h4 class="text-base font-medium text-gray-900 dark:text-gray-100 mb-3 flex items-center gap-2">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                  </svg>
                  Package Details
                </h4>
                <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg border border-gray-200 dark:border-gray-600">
                  <div v-for="(pkg, index) in delivery.packages" :key="pkg.id" 
                       class="p-4 border-b border-gray-200 dark:border-gray-600 last:border-b-0">
                    <p class="font-medium text-gray-900 dark:text-gray-100 mb-2">{{ pkg.item_name }}</p>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-2 text-sm text-gray-600 dark:text-gray-400">
                      <div class="flex items-center gap-1">
                        <span class="font-medium">Weight:</span>
                        <span>{{ pkg.weight }} kg</span>
                      </div>
                      <div class="flex items-center gap-1">
                        <span class="font-medium">Dimensions:</span>
                        <span>{{ pkg.height }}×{{ pkg.width }}×{{ pkg.length }}cm</span>
                      </div>
                      <div class="flex items-center gap-1">
                        <span class="font-medium">Value:</span>
                        <span>₱{{ parseFloat(pkg.value).toFixed(2) }}</span>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Total Amount -->
              <div class="flex justify-between items-center p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800">
                <span class="text-lg font-semibold text-blue-900 dark:text-blue-100">Total Amount Due:</span>
                <span class="text-2xl font-bold text-blue-600 dark:text-blue-400">₱{{ parseFloat(delivery.total_price).toFixed(2) }}</span>
              </div>
            </div>
          </div>

          <!-- PAYMENT INSTRUCTIONS CARD -->
          <div v-if="showPaymentInstructions" class="bg-white dark:bg-gray-800 shadow-sm rounded-lg border border-gray-200 dark:border-gray-700">
            <div class="p-4 bg-blue-50 dark:bg-blue-900/20 border-b border-blue-200 dark:border-blue-800">
              <h3 class="font-medium text-blue-900 dark:text-blue-100 flex items-center gap-2">
                <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                Payment Instructions
              </h3>
            </div>
            <div class="p-4 md:p-6">
              <div class="flex flex-col md:flex-row gap-6">
                <!-- Instructions -->
                <div class="flex-1">
                  <!-- GCash payment information -->
                  <div v-if="selectedMethod === 'gcash'" class="space-y-4">
                    <h4 class="text-lg font-medium text-blue-900 dark:text-blue-100 flex items-center gap-2">
                      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"/>
                      </svg>
                      GCash Payment
                    </h4>
                    <ol class="list-decimal list-inside space-y-3 text-sm text-blue-800 dark:text-blue-300">
                      <li>Open your GCash app</li>
                      <li>Go to "Scan QR Code"</li>
                      <li>Scan the QR code on the right</li>
                      <li>Verify the amount: <strong>₱{{ parseFloat(delivery.total_price).toFixed(2) }}</strong></li>
                      <li>Complete the transaction</li>
                      <li>Take a screenshot of the confirmation</li>
                      <li>Upload the screenshot below</li>
                    </ol>
                  </div>
                  
                <!-- BANK PAYMENT INSTRUCTIONS - 2 COLUMN LAYOUT -->
<div v-else-if="selectedMethod === 'bank'" class="space-y-4">
  <h4 class="text-lg font-medium text-blue-900 dark:text-blue-100 flex items-center gap-2">
    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
    </svg>
    Bank Transfer
  </h4>
  
  <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    <!-- Left Column: Bank Details -->
    <div>
      <h5 class="font-medium text-blue-800 dark:text-blue-200 mb-3">Bank Details</h5>
      <div class="space-y-3 text-sm text-blue-800 dark:text-blue-300">
        <div class="flex justify-between items-center p-3 bg-blue-100 dark:bg-blue-900/30 rounded-lg">
          <span class="font-medium">Bank:</span>
          <span>Sample Bank</span>
        </div>
        <div class="flex justify-between items-center p-3 bg-blue-100 dark:bg-blue-900/30 rounded-lg">
          <span class="font-medium">Account Name:</span>
          <span>Infinitrix Logistics</span>
        </div>
        <div class="flex justify-between items-center p-3 bg-blue-100 dark:bg-blue-900/30 rounded-lg">
          <span class="font-medium">Account Number:</span>
          <span class="font-mono">1234-5678-9012</span>
        </div>
        <div class="flex justify-between items-center p-3 bg-blue-100 dark:bg-blue-900/30 rounded-lg">
          <span class="font-medium">Amount:</span>
          <span class="font-bold">₱{{ parseFloat(delivery.total_price).toFixed(2) }}</span>
        </div>
        <div class="flex justify-between items-center p-3 bg-blue-100 dark:bg-blue-900/30 rounded-lg">
          <span class="font-medium">Reference:</span>
          <span class="font-mono">{{ delivery.reference_number }}</span>
        </div>
      </div>
    </div>

    <!-- Right Column: Steps -->
    <div>
      <h5 class="font-medium text-blue-800 dark:text-blue-200 mb-3">Transfer Steps</h5>
      <ol class="list-decimal list-inside space-y-3 text-sm text-blue-800 dark:text-blue-300">
        <li>Log in to your bank's mobile app or website</li>
        <li>Go to "Send Money" or "Transfer"</li>
        <li>Enter the bank details shown</li>
        <li>Verify the amount and reference number</li>
        <li>Complete the transaction</li>
        <li>Take a screenshot of the confirmation</li>
        <li>Upload the screenshot below</li>
      </ol>
    </div>
  </div>
</div>
                </div>
                
                <!-- QR Code for GCash -->
                <div v-if="selectedMethod === 'gcash'" class="flex flex-col items-center justify-center">
                  <div class="bg-white dark:bg-gray-700 p-4 rounded-lg border border-gray-200 dark:border-gray-600">
                    <div class="w-40 h-40 bg-gray-200 dark:bg-gray-600 flex items-center justify-center mb-2 rounded">
                      <span class="text-gray-500 dark:text-gray-400 text-sm text-center">GCash QR Code</span>
                    </div>
                    <p class="text-xs text-center text-gray-600 dark:text-gray-400 mt-2">Scan to pay with GCash</p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- RIGHT COLUMN: Payment Form -->
        <div class="lg:col-span-1">
          <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg border border-gray-200 dark:border-gray-700 sticky top-6">
            <div class="p-4 bg-gray-50 dark:bg-gray-700 border-b border-gray-200 dark:border-gray-700">
              <h3 class="font-medium text-gray-900 dark:text-gray-100 flex items-center gap-2">
                <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"/>
                </svg>
                Payment Information
              </h3>
            </div>
            <div class="p-4">
              <form @submit.prevent="submit" enctype="multipart/form-data">
                <div class="space-y-4">
                  <!-- Payment Method -->
                  <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                      Payment Method *
                    </label>
                    <div class="space-y-2">
                      <div 
                        v-for="method in paymentMethods" 
                        :key="method"
                        @click="form.method = method"
                        :class="[
                          'border rounded-lg p-3 cursor-pointer transition-all',
                          form.method === method 
                            ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20 dark:border-blue-600' 
                            : 'border-gray-300 dark:border-gray-600 hover:border-gray-400 dark:hover:border-gray-500'
                        ]"
                      >
                        <div class="flex items-center">
                          <input 
                            type="radio" 
                            :id="`method-${method}`"
                            :value="method"
                            v-model="form.method"
                            class="h-4 w-4 text-blue-600 focus:ring-blue-500 dark:focus:ring-blue-400"
                          />
                          <label :for="`method-${method}`" class="ml-3 block text-sm font-medium text-gray-700 dark:text-gray-300 capitalize">
                            {{ method }}
                          </label>
                        </div>
                      </div>
                    </div>
                    <InputError :message="form.errors.method" class="mt-2" />
                  </div>

                  <!-- Payment Method Change Notice -->
                  <div v-if="delivery.payment_method === 'cash' && form.method && form.method !== 'cash'" 
                       class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-3">
                    <div class="flex items-start">
                      <InformationCircleIcon class="h-5 w-5 text-yellow-400 mr-2 mt-0.5 flex-shrink-0" />
                      <span class="text-yellow-800 dark:text-yellow-300 text-sm">
                        <strong>Note:</strong> Your payment method will be updated from Cash to {{ form.method.toUpperCase() }}.
                        Future communications will reference this payment method.
                      </span>
                    </div>
                  </div>

                  <!-- Reference Number (for GCash/Bank) -->
                  <div v-if="form.method && form.method !== 'cash'">
                    <label for="reference_number" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                      Reference Number *
                    </label>
                    <TextInput
                      id="reference_number"
                      v-model="form.reference_number"
                      type="text"
                      class="block w-full"
                      placeholder="Enter transaction reference number"
                    />
                    <InputError :message="form.errors.reference_number" class="mt-2" />
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                      Enter the exact reference number from your transaction
                    </p>
                  </div>

                  <!-- Hidden Amount Field -->
                  <div v-show="false">
                    <TextInput
                      id="amount"
                      v-model="form.amount"
                      type="number"
                      step="0.01"
                      min="0.01"
                      class="block w-full"
                    />
                  </div>

                  <!-- Receipt Image Upload -->
                  <div>
                    <label for="receipt_image" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                      Payment Proof (Screenshot/Photo) *
                    </label>
                    <input
                      id="receipt_image"
                      type="file"
                      @change="handleReceiptUpload"
                      accept="image/*"
                      class="block w-full text-sm text-gray-500 dark:text-gray-400 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-blue-50 dark:file:bg-blue-900/20 file:text-blue-700 dark:file:text-blue-400 hover:file:bg-blue-100 dark:hover:file:bg-blue-900/30"
                    />
                    <InputError :message="form.errors.receipt_image" class="mt-2" />
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                      Upload a clear screenshot or photo of your payment confirmation
                    </p>

                    <!-- Image Preview -->
                    <div v-if="receiptPreview" class="mt-3">
                      <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Preview:
                      </label>
                      <div class="relative inline-block">
                        <img 
                          :src="receiptPreview" 
                          alt="Payment proof preview"
                          class="max-w-full h-32 rounded-lg border-2 border-blue-300 dark:border-blue-600 object-contain cursor-pointer hover:shadow-md transition-shadow"
                          @click="openImageModal(receiptPreview)"
                        />
                        <button
                          type="button"
                          @click="removeImagePreview"
                          class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full p-1 hover:bg-red-600 transition-colors"
                        >
                          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                          </svg>
                        </button>
                        <div class="absolute bottom-2 right-2">
                          <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-800 dark:text-blue-100">
                            Preview
                          </span>
                        </div>
                      </div>
                      <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                        Click the image to view larger, or the X to remove
                      </p>
                    </div>
                  </div>

                  <!-- Notes -->
                  <div>
                    <label for="notes" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                      Additional Notes (Optional)
                    </label>
                    <TextArea
                      id="notes"
                      v-model="form.notes"
                      :rows="3"
                      class="block w-full text-sm"
                      placeholder="Any additional information about your payment..."
                    />
                    <InputError :message="form.errors.notes" class="mt-2" />
                  </div>

                  <!-- Submit Button -->
                  <div class="pt-2">
                    <PrimaryButton
                      type="submit"
                      :disabled="form.processing || !isFormValid"
                      class="w-full justify-center"
                    >
                      <span v-if="form.processing">
                        <LoadingSpinner size="xs" class="mr-2" />
                        Processing Payment...
                      </span>
                      <span v-else class="flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        Submit Payment
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
  </GuestLayout>
</template>

<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Link, useForm, router } from '@inertiajs/vue3';
import TextInput from '@/Components/TextInput.vue';
import TextArea from '@/Components/TextArea.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import LoadingSpinner from '@/Components/LoadingSpinner.vue';
import { computed, ref, watch } from 'vue';
import { InformationCircleIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
  delivery: Object,
  paymentMethods: Array,
  paymentType: {
    type: String,
    default: 'prepaid'
  }
});

const form = useForm({
  method: '',
  reference_number: '',
  amount: parseFloat(props.delivery.total_price),
  receipt_image: null,
  notes: '',
});

const selectedMethod = ref('');
const receiptPreview = ref(null);

const showPaymentInstructions = computed(() => {
  return selectedMethod.value && selectedMethod.value !== 'cash';
});

const isFormValid = computed(() => {
  return form.method && 
         (form.method === 'cash' || form.reference_number) && 
         form.receipt_image;
});

watch(() => form.method, (newMethod) => {
  selectedMethod.value = newMethod;
});

const handleReceiptUpload = (event) => {
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
};

const removeImagePreview = () => {
  receiptPreview.value = null;
  form.receipt_image = null;
  // Clear the file input
  const fileInput = document.getElementById('receipt_image');
  if (fileInput) {
    fileInput.value = '';
  }
};

const openImageModal = (imageUrl) => {
  window.open(imageUrl, '_blank');
};

const submit = () => {
  form.post(route('customer.payments.store', props.delivery.id), {
    forceFormData: true,
    onSuccess: () => {
      form.reset();
      receiptPreview.value = null;
    }
  });
};
</script>