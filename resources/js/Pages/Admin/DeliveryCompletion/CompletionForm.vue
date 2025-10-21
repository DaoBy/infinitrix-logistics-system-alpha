<template>
  <EmployeeLayout>
    <div class="max-w-4xl mx-auto py-6 sm:px-6 lg:px-8">
      <div class="px-4 py-6 sm:px-0">
        <!-- Header with Status -->
        <div class="mb-6">
          <div class="flex items-center justify-between">
            <div>
              <h1 class="text-2xl font-bold text-gray-900">Delivery Completion</h1>
              <p class="mt-1 text-sm text-gray-600">
                Finalize delivery process and confirm package handover.
              </p>
            </div>
            <div>
              <span :class="statusBadgeClass(order.status)" class="inline-flex px-3 py-1 text-sm font-semibold leading-5 rounded-full">
                {{ order.status.replace('_', ' ').toUpperCase() }}
              </span>
            </div>
          </div>
        </div>

        <!-- Process Guide -->
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
          <div class="flex">
            <div class="flex-shrink-0">
              <InformationCircleIcon class="h-5 w-5 text-blue-400" />
            </div>
            <div class="ml-3">
              <h3 class="text-sm font-medium text-blue-800">
                Process Guide
              </h3>
              <div class="mt-2 text-sm text-blue-700">
                <p v-if="order.status === 'delivered'" class="font-medium">✅ Perfect Delivery</p>
                <p v-else class="font-medium">⚠️ Delivery with Issues</p>
                <ul class="mt-1 list-disc list-inside space-y-1">
                  <li>Review sender and receiver details</li>
                  <li>Verify package information below</li>
                  <li>Confirm delivery completion</li>
                  <li v-if="order.status === 'needs_review' && isPrepaid">
                    System will create refund request for damaged/lost packages
                  </li>
                  <li v-else-if="order.status === 'needs_review' && isPostpaid">
                    System will adjust invoice for damaged/lost packages
                  </li>
                </ul>
              </div>
            </div>
          </div>
        </div>

        <!-- Existing Refund Alert -->
        <div v-if="hasExistingRefund" class="mb-6">
          <div class="rounded-md bg-yellow-50 p-4">
            <div class="flex">
              <div class="flex-shrink-0">
                <ExclamationTriangleIcon class="h-5 w-5 text-yellow-400" />
              </div>
              <div class="ml-3 flex-1 md:flex md:justify-between">
                <div>
                  <p class="text-sm font-medium text-yellow-800">
                    Refund Request Already Exists
                  </p>
                  <p class="mt-1 text-sm text-yellow-700">
                    <span v-if="existingRefundStatus === 'pending'">
                      A refund request is pending review. No need to complete delivery again.
                    </span>
                    <span v-else-if="existingRefundStatus === 'processed'">
                      Refund has been processed. This delivery is complete.
                    </span>
                  </p>
                </div>
                <div class="mt-3 md:mt-0 md:ml-6" v-if="existingRefundId">
                  <Link
                    :href="route('refunds.show', { refund: existingRefundId })"
                    class="inline-flex items-center text-sm font-medium text-yellow-800 hover:text-yellow-900"
                  >
                    View Refund Details
                    <ArrowRightIcon class="ml-1 h-4 w-4" />
                  </Link>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Delivery Parties Information -->
        <div class="bg-white shadow rounded-lg mb-6">
          <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
            <h3 class="text-lg font-medium leading-6 text-gray-900">
              Delivery Parties
            </h3>
          </div>
          <div class="px-4 py-5 sm:p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
              <!-- Sender Information -->
              <div>
                <h4 class="text-sm font-medium text-gray-500 mb-4">Sender Information</h4>
                <dl class="space-y-3">
                  <div>
                    <dt class="text-xs font-medium text-gray-400">Name</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ order.delivery_request.sender.name }}</dd>
                  </div>
                  <div v-if="order.delivery_request.sender.phone">
                    <dt class="text-xs font-medium text-gray-400">Phone</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ order.delivery_request.sender.phone }}</dd>
                  </div>
                  <div v-if="order.delivery_request.sender.email">
                    <dt class="text-xs font-medium text-gray-400">Email</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ order.delivery_request.sender.email }}</dd>
                  </div>
                  <div>
                    <dt class="text-xs font-medium text-gray-400">Pick-up Region</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ order.delivery_request.pick_up_region.name }}</dd>
                  </div>
                </dl>
              </div>

              <!-- Receiver Information -->
              <div>
                <h4 class="text-sm font-medium text-gray-500 mb-4">Receiver Information</h4>
                <dl class="space-y-3">
                  <div>
                    <dt class="text-xs font-medium text-gray-400">Name</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ order.delivery_request.receiver.name }}</dd>
                  </div>
                  <div v-if="order.delivery_request.receiver.phone">
                    <dt class="text-xs font-medium text-gray-400">Phone</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ order.delivery_request.receiver.phone }}</dd>
                  </div>
                  <div v-if="order.delivery_request.receiver.email">
                    <dt class="text-xs font-medium text-gray-400">Email</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ order.delivery_request.receiver.email }}</dd>
                  </div>
                  <div>
                    <dt class="text-xs font-medium text-gray-400">Delivery Region</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ order.delivery_request.drop_off_region.name }}</dd>
                  </div>
                </dl>
              </div>
            </div>
          </div>
        </div>

        <!-- Package Details -->
        <div class="bg-white shadow rounded-lg mb-6">
          <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
            <div class="flex justify-between items-center">
              <h3 class="text-lg font-medium leading-6 text-gray-900">
                Package Details
              </h3>
              <span class="text-sm text-gray-500">
                {{ order.delivery_request.packages.length }} packages total
              </span>
            </div>
          </div>
          <div class="px-4 py-5 sm:p-6">
            <div class="overflow-hidden">
              <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                  <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Item</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Dimensions</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Weight</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Value</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                  </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                  <tr v-for="pkg in order.delivery_request.packages" :key="pkg.id">
                    <td class="px-4 py-4 whitespace-nowrap">
                      <div class="text-sm font-medium text-gray-900">{{ pkg.item_name }}</div>
                      <div class="text-sm text-gray-500">{{ pkg.item_code }}</div>
                      <div v-if="pkg.description" class="text-xs text-gray-400 mt-1">{{ pkg.description }}</div>
                    </td>
                    <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900">
                      <span v-if="pkg.height && pkg.width && pkg.length">
                        {{ pkg.height }} × {{ pkg.width }} × {{ pkg.length }} cm
                      </span>
                      <span v-else class="text-gray-400">-</span>
                    </td>
                    <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900">
                      {{ pkg.weight }} kg
                    </td>
                    <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900">
                      ₱{{ pkg.value }}
                    </td>
                    <td class="px-4 py-4 whitespace-nowrap">
                      <span :class="packageStatusBadgeClass(pkg.status)" class="inline-flex px-2 text-xs font-semibold leading-5 rounded-full">
                        {{ pkg.status.replace('_', ' ').toUpperCase() }}
                      </span>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <!-- Delivery Summary -->
        <div class="bg-white shadow rounded-lg mb-6">
          <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
            <h3 class="text-lg font-medium leading-6 text-gray-900">
              Delivery Summary
            </h3>
          </div>
          <div class="px-4 py-5 sm:p-6">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-center">
              <div class="bg-green-50 p-4 rounded-lg">
                <div class="text-2xl font-bold text-green-600">{{ outcomeStats.delivered_count }}</div>
                <div class="text-sm text-green-800">Successfully Delivered</div>
              </div>
              <div class="bg-yellow-50 p-4 rounded-lg" v-if="outcomeStats.damaged_count > 0">
                <div class="text-2xl font-bold text-yellow-600">{{ outcomeStats.damaged_count }}</div>
                <div class="text-sm text-yellow-800">Damaged</div>
              </div>
              <div class="bg-red-50 p-4 rounded-lg" v-if="outcomeStats.lost_count > 0">
                <div class="text-2xl font-bold text-red-600">{{ outcomeStats.lost_count }}</div>
                <div class="text-sm text-red-800">Lost</div>
              </div>
              <div class="bg-blue-50 p-4 rounded-lg">
                <div class="text-2xl font-bold text-blue-600 capitalize">{{ order.delivery_request.payment_type }}</div>
                <div class="text-sm text-blue-800">Payment Type</div>
              </div>
            </div>

            <!-- Financial Notice -->
            <div v-if="order.status === 'needs_review' && !hasExistingRefund" class="mt-6 p-4 bg-orange-50 border border-orange-200 rounded-md">
              <div class="flex">
                <div class="flex-shrink-0">
                  <ExclamationTriangleIcon class="h-5 w-5 text-orange-400" />
                </div>
                <div class="ml-3">
                  <h3 class="text-sm font-medium text-orange-800">
                    Financial Action Required
                  </h3>
                  <div class="mt-2 text-sm text-orange-700">
                    <p v-if="isPrepaid">
                      A refund request will be created for the damaged/lost packages. Customer will be contacted for negotiation.
                    </p>
                    <p v-else-if="isPostpaid">
                      Invoice will be adjusted to exclude charges for damaged/lost packages.
                    </p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Completion Form -->
        <form @submit.prevent="submit">
          <div class="bg-white shadow rounded-lg">
            <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
              <h3 class="text-lg font-medium leading-6 text-gray-900">
                Final Confirmation
              </h3>
            </div>
            <div class="px-4 py-5 sm:p-6">
              <!-- Confirmation Checkbox -->
              <div class="flex items-start">
                <div class="flex items-center h-5">
                  <input
                    v-model="form.confirm_completion"
                    id="confirm_completion"
                    name="confirm_completion"
                    type="checkbox"
                    :disabled="hasExistingRefund"
                    class="focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300 rounded"
                  />
                </div>
                <div class="ml-3 text-sm">
                  <label for="confirm_completion" class="font-medium text-gray-700">
                    I confirm that all information above has been verified and the delivery process is complete
                  </label>
                  <p class="text-gray-500 mt-1">
                    By checking this box, you confirm that package details, receiver information, and delivery outcomes have been properly reviewed.
                  </p>
                </div>
              </div>
              <p v-if="form.errors.confirm_completion" class="mt-2 text-sm text-red-600">
                {{ form.errors.confirm_completion }}
              </p>

              <!-- Optional Notes -->
              <div class="mt-6">
                <label for="notes" class="block text-sm font-medium text-gray-700">
                  Additional Notes (Optional)
                </label>
                <textarea
                  v-model="form.notes"
                  id="notes"
                  rows="3"
                  :disabled="hasExistingRefund"
                  class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm disabled:opacity-50"
                  placeholder="Any special notes about the delivery, handover process, or customer communication..."
                ></textarea>
              </div>
            </div>
          </div>

          <!-- Action Buttons -->
          <div class="flex justify-end space-x-3 mt-6">
            <Link
              :href="route('delivery-completion.ready-for-completion')"
              class="bg-white py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
            >
              Back to List
            </Link>
            
            <button
              v-if="!hasExistingRefund"
              type="submit"
              :disabled="form.processing || !form.confirm_completion"
              :class="buttonClass"
            >
              <span v-if="form.processing">
                <ArrowPathIcon class="h-4 w-4 animate-spin mr-2" />
                Processing...
              </span>
              <span v-else>
                {{ buttonText }}
              </span>
            </button>

            <Link
              v-else
              :href="route('refunds.show', { refund: existingRefundId })"
              class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500"
            >
              View Refund Details
            </Link>
          </div>
        </form>
      </div>
    </div>
  </EmployeeLayout>
</template>

<script setup>
import EmployeeLayout from '@/Layouts/EmployeeLayout.vue'
import { Link, useForm } from '@inertiajs/vue3'
import { 
  ExclamationTriangleIcon, 
  InformationCircleIcon,
  ArrowPathIcon,
  ArrowRightIcon 
} from '@heroicons/vue/24/outline'
import { computed } from 'vue'

const props = defineProps({
  order: Object,
  outcomeStats: Object,
  requiresRefund: Boolean,
  hasExistingRefund: Boolean,
  existingRefundStatus: String,
  existingRefundId: Number
})

// Computed properties
const isPostpaid = computed(() => {
  return props.order.delivery_request?.payment_type === 'postpaid'
})

const isPrepaid = computed(() => {
  return props.order.delivery_request?.payment_type === 'prepaid'
})

// Dynamic button text and styling
const buttonText = computed(() => {
  if (props.order.status === 'delivered') {
    return 'Complete Perfect Delivery'
  } else {
    return 'Complete Delivery with Issues'
  }
})
const pickUpRegionName = computed(() => {
  return props.order.delivery_request?.pick_up_region?.name || 'N/A'
})

const dropOffRegionName = computed(() => {
  return props.order.delivery_request?.drop_off_region?.name || 'N/A'
})

const buttonClass = computed(() => {
  const baseClass = 'inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white focus:outline-none focus:ring-2 focus:ring-offset-2 disabled:opacity-50'
  
  if (props.order.status === 'delivered') {
    return `${baseClass} bg-green-600 hover:bg-green-700 focus:ring-green-500`
  } else {
    return `${baseClass} bg-orange-600 hover:bg-orange-700 focus:ring-orange-500`
  }
})

// Simplified form - only confirmation and notes
const form = useForm({
  confirm_completion: false,
  notes: '',
  receiver_name: props.order.delivery_request?.receiver?.name || '', // ADD THIS
  receiver_contact: props.order.delivery_request?.receiver?.phone || '', // ADD THIS
  release_packages: props.order.delivery_request?.packages?.map(p => p.id) || [] // ADD THIS
})

function submit() {
  if (props.hasExistingRefund) {
    return;
  }
  
  console.log('🟡 Form submitting...', {
    orderId: props.order.id,
    formData: form.data(),
    confirm_completion: form.confirm_completion
  });
  
  form.post(route('delivery-completion.process', { order: props.order.id }), {
    onSuccess: () => {
      console.log('✅ Form submitted successfully');
    },
    onError: (errors) => {
      console.log('❌ Form submission failed:', errors);
    },
    onFinish: () => {
      console.log('🏁 Form submission finished');
    }
  });
}

function statusBadgeClass(status) {
  const classes = {
    delivered: 'bg-green-100 text-green-800',
    needs_review: 'bg-orange-100 text-orange-800',
    completed: 'bg-blue-100 text-blue-800'
  }
  return classes[status] || 'bg-gray-100 text-gray-800'
}

function packageStatusBadgeClass(status) {
  const classes = {
    delivered: 'bg-green-100 text-green-800',
    damaged_in_transit: 'bg-red-100 text-red-800',
    lost_in_transit: 'bg-gray-100 text-gray-800',
    in_transit: 'bg-blue-100 text-blue-800',
    completed: 'bg-blue-100 text-blue-800'
  }
  return classes[status] || 'bg-gray-100 text-gray-800'
}
</script>