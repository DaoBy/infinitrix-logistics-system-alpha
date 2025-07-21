<template>
  <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4 border border-gray-200 dark:border-gray-600">
    <h3 class="font-medium text-gray-900 dark:text-gray-100 mb-2">Delivery Summary</h3>
    <div class="grid grid-cols-2 gap-4">
      <div>
        <p class="text-xs text-gray-500 dark:text-gray-400">DR Number</p>
        <p class="text-sm font-medium text-gray-900 dark:text-gray-100">DR-{{ delivery.delivery_request.id.toString().padStart(6, '0') }}</p>
      </div>
      <div>
        <p class="text-xs text-gray-500 dark:text-gray-400">Status</p>
        <StatusBadge :status="delivery.status" />
      </div>
      <div>
        <p class="text-xs text-gray-500 dark:text-gray-400">Route</p>
        <p class="text-sm font-medium text-gray-900 dark:text-gray-100">
          {{ delivery.delivery_request.pickUpBranch?.name || 'N/A' }} → {{ delivery.delivery_request.dropOffBranch?.name || 'N/A' }}
        </p>
      </div>
      <div>
        <p class="text-xs text-gray-500 dark:text-gray-400">Total Volume</p>
        <p class="text-sm font-medium text-gray-900 dark:text-gray-100">
          {{ calculateTotalVolume(delivery.delivery_request.packages) }} m³
        </p>
      </div>
    </div>
  </div>
</template>

<script setup>
import StatusBadge from './StatusBadge.vue';
import { calculateTotalVolume } from '@/Utils/helpers';

defineProps({
  delivery: {
    type: Object,
    required: true
  }
});
</script>