<template>
  <Head title="Assigned Deliveries" />

  <EmployeeLayout>
    <template #header>
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
        <div>
          <h2 class="text-2xl font-bold leading-tight text-gray-900 dark:text-gray-100">
            Assigned Deliveries
          </h2>
        </div>
        <SecondaryButton
          @click="goToDashboard"
          class="ml-0 sm:ml-4 mt-3 sm:mt-0"
        >
          <svg class="h-5 w-5 mr-2" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3 10h2l.4 2M7 13h10l4-8H5.4" />
          </svg>
          Dashboard
        </SecondaryButton>
      </div>
    </template>

    <div class="py-12">
      <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
        <div class="bg-white shadow-sm rounded-xl dark:bg-gray-800 overflow-hidden">
          <div class="p-6">
            <div v-if="deliveries.length === 0" class="text-center py-12">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 mx-auto text-gray-300 dark:text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 17v-2a4 4 0 018 0v2M12 7a4 4 0 100 8 4 4 0 000-8z" />
              </svg>
              <p class="mt-4 text-gray-500 dark:text-gray-400">No assigned deliveries found</p>
            </div>

            <div v-else class="space-y-8">
              <div 
                v-for="delivery in deliveries" 
                :key="delivery.id"
                class="bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg p-6 shadow-sm"
              >
                <div class="flex flex-col md:flex-row md:justify-between md:items-center gap-4">
                  <!-- Status and Estimated Arrival -->
                  <div class="flex items-center gap-3">
                    <span 
                      class="px-3 py-1 text-sm font-medium rounded-full"
                      :class="{
                        'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-200': delivery.status === 'assigned',
                        'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-200': delivery.status === 'dispatched',
                        'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-200': delivery.status === 'in_transit'
                      }"
                    >
                      {{ formatStatus(delivery.status) }}
                    </span>
                    <span class="text-sm text-gray-500 dark:text-gray-300 flex items-center gap-1">
                      <svg class="h-4 w-4 inline mr-1" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                      </svg>
                      {{ delivery.estimated_arrival || 'N/A' }}
                    </span>
                  </div>
                  <!-- Actions -->
                  <div class="flex gap-3">
                    <PrimaryButton
                      type="button"
                      class="flex items-center"
                      @click="goToStatusUpdate"
                    >
                      Update Status
                    </PrimaryButton>
                    <PrimaryButton
                      v-if="delivery.packages && delivery.packages.length > 0"
                      type="button"
                      class="flex items-center"
                      @click="goToTrack(delivery.packages[0].id)"
                    >
                      Track
                    </PrimaryButton>
                  </div>
                </div>

                <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                  <!-- Receiver and Truck -->
                  <div class="space-y-3">
                    <div>
                      <h3 class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase flex items-center gap-1">
                        <svg class="h-4 w-4 text-blue-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                        Receiver
                      </h3>
                      <p class="text-base text-gray-900 dark:text-white">{{ delivery.receiver || 'N/A' }}</p>
                    </div>
                    <div>
                      <h3 class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase flex items-center gap-1">
                        <svg class="h-4 w-4 text-blue-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                        </svg>
                        Truck
                      </h3>
                      <p class="text-base text-gray-900 dark:text-white">
                        <span v-if="delivery.truck">
                          {{ delivery.truck.make }} {{ delivery.truck.model }} ({{ delivery.truck.license_plate }})
                        </span>
                        <span v-else>N/A</span>
                      </p>
                    </div>
                  </div>
                  <!-- Packages -->
                  <div>
                    <div class="flex items-center justify-between">
                      <h3 class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase flex items-center gap-1">
                        <svg class="h-4 w-4 text-blue-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                          <rect width="20" height="12" x="2" y="6" rx="2" />
                        </svg>
                        Packages ({{ delivery.package_count || 0 }})
                      </h3>
                      <SecondaryButton
                        type="button"
                        @click="togglePackageDetails(delivery.id)"
                        class="text-xs px-2 py-1"
                      >
                        {{ showDetails[delivery.id] ? 'Hide' : 'Show' }}
                      </SecondaryButton>
                    </div>
                    <div v-if="showDetails[delivery.id] && delivery.packages" class="mt-2 space-y-1">
                      <div 
                        v-for="pkg in delivery.packages" 
                        :key="pkg.id"
                        class="text-sm text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-800 rounded px-2 py-1"
                      >
                        {{ pkg.item_code || 'N/A' }}
                      </div>
                    </div>
                  </div>
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
// filepath: c:\Users\Administrator\Desktop\BackupX3\Infinitrix_Logistics_System-620c13a1ffa127492a69167691c2c187a86ba632\backend\resources\js\Pages\Driver\AssignedDeliveries.vue
import EmployeeLayout from '@/Layouts/EmployeeLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref } from 'vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { router } from '@inertiajs/vue3';

const props = defineProps({
  deliveries: {
    type: Array,
    default: () => []
  }
});

const showDetails = ref({});

const togglePackageDetails = (deliveryId) => {
  showDetails.value = {
    ...showDetails.value,
    [deliveryId]: !showDetails.value[deliveryId]
  };
};

function formatStatus(status) {
  if (!status) return 'N/A';
  const map = {
    assigned: 'Assigned',
    dispatched: 'Dispatched',
    in_transit: 'In Transit',
    delivered: 'Delivered',
    completed: 'Completed',
    returned: 'Returned',
    rejected: 'Rejected'
  };
  return map[status] || (typeof status === 'string' ? status.charAt(0).toUpperCase() + status.slice(1) : status);
}

// Button navigation handlers
function goToStatusUpdate() {
  router.visit(route('driver.status-update'));
}
function goToTrack(packageId) {
  router.visit(route('driver.track-package', { package: packageId }));
}
function goToDashboard() {
  router.visit(route('driver.dashboard'));
}
</script>