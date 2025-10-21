<template>
  <EmployeeLayout>
    <div class="space-y-6">
      <div class="sm:flex sm:items-center">
        <div class="sm:flex-auto">
          <h1 class="text-2xl font-semibold text-gray-900">Refunds & Adjustments</h1>
          <p class="mt-2 text-sm text-gray-700">
            Manage customer refunds and invoice adjustments for delivery issues.
          </p>
        </div>
        <div class="mt-4 sm:mt-0 sm:ml-16 sm:flex-none">
          <Link
            :href="route('refunds.create')"
            class="inline-flex items-center justify-center rounded-md border border-transparent bg-blue-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 sm:w-auto"
          >
            Create Refund/Adjustment
          </Link>
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
                v-model="filters.type"
                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                @change="refresh"
              >
                <option value="">All Types</option>
                <option value="refund">Refund</option>
                <option value="adjustment">Adjustment</option>
                <option value="needs_adjustment">Needs Adjustment</option>
              </select>
            </div>
            <div class="sm:w-48">
              <select
                v-model="filters.status"
                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                @change="refresh"
              >
                <option value="">All Status</option>
                <option value="pending">Pending Refund</option>
                <option value="pending_adjustment">Pending Adjustment</option>
                <option value="processed">Processed Refund</option>
                <option value="adjusted">Adjusted Invoice</option>
                <option value="needs_adjustment">Needs Adjustment</option>
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

      <!-- Stats -->
      <div class="grid grid-cols-1 gap-5 sm:grid-cols-3">
        <div class="bg-white overflow-hidden shadow rounded-lg">
          <div class="px-4 py-5 sm:p-6">
            <dt class="text-sm font-medium text-gray-500 truncate">Pending Refunds</dt>
            <dd class="mt-1 text-3xl font-semibold text-gray-900">{{ stats?.pending_refunds || 0 }}</dd>
          </div>
        </div>
        <div class="bg-white overflow-hidden shadow rounded-lg">
          <div class="px-4 py-5 sm:p-6">
            <dt class="text-sm font-medium text-gray-500 truncate">Pending Adjustments</dt>
            <dd class="mt-1 text-3xl font-semibold text-gray-900">{{ stats?.pending_adjustments || 0 }}</dd>
          </div>
        </div>
        <div class="bg-white overflow-hidden shadow rounded-lg">
          <div class="px-4 py-5 sm:p-6">
            <dt class="text-sm font-medium text-gray-500 truncate">Needs Adjustment</dt>
            <dd class="mt-1 text-3xl font-semibold text-gray-900">{{ stats?.needs_adjustment || 0 }}</dd>
          </div>
        </div>
      </div>

      <!-- Refunds & Adjustments Table -->
      <div class="bg-white shadow rounded-lg">
        <div class="overflow-hidden">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Type
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Reference
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Sender
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Original Amount
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Adjustment/Refund
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  New Amount Due
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Status
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Created
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Actions
                </th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <!-- Existing Refunds/Adjustments -->
              <tr v-for="refund in refunds.data" :key="refund.id">
                <td class="px-6 py-4 whitespace-nowrap">
                  <span :class="typeBadgeClass(refund.type)" class="inline-flex px-2 text-xs font-semibold leading-5 rounded-full">
                    {{ refund.type_label }}
                  </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                  {{ refund.delivery_request.reference_number }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                  {{ refund.delivery_request.sender.name }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                  ₱{{ refund.original_amount }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                  ₱{{ refund.refund_amount }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                  <span v-if="refund.type === 'adjustment' && refund.adjusted_amount">
                    ₱{{ refund.adjusted_amount }}
                  </span>
                  <span v-else class="text-gray-400">—</span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <span :class="statusBadgeClass(refund.status)" class="inline-flex px-2 text-xs font-semibold leading-5 rounded-full">
                    {{ refund.status_label }}
                  </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                  {{ formatDate(refund.created_at) }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                  <Link
                    :href="route('refunds.show', { refund: refund.id })"
                    class="text-blue-600 hover:text-blue-900 mr-4"
                  >
                    View
                  </Link>
                  <Link
                    v-if="refund.status === 'pending' || refund.status === 'pending_adjustment'"
                    :href="route('refunds.edit', { refund: refund.id })"
                    class="text-green-600 hover:text-green-900"
                  >
                    {{ refund.type === 'adjustment' ? 'Adjust' : 'Negotiate' }}
                  </Link>
                </td>
              </tr>

              <!-- Delivery Requests Needing Adjustment -->
              <tr v-for="delivery in needsAdjustment.data" :key="'adjustment-' + delivery.id" class="bg-orange-50">
                <td class="px-6 py-4 whitespace-nowrap">
                  <span class="inline-flex px-2 text-xs font-semibold leading-5 rounded-full bg-orange-100 text-orange-800">
                    Needs Adjustment
                  </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                  {{ delivery.reference_number }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                  {{ delivery.sender.name }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                  ₱{{ delivery.total_price }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                  <span class="text-gray-400">—</span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                  <span class="text-gray-400">—</span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <span class="inline-flex px-2 text-xs font-semibold leading-5 rounded-full bg-yellow-100 text-yellow-800">
                    Awaiting Action
                  </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                  {{ formatDate(delivery.updated_at) }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                  <Link
                    :href="route('refunds.create', { delivery_request_id: delivery.id })"
                    class="text-green-600 hover:text-green-900"
                  >
                    Create Adjustment
                  </Link>
                </td>
              </tr>

              <tr v-if="refunds.data.length === 0 && needsAdjustment.data.length === 0">
                <td colspan="9" class="px-6 py-4 text-center text-sm text-gray-500">
                  No refunds or adjustments found.
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        <div class="flex justify-between items-center px-4 py-3 border-t border-gray-200">
          <Pagination :links="refunds.links" v-if="refunds.data.length > 0" />
          <Pagination :links="needsAdjustment.links" v-if="needsAdjustment.data.length > 0" />
        </div>
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
  refunds: Object,
  needsAdjustment: Object,
  stats: {
    type: Object,
    default: () => ({
      pending_refunds: 0,
      pending_adjustments: 0,
      needs_adjustment: 0
    })
  },
  filters: Object
})

const filters = reactive({
  search: props.filters?.search || '',
  type: props.filters?.type || '',
  status: props.filters?.status || '',
  reason: props.filters?.reason || ''
})

function refresh() {
  router.get(route('refunds.index'), filters, {
    preserveState: true,
    replace: true
  })
}

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

function formatDate(date) {
  return new Date(date).toLocaleDateString()
}

// Debounced search
watch(() => filters.search, () => {
  if (filters.search === '' || filters.search.length >= 3) {
    refresh()
  }
})
</script>