<template>
  <EmployeeLayout>
    <div class="space-y-6">
      <div class="sm:flex sm:items-center">
        <div class="sm:flex-auto">
          <h1 class="text-2xl font-semibold text-gray-900">Delivery Completion</h1>
          <p class="mt-2 text-sm text-gray-700">
            Process delivery outcomes and handle package releases.
          </p>
        </div>
      </div>

      <!-- Filters -->
      <div class="bg-white shadow rounded-lg">
        <div class="p-4 border-b border-gray-200">
          <div class="flex flex-col sm:flex-row gap-4">
            <div class="flex-1">
              <input
                v-model="filters.search"
                type="text"
                placeholder="Search by reference number or sender..."
                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                @keyup.enter="refresh"
              />
            </div>
            <div class="sm:w-48">
              <select
                v-model="filters.status"
                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                @change="refresh"
              >
                <option value="">All Status</option>
                <option value="delivered">Delivered</option>
                <option value="needs_review">Needs Review</option>
                <option value="completed">Completed</option>
              </select>
            </div>
            <button
              @click="refresh"
              class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
            >
              Search
            </button>
          </div>
        </div>
      </div>

      <!-- Pending Completion -->
      <div class="bg-white shadow rounded-lg">
        <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
          <h3 class="text-lg font-medium leading-6 text-gray-900">
            Pending Completion ({{ pendingOrders?.meta?.total || 0 }})
          </h3>
          <p class="mt-1 text-sm text-gray-500">
            Orders that need completion processing
          </p>
        </div>
        <div class="overflow-hidden">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Reference
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Sender → Receiver
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Driver
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Status
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Packages
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Payment
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Refund Status
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Actions
                </th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <tr v-if="pendingOrders?.data?.length > 0" v-for="order in pendingOrders.data" :key="order.id">
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                  {{ order.delivery_request?.reference_number || 'N/A' }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                  <div>{{ order.delivery_request?.sender?.name || 'N/A' }}</div>
                  <div class="text-xs text-gray-400">→ {{ order.delivery_request?.receiver?.name || 'N/A' }}</div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                  {{ order.driver?.name || 'Unassigned' }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <span :class="statusBadgeClass(order.status)" class="inline-flex px-2 text-xs font-semibold leading-5 rounded-full">
                    {{ formatStatus(order.status) }}
                  </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                  <div class="space-y-1">
                    <div class="flex items-center space-x-1">
                      <span class="font-medium">{{ order.delivery_request?.packages?.length || 0 }} total</span>
                    </div>
                    <div class="flex flex-wrap gap-1 text-xs">
                      <span v-if="getDeliveredCount(order) > 0" class="text-green-600 bg-green-50 px-1 rounded">
                        {{ getDeliveredCount(order) }} delivered
                      </span>
                      <span v-if="getDamagedCount(order) > 0" class="text-orange-600 bg-orange-50 px-1 rounded">
                        {{ getDamagedCount(order) }} damaged
                      </span>
                      <span v-if="getLostCount(order) > 0" class="text-red-600 bg-red-50 px-1 rounded">
                        {{ getLostCount(order) }} lost
                      </span>
                    </div>
                  </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                  <div class="capitalize">{{ order.delivery_request?.payment_type || 'N/A' }}</div>
                  <div :class="paymentStatusClass(order.delivery_request?.payment_status)" class="text-xs capitalize">
                    {{ order.delivery_request?.payment_status || 'N/A' }}
                  </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm">
                  <span v-if="hasRefund(order)" :class="refundStatusClass(order)" class="inline-flex px-2 text-xs font-semibold leading-5 rounded-full">
                    {{ getRefundStatus(order) }}
                  </span>
                  <span v-else-if="needsRefund(order)" class="inline-flex px-2 text-xs font-semibold leading-5 rounded-full bg-yellow-100 text-yellow-800">
                    Needs Refund
                  </span>
                  <span v-else class="text-gray-400 text-xs">No refund</span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                  <div class="flex space-x-2">
                    <Link
                      :href="route('delivery-completion.show-form', { order: order.id })"
                      class="text-blue-600 hover:text-blue-900"
                    >
                      Process
                    </Link>
                    <Link
                      v-if="hasRefund(order) && getRefundId(order)"
                      :href="route('refunds.show', { refund: getRefundId(order) })"
                      class="text-green-600 hover:text-green-900"
                    >
                      View Refund
                    </Link>
                  </div>
                </td>
              </tr>
              <tr v-else>
                <td colspan="8" class="px-6 py-4 text-center text-sm text-gray-500">
                  No pending orders found.
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        <Pagination v-if="pendingOrders?.links" :links="pendingOrders.links" class="px-4 py-3" />
      </div>

      <!-- Completed Orders -->
      <div class="bg-white shadow rounded-lg">
        <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
          <h3 class="text-lg font-medium leading-6 text-gray-900">
            Completed Orders ({{ completedOrders?.meta?.total || 0 }})
          </h3>
          <p class="mt-1 text-sm text-gray-500">
            Successfully processed deliveries
          </p>
        </div>
        <div class="overflow-hidden">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Reference
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Sender → Receiver
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Driver
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Completed At
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Packages
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Payment Status
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Refund Status
                </th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <tr v-if="completedOrders?.data?.length > 0" v-for="order in completedOrders.data" :key="order.id">
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                  {{ order.delivery_request?.reference_number || 'N/A' }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                  <div>{{ order.delivery_request?.sender?.name || 'N/A' }}</div>
                  <div class="text-xs text-gray-400">→ {{ order.delivery_request?.receiver?.name || 'N/A' }}</div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                  {{ order.driver?.name || 'Unassigned' }}
                </td>
            <!-- In the completed orders table -->
<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
  {{ formatDate(getCompletedDate(order)) }}
</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                  <div class="space-y-1">
                    <div class="flex items-center space-x-1">
                      <span class="font-medium">{{ order.delivery_request?.packages?.length || 0 }} total</span>
                    </div>
                    <div class="flex flex-wrap gap-1 text-xs">
                      <span v-if="getDeliveredCount(order) > 0" class="text-green-600 bg-green-50 px-1 rounded">
                        {{ getDeliveredCount(order) }} delivered
                      </span>
                      <span v-if="getDamagedCount(order) > 0" class="text-orange-600 bg-orange-50 px-1 rounded">
                        {{ getDamagedCount(order) }} damaged
                      </span>
                      <span v-if="getLostCount(order) > 0" class="text-red-600 bg-red-50 px-1 rounded">
                        {{ getLostCount(order) }} lost
                      </span>
                    </div>
                  </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                  <span :class="paymentStatusBadgeClass(order.delivery_request?.payment_status)" class="inline-flex px-2 text-xs font-semibold leading-5 rounded-full capitalize">
                    {{ order.delivery_request?.payment_status || 'N/A' }}
                  </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm">
                  <span v-if="hasRefund(order)" :class="refundStatusClass(order)" class="inline-flex px-2 text-xs font-semibold leading-5 rounded-full">
                    {{ getRefundStatus(order) }}
                  </span>
                  <span v-else-if="needsRefund(order)" class="inline-flex px-2 text-xs font-semibold leading-5 rounded-full bg-yellow-100 text-yellow-800">
                    Needs Refund
                  </span>
                  <span v-else class="text-gray-400 text-xs">No refund</span>
                </td>
              </tr>
              <tr v-else>
                <td colspan="7" class="px-6 py-4 text-center text-sm text-gray-500">
                  No completed orders found.
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        <Pagination v-if="completedOrders?.links" :links="completedOrders.links" class="px-4 py-3" />
      </div>
    </div>
  </EmployeeLayout>
</template>

<script setup>
import EmployeeLayout from '@/Layouts/EmployeeLayout.vue'
import { Link, router } from '@inertiajs/vue3'
import { reactive, watch } from 'vue'
import Pagination from '@/Components/Pagination.vue'

const props = defineProps({
  pendingOrders: Object,
  completedOrders: Object,
  filters: Object
})

const filters = reactive({
  search: props.filters?.search || '',
  status: props.filters?.status || ''
})

function refresh() {
  router.get(route('delivery-completion.ready-for-completion'), filters, {
    preserveState: true,
    replace: true
  })
}

// Helper functions
function getDeliveredCount(order) {
  return order.delivery_request?.packages?.filter(p => p.status === 'delivered').length || 0
}

function getDamagedCount(order) {
  return order.delivery_request?.packages?.filter(p => p.status === 'damaged_in_transit').length || 0
}

function getLostCount(order) {
  return order.delivery_request?.packages?.filter(p => p.status === 'lost_in_transit').length || 0
}

function getIncidentCount(order) {
  return getDamagedCount(order) + getLostCount(order)
}

function hasRefund(order) {
  return order.delivery_request?.refunds?.length > 0
}

function needsRefund(order) {
  return order.status === 'needs_review' && 
         order.delivery_request?.payment_type === 'prepaid' &&
         !hasRefund(order)
}

function getRefundStatus(order) {
  const refund = order.delivery_request?.refunds?.[0]
  if (!refund) return ''
  
  // Handle both string status and object with label
  if (typeof refund.status === 'string') {
    return refund.status.charAt(0).toUpperCase() + refund.status.slice(1)
  }
  return refund.status_label || ''
}

function getRefundId(order) {
  return order.delivery_request?.refunds?.[0]?.id
}

function formatStatus(status) {
  if (!status) return 'UNKNOWN'
  return status.split('_').map(word => 
    word.charAt(0).toUpperCase() + word.slice(1)
  ).join(' ')
}

function statusBadgeClass(status) {
  const classes = {
    delivered: 'bg-green-100 text-green-800',
    needs_review: 'bg-yellow-100 text-yellow-800',
    completed: 'bg-blue-100 text-blue-800',
    partially_delivered: 'bg-orange-100 text-orange-800'
  }
  return classes[status] || 'bg-gray-100 text-gray-800'
}

function paymentStatusClass(status) {
  const classes = {
    paid: 'text-green-600',
    awaiting_payment: 'text-yellow-600',
    refunded: 'text-blue-600',
    requires_adjustment: 'text-orange-600',
    unpaid: 'text-red-600',
    cancelled: 'text-red-600'
  }
  return classes[status] || 'text-gray-600'
}

function paymentStatusBadgeClass(status) {
  const classes = {
    paid: 'bg-green-100 text-green-800',
    awaiting_payment: 'bg-yellow-100 text-yellow-800',
    refunded: 'bg-blue-100 text-blue-800',
    requires_adjustment: 'bg-orange-100 text-orange-800',
    unpaid: 'bg-red-100 text-red-800'
  }
  return classes[status] || 'bg-gray-100 text-gray-800'
}

function refundStatusClass(order) {
  const status = getRefundStatus(order).toLowerCase()
  const classes = {
    pending: 'bg-yellow-100 text-yellow-800',
    processed: 'bg-green-100 text-green-800',
    adjusted: 'bg-blue-100 text-blue-800'
  }
  return classes[status] || 'bg-gray-100 text-gray-800'
}

function formatDate(date) {
  if (!date) return 'N/A'
  
  try {
    const dateObj = new Date(date)
    if (isNaN(dateObj.getTime())) return 'Invalid Date'
    
    return dateObj.toLocaleDateString('en-US', {
      year: 'numeric',
      month: 'short',
      day: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    })
  } catch (error) {
    console.error('Date formatting error:', error)
    return 'Invalid Date'
  }
}

// Use updated_at as fallback for completed_at
function getCompletedDate(order) {
  // First try completed_at, then updated_at, then created_at
  return order.completed_at || order.updated_at || order.created_at
}

// Debounced search
let searchTimeout
watch(() => filters.search, () => {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    if (filters.search === '' || filters.search.length >= 3) {
      refresh()
    }
  }, 500)
})
</script>