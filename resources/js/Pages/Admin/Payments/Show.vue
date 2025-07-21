<template>
  <EmployeeLayout>
    <template #header>
      <div class="flex justify-between items-center px-6">
        <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
          {{ paymentType === 'prepaid' ? 'Record Payment' : 'Record Collection' }}
        </h2>
        <Link 
          :href="route('staff.payments.index')"
          class="inline-flex items-center text-sm text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-200"
        >
          <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
          </svg>
          Back to payments
        </Link>
      </div>
    </template>

    <div class="px-6 py-4">
      <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg border border-gray-200 dark:border-gray-700">
        <div class="p-4 bg-gray-50 dark:bg-gray-700 border-b border-gray-200 dark:border-gray-700">
          <h3 class="font-medium text-gray-900 dark:text-gray-100">
            Reference: {{ delivery.reference_number || ('DR-' + String(delivery.id).padStart(6, '0')) }}
          </h3>
        </div>

        <div class="p-6 space-y-6">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Sender Information -->
            <div class="bg-gray-50 dark:bg-gray-700/50 p-4 rounded-lg">
              <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-3">Sender Information</h3>
              <div class="space-y-2">
                <p class="font-medium">{{ delivery.sender.name }}</p>
                <p class="text-sm text-gray-600 dark:text-gray-400">{{ delivery.sender.email }}</p>
                <p class="text-sm text-gray-600 dark:text-gray-400">{{ delivery.sender.mobile }}</p>
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-2">{{ delivery.sender.address }}</p>
              </div>
            </div>

            <!-- Receiver Information -->
            <div class="bg-gray-50 dark:bg-gray-700/50 p-4 rounded-lg">
              <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-3">Receiver Information</h3>
              <div class="space-y-2">
                <p class="font-medium">{{ delivery.receiver.name }}</p>
                <p class="text-sm text-gray-600 dark:text-gray-400">{{ delivery.receiver.email }}</p>
                <p class="text-sm text-gray-600 dark:text-gray-400">{{ delivery.receiver.mobile }}</p>
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-2">{{ delivery.receiver.address }}</p>
              </div>
            </div>
          </div>

          <!-- Package Details -->
          <div>
            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-3">Package Details</h3>
            <div class="overflow-x-auto">
              <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                  <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Item</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Dimensions</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Weight</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Value</th>
                  </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                  <tr v-for="pkg in delivery.packages" :key="pkg.id">
                    <td class="px-4 py-4 whitespace-nowrap">
                      <div class="font-medium text-gray-900 dark:text-gray-100">{{ pkg.item_name }}</div>
                      <div class="text-sm text-gray-500 dark:text-gray-400">{{ pkg.category }}</div>
                    </td>
                    <td class="px-4 py-4 whitespace-nowrap text-gray-900 dark:text-gray-100">
                      {{ pkg.height }}cm × {{ pkg.width }}cm × {{ pkg.length }}cm
                    </td>
                    <td class="px-4 py-4 whitespace-nowrap text-gray-900 dark:text-gray-100">
                      {{ pkg.weight }} kg
                    </td>
                    <td class="px-4 py-4 whitespace-nowrap text-gray-900 dark:text-gray-100">
                      ₱{{ pkg.value.toFixed(2) }}
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <!-- Payment Information -->
          <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Payment Information</h3>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <!-- Price Breakdown -->
              <div class="bg-gray-50 dark:bg-gray-700/50 p-4 rounded-lg">
                <h3 class="font-medium text-gray-900 dark:text-gray-100 mb-3">Price Breakdown</h3>
                <div class="space-y-3">
                  <div class="flex justify-between">
                    <span class="text-gray-700 dark:text-gray-300">Base Fee:</span>
                    <span class="text-gray-900 dark:text-gray-100">₱{{ (typeof delivery.base_fee === 'number' ? delivery.base_fee : parseFloat(delivery.base_fee || 0)).toFixed(2) }}</span>
                  </div>
                  <div class="flex justify-between">
                    <span class="text-gray-700 dark:text-gray-300">Volume Fee:</span>
                    <span class="text-gray-900 dark:text-gray-100">₱{{ (typeof delivery.volume_fee === 'number' ? delivery.volume_fee : parseFloat(delivery.volume_fee || 0)).toFixed(2) }}</span>
                  </div>
                  <div class="flex justify-between">
                    <span class="text-gray-700 dark:text-gray-300">Weight Fee:</span>
                    <span class="text-gray-900 dark:text-gray-100">₱{{ (typeof delivery.weight_fee === 'number' ? delivery.weight_fee : parseFloat(delivery.weight_fee || 0)).toFixed(2) }}</span>
                  </div>
                  <div class="flex justify-between">
                    <span class="text-gray-700 dark:text-gray-300">Package Fee ({{ delivery.packages.length }}):</span>
                    <span class="text-gray-900 dark:text-gray-100">₱{{ (typeof delivery.package_fee === 'number' ? delivery.package_fee : parseFloat(delivery.package_fee || 0)).toFixed(2) }}</span>
                  </div>
                  <div class="border-t border-gray-200 dark:border-gray-700 pt-2 mt-2 font-medium flex justify-between">
                    <span class="text-gray-900 dark:text-gray-100">Total:</span>
                    <span class="text-gray-900 dark:text-gray-100">₱{{ (typeof delivery.total_price === 'number' ? delivery.total_price : parseFloat(delivery.total_price || 0)).toFixed(2) }}</span>
                  </div>
                </div>
              </div>

              <!-- Payment Form -->
              <div>
                <form @submit.prevent="submit">
                  <div class="space-y-4">
                    <div>
                      <InputLabel value="Payment Method *" />
                      <SelectInput
                        v-model="form.method"
                        :options="methodOptions"
                        placeholder="Select method"
                        :error="form.errors?.method"
                        :disabled="paymentType !== 'prepaid'"
                      />
                      <InputError :message="form.errors?.method" />
                    </div>

                    <div>
                      <InputLabel value="Amount Paid *" />
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
                          :disabled="paymentType !== 'prepaid'"
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
                        :disabled="paymentType !== 'prepaid'"
                      />
                      <InputError :message="form.errors.receipt_image" />
                    </div>

                    <div>
                      <InputLabel value="Notes" />
                      <TextArea
                        v-model="form.notes"
                        :rows="3"
                        placeholder="Any additional notes about this payment..."
                        :disabled="paymentType !== 'prepaid'"
                      />
                    </div>

                    <div class="flex justify-end pt-2">
                      <PrimaryButton
                        type="submit"
                        :disabled="form.processing || paymentType !== 'prepaid'"
                      >
                        <span v-if="form.processing">
                          <LoadingSpinner size="xs" class="mr-2" /> Processing...
                        </span>
                        <span v-else>
                          Confirm Payment
                        </span>
                      </PrimaryButton>
                    </div>
                    <div v-if="paymentType !== 'prepaid'" class="text-sm text-blue-600 dark:text-blue-400 pt-2">
                      Postpaid payments are collected by collectors and cannot be recorded here.
                    </div>
                  </div>
                </form>
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
import { Link, useForm } from '@inertiajs/vue3';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import TextInput from '@/Components/TextInput.vue';
import TextArea from '@/Components/TextArea.vue';
import SelectInput from '@/Components/SelectInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import LoadingSpinner from '@/Components/LoadingSpinner.vue';

const props = defineProps({
  delivery: Object,
  paymentType: {
    type: String,
    default: 'prepaid'
  }
});

const methodOptions = [
  { value: 'cash', label: 'Cash' },
  { value: 'gcash', label: 'GCash' },
  { value: 'bank', label: 'Bank Transfer' }
];

const form = useForm({
  method: '',
  amount: props.delivery.total_price,
  receipt_image: null,
  notes: '',
  type: props.paymentType
});

const submit = () => {
  if (props.paymentType !== 'prepaid') {
    // Prevent submission for postpaid
    return;
  }
  form.post(route('staff.payments.store', props.delivery.id), {
    forceFormData: true,
    onSuccess: () => {
      form.reset();
    },
    onError: (errors) => {
      // Log errors for debugging
      console.error('Form submission errors:', errors);
    }
  });
};
</script>