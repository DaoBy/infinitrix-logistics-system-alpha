<template>
  <EmployeeLayout>
    <template #header>
      <div class="flex justify-between items-center px-4 sm:px-6">
        <div class="min-w-0 flex-1">
          <h2 class="text-lg sm:text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200 truncate">
            Payment Details - #{{ paymentReference }}
          </h2>
          <p class="mt-1 text-sm text-gray-600 dark:text-gray-400 hidden sm:block">
            View payment information and details
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
      <!-- Status Alert -->
      <div :class="statusAlertClass" class="rounded-lg p-4 mb-6">
        <div class="flex items-center">
          <component :is="statusIcon" class="h-5 w-5 mr-3" />
          <div>
            <h3 class="text-lg font-medium">{{ statusTitle }}</h3>
            <p class="text-sm mt-1">{{ statusMessage }}</p>
            <p v-if="payment.rejection_reason" class="mt-2 text-sm bg-white bg-opacity-30 dark:bg-gray-800 dark:bg-opacity-30 p-2 rounded">
              <strong>Reason:</strong> {{ payment.rejection_reason }}
            </p>
          </div>
        </div>
      </div>

      <!-- MAIN CONTENT GRID -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- LEFT COLUMN: Payment Information -->
        <div class="lg:col-span-2 space-y-6">
          <!-- PAYMENT INFORMATION CARD -->
          <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg border border-gray-200 dark:border-gray-700">
            <div class="p-4 bg-gray-50 dark:bg-gray-700 border-b border-gray-200 dark:border-gray-700">
              <h3 class="font-medium text-gray-900 dark:text-gray-100 flex items-center gap-2">
                <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                </svg>
                Payment Information
              </h3>
            </div>
            <div class="p-4 md:p-6">
              <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Left Column -->
                <div class="space-y-4">
                  <div class="flex justify-between items-center p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                    <span class="text-gray-700 dark:text-gray-300">Reference Number:</span>
                    <span class="font-medium text-gray-900 dark:text-gray-100">{{ payment.reference_number || 'N/A' }}</span>
                  </div>
                  
                  <div class="flex justify-between items-center p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                    <span class="text-gray-700 dark:text-gray-300">Method:</span>
                    <span class="capitalize font-medium text-gray-900 dark:text-gray-100">{{ payment.method }}</span>
                  </div>
                  
                  <div class="flex justify-between items-center p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                    <span class="text-gray-700 dark:text-gray-300">Source:</span>
                    <span class="capitalize font-medium text-gray-900 dark:text-gray-100">{{ formatSource(payment.source) }}</span>
                  </div>
                </div>

                <!-- Right Column -->
                <div class="space-y-4">
                  <div class="flex justify-between items-center p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                    <span class="text-gray-700 dark:text-gray-300">Date:</span>
                    <span class="font-medium text-gray-900 dark:text-gray-100">{{ formatDateTime(payment.paid_at) }}</span>
                  </div>
                  
                  <div class="flex justify-between items-center p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                    <span class="text-gray-700 dark:text-gray-300">Type:</span>
                    <span class="capitalize font-medium text-gray-900 dark:text-gray-100">{{ payment.type }}</span>
                  </div>
                  
                  <div v-if="payment.verified_at" class="flex justify-between items-center p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                    <span class="text-gray-700 dark:text-gray-300">Verified At:</span>
                    <span class="font-medium text-gray-900 dark:text-gray-100">{{ formatDateTime(payment.verified_at) }}</span>
                  </div>
                  
                  <div v-if="payment.collected_by" class="flex justify-between items-center p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                    <span class="text-gray-700 dark:text-gray-300">Collected By:</span>
                    <span class="font-medium text-gray-900 dark:text-gray-100">{{ payment.collected_by?.name || 'N/A' }}</span>
                  </div>
                </div>
              </div>

              <!-- Amount -->
              <div class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
                <div class="flex justify-between items-center p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800">
                  <span class="text-lg font-semibold text-blue-900 dark:text-blue-100">Amount Paid:</span>
                  <span class="text-2xl font-bold text-blue-600 dark:text-blue-400">₱{{ parseFloat(payment.amount).toFixed(2) }}</span>
                </div>
              </div>
            </div>
          </div>

          <!-- RECEIPT IMAGE CARD -->
          <div v-if="payment.receipt_image" class="bg-white dark:bg-gray-800 shadow-sm rounded-lg border border-gray-200 dark:border-gray-700">
            <div class="p-4 bg-gray-50 dark:bg-gray-700 border-b border-gray-200 dark:border-gray-700">
              <h3 class="font-medium text-gray-900 dark:text-gray-100 flex items-center gap-2">
                <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
                Payment Proof
              </h3>
            </div>
            <div class="p-4 md:p-6">
              <div class="flex justify-center">
                <img 
                  :src="payment.receipt_image_url" 
                  alt="Payment receipt"
                  class="max-w-full h-auto rounded-lg border-2 border-gray-200 dark:border-gray-600 max-h-64 object-contain cursor-pointer hover:shadow-md transition-shadow"
                  @error="handleImageError"
                  @click="openImageModal(payment.receipt_image_url)"
                />
              </div>
            </div>
          </div>

          <!-- NOTES CARD -->
          <div v-if="payment.notes" class="bg-white dark:bg-gray-800 shadow-sm rounded-lg border border-gray-200 dark:border-gray-700">
            <div class="p-4 bg-gray-50 dark:bg-gray-700 border-b border-gray-200 dark:border-gray-700">
              <h3 class="font-medium text-gray-900 dark:text-gray-100 flex items-center gap-2">
                <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                </svg>
                Notes
              </h3>
            </div>
            <div class="p-4 md:p-6">
              <p class="text-sm text-gray-700 dark:text-gray-300 whitespace-pre-wrap">{{ payment.notes }}</p>
            </div>
          </div>
        </div>

        <!-- RIGHT COLUMN: Delivery & Actions -->
        <div class="lg:col-span-1 space-y-6">
          <!-- DELIVERY SUMMARY CARD -->
          <div v-if="payment.delivery_request" class="bg-white dark:bg-gray-800 shadow-sm rounded-lg border border-gray-200 dark:border-gray-700">
            <div class="p-4 bg-gray-50 dark:bg-gray-700 border-b border-gray-200 dark:border-gray-700">
              <h3 class="font-medium text-gray-900 dark:text-gray-100 flex items-center gap-2">
                <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                </svg>
                Delivery Summary
              </h3>
            </div>
            <div class="p-4">
              <div class="space-y-4">
                <div class="flex justify-between items-center p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                  <span class="text-gray-700 dark:text-gray-300">Reference #:</span>
                  <span class="font-medium text-gray-900 dark:text-gray-100">{{ payment.delivery_request.reference_number }}</span>
                </div>
                
                <div class="flex justify-between items-center p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                  <span class="text-gray-700 dark:text-gray-300">Route:</span>
                  <span class="font-medium text-gray-900 dark:text-gray-100 text-right">
                    {{ payment.delivery_request.pick_up_region?.name || 'N/A' }} → 
                    {{ payment.delivery_request.drop_off_region?.name || 'N/A' }}
                  </span>
                </div>
                
                <div class="flex justify-between items-center p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                  <span class="text-gray-700 dark:text-gray-300">Packages:</span>
                  <span class="font-medium text-gray-900 dark:text-gray-100">{{ payment.delivery_request.packages?.length || 0 }} items</span>
                </div>

                <div class="pt-4 border-t border-gray-200 dark:border-gray-700">
                  <div class="flex justify-between items-center p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800">
                    <span class="text-lg font-semibold text-blue-900 dark:text-blue-100">Total:</span>
                    <span class="text-xl font-bold text-blue-600 dark:text-blue-400">₱{{ parseFloat(payment.delivery_request.total_price).toFixed(2) }}</span>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- CUSTOMER INFORMATION CARD -->
          <div v-if="payment.delivery_request" class="bg-white dark:bg-gray-800 shadow-sm rounded-lg border border-gray-200 dark:border-gray-700">
            <div class="p-4 bg-gray-50 dark:bg-gray-700 border-b border-gray-200 dark:border-gray-700">
              <h3 class="font-medium text-gray-900 dark:text-gray-100 flex items-center gap-2">
                <svg class="w-5 h-5 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                </svg>
                Customer Information
              </h3>
            </div>
            <div class="p-4">
              <div class="space-y-4">
                <!-- Sender -->
                <div class="bg-gray-50 dark:bg-gray-700/50 p-3 rounded-lg">
                  <h4 class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-2 flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                    Sender
                  </h4>
                  <div class="space-y-1 text-sm">
                    <p class="font-medium text-gray-900 dark:text-gray-100">{{ payment.delivery_request.sender?.name || 'N/A' }}</p>
                    <p class="text-gray-600 dark:text-gray-400">{{ payment.delivery_request.sender?.mobile || 'No phone' }}</p>
                    <p class="text-gray-600 dark:text-gray-400">{{ payment.delivery_request.sender?.email || 'No email' }}</p>
                  </div>
                </div>
                
                <!-- Receiver -->
                <div class="bg-gray-50 dark:bg-gray-700/50 p-3 rounded-lg">
                  <h4 class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-2 flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                    Receiver
                  </h4>
                  <div class="space-y-1 text-sm">
                    <p class="font-medium text-gray-900 dark:text-gray-100">{{ payment.delivery_request.receiver?.name || 'N/A' }}</p>
                    <p class="text-gray-600 dark:text-gray-400">{{ payment.delivery_request.receiver?.mobile || 'No phone' }}</p>
                    <p class="text-gray-600 dark:text-gray-400">{{ payment.delivery_request.receiver?.email || 'No email' }}</p>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- VERIFICATION ACTIONS CARD -->
          <div v-if="!payment.verified_by && !payment.rejected_by" class="bg-white dark:bg-gray-800 shadow-sm rounded-lg border border-gray-200 dark:border-gray-700">
            <div class="p-4 bg-gray-50 dark:bg-gray-700 border-b border-gray-200 dark:border-gray-700">
              <h3 class="font-medium text-gray-900 dark:text-gray-100 flex items-center gap-2">
                <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                Verification
              </h3>
            </div>
            <div class="p-4">
              <Link
                :href="route('staff.payments.verify', payment.id)" 
                class="block w-full"
              >
                <PrimaryButton class="w-full justify-center">
                  Verify Payment
                </PrimaryButton>
              </Link>
            </div>
          </div>

          <!-- NO DELIVERY INFO CARD -->
          <div v-else-if="!payment.delivery_request" class="bg-white dark:bg-gray-800 shadow-sm rounded-lg border border-gray-200 dark:border-gray-700">
            <div class="p-4 bg-gray-50 dark:bg-gray-700 border-b border-gray-200 dark:border-gray-700">
              <h3 class="font-medium text-gray-900 dark:text-gray-100 flex items-center gap-2">
                <svg class="w-5 h-5 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                </svg>
                Delivery Information
              </h3>
            </div>
            <div class="p-4">
              <div class="text-center py-4">
                <svg class="mx-auto h-8 w-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25zM6.75 12h.008v.008H6.75V12zm0 3h.008v.008H6.75V15zm0 3h.008v.008H6.75V18z"/>
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-gray-100">No Delivery Information</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">No delivery request information available for this payment.</p>
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
import { Link, router } from '@inertiajs/vue3';
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
  if (props.payment.verified_by) return 'bg-green-50 border border-green-200 text-green-800 dark:bg-green-900/20 dark:border-green-800 dark:text-green-300';
  if (props.payment.rejected_by) return 'bg-red-50 border border-red-200 text-red-800 dark:bg-red-900/20 dark:border-red-800 dark:text-red-300';
  return 'bg-yellow-50 border border-yellow-200 text-yellow-800 dark:bg-yellow-900/20 dark:border-yellow-800 dark:text-yellow-300';
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

const openImageModal = (imageUrl) => {
  window.open(imageUrl, '_blank');
};
</script>