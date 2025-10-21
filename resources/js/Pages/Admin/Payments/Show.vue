<template>
  <EmployeeLayout>
    <template #header>
      <div class="flex justify-between items-center">
        <div>
          <h2 class="text-xl font-semibold text-gray-800">
            Payment Details - #{{ paymentReference }}
          </h2>
          <p class="text-sm text-gray-600 mt-1">
            <span :class="statusBadgeClass">{{ deliveryStatus }}</span>
            <span :class="paymentStatusBadgeClass" class="ml-2">
              Payment: {{ paymentStatusText }}
            </span>
          </p>
        </div>
        <Link 
          :href="route('staff.payments.dashboard')"  
          class="text-sm text-blue-600 hover:text-blue-800"
        >
          <SecondaryButton>
            ← Back to Payments
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
                  <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-500">Reference Number</label>
                    <p class="mt-1 text-sm text-gray-900">{{ payment.reference_number || 'N/A' }}</p>
                  </div>
                  <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-500">Method</label>
                    <p class="mt-1 text-sm text-gray-900 capitalize">{{ payment.method }}</p>
                  </div>
                  <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-500">Source</label>
                    <p class="mt-1 text-sm text-gray-900 capitalize">{{ formatSource(payment.source) }}</p>
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
                  <div v-if="payment.verified_at" class="mb-4">
                    <label class="block text-sm font-medium text-gray-500">Verified At</label>
                    <p class="mt-1 text-sm text-gray-900">{{ formatDateTime(payment.verified_at) }}</p>
                  </div>
                  <div v-if="payment.collected_by" class="mb-4">
                    <label class="block text-sm font-medium text-gray-500">Collected By</label>
                    <p class="mt-1 text-sm text-gray-900">{{ payment.collected_by?.name || 'N/A' }}</p>
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

          <!-- Receipt Image -->
          <div v-if="payment.receipt_image" class="bg-white shadow rounded-lg overflow-hidden">
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

          <!-- Notes -->
          <div v-if="payment.notes" class="bg-white shadow rounded-lg overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
              <h3 class="text-lg font-medium text-gray-900">Notes</h3>
            </div>
            <div class="px-6 py-4">
              <p class="text-sm text-gray-700">{{ payment.notes }}</p>
            </div>
          </div>
        </div>

        <!-- Delivery Summary -->
        <div class="space-y-6" v-if="payment.delivery_request">
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
                  {{ payment.delivery_request.pick_up_region?.name || 'N/A' }} → 
                  {{ payment.delivery_request.drop_off_region?.name || 'N/A' }}
                </p>
              </div>
              
              <div class="mb-4">
                <label class="block text-sm font-medium text-gray-500">Packages</label>
                <p class="mt-1 text-sm text-gray-900">{{ payment.delivery_request.packages?.length || 0 }} items</p>
              </div>
              
              <div class="pt-4 border-t border-gray-200">
                <div class="flex justify-between items-center">
                  <span class="text-sm font-semibold text-gray-900">Total:</span>
                  <span class="text-lg font-bold text-blue-700">₱{{ parseFloat(payment.delivery_request.total_price).toFixed(2) }}</span>
                </div>
              </div>
            </div>
          </div>

          <!-- Customer Information -->
          <div class="bg-white shadow rounded-lg overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
              <h3 class="text-lg font-medium text-gray-900">Customer Information</h3>
            </div>
            <div class="px-6 py-4 space-y-4">
              <div>
                <label class="block text-sm font-medium text-gray-500">Sender</label>
                <p class="mt-1 text-sm font-medium text-gray-900">{{ payment.delivery_request.sender?.name || 'N/A' }}</p>
                <p class="text-xs text-gray-600">{{ payment.delivery_request.sender?.mobile || '' }}</p>
                <p class="text-xs text-gray-600">{{ payment.delivery_request.sender?.email || '' }}</p>
              </div>
              
              <div>
                <label class="block text-sm font-medium text-gray-500">Receiver</label>
                <p class="mt-1 text-sm font-medium text-gray-900">{{ payment.delivery_request.receiver?.name || 'N/A' }}</p>
                <p class="text-xs text-gray-600">{{ payment.delivery_request.receiver?.mobile || '' }}</p>
                <p class="text-xs text-gray-600">{{ payment.delivery_request.receiver?.email || '' }}</p>
              </div>
            </div>
          </div>

          <!-- Action Buttons -->
          <div v-if="!payment.verified_by && !payment.rejected_by" class="bg-white shadow rounded-lg overflow-hidden">
      <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
        <h3 class="text-lg font-medium text-gray-900">Verification</h3>
      </div>
      <div class="px-6 py-4 space-y-3">
        <Link
          :href="route('staff.payments.verify', payment.id)" 
          class="w-full"
        >
          <PrimaryButton class="w-full justify-center">
            Verify Payment
          </PrimaryButton>
        </Link>
      </div>
    </div>
        </div>

        <!-- No Delivery Request Info -->
        <div v-else class="space-y-6">
          <div class="bg-white shadow rounded-lg overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
              <h3 class="text-lg font-medium text-gray-900">Delivery Information</h3>
            </div>
            <div class="px-6 py-4">
              <p class="text-sm text-gray-600">No delivery request information available for this payment.</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </EmployeeLayout>
</template>

<script setup>
import EmployeeLayout from '@/Layouts/EmployeeLayout.vue';
import { Link } from '@inertiajs/vue3';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { 
  CheckCircleIcon, 
  XCircleIcon, 
  ClockIcon,
  ExclamationTriangleIcon
} from '@heroicons/vue/24/outline';
import { computed } from 'vue';

const props = defineProps({
  payment: Object
});

// Safe accessors for potentially null relationships
const paymentReference = computed(() => {
  return props.payment.delivery_request?.reference_number || `Payment #${props.payment.id}`;
});

const deliveryStatus = computed(() => {
  return props.payment.delivery_request?.status || 'unknown';
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
    return `Verified by ${props.payment.verified_by?.name || 'staff'} on ${formatDateTime(props.payment.verified_at)}`;
  }
  if (props.payment.rejected_by) {
    return `Rejected by ${props.payment.rejected_by?.name || 'staff'} on ${formatDateTime(props.payment.rejected_at)}`;
  }
  return 'This payment is awaiting verification by staff.';
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

const formatSource = (source) => {
  if (!source) return 'N/A';
  return source.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
};

const handleImageError = (event) => {
  event.target.src = '/images/placeholder-receipt.jpg';
};
</script>