<template>
  <EmployeeLayout>
    <template #header>
      <div class="flex justify-between items-center">
        <div>
          <h2 class="text-xl font-semibold text-gray-800">
            Payment Management
          </h2>
          <p class="text-sm text-gray-600 mt-1">
            Manage all payment activities in one place
          </p>
        </div>
        <div class="flex items-center space-x-4">
          <!-- Global search for current tab -->
          <SearchInput
            v-model="currentSearch"
            :placeholder="searchPlaceholder"
            class="w-64"
            @keyup.enter="applySearch"
          />
          <PrimaryButton @click="applySearch">
            Search
          </PrimaryButton>
        </div>
      </div>
    </template>

    <div class="py-6">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <!-- Tab Navigation -->
        <div class="flex space-x-1 mb-6 border-b border-gray-200">
          <TabButton 
            :active="activeTab === 'verification'" 
            @click="switchTab('verification')"
            class="flex items-center"
          >
            ⏳ Verification Queue
            <span class="ml-2 bg-yellow-100 text-yellow-800 px-2 py-1 rounded-full text-xs font-medium">
              {{ stats.pending_verification || 0 }}
            </span>
          </TabButton>
          
          <TabButton 
            :active="activeTab === 'collection'" 
            @click="switchTab('collection')"
            class="flex items-center"
          >
            💰 Collection Management
            <span class="ml-2 bg-blue-100 text-blue-800 px-2 py-1 rounded-full text-xs font-medium">
              {{ stats.needs_collection || 0 }}
            </span>
          </TabButton>
          
          <TabButton 
            :active="activeTab === 'history'" 
            @click="switchTab('history')"
            class="flex items-center"
          >
            📋 Payment History
            <span class="ml-2 bg-green-100 text-green-800 px-2 py-1 rounded-full text-xs font-medium">
              {{ stats.verified_today || 0 }} today
            </span>
          </TabButton>
        </div>

        <!-- Tab Content -->
        <div v-if="activeTab === 'verification'" class="space-y-6">
          <!-- Verification Queue -->
          <div class="bg-white shadow rounded-lg">
            <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
              <h3 class="text-lg font-medium text-gray-900">
                Payments Needing Verification
              </h3>
              <p class="mt-1 text-sm text-gray-500">
                Review online payments and collector submissions. Verify or reject with reasons.
              </p>
            </div>
            
            <div class="px-4 py-5 sm:p-6">
              <div v-if="verificationPayments.data.length === 0" class="text-center py-8">
                <CheckCircleIcon class="mx-auto h-12 w-12 text-green-500" />
                <h3 class="mt-2 text-sm font-medium text-gray-900">All caught up!</h3>
                <p class="mt-1 text-sm text-gray-500">No payments pending verification.</p>
              </div>
              
              <div v-else class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                  <thead class="bg-gray-50">
                    <tr>
                      <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Reference & Method
                      </th>
                      <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Customer & Amount
                      </th>
                      <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Source & Submitted
                      </th>
                      <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Actions
                      </th>
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
                          <span class="text-xs">(Due: ₱{{ payment.delivery_request?.total_price || '0.00' }})</span>
                        </div>
                      </td>
                      <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm text-gray-900 capitalize">
                          {{ payment.source?.replace(/_/g, ' ') || 'N/A' }}
                        </div>
                        <div class="text-sm text-gray-500">
                          {{ formatDate(payment.created_at) }}
                          <span class="text-xs">by {{ payment.submitted_by?.name || 'System' }}</span>
                        </div>
                      </td>
                      <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                        <div class="flex justify-end space-x-2">
                          <Link
                            :href="route('staff.payments.verify', payment.id)"
                            class="text-blue-600 hover:text-blue-900 px-3 py-1 border border-blue-600 rounded text-xs"
                          >
                            Review
                          </Link>
                          <button
                            @click="showRejectModal(payment)"
                            class="text-red-600 hover:text-red-900 px-3 py-1 border border-red-600 rounded text-xs"
                          >
                            Reject
                          </button>
                        </div>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
              
              <Pagination
                v-if="verificationPayments.meta && verificationPayments.meta.last_page > 1"
                :pagination="verificationPayments.meta"
                @page-changed="(page) => handlePageChange(page, 'verification')"
                class="mt-6"
              />
            </div>
          </div>
        </div>

        <div v-if="activeTab === 'collection'" class="space-y-6">
          <!-- Collection Type Filter -->
          <div class="flex space-x-4 mb-4">
            <select v-model="collectionType" @change="applyCollectionFilters" class="border-gray-300 rounded-md shadow-sm">
              <option value="all">All Types</option>
              <option value="prepaid">Prepaid Deliveries</option>
              <option value="postpaid">Postpaid Deliveries</option>
            </select>
          </div>

          <!-- Prepaid Collection -->
          <div v-if="collectionType === 'all' || collectionType === 'prepaid'" class="bg-white shadow rounded-lg">
            <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
              <h3 class="text-lg font-medium text-gray-900">
                Prepaid Deliveries Needing Payment
              </h3>
              <p class="mt-1 text-sm text-gray-500">
                Record cash payments or track online payment status for prepaid deliveries.
              </p>
            </div>
            
            <div class="px-4 py-5 sm:p-6">
              <div v-if="prepaidRequests.data.length === 0" class="text-center py-4 text-sm text-gray-500">
                No prepaid deliveries needing payment.
              </div>
              
              <div v-else class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                  <thead class="bg-gray-50">
                    <tr>
                      <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Reference & Sender
                      </th>
                      <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Payment Method & Amount
                      </th>
                      <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Status
                      </th>
                      <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Actions
                      </th>
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
                          class="text-xs"
                        >
                          Record Payment
                        </PrimaryButton>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
              
              <Pagination
                v-if="prepaidRequests.meta && prepaidRequests.meta.last_page > 1"
                :pagination="prepaidRequests.meta"
                @page-changed="(page) => handlePageChange(page, 'prepaid')"
                class="mt-6"
              />
            </div>
          </div>

          <!-- Postpaid Collection -->
          <div v-if="collectionType === 'all' || collectionType === 'postpaid'" class="bg-white shadow rounded-lg mt-6">
            <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
              <h3 class="text-lg font-medium text-gray-900">
                Postpaid Deliveries Needing Collection
              </h3>
              <p class="mt-1 text-sm text-gray-500">
                Track postpaid deliveries that require payment collection after delivery.
              </p>
            </div>
            
            <div class="px-4 py-5 sm:p-6">
              <div v-if="postpaidRequests.data.length === 0" class="text-center py-4 text-sm text-gray-500">
                No postpaid deliveries needing collection.
              </div>
              
              <div v-else class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                  <thead class="bg-gray-50">
                    <tr>
                      <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Order & Sender
                      </th>
                      <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Amount & Status
                      </th>
                      <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Delivery Info
                      </th>
                      <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Actions
                      </th>
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
                          class="text-xs"
                        >
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
              
              <Pagination
                v-if="postpaidRequests.meta && postpaidRequests.meta.last_page > 1"
                :pagination="postpaidRequests.meta"
                @page-changed="(page) => handlePageChange(page, 'postpaid')"
                class="mt-6"
              />
            </div>
          </div>
        </div>

        <div v-if="activeTab === 'history'" class="space-y-6">
          <!-- Payment History -->
          <div class="bg-white shadow rounded-lg">
            <div class="px-4 py-5 sm:px-6 border-b border-gray-200">
              <h3 class="text-lg font-medium text-gray-900">
                Payment History
              </h3>
              <p class="mt-1 text-sm text-gray-500">
                View all processed payments - verified, rejected, and historical records.
              </p>
            </div>
            
            <div class="px-4 py-5 sm:p-6">
              <div v-if="historyPayments.data.length === 0" class="text-center py-8">
                <ArchiveBoxIcon class="mx-auto h-12 w-12 text-gray-400" />
                <h3 class="mt-2 text-sm font-medium text-gray-900">No payment history</h3>
                <p class="mt-1 text-sm text-gray-500">Processed payments will appear here.</p>
              </div>
              
              <div v-else class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                  <thead class="bg-gray-50">
                    <tr>
                      <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Reference & Customer
                      </th>
                      <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Method & Amount
                      </th>
                      <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Status & Verifier
                      </th>
                      <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Date Processed
                      </th>
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
                    </tr>
                  </tbody>
                </table>
              </div>
              
              <Pagination
                v-if="historyPayments.meta && historyPayments.meta.last_page > 1"
                :pagination="historyPayments.meta"
                @page-changed="(page) => handlePageChange(page, 'history')"
                class="mt-6"
              />
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Reject Modal (same as before) -->
    <Modal :show="showModal" @close="showModal = false">
      <div class="p-6">
        <h2 class="text-lg font-medium text-gray-900 mb-4">
          Reject Payment
        </h2>
        <form @submit.prevent="rejectPayment">
          <TextArea
            v-model="rejectForm.rejection_reason"
            placeholder="Enter rejection reason..."
            rows="4"
            class="w-full"
          />
          <InputError :message="rejectForm.errors.rejection_reason" class="mt-2" />
          <div class="mt-6 flex justify-end space-x-3">
            <SecondaryButton @click="showModal = false">
              Cancel
            </SecondaryButton>
            <DangerButton type="submit" :disabled="rejectForm.processing">
              Confirm Reject
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
import { ref, computed, watch } from 'vue';
import {
  CheckCircleIcon,
  ArchiveBoxIcon,
} from '@heroicons/vue/24/outline';

import SearchInput from '@/Components/SearchInput.vue';
import TabButton from '@/Components/TabButton.vue';
import Pagination from '@/Components/Pagination.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import Modal from '@/Components/Modal.vue';
import TextArea from '@/Components/TextArea.vue';
import InputError from '@/Components/InputError.vue';
import DangerButton from '@/Components/DangerButton.vue';

const props = defineProps({
  activeTab: {
    type: String,
    default: 'verification'
  },
  verificationPayments: {
    type: Object,
    default: () => ({ data: [] })
  },
  prepaidRequests: {
    type: Object,
    default: () => ({ data: [] })
  },
  postpaidRequests: {
    type: Object,
    default: () => ({ data: [] })
  },
  historyPayments: {
    type: Object,
    default: () => ({ data: [] })
  },
  stats: {
    type: Object,
    default: () => ({})
  },
  filters: {
    type: Object,
    default: () => ({})
  }
});

const activeTab = ref(props.activeTab);
const currentSearch = ref(props.filters.search || '');
const collectionType = ref(props.filters.collection_type || 'all');
const showModal = ref(false);
const selectedPayment = ref(null);

const searchPlaceholder = computed(() => {
  switch (activeTab.value) {
    case 'verification': return 'Search payments...';
    case 'collection': return 'Search deliveries...';
    case 'history': return 'Search history...';
    default: return 'Search...';
  }
});

const rejectForm = useForm({
  rejection_reason: '',
});

const switchTab = (tab) => {
  activeTab.value = tab;
  currentSearch.value = '';
  router.get(route('staff.payments.dashboard'), {
    tab: tab
  }, {
    preserveState: true,
    preserveScroll: true,
    replace: true
  });
};

const applySearch = () => {
  const params = {
    tab: activeTab.value,
  };

  if (currentSearch.value) {
    if (activeTab.value === 'collection') {
      params.collection_search = currentSearch.value;
    } else if (activeTab.value === 'history') {
      params.history_search = currentSearch.value;
    } else {
      params.search = currentSearch.value;
    }
  }

  router.get(route('staff.payments.dashboard'), params, {
    preserveState: true,
    preserveScroll: true,
  });
};

const applyCollectionFilters = () => {
  router.get(route('staff.payments.dashboard'), {
    tab: 'collection',
    collection_type: collectionType.value,
    collection_search: currentSearch.value,
  }, {
    preserveState: true,
    preserveScroll: true,
  });
};

const handlePageChange = (page, type) => {
  const params = {
    tab: activeTab.value,
  };

  // Set the correct page parameter based on the pagination type
  if (type === 'verification') {
    params.verification_page = page;
  } else if (type === 'prepaid') {
    params.prepaid_page = page;
  } else if (type === 'postpaid') {
    params.postpaid_page = page;
  } else if (type === 'history') {
    params.history_page = page;
  }

  router.get(route('staff.payments.dashboard'), params, {
    preserveState: true,
    preserveScroll: true,
  });
};

const showRejectModal = (payment) => {
  selectedPayment.value = payment;
  showModal.value = true;
};

const rejectPayment = () => {
  if (!selectedPayment.value) return;
  
  rejectForm.post(route('staff.payments.reject', selectedPayment.value.id), {
    preserveScroll: true,
    onSuccess: () => {
      showModal.value = false;
      rejectForm.reset();
      selectedPayment.value = null;
      router.reload({ only: ['verificationPayments', 'stats'] });
    },
  });
};

const goToRecordPayment = (deliveryId, type) => {
  router.visit(route('staff.payments.create', { delivery_id: deliveryId }));
};

const goToPaymentDetails = (paymentId) => {
  router.visit(route('staff.payments.show', paymentId));
};

const getPaymentStatus = (request) => {
  if (request.payment?.verified_by) return 'verified';
  if (request.payment?.rejected_by) return 'rejected';
  if (request.payment) return 'pending_verification';
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
</script>
[file content end]