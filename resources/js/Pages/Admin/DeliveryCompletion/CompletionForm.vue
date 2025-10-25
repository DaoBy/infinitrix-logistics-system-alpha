<template>
  <EmployeeLayout>
    <template #header>
      <div class="flex justify-between items-center px-4 sm:px-6">
        <div class="min-w-0 flex-1">
          <h2 class="text-lg sm:text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200 truncate">
            Delivery Completion
          </h2>
          <p class="mt-1 text-sm text-gray-600 dark:text-gray-400 hidden sm:block">
            Finalize delivery process and confirm package handover
          </p>
        </div>
        <SecondaryButton 
          @click="$inertia.visit(route('delivery-completion.ready-for-completion'))"
          class="inline-flex items-center text-sm whitespace-nowrap shrink-0 ml-2"
        >
          <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1 sm:mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
          </svg>
          <span class="hidden sm:inline">Back to Deliveries</span>
          <span class="sm:hidden">Back</span>
        </SecondaryButton>
      </div>
    </template>

    <div class="px-4 md:px-6 py-4 max-w-7xl mx-auto">
      <!-- MAIN CONTENT GRID -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- LEFT COLUMN: Delivery Information -->
        <div class="lg:col-span-2 space-y-6">
          <!-- DELIVERY OVERVIEW CARD -->
          <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg border border-gray-200 dark:border-gray-700">
            <!-- Delivery Header -->
            <div class="flex flex-col md:flex-row md:items-center md:justify-between p-4 bg-blue-50 dark:bg-blue-900/20 border-b border-blue-200 dark:border-blue-800">
              <div class="flex-1">
                <div class="flex flex-wrap items-center gap-2 mb-2">
                  <span :class="statusBadgeClass(order.status)" class="inline-flex px-3 py-1 text-sm font-semibold leading-5 rounded-full">
                    {{ order.status.replace('_', ' ').toUpperCase() }}
                  </span>
                  <span class="text-lg font-bold text-blue-600 dark:text-blue-400 tracking-wide">
                    {{ order.delivery_request?.reference_number || 'N/A' }}
                  </span>
                </div>
                <div class="flex flex-wrap items-center gap-4 text-xs text-gray-600 dark:text-gray-300">
                  <span>Order ID: DO-{{ String(order.id).padStart(6, '0') }}</span>
                  <span v-if="order.created_at">Created: {{ formatDate(order.created_at) }}</span>
                  <span v-if="order.driver">Driver: {{ order.driver.name }}</span>
                </div>
              </div>
              <div class="mt-3 md:mt-0">
                <div class="text-2xl font-bold text-blue-900 dark:text-blue-100 text-center md:text-right">
                  {{ outcomeStats.total_packages }}
                </div>
                <div class="text-xs text-gray-600 dark:text-gray-300 text-center md:text-right">
                  Total Packages
                </div>
              </div>
            </div>

            <!-- Delivery Summary Stats -->
            <div class="p-4 md:p-6">
              <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                <div class="bg-green-50 dark:bg-green-900/20 p-4 rounded-lg text-center">
                  <div class="text-2xl font-bold text-green-600">{{ outcomeStats.delivered_count }}</div>
                  <div class="text-sm text-green-800 dark:text-green-300">Delivered</div>
                </div>
                <div class="bg-yellow-50 dark:bg-yellow-900/20 p-4 rounded-lg text-center" v-if="outcomeStats.damaged_count > 0">
                  <div class="text-2xl font-bold text-yellow-600">{{ outcomeStats.damaged_count }}</div>
                  <div class="text-sm text-yellow-800 dark:text-yellow-300">Damaged</div>
                </div>
                <div class="bg-red-50 dark:bg-red-900/20 p-4 rounded-lg text-center" v-if="outcomeStats.lost_count > 0">
                  <div class="text-2xl font-bold text-red-600">{{ outcomeStats.lost_count }}</div>
                  <div class="text-sm text-red-800 dark:text-red-300">Lost</div>
                </div>
                <div class="bg-blue-50 dark:bg-blue-900/20 p-4 rounded-lg text-center">
                  <div class="text-2xl font-bold text-blue-600 capitalize">{{ order.delivery_request?.payment_type }}</div>
                  <div class="text-sm text-blue-800 dark:text-blue-300">Payment Type</div>
                </div>
              </div>

              <!-- Process Guide -->
              <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
                <div class="flex">
                  <div class="flex-shrink-0">
                    <InformationCircleIcon class="h-5 w-5 text-blue-400" />
                  </div>
                  <div class="ml-3">
                    <h3 class="text-sm font-medium text-blue-800 dark:text-blue-300">
                      Process Guide
                    </h3>
                    <div class="mt-2 text-sm text-blue-700 dark:text-blue-400">
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
            </div>
          </div>

          <!-- PARTIES INFORMATION CARD -->
          <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg border border-gray-200 dark:border-gray-700">
            <div class="p-4 bg-gray-50 dark:bg-gray-700 border-b border-gray-200 dark:border-gray-700">
              <h3 class="font-medium text-gray-900 dark:text-gray-100 flex items-center gap-2">
                <svg class="w-5 h-5 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                </svg>
                Delivery Parties
              </h3>
            </div>
            <div class="p-4 md:p-6">
              <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Sender Information -->
                <div class="bg-gray-50 dark:bg-gray-700/50 p-4 rounded-lg">
                  <h3 class="text-base font-medium text-gray-900 dark:text-gray-100 mb-3 flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                    Sender
                  </h3>
                  <div class="space-y-2">
                    <p class="font-medium truncate">{{ order.delivery_request?.sender?.name || 'N/A' }}</p>
                    <p class="text-sm text-gray-600 dark:text-gray-400 flex items-center gap-1">
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                      </svg>
                      {{ order.delivery_request?.sender?.phone || order.delivery_request?.sender?.mobile || 'No phone' }}
                    </p>
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                      <span class="font-medium">Pick-up:</span> {{ pickUpRegionName }}
                    </p>
                  </div>
                </div>

                <!-- Receiver Information -->
                <div class="bg-gray-50 dark:bg-gray-700/50 p-4 rounded-lg">
                  <h3 class="text-base font-medium text-gray-900 dark:text-gray-100 mb-3 flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                    Receiver
                  </h3>
                  <div class="space-y-2">
                    <p class="font-medium truncate">{{ order.delivery_request?.receiver?.name || 'N/A' }}</p>
                    <p class="text-sm text-gray-600 dark:text-gray-400 flex items-center gap-1">
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                      </svg>
                      {{ order.delivery_request?.receiver?.phone || order.delivery_request?.receiver?.mobile || 'No phone' }}
                    </p>
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                      <span class="font-medium">Delivery:</span> {{ dropOffRegionName }}
                    </p>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- PACKAGE DETAILS CARD -->
          <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg border border-gray-200 dark:border-gray-700">
            <div class="p-4 bg-gray-50 dark:bg-gray-700 border-b border-gray-200 dark:border-gray-700">
              <div class="flex justify-between items-center">
                <h3 class="font-medium text-gray-900 dark:text-gray-100 flex items-center gap-2">
                  <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                  </svg>
                  Package Details
                </h3>
                <span class="text-sm text-gray-500">
                  {{ order.delivery_request.packages.length }} packages total
                </span>
              </div>
            </div>
            <div class="p-4 md:p-6">
              <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                  <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                      <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Item</th>
                      <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Dimensions</th>
                      <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Weight</th>
                      <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Value</th>
                      <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    </tr>
                  </thead>
                  <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    <tr v-for="pkg in order.delivery_request.packages" :key="pkg.id">
                      <td class="px-4 py-4 whitespace-nowrap">
                        <div class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ pkg.item_name }}</div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">{{ pkg.item_code }}</div>
                        <div v-if="pkg.description" class="text-xs text-gray-400 dark:text-gray-500 mt-1">{{ pkg.description }}</div>
                      </td>
                      <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                        <span v-if="pkg.height && pkg.width && pkg.length">
                          {{ pkg.height }} × {{ pkg.width }} × {{ pkg.length }} cm
                        </span>
                        <span v-else class="text-gray-400">-</span>
                      </td>
                      <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                        {{ pkg.weight }} kg
                      </td>
                      <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
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
        </div>

        <!-- RIGHT COLUMN: Completion Actions -->
        <div class="lg:col-span-1">
          <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg border border-gray-200 dark:border-gray-700 sticky top-6">
            <div class="p-4 bg-gray-50 dark:bg-gray-700 border-b border-gray-200 dark:border-gray-700">
              <h3 class="font-medium text-gray-900 dark:text-gray-100 flex items-center gap-2">
                <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                Completion
              </h3>
            </div>
            <div class="p-4">
              <!-- Existing Refund Alert -->
              <div v-if="hasExistingRefund" class="mb-6">
                <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-4">
                  <div class="flex items-center">
                    <ExclamationTriangleIcon class="h-5 w-5 text-yellow-400 mr-3" />
                    <div>
                      <h3 class="text-lg font-medium text-yellow-800 dark:text-yellow-300">Refund Request Exists</h3>
                      <p class="text-yellow-700 dark:text-yellow-400 text-sm mt-1">
                        <span v-if="existingRefundStatus === 'pending'">
                          A refund request is pending review. No need to complete delivery again.
                        </span>
                        <span v-else-if="existingRefundStatus === 'processed'">
                          Refund has been processed. This delivery is complete.
                        </span>
                      </p>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Financial Notice -->
              <div v-if="order.status === 'needs_review' && !hasExistingRefund" class="mb-6">
                <div class="bg-orange-50 dark:bg-orange-900/20 border border-orange-200 dark:border-orange-800 rounded-lg p-4">
                  <div class="flex items-center">
                    <ExclamationTriangleIcon class="h-5 w-5 text-orange-400 mr-3" />
                    <div>
                      <h3 class="text-lg font-medium text-orange-800 dark:text-orange-300">Financial Action Required</h3>
                      <p class="text-orange-700 dark:text-orange-400 text-sm mt-1">
                        <span v-if="isPrepaid">
                          A refund request will be created for the damaged/lost packages. Customer will be contacted for negotiation.
                        </span>
                        <span v-else-if="isPostpaid">
                          Invoice will be adjusted to exclude charges for damaged/lost packages.
                        </span>
                      </p>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Optional Notes -->
              <div class="mb-6">
                <label for="notes" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                  Additional Notes (Optional)
                </label>
                <textarea
                  v-model="form.notes"
                  id="notes"
                  rows="3"
                  :disabled="hasExistingRefund"
                  class="block w-full border border-gray-300 dark:border-gray-600 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white sm:text-sm disabled:opacity-50"
                  placeholder="Any special notes about the delivery, handover process, or customer communication..."
                ></textarea>
              </div>

              <!-- Action Buttons -->
              <div class="space-y-3">
                <PrimaryButton
                  v-if="!hasExistingRefund"
                  @click="showConfirmationModal = true"
                  :disabled="form.processing"
                  class="w-full justify-center"
                  :class="buttonClass"
                >
                  <span v-if="form.processing">
                    <ArrowPathIcon class="h-4 w-4 animate-spin mr-2" />
                    Processing...
                  </span>
                  <span v-else class="flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19-7"/>
                    </svg>
                    {{ buttonText }}
                  </span>
                </PrimaryButton>

                <SecondaryButton
                  @click="$inertia.visit(route('delivery-completion.ready-for-completion'))"
                  class="w-full justify-center"
                >
                  Back to Deliveries
                </SecondaryButton>

                <Link
                  v-if="hasExistingRefund && existingRefundId"
                  :href="route('refunds.show', { refund: existingRefundId })"
                  class="inline-flex justify-center w-full py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500"
                >
                  View Refund Details
                </Link>
              </div>

              <!-- Completion Checklist -->
              <div v-if="!hasExistingRefund" class="mt-6 p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                <h4 class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-3">Completion Checklist</h4>
                <ul class="space-y-2 text-xs text-gray-600 dark:text-gray-400">
                  <li class="flex items-center gap-2">
                    <svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19-7"/>
                    </svg>
                    All package statuses have been verified
                  </li>
                  <li class="flex items-center gap-2">
                    <svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19-7"/>
                    </svg>
                    Receiver information is accurate
                  </li>
                  <li class="flex items-center gap-2">
                    <svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19-7"/>
                    </svg>
                    Delivery outcome matches package status
                  </li>
                </ul>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

       <!-- Confirmation Modal -->
    <Modal :show="showConfirmationModal" @close="showConfirmationModal = false" maxWidth="md">
      <div class="p-6">
        <!-- Modal Header -->
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 flex items-center gap-2">
            <svg class="w-6 h-6 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            Confirm Delivery Completion
          </h3>
          <button @click="showConfirmationModal = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
          </button>
        </div>

        <!-- Modal Content -->
        <div class="space-y-4">
          <!-- Status Indicator -->
          <div v-if="order.status === 'delivered'" class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg p-4">
            <div class="flex items-center">
              <svg class="w-5 h-5 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
              </svg>
              <span class="text-green-800 dark:text-green-300 font-medium">Perfect Delivery</span>
            </div>
            <p class="text-sm text-green-700 dark:text-green-400 mt-1">
              All packages were successfully delivered to the receiver.
            </p>
          </div>

          <div v-else class="bg-orange-50 dark:bg-orange-900/20 border border-orange-200 dark:border-orange-800 rounded-lg p-4">
            <div class="flex items-center">
              <svg class="w-5 h-5 text-orange-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 15.5c-.77.833.192 2.5 1.732 2.5z"/>
              </svg>
              <span class="text-orange-800 dark:text-orange-300 font-medium">Delivery with Issues</span>
            </div>
            <p class="text-sm text-orange-700 dark:text-orange-400 mt-1">
              Some packages require attention. System will handle financial adjustments automatically.
            </p>
          </div>

          <!-- Confirmation Message -->
          <div class="bg-gray-50 dark:bg-gray-700/50 border border-gray-200 dark:border-gray-700 rounded-lg p-4">
            <p class="text-sm text-gray-700 dark:text-gray-300 font-medium mb-2">
              Are you sure you want to complete this delivery?
            </p>
            <p class="text-sm text-gray-600 dark:text-gray-400">
              This action will finalize the delivery process and update all records accordingly.
            </p>
          </div>

          <!-- Final Confirmation -->
          <div class="flex items-start p-3 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg">
            <input
              v-model="modalConfirmed"
              id="modal-confirm"
              type="checkbox"
              class="h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500 mt-0.5"
            />
            <label for="modal-confirm" class="ml-3 text-sm text-yellow-800 dark:text-yellow-300">
              I confirm that I have verified all delivery details and want to proceed with completion
            </label>
          </div>
        </div>

        <!-- Modal Actions -->
        <div class="flex justify-end space-x-3 mt-6">
          <SecondaryButton @click="showConfirmationModal = false" class="px-4">
            Cancel
          </SecondaryButton>
          <PrimaryButton 
            @click="submitCompletion" 
            :disabled="!modalConfirmed || form.processing"
            :class="buttonClass"
            class="px-4"
          >
            <span v-if="form.processing">
              <ArrowPathIcon class="h-4 w-4 animate-spin mr-2" />
              Processing...
            </span>
            <span v-else>
              Confirm & Complete
            </span>
          </PrimaryButton>
        </div>
      </div>
    </Modal>
  </EmployeeLayout>
</template>

<script setup>
import EmployeeLayout from '@/Layouts/EmployeeLayout.vue'
import PrimaryButton from '@/Components/PrimaryButton.vue'
import SecondaryButton from '@/Components/SecondaryButton.vue'
import Modal from '@/Components/Modal.vue'
import { Link, useForm } from '@inertiajs/vue3'
import { 
  ExclamationTriangleIcon, 
  InformationCircleIcon,
  ArrowPathIcon
} from '@heroicons/vue/24/outline'
import { computed, ref } from 'vue'

const props = defineProps({
  order: Object,
  outcomeStats: Object,
  requiresRefund: Boolean,
  hasExistingRefund: Boolean,
  existingRefundStatus: String,
  existingRefundId: Number
})

// Reactive state
const showConfirmationModal = ref(false)
const modalConfirmed = ref(false)

// Computed properties
const isPostpaid = computed(() => {
  return props.order.delivery_request?.payment_type === 'postpaid'
})

const isPrepaid = computed(() => {
  return props.order.delivery_request?.payment_type === 'prepaid'
})

const pickUpRegionName = computed(() => {
  return props.order.delivery_request?.pick_up_region?.name || 'N/A'
})

const dropOffRegionName = computed(() => {
  return props.order.delivery_request?.drop_off_region?.name || 'N/A'
})

// Dynamic button text and styling
const buttonText = computed(() => {
  if (props.order.status === 'delivered') {
    return 'Complete Perfect Delivery'
  } else {
    return 'Complete Delivery with Issues'
  }
})

const buttonClass = computed(() => {
  if (props.order.status === 'delivered') {
    return 'bg-green-600 hover:bg-green-700 focus:ring-green-500'
  } else {
    return 'bg-orange-600 hover:bg-orange-700 focus:ring-orange-500'
  }
})

// Form setup
const form = useForm({
  confirm_completion: true, // Always true now since we have modal confirmation
  notes: '',
  receiver_name: props.order.delivery_request?.receiver?.name || '',
  receiver_contact: props.order.delivery_request?.receiver?.phone || props.order.delivery_request?.receiver?.mobile || '',
  release_packages: props.order.delivery_request?.packages?.map(p => p.id) || []
})

function submitCompletion() {
  if (!modalConfirmed.value) return
  
  console.log('🟡 Form submitting...', {
    orderId: props.order.id,
    formData: form.data()
  });
  
  form.post(route('delivery-completion.process', { order: props.order.id }), {
    onSuccess: () => {
      console.log('✅ Form submitted successfully');
      showConfirmationModal.value = false
      modalConfirmed.value = false
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
    delivered: 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-300',
    needs_review: 'bg-orange-100 text-orange-800 dark:bg-orange-900/20 dark:text-orange-300',
    completed: 'bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-300'
  }
  return classes[status] || 'bg-gray-100 text-gray-800 dark:bg-gray-900/20 dark:text-gray-300'
}

function packageStatusBadgeClass(status) {
  const classes = {
    delivered: 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-300',
    damaged_in_transit: 'bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-300',
    lost_in_transit: 'bg-gray-100 text-gray-800 dark:bg-gray-900/20 dark:text-gray-300',
    in_transit: 'bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-300',
    completed: 'bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-300'
  }
  return classes[status] || 'bg-gray-100 text-gray-800 dark:bg-gray-900/20 dark:text-gray-300'
}

function formatDate(dateString) {
  if (!dateString) return 'N/A';
  try {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
      year: 'numeric',
      month: 'short',
      day: 'numeric'
    });
  } catch (e) {
    return 'Invalid date';
  }
}
</script>