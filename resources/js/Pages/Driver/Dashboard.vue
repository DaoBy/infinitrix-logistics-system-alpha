<script setup>
import EmployeeLayout from '@/Layouts/EmployeeLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

const props = defineProps({
    stats: Object,
    activeDeliveries: Array,
    recentDeliveries: Object,
    currentTruck: Object,
    user: Object,
    backhaul_available: Boolean
});

const formatNumber = (num) => {
    return num?.toLocaleString() ?? '0';
};

function goToAssignedDeliveries() {
  router.visit(route('driver.assigned-deliveries'));
}

// Handle backhaul enable/disable
function toggleBackhaul() {
  if (props.backhaul_available) {
    // Disable backhaul
    router.post(route('driver.backhaul.disable'), {}, {
      preserveScroll: true,
      onSuccess: () => {
        router.reload();
      }
    });
  } else {
    // Enable backhaul
    router.post(route('driver.backhaul.enable'), {}, {
      preserveScroll: true,
      onSuccess: () => {
        router.reload();
      }
    });
  }
}

// Helper function to format truck status with null checking
function formatTruckStatus(status) {
  if (!status) return 'Unknown';
  
  const statusMap = {
    'in_transit': 'In Transit',
    'available': 'Available',
    'available_for_backhaul': 'Available for Backhaul',
    'maintenance': 'Maintenance',
    'returning': 'Returning',
    'assigned': 'Assigned'
  };
  
  return statusMap[status] || status.charAt(0).toUpperCase() + status.slice(1).replace('_', ' ');
}

// Helper function to get truck status classes
function getTruckStatusClasses(status) {
  if (!status) return 'bg-gray-100 text-gray-800 px-2 py-0.5 rounded-full';
  
  const classMap = {
    'in_transit': 'bg-blue-100 text-blue-800 px-2 py-0.5 rounded-full font-semibold',
    'available': 'bg-green-100 text-green-800 px-2 py-0.5 rounded-full font-semibold',
    'available_for_backhaul': 'bg-purple-100 text-purple-800 px-2 py-0.5 rounded-full font-semibold',
    'maintenance': 'bg-yellow-100 text-yellow-800 px-2 py-0.5 rounded-full font-semibold',
    'returning': 'bg-orange-100 text-orange-800 px-2 py-0.5 rounded-full font-semibold',
    'assigned': 'bg-indigo-100 text-indigo-800 px-2 py-0.5 rounded-full font-semibold'
  };
  
  return classMap[status] || 'bg-gray-100 text-gray-800 px-2 py-0.5 rounded-full';
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
                    <!-- Backhaul Status Badge -->
                    <div class="mt-1 flex items-center gap-2">
                        <span 
                            :class="[
                                'px-2 py-1 text-xs font-medium rounded-full',
                                backhaul_available 
                                    ? 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200' 
                                    : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300'
                            ]"
                        >
                            {{ backhaul_available ? '🚛 Backhaul Available' : '📦 Regular Assignment' }}
                        </span>
                        <span v-if="backhaul_available" class="text-xs text-purple-600 dark:text-purple-400">
                            Available for return trip assignments
                        </span>
                    </div>
                </div>
                <div class="flex gap-2">
                    <!-- Backhaul Toggle Button -->
                    <PrimaryButton 
                        v-if="stats.backhaul_eligible"
                        @click="toggleBackhaul"
                        :class="[
                            'ml-0 sm:ml-4 mt-3 sm:mt-0',
                            backhaul_available ? 'bg-red-600 hover:bg-red-700' : 'bg-purple-600 hover:bg-purple-700'
                        ]"
                    >
                        <svg class="h-5 w-5 mr-2" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path v-if="backhaul_available" stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            <path v-else stroke-linecap="round" stroke-linejoin="round" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                        </svg>
                        {{ backhaul_available ? 'Disable Backhaul' : 'Enable Backhaul' }}
                    </PrimaryButton>
                    
                    <SecondaryButton @click="goToAssignedDeliveries" class="ml-0 sm:ml-4 mt-3 sm:mt-0">
                        <svg class="h-5 w-5 mr-2" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                        </svg>
                        Assigned Deliveries
                    </SecondaryButton>
                </div>
            </div>
        </template>
        
        <div class="py-8 sm:py-12">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <!-- Stats Cards - Compact & Mobile Friendly -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 mb-8">
                    <!-- Active Deliveries Card -->
                    <div class="p-4 bg-white rounded-lg shadow dark:bg-gray-800 border-l-4 border-blue-500">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="p-2 rounded-full bg-blue-100 text-blue-600 dark:bg-blue-900 dark:text-blue-200">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Active Deliveries</p>
                                    <p class="text-xl font-semibold text-gray-900 dark:text-white">
                                        {{ formatNumber(stats.active_deliveries) }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Total Packages Card -->
                    <div class="p-4 bg-white rounded-lg shadow dark:bg-gray-800 border-l-4 border-green-500">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="p-2 rounded-full bg-green-100 text-green-600 dark:bg-green-900 dark:text-green-200">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Packages</p>
                                    <p class="text-xl font-semibold text-gray-900 dark:text-white">
                                        {{ formatNumber(stats.total_packages || 0) }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Assignment Type Card -->
                    <div class="p-4 bg-white rounded-lg shadow dark:bg-gray-800 border-l-4" 
                         :class="backhaul_available ? 'border-purple-500' : 'border-gray-500'">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="p-2 rounded-full" 
                                     :class="backhaul_available 
                                         ? 'bg-purple-100 text-purple-600 dark:bg-purple-900 dark:text-purple-200' 
                                         : 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300'">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path v-if="backhaul_available" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                                        <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18" />
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Assignment Type</p>
                                    <p class="text-xl font-semibold" 
                                       :class="backhaul_available ? 'text-purple-600 dark:text-purple-400' : 'text-gray-900 dark:text-white'">
                                        {{ backhaul_available ? 'Backhaul' : 'Regular' }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Current Truck - Compact Design -->
                <div class="mb-8" v-if="currentTruck">
                    <div class="p-4 bg-white rounded-lg shadow dark:bg-gray-800">
                        <div class="flex items-center gap-3 mb-3">
                            <div class="p-2 rounded-full bg-blue-100 text-blue-600 dark:bg-blue-900 dark:text-blue-200">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                </svg>
                            </div>
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white">Current Truck</h3>
                            <!-- Backhaul badge in truck section -->
                            <span v-if="backhaul_available" class="px-2 py-1 text-xs bg-purple-100 text-purple-800 rounded-full font-medium dark:bg-purple-900 dark:text-purple-200">
                                Backhaul Mode
                            </span>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 text-sm">
                            <div class="flex flex-col">
                                <span class="text-xs font-medium text-gray-500 dark:text-gray-400">Make & Model</span>
                                <span class="text-gray-900 dark:text-white">{{ currentTruck.make }} {{ currentTruck.model }}</span>
                            </div>
                            <div class="flex flex-col">
                                <span class="text-xs font-medium text-gray-500 dark:text-gray-400">License Plate</span>
                                <span class="text-gray-900 dark:text-white">{{ currentTruck.license_plate }}</span>
                            </div>
                            <div class="flex flex-col">
                                <span class="text-xs font-medium text-gray-500 dark:text-gray-400">Status</span>
                                <span class="text-gray-900 dark:text-white">
                                    <span :class="getTruckStatusClasses(currentTruck.status)" class="text-xs">
                                        {{ formatTruckStatus(currentTruck.status) }}
                                    </span>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Active Deliveries - Compact Design -->
                <div class="mb-8">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="p-2 rounded-full bg-green-100 text-green-600 dark:bg-green-900 dark:text-green-200">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white">Active Deliveries</h3>
                        <!-- Backhaul filter badge -->
                        <span 
                            class="px-2 py-1 text-xs rounded-full cursor-pointer transition-colors"
                            :class="backhaul_available 
                                ? 'bg-purple-100 text-purple-800 border border-purple-300 dark:bg-purple-900 dark:text-purple-200 dark:border-purple-700'
                                : 'bg-gray-100 text-gray-800 border border-gray-300 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600'"
                            @click="toggleBackhaul"
                        >
                            {{ backhaul_available ? '🚛 Backhaul' : '📦 Regular' }}
                        </span>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4" v-if="activeDeliveries.length > 0">
                        <div v-for="delivery in activeDeliveries.slice(0, 6)" :key="delivery.id" 
                             class="p-4 bg-white rounded-lg shadow dark:bg-gray-800 border-l-4 hover:shadow-md transition-shadow"
                             :class="delivery.is_backhaul ? 'border-purple-500' : 'border-blue-500'">
                            <!-- Delivery Header -->
                            <div class="flex justify-between items-start mb-3">
                                <div class="flex-1 min-w-0">
                                    <h4 class="text-sm font-semibold text-gray-900 dark:text-white truncate">
                                        {{ delivery.receiver }}
                                    </h4>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                        {{ delivery.estimated_arrival }}
                                    </p>
                                </div>
                                <div class="flex flex-col items-end gap-1 ml-2">
                                    <!-- Status Badge -->
                                    <span class="px-2 py-1 text-xs rounded-full" 
                                        :class="{
                                            'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200': delivery.status === 'assigned',
                                            'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200': delivery.status === 'dispatched',
                                            'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200': delivery.status === 'in_transit'
                                        }">
                                        {{ delivery.status === 'in_transit' ? 'In Transit' : delivery.status.charAt(0).toUpperCase() + delivery.status.slice(1) }}
                                    </span>
                                    <!-- Backhaul delivery badge -->
                                    <span v-if="delivery.is_backhaul" class="px-2 py-0.5 text-xs bg-purple-100 text-purple-800 rounded-full dark:bg-purple-900 dark:text-purple-200">
                                        Backhaul
                                    </span>
                                </div>
                            </div>

                            <!-- Delivery Details -->
                            <div class="space-y-2 text-xs text-gray-600 dark:text-gray-300">
                                <div class="flex justify-between">
                                    <span class="font-medium">Packages:</span>
                                    <span>{{ delivery.package_count }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="font-medium">Reference:</span>
                                    <span class="truncate ml-2">{{ delivery.reference_number || 'N/A' }}</span>
                                </div>
                            </div>

                            <!-- Backhaul info -->
                            <div v-if="delivery.is_backhaul" class="mt-3 p-2 bg-purple-50 rounded text-xs text-purple-700 dark:bg-purple-900/20 dark:text-purple-300">
                                <div class="flex items-center gap-1">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                                    </svg>
                                    <strong>Backhaul Assignment</strong>
                                </div>
                                <div class="text-xs mt-1">Return trip to home region</div>
                            </div>

                            <!-- Action Button -->
                            <div class="mt-4">
                                <PrimaryButton
                                    @click="goToAssignedDeliveries"
                                    class="w-full justify-center text-sm py-2"
                                >
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                    </svg>
                                    View Details
                                </PrimaryButton>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Empty State -->
                    <div v-else class="p-6 text-center bg-white rounded-lg shadow dark:bg-gray-800">
                        <div class="p-3 rounded-full bg-gray-100 text-gray-400 dark:bg-gray-700 dark:text-gray-500 inline-flex mb-3">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2a4 4 0 018 0v2M12 7a4 4 0 100 8 4 4 0 000-8z" />
                            </svg>
                        </div>
                        <p class="text-gray-500 dark:text-gray-400">
                            {{ backhaul_available ? 'No backhaul assignments available' : 'No active deliveries' }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </EmployeeLayout>
</template>