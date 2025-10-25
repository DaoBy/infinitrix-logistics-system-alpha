[file name]: Show.vue
[file content begin]
<template>
  <EmployeeLayout>
    <template #header>
      <div class="flex justify-between items-center px-4 sm:px-6">
        <div class="min-w-0 flex-1">
          <h2 class="text-lg sm:text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200 truncate">
            {{ refund.type === 'adjustment' ? 'Adjustment Details' : 'Refund Details' }}
          </h2>
          <p class="mt-1 text-sm text-gray-600 dark:text-gray-400 hidden sm:block">
            {{ refund.type === 'adjustment' ? 'Invoice adjustment information.' : 'Refund information and processing.' }}
          </p>
        </div>
        <div class="flex items-center space-x-2">
          <SecondaryButton 
            @click="$inertia.visit(route('refunds.index'))"
            class="inline-flex items-center text-sm whitespace-nowrap shrink-0"
          >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1 sm:mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            <span class="hidden sm:inline">Back to List</span>
            <span class="sm:hidden">Back</span>
          </SecondaryButton>
          <PrimaryButton
            v-if="refund.status === 'pending' || refund.status === 'pending_adjustment'"
            @click="$inertia.visit(route('refunds.edit', { refund: refund.id }))"
            class="inline-flex items-center"
          >
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
            </svg>
            {{ refund.type === 'adjustment' ? 'Process Adjustment' : 'Process Refund' }}
          </PrimaryButton>
        </div>
      </div>
    </template>

    <div class="px-4 md:px-6 py-4 max-w-7xl mx-auto">
      <!-- MAIN CONTENT GRID -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- LEFT COLUMN: Refund Information & Details -->
        <div class="lg:col-span-2 space-y-6">
          <!-- REFUND OVERVIEW CARD -->
          <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg border border-gray-200 dark:border-gray-700">
            <!-- Header -->
            <div class="flex flex-col md:flex-row md:items-center md:justify-between p-4 bg-indigo-50 dark:bg-indigo-900/20 border-b border-indigo-200 dark:border-indigo-800">
              <div class="flex-1">
                <div class="flex flex-wrap items-center gap-2 mb-2">
                  <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-indigo-100 text-indigo-800 dark:bg-indigo-800 dark:text-indigo-100 border border-indigo-200 dark:border-indigo-700">
                    Reference#
                  </span>
                  <span class="text-lg font-bold text-indigo-600 dark:text-indigo-400 tracking-wide">
                    {{ refund.delivery_request?.reference_number }}
                  </span>
                  <span :class="statusBadgeClass(refund.status)" class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium">
                    {{ refund.status_label }}
                  </span>
                  <span :class="typeBadgeClass(refund.type)" class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium">
                    {{ refund.type_label }}
                  </span>
                </div>
                <div class="flex flex-wrap items-center gap-4 text-xs text-gray-600 dark:text-gray-300">
                  <span>Delivery ID: DO-{{ String(refund.delivery_request_id).padStart(6, '0') }}</span>
                  <span v-if="refund.processed_at">{{ refund.type === 'adjustment' ? 'Adjusted' : 'Processed' }}: {{ formatDate(refund.processed_at) }}</span>
                </div>
              </div>
              <div class="mt-3 md:mt-0">
                <div class="text-2xl font-bold text-indigo-900 dark:text-indigo-100 text-center md:text-right">
                  ₱{{ Number(refund.refund_amount).toFixed(2) }}
                </div>
                <div class="text-xs text-gray-600 dark:text-gray-300 text-center md:text-right">
                  {{ refund.type === 'adjustment' ? 'Adjustment Amount' : 'Refund Amount' }}
                </div>
              </div>
            </div>

            <!-- Details -->
            <div class="p-4 md:p-6">
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                <div class="bg-gray-50 dark:bg-gray-700/50 p-3 rounded-lg">
                  <h4 class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-3 flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Delivery Information
                  </h4>
                  <div class="space-y-2 text-sm">
                    <div class="flex justify-between">
                      <span class="text-gray-600 dark:text-gray-400">Reference:</span>
                      <span class="font-medium text-gray-900 dark:text-gray-100">{{ refund.delivery_request?.reference_number }}</span>
                    </div>
                    <div class="flex justify-between">
                      <span class="text-gray-600 dark:text-gray-400">Payment Type:</span>
                      <span class="capitalize font-medium text-gray-900 dark:text-gray-100">
                        {{ refund.delivery_request?.payment_type }}
                      </span>
                    </div>
                    <div class="flex justify-between">
                      <span class="text-gray-600 dark:text-gray-400">Sender:</span>
                      <span class="font-medium text-gray-900 dark:text-gray-100">
                        {{ refund.delivery_request?.sender?.name }}
                      </span>
                    </div>
                    <div class="flex justify-between">
                      <span class="text-gray-600 dark:text-gray-400">Receiver:</span>
                      <span class="font-medium text-gray-900 dark:text-gray-100">
                        {{ refund.delivery_request?.receiver?.name }}
                      </span>
                    </div>
                  </div>
                </div>

                <div class="bg-gray-50 dark:bg-gray-700/50 p-3 rounded-lg">
                  <h4 class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-3 flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"/>
                    </svg>
                    Financial Details
                  </h4>
                  <div class="space-y-2 text-sm">
                    <div class="flex justify-between">
                      <span class="text-gray-600 dark:text-gray-400">Original Amount:</span>
                      <span class="font-medium text-gray-900 dark:text-gray-100">₱{{ refund.original_amount }}</span>
                    </div>
                    <div class="flex justify-between">
                      <span class="text-gray-600 dark:text-gray-400">{{ refund.type === 'adjustment' ? 'Adjustment' : 'Refund' }} Amount:</span>
                      <span class="font-medium text-gray-900 dark:text-gray-100">₱{{ refund.refund_amount }}</span>
                    </div>
                    <div v-if="refund.type === 'adjustment'" class="flex justify-between">
                      <span class="text-gray-600 dark:text-gray-400">New Amount Due:</span>
                      <span class="font-medium text-green-600 dark:text-green-400">₱{{ newAmountDue }}</span>
                    </div>
                    <div v-if="refund.processed_at" class="flex justify-between">
                      <span class="text-gray-600 dark:text-gray-400">{{ refund.type === 'adjustment' ? 'Adjusted' : 'Processed' }} Date:</span>
                      <span class="font-medium text-gray-900 dark:text-gray-100">{{ formatDate(refund.processed_at) }}</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- REFUND DETAILS CARD -->
          <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg border border-gray-200 dark:border-gray-700">
            <div class="p-4 bg-blue-50 dark:bg-blue-900/20 border-b border-blue-200 dark:border-blue-800">
              <h3 class="font-medium text-blue-900 dark:text-blue-100 flex items-center gap-2">
                <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                {{ refund.type === 'adjustment' ? 'Adjustment Details' : 'Refund Details' }}
              </h3>
            </div>
            <div class="p-4 md:p-6">
              <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Reason & Description -->
                <div class="space-y-4">
                  <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                      Reason
                    </label>
                    <div class="text-sm text-gray-900 dark:text-gray-100 bg-gray-50 dark:bg-gray-700/50 p-3 rounded border border-gray-200 dark:border-gray-600">
                      {{ refund.reason_label || 'Not specified' }}
                    </div>
                  </div>

                  <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                      Description
                    </label>
                    <div class="text-sm text-gray-900 dark:text-gray-100 bg-gray-50 dark:bg-gray-700/50 p-3 rounded border border-gray-200 dark:border-gray-600 min-h-[80px]">
                      {{ refund.description || 'No description provided' }}
                    </div>
                  </div>
                </div>

                <!-- Internal Notes & Timeline -->
                <div class="space-y-4">
                  <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                      Internal Notes
                    </label>
                    <div class="text-sm text-gray-900 dark:text-gray-100 bg-gray-50 dark:bg-gray-700/50 p-3 rounded border border-gray-200 dark:border-gray-600 min-h-[80px]">
                      {{ refund.notes || 'No internal notes' }}
                    </div>
                  </div>

                  <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                      Timeline
                    </label>
                    <div class="space-y-2 text-sm">
                      <div class="flex justify-between">
                        <span class="text-gray-600 dark:text-gray-400">Created:</span>
                        <span class="font-medium text-gray-900 dark:text-gray-100">{{ formatDate(refund.created_at) }}</span>
                      </div>
                      <div v-if="refund.updated_at" class="flex justify-between">
                        <span class="text-gray-600 dark:text-gray-400">Last Updated:</span>
                        <span class="font-medium text-gray-900 dark:text-gray-100">{{ formatDate(refund.updated_at) }}</span>
                      </div>
                      <div v-if="refund.processed_at" class="flex justify-between">
                        <span class="text-gray-600 dark:text-gray-400">{{ refund.type === 'adjustment' ? 'Adjusted' : 'Processed' }}:</span>
                        <span class="font-medium text-green-600 dark:text-green-400">{{ formatDate(refund.processed_at) }}</span>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- RIGHT COLUMN: Evidence & Packages -->
        <div class="lg:col-span-1 space-y-6">
          <!-- EVIDENCE COMPARISON -->
          <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg border border-gray-200 dark:border-gray-700">
            <div class="p-4 bg-gray-50 dark:bg-gray-700 border-b border-gray-200 dark:border-gray-700">
              <h3 class="font-medium text-gray-900 dark:text-gray-100 flex items-center gap-2">
                <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
                Evidence Comparison
              </h3>
            </div>
            <div class="p-4">
              <!-- Original Package Photos -->
              <div class="mb-4">
                <h4 class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-2">Original Package Photos</h4>
                <div v-if="originalPhotos.length > 0" class="grid grid-cols-2 gap-2">
                  <div 
                    v-for="(photo, index) in originalPhotos" 
                    :key="index"
                    class="relative"
                  >
                    <img 
                      :src="photo" 
                      :alt="`Original package photo ${index + 1}`" 
                      class="w-full h-20 object-cover rounded border border-gray-200 dark:border-gray-600 cursor-pointer hover:opacity-80 transition-opacity"
                      @click="openImageModal(photo)"
                    />
                  </div>
                </div>
                <div v-else class="text-center py-4 bg-gray-50 dark:bg-gray-700/50 rounded">
                  <svg class="mx-auto h-8 w-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                  </svg>
                  <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">No original photos</p>
                </div>
              </div>

              <!-- Incident Evidence Photos -->
              <div>
                <h4 class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-2">Incident Evidence</h4>
                <div v-if="incidentEvidence.length > 0" class="grid grid-cols-2 gap-2">
                  <div 
                    v-for="(photo, index) in incidentEvidence" 
                    :key="index"
                    class="relative"
                  >
                    <img 
                      :src="photo" 
                      :alt="`Incident evidence ${index + 1}`" 
                      class="w-full h-20 object-cover rounded border border-gray-200 dark:border-gray-600 cursor-pointer hover:opacity-80 transition-opacity"
                      @click="openImageModal(photo)"
                    />
                  </div>
                </div>
                <div v-else class="text-center py-4 bg-gray-50 dark:bg-gray-700/50 rounded">
                  <svg class="mx-auto h-8 w-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                  </svg>
                  <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">No incident evidence</p>
                </div>
              </div>

              <!-- Incident Details -->
              <div v-if="hasIncidentDetails" class="mt-4 p-3 bg-yellow-50 dark:bg-yellow-900/20 rounded border border-yellow-200 dark:border-yellow-800">
                <h4 class="text-sm font-medium text-yellow-800 dark:text-yellow-200 mb-2">Incident Report</h4>
                <p class="text-xs text-yellow-700 dark:text-yellow-300 mb-2">
                  {{ incidentDescription }}
                </p>
                <div class="text-xs text-yellow-600 dark:text-yellow-400">
                  Reported: {{ formatDate(incidentReportedAt) }}
                </div>
              </div>
            </div>
          </div>

          <!-- AFFECTED PACKAGES -->
          <div v-if="refund.refunded_packages_list && refund.refunded_packages_list.length > 0" class="bg-white dark:bg-gray-800 shadow-sm rounded-lg border border-gray-200 dark:border-gray-700">
            <div class="p-4 bg-gray-50 dark:bg-gray-700 border-b border-gray-200 dark:border-gray-700">
              <h3 class="font-medium text-gray-900 dark:text-gray-100 flex items-center gap-2">
                <svg class="w-5 h-5 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                </svg>
                Affected Packages ({{ refund.refunded_packages_list.length }})
              </h3>
            </div>
            <div class="p-4">
              <div class="space-y-3">
                <div 
                  v-for="pkg in refund.refunded_packages_list" 
                  :key="pkg.id"
                  class="bg-gray-50 dark:bg-gray-700/50 p-3 rounded-lg border border-gray-200 dark:border-gray-600"
                >
                  <div class="flex justify-between items-start mb-2">
                    <h4 class="text-sm font-medium text-gray-900 dark:text-gray-100 truncate">
                      {{ pkg.item_name || 'Unnamed Package' }}
                    </h4>
                    <span :class="packageStatusBadgeClass(pkg.status)" class="inline-flex px-2 text-xs font-semibold leading-5 rounded-full">
                      {{ pkg.status }}
                    </span>
                  </div>
                  <div class="text-xs text-gray-600 dark:text-gray-400 space-y-1">
                    <div class="flex justify-between">
                      <span>Value:</span>
                      <span class="font-medium">₱{{ pkg.value || '0.00' }}</span>
                    </div>
                    <div class="flex justify-between">
                      <span>Weight:</span>
                      <span>{{ pkg.weight }} kg</span>
                    </div>
                    <div v-if="pkg.incident_description" class="text-red-600 dark:text-red-400 text-xs mt-1">
                      {{ pkg.incident_description }}
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- QUICK ACTIONS -->
          <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg border border-gray-200 dark:border-gray-700">
            <div class="p-4 bg-gray-50 dark:bg-gray-700 border-b border-gray-200 dark:border-gray-700">
              <h3 class="font-medium text-gray-900 dark:text-gray-100 flex items-center gap-2">
                <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                </svg>
                Quick Actions
              </h3>
            </div>
            <div class="p-4">
              <div class="space-y-2">
                <button
                  v-if="refund.status === 'pending' || refund.status === 'pending_adjustment'"
                  @click="$inertia.visit(route('refunds.edit', { refund: refund.id }))"
                  class="w-full text-left px-3 py-2 text-sm bg-green-50 hover:bg-green-100 dark:bg-green-900/20 dark:hover:bg-green-900/30 text-green-700 dark:text-green-300 rounded border border-green-200 dark:border-green-800 transition-colors"
                >
                  {{ refund.type === 'adjustment' ? 'Process Adjustment' : 'Process Refund' }}
                </button>
                <button
                  @click="$inertia.visit(route('refunds.index'))"
                  class="w-full text-left px-3 py-2 text-sm bg-blue-50 hover:bg-blue-100 dark:bg-blue-900/20 dark:hover:bg-blue-900/30 text-blue-700 dark:text-blue-300 rounded border border-blue-200 dark:border-blue-800 transition-colors"
                >
                  Back to List
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </EmployeeLayout>
</template>

<script setup>
import EmployeeLayout from '@/Layouts/EmployeeLayout.vue'
import PrimaryButton from '@/Components/PrimaryButton.vue'
import SecondaryButton from '@/Components/SecondaryButton.vue'
import { computed } from 'vue'

const props = defineProps({
  refund: Object
})

// Calculate new amount due for adjustments
const newAmountDue = computed(() => {
  if (props.refund.type === 'adjustment') {
    return Math.max(0, props.refund.original_amount - props.refund.refund_amount)
  }
  return 0
})

// Extract real photos from packages
const originalPhotos = computed(() => {
  const photos = []
  if (props.refund.refunded_packages_list) {
    props.refund.refunded_packages_list.forEach(pkg => {
      if (pkg.photo_url && Array.isArray(pkg.photo_url)) {
        photos.push(...pkg.photo_url)
      }
    })
  }
  return photos.slice(0, 4) // Limit to 4 photos
})

const incidentEvidence = computed(() => {
  const evidence = []
  if (props.refund.refunded_packages_list) {
    props.refund.refunded_packages_list.forEach(pkg => {
      if (pkg.incident_evidence && Array.isArray(pkg.incident_evidence)) {
        // Convert storage paths to URLs
        const evidenceUrls = pkg.incident_evidence.map(path => {
          return path.startsWith('http') ? path : `/storage/${path}`
        })
        evidence.push(...evidenceUrls)
      }
    })
  }
  return evidence.slice(0, 4) // Limit to 4 photos
})

const hasIncidentDetails = computed(() => {
  return props.refund.refunded_packages_list?.some(pkg => 
    pkg.incident_description || pkg.incident_reported_at
  )
})

const incidentDescription = computed(() => {
  const pkg = props.refund.refunded_packages_list?.find(pkg => pkg.incident_description)
  return pkg?.incident_description || 'No incident description provided'
})

const incidentReportedAt = computed(() => {
  const pkg = props.refund.refunded_packages_list?.find(pkg => pkg.incident_reported_at)
  return pkg?.incident_reported_at
})

function formatDate(dateString) {
  if (!dateString) return 'N/A'
  const date = new Date(dateString)
  return date.toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

function statusBadgeClass(status) {
  const classes = {
    'pending': 'bg-yellow-100 text-yellow-800 dark:bg-yellow-800 dark:text-yellow-100',
    'pending_adjustment': 'bg-yellow-100 text-yellow-800 dark:bg-yellow-800 dark:text-yellow-100',
    'processed': 'bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100',
    'adjusted': 'bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100',
    'rejected': 'bg-red-100 text-red-800 dark:bg-red-800 dark:text-red-100'
  }
  return classes[status] || 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-100'
}

function typeBadgeClass(type) {
  const classes = {
    'refund': 'bg-blue-100 text-blue-800 dark:bg-blue-800 dark:text-blue-100',
    'adjustment': 'bg-purple-100 text-purple-800 dark:bg-purple-800 dark:text-purple-100'
  }
  return classes[type] || 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-100'
}

function packageStatusBadgeClass(status) {
  const classes = {
    'damaged_in_transit': 'bg-red-100 text-red-800 dark:bg-red-800 dark:text-red-100',
    'lost_in_transit': 'bg-red-100 text-red-800 dark:bg-red-800 dark:text-red-100',
    'delivered': 'bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100',
    'completed': 'bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100'
  }
  return classes[status] || 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-100'
}

function openImageModal(imageUrl) {
  window.open(imageUrl, '_blank')
}
</script>
[file content end]