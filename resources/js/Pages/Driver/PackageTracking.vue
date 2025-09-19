<template>
  <Head :title="`Tracking Package ${packageData.item_code || ''}`" />

  <EmployeeLayout>
    <div class="py-8">
      <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
        <!-- Universal Back Button using SecondaryButton -->
        <SecondaryButton
          type="button"
          @click="goBack"
          class="mb-4 inline-flex items-center"
        >
          <svg class="h-5 w-5 mr-2" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
          </svg>
          Back
        </SecondaryButton>

        <!-- Package Header -->
        <div class="bg-white rounded-xl shadow-sm p-6 mb-6 dark:bg-gray-800">
          <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
              <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                Tracking #{{ packageData.item_code || 'N/A' }}
              </h1>
              <p class="text-gray-600 dark:text-gray-300 mt-1">
                {{ packageData.item_name || 'N/A' }}
              </p>
            </div>
            <div class="flex items-center gap-3">
              <div class="flex-shrink-0">
                <img v-if="packageData.photo_url" :src="packageData.photo_url" alt="Package photo" 
                     class="h-16 w-16 rounded-lg object-cover border border-gray-200 dark:border-gray-600">
              </div>
              <div class="text-right">
                <div class="px-3 py-1 text-sm font-medium rounded-full inline-flex items-center gap-1"
                     :class="statusClasses">
                  <span class="w-2 h-2 rounded-full" :class="statusDotClasses"></span>
                  {{ formattedStatus }}
                </div>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                  Last updated: {{ lastUpdatedTime }}
                </p>
              </div>
            </div>
          </div>
        </div>

        <!-- Main Content Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
          <!-- Left Column - Combined Journey Timeline -->
          <div class="lg:col-span-2">
            <!-- Delivery Journey with Transfers -->
            <div class="bg-white rounded-xl shadow-sm p-6 dark:bg-gray-800">
              <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-indigo-500" viewBox="0 0 20 20" fill="currentColor">
                  <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-8.707l-3-3a1 1 0 00-1.414 0l-3 3a1 1 0 001.414 1.414L9 9.414V13a1 1 0 102 0V9.414l1.293 1.293a1 1 0 001.414-1.414z" clip-rule="evenodd" />
                </svg>
                Delivery Journey
              </h2>
              
              <div class="relative">
                <!-- Journey Line -->
                <div class="absolute left-4 top-0 bottom-0 w-0.5 bg-gray-200 dark:bg-gray-700"></div>
                
                <div class="space-y-6 pl-8">
                  <template v-if="combinedTimeline.length > 0">
                    <div v-for="(item, index) in combinedTimeline" :key="index" class="relative">
                      <!-- Timeline Dot -->
                      <div class="absolute -left-8 top-1.5 flex h-4 w-4 items-center justify-center rounded-full"
                          :class="{
                            'bg-indigo-500 ring-4 ring-indigo-200 dark:ring-indigo-900': index === 0,
                            'bg-gray-300 dark:bg-gray-600': index !== 0
                          }">
                        <div v-if="index === 0" class="h-1.5 w-1.5 rounded-full bg-white"></div>
                      </div>
                      
                      <!-- Status Card -->
                      <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-4 shadow-xs">
                        <div class="flex justify-between items-start">
                          <div>
                            <h3 class="font-medium text-gray-900 dark:text-white">
                              <template v-if="item.type === 'status' && item.data.status === 'in_transit' && item.data.remarks && item.data.remarks.toLowerCase().includes('dispatched')">
                                Dispatched
                              </template>
                              <template v-else-if="item.type === 'transfer'">
                                In Transit
                              </template>
                              <template v-else>
                                {{ formatStatus(item.data.status) }}
                              </template>
                            </h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                              {{ formatDateTime(item.timestamp) }}
                            </p>
                          </div>
                          <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium"
                                :class="getStatusBadgeClass(
                                  item.type === 'status' && item.data.status === 'in_transit' && item.data.remarks && item.data.remarks.toLowerCase().includes('dispatched')
                                    ? 'dispatched'
                                    : item.type === 'transfer'
                                      ? 'in_transit'
                                      : item.data.status
                                )">
                            <template v-if="item.type === 'status' && item.data.status === 'in_transit' && item.data.remarks && item.data.remarks.toLowerCase().includes('dispatched')">
                              Dispatched
                            </template>
                            <template v-else-if="item.type === 'transfer'">
                              In Transit
                            </template>
                            <template v-else>
                              {{ formatStatus(item.data.status) }}
                            </template>
                          </span>
                        </div>
                        
                        <div v-if="item.type === 'status' && item.data.remarks" class="mt-2 text-sm text-gray-600 dark:text-gray-300">
                          <p>{{ item.data.remarks }}</p>
                        </div>

                        <div v-if="item.type === 'transfer'" class="mt-3">
                          <div class="flex items-center text-sm text-gray-600 dark:text-gray-300">
                            <div class="flex-shrink-0 h-5 w-5 text-gray-400 mr-2">
                              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                              </svg>
                            </div>
                            <div>
                              <span class="font-medium">{{ item.data.from || 'N/A' }}</span>
                              <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mx-1 inline text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3" />
                              </svg>
                              <span class="font-medium">{{ item.data.to || 'N/A' }}</span>
                            </div>
                          </div>
                          <div v-if="item.data.remarks" class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                            {{ item.data.remarks }}
                          </div>
                        </div>
                        
                        <div class="mt-2 text-xs text-gray-500 dark:text-gray-400 flex items-center gap-1">
                          <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                          </svg>
                          {{ item.type === 'status' 
                            ? (item.data.updated_by || (item.data.updatedBy?.name ?? 'System')) 
                            : (item.data.processor || item.data.processor_name || (item.data.processorObj?.name ?? 'System')) }}
                        </div>
                      </div>
                    </div>
                  </template>
                  
                  <div v-if="combinedTimeline.length === 0" class="text-center py-6 text-gray-500 dark:text-gray-400">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 mx-auto text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <p class="mt-2">No tracking information available</p>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Right Column - Package Details -->
          <div class="space-y-6">
            <!-- Package Information -->
            <div class="bg-white rounded-xl shadow-sm p-6 dark:bg-gray-800">
              <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-indigo-500" viewBox="0 0 20 20" fill="currentColor">
                  <path fill-rule="evenodd" d="M10 2a4 4 0 00-4 4v1H5a1 1 0 00-.994.89l-1 9A1 1 0 004 18h12a1 1 0 00.994-1.11l-1-9A1 1 0 0015 7h-1V6a4 4 0 00-4-4zm2 5V6a2 2 0 10-4 0v1h4zm-6 3a1 1 0 112 0 1 1 0 01-2 0zm7-1a1 1 0 100 2 1 1 0 000-2z" clip-rule="evenodd" />
                </svg>
                Package Details
              </h2>
              
              <div class="space-y-4">
                <div class="flex items-start">
                  <div class="flex-shrink-0 h-6 w-6 text-gray-400">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                  </div>
                  <div class="ml-3">
                    <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Sender</h3>
                    <p class="mt-1 text-sm text-gray-900 dark:text-white">
                      {{ packageData.sender || packageData.sender_name || 'N/A' }}
                    </p>
                  </div>
                </div>
                
                <div class="flex items-start">
                  <div class="flex-shrink-0 h-6 w-6 text-gray-400">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                    </svg>
                  </div>
                  <div class="ml-3">
                    <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Receiver</h3>
                    <p class="mt-1 text-sm text-gray-900 dark:text-white">
                      {{ packageData.receiver || packageData.receiver_name || 'N/A' }}
                    </p>
                  </div>
                </div>
                
                <div class="flex items-start">
                  <div class="flex-shrink-0 h-6 w-6 text-gray-400">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                  </div>
                  <div class="ml-3">
                    <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Destination</h3>
                    <p class="mt-1 text-sm text-gray-900 dark:text-white">
                      {{ packageData.destination || packageData.drop_off_region || 'N/A' }}
                    </p>
                  </div>
                </div>
                
                <div class="flex items-start">
                  <div class="flex-shrink-0 h-6 w-6 text-gray-400">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5.636 18.364a9 9 0 010-12.728m12.728 0a9 9 0 010 12.728m-9.9-2.829a5 5 0 010-7.07m7.072 0a5 5 0 010 7.07M13 12a1 1 0 11-2 0 1 1 0 012 0z" />
                    </svg>
                  </div>
                  <div class="ml-3">
                    <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Current Location</h3>
                    <p class="mt-1 text-sm text-gray-900 dark:text-white">
                      {{ packageData.current_region || packageData.current_region_name || 'N/A' }}
                    </p>
                  </div>
                </div>
                
                <div v-if="packageData.description" class="flex items-start">
                  <div class="flex-shrink-0 h-6 w-6 text-gray-400">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z" />
                    </svg>
                  </div>
                  <div class="ml-3">
                    <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Description</h3>
                    <p class="mt-1 text-sm text-gray-900 dark:text-white">
                      {{ packageData.description }}
                    </p>
                  </div>
                </div>
                
                <div v-if="packageData.category" class="flex items-start">
                  <div class="flex-shrink-0 h-6 w-6 text-gray-400">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z" />
                    </svg>
                  </div>
                  <div class="ml-3">
                    <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Category</h3>
                    <p class="mt-1 text-sm text-gray-900 dark:text-white">
                      {{ packageData.category }}
                    </p>
                  </div>
                </div>
              </div>
            </div>

            <!-- Package Specifications -->
            <div class="bg-white rounded-xl shadow-sm p-6 dark:bg-gray-800">
              <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-indigo-500" viewBox="0 0 20 20" fill="currentColor">
                  <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd" />
                </svg>
                Specifications
              </h2>
              
              <div class="space-y-4">
                <div v-if="packageData.weight" class="flex items-start">
                  <div class="flex-shrink-0 h-6 w-6 text-gray-400">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M6 18V6a2 2 0 012-2h8a2 2 0 012 2v12" />
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M6 18h12" />
                    </svg>
                  </div>
                  <div class="ml-3">
                    <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Weight</h3>
                    <p class="mt-1 text-sm text-gray-900 dark:text-white">
                      {{ packageData.weight }} kg
                    </p>
                  </div>
                </div>
                
                <div v-if="packageData.value" class="flex items-start">
                  <div class="flex-shrink-0 h-6 w-6 text-gray-400">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8c-1.657 0-3 1.343-3 3s1.343 3 3 3 3-1.343 3-3-1.343-3-3-3zm0 0V4m0 12v4" />
                    </svg>
                  </div>
                  <div class="ml-3">
                    <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Declared Value</h3>
                    <p class="mt-1 text-sm text-gray-900 dark:text-white">
                      ₱{{ Number(packageData.value).toFixed(2) }}
                    </p>
                  </div>
                </div>
                
                <div v-if="packageData.height && packageData.width && packageData.length" class="flex items-start">
                  <div class="flex-shrink-0 h-6 w-6 text-gray-400">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <rect width="20" height="12" x="2" y="6" rx="2" />
                    </svg>
                  </div>
                  <div class="ml-3">
                    <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Dimensions</h3>
                    <p class="mt-1 text-sm text-gray-900 dark:text-white">
                      {{ packageData.height }} × {{ packageData.width }} × {{ packageData.length }} cm
                    </p>
                  </div>
                </div>
                
                <div v-if="packageData.volume" class="flex items-start">
                  <div class="flex-shrink-0 h-6 w-6 text-gray-400">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <circle cx="12" cy="12" r="10" />
                    </svg>
                  </div>
                  <div class="ml-3">
                    <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Volume</h3>
                    <p class="mt-1 text-sm text-gray-900 dark:text-white">
                      {{ Number(packageData.volume || 0).toFixed(2) }} m³
                    </p>
                  </div>
                </div>

                <div v-if="packageData.item_code" class="flex items-start">
                  <div class="flex-shrink-0 h-6 w-6 text-gray-400">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <rect width="20" height="12" x="2" y="6" rx="2" />
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M6 10h.01M6 14h.01M10 10h.01M10 14h.01M14 10h.01M14 14h.01" />
                    </svg>
                  </div>
                  <div class="ml-3">
                    <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Item Code</h3>
                    <p class="mt-1 text-sm text-gray-900 dark:text-white">
                      {{ packageData.item_code }}
                    </p>
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
import EmployeeLayout from '@/Layouts/EmployeeLayout.vue';
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';

const props = defineProps({
  package: { type: Object, required: true },
  statusHistory: { type: Array, default: () => [] },
  transfers: { type: Array, default: () => [] }
});

const packageData = computed(() => props.package || {});
const statusHistoryData = computed(() => props.statusHistory || packageData.value.statusHistory || packageData.value.status_history || []);
const transfersData = computed(() => props.transfers || packageData.value.transfers || []);

// Combine status history and transfers into a single timeline
const combinedTimeline = computed(() => {
  const timeline = [];
  
  // Add status history items
  let lastStatus = null;
  statusHistoryData.value.forEach(history => {
    if (history.status !== lastStatus) {
      timeline.push({
        type: 'status',
        timestamp: history.updated_at,
        data: history
      });
      lastStatus = history.status;
    }
  });
  
  // Add transfer items
  transfersData.value.forEach(transfer => {
    timeline.push({
      type: 'transfer',
      timestamp: transfer.transferred_at || transfer.processed_at,
      data: transfer
    });
  });
  
  // Sort by timestamp (newest first)
  return timeline.sort((a, b) => new Date(b.timestamp) - new Date(a.timestamp));
});

const statusOptions = {
  'preparing': 'Preparing',
  'ready_for_pickup': 'Ready for Pickup',
  'loaded': 'Loaded',
  'in_transit': 'In Transit',
  'delivered': 'Delivered',
  'completed': 'Completed',
  'returned': 'Returned',
  'rejected': 'Rejected',
  'pending': 'Pending',
  'processing': 'Processing'
};

function formatStatus(status) {
  if (!status) return 'N/A';
  // Show "Dispatched" instead of "In Transit" if status is in_transit
  if (status === 'in_transit') return 'Dispatched';
  return statusOptions[status] || (typeof status === 'string' ? status.charAt(0).toUpperCase() + status.slice(1) : status);
}

function formatDateTime(dateString) {
  if (!dateString) return 'N/A';
  try {
    const date = new Date(dateString);
    return date.toLocaleString('en-US', {
      year: 'numeric',
      month: 'short',
      day: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    });
  } catch (e) {
    return 'Invalid date';
  }
}

const formattedStatus = computed(() => formatStatus(packageData.value.status));

const lastUpdatedTime = computed(() => {
  if (combinedTimeline.value.length > 0) {
    return formatDateTime(combinedTimeline.value[0].timestamp);
  }
  return 'N/A';
});

const statusClasses = computed(() => {
  const base = 'px-3 py-1 text-sm font-medium rounded-full inline-flex items-center';
  const status = packageData.value.status;
  if (!status) return `${base} bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300`;
  const s = String(status).toLowerCase();
  if (s === 'delivered' || s === 'completed') return `${base} bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-200`;
  if (s === 'returned') return `${base} bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-200`;
  if (s === 'in_transit' || s === 'loaded' || s === 'ready_for_pickup') return `${base} bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-200`;
  if (s === 'rejected') return `${base} bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-200`;
  if (s === 'pending' || s === 'preparing' || s === 'processing') return `${base} bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-200`;
  return `${base} bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300`;
});

const statusDotClasses = computed(() => {
  const status = packageData.value.status;
  if (!status) return 'bg-gray-400';
  const s = String(status).toLowerCase();
  if (s === 'delivered' || s === 'completed') return 'bg-green-500';
  if (s === 'returned') return 'bg-red-500';
  if (s === 'in_transit' || s === 'loaded' || s === 'ready_for_pickup') return 'bg-blue-500';
  if (s === 'rejected') return 'bg-red-500';
  if (s === 'pending' || s === 'preparing' || s === 'processing') return 'bg-yellow-500';
  return 'bg-gray-400';
});

function getStatusBadgeClass(status) {
  if (!status) return 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300';
  const s = String(status).toLowerCase();
  if (s === 'dispatched') return 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-200'; // Same as in_transit
  if (s === 'delivered' || s === 'completed') return 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-200';
  if (s === 'returned') return 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-200';
  if (s === 'in_transit' || s === 'loaded' || s === 'ready_for_pickup') return 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-200';
  if (s === 'rejected') return 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-200';
  if (s === 'pending' || s === 'preparing' || s === 'processing') return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-200';
  return 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300';
}

// Find the first in_transit status history item (not transfer)
const firstDispatchedId = computed(() => {
  for (let i = combinedTimeline.value.length - 1; i >= 0; i--) {
    const item = combinedTimeline.value[i];
    if (item.type === 'status' && item.data.status === 'in_transit' && item.data.remarks && item.data.remarks.toLowerCase().includes('dispatched')) {
      return item.timestamp + '-' + (item.data.updated_by || item.data.updatedBy?.name || '');
    }
  }
  return null;
});

function goBack() {
  if (window.history.length > 1) {
    window.history.back();
  } else {
    // fallback: go to dashboard or home if no history
    window.location.href = '/';
  }
}
</script>