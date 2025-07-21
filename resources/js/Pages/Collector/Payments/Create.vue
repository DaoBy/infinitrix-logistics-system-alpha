<!-- resources/js/Pages/Collector/Payments/Create.vue -->
<template>
  <EmployeeLayout>
    <template #header>
      <div class="flex justify-between items-center px-6">
        <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
          Record Postpaid Collection
        </h2>
        <Link 
          :href="route('collector.payments.index')"
          class="inline-flex items-center text-sm text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-200"
        >
          <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
          </svg>
          Back to collections
        </Link>
      </div>
    </template>

    <div class="px-2 md:px-6 py-4 max-w-5xl mx-auto">
      <!-- Delivery Info -->
      <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg border border-gray-200 dark:border-gray-700 mb-6">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between p-4 bg-indigo-50 dark:bg-indigo-900 border-b border-gray-200 dark:border-gray-700">
          <div>
            <div class="flex items-center gap-2">
              <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-indigo-100 text-indigo-800 dark:bg-indigo-800 dark:text-indigo-100">
                Reference #
              </span>
              <span class="text-lg font-bold text-indigo-900 dark:text-indigo-100 tracking-wide">
                {{ delivery.reference_number || ('DR-' + String(delivery.id).padStart(6, '0')) }}
              </span>
            </div>
            <div class="mt-1 text-xs text-gray-500 dark:text-gray-300">
              Delivery ID: DO-{{ String(delivery.id).padStart(6, '0') }}
              <span v-if="delivery.created_at">&nbsp;|&nbsp;Created: {{ formatDate(delivery.created_at) }}</span>
            </div>
          </div>
          <div class="mt-3 md:mt-0 flex items-center gap-2">
            <span class="font-medium text-gray-900 dark:text-gray-100">
              ₱{{ Number(delivery.total_price).toFixed(2) }}
            </span>
            <span class="text-xs text-gray-500 dark:text-gray-300">Total Amount</span>
          </div>
        </div>
        <div class="p-4 md:p-6">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4 md:gap-6">
            <!-- Sender Information -->
            <div class="bg-gray-50 dark:bg-gray-700/50 p-3 md:p-4 rounded-lg flex flex-col gap-1">
              <h3 class="text-base font-medium text-gray-900 dark:text-gray-100 mb-2">Sender</h3>
              <p class="font-medium truncate">{{ delivery.sender.name }}</p>
              <p class="text-xs text-gray-600 dark:text-gray-400 truncate">{{ delivery.sender.email }}</p>
              <p class="text-xs text-gray-600 dark:text-gray-400 truncate">{{ delivery.sender.mobile }}</p>
            </div>
            <!-- Receiver Information -->
            <div class="bg-gray-50 dark:bg-gray-700/50 p-3 md:p-4 rounded-lg flex flex-col gap-1">
              <h3 class="text-base font-medium text-gray-900 dark:text-gray-100 mb-2">Receiver</h3>
              <p class="font-medium truncate">{{ delivery.receiver.name }}</p>
              <p class="text-xs text-gray-600 dark:text-gray-400 truncate">{{ delivery.receiver.email }}</p>
              <p class="text-xs text-gray-600 dark:text-gray-400 truncate">{{ delivery.receiver.mobile }}</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Payment Form -->
      <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg border border-gray-200 dark:border-gray-700">
        <div class="p-3 md:p-4 bg-gray-50 dark:bg-gray-700 border-b border-gray-200 dark:border-gray-700">
          <h3 class="font-medium text-gray-900 dark:text-gray-100">
            Collection Details
          </h3>
        </div>
        <div class="p-4 md:p-6">
          <form @submit.prevent="submit">
            <div class="space-y-4">
              <div>
                <InputLabel value="Payment Method *" />
                <SelectInput
                  v-model="form.method"
                  :options="[
                    { value: 'cash', label: 'Cash' },
                    { value: 'gcash', label: 'GCash' },
                    { value: 'bank', label: 'Bank Transfer' }
                  ]"
                  placeholder="Select method"
                  :error="form.errors?.method"
                />
                <InputError :message="form.errors?.method" />
              </div>

              <div>
                <InputLabel value="Amount Collected" />
                <div class="relative rounded-md shadow-sm">
                  <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <span class="text-gray-500 dark:text-gray-400 sm:text-sm">₱</span>
                  </div>
                  <TextInput
                    v-model="form.amount"
                    type="number"
                    step="0.01"
                    min="0"
                    class="block w-full pl-7"
                    placeholder="0.00"
                    :error="form.errors.amount"
                    :readonly="true"
                    tabindex="-1"
                  />
                </div>
                <InputError :message="form.errors.amount" />
              </div>

              <div>
                <InputLabel value="Receipt Photo (Optional)" />
                <input
                  type="file"
                  @input="form.receipt_image = $event.target.files[0]"
                  accept="image/*"
                  class="block w-full text-sm text-gray-500 dark:text-gray-400 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-blue-50 dark:file:bg-blue-900/20 file:text-blue-700 dark:file:text-blue-400 hover:file:bg-blue-100 dark:hover:file:bg-blue-900/30"
                />
                <InputError :message="form.errors.receipt_image" />
              </div>

              <div>
                <InputLabel value="Notes" />
                <TextArea
                  v-model="form.notes"
                  :rows="3"
                  placeholder="Any additional notes about this collection..."
                />
              </div>

              <div class="flex justify-end pt-2">
                <PrimaryButton
                  type="submit"
                  :disabled="form.processing"
                >
                  <span v-if="form.processing">
                    <LoadingSpinner size="xs" class="mr-2" /> Processing...
                  </span>
                  <span v-else>
                    Record Collection
                  </span>
                </PrimaryButton>
              </div>
            </div>
          </form>
        </div>
      </div>
    </div>
  </EmployeeLayout>
</template>

<script setup>
import EmployeeLayout from '@/Layouts/EmployeeLayout.vue';
import { Link, useForm } from '@inertiajs/vue3';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import TextInput from '@/Components/TextInput.vue';
import TextArea from '@/Components/TextArea.vue';
import SelectInput from '@/Components/SelectInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import LoadingSpinner from '@/Components/LoadingSpinner.vue';

const props = defineProps({
  delivery: Object
});

const form = useForm({
  method: '',
  amount: Number(props.delivery.total_price) || 0,
  receipt_image: null,
  notes: '',
  type: 'postpaid' // Always postpaid for collector
});

const submit = () => {
  form.post(route('collector.payments.store', props.delivery.id), {
    forceFormData: true,
    onSuccess: () => {
      form.reset();
    },
    onError: (errors) => {
      // Log errors for debugging
      console.error('Form submission errors:', errors);
    },
    onFinish: () => {
      // Optionally log finish
      // console.log('Form submission finished');
    }
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
</script>