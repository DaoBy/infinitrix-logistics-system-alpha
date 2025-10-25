[file name]: Edit.vue
[file content begin]
<template>
  <EmployeeLayout>
    <template #header>
      <div class="flex justify-between items-center px-4 sm:px-6">
        <div class="min-w-0 flex-1">
          <h2 class="text-lg sm:text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200 truncate">
            {{ refund.type === 'adjustment' ? 'Process Invoice Adjustment' : 'Process Refund' }}
          </h2>
          <p class="mt-1 text-sm text-gray-600 dark:text-gray-400 hidden sm:block">
            {{ refund.type === 'adjustment' ? 'Negotiate and apply invoice adjustment.' : 'Negotiate and process refund with customer.' }}
          </p>
        </div>
        <SecondaryButton 
          @click="$inertia.visit(route('refunds.index'))"
          class="inline-flex items-center text-sm whitespace-nowrap shrink-0 ml-2"
        >
          <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1 sm:mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
          </svg>
          <span class="hidden sm:inline">Back to Refunds</span>
          <span class="sm:hidden">Back</span>
        </SecondaryButton>
      </div>
    </template>

    <div class="px-4 md:px-6 py-4 max-w-7xl mx-auto">
      <!-- DEBUG INFO - Remove in production -->
      <div v-if="showDebug" class="mb-4 p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
        <h3 class="text-sm font-medium text-yellow-800">Debug Information</h3>
        <pre class="text-xs mt-2">{{ debugInfo }}</pre>
      </div>

      <!-- MAIN CONTENT GRID -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- LEFT COLUMN: Refund Information & Form -->
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
                  <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-800 dark:text-yellow-100">
                    {{ refund.type === 'adjustment' ? 'Pending Adjustment' : 'Pending Refund' }}
                  </span>
                </div>
                <div class="flex flex-wrap items-center gap-4 text-xs text-gray-600 dark:text-gray-300">
                  <span>Type: {{ refund.type_label }}</span>
                  <span>Sender: {{ refund.delivery_request?.sender?.name }}</span>
                </div>
              </div>
              <div class="mt-3 md:mt-0">
                <div class="text-2xl font-bold text-indigo-900 dark:text-indigo-100 text-center md:text-right">
                  ₱{{ Number(refund.original_amount).toFixed(2) }}
                </div>
                <div class="text-xs text-gray-600 dark:text-gray-300 text-center md:text-right">
                  Original Amount
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

                <!-- FINANCIAL DETAILS - UPDATED -->
                <div class="bg-gray-50 dark:bg-gray-700/50 p-3 rounded-lg">
                  <h4 class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-3 flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"/>
                    </svg>
                    Financial Details
                  </h4>
                  <div class="space-y-2 text-sm">
                    <div class="flex justify-between">
                      <span class="text-gray-600 dark:text-gray-400">Delivery Fee:</span>
                      <span class="font-medium text-gray-900 dark:text-gray-100">₱{{ deliveryFee }}</span>
                    </div>
                    <div class="flex justify-between">
                      <span class="text-gray-600 dark:text-gray-400">Affected Package Values:</span>
                      <span class="font-medium text-gray-900 dark:text-gray-100">₱{{ affectedPackageValues.toFixed(2) }}</span>
                    </div>
                    <div class="flex justify-between">
                      <span class="text-gray-600 dark:text-gray-400">Total Package Values:</span>
                      <span class="font-medium text-gray-500 dark:text-gray-400 text-xs">₱{{ totalPackageValues.toFixed(2) }}</span>
                    </div>
                    <div class="flex justify-between border-t border-gray-200 dark:border-gray-600 pt-2">
                      <span class="text-gray-600 dark:text-gray-400 font-semibold">Max {{ refund.type === 'adjustment' ? 'Adjustable' : 'Refundable' }}:</span>
                      <span class="font-bold text-green-600 dark:text-green-400">₱{{ maxRefundable }}</span>
                    </div>
                    <div v-if="refund.type === 'adjustment'" class="flex justify-between">
                      <span class="text-gray-600 dark:text-gray-400">New Amount Due:</span>
                      <span class="font-medium text-blue-600 dark:text-blue-400">₱{{ newAmountDue }}</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- NEGOTIATION FORM -->
          <form @submit.prevent="submit">
            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg border border-gray-200 dark:border-gray-700">
              <div class="p-4 bg-blue-50 dark:bg-blue-900/20 border-b border-blue-200 dark:border-blue-800">
                <h3 class="font-medium text-blue-900 dark:text-blue-100 flex items-center gap-2">
                  <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/>
                  </svg>
                  {{ refund.type === 'adjustment' ? 'Adjustment Negotiation' : 'Refund Negotiation' }}
                </h3>
              </div>
              <div class="p-4 md:p-6">
                <!-- Amount -->
                <div class="mb-6">
                  <label for="refund_amount" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
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
                      class="block w-full pl-7 pr-12 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white sm:text-sm"
                      placeholder="0.00"
                      @input="calculateNewAmount"
                    />
                  </div>
                  <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Maximum {{ refund.type === 'adjustment' ? 'adjustable' : 'refundable' }} amount: ₱{{ maxRefundable }}
                    <span class="text-xs text-gray-400">(Delivery Fee: ₱{{ deliveryFee }} + Affected Packages: ₱{{ affectedPackageValues.toFixed(2) }})</span>
                  </p>
                  <p v-if="refund.type === 'adjustment'" class="mt-1 text-sm text-blue-600 dark:text-blue-400">
                    New amount due: ₱{{ newAmountDue }}
                  </p>
                  <p v-if="form.errors.refund_amount" class="mt-1 text-sm text-red-600 dark:text-red-400">
                    {{ form.errors.refund_amount }}
                  </p>
                </div>

                <!-- Reason -->
                <div class="mb-6">
                  <label for="reason" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    Reason *
                  </label>
                  <select
                    v-model="form.reason"
                    id="reason"
                    class="mt-1 block w-full border border-gray-300 dark:border-gray-600 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white sm:text-sm"
                  >
                    <option value="">Select a reason</option>
                    <option v-for="(label, value) in reasonOptions" :key="value" :value="value">
                      {{ label }}
                    </option>
                  </select>
                  <p v-if="form.errors.reason" class="mt-1 text-sm text-red-600 dark:text-red-400">
                    {{ form.errors.reason }}
                  </p>
                </div>

                <!-- Description -->
                <div class="mb-6">
                  <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    Description *
                  </label>
                  <textarea
                    v-model="form.description"
                    id="description"
                    rows="3"
                    class="mt-1 block w-full border border-gray-300 dark:border-gray-600 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white sm:text-sm"
                    :placeholder="refund.type === 'adjustment' ? 'Describe the adjustment reason and negotiation details...' : 'Describe the refund reason and negotiation details...'"
                  ></textarea>
                  <p v-if="form.errors.description" class="mt-1 text-sm text-red-600 dark:text-red-400">
                    {{ form.errors.description }}
                  </p>
                </div>

                <!-- Notes -->
                <div class="mb-6">
                  <label for="notes" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    Internal Notes
                  </label>
                  <textarea
                    v-model="form.notes"
                    id="notes"
                    rows="2"
                    class="mt-1 block w-full border border-gray-300 dark:border-gray-600 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white sm:text-sm"
                    :placeholder="refund.type === 'adjustment' ? 'Internal notes about the adjustment...' : 'Internal notes about the negotiation...'"
                  ></textarea>
                </div>

                <!-- Action Selection -->
                <div class="mb-6">
                  <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                    Action
                  </label>
                  <div class="space-y-2">
                    <div class="flex items-center">
                      <input
                        v-model="form.action"
                        id="update_pending"
                        value="update_pending"
                        type="radio"
                        class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 dark:border-gray-600 dark:bg-gray-700"
                      />
                      <label for="update_pending" class="ml-3 block text-sm font-medium text-gray-700 dark:text-gray-300">
                        Update negotiation details (keep as pending)
                      </label>
                    </div>
                    <div class="flex items-center">
                      <input
                        v-model="form.action"
                        id="process"
                        value="process"
                        type="radio"
                        class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 dark:border-gray-600 dark:bg-gray-700"
                      />
                      <label for="process" class="ml-3 block text-sm font-medium text-gray-700 dark:text-gray-300">
                        {{ refund.type === 'adjustment' ? 'Apply adjustment immediately' : 'Process refund immediately' }}
                      </label>
                    </div>
                  </div>
                  <p v-if="form.errors.action" class="mt-1 text-sm text-red-600 dark:text-red-400">
                    {{ form.errors.action }}
                  </p>
                </div>
              </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex justify-end space-x-3 mt-6">
              <SecondaryButton
                @click="$inertia.visit(route('refunds.show', { refund: refund.id }))"
                class="inline-flex items-center"
              >
                Cancel
              </SecondaryButton>
              <PrimaryButton
                type="submit"
                :disabled="form.processing"
                class="inline-flex items-center"
              >
                <span v-if="form.processing">
                  <LoadingSpinner size="xs" class="mr-2" />
                  Processing...
                </span>
                <span v-else>
                  {{ form.action === 'process' ? (refund.type === 'adjustment' ? 'Apply Adjustment' : 'Process Refund') : 'Update Details' }}
                </span>
              </PrimaryButton>
            </div>
          </form>
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
          <div v-if="affectedPackages.length > 0" class="bg-white dark:bg-gray-800 shadow-sm rounded-lg border border-gray-200 dark:border-gray-700">
            <div class="p-4 bg-gray-50 dark:bg-gray-700 border-b border-gray-200 dark:border-gray-700">
              <h3 class="font-medium text-gray-900 dark:text-gray-100 flex items-center gap-2">
                <svg class="w-5 h-5 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                </svg>
                Affected Packages ({{ affectedPackages.length }})
              </h3>
            </div>
            <div class="p-4">
              <div class="space-y-3">
                <div 
                  v-for="pkg in affectedPackages" 
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

          <!-- ALL PACKAGES (for reference) -->
          <div v-if="allPackages.length > 0" class="bg-white dark:bg-gray-800 shadow-sm rounded-lg border border-gray-200 dark:border-gray-700">
            <div class="p-4 bg-gray-50 dark:bg-gray-700 border-b border-gray-200 dark:border-gray-700">
              <h3 class="font-medium text-gray-900 dark:text-gray-100 flex items-center gap-2">
                <svg class="w-5 h-5 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                </svg>
                All Packages ({{ allPackages.length }})
              </h3>
            </div>
            <div class="p-4">
              <div class="space-y-2">
                <div 
                  v-for="pkg in allPackages" 
                  :key="pkg.id"
                  class="text-xs text-gray-600 dark:text-gray-400 p-2 rounded border"
                  :class="pkg.isAffected ? 'bg-red-50 border-red-200 dark:bg-red-900/20 dark:border-red-800' : 'bg-gray-50 border-gray-200 dark:bg-gray-700/50 dark:border-gray-600'"
                >
                  <div class="flex justify-between">
                    <span class="font-medium">{{ pkg.item_name }}</span>
                    <span :class="pkg.isAffected ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400'">
                      ₱{{ pkg.value }}
                    </span>
                  </div>
                  <div class="flex justify-between text-xs">
                    <span>{{ pkg.status }}</span>
                    <span v-if="pkg.isAffected" class="text-red-600 dark:text-red-400">● Affected</span>
                    <span v-else class="text-green-600 dark:text-green-400">● OK</span>
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
                  type="button"
                  @click="form.action = 'process'; submit()"
                  :disabled="form.processing"
                  class="w-full text-left px-3 py-2 text-sm bg-green-50 hover:bg-green-100 dark:bg-green-900/20 dark:hover:bg-green-900/30 text-green-700 dark:text-green-300 rounded border border-green-200 dark:border-green-800 transition-colors"
                >
                  {{ refund.type === 'adjustment' ? 'Apply Full Adjustment' : 'Process Full Refund' }}
                </button>
                <button
                  type="button"
                  @click="$inertia.visit(route('refunds.show', { refund: refund.id }))"
                  class="w-full text-left px-3 py-2 text-sm bg-blue-50 hover:bg-blue-100 dark:bg-blue-900/20 dark:hover:bg-blue-900/30 text-blue-700 dark:text-blue-300 rounded border border-blue-200 dark:border-blue-800 transition-colors"
                >
                  View Details
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
import LoadingSpinner from '@/Components/LoadingSpinner.vue'
import { useForm } from '@inertiajs/vue3'
import { ref, computed, onMounted } from 'vue'

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

const showDebug = ref(true) // Set to false in production

// Calculate breakdown for display
const deliveryFee = computed(() => {
  return props.refund.original_amount || 0
})

// Get all packages from delivery request
const allPackages = computed(() => {
  if (!props.refund.delivery_request?.packages) return []
  
  return props.refund.delivery_request.packages.map(pkg => ({
    ...pkg,
    isAffected: props.refund.refunded_packages?.includes(pkg.id) || false
  }))
})

// Get only affected packages (those in refunded_packages array)
const affectedPackages = computed(() => {
  return allPackages.value.filter(pkg => pkg.isAffected)
})

// Calculate values for affected packages only
const affectedPackageValues = computed(() => {
  return affectedPackages.value.reduce((sum, pkg) => sum + (parseFloat(pkg.value) || 0), 0)
})

// Calculate total values for all packages (for reference)
const totalPackageValues = computed(() => {
  return allPackages.value.reduce((sum, pkg) => sum + (parseFloat(pkg.value) || 0), 0)
})

// Calculate new amount due for adjustments
const newAmountDue = computed(() => {
  if (props.refund.type === 'adjustment') {
    const adjustmentAmount = Number(form.refund_amount) || 0
    return Math.max(0, props.refund.original_amount - adjustmentAmount)
  }
  return 0
})

// Extract real photos from AFFECTED packages only
const originalPhotos = computed(() => {
  const photos = []
  affectedPackages.value.forEach(pkg => {
    if (pkg.photo_url && Array.isArray(pkg.photo_url)) {
      photos.push(...pkg.photo_url)
    }
  })
  return photos.slice(0, 4) // Limit to 4 photos
})

const incidentEvidence = computed(() => {
  const evidence = []
  affectedPackages.value.forEach(pkg => {
    if (pkg.incident_evidence && Array.isArray(pkg.incident_evidence)) {
      // Convert storage paths to URLs
      const evidenceUrls = pkg.incident_evidence.map(path => {
        return path.startsWith('http') ? path : `/storage/${path}`
      })
      evidence.push(...evidenceUrls)
    }
  })
  return evidence.slice(0, 4) // Limit to 4 photos
})

const hasIncidentDetails = computed(() => {
  return affectedPackages.value.some(pkg => 
    pkg.incident_description || pkg.incident_reported_at
  )
})

const incidentDescription = computed(() => {
  const pkg = affectedPackages.value.find(pkg => pkg.incident_description)
  return pkg?.incident_description || 'No incident description provided'
})

const incidentReportedAt = computed(() => {
  const pkg = affectedPackages.value.find(pkg => pkg.incident_reported_at)
  return pkg?.incident_reported_at
})

// Debug information
const debugInfo = computed(() => {
  return {
    refund_id: props.refund.id,
    original_amount: props.refund.original_amount,
    maxRefundable_from_props: props.maxRefundable,
    delivery_fee: deliveryFee.value,
    affected_package_values: affectedPackageValues.value,
    total_package_values: totalPackageValues.value,
    calculated_total: deliveryFee.value + affectedPackageValues.value,
    refunded_packages: props.refund.refunded_packages,
    affected_packages_count: affectedPackages.value.length,
    all_packages_count: allPackages.value.length
  }
})

function calculateNewAmount() {
  // This will trigger the computed property update
}

function submit() {
  form.put(route('refunds.update', { refund: props.refund.id }))
}

function openImageModal(imageUrl) {
  window.open(imageUrl, '_blank')
}

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

function packageStatusBadgeClass(status) {
  const classes = {
    'damaged_in_transit': 'bg-red-100 text-red-800 dark:bg-red-800 dark:text-red-100',
    'lost_in_transit': 'bg-red-100 text-red-800 dark:bg-red-800 dark:text-red-100',
    'delivered': 'bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100',
    'completed': 'bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100'
  }
  return classes[status] || 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-100'
}

// Debug logging
onMounted(() => {
  console.log('Refund Edit Debug:', debugInfo.value)
  console.log('Affected Packages:', affectedPackages.value)
  console.log('All Packages:', allPackages.value)
})
</script>
[file content end]