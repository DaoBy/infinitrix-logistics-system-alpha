<template>
  <EmployeeLayout>
    <template #header>
      <div class="flex justify-between items-center px-4 sm:px-6">
        <div class="min-w-0 flex-1">
          <h2 class="text-lg sm:text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200 truncate">
            Collection Dashboard
          </h2>
          <p class="mt-1 text-sm text-gray-600 dark:text-gray-400 hidden sm:block">
            Overview of your collection activities and performance
          </p>
        </div>
        <div class="flex items-center gap-2 text-xs sm:text-sm text-gray-500 dark:text-gray-400">
          <ClockIcon class="h-4 w-4" />
          <span>Updated: {{ lastUpdated }}</span>
        </div>
      </div>
    </template>

    <!-- Stats Cards -->
    <div class="px-4 md:px-6 py-4 max-w-7xl mx-auto">
      <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 mb-6">
        <!-- Pending Collections Card -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-3 sm:p-4">
          <div class="flex items-center">
            <div class="flex-shrink-0">
              <div class="w-10 h-10 sm:w-12 sm:h-12 bg-yellow-100 dark:bg-yellow-900/30 rounded-lg flex items-center justify-center">
                <ClockIcon class="h-5 w-5 sm:h-6 sm:w-6 text-yellow-600 dark:text-yellow-400" />
              </div>
            </div>
            <div class="ml-3">
              <p class="text-xs sm:text-sm font-medium text-gray-600 dark:text-gray-400">Pending Collections</p>
              <p class="text-xl sm:text-2xl font-bold text-gray-900 dark:text-gray-100">{{ stats.pending_collections }}</p>
            </div>
          </div>
          <div class="mt-2">
            <SecondaryButton 
              :href="route('collector.payments.pending')"
              class="!px-2 !py-1 !text-xs w-full justify-center"
            >
              View All
            </SecondaryButton>
          </div>
        </div>

        <!-- Pending Verification Card -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-3 sm:p-4">
          <div class="flex items-center">
            <div class="flex-shrink-0">
              <div class="w-10 h-10 sm:w-12 sm:h-12 bg-blue-100 dark:bg-blue-900/30 rounded-lg flex items-center justify-center">
                <DocumentCheckIcon class="h-5 w-5 sm:h-6 sm:w-6 text-blue-600 dark:text-blue-400" />
              </div>
            </div>
            <div class="ml-3">
              <p class="text-xs sm:text-sm font-medium text-gray-600 dark:text-gray-400">Pending Verification</p>
              <p class="text-xl sm:text-2xl font-bold text-gray-900 dark:text-gray-100">{{ stats.pending_verification }}</p>
            </div>
          </div>
          <div class="mt-2">
            <SecondaryButton 
              :href="route('collector.payments.index', { status: 'pending' })"
              class="!px-2 !py-1 !text-xs w-full justify-center"
            >
              View All
            </SecondaryButton>
          </div>
        </div>

        <!-- Verified Payments Card -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-3 sm:p-4">
          <div class="flex items-center">
            <div class="flex-shrink-0">
              <div class="w-10 h-10 sm:w-12 sm:h-12 bg-green-100 dark:bg-green-900/30 rounded-lg flex items-center justify-center">
                <CheckCircleIcon class="h-5 w-5 sm:h-6 sm:w-6 text-green-600 dark:text-green-400" />
              </div>
            </div>
            <div class="ml-3">
              <p class="text-xs sm:text-sm font-medium text-gray-600 dark:text-gray-400">Verified Payments</p>
              <p class="text-xl sm:text-2xl font-bold text-gray-900 dark:text-gray-100">{{ stats.verified_payments }}</p>
            </div>
          </div>
          <div class="mt-2">
            <SecondaryButton 
              :href="route('collector.payments.index', { status: 'verified' })"
              class="!px-2 !py-1 !text-xs w-full justify-center"
            >
              View All
            </SecondaryButton>
          </div>
        </div>

        <!-- Rejected Payments Card -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-3 sm:p-4">
          <div class="flex items-center">
            <div class="flex-shrink-0">
              <div class="w-10 h-10 sm:w-12 sm:h-12 bg-red-100 dark:bg-red-900/30 rounded-lg flex items-center justify-center">
                <ExclamationTriangleIcon class="h-5 w-5 sm:h-6 sm:w-6 text-red-600 dark:text-red-400" />
              </div>
            </div>
            <div class="ml-3">
              <p class="text-xs sm:text-sm font-medium text-gray-600 dark:text-gray-400">Rejected Payments</p>
              <p class="text-xl sm:text-2xl font-bold text-gray-900 dark:text-gray-100">{{ stats.rejected_payments }}</p>
            </div>
          </div>
          <div class="mt-2">
            <SecondaryButton 
              :href="route('collector.payments.index', { status: 'rejected' })"
              class="!px-2 !py-1 !text-xs w-full justify-center"
            >
              View All
            </SecondaryButton>
          </div>
        </div>
      </div>

      <!-- Main Content Grid -->
      <div class="grid grid-cols-1 xl:grid-cols-3 gap-4 sm:gap-6">
        <!-- Left Column - Pending Collections -->
        <div class="xl:col-span-2 space-y-4 sm:space-y-6">
          <!-- Recent Pending Collections -->
          <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
            <div class="p-4 border-b border-gray-200 dark:border-gray-700">
              <div class="flex items-center justify-between">
                <h3 class="text-base sm:text-lg font-medium text-gray-900 dark:text-gray-100 flex items-center gap-2">
                  <ClockIcon class="h-5 w-5 text-yellow-500" />
                  Recent Pending Collections
                </h3>
                <span class="text-xs text-gray-500 dark:text-gray-400">
                  {{ filteredPendingCollections.length }} items
                </span>
              </div>
            </div>
            <div class="p-4">
              <!-- Mobile View -->
              <div class="sm:hidden space-y-4">
                <div v-for="item in filteredPendingCollections" :key="item.id" 
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
                </div>
              </div>

              <!-- Desktop View - 2 columns side by side -->
              <div class="hidden sm:block">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                  <div 
                    v-for="item in filteredPendingCollections" 
                    :key="item.id"
                    class="border border-gray-200 dark:border-gray-600 rounded-lg p-4 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors"
                  >
                    <!-- Header -->
                    <div class="flex items-center justify-between mb-3">
                      <div class="flex items-center gap-2">
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100">
                          Ref
                        </span>
                        <span class="font-bold text-green-700 dark:text-green-300 text-sm">
                          {{ item.delivery_request?.reference_number || `DR-${String(item.delivery_request_id).padStart(6, '0')}` }}
                        </span>
                      </div>
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
                    <div class="grid grid-cols-2 gap-3 mb-3">
                      <div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">Amount</div>
                        <div class="text-lg font-bold text-gray-900 dark:text-gray-100">
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
                            <span v-if="isOverdue(item)" class="text-red-500 text-xs block">(Overdue)</span>
                          </template>
                          <template v-else>—</template>
                        </div>
                      </div>
                    </div>

                    <!-- Payment Terms -->
                    <div class="text-xs text-gray-500 dark:text-gray-400 mb-2">
                      Terms: {{ formatPaymentTerms(item.delivery_request?.payment_terms) }}
                    </div>

                    <!-- ID -->
                    <div class="text-xs text-gray-500 dark:text-gray-400">
                      ID: DO-{{ String(item.delivery_request_id).padStart(6, '0') }}
                    </div>

                    <!-- Uncollectible Info -->
                    <div v-if="getPaymentStatus(item) === 'uncollectible'" class="bg-orange-50 dark:bg-orange-900/20 rounded p-2 mt-3">
                      <div class="text-xs text-orange-700 dark:text-orange-300">
                        <div class="font-medium">Extended Due Date</div>
                        <div>Reason: {{ item.delivery_request?.non_payment_reason }}</div>
                        <div>New due: {{ formatDate(item.delivery_request?.payment_due_date) }}</div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <div v-if="filteredPendingCollections.length === 0" class="text-center py-6">
                <DocumentCheckIcon class="mx-auto h-8 w-8 text-gray-400" />
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">No pending collections</p>
              </div>
              
              <div class="mt-4 text-center">
                <SecondaryButton 
                  :href="route('collector.payments.pending')"
                  class="w-full justify-center"
                >
                  View All Pending Collections
                </SecondaryButton>
              </div>
            </div>
          </div>

          <!-- Overdue Collections Alert -->
          <div v-if="overdueCollections.length > 0" class="bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-700 rounded-lg p-4">
            <div class="flex items-start gap-3">
              <ExclamationTriangleIcon class="h-5 w-5 text-red-400 mt-0.5 flex-shrink-0" />
              <div class="flex-1">
                <h3 class="text-base font-medium text-red-800 dark:text-red-200">Overdue Collections</h3>
                <p class="text-sm text-red-700 dark:text-red-300 mb-3">
                  You have {{ overdueCollections.length }} collection(s) that are overdue for payment.
                </p>
                <div class="space-y-2">
                  <div 
                    v-for="delivery in overdueCollections.slice(0, 2)" 
                    :key="delivery.id"
                    class="flex items-center justify-between p-2 bg-red-100 dark:bg-red-800/50 rounded border border-red-200 dark:border-red-600"
                  >
                    <div class="min-w-0">
                      <div class="flex items-center gap-2 mb-1">
                        <span class="text-sm font-medium text-red-800 dark:text-red-200 truncate">
                          {{ delivery.delivery_request?.reference_number || `DR-${String(delivery.delivery_request_id).padStart(6, '0')}` }}
                        </span>
                        <span class="text-xs text-red-700 dark:text-red-300">
                          Due: {{ formatDate(delivery.delivery_request?.payment_due_date) }}
                        </span>
                      </div>
                    </div>
                    <PrimaryButton
                      @click="collectPayment(delivery.delivery_request_id)"
                      class="!px-2 !py-1 !text-xs bg-red-600 hover:bg-red-700 whitespace-nowrap"
                    >
                      Collect
                    </PrimaryButton>
                  </div>
                </div>
                <div v-if="overdueCollections.length > 2" class="mt-2 text-center">
                  <SecondaryButton 
                    :href="route('collector.payments.pending')"
                    class="!px-2 !py-1 !text-xs"
                  >
                    +{{ overdueCollections.length - 2 }} more overdue
                  </SecondaryButton>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Right Column - Activities & Performance -->
        <div class="space-y-4 sm:space-y-6">
          <!-- Recent Payment Activities -->
          <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
            <div class="p-4 border-b border-gray-200 dark:border-gray-700">
              <div class="flex items-center justify-between">
                <h3 class="text-base sm:text-lg font-medium text-gray-900 dark:text-gray-100 flex items-center gap-2">
                  <CurrencyDollarIcon class="h-5 w-5 text-green-500" />
                  Recent Activities
                </h3>
                <span class="text-xs text-gray-500 dark:text-gray-400">
                  {{ recentActivities.length }} items
                </span>
              </div>
            </div>
            <div class="p-4">
              <!-- Mobile View -->
              <div class="sm:hidden space-y-4">
                <div v-for="activity in recentActivities" :key="activity.id" 
                     class="rounded-lg border border-gray-200 dark:border-gray-700 shadow-sm p-4 bg-white dark:bg-gray-800">
                  
                  <!-- Header Section -->
                  <div class="flex justify-between items-start mb-3">
                    <div>
                      <div class="flex items-center gap-2 mb-1">
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100">
                          Ref
                        </span>
                        <span class="font-bold text-green-700 dark:text-green-300 tracking-wide">
                          {{ activity.delivery_request?.reference_number || `DR-${String(activity.delivery_request_id).padStart(6, '0')}` }}
                        </span>
                      </div>
                      <div class="text-xs text-gray-500 dark:text-gray-400">
                        ID: DO-{{ String(activity.delivery_request_id).padStart(6, '0') }}
                        <span v-if="activity.created_at"> | Created: {{ formatDate(activity.created_at) }}</span>
                      </div>
                    </div>
                    <!-- Status Badge -->
                    <div class="text-right">
                      <span :class="getActivityBadgeClass(activity)" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium">
                        {{ getActivityStatusText(activity) }}
                      </span>
                      <div v-if="activity.verified_by || activity.rejected_by" class="text-xs text-gray-500 mt-1">
                        {{ (activity.verified_by || activity.rejected_by)?.name }}
                      </div>
                      <div v-if="(activity.verified_by || activity.rejected_by)?.employee_profile?.employee_id" class="text-xs text-gray-400">
                        ID: {{ (activity.verified_by || activity.rejected_by)?.employee_profile?.employee_id }}
                      </div>
                    </div>
                  </div>

                  <!-- Customer Info -->
                  <div class="mb-3">
                    <div class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-1">
                      {{
                        activity.delivery_request?.sender?.name ||
                        activity.delivery_request?.sender?.company_name ||
                        activity.delivery_request?.receiver?.name ||
                        activity.delivery_request?.receiver?.company_name ||
                        'N/A'
                      }}
                    </div>
                    <div class="text-xs text-gray-500 dark:text-gray-400 space-y-1">
                      <div class="flex items-center gap-1">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                        </svg>
                        {{ getCustomerPhone(activity) || 'No phone' }}
                      </div>
                      <div class="flex items-center gap-1">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                        {{ getCustomerEmail(activity) || 'No email' }}
                      </div>
                    </div>
                  </div>

                  <!-- Payment Details -->
                  <div class="grid grid-cols-2 gap-4 mb-3">
                    <div>
                      <div class="text-xs text-gray-500 dark:text-gray-400">Amount</div>
                      <div class="text-sm font-semibold text-gray-900 dark:text-gray-100">
                        ₱{{ Number(activity.amount).toFixed(2) }}
                      </div>
                    </div>
                    <div>
                      <div class="text-xs text-gray-500 dark:text-gray-400">Collected At</div>
                      <div class="text-sm text-gray-900 dark:text-gray-100">
                        {{ activity.collected_at ? formatDate(activity.collected_at) : 'Not collected' }}
                      </div>
                    </div>
                  </div>

                  <!-- Action Buttons -->
                  <div class="flex justify-between gap-2">
                    <PrimaryButton
                      @click="viewPaymentDetails(activity.id)"
                      class="!px-3 !py-2 !text-xs flex-1 flex items-center justify-center gap-1"
                    >
                      <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0zm6 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                      </svg>
                      View
                    </PrimaryButton>
                  </div>
                </div>
              </div>

              <!-- Desktop View -->
              <div class="hidden sm:block space-y-4">
                <div 
                  v-for="activity in recentActivities" 
                  :key="activity.id"
                  class="border border-gray-200 dark:border-gray-600 rounded-lg p-4 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors"
                >
                  <div class="flex justify-between items-start">
                    <!-- Left Section -->
                    <div class="flex-1">
                      <!-- Header -->
                      <div class="flex items-center gap-3 mb-3">
                        <div class="flex items-center gap-2">
                          <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100">
                            Ref
                          </span>
                          <span class="font-bold text-green-700 dark:text-green-300 text-sm">
                            {{ activity.delivery_request?.reference_number || `DR-${String(activity.delivery_request_id).padStart(6, '0')}` }}
                          </span>
                        </div>
                        <span :class="getActivityBadgeClass(activity)" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium">
                          {{ getActivityStatusText(activity) }}
                        </span>
                      </div>

                      <!-- Customer Info with Amount on the right -->
                      <div class="flex items-start justify-between">
                        <div>
                          <div class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-1">
                            {{
                              activity.delivery_request?.sender?.name ||
                              activity.delivery_request?.sender?.company_name ||
                              activity.delivery_request?.receiver?.name ||
                              activity.delivery_request?.receiver?.company_name ||
                              'N/A'
                            }}
                          </div>
                          <div class="text-xs text-gray-500 dark:text-gray-400 space-y-1">
                            <div class="flex items-center gap-1">
                              <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                              </svg>
                              {{ getCustomerPhone(activity) || 'No phone' }}
                            </div>
                            <div class="flex items-center gap-1">
                              <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                              </svg>
                              {{ getCustomerEmail(activity) || 'No email' }}
                            </div>
                          </div>
                        </div>

                        <!-- Amount on the right side -->
                        <div class="text-right ml-6">
                          <div class="text-lg font-bold text-gray-900 dark:text-gray-100">
                            ₱{{ Number(activity.amount).toFixed(2) }}
                          </div>
                        </div>
                      </div>

                      <!-- Collection Date and Action -->
                      <div class="flex items-center justify-between mt-3">
                        <div class="text-xs text-gray-500 dark:text-gray-400">
                          Collected: {{ activity.collected_at ? formatDate(activity.collected_at) : 'Not collected' }}
                        </div>
                        <PrimaryButton
                          @click="viewPaymentDetails(activity.id)"
                          class="!px-3 !py-1.5 !text-xs"
                        >
                          View Details
                        </PrimaryButton>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <div v-if="recentActivities.length === 0" class="text-center py-6">
                <CurrencyDollarIcon class="mx-auto h-8 w-8 text-gray-400" />
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">No recent activities</p>
              </div>
              
              <div class="mt-4 text-center">
                <SecondaryButton 
                  :href="route('collector.payments.index')"
                  class="w-full justify-center"
                >
                  View All Payment History
                </SecondaryButton>
              </div>
            </div>
          </div>

          <!-- Performance Metrics -->
          <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
            <div class="p-4 border-b border-gray-200 dark:border-gray-700">
              <h3 class="text-base sm:text-lg font-medium text-gray-900 dark:text-gray-100 flex items-center gap-2">
                <ChartBarIcon class="h-5 w-5 text-indigo-500" />
                Performance
              </h3>
            </div>
            <div class="p-4 sm:p-6">
              <div class="text-center">
                <div class="text-2xl sm:text-3xl font-bold text-green-600 dark:text-green-400 mb-2">
                  ₱{{ metrics.total_collected.toLocaleString() }}
                </div>
                <div class="text-sm text-gray-500 dark:text-gray-400">Total Collected This Month</div>
                <div class="mt-3 text-xs text-gray-400 dark:text-gray-500">
                  Verified payments only
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </EmployeeLayout>
</template>

<script setup>
import EmployeeLayout from '@/Layouts/EmployeeLayout.vue';
import { Link, router } from '@inertiajs/vue3';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { 
  ClockIcon,
  DocumentCheckIcon,
  CheckCircleIcon,
  ExclamationTriangleIcon,
  CurrencyDollarIcon,
  ChartBarIcon
} from '@heroicons/vue/24/outline';
import { computed } from 'vue';

const props = defineProps({
  stats: {
    type: Object,
    default: () => ({})
  },
  recentPendingCollections: {
    type: Array,
    default: () => []
  },
  recentActivities: {
    type: Array,
    default: () => []
  },
  overdueCollections: {
    type: Array,
    default: () => []
  },
  metrics: {
    type: Object,
    default: () => ({})
  }
});

// Filter out Extended Due status from pending collections
const filteredPendingCollections = computed(() => {
  return props.recentPendingCollections
    .filter(delivery => {
      const status = delivery.delivery_request?.payment_status;
      return !['uncollectible', 'verified', 'pending_verification'].includes(status);
    })
    .slice(0, 4); // Show up to 4 items for 2x2 grid
});

// Limit activities to 3 items
const recentActivities = computed(() => props.recentActivities.slice(0, 3));

const lastUpdated = computed(() => {
  return new Date().toLocaleString('en-US', {
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  });
});

// Status helpers for pending collections
function getPaymentStatus(delivery) {
  return delivery.delivery_request?.payment_status || 'pending';
}

function getStatusText(delivery) {
  const status = getPaymentStatus(delivery);
  switch (status) {
    case 'verified':
      return 'Verified';
    case 'pending_verification':
      return 'Pending Verification';
    case 'uncollectible':
      return 'Extended Due';
    case 'overdue':
      return 'Overdue';
    default:
      return 'Pending Collection';
  }
}

function getStatusBadgeClass(delivery) {
  const status = getPaymentStatus(delivery);
  switch (status) {
    case 'verified':
      return 'bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100';
    case 'pending_verification':
      return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-800 dark:text-yellow-100';
    case 'uncollectible':
      return 'bg-orange-100 text-orange-800 dark:bg-orange-800 dark:text-orange-100';
    case 'overdue':
      return 'bg-red-100 text-red-800 dark:bg-red-800 dark:text-red-100';
    default:
      return 'bg-blue-100 text-blue-800 dark:bg-blue-800 dark:text-blue-100';
  }
}

function getRowClass(delivery) {
  const status = getPaymentStatus(delivery);
  if (status === 'uncollectible') {
    return 'bg-orange-50 dark:bg-orange-900/20';
  }
  if (isOverdue(delivery)) {
    return 'bg-red-50 dark:bg-red-900/20';
  }
  return 'bg-white dark:bg-gray-800';
}

// Status helpers for activities
function getActivityStatusText(payment) {
  if (payment.verified_by) return 'Verified';
  if (payment.rejected_by) return 'Rejected';
  return 'Pending Verification';
}

function getActivityBadgeClass(payment) {
  if (payment.verified_by) return 'bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100';
  if (payment.rejected_by) return 'bg-red-100 text-red-800 dark:bg-red-800 dark:text-red-100';
  return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-800 dark:text-yellow-100';
}

function getCustomerPhone(payment) {
  return payment.delivery_request?.sender?.mobile || 
         payment.delivery_request?.receiver?.mobile || 
         payment.delivery_request?.sender?.phone ||
         payment.delivery_request?.receiver?.phone;
}

function getCustomerEmail(payment) {
  return payment.delivery_request?.sender?.email || 
         payment.delivery_request?.receiver?.email;
}

function isOverdue(delivery) {
  if (!delivery.delivery_request?.payment_due_date) return false;
  const dueDate = new Date(delivery.delivery_request.payment_due_date);
  return dueDate < new Date();
}

function formatAmount(amount) {
  return amount ? Number(amount).toFixed(2) : '0.00';
}

function formatPaymentTerms(terms) {
  if (!terms) return 'Standard';
  if (terms === 'cnd') return 'Cash on Delivery';
  return terms.charAt(0).toUpperCase() + terms.slice(1);
}

function collectPayment(deliveryRequestId) {
  router.visit(route('collector.payments.create', deliveryRequestId));
}

function viewPaymentDetails(paymentId) {
  router.visit(route('collector.payments.show', paymentId));
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