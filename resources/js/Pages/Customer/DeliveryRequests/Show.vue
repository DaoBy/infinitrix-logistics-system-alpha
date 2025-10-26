<template>
  <GuestLayout>
    <div class="min-h-screen bg-gray-50 py-8">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header with Back Button -->
        <div class="flex justify-between items-center mb-8">
          <div>
            <h1 class="text-3xl font-bold text-gray-900">Delivery Request #{{ delivery.reference_number }}</h1>
            <p class="text-gray-600 mt-2">Track your delivery request status and package information</p>
          </div>
          <Link 
            :href="route('customer.delivery-requests.index')"
            class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 disabled:opacity-25 transition ease-in-out duration-150"
          >
            ← Back to My Deliveries
          </Link>
        </div>

        <!-- Status & Next Steps Card -->
        <div class="mb-6 bg-blue-50 border border-blue-200 rounded-lg p-6">
          <h3 class="font-semibold text-blue-800 flex items-center mb-4 text-lg">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            Status & Next Steps
          </h3>
          
          <div class="space-y-4 text-sm text-blue-700">
            <!-- Rejected Status -->
            <div v-if="delivery.status === 'rejected'" class="flex items-start">
              <div class="w-6 h-6 bg-red-500 text-white rounded-full flex items-center justify-center text-xs font-bold mr-3 flex-shrink-0">!</div>
              <div>
                <strong class="text-red-700">Delivery Request Rejected</strong>
                <p class="mt-1" v-if="delivery.rejection_reason">
                  <strong>Reason:</strong> {{ delivery.rejection_reason }}
                </p>
                <p class="mt-1">Please contact support if you need assistance.</p>
              </div>
            </div>

            <!-- Pending Approval -->
            <div v-else-if="delivery.status === 'pending'" class="flex items-start">
              <div class="w-6 h-6 bg-yellow-500 text-white rounded-full flex items-center justify-center text-xs font-bold mr-3 flex-shrink-0">1</div>
              <div>
                <strong>Pending Approval</strong>
                <p class="mt-1">Your delivery request is awaiting approval from our team. This usually takes 1-2 business days.</p>
              </div>
            </div>

            <!-- Approved - Prepaid Payment Required -->
            <div v-else-if="delivery.status === 'approved' && 
                       delivery.payment_type === 'prepaid' && 
                       delivery.payment_status !== 'paid' && 
                       !delivery.delivery_order" class="flex items-start">
              <div class="w-6 h-6 bg-blue-500 text-white rounded-full flex items-center justify-center text-xs font-bold mr-3 flex-shrink-0">1</div>
              <div>
                <strong>Payment Required</strong>
                <p class="mt-1">Complete your payment to proceed with package processing.</p>
              </div>
            </div>

            <!-- Payment Completed - Prepaid Processing -->
            <div v-else-if="delivery.payment_status === 'paid' && 
                       delivery.payment_type === 'prepaid' && 
                       !delivery.delivery_order && 
                       delivery.status !== 'completed'" class="flex items-start">
              <div class="w-6 h-6 bg-blue-500 text-white rounded-full flex items-center justify-center text-xs font-bold mr-3 flex-shrink-0">1</div>
              <div>
                <strong>Package Processing</strong>
                <p class="mt-1">Your packages will be processed and prepared for transit.</p>
              </div>
            </div>

            <!-- Delivery Order in Progress -->
            <div v-else-if="delivery.delivery_order && 
                       ['assigned', 'dispatched', 'in_transit'].includes(delivery.delivery_order.status)" class="flex items-start">
              <div class="w-6 h-6 bg-blue-500 text-white rounded-full flex items-center justify-center text-xs font-bold mr-3 flex-shrink-0">1</div>
              <div>
                <strong>Delivery in Progress</strong>
                <p class="mt-1">Your packages are on the way to the destination.</p>
              </div>
            </div>

            <!-- Delivery Needs Review -->
            <div v-else-if="delivery.delivery_order && 
                       delivery.delivery_order.status === 'needs_review'" class="flex items-start">
              <div class="w-6 h-6 bg-orange-500 text-white rounded-full flex items-center justify-center text-xs font-bold mr-3 flex-shrink-0">!</div>
              <div>
                <strong>Delivery Review Needed</strong>
                <p class="mt-1">Some packages require review. Our team will contact you shortly.</p>
              </div>
            </div>

            <!-- Postpaid Payment Due -->
            <div v-else-if="delivery.payment_type === 'postpaid' && 
                       delivery.status === 'completed' && 
                       delivery.payment_status !== 'paid'" class="flex items-start">
              <div class="w-6 h-6 bg-blue-500 text-white rounded-full flex items-center justify-center text-xs font-bold mr-3 flex-shrink-0">1</div>
              <div>
                <strong>Payment Due</strong>
                <p class="mt-1">
                  <span v-if="delivery.payment_due_date">Please settle your payment by {{ formatDate(delivery.payment_due_date) }}</span>
                  <span v-else>Your payment is now due.</span>
                </p>
              </div>
            </div>

            <!-- Prepaid Payment Due After Completion -->
            <div v-else-if="delivery.payment_type === 'prepaid' && 
                       delivery.status === 'completed' && 
                       delivery.payment_status !== 'paid'" class="flex items-start">
              <div class="w-6 h-6 bg-blue-500 text-white rounded-full flex items-center justify-center text-xs font-bold mr-3 flex-shrink-0">1</div>
              <div>
                <strong>Final Payment Due</strong>
                <p class="mt-1">
                  <span v-if="delivery.payment_due_date">Please settle your final payment by {{ formatDate(delivery.payment_due_date) }}</span>
                  <span v-else>Your final payment is now due.</span>
                </p>
              </div>
            </div>

            <!-- Completed -->
            <div v-else-if="delivery.status === 'completed' && 
                       delivery.payment_status === 'paid'" class="flex items-start">
              <div class="w-6 h-6 bg-green-500 text-white rounded-full flex items-center justify-center text-xs font-bold mr-3 flex-shrink-0">✓</div>
              <div>
                <strong>Delivery Completed</strong>
                <p class="mt-1">Your delivery has been successfully completed. Thank you for choosing our service!</p>
              </div>
            </div>

            <!-- Default/Unknown Status -->
            <div v-else class="flex items-start">
              <div class="w-6 h-6 bg-gray-500 text-white rounded-full flex items-center justify-center text-xs font-bold mr-3 flex-shrink-0">?</div>
              <div>
                <strong>Processing</strong>
                <p class="mt-1">Your delivery request is being processed. We'll update you soon.</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Main Content Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
          <!-- Left Column - Main Content (3/4 width) -->
          <div class="lg:col-span-3 space-y-6">
          <!-- Delivery Status Timeline -->
<div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
  <h2 class="text-xl font-semibold text-gray-900 mb-6">Delivery Progress</h2>
  
  <!-- Enhanced Status Timeline -->
  <div class="relative">
    <!-- Timeline Line -->
    <div class="absolute left-4 top-0 bottom-0 w-0.5 bg-gray-200"></div>
    
    <!-- Timeline Steps -->
    <div class="space-y-8">
      <!-- Step 1: Request Submitted -->
      <div class="relative flex items-start">
        <div class="flex-shrink-0">
          <div :class="[
            'w-8 h-8 rounded-full flex items-center justify-center text-sm font-medium',
            currentStep >= 1 ? 'bg-green-600 text-white' : 'bg-gray-300 text-gray-600'
          ]">
            ✓
          </div>
        </div>
        <div class="ml-6 flex-1">
          <div class="flex items-center justify-between">
            <h3 class="text-lg font-medium text-gray-900">Request Submitted</h3>
            <span class="text-sm text-gray-500">{{ formatDate(delivery.created_at) }}</span>
          </div>
          <p class="mt-1 text-gray-600">Your delivery request has been received and is being processed.</p>
        </div>
      </div>

      <!-- Step 2: Approval -->
      <div class="relative flex items-start">
        <div class="flex-shrink-0">
          <div :class="[
            'w-8 h-8 rounded-full flex items-center justify-center text-sm font-medium',
            currentStep >= 2 ? 'bg-green-600 text-white' : 'bg-gray-300 text-gray-600',
            delivery.status === 'rejected' ? 'bg-red-600 text-white' : ''
          ]">
            <span v-if="delivery.status === 'rejected'">!</span>
            <span v-else>✓</span>
          </div>
        </div>
        <div class="ml-6 flex-1">
          <div class="flex items-center justify-between">
            <h3 class="text-lg font-medium text-gray-900">
              {{ delivery.status === 'rejected' ? 'Request Rejected' : 'Approval' }}
            </h3>
            <span v-if="delivery.approved_at" class="text-sm text-gray-500">{{ formatDate(delivery.approved_at) }}</span>
            <span v-else-if="delivery.rejected_at" class="text-sm text-gray-500">{{ formatDate(delivery.rejected_at) }}</span>
          </div>
          <p class="mt-1 text-gray-600" v-if="delivery.status === 'rejected'">
            Request was rejected. Reason: {{ delivery.rejection_reason || 'Not specified' }}
          </p>
          <p class="mt-1 text-gray-600" v-else-if="delivery.status === 'approved'">
            Your request has been approved and is ready for processing.
          </p>
          <p class="mt-1 text-gray-600" v-else>
            Awaiting approval from our team.
          </p>
        </div>
      </div>

      <!-- Payment Processing (Prepaid) -->
      <div v-if="showPaymentProcessingStep" class="relative flex items-start">
        <div class="flex-shrink-0">
          <div :class="[
            'w-8 h-8 rounded-full flex items-center justify-center text-sm font-medium',
            currentStep >= 3 ? 'bg-green-600 text-white' : 'bg-gray-300 text-gray-600',
            delivery.payment_status === 'rejected' ? 'bg-red-600 text-white' : ''
          ]">
            <span v-if="delivery.payment_status === 'rejected'">!</span>
            <span v-else>✓</span>
          </div>
        </div>
        <div class="ml-6 flex-1">
          <div class="flex items-center justify-between">
            <h3 class="text-lg font-medium text-gray-900">Payment Processing</h3>
            <span v-if="delivery.payment_status === 'paid'" class="text-sm text-green-600">Paid</span>
            <span v-else-if="delivery.payment_status === 'rejected'" class="text-sm text-red-600">Rejected</span>
            <span v-else class="text-sm text-yellow-600">Pending</span>
          </div>
          <p class="mt-1 text-gray-600">
            <span v-if="delivery.payment_status === 'paid'">Payment completed and verified.</span>
            <span v-else-if="delivery.payment_status === 'rejected'">Payment was rejected. Please resubmit.</span>
            <span v-else>Payment required to proceed with delivery.</span>
          </p>
          <!-- Show payment method for prepaid -->
          <p v-if="delivery.payment_method" class="mt-1 text-sm text-gray-500">
            Method: {{ delivery.payment_method }}
          </p>
        </div>
      </div>

      <!-- Delivery Activity -->
      <div v-if="showDeliveryOrderStep" class="relative flex items-start">
        <div class="flex-shrink-0">
          <div :class="[
            'w-8 h-8 rounded-full flex items-center justify-center text-sm font-medium',
            currentStep >= 4 ? 'bg-green-600 text-white' : 'bg-gray-300 text-gray-600'
          ]">
            ✓
          </div>
        </div>
        <div class="ml-6 flex-1">
          <div class="flex items-center justify-between">
            <h3 class="text-lg font-medium text-gray-900">Delivery Activity</h3>
            <span :class="deliveryOrderStatusBadgeClass" class="text-xs font-medium px-2 py-1 rounded-full">
              {{ deliveryOrderStatusLabel }}
            </span>
          </div>
          <p class="mt-1 text-gray-600">
            {{ deliveryOrderStatusDescription }}
          </p>
          <!-- Show package summary if available -->
          <div v-if="packageStatusSummary" class="mt-2 text-sm text-gray-500">
            <p>Packages: {{ packageStatusSummary.delivered }}/{{ packageStatusSummary.total }} delivered</p>
            <p v-if="packageStatusSummary.hasIssues" class="text-orange-600">
              Note: Some packages require review
            </p>
          </div>
        </div>
      </div>

      <!-- Completed -->
      <div v-if="showCompletedStep" class="relative flex items-start">
        <div class="flex-shrink-0">
          <div :class="[
            'w-8 h-8 rounded-full flex items-center justify-center text-sm font-medium',
            currentStep >= 5 ? 'bg-green-600 text-white' : 'bg-gray-300 text-gray-600'
          ]">
            ✓
          </div>
        </div>
        <div class="ml-6 flex-1">
          <div class="flex items-center justify-between">
            <h3 class="text-lg font-medium text-gray-900">Completed</h3>
            <span class="text-sm text-green-600">Finished</span>
          </div>
          <p class="mt-1 text-gray-600">
            Your delivery request has been fully completed.
          </p>
          <!-- Show success message when completed -->
          <div v-if="packageStatusSummary && delivery.status === 'completed'" class="mt-2 text-sm text-green-600">
            <p>✅ All {{ packageStatusSummary.total }} packages successfully delivered</p>
          </div>
        </div>
      </div>

      <!-- Postpaid Payment (Conditional) -->
      <div v-if="showPostpaidPaymentStep" class="relative flex items-start">
        <div class="flex-shrink-0">
          <div :class="[
            'w-8 h-8 rounded-full flex items-center justify-center text-sm font-medium',
            delivery.payment_status === 'paid' ? 'bg-green-600 text-white' : 'bg-gray-300 text-gray-600'
          ]">
            <span v-if="delivery.payment_status === 'paid'">✓</span>
            <span v-else>$</span>
          </div>
        </div>
        <div class="ml-6 flex-1">
          <div class="flex items-center justify-between">
            <h3 class="text-lg font-medium text-gray-900">Payment Settlement</h3>
            <span v-if="delivery.payment_status === 'paid'" class="text-sm text-green-600">Paid</span>
            <span v-else class="text-sm text-yellow-600">Pending</span>
          </div>
          <p class="mt-1 text-gray-600">
            <span v-if="delivery.payment_status === 'paid'">Payment has been settled.</span>
            <span v-else>Payment is due after delivery completion.</span>
          </p>
        </div>
      </div>
    </div>
  </div>
</div>

            <!-- Package Information -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
              <div class="flex justify-between items-center mb-6">
                <h2 class="text-xl font-semibold text-gray-900">Package Information</h2>
                <span class="text-sm text-gray-500">{{ delivery.packages.length }} package(s)</span>
              </div>

              <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div v-for="pkg in delivery.packages" :key="pkg.id" class="border border-gray-200 rounded-lg p-4 hover:border-green-300 transition-colors duration-200">
                  <div class="flex justify-between items-start mb-3">
                    <h3 class="font-semibold text-gray-900">{{ pkg.item_name }}</h3>
                    <span class="text-xs bg-blue-100 text-blue-800 px-2 py-1 rounded-full">{{ pkg.category }}</span>
                  </div>
                  
                  <div class="space-y-2 text-sm text-gray-600">
                    <div class="flex justify-between">
                      <span>Dimensions:</span>
                      <span class="font-medium">{{ pkg.length }}cm × {{ pkg.width }}cm × {{ pkg.height }}cm</span>
                    </div>
                    <div class="flex justify-between">
                      <span>Weight:</span>
                      <span class="font-medium">{{ pkg.weight }} kg</span>
                    </div>
                    <div class="flex justify-between">
                      <span>Value:</span>
                      <span class="font-medium">₱{{ pkg.value }}</span>
                    </div>
                  </div>

                  <div class="mt-4 pt-3 border-t border-gray-100">
                    <Link 
                      :href="route('tracking.public', { itemCode: pkg.item_code })"
                      class="w-full inline-flex justify-center items-center px-3 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 focus:bg-green-700 active:bg-green-900 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition ease-in-out duration-150"
                    >
                      <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                      </svg>
                      Track Package
                    </Link>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Right Column - Sidebar (1/4 width) -->
          <div class="lg:col-span-1 space-y-6">
          <!-- Quick Actions Card -->
<div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 sticky top-6">
  <h3 class="text-lg font-semibold text-gray-900 mb-4">Quick Actions</h3>
  
  <div class="space-y-3">
    <!-- Pay Now Button -->
    <Link
      v-if="canMakePayment"
      :href="route('customer.payments.create', delivery.id)"
      class="w-full inline-flex justify-center items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 focus:bg-green-700 active:bg-green-900 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition ease-in-out duration-150"
    >
      {{ payNowButtonText }}
    </Link>

    <!-- Contact Support -->
    <a 
      :href="route('contact.us')"
      class="w-full inline-flex justify-center items-center px-4 py-2 border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition ease-in-out duration-150"
    >
      Contact Support
    </a>

    <!-- Download Waybill -->
    <button
      v-if="delivery.status === 'completed'"
      @click="downloadWaybill"
      class="w-full inline-flex justify-center items-center px-4 py-2 border border-green-300 text-green-700 rounded-md font-semibold text-xs uppercase tracking-widest hover:bg-green-50 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition ease-in-out duration-150"
    >
      <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
      </svg>
      Download Waybill
    </button>
  </div>

  <!-- Status Badges -->
  <div class="mt-6 pt-6 border-t border-gray-200">
    <div class="space-y-3">
      <div class="flex justify-between items-center">
        <span class="text-sm text-gray-600">Delivery Status:</span>
        <span :class="statusBadgeClass" class="text-xs font-medium">{{ deliveryStatusLabel }}</span>
      </div>
      <div class="flex justify-between items-center">
        <span class="text-sm text-gray-600">Payment Status:</span>
        <span :class="paymentStatusBadgeClass" class="text-xs font-medium">{{ paymentStatusLabel }}</span>
      </div>
    </div>
  </div>
</div>

            <!-- Payment Summary -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
              <h3 class="text-lg font-semibold text-gray-900 mb-4">Payment Summary</h3>
              
              <div class="space-y-3">
                <div class="flex justify-between text-sm">
                  <span class="text-gray-600">Base Fee:</span>
                  <span class="text-gray-900">₱{{ delivery.base_fee }}</span>
                </div>
                <div class="flex justify-between text-sm">
                  <span class="text-gray-600">Volume Fee:</span>
                  <span class="text-gray-900">₱{{ delivery.volume_fee }}</span>
                </div>
                <div class="flex justify-between text-sm">
                  <span class="text-gray-600">Weight Fee:</span>
                  <span class="text-gray-900">₱{{ delivery.weight_fee }}</span>
                </div>
                <div class="flex justify-between text-sm">
                  <span class="text-gray-600">Package Fee:</span>
                  <span class="text-gray-900">₱{{ delivery.package_fee }}</span>
                </div>
                <div class="border-t border-gray-200 pt-3 mt-3">
                  <div class="flex justify-between items-center">
                    <span class="text-lg font-semibold text-gray-900">Total:</span>
                    <span class="text-xl font-bold text-green-600">₱{{ delivery.total_price }}</span>
                  </div>
                </div>
              </div>

              <div class="mt-4 pt-4 border-t border-gray-200">
                <div class="space-y-2 text-sm">
                  <div class="flex justify-between">
                    <span class="text-gray-600">Payment Method:</span>
                    <span class="text-gray-900 capitalize">{{ delivery.payment_method }}</span>
                  </div>
                  <div class="flex justify-between">
                    <span class="text-gray-600">Payment Type:</span>
                    <span class="text-gray-900 capitalize">{{ delivery.payment_type }}</span>
                  </div>
                  <div v-if="delivery.payment_due_date" class="flex justify-between">
                    <span class="text-gray-600">Due Date:</span>
                    <span class="text-gray-900">{{ formatDate(delivery.payment_due_date) }}</span>
                  </div>
                </div>
              </div>
            </div>

            <!-- Route Information -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
              <h3 class="text-lg font-semibold text-gray-900 mb-4 text-center">Delivery Route</h3>
              
              <div class="space-y-4">
                <div class="flex items-center space-x-3 justify-center">
                  <div class="w-3 h-3 bg-green-500 rounded-full flex-shrink-0"></div>
                  <div class="text-center">
                    <p class="text-sm text-gray-600">Pickup From</p>
                    <p class="font-semibold text-green-700">{{ delivery.pick_up_region?.name || 'Not specified' }}</p>
                  </div>
                </div>
                
                <div class="flex justify-center">
                  <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3" />
                  </svg>
                </div>
                
                <div class="flex items-center space-x-3 justify-center">
                  <div class="w-3 h-3 bg-blue-500 rounded-full flex-shrink-0"></div>
                  <div class="text-center">
                    <p class="text-sm text-gray-600">Deliver To</p>
                    <p class="font-semibold text-blue-700">{{ delivery.drop_off_region?.name || 'Not specified' }}</p>
                  </div>
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

const props = defineProps({
  delivery: {
    type: Object,
    required: true
  }
})

// Enhanced computed properties for multi-model timeline
const currentStep = computed(() => {
  const status = props.delivery.status
  const deliveryOrder = props.delivery.delivery_order

  // Step 5: Main delivery is completed OR delivery order is completed (highest priority)
  if (status === 'completed' || (deliveryOrder && deliveryOrder.status === 'completed')) {
    return 5
  }
  
  // Step 4: Delivery Order exists and is beyond assigned
  if (deliveryOrder && ['dispatched', 'in_transit', 'delivered', 'needs_review'].includes(deliveryOrder.status)) {
    return 4
  }
  
  // Step 1: Request Submitted (always)
  if (status === 'pending') return 1
  
  // Step 2: Approval
  if (status === 'approved') {
    // Check if payment is needed for prepaid
    if (props.delivery.payment_type === 'prepaid' && props.delivery.payment_status !== 'paid') {
      return 2 // Still at approval, payment pending
    }
    return 3 // Approved and payment handled
  }
  
  return 1 // Default fallback
})

const deliveryOrderStatus = computed(() => {
  return props.delivery.delivery_order?.status || 'not_created'
})

// FIXED: Delivery Order status labels
const deliveryOrderStatusLabel = computed(() => {
  const labels = {
    'not_created': 'Not Yet Created',
    'pending': 'Pending',
    'pending_payment': 'Pending Payment',
    'ready': 'Ready for Assignment',
    'assigned': 'Assigned to Driver',
    'dispatched': 'Dispatched',
    'in_transit': 'In Transit',
    'delivered': 'Delivered',
    'needs_review': 'Needs Review',
    'completed': 'Completed',
    'cancelled': 'Cancelled'
  }
  
  const status = deliveryOrderStatus.value
  return labels[status] || (status ? status.split('_').map(word => 
    word.charAt(0).toUpperCase() + word.slice(1)
  ).join(' ') : 'Pending')
})

// FIXED: Delivery Order status descriptions
const deliveryOrderStatusDescription = computed(() => {
  const descriptions = {
    'not_created': 'Delivery order is being prepared',
    'pending': 'Waiting for processing',
    'pending_payment': 'Awaiting payment confirmation',
    'ready': 'Ready to be assigned to a driver',
    'assigned': 'Assigned to driver, waiting for dispatch',
    'dispatched': 'Driver has departed with your packages',
    'in_transit': 'Packages are currently in transit',
    'delivered': 'All packages delivered successfully',
    'needs_review': 'Delivered with some issues to review',
    'completed': 'Delivery process fully completed',
    'cancelled': 'Delivery was cancelled'
  }
  return descriptions[deliveryOrderStatus.value] || 'Delivery in progress'
})

// FIXED: Delivery Order status badge classes
const deliveryOrderStatusBadgeClass = computed(() => {
  const status = deliveryOrderStatus.value
  if (status === 'delivered' || status === 'completed') return 'bg-green-100 text-green-800'
  if (status === 'needs_review') return 'bg-orange-100 text-orange-800'
  if (status === 'dispatched' || status === 'in_transit') return 'bg-yellow-100 text-yellow-800'
  if (status === 'assigned' || status === 'ready') return 'bg-blue-100 text-blue-800'
  if (status === 'cancelled') return 'bg-red-100 text-red-800'
  if (status === 'pending' || status === 'pending_payment') return 'bg-yellow-100 text-yellow-800'
  return 'bg-gray-100 text-gray-800'
})

// Change the button text to be more specific
const downloadWaybill = () => {
  // Use the delivery request ID instead of waybill_id
  window.open(route('waybills.preview.by_delivery', props.delivery.id), '_blank')
}
// Conditional step visibility
const showPaymentProcessingStep = computed(() => {
  return props.delivery.payment_type === 'prepaid' && 
         props.delivery.status !== 'rejected'
})

const showDeliveryOrderStep = computed(() => {
  return props.delivery.delivery_order !== null && 
         props.delivery.status !== 'rejected'
})

const showCompletedStep = computed(() => {
  return props.delivery.status === 'completed'
})

const showPostpaidPaymentStep = computed(() => {
  return props.delivery.payment_type === 'postpaid' && 
         props.delivery.status === 'completed'
})

// Package status helpers
const packageStatusSummary = computed(() => {
  if (!props.delivery.packages) return null
  
  const total = props.delivery.packages.length
  
  // If delivery is completed, consider all packages as delivered
  if (props.delivery.status === 'completed') {
    return {
      total,
      delivered: total,
      loaded: total,
      inTransit: total,
      damaged: 0,
      lost: 0,
      hasIssues: false,
      successRate: 100
    }
  }
  
  // Original logic for other statuses
  const loaded = props.delivery.packages.filter(pkg => pkg.status === 'loaded').length
  const inTransit = props.delivery.packages.filter(pkg => pkg.status === 'in_transit').length
  const delivered = props.delivery.packages.filter(pkg => pkg.status === 'delivered').length
  const damaged = props.delivery.packages.filter(pkg => pkg.status === 'damaged_in_transit').length
  const lost = props.delivery.packages.filter(pkg => pkg.status === 'lost_in_transit').length
  
  return {
    total,
    loaded,
    inTransit,
    delivered,
    damaged,
    lost,
    hasIssues: damaged > 0 || lost > 0,
    successRate: total > 0 ? Math.round((delivered / total) * 100) : 0
  }
})

const paymentStatusBadgeClass = computed(() => {
  const status = props.delivery.payment_status?.toLowerCase()
  const baseClasses = 'px-2 py-1 rounded-full text-xs font-medium'
  
  const statusClasses = {
    'paid': 'bg-green-100 text-green-800',
    'verified': 'bg-green-100 text-green-800',
    'completed': 'bg-green-100 text-green-800',
    'unpaid': 'bg-yellow-100 text-yellow-800',
    'pending': 'bg-yellow-100 text-yellow-800',
    'pending_payment': 'bg-yellow-100 text-yellow-800',
    'pending_verification': 'bg-blue-100 text-blue-800',
    'processing': 'bg-blue-100 text-blue-800',
    'awaiting_payment': 'bg-yellow-100 text-yellow-800',
    'requires_adjustment': 'bg-orange-100 text-orange-800',
    'refunded': 'bg-purple-100 text-purple-800',
    'rejected': 'bg-red-100 text-red-800',
    'failed': 'bg-red-100 text-red-800',
    'cancelled': 'bg-red-100 text-red-800',
    'partially_paid': 'bg-orange-100 text-orange-800',
    'overdue': 'bg-red-100 text-red-800'
  }
  
  return `${baseClasses} ${statusClasses[status] || 'bg-gray-100 text-gray-800'}`
})

// Enhanced payment status handling
const paymentStatusLabel = computed(() => {
  const labels = {
    'paid': 'Paid',
    'unpaid': 'Unpaid',
    'pending': 'Pending Payment',
    'pending_payment': 'Pending Payment',
    'pending_verification': 'Pending Verification',
    'rejected': 'Rejected',
    'awaiting_payment': 'Awaiting Payment',
    'requires_adjustment': 'Adjustment Needed',
    'refunded': 'Refunded',
    'verified': 'Verified',
    'failed': 'Failed',
    'cancelled': 'Cancelled',
    'processing': 'Processing',
    'completed': 'Completed',
    'partially_paid': 'Partially Paid',
    'overdue': 'Overdue'
  }
  
  const status = props.delivery.payment_status
  if (!status) return 'Pending'
  
  // Try exact match first
  if (labels[status]) {
    return labels[status]
  }
  
  // Try case-insensitive match
  const lowerStatus = status.toLowerCase()
  for (const [key, value] of Object.entries(labels)) {
    if (key.toLowerCase() === lowerStatus) {
      return value
    }
  }
  
  // Fallback: format the status string
  return status.split('_').map(word => 
    word.charAt(0).toUpperCase() + word.slice(1)
  ).join(' ')
})

// Enhanced payment eligibility check
const canMakePayment = computed(() => {
  const status = props.delivery.payment_status
  
  if (status === 'paid' && props.delivery.payment_verified) {
    return false
  }
  
  if (status === 'requires_adjustment') {
    return false
  }
  
  if (props.delivery.payment_type === 'prepaid') {
    return props.delivery.status === 'approved'
  }
  
  if (props.delivery.payment_type === 'postpaid') {
    return props.delivery.status === 'completed' && 
           status !== 'requires_adjustment'
  }
  
  return false
})

const showNextSteps = computed(() => {
  return !['rejected', 'completed'].includes(props.delivery.status) || 
         (props.delivery.payment_type === 'postpaid' && 
          ['awaiting_payment', 'requires_adjustment'].includes(props.delivery.payment_status))
})

// Enhanced pay button text
const payNowButtonText = computed(() => {
  const status = props.delivery.payment_status
  if (status === 'rejected') return 'Resubmit Payment'
  if (status === 'paid' && !props.delivery.payment_verified) return 'Update Payment'
  if (status === 'requires_adjustment') return 'Awaiting Invoice'
  if (status === 'pending_payment') return 'Pay Now'
  if (status === 'unpaid') return 'Pay Now'
  return 'Pay Now'
})

// Delivery status badge
const deliveryStatusLabel = computed(() => {
  const labels = {
    'pending': 'Pending Approval',
    'approved': 'Approved',
    'rejected': 'Rejected',
    'completed': 'Completed',
    'draft': 'Draft',
    'in_transit': 'In Transit',
    'delivered': 'Delivered',
    'cancelled': 'Cancelled',
    'processing': 'Processing',
    'ready': 'Ready'
  }
  
  const status = props.delivery.status
  if (!status) return 'Unknown'
  
  // Try exact match first
  if (labels[status]) {
    return labels[status]
  }
  
  // Try case-insensitive match
  const lowerStatus = status.toLowerCase()
  for (const [key, value] of Object.entries(labels)) {
    if (key.toLowerCase() === lowerStatus) {
      return value
    }
  }
  
  // Fallback: format the status string
  return status.split('_').map(word => 
    word.charAt(0).toUpperCase() + word.slice(1)
  ).join(' ')
})

const statusBadgeClass = computed(() => {
  const status = props.delivery.status
  if (status === 'approved') return 'bg-green-100 text-green-800 px-2 py-1 rounded-full'
  if (status === 'rejected') return 'bg-red-100 text-red-800 px-2 py-1 rounded-full'
  if (status === 'completed') return 'bg-blue-100 text-blue-800 px-2 py-1 rounded-full'
  if (status === 'pending') return 'bg-yellow-100 text-yellow-800 px-2 py-1 rounded-full'
  return 'bg-gray-100 text-gray-800 px-2 py-1 rounded-full'
})

const formatDate = (dateString) => {
  if (!dateString) return 'N/A'
  return new Date(dateString).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  })
}

const formatDateTime = (dateString) => {
  if (!dateString) return 'N/A'
  return new Date(dateString).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}
</script>