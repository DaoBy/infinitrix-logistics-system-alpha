<template>
  <EmployeeLayout>
    <template #header>
      <div class="flex justify-between items-center">
        <div>
          <h2 class="text-xl font-semibold text-gray-800">
            Payment Verification
          </h2>
          <p class="text-sm text-gray-600 mt-1">
            Review and verify customer and collector payments
          </p>
        </div>
        <div class="flex space-x-4">
          <SearchInput
            v-model="filters.search"
            placeholder="Search references, names..."
            class="w-64"
          />
          <SelectInput
            v-model="filters.status"
            :options="statusOptions"
            class="w-40"
          />
          <SelectInput
            v-model="filters.source"
            :options="sourceOptions"
            class="w-48"
          />
        </div>
      </div>
    </template>

    <div class="py-6">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <!-- Stats Overview -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
          <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6">
              <div class="flex items-center">
                <div class="flex-shrink-0 bg-gray-100 rounded-md p-3">
                  <ClockIcon class="h-6 w-6 text-gray-600" />
                </div>
                <div class="ml-5 w-0 flex-1">
                  <dl>
                    <dt class="text-sm font-medium text-gray-500 truncate">
                      Pending Verification
                    </dt>
                    <dd class="flex items-baseline">
                      <div class="text-2xl font-semibold text-gray-900">
                        {{ stats?.pending || 0 }}
                      </div>
                    </dd>
                  </dl>
                </div>
              </div>
            </div>
          </div>

          <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6">
              <div class="flex items-center">
                <div class="flex-shrink-0 bg-green-100 rounded-md p-3">
                  <CheckCircleIcon class="h-6 w-6 text-green-600" />
                </div>
                <div class="ml-5 w-0 flex-1">
                  <dl>
                    <dt class="text-sm font-medium text-gray-500 truncate">
                      Verified Today
                    </dt>
                    <dd class="flex items-baseline">
                      <div class="text-2xl font-semibold text-gray-900">
                        {{ stats?.verified_today || 0 }}
                      </div>
                    </dd>
                  </dl>
                </div>
              </div>
            </div>
          </div>

          <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6">
              <div class="flex items-center">
                <div class="flex-shrink-0 bg-blue-100 rounded-md p-3">
                  <UsersIcon class="h-6 w-6 text-blue-600" />
                </div>
                <div class="ml-5 w-0 flex-1">
                  <dl>
                    <dt class="text-sm font-medium text-gray-500 truncate">
                      Customer Online
                    </dt>
                    <dd class="flex items-baseline">
                      <div class="text-2xl font-semibold text-gray-900">
                        {{ stats?.customer_online || 0 }}
                      </div>
                    </dd>
                  </dl>
                </div>
              </div>
            </div>
          </div>

          <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6">
              <div class="flex items-center">
                <div class="flex-shrink-0 bg-purple-100 rounded-md p-3">
                  <TruckIcon class="h-6 w-6 text-purple-600" />
                </div>
                <div class="ml-5 w-0 flex-1">
                  <dl>
                    <dt class="text-sm font-medium text-gray-500 truncate">
                      Collector
                    </dt>
                    <dd class="flex items-baseline">
                      <div class="text-2xl font-semibold text-gray-900">
                        {{ stats?.collector || 0 }}
                      </div>
                    </dd>
                  </dl>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Payments Table -->
        <div class="bg-white overflow-hidden shadow rounded-lg">
          <div class="px-4 py-5 sm:p-6">
            <div class="overflow-x-auto">
              <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                  <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Reference
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Customer
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Method & Source
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Amount
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Status
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Submitted
                    </th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Actions
                    </th>
                  </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                  <tr v-for="payment in payments?.data || []" :key="payment.id" class="hover:bg-gray-50">
                    <td class="px-6 py-4 whitespace-nowrap">
                      <div class="text-sm font-medium text-gray-900">
                        {{ payment.delivery_request?.reference_number || 'N/A' }}
                      </div>
                      <div class="text-sm text-gray-500">
                        {{ payment.method?.toUpperCase() || 'N/A' }}
                        <span v-if="payment.reference_number">
                          #{{ payment.reference_number }}
                        </span>
                      </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                      <div class="text-sm font-medium text-gray-900">
                        {{ payment.delivery_request?.sender?.name || 'N/A' }}
                      </div>
                      <div class="text-sm text-gray-500">
                        {{ payment.delivery_request?.sender?.mobile || 'N/A' }}
                      </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                      <div class="text-sm text-gray-900 capitalize">
                        {{ payment.method || 'N/A' }}
                      </div>
                      <div class="text-sm text-gray-500 capitalize">
                        {{ payment.source?.replace('_', ' ') || 'N/A' }}
                      </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                      <div class="text-sm font-medium text-gray-900">
                        ₱{{ parseFloat(payment.amount || 0).toFixed(2) }}
                      </div>
                      <div class="text-sm text-gray-500">
                        Due: ₱{{ payment.delivery_request?.total_price || '0.00' }}
                      </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                      <span :class="[
                        'inline-flex px-2 py-1 text-xs font-semibold rounded-full',
                        payment.verified_by ? 'bg-green-100 text-green-800' :
                        payment.rejected_by ? 'bg-red-100 text-red-800' :
                        'bg-yellow-100 text-yellow-800'
                      ]">
                        {{ payment.status ? formatStatus(payment.status) : 
                           payment.verified_by ? 'Verified' : 
                           payment.rejected_by ? 'Rejected' : 'Pending' }}
                      </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                      <div class="text-sm text-gray-900">
                        {{ formatDate(payment.created_at) }}
                      </div>
                      <div class="text-sm text-gray-500">
                        by {{ payment.submitted_by?.name || 'System' }}
                      </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                      <div class="flex justify-end space-x-2">
                        <Link
                          :href="route('staff.payments.verify', payment.id)"
                          class="text-blue-600 hover:text-blue-900"
                        >
                          Review
                        </Link>
                        <button
                          v-if="!payment.verified_by && !payment.rejected_by"
                          @click="showRejectModal(payment)"
                          class="text-red-600 hover:text-red-900"
                        >
                          Reject
                        </button>
                      </div>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <!-- Pagination -->
            <Pagination
              v-if="payments?.meta && payments.meta.last_page > 1"
              :pagination="payments.meta"
              @page-changed="handlePageChange"
              class="mt-6"
            />
          </div>
        </div>
      </div>
    </div>

    <!-- Reject Modal -->
    <Modal :show="showModal" @close="showModal = false">
      <div class="p-6">
        <h2 class="text-lg font-medium text-gray-900 mb-4">
          Reject Payment
        </h2>
        <p class="text-sm text-gray-600 mb-4">
          Please provide a reason for rejecting this payment. The customer will be notified.
        </p>
        
        <form @submit.prevent="rejectPayment">
          <TextArea
            v-model="rejectForm.rejection_reason"
            name="rejection_reason"
            placeholder="Enter rejection reason..."
            rows="4"
            class="w-full"
            :error="rejectForm.errors.rejection_reason"
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
import { ref, watch, computed } from 'vue';
import {
  ClockIcon,
  CheckCircleIcon,
  UsersIcon,
  TruckIcon,
} from '@heroicons/vue/24/outline';

import SearchInput from '@/Components/SearchInput.vue';
import SelectInput from '@/Components/SelectInput.vue';
import Pagination from '@/Components/Pagination.vue';
import Modal from '@/Components/Modal.vue';
import TextArea from '@/Components/TextArea.vue';
import InputError from '@/Components/InputError.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';

const props = defineProps({
  payments: {
    type: Object,
    default: () => ({ data: [], meta: {} })
  },
  filters: {
    type: Object,
    default: () => ({})
  },
  stats: {
    type: Object,
    default: () => ({})
  }
});

const filters = ref({
  status: props.filters?.status || 'pending',
  search: props.filters?.search || '',
  source: props.filters?.source || '',
});

const showModal = ref(false);
const selectedPayment = ref(null);

const statusOptions = computed(() => [
  { value: 'pending', label: 'Pending' },
  { value: 'verified', label: 'Verified' },
  { value: 'rejected', label: 'Rejected' },
]);

const sourceOptions = computed(() => [
  { value: '', label: 'All Sources' },
  { value: 'branch_staff', label: 'Branch Staff' },
  { value: 'collector', label: 'Collector' },
  { value: 'customer_online', label: 'Customer Online' },
]);

const rejectForm = useForm({
  rejection_reason: '',
});

watch(filters.value, (newFilters) => {
  // Convert the status filter to work with both status field and legacy fields
  let filterParams = { ...newFilters };
  // If filtering by 'pending', we need to include payments that are not verified or rejected
  if (filterParams.status === 'pending') {
    filterParams.pending = true;
  }
  router.get(route('staff.payments.verification.index'), filterParams, {
    preserveState: true,
    replace: true,
  });
});

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
      // Force reload to see the changes
      router.reload({ only: ['payments'] });
    },
    onError: (errors) => {
      console.error('Rejection failed:', errors);
    }
  });
};

const handlePageChange = (page) => {
  router.get(route('staff.payments.verification.index'), {
    ...filters.value,
    page,
  }, {
    preserveState: true,
  });
};

const formatDate = (dateString) => {
  if (!dateString) return 'N/A';
  
  return new Date(dateString).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
  });
};

const formatStatus = (status) => {
  switch (status) {
    case 'pending':
      return 'Pending Verification';
    case 'verified':
      return 'Verified';
    case 'rejected':
      return 'Rejected';
    default:
      return status;
  }
};
</script>