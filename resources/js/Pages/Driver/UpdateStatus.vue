<template>
  <Head title="Update Status" />

  <EmployeeLayout>
    <template #header>
      <div class="flex justify-between items-center w-full px-4 md:px-6 lg:px-8">
        <!-- Left: Title & Subtitle -->
        <div>
          <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
            Update Status
          </h2>
          <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
            Manage your location, packages, and assignment status.
          </p>
        </div>
      </div>
    </template>

    <!-- MAIN CONTENT CONTAINER WITH PROPER PADDING -->
    <div class="px-4 md:px-6 lg:px-8 py-4">
      <!-- Flash Messages -->
      <div v-if="$page.props.flash?.success" class="mb-4 p-4 bg-green-50 text-green-800 rounded-lg dark:bg-green-900/20 dark:text-green-200 border border-green-200 dark:border-green-800">
        <div class="flex items-center">
          <svg class="h-5 w-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
          </svg>
          {{ $page.props.flash.success }}
        </div>
      </div>
      <div v-if="$page.props.flash?.error" class="mb-4 p-4 bg-red-50 text-red-800 rounded-lg dark:bg-red-900/20 dark:text-red-200 border border-red-200 dark:border-red-800">
        <div class="flex items-center">
          <svg class="h-5 w-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
          </svg>
          {{ $page.props.flash.error }}
        </div>
      </div>

      <!-- Current Status Card -->
      <div v-if="currentAssignment" class="mb-6">
        <div :class="[
          'rounded-lg border border-gray-200 dark:border-gray-700 shadow-sm p-4 sm:p-6',
          assignmentStatusDisplay?.color === 'green' ? 'bg-green-50 dark:bg-green-900/20' : 'bg-gray-50 dark:bg-gray-900/20'
        ]">
          <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
            <div class="flex items-start space-x-4">
              <div class="flex-shrink-0 mt-1">
                <div class="w-10 h-10 sm:w-12 sm:h-12 rounded-lg flex items-center justify-center bg-green-100 dark:bg-green-800">
                  <svg class="w-5 h-5 sm:w-6 sm:h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path :d="assignmentStatusDisplay?.iconPath" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"/>
                  </svg>
                </div>
              </div>
              <div class="flex-1 min-w-0">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 mb-2">
                  <h3 class="text-lg font-semibold text-green-800 dark:text-green-200">
                    {{ assignmentStatusDisplay?.label }}
                  </h3>
                </div>
                <p class="text-sm text-green-700 dark:text-green-300">
                  {{ assignmentStatusDisplay?.description }}
                </p>
                
                <!-- Status-specific additional info -->
                <div v-if="currentAssignment.current_status === 'cooldown' && currentAssignment.cooldown_ends_at" 
                     class="mt-3 p-3 bg-white dark:bg-gray-800 rounded border">
                  <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between text-sm gap-2">
                    <span class="font-medium text-green-800 dark:text-green-200">Cooldown Period</span>
                    <span class="font-mono text-sm text-green-800 dark:text-green-200">
                      Ends: {{ formatDateTime(currentAssignment.cooldown_ends_at) }}
                    </span>
                  </div>
                  <div v-if="currentAssignment.cooldown_finished" class="mt-2 text-xs text-green-600 dark:text-green-400 font-medium">
                    ✅ Ready for next action
                  </div>
                  <div v-if="currentAssignment.is_final_cooldown" class="mt-1 text-xs text-green-600 dark:text-green-400 font-medium">
                    ⭐ FINAL COOLDOWN - Complete to finish assignment
                  </div>
                </div>

                <div v-if="currentAssignment.current_status === 'returning'" class="mt-3 p-3 bg-white dark:bg-gray-800 rounded border">
                  <div class="flex items-center text-sm text-green-800 dark:text-green-200">
                    <svg class="w-4 h-4 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span>Proceed to <strong class="ml-1">{{ currentAssignment.home_region_name }}</strong> and confirm arrival</span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Action Cards Grid -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-6">
        <!-- Cooldown Actions -->
        <div v-if="showCooldownOptions" class="space-y-4">
          <!-- Skip Cooldown Card -->
          <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 shadow-sm p-4">
            <div class="flex items-center space-x-3 mb-3">
              <div class="w-10 h-10 bg-green-100 dark:bg-green-800 rounded-lg flex items-center justify-center flex-shrink-0">
                <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                </svg>
              </div>
              <div class="min-w-0 flex-1">
                <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100 truncate">Skip Cooldown</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 truncate">Option A: Immediate Backhaul Access</p>
              </div>
            </div>
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-4 line-clamp-2">
              Become immediately eligible for backhaul assignments and skip the remaining cooldown period.
            </p>
            <PrimaryButton @click="skipCooldown" class="w-full bg-green-600 hover:bg-green-700 focus:ring-green-500 text-sm py-2">
              <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
              </svg>
              Skip Cooldown
            </PrimaryButton>
          </div>

          <!-- Return Without Backhaul Card -->
          <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 shadow-sm p-4">
            <div class="flex items-center space-x-3 mb-3">
              <div class="w-10 h-10 bg-green-100 dark:bg-green-800 rounded-lg flex items-center justify-center flex-shrink-0">
                <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
              </div>
              <div class="min-w-0 flex-1">
                <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100 truncate">Return to Base</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 truncate">Option B: Direct Return</p>
              </div>
            </div>
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-4 line-clamp-2">
              Return directly to home base without waiting for backhaul assignments.
            </p>
            <PrimaryButton @click="openReturnModal" class="w-full bg-green-600 hover:bg-green-700 focus:ring-green-500 text-sm py-2">
              <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
              </svg>
              Initiate Return
            </PrimaryButton>
          </div>
        </div>

        <!-- Single Action Cards -->
        <div v-if="showConfirmArrivalButton" class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 shadow-sm p-4">
          <div class="flex items-center space-x-3 mb-3">
            <div class="w-10 h-10 bg-green-100 dark:bg-green-800 rounded-lg flex items-center justify-center flex-shrink-0">
              <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
              </svg>
            </div>
            <div class="min-w-0 flex-1">
              <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100 truncate">Confirm Arrival</h3>
              <p class="text-sm text-gray-500 dark:text-gray-400 truncate">You've Arrived at Home Region</p>
            </div>
          </div>
          <p class="text-sm text-gray-600 dark:text-gray-400 mb-4 line-clamp-2">
            Confirm your arrival at the home region to proceed with assignment completion.
          </p>
          <PrimaryButton @click="confirmArrivalAtHome" class="w-full bg-green-600 hover:bg-green-700 focus:ring-green-500 text-sm py-2">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19-7"/>
            </svg>
            Confirm Arrival
          </PrimaryButton>
        </div>

        <div v-if="showCompleteCooldownButton" class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 shadow-sm p-4">
          <div class="flex items-center space-x-3 mb-3">
            <div class="w-10 h-10 bg-green-100 dark:bg-green-800 rounded-lg flex items-center justify-center flex-shrink-0">
              <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
              </svg>
            </div>
            <div class="min-w-0 flex-1">
              <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100 truncate">Complete Assignment</h3>
              <p class="text-sm text-gray-500 dark:text-gray-400 truncate">Finalize Current Assignment</p>
            </div>
          </div>
          <p class="text-sm text-gray-600 dark:text-gray-400 mb-4 line-clamp-2">
            Your assignment is ready for completion. Finalize to become available for new assignments.
          </p>
          <PrimaryButton @click="completeCooldown" class="w-full bg-green-600 hover:bg-green-700 focus:ring-green-500 text-sm py-2">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            Complete
          </PrimaryButton>
        </div>

        <div v-if="showReturnInBackhaulMode" class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 shadow-sm p-4">
          <div class="flex items-center space-x-3 mb-3">
            <div class="w-10 h-10 bg-green-100 dark:bg-green-800 rounded-lg flex items-center justify-center flex-shrink-0">
              <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
              </svg>
            </div>
            <div class="min-w-0 flex-1">
              <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100 truncate">Return to Base</h3>
              <p class="text-sm text-gray-500 dark:text-gray-400 truncate">No Backhaul Available</p>
            </div>
          </div>
          <p class="text-sm text-gray-600 dark:text-gray-400 mb-4 line-clamp-2">
            No backhaul assignments are currently available. Return to your home region.
          </p>
          <PrimaryButton @click="openReturnModal" class="w-full bg-green-600 hover:bg-green-700 focus:ring-green-500 text-sm py-2">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            Return to Base
          </PrimaryButton>
        </div>
      </div>

    <!-- Location and Map Section -->
<div v-if="hasActiveDeliveries" class="space-y-6 xl:space-y-0 xl:grid xl:grid-cols-2 xl:gap-6 mb-6">
  <!-- Location Update Card -->
  <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 shadow-sm">
    <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700">
      <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100 flex items-center gap-2">
        <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
        </svg>
        Update Location
      </h3>
    </div>
    <div class="p-4">
      <!-- Current Location -->
      <div class="mb-4 p-3 bg-green-50 dark:bg-green-900/20 rounded border border-green-200 dark:border-green-800">
        <div class="text-xs text-green-600 dark:text-green-400 mb-1">Current Location</div>
        <div class="text-sm font-medium text-green-800 dark:text-green-200 truncate">{{ currentLocation }}</div>
      </div>

      <!-- Region Selection -->
      <div class="mb-4">
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Select New Location</label>
        
        <!-- Quick Access -->
        <div v-if="quickLocationOptions.length > 0" class="mb-4">
          <div class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-2">Quick Access</div>
          <div class="grid grid-cols-2 gap-2">
            <button
              v-for="location in quickLocationOptions"
              :key="location.id"
              @click="selectQuickLocation(location)"
              :class="[
                'p-3 rounded border text-left transition-all duration-200 text-sm min-w-0',
                driverRegionForm.region_id === location.id
                  ? 'border-green-500 bg-green-50 dark:bg-green-900/30 shadow-sm'
                  : 'border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 hover:border-green-300 dark:hover:border-green-600'
              ]"
            >
              <div class="flex items-center justify-between truncate">
                <span class="font-medium text-gray-900 dark:text-gray-100 truncate">{{ location.name }}</span>
                <div v-if="location.color_hex" class="w-2 h-2 rounded-full ml-2 flex-shrink-0" :style="{ backgroundColor: location.color_hex }"></div>
              </div>
              <div v-if="location.id === currentRegionId" class="text-xs text-green-600 dark:text-green-400 mt-1 truncate">Current</div>
            </button>
          </div>
        </div>

        <!-- All Regions -->
        <div>
          <div class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-2">All Regions</div>
          <div class="max-h-48 overflow-y-auto space-y-2 p-1">
            <button
              v-for="region in allRegions"
              :key="region.id"
              @click="selectLocation(region.id)"
              :class="[
                'w-full p-3 rounded border text-left transition-all duration-200 text-sm',
                driverRegionForm.region_id === region.id
                  ? 'border-green-500 bg-green-50 dark:bg-green-900/30 shadow-sm'
                  : 'border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-800 hover:border-green-300 dark:hover:border-green-600'
              ]"
            >
              <div class="flex items-center justify-between">
                <span class="font-medium text-gray-900 dark:text-gray-100 truncate">{{ region.name }}</span>
                <div v-if="region.color_hex" class="w-2 h-2 rounded-full ml-2 flex-shrink-0" :style="{ backgroundColor: region.color_hex }"></div>
              </div>
            </button>
          </div>
        </div>
      </div>

      <!-- Update Options - REPLACED CHECKBOXES WITH MESSAGE -->
      <div class="space-y-3 p-3 bg-green-50 dark:bg-green-900/20 rounded border border-green-200 dark:border-green-700">
        <div class="flex items-start gap-3">
          <svg class="w-4 h-4 text-green-600 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
          </svg>
          <div class="text-sm text-green-700 dark:text-green-300">
            <strong>Package Location Updates:</strong> Changing your location will automatically update all assigned packages to your new location. Only packages with "In Transit" status will be moved.
          </div>
        </div>
      </div>

      <!-- Update Button -->
      <PrimaryButton 
        @click="openLocationUpdateModal"
        :disabled="!driverRegionForm.region_id || driverRegionForm.region_id === currentRegionId"
        class="w-full mt-4 text-sm py-2.5 bg-green-600 hover:bg-green-700 focus:ring-green-500"
      >
        Update Location
      </PrimaryButton>
    </div>
  </div>

  <!-- Map Card - Simplified without LTooltip -->
  <div v-if="hasRouteData" class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 shadow-sm flex flex-col">

          <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700 flex-shrink-0">
            <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100 flex items-center gap-2">
              <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/>
              </svg>
              Route Map
            </h3>
          </div>
          <div class="p-4 flex-1 min-h-0">
            <!-- Map container with fixed height that expands -->
            <div class="h-64 sm:h-80 xl:h-full rounded border border-gray-200 dark:border-gray-700 relative" style="min-height: 300px;">
              <l-map 
                ref="mapRef" 
                :zoom="zoom" 
                :center="center" 
                :bounds="mapBounds"
                :options="{ zoomControl: true, dragging: true, tap: true }"
                @ready="onMapReady"
                class="w-full h-full absolute inset-0"
              >
                <l-tile-layer
                  url="https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png"
                  attribution='&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
                />
                
                <l-marker
                  v-for="(step, idx) in filteredRouteSteps"
                  :key="`route-step-${idx}-${step.region?.id || 'undefined'}`"
                  :lat-lng="[Number(step.region.latitude), Number(step.region.longitude)]"
                  :icon="getMarkerIcon(step, idx)"
                >
                  <l-popup>
                    <div class="text-sm font-medium text-gray-900">
                      {{ step.region?.name || 'Unknown' }}
                    </div>
                    <div v-if="step.region.id === currentRegionId" class="text-xs text-green-600 font-semibold">
                      🟢 Current Location
                    </div>
                    <div v-else-if="isDestination(step.region.id)" class="text-xs text-blue-600">
                      Destination
                    </div>
                    <div v-else class="text-xs text-gray-600">
                      Route Stop
                    </div>
                  </l-popup>
                </l-marker>
                
                <l-control-zoom position="bottomright"></l-control-zoom>
              </l-map>
            </div>
          </div>
        </div>
      </div>

      <!-- No Active Deliveries Message -->
<div v-else class="bg-yellow-50 dark:bg-yellow-900/20 rounded-lg border border-yellow-200 dark:border-yellow-800 p-6 text-center">
  <div class="flex flex-col items-center justify-center space-y-3">
    <svg class="w-12 h-12 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
    </svg>
    <h3 class="text-lg font-semibold text-yellow-800 dark:text-yellow-200">No Active Deliveries</h3>
    <p class="text-yellow-700 dark:text-yellow-300 max-w-md">
      Location updates are only available when you have active deliveries. 
      <span v-if="currentAssignment && currentAssignment.current_status === 'completed'" class="font-medium">
        Your assignment has been completed.
      </span>
      <span v-else-if="currentAssignment && currentAssignment.current_status === 'returning'" class="font-medium">
        You are currently returning to base.
      </span>
      <span v-else-if="currentAssignment && currentAssignment.current_status === 'cooldown'" class="font-medium">
        You are in cooldown period.
      </span>
      <span v-else class="font-medium">
        You don't have any active assignments.
      </span>
    </p>
  </div>
</div>

      <!-- Package Status Note -->
      <div class="bg-green-50 dark:bg-green-900/20 rounded-lg border border-green-200 dark:border-green-800 p-4">
        <div class="flex items-start space-x-3">
          <svg class="w-5 h-5 text-green-600 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.35 16.5c-.77.833.192 2.5 1.732 2.5z"/>
          </svg>
          <div class="text-sm text-green-800 dark:text-green-200">
            <strong>Package Status Updates:</strong> For detailed package status management, please visit your 
            <a :href="route('driver.assigned-deliveries')" class="underline font-medium hover:text-green-900 dark:hover:text-green-100">
              Assigned Deliveries page
            </a>.
          </div>
        </div>
      </div>
    </div>

    <!-- Modals (Keep existing modal code, just ensure they use green theme) -->
    <!-- Skip Cooldown Modal -->
    <Modal :show="showSkipCooldownModal" @close="showSkipCooldownModal = false">
      <div class="p-4 sm:p-6">
        <div class="flex items-center gap-3 mb-4">
          <div class="flex-shrink-0">
            <div class="w-10 h-10 sm:w-12 sm:h-12 bg-green-100 rounded-lg flex items-center justify-center">
              <svg class="w-5 h-5 sm:w-6 sm:h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
              </svg>
            </div>
          </div>
          <div class="min-w-0 flex-1">
            <h2 class="text-lg sm:text-xl font-semibold text-gray-900 dark:text-white truncate">Skip Cooldown Period</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1 truncate">Option A: Become immediately eligible for backhaul assignments</p>
          </div>
        </div>

        <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg p-4 mb-4">
          <div class="flex">
            <svg class="h-5 w-5 text-green-400 mr-2 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <div class="text-sm text-green-700 dark:text-green-300">
              <strong>Please confirm this action:</strong>
              <ul class="mt-2 ml-4 list-disc space-y-1">
                <li>You will skip the remaining cooldown period</li>
                <li>You will become immediately eligible for backhaul assignments</li>
                <li>This action cannot be undone</li>
                <li>System will start looking for backhaul opportunities</li>
              </ul>
            </div>
          </div>
        </div>

        <div class="flex flex-col sm:flex-row sm:justify-end sm:space-x-3 space-y-3 sm:space-y-0 mt-6">
          <SecondaryButton @click="showSkipCooldownModal = false" class="w-full sm:w-auto">
            Cancel
          </SecondaryButton>
          <PrimaryButton @click="confirmSkipCooldown" class="w-full sm:w-auto bg-green-600 hover:bg-green-700 focus:ring-green-500">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
            </svg>
            Skip Cooldown
          </PrimaryButton>
        </div>
      </div>
    </Modal>
           <!-- Skip Cooldown Modal -->

    <!-- Return Without Backhaul Modal -->
    <Modal :show="showReturnModal" @close="showReturnModal = false">
      <div class="p-4 sm:p-6">
        <div class="flex items-center gap-3 mb-4">
          <div class="flex-shrink-0">
            <div class="w-10 h-10 sm:w-12 sm:h-12 bg-green-100 rounded-lg flex items-center justify-center">
              <svg class="w-5 h-5 sm:w-6 sm:h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
              </svg>
            </div>
          </div>
          <div class="min-w-0 flex-1">
            <h2 class="text-lg sm:text-xl font-semibold text-gray-900 dark:text-white truncate">Return Without Backhaul</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1 truncate">Option B: Return directly to home base</p>
          </div>
        </div>

        <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg p-4 mb-4">
          <div class="flex">
            <svg class="h-5 w-5 text-green-400 mr-2 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <div class="text-sm text-green-700 dark:text-green-300">
              <strong>Return Process Overview:</strong>
              <ul class="mt-2 ml-4 list-disc space-y-1">
                <li>You will start returning to your home region immediately</li>
                <li>You cannot accept new backhaul assignments during return</li>
                <li>When you arrive at home region, confirm your arrival</li>
                <li>Final cooldown period will begin after arrival confirmation</li>
                <li>Assignment completion process will initiate</li>
              </ul>
            </div>
          </div>
        </div>

        <div class="mb-4">
          <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
            Reason for Returning (Optional)
          </label>
          <textarea
            v-model="returnForm.reason"
            rows="3"
            class="w-full rounded-lg border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white text-sm"
            placeholder="Provide a reason for returning without backhaul (optional)..."
          ></textarea>
        </div>

        <div class="flex flex-col sm:flex-row sm:justify-end sm:space-x-3 space-y-3 sm:space-y-0 mt-6">
          <SecondaryButton @click="showReturnModal = false" class="w-full sm:w-auto">
            Cancel
          </SecondaryButton>
          <PrimaryButton 
            @click="confirmReturnWithoutBackhaul"
            class="bg-green-600 hover:bg-green-700 focus:ring-green-500 w-full sm:w-auto"
          >
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            Confirm Return
          </PrimaryButton>
        </div>
      </div>
    </Modal>

    <!-- Location Update Modal -->
    <Modal :show="showLocationUpdateModal" @close="showLocationUpdateModal = false">
      <div class="p-4 sm:p-6">
        <div class="flex items-center gap-3 mb-4">
          <div class="flex-shrink-0">
            <div class="w-10 h-10 sm:w-12 sm:h-12 bg-green-100 rounded-lg flex items-center justify-center">
              <svg class="w-5 h-5 sm:w-6 sm:h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
              </svg>
            </div>
          </div>
          <div class="min-w-0 flex-1">
            <h2 class="text-lg sm:text-xl font-semibold text-gray-900 dark:text-white truncate">Confirm Location & Package Updates</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1 truncate">Review and confirm your location change</p>
          </div>
        </div>

        <div class="space-y-4 mb-4">
          <div class="flex items-start">
            <svg class="h-5 w-5 text-green-500 mt-0.5 mr-2 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
            </svg>
            <div class="min-w-0 flex-1">
              <p class="text-sm font-medium text-gray-700 dark:text-gray-300">Location Update</p>
              <p class="text-sm text-gray-600 dark:text-gray-400 truncate">
                Moving from <span class="font-semibold">{{ currentLocation }}</span> to <span class="font-semibold">{{ selectedRegionName }}</span>
              </p>
            </div>
          </div>

          <div class="flex items-start">
            <svg class="h-5 w-5 text-green-500 mt-0.5 mr-2 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
            </svg>
            <div class="min-w-0 flex-1">
              <p class="text-sm font-medium text-gray-700 dark:text-gray-300">Package Updates</p>
              <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                All assigned packages with "In Transit" status will be automatically moved to <span class="font-semibold">{{ selectedRegionName }}</span>
              </p>
            </div>
          </div>

          <div class="flex items-start">
            <svg class="h-5 w-5 text-green-500 mt-0.5 mr-2 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <div class="min-w-0 flex-1">
              <p class="text-sm font-medium text-gray-700 dark:text-gray-300">Package Status Management</p>
              <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                To update package delivery statuses, please visit your 
                <a :href="route('driver.assigned-deliveries')" class="text-green-600 hover:text-green-700 dark:text-green-400 dark:hover:text-green-300 font-medium underline">
                  Assigned Deliveries page
                </a> 
                after updating your location.
              </p>
            </div>
          </div>
        </div>

        <div class="flex flex-col sm:flex-row sm:justify-end sm:space-x-3 space-y-3 sm:space-y-0 mt-6">
          <SecondaryButton @click="showLocationUpdateModal = false" class="w-full sm:w-auto">
            Cancel
          </SecondaryButton>
          <PrimaryButton @click="updateDriverRegion" class="w-full sm:w-auto bg-green-600 hover:bg-green-700 focus:ring-green-500">
            Confirm Location Update
          </PrimaryButton>
        </div>
      </div>
    </Modal>

    <!-- Confirm Arrival Modal -->
    <Modal :show="showConfirmArrivalModal" @close="showConfirmArrivalModal = false">
      <div class="p-4 sm:p-6">
        <div class="flex items-center gap-3 mb-4">
          <div class="flex-shrink-0">
            <div class="w-10 h-10 sm:w-12 sm:h-12 bg-green-100 rounded-lg flex items-center justify-center">
              <svg class="w-5 h-5 sm:w-6 sm:h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
              </svg>
            </div>
          </div>
          <div class="min-w-0 flex-1">
            <h2 class="text-lg sm:text-xl font-semibold text-gray-900 dark:text-white truncate">Confirm Arrival at Home Base</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1 truncate">Option B Completion: You have arrived at your home region</p>
          </div>
        </div>

        <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg p-4 mb-4">
          <div class="flex">
            <svg class="h-5 w-5 text-green-400 mr-2 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <div class="text-sm text-green-700 dark:text-green-300">
              <strong>Next Steps After Arrival Confirmation:</strong>
              <ul class="mt-2 ml-4 list-disc space-y-1">
                <li>Your assignment will move to final cooldown period</li>
                <li>Final cooldown timer will start (30 minutes)</li>
                <li>Complete the cooldown to finish your assignment</li>
                <li>You will become available for new assignments</li>
                <li>Truck will be freed for other drivers</li>
              </ul>
            </div>
          </div>
        </div>

        <div class="flex flex-col sm:flex-row sm:justify-end sm:space-x-3 space-y-3 sm:space-y-0 mt-6">
          <SecondaryButton @click="showConfirmArrivalModal = false" class="w-full sm:w-auto">
            Cancel
          </SecondaryButton>
          <PrimaryButton @click="confirmArrival" class="bg-green-600 hover:bg-green-700 focus:ring-green-500 w-full sm:w-auto">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
            Confirm Arrival
          </PrimaryButton>
        </div>
      </div>
    </Modal>

    <!-- Complete Cooldown Modal -->
    <Modal :show="showCompleteCooldownModal" @close="showCompleteCooldownModal = false">
      <div class="p-4 sm:p-6">
        <div class="flex items-center gap-3 mb-4">
          <div class="flex-shrink-0">
            <div class="w-10 h-10 sm:w-12 sm:h-12 bg-green-100 rounded-lg flex items-center justify-center">
              <svg class="w-5 h-5 sm:w-6 sm:h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
              </svg>
            </div>
          </div>
          <div class="min-w-0 flex-1">
            <h2 class="text-lg sm:text-xl font-semibold text-gray-900 dark:text-white truncate">Complete Cooldown Period</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1 truncate">Finish your assignment and become available for new assignments</p>
          </div>
        </div>

        <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg p-4 mb-4">
          <div class="flex">
            <svg class="h-5 w-5 text-green-400 mr-2 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <div class="text-sm text-green-700 dark:text-green-300">
              <strong>Assignment Completion Process:</strong>
              <ul class="mt-2 ml-4 list-disc space-y-1">
                <li>Your current assignment will be marked as completed</li>
                <li>You will become available for new assignments</li>
                <li>Your truck will be freed for other drivers</li>
                <li>All assignment resources will be released</li>
                <li>Performance metrics will be recorded</li>
              </ul>
            </div>
          </div>
        </div>

        <div class="flex flex-col sm:flex-row sm:justify-end sm:space-x-3 space-y-3 sm:space-y-0 mt-6">
          <SecondaryButton @click="showCompleteCooldownModal = false" class="w-full sm:w-auto">
            Cancel
          </SecondaryButton>
          <PrimaryButton @click="completeCooldownAction" class="bg-green-600 hover:bg-green-700 focus:ring-green-500 w-full sm:w-auto">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            Complete
          </PrimaryButton>
        </div>
      </div>
    </Modal>
  </EmployeeLayout>
</template>

<script setup>
import { ref, computed, onMounted, nextTick } from 'vue'
import { Head, useForm, router } from '@inertiajs/vue3'
import EmployeeLayout from '@/Layouts/EmployeeLayout.vue'
import PrimaryButton from '@/Components/PrimaryButton.vue'
import SecondaryButton from '@/Components/SecondaryButton.vue'
import Modal from '@/Components/Modal.vue'

// Leaflet imports - simplified
import 'leaflet/dist/leaflet.css'
import { LMap, LTileLayer, LMarker, LPopup, LControlZoom } from '@vue-leaflet/vue-leaflet'
import L from 'leaflet'

// Fix for default markers
delete L.Icon.Default.prototype._getIconUrl
L.Icon.Default.mergeOptions({
  iconRetinaUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.7.1/images/marker-icon-2x.png',
  iconUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.7.1/images/marker-icon.png',
  shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.7.1/images/marker-shadow.png',
})

const props = defineProps({
  packages: Array,
  regions: Array,
  currentAssignment: Object,
  routeData: Object,
  currentLocation: String,
  currentRegionId: Number,
  statusOptions: Object,
  backhaulAvailable: Boolean,
  allPackagesFinal: Boolean
})

// Refs
const mapRef = ref(null)
const zoom = ref(10)
const center = ref([14.5995, 120.9842])
const mapBounds = ref(null)
const showSkipCooldownModal = ref(false)
const showReturnModal = ref(false)
const showConfirmArrivalModal = ref(false)
const showCompleteCooldownModal = ref(false)
const showLocationUpdateModal = ref(false)

// Forms - simplified without checkboxes
const driverRegionForm = useForm({
  region_id: null
})

const hasActiveDeliveries = computed(() => {
  return props.currentAssignment && 
         props.currentAssignment.current_status !== 'completed' &&
         props.currentAssignment.current_status !== 'cancelled' &&
         props.currentAssignment.current_status !== 'returning' &&
         props.currentAssignment.current_status !== 'cooldown'
})

const returnForm = useForm({
  reason: ''
})

// Computed properties
const hasRouteData = computed(() => {
  return props.routeData?.route?.length > 0
})

const filteredRouteSteps = computed(() => {
  if (!hasRouteData.value) return []
  return props.routeData.route.filter(step => step.region && step.region.latitude && step.region.longitude)
})

const allRegions = computed(() => {
  return props.regions || []
})

const quickLocationOptions = computed(() => {
  const options = []
  
  // Add current location
  if (props.currentRegionId) {
    const currentRegion = props.regions.find(r => r.id === props.currentRegionId)
    if (currentRegion) {
      options.push(currentRegion)
    }
  }
  
  // Add route regions
  if (hasRouteData.value) {
    props.routeData.route.forEach(step => {
      if (step.region && !options.some(opt => opt.id === step.region.id)) {
        options.push(step.region)
      }
    })
  }
  
  // Add more regions if needed to reach 6
  if (options.length < 6) {
    const additionalRegions = props.regions
      .filter(region => !options.some(opt => opt.id === region.id))
      .slice(0, 6 - options.length)
    options.push(...additionalRegions)
  }
  
  return options.slice(0, 6)
})

const selectedRegionName = computed(() => {
  if (!driverRegionForm.region_id) return null
  const region = props.regions.find(r => r.id === driverRegionForm.region_id)
  return region ? region.name : null
})

// Assignment status display - simplified to always use green theme
const assignmentStatusDisplay = computed(() => {
  if (!props.currentAssignment) return null
  
  const status = props.currentAssignment.current_status
  const isFinalCooldown = props.currentAssignment.is_final_cooldown
  
  const baseDisplay = {
    color: 'green',
    iconBg: 'bg-green-100 dark:bg-green-800',
    iconColor: 'text-green-600 dark:text-green-400',
    textColor: 'text-green-800 dark:text-green-200',
    descriptionColor: 'text-green-700 dark:text-green-300'
  }
  
  switch (status) {
    case 'active':
      return {
        ...baseDisplay,
        label: 'Active Delivery',
        description: 'You are actively delivering packages to destination regions. Monitor your route and update locations as you progress.',
        iconPath: 'M13 10V3L4 14h7v7l9-11h-7z'
      }
    case 'in_transit':
      return {
        ...baseDisplay,
        label: 'In Transit',
        description: 'Currently transporting packages between regions. Ensure packages are secure and track your route progress.',
        iconPath: 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z'
      }
    case 'cooldown':
      if (isFinalCooldown) {
        return {
          ...baseDisplay,
          label: 'Final Cooldown Period',
          description: 'Complete cooldown to finish your assignment. You are almost done!',
          iconPath: 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z'
        }
      }
      return {
        ...baseDisplay,
        label: 'Cooldown Period',
        description: 'Waiting period before backhaul eligibility. Consider your options for backhaul assignments or returning to base.',
        iconPath: 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z'
      }
    case 'backhaul_eligible':
      return {
        ...baseDisplay,
        label: 'Backhaul Eligible',
        description: 'You can accept backhaul assignments or return to home base. Check for available backhaul opportunities.',
        iconPath: 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'
      }
    case 'returning':
      return {
        ...baseDisplay,
        label: 'Returning to Home Base',
        description: 'You are on your way back to your home region. Confirm arrival when you reach your destination.',
        iconPath: 'M14 10h4.764a2 2 0 011.789 2.894l-3.5 7A2 2 0 0115.263 21h-4.017c-.163 0-.326-.02-.485-.06L7 20m7-10V5a2 2 0 00-2-2h-.095c-.5 0-.905.405-.905.905 0 .714-.211 1.412-.608 2.006L7 11v9m7-10h-2M7 20H5a2 2 0 01-2-2v-6a2 2 0 012-2h2.5'
      }
    case 'completed':
      return {
        ...baseDisplay,
        label: 'Assignment Completed',
        description: 'Your assignment has been completed successfully. You are now available for new assignments.',
        iconPath: 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'
      }
    default:
      return {
        ...baseDisplay,
        label: 'Unknown Status',
        description: 'Current assignment status is being determined. Please wait or contact support if this persists.',
        iconPath: 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z'
      }
  }
})

const showCooldownOptions = computed(() => {
  return props.currentAssignment?.current_status === 'cooldown' && 
         !props.currentAssignment.is_final_cooldown
})

const showConfirmArrivalButton = computed(() => {
  return props.currentAssignment?.current_status === 'returning'
})

const showCompleteCooldownButton = computed(() => {
  return props.currentAssignment?.current_status === 'cooldown' && 
         props.currentAssignment.is_final_cooldown
})

const showReturnInBackhaulMode = computed(() => {
  return props.currentAssignment?.current_status === 'backhaul_eligible'
})

// Methods
const formatDateTime = (dateString) => {
  if (!dateString) return 'N/A'
  return new Date(dateString).toLocaleString()
}

const onMapReady = () => {
  // Add a small delay to ensure the map is fully initialized
  setTimeout(() => {
    fitMapToBounds()
  }, 100)
}

const fitMapToBounds = () => {
  if (!mapRef.value || !mapRef.value.leafletObject || !filteredRouteSteps.value.length) return
  
  nextTick(() => {
    const map = mapRef.value.leafletObject
    if (!map) return
    
    const bounds = L.latLngBounds(filteredRouteSteps.value.map(step => 
      [Number(step.region.latitude), Number(step.region.longitude)]
    ))
    
    mapBounds.value = bounds
    map.fitBounds(bounds, { padding: [20, 20] })
  })
}

const getMarkerIcon = (step, idx) => {
  const isCurrent = step.region.id === props.currentRegionId
  const regionColor = isCurrent ? '#10B981' : (step.region.color_hex || '#6B7280')
  
  return L.divIcon({
    html: `
      <div class="relative">
        <div class="w-8 h-8 rounded-full border-2 border-white shadow-lg flex items-center justify-center" style="background-color: ${regionColor}">
          ${isCurrent ? `
            <div class="w-3 h-3 bg-white rounded-full"></div>
          ` : ''}
        </div>
        ${isCurrent ? `
          <div class="absolute -top-1 -right-1 w-4 h-4 rounded-full border-2 border-white animate-ping" style="background-color: ${regionColor}"></div>
        ` : ''}
      </div>
    `,
    className: 'custom-marker',
    iconSize: [32, 32],
    iconAnchor: [16, 16]
  })
}

const isDestination = (regionId) => {
  return props.currentAssignment?.region_id === regionId
}

const selectQuickLocation = (location) => {
  driverRegionForm.region_id = location.id
}

const selectLocation = (regionId) => {
  driverRegionForm.region_id = regionId
}

const openLocationUpdateModal = () => {
  // Temporary: Remove condition to test if modal works
  showLocationUpdateModal.value = true
  
  // Original condition (comment out for testing):
  // if (!driverRegionForm.region_id || driverRegionForm.region_id === props.currentRegionId) return
  // showLocationUpdateModal.value = true
}

const updateDriverRegion = () => {
  // Add the package update parameters that were removed from the form
  const formData = {
    ...driverRegionForm.data(),
    update_packages: true,
    only_in_transit: true
  }
  
  driverRegionForm.transform(() => formData).post(route('driver.update-region'), {
    preserveScroll: true,
    onSuccess: () => {
      showLocationUpdateModal.value = false
    }
  })
}

const skipCooldown = () => {
  showSkipCooldownModal.value = true
}

const confirmSkipCooldown = () => {
  router.post(route('driver.skip-cooldown'), {}, {
    preserveScroll: true,
    onSuccess: () => {
      showSkipCooldownModal.value = false
    }
  })
}

const openReturnModal = () => {
  showReturnModal.value = true
}

const confirmReturnWithoutBackhaul = () => {
  returnForm.post(route('driver.return-without-backhaul'), {
    preserveScroll: true,
    onSuccess: () => {
      showReturnModal.value = false
      returnForm.reset()
    }
  })
}

const confirmArrivalAtHome = () => {
  showConfirmArrivalModal.value = true
}

const confirmArrival = () => {
  router.post(route('driver.confirm-arrival-home'), {}, {
    preserveScroll: true,
    onSuccess: () => {
      showConfirmArrivalModal.value = false
    }
  })
}

const completeCooldown = () => {
  showCompleteCooldownModal.value = true
}

const completeCooldownAction = () => {
  router.post(route('driver.complete-cooldown'), {}, {
    preserveScroll: true,
    onSuccess: () => {
      showCompleteCooldownModal.value = false
    }
  })
}

onMounted(() => {
  if (props.currentRegionId) {
    driverRegionForm.region_id = props.currentRegionId
  }
  
  nextTick(() => {
    fitMapToBounds()
  })
})
</script>

<style>
.leaflet-container {
  width: 100% !important;
  height: 100% !important;
  border-radius: 0.375rem;
}

.map-container {
  position: relative;
  width: 100%;
  height: 100%;
}

.absolute.inset-0 {
  position: absolute;
  top: 0;
  right: 0;
  bottom: 0;
  left: 0;
}

.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

@media (max-width: 768px) {
  .grid-cols-2 {
    grid-template-columns: 1fr;
  }
}
</style>