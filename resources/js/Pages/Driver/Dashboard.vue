<script setup>
import EmployeeLayout from '@/Layouts/EmployeeLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import Pagination from '@/Components/Pagination.vue';

const props = defineProps({
    stats: Object,
    activeDeliveries: Array,
    recentDeliveries: Object, // Change to Object for pagination
    currentTruck: Object,
    user: Object
});

const formatNumber = (num) => {
    return num?.toLocaleString() ?? '0';
};

function goToStatusUpdate() {
  window.location.href = route('driver.status-update');
}
function goToTrack(packageId) {
  window.location.href = route('driver.track-package', { package: packageId });
}
function goToDeliveryShow(deliveryId) {
  router.visit(route('deliveries.show', { delivery: deliveryId }));
}
function goToAssignedDeliveries() {
  router.visit(route('driver.assigned-deliveries'));
}
function goToViewAllDeliveries() {
  router.visit(route('driver.assigned-deliveries'));
}

// Pagination handler for recent deliveries
function handleRecentPageChange(page) {
  router.visit(route('driver.dashboard', { page }), {
    preserveScroll: true,
    preserveState: true,
  });
}
</script>

<template>
    <Head title="Driver Dashboard" />

    <EmployeeLayout>
        <template #header>
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                <div>
                    <h2 class="text-2xl font-bold leading-tight text-gray-900 dark:text-gray-100">
                        Dashboard - Welcome, {{ user.name }}
                    </h2>
                </div>
                <SecondaryButton @click="goToAssignedDeliveries" class="ml-0 sm:ml-4 mt-3 sm:mt-0">
                    <svg class="h-5 w-5 mr-2" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    Assigned Deliveries
                </SecondaryButton>
            </div>
        </template>
        
        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <!-- Stats Cards -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                    <div class="p-6 bg-white rounded-lg shadow dark:bg-gray-800 h-full flex flex-col justify-between">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Active Deliveries</p>
                                <p class="text-2xl font-semibold text-gray-900 dark:text-white">
                                    {{ formatNumber(stats.active_deliveries) }}
                                </p>
                            </div>
                            <div class="p-3 rounded-full bg-blue-100 text-blue-600 dark:bg-blue-900 dark:text-blue-200">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                </svg>
                            </div>
                        </div>
                    </div>

                    <div class="p-6 bg-white rounded-lg shadow dark:bg-gray-800 h-full flex flex-col justify-between">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Packages In Transit</p>
                                <p class="text-2xl font-semibold text-gray-900 dark:text-white">
                                    {{ formatNumber(stats.packages_in_transit) }}
                                </p>
                            </div>
                            <div class="p-3 rounded-full bg-yellow-100 text-yellow-600 dark:bg-yellow-900 dark:text-yellow-200">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0" />
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Current Truck -->
                <div class="mb-8" v-if="currentTruck">
                    <div class="p-6 bg-white rounded-lg shadow dark:bg-gray-800">
                        <div class="flex items-center gap-2 mb-4">
                            <svg class="h-5 w-5 text-blue-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                            </svg>
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white">Current Truck Assignment</h3>
                        </div>
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                            <div>
                                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Make & Model</p>
                                <p class="text-gray-900 dark:text-white">{{ currentTruck.make }} {{ currentTruck.model }}</p>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">License Plate</p>
                                <p class="text-gray-900 dark:text-white">{{ currentTruck.license_plate }}</p>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Status</p>
                                <p class="text-gray-900 dark:text-white capitalize">
                                    <span
                                      :class="{
                                        'bg-blue-100 text-blue-800 px-2 py-0.5 rounded-full font-semibold': currentTruck.status === 'in_transit',
                                        'bg-green-100 text-green-800 px-2 py-0.5 rounded-full font-semibold': currentTruck.status === 'available',
                                        'bg-yellow-100 text-yellow-800 px-2 py-0.5 rounded-full font-semibold': currentTruck.status === 'maintenance',
                                        'bg-gray-100 text-gray-800 px-2 py-0.5 rounded-full': !['in_transit','available','maintenance'].includes(currentTruck.status)
                                      }"
                                    >
                                      {{ currentTruck.status === 'in_transit'
                                          ? 'In Transit'
                                          : currentTruck.status.charAt(0).toUpperCase() + currentTruck.status.slice(1).replace('_', ' ') }}
                                    </span>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Active Deliveries -->
                <div class="mb-8">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center gap-2">
                            <svg class="h-5 w-5 text-blue-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 17v-2a4 4 0 018 0v2M12 7a4 4 0 100 8 4 4 0 000-8z" />
                            </svg>
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white">Active Deliveries</h3>
                        </div>
                        <SecondaryButton @click="goToViewAllDeliveries" class="ml-4">
                            View All
                        </SecondaryButton>
                    </div>
                    
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3" v-if="activeDeliveries.length > 0">
                        <div v-for="delivery in activeDeliveries.slice(0, 3)" :key="delivery.id" class="p-4 bg-white rounded-lg shadow dark:bg-gray-800">
                            <div class="flex justify-between items-start mb-2">
                                <span class="text-sm font-medium text-gray-900 dark:text-white">{{ delivery.receiver }}</span>
                                <span class="px-2 py-1 text-xs rounded-full" 
                                    :class="{
                                        'bg-yellow-100 text-yellow-800': delivery.status === 'assigned',
                                        'bg-blue-100 text-blue-800': delivery.status === 'dispatched',
                                        'bg-green-100 text-green-800': delivery.status === 'in_transit'
                                    }">
                                    {{ delivery.status === 'in_transit' ? 'In Transit' : delivery.status.charAt(0).toUpperCase() + delivery.status.slice(1) }}
                                </span>
                            </div>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mb-2">{{ delivery.estimated_arrival }}</p>
                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ delivery.package_count }} packages</p>
                            <div class="mt-4 flex space-x-2">
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
                    </div>
                    <div v-else class="p-6 text-center bg-white rounded-lg shadow dark:bg-gray-800">
                        <p class="text-gray-500 dark:text-gray-400">No active deliveries</p>
                    </div>
                </div>

                <!-- Recent Deliveries -->
                <div>
                    <div class="flex items-center gap-2 mb-4">
                        <svg class="h-5 w-5 text-blue-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                        </svg>
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white">Recent Deliveries</h3>
                    </div>
                    <div class="overflow-hidden bg-white shadow dark:bg-gray-800 sm:rounded-lg">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-300">
                                        Reference #
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-300">
                                        Receiver
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-300">
                                        Packages
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-300">
                                        Delivered At
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-300">
                                        Status
                                    </th>
                                    <th scope="col" class="px-6 py-3"></th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200 dark:bg-gray-800 dark:divide-gray-700">
                                <tr v-for="delivery in recentDeliveries.data || []" :key="delivery.id">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                        {{ delivery.reference_number || 'N/A' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                        {{ delivery.receiver }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">
                                        {{ delivery.package_count }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">
                                        {{ delivery.delivered_at ? delivery.delivered_at : '—' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        <span class="px-2 py-1 rounded-full text-xs bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                            Completed
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm">
                                        <SecondaryButton
                                            type="button"
                                            @click="goToDeliveryShow(delivery.id)"
                                            class="px-3 py-1 text-xs"
                                        >
                                            View
                                        </SecondaryButton>
                                    </td>
                                </tr>
                                <tr v-if="!recentDeliveries.data || recentDeliveries.data.length === 0">
                                    <td colspan="6" class="px-6 py-4 text-center text-sm text-gray-500 dark:text-gray-300">
                                        No recent deliveries
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        <!-- Pagination Component -->
                        <Pagination
                          v-if="recentDeliveries.last_page > 1"
                          :pagination="recentDeliveries"
                          @page-changed="handleRecentPageChange"
                        />
                    </div>
                </div>
            </div>
        </div>
    </EmployeeLayout>
</template>