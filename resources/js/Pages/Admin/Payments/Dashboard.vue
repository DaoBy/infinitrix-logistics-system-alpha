<template>
  <EmployeeLayout>
    <template #header>
      <div class="flex flex-wrap justify-between items-center gap-4 px-4 md:px-6 max-w-screen-xl mx-auto w-full">
        <div>
          <h2 class="text-xl font-semibold leading-tight text-gray-800">
            Payment Management
          </h2>
          <p class="text-sm text-gray-600 mt-1">
            Manage all payment activities in one place
          </p>
        </div>
      </div>
    </template>
    <div class="py-6 px-2 md:px-6">

      <div class="bg-white shadow-sm rounded-lg border border-gray-200 mb-6 max-w-screen-xl mx-auto">
        <div class="border-b border-gray-200">
          <nav class="-mb-px flex space-x-8 px-6">
            <!-- Only show Verification Queue for admin users -->
            <button
              v-if="$page.props.auth.user.role === 'admin'"
              @click="switchTab('verification')"
              :class="[
                'py-4 px-1 border-b-2 font-medium text-sm transition-colors duration-200 flex items-center',
                activeTab === 'verification'
                  ? 'border-blue-500 text-blue-600'
                  : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
              ]"
            >
              ⏳ Verification Queue
              <span class="ml-2 bg-blue-100 text-blue-800 px-2 py-1 rounded-full text-xs font-medium">
                {{ stats.pending_verification || 0 }}
              </span>
            </button>
            
            <button
              @click="switchTab('collection')"
              :class="[
                'py-4 px-1 border-b-2 font-medium text-sm transition-colors duration-200 flex items-center',
                activeTab === 'collection'
                  ? 'border-green-500 text-green-600'
                  : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
              ]"
            >
              💰 Collection Management
              <span class="ml-2 bg-green-100 text-green-800 px-2 py-1 rounded-full text-xs font-medium">
                {{ stats.needs_collection || 0 }}
              </span>
            </button>
            <button
              @click="switchTab('history')"
              :class="[
                'py-4 px-1 border-b-2 font-medium text-sm transition-colors duration-200 flex items-center',
                activeTab === 'history'
                  ? 'border-purple-500 text-purple-600'
                  : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
              ]"
            >
              📋 Payment History
              <span class="ml-2 bg-purple-100 text-purple-800 px-2 py-1 rounded-full text-xs font-medium">
                {{ stats.verified_today || 0 }} today
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
                :placeholder="searchPlaceholder"
                @keyup.enter="handleFilterChange"
                @input="handleDebouncedFilter"
                class="w-full"
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

          <!-- Advanced Filters -->
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mt-4">
            <!-- Verification Tab Filters -->
            <template v-if="activeTab === 'verification' && $page.props.auth.user.role === 'admin'">
              <SelectInput
                v-model="filters.payment_method"
                :options="paymentMethodOptions"
                placeholder="Payment Method"
                @change="handleFilterChange"
              />
              <SelectInput
                v-model="filters.payment_source"
                :options="paymentSourceOptions"
                placeholder="Payment Source"
                @change="handleFilterChange"
              />
            </template>
            
            <!-- Collection Tab Filters -->
            <template v-else-if="activeTab === 'collection'">
              <SelectInput
                v-model="filters.collection_type"
                :options="collectionTypeOptions"
                placeholder="Collection Type"
                @change="handleCollectionTypeChange"
              />
              <SelectInput
                v-model="filters.payment_status"
                :options="paymentStatusOptions"
                placeholder="Payment Status"
                @change="handleFilterChange"
              />
            </template>
            
            <!-- History Tab Filters -->
            <template v-else-if="activeTab === 'history'">
              <SelectInput
                v-model="filters.history_status"
                :options="historyStatusOptions"
                placeholder="History Status"
                @change="handleFilterChange"
              />
              <SelectInput
                v-model="filters.verified_by"
                :options="verifiedByOptions"
                placeholder="Verified By"
                @change="handleFilterChange"
              />
            </template>
          </div>

          <!-- Filter Info -->
          <div class="flex justify-between items-center mt-4">
            <div class="text-sm text-gray-500">
              Showing {{ getCurrentDataCount() }} {{ getItemName() }}
              <span v-if="filters.search" class="ml-2">• "{{ filters.search }}"</span>
              <span v-if="filters.payment_method && activeTab === 'verification' && $page.props.auth.user.role === 'admin'" class="ml-2">
                • {{ getPaymentMethodLabel(filters.payment_method) }}
              </span>
              <span v-if="filters.payment_source && activeTab === 'verification' && $page.props.auth.user.role === 'admin'" class="ml-2">
                • {{ getPaymentSourceLabel(filters.payment_source) }}
              </span>
              <span v-if="filters.collection_type && activeTab === 'collection'" class="ml-2">
                • {{ getCollectionTypeLabel(filters.collection_type) }}
              </span>
              <span v-if="filters.payment_status && activeTab === 'collection'" class="ml-2">
                • {{ getPaymentStatusLabel(filters.payment_status) }}
              </span>
              <span v-if="filters.history_status && activeTab === 'history'" class="ml-2">
                • {{ getHistoryStatusLabel(filters.history_status) }}
              </span>
            </div>
          </div>
        </div>
      </div>

      <!-- Tab Content -->
      
      <!-- Verification Queue - Only show for admin -->
      <div v-if="activeTab === 'verification' && $page.props.auth.user.role === 'admin'">
        <div class="bg-white shadow-sm rounded-lg overflow-hidden border border-gray-200 max-w-screen-xl mx-auto">
          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
              <thead class="bg-gray-50">
                <tr>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reference & Method</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Customer & Amount</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Source & Submitted</th>
                  <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
              </thead>
              <tbody class="bg-white divide-y divide-gray-200">
                <tr v-for="payment in verificationPayments.data" :key="payment.id" class="hover:bg-gray-50">
                  <td class="px-6 py-4 whitespace-nowrap">
                    <div class="text-sm font-medium text-gray-900">
                      {{ payment.delivery_request?.reference_number || 'N/A' }}
                    </div>
                    <div class="text-sm text-gray-500 capitalize">
                      {{ payment.method }} 
                      <span v-if="payment.reference_number">#{{ payment.reference_number }}</span>
                    </div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <div class="text-sm font-medium text-gray-900">
                      {{ payment.delivery_request?.sender?.name || 'N/A' }}
                    </div>
                    <div class="text-sm text-gray-500">
                      ₱{{ parseFloat(payment.amount || 0).toFixed(2) }}
                    </div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <div class="text-sm text-gray-900 capitalize">
                      {{ payment.source?.replace(/_/g, ' ') || 'N/A' }}
                    </div>
                    <div class="text-sm text-gray-500">
                      {{ formatDate(payment.created_at) }}
                    </div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                    <div class="flex justify-end space-x-2">
                      <SecondaryButton
                        @click="goToVerifyPayment(payment.id)"
                        size="xs"
                        class="flex items-center"
                      >
                        <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                        </svg>
                        Review
                      </SecondaryButton>
                      <DangerButton
                        @click="showRejectModal(payment)"
                        size="xs"
                        class="flex items-center"
                      >
                        <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                        Reject
                      </DangerButton>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Empty State -->
          <div v-if="verificationPayments.data.length === 0" class="text-center py-12">
            <CheckCircleIcon class="h-12 w-12 mx-auto text-gray-400" />
            <p class="text-gray-500 mt-2">No payments pending verification</p>
          </div>
        </div>

        <!-- Pagination for Verification -->
        <Pagination
          v-if="verificationPayments.meta && verificationPayments.meta.last_page > 1"
          :pagination="verificationPayments.meta"
          @page-changed="(page) => handlePageChange(page, 'verification')"
          class="mt-6"
        />
      </div>

      <!-- Collection Management -->
      <div v-else-if="activeTab === 'collection'">
        <!-- Prepaid Collection -->
        <div v-if="filters.collection_type === 'all' || filters.collection_type === 'prepaid'" class="bg-white shadow-sm rounded-lg overflow-hidden border border-gray-200 max-w-screen-xl mx-auto mb-6">
          <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
            <div class="flex justify-between items-center">
              <div>
                <h3 class="text-lg font-medium text-gray-900">
                  Prepaid Deliveries Needing Payment
                </h3>
                <p class="mt-1 text-sm text-gray-500">
                  Record cash payments or track online payment status for prepaid deliveries.
                </p>
              </div>
              <div class="text-sm text-gray-500">
                Showing {{ prepaidRequests.data.length }} deliveries
              </div>
            </div>
          </div>
          
          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
              <thead class="bg-gray-50">
                <tr>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reference & Sender</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Payment Method & Amount</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                  <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
              </thead>
              <tbody class="bg-white divide-y divide-gray-200">
                <tr v-for="request in prepaidRequests.data" :key="request.id" class="hover:bg-gray-50">
                  <td class="px-6 py-4 whitespace-nowrap">
                    <div class="text-sm font-medium text-gray-900">
                      {{ request.reference_number || `DR-${String(request.id).padStart(6, '0')}` }}
                    </div>
                    <div class="text-sm text-gray-500">
                      {{ request.sender?.name || 'N/A' }}
                    </div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <div class="text-sm font-medium text-gray-900 capitalize">
                      {{ request.payment_method }}
                    </div>
                    <div class="text-sm text-gray-500">
                      ₱{{ parseFloat(request.total_price || 0).toFixed(2) }}
                    </div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <StatusBadge 
                      :status="getPaymentStatus(request)" 
                      :variant="getStatusVariant(request)"
                    >
                      {{ getStatusLabel(request) }}
                    </StatusBadge>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                    <PrimaryButton
                      @click="goToRecordPayment(request.id, 'prepaid')"
                      size="xs"
                      class="flex items-center"
                    >
                      <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                      </svg>
                      Record Payment
                    </PrimaryButton>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Empty State -->
          <div v-if="prepaidRequests.data.length === 0" class="text-center py-12">
            <CheckCircleIcon class="h-12 w-12 mx-auto text-gray-400" />
            <p class="text-gray-500 mt-2">No prepaid deliveries needing payment</p>
          </div>

          <!-- Pagination for Prepaid -->
          <Pagination
            v-if="prepaidRequests.meta && prepaidRequests.meta.last_page > 1"
            :pagination="prepaidRequests.meta"
            @page-changed="(page) => handlePageChange(page, 'prepaid')"
            class="mt-6"
          />
        </div>

        <!-- Postpaid Collection -->
        <div v-if="filters.collection_type === 'all' || filters.collection_type === 'postpaid'" class="bg-white shadow-sm rounded-lg overflow-hidden border border-gray-200 max-w-screen-xl mx-auto">
          <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
            <div class="flex justify-between items-center">
              <div>
                <h3 class="text-lg font-medium text-gray-900">
                  Postpaid Deliveries Needing Collection
                </h3>
                <p class="mt-1 text-sm text-gray-500">
                  Track postpaid deliveries that require payment collection.
                </p>
              </div>
              <div class="text-sm text-gray-500">
                Showing {{ postpaidRequests.data.length }} deliveries
              </div>
            </div>
          </div>
          
          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
              <thead class="bg-gray-50">
                <tr>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Order & Sender</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount & Status</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Delivery Info</th>
                  <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
              </thead>
              <tbody class="bg-white divide-y divide-gray-200">
                <tr v-for="order in postpaidRequests.data" :key="order.id" class="hover:bg-gray-50">
                  <td class="px-6 py-4 whitespace-nowrap">
                    <div class="text-sm font-medium text-gray-900">
                      {{ order.delivery_request?.reference_number || `DR-${String(order.delivery_request?.id).padStart(6, '0')}` }}
                    </div>
                    <div class="text-sm text-gray-500">
                      {{ order.delivery_request?.sender?.name || 'N/A' }}
                    </div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <div class="text-sm font-medium text-gray-900">
                      ₱{{ parseFloat(order.delivery_request?.total_price || 0).toFixed(2) }}
                    </div>
                    <StatusBadge 
                      :status="order.payment_status" 
                      :variant="order.payment_status === 'collected' ? 'info' : 'warning'"
                      class="mt-1"
                    >
                      {{ order.payment_status === 'collected' ? 'Collected' : 'Uncollected' }}
                    </StatusBadge>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <div class="text-sm text-gray-900">
                      {{ order.status }}
                    </div>
                    <div class="text-sm text-gray-500">
                      {{ order.driver?.name || 'No driver' }}
                    </div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                    <SecondaryButton
                      v-if="order.payment_status === 'collected' && order.delivery_request?.payment"
                      @click="goToPaymentDetails(order.delivery_request.payment.id)"
                      size="xs"
                      class="flex items-center"
                    >
                      <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                      </svg>
                      View Payment
                    </SecondaryButton>
                    <span v-else class="text-xs text-gray-500">
                      Awaiting collection
                    </span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Empty State -->
          <div v-if="postpaidRequests.data.length === 0" class="text-center py-12">
            <CheckCircleIcon class="h-12 w-12 mx-auto text-gray-400" />
            <p class="text-gray-500 mt-2">No postpaid deliveries needing collection</p>
          </div>

          <!-- Pagination for Postpaid -->
          <Pagination
            v-if="postpaidRequests.meta && postpaidRequests.meta.last_page > 1"
            :pagination="postpaidRequests.meta"
            @page-changed="(page) => handlePageChange(page, 'postpaid')"
            class="mt-6"
          />
        </div>
      </div>

      <!-- Payment History -->
      <div v-else-if="activeTab === 'history'">
        
        <div class="bg-white shadow-sm rounded-lg overflow-hidden border border-gray-200 max-w-screen-xl mx-auto">
          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
              <thead class="bg-gray-50">
                <tr>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reference & Customer</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Method & Amount</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status & Verifier</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date Processed</th>
                  <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
              </thead>
              <tbody class="bg-white divide-y divide-gray-200">
                <tr v-for="payment in historyPayments.data" :key="payment.id" class="hover:bg-gray-50">
                  <td class="px-6 py-4 whitespace-nowrap">
                    <div class="text-sm font-medium text-gray-900">
                      {{ payment.delivery_request?.reference_number || 'N/A' }}
                    </div>
                    <div class="text-sm text-gray-500">
                      {{ payment.delivery_request?.sender?.name || 'N/A' }}
                    </div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <div class="text-sm text-gray-900 capitalize">
                      {{ payment.method }}
                    </div>
                    <div class="text-sm text-gray-500">
                      ₱{{ parseFloat(payment.amount || 0).toFixed(2) }}
                    </div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <StatusBadge 
                      :status="payment.status" 
                      :variant="payment.status === 'verified' ? 'success' : 'danger'"
                    >
                      {{ payment.status === 'verified' ? 'Verified' : 'Rejected' }}
                    </StatusBadge>
                    <div class="text-sm text-gray-500 mt-1">
                      by {{ payment.verified_by?.name || payment.rejected_by?.name || 'System' }}
                    </div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <div class="text-sm text-gray-900">
                      {{ formatDate(payment.verified_at || payment.rejected_at || payment.updated_at) }}
                    </div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                    <SecondaryButton
                      @click="goToPaymentDetails(payment.id)"
                      size="xs"
                      class="flex items-center"
                    >
                      <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                      </svg>
                      View Payment
                    </SecondaryButton>
                  </td>
                </tr>
              </tbody>
              
            </table>
          </div>

          <!-- Empty State -->
          <div v-if="historyPayments.data.length === 0" class="text-center py-12">
            <ArchiveBoxIcon class="h-12 w-12 mx-auto text-gray-400" />
            <p class="text-gray-500 mt-2">No payment history found</p>
          </div>
        </div>

        <!-- Pagination for History -->
    <Pagination
  v-if="historyPayments.meta && historyPayments.meta.last_page > 1 && historyPayments.data.length > 0"
  :pagination="historyPayments.meta"
  @page-changed="(page) => handlePageChange(page, 'history')"
  class="mt-6"
/>
      </div>
    </div>

  <!-- Reject Modal (Compact Version) -->
<Modal :show="showModal" @close="closeRejectModal" max-width="lg">
  <div class="p-4">
    <!-- Header -->
    <div class="flex items-center mb-3">
      <div class="h-8 w-8 bg-red-100 rounded-full flex items-center justify-center">
        <svg class="h-5 w-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.35 16.5c-.77.833.192 2.5 1.732 2.5z" />
        </svg>
      </div>
      <div class="ml-2">
        <h2 class="text-base font-semibold text-gray-900">Reject Payment</h2>
        <p class="text-xs text-gray-500">This action cannot be undone</p>
      </div>
    </div>

    <!-- Payment Details -->
    <div class="bg-gray-50 rounded-md p-3 mb-4">
      <h3 class="text-xs font-medium text-gray-900 mb-2">Payment Details</h3>
      <div class="grid grid-cols-2 gap-2 text-xs">
        <div>
          <span class="text-gray-500">Reference:</span>
          <p class="font-medium truncate">{{ selectedPayment?.delivery_request?.reference_number || 'N/A' }}</p>
        </div>
        <div>
          <span class="text-gray-500">Customer:</span>
          <p class="font-medium truncate">{{ selectedPayment?.delivery_request?.sender?.name || 'N/A' }}</p>
        </div>
        <div>
          <span class="text-gray-500">Amount:</span>
          <p class="font-medium">₱{{ selectedPayment?.amount ? parseFloat(selectedPayment.amount).toFixed(2) : '0.00' }}</p>
        </div>
        <div>
          <span class="text-gray-500">Method:</span>
          <p class="font-medium capitalize">{{ selectedPayment?.method || 'N/A' }}</p>
        </div>
      </div>
    </div>

    <!-- Rejection Form -->
    <form @submit.prevent="rejectPayment" class="space-y-4">
      <div>
        <label class="block text-xs font-medium text-gray-700 mb-2">
          Rejection Reason <span class="text-red-500">*</span>
        </label>

        <!-- Quick Buttons -->
        <div class="grid grid-cols-2 gap-1 mb-2">
          <button
            v-for="reason in commonRejectionReasons"
            :key="reason.value"
            type="button"
            @click="selectRejectionReason(reason.value)"
            :class="[
              'text-xs px-2 py-1.5 rounded border transition-colors text-left',
              rejectForm.rejection_reason === reason.value
                ? 'bg-red-100 border-red-300 text-red-700'
                : 'bg-white border-gray-300 text-gray-700 hover:bg-gray-50'
            ]"
          >
            {{ reason.label }}
          </button>
        </div>

        <TextArea
          v-model="rejectForm.rejection_reason"
          placeholder="Reason for rejection..."
          rows="2"
          class="w-full text-sm"
          required
        />
        <InputError :message="rejectForm.errors.rejection_reason" class="mt-1 text-xs" />
        <p class="text-xs text-gray-500 mt-1">Visible to the customer.</p>
      </div>

      <!-- Internal Notes -->
      <div>
        <label class="block text-xs font-medium text-gray-700 mb-1">
          Internal Notes (Optional)
        </label>
        <TextArea
          v-model="rejectForm.internal_notes"
          placeholder="For internal staff reference..."
          rows="2"
          class="w-full text-sm"
        />
      </div>

      <!-- Warning -->
      <div class="bg-yellow-50 border border-yellow-200 rounded-md p-3">
        <div class="flex">
          <svg class="h-4 w-4 text-yellow-400 mr-2 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.35 16.5c-.77.833.192 2.5 1.732 2.5z" />
          </svg>
          <div class="text-xs text-yellow-700">
            <p class="font-medium">Confirm this rejection</p>
            <p>This will mark it as rejected and notify the customer.</p>
          </div>
        </div>
      </div>

      <!-- Actions -->
      <div class="flex justify-end space-x-2 border-t border-gray-200 pt-3">
        <SecondaryButton 
          type="button" 
          @click="closeRejectModal"
          :disabled="rejectForm.processing"
          class="text-xs px-3 py-1.5"
        >
          Cancel
        </SecondaryButton>
        <DangerButton 
          type="submit" 
          :disabled="rejectForm.processing || !rejectForm.rejection_reason"
          :loading="rejectForm.processing"
          class="text-xs px-3 py-1.5"
        >
          <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
          Reject
        </DangerButton>
      </div>
    </form>
  </div>
</Modal>

  </EmployeeLayout>
</template>

<script setup>
import EmployeeLayout from '@/Layouts/EmployeeLayout.vue';
import { Link, router, useForm } from '@inertiajs/vue3';
import { ref, computed, reactive, onMounted } from 'vue';
import {
  CheckCircleIcon,
  ArchiveBoxIcon,
} from '@heroicons/vue/24/outline';

import SearchInput from '@/Components/SearchInput.vue';
import SelectInput from '@/Components/SelectInput.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import Modal from '@/Components/Modal.vue';
import TextArea from '@/Components/TextArea.vue';
import InputError from '@/Components/InputError.vue';
import DangerButton from '@/Components/DangerButton.vue';
import Pagination from '@/Components/Pagination.vue';
import { watch } from 'vue';

const props = defineProps({
  activeTab: {
    type: String,
    default: 'verification'
  },
  verificationPayments: {
    type: Object,
    default: () => ({ data: [], meta: {} })
  },
  prepaidRequests: {
    type: Object,
    default: () => ({ data: [], meta: {} })
  },
  postpaidRequests: {
    type: Object,
    default: () => ({ data: [], meta: {} })
  },
  historyPayments: {
    type: Object,
    default: () => ({ data: [], meta: {} })
  },
  stats: {
    type: Object,
    default: () => ({})
  },
  filters: {
    type: Object,
    default: () => ({})
  },
  auth: {
    type: Object,
    default: () => ({})
  }
});

const activeTab = ref(props.activeTab);
const showModal = ref(false);
const selectedPayment = ref(null);

// Set default tab to 'collection' for non-admin users if they somehow access verification
onMounted(() => {
  if (props.auth.user.role !== 'admin' && activeTab.value === 'verification') {
    activeTab.value = 'collection';
    // Also update the URL to reflect the change
    handleFilterChange();
  }
});

// Initialize filters from props
const filters = reactive({
  search: props.filters?.search || '',
  payment_method: props.filters?.payment_method || '',
  payment_source: props.filters?.payment_source || '',
  collection_type: props.filters?.collection_type || 'all',
  payment_status: props.filters?.payment_status || '',
  history_status: props.filters?.history_status || '',
  verified_by: props.filters?.verified_by || ''
});

// Computed options for filters
const paymentMethodOptions = computed(() => [
  { value: '', label: 'All Methods' },
  { value: 'cash', label: 'Cash' },
  { value: 'gcash', label: 'GCash' },
  { value: 'bank', label: 'Bank Transfer' }
]);

const paymentSourceOptions = computed(() => [
  { value: '', label: 'All Sources' },
  { value: 'online', label: 'Online' },
  { value: 'branch_staff', label: 'Branch Staff' },
  { value: 'collector', label: 'Collector' }
]);

const collectionTypeOptions = computed(() => [
  { value: 'all', label: 'All Types' },
  { value: 'prepaid', label: 'Prepaid Deliveries' },
  { value: 'postpaid', label: 'Postpaid Deliveries' }
]);

const paymentStatusOptions = computed(() => [
  { value: '', label: 'All Statuses' },
  { value: 'unpaid', label: 'Unpaid' },
  { value: 'pending', label: 'Pending' }
]);

const historyStatusOptions = computed(() => [
  { value: '', label: 'All History' },
  { value: 'verified', label: 'Verified' },
  { value: 'rejected', label: 'Rejected' }
]);

const verifiedByOptions = computed(() => [
  { value: '', label: 'All Verifiers' },
  { value: 'system', label: 'System' },
  { value: 'staff', label: 'Staff' }
]);

const searchPlaceholder = computed(() => {
  switch (activeTab.value) {
    case 'verification': return 'Search by reference, customer, or amount...';
    case 'collection': return 'Search by reference, sender, or amount...';
    case 'history': return 'Search by reference, customer, or amount...';
    default: return 'Search...';
  }
});
watch(() => props.historyPayments.meta, (newMeta) => {
    console.log('History Payments Meta:', newMeta);
}, { immediate: true });


const commonRejectionReasons = ref([
  { value: 'insufficient_funds', label: 'Insufficient funds' },
  { value: 'invalid_reference', label: 'Invalid reference number' },
  { value: 'suspected_fraud', label: 'Suspected fraud' },
  { value: 'poor_quality', label: 'Unclear proof of payment' },
  { value: 'duplicate_payment', label: 'Duplicate payment' },
  { value: 'expired_receipt', label: 'Expired receipt' },
  { value: 'other', label: 'Other reason' }
]);

// Update the rejectForm to include internal_notes
const rejectForm = useForm({
  rejection_reason: '',
  internal_notes: ''
});
// Add method to select rejection reason
function selectRejectionReason(reason) {
  rejectForm.rejection_reason = reason;
}

// Methods
function getCurrentDataCount() {
  switch (activeTab.value) {
    case 'verification': return props.verificationPayments.data.length;
    case 'collection': 
      if (filters.collection_type === 'prepaid') {
        return props.prepaidRequests.data.length;
      } else if (filters.collection_type === 'postpaid') {
        return props.postpaidRequests.data.length;
      } else {
        return props.prepaidRequests.data.length + props.postpaidRequests.data.length;
      }
    case 'history': return props.historyPayments.data.length;
    default: return 0;
  }
}

function getItemName() {
  switch (activeTab.value) {
    case 'verification': return 'payments';
    case 'collection': 
      if (filters.collection_type === 'prepaid') {
        return 'prepaid deliveries';
      } else if (filters.collection_type === 'postpaid') {
        return 'postpaid deliveries';
      } else {
        return 'deliveries';
      }
    case 'history': return 'payments';
    default: return 'items';
  }
}

function switchTab(tab) {
  // Prevent non-admin users from switching to verification tab
  if (tab === 'verification' && props.auth.user.role !== 'admin') {
    return;
  }
  
  activeTab.value = tab;
  // Reset filters when switching tabs
  Object.keys(filters).forEach(key => {
    if (key !== 'collection_type') {
      filters[key] = '';
    }
  });
  filters.collection_type = 'all';
  
  handleFilterChange();
}

function handleFilterChange() {
  const payload = {
    tab: activeTab.value,
    ...filters
  };
  
  // Remove empty filters
  Object.keys(payload).forEach(key => {
    if (payload[key] === '' || payload[key] === null) {
      delete payload[key];
    }
  });
  
  router.visit(route('staff.payments.dashboard'), {
    data: payload,
    preserveState: true,
    preserveScroll: true,
    replace: true
  });
}

function handleCollectionTypeChange() {
  // When collection type changes, we need to update the display immediately
  handleFilterChange();
}

// Debounced search
let searchTimeout = null;
function handleDebouncedFilter() {
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => {
    handleFilterChange();
  }, 500);
}

function resetFilters() {
  Object.keys(filters).forEach(key => {
    filters[key] = '';
  });
  filters.collection_type = 'all';
  handleFilterChange();
}

function refreshData() {
  router.reload({
    only: ['verificationPayments', 'prepaidRequests', 'postpaidRequests', 'historyPayments', 'stats'],
    preserveScroll: true
  });
}

function handlePageChange(page, type) {
  if (page >= 1 && page <= getLastPage(type)) {
    const payload = {
      tab: activeTab.value,
      ...filters
    };
    
    // Set the correct page parameter based on the type
    if (type === 'verification') {
      payload.verification_page = page;
    } else if (type === 'prepaid') {
      payload.prepaid_page = page;
    } else if (type === 'postpaid') {
      payload.postpaid_page = page;
    } else if (type === 'history') {
      payload.history_page = page;
    }
    
    // Remove empty filters
    Object.keys(payload).forEach(key => {
      if (payload[key] === '' || payload[key] === null) {
        delete payload[key];
      }
    });
    
    router.visit(route('staff.payments.dashboard'), {
      data: payload,
      preserveState: true,
      preserveScroll: true
    });
  }
}

function getLastPage(type) {
  switch (type) {
    case 'verification': return props.verificationPayments.meta?.last_page || 1;
    case 'prepaid': return props.prepaidRequests.meta?.last_page || 1;
    case 'postpaid': return props.postpaidRequests.meta?.last_page || 1;
    case 'history': return props.historyPayments.meta?.last_page || 1;
    default: return 1;
  }
}

// Add method to close modal
function closeRejectModal() {
  showModal.value = false;
  selectedPayment.value = null;
  rejectForm.clearErrors();
  rejectForm.reset();
}

// Helper methods for filter labels
function getPaymentMethodLabel(method) {
  const labels = {
    'cash': 'Cash',
    'gcash': 'GCash', 
    'bank': 'Bank Transfer'
  };
  return labels[method] || method;
}

function getPaymentSourceLabel(source) {
  const labels = {
    'online': 'Online',
    'branch_staff': 'Branch Staff',
    'collector': 'Collector'
  };
  return labels[source] || source;
}

function getCollectionTypeLabel(type) {
  const labels = {
    'all': 'All Types',
    'prepaid': 'Prepaid',
    'postpaid': 'Postpaid'
  };
  return labels[type] || type;
}

function getPaymentStatusLabel(status) {
  const labels = {
    'unpaid': 'Unpaid',
    'pending': 'Pending',
    'paid': 'Paid'
  };
  return labels[status] || status;
}

function getHistoryStatusLabel(status) {
  const labels = {
    'verified': 'Verified',
    'rejected': 'Rejected'
  };
  return labels[status] || status;
}

// Update the showRejectModal function
const showRejectModal = (payment) => {
  selectedPayment.value = payment;
  rejectForm.rejection_reason = '';
  rejectForm.internal_notes = '';
  showModal.value = true;
};

// Update the rejectPayment method to include internal_notes
const rejectPayment = () => {
  if (!selectedPayment.value) return;
  
  rejectForm.post(route('staff.payments.reject', selectedPayment.value.id), {
    preserveScroll: true,
    onSuccess: () => {
      closeRejectModal();
      router.reload({ only: ['verificationPayments', 'stats'] });
    },
  });
};

const goToVerifyPayment = (paymentId) => {
  router.visit(route('staff.payments.verify', paymentId));
};

const goToRecordPayment = (deliveryId, type) => {
  router.visit(route('staff.payments.create', { delivery_id: deliveryId }));
};

const goToPaymentDetails = (paymentId) => {
  router.visit(route('staff.payments.show', paymentId));
};

const getPaymentStatus = (request) => {
  // If payment exists and is verified, return 'verified'
  if (request.payment?.status === 'verified') return 'verified';
  if (request.payment?.status === 'rejected') return 'rejected';
  if (request.payment) return 'pending_verification';
  
  // Fall back to delivery request payment status
  return request.payment_status || 'unpaid';
};

const getStatusVariant = (request) => {
  const status = getPaymentStatus(request);
  switch (status) {
    case 'verified': return 'success';
    case 'rejected': return 'danger';
    case 'pending_verification': return 'warning';
    default: return 'secondary';
  }
};

const getStatusLabel = (request) => {
  const status = getPaymentStatus(request);
  switch (status) {
    case 'verified': return 'Verified';
    case 'rejected': return 'Rejected';
    case 'pending_verification': return 'Pending Verification';
    default: return 'Unpaid';
  }
};

const formatDate = (dateString) => {
  if (!dateString) return 'N/A';
  return new Date(dateString).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
  });
};

// Add this to see what meta data you're receiving
// Add this computed property in your script section

</script>