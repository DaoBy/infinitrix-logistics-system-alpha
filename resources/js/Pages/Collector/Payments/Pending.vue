<template>
  <EmployeeLayout>
    <template #header>
      <div class="flex justify-between items-center w-full px-4 md:px-6 lg:px-8">
        <!-- Left: Title & Subtitle -->
        <div>
          <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
            Pending Collections
          </h2>
          <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
            Deliveries that are awaiting payment collection from customers.
          </p>
        </div>
      </div>
    </template>

    <!-- ZOOM CONTENT WRAPPER -->
    <div class="zoom-content">
      <!-- MAIN CONTENT CONTAINER WITH PROPER PADDING -->
      <div class="px-4 md:px-6 lg:px-8 py-4">
        <!-- Search and Filter Bar -->
        <div class="mb-4 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
          <div class="w-full sm:w-80">
            <SearchInput 
              v-model="search"
              placeholder="Search by reference, sender, or receiver..."
              class="w-full"
            />
          </div>
          <div class="flex items-center gap-3 w-full sm:w-auto">
            <div class="w-full sm:w-48">
              <SelectInput
                v-model="status"
                :options="statusOptions"
                class="w-full"
              />
            </div>
            <div class="text-sm text-gray-500 dark:text-gray-400 bg-blue-50 dark:bg-blue-900/30 px-3 py-1 rounded border border-blue-100 dark:border-blue-800">
              📋 Showing {{ deliveries?.data?.length ?? 0 }} of {{ deliveries?.total ?? 0 }} 
              {{ (deliveries?.total ?? 0) === 1 ? 'entry' : 'entries' }}
            </div>
          </div>
        </div>

        <!-- Mobile View - Card Layout -->
        <div class="sm:hidden space-y-4">
          <div v-for="item in deliveries?.data || []" :key="item.id" 
               :class="getRowClass(item)"
               class="rounded-lg border border-gray-200 dark:border-gray-700 shadow-sm p-4">
            
            <!-- Header Section -->
            <div class="flex justify-between items-start mb-3">
              <div>
                <div class="flex items-center gap-2 mb-1">
                  <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100">
                    Ref
                  </span>
                  <span class="font-bold text-green-700 dark:text-green-300 tracking-wide">
                    {{ item.delivery_request?.reference_number || `DR-${String(item.delivery_request_id).padStart(6, '0')}` }}
                  </span>
                </div>
                <div class="text-xs text-gray-500 dark:text-gray-400">
                  ID: DO-{{ String(item.delivery_request_id).padStart(6, '0') }}
                </div>
              </div>
              <!-- Custom Status Badge -->
              <span :class="getStatusBadgeClass(item)" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium">
                {{ getStatusText(item) }}
              </span>
            </div>

            <!-- Customer Info -->
            <div class="mb-3">
              <div class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-1">
                {{ item.delivery_request?.sender?.name || 'N/A' }}
              </div>
              <div class="text-xs text-gray-500 dark:text-gray-400 space-y-1">
                <div class="flex items-center gap-1">
                  <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                  </svg>
                  {{ item.delivery_request?.sender?.mobile || 'No phone' }}
                </div>
                <div class="flex items-center gap-1">
                  <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                  </svg>
                  {{ item.delivery_request?.sender?.email || 'No email' }}
                </div>
              </div>
            </div>

            <!-- Amount and Due Date -->
            <div class="grid grid-cols-2 gap-4 mb-3">
              <div>
                <div class="text-xs text-gray-500 dark:text-gray-400">Amount</div>
                <div class="text-sm font-semibold text-gray-900 dark:text-gray-100">
                  ₱{{ formatAmount(item.delivery_request?.total_price) }}
                </div>
              </div>
              <div>
                <div class="text-xs text-gray-500 dark:text-gray-400">Due Date</div>
                <div class="text-sm text-gray-900 dark:text-gray-100">
                  <template v-if="item.delivery_request?.payment_terms === 'cnd'">
                    CND
                  </template>
                  <template v-else-if="item.delivery_request?.payment_due_date">
                    {{ formatDate(item.delivery_request.payment_due_date) }}
                    <span v-if="isOverdue(item)" class="text-red-500 text-xs">(Overdue)</span>
                  </template>
                  <template v-else>—</template>
                </div>
              </div>
            </div>

            <!-- Payment Terms -->
            <div class="text-xs text-gray-500 dark:text-gray-400 mb-3">
              Terms: {{ formatPaymentTerms(item.delivery_request?.payment_terms) }}
            </div>

            <!-- Uncollectible Info -->
            <div v-if="getPaymentStatus(item) === 'uncollectible'" class="bg-orange-50 dark:bg-orange-900/20 rounded p-2 mb-3">
              <div class="text-xs text-orange-700 dark:text-orange-300">
                <div class="font-medium">Extended Due Date</div>
                <div>Reason: {{ item.delivery_request?.non_payment_reason }}</div>
                <div>New due: {{ formatDate(item.delivery_request?.payment_due_date) }}</div>
              </div>
            </div>

      <!-- Action Buttons Section - MOBILE -->
<div class="flex justify-between gap-2">
  <PrimaryButton
    v-if="canCollectPayment(item)"
    @click="goToCollectPayment(item.delivery_request_id)"
    class="!px-3 !py-2 !text-xs flex-1 flex items-center justify-center gap-1"
  >
    <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
    </svg>
    Collect
  </PrimaryButton>

  <!-- REMOVED: The Mark Uncollectible/Extend button for uncollectible status -->
  <DangerButton
    v-if="canMarkUncollectible(item) && getPaymentStatus(item) !== 'uncollectible'"
    @click="openUncollectibleModal(item)"
    class="!px-3 !py-2 !text-xs flex-1 flex items-center justify-center gap-1"
  >
    <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
    </svg>
    Uncollectible
  </DangerButton>

  <SecondaryButton
    @click="openInfoDialog(item)"
    class="!px-3 !py-2 !text-xs flex items-center justify-center gap-1"
  >
    <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0zm6 0a9 9 0 11-18 0 9 9 0 0118 0z" />
    </svg>
    Info
  </SecondaryButton>
</div>
          </div>

          <!-- Mobile Empty State -->
          <div v-if="!deliveries?.data || deliveries.data.length === 0" class="text-center py-8">
            <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-6">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-gray-400 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
              <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-2">No pending collections found</h3>
              <p class="text-gray-500 dark:text-gray-400">
                {{ search ? 'Try adjusting your search terms' : 'All payments have been collected' }}
              </p>
            </div>
          </div>

          <!-- Mobile Pagination -->
          <Pagination 
            v-if="deliveries?.last_page > 1"
            :pagination="deliveries" 
            @page-changed="handlePageChange" 
            class="mt-6"
          />
        </div>

        <!-- Desktop View - Custom Table -->
        <div class="hidden sm:block justify-center items-center">
          <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg border border-gray-200 dark:border-gray-700 w-full">
            <div class="overflow-x-auto">
              <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                  <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Reference</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Customer</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Amount</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Due Date</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider"></th>
                  </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                  <tr
                    v-for="item in deliveries?.data || []"
                    :key="item.id"
                    :class="getRowClass(item)"
                    class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors duration-150"
                  >
                    <!-- Reference Column -->
                    <td class="px-6 py-4 whitespace-nowrap">
                      <div class="flex flex-col">
                        <div class="flex items-center gap-2">
                          <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100">
                            Reference #
                          </span>
                          <span class="font-bold text-green-700 dark:text-green-300 tracking-wide">
                            {{ item.delivery_request?.reference_number || `DR-${String(item.delivery_request_id).padStart(6, '0')}` }}
                          </span>
                        </div>
                        <div class="mt-1 flex flex-wrap items-center gap-2 text-xs text-gray-500 dark:text-gray-300">
                          <span>
                            Delivery ID: DO-{{ String(item.delivery_request_id).padStart(6, '0') }}
                          </span>
                          <span v-if="item.created_at">
                            | Created: {{ formatDate(item.created_at) }}
                          </span>
                        </div>
                      </div>
                    </td>

                    <!-- Status Column -->
                    <td class="px-6 py-4 whitespace-nowrap">
                      <!-- Custom Status Badge -->
                      <span :class="getStatusBadgeClass(item)" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium">
                        {{ getStatusText(item) }}
                      </span>
                      
                      <!-- Show reason and extended due date for uncollectible items -->
                      <div v-if="getPaymentStatus(item) === 'uncollectible'" class="text-xs text-gray-500 mt-1">
                        <div>Reason: {{ item.delivery_request?.non_payment_reason }}</div>
                        <div>Extended due: {{ formatDate(item.delivery_request?.payment_due_date) }}</div>
                      </div>
                      
                      <!-- Show payment terms for regular pending items -->
                      <div v-else class="text-xs text-gray-500 mt-1">
                        Terms: {{ formatPaymentTerms(item.delivery_request?.payment_terms) }}
                      </div>
                    </td>

                    <!-- Customer Column -->
                    <td class="px-6 py-4 whitespace-nowrap">
                      <div class="text-sm text-gray-900 dark:text-gray-100 font-medium">
                        {{ item.delivery_request?.sender?.name || 'N/A' }}
                      </div>
                      <div class="text-xs text-gray-500 dark:text-gray-400 flex items-center gap-1 mt-1">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                        </svg>
                        {{ item.delivery_request?.sender?.mobile || 'No phone' }}
                      </div>
                      <div class="text-xs text-gray-500 dark:text-gray-400 flex items-center gap-1 mt-1">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                        {{ item.delivery_request?.sender?.email || 'No email' }}
                      </div>
                    </td>

                    <!-- Amount Column -->
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                      ₱{{ formatAmount(item.delivery_request?.total_price) }}
                    </td>

                    <!-- Due Date Column -->
                    <td class="px-6 py-4 whitespace-nowrap">
                      <div v-if="item.delivery_request?.payment_terms === 'cnd'" class="text-gray-500 dark:text-gray-400 font-medium">
                        CND
                      </div>
                      <div v-else-if="item.delivery_request?.payment_due_date" class="text-gray-500 dark:text-gray-400">
                        {{ formatDate(item.delivery_request.payment_due_date) }}
                        <span v-if="isOverdue(item)" class="text-red-500 font-semibold">(Overdue)</span>
                      </div>
                      <div v-else class="text-gray-400">—</div>
                    </td>

                  <!-- Actions Column - DESKTOP -->
<td class="px-6 py-4 whitespace-nowrap">
  <div class="flex space-x-2 justify-end">
    <!-- Collect Payment Button -->
    <PrimaryButton
      v-if="canCollectPayment(item)"
      @click="goToCollectPayment(item.delivery_request_id)"
      class="!px-3 !py-2 !text-xs flex items-center justify-center gap-1"
      title="Collect Payment"
    >
      <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
      </svg>
      Collect
    </PrimaryButton>

    <!-- REMOVED: Mark Uncollectible Button for uncollectible status -->
    <DangerButton
      v-if="canMarkUncollectible(item) && getPaymentStatus(item) !== 'uncollectible'"
      @click="openUncollectibleModal(item)"
      class="!px-3 !py-2 !text-xs flex items-center justify-center gap-1"
      title="Mark Uncollectible"
    >
      <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
      </svg>
      Uncollectible
    </DangerButton>

    <!-- View Info Button -->
    <SecondaryButton
      @click="openInfoDialog(item)"
      class="!px-3 !py-2 !text-xs flex items-center justify-center gap-1"
      title="View Full Details"
    >
      <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0zm6 0a9 9 0 11-18 0 9 9 0 0118 0z" />
      </svg>
      Info
    </SecondaryButton>
  </div>
</td>
                  </tr>
                  
                  <!-- Empty State -->
                  <tr v-if="!deliveries?.data || deliveries.data.length === 0">
                    <td colspan="6" class="px-6 py-8 text-center">
                      <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-6 max-w-md mx-auto">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-gray-400 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-2">No pending collections found</h3>
                        <p class="text-gray-500 dark:text-gray-400 mb-3">
                          {{ search ? 'Try adjusting your search terms' : 'All payments have been collected' }}
                        </p>
                      </div>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <!-- Desktop Pagination -->
            <Pagination 
              v-if="deliveries?.last_page > 1"
              :pagination="deliveries" 
              @page-changed="handlePageChange" 
              class="mt-4 border-t border-gray-200 dark:border-gray-700 p-4"
            />
          </div>
        </div>
      </div>
    </div>
    <!-- Mark as Uncollectible Modal -->
    <v-dialog v-model="uncollectibleDialog" max-width="500">
      <v-card class="border border-gray-200 dark:border-gray-700 rounded-lg">
        <v-card-title class="pb-0 border-b border-gray-100 dark:border-gray-700 relative">
          <div>
            <span class="text-lg font-semibold text-red-700 dark:text-red-300">
              {{ modalTitle }}
            </span>
            <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
              Reference:
              <span class="font-bold text-indigo-900 dark:text-indigo-100">
                {{ selectedDelivery?.delivery_request?.reference_number || `DR-${String(selectedDelivery?.delivery_request_id).padStart(6, '0')}` }}
              </span>
              <span v-if="selectedDelivery?.delivery_request?.non_payment_reason" class="ml-2 text-orange-600">
                (Previously extended)
              </span>
            </div>
          </div>
          <button
            type="button"
            @click="closeUncollectibleModal"
            class="absolute top-3 right-3 text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 focus:outline-none"
            aria-label="Close"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
            </svg>
          </button>
        </v-card-title>
        <v-card-text class="p-6">
          <div class="space-y-4">
            <div class="bg-red-50 dark:bg-red-900/30 rounded-lg p-4 border border-red-200 dark:border-red-700">
              <div class="text-sm text-gray-900 dark:text-gray-100 font-medium mb-2">
                {{ modalDescription }}
              </div>
              
              <!-- Reason Dropdown -->
              <div class="mb-4">
                <InputLabel value="Reason *" class="mb-2" />
                <SelectInput
                  v-model="selectedReason"
                  :options="reasonOptions"
                  placeholder="Select a reason"
                  :error="formErrors.non_payment_reason"
                  class="w-full"
                />
              </div>

              <!-- Different Reason Textarea -->
              <div v-if="selectedReason === 'other'">
                <InputLabel value="Please specify *" class="mb-2" />
                <TextArea
                  v-model="customReason"
                  :rows="3"
                  placeholder="Please provide details about why payment couldn't be collected..."
                  :error="formErrors.non_payment_reason"
                />
              </div>

              <div class="text-xs text-gray-600 dark:text-gray-400 mt-2">
                The due date will be extended by 7 days and the customer will be notified.
              </div>
            </div>
          </div>
        </v-card-text>
        <v-card-actions class="px-6 pb-4">
          <v-spacer></v-spacer>
          <SecondaryButton type="button" @click="closeUncollectibleModal">
            Cancel
          </SecondaryButton>
          <DangerButton
            type="button"
            :disabled="submitting || !selectedReason || (selectedReason === 'other' && !customReason.trim())"
            :class="{ 'opacity-50': submitting || !selectedReason || (selectedReason === 'other' && !customReason.trim()) }"
            @click="submitUncollectible"
          >
            <span v-if="submitting">
              <LoadingSpinner size="xs" class="mr-2" /> Processing...
            </span>
            <span v-else>
              {{ modalButtonText }}
            </span>
          </DangerButton>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Enhanced Delivery Info Modal - REMOVED DELIVERY INFORMATION SECTION -->
    <v-dialog v-model="infoDialog" max-width="800">
      <template v-if="infoDelivery">
        <v-card class="border border-gray-200 dark:border-gray-700 rounded-lg">
          <v-card-title class="pb-0 border-b border-gray-100 dark:border-gray-700 relative">
            <div>
              <span class="text-lg font-semibold text-gray-900 dark:text-gray-100">Delivery Request Details</span>
              <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                Reference: 
                <span class="font-bold text-indigo-900 dark:text-indigo-100">
                  {{ infoDelivery.delivery_request?.reference_number || `DR-${String(infoDelivery.delivery_request_id).padStart(6, '0')}` }}
                </span>
              </div>
            </div>
            <button
              type="button"
              @click="closeInfoDialog"
              class="absolute top-3 right-3 text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 focus:outline-none"
              aria-label="Close"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
              </svg>
            </button>
          </v-card-title>
          <v-card-text class="p-6">
            <div class="space-y-6">
              <!-- Contact Information Section -->
              <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Sender Information -->
                <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 shadow-sm">
                  <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 border-b border-gray-200 dark:border-gray-600">
                    <h4 class="font-semibold text-gray-900 dark:text-gray-100 flex items-center gap-2">
                      <svg class="w-4 h-4 text-blue-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                      </svg>
                      Sender Information
                    </h4>
                  </div>
                  <div class="p-4 space-y-3">
                    <div>
                      <label class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Name</label>
                      <p class="text-sm text-gray-900 dark:text-gray-100 mt-1">{{ infoDelivery.delivery_request?.sender?.name || '—' }}</p>
                    </div>
                    <div>
                      <label class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Phone</label>
                      <p class="text-sm text-gray-900 dark:text-gray-100 mt-1">{{ infoDelivery.delivery_request?.sender?.mobile || '—' }}</p>
                    </div>
                    <div>
                      <label class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Email</label>
                      <p class="text-sm text-gray-900 dark:text-gray-100 mt-1">{{ infoDelivery.delivery_request?.sender?.email || '—' }}</p>
                    </div>
                    <div>
                      <label class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Address</label>
                      <p class="text-sm text-gray-900 dark:text-gray-100 mt-1">{{ infoDelivery.delivery_request?.sender?.address || '—' }}</p>
                    </div>
                  </div>
                </div>

                <!-- Receiver Information -->
                <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 shadow-sm">
                  <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 border-b border-gray-200 dark:border-gray-600">
                    <h4 class="font-semibold text-gray-900 dark:text-gray-100 flex items-center gap-2">
                      <svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                      </svg>
                      Receiver Information
                    </h4>
                  </div>
                  <div class="p-4 space-y-3">
                    <div>
                      <label class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Name</label>
                      <p class="text-sm text-gray-900 dark:text-gray-100 mt-1">{{ infoDelivery.delivery_request?.receiver?.name || '—' }}</p>
                    </div>
                    <div>
                      <label class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Phone</label>
                      <p class="text-sm text-gray-900 dark:text-gray-100 mt-1">{{ infoDelivery.delivery_request?.receiver?.mobile || '—' }}</p>
                    </div>
                    <div>
                      <label class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Email</label>
                      <p class="text-sm text-gray-900 dark:text-gray-100 mt-1">{{ infoDelivery.delivery_request?.receiver?.email || '—' }}</p>
                    </div>
                    <div>
                      <label class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Address</label>
                      <p class="text-sm text-gray-900 dark:text-gray-100 mt-1">{{ infoDelivery.delivery_request?.receiver?.address || '—' }}</p>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Collection History Section - REMOVED TOTAL ATTEMPTS -->
              <div v-if="infoDelivery.delivery_request?.non_payment_reason" class="bg-orange-50 dark:bg-orange-900/20 rounded-lg p-4 border border-orange-200 dark:border-orange-700">
                <h4 class="font-semibold text-orange-700 dark:text-orange-200 mb-3 flex items-center gap-1">
                  <ExclamationTriangleIcon class="w-4 h-4" />
                  Collection History
                </h4>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                  <div>
                    <span class="font-medium text-gray-700 dark:text-gray-300">Previous Issue:</span>
                    <div class="text-orange-600 dark:text-orange-300 mt-1">{{ infoDelivery.delivery_request.non_payment_reason }}</div>
                  </div>
                  <div v-if="infoDelivery.delivery_request?.last_uncollectible_at">
                    <span class="font-medium text-gray-700 dark:text-gray-300">Last Attempt:</span>
                    <div class="text-orange-600 dark:text-orange-300 mt-1">{{ formatDateTime(infoDelivery.delivery_request.last_uncollectible_at) }}</div>
                  </div>
                </div>
              </div>

              <!-- DELIVERY INFORMATION SECTION REMOVED -->
            </div>
          </v-card-text>
          <v-card-actions class="px-6 pb-4">
            <v-spacer></v-spacer>
            <PrimaryButton
              v-if="canCollectPayment(infoDelivery)"
              @click="goToCollectPayment(infoDelivery.delivery_request_id)"
              class="mr-2"
            >
              Collect Payment
            </PrimaryButton>
            <SecondaryButton @click="closeInfoDialog">
              Close
            </SecondaryButton>
          </v-card-actions>
        </v-card>
      </template>
    </v-dialog>
  </EmployeeLayout>
</template>

<script setup>
import EmployeeLayout from '@/Layouts/EmployeeLayout.vue';
import SearchInput from '@/Components/SearchInput.vue';
import SelectInput from '@/Components/SelectInput.vue';
import TextArea from '@/Components/TextArea.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import InputLabel from '@/Components/InputLabel.vue';
import LoadingSpinner from '@/Components/LoadingSpinner.vue';
import Pagination from '@/Components/Pagination.vue';
import { 
  ExclamationTriangleIcon
} from '@heroicons/vue/24/outline';
import { ref, watch, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import debounce from 'lodash/debounce';

const props = defineProps({
  deliveries: Object,
  filters: Object
});

const search = ref(props.filters.search || '');
const status = ref(props.filters.status || 'pending');
const sortField = ref('created_at');
const sortDirection = ref('desc');

// Fixed status options - make sure these match your backend
const statusOptions = [
  { value: '', label: 'All Statuses' },
  { value: 'pending', label: 'Pending Collection' },
  { value: 'uncollectible', label: 'Extended Due Date' }
];

const handlePageChange = (page) => {
  router.get(route('collector.payments.pending'), {
    page: page,
    search: search.value,
    status: status.value
  }, {
    preserveState: true,
    preserveScroll: true
  });
};

watch([search, status], debounce(([searchValue, statusValue]) => {
  router.get(route('collector.payments.pending'), {
    search: searchValue,
    status: statusValue
  }, {
    preserveState: true,
    preserveScroll: true,
    replace: true
  });
}, 300));

// --- Mark as Uncollectible Modal Logic ---
const uncollectibleDialog = ref(false);
const selectedDelivery = ref(null);
const selectedReason = ref('');
const customReason = ref('');
const formErrors = ref({});
const submitting = ref(false);

const reasonOptions = [
  { value: '', label: 'Select a reason' },
  { value: 'customer_unavailable', label: 'Customer Unavailable' },
  { value: 'customer_refused', label: 'Customer Refused to Pay' },
  { value: 'missing_documents', label: 'Missing Payment Documents' },
  { value: 'invalid_payment_proof', label: 'Invalid Payment Proof' },
  { value: 'wrong_information', label: 'Wrong Transaction Information' },
  { value: 'other', label: 'Different Reason' }
];

// Computed properties for dynamic modal content
const modalTitle = computed(() => {
  if (!selectedDelivery.value) return 'Mark as Uncollectible';
  const status = getPaymentStatus(selectedDelivery.value);
  return status === 'uncollectible' ? 'Extend Due Date' : 'Mark as Uncollectible';
});

const modalDescription = computed(() => {
  if (!selectedDelivery.value) return 'Please select the reason why this delivery cannot be collected.';
  const status = getPaymentStatus(selectedDelivery.value);
  return status === 'uncollectible' 
    ? 'This delivery already has an extended due date. Please select a reason to extend it again.'
    : 'Please select the reason why this delivery cannot be collected.';
});

const modalButtonText = computed(() => {
  if (!selectedDelivery.value) return 'Mark as Uncollectible';
  const status = getPaymentStatus(selectedDelivery.value);
  return status === 'uncollectible' ? 'Extend Due Date' : 'Mark as Uncollectible';
});

function openUncollectibleModal(item) {
  selectedDelivery.value = item;
  selectedReason.value = '';
  customReason.value = '';
  formErrors.value = {};
  uncollectibleDialog.value = true;
}

function closeUncollectibleModal() {
  uncollectibleDialog.value = false;
  selectedDelivery.value = null;
  selectedReason.value = '';
  customReason.value = '';
  formErrors.value = {};
  submitting.value = false;
}

function submitUncollectible() {
  if (!selectedDelivery.value) return;
  
  submitting.value = true;
  formErrors.value = {};

  // Build the reason text
  let reasonText = '';
  const selectedOption = reasonOptions.find(opt => opt.value === selectedReason.value);
  
  if (selectedReason.value === 'other') {
    reasonText = customReason.value.trim();
  } else {
    reasonText = selectedOption?.label || selectedReason.value;
  }

  // Validate
  if (!reasonText) {
    formErrors.value = { non_payment_reason: ['Please provide a reason'] };
    submitting.value = false;
    return;
  }

  router.post(
    route('collector.payments.mark-uncollectible', selectedDelivery.value.delivery_request_id),
    { non_payment_reason: reasonText },
    {
      preserveScroll: true,
      onSuccess: () => {
        submitting.value = false;
        closeUncollectibleModal();
      },
      onError: (errors) => {
        formErrors.value = errors;
        submitting.value = false;
      }
    }
  );
}

function goToCollectPayment(deliveryRequestId) {
  router.visit(route('collector.payments.create', deliveryRequestId));
}

// Payment terms formatting
function formatPaymentTerms(terms) {
  if (!terms) return 'Standard';
  
  const termMap = {
    'net_7': 'Net 7',
    'net_15': 'Net 15', 
    'net_30': 'Net 30',
    'cnd': 'CND',
    'cod': 'COD'
  };
  
  return termMap[terms] || terms;
}

// Status helper methods
function getPaymentStatus(item) {
  const status = item.delivery_request?.payment_status;
  return status || 'pending';
}

function getStatusBadgeClass(item) {
  const status = getPaymentStatus(item);
  switch (status) {
    case 'uncollectible': 
      return 'bg-orange-100 text-orange-800 dark:bg-orange-800 dark:text-orange-100'; // Orange for extended due date
    case 'pending': 
    case 'pending_payment': 
    case 'unpaid': 
    default: 
      return 'bg-blue-100 text-blue-800 dark:bg-blue-800 dark:text-blue-100'; // Blue for regular pending
  }
}

function getStatusText(item) {
  const status = getPaymentStatus(item);
  switch (status) {
    case 'uncollectible': return 'Extended Due Date';
    case 'pending': 
    case 'pending_payment': 
    case 'unpaid': 
    default: return 'Pending Collection';
  }
}

function getAttemptCount(item) {
  // This would ideally come from your database
  // For now, we'll count based on non_payment_reason existence
  return item.delivery_request?.non_payment_reason ? 1 : 0;
}

function getRowClass(item) {
  const status = getPaymentStatus(item);
  
  if (status === 'uncollectible') {
    return 'bg-orange-50 dark:bg-orange-900/20'; // Orange background for extended due date
  }
  
  if (isOverdue(item)) {
    return 'bg-red-100 dark:bg-red-900/30'; // Red background for overdue
  }
  
  return 'bg-white dark:bg-gray-800';
}

// Action availability - ALL statuses can be collected
function canCollectPayment(item) {
  const status = getPaymentStatus(item);
  return ['pending', 'pending_payment', 'unpaid', 'uncollectible', null].includes(status);
}

// Mark Uncollectible available for ALL pending items (including already uncollectible)
function canMarkUncollectible(item) {
  const status = getPaymentStatus(item);
  return ['pending', 'pending_payment', 'unpaid', 'uncollectible', null].includes(status);
}

function isOverdue(item) {
  // CND items don't have due dates, so they can't be overdue
  if (item.delivery_request?.payment_terms === 'cnd') return false;
  if (!item.delivery_request?.payment_due_date) return false;
  const dueDate = new Date(item.delivery_request.payment_due_date);
  return dueDate < new Date();
}

function formatAmount(amount) {
  return amount !== undefined && amount !== null
    ? (parseFloat(amount) || 0).toFixed(2)
    : '0.00';
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

function formatDateTime(dateString) {
  if (!dateString) return 'N/A';
  try {
    const date = new Date(dateString);
    return date.toLocaleString('en-US', {
      year: 'numeric',
      month: 'short',
      day: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    });
  } catch (e) {
    return 'Invalid date';
  }
}

// --- Delivery Info Modal Logic ---
const infoDialog = ref(false);
const infoDelivery = ref(null);

function openInfoDialog(item) {
  infoDelivery.value = item;
  infoDialog.value = true;
}

function closeInfoDialog() {
  infoDialog.value = false;
  infoDelivery.value = null;
}
</script>

<style>
.zoom-content {
  zoom: 0.9;
  transform-origin: top center;
}

/* Mobile optimizations */
@media (max-width: 640px) {
  .zoom-content {
    zoom: 1;
  }
}
</style>