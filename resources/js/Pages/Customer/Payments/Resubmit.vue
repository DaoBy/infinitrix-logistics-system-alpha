<template>
  <GuestLayout>
    <template #header>
      <div class="flex justify-between items-center">
        <div>
          <h2 class="text-xl font-semibold text-gray-800">
            Resubmit Payment - Delivery #{{ delivery.reference_number }}
          </h2>
          <p class="text-sm text-gray-600 mt-1">
            Your previous payment was rejected. Please correct the information and resubmit.
          </p>
          <p class="text-sm text-red-600 mt-1" v-if="existingPayment.rejection_reason">
            <strong>Reason for rejection:</strong> {{ existingPayment.rejection_reason }}
          </p>
        </div>
        <Link 
          :href="route('customer.delivery-requests.show', delivery.id)"
          class="text-sm text-blue-600 hover:text-blue-800"
        >
          <SecondaryButton>
            ← Back to Delivery Details
          </SecondaryButton>
        </Link>
      </div>
    </template>

    <div class="max-w-4xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
      <!-- Rejection Notice -->
      <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6">
        <div class="flex">
          <ExclamationTriangleIcon class="h-5 w-5 text-yellow-400 mr-3" />
          <div>
            <h3 class="text-sm font-medium text-yellow-800">Payment Requires Correction</h3>
            <p class="text-sm text-yellow-700 mt-1">
              Your previous payment submission was rejected. Please review the rejection reason and correct the information below.
            </p>
          </div>
        </div>
      </div>

     <!-- Existing Payment Details -->
<!-- Existing Payment Details -->
<div class="bg-white border border-gray-200 rounded-lg shadow-sm p-5 mb-6">
  <h4 class="font-semibold text-gray-800 mb-4">Previous Submission Details</h4>
  
  <dl class="divide-y divide-gray-100">
    <!-- Method -->
    <div class="py-2 grid grid-cols-2 gap-4 text-sm">
      <dt class="text-gray-600">Method</dt>
      <dd class="font-medium capitalize text-gray-900">
        {{ existingPayment.method }}
      </dd>
    </div>

    <!-- Amount -->
    <div class="py-2 grid grid-cols-2 gap-4 text-sm">
      <dt class="text-gray-600">Amount</dt>
      <dd class="font-medium text-gray-900">
        ₱{{ parseFloat(existingPayment.amount).toFixed(2) }}
      </dd>
    </div>

    <!-- Reference Number -->
    <div v-if="existingPayment.reference_number" class="py-2 grid grid-cols-2 gap-4 text-sm">
      <dt class="text-gray-600">Reference #</dt>
      <dd class="font-medium text-gray-900">
        {{ existingPayment.reference_number }}
      </dd>
    </div>

    <!-- Submitted -->
    <div class="py-2 grid grid-cols-2 gap-4 text-sm">
      <dt class="text-gray-600">Submitted</dt>
      <dd class="font-medium text-gray-900">
        {{ formatDate(existingPayment.created_at) }}
      </dd>
    </div>
  </dl>
</div>



      <!-- Payment Form (same as Create.vue but pre-filled) -->
      <div class="bg-white shadow rounded-lg overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
          <h3 class="text-lg font-medium text-gray-900">Corrected Payment Information</h3>
        </div>
        
        <form @submit.prevent="submit" class="px-6 py-4">
          <!-- Same form fields as Create.vue but pre-filled -->
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
                  :class="[
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

            <!-- Reference Number -->
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
                placeholder="Enter corrected reference number"
              />
              <InputError :message="form.errors.reference_number" class="mt-2" />
            </div>

           <!-- Amount -->
<div class="md:col-span-2" v-show="false">
  <label for="amount" class="block text-sm font-medium text-gray-700">
    Amount Paid *
  </label>
  <div class="mt-1 relative rounded-md shadow-sm">
    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
      <span class="text-gray-500 sm:text-sm">₱</span>
    </div>
    <TextInput
      id="amount"
      v-model="form.amount"
      type="number"
      step="0.01"
      min="0"
      :class="[
        'block w-full pl-7 pr-12',
        { 'border-red-500': form.errors.amount }
      ]"
      placeholder="0.00"
    />
  </div>
  <InputError :message="form.errors.amount" class="mt-2" />
</div>


            <!-- Receipt Image -->
            <div class="md:col-span-2">
              <label for="receipt_image" class="block text-sm font-medium text-gray-700">
                New Payment Proof (Screenshot/Photo) *
                <span class="text-sm text-gray-500 font-normal">- Upload a new image</span>
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
            </div>

            <!-- Notes -->
            <div class="md:col-span-2">
              <label for="notes" class="block text-sm font-medium text-gray-700">
                Additional Notes (Optional)
              </label>
              <TextArea
                id="notes"
                v-model="form.notes"
                rows="3"
                class="mt-1 block w-full"
                placeholder="Any additional information about your corrected payment..."
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
                Resubmitting Payment...
              </span>
              <span v-else>
                Resubmit Payment
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
import { Link, useForm } from '@inertiajs/vue3';
import TextInput from '@/Components/TextInput.vue';
import TextArea from '@/Components/TextArea.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import LoadingSpinner from '@/Components/LoadingSpinner.vue';
import { ExclamationTriangleIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
  delivery: Object,
  existingPayment: Object,
  paymentMethods: Array,
});

const form = useForm({
  method: props.existingPayment.method,
  reference_number: props.existingPayment.reference_number,
  amount: parseFloat(props.existingPayment.amount),
  receipt_image: null,
  notes: props.existingPayment.notes || '',
});

const formatDate = (dateString) => {
  return new Date(dateString).toLocaleString();
};

const submit = () => {
  form.post(route('customer.payments.update', {
    delivery: props.delivery.id,
    payment: props.existingPayment.id
  }), {
    forceFormData: true,
    onSuccess: () => {
      form.reset();
    },
  });
};
</script>