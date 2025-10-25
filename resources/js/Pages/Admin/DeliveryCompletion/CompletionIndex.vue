<template>
  <EmployeeLayout>
    <template #header>
<div class="flex flex-wrap justify-between items-center gap-4 px-4 md:px-6 w-full">        <div>
          <h2 class="text-xl font-semibold leading-tight text-gray-800">
            Delivery Completion
          </h2>
          <p class="text-sm text-gray-600 mt-1">
            Process delivery outcomes and handle package releases.
          </p>
        </div>
      </div>
    </template>

<div class="py-6 px-2 md:px-6 w-full max-w-[95rem] mx-auto">        
<div class="bg-white shadow-sm rounded-lg border border-gray-200 mb-6 w-full">        <div class="border-b border-gray-200">
          <nav class="-mb-px flex space-x-8 px-6">
            <button
              @click="switchTab('pending')"
              :class="[
                'py-4 px-1 border-b-2 font-medium text-sm transition-colors duration-200 flex items-center',
                activeTab === 'pending'
                  ? 'border-blue-500 text-blue-600'
                  : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
              ]"
            >
              ⏳ Pending Completion
              <span class="ml-2 bg-blue-100 text-blue-800 px-2 py-1 rounded-full text-xs font-medium">
                {{ stats.pending_total || 0 }}
              </span>
            </button>
            <button
              @click="switchTab('completed')"
              :class="[
                'py-4 px-1 border-b-2 font-medium text-sm transition-colors duration-200 flex items-center',
                activeTab === 'completed'
                  ? 'border-green-500 text-green-600'
                  : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
              ]"
            >
              ✅ Completed Orders
              <span class="ml-2 bg-green-100 text-green-800 px-2 py-1 rounded-full text-xs font-medium">
                {{ stats.completed_total || 0 }}
              </span>
            </button>
          </nav>
        </div>

        <!-- Filters Section -->
        <div class="p-4 border-b border-gray-200">
          <div class="flex flex-col lg:flex-row gap-4">
            <!-- Search -->
            <div class="flex-1">
              <SearchInput
                v-model="filters.search"
                placeholder="Search by reference number or sender..."
                @keyup.enter="handleFilterChange"
                @input="handleDebouncedFilter"
                class="w-full"
              />
            </div>
            
            <!-- Status Filter -->
            <div class="sm:w-48">
              <SelectInput
                v-model="filters.status"
                :options="statusOptions"
                placeholder="All Status"
                @change="handleFilterChange"
              />
            </div>

            <!-- Filter Actions -->
            <div class="flex items-center gap-2">
              <SecondaryButton @click="refreshData">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
                Refresh
              </SecondaryButton>
              <SecondaryButton @click="resetFilters">
                Reset
              </SecondaryButton>
            </div>
          </div>

          <!-- Filter Info -->
          <div class="flex justify-between items-center mt-4">
            <div class="text-sm text-gray-500">
              Showing {{ getCurrentDataCount() }} {{ getItemName() }}
              <span v-if="filters.search" class="ml-2">• "{{ filters.search }}"</span>
              <span v-if="filters.status" class="ml-2">
                • {{ getStatusLabel(filters.status) }}
              </span>
            </div>
          </div>
        </div>
      </div>

      <!-- Tab Content -->
      
      <!-- Pending Completion -->
      <div v-if="activeTab === 'pending'">
<div class="bg-white shadow-sm rounded-lg overflow-hidden border border-gray-200 w-full">          <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
            <div class="flex justify-between items-center">
              <div>
                <h3 class="text-lg font-medium text-gray-900">
                  Pending Completion
                </h3>
                <p class="mt-1 text-sm text-gray-500">
                  Orders that need completion processing
                </p>
              </div>
              <div class="text-sm text-gray-500">
                Showing {{ pendingOrders?.data?.length || 0 }} orders
              </div>
            </div>
          </div>

          <div class="overflow-x-auto">
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
                  <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Actions
                  </th>
                </tr>
              </thead>
              <tbody class="bg-white divide-y divide-gray-200">
                <tr v-if="pendingOrders?.data?.length > 0" v-for="order in pendingOrders.data" :key="order.id" class="hover:bg-gray-50">
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
                    <StatusBadge 
                      :status="order.status" 
                      :variant="getStatusVariant(order.status)"
                    >
                      {{ formatStatus(order.status) }}
                    </StatusBadge>
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
                    <StatusBadge 
                      :status="order.delivery_request?.payment_status" 
                      :variant="getPaymentStatusVariant(order.delivery_request?.payment_status)"
                      size="xs"
                    >
                      {{ formatStatus(order.delivery_request?.payment_status) }}
                    </StatusBadge>
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
                  <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                    <div class="flex justify-end space-x-2">
                      <PrimaryButton
                        @click="goToProcessOrder(order.id)"
                        size="xs"
                        class="flex items-center"
                      >
                        <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                        Process
                      </PrimaryButton>
                      <SecondaryButton
                        v-if="hasRefund(order) && getRefundId(order)"
                        @click="goToViewRefund(getRefundId(order))"
                        size="xs"
                        class="flex items-center"
                      >
                        <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                        </svg>
                        View Refund
                      </SecondaryButton>
                    </div>
                  </td>
                </tr>
                <tr v-else>
                  <td colspan="8" class="px-6 py-4 text-center text-sm text-gray-500">
                    <div class="text-center py-12">
                      <CheckCircleIcon class="h-12 w-12 mx-auto text-gray-400" />
                      <p class="text-gray-500 mt-2">No pending orders found</p>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Pagination for Pending -->
          <Pagination
            v-if="pendingOrders?.meta && pendingOrders.meta.last_page > 1"
            :pagination="pendingOrders.meta"
            @page-changed="(page) => handlePageChange(page, 'pending')"
            class="px-4 py-3"
          />
        </div>
      </div>

      <!-- Completed Orders -->
      <div v-else-if="activeTab === 'completed'">
<div class="bg-white shadow-sm rounded-lg overflow-hidden border border-gray-200 w-full">          <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
            <div class="flex justify-between items-center">
              <div>
                <h3 class="text-lg font-medium text-gray-900">
                  Completed Orders
                </h3>
                <p class="mt-1 text-sm text-gray-500">
                  Successfully processed deliveries
                </p>
              </div>
              <div class="text-sm text-gray-500">
                Showing {{ completedOrders?.data?.length || 0 }} orders
              </div>
            </div>
          </div>

          <div class="overflow-x-auto">
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
                <tr v-if="completedOrders?.data?.length > 0" v-for="order in completedOrders.data" :key="order.id" class="hover:bg-gray-50">
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
                    <StatusBadge 
                      :status="order.delivery_request?.payment_status" 
                      :variant="getPaymentStatusVariant(order.delivery_request?.payment_status)"
                    >
                      {{ formatStatus(order.delivery_request?.payment_status) }}
                    </StatusBadge>
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
                    <div class="text-center py-12">
                      <ArchiveBoxIcon class="h-12 w-12 mx-auto text-gray-400" />
                      <p class="text-gray-500 mt-2">No completed orders found</p>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Pagination for Completed -->
          <Pagination
            v-if="completedOrders?.meta && completedOrders.meta.last_page > 1"
            :pagination="completedOrders.meta"
            @page-changed="(page) => handlePageChange(page, 'completed')"
            class="px-4 py-3"
          />
        </div>
      </div>
    </div>
  </EmployeeLayout>
</template>

<script setup>
import EmployeeLayout from '@/Layouts/EmployeeLayout.vue'
import { Link, router } from '@inertiajs/vue3'
import { reactive, ref, computed, watch } from 'vue'
import {
  CheckCircleIcon,
  ArchiveBoxIcon,
} from '@heroicons/vue/24/outline'

import SearchInput from '@/Components/SearchInput.vue'
import SelectInput from '@/Components/SelectInput.vue'
import SecondaryButton from '@/Components/SecondaryButton.vue'
import PrimaryButton from '@/Components/PrimaryButton.vue'
import StatusBadge from '@/Components/StatusBadge.vue'
import Pagination from '@/Components/Pagination.vue'

const props = defineProps({
  pendingOrders: Object,
  completedOrders: Object,
  stats: Object,
  filters: Object
})

const activeTab = ref(props.filters?.tab || 'pending')

// Initialize filters from props
const filters = reactive({
  search: props.filters?.search || '',
  status: props.filters?.status || ''
})

// Computed options for filters
const statusOptions = computed(() => [
  { value: '', label: 'All Status' },
  { value: 'delivered', label: 'Delivered' },
  { value: 'needs_review', label: 'Needs Review' },
  { value: 'completed', label: 'Completed' }
])

// Methods
function getCurrentDataCount() {
  switch (activeTab.value) {
    case 'pending': return props.pendingOrders?.data?.length || 0
    case 'completed': return props.completedOrders?.data?.length || 0
    default: return 0
  }
}

function getItemName() {
  return activeTab.value === 'pending' ? 'pending orders' : 'completed orders'
}

function switchTab(tab) {
  activeTab.value = tab
  handleFilterChange()
}

function handleFilterChange() {
  const payload = {
    tab: activeTab.value,
    ...filters
  }
  
  // Remove empty filters
  Object.keys(payload).forEach(key => {
    if (payload[key] === '' || payload[key] === null) {
      delete payload[key]
    }
  })
  
  router.visit(route('delivery-completion.ready-for-completion'), {
    data: payload,
    preserveState: true,
    preserveScroll: true,
    replace: true
  })
}

// Debounced search
let searchTimeout = null
function handleDebouncedFilter() {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    if (filters.search === '' || filters.search.length >= 3) {
      handleFilterChange()
    }
  }, 500)
}

function resetFilters() {
  Object.keys(filters).forEach(key => {
    filters[key] = ''
  })
  handleFilterChange()
}

function refreshData() {
  router.reload({
    only: ['pendingOrders', 'completedOrders', 'stats'],
    preserveScroll: true
  })
}

function handlePageChange(page, type) {
  if (page >= 1 && page <= getLastPage(type)) {
    const payload = {
      tab: activeTab.value,
      ...filters
    }
    
    // Set the correct page parameter based on the type
    if (type === 'pending') {
      payload.pending_page = page
    } else if (type === 'completed') {
      payload.completed_page = page
    }
    
    // Remove empty filters
    Object.keys(payload).forEach(key => {
      if (payload[key] === '' || payload[key] === null) {
        delete payload[key]
      }
    })
    
    router.visit(route('delivery-completion.ready-for-completion'), {
      data: payload,
      preserveState: true,
      preserveScroll: true
    })
  }
}

function getLastPage(type) {
  switch (type) {
    case 'pending': return props.pendingOrders?.meta?.last_page || 1
    case 'completed': return props.completedOrders?.meta?.last_page || 1
    default: return 1
  }
}

// Helper methods for filter labels
function getStatusLabel(status) {
  const labels = {
    'delivered': 'Delivered',
    'needs_review': 'Needs Review', 
    'completed': 'Completed'
  }
  return labels[status] || status
}

// Navigation methods
function goToProcessOrder(orderId) {
  router.visit(route('delivery-completion.show-form', { order: orderId }))
}

function goToViewRefund(refundId) {
  router.visit(route('refunds.show', { refund: refundId }))
}

// Status formatting and coloring methods
function formatStatus(status) {
  if (!status) return 'UNKNOWN'
  return status.split('_').map(word => 
    word.charAt(0).toUpperCase() + word.slice(1)
  ).join(' ')
}

// Status badge variants for delivery order statuses
function getStatusVariant(status) {
  const variants = {
    'delivered': 'success',
    'needs_review': 'warning',
    'completed': 'info',
    'partially_delivered': 'warning',
    'pending': 'warning',
    'in_transit': 'info',
    'cancelled': 'danger'
  }
  return variants[status] || 'secondary'
}

// Status badge variants for payment statuses
function getPaymentStatusVariant(status) {
  const variants = {
    'paid': 'success',
    'unpaid': 'danger',
    'pending': 'warning',
    'awaiting_payment': 'warning',
    'refunded': 'info',
    'requires_adjustment': 'warning',
    'cancelled': 'danger',
    'verified': 'success',
    'rejected': 'danger',
    'pending_verification': 'warning'
  }
  return variants[status] || 'secondary'
}

// Original helper functions from the component
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

function refundStatusClass(order) {
  const status = getRefundStatus(order).toLowerCase()
  const classes = {
    'pending': 'bg-yellow-100 text-yellow-800',
    'processed': 'bg-green-100 text-green-800',
    'adjusted': 'bg-blue-100 text-blue-800'
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

function getCompletedDate(order) {
  return order.completed_at || order.updated_at || order.created_at
}
</script>