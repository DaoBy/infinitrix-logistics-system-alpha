<template>
  <EmployeeLayout>
    <template #header>
      <div class="flex justify-between items-center px-4 sm:px-6">
        <div class="min-w-0 flex-1">
          <h2 class="text-lg sm:text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200 truncate">
            Record Cash Payment
          </h2>
          <p class="mt-1 text-sm text-gray-600 dark:text-gray-400 hidden sm:block">
            Process over-the-counter cash payments
          </p>
        </div>
        <SecondaryButton 
          @click="router.visit(route('staff.payments.dashboard'))"
          class="inline-flex items-center text-sm whitespace-nowrap shrink-0 ml-2"
        >
          <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1 sm:mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
          </svg>
          <span class="hidden sm:inline">Back to Payments</span>
          <span class="sm:hidden">Back</span>
        </SecondaryButton>
      </div>
    </template>

    <div class="px-4 md:px-6 py-4 max-w-7xl mx-auto">
      <!-- Loading state -->
      <div v-if="delivery === null" class="bg-white dark:bg-gray-800 shadow-sm rounded-lg border border-gray-200 dark:border-gray-700 p-8 text-center">
        <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-blue-100 dark:bg-blue-900">
          <LoadingSpinner class="w-6 h-6 text-blue-600 dark:text-blue-400" />
        </div>
        <h3 class="mt-4 text-lg font-medium text-gray-900 dark:text-gray-100">Loading Delivery Information</h3>
        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Please wait while we load the delivery details...</p>
      </div>

      <!-- No delivery provided message -->
      <div v-else-if="!delivery" class="bg-white dark:bg-gray-800 shadow-sm rounded-lg border border-gray-200 dark:border-gray-700 p-8 text-center">
        <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-gray-100 dark:bg-gray-700">
          <svg class="h-6 w-6 text-gray-500 dark:text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
          </svg>
        </div>
        <h3 class="mt-4 text-lg font-medium text-gray-900 dark:text-gray-100">No Delivery Selected</h3>
        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Please go back to the payments page and select a delivery to record payment.</p>
        <div class="mt-6">
          <PrimaryButton @click="router.visit(route('staff.payments.dashboard'))">
            ← Back to Payments
          </PrimaryButton>
        </div>
      </div>

      <!-- MAIN CONTENT GRID -->
      <div v-else class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- LEFT COLUMN: Delivery Information -->
        <div class="lg:col-span-2 space-y-6">
          <!-- DELIVERY OVERVIEW CARD -->
          <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg border border-gray-200 dark:border-gray-700">
            <!-- Delivery Header -->
            <div class="flex flex-col md:flex-row md:items-center md:justify-between p-4 bg-gray-50 dark:bg-gray-700 border-b border-gray-200 dark:border-gray-700">
              <div class="flex-1">
                <div class="flex flex-wrap items-center gap-2 mb-2">
                  <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100 border border-green-200 dark:border-green-700">
                    Reference#
                  </span>
                  <span class="text-lg font-bold text-green-600 dark:text-green-400 tracking-wide">
                    {{ delivery.reference_number }}
                  </span>
                  <span :class="paymentStatusClass" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium capitalize">
                    {{ delivery.payment_status || 'N/A' }}
                  </span>
                </div>
                <div class="flex flex-wrap items-center gap-4 text-xs text-gray-600 dark:text-gray-300">
                  <span>Delivery ID: DO-{{ String(delivery.id).padStart(6, '0') }}</span>
                  <span v-if="delivery.created_at">Created: {{ formatDate(delivery.created_at) }}</span>
                </div>
              </div>
            </div>

            <!-- Customer Information -->
            <div class="p-4 md:p-6">
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4 md:gap-6 mb-6">
                <!-- Sender Information -->
                <div class="bg-gray-50 dark:bg-gray-700/50 p-4 rounded-lg">
                  <h3 class="text-base font-medium text-gray-900 dark:text-gray-100 mb-3 flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                    Sender
                  </h3>
                  <div class="space-y-2">
                    <p class="font-medium truncate">{{ delivery.sender?.name || 'N/A' }}</p>
                    <p class="text-sm text-gray-600 dark:text-gray-400 flex items-center gap-1">
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                      </svg>
                      {{ delivery.sender?.email || 'No email' }}
                    </p>
                    <p class="text-sm text-gray-600 dark:text-gray-400 flex items-center gap-1">
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                      </svg>
                      {{ delivery.sender?.mobile || 'No phone' }}
                    </p>
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                      {{ delivery.sender?.address || 'No address' }}
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
                    <p class="font-medium truncate">{{ delivery.receiver?.name || 'N/A' }}</p>
                    <p class="text-sm text-gray-600 dark:text-gray-400 flex items-center gap-1">
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                      </svg>
                      {{ delivery.receiver?.email || 'No email' }}
                    </p>
                    <p class="text-sm text-gray-600 dark:text-gray-400 flex items-center gap-1">
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                      </svg>
                      {{ delivery.receiver?.mobile || 'No phone' }}
                    </p>
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                      {{ delivery.receiver?.address || 'No address' }}
                    </p>
                  </div>
                </div>
              </div>

              <!-- Package Summary -->
              <div class="bg-gray-50 dark:bg-gray-700/50 p-4 rounded-lg">
                <h3 class="text-base font-medium text-gray-900 dark:text-gray-100 mb-3 flex items-center gap-2">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                  </svg>
                  Package Details
                </h3>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm mb-4">
                  <div>
                    <span class="text-gray-500 dark:text-gray-400">Total Packages:</span>
                    <p class="font-medium">{{ delivery.packages?.length || 0 }}</p>
                  </div>
                  <div>
                    <span class="text-gray-500 dark:text-gray-400">Total Weight:</span>
                    <p class="font-medium">{{ calculateTotalWeight() }} kg</p>
                  </div>
                  <div>
                    <span class="text-gray-500 dark:text-gray-400">Total Volume:</span>
                    <p class="font-medium">{{ calculateTotalVolume() }} </p>
                  </div>
                  <div>
                    <span class="text-gray-500 dark:text-gray-400">Total Value:</span>
                    <p class="font-medium">₱{{ calculateTotalValue().toFixed(2) }}</p>
                  </div>
                </div>

                <!-- Package Details Table -->
                <div class="overflow-x-auto">
                  <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-100 dark:bg-gray-700">
                      <tr>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                          Item
                        </th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                          Dimensions
                        </th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                          Weight
                        </th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                          Value
                        </th>
                      </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                      <tr v-for="pkg in delivery.packages" :key="pkg.id" class="hover:bg-gray-50 dark:hover:bg-gray-700">
                        <td class="px-4 py-4 whitespace-nowrap">
                          <div class="font-medium text-gray-900 dark:text-gray-100">{{ pkg.item_name || 'N/A' }}</div>
                          <div class="text-sm text-gray-500 dark:text-gray-400">{{ pkg.category || 'Uncategorized' }}</div>
                        </td>
                        <td class="px-4 py-4 whitespace-nowrap text-gray-900 dark:text-gray-100">
                          {{ pkg.height || 0 }}cm × {{ pkg.width || 0 }}cm × {{ pkg.length || 0 }}cm
                        </td>
                        <td class="px-4 py-4 whitespace-nowrap text-gray-900 dark:text-gray-100">
                          {{ pkg.weight || 0 }} kg
                        </td>
                        <td class="px-4 py-4 whitespace-nowrap text-gray-900 dark:text-gray-100 font-medium">
                          ₱{{ parseFloat(pkg.value || 0).toFixed(2) }}
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          </div>

          <!-- Price Breakdown -->
          <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg border border-gray-200 dark:border-gray-700">
            <div class="p-4 bg-blue-50 dark:bg-blue-900/20 border-b border-blue-200 dark:border-blue-800">
              <h3 class="font-medium text-blue-900 dark:text-blue-100 flex items-center gap-2">
                <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                </svg>
                Price Breakdown
              </h3>
            </div>
            <div class="p-4 md:p-6">
              <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Fees -->
                <div class="space-y-4">
                  <div class="flex justify-between items-center p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                    <span class="text-gray-700 dark:text-gray-300">Base Fee:</span>
                    <span class="font-medium text-gray-900 dark:text-gray-100">₱{{ parseFloat(delivery.base_fee || 0).toFixed(2) }}</span>
                  </div>
                  
                  <div class="flex justify-between items-center p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                    <span class="text-gray-700 dark:text-gray-300">Volume Fee:</span>
                    <span class="font-medium text-gray-900 dark:text-gray-100">₱{{ parseFloat(delivery.volume_fee || 0).toFixed(2) }}</span>
                  </div>
                  
                  <div class="flex justify-between items-center p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                    <span class="text-gray-700 dark:text-gray-300">Weight Fee:</span>
                    <span class="font-medium text-gray-900 dark:text-gray-100">₱{{ parseFloat(delivery.weight_fee || 0).toFixed(2) }}</span>
                  </div>
                  
                  <div class="flex justify-between items-center p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                    <span class="text-gray-700 dark:text-gray-300">Package Fee ({{ (delivery.packages && delivery.packages.length) || 0 }}):</span>
                    <span class="font-medium text-gray-900 dark:text-gray-100">₱{{ parseFloat(delivery.package_fee || 0).toFixed(2) }}</span>
                  </div>
                </div>

                <!-- Total -->
                <div class="border-l border-gray-200 dark:border-gray-700 pl-6">
                  <div class="flex justify-between items-center text-lg font-semibold mb-4 p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                    <span class="text-blue-900 dark:text-blue-100">Total Amount:</span>
                    <span class="text-blue-600 dark:text-blue-400">₱{{ parseFloat(delivery.total_price || 0).toFixed(2) }}</span>
                  </div>
                  
                  <div class="flex justify-between items-center text-sm text-gray-600 dark:text-gray-400 mb-2">
                    <span>Original Payment Method:</span>
                    <span class="capitalize font-medium">{{ delivery.payment_method || 'N/A' }}</span>
                  </div>
                  
                  <div class="flex justify-between items-center text-sm text-gray-600 dark:text-gray-400">
                    <span>Current Status:</span>
                    <span :class="paymentStatusClass" class="capitalize px-2 py-1 rounded-full text-xs">
                      {{ delivery.payment_status || 'N/A' }}
                    </span>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Payment Method Change Notice -->
          <div v-if="delivery.payment_method && delivery.payment_method !== 'cash'" 
               class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-4">
            <div class="flex items-center">
              <svg class="h-5 w-5 text-yellow-400 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
              </svg>
              <div>
                <h4 class="font-medium text-yellow-800 dark:text-yellow-200">Payment Method Change</h4>
                <p class="text-sm text-yellow-700 dark:text-yellow-300 mt-1">
                  Changing payment method from <strong>{{ delivery.payment_method.toUpperCase() }}</strong> to <strong>CASH</strong>
                </p>
                <p class="text-xs text-yellow-600 dark:text-yellow-400 mt-2">
                  This will override the original payment method selection to Cash.
                </p>
              </div>
            </div>
          </div>
        </div>

        <!-- RIGHT COLUMN: Payment Form -->
        <div class="lg:col-span-1">
          <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg border border-gray-200 dark:border-gray-700 sticky top-6">
            <div class="p-4 bg-gray-50 dark:bg-gray-700 border-b border-gray-200 dark:border-gray-700">
              <h3 class="font-medium text-gray-900 dark:text-gray-100 flex items-center gap-2">
                <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                Cash Payment Details
              </h3>
            </div>
            <div class="p-4">
              <form @submit.prevent="submit">
                <div class="space-y-4">
                  <!-- Payment Method - Fixed to Cash Only -->
                  <div>
                    <InputLabel value="Payment Method" />
                    <div class="mt-1 p-3 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-md">
                      <div class="flex items-center gap-3">
                        <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                        <div>
                          <p class="font-medium text-blue-900 dark:text-blue-100">Cash Payment</p>
                          <p class="text-xs text-blue-700 dark:text-blue-300">Over-the-counter cash payment processing</p>
                        </div>
                      </div>
                    </div>
                    <input type="hidden" v-model="form.method" />
                  </div>

                  <!-- Payment Amount Group -->
                  <div class="bg-gray-50 dark:bg-gray-700/50 p-3 rounded-lg border border-gray-200 dark:border-gray-600">
                    <h4 class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-3 flex items-center gap-2">
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                      </svg>
                      Payment Amount
                    </h4>
                    
                    <div class="space-y-3">
                      <!-- Total Due -->
                      <div class="flex justify-between items-center p-2 bg-white dark:bg-gray-600 rounded border">
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Total Due:</span>
                        <span class="text-lg font-bold text-gray-900 dark:text-gray-100">
                          ₱{{ parseFloat(delivery.total_price || 0).toFixed(2) }}
                        </span>
                      </div>

                      <!-- Amount Received -->
                      <div>
                        <InputLabel value="Amount Received *" />
                        <div class="relative rounded-md shadow-sm">
                          <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <span class="text-gray-500 dark:text-gray-400 sm:text-sm">₱</span>
                          </div>
                          <TextInput
                            v-model="form.amount_received"
                            type="number"
                            step="0.01"
                            min="0.01"
                            class="block w-full pl-7 text-base font-semibold"
                            placeholder="0.00"
                            :error="form.errors.amount_received"
                          />
                        </div>
                        <InputError :message="form.errors.amount_received" />
                        
                        <!-- Amount Validation Message -->
                        <div v-if="form.amount_received && parseFloat(form.amount_received) < parseFloat(delivery.total_price || 0)" 
                             class="mt-1 text-xs text-red-600 dark:text-red-400 flex items-center gap-1">
                          <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                          </svg>
                          Amount must cover the total due of ₱{{ parseFloat(delivery.total_price || 0).toFixed(2) }}
                        </div>
                      </div>

                      <!-- Change -->
                      <div :class="['flex justify-between items-center p-2 rounded border', change >= 0 ? 'bg-green-50 dark:bg-green-900/20 border-green-200 dark:border-green-800' : 'bg-red-50 dark:bg-red-900/20 border-red-200 dark:border-red-800']">
                        <span :class="['text-sm font-medium', change >= 0 ? 'text-green-800 dark:text-green-300' : 'text-red-800 dark:text-red-300']">
                          Change:
                        </span>
                        <span :class="['text-lg font-bold', change >= 0 ? 'text-green-900 dark:text-green-100' : 'text-red-900 dark:text-red-100']">
                          ₱{{ change.toFixed(2) }}
                        </span>
                      </div>
                    </div>
                  </div>

                  <!-- Collection Notes -->
                  <div>
                    <InputLabel value="Notes (Optional)" />
                    <TextArea
                      v-model="form.notes"
                      :rows="3"
                      placeholder="Add any additional notes about this cash payment..."
                      class="resize-none text-sm"
                    />
                    <InputError :message="form.errors.notes" />
                  </div>

                  <!-- Staff Information -->
                  <div class="bg-gray-50 dark:bg-gray-700/50 p-3 rounded-lg border border-gray-200 dark:border-gray-600">
                    <h4 class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-2 flex items-center gap-2">
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                      </svg>
                      Staff Information
                    </h4>
                    <div class="space-y-1 text-xs text-gray-600 dark:text-gray-400">
                      <p>Staff: {{ $page.props.auth.user.name }}</p>
                      <p>Date: {{ currentDate }}</p>
                      <p>Time: {{ currentTime }}</p>
                    </div>
                  </div>

                  <!-- Submit Button -->
                  <div class="flex flex-col gap-2 pt-2">
                    <SecondaryButton
                      @click="resetForm"
                      :disabled="form.processing"
                      class="w-full justify-center"
                    >
                      Reset Form
                    </SecondaryButton>
                    <PrimaryButton
                      type="submit"
                      :disabled="form.processing || !canSubmit"
                      class="w-full justify-center"
                    >
                      <span v-if="form.processing">
                        <LoadingSpinner size="xs" class="mr-2" />
                        Processing...
                      </span>
                      <span v-else class="flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        Record Cash Payment
                      </span>
                    </PrimaryButton>
                  </div>
                </div>
              </form>
            </div>
          </div>
        </div>
      </div>
    </div>
  </EmployeeLayout>
</template>

<script setup>
import EmployeeLayout from '@/Layouts/EmployeeLayout.vue';
import { useForm, router } from '@inertiajs/vue3';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import TextInput from '@/Components/TextInput.vue';
import TextArea from '@/Components/TextArea.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import LoadingSpinner from '@/Components/LoadingSpinner.vue';
import { computed, onMounted, ref } from 'vue';

const props = defineProps({
  delivery: {
    type: Object,
    default: null
  }
});

const currentDate = ref(new Date().toLocaleDateString('en-US', {
  year: 'numeric',
  month: 'long',
  day: 'numeric'
}));

const currentTime = ref(new Date().toLocaleTimeString('en-US', {
  hour: '2-digit',
  minute: '2-digit'
}));

const form = useForm({
  delivery_request_id: '',
  method: 'cash', // Fixed to cash only
  amount_received: '',
  amount: 0,
  notes: ''
});

// Calculate change
const change = computed(() => {
  if (!form.amount_received || !props.delivery) return 0;
  const received = parseFloat(form.amount_received);
  const total = parseFloat(props.delivery.total_price || 0);
  return received - total;
});

// Payment status badge class
const paymentStatusClass = computed(() => {
  const status = props.delivery?.payment_status;
  if (status === 'paid') return 'bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100';
  if (status === 'pending') return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-800 dark:text-yellow-100';
  if (status === 'rejected') return 'bg-red-100 text-red-800 dark:bg-red-800 dark:text-red-100';
  return 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-100';
});

// Validation for submit button
const canSubmit = computed(() => {
  if (!form.amount_received || !props.delivery) return false;
  
  const received = parseFloat(form.amount_received);
  const total = parseFloat(props.delivery.total_price || 0);
  
  // For cash, check if sufficient amount was received
  return received >= total;
});

// Helper functions
function calculateTotalWeight() {
  if (!props.delivery.packages || props.delivery.packages.length === 0) return '0';
  const total = props.delivery.packages.reduce((total, pkg) => total + (Number(pkg.weight) || 0), 0);
  return total > 0 ? total.toFixed(2) : '0';
}

function calculateTotalVolume() {
  if (!props.delivery.packages || props.delivery.packages.length === 0) return '0';
  
  const total = props.delivery.packages.reduce((total, pkg) => {
    const volume = (Number(pkg.height) * Number(pkg.width) * Number(pkg.length)) / 1000000;
    return total + (volume || 0);
  }, 0);
  
  // Convert to cm³ if volume is less than 0.001 m³ (very small packages)
  if (total < 0.001 && total > 0) {
    const volumeCm3 = total * 1000000; // Convert back to cm³
    return volumeCm3 > 0 ? volumeCm3.toFixed(0) + ' cm³' : '0';
  }
  
  return total > 0 ? total.toFixed(3) + ' m³' : '0';
}

function calculateTotalValue() {
  if (!props.delivery.packages || props.delivery.packages.length === 0) return 0;
  return props.delivery.packages.reduce((total, pkg) => total + (Number(pkg.value) || 0), 0);
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
  } catch {
    return 'Invalid Date';
  }
}

function resetForm() {
  form.reset();
  form.method = 'cash';
  form.delivery_request_id = props.delivery?.id || '';
}

function submit() {
  if (!props.delivery || !canSubmit.value) return;
  
  form.amount = parseFloat(props.delivery.total_price || 0);
  form.delivery_request_id = props.delivery.id;
  
  form.post(route('staff.payments.store'), {
    preserveScroll: true,
    onSuccess: () => {
      // Form reset is handled by Inertia response
    }
  });
}

// Initialize form when delivery is available
onMounted(() => {
  if (props.delivery) {
    form.delivery_request_id = props.delivery.id;
  }
});
</script>

<style scoped>
input[type="number"]::-webkit-outer-spin-button,
input[type="number"]::-webkit-inner-spin-button {
  -webkit-appearance: none;
  margin: 0;
}
input[type="number"] {
  -moz-appearance: textfield;
}
</style>