<template>
  <GuestLayout>
    <template #header>
      <div class="flex justify-between items-center">
        <div>
          <h2 class="text-xl font-semibold text-gray-800">
            {{ paymentType === 'postpaid' ? 'Settle Postpaid Payment' : 'Make Payment' }} - Delivery #{{ delivery.reference_number }}
          </h2>
          <p v-if="paymentType === 'postpaid'" class="text-sm text-gray-600 mt-1">
            Your delivery is complete. Please settle your payment online.
          </p>
        </div>
        <Link 
          :href="route('customer.delivery-requests.show', delivery.id)"
          as="button"
        >
          <SecondaryButton>
            ← Back to Delivery Details
          </SecondaryButton>
        </Link>
      </div>
    </template>

    <div class="max-w-4xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
      <!-- Delivery Summary -->
      <div class="bg-white shadow rounded-lg mb-6 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
          <h3 class="text-lg font-medium text-gray-900">Delivery Summary</h3>
        </div>
        <div class="px-6 py-4">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <h4 class="font-medium text-gray-700 mb-2">Sender Information</h4>
              <p class="text-gray-900">{{ delivery.sender.name }}</p>
              <p class="text-sm text-gray-600">{{ delivery.sender.email }}</p>
              <p class="text-sm text-gray-600">{{ delivery.sender.mobile }}</p>
            </div>
            <div>
              <h4 class="font-medium text-gray-700 mb-2">Receiver Information</h4>
              <p class="text-gray-900">{{ delivery.receiver.name }}</p>
              <p class="text-sm text-gray-600">{{ delivery.receiver.email }}</p>
              <p class="text-sm text-gray-600">{{ delivery.receiver.mobile }}</p>
            </div>
          </div>
          
          <div class="mt-6">
  <h4 class="font-medium text-gray-700 mb-2">Package Details</h4>
  <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
    <div v-for="(pkg, index) in delivery.packages" :key="pkg.id" 
         class="mb-3 last:mb-0 pb-3 last:pb-0 border-b last:border-b-0 border-gray-200">

      <!-- Item name -->
      <p class="font-medium text-gray-900">{{ pkg.item_name }}</p>

      <!-- Detailed description -->
      <div class="text-sm text-gray-600 space-y-1">
        <p><span class="font-medium">Weight:</span> {{ pkg.weight }} kg</p>
        <p><span class="font-medium">Dimensions:</span> 
          {{ pkg.height }} cm × {{ pkg.width }} cm × {{ pkg.length }} cm
        </p>
        <p><span class="font-medium">Package Value:</span> ₱{{ parseFloat(pkg.value).toFixed(2) }}</p>
      </div>

    </div>
  </div>
</div>

          
<div class="mt-6 p-4 bg-white rounded-lg border border-gray-200">
  <div class="flex justify-between items-center">
    <span class="text-lg font-semibold text-gray-900">Total Amount Due:</span>
    <span class="text-2xl font-bold text-gray-800">₱{{ parseFloat(delivery.total_price).toFixed(2) }}</span>
  </div>
</div>

        </div>
      </div>

      <!-- Payment Instructions -->
      <div v-if="showPaymentInstructions" class="bg-blue-50 border border-blue-200 rounded-lg p-6 mb-6">
        <div class="flex flex-col md:flex-row gap-6">
          <!-- Instructions -->
          <div class="flex-1">
            <h3 class="text-lg font-medium text-blue-800 mb-3">Payment Instructions</h3>
            
            <!-- GCash payment information -->
            <div v-if="selectedMethod === 'gcash'" class="space-y-3">
              <h4 class="font-medium text-blue-700">💳 GCash Payment Instructions</h4>
              <ol class="list-decimal list-inside space-y-2 text-sm text-blue-800">
                <li>Open your GCash app</li>
                <li>Go to "Scan QR Code"</li>
                <li>Scan the QR code on the right</li>
                <li>Verify the amount: <strong>₱{{ parseFloat(delivery.total_price).toFixed(2) }}</strong></li>
                <li>Complete the transaction</li>
                <li>Take a screenshot of the confirmation</li>
                <li>Upload the screenshot below</li>
              </ol>
            </div>
            
            <!-- Bank payment information -->
            <div v-else-if="selectedMethod === 'bank'" class="space-y-3">
              <h4 class="font-medium text-blue-700">🏦 Bank Transfer Instructions</h4>
              <div class="text-sm text-blue-800 space-y-2">
                <p><strong>Bank:</strong> Sample Bank</p>
                <p><strong>Account Name:</strong> Infinitrix Logistics</p>
                <p><strong>Account Number:</strong> 1234-5678-9012</p>
                <p><strong>Amount:</strong> ₱{{ parseFloat(delivery.total_price).toFixed(2) }}</p>
                <p><strong>Reference:</strong> {{ delivery.reference_number }}</p>
                <p class="mt-2">After completing the transfer, please take a screenshot of the confirmation and upload it below.</p>
              </div>
            </div>
          </div>
          
          <!-- QR Code for GCash -->
          <div v-if="selectedMethod === 'gcash'" class="flex flex-col items-center justify-center">
            <div class="bg-white p-4 rounded-lg border border-gray-200">
              <div class="w-40 h-40 bg-gray-200 flex items-center justify-center mb-2">
                <span class="text-gray-500 text-sm">GCash QR Code</span>
              </div>
              <p class="text-xs text-center text-gray-600 mt-2">Scan to pay with GCash</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Payment Form -->
      <div class="bg-white shadow rounded-lg overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
          <h3 class="text-lg font-medium text-gray-900">Payment Information</h3>
        </div>
        
        <form @submit.prevent="submit" class="px-6 py-4" enctype="multipart/form-data">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Payment Method -->
            <div class="md:col-span-2">
              <label class="block text-sm font-medium text-gray-700 mb-2">
                Payment Method *
              </label>
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div 
                  v-for="method in paymentMethods" 
                  :key="method"
                  @click="form.method = method"
                  :class=" [
                    'border rounded-lg p-4 cursor-pointer transition-colors',
                    form.method === method 
                      ? 'border-blue-500 bg-blue-50' 
                      : 'border-gray-300 hover:border-gray-400'
                  ]"
                >
                  <div class="flex items-center">
                    <input 
                      type="radio" 
                      :id="`method-${method}`"
                      :value="method"
                      v-model="form.method"
                      class="h-4 w-4 text-blue-600 focus:ring-blue-500"
                    />
                    <label :for="`method-${method}`" class="ml-3 block text-sm font-medium text-gray-700 capitalize">
                      {{ method }}
                    </label>
                  </div>
                </div>
              </div>
              <InputError :message="form.errors.method" class="mt-2" />
            </div>

            <!-- Show note if switching from cash to gcash/bank -->
            <div v-if="delivery.payment_method === 'cash' && form.method && form.method !== 'cash'" 
                 class="md:col-span-2 bg-yellow-50 border border-yellow-200 rounded-lg p-4 mt-2">
              <div class="flex items-center">
                <InformationCircleIcon class="h-5 w-5 text-yellow-400 mr-2" />
                <span class="text-yellow-800 text-sm">
                  <strong>Note:</strong> Your payment method will be updated from Cash to {{ form.method.toUpperCase() }}.
                  Future communications will reference this payment method.
                </span>
              </div>
            </div>

            <!-- Reference Number (for GCash/Bank) -->
            <div v-if="form.method && form.method !== 'cash'" class="md:col-span-2">
              <label for="reference_number" class="block text-sm font-medium text-gray-700">
                Reference Number *
              </label>
              <TextInput
                id="reference_number"
                v-model="form.reference_number"
                type="text"
                class="mt-1 block w-full"
                :class="{ 'border-red-500': form.errors.reference_number }"
                placeholder="Enter transaction reference number"
              />
              <InputError :message="form.errors.reference_number" class="mt-2" />
              <p class="mt-1 text-sm text-gray-500">
                Please enter the exact reference number from your transaction.
              </p>
            </div>

          <div class="md:col-span-2" v-show="false">
  <label for="amount" class="block text-sm font-medium text-gray-700">
    Amount *
  </label>
  <TextInput
    id="amount"
    v-model="form.amount"
    type="number"
    step="0.01"
    min="0.01"
    class="mt-1 block w-full"
    :class="{ 'border-red-500': form.errors.amount }"
    placeholder="Enter payment amount"
  />
  <InputError :message="form.errors.amount" class="mt-2" />
  <p class="mt-1 text-sm text-gray-500">
    Total due: ₱{{ parseFloat(delivery.total_price).toFixed(2) }}
  </p>
</div>


            <!-- Receipt Image -->
            <div class="md:col-span-2">
              <label for="receipt_image" class="block text-sm font-medium text-gray-700">
                Payment Proof (Screenshot/Photo) *
              </label>
              <input
                id="receipt_image"
                type="file"
                @input="form.receipt_image = $event.target.files[0]"
                accept="image/*"
                class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"
                :class="{ 'border-red-500': form.errors.receipt_image }"
              />
              <InputError :message="form.errors.receipt_image" class="mt-2" />
              <p class="mt-1 text-sm text-gray-500">
                Upload a clear screenshot or photo of your payment confirmation
              </p>
            </div>

            <!-- Notes -->
            <div class="md:col-span-2">
              <label for="notes" class="block text-sm font-medium text-gray-700">
                Additional Notes (Optional)
              </label>
              <TextArea
                id="notes"
                v-model="form.notes"
                :rows="3" 
                class="mt-1 block w-full"
                placeholder="Any additional information about your payment..."
              />
              <InputError :message="form.errors.notes" class="mt-2" />
            </div>
          </div>

          <!-- Submit Button -->
          <div class="mt-6 flex justify-end space-x-3">
            <PrimaryButton
              type="submit"
              :disabled="form.processing"
            >
              <span v-if="form.processing">
                <LoadingSpinner class="w-4 h-4 mr-2" />
                Processing Payment...
              </span>
              <span v-else>
                Submit Payment
              </span>
            </PrimaryButton>
          </div>
        </form>
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

const showPaymentInstructions = computed(() => {
  return selectedMethod.value && selectedMethod.value !== 'cash';
});

watch(() => form.method, (newMethod) => {
  selectedMethod.value = newMethod;
});

const submit = () => {
  console.log('Submitting payment form:', form.data());
  
  form.post(route('customer.payments.store', props.delivery.id), {
    forceFormData: true,
    onSuccess: () => {
      console.log('Payment submitted successfully');
      form.reset();
    },
    onError: (errors) => {
      console.log('Form errors:', errors);
    },
    onFinish: () => {
      console.log('Form submission finished');
    },
  });
};
</script>