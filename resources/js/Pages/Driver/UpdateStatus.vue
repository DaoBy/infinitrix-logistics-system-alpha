<template>
  <Head title="Update Package Status" />

  <EmployeeLayout>
    <template #header>
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
        <div>
          <h2 class="text-2xl font-bold leading-tight text-gray-900 dark:text-gray-100">
            Update Package Status
          </h2>
          <div class="flex items-center mt-1 text-sm text-gray-600 dark:text-gray-300">
            <svg class="h-4 w-4 mr-1 text-blue-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M12 11c1.104 0 2-.896 2-2s-.896-2-2-2-2 .896-2 2 .896 2 2 2zm0 0c-4 0-7 2-7 4v2h14v-2c0-2-3-4-7-4z"/>
            </svg>
            <span>Current Location: <span class="font-semibold">{{ currentLocation || 'Unknown' }}</span></span>
            <span v-if="currentRegionId" class="ml-2 px-2 py-1 text-xs bg-blue-100 text-blue-800 rounded-full">
              Region ID: {{ currentRegionId }}
            </span>
          </div>
          
          <!-- Assignment Status Display -->
          <div v-if="currentAssignment" class="mt-3 space-y-2">
            <!-- Dynamic status display based on current status -->
            <div :class="[
                'p-3 rounded-lg border',
                assignmentStatusDisplay?.color === 'blue' ? 'bg-blue-50 border-blue-200' :
                assignmentStatusDisplay?.color === 'purple' ? 'bg-purple-50 border-purple-200' :
                assignmentStatusDisplay?.color === 'orange' ? 'bg-orange-50 border-orange-200' :
                assignmentStatusDisplay?.color === 'yellow' ? 'bg-yellow-50 border-yellow-200' :
                'bg-gray-50 border-gray-200'
            ]">
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <svg class="w-5 h-5 mr-2" :class="`text-${assignmentStatusDisplay?.color}-600`" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <div>
                            <p class="text-sm font-medium" :class="`text-${assignmentStatusDisplay?.color}-800`">
                                {{ assignmentStatusDisplay?.label }}
                            </p>
                            <p class="text-xs" :class="`text-${assignmentStatusDisplay?.color}-600`">
                                {{ assignmentStatusDisplay?.description }}
                            </p>
                            <!-- Show cooldown timer if in cooldown -->
                            <p v-if="currentAssignment.current_status === 'cooldown' && currentAssignment.cooldown_ends_at" 
                               class="text-xs mt-1" :class="`text-${assignmentStatusDisplay?.color}-600`">
                                Cooldown ends: {{ formatDateTime(currentAssignment.cooldown_ends_at) }}
                                <span v-if="currentAssignment.cooldown_finished" class="ml-2 text-green-600 font-semibold">
                                  (Ready for next action)
                                </span>
                            </p>
                            <!-- Show final cooldown indicator -->
                            <p v-if="currentAssignment.current_status === 'cooldown' && currentAssignment.is_final_cooldown" 
                               class="text-xs mt-1 text-red-600 font-semibold">
                                ⭐ FINAL COOLDOWN - Complete to finish assignment
                            </p>
                        </div>
                    </div>
                    <span class="px-2 py-1 text-xs rounded-full" 
                          :class="`bg-${assignmentStatusDisplay?.color}-100 text-${assignmentStatusDisplay?.color}-800`">
                        {{ currentAssignment.home_region_name || 'Unknown Region' }}
                    </span>
                </div>
            </div>
          </div>
        </div>
        <SecondaryButton
          @click="router.visit(route('driver.dashboard'))"
          class="ml-0 sm:ml-4 mt-3 sm:mt-0"
        >
          <svg class="h-5 w-5 mr-2" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3 10h2l.4 2M7 13h10l4-8H5.4" />
          </svg>
          Back to Dashboard
        </SecondaryButton>
      </div>
    </template>

    <!-- Flash Messages -->
    <div v-if="$page.props.flash?.success" class="mb-4 p-4 bg-green-50 text-green-800 rounded-lg dark:bg-green-900/20 dark:text-green-200">
      {{ $page.props.flash.success }}
    </div>
    <div v-if="$page.props.flash?.error" class="mb-4 p-4 bg-red-50 text-red-800 rounded-lg dark:bg-red-900/20 dark:text-red-200">
      {{ $page.props.flash.error }}
    </div>

    <!-- Status Validation Messages -->
    <div v-if="currentAssignment?.current_status === 'returning'" class="mb-4 p-4 bg-blue-50 text-blue-800 rounded-lg dark:bg-blue-900/20 dark:text-blue-200">
        <div class="flex items-center">
            <svg class="h-5 w-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <div>
                <p class="font-medium">You are returning to your home region</p>
                <p class="text-sm">Please proceed to <strong>{{ currentAssignment.home_region_name || 'Home Region' }}</strong> and confirm arrival when you arrive.</p>
            </div>
        </div>
    </div>

  <!-- Cooldown Options Section - Show both Option A and Option B during first cooldown -->
<div v-if="showCooldownOptions" class="mb-8 space-y-4">
  <!-- Skip Cooldown Option - OPTION A -->
  <div class="p-4 bg-blue-50 rounded-lg border border-blue-200">
    <div class="flex items-center gap-2 mb-2">
      <svg class="h-5 w-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
      </svg>
      <h3 class="text-base font-semibold text-blue-800">Option A: Skip Cooldown Timer</h3>
    </div>
    <p class="text-sm text-blue-700 mb-3">
      Become immediately eligible for backhaul assignments. This will skip the remaining cooldown period.
    </p>
   <PrimaryButton @click="skipCooldown" class="w-full">
  Skip Cooldown & Become Backhaul Eligible
</PrimaryButton>
  </div>

   <!-- Return Without Backhaul Option - OPTION B -->
  <div class="p-4 bg-orange-50 rounded-lg border border-orange-200">
    <div class="flex items-center gap-2 mb-2">
      <svg class="h-5 w-5 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
      </svg>
      <h3 class="text-base font-semibold text-orange-800">Option B: Return Without Backhaul</h3>
    </div>
    <p class="text-sm text-orange-700 mb-3">
      Return directly to home base without waiting for backhaul assignments. You'll need to confirm arrival when you reach your home region.
    </p>
    
    <div class="space-y-3">
      <textarea
        v-model="returnForm.reason"
        rows="2"
        placeholder="Reason for returning without backhaul..."
        class="w-full rounded border-orange-300 shadow-sm focus:border-orange-500 focus:ring-orange-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
      ></textarea>
     <!-- In the Return Without Backhaul section -->
<PrimaryButton 
  @click="returnWithoutBackhaul"
  :disabled="!returnForm.reason"
  class="w-full bg-orange-600 hover:bg-orange-700 focus:ring-orange-500"
>
  Confirm Return Without Backhaul
</PrimaryButton>
    </div>
  </div>
</div>
    <!-- Confirm Arrival Button (when returning) -->
    <div v-if="showConfirmArrivalButton" class="mb-8 p-4 bg-green-50 rounded-lg border border-green-200">
      <div class="flex items-center gap-2 mb-2">
        <svg class="h-5 w-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
        </svg>
        <h3 class="text-base font-semibold text-green-800">Confirm Arrival at Home Base</h3>
      </div>
      <p class="text-sm text-green-700 mb-3">
        You are returning to home base. Click below when you arrive at <strong>{{ currentAssignment.home_region_name || 'Home Region' }}</strong>.
      </p>
  <PrimaryButton @click="confirmArrivalAtHome" class="w-full bg-green-600 hover:bg-green-700 focus:ring-green-500">
  Confirm Arrival at Home Base
</PrimaryButton>
    </div>

    <!-- Complete Cooldown Button -->
    <div v-if="showCompleteCooldownButton" class="mb-8 p-4 bg-green-50 rounded-lg border border-green-200">
      <div class="flex items-center gap-2 mb-2">
        <svg class="h-5 w-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
        </svg>
        <h3 class="text-base font-semibold text-green-800">Complete Cooldown Period</h3>
      </div>
      <p class="text-sm text-green-700 mb-3">
        Your cooldown period is complete. Click below to finish your assignment and become available for new assignments.
      </p>
   <PrimaryButton @click="completeCooldown" class="w-full bg-green-600 hover:bg-green-700 focus:ring-green-500">
  Complete Cooldown & Finish Assignment
</PrimaryButton>
    </div>

    <div class="py-12">
      <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
        <div class="overflow-hidden bg-white shadow-sm sm:rounded-xl dark:bg-gray-800">
          <div class="p-6 text-gray-900 dark:text-gray-100">
            <!-- Location Status Card -->
            <div class="mb-8 p-4 bg-blue-50 rounded-lg border border-blue-200 dark:bg-blue-900/20 dark:border-blue-800">
              <div class="flex items-center gap-2 mb-2">
                <svg class="h-5 w-5 text-blue-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                <h3 class="text-base font-semibold text-blue-800 dark:text-blue-200">
                  Current Location Status
                </h3>
              </div>
              <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                <div class="bg-white dark:bg-gray-700 p-3 rounded-lg border">
                  <div class="font-medium text-gray-700 dark:text-gray-300">Driver Location</div>
                  <div class="text-lg font-bold text-blue-600 dark:text-blue-400">{{ currentLocation || 'Unknown' }}</div>
                  <div class="text-xs text-gray-500 dark:text-gray-400">Region ID: {{ currentRegionId || 'N/A' }}</div>
                </div>
                <div class="bg-white dark:bg-gray-700 p-3 rounded-lg border">
                  <div class="font-medium text-gray-700 dark:text-gray-300">Active Packages</div>
                  <div class="text-lg font-bold text-green-600 dark:text-green-400">{{ packages.length }}</div>
                  <div class="text-xs text-gray-500 dark:text-gray-400">In your care</div>
                </div>
                <div class="bg-white dark:bg-gray-700 p-3 rounded-lg border">
                  <div class="font-medium text-gray-700 dark:text-gray-300">Assignment Status</div>
                  <div class="text-lg font-bold text-purple-600 dark:text-purple-400">
                    {{ currentAssignment?.current_status ? formatStatus(currentAssignment.current_status) : 'No Assignment' }}
                  </div>
                  <div class="text-xs text-gray-500 dark:text-gray-400">Current mode</div>
                </div>
              </div>
            </div>

            <!-- Delivery Route Map Section -->
            <div v-if="hasRouteData" class="mb-8 p-4 bg-white rounded-lg shadow-sm dark:bg-gray-800 border border-gray-200 dark:border-gray-700">
              <div class="flex items-center gap-2 mb-4">
                <svg class="h-5 w-5 text-blue-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" />
                </svg>
                <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200">
                  Delivery Route Map
                </h3>
              </div>
              
              <div class="h-96 rounded-md overflow-hidden relative z-0 border border-gray-200 dark:border-gray-700">
                <l-map 
                  ref="mapRef" 
                  :zoom="zoom" 
                  :center="center" 
                  :bounds="mapBounds"
                  :options="{ zoomControl: false }"
                  @ready="onMapReady"
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
                      <div class="font-medium text-gray-900">
                        {{ step.region?.name || 'Unknown' }}
                      </div>
                      <div v-if="isDestination(step.region.id)" class="text-xs text-green-600">
                        Delivery Destination
                      </div>
                      <div v-else-if="step.region.id === currentRegionId" class="text-xs text-blue-600">
                        Current Location
                      </div>
                      <div v-else class="text-xs text-gray-600">
                        Stop {{ idx + 1 }}
                        <span v-if="step.estimated_minutes > 0">
                          • ETA: {{ step.estimated_minutes }} mins
                        </span>
                      </div>
                    </l-popup>
                  </l-marker>
                  
                  <l-control-zoom position="topright"></l-control-zoom>
                </l-map>
              </div>
              
              <div class="mt-4 grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="bg-blue-50 dark:bg-blue-900/20 p-3 rounded-lg">
                  <div class="flex items-center">
                    <div class="w-3 h-3 rounded-full bg-blue-500 mr-2"></div>
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Route Stops</span>
                  </div>
                  <div class="mt-1 text-xs text-gray-600 dark:text-gray-400">
                    {{ routeData.route.length }} stops total
                  </div>
                </div>
                
                <div class="bg-green-50 dark:bg-green-900/20 p-3 rounded-lg">
                  <div class="flex items-center">
                    <svg class="w-4 h-4 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Estimated Time</span>
                  </div>
                  <div class="mt-1 text-xs text-gray-600 dark:text-gray-400">
                    Total: {{ totalRouteTime }} minutes
                  </div>
                </div>
                
                <div class="bg-purple-50 dark:bg-purple-900/20 p-3 rounded-lg">
                  <div class="flex items-center">
                    <svg class="w-4 h-4 text-purple-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Next Stop</span>
                  </div>
                  <div class="mt-1 text-xs text-gray-600 dark:text-gray-400">
                    {{ routeData.route[1]?.region?.name || 'None' }} 
                    <span v-if="routeData.route[1]?.estimated_minutes">({{ routeData.route[1].estimated_minutes }} mins)</span>
                  </div>
                </div>
              </div>
            </div>

            <!-- No Route Data Message -->
            <div v-else class="mb-8 p-4 bg-gray-50 rounded-lg border border-gray-200 dark:bg-gray-700 dark:border-gray-600">
              <div class="flex items-center gap-2">
                <svg class="h-5 w-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <p class="text-gray-700 dark:text-gray-300">
                  No active route data available. Update your location or check for assigned deliveries.
                </p>
              </div>
            </div>

            <!-- Enhanced Location Update Section -->
            <div class="mb-8 p-4 bg-blue-50 rounded-lg dark:bg-blue-900/20 border border-blue-100 dark:border-blue-800">
              <div class="flex items-center gap-2 mb-4">
                <svg class="h-5 w-5 text-blue-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                <h3 class="text-base font-semibold text-blue-800 dark:text-blue-200">
                  Update Your Location & Package Status
                </h3>
              </div>
              
              <div class="mb-4 p-3 bg-blue-100 rounded-lg dark:bg-blue-800/30">
                <div class="flex items-start">
                  <svg class="h-5 w-5 text-blue-600 mt-0.5 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                  </svg>
                  <div class="text-sm text-blue-700 dark:text-blue-300">
                    <p class="font-medium">Automatic Package Updates</p>
                    <p class="mt-1">When you update your location, the system will automatically:</p>
                    <ul class="mt-1 ml-4 list-disc space-y-1">
                      <li>Update your current location and all assigned packages</li>
                      <li>Auto-deliver packages that reach their destination</li>
                      <li>Auto-return packages that reach the sender's region</li>
                      <li>Update package status to "In Transit" for movement between regions</li>
                      <li>Check for assignment completion and cooldown transitions</li>
                    </ul>
                  </div>
                </div>
              </div>
              
              <!-- Route Visualization -->
              <div v-if="hasRouteData" class="mb-4">
                <div class="flex items-center justify-center overflow-x-auto py-2 px-4">
                  <div v-for="(step, index) in routeData.route" :key="index" class="flex items-center">
                    <div class="flex flex-col items-center">
                      <div class="relative">
                        <div :class="[
                          'w-10 h-10 rounded-full flex items-center justify-center border-2',
                          step.region.id === currentRegionId ? 'bg-blue-100 border-blue-500' : 
                          (index < routeData.route.findIndex(r => r.region.id === currentRegionId) ? 'bg-gray-100 border-gray-300' : 'bg-white border-gray-300 dark:bg-gray-700 dark:border-gray-500'),
                          driverRegionForm.region_id === step.region.id ? 'ring-2 ring-blue-400' : ''
                        ]">
                          <span class="text-sm font-medium">
                            {{ index + 1 }}
                          </span>
                        </div>
                        
                        <div v-if="index < routeData.route.length - 1" class="absolute top-1/2 right-0 transform translate-x-1/2 -translate-y-1/2">
                          <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                          </svg>
                        </div>
                      </div>
                      <span class="mt-2 text-xs font-medium text-center max-w-[80px] truncate">
                        {{ step.region.name }}
                      </span>
                    </div>
                  </div>
                </div>
              </div>
              
              <!-- Current Location Selector - Filtered to Assignment Regions -->
              <div class="relative">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                  Select Your Current Location
                </label>
                <select
                  v-model="driverRegionForm.region_id"
                  class="block w-full px-4 py-3 pr-10 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md shadow-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                >
                  <option value="">Select your current location</option>
                  
                  <!-- Route Regions Group -->
                  <optgroup v-if="hasRouteData" label="Your Route Regions">
                    <option 
                      v-for="step in routeData.route" 
                      :key="step.region.id" 
                      :value="step.region.id"
                      :disabled="step.region.id === currentRegionId"
                    >
                      {{ step.region.name }} 
                      <template v-if="step.region.id === currentRegionId">(Current)</template>
                    </option>
                  </optgroup>
                  
                  <!-- Assignment Regions Group (Pickup and Dropoff only) -->
                  <optgroup label="Assignment Regions">
                    <option 
                      v-for="region in assignmentRegions" 
                      :key="region.id" 
                      :value="region.id"
                      :disabled="region.id === currentRegionId"
                    >
                      {{ region.name }}
                      <template v-if="region.id === currentRegionId">(Current)</template>
                      <template v-if="region.type === 'pickup'"> - Pickup</template>
                      <template v-if="region.type === 'dropoff'"> - Dropoff</template>
                    </option>
                  </optgroup>
                </select>
                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-700 dark:text-gray-300">
                  <ChevronDownIcon class="h-5 w-5" />
                </div>
              </div>
              
              <!-- Package Update Options -->
              <div class="mt-4 space-y-3">
                <div class="flex items-center">
                  <Checkbox
                    v-model:checked="driverRegionForm.update_packages"
                    class="mr-2"
                  />
                  <label class="text-sm text-gray-700 dark:text-gray-300">
                    Update package locations and statuses automatically
                  </label>
                </div>
                
                <div v-if="driverRegionForm.update_packages" class="ml-6">
                  <div class="flex items-center">
                    <Checkbox
                      v-model:checked="driverRegionForm.only_in_transit"
                      class="mr-2"
                    />
                    <label class="text-sm text-gray-700 dark:text-gray-300">
                      Only update packages that are "In Transit"
                    </label>
                  </div>
                  <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                    When checked, only packages with "In Transit" status will be moved to the new location.
                    Uncheck to update all assigned packages regardless of status.
                  </p>
                </div>
              </div>
              
              <div class="mt-4 flex justify-end">
                <PrimaryButton 
                  @click="openLocationUpdateModal"
                  :disabled="!driverRegionForm.region_id"
                  class="w-full sm:w-auto"
                >
                  Update Location & Packages
                </PrimaryButton>
              </div>
            </div>

            <!-- Status Update Form -->
            <div v-if="selectedPackages.length > 0" class="space-y-6">
              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                  Update Status
                </label>
                <select
                  v-model="form.status"
                  class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                >
                  <option value="">Select Status</option>
                  <option value="loaded">Loaded</option>
                  <option value="in_transit">In Transit</option>
                  <option value="delivered">Delivered</option>
                  <option value="returned">Returned to Sender Branch</option>
                </select>
                <InputError :message="form.errors.status" class="mt-1" />
                <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                  Choose the new status for the selected packages.
                </div>
              </div>

              <!-- Region selection for status changes that involve location -->
              <div v-if="form.status && form.status !== 'loaded'">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                  Current Region
                </label>
                <select
                  v-model="form.region_id"
                  class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                >
                  <option value="">Select Current Region</option>
                  <option 
                    v-for="region in assignmentRegions" 
                    :key="region.id" 
                    :value="region.id"
                  >
                    {{ region.name }}
                    <template v-if="region.type === 'pickup'"> - Pickup</template>
                    <template v-if="region.type === 'dropoff'"> - Dropoff</template>
                  </option>
                </select>
                <InputError :message="form.errors.region_id" class="mt-1" />
                <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                  Select the region where you're updating the package status.
                  <span v-if="form.status === 'delivered'" class="text-green-600 font-semibold">
                    Packages at their destination will be auto-delivered.
                  </span>
                  <span v-if="form.status === 'returned'" class="text-red-600 font-semibold">
                    Packages at sender region will be auto-returned.
                  </span>
                </div>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                  Remarks
                </label>
                <textarea
                  v-model="form.remarks"
                  rows="3"
                  class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                  placeholder="Optional notes about this status update"
                ></textarea>
                <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                  Add any additional notes for this update (optional).
                </div>
              </div>

              <div class="flex justify-end">
                <PrimaryButton @click="openConfirmationModal">
                  Update Status
                </PrimaryButton>
              </div>
            </div>

            <!-- Grouped Packages Section -->
            <div class="mt-10 space-y-6">
              <div
                v-for="(group, index) in groupedPackages"
                :key="index"
                class="border rounded-lg mb-6"
              >
                <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 flex justify-between items-center">
                  <div>
                    <h3 class="font-medium text-gray-900 dark:text-white">
                      To: {{ group.destination.name }}
                    </h3>
                    <div class="flex items-center mt-1 text-xs text-gray-500 dark:text-gray-300">
                      <span class="inline-flex items-center">
                        <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        Current: {{ group.currentRegion }}
                      </span>
                      <span class="mx-2">•</span>
                      <span>{{ group.packages.length }} package(s)</span>
                    </div>
                  </div>
                </div>
                <div class="divide-y divide-gray-200 dark:divide-gray-700">
                  <div
                    v-for="pkg in group.packages"
                    :key="pkg.id"
                    class="flex items-center justify-between p-4 hover:bg-blue-50 dark:hover:bg-blue-900/30 cursor-pointer transition"
                    :class="{
                      'border-blue-200 bg-blue-50 dark:bg-blue-900/30': selectedPackages.includes(pkg.id),
                      'border-gray-200 dark:border-gray-700': !selectedPackages.includes(pkg.id)
                    }"
                    @click="togglePackageSelection(pkg.id)"
                  >
                    <div class="flex items-center space-x-4">
                      <Checkbox
                        v-model:checked="selectedPackages"
                        :value="pkg.id"
                        :checked="selectedPackages.includes(pkg.id)"
                        @click.stop
                      />
                      <div>
                        <div class="font-medium text-gray-900 dark:text-white">
                          {{ pkg.item_code }}
                        </div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">
                          {{ pkg.item_name }}
                        </div>
                        <div class="text-xs text-gray-400 dark:text-gray-500 mt-1">
                          Status: <span class="font-semibold">{{ statusOptions[pkg.status] || pkg.status }}</span>
                          <span class="mx-2">|</span>
                          Current Region: <span class="font-semibold">{{ pkg.current_region?.name || 'Unknown' }}</span>
                        </div>
                        <div v-if="pkg.deliveryRequest" class="text-xs text-gray-400 dark:text-gray-500">
                          Destination: <span class="font-semibold">{{ pkg.deliveryRequest.dropOffRegion || 'Unknown' }}</span>
                          <span class="mx-2">|</span>
                          Pickup: <span class="font-semibold">{{ pkg.deliveryRequest.pickUpRegion || 'Unknown' }}</span>
                        </div>
                        
                        <!-- Package Verification Status -->
                        <div class="mt-2">
                          <div v-if="pkg.verified_at" class="flex items-center text-xs text-green-600">
                            <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            Verified {{ formatDateTime(pkg.verified_at) }}
                          </div>
                          <div v-else class="flex items-center text-xs text-yellow-600">
                            <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            Pending Verification
                          </div>
                        </div>
                      </div>
                    </div>
                    <div class="flex flex-col items-end space-y-1">
                      <span :class="statusClasses(pkg.status)">
                        {{ statusOptions[pkg.status] || pkg.status }}
                      </span>
                      <span class="text-xs text-gray-500 dark:text-gray-400">
                        Code: {{ pkg.item_code }}
                      </span>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- No Packages Message -->
            <div v-if="packages.length === 0" class="text-center py-12">
              <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
              </svg>
              <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-gray-100">No packages assigned</h3>
              <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                You don't have any packages assigned to you at the moment.
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Confirmation Modal -->
    <Modal :show="showConfirmationModal" @close="showConfirmationModal = false">
      <div class="p-6">
        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
          Update Summary
        </h2>

        <div class="mb-6 space-y-3">
          <!-- Auto-delivered packages -->
          <div v-if="updateSummary.delivered > 0" class="flex items-start">
            <svg class="h-5 w-5 text-green-500 mt-0.5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
            <p class="text-gray-700 dark:text-gray-300">
              <span class="font-semibold">{{ updateSummary.delivered }}</span> package(s) will be automatically marked as <span class="font-semibold">Delivered</span> at destination
            </p>
          </div>

          <!-- Auto-returned packages -->
          <div v-if="updateSummary.returned > 0" class="flex items-start">
            <svg class="h-5 w-5 text-orange-500 mt-0.5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 15v-1a4 4 0 00-4-4H8m0 0l3 3m-3-3l3-3m9 14V5a2 2 0 00-2-2H6a2 2 0 00-2 2v16l4-2 4 2 4-2 4 2z" />
            </svg>
            <p class="text-gray-700 dark:text-gray-300">
              <span class="font-semibold">{{ updateSummary.returned }}</span> package(s) will be automatically marked as <span class="font-semibold">Returned</span> at sender
            </p>
          </div>

          <!-- Regular status updates -->
          <div v-if="updateSummary.regular > 0" class="flex items-start">
            <svg class="h-5 w-5 text-blue-500 mt-0.5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <p class="text-gray-700 dark:text-gray-300">
              <span class="font-semibold">{{ updateSummary.regular }}</span> package(s) will be updated to <span class="font-semibold">{{ statusOptions[form.status] }}</span>
            </p>
          </div>
        </div>

        <div class="flex justify-end space-x-3">
          <SecondaryButton @click="showConfirmationModal = false">
            Cancel
          </SecondaryButton>
          <PrimaryButton @click="submitStatusUpdate">
            Confirm Update
          </PrimaryButton>
        </div>
      </div>
    </Modal>

    <!-- Enhanced Location Update Confirmation Modal -->
    <Modal :show="showLocationUpdateModal" @close="showLocationUpdateModal = false">
      <div class="p-6">
        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
          Confirm Location & Package Updates
        </h2>

        <div class="mb-6 space-y-4">
          <!-- Location Update -->
          <div class="flex items-start">
            <svg class="h-5 w-5 text-blue-500 mt-0.5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
            </svg>
            <div>
              <p class="text-gray-700 dark:text-gray-300 font-medium">
                Location Update
              </p>
              <p class="text-sm text-gray-600 dark:text-gray-400">
                Moving from <span class="font-semibold">{{ currentLocation }}</span> to <span class="font-semibold">{{ selectedRegionName }}</span>
              </p>
            </div>
          </div>

          <!-- Package Updates -->
          <div class="flex items-start">
            <svg class="h-5 w-5 text-purple-500 mt-0.5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
            </svg>
            <div>
              <p class="text-gray-700 dark:text-gray-300 font-medium">
                Package Updates
              </p>
              <ul class="text-sm text-gray-600 dark:text-gray-400 mt-1 space-y-1">
                <li v-if="driverRegionForm.update_packages">
                  • All assigned packages will be moved to <span class="font-semibold">{{ selectedRegionName }}</span>
                </li>
                <li v-if="driverRegionForm.only_in_transit">
                  • Only packages with "In Transit" status will be updated
                </li>
                <li v-if="!driverRegionForm.only_in_transit">
                  • All assigned packages will be updated regardless of status
                </li>
              </ul>
            </div>
          </div>

          <!-- Automatic Status Changes -->
          <div v-if="locationUpdateSummary.delivered > 0 || locationUpdateSummary.returned > 0" class="flex items-start">
            <svg class="h-5 w-5 text-green-500 mt-0.5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <div>
              <p class="text-gray-700 dark:text-gray-300 font-medium">
                Automatic Status Changes
              </p>
              <div v-if="locationUpdateSummary.delivered > 0" class="text-sm text-green-600 dark:text-green-400 mt-1">
                • {{ locationUpdateSummary.delivered }} package(s) will be <span class="font-semibold">auto-delivered</span> at destination
              </div>
              <div v-if="locationUpdateSummary.returned > 0" class="text-sm text-orange-600 dark:text-orange-400 mt-1">
                • {{ locationUpdateSummary.returned }} package(s) will be <span class="font-semibold">auto-returned</span> to sender
              </div>
            </div>
          </div>

          <!-- Assignment Status Impact -->
          <div class="flex items-start">
            <svg class="h-5 w-5 text-yellow-500 mt-0.5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <div>
              <p class="text-gray-700 dark:text-gray-300 font-medium">
                Assignment Status Impact
              </p>
              <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                System will automatically check if all packages are delivered and update your assignment status accordingly.
              </p>
            </div>
          </div>
        </div>

        <div class="flex justify-end space-x-3">
          <SecondaryButton @click="showLocationUpdateModal = false">
            Cancel
          </SecondaryButton>
          <PrimaryButton @click="submitLocationUpdate">
            Confirm Location & Package Updates
          </PrimaryButton>
        </div>
      </div>


      
    </Modal>

    <!-- Skip Cooldown Modal -->
<Modal :show="showSkipCooldownModal" @close="showSkipCooldownModal = false">
  <div class="p-6">
    <div class="flex items-center gap-3 mb-4">
      <div class="flex-shrink-0">
        <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center">
          <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
          </svg>
        </div>
      </div>
      <div>
        <h2 class="text-lg font-medium text-gray-900 dark:text-white">
          Skip Cooldown Period
        </h2>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
          Option A: Become immediately eligible for backhaul assignments
        </p>
      </div>
    </div>

    <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4 mb-4">
      <div class="flex">
        <svg class="h-5 w-5 text-blue-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        <div class="text-sm text-blue-700 dark:text-blue-300">
          <strong>Please confirm:</strong>
          <ul class="mt-1 ml-4 list-disc space-y-1">
            <li>You will skip the remaining cooldown period</li>
            <li>You will become immediately eligible for backhaul assignments</li>
            <li>This action cannot be undone</li>
          </ul>
        </div>
      </div>
    </div>

    <div class="flex justify-end space-x-3 mt-6">
      <SecondaryButton @click="showSkipCooldownModal = false">
        Cancel
      </SecondaryButton>
      <PrimaryButton @click="confirmSkipCooldown">
        Skip Cooldown & Become Backhaul Eligible
      </PrimaryButton>
    </div>
  </div>
</Modal>

<!-- Return Without Backhaul Modal -->
<Modal :show="showReturnModal" @close="showReturnModal = false">
  <div class="p-6">
    <div class="flex items-center gap-3 mb-4">
      <div class="flex-shrink-0">
        <div class="w-10 h-10 bg-orange-100 rounded-full flex items-center justify-center">
          <svg class="w-5 h-5 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
          </svg>
        </div>
      </div>
      <div>
        <h2 class="text-lg font-medium text-gray-900 dark:text-white">
          Return Without Backhaul
        </h2>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
          Option B: Return directly to home base
        </p>
      </div>
    </div>

    <div class="bg-orange-50 dark:bg-orange-900/20 border border-orange-200 dark:border-orange-800 rounded-lg p-4 mb-4">
      <div class="flex">
        <svg class="h-5 w-5 text-orange-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        <div class="text-sm text-orange-700 dark:text-orange-300">
          <strong>Return Process:</strong>
          <ul class="mt-1 ml-4 list-disc space-y-1">
            <li>You will start returning to your home region</li>
            <li>You cannot accept new backhaul assignments</li>
            <li>When you arrive at home region, confirm your arrival</li>
            <li>Final cooldown period will begin after arrival</li>
          </ul>
        </div>
      </div>
    </div>

    <div class="mb-4">
      <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
        Reason for Returning
      </label>
      <textarea
        v-model="returnForm.reason"
        rows="3"
        class="w-full rounded-md border-gray-300 shadow-sm focus:border-orange-500 focus:ring-orange-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
        placeholder="Please provide a reason for returning without backhaul..."
      ></textarea>
    </div>

    <div class="flex justify-end space-x-3 mt-6">
      <SecondaryButton @click="showReturnModal = false">
        Cancel
      </SecondaryButton>
      <PrimaryButton 
        @click="confirmReturnWithoutBackhaul"
        :disabled="!returnForm.reason.trim()"
        class="bg-orange-600 hover:bg-orange-700 focus:ring-orange-500"
      >
        Confirm Return Without Backhaul
      </PrimaryButton>
    </div>
  </div>
</Modal>

<!-- Confirm Arrival Modal -->
<Modal :show="showConfirmArrivalModal" @close="showConfirmArrivalModal = false">
  <div class="p-6">
    <div class="flex items-center gap-3 mb-4">
      <div class="flex-shrink-0">
        <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center">
          <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
          </svg>
        </div>
      </div>
      <div>
        <h2 class="text-lg font-medium text-gray-900 dark:text-white">
          Confirm Arrival at Home Base
        </h2>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
          Option B Completion: You have arrived at your home region
        </p>
      </div>
    </div>

    <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg p-4 mb-4">
      <div class="flex">
        <svg class="h-5 w-5 text-green-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        <div class="text-sm text-green-700 dark:text-green-300">
          <strong>Next Steps:</strong>
          <ul class="mt-1 ml-4 list-disc space-y-1">
            <li>Your assignment will move to final cooldown period</li>
            <li>Final cooldown timer will start (30 minutes)</li>
            <li>Complete the cooldown to finish your assignment</li>
            <li>You will become available for new assignments</li>
          </ul>
        </div>
      </div>
    </div>

    <div class="flex justify-end space-x-3 mt-6">
      <SecondaryButton @click="showConfirmArrivalModal = false">
        Cancel
      </SecondaryButton>
      <PrimaryButton @click="confirmArrivalAction" class="bg-green-600 hover:bg-green-700 focus:ring-green-500">
        Confirm Arrival at Home Base
      </PrimaryButton>
    </div>
  </div>
</Modal>

<!-- Complete Cooldown Modal -->
<Modal :show="showCompleteCooldownModal" @close="showCompleteCooldownModal = false">
  <div class="p-6">
    <div class="flex items-center gap-3 mb-4">
      <div class="flex-shrink-0">
        <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center">
          <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
          </svg>
        </div>
      </div>
      <div>
        <h2 class="text-lg font-medium text-gray-900 dark:text-white">
          Complete Cooldown Period
        </h2>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
          Finish your assignment and become available for new assignments
        </p>
      </div>
    </div>

    <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg p-4 mb-4">
      <div class="flex">
        <svg class="h-5 w-5 text-green-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        <div class="text-sm text-green-700 dark:text-green-300">
          <strong>Assignment Completion:</strong>
          <ul class="mt-1 ml-4 list-disc space-y-1">
            <li>Your current assignment will be marked as completed</li>
            <li>You will become available for new assignments</li>
            <li>Your truck will be freed for other drivers</li>
            <li>All assignment resources will be released</li>
          </ul>
        </div>
      </div>
    </div>

    <div class="flex justify-end space-x-3 mt-6">
      <SecondaryButton @click="showCompleteCooldownModal = false">
        Cancel
      </SecondaryButton>
      <PrimaryButton @click="confirmCompleteCooldown" class="bg-green-600 hover:bg-green-700 focus:ring-green-500">
        Complete Cooldown & Finish Assignment
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
import InputError from '@/Components/InputError.vue'
import Checkbox from '@/Components/Checkbox.vue'
import Modal from '@/Components/Modal.vue'
import { ChevronDownIcon } from '@heroicons/vue/20/solid'

// Leaflet imports
import { LMap, LTileLayer, LMarker, LPopup, LControlZoom } from '@vue-leaflet/vue-leaflet'
import 'leaflet/dist/leaflet.css'
import L from 'leaflet'

// Fix for default markers in Leaflet with Webpack
delete L.Icon.Default.prototype._getIconUrl
L.Icon.Default.mergeOptions({
  iconRetinaUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.7.1/images/marker-icon-2x.png',
  iconUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.7.1/images/marker-icon.png',
  shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.7.1/images/marker-shadow.png',
})

const props = defineProps({
  packages: {
    type: Array,
    default: () => []
  },
  regions: {
    type: Array,
    default: () => []
  },
  currentRegionId: {
    type: Number,
    default: null
  },
  currentLocation: {
    type: String,
    default: 'Unknown'
  },
  routeData: {
    type: Object,
    default: () => ({ route: [] })
  },
  currentAssignment: {
    type: Object,
    default: null
  },
  statusOptions: Object,
  backhaulAvailable: Boolean,
  allPackagesFinal: Boolean
})

// State Management
const showConfirmationModal = ref(false);
const showLocationUpdateModal = ref(false);
const selectedPackages = ref([]);

// Forms
const form = useForm({
    package_ids: [],
    status: '',
    remarks: '',
    region_id: ''
});

const driverRegionForm = useForm({
    region_id: null,
    update_packages: true,
    only_in_transit: true
});

const skipCooldownForm = useForm({});
const returnForm = useForm({ reason: '' });
const confirmArrivalForm = useForm({});
const completeCooldownForm = useForm({});

// Map Refs and State
const mapRef = ref(null);
const center = ref([13.1399, 123.7448]);
const zoom = ref(11);
const mapBounds = ref(null);

// Status options for display
const statusOptions = {
  'loaded': 'Loaded',
  'in_transit': 'In Transit',
  'delivered': 'Delivered',
  'returned': 'Returned to Sender',
  'completed': 'Completed'
}

// Status classes for styling
const statusClasses = (status) => {
  const baseClasses = 'text-xs font-semibold px-2 py-1 rounded-full'
  switch (status) {
    case 'loaded':
      return `${baseClasses} bg-blue-100 text-blue-800`
    case 'in_transit':
      return `${baseClasses} bg-yellow-100 text-yellow-800`
    case 'delivered':
      return `${baseClasses} bg-green-100 text-green-800`
    case 'returned':
      return `${baseClasses} bg-red-100 text-red-800`
    case 'completed':
      return `${baseClasses} bg-gray-100 text-gray-800`
    default:
      return `${baseClasses} bg-gray-100 text-gray-800`
  }
}

// Format status for display
const formatStatus = (status) => {
  return status.split('_').map(word => 
    word.charAt(0).toUpperCase() + word.slice(1)
  ).join(' ')
}

// Format date time
const formatDateTime = (dateString) => {
  if (!dateString) return 'N/A'
  return new Date(dateString).toLocaleString()
}

// NEW: Filter regions to only show pickup and dropoff regions from assignments
const assignmentRegions = computed(() => {
  const uniqueRegions = new Map();
  
  props.packages.forEach(pkg => {
    if (pkg.deliveryRequest) {
      // Add pickup region
      if (pkg.deliveryRequest.pick_up_region_id && pkg.deliveryRequest.pickUpRegion) {
        uniqueRegions.set(pkg.deliveryRequest.pick_up_region_id, {
          id: pkg.deliveryRequest.pick_up_region_id,
          name: pkg.deliveryRequest.pickUpRegion,
          type: 'pickup'
        });
      }
      
      // Add dropoff region
      if (pkg.deliveryRequest.drop_off_region_id && pkg.deliveryRequest.dropOffRegion) {
        uniqueRegions.set(pkg.deliveryRequest.drop_off_region_id, {
          id: pkg.deliveryRequest.drop_off_region_id,
          name: pkg.deliveryRequest.dropOffRegion,
          type: 'dropoff'
        });
      }
    }
  });
  
  return Array.from(uniqueRegions.values()).sort((a, b) => a.name.localeCompare(b.name));
});

// Toggle package selection
const togglePackageSelection = (packageId) => {
  const index = selectedPackages.value.indexOf(packageId)
  if (index > -1) {
    selectedPackages.value.splice(index, 1)
  } else {
    selectedPackages.value.push(packageId)
  }
}

const showSkipCooldownModal = ref(false);
const showReturnModal = ref(false);
const showConfirmArrivalModal = ref(false);
const showCompleteCooldownModal = ref(false);


// Group packages by destination
const groupedPackages = computed(() => {
  const groups = {}
  
  props.packages.forEach(pkg => {
    const destinationId = pkg.deliveryRequest?.dropOffRegionId || 'unknown'
    const destinationName = pkg.deliveryRequest?.dropOffRegion || 'Unknown Destination'
    const currentRegion = pkg.current_region?.name || 'Unknown Region'
    
    if (!groups[destinationId]) {
      groups[destinationId] = {
        destination: {
          id: destinationId,
          name: destinationName
        },
        currentRegion: currentRegion,
        packages: []
      }
    }
    
    groups[destinationId].packages.push(pkg)
  })
  
  return Object.values(groups)
})

// Assignment status display logic
const assignmentStatusDisplay = computed(() => {
  if (!props.currentAssignment) return null;
  
  const status = props.currentAssignment.current_status;
  const isFinalCooldown = props.currentAssignment.is_final_cooldown;
  
  switch (status) {
    case 'active':
      return {
        label: 'Active Delivery',
        description: 'You are actively delivering packages to destination regions',
        color: 'blue'
      };
    case 'in_transit':
      return {
        label: 'In Transit',
        description: 'Currently transporting packages between regions',
        color: 'indigo'
      };
    case 'cooldown':
      if (isFinalCooldown) {
        return {
          label: 'Final Cooldown Period',
          description: 'Complete cooldown to finish your assignment',
          color: 'purple'
        };
      }
      return {
        label: 'Cooldown Period',
        description: 'Waiting period before backhaul eligibility',
        color: 'orange'
      };
    case 'backhaul_eligible':
      return {
        label: 'Backhaul Eligible',
        description: 'You can accept backhaul assignments or return to home base',
        color: 'green'
      };
    case 'returning':
      return {
        label: 'Returning to Home Base',
        description: 'You are on your way back to your home region',
        color: 'yellow'
      };
    case 'completed':
      return {
        label: 'Assignment Completed',
        description: 'Your assignment has been completed successfully',
        color: 'green'
      };
    default:
      return {
        label: 'Unknown Status',
        description: 'Current assignment status is unknown',
        color: 'gray'
      };
  }
});

// Debug method to check assignment state
const debugAssignmentState = () => {
  console.log('Current Assignment:', {
    status: props.currentAssignment?.current_status,
    is_final_cooldown: props.currentAssignment?.is_final_cooldown,
    cooldown_ends_at: props.currentAssignment?.cooldown_ends_at,
    backhaul_eligible_at: props.currentAssignment?.backhaul_eligible_at
  });
};

// Call this when component mounts or when assignment changes
onMounted(() => {
  debugAssignmentState();
});

// Show/hide logic for cooldown options
const showCooldownOptions = computed(() => {
  return props.currentAssignment?.current_status === 'cooldown' && 
         !props.currentAssignment.is_final_cooldown;
});

const showSkipCooldownButton = computed(() => {
  return props.currentAssignment?.current_status === 'cooldown' && 
         !props.currentAssignment.is_final_cooldown;
});

// Single return option that shows in multiple statuses
const showReturnOptions = computed(() => {
  return props.currentAssignment?.current_status === 'cooldown' && 
         !props.currentAssignment.is_final_cooldown ||
         props.currentAssignment?.current_status === 'backhaul_eligible' ||
         props.currentAssignment?.current_status === 'active' ||
         props.currentAssignment?.current_status === 'in_transit';
});

const showConfirmArrivalButton = computed(() => {
  return props.currentAssignment?.current_status === 'returning';
});

const showCompleteCooldownButton = computed(() => {
  return props.currentAssignment?.current_status === 'cooldown' && 
         props.currentAssignment.is_final_cooldown;
});

// Computed properties
const selectedRegionName = computed(() => {
  if (!driverRegionForm.region_id) return ''
  // Check route regions first
  const routeRegion = props.routeData.route.find(step => step.region.id == driverRegionForm.region_id)
  if (routeRegion) return routeRegion.region.name
  
  // Check assignment regions
  const assignmentRegion = assignmentRegions.value.find(r => r.id == driverRegionForm.region_id)
  return assignmentRegion ? assignmentRegion.name : ''
})

const filteredRouteSteps = computed(() => {
  return props.routeData.route.filter(step => step.region && step.region.latitude && step.region.longitude)
})

const totalRouteTime = computed(() => {
  return props.routeData.route.reduce((total, step) => total + (step.estimated_minutes || 0), 0)
})

const hasRouteData = computed(() => {
  return props.routeData.route && props.routeData.route.length > 0
})

// Update summary for confirmation modal
const updateSummary = computed(() => {
  const summary = {
    delivered: 0,
    returned: 0,
    regular: 0
  }
  
  if (!form.status || selectedPackages.value.length === 0) return summary
  
  selectedPackages.value.forEach(packageId => {
    const pkg = props.packages.find(p => p.id === packageId)
    if (!pkg) return
    
    const isAtDestination = pkg.deliveryRequest?.dropOffRegionId === form.region_id
    const isAtSender = pkg.deliveryRequest?.pickUpRegionId === form.region_id
    
    if (form.status === 'delivered' && isAtDestination) {
      summary.delivered++
    } else if (form.status === 'returned' && isAtSender) {
      summary.returned++
    } else {
      summary.regular++
    }
  })
  
  return summary
})

// Location update summary
const locationUpdateSummary = computed(() => {
  const summary = {
    delivered: 0,
    returned: 0
  }
  
  if (!driverRegionForm.region_id) return summary
  
  props.packages.forEach(pkg => {
    const isAtDestination = pkg.deliveryRequest?.dropOffRegionId == driverRegionForm.region_id
    const isAtSender = pkg.deliveryRequest?.pickUpRegionId == driverRegionForm.region_id
    
    if (isAtDestination) {
      summary.delivered++
    } else if (isAtSender) {
      summary.returned++
    }
  })
  
  return summary
})

// Methods
const isDestination = (regionId) => {
  return props.routeData.route.some(step => 
    step.region.id === regionId && 
    step.region.id !== props.currentRegionId
  )
}

const getMarkerIcon = (step, index) => {
  const isCurrent = step.region.id === props.currentRegionId
  const isDestination = step.region.id !== props.currentRegionId && 
    index === props.routeData.route.length - 1
  
  let html = `
    <div class="relative flex items-center justify-center w-8 h-8 text-white font-bold text-xs rounded-full shadow-lg ${
      isCurrent ? 'bg-blue-500 border-2 border-white' : 
      isDestination ? 'bg-green-500 border-2 border-white' : 
      'bg-gray-500 border-2 border-white'
    }">
      ${index + 1}
    </div>
  `
  
  return L.divIcon({
    html: html,
    className: 'custom-marker',
    iconSize: [32, 32],
    iconAnchor: [16, 16]
  })
}

const onMapReady = () => {
  nextTick(() => {
    if (mapRef.value?.leafletObject && filteredRouteSteps.value.length > 0) {
      const bounds = L.latLngBounds(filteredRouteSteps.value.map(step => 
        [Number(step.region.latitude), Number(step.region.longitude)]
      ))
      mapRef.value.leafletObject.fitBounds(bounds, { padding: [20, 20] })
    }
  })
}

const openConfirmationModal = () => {
  if (!form.status) {
    alert('Please select a status first.')
    return
  }
  
  if (selectedPackages.value.length === 0) {
    alert('Please select at least one package.')
    return
  }
  
  form.package_ids = selectedPackages.value
  showConfirmationModal.value = true
}

const submitStatusUpdate = () => {
  form.post(route('driver.bulk-update-status'), {
    preserveScroll: true,
    onSuccess: () => {
      showConfirmationModal.value = false
      form.reset()
      selectedPackages.value = []
    }
  })
}

const openLocationUpdateModal = () => {
  if (!driverRegionForm.region_id) {
    alert('Please select a location first.')
    return
  }
  
  showLocationUpdateModal.value = true
}

const submitLocationUpdate = () => {
  driverRegionForm.post(route('driver.update-region'), {
    preserveScroll: true,
    onSuccess: () => {
      showLocationUpdateModal.value = false
      driverRegionForm.region_id = ''
    }
  })
}

const returnWithoutBackhaul = () => {
  if (!returnForm.reason.trim()) {
    alert("Please provide a reason for returning without backhaul.");
    return;
  }
  showReturnModal.value = true;
};

const confirmReturnWithoutBackhaul = () => {
  if (!returnForm.reason.trim()) {
    alert("Please provide a reason for returning without backhaul.");
    return;
  }
  
  returnForm.post(route('driver.return-without-backhaul'), {
    preserveScroll: true,
    onSuccess: () => {
      returnForm.reason = "";
      showReturnModal.value = false;
    },
    onError: (errors) => {
      console.error('Return without backhaul failed:', errors);
    }
  });
};


// UPDATED: Enhanced skip cooldown with validation
const skipCooldown = () => {
  showSkipCooldownModal.value = true;
};


const confirmSkipCooldown = () => {
  router.post(route('driver.skip-cooldown'), {}, {
    onSuccess: () => {
      showSkipCooldownModal.value = false;
    }
  });
};



// UPDATED: Enhanced confirm arrival with validation
const confirmArrivalAtHome = () => {
  showConfirmArrivalModal.value = true;
};

const confirmArrivalAction = () => {
  router.post(route('driver.confirm-arrival-home'), {}, {
    onSuccess: () => {
      showConfirmArrivalModal.value = false;
    }
  });
};

// UPDATED: Complete cooldown method with final cooldown validation
const completeCooldown = () => {
  showCompleteCooldownModal.value = true;
};

const confirmCompleteCooldown = () => {
  router.post(route('driver.complete-cooldown'), {}, {
    onSuccess: () => {
      showCompleteCooldownModal.value = false;
    }
  });
};

onMounted(() => {
  // Set initial form values
  if (props.currentRegionId) {
    driverRegionForm.region_id = props.currentRegionId.toString()
  }
})
</script>

<style scoped>
.custom-marker {
  background: transparent !important;
  border: none !important;
}

.leaflet-popup-content {
  margin: 12px 16px;
  line-height: 1.4;
}

.leaflet-popup-content-wrapper {
  border-radius: 8px;
}
</style>