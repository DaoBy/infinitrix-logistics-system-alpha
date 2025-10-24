<template>
  <Head title="Assigned Deliveries" />

  <EmployeeLayout>
    <template #header>
      <div class="flex justify-between items-center w-full px-4 md:px-6 lg:px-8">
        <!-- Left: Title & Subtitle -->
        <div>
          <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
            Assigned Deliveries
          </h2>
          <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
            Manage your deliveries and report package status at destination
          </p>
        </div>
      </div>
    </template>

    <!-- MAIN CONTENT CONTAINER WITH PROPER PADDING -->
    <div class="px-3 sm:px-4 md:px-6 lg:px-8 py-3 sm:py-4">
      <!-- Search and Filter Bar -->
      <div class="mb-4 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
        <div class="w-full sm:w-80">
          <SearchInput 
            v-model="search"
            placeholder="Search by reference, package code, or region..."
            class="w-full"
          />
        </div>
        <div class="flex items-center gap-3 w-full sm:w-auto">
          <div class="text-sm text-gray-500 dark:text-gray-400 bg-green-50 dark:bg-green-900/30 px-3 py-1 rounded border border-green-100 dark:border-green-800">
            📦 Showing {{ filteredDeliveries.length }} of {{ deliveries.length }} 
            {{ deliveries.length === 1 ? 'delivery' : 'deliveries' }}
          </div>
        </div>
      </div>

      <!-- Bulk Status Update Section -->
      <div v-if="readyForStatusUpdate.length > 0 && selectedPackages.length > 0" 
           class="mb-4 p-3 sm:p-4 bg-green-50 dark:bg-green-900/20 rounded-lg border border-green-200 dark:border-green-800">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 sm:gap-4">
          <div class="flex items-center gap-3">
            <div class="w-6 h-6 sm:w-8 sm:h-8 bg-green-100 dark:bg-green-800 rounded-lg flex items-center justify-center">
              <svg class="w-3 h-3 sm:w-4 sm:h-4 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
              </svg>
            </div>
            <div>
              <h3 class="text-sm font-semibold text-green-800 dark:text-green-200">
                Bulk Status Update Ready
              </h3>
              <p class="text-xs text-green-700 dark:text-green-300">
                {{ selectedPackages.length }} package(s) selected for status update
              </p>
            </div>
          </div>
          <div class="flex gap-2 mt-2 sm:mt-0">
            <SecondaryButton @click="clearSelection" class="text-xs !px-3 !py-2">
              Clear Selection
            </SecondaryButton>
            <PrimaryButton @click="openBulkStatusModal" class="text-xs !px-3 !py-2">
              Update Selected
            </PrimaryButton>
          </div>
        </div>
      </div>

      <!-- Packages Ready for Status Update Section -->
      <div v-if="readyForStatusUpdate.length > 0" class="mb-6">
        <div class="bg-green-50 dark:bg-green-900/20 rounded-lg border border-green-200 dark:border-green-800 shadow-sm overflow-hidden">
          <div class="px-3 sm:px-4 py-3 border-b border-green-200 dark:border-green-700">
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-2">
                <svg class="h-4 w-4 sm:h-5 sm:w-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <h3 class="text-sm sm:text-base font-semibold text-green-800 dark:text-green-200">
                  Packages Ready for Status Update
                </h3>
              </div>
              <span class="bg-green-100 text-green-800 text-xs font-medium px-2 py-1 rounded-full dark:bg-green-900 dark:text-green-200">
                {{ readyForStatusUpdate.length }} package(s)
              </span>
            </div>
          </div>
          <div class="p-3 sm:p-4">
            <p class="text-xs sm:text-sm text-green-700 dark:text-green-300 mb-3 sm:mb-4">
              You've arrived at destination regions. Update the status of packages that have reached their destinations.
            </p>
            
            <!-- Mobile View - Cards -->
            <div class="sm:hidden space-y-3">
              <div 
                v-for="pkg in readyForStatusUpdate" 
                :key="pkg.id"
                class="bg-white dark:bg-gray-800 rounded-lg border border-green-200 dark:border-green-700 p-3"
              >
                <div class="flex items-start gap-3">
                  <Checkbox
                    :checked="selectedPackages.includes(pkg.id)"
                    @change="togglePackageSelection(pkg.id)"
                    class="mt-1 flex-shrink-0"
                  />
                  <div class="flex-1 min-w-0">
                    <!-- Package Header -->
                    <div class="flex justify-between items-start mb-2">
                      <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-2 mb-1">
                          <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100">
                            Pkg
                          </span>
                          <span class="font-bold text-green-700 dark:text-green-300 tracking-wide truncate">
                            {{ pkg.item_code }}
                          </span>
                        </div>
                        <div class="text-xs text-gray-500 dark:text-gray-400 truncate">
                          {{ pkg.item_name }}
                        </div>
                      </div>
                      <PrimaryButton
                        @click="openStatusModal(pkg)"
                        class="!px-2 !py-1.5 !text-xs flex items-center justify-center gap-1 flex-shrink-0 ml-2"
                      >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        Update
                      </PrimaryButton>
                    </div>

                    <!-- Package Details -->
                    <div class="space-y-1 text-gray-600 dark:text-gray-400 mb-2">
                      <div class="flex justify-between">
                        <span class="text-xs font-medium">Category:</span>
                        <span class="text-xs">{{ pkg.category || 'General' }}</span>
                      </div>
                      <div class="flex justify-between">
                        <span class="text-xs font-medium">Weight:</span>
                        <span class="text-xs">{{ formatWeight(pkg.weight) }}</span>
                      </div>
                      <div class="flex justify-between">
                        <span class="text-xs font-medium">Volume:</span>
                        <span class="text-xs">{{ formatVolume(pkg.volume) }}</span>
                      </div>
                    </div>

                    <!-- Location Info -->
                    <div class="text-xs text-gray-500 dark:text-gray-400 space-y-1">
                      <div class="flex items-center gap-1">
                        <svg class="w-3 h-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        <span class="font-medium">Ref:</span> 
                        <span class="truncate">{{ pkg.deliveryRequest?.reference_number }}</span>
                      </div>
                      <div class="flex flex-col gap-1">
                        <div class="flex items-center gap-1">
                          <span class="font-medium">Current:</span>
                          <span class="truncate">{{ pkg.current_region?.name }}</span>
                        </div>
                        <div class="flex items-center gap-1">
                          <span class="font-medium">Destination:</span>
                          <span class="truncate">{{ pkg.deliveryRequest?.dropOffRegion }}</span>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

           <!-- Desktop View - Table -->
<div class="hidden sm:block">
  <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
      <thead class="bg-gray-50 dark:bg-gray-700">
        <tr>
          <th class="w-8 px-4 py-3">
            <Checkbox
              :checked="allPackagesSelected"
              @change="toggleSelectAll"
            />
          </th>
          <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
            Package Code
          </th>
          <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
            Package Name
          </th>
          <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
            Category
          </th>
          <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
            Weight
          </th>
          <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
            Volume
          </th>
          <!-- REMOVED REFERENCE COLUMN HEADER -->
          <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
            Current Location
          </th>
          <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
            Destination
          </th>
          <th class="w-20 px-4 py-3"></th>
        </tr>
      </thead>
      <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
        <tr 
          v-for="pkg in readyForStatusUpdate" 
          :key="pkg.id"
          class="hover:bg-gray-50 dark:hover:bg-gray-700"
        >
          <td class="px-4 py-3">
            <Checkbox
              :checked="selectedPackages.includes(pkg.id)"
              @change="togglePackageSelection(pkg.id)"
            />
          </td>
          <td class="px-4 py-3 text-sm font-medium text-gray-900 dark:text-white">
            <div class="flex items-center gap-2">
              <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100">
                Pkg
              </span>
              {{ pkg.item_code }}
            </div>
          </td>
          <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">
            {{ pkg.item_name }}
          </td>
          <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">
            {{ pkg.category || 'General' }}
          </td>
          <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">
            {{ formatWeight(pkg.weight) }}
          </td>
          <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">
            {{ formatVolume(pkg.volume) }}
          </td>
          <!-- REMOVED REFERENCE COLUMN DATA -->
          <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">
            {{ pkg.current_region?.name }}
          </td>
          <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">
            {{ pkg.deliveryRequest?.dropOffRegion }}
          </td>
          <td class="px-4 py-3">
            <PrimaryButton
              @click="openStatusModal(pkg)"
              class="!px-3 !py-2 !text-xs flex items-center justify-center gap-1"
            >
              <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
              Update
            </PrimaryButton>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</div>
          </div>
        </div>
      </div>

      <!-- Main Deliveries List -->
      <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">
        <div class="px-3 sm:px-4 py-3 border-b border-gray-200 dark:border-gray-700">
          <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100">
            Active Deliveries
          </h3>
        </div>
        <div class="p-3 sm:p-4">
     <!-- Mobile View - Card Layout -->
<div class="sm:hidden space-y-4">
  <div 
    v-for="delivery in filteredDeliveries" 
    :key="delivery.id"
    :class="getRowClass(delivery)"
    class="rounded-lg border border-gray-200 dark:border-gray-700 shadow-sm p-3"
  >
    <!-- Header Section -->
    <div class="flex justify-between items-start mb-3">
      <div class="min-w-0 flex-1">
        <div class="flex items-center gap-2 mb-1">
          <!-- REMOVED REFERENCE BADGE -->
          <span class="font-bold text-green-700 dark:text-green-300 tracking-wide truncate">
            {{ delivery.reference_number }}
          </span>
        </div>
        <div class="text-xs text-gray-500 dark:text-gray-400">
          ID: DO-{{ String(delivery.id).padStart(6, '0') }}
        </div>
      </div>
      <!-- Status Badge -->
      <span :class="getStatusBadgeClass(delivery)" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium flex-shrink-0 ml-2">
        {{ formatStatus(delivery.status) }}
      </span>
    </div>

    <!-- Mobile View - Route Info Section -->
    <div class="mb-3">
      <div class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-2">
        Route Information
      </div>
      <div class="text-xs text-gray-500 dark:text-gray-400 space-y-2">
        <div class="flex items-start gap-2">
          <svg class="w-3 h-3 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
          </svg>
          <div>
            <div class="font-medium">Pick Up:</div>
            <div class="truncate">{{ getPickUpRegion(delivery) }}</div>
          </div>
        </div>
        <div class="flex items-start gap-2">
          <svg class="w-3 h-3 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
          </svg>
          <div>
            <div class="font-medium">Drop Off:</div>
            <div class="truncate">{{ getDropOffRegion(delivery) }}</div>
          </div>
        </div>
      </div>
    </div>

    <!-- Packages Summary -->
    <div class="mb-3">
      <div class="text-xs text-gray-500 dark:text-gray-400">Total Packages</div>
      <div class="text-sm font-semibold text-gray-900 dark:text-gray-100">
        {{ delivery.package_count || 0 }}
      </div>
    </div>

    <!-- Backhaul Info -->
    <div v-if="delivery.is_backhaul" class="bg-purple-50 dark:bg-purple-900/20 rounded p-2 mb-3">
      <div class="text-xs text-purple-700 dark:text-purple-300">
        <div class="font-medium">Backhaul Delivery</div>
        <div>Return trip with collected packages</div>
      </div>
    </div>

    <!-- Show Packages Button - MOBILE -->
    <div class="flex justify-between gap-2">
      <PrimaryButton
        @click="toggleShowPackages(delivery.id)"
        class="!px-3 !py-2 !text-xs flex-1 flex items-center justify-center gap-1"
      >
        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0zm6 0a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        {{ showPackages[delivery.id] ? 'Hide' : 'Show' }} Packages
      </PrimaryButton>
    </div>

    <!-- Packages List -->
    <div v-if="showPackages[delivery.id] && delivery.packages && delivery.packages.length > 0" class="mt-4 space-y-2">
      <div v-for="pkg in delivery.packages" :key="pkg.id" class="bg-gray-50 dark:bg-gray-700 rounded p-3 border border-gray-200 dark:border-gray-600">
        <div class="flex justify-between items-start mb-2">
          <div class="min-w-0 flex-1">
            <div class="flex items-center gap-2 mb-1">
              <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                {{ pkg.item_code }}
              </span>
            </div>
            <div class="text-xs text-gray-600 dark:text-gray-300 truncate">{{ pkg.item_name }}</div>
          </div>
          <span v-if="pkg.current_region?.name === pkg.deliveryRequest?.dropOffRegion" class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200 flex-shrink-0 ml-2">
            Ready
          </span>
        </div>
        <div class="space-y-1 text-xs text-gray-500 dark:text-gray-400">
          <div class="flex justify-between">
            <span class="font-medium">Category:</span>
            <span>{{ pkg.category || 'General' }}</span>
          </div>
          <div class="flex justify-between">
            <span class="font-medium">Weight:</span>
            <span>{{ formatWeight(pkg.weight) }}</span>
          </div>
          <div class="flex justify-between">
            <span class="font-medium">Volume:</span>
            <span>{{ formatVolume(pkg.volume) }}</span>
          </div>
          <div class="flex justify-between">
            <span class="font-medium">Current:</span>
            <span class="truncate ml-1">{{ pkg.current_region?.name || 'N/A' }}</span>
          </div>
          <div class="flex justify-between">
            <span class="font-medium">Destination:</span>
            <span class="truncate ml-1">{{ pkg.deliveryRequest?.dropOffRegion || 'N/A' }}</span>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Mobile Empty State -->
  <div v-if="filteredDeliveries.length === 0" class="text-center py-8">
    <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-6">
      <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-gray-400 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2a4 4 0 018 0v2M12 7a4 4 0 100 8 4 4 0 000-8z" />
      </svg>
      <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-2">No assigned deliveries found</h3>
      <p class="text-gray-500 dark:text-gray-400">
        {{ search ? 'Try adjusting your search terms' : 'All deliveries have been completed' }}
      </p>
    </div>
  </div>
</div>

          <!-- Desktop View - Custom Table -->
          <div class="hidden sm:block">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg border border-gray-200 dark:border-gray-700 w-full">
              <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                  <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                      <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Reference</th>
                      <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</th>
                      <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Route</th>
                      <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Packages</th>
                      <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider"></th>
                    </tr>
                  </thead>
                  <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    <template v-for="delivery in filteredDeliveries" :key="delivery.id">
                      <tr
                        :class="getRowClass(delivery)"
                        class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors duration-150"
                      >
                        <!-- Reference Column -->
                        <td class="px-6 py-4 whitespace-nowrap">
                          <div class="flex flex-col">
                            <div class="flex items-center gap-2">
                              <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100">
                                Reference #
                              </span>
                              <span class="font-bold text-green-700 dark:text-green-300 tracking-wide">
                                {{ delivery.reference_number }}
                              </span>
                              <span 
                                v-if="delivery.is_backhaul"
                                class="px-2 py-0.5 text-xs font-medium rounded-full bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-200"
                              >
                                Backhaul
                              </span>
                            </div>
                            <div class="mt-1 flex flex-wrap items-center gap-2 text-xs text-gray-500 dark:text-gray-300">
                              <span>
                                Delivery ID: DO-{{ String(delivery.id).padStart(6, '0') }}
                              </span>
                            </div>
                          </div>
                        </td>

                        <!-- Status Column -->
                        <td class="px-6 py-4 whitespace-nowrap">
                          <span :class="getStatusBadgeClass(delivery)" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium">
                            {{ formatStatus(delivery.status) }}
                          </span>
                          
                          <div v-if="delivery.is_backhaul" class="text-xs text-purple-600 dark:text-purple-400 mt-1">
                            Backhaul Delivery
                          </div>
                        </td>

                        <!-- Desktop View - Route Column -->
                        <td class="px-6 py-4 whitespace-nowrap">
                          <div class="text-sm text-gray-900 dark:text-gray-100 font-medium">
                            Route Information
                          </div>
                          <div class="text-xs text-gray-500 dark:text-gray-400 flex items-center gap-1 mt-1">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            <span class="font-medium">Pick Up:</span> 
                            {{ getPickUpRegion(delivery) }}
                          </div>
                          <div class="text-xs text-gray-500 dark:text-gray-400 flex items-center gap-1 mt-1">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            <span class="font-medium">Drop Off:</span> 
                            {{ getDropOffRegion(delivery) }}
                          </div>
                        </td>

                        <!-- Packages Column -->
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                          {{ delivery.package_count || 0 }}
                        </td>

                        <!-- Actions Column - DESKTOP -->
                        <td class="px-6 py-4 whitespace-nowrap">
                          <div class="flex space-x-2 justify-end">
                            <!-- Show Packages Button -->
                            <PrimaryButton
                              @click="toggleShowPackages(delivery.id)"
                              class="!px-3 !py-2 !text-xs flex items-center justify-center gap-1"
                              :title="showPackages[delivery.id] ? 'Hide Packages' : 'Show Packages'"
                            >
                              <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0zm6 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                              </svg>
                              {{ showPackages[delivery.id] ? 'Hide' : 'Show' }} Packages
                            </PrimaryButton>
                          </div>
                        </td>
                      </tr>

                      <!-- Packages Row - Expanded View -->
                      <tr v-if="showPackages[delivery.id] && delivery.packages && delivery.packages.length > 0">
                        <td colspan="5" class="px-6 py-4 bg-gray-50 dark:bg-gray-700">
                          <div class="space-y-3">
                            <h4 class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-2">Packages in this Delivery</h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                              <div 
                                v-for="pkg in delivery.packages" 
                                :key="pkg.id"
                                class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-600 p-3"
                              >
                                <div class="flex justify-between items-start mb-2">
                                  <div>
                                    <div class="flex items-center gap-2 mb-1">
                                      <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                        {{ pkg.item_code }}
                                      </span>
                                    </div>
                                    <div class="text-sm text-gray-900 dark:text-gray-100 font-medium">{{ pkg.item_name }}</div>
                                  </div>
                                  <span v-if="pkg.current_region?.name === pkg.deliveryRequest?.dropOffRegion" class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                    Ready for Update
                                  </span>
                                </div>
                                <div class="space-y-1 text-xs text-gray-600 dark:text-gray-300">
                                  <div><span class="font-medium">Category:</span> {{ pkg.category || 'General' }}</div>
                                  <div><span class="font-medium">Weight:</span> {{ formatWeight(pkg.weight) }}</div>
                                  <div><span class="font-medium">Volume:</span> {{ formatVolume(pkg.volume) }}</div>
                                  <div><span class="font-medium">Current Location:</span> {{ pkg.current_region?.name || 'N/A' }}</div>
                                  <div><span class="font-medium">Destination:</span> {{ pkg.deliveryRequest?.dropOffRegion || 'N/A' }}</div>
                                </div>
                              </div>
                            </div>
                          </div>
                        </td>
                      </tr>
                    </template>
                    
                    <!-- Empty State -->
                    <tr v-if="filteredDeliveries.length === 0">
                      <td colspan="5" class="px-6 py-8 text-center">
                        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-6 max-w-md mx-auto">
                          <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-gray-400 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2a4 4 0 018 0v2M12 7a4 4 0 100 8 4 4 0 000-8z" />
                          </svg>
                          <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-2">No assigned deliveries found</h3>
                          <p class="text-gray-500 dark:text-gray-400 mb-3">
                            {{ search ? 'Try adjusting your search terms' : 'All deliveries have been completed' }}
                          </p>
                        </div>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Single Package Status Update Modal -->
    <Modal :show="showStatusModal" @close="closeStatusModal" max-width="md">
      <div class="p-6">
        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
          Update Package Status
        </h2>
        <PackageStatusForm
          v-if="selectedPackage"
          :package="selectedPackage"
          @submitted="onStatusSubmitted"
          @cancel="closeStatusModal"
        />
      </div>
    </Modal>

    <!-- Bulk Status Update Modal -->
    <Modal :show="showBulkStatusModal" @close="closeBulkStatusModal" max-width="md">
      <div class="p-6">
        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
          Bulk Update Package Status
        </h2>
        <BulkStatusForm
          v-if="selectedPackages.length > 0"
          :package-ids="selectedPackages"
          :packages="selectedPackagesData"
          @submitted="onBulkStatusSubmitted"
          @cancel="closeBulkStatusModal"
        />
      </div>
    </Modal>
  </EmployeeLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import EmployeeLayout from '@/Layouts/EmployeeLayout.vue'
import SearchInput from '@/Components/SearchInput.vue'
import PrimaryButton from '@/Components/PrimaryButton.vue'
import SecondaryButton from '@/Components/SecondaryButton.vue'
import Checkbox from '@/Components/Checkbox.vue'
import Modal from '@/Components/Modal.vue'
import PackageStatusForm from '@/Components/PackageStatusForm.vue'
import BulkStatusForm from '@/Components/BulkStatusForm.vue'

// Props
const props = defineProps({
  deliveries: {
    type: Array,
    default: () => []
  }
})

// Refs
const search = ref('')
const showPackages = ref({})
const selectedPackage = ref(null)
const showStatusModal = ref(false)
const showBulkStatusModal = ref(false)
const selectedPackages = ref([])

// Computed
const filteredDeliveries = computed(() => {
  if (!search.value) {
    return props.deliveries
  }
  
  const searchTerm = search.value.toLowerCase()
  return props.deliveries.filter(delivery => {
    return (
      delivery.reference_number?.toLowerCase().includes(searchTerm) ||
      delivery.packages?.some(pkg => 
        pkg.item_code?.toLowerCase().includes(searchTerm) ||
        pkg.item_name?.toLowerCase().includes(searchTerm) ||
        pkg.current_region?.name?.toLowerCase().includes(searchTerm) ||
        pkg.deliveryRequest?.dropOffRegion?.toLowerCase().includes(searchTerm)
      )
    )
  })
})

const readyForStatusUpdate = computed(() => {
  const packages = []
  props.deliveries.forEach(delivery => {
    if (delivery.packages) {
      delivery.packages.forEach(pkg => {
        if (pkg.current_region?.name === pkg.deliveryRequest?.dropOffRegion) {
          packages.push(pkg)
        }
      })
    }
  })
  return packages
})

const allPackagesSelected = computed(() => {
  return readyForStatusUpdate.value.length > 0 && 
         selectedPackages.value.length === readyForStatusUpdate.value.length
})

// Get the actual package objects for the selected package IDs
const selectedPackagesData = computed(() => {
  return readyForStatusUpdate.value.filter(pkg => 
    selectedPackages.value.includes(pkg.id)
  )
})

// Methods
const toggleShowPackages = (deliveryId) => {
  showPackages.value[deliveryId] = !showPackages.value[deliveryId]
}

const getRowClass = (delivery) => {
  if (delivery.is_backhaul) {
    return 'bg-purple-50 dark:bg-purple-900/10 border-l-4 border-purple-400'
  }
  return ''
}

const getStatusBadgeClass = (delivery) => {
  const status = delivery.status?.toLowerCase()
  switch (status) {
    case 'completed':
      return 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-200'
    case 'in_progress':
      return 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-200'
    case 'pending':
      return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-200'
    case 'cancelled':
      return 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-200'
    default:
      return 'bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-200'
  }
}

const formatStatus = (status) => {
  if (!status) return 'Unknown'
  return status.split('_').map(word => 
    word.charAt(0).toUpperCase() + word.slice(1)
  ).join(' ')
}

const getPickUpRegion = (delivery) => {
  return delivery.pickup_region?.name || 
         delivery.pickUpRegion || 
         delivery.pick_up_region?.name || 
         'N/A'
}

const getDropOffRegion = (delivery) => {
  return delivery.dropoff_region?.name || 
         delivery.dropOffRegion || 
         delivery.drop_off_region?.name || 
         'N/A'
}

const formatWeight = (weight) => {
  if (!weight) return '0 kg'
  return `${parseFloat(weight).toFixed(2)} kg`
}

const formatVolume = (volume) => {
  if (!volume) return '0 m³'
  return `${parseFloat(volume).toFixed(2)} m³`
}

const openStatusModal = (pkg) => {
  selectedPackage.value = pkg
  showStatusModal.value = true
}

const closeStatusModal = () => {
  showStatusModal.value = false
  selectedPackage.value = null
}

const openBulkStatusModal = () => {
  if (selectedPackages.value.length > 0) {
    showBulkStatusModal.value = true
  }
}

const closeBulkStatusModal = () => {
  showBulkStatusModal.value = false
}

const togglePackageSelection = (packageId) => {
  const index = selectedPackages.value.indexOf(packageId)
  if (index > -1) {
    selectedPackages.value.splice(index, 1)
  } else {
    selectedPackages.value.push(packageId)
  }
}

const toggleSelectAll = () => {
  if (allPackagesSelected.value) {
    selectedPackages.value = []
  } else {
    selectedPackages.value = readyForStatusUpdate.value.map(pkg => pkg.id)
  }
}

const clearSelection = () => {
  selectedPackages.value = []
}

const onStatusSubmitted = () => {
  closeStatusModal()
  // Refresh the page to get updated data
  router.reload()
}

const onBulkStatusSubmitted = () => {
  closeBulkStatusModal()
  clearSelection()
  // Refresh the page to get updated data
  router.reload()
}

// Lifecycle
onMounted(() => {
  // Initialize showPackages state
  props.deliveries.forEach(delivery => {
    showPackages.value[delivery.id] = false
  })
})
</script>

<style scoped>
/* Custom scrollbar for better dark mode support */
::-webkit-scrollbar {
  width: 6px;
  height: 6px;
}

::-webkit-scrollbar-track {
  @apply bg-gray-100 dark:bg-gray-700;
}

::-webkit-scrollbar-thumb {
  @apply bg-gray-300 dark:bg-gray-500 rounded-full;
}

::-webkit-scrollbar-thumb:hover {
  @apply bg-gray-400 dark:bg-gray-400;
}
</style>