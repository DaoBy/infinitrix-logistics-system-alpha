<template>
  <GuestLayout>
    <template #header>
      <div class="flex justify-between items-center">
        <div>
          <h2 class="text-xl font-semibold text-gray-800">
            Delivery Request #{{ delivery.reference_number }}
          </h2>
          <p class="text-sm text-gray-600 mt-1">
            Status: <span :class="statusBadgeClass">{{ delivery.status }}</span>
            <span v-if="delivery.payment_status" :class="paymentStatusBadgeClass" class="ml-2">
              Payment: {{ delivery.payment_status }}
            </span>
          </p>
        </div>
        <Link 
          :href="route('customer.delivery-requests.index')"
          class="text-sm text-blue-600 hover:text-blue-800"
        >
          <SecondaryButton>
            ← Back to My Deliveries
          </SecondaryButton>
        </Link>
      </div>
    </template>

    <div class="max-w-6xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
      <!-- Status Alerts -->
      <div v-if="delivery.status === 'rejected'" class="mb-6 bg-red-50 border border-red-200 rounded-lg p-4">
        <div class="flex items-center">
          <XCircleIcon class="h-5 w-5 text-red-400 mr-2" />
          <span class="text-red-800 font-medium">Delivery Request Rejected</span>
        </div>
        <p class="text-red-700 mt-1" v-if="delivery.rejection_reason">
          Reason: {{ delivery.rejection_reason }}
        </p>
      </div>

      <div v-else-if="delivery.status === 'pending'" class="mb-6 bg-yellow-50 border border-yellow-200 rounded-lg p-4">
        <div class="flex items-center">
          <ClockIcon class="h-5 w-5 text-yellow-400 mr-2" />
          <span class="text-yellow-800 font-medium">Pending Approval</span>
        </div>
        <p class="text-yellow-700 mt-1">
          Your delivery request is awaiting approval from our team.
        </p>
      </div>

      <!-- Payment Status Section -->
      <div v-if="showPaymentStatus" class="mb-6 rounded-lg p-6" :class="paymentStatusAlertClass">
        <h3 class="text-lg font-medium mb-2" :class="paymentStatusTitleClass">
          {{ paymentStatusTitle }}
        </h3>
        
        <div :class="paymentStatusMessageClass" class="space-y-3">
          <p>{{ paymentStatusMessage }}</p>
          
          <!-- Show rejection reason if payment was rejected -->
          <div v-if="delivery.payment_status === 'rejected' && delivery.payment?.rejection_reason" 
               class="bg-white bg-opacity-30 p-3 rounded mt-2">
            <div class="flex justify-between items-center">
              <div class="flex items-center space-x-2">
                <h4 class="font-medium">📝 Rejection Reason:</h4>
                <p class="text-sm">{{ delivery.payment.rejection_reason }}</p>
              </div>
              <!-- Resubmit Button for Rejected Payments - MOVED HERE -->
              <div v-if="delivery.payment_status === 'rejected' && delivery.payment">
                <Link
                  :href="route('customer.payments.resubmit', {
                    delivery: delivery.id,
                    payment: delivery.payment.id
                  })"
                  as="button"
                >
                  <PrimaryButton>
                    Resubmit Payment
                  </PrimaryButton>
                </Link>
              </div>
            </div>
          </div>

          <!-- Postpaid Completion Instructions - ONLY show for unpaid postpaid -->
          <div v-if="delivery.payment_type === 'postpaid' && 
                     ['completed', 'delivered'].includes(delivery.status) && 
                     delivery.payment_status !== 'paid'" 
               class="bg-white bg-opacity-30 p-4 rounded-lg mt-4 border border-blue-200">
            <h4 class="font-medium text-blue-800 mb-2">📋 Payment Instructions</h4>
            
            <div class="space-y-3 text-sm text-blue-700">
              <!-- Due Date Information -->
              <div v-if="delivery.payment_due_date" class="flex items-start space-x-2">
                <span class="font-medium">📅 Due Date:</span>
                <span>Please settle your payment by <strong>{{ formatDate(delivery.payment_due_date) }}</strong></span>
              </div>
              
              <!-- Payment Options -->
              <div class="flex items-start space-x-2">
                <span class="font-medium">💳 Payment Methods:</span>
                <div>
                  <p><strong>Option 1 - Online:</strong> Pay now using GCash or Bank Transfer</p>
                  <p><strong>Option 2 - Collector:</strong> Wait for our collector to contact you for cash payment</p>
                  <p><strong>Option 3 - Branch:</strong> Visit any branch to pay in cash</p>
                </div>
              </div>
              
              <!-- Late Payment Warning -->
              <div v-if="delivery.payment_due_date && isPaymentOverdue" class="bg-yellow-50 border border-yellow-200 p-3 rounded">
                <p class="text-yellow-800 font-medium">⚠️ Payment Overdue</p>
                <p class="text-yellow-700 text-sm mt-1">Please settle your balance immediately to avoid service interruptions.</p>
              </div>
            </div>
          </div>
          
          <!-- Simple payment method info for GCash/Bank - NOT for pending verification -->
          <div v-if="shouldShowSimplePaymentInfo && delivery.payment_status !== 'pending_verification'" class="bg-white bg-opacity-30 p-3 rounded mt-2">
            <div class="flex justify-between items-center">
              <div class="flex-1">
                <h4 class="font-medium mb-1">💳 Online Payment Available</h4>
                <p class="text-sm" v-if="delivery.payment_type === 'postpaid' && !['completed', 'delivered'].includes(delivery.status)">
                  You can pay online now using GCash or bank transfer. Paying early helps speed up the delivery process.
                </p>
                <p class="text-sm" v-else>
                  You can pay online now using GCash or bank transfer for faster processing.
                </p>
              </div>
              <div class="ml-4 flex-shrink-0">
                <!-- Pay Now Button - Show for GCash/Bank methods OR postpaid -->
                <Link
                  v-if="canMakePayment && delivery.payment_status !== 'rejected' && delivery.payment_status !== 'pending_verification' && (delivery.payment_method === 'gcash' || delivery.payment_method === 'bank' || delivery.payment_type === 'postpaid')"
                  :href="route('customer.payments.create', delivery.id)"
                  as="button"
                >
                  <PrimaryButton>
                    {{ payNowButtonText }}
                  </PrimaryButton>
                </Link>

                <!-- Optional payment button for cash method - Show ONLY for prepaid cash -->
                <Link
                  v-if="delivery.payment_method === 'cash' && canMakePayment && delivery.payment_status !== 'rejected' && delivery.payment_status !== 'pending_verification' && delivery.payment_type === 'prepaid'"
                  :href="route('customer.payments.create', delivery.id)"
                  as="button"
                >
                  <SecondaryButton>
                    Pay Online Instead
                  </SecondaryButton>
                </Link>
              </div>
            </div>
          </div>
          
          <!-- Cash payment information - ONLY show for unpaid prepaid cash -->
          <div v-if="delivery.payment_method === 'cash' && 
                     delivery.payment_type === 'prepaid' && 
                     delivery.payment_status !== 'paid'" 
               class="bg-white bg-opacity-30 p-3 rounded mt-2">
            <h4 class="font-medium mb-1">💰 Cash Payment Instructions</h4>
            <p class="text-sm">Please pay with cash when you drop off your package at our <strong>{{ delivery.pick_up_region?.name }}</strong> branch. Our staff will assist you with the payment process.</p>
            <p class="text-sm mt-1">You also have the option to pay online now if you prefer.</p>
          </div>

          <!-- Flexible payment info for cash method -->
          <div v-if="delivery.payment_method === 'cash' && canMakePayment" 
               class="bg-blue-50 border border-blue-200 rounded-lg p-4 mt-4">
            <div class="flex items-center">
              <InformationCircleIcon class="h-5 w-5 text-blue-400 mr-2" />
              <span class="text-blue-800 text-sm">
                <strong>Flexible Payment:</strong> You selected Cash payment, but you can also pay online now using GCash or Bank Transfer for faster processing.
              </span>
            </div>
          </div>
          
          <!-- Next Steps - ONLY show when payment verified but delivery not complete AND not prepaid cash -->
          <div v-if="delivery.payment_status === 'paid' && 
                     delivery.payment_verified && 
                     !['completed', 'delivered'].includes(delivery.status) &&
                     !(delivery.payment_type === 'prepaid' && delivery.payment_method === 'cash')" 
               class="bg-white bg-opacity-30 p-3 rounded mt-2">
            <h4 class="font-medium mb-1">✅ Next Steps</h4>
            <p class="text-sm">Your payment has been verified. You can now proceed to prepare your package for shipment.</p>
          </div>
          
          <!-- Rejected payment information -->
          <div v-if="delivery.payment_status === 'rejected'" class="bg-white bg-opacity-30 p-3 rounded mt-2">
            <h4 class="font-medium mb-1">ℹ️ Need Assistance?</h4>
            <p class="text-sm">
              Your payment was not accepted. If you need help, please reach out to our support team or try resubmitting with the correct information.
            </p>
          </div>
        </div>

        <!-- Support Information for Completed & Paid Deliveries -->
        <div v-if="delivery.payment_status === 'paid' && 
                   delivery.payment_verified && 
                   ['completed', 'delivered'].includes(delivery.status)" 
             class="bg-blue-50 border border-blue-200 rounded-lg p-4 mt-4">
          <div class="flex items-center">
            <InformationCircleIcon class="h-5 w-5 text-blue-400 mr-2" />
            <span class="text-blue-800 text-sm">
              <strong>Need assistance?</strong> If you have any questions about your delivery, please visit our 
              <Link :href="route('contact.us')" class="text-blue-600 hover:text-blue-800 underline">Contact Us</Link> 
              page for support.
            </span>
          </div>
        </div>

        <!-- Package Handover Instructions - ONLY for prepaid that need to drop off -->
        <div v-if="delivery.payment_status === 'paid' && 
                   delivery.payment_verified && 
                   delivery.payment_type === 'prepaid' &&
                   !['completed', 'delivered'].includes(delivery.status)" 
             class="mt-4 bg-white bg-opacity-20 p-3 rounded">
          <p class="text-sm font-medium">
            📦 Package Handover Instructions:
          </p>
          <ul class="text-sm mt-2 list-disc list-inside">
            <li>Please bring your package to the selected pickup branch: <strong>{{ delivery.pick_up_region?.name }}</strong></li>
            <li>Ensure your package is properly packaged and labeled</li>
            <li>Bring a valid ID for verification</li>
            <li>Reference number: <strong>{{ delivery.reference_number }}</strong></li>
          </ul>
        </div>
      </div>

      <!-- Main Content Grid -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Left Column - Delivery Information -->
        <div class="lg:col-span-2 space-y-6">
          <!-- Sender & Receiver Information -->
          <div class="bg-white shadow rounded-lg overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
              <h3 class="text-lg font-medium text-gray-900">Contact Information</h3>
            </div>
            <div class="px-6 py-4">
              <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                <!-- Sender -->
                <div class="border border-gray-200 rounded-lg p-4 bg-gray-50">
                  <h4 class="font-medium text-gray-700 mb-3 flex items-center">
                    <svg class="h-5 w-5 text-gray-400 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                            d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                    Sender
                  </h4>
                  <div class="space-y-2 text-sm">
                    <p><span class="text-gray-500">Name:</span> <span class="text-gray-900 font-medium">{{ delivery.sender?.name || 'N/A' }}</span></p>
                    <p><span class="text-gray-500">Email:</span> <span class="text-gray-900">{{ delivery.sender?.email || 'N/A' }}</span></p>
                    <p><span class="text-gray-500">Mobile:</span> <span class="text-gray-900">{{ delivery.sender?.mobile || 'N/A' }}</span></p>
                    <p><span class="text-gray-500">Address:</span> <span class="text-gray-900">{{ delivery.sender?.address || 'N/A' }}</span></p>
                  </div>
                </div>

                <!-- Receiver -->
                <div class="border border-gray-200 rounded-lg p-4 bg-gray-50">
                  <h4 class="font-medium text-gray-700 mb-3 flex items-center">
                    <svg class="h-5 w-5 text-gray-400 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                            d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                    Receiver
                  </h4>
                  <div class="space-y-2 text-sm">
                    <p><span class="text-gray-500">Name:</span> <span class="text-gray-900 font-medium">{{ delivery.receiver?.name || 'N/A' }}</span></p>
                    <p><span class="text-gray-500">Email:</span> <span class="text-gray-900">{{ delivery.receiver?.email || 'N/A' }}</span></p>
                    <p><span class="text-gray-500">Mobile:</span> <span class="text-gray-900">{{ delivery.receiver?.mobile || 'N/A' }}</span></p>
                    <p><span class="text-gray-500">Address:</span> <span class="text-gray-900">{{ delivery.receiver?.address || 'N/A' }}</span></p>
                  </div>
                </div>

              </div>
            </div>
          </div>

          <!-- Package Details -->
          <div class="bg-white shadow rounded-lg overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
              <h3 class="text-lg font-medium text-gray-900">Package Details</h3>
            </div>
            <div class="px-6 py-4">
              <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                  <thead class="bg-gray-50">
                    <tr>
                      <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Item</th>
                      <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Dimensions</th>
                      <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Weight</th>
                      <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Value</th>
                    </tr>
                  </thead>
                  <tbody class="bg-white divide-y divide-gray-200">
                    <tr v-for="pkg in delivery.packages" :key="pkg.id">
                      <td class="px-4 py-4 whitespace-nowrap">
                        <div class="font-medium text-gray-900">{{ pkg.item_name }}</div>
                        <div class="text-sm text-gray-500">{{ pkg.category }}</div>
                      </td>
                      <td class="px-4 py-4 whitespace-nowrap text-gray-900">
                        {{ pkg.height }}cm × {{ pkg.width }}cm × {{ pkg.length }}cm
                      </td>
                      <td class="px-4 py-4 whitespace-nowrap text-gray-900">
                        {{ pkg.weight }} kg
                      </td>
                      <td class="px-4 py-4 whitespace-nowrap text-gray-900">
                        ₱{{ pkg.value }}
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>

        <!-- Right Column - Payment & Status Information -->
        <div class="space-y-6">
          <!-- Payment Summary -->
          <div class="bg-white shadow rounded-lg overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
              <h3 class="text-lg font-medium text-gray-900">Payment Summary</h3>
            </div>
            <div class="px-6 py-4">
              <div class="space-y-3">
                <div class="flex justify-between">
                  <span class="text-gray-600">Base Fee:</span>
                  <span class="text-gray-900">₱{{ delivery.base_fee }}</span>
                </div>
                <div class="flex justify-between">
                  <span class="text-gray-600">Volume Fee:</span>
                  <span class="text-gray-900">₱{{ delivery.volume_fee }}</span>
                </div>
                <div class="flex justify-between">
                  <span class="text-gray-600">Weight Fee:</span>
                  <span class="text-gray-900">₱{{ delivery.weight_fee }}</span>
                </div>
                <div class="flex justify-between">
                  <span class="text-gray-600">Package Fee ({{ delivery.packages.length }}):</span>
                  <span class="text-gray-900">₱{{ delivery.package_fee }}</span>
                </div>
                <div class="border-t border-gray-200 pt-3 mt-3">
                  <div class="flex justify-between text-lg font-semibold">
                    <span class="text-gray-900">Total:</span>
                    <span class="text-blue-600">₱{{ delivery.total_price }}</span>
                  </div>
                </div>
              </div>

              <div class="mt-4 pt-4 border-t border-gray-200">
                <div class="flex justify-between">
                  <span class="text-gray-600">Payment Method:</span>
                  <span class="text-gray-900 capitalize">{{ delivery.payment_method }}</span>
                </div>
                <div class="flex justify-between mt-2">
                  <span class="text-gray-600">Payment Type:</span>
                  <span class="text-gray-900 capitalize">{{ delivery.payment_type }}</span>
                </div>
                <div v-if="delivery.payment_due_date" class="flex justify-between mt-2">
                  <span class="text-gray-600">Due Date:</span>
                  <span class="text-gray-900">{{ formatDate(delivery.payment_due_date) }}</span>
                </div>
              </div>
            </div>
          </div>

          <!-- Status Timeline -->
          <div class="bg-white shadow rounded-lg overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
              <h3 class="text-lg font-medium text-gray-900">Delivery Route</h3>
            </div>
            <div class="px-6 py-4">
              <div class="flex items-center justify-between">
                <div class="text-center">
                  <div class="text-sm font-medium text-gray-500">Pickup Location</div>
                  <div class="mt-1 text-gray-900">{{ delivery.pick_up_region.name }}</div>
                </div>
                <div class="flex-1 mx-4 border-t-2 border-dashed border-gray-300"></div>
                <div class="text-center">
                  <div class="text-sm font-medium text-gray-500">Delivery Location</div>
                  <div class="mt-1 text-gray-900">{{ delivery.drop_off_region.name }}</div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </GuestLayout>
</template>

<script setup>
import { computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import GuestLayout from '@/Layouts/GuestLayout.vue'
import PrimaryButton from '@/Components/PrimaryButton.vue'
import SecondaryButton from '@/Components/SecondaryButton.vue'
import { XCircleIcon, ClockIcon, InformationCircleIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
  delivery: {
    type: Object,
    required: true
  },
  statusHistory: {
    type: Array,
    default: () => []
  }
})

// Show payment status when delivery is approved or (postpaid and completed/delivered)
const showPaymentStatus = computed(() => {
  return props.delivery.status === 'approved' || 
    (props.delivery.payment_type === 'postpaid' && ['completed', 'delivered'].includes(props.delivery.status))
})

// FIXED: Simplified payment eligibility check
const canMakePayment = computed(() => {
  const status = props.delivery.payment_status;
  
  // If already paid and verified, cannot pay again
  if (status === 'paid' && props.delivery.payment_verified) {
    return false;
  }
  
  // Prepaid: only when approved
  if (props.delivery.payment_type === 'prepaid') {
    return props.delivery.status === 'approved';
  }
  
  // POSTPAID FIX: Can pay when approved, completed, or delivered
  if (props.delivery.payment_type === 'postpaid') {
    return ['approved', 'completed', 'delivered'].includes(props.delivery.status);
  }
  
  return false;
})

// FIXED: Show payment action based on canMakePayment
const showPaymentAction = computed(() => {
  return canMakePayment.value;
})

// Check if payment is overdue
const isPaymentOverdue = computed(() => {
  if (!props.delivery.payment_due_date) return false;
  const dueDate = new Date(props.delivery.payment_due_date);
  const today = new Date();
  return dueDate < today;
})

const statusBadgeClass = computed(() => {
  const status = props.delivery.status
  if (status === 'approved') return 'text-green-800 bg-green-100 px-2 py-1 rounded-full text-xs'
  if (status === 'rejected') return 'text-red-800 bg-red-100 px-2 py-1 rounded-full text-xs'
  return 'text-yellow-800 bg-yellow-100 px-2 py-1 rounded-full text-xs'
})

const paymentStatusBadgeClass = computed(() => {
  const status = props.delivery.payment_status
  if (status === 'paid') return 'text-green-800 bg-green-100 px-2 py-1 rounded-full text-xs'
  if (status === 'rejected') return 'text-red-800 bg-red-100 px-2 py-1 rounded-full text-xs'
  if (status === 'pending_verification') return 'text-blue-800 bg-blue-100 px-2 py-1 rounded-full text-xs'
  return 'text-yellow-800 bg-yellow-100 px-2 py-1 rounded-full text-xs'
})

const paymentStatusAlertClass = computed(() => {
  const status = props.delivery.payment_status
  if (status === 'paid') return 'bg-green-50 border border-green-200'
  if (status === 'rejected') return 'bg-red-50 border border-red-200'
  if (status === 'pending_verification') return 'bg-blue-50 border border-blue-200'
  return 'bg-yellow-50 border border-yellow-200'
})

const paymentStatusTitleClass = computed(() => {
  const status = props.delivery.payment_status
  if (status === 'paid') return 'text-green-800'
  if (status === 'rejected') return 'text-red-800'
  if (status === 'pending_verification') return 'text-blue-800'
  return 'text-yellow-800'
})

const paymentStatusMessageClass = computed(() => {
  const status = props.delivery.payment_status
  if (status === 'paid') return 'text-green-700'
  if (status === 'rejected') return 'text-red-700'
  if (status === 'pending_verification') return 'text-blue-700'
  return 'text-yellow-700'
})

// FIXED: Better payment status titles
const paymentStatusTitle = computed(() => {
  const status = props.delivery.payment_status
  if (status === 'paid') return 'Payment Completed'
  if (status === 'rejected') return 'Payment Issue'
  if (status === 'pending_verification') return 'Payment Verification in Progress'
  
  // Different message for postpaid vs prepaid
  if (props.delivery.payment_type === 'postpaid') {
    // Postpaid: different messages based on delivery status
    if (['completed', 'delivered'].includes(props.delivery.status)) {
      return 'Ready for Payment'
    } else {
      return 'Payment Available'
    }
  }
  return 'Payment Required'
})

// FIXED: Better payment status messages
const paymentStatusMessage = computed(() => {
  const status = props.delivery.payment_status
  if (status === 'paid') {
    if (props.delivery.payment_verified) {
      // Different message for completed/delivered vs in-progress
      if (['completed', 'delivered'].includes(props.delivery.status)) {
        return 'Your delivery has been successfully completed. Thank you for choosing our service!'
      } else {
        return 'Your payment has been verified and your delivery is being processed.'
      }
    }
    return 'Your payment has been received and is awaiting verification.'
  }
  if (status === 'rejected') {
    return 'There was an issue with your payment. Please review and resubmit.'
  }
  if (status === 'pending_verification') {
    return 'Your payment is being verified by our team. This usually takes 1-2 business days.'
  }
  
  // Different message for postpaid vs prepaid
  if (props.delivery.payment_type === 'postpaid') {
    // Postpaid: different messages based on delivery status
    if (['completed', 'delivered'].includes(props.delivery.status)) {
      return 'Your delivery is complete. You can now settle your payment.'
    } else {
      return 'Your delivery is in progress. You can pay now or wait until delivery is complete.'
    }
  }
  return 'Your delivery request has been approved. Please complete your payment to proceed.'
})

const isPrepaid = computed(() => {
  return props.delivery.payment_type === 'prepaid'
})

const shouldShowSimplePaymentInfo = computed(() => {
  // Show for both prepaid and postpaid when payment is needed, but NOT for pending verification
  return canMakePayment.value && props.delivery.payment_status !== 'pending_verification';
})

const payNowButtonText = computed(() => {
  const status = props.delivery.payment_status
  if (status === 'rejected') return 'Resubmit Payment'
  if (status === 'paid' && !props.delivery.payment_verified) return 'Update Payment'
  return 'Pay Now'
})

const formatDate = (dateString) => {
  return new Date(dateString).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  })
}

const formatDateTime = (dateString) => {
  return new Date(dateString).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}
</script>