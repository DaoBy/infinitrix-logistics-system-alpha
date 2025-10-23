[file name]: DeliveryDashboard.vue
[file content begin]
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

      <!-- Quick Stats - Mobile Responsive -->
      <div class="grid grid-cols-2 md:grid-cols-5 gap-3 mb-8">
        <!-- Total -->
        <div class="bg-white overflow-hidden shadow rounded-lg">
          <div class="p-4">
            <div class="flex items-center">
              <div class="flex-shrink-0 bg-blue-100 rounded-md p-2">
                <DocumentTextIcon class="h-5 w-5 text-blue-600" />
              </div>
              <div class="ml-3 flex-1 min-w-0">
                <p class="text-xs font-medium text-gray-500 truncate">Total</p>
                <p class="text-lg font-semibold text-gray-900">{{ stats?.total || 0 }}</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Pending -->
        <div class="bg-white overflow-hidden shadow rounded-lg">
          <div class="p-4">
            <div class="flex items-center">
              <div class="flex-shrink-0 bg-yellow-100 rounded-md p-2">
                <ClockIcon class="h-5 w-5 text-yellow-600" />
              </div>
              <div class="ml-3 flex-1 min-w-0">
                <p class="text-xs font-medium text-gray-500 truncate">Pending</p>
                <p class="text-lg font-semibold text-gray-900">{{ stats?.pending || 0 }}</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Approved -->
        <div class="bg-white overflow-hidden shadow rounded-lg">
          <div class="p-4">
            <div class="flex items-center">
              <div class="flex-shrink-0 bg-green-100 rounded-md p-2">
                <CheckCircleIcon class="h-5 w-5 text-green-600" />
              </div>
              <div class="ml-3 flex-1 min-w-0">
                <p class="text-xs font-medium text-gray-500 truncate">Approved</p>
                <p class="text-lg font-semibold text-gray-900">{{ stats?.approved || 0 }}</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Completed -->
        <div class="bg-white overflow-hidden shadow rounded-lg">
          <div class="p-4">
            <div class="flex items-center">
              <div class="flex-shrink-0 bg-purple-100 rounded-md p-2">
                <TruckIcon class="h-5 w-5 text-purple-600" />
              </div>
              <div class="ml-3 flex-1 min-w-0">
                <p class="text-xs font-medium text-gray-500 truncate">Completed</p>
                <p class="text-lg font-semibold text-gray-900">{{ stats?.completed || 0 }}</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Awaiting Payment -->
        <div class="bg-white overflow-hidden shadow rounded-lg">
          <div class="p-4">
            <div class="flex items-center">
              <div class="flex-shrink-0 bg-red-100 rounded-md p-2">
                <BanknotesIcon class="h-5 w-5 text-red-600" />
              </div>
              <div class="ml-3 flex-1 min-w-0">
                <p class="text-xs font-medium text-gray-500 truncate">Payment Due</p>
                <p class="text-lg font-semibold text-gray-900">{{ stats?.awaiting_payment || 0 }}</p>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Unified Deliveries Table -->
      <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
        <div class="p-4 sm:p-6 bg-white border-b border-gray-200">
          <!-- Table Header with Filters -->
          <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 mb-6">
            <div>
              <h3 class="text-lg font-medium text-gray-900">All Deliveries</h3>
              <p class="text-sm text-gray-500 mt-1">
                View all your delivery requests and orders in one place
              </p>
            </div>
            <div class="flex flex-col sm:flex-row items-start sm:items-center space-y-3 sm:space-y-0 sm:space-x-4 w-full sm:w-auto">
              <!-- Status Filter -->
              <div class="flex items-center space-x-2 w-full sm:w-auto">
                <label class="text-sm font-medium text-gray-700 whitespace-nowrap">Status:</label>
                <select 
                  v-model="statusFilter"
                  class="border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm w-full sm:w-32"
                >
                  <option value="all">All Status</option>
                  <option value="pending">Pending</option>
                  <option value="approved">Approved</option>
                  <option value="completed">Completed</option>
                  <option value="rejected">Rejected</option>
                </select>
              </div>

              <!-- Search Input -->
              <SearchInput 
                v-model="search" 
                placeholder="Search deliveries..." 
                class="w-full sm:w-64"
              />

              <div class="text-sm text-gray-500 bg-gray-50 px-3 py-1 rounded border border-gray-200 whitespace-nowrap">
                📋 {{ deliveries?.data?.length || 0 }} of {{ deliveries?.total || 0 }}
                <span v-if="deliveries && deliveries.last_page > 1" class="ml-1">
                  (Page {{ deliveries.current_page }} of {{ deliveries.last_page }})
                </span>
              </div>
            </div>
          </div>

          <!-- Mobile Cards View -->
          <div class="md:hidden space-y-4">
            <div v-for="row in deliveries?.data || []" :key="row.id" class="bg-gray-50 rounded-lg p-4 border border-gray-200">
              <!-- Header -->
              <div class="flex justify-between items-start mb-3">
                <div>
                  <h4 class="font-semibold text-gray-900">#{{ row.id.toString().padStart(6, '0') }}</h4>
                  <p v-if="row.reference_number" class="text-xs text-gray-500">Ref: {{ row.reference_number }}</p>
                </div>
                <div class="text-right">
                  <StatusBadge :status="getCombinedStatus(row)" class="mb-1">
                    {{ getCombinedStatusLabel(row) }}
                  </StatusBadge>
                  <StatusBadge :status="getPaymentStatusColor(row)" class="text-xs">
                    {{ getPaymentStatusLabel(row) }}
                  </StatusBadge>
                </div>
              </div>

              <!-- Details -->
              <div class="space-y-2 text-sm">
                <div>
                  <span class="font-medium text-gray-700">To:</span>
                  <span class="text-gray-900 ml-1">{{ row.receiver.name }}</span>
                  <span v-if="row.receiver.company_name" class="text-gray-500 text-xs block">
                    {{ row.receiver.company_name }}
                  </span>
                </div>
                
                <div class="flex items-center text-gray-600">
                  <span class="text-xs">{{ row.pick_up_region?.name }}</span>
                  <ArrowRightIcon class="h-3 w-3 mx-1" />
                  <span class="text-xs">{{ row.drop_off_region?.name }}</span>
                </div>

                <div class="flex justify-between">
                  <span class="text-gray-600">Packages:</span>
                  <span class="font-medium">{{ row.package_count }}</span>
                </div>

                <div class="flex justify-between">
                  <span class="text-gray-600">Total:</span>
                  <span class="font-medium text-green-600">₱{{ parseFloat(row.total_price || 0).toFixed(2) }}</span>
                </div>

                <div class="flex justify-between">
                  <span class="text-gray-600">Payment:</span>
                  <span class="capitalize">{{ getPaymentTypeLabel(row.payment_method) }}</span>
                </div>

                <div class="flex justify-between">
                  <span class="text-gray-600">Created:</span>
                  <span class="text-gray-500">{{ formatDateShort(row.created_at) }}</span>
                </div>
              </div>

              <!-- Actions -->
              <div class="flex flex-wrap gap-2 mt-4 pt-3 border-t border-gray-200">
                <SecondaryButton
                  @click="viewDelivery(row.id)"
                  class="text-xs py-1 px-2 flex-1 min-w-0"
                >
                  View
                </SecondaryButton>

                <PrimaryButton
                  v-if="canPay(row)"
                  @click="goToPayment(row)"
                  class="text-xs py-1 px-2 flex-1 min-w-0"
                >
                  {{ getPayButtonText(row) }}
                </PrimaryButton>

                <SecondaryButton
                  v-if="shouldShowViewPaymentButton(row)"
                  @click="viewPayment(getPaymentId(row))"
                  class="text-xs py-1 px-2 flex-1 min-w-0"
                >
                  View Payment
                </SecondaryButton>
              </div>
            </div>
          </div>

          <!-- Desktop Table View -->
          <div class="hidden md:block">
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
                    <span class="font-medium text-xs">{{ row.pick_up_region?.name }}</span>
                    <ArrowRightIcon class="h-3 w-3 mx-1" />
                    <span class="font-medium text-xs">{{ row.drop_off_region?.name }}</span>
                  </div>
                </div>
              </template>

              <template #packages="{ row }">
                <div class="text-center">
                  <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                    {{ row.package_count }} {{ row.package_count === 1 ? 'pkg' : 'pkgs' }}
                  </span>
                </div>
              </template>

              <template #total_price="{ row }">
                <span class="font-medium text-gray-900 text-sm">
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
                <StatusBadge :status="getCombinedStatus(row)" class="text-xs">
                  {{ getCombinedStatusLabel(row) }}
                </StatusBadge>
              </template>

              <template #payment_status="{ row }">
                <StatusBadge :status="getPaymentStatusColor(row)" class="text-xs">
                  {{ getPaymentStatusLabel(row) }}
                </StatusBadge>
              </template>

              <template #created_at="{ row }">
                <span class="text-sm text-gray-500">
                  {{ formatDateShort(row.created_at) }}
                </span>
              </template>

              <template #actions="{ row }">
                <div class="flex space-x-1">
                  <SecondaryButton
                    @click="viewDelivery(row.id)"
                    class="text-xs py-1 px-2"
                  >
                    View
                  </SecondaryButton>

                  <PrimaryButton
                    v-if="canPay(row)"
                    @click="goToPayment(row)"
                    class="text-xs py-1 px-2"
                  >
                    {{ getPayButtonText(row) }}
                  </PrimaryButton>

                  <SecondaryButton
                    v-if="shouldShowViewPaymentButton(row)"
                    @click="viewPayment(getPaymentId(row))"
                    class="text-xs py-1 px-2"
                  >
                    Payment
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
          </div>

          <!-- Pagination -->
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
  { field: 'id', header: 'ID', sortable: true, width: '100px' },
  { field: 'receiver', header: 'Receiver', sortable: true },
  { field: 'route', header: 'Route', sortable: false, width: '150px' },
  { field: 'packages', header: 'Packages', sortable: true, width: '100px' },
  { field: 'total_price', header: 'Total', sortable: true, width: '100px' },
  { field: 'payment_method', header: 'Method', sortable: true, width: '100px' },
  { field: 'payment_type', header: 'Type', sortable: true, width: '100px' },
  { field: 'status', header: 'Status', sortable: true, width: '120px' },
  { field: 'payment_status', header: 'Payment', sortable: true, width: '120px' },
  { field: 'created_at', header: 'Created', sortable: true, width: '120px' },
  { field: 'actions', header: 'Actions', sortable: false, width: '180px' },
];

// Combined status logic that checks both DeliveryRequest and DeliveryOrder
const getCombinedStatus = (row) => {
  // Priority 1: Delivery Request final statuses
  if (row.status === 'rejected' || row.status === 'completed') {
    return row.status;
  }
  
  // Priority 2: Delivery Order status (if exists)
  const deliveryOrder = row.delivery_order;
  if (deliveryOrder) {
    // Map delivery order statuses to combined status
    const statusMap = {
      'assigned': 'approved', // Still considered approved until dispatched
      'dispatched': 'approved', // Show as approved for consistency
      'in_transit': 'approved', // Show as approved for consistency
      'delivered': 'completed', // Map delivered to completed
      'needs_review': 'completed', // Show as completed but with issues
      'completed': 'completed'
    };
    
    const combinedStatus = statusMap[deliveryOrder.status];
    if (combinedStatus) {
      return combinedStatus;
    }
  }
  
  // Priority 3: Delivery Request status
  return row.status;
};

const getCombinedStatusLabel = (row) => {
  const status = getCombinedStatus(row);
  const labels = {
    'draft': 'Draft',
    'pending': 'Pending Approval',
    'approved': 'Approved',
    'completed': 'Completed',
    'rejected': 'Rejected'
  };
  return labels[status] || status;
};

// Enhanced payment status helper that considers both models
const getDisplayPaymentStatus = (row) => {
  // Priority 1: Explicit payment status from delivery request
  if (row.payment_status) return row.payment_status;
  
  // Priority 2: Payment model status
  if (row.payment && row.payment.status) return row.payment.status;
  
  // Priority 3: Determine based on combined delivery status and payment type
  const combinedStatus = getCombinedStatus(row);
  
  if (combinedStatus === 'completed') {
    // For completed deliveries
    if (row.payment_type === 'postpaid') {
      return row.payment_method ? 'awaiting_payment' : 'unpaid';
    }
    // For prepaid, assume paid if completed
    return 'paid';
  }
  
  if (combinedStatus === 'approved') {
    // For approved deliveries
    if (row.payment_type === 'prepaid') {
      return row.payment_method ? 'pending_payment' : 'unpaid';
    }
    // Postpaid approved - no payment needed yet
    return 'unpaid';
  }
  
  // Default for other statuses
  return 'unpaid';
};

// Payment status label mapping
const getPaymentStatusLabel = (row) => {
  const status = getDisplayPaymentStatus(row);
  const labels = {
    'unpaid': 'Payment Required',
    'pending_payment': 'Payment Pending',
    'pending_verification': 'Verifying Payment',
    'paid': 'Payment Received',
    'verified': 'Payment Verified',
    'rejected': 'Payment Rejected',
    'awaiting_payment': 'Payment Due',
    'requires_adjustment': 'Invoice Adjustment',
    'refunded': 'Refunded'
  };
  return labels[status] || status;
};

// Payment status color mapping for StatusBadge
const getPaymentStatusColor = (row) => {
  const status = getDisplayPaymentStatus(row);
  const colors = {
    'unpaid': 'red',
    'pending_payment': 'yellow',
    'pending_verification': 'blue',
    'paid': 'green',
    'verified': 'green',
    'rejected': 'red',
    'awaiting_payment': 'yellow',
    'requires_adjustment': 'orange',
    'refunded': 'purple'
  };
  return colors[status] || 'gray';
};

// Payment type label mapping
const getPaymentTypeLabel = (paymentMethod) => {
  if (!paymentMethod) return 'Not set';
  
  const types = {
    'cash': 'Cash',
    'gcash': 'GCash',
    'bank': 'Bank Transfer'
  };
  return types[paymentMethod] || paymentMethod;
};

// Payment helper functions
const shouldShowViewPaymentButton = (row) => {
  const paymentStatus = getDisplayPaymentStatus(row);
  const paymentId = getPaymentId(row);
  return paymentId && ['pending_verification', 'paid', 'verified', 'rejected', 'refunded'].includes(paymentStatus);
};

const getPaymentId = (row) => {
  if (row.payment && row.payment.id) return row.payment.id;
  return null;
};

// Payment Actions - Enhanced to handle all scenarios
const canPay = (row) => {
  const paymentStatus = getDisplayPaymentStatus(row);
  const requiresPayment = ['unpaid', 'pending_payment', 'rejected', 'awaiting_payment'].includes(paymentStatus);
  
  const combinedStatus = getCombinedStatus(row);
  
  // Allow payment for:
  // - Approved deliveries (both prepaid and postpaid)
  // - Completed postpaid deliveries that need payment
  const isEligibleStatus = combinedStatus === 'approved' || 
                          (combinedStatus === 'completed' && row.payment_type === 'postpaid');
  
  return requiresPayment && isEligibleStatus && paymentStatus !== 'requires_adjustment';
};

// Get appropriate button text based on payment status
const getPayButtonText = (row) => {
  const paymentStatus = getDisplayPaymentStatus(row);
  if (paymentStatus === 'rejected') return 'Resubmit';
  if (paymentStatus === 'awaiting_payment') return 'Pay Now';
  return 'Pay Now';
};

// Navigation functions
const viewDelivery = (id) => {
  router.visit(route('customer.delivery-requests.show', id));
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

// Format date for table (compact)
const formatDateShort = (dateString) => {
  if (!dateString) return '—';
  const date = new Date(dateString);
  if (isNaN(date.getTime())) return '—';
  return date.toLocaleDateString('en-US', {
    month: 'short',
    day: 'numeric'
  });
};

// Format date (full)
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
[file content end]