<template>
  <EmployeeLayout>
    <template #header>
      <div class="flex justify-between items-center w-full px-4 md:px-6 lg:px-8">
        <!-- Left: Title & Subtitle -->
        <div>
          <h2 class="text-lg md:text-xl font-semibold leading-tight text-gray-800">Package Tracking</h2>
          <p class="mt-1 text-xs md:text-sm text-gray-500">
            Tracking #{{ packageData.item_code || 'N/A' }} - {{ packageData.item_name || 'N/A' }}
          </p>
        </div>

        <!-- Right: Status Badge + Back Button -->
        <div class="flex items-center gap-3">
          <!-- Status Badge -->
          <div class="px-3 py-1 text-sm font-medium rounded-full inline-flex items-center gap-1" :class="statusClasses">
            <span class="w-2 h-2 rounded-full" :class="statusDotClasses"></span>
            {{ formattedStatus }}
          </div>
          
          <!-- Incident Alert Badge -->
          <div v-if="hasIncident" class="px-3 py-1 text-sm font-medium rounded-full inline-flex items-center gap-1 bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-200">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z" />
            </svg>
            {{ incidentType }}
          </div>
          
          <!-- Back Button -->
          <SecondaryButton
            type="button"
            @click="goBack"
            class="inline-flex items-center"
          >
            <svg class="h-5 w-5 mr-2" fill="none" stroke="currentColor" stroke-width="1" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
            </svg>
            Back
          </SecondaryButton>
        </div>
      </div>
    </template>

    <!-- ZOOM CONTENT WRAPPER -->
    <div class="zoom-content">
    <!-- MAIN CONTENT CONTAINER WITH PROPER PADDING -->
    <div class="px-4 md:px-6 py-4">
      <!-- Incident Alert Banner -->
      <div v-if="hasIncident" class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6 dark:bg-red-900/20 dark:border-red-800">
        <div class="flex items-start">
          <div class="flex-shrink-0">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z" />
            </svg>
          </div>
          <div class="ml-3 flex-1">
            <h3 class="text-sm font-medium text-red-800 dark:text-red-200">
              Package Incident Reported
            </h3>
            <div class="mt-2 text-sm text-red-700 dark:text-red-300">
              <p><strong>Type:</strong> {{ incidentType }}</p>
              <p v-if="packageData.incident_description"><strong>Description:</strong> {{ packageData.incident_description }}</p>
              <p><strong>Reported:</strong> {{ formatDateTime(packageData.incident_reported_at) }}</p>
              <p v-if="packageData.incident_resolved_at"><strong>Resolved:</strong> {{ formatDateTime(packageData.incident_resolved_at) }}</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Progress Tracker -->
      <div class="bg-white rounded-xl shadow-sm p-4 md:p-6 mb-6 dark:bg-gray-800">
        <h3 class="text-base md:text-lg font-semibold text-gray-900 dark:text-white mb-4">Delivery Progress</h3>
        <div class="relative">
          <!-- Progress Line at Bottom -->
          <div class="absolute left-0 right-0 bottom-0 h-1 bg-gray-200 dark:bg-gray-700"></div>
          
          <div class="relative flex justify-between pb-4">
            <div v-for="(step, index) in deliverySteps" :key="step.status" class="flex flex-col items-center relative z-10 flex-1">
              <!-- Step Icon -->
              <div class="w-10 h-10 rounded-full flex items-center justify-center mb-2 transition-all duration-300"
                   :class="getStepIconClass(step.status)">
                <component 
                  :is="getStepIcon(step.status)" 
                  class="w-4 h-4" 
                  :class="isStepCompleted(step.status) ? 'text-white' : 'text-gray-400'"
                />
              </div>
              
              <!-- Step Label -->
              <span class="text-xs font-medium text-center max-w-20 md:max-w-24 leading-tight" 
                    :class="isStepCompleted(step.status) ? 'text-gray-900 dark:text-white' : 'text-gray-400 dark:text-gray-500'">
                {{ step.label }}
              </span>
              
              <!-- Step Time (if completed) -->
              <span v-if="getStepCompletionTime(step.status)" class="text-xs text-gray-400 mt-1 text-center hidden md:block">
                {{ getStepCompletionTime(step.status) }}
              </span>
            </div>
          </div>
        </div>
      </div>

      <!-- Main Content Grid -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 md:gap-6">
        <!-- Left Column - Combined Journey Timeline -->
        <div class="lg:col-span-2">
        <!-- Delivery Journey with Transfers -->
<div class="bg-white rounded-xl shadow-sm p-4 md:p-6 dark:bg-gray-800 h-full flex flex-col">
  <h2 class="text-base md:text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-green-500" viewBox="0 0 20 20" fill="currentColor">
      <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-8.707l-3-3a1 1 0 00-1.414 0l-3 3a1 1 0 001.414 1.414L9 9.414V13a1 1 0 102 0V9.414l1.293 1.293a1 1 0 001.414-1.414z" clip-rule="evenodd" />
    </svg>
    Delivery Journey
  </h2>
            
            <!-- Scrollable Timeline Container -->
<div class="flex-1 overflow-y-auto min-h-0" style="max-height: 560px;"> 
              <div class="relative">
                <!-- Journey Line -->
                <div class="absolute left-3 md:left-4 top-0 bottom-0 w-0.5 bg-gray-200 dark:bg-gray-700"></div>
                
<div class="space-y-4 pl-9 md:pl-11"> <!-- Increased from pl-6 md:pl-8 -->
                  <template v-if="combinedTimeline.length > 0">
<div v-for="(item, index) in combinedTimeline" :key="getTimelineKey(item, index)" class="relative">                      <!-- Timeline Dot -->
                      <div class="absolute -left-5 md:-left-8 top-1.5 flex h-3 w-3 md:h-4 md:w-4 items-center justify-center rounded-full"
                          :class="getTimelineDotClass(index, item)">
                        <div v-if="index === combinedTimeline.length - 1" class="h-1.5 w-1.5 rounded-full bg-white"></div>
                        <!-- Incident Icon for damaged/lost packages -->
                        <svg v-if="isIncidentItem(item)" xmlns="http://www.w3.org/2000/svg" class="h-2 w-2 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z" />
                        </svg>
                      </div>
                      
                      <!-- Status Card -->
                      <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-3 md:p-4 shadow-xs" :class="getTimelineCardClass(item)">
                        <div class="flex justify-between items-start flex-col md:flex-row gap-2">
                          <div class="flex-1">
                            <h3 class="font-medium text-gray-900 dark:text-white text-sm md:text-base">
                              {{ getTimelineTitle(item) }}
                            </h3>
                            <p class="text-xs md:text-sm text-gray-500 dark:text-gray-400 mt-1">
                              {{ formatDateTime(item.timestamp) }}
                            </p>
                          </div>
                          <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium whitespace-nowrap"
                                :class="getStatusBadgeClass(getTimelineStatus(item))">
                            {{ getTimelineTitle(item) }}
                          </span>
                        </div>
                        
                        <div v-if="getTimelineRemarks(item)" class="mt-2 text-xs md:text-sm text-gray-600 dark:text-gray-300">
                          <p>{{ getTimelineRemarks(item) }}</p>
                        </div>

                        <!-- Incident Details -->
                        <div v-if="isIncidentItem(item)" class="mt-3 p-3 bg-red-50 border border-red-200 rounded-md dark:bg-red-900/20 dark:border-red-800">
                          <div class="flex items-start">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-red-500 mt-0.5 mr-2 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z" />
                            </svg>
                            <div>
                              <p class="text-sm font-medium text-red-800 dark:text-red-200">Package Incident</p>
                              <p v-if="packageData.incident_description" class="text-xs text-red-700 dark:text-red-300 mt-1">
                                {{ packageData.incident_description }}
                              </p>
                            </div>
                          </div>
                        </div>

                        <div v-if="item.type === 'transfer'" class="mt-3">
                          <div class="flex items-center text-xs md:text-sm text-gray-600 dark:text-gray-300">
                            <div class="flex-shrink-0 h-4 w-4 md:h-5 md:w-5 text-gray-400 mr-2">
                              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                              </svg>
                            </div>
                            <div class="flex flex-wrap items-center">
                              <span class="font-medium">{{ item.data.fromRegion?.name || item.data.from_region_name || 'N/A' }}</span>
                              <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 md:h-4 md:w-4 mx-1 inline text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3" />
                              </svg>
                              <span class="font-medium">{{ item.data.toRegion?.name || item.data.to_region_name || 'N/A' }}</span>
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
                          {{ getTimelineActor(item) }}
                        </div>
                      </div>
                    </div>
                  </template>
                  
                  <div v-if="combinedTimeline.length === 0" class="text-center py-6 text-gray-500 dark:text-gray-400">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 mx-auto text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <p class="mt-2 text-sm">No tracking information available</p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Right Column - Package Details -->
        <div class="space-y-4 md:space-y-6">
          <!-- Package Information - Reduced Height -->
          <div class="bg-white rounded-xl shadow-sm p-4 md:p-6 dark:bg-gray-800">
            <h2 class="text-base md:text-lg font-semibold text-gray-900 dark:text-white mb-3 md:mb-4 flex items-center gap-2">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-green-500" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M10 2a4 4 0 00-4 4v1H5a1 1 0 00-.994.89l-1 9A1 1 0 004 18h12a1 1 0 00.994-1.11l-1-9A1 1 0 0015 7h-1V6a4 4 0 00-4-4zm2 5V6a2 2 0 10-4 0v1h4zm-6 3a1 1 0 112 0 1 1 0 01-2 0zm7-1a1 1 0 100 2 1 1 0 000-2z" clip-rule="evenodd" />
              </svg>
              Package Details
            </h2>
            
            <div class="space-y-3">
              <div class="flex items-start">
                <div class="flex-shrink-0 h-5 w-5 text-gray-400">
                  <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                  </svg>
                </div>
                <div class="ml-3">
                  <h3 class="text-xs md:text-sm font-medium text-gray-500 dark:text-gray-400">Sender</h3>
                  <p class="mt-1 text-xs md:text-sm text-gray-900 dark:text-white">
                    {{ getSenderName() }}
                  </p>
                </div>
              </div>
              
              <div class="flex items-start">
                <div class="flex-shrink-0 h-5 w-5 text-gray-400">
                  <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                  </svg>
                </div>
                <div class="ml-3">
                  <h3 class="text-xs md:text-sm font-medium text-gray-500 dark:text-gray-400">Receiver</h3>
                  <p class="mt-1 text-xs md:text-sm text-gray-900 dark:text-white">
                    {{ getReceiverName() }}
                  </p>
                </div>
              </div>
              
              <div class="flex items-start">
                <div class="flex-shrink-0 h-5 w-5 text-gray-400">
                  <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                  </svg>
                </div>
                <div class="ml-3">
                  <h3 class="text-xs md:text-sm font-medium text-gray-500 dark:text-gray-400">Destination</h3>
                  <p class="mt-1 text-xs md:text-sm text-gray-900 dark:text-white">
                    {{ getDestination() }}
                  </p>
                </div>
              </div>
              
              <div class="flex items-start">
                <div class="flex-shrink-0 h-5 w-5 text-gray-400">
                  <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5.636 18.364a9 9 0 010-12.728m12.728 0a9 9 0 010 12.728m-9.9-2.829a5 5 0 010-7.07m7.072 0a5 5 0 010 7.07M13 12a1 1 0 11-2 0 1 1 0 012 0z" />
                  </svg>
                </div>
                <div class="ml-3">
                  <h3 class="text-xs md:text-sm font-medium text-gray-500 dark:text-gray-400">Current Location</h3>
                  <p class="mt-1 text-xs md:text-sm text-gray-900 dark:text-white">
                    {{ packageData.current_region?.name || packageData.current_region_name || 'N/A' }}
                  </p>
                </div>
              </div>
              
              <div v-if="packageData.description" class="flex items-start">
                <div class="flex-shrink-0 h-5 w-5 text-gray-400">
                  <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z" />
                  </svg>
                </div>
                <div class="ml-3">
                  <h3 class="text-xs md:text-sm font-medium text-gray-500 dark:text-gray-400">Description</h3>
                  <p class="mt-1 text-xs md:text-sm text-gray-900 dark:text-white">
                    {{ packageData.description }}
                  </p>
                </div>
              </div>
            </div>
          </div>

          <!-- Package Specifications - Reduced Height -->
          <div class="bg-white rounded-xl shadow-sm p-4 md:p-6 dark:bg-gray-800">
            <h2 class="text-base md:text-lg font-semibold text-gray-900 dark:text-white mb-3 md:mb-4 flex items-center gap-2">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-green-500" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd" />
              </svg>
              Specifications
            </h2>
            
            <div class="space-y-3">
              <div v-if="packageData.weight" class="flex items-start">
                <div class="flex-shrink-0 h-5 w-5 text-gray-400">
                  <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M6 18V6a2 2 0 012-2h8a2 2 0 012 2v12" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M6 18h12" />
                  </svg>
                </div>
                <div class="ml-3">
                  <h3 class="text-xs md:text-sm font-medium text-gray-500 dark:text-gray-400">Weight</h3>
                  <p class="mt-1 text-xs md:text-sm text-gray-900 dark:text-white">
                    {{ packageData.weight }} kg
                  </p>
                </div>
              </div>
              
              <div v-if="packageData.value" class="flex items-start">
                <div class="flex-shrink-0 h-5 w-5 text-gray-400">
                  <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8c-1.657 0-3 1.343-3 3s1.343 3 3 3 3-1.343 3-3-1.343-3-3-3zm0 0V4m0 12v4" />
                  </svg>
                </div>
                <div class="ml-3">
                  <h3 class="text-xs md:text-sm font-medium text-gray-500 dark:text-gray-400">Declared Value</h3>
                  <p class="mt-1 text-xs md:text-sm text-gray-900 dark:text-white">
                    ₱{{ Number(packageData.value).toFixed(2) }}
                  </p>
                </div>
              </div>
              
              <div v-if="packageData.height && packageData.width && packageData.length" class="flex items-start">
                <div class="flex-shrink-0 h-5 w-5 text-gray-400">
                  <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <rect width="20" height="12" x="2" y="6" rx="2" />
                  </svg>
                </div>
                <div class="ml-3">
                  <h3 class="text-xs md:text-sm font-medium text-gray-500 dark:text-gray-400">Dimensions</h3>
                  <p class="mt-1 text-xs md:text-sm text-gray-900 dark:text-white">
                    {{ packageData.height }} × {{ packageData.width }} × {{ packageData.length }} cm
                  </p>
                </div>
              </div>
              
              <div v-if="packageData.volume" class="flex items-start">
                <div class="flex-shrink-0 h-5 w-5 text-gray-400">
                  <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <circle cx="12" cy="12" r="10" />
                  </svg>
                </div>
                <div class="ml-3">
                  <h3 class="text-xs md:text-sm font-medium text-gray-500 dark:text-gray-400">Volume</h3>
                  <p class="mt-1 text-xs md:text-sm text-gray-900 dark:text-white">
                    {{ Number(packageData.volume || 0).toFixed(2) }} m³
                  </p>
                </div>
              </div>

              <div v-if="packageData.item_code" class="flex items-start">
                <div class="flex-shrink-0 h-5 w-5 text-gray-400">
                  <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <rect width="20" height="12" x="2" y="6" rx="2" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M6 10h.01M6 14h.01M10 10h.01M10 14h.01M14 10h.01M14 14h.01" />
                  </svg>
                </div>
                <div class="ml-3">
                  <h3 class="text-xs md:text-sm font-medium text-gray-500 dark:text-gray-400">Item Code</h3>
                  <p class="mt-1 text-xs md:text-sm text-gray-900 dark:text-white">
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

// Import icons for the progress tracker
import { 
  CheckBadgeIcon, 
  TruckIcon, 
  RocketLaunchIcon, 
  HomeIcon, 
  ClipboardDocumentCheckIcon,
  ExclamationTriangleIcon 
} from '@heroicons/vue/24/outline';

const props = defineProps({
  package: { type: Object, required: true },
  statusHistory: { type: Array, default: () => [] },
  transfers: { type: Array, default: () => [] }
});

const packageData = computed(() => props.package || {});
const statusHistoryData = computed(() => props.statusHistory || []);
const transfersData = computed(() => props.transfers || []);

// Check if package has incident
const hasIncident = computed(() => {
  return packageData.value.status === 'damaged_in_transit' || 
         packageData.value.status === 'lost_in_transit';
});

// Get incident type for display
const incidentType = computed(() => {
  if (packageData.value.status === 'damaged_in_transit') return 'Damaged in Transit';
  if (packageData.value.status === 'lost_in_transit') return 'Lost in Transit';
  return '';
});

// Delivery progress steps with icons
const deliverySteps = computed(() => [
  { status: 'preparing', label: 'Approved', icon: ClipboardDocumentCheckIcon },
  { status: 'loaded', label: 'Loaded', icon: TruckIcon },
  { status: 'in_transit', label: 'Dispatched', icon: RocketLaunchIcon },
  { status: 'delivered', label: 'Delivered', icon: HomeIcon },
  { status: 'completed', label: 'Complete', icon: CheckBadgeIcon }
]);

// Get step icon component
const getStepIcon = (stepStatus) => {
  const step = deliverySteps.value.find(s => s.status === stepStatus);
  return step?.icon || CheckBadgeIcon;
};

// Check if a step is completed
const isStepCompleted = (stepStatus) => {
  const stepIndex = deliverySteps.value.findIndex(step => step.status === stepStatus);
  const currentStepIndex = deliverySteps.value.findIndex(step => step.status === packageData.value.status);
  
  return stepIndex <= currentStepIndex;
};

// Get step completion time
const getStepCompletionTime = (stepStatus) => {
  const stepHistory = statusHistoryData.value.find(history => history.status === stepStatus);
  if (stepHistory) {
    return formatDateOnly(stepHistory.updated_at);
  }
  return null;
};

// Get step icon class based on completion - GREEN THEME
const getStepIconClass = (stepStatus) => {
  if (isStepCompleted(stepStatus)) {
    return 'bg-green-500 shadow-sm border-2 border-green-600';
  }
  return 'bg-gray-100 border-2 border-gray-300';
};

// Format date only (without time)
const formatDateOnly = (dateString) => {
  if (!dateString) return '';
  try {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
      month: 'short',
      day: 'numeric'
    });
  } catch (e) {
    return '';
  }
};

// FIXED: Combine status history and transfers into a single timeline in CHRONOLOGICAL order
const combinedTimeline = computed(() => {
  const timeline = [];
  
  // Add ALL status history items
  statusHistoryData.value.forEach(history => {
    timeline.push({
      type: 'status',
      timestamp: history.updated_at || history.created_at,
      data: history
    });
  });
  
  // Add ALL transfer items
  transfersData.value.forEach(transfer => {
    timeline.push({
      type: 'transfer',
      timestamp: transfer.transferred_at || transfer.created_at,
      data: transfer
    });
  });
  
  // Sort by timestamp NEWEST FIRST (for bottom-to-top display)
  return timeline.sort((a, b) => new Date(b.timestamp) - new Date(a.timestamp));
});

// Add the goBack function to your script
function goBack() {
  if (window.history.length > 1) {
    window.history.back();
  } else {
    // fallback: go to dashboard or home if no history
    window.location.href = '/';
  }
}

// UPDATED: Complete status options including incident statuses
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
  'processing': 'Processing',
  'damaged_in_transit': 'Damaged in Transit',
  'lost_in_transit': 'Lost in Transit'
};

function formatStatus(status) {
  if (!status) return 'N/A';
  if (status === 'in_transit') return 'In Transit';
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

// UPDATED: Status badge classes including incident statuses
const statusClasses = computed(() => {
  const base = 'px-3 py-1 text-sm font-medium rounded-full inline-flex items-center';
  const status = packageData.value.status;
  if (!status) return `${base} bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300`;
  const s = String(status).toLowerCase();
  
  if (s === 'completed') return `${base} bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-200`;
  if (s === 'delivered') return `${base} bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-200`;
  if (s === 'in_transit') return `${base} bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-200`;
  if (s === 'loaded') return `${base} bg-indigo-100 text-indigo-800 dark:bg-indigo-900/30 dark:text-indigo-200`;
  if (s === 'ready_for_pickup') return `${base} bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-200`;
  if (s === 'preparing') return `${base} bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-200`;
  if (s === 'processing') return `${base} bg-cyan-100 text-cyan-800 dark:bg-cyan-900/30 dark:text-cyan-200`;
  if (s === 'pending') return `${base} bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-200`;
  if (s === 'returned') return `${base} bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-200`;
  if (s === 'rejected') return `${base} bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-200`;
  if (s === 'damaged_in_transit' || s === 'lost_in_transit') return `${base} bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-200`;
  
  return `${base} bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300`;
});

// UPDATED: GREEN THEME status dot classes including incident statuses
const statusDotClasses = computed(() => {
  const status = packageData.value.status;
  if (!status) return 'bg-gray-400';
  const s = String(status).toLowerCase();
  if (s === 'delivered' || s === 'completed') return 'bg-green-500';
  if (s === 'returned' || s === 'rejected' || s === 'damaged_in_transit' || s === 'lost_in_transit') return 'bg-red-500';
  if (s === 'in_transit' || s === 'loaded' || s === 'ready_for_pickup') return 'bg-green-500';
  if (s === 'pending' || s === 'preparing' || s === 'processing') return 'bg-yellow-500';
  return 'bg-gray-400';
});

// UPDATED: GREEN THEME status badge classes including incident statuses
function getStatusBadgeClass(status) {
  if (!status) return 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300';
  const s = String(status).toLowerCase();
  
  if (s === 'completed') return 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-200';
  if (s === 'delivered') return 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-200';
  if (s === 'in_transit') return 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-200';
  if (s === 'loaded') return 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/30 dark:text-indigo-200';
  if (s === 'ready_for_pickup') return 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-200';
  if (s === 'preparing') return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-200';
  if (s === 'processing') return 'bg-cyan-100 text-cyan-800 dark:bg-cyan-900/30 dark:text-cyan-200';
  if (s === 'pending') return 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-200';
  if (s === 'returned' || s === 'rejected' || s === 'damaged_in_transit' || s === 'lost_in_transit') return 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-200';
  
  return 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300';
}

// Helper functions for timeline items
function getTimelineKey(item, index) {
  return `${item.type}-${item.timestamp}-${index}`;
}

// UPDATED: GREEN THEME timeline dot classes with incident indicators
function getTimelineDotClass(index, item) {
  if (index === 0) {
    if (isIncidentItem(item)) {
      return 'bg-red-500 ring-4 ring-red-200 dark:ring-red-900';
    }
    return 'bg-green-500 ring-4 ring-green-200 dark:ring-green-900';
  }
  if (isIncidentItem(item)) {
    return 'bg-red-500 ring-2 ring-red-200 dark:ring-red-900';
  }
  return 'bg-gray-300 dark:bg-gray-600';
}

// Check if timeline item is an incident
function isIncidentItem(item) {
  return item.type === 'status' && 
         (item.data.status === 'damaged_in_transit' || item.data.status === 'lost_in_transit');
}

// Get timeline card class for incidents
function getTimelineCardClass(item) {
  if (isIncidentItem(item)) {
    return 'border-red-300 bg-red-50 dark:border-red-800 dark:bg-red-900/20';
  }
  return '';
}

function getTimelineTitle(item) {
  if (item.type === 'status') {
    return formatStatus(item.data.status);
  } else if (item.type === 'transfer') {
    return 'In Transit';
  }
  return 'Unknown';
}

function getTimelineStatus(item) {
  if (item.type === 'status') {
    return item.data.status;
  } else if (item.type === 'transfer') {
    return 'in_transit';
  }
  return 'unknown';
}

function getTimelineRemarks(item) {
  if (item.type === 'status') {
    return item.data.remarks;
  } else if (item.type === 'transfer') {
    return item.data.remarks;
  }
  return null;
}

function getTimelineActor(item) {
  if (item.type === 'status') {
    return item.data.updated_by?.name || item.data.updatedBy?.name || 'System';
  } else if (item.type === 'transfer') {
    return item.data.processor?.name || item.data.processor_name || 'System';
  }
  return 'System';
}

// Helper functions for package details
function getSenderName() {
  const deliveryRequest = packageData.value.delivery_request || packageData.value.deliveryRequest;
  if (deliveryRequest?.sender?.name) return deliveryRequest.sender.name;
  if (packageData.value.sender) return packageData.value.sender;
  if (packageData.value.sender_name) return packageData.value.sender_name;
  return 'N/A';
}

function getReceiverName() {
  const deliveryRequest = packageData.value.delivery_request || packageData.value.deliveryRequest;
  if (deliveryRequest?.receiver?.name) return deliveryRequest.receiver.name;
  if (packageData.value.receiver) return packageData.value.receiver;
  if (packageData.value.receiver_name) return packageData.value.receiver_name;
  return 'N/A';
}

function getDestination() {
  const deliveryRequest = packageData.value.delivery_request || packageData.value.deliveryRequest;
  if (deliveryRequest?.dropOffRegion?.name) return deliveryRequest.dropOffRegion.name;
  if (deliveryRequest?.drop_off_region?.name) return deliveryRequest.drop_off_region.name;
  if (packageData.value.destination) return packageData.value.destination;
  if (packageData.value.drop_off_region) return packageData.value.drop_off_region;
  return 'N/A';
}
</script>

<style scoped>

.zoom-content {
  zoom: 0.80;
}

/* Ensure proper spacing and layout */
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

/* Scrollable timeline styling */
.max-h-80 {
  max-height: 20rem; /* 320px - better for 1080p */
}

.max-h-96 {
  max-height: 24rem; /* 384px */
}

.overflow-y-auto {
  scrollbar-width: thin;
  scrollbar-color: #cbd5e1 #f1f5f9;
}

.overflow-y-auto::-webkit-scrollbar {
  width: 6px;
}

.overflow-y-auto::-webkit-scrollbar-track {
  background: #f1f5f9;
  border-radius: 3px;
}

.overflow-y-auto::-webkit-scrollbar-thumb {
  background: #cbd5e1;
  border-radius: 3px;
}

.overflow-y-auto::-webkit-scrollbar-thumb:hover {
  background: #94a3b8;
}

/* Mobile responsiveness */
@media (max-width: 768px) {
  .grid-cols-1 {
    grid-template-columns: 1fr;
  }
  
  .lg\:col-span-2 {
    grid-column: span 1;
  }
}

/* Ensure text doesn't overflow on mobile */
.whitespace-nowrap {
  white-space: nowrap;
}
</style>