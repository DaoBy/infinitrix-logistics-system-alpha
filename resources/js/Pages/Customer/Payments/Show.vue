<template>
  <GuestLayout>
    <template #header>
      <div class="flex justify-between items-center">
        <div>
          <h2 class="text-xl font-semibold text-gray-800">
            Payment Details - #{{ payment.delivery_request.reference_number }}
          </h2>
          <p class="text-sm text-gray-600 mt-1">
            <span :class="statusBadgeClass">{{ payment.delivery_request.status }}</span>
            <span :class="paymentStatusBadgeClass" class="ml-2">
              Payment: {{ paymentStatusText }}
            </span>
          </p>
        </div>
        <Link 
          :href="route('customer.delivery-requests.show', payment.delivery_request_id)"
          class="text-sm text-blue-600 hover:text-blue-800"
        >
          <SecondaryButton>
            ← Back to Delivery
          </SecondaryButton>
        </Link>
      </div>
    </template>

    <div class="max-w-4xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
      <!-- Status Alert -->
      <div :class="statusAlertClass" class="mb-6 rounded-lg p-4">
        <div class="flex items-center">
          <component :is="statusIcon" class="h-5 w-5 mr-2" />
          <span class="font-medium">{{ statusTitle }}</span>
        </div>
        <p class="mt-1 text-sm">{{ statusMessage }}</p>
        <p v-if="payment.rejection_reason" class="mt-2 text-sm bg-white bg-opacity-30 p-2 rounded">
          <strong>Reason:</strong> {{ payment.rejection_reason }}
        </p>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Payment Details -->
        <div class="lg:col-span-2 space-y-6">
          <div class="bg-white shadow rounded-lg overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
              <h3 class="text-lg font-medium text-gray-900">Payment Information</h3>
            </div>
            <div class="px-6 py-4">
              <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                  <div v-if="!isCashPayment" class="mb-4">
                    <label class="block text-sm font-medium text-gray-500">Reference Number</label>
                    <p class="mt-1 text-sm text-gray-900">{{ payment.reference_number || 'N/A' }}</p>
                  </div>
                  <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-500">Method</label>
                    <p class="mt-1 text-sm text-gray-900 capitalize">{{ payment.method }}</p>
                  </div>
                </div>
                <div>
                  <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-500">Date</label>
                    <p class="mt-1 text-sm text-gray-900">{{ formatDateTime(payment.paid_at) }}</p>
                  </div>
                  <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-500">Type</label>
                    <p class="mt-1 text-sm text-gray-900 capitalize">{{ payment.type }}</p>
                  </div>
                </div>
              </div>
              
              <div class="mt-4 pt-4 border-t border-gray-200">
                <div class="flex justify-between items-center">
                  <span class="text-lg font-semibold text-gray-900">Amount Paid:</span>
                  <span class="text-2xl font-bold text-blue-700">₱{{ parseFloat(payment.amount).toFixed(2) }}</span>
                </div>
              </div>
            </div>
          </div>

          <!-- Receipt Image (Only for online payments) -->
          <div v-if="!isCashPayment" class="bg-white shadow rounded-lg overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
              <h3 class="text-lg font-medium text-gray-900">Payment Proof</h3>
            </div>
            <div class="px-6 py-4">
              <img 
                :src="payment.receipt_image_url" 
                alt="Payment receipt"
                class="max-w-full h-auto rounded-lg border border-gray-200 mx-auto max-h-64 object-contain"
                @error="handleImageError"
              />
            </div>
          </div>

          <!-- Cash Payment Note -->
          <div v-if="isCashPayment" class="bg-blue-50 border border-blue-200 rounded-lg p-4">
            <div class="flex">
              <div class="flex-shrink-0">
                <InformationCircleIcon class="h-5 w-5 text-blue-400" />
              </div>
              <div class="ml-3">
                <h3 class="text-sm font-medium text-blue-800">Cash Payment</h3>
                <div class="mt-2 text-sm text-blue-700">
                  <p>This payment was made in cash. No digital receipt or reference number is available.</p>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Delivery Summary -->
        <div class="space-y-6">
          <div class="bg-white shadow rounded-lg overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
              <h3 class="text-lg font-medium text-gray-900">Delivery Summary</h3>
            </div>
            <div class="px-6 py-4">
              <div class="mb-4">
                <label class="block text-sm font-medium text-gray-500">Reference #</label>
                <p class="mt-1 text-sm text-gray-900">{{ payment.delivery_request.reference_number }}</p>
              </div>
              
              <div class="mb-4">
                <label class="block text-sm font-medium text-gray-500">Route</label>
                <p class="mt-1 text-sm text-gray-900">
                  {{ payment.delivery_request.pick_up_region?.name }} → 
                  {{ payment.delivery_request.drop_off_region?.name }}
                </p>
              </div>
              
              <div class="mb-4">
                <label class="block text-sm font-medium text-gray-500">Packages</label>
                <p class="mt-1 text-sm text-gray-900">{{ payment.delivery_request.packages?.length }} items</p>
              </div>
            
            </div>
          </div>

          <!-- Action Buttons -->
          <div class="bg-white shadow rounded-lg overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
              <h3 class="text-lg font-medium text-gray-900">Actions</h3>
            </div>
            <div class="px-6 py-4 space-y-3">
              <Link
                :href="route('customer.delivery-requests.show', payment.delivery_request_id)"
                class="w-full"
              >
                <PrimaryButton class="w-full justify-center">
                  View Delivery Details
                </PrimaryButton>
              </Link>
              
              <Link
                v-if="payment.rejected_by && !isCashPayment"
                :href="route('customer.payments.create', payment.delivery_request_id)"
                class="w-full"
              >
                <SecondaryButton class="w-full justify-center">
                  Submit New Payment
                </SecondaryButton>
              </Link>

              <Link
                v-if="payment.rejected_by && isCashPayment"
                :href="route('customer.delivery-requests.show', payment.delivery_request_id)"
                class="w-full"
              >
                <SecondaryButton class="w-full justify-center">
                  Contact Support
                </SecondaryButton>
              </Link>
            </div>
          </div>
        </div>
      </div>
    </div>
  </GuestLayout>
</template>

<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Link } from '@inertiajs/vue3';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { 
  CheckCircleIcon, 
  XCircleIcon, 
  ClockIcon,
  ExclamationTriangleIcon,
  InformationCircleIcon
} from '@heroicons/vue/24/outline';
import { computed } from 'vue';

const props = defineProps({
  payment: Object
});

// Check if payment method is cash
const isCashPayment = computed(() => {
  return props.payment.method === 'cash';
});

const statusBadgeClass = computed(() => {
  return 'text-green-800 bg-green-100 px-2 py-1 rounded-full text-xs';
});

const paymentStatusBadgeClass = computed(() => {
  if (props.payment.verified_by) return 'text-green-800 bg-green-100 px-2 py-1 rounded-full text-xs';
  if (props.payment.rejected_by) return 'text-red-800 bg-red-100 px-2 py-1 rounded-full text-xs';
  return 'text-yellow-800 bg-yellow-100 px-2 py-1 rounded-full text-xs';
});

const paymentStatusText = computed(() => {
  if (props.payment.verified_by) return 'verified';
  if (props.payment.rejected_by) return 'rejected';
  return 'pending verification';
});

const statusAlertClass = computed(() => {
  if (props.payment.verified_by) return 'bg-green-50 border border-green-200 text-green-800';
  if (props.payment.rejected_by) return 'bg-red-50 border border-red-200 text-red-800';
  return 'bg-yellow-50 border border-yellow-200 text-yellow-800';
});

const statusIcon = computed(() => {
  if (props.payment.verified_by) return CheckCircleIcon;
  if (props.payment.rejected_by) return XCircleIcon;
  return ClockIcon;
});

const statusTitle = computed(() => {
  if (props.payment.verified_by) return 'Payment Verified';
  if (props.payment.rejected_by) return 'Payment Rejected';
  return 'Pending Verification';
});

const statusMessage = computed(() => {
  if (props.payment.verified_by) {
    return `Verified by ${props.payment.verified_by?.name} on ${formatDateTime(props.payment.verified_at)}`;
  }
  if (props.payment.rejected_by) {
    return `Rejected on ${formatDateTime(props.payment.rejected_at)}`;
  }
  return 'Your payment is being reviewed by our team. This usually takes 1-2 business days.';
});

const formatDateTime = (dateString) => {
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
};

const handleImageError = (event) => {
  event.target.src = 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAwIiBoZWlnaHQ9IjIwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KICA8cmVjdCB3aWR0aD0iMjAwIiBoZWlnaHQ9IjIwMCIgZmlsbD0iI2YzZjNmMyIvPgogIDx0ZXh0IHg9IjUwJSIgeT0iNTAlIiBkeT0iLjM1ZW0iIHRleHQtYW5jaG9yPSJtaWRkbGUiIGZvbnQtZmFtaWx5PSJzYW5zLXNlcmlmIiBmb250LXNpemU9IjE0IiBmaWxsPSIjOTk5Ij5ObyBJbWFnZTwvdGV4dD4KICA8dGV4dCB4PSI1MCUiIHk9IjUwJSIgZHk9IjEuMzVlbSIgdGV4dC1hbmNob3I9Im1pZGRsZSIgZm9udC1mYW1pbHk9InNhbnMtc2VyaWYiIGZvbnQtc2l6ZT0iMTIiIGZpbGw9IiM5OTkiPkF2YWlsYWJsZTwvdGV4dD4KPC9zdmc+';
};
</script>