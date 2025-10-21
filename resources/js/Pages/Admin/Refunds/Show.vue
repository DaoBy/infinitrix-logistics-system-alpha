<template>
  <EmployeeLayout>
    <div class="max-w-4xl mx-auto py-6 sm:px-6 lg:px-8">
      <div class="px-4 py-6 sm:px-0">
        <!-- Header -->
        <div class="mb-6">
          <div class="flex justify-between items-start">
            <div>
              <h1 class="text-2xl font-bold text-gray-900">
                {{ refund.type === 'adjustment' ? 'Adjustment Details' : 'Refund Details' }}
              </h1>
              <p class="mt-1 text-sm text-gray-600">
                {{ refund.type === 'adjustment' ? 'Invoice adjustment information.' : 'Refund information and processing.' }}
              </p>
            </div>
            <div class="flex space-x-3">
              <Link
                :href="route('refunds.index')"
                class="bg-white py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
              >
                Back to List
              </Link>
              <Link
                v-if="refund.status === 'pending' || refund.status === 'pending_adjustment'"
                :href="route('refunds.edit', { refund: refund.id })"
                class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500"
              >
                {{ refund.type === 'adjustment' ? 'Process Adjustment' : 'Process Refund' }}
              </Link>
            </div>
          </div>
        </div>

        <!-- Refund/Adjustment Information -->
        <div class="bg-white shadow rounded-lg mb-6">
          <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
            <h3 class="text-lg font-medium leading-6 text-gray-900">
              {{ refund.type === 'adjustment' ? 'Adjustment Information' : 'Refund Information' }}
            </h3>
          </div>
          <div class="px-4 py-5 sm:p-6">
            <dl class="grid grid-cols-1 gap-x-4 gap-y-6 sm:grid-cols-2">
              <div>
                <dt class="text-sm font-medium text-gray-500">Type</dt>
                <dd class="mt-1">
                  <span :class="typeBadgeClass(refund.type)" class="inline-flex px-2 text-xs font-semibold leading-5 rounded-full">
                    {{ refund.type_label }}
                  </span>
                </dd>
              </div>
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
                <dt class="text-sm font-medium text-gray-500">
                  {{ refund.type === 'adjustment' ? 'Adjustment Amount' : 'Refund Amount' }}
                </dt>
                <dd class="mt-1 text-sm text-gray-900">₱{{ refund.refund_amount }}</dd>
              </div>
              <div v-if="refund.type === 'adjustment' && refund.adjusted_amount !== null">
                <dt class="text-sm font-medium text-gray-500">New Amount Due</dt>
                <dd class="mt-1 text-sm text-gray-900">₱{{ refund.adjusted_amount }}</dd>
              </div>
              <div>
                <dt class="text-sm font-medium text-gray-500">Reason</dt>
                <dd class="mt-1 text-sm text-gray-900">{{ refund.reason_label }}</dd>
              </div>
              <div>
                <dt class="text-sm font-medium text-gray-500">Status</dt>
                <dd class="mt-1">
                  <span :class="statusBadgeClass(refund.status)" class="inline-flex px-2 text-xs font-semibold leading-5 rounded-full">
                    {{ refund.status_label }}
                  </span>
                </dd>
              </div>
              <div v-if="refund.processed_at" class="sm:col-span-2">
                <dt class="text-sm font-medium text-gray-500">
                  {{ refund.type === 'adjustment' ? 'Adjusted At' : 'Processed At' }}
                </dt>
                <dd class="mt-1 text-sm text-gray-900">{{ formatDate(refund.processed_at) }}</dd>
              </div>
              <div v-if="refund.processor" class="sm:col-span-2">
                <dt class="text-sm font-medium text-gray-500">
                  {{ refund.type === 'adjustment' ? 'Adjusted By' : 'Processed By' }}
                </dt>
                <dd class="mt-1 text-sm text-gray-900">{{ refund.processor.name }}</dd>
              </div>
            </dl>
          </div>
        </div>

        <!-- Description -->
        <div class="bg-white shadow rounded-lg mb-6">
          <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
            <h3 class="text-lg font-medium leading-6 text-gray-900">
              Description
            </h3>
          </div>
          <div class="px-4 py-5 sm:p-6">
            <p class="text-sm text-gray-900">{{ refund.description }}</p>
          </div>
        </div>

        <!-- Notes -->
        <div v-if="refund.notes" class="bg-white shadow rounded-lg mb-6">
          <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
            <h3 class="text-lg font-medium leading-6 text-gray-900">
              Notes
            </h3>
          </div>
          <div class="px-4 py-5 sm:p-6">
            <p class="text-sm text-gray-900">{{ refund.notes }}</p>
          </div>
        </div>

        <!-- Affected Packages -->
        <div v-if="refund.refunded_packages_list && refund.refunded_packages_list.length > 0" class="bg-white shadow rounded-lg">
          <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
            <h3 class="text-lg font-medium leading-6 text-gray-900">
              Affected Packages ({{ refund.refunded_packages_list?.length || 0 }})
            </h3>
          </div>
          <div class="px-4 py-5 sm:p-6">
            <div class="overflow-hidden">
              <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                  <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Item Name
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Value
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Status
                    </th>
                  </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                  <tr v-for="pkg in refund.refunded_packages_list" :key="pkg.id">
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                      {{ pkg.item_name || 'N/A' }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                      ₱{{ pkg.value || '0.00' }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                      <span :class="packageStatusBadgeClass(pkg.status)" class="inline-flex px-2 text-xs font-semibold leading-5 rounded-full">
                        {{ pkg.status }}
                      </span>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>
  </EmployeeLayout>
</template>

<script setup>
import EmployeeLayout from '@/Layouts/EmployeeLayout.vue'
import { Link } from '@inertiajs/vue3'

const props = defineProps({
  refund: Object
})

function typeBadgeClass(type) {
  const classes = {
    refund: 'bg-purple-100 text-purple-800',
    adjustment: 'bg-orange-100 text-orange-800'
  }
  return classes[type] || 'bg-gray-100 text-gray-800'
}

function statusBadgeClass(status) {
  const classes = {
    pending: 'bg-yellow-100 text-yellow-800',
    pending_adjustment: 'bg-yellow-100 text-yellow-800',
    processed: 'bg-green-100 text-green-800',
    adjusted: 'bg-green-100 text-green-800'
  }
  return classes[status] || 'bg-gray-100 text-gray-800'
}

function packageStatusBadgeClass(status) {
  const classes = {
    'damaged_in_transit': 'bg-red-100 text-red-800',
    'lost_in_transit': 'bg-red-100 text-red-800',
    'delivered': 'bg-green-100 text-green-800',
    'completed': 'bg-green-100 text-green-800'
  }
  return classes[status] || 'bg-gray-100 text-gray-800'
}

function formatDate(date) {
  return new Date(date).toLocaleDateString()
}
</script>