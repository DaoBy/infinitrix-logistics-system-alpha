<template>
  <EmployeeLayout>
    <template #header>
      <div class="flex justify-between items-center">
        <div>
          <h2 class="text-xl font-semibold text-gray-800">
            Record Cash Payment
          </h2>
          <p class="text-sm text-gray-600 mt-1">Process over-the-counter cash payments</p>
        </div>
        <Link :href="route('staff.payments.dashboard')">  
          <SecondaryButton>← Back to Payments</SecondaryButton>
        </Link>
      </div>
    </template>

    <div class="max-w-6xl mx-auto py-6">
      <!-- Payment Form -->
      <div v-if="delivery && delivery.packages" class="bg-white shadow-sm rounded-lg border border-gray-200">
        <!-- Header -->
        <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
          <h3 class="text-lg font-medium text-gray-800">Cash Payment Processing</h3>
          <p class="text-sm text-gray-600 mt-1">Reference: {{ delivery.reference_number }}</p>
        </div>
        
        <div class="p-6">
          <!-- Delivery Information -->
          <div class="mb-8">
            <h4 class="font-medium text-gray-700 mb-4 text-lg border-b pb-2">Delivery Information</h4>
            
           <!-- Sender & Receiver Information -->
<div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
  <!-- Sender -->
  <div class="border border-gray-200 rounded-lg p-4 bg-gray-50">
    <h5 class="font-medium text-gray-700 mb-3 flex items-center">
      <svg class="h-5 w-5 text-gray-400 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
              d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
      </svg>
      Sender Information
    </h5>
    <div class="space-y-2 text-sm">
      <p><span class="text-gray-500">Name:</span> <span class="text-gray-900 font-medium">{{ delivery.sender?.name || 'N/A' }}</span></p>
      <p><span class="text-gray-500">Email:</span> <span class="text-gray-900">{{ delivery.sender?.email || 'N/A' }}</span></p>
      <p><span class="text-gray-500">Mobile:</span> <span class="text-gray-900">{{ delivery.sender?.mobile || 'N/A' }}</span></p>
      <p><span class="text-gray-500">Address:</span> <span class="text-gray-900">{{ delivery.sender?.address || 'N/A' }}</span></p>
    </div>
  </div>

  <!-- Receiver -->
  <div class="border border-gray-200 rounded-lg p-4 bg-gray-50">
    <h5 class="font-medium text-gray-700 mb-3 flex items-center">
      <svg class="h-5 w-5 text-gray-400 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
              d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
      </svg>
      Receiver Information
    </h5>
    <div class="space-y-2 text-sm">
      <p><span class="text-gray-500">Name:</span> <span class="text-gray-900 font-medium">{{ delivery.receiver?.name || 'N/A' }}</span></p>
      <p><span class="text-gray-500">Email:</span> <span class="text-gray-900">{{ delivery.receiver?.email || 'N/A' }}</span></p>
      <p><span class="text-gray-500">Mobile:</span> <span class="text-gray-900">{{ delivery.receiver?.mobile || 'N/A' }}</span></p>
      <p><span class="text-gray-500">Address:</span> <span class="text-gray-900">{{ delivery.receiver?.address || 'N/A' }}</span></p>
    </div>
  </div>
</div>


            <!-- Package Details -->
<div class="bg-white shadow rounded-lg overflow-hidden">
  <!-- Header -->
  <div class="px-6 py-4 border-b border-gray-200">
    <h3 class="text-lg font-medium text-gray-900">Package Details</h3>
  </div>

  <!-- Table -->
  <div class="px-6 py-4">
    <div class="overflow-x-auto">
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              Item
            </th>
            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              Dimensions
            </th>
            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              Weight
            </th>
            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              Value
            </th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
          <tr v-for="pkg in delivery.packages" :key="pkg.id" class="hover:bg-gray-50">
            <!-- Item + Category -->
            <td class="px-4 py-4 whitespace-nowrap">
              <div class="font-medium text-gray-900">{{ pkg.item_name || 'N/A' }}</div>
              <div class="text-sm text-gray-500">{{ pkg.category || 'Uncategorized' }}</div>
            </td>

            <!-- Dimensions -->
            <td class="px-4 py-4 whitespace-nowrap text-gray-900">
              {{ pkg.height || 0 }}cm × {{ pkg.width || 0 }}cm × {{ pkg.length || 0 }}cm
            </td>

            <!-- Weight -->
            <td class="px-4 py-4 whitespace-nowrap text-gray-900">
              {{ pkg.weight || 0 }} kg
            </td>

            <!-- Value -->
            <td class="px-4 py-4 whitespace-nowrap text-gray-900 font-medium">
              ₱{{ parseFloat(pkg.value || 0).toFixed(2) }}
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</div>

          </div>

          <!-- Price Breakdown -->
          <div class="mb-6 border border-gray-200 rounded-lg p-4 bg-blue-50">
            <h4 class="font-medium text-gray-700 mb-4 text-lg border-b pb-2">Price Breakdown</h4>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <!-- Fees -->
              <div class="space-y-3">
                <div class="flex justify-between items-center">
                  <span class="text-gray-600">Base Fee:</span>
                  <span class="text-gray-900 font-medium">₱{{ parseFloat(delivery.base_fee || 0).toFixed(2) }}</span>
                </div>
                
                <div class="flex justify-between items-center">
                  <span class="text-gray-600">Volume Fee:</span>
                  <span class="text-gray-900 font-medium">₱{{ parseFloat(delivery.volume_fee || 0).toFixed(2) }}</span>
                </div>
                
                <div class="flex justify-between items-center">
                  <span class="text-gray-600">Weight Fee:</span>
                  <span class="text-gray-900 font-medium">₱{{ parseFloat(delivery.weight_fee || 0).toFixed(2) }}</span>
                </div>
                
                <div class="flex justify-between items-center">
                  <span class="text-gray-600">Package Fee ({{ (delivery.packages && delivery.packages.length) || 0 }}):</span>
                  <span class="text-gray-900 font-medium">₱{{ parseFloat(delivery.package_fee || 0).toFixed(2) }}</span>
                </div>
              </div>

              <!-- Total -->
              <div class="border-l border-gray-200 pl-6">
                <div class="flex justify-between items-center text-lg font-semibold mb-2">
                  <span class="text-gray-800">Total Amount:</span>
                  <span class="text-blue-600">₱{{ parseFloat(delivery.total_price || 0).toFixed(2) }}</span>
                </div>
                
                <div class="flex justify-between items-center text-sm text-gray-600">
                  <span>Original Payment Method:</span>
                  <span class="capitalize font-medium">{{ delivery.payment_method || 'N/A' }}</span>
                </div>
                
                <div v-if="delivery.payment_status" class="flex justify-between items-center text-sm text-gray-600 mt-1">
                  <span>Current Status:</span>
                  <span :class="paymentStatusClass" class="capitalize px-2 py-1 rounded-full text-xs">
                    {{ delivery.payment_status || 'N/A' }}
                  </span>
                </div>
              </div>
            </div>
          </div>

          <!-- Payment Method Change Notice -->
          <div v-if="delivery.payment_method && delivery.payment_method !== 'cash'" 
               class="mb-6 bg-yellow-50 border border-yellow-200 rounded-lg p-4">
            <div class="flex items-center">
              <svg class="h-5 w-5 text-yellow-400 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
              </svg>
              <div>
                <h4 class="font-medium text-yellow-800">Payment Method Change</h4>
                <p class="text-sm text-yellow-700 mt-1">
                  Changing payment method from <strong>{{ delivery.payment_method.toUpperCase() }}</strong> to <strong>CASH</strong>
                </p>
                <p class="text-xs text-yellow-600 mt-2">
                  This will override the original payment method selection to Cash.
                </p>
              </div>
            </div>
          </div>

          <!-- Payment Details -->
          <div class="mb-8">
            <h4 class="font-medium text-gray-700 mb-4 text-lg border-b pb-2">Cash Payment Details</h4>
            
            <!-- Amount Section -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
              <!-- Total Due -->
              <div class="border border-gray-200 rounded-md p-4 bg-gray-50">
                <p class="text-sm text-gray-600 mb-1">Total Due</p>
                <p class="text-xl font-semibold text-gray-900">₱{{ parseFloat(delivery.total_price || 0).toFixed(2) }}</p>
              </div>
              
              <!-- Amount Received -->
              <div class="border border-gray-200 rounded-md p-4">
                <label class="block text-sm text-gray-600 mb-1 font-medium">
                  Amount Received *
                </label>
                <div class="relative">
                  <span class="absolute left-3 top-3 text-gray-500">₱</span>
                  <TextInput
                    v-model="form.amount_received"
                    type="number"
                    step="0.01"
                    min="0.01"
                    placeholder="0.00"
                    class="w-full pl-8 text-base border-gray-300 focus:border-blue-500"
                  />
                </div>
                <InputError :message="form.errors.amount_received" class="mt-1" />
              </div>
              
              <!-- Change -->
              <div :class="['border rounded-md p-4', change >= 0 ? 'border-gray-200 bg-green-50' : 'border-red-200 bg-red-50']">
                <p class="text-sm text-gray-600 mb-1">Change</p>
                <p class="text-xl font-semibold" :class="change >= 0 ? 'text-green-700' : 'text-red-700'">
                  ₱{{ change.toFixed(2) }}
                </p>
                <p v-if="change < 0 && form.amount_received > 0" class="text-xs mt-1 text-red-600">
                  Insufficient payment
                </p>
                <p v-else-if="change > 0" class="text-xs mt-1 text-green-600">
                  Change to be given
                </p>
              </div>
            </div>

            <!-- Notes -->
            <div>
              <label class="block text-sm text-gray-600 mb-2 font-medium">Notes (Optional)</label>
              <TextArea 
                v-model="form.notes" 
                rows="3" 
                class="w-full border-gray-300 focus:border-blue-500" 
                placeholder="Add any additional notes about this cash payment..."
              />
              <InputError :message="form.errors.notes" />
            </div>
          </div>

          <!-- Action Buttons -->
          <div class="flex justify-between items-center pt-6 border-t border-gray-200">
            <div class="text-sm text-gray-500">
              <p>Staff: {{ $page.props.auth.user.name }}</p>
              <p>Date: {{ currentDate }}</p>
              <p>Time: {{ currentTime }}</p>
            </div>
            <div class="flex space-x-3">
              <SecondaryButton @click="resetForm">
                Reset Form
              </SecondaryButton>
              <PrimaryButton 
                :disabled="form.processing || !canSubmit"
                class="min-w-[150px]"
                @click="submit"
              >
                <span v-if="form.processing">
                  <LoadingSpinner class="w-4 h-4 mr-2" />
                  Processing...
                </span>
                <span v-else>Record Cash Payment</span>
              </PrimaryButton>
            </div>
          </div>
        </div>
      </div>

      <!-- Loading state -->
      <div v-else-if="delivery === null" class="bg-white shadow-sm rounded-lg border border-gray-200 p-8 text-center">
        <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-blue-100">
          <LoadingSpinner class="w-6 h-6 text-blue-600" />
        </div>
        <h3 class="mt-4 text-lg font-medium text-gray-900">Loading Delivery Information</h3>
        <p class="mt-2 text-sm text-gray-500">Please wait while we load the delivery details...</p>
      </div>

      <!-- No delivery provided message -->
      <div v-else class="bg-white shadow-sm rounded-lg border border-gray-200 p-8 text-center">
        <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-gray-100">
          <svg class="h-6 w-6 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
          </svg>
        </div>
        <h3 class="mt-4 text-lg font-medium text-gray-900">No Delivery Selected</h3>
        <p class="mt-2 text-sm text-gray-500">Please go back to the payments page and select a delivery to record payment.</p>
        <div class="mt-6">
             <Link :href="route('staff.payments.dashboard')">  
          <PrimaryButton class="px-4 py-2">
            ← Back to Payments
          </PrimaryButton>
          </Link>
        </div>
      </div>
    </div>
  </EmployeeLayout>
</template>

<script setup>
import EmployeeLayout from '@/Layouts/EmployeeLayout.vue';
import { Link, useForm } from '@inertiajs/vue3';
import TextInput from '@/Components/TextInput.vue';
import TextArea from '@/Components/TextArea.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import LoadingSpinner from '@/Components/LoadingSpinner.vue';
import { computed, onMounted, ref } from 'vue';

const props = defineProps({
  delivery: {
    type: Object,
    default: null
  }
});

const currentDate = ref(new Date().toLocaleDateString('en-US', {
  year: 'numeric',
  month: 'long',
  day: 'numeric'
}));

const currentTime = ref(new Date().toLocaleTimeString('en-US', {
  hour: '2-digit',
  minute: '2-digit'
}));

const form = useForm({
  delivery_request_id: '',
  method: 'cash', // Fixed to cash only
  amount_received: '',
  amount: 0,
  notes: ''
});

// Calculate change
const change = computed(() => {
  if (!form.amount_received || !props.delivery) return 0;
  const received = parseFloat(form.amount_received);
  const total = parseFloat(props.delivery.total_price || 0);
  return received - total;
});

// Calculate package volume
const calculateVolume = (pkg) => {
  const height = parseFloat(pkg.height || 0);
  const width = parseFloat(pkg.width || 0);
  const length = parseFloat(pkg.length || 0);
  const volume = (height * width * length) / 1000000; // Convert to m³
  return volume.toFixed(4);
};

// Payment status badge class
const paymentStatusClass = computed(() => {
  const status = props.delivery?.payment_status;
  if (status === 'paid') return 'bg-green-100 text-green-800';
  if (status === 'pending') return 'bg-yellow-100 text-yellow-800';
  if (status === 'rejected') return 'bg-red-100 text-red-800';
  return 'bg-gray-100 text-gray-800';
});

// Validation for submit button
const canSubmit = computed(() => {
  if (!form.amount_received || !props.delivery) return false;
  
  const received = parseFloat(form.amount_received);
  const total = parseFloat(props.delivery.total_price || 0);
  
  // For cash, check if sufficient amount was received
  return received >= total;
});

// Initialize form when delivery is available
onMounted(() => {
  if (props.delivery) {
    form.delivery_request_id = props.delivery.id;
    form.amount = parseFloat(props.delivery.total_price || 0);
  }
});

const resetForm = () => {
  if (props.delivery) {
    form.amount_received = '';
    form.notes = '';
  }
};

const submit = () => {
  if (!props.delivery) return;
  form.amount = parseFloat(props.delivery.total_price || 0);
  form.post(route('staff.payments.store'), { 
    onSuccess: () => {
      form.reset();
    }
  });
};
</script>

<style scoped>
input[type="number"]::-webkit-outer-spin-button,
input[type="number"]::-webkit-inner-spin-button {
  -webkit-appearance: none;
  margin: 0;
}
input[type="number"] {
  -moz-appearance: textfield;
}
</style>