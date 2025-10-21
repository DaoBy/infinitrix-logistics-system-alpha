<template>
  <EmployeeLayout>
    <div class="max-w-4xl mx-auto py-6 sm:px-6 lg:px-8">
      <div class="px-4 py-6 sm:px-0">
        <!-- Header -->
        <div class="mb-6">
          <h1 class="text-2xl font-bold text-gray-900">
            {{ refund.type === 'adjustment' ? 'Process Invoice Adjustment' : 'Process Refund' }}
          </h1>
          <p class="mt-1 text-sm text-gray-600">
            {{ refund.type === 'adjustment' ? 'Negotiate and apply invoice adjustment.' : 'Negotiate and process refund with customer.' }}
          </p>
        </div>

        <!-- Refund/Adjustment Information -->
        <div class="bg-white shadow rounded-lg mb-6">
          <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
            <h3 class="text-lg font-medium leading-6 text-gray-900">
              {{ refund.type === 'adjustment' ? 'Adjustment Details' : 'Refund Details' }}
            </h3>
          </div>
          <div class="px-4 py-5 sm:p-6">
            <dl class="grid grid-cols-1 gap-x-4 gap-y-6 sm:grid-cols-2">
              <div>
                <dt class="text-sm font-medium text-gray-500">Reference Number</dt>
                <dd class="mt-1 text-sm text-gray-900">{{ refund.delivery_request.reference_number }}</dd>
              </div>
              <div>
                <dt class="text-sm font-medium text-gray-500">Sender</dt>
                <dd class="mt-1 text-sm text-gray-900">{{ refund.delivery_request.sender.name }}</dd>
              </div>
              <div>
                <dt class="text-sm font-medium text-gray-500">Original Amount</dt>
                <dd class="mt-1 text-sm text-gray-900">₱{{ refund.original_amount }}</dd>
              </div>
              <div>
                <dt class="text-sm font-medium text-gray-500">Payment Type</dt>
                <dd class="mt-1 text-sm text-gray-900 capitalize">{{ refund.delivery_request.payment_type }}</dd>
              </div>
              <div>
                <dt class="text-sm font-medium text-gray-500">Maximum {{ refund.type === 'adjustment' ? 'Adjustable' : 'Refundable' }}</dt>
                <dd class="mt-1 text-sm text-gray-900">₱{{ maxRefundable }}</dd>
              </div>
              <div v-if="refund.type === 'adjustment'">
                <dt class="text-sm font-medium text-gray-500">New Amount Due</dt>
                <dd class="mt-1 text-sm text-gray-900">₱{{ newAmountDue }}</dd>
              </div>
            </dl>
          </div>
        </div>

        <!-- Negotiation Form -->
        <form @submit.prevent="submit">
          <div class="bg-white shadow rounded-lg mb-6">
            <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
              <h3 class="text-lg font-medium leading-6 text-gray-900">
                {{ refund.type === 'adjustment' ? 'Adjustment Negotiation' : 'Refund Negotiation' }}
              </h3>
            </div>
            <div class="px-4 py-5 sm:p-6">
              <!-- Amount -->
              <div class="mb-6">
                <label for="refund_amount" class="block text-sm font-medium text-gray-700">
                  {{ refund.type === 'adjustment' ? 'Adjustment Amount *' : 'Refund Amount *' }}
                </label>
                <div class="mt-1 relative rounded-md shadow-sm">
                  <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <span class="text-gray-500 sm:text-sm">₱</span>
                  </div>
                  <input
                    v-model="form.refund_amount"
                    type="number"
                    step="0.01"
                    min="0"
                    :max="maxRefundable"
                    id="refund_amount"
                    class="block w-full pl-7 pr-12 border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                    placeholder="0.00"
                    @input="calculateNewAmount"
                  />
                </div>
                <p class="mt-1 text-sm text-gray-500">
                  Maximum {{ refund.type === 'adjustment' ? 'adjustable' : 'refundable' }} amount: ₱{{ maxRefundable }}
                </p>
                <p v-if="refund.type === 'adjustment'" class="mt-1 text-sm text-blue-600">
                  New amount due: ₱{{ newAmountDue }}
                </p>
                <p v-if="form.errors.refund_amount" class="mt-1 text-sm text-red-600">
                  {{ form.errors.refund_amount }}
                </p>
              </div>

              <!-- Reason -->
              <div class="mb-6">
                <label for="reason" class="block text-sm font-medium text-gray-700">
                  Reason *
                </label>
                <select
                  v-model="form.reason"
                  id="reason"
                  class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                >
                  <option value="">Select a reason</option>
                  <option v-for="(label, value) in reasonOptions" :key="value" :value="value">
                    {{ label }}
                  </option>
                </select>
                <p v-if="form.errors.reason" class="mt-1 text-sm text-red-600">
                  {{ form.errors.reason }}
                </p>
              </div>

              <!-- Description -->
              <div class="mb-6">
                <label for="description" class="block text-sm font-medium text-gray-700">
                  Description *
                </label>
                <textarea
                  v-model="form.description"
                  id="description"
                  rows="3"
                  class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                  :placeholder="refund.type === 'adjustment' ? 'Describe the adjustment reason and negotiation details...' : 'Describe the refund reason and negotiation details...'"
                ></textarea>
                <p v-if="form.errors.description" class="mt-1 text-sm text-red-600">
                  {{ form.errors.description }}
                </p>
              </div>

              <!-- Notes -->
              <div class="mb-6">
                <label for="notes" class="block text-sm font-medium text-gray-700">
                  Internal Notes
                </label>
                <textarea
                  v-model="form.notes"
                  id="notes"
                  rows="2"
                  class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                  :placeholder="refund.type === 'adjustment' ? 'Internal notes about the adjustment...' : 'Internal notes about the negotiation...'"
                ></textarea>
              </div>

              <!-- Action Selection -->
              <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-3">
                  Action
                </label>
                <div class="space-y-2">
                  <div class="flex items-center">
                    <input
                      v-model="form.action"
                      id="update_pending"
                      value="update_pending"
                      type="radio"
                      class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300"
                    />
                    <label for="update_pending" class="ml-3 block text-sm font-medium text-gray-700">
                      Update negotiation details (keep as pending)
                    </label>
                  </div>
                  <div class="flex items-center">
                    <input
                      v-model="form.action"
                      id="process"
                      value="process"
                      type="radio"
                      class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300"
                    />
                    <label for="process" class="ml-3 block text-sm font-medium text-gray-700">
                      {{ refund.type === 'adjustment' ? 'Apply adjustment immediately' : 'Process refund immediately' }}
                    </label>
                  </div>
                </div>
                <p v-if="form.errors.action" class="mt-1 text-sm text-red-600">
                  {{ form.errors.action }}
                </p>
              </div>
            </div>
          </div>

          <!-- Action Buttons -->
          <div class="flex justify-end space-x-3">
            <Link
              :href="route('refunds.show', { refund: refund.id })"
              class="bg-white py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
            >
              Cancel
            </Link>
            <button
              type="submit"
              :disabled="form.processing"
              class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50"
            >
              {{ form.action === 'process' ? (refund.type === 'adjustment' ? 'Apply Adjustment' : 'Process Refund') : 'Update Details' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </EmployeeLayout>
</template>

<script setup>
import EmployeeLayout from '@/Layouts/EmployeeLayout.vue'
import { Link, useForm } from '@inertiajs/vue3'
import { ref, computed } from 'vue'

const props = defineProps({
  refund: Object,
  maxRefundable: Number,
  reasonOptions: Object
})

const form = useForm({
  refund_amount: props.refund.refund_amount,
  reason: props.refund.reason,
  description: props.refund.description,
  notes: props.refund.notes,
  action: 'process'
})

// Calculate new amount due for adjustments
const newAmountDue = computed(() => {
  if (props.refund.type === 'adjustment') {
    const adjustmentAmount = Number(form.refund_amount) || 0
    return Math.max(0, props.refund.original_amount - adjustmentAmount)
  }
  return 0
})

function calculateNewAmount() {
  // This will trigger the computed property update
}

function submit() {
  form.put(route('refunds.update', { refund: props.refund.id }))
}
</script>