<template>
  <GuestLayout>
    <template #header>
      <div class="flex justify-between items-center w-full px-6 md:px-8">
        <!-- Left: Title & Subtitle -->
        <div>
          <h2 class="text-xl font-semibold leading-tight text-gray-800">My Deliveries</h2>
          <p class="mt-1 text-sm text-gray-500">
            View all your delivery requests and orders in one place
          </p>
        </div>

        <!-- Right: Buttons -->
        <div class="flex gap-2">
          <PrimaryButton @click="router.visit(route('customer.delivery-requests.create'))">
            + New Request
          </PrimaryButton>
        </div>
      </div>
    </template>


      <div class="px-6 sm:px-8">
      <!-- Status Messages -->
      <div v-if="status || success || error" class="mb-6">
        <Alert v-if="status" type="info">{{ status }}</Alert>
        <Alert v-if="success" type="success">{{ success }}</Alert>
        <Alert v-if="error" type="danger">{{ error }}</Alert>
      </div>

      <!-- Quick Stats -->
      <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-8">
        <div class="bg-white overflow-hidden shadow rounded-lg">
          <div class="px-4 py-5 sm:p-6">
            <div class="flex items-center">
              <div class="flex-shrink-0 bg-blue-100 rounded-md p-3">
                <DocumentTextIcon class="h-6 w-6 text-blue-600" />
              </div>
              <div class="ml-5 w-0 flex-1">
                <dl>
                  <dt class="text-sm font-medium text-gray-500 truncate">Total</dt>
                  <dd class="flex items-baseline">
                    <div class="text-2xl font-semibold text-gray-900">
                      {{ stats?.total || 0 }}
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
              <div class="flex-shrink-0 bg-yellow-100 rounded-md p-3">
                <ClockIcon class="h-6 w-6 text-yellow-600" />
              </div>
              <div class="ml-5 w-0 flex-1">
                <dl>
                  <dt class="text-sm font-medium text-gray-500 truncate">Pending</dt>
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
                  <dt class="text-sm font-medium text-gray-500 truncate">Approved</dt>
                  <dd class="flex items-baseline">
                    <div class="text-2xl font-semibold text-gray-900">
                      {{ stats?.approved || 0 }}
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
                  <dt class="text-sm font-medium text-gray-500 truncate">Completed</dt>
                  <dd class="flex items-baseline">
                    <div class="text-2xl font-semibold text-gray-900">
                      {{ stats?.completed || 0 }}
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
              <div class="flex-shrink-0 bg-red-100 rounded-md p-3">
                <BanknotesIcon class="h-6 w-6 text-red-600" />
              </div>
              <div class="ml-5 w-0 flex-1">
                <dl>
                  <dt class="text-sm font-medium text-gray-500 truncate">Awaiting Payment</dt>
                  <dd class="flex items-baseline">
                    <div class="text-2xl font-semibold text-gray-900">
                      {{ stats?.awaiting_payment || 0 }}
                    </div>
                  </dd>
                </dl>
              </div>
            </div>
          </div>
        </div>
      </div>

         <!-- Unified Deliveries Table -->
      <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
        <div class="p-6 bg-white border-b border-gray-200">
          <!-- Table Header with Filters -->
          <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 mb-6">
            <div>
              <h3 class="text-lg font-medium text-gray-900">All Deliveries</h3>
              <p class="text-sm text-gray-500 mt-1">
                View all your delivery requests and orders in one place
              </p>
            </div>
            <div class="flex flex-col sm:flex-row items-start sm:items-center space-y-3 sm:space-y-0 sm:space-x-4">
              <!-- Status Filter -->
             <div class="flex items-center space-x-2">
  <label class="text-sm font-medium text-gray-700">Status:</label>
  <select 
    v-model="statusFilter"
    class="border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm"
  >
    <option value="all">All Status</option>
    <option value="pending">Pending</option>
    <option value="approved">Approved</option>
    <option value="in_transit">In Transit</option>
    <option value="delivered">Delivered</option>
    <option value="completed">Completed</option>
    <option value="rejected">Rejected</option>
  </select>
</div>

              <!-- Search Input -->
              <SearchInput 
                v-model="search" 
                placeholder="Search deliveries..." 
                class="w-64"
              />

              <div class="text-sm text-gray-500 bg-gray-50 px-3 py-1 rounded border border-gray-200">
                📋 {{ deliveries?.data?.length || 0 }} of {{ deliveries?.total || 0 }} deliveries
                <span v-if="deliveries && deliveries.last_page > 1" class="ml-1">
                  (Page {{ deliveries.current_page }} of {{ deliveries.last_page }})
                </span>
              </div>
            </div>
          </div>

          <!-- Data Table -->
          <DataTable 
            :columns="columns" 
            :data="deliveries?.data || []"
            :sort-field="sortField"
            :sort-direction="sortDirection"
            :numeric-fields="['id', 'total_price', 'package_count']"
            @sort="handleSort"
            class="w-full"
          >
            <template #id="{ row }">
              <div class="flex flex-col">
                <span class="font-medium text-gray-900">#{{ row.id.toString().padStart(6, '0') }}</span>
                <span v-if="row.reference_number" class="text-xs text-gray-500">
                  Ref: {{ row.reference_number }}
                </span>
              </div>
            </template>

            <template #receiver="{ row }">
              <div>
                <span class="font-medium text-gray-900">{{ row.receiver.name }}</span>
                <span v-if="row.receiver.company_name" class="text-xs text-gray-500 block">
                  {{ row.receiver.company_name }}
                </span>
              </div>
            </template>

            <template #route="{ row }">
              <div class="text-sm">
                <div class="flex items-center text-gray-600">
                  <span class="font-medium">{{ row.pick_up_region?.name }}</span>
                  <ArrowRightIcon class="h-4 w-4 mx-2" />
                  <span class="font-medium">{{ row.drop_off_region?.name }}</span>
                </div>
              </div>
            </template>

            <template #packages="{ row }">
              <div class="text-center">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                  {{ row.package_count }} {{ row.package_count === 1 ? 'package' : 'packages' }}
                </span>
              </div>
            </template>

            <template #total_price="{ row }">
              <span class="font-medium text-gray-900">
                ₱{{ parseFloat(row.total_price || 0).toFixed(2) }}
              </span>
            </template>

            <template #payment_method="{ row }">
              <span class="text-sm text-gray-700 capitalize">
                {{ row.payment_method || 'Not set' }}
              </span>
            </template>

            <template #payment_type="{ row }">
              <span class="text-sm text-gray-700 capitalize">
                {{ getPaymentTypeLabel(row.payment_method) }}
              </span>
            </template>

            <template #status="{ row }">
              <StatusBadge :status="row.status">
                {{ getStatusLabel(row.status) }}
              </StatusBadge>
            </template>

            <template #payment_status="{ row }">
              <StatusBadge :status="getPaymentStatusColor(row)">
                {{ getPaymentStatusLabel(row) }}
              </StatusBadge>
            </template>

            <template #created_at="{ row }">
              <span class="text-sm text-gray-500">
                {{ formatDate(row.created_at) }}
              </span>
            </template>

           <template #actions="{ row }">
  <div class="flex space-x-2">
    <!-- View Button - Always visible but conditionally enabled -->
    <SecondaryButton
      @click="viewDelivery(row.id)"
      :disabled="false" 
      class="text-xs py-1 px-2"
      :class="{ 'opacity-50 cursor-not-allowed': false }"
    >
      View
    </SecondaryButton>
    
    <!-- Edit Button - Only for draft status -->
    <PrimaryButton
      @click="editDelivery(row.id)"
      :disabled="row.status !== 'pending'"
      class="text-xs py-1 px-2"
      :class="{ 'opacity-50 cursor-not-allowed': row.status !== 'pending' }"
    >
      Edit
    </PrimaryButton>
    
      <!-- Pay Now Button - UPDATED: Show for approved deliveries that need payment -->
                <PrimaryButton
                  @click="goToPayment(row)"
                  :disabled="!canPay(row)"
                  class="text-xs py-1 px-2"
                  :class="{ 'opacity-50 cursor-not-allowed': !canPay(row) }"
                >
                  {{ getPayButtonText(row) }}
                </PrimaryButton>

     <!-- View Payment Button - Only when payment exists -->
                <SecondaryButton
                  @click="viewPayment(getPaymentId(row))"
                  :disabled="!shouldShowViewPaymentButton(row)"
                  class="text-xs py-1 px-2"
                  :class="{ 'opacity-50 cursor-not-allowed': !shouldShowViewPaymentButton(row) }"
                >
                  View Payment
                </SecondaryButton>
  </div>
</template>

             <!-- Empty State -->
            <template #empty>
              <div class="text-center py-12">
                <div class="bg-gray-50 rounded-lg p-8 max-w-md mx-auto">
                  <DocumentTextIcon class="h-12 w-12 text-gray-400 mx-auto mb-4" />
                  <h3 class="text-lg font-medium text-gray-900 mb-2">No deliveries found</h3>
                  <p class="text-gray-500 mb-4">
                    {{ search || statusFilter !== 'all' ? 'Try adjusting your search or filters' : 'Get started by creating your first delivery request' }}
                  </p>
                  <PrimaryButton @click="router.visit(route('customer.delivery-requests.create'))">
                    Create Delivery Request
                  </PrimaryButton>
                </div>
              </div>
            </template>
          </DataTable>

               <!-- Pagination - FIXED -->
          <div class="mt-4">
            <Pagination 
              v-if="deliveries && deliveries.last_page > 1"
              :pagination="{
                current_page: deliveries.current_page,
                last_page: deliveries.last_page,
                total: deliveries.total,
                from: deliveries.from,
                to: deliveries.to,
              }"
              @page-changed="handlePageChange" 
            />
          </div>
        </div>
      </div>
    </div>
  </GuestLayout>
</template>

<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import SearchInput from '@/Components/SearchInput.vue';
import Alert from '@/Components/Alert.vue';
import DataTable from '@/Components/DataTable.vue';
import Pagination from '@/Components/Pagination.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import { 
  DocumentTextIcon, 
  CheckCircleIcon, 
  ClockIcon,
  BanknotesIcon,
  TruckIcon,
  ArrowRightIcon
} from '@heroicons/vue/24/outline';
import { router } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

const props = defineProps({
  deliveries: Object,
  stats: Object,
  filters: Object,
  status: String,
  success: String,
  error: String,
});

const search = ref(props.filters?.search || '');
const statusFilter = ref(props.filters?.status_filter || 'all');
const sortField = ref('created_at');
const sortDirection = ref('desc');

// Table Columns
const columns = [
  { field: 'id', header: 'Delivery ID', sortable: true },
  { field: 'receiver', header: 'Receiver', sortable: true },
  { field: 'route', header: 'Route', sortable: false },
  { field: 'packages', header: 'Packages', sortable: true },
  { field: 'total_price', header: 'Total Price', sortable: true },
  { field: 'payment_method', header: 'Payment Method', sortable: true },
  { field: 'payment_type', header: 'Payment Type', sortable: true },
  { field: 'status', header: 'Status', sortable: true },
  { field: 'payment_status', header: 'Payment Status', sortable: true },
  { field: 'created_at', header: 'Created', sortable: true },
  { field: 'actions', header: 'Actions', sortable: false },
];

// Status label mapping
const getStatusLabel = (status) => {
  const labels = {
    draft: 'Draft',
    pending: 'Pending',
    approved: 'Approved',
    in_transit: 'In Transit',
    delivered: 'Delivered',
    completed: 'Completed',
    rejected: 'Rejected'
  };
  return labels[status] || status;
};

// Payment status label mapping
const getPaymentStatusLabel = (row) => {
  const status = getDisplayPaymentStatus(row);
  const labels = {
    unpaid: 'Payment Required',
    pending_payment: 'Payment Pending',
    pending_verification: 'Verifying Payment',
    paid: 'Payment Received',
    verified: 'Payment Verified',
    rejected: 'Payment Rejected'
  };
  return labels[status] || status;
};

// Payment status color mapping for StatusBadge
const getPaymentStatusColor = (row) => {
  const status = getDisplayPaymentStatus(row);
  const colors = {
    unpaid: 'red',
    pending_payment: 'yellow',
    pending_verification: 'blue',
    paid: 'green',
    verified: 'green',
    rejected: 'red'
  };
  return colors[status] || 'gray';
};

// Payment type label mapping
const getPaymentTypeLabel = (paymentMethod) => {
  if (!paymentMethod) return 'Not set';
  
  const types = {
    cash: 'Cash',
    gcash: 'GCash',
    bank: 'Bank Transfer'
  };
  return types[paymentMethod] || paymentMethod;
};

// Payment status helper functions
const getDisplayPaymentStatus = (row) => {
  if (row.payment_status) return row.payment_status;
  if (row.payment && row.payment.status) return row.payment.status;
  return 'unpaid';
};

const shouldShowViewPaymentButton = (row) => {
  const paymentStatus = getDisplayPaymentStatus(row);
  const paymentId = getPaymentId(row);
  return paymentId && ['pending_verification', 'paid', 'verified', 'rejected'].includes(paymentStatus);
};

const getPaymentId = (row) => {
  if (row.payment && row.payment.id) return row.payment.id;
  return null;
};

// Payment Actions - Allow payment for approved deliveries (both prepaid and postpaid)
const canPay = (row) => {
  const paymentStatus = getDisplayPaymentStatus(row);
  const requiresPayment = ['unpaid', 'pending_payment', 'rejected'].includes(paymentStatus);
  
  // Allow payment for both prepaid and postpaid when approved
  return requiresPayment && row.status === 'approved';
};

// Get appropriate button text based on payment status
const getPayButtonText = (row) => {
  const paymentStatus = getDisplayPaymentStatus(row);
  if (paymentStatus === 'rejected') return 'Resubmit Payment';
  return 'Pay Now';
};

// Navigation functions
const viewDelivery = (id) => {
  router.visit(route('customer.delivery-requests.show', id));
};

const editDelivery = (id) => {
  router.visit(route('customer.delivery-requests.edit', id));
};

const goToPayment = (row) => {
  // If payment was rejected, go to resubmit page, otherwise create new payment
  if (row.payment_status === 'rejected' && row.payment) {
    router.visit(route('customer.payments.resubmit', {
      delivery: row.id,
      payment: row.payment.id
    }));
  } else {
    router.visit(route('customer.payments.create', row.id));
  }
};

const viewPayment = (id) => {
  router.visit(route('customer.payments.show', id));
};

// Sort handler
const handleSort = (sortParams) => {
  sortField.value = sortParams.field;
  sortDirection.value = sortParams.direction;
};

// Page change handler
const handlePageChange = (page) => {
  router.get(route('customer.delivery-requests.index'), { 
    page: page,
    search: search.value,
    status_filter: statusFilter.value,
  }, {
    preserveState: true,
    preserveScroll: true,
    replace: true
  });
};

// Format date
const formatDate = (dateString) => {
  if (!dateString) return '—';
  const date = new Date(dateString);
  if (isNaN(date.getTime())) return '—';
  return date.toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  });
};

// Watch for search and filter changes
watch([search, statusFilter], () => {
  router.get(route('customer.delivery-requests.index'), {
    search: search.value,
    status_filter: statusFilter.value,
    page: 1, // Reset to first page when filtering
  }, {
    preserveState: true,
    replace: true,
    preserveScroll: true,
  });
});
</script>