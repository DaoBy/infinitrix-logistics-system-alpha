<template>
  <EmployeeLayout>
    <template #header>
      <div class="flex justify-between items-center px-6">
        <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
          Waybill: {{ waybill.waybill_number }}
        </h2>
        <div class="flex space-x-2">
          <PrimaryButton
            @click="printWaybill"
            class="inline-flex items-center no-print"
          >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" viewBox="0 0 20 20" fill="currentColor">
              <path fill-rule="evenodd" d="M5 4v3H4a2 2 0 00-2 2v3a2 2 0 002 2h1v2a2 2 0 002 2h6a2 2 0 002-2v-2h1a2 2 0 002-2V9a2 2 0 00-2-2h-1V4a2 2 0 00-2-2H7a2 2 0 00-2 2zm8 0H7v3h6V4zm0 8H7v4h6v-4z" clip-rule="evenodd" />
            </svg>
            Print Complete Waybill
          </PrimaryButton>
          <SecondaryButton @click="goBack" class="inline-flex items-center">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" viewBox="0 0 20 20" fill="currentColor">
              <path fill-rule="evenodd" d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l4.293 4.293a1 1 0 010 1.414z" clip-rule="evenodd" />
            </svg>
            Back to Waybills
          </SecondaryButton>
        </div>
      </div>
    </template>

    <div class="px-6 py-4">
      <div id="print-area" class="bg-white dark:bg-gray-800 shadow-sm rounded-lg overflow-hidden border border-gray-200 dark:border-gray-700 max-w-4xl mx-auto">
        <div class="p-6 print:p-0">
          <!-- Header -->
          <div class="text-center mb-6">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100">INFINITRIX EXPRESS CARGO</h1>
            <p class="text-xl text-gray-600 dark:text-gray-300">DELIVERY RECEIPT / WAYBILL (COMPLETED)</p>
          </div>

          <!-- Waybill Information -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
            <div>
              <div class="bg-gray-50 dark:bg-gray-700 p-4 rounded">
                <p class="text-sm text-gray-500 dark:text-gray-400"><span class="font-semibold">Waybill No:</span> <span class="text-gray-900 dark:text-gray-100">{{ waybill.waybill_number }}</span></p>
                <p class="text-sm text-gray-500 dark:text-gray-400"><span class="font-semibold">Date:</span> <span class="text-gray-900 dark:text-gray-100">{{ formatDate(waybill.created_at) }}</span></p>
              </div>
            </div>
            <div>
              <div class="bg-gray-50 dark:bg-gray-700 p-4 rounded">
                <p class="text-sm text-gray-500 dark:text-gray-400"><span class="font-semibold">Reference No:</span> <span class="text-gray-900 dark:text-gray-100">{{ waybill.delivery_request?.reference_number || 'N/A' }}</span></p>
                <p class="text-sm text-gray-500 dark:text-gray-400"><span class="font-semibold">Delivery Type:</span> <span class="text-gray-900 dark:text-gray-100">Branch to Branch</span></p>
              </div>
            </div>
          </div>

          <!-- Shipper & Consignee -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <div class="bg-gray-50 dark:bg-gray-700 p-4 rounded">
              <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-2">Shipper Information</h3>
              <p class="text-sm text-gray-500 dark:text-gray-400"><span class="font-semibold">Name:</span> <span class="text-gray-900 dark:text-gray-100">{{ waybill.delivery_request?.sender?.name || waybill.delivery_request?.sender?.company_name || 'N/A' }}</span></p>
              <p class="text-sm text-gray-500 dark:text-gray-400"><span class="font-semibold">Address:</span> <span class="text-gray-900 dark:text-gray-100">{{ waybill.delivery_request?.sender?.address || 'N/A' }}</span></p>
              <p class="text-sm text-gray-500 dark:text-gray-400"><span class="font-semibold">Mobile:</span> <span class="text-gray-900 dark:text-gray-100">{{ waybill.delivery_request?.sender?.mobile || 'N/A' }}</span></p>
            </div>
            <div class="bg-gray-50 dark:bg-gray-700 p-4 rounded">
              <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-2">Consignee Information</h3>
              <p class="text-sm text-gray-500 dark:text-gray-400"><span class="font-semibold">Name:</span> <span class="text-gray-900 dark:text-gray-100">{{ waybill.delivery_request?.receiver?.name || waybill.delivery_request?.receiver?.company_name || 'N/A' }}</span></p>
              <p class="text-sm text-gray-500 dark:text-gray-400"><span class="font-semibold">Address:</span> <span class="text-gray-900 dark:text-gray-100">{{ waybill.delivery_request?.receiver?.address || 'N/A' }}</span></p>
              <p class="text-sm text-gray-500 dark:text-gray-400"><span class="font-semibold">Mobile:</span> <span class="text-gray-900 dark:text-gray-100">{{ waybill.delivery_request?.receiver?.mobile || 'N/A' }}</span></p>
            </div>
          </div>

          <!-- Route Information -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <div class="bg-gray-50 dark:bg-gray-700 p-4 rounded">
              <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-2">Pickup Branch/Region</h3>
              <p class="text-sm text-gray-900 dark:text-gray-100">
                {{ waybill.delivery_request?.pickUpRegion?.name || waybill.delivery_request?.pick_up_region?.name || 'N/A' }}
              </p>
              <p class="text-sm text-gray-500 dark:text-gray-400">
                {{ waybill.delivery_request?.pickUpRegion?.address || waybill.delivery_request?.pick_up_region?.address || waybill.delivery_request?.pickUpRegion?.warehouse_address || waybill.delivery_request?.pick_up_region?.warehouse_address || 'N/A' }}
              </p>
            </div>
            <div class="bg-gray-50 dark:bg-gray-700 p-4 rounded">
              <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-2">Drop-off Branch/Region</h3>
              <p class="text-sm text-gray-900 dark:text-gray-100">
                {{ waybill.delivery_request?.dropOffRegion?.name || waybill.delivery_request?.drop_off_region?.name || 'N/A' }}
              </p>
              <p class="text-sm text-gray-500 dark:text-gray-400">
                {{ waybill.delivery_request?.dropOffRegion?.warehouse_address || waybill.delivery_request?.drop_off_region?.warehouse_address || waybill.delivery_request?.dropOffRegion?.address || waybill.delivery_request?.drop_off_region?.address || 'N/A' }}
              </p>
            </div>
          </div>

          <!-- Package Info -->
          <div class="mb-6">
            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-2">Package Information</h3>
            <div class="overflow-x-auto">
              <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                  <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Item Code</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Description</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Weight (kg)</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Dimensions (cm)</th>
                  </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                  <tr v-for="pkg in waybill.delivery_request?.packages" :key="pkg.id">
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">{{ pkg.item_code || 'N/A' }}</td>
                    <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-300">{{ pkg.item_name || 'Unspecified Item' }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">{{ pkg.weight || 'N/A' }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">{{ pkg.length }}x{{ pkg.width }}x{{ pkg.height }}</td>
                  </tr>
                  <tr v-if="!waybill.delivery_request?.packages?.length">
                    <td colspan="4" class="px-6 py-4 text-center text-sm text-gray-500 dark:text-gray-400">No packages found</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

<!-- Payment Information -->
<div class="mb-6">
  <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Payment Information</h3>
  


  <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
    <!-- Column 1 -->
    <div class="bg-gray-50 dark:bg-gray-700 p-4 rounded">
      <p class="text-sm text-gray-500 dark:text-gray-400 mb-2">
        <span class="font-semibold">Payment Type:</span>
      </p>
      <p class="text-sm text-gray-900 dark:text-gray-100 font-medium">
        {{ waybill.delivery_request?.payment_type?.toUpperCase() || 'N/A' }}
      </p>
      
      <p class="text-sm text-gray-500 dark:text-gray-400 mt-3 mb-2">
        <span class="font-semibold">Method:</span>
      </p>
      <p class="text-sm text-gray-900 dark:text-gray-100 font-medium">
        {{ waybill.delivery_request?.payment_method?.toUpperCase() || 'N/A' }}
      </p>
    </div>

    <!-- Column 2 -->
    <div class="bg-gray-50 dark:bg-gray-700 p-4 rounded">
      <p class="text-sm text-gray-500 dark:text-gray-400 mb-2">
        <span class="font-semibold">Payment Terms:</span>
      </p>
      <p class="text-sm text-gray-900 dark:text-gray-100 font-medium">
        {{
          waybill.delivery_request?.payment_type === 'postpaid' 
            ? (waybill.delivery_request?.payment_terms === 'net_7' ? 'Net 7' :
               waybill.delivery_request?.payment_terms === 'net_15' ? 'Net 15' :
               waybill.delivery_request?.payment_terms === 'net_30' ? 'Net 30' :
               waybill.delivery_request?.payment_terms === 'cnd' ? 'CND' :
               'N/A')
            : 'For Postpaid Only'
        }}
      </p>
      
      <p class="text-sm text-gray-500 dark:text-gray-400 mt-3 mb-2">
        <span class="font-semibold">Due Date:</span>
      </p>
      <p class="text-sm text-gray-900 dark:text-gray-100 font-medium">
        {{
          waybill.delivery_request?.payment_type === 'postpaid'
            ? (waybill.delivery_request?.payment_due_date ? formatDate(waybill.delivery_request.payment_due_date) : 'N/A')
            : 'For Postpaid Only'
        }}
      </p>
    </div>

    <!-- Column 3 -->
    <div class="bg-gray-50 dark:bg-gray-700 p-4 rounded">
      <p class="text-sm text-gray-500 dark:text-gray-400 mb-2">
        <span class="font-semibold">Total Amount:</span>
      </p>
      <p class="text-lg text-gray-900 dark:text-gray-100 font-bold">
        {{ formatCurrency(waybill.delivery_request?.total_price || 0) }}
      </p>
      
      <p class="text-sm text-gray-500 dark:text-gray-400 mt-3 mb-2">
        <span class="font-semibold">Status:</span>
      </p>
      <p class="text-sm font-medium" :class="paymentStatusColor">
        {{ getFinalPaymentStatus() }}
      </p>
    </div>
  </div>
</div>

         

          <!-- Footer Message -->
          <div class="text-center py-4 border-t border-gray-200 dark:border-gray-700 print:border-t-0">
            <div class="flex flex-col md:flex-row justify-center items-center gap-8 mb-2">
              <div>
          
              </div>
              <div v-if="order?.truck">
             
              </div>
            </div>
            <p class="text-gray-600 dark:text-gray-300">Thank you for choosing Infinitrix Express!</p>
            <div class="text-xs text-gray-400 mt-2">
              This is the final/complete waybill. All delivery and payment details are finalized.
            </div>
          </div>
        </div>
      </div>
    </div>
  </EmployeeLayout>
</template>

<script setup>
import EmployeeLayout from '@/Layouts/EmployeeLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { router } from '@inertiajs/vue3';
import { computed } from 'vue';
import { onMounted } from 'vue';
const props = defineProps({
  waybill: {
    type: Object,
    required: true
  },
  order: {
    type: Object,
    required: true
  }
});

const getFinalPaymentStatus = () => {
  const paymentType = props.waybill.delivery_request?.payment_type;
  const paymentMethod = props.waybill.delivery_request?.payment_method;
  const paymentStatus = props.waybill.delivery_request?.payment_status;
  const paymentVerified = props.waybill.delivery_request?.payment_verified;
  const isPaid = props.waybill.delivery_request?.is_paid;
  const orderStatus = props.order?.status;

  let finalStatus = 'UNKNOWN';
  
  if (paymentType === 'prepaid') {
    // Prepaid cash is always considered PAID
    if (paymentMethod === 'cash') {
      finalStatus = 'PAID';
    } else {
      // For other prepaid methods, check payment status
      const paid = (paymentStatus === 'paid' && paymentVerified) || isPaid;
      finalStatus = paid ? 'PAID' : 'PENDING';
    }
  } else {
    // Postpaid
    finalStatus = orderStatus === 'completed' ? 'COLLECTED' : 'TO BE COLLECTED';
  }

  // Log the payment status calculation for debugging
  console.log('🔍 PAYMENT STATUS DEBUG:', {
    paymentType,
    paymentMethod,
    paymentStatus,
    paymentVerified,
    isPaid,
    orderStatus,
    finalStatus,
    waybillId: props.waybill.id,
    deliveryRequestId: props.waybill.delivery_request?.id
  });

  return finalStatus;
};

onMounted(() => {
  console.log('💰 FULL PAYMENT DATA:', {
    waybill: props.waybill,
    delivery_request: props.waybill.delivery_request,
    order: props.order,
    paymentData: {
      type: props.waybill.delivery_request?.payment_type,
      method: props.waybill.delivery_request?.payment_method,
      status: props.waybill.delivery_request?.payment_status,
      verified: props.waybill.delivery_request?.payment_verified,
      is_paid: props.waybill.delivery_request?.is_paid,
      total_price: props.waybill.delivery_request?.total_price
    }
  });
});
const formatCurrency = (amount) => {
  if (isNaN(amount)) return '₱0.00';
  return new Intl.NumberFormat('en-PH', {
    style: 'currency',
    currency: 'PHP',
    minimumFractionDigits: 2
  }).format(amount);
};
const paymentStatusColor = computed(() => {
  const finalStatus = getFinalPaymentStatus();
  
  switch (finalStatus) {
    case 'PAID':
    case 'COLLECTED':
      return 'text-green-600 dark:text-green-400';
    case 'PENDING':
      return 'text-yellow-600 dark:text-yellow-400';
    case 'TO BE COLLECTED':
      return 'text-blue-600 dark:text-blue-400';
    default:
      return 'text-gray-600 dark:text-gray-400';
  }
});
const formatDate = (dateString) => {
  if (!dateString) return 'Not specified';
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
};

const formatDateTime = (dateString) => {
  if (!dateString) return 'Not specified';
  try {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
      year: 'numeric',
      month: 'short',
      day: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    });
  } catch (e) {
    return 'Invalid date';
  }
};

const printWaybill = () => {
  window.open(route('waybills.preview', { id: props.waybill.id, final: true }), '_blank');
};

const goBack = () => {
  router.visit(route('waybills.index'));
};
</script>

<style scoped>
@media print {
  body * {
    visibility: hidden;
  }
  #print-area, #print-area * {
    visibility: visible;
  }
  #print-area {
    position: absolute;
    left: 0;
    top: 0;
    width: 100%;
    max-width: 100%;
    margin: 0;
    padding: 0;
    box-shadow: none;
    border: none;
  }
  .no-print {
    display: none !important;
  }
}
</style>