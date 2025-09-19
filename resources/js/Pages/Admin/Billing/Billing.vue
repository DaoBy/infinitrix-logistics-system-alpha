<template>
  <EmployeeLayout>
    <template #header>
      <div class="flex justify-between items-center px-6">
        <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
          Billing & Waybill Generation
        </h2>
        <div class="flex space-x-2">
          <PrimaryButton 
            @click="printWaybill" 
            class="inline-flex items-center"
            v-if="deliveryRequest.delivery_order?.status !== 'completed'"
          >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" viewBox="0 0 20 20" fill="currentColor">
              <path fill-rule="evenodd" d="M5 4v3H4a2 2 0 00-2 2v3a2 2 0 002 2h1v2a2 2 0 002 2h6a2 2 0 002-2v-2h1a2 2 0 002-2V9a2 2 0 00-2-2h-1V4a2 2 0 00-2-2H7a2 2 0 00-2 2zm8 0H7v3h6V4zm0 8H7v4h6v-4z" clip-rule="evenodd" />
            </svg>
            Print Initial Waybill
          </PrimaryButton>
          <PrimaryButton 
            @click="printFinalWaybill" 
            class="inline-flex items-center"
            v-if="deliveryRequest.delivery_order?.status === 'completed'"
          >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" viewBox="0 0 20 20" fill="currentColor">
              <path fill-rule="evenodd" d="M5 4v3H4a2 2 0 00-2 2v3a2 2 0 002 2h1v2a2 2 0 002 2h6a2 2 0 002-2v-2h1a2 2 0 002-2V9a2 2 0 00-2-2h-1V4a2 2 0 00-2-2H7a2 2 0 00-2 2zm8 0H7v3h6V4zm0 8H7v4h6v-4z" clip-rule="evenodd" />
            </svg>
            Print Final Waybill
          </PrimaryButton>
          <PrimaryButton @click="generateWaybill" class="inline-flex items-center">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" viewBox="0 0 20 20" fill="currentColor">
              <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 6a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1zm1 3a1 1 0 100 2h6a1 1 0 100-2H7z" clip-rule="evenodd" />
            </svg>
            Generate Waybill
          </PrimaryButton>
        </div>
      </div>
    </template>

    <div class="px-6 py-4">
      <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg overflow-hidden border border-gray-200 dark:border-gray-700 max-w-4xl mx-auto">
        <div class="p-6">
          <!-- Payment Status Banner -->
          <div 
            class="mb-6 p-4 rounded-lg"
            :class="{
              'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200': deliveryRequest.isPaid(),
              'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200': !deliveryRequest.isPaid() && deliveryRequest.payment_type === 'prepaid',
              'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200': deliveryRequest.payment_type === 'postpaid'
            }"
          >
            <div class="flex items-center">
              <svg v-if="deliveryRequest.isPaid()" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
              </svg>
              <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM7 9a1 1 0 000 2h6a1 1 0 100-2H7z" clip-rule="evenodd" />
              </svg>
              <span class="font-medium">
                {{ deliveryRequest.payment_type.toUpperCase() }} - 
                {{ deliveryRequest.isPaid() ? 'PAID' : deliveryRequest.payment_type === 'prepaid' ? 'PENDING PAYMENT' : 'TO BE COLLECTED' }}
              </span>
            </div>
            <p v-if="deliveryRequest.isPaid() && deliveryRequest.payment" class="mt-1 text-sm">
              Paid on: {{ formatDateTime(deliveryRequest.payment.created_at) }}
              <span v-if="deliveryRequest.payment_method">via {{ deliveryRequest.payment_method.toUpperCase() }}</span>
            </p>
          </div>

          <!-- Billing Information -->
          <div class="mb-8">
            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Billing Details</h3>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
              <div>
                <h4 class="text-md font-medium text-gray-700 dark:text-gray-300 mb-2">Customer Information</h4>
                <p class="text-sm text-gray-900 dark:text-gray-100">
                  {{ deliveryRequest.sender?.name || deliveryRequest.sender?.company_name || 'N/A' }}
                </p>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                  {{ deliveryRequest.sender?.address || 'N/A' }}
                </p>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                  {{ deliveryRequest.sender?.mobile || 'N/A' }}
                </p>
              </div>
              
              <div>
                <h4 class="text-md font-medium text-gray-700 dark:text-gray-300 mb-2">Service Information</h4>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                  Waybill Number: <span class="font-medium text-gray-900 dark:text-gray-100">{{ waybillNumber }}</span>
                </p>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                  Request Date: <span class="font-medium text-gray-900 dark:text-gray-100">{{ formatDate(deliveryRequest.created_at) }}</span>
                </p>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                  Delivery Status: 
                  <span class="font-medium" :class="{
                    'text-green-600 dark:text-green-400': deliveryRequest.delivery_order?.status === 'completed',
                    'text-blue-600 dark:text-blue-400': deliveryRequest.delivery_order?.status === 'in_transit',
                    'text-yellow-600 dark:text-yellow-400': deliveryRequest.delivery_order?.status === 'pending'
                  }">
                    {{ deliveryRequest.delivery_order?.status?.toUpperCase() || 'PENDING' }}
                  </span>
                </p>
              </div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <h4 class="text-md font-medium text-gray-700 dark:text-gray-300 mb-2">Origin</h4>
                <p class="text-sm text-gray-900 dark:text-gray-100">
                  {{ deliveryRequest.pickUpRegion?.name || 'N/A' }}
                </p>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                  {{ deliveryRequest.pickUpRegion?.address || 'N/A' }}
                </p>
              </div>
              
              <div>
                <h4 class="text-md font-medium text-gray-700 dark:text-gray-300 mb-2">Destination</h4>
                <p class="text-sm text-gray-900 dark:text-gray-100">
                  {{ deliveryRequest.dropOffRegion?.name || 'N/A' }}
                </p>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                  {{ deliveryRequest.dropOffRegion?.address || 'N/A' }}
                </p>
              </div>
            </div>
          </div>

          <!-- Pricing Breakdown -->
          <div class="mb-8">
            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Pricing Breakdown</h3>
            
            <div class="overflow-x-auto">
              <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                  <tr>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                      Item
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                      Description
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                      Dimensions (cm)
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                      Weight (kg)
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                      Price
                    </th>
                  </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                  <tr v-for="pkg in deliveryRequest.packages" :key="pkg.id">
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">
                      {{ pkg.item_code || 'N/A' }}
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-300">
                      {{ pkg.item_name || 'Unspecified Item' }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">
                      {{ pkg.length }} × {{ pkg.width }} × {{ pkg.height }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">
                      {{ pkg.weight || 'N/A' }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">
                      {{ formatCurrency(calculatePackagePrice(pkg)) }}
                    </td>
                  </tr>
                </tbody>
                <tfoot class="bg-gray-50 dark:bg-gray-700">
                  <tr>
                    <td colspan="4" class="px-6 py-4 text-right text-sm font-medium text-gray-500 dark:text-gray-300">
                      Subtotal:
                    </td>
                   <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100">
                    {{ formatCurrency(priceMatrix?.base_fee || 0) }} <!-- FIXED: Only one ) -->
                  </td>
                  </tr>
                  <tr>
                    <td colspan="4" class="px-6 py-4 text-right text-sm font-medium text-gray-500 dark:text-gray-300">
                      Base Fee:
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100">
                      {{ formatCurrency(priceMatrix?.base_fee || 0)) }}
                    </td>
                  </tr>
                  <tr>
                    <td colspan="4" class="px-6 py-4 text-right text-sm font-medium text-gray-500 dark:text-gray-300">
                      Total:
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-900 dark:text-gray-100">
                      {{ formatCurrency(deliveryRequest.total_price) }}
                    </td>
                  </tr>
                </tfoot>
              </table>
            </div>
          </div>

          <!-- Delivery Information -->
          <div>
            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Delivery Information</h3>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <h4 class="text-md font-medium text-gray-700 dark:text-gray-300 mb-2">Assigned Driver</h4>
                <p class="text-sm text-gray-900 dark:text-gray-100">
                  {{ deliveryRequest.deliveryOrder?.driver?.name || 'Not assigned yet' }}
                </p>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                  {{ deliveryRequest.deliveryOrder?.driver?.mobile || '' }}
                </p>
                <p v-if="deliveryRequest.deliveryOrder?.estimated_arrival" class="text-sm text-gray-500 dark:text-gray-400 mt-2">
                  <span class="font-medium">ETA:</span> {{ formatDate(deliveryRequest.deliveryOrder.estimated_arrival) }}
                </p>
              </div>
              
              <div>
                <h4 class="text-md font-medium text-gray-700 dark:text-gray-300 mb-2">Assigned Truck</h4>
                <p class="text-sm text-gray-900 dark:text-gray-100">
                  {{ deliveryRequest.deliveryOrder?.truck?.license_plate || 'Not assigned yet' }}
                </p>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                  {{ deliveryRequest.deliveryOrder?.truck?.make }} {{ deliveryRequest.deliveryOrder?.truck?.model }}
                </p>
                <p v-if="deliveryRequest.deliveryOrder?.current_region" class="text-sm text-gray-500 dark:text-gray-400 mt-2">
                  <span class="font-medium">Current Location:</span> {{ deliveryRequest.deliveryOrder.current_region.name }}
                </p>
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
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { router } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
  deliveryRequest: {
    type: Object,
    required: true
  },
  waybillNumber: {
    type: String,
    required: true
  },
  priceMatrix: {
    type: Object,
    default: () => ({})
  }
});

const formatCurrency = (amount) => {
  if (isNaN(amount)) return '₱0.00';
  return new Intl.NumberFormat('en-PH', {
    style: 'currency',
    currency: 'PHP',
    minimumFractionDigits: 2
  }).format(amount);
};

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

const calculatePackagePrice = (pkg) => {
  if (!props.priceMatrix) return 0;
  
  const volume = (pkg.height * pkg.width * pkg.length) / 1000000; // Convert to m³
  return (volume * props.priceMatrix.volume_rate) 
       + (pkg.weight * props.priceMatrix.weight_rate)
       + props.priceMatrix.package_rate;
};

const generateWaybill = () => {
  router.post(route('waybills.generate', props.deliveryRequest.id), {
    onSuccess: () => {
      router.visit(route('waybills.index'));
    }
  });
};

const printWaybill = () => {
  // Always print the initial waybill (not final)
  window.open(route('waybills.download', { 
    id: props.deliveryRequest.waybill?.id,
    final: false 
  }), '_blank');
};

const printFinalWaybill = () => {
  // Always print the final/complete waybill
  window.open(route('waybills.download', { 
    id: props.deliveryRequest.waybill?.id,
    final: true 
  }), '_blank');
};
</script>