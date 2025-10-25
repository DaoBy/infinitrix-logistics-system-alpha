<template>
  <EmployeeLayout>
    <template #header>
      <div class="flex flex-wrap justify-between items-center gap-4 px-4 md:px-6 w-full">
        <div>
          <h2 class="text-xl font-semibold leading-tight text-gray-800">
            Cargo Assignment Dashboard
          </h2>
          <p class="text-sm text-gray-600 mt-1">
            Manage delivery assignments and track active deliveries
          </p>

        </div>
        <div class="flex flex-wrap gap-2">
          <SearchInput
            v-model="filters.search"
            placeholder="Search deliveries..."
            @keyup.enter="handleFilterChange"
            @input="handleDebouncedFilter"
            class="w-full md:w-64"
          />
          <SecondaryButton @click="refreshData" :loading="refreshing">
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
            </svg>
            Refresh
          </SecondaryButton>
        </div>
      </div>
    </template>

    <!-- Status Messages -->
    <div v-if="hasFlashMessages" class="px-4 md:px-6 py-2 max-w-screen-xl mx-auto">
      <FlashMessages :flash="flash" />
    </div>

    <div class="py-6 px-2 md:px-6">
      <!-- Loading State -->
      <div v-if="loading" class="flex justify-center items-center py-12">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
      </div>

      <!-- Error State -->
      <div v-else-if="error" class="bg-red-50 border border-red-200 rounded-lg p-6 text-center mb-6 max-w-screen-xl mx-auto">
        <svg class="mx-auto h-12 w-12 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.35 16.5c-.77.833.192 2.5 1.732 2.5z" />
        </svg>
        <h3 class="mt-2 text-sm font-medium text-red-800">Failed to load deliveries</h3>
        <p class="mt-1 text-sm text-red-600">{{ error }}</p>
        <PrimaryButton @click="loadData" class="mt-4">
          Try Again
        </PrimaryButton>
      </div>

      <!-- Main Content -->
      <template v-else>
        <!-- Tabs Navigation -->
        <div class="bg-white shadow-sm rounded-lg border border-gray-200 mb-6 max-w-screen-xl mx-auto">
          <div class="border-b border-gray-200">
            <nav class="-mb-px flex space-x-8 px-6">
              <button
                @click="switchTab('ready')"
                :class="[
                  'py-4 px-1 border-b-2 font-medium text-sm transition-colors duration-200',
                  activeTab === 'ready'
                    ? 'border-blue-500 text-blue-600'
                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                ]"
                :disabled="tabLoading"
              >
                Assignment
              </button>
              <button
                @click="switchTab('assigned')"
                :class="[
                  'py-4 px-1 border-b-2 font-medium text-sm transition-colors duration-200',
                  activeTab === 'assigned'
                    ? 'border-green-500 text-green-600'
                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                ]"
                :disabled="tabLoading"
              >
                Assigned
              </button>
              <button
                @click="switchTab('active')"
                :class="[
                  'py-4 px-1 border-b-2 font-medium text-sm transition-colors duration-200',
                  activeTab === 'active'
                    ? 'border-purple-500 text-purple-600'
                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                ]"
                :disabled="tabLoading"
              >
                Active Deliveries
              </button>
            </nav>
          </div>

          <!-- Filters Section -->
          <div class="p-4 border-b border-gray-200">
            <div class="flex flex-col lg:flex-row gap-4">
              <!-- Search -->
              <div class="flex-1">
                <SearchInput
                  v-model="filters.search"
                  :placeholder="getSearchPlaceholder()"
                  @keyup.enter="handleFilterChange"
                  @input="handleDebouncedFilter"
                  class="w-full"
                  :disabled="tabLoading"
                />
              </div>
              
              <!-- Filter Actions -->
              <div class="flex items-center gap-2">
                <SecondaryButton @click="refreshData" :loading="refreshing" :disabled="tabLoading">
                  <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                  </svg>
                  Refresh
                </SecondaryButton>
                <SecondaryButton @click="resetFilters" :disabled="tabLoading">
                  Reset
                </SecondaryButton>
              </div>
            </div>

            <!-- Advanced Filters -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mt-4">
              <SelectInput
                v-model="filters.region_id"
                :options="regionOptions"
                placeholder="Filter by Region"
                @change="handleFilterChange"
                :disabled="tabLoading"
              />

              <!-- Ready Tab Filters -->
                <SelectInput
    v-if="activeTab === 'ready'"
    v-model="filters.sticker_status"
    :options="stickerStatusOptions"
    placeholder="Sticker Status"
    @change="handleFilterChange"
    :disabled="tabLoading"
  />

              <!-- Assigned Tab Filters -->
             <SelectInput
    v-if="activeTab === 'assigned'"
    v-model="filters.driver_id"
    :options="driverOptions"
    placeholder="Filter by Driver"
    @change="handleFilterChange"
    :disabled="tabLoading"
  />

              
            </div>

            <!-- Filter Info -->
         <div class="flex justify-between items-center mt-4">
  <div class="text-sm text-gray-500">
    Showing {{ deliveries.meta?.from || 0 }} to {{ deliveries.meta?.to || 0 }} of {{ deliveries.meta?.total || 0 }} deliveries
    <span v-if="filters.region_id" class="ml-2">
      in {{ getSelectedRegionName(filters.region_id) }}
    </span>
    <span v-if="filters.sticker_status && activeTab === 'ready'" class="ml-2">
      • {{ getStickerStatusLabel(filters.sticker_status) }}
    </span>
    <!-- Add driver filter display for assigned tab -->
    <span v-if="filters.driver_id && activeTab === 'assigned'" class="ml-2">
      • Driver: {{ getSelectedDriverName(filters.driver_id) }}
    </span>
  </div>
  <div v-if="activeTab === 'ready'" class="text-xs text-gray-600">
    <span class="flex items-center">
      <div class="w-2 h-2 bg-yellow-500 rounded-full mr-1"></div>
      Missing Stickers
    </span>
  </div>
</div>
          </div>
        </div>

        <!-- Tab Loading State -->
        <div v-if="tabLoading" class="flex justify-center items-center py-12 max-w-screen-xl mx-auto">
          <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
          <span class="ml-3 text-gray-600">Loading {{ getTabLabel() }} deliveries...</span>
        </div>

        <!-- Batch Assignment Suggestions for Ready Tab -->
        <div v-else-if="activeTab === 'ready' && effectiveBatchSuggestions.length > 0" class="mb-6 max-w-screen-xl mx-auto">
          <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
            <h3 class="text-lg font-medium text-blue-800 mb-3">Batch Assignment Suggestions</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
              <div 
                v-for="suggestion in effectiveBatchSuggestions" 
                :key="suggestion.destination_region?.id || suggestion.pickup_region?.id"
                class="bg-white border border-blue-200 rounded-lg p-4 hover:shadow-md transition-shadow"
              >
                <div class="flex justify-between items-start mb-3">
                  <div>
                    <p class="font-medium text-gray-900">
                      {{ suggestion.pickup_region?.name ?? 'N/A' }} → {{ suggestion.destination_region?.name ?? 'N/A' }}
                    </p>
                    <p class="text-sm text-gray-500">{{ suggestion.delivery_requests?.length ?? 0 }} deliveries</p>
                  </div>
                  <span 
                    v-if="isBackhaulSuggestion(suggestion)"
                    class="px-2 py-1 rounded-full text-xs font-semibold bg-purple-100 text-purple-800 border border-purple-300"
                  >
                    Backhaul
                  </span>
                </div>
                
                <div class="text-xs text-gray-600 mb-3">
                  <p>Volume: {{ formatVolume(suggestion.total_volume || 0) }}</p>
                  <p>Weight: {{ formatWeight(suggestion.total_weight || 0) }}</p>
                </div>

                <!-- Visual Warnings for Batch Suggestions -->
                <div v-if="hasUnstickerizedPackagesInSuggestion(suggestion)" class="mb-3 p-2 bg-yellow-50 border border-yellow-200 rounded text-xs text-yellow-800">
                  ⚠️ Some packages are missing stickers
                </div>

                <PrimaryButton 
                  @click="prepareBatchAssignment(suggestion)"
                  size="xs"
                  class="w-full"
                  :disabled="!suitableDriverTruckSets(suggestion).length || hasUnstickerizedPackagesInSuggestion(suggestion)"
                >
                  Assign Batch ({{ suitableDriverTruckSets(suggestion).length }} sets available)
                </PrimaryButton>
              </div>
            </div>
          </div>
        </div>

        <!-- Deliveries Table View -->
        <template v-if="!tabLoading">
          <!-- Ready for Assignment Table -->
          <div v-if="activeTab === 'ready'" class="max-w-screen-xl mx-auto">
            <div class="bg-white shadow-sm rounded-lg overflow-hidden border border-gray-200">
              <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                  <thead class="bg-gray-50">
                    <tr>
                      <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        <input
                          type="checkbox"
                          :checked="allSelected"
                          @change="toggleSelectAll"
                          class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                        >
                      </th>
                      <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reference #</th>
                      <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Route</th>
                      <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Packages</th>
                      <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Volume</th>
                      <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Weight</th>
                      <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                      <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created</th>
                      <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                  </thead>
                  <tbody class="bg-white divide-y divide-gray-200">
                    <tr 
                      v-for="delivery in filteredDeliveries" 
                      :key="delivery.id" 
                      class="hover:bg-gray-50"
                      :class="{ 
                        'bg-blue-50': isDeliverySelected(delivery.id),
                        'bg-yellow-50': hasUnstickerizedPackages(delivery)
                      }"
                    >
                      <td class="px-6 py-4 whitespace-nowrap">
                        <input
                          type="checkbox"
                          :checked="isDeliverySelected(delivery.id)"
                          @change="toggleDeliverySelection(delivery)"
                          class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                        >
                      </td>
                      <td class="px-6 py-4 whitespace-nowrap">
                        <div class="flex items-center space-x-2">
                          <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-green-100 text-green-800">
                            Ref
                          </span>
                          <span class="font-bold text-green-700 tracking-wide text-sm">
                            {{ delivery.delivery_request?.reference_number || `DR-${String(delivery.delivery_request_id || '').padStart(6, '0')}` }}
                          </span>
                        </div>
                        <div class="text-xs text-gray-500 mt-1">
                          ID: DO-{{ String(delivery.delivery_request_id || '').padStart(6, '0') }}
                        </div>
                        <div v-if="hasUnstickerizedPackages(delivery)" class="text-xs text-yellow-600 flex items-center mt-1">
                          <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 mr-1" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                          </svg>
                          Missing Stickers
                        </div>
                      </td>
                      <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm text-gray-900">{{ delivery.delivery_request?.pick_up_region?.name ?? 'N/A' }}</div>
                        <div class="text-sm text-gray-500">→ {{ delivery.delivery_request?.drop_off_region?.name ?? 'N/A' }}</div>
                      </td>
                      <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        {{ delivery.delivery_request?.packages?.length || 0 }}
                      </td>
                      <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        {{ formatVolume(calculateTotalVolume(delivery.delivery_request?.packages || [])) }}
                      </td>
                      <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        {{ formatWeight(calculateTotalWeight(delivery.delivery_request?.packages || [])) }}
                      </td>
                   <td class="px-6 py-4 whitespace-nowrap">
  <StatusBadge 
    :status="delivery.status" 
    :variant="getDeliveryStatusVariant(delivery.status)"
  >
    {{ getDeliveryStatusLabel(delivery.status) }}
  </StatusBadge>
</td>
                      <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        {{ formatDate(delivery.created_at) }}
                      </td>
                      <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                        <SecondaryButton 
                          @click="viewDetails(delivery.delivery_request_id)"
                          size="xs"
                        >
                          Details
                        </SecondaryButton>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>

              <!-- Empty State for Ready -->
              <div v-if="filteredDeliveries.length === 0" class="text-center py-12">
                <div class="text-gray-400 mb-4">
                  <svg class="h-12 w-12 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                  </svg>
                </div>
                <p class="text-gray-500">No deliveries ready for assignment</p>
              </div>

              <!-- Pagination for Ready Tab -->
              <div v-if="deliveries.meta && deliveries.meta.last_page > 1" class="bg-white px-4 py-3 flex items-center justify-between border-t border-gray-200 sm:px-6">
                <div class="flex-1 flex justify-between items-center">
                  <div class="text-sm text-gray-700">
                    Page {{ deliveries.meta.current_page }} of {{ deliveries.meta.last_page }}
                  </div>
                  <div class="flex space-x-2">
                    <button
                      @click="handlePageChange(deliveries.meta.current_page - 1)"
                      :disabled="deliveries.meta.current_page <= 1"
                      class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                      Previous
                    </button>
                    <button
                      @click="handlePageChange(deliveries.meta.current_page + 1)"
                      :disabled="deliveries.meta.current_page >= deliveries.meta.last_page"
                      class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                      Next
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Assigned Deliveries Table -->
          <div v-else-if="activeTab === 'assigned'" class="max-w-screen-xl mx-auto">
            <div class="bg-white shadow-sm rounded-lg overflow-hidden border border-gray-200">
              <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                  <thead class="bg-gray-50">
                    <tr>
                      <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reference #</th>
                      <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Route</th>
                      <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Assigned To</th>
                      <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Truck</th>
                      <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Manifest Status</th>
                      <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Assigned At</th>
                      <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                  </thead>
                  <tbody class="bg-white divide-y divide-gray-200">
                    <tr 
                      v-for="delivery in processedAssignedDeliveries" 
                      :key="delivery.id" 
                      class="hover:bg-gray-50"
                      :class="{ 'bg-blue-50': delivery.driver_truck_assignment?.has_finalized_manifest }"
                    >
                      <td class="px-6 py-4 whitespace-nowrap">
                        <div class="flex items-center space-x-2">
                          <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-green-100 text-green-800">
                            Ref
                          </span>
                          <span class="font-bold text-green-700 tracking-wide text-sm">
                            {{ delivery.delivery_request?.reference_number || `DR-${String(delivery.delivery_request_id || '').padStart(6, '0')}` }}
                          </span>
                        </div>
                        <div class="text-xs text-gray-500 mt-1">
                          ID: DO-{{ String(delivery.id || '').padStart(6, '0') }}
                        </div>
                      </td>
                      <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm text-gray-900">{{ delivery.delivery_request?.pick_up_region?.name ?? 'N/A' }}</div>
                        <div class="text-sm text-gray-500">→ {{ delivery.delivery_request?.drop_off_region?.name ?? 'N/A' }}</div>
                      </td>
                      <td class="px-6 py-4 whitespace-nowrap">
                        <div class="flex items-center">
                          <div v-if="delivery.driver_truck_assignment?.driver" class="flex-shrink-0 h-8 w-8 bg-green-100 rounded-full flex items-center justify-center">
                            <span class="text-green-600 font-medium text-xs">
                              {{ getInitials(delivery.driver_truck_assignment.driver.name) }}
                            </span>
                          </div>
                          <div v-else class="flex-shrink-0 h-8 w-8 bg-gray-100 rounded-full flex items-center justify-center">
                            <span class="text-gray-400 font-medium text-xs">??</span>
                          </div>
                          <div class="ml-3">
                            <div class="text-sm font-medium text-gray-900">{{ delivery.driver_truck_assignment?.driver?.name ?? 'N/A' }}</div>
                          </div>
                        </div>
                      </td>
                      <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        <div>{{ delivery.driver_truck_assignment?.truck?.license_plate ?? 'N/A' }}</div>
                        <div class="text-xs text-gray-500">
                          {{ delivery.driver_truck_assignment?.truck ? `${delivery.driver_truck_assignment.truck.make} ${delivery.driver_truck_assignment.truck.model}` : 'N/A' }}
                        </div>
                      </td>
                      <td class="px-6 py-4 whitespace-nowrap">
                        <span 
                          v-if="delivery.driver_truck_assignment?.has_finalized_manifest"
                          class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-blue-100 text-blue-800 border border-blue-300"
                        >
                          <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                          </svg>
                          Finalized
                        </span>
                        <span 
                          v-else
                          class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-gray-100 text-gray-800 border border-gray-300"
                        >
                          <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                          </svg>
                          Not Finalized
                        </span>
                      </td>
                      <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        {{ delivery.driver_truck_assignment?.assigned_at ? formatDateTime(delivery.driver_truck_assignment.assigned_at) : 'N/A' }}
                      </td>
                      <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                        <div class="flex justify-end space-x-2">
                          <SecondaryButton 
                            @click="viewDetails(delivery.delivery_request_id)"
                            size="xs"
                          >
                            Details
                          </SecondaryButton>
                          <DangerButton 
                            v-if="!delivery.driver_truck_assignment?.has_finalized_manifest"
                            @click="confirmCancel(delivery)"
                            size="xs"
                            title="Cancel Assignment"
                          >
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" viewBox="0 0 20 20" fill="currentColor">
                              <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd"/>
                            </svg>
                          </DangerButton>
                          <span 
                            v-else
                            class="inline-flex items-center px-2 py-1 text-xs text-gray-400 bg-gray-100 rounded border border-gray-300"
                            title="Cannot cancel - Manifest finalized"
                          >
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3 mr-1" viewBox="0 0 20 20" fill="currentColor">
                              <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd" />
                            </svg>
                            Locked
                          </span>
                        </div>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>

              <!-- Empty State for Assigned -->
              <div v-if="filteredDeliveries.length === 0" class="text-center py-12">
                <div class="text-gray-400 mb-4">
                  <svg class="h-12 w-12 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                  </svg>
                </div>
                <p class="text-gray-500">No assigned deliveries found</p>
              </div>

              <!-- Pagination for Assigned Tab -->
              <div v-if="deliveries.meta && deliveries.meta.last_page > 1" class="bg-white px-4 py-3 flex items-center justify-between border-t border-gray-200 sm:px-6">
                <div class="flex-1 flex justify-between items-center">
                  <div class="text-sm text-gray-700">
                    Page {{ deliveries.meta.current_page }} of {{ deliveries.meta.last_page }}
                  </div>
                  <div class="flex space-x-2">
                    <button
                      @click="handlePageChange(deliveries.meta.current_page - 1)"
                      :disabled="deliveries.meta.current_page <= 1"
                      class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                      Previous
                    </button>
                    <button
                      @click="handlePageChange(deliveries.meta.current_page + 1)"
                      :disabled="deliveries.meta.current_page >= deliveries.meta.last_page"
                      class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                      Next
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Active Deliveries Table -->
          <div v-else-if="activeTab === 'active'" class="max-w-screen-xl mx-auto">
            <div class="bg-white shadow-sm rounded-lg overflow-hidden border border-gray-200">
              <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                  <thead class="bg-gray-50">
                    <tr>
                      <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reference #</th>
                      <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Route</th>
                      <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Driver</th>
                      <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Truck</th>
                      <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Dispatched</th>
                      <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Est. Arrival</th>
                      <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                  </thead>
                  <tbody class="bg-white divide-y divide-gray-200">
                    <tr v-for="delivery in filteredDeliveries" :key="delivery.id" class="hover:bg-gray-50">
                      <td class="px-6 py-4 whitespace-nowrap">
                        <div class="flex items-center space-x-2">
                          <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-green-100 text-green-800">
                            Ref
                          </span>
                          <span class="font-bold text-green-700 tracking-wide text-sm">
                            {{ delivery.delivery_request?.reference_number || `DR-${String(delivery.delivery_request_id || '').padStart(6, '0')}` }}
                          </span>
                        </div>
                        <div class="text-xs text-gray-500 mt-1">
                          ID: DO-{{ String(delivery.delivery_request_id || '').padStart(6, '0') }}
                        </div>
                      </td>
                      <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm text-gray-900">{{ delivery.delivery_request?.pick_up_region?.name ?? 'N/A' }}</div>
                        <div class="text-sm text-gray-500">→ {{ delivery.delivery_request?.drop_off_region?.name ?? 'N/A' }}</div>
                      </td>
                      <td class="px-6 py-4 whitespace-nowrap">
                        <div class="flex items-center">
                          <div class="flex-shrink-0 h-8 w-8 bg-purple-100 rounded-full flex items-center justify-center">
                            <span class="text-purple-600 font-medium text-xs">
                              {{ getInitials(delivery.driver_truck_assignment?.driver?.name) }}
                            </span>
                          </div>
                          <div class="ml-3">
                            <div class="text-sm font-medium text-gray-900">{{ delivery.driver_truck_assignment?.driver?.name ?? 'N/A' }}</div>
                            <div class="text-xs text-gray-500">{{ delivery.driver_truck_assignment?.driver?.employee_id ?? 'N/A' }}</div>
                          </div>
                        </div>
                      </td>
                      <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        <div>{{ delivery.driver_truck_assignment?.truck?.license_plate ?? 'N/A' }}</div>
                        <div class="text-xs text-gray-500">
                          {{ delivery.driver_truck_assignment?.truck ? `${delivery.driver_truck_assignment.truck.make} ${delivery.driver_truck_assignment.truck.model}` : '' }}
                        </div>
                      </td>
                      <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        {{ formatDateTime(delivery.dispatched_at) }}
                      </td>
                      <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        {{ formatDateTime(delivery.estimated_arrival) }}
                      </td>
                      <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                        <SecondaryButton 
                          @click="viewDetails(delivery.delivery_request_id)"
                          size="xs"
                        >
                          Track
                        </SecondaryButton>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>

              <!-- Empty State for Active -->
              <div v-if="filteredDeliveries.length === 0" class="text-center py-12">
                <div class="text-gray-400 mb-4">
                  <svg class="h-12 w-12 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                  </svg>
                </div>
                <p class="text-gray-500">No active deliveries found</p>
              </div>

              <!-- Pagination for Active Tab -->
              <div v-if="deliveries.meta && deliveries.meta.last_page > 1" class="bg-white px-4 py-3 flex items-center justify-between border-t border-gray-200 sm:px-6">
                <div class="flex-1 flex justify-between items-center">
                  <div class="text-sm text-gray-700">
                    Page {{ deliveries.meta.current_page }} of {{ deliveries.meta.last_page }}
                  </div>
                  <div class="flex space-x-2">
                    <button
                      @click="handlePageChange(deliveries.meta.current_page - 1)"
                      :disabled="deliveries.meta.current_page <= 1"
                      class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                      Previous
                    </button>
                    <button
                      @click="handlePageChange(deliveries.meta.current_page + 1)"
                      :disabled="deliveries.meta.current_page >= deliveries.meta.last_page"
                      class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                      Next
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </template>

        <!-- Selected Deliveries & Driver-Truck Sets - Always Visible -->
        <div class="mt-8 max-w-screen-xl mx-auto">
          <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Selected Deliveries Panel -->
            <div class="lg:col-span-1">
              <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
                <div class="flex justify-between items-center mb-4">
                  <h3 class="text-lg font-medium text-gray-900">
                    Selected Deliveries ({{ selectedDeliveries.length }})
                  </h3>
                  <button 
                    v-if="selectedDeliveries.length > 0"
                    @click="clearSelection"
                    class="text-sm text-red-500 hover:text-red-700"
                  >
                    Clear All
                  </button>
                </div>

                <div class="space-y-3 max-h-96 overflow-y-auto">
                  <template v-if="selectedDeliveries.length > 0">
                    <div 
                      v-for="delivery in selectedDeliveries" 
                      :key="delivery.id"
                      class="p-3 border border-gray-200 rounded-lg hover:bg-gray-50"
                      :class="{
                        'bg-yellow-50 border-yellow-200': hasUnstickerizedPackages(delivery),
                        'bg-red-50 border-red-200': hasRegionMismatch(delivery)
                      }"
                    >
                      <div class="flex justify-between items-start">
                        <div class="flex-1">
                          <div class="flex items-center space-x-2 mb-2">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-green-100 text-green-800">
                              Ref
                            </span>
                            <span class="font-bold text-green-700 tracking-wide text-sm">
                              {{ delivery.delivery_request?.reference_number || `DR-${delivery.delivery_request_id?.toString()?.padStart(6, '0')}` }}
                            </span>
                          </div>
                          <p class="text-xs text-gray-500">
                            {{ delivery.delivery_request?.pick_up_region?.name }} → 
                            {{ delivery.delivery_request?.drop_off_region?.name }}
                          </p>
                          <div class="mt-1 text-xs text-gray-600">
                            <p>Vol: {{ formatVolume(calculateTotalVolume(delivery.delivery_request?.packages || [])) }}</p>
                            <p>Wt: {{ formatWeight(calculateTotalWeight(delivery.delivery_request?.packages || [])) }}</p>
                          </div>
                          
                          <!-- Visual Error Indicators -->
                          <div v-if="hasUnstickerizedPackages(delivery)" class="mt-1 p-1 bg-yellow-100 border border-yellow-300 rounded text-xs text-yellow-800">
                            ⚠️ Missing Stickers
                          </div>
                          <div v-if="hasRegionMismatch(delivery)" class="mt-1 p-1 bg-red-100 border border-red-300 rounded text-xs text-red-800">
                            ⚠️ Region Mismatch
                          </div>
                        </div>
                        <button 
                          @click="removeDelivery(delivery.id)"
                          class="text-red-500 hover:text-red-700 ml-2"
                        >
                          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                          </svg>
                        </button>
                      </div>
                    </div>
                  </template>
                  <div v-else class="text-center text-gray-500 py-8">
                    <svg class="h-8 w-8 mx-auto mb-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    <p class="text-sm">No deliveries selected</p>
                  </div>
                </div>

                <div v-if="selectedDeliveries.length > 0" class="mt-4 pt-4 border-t border-gray-200">
                  <div class="text-sm text-gray-600 space-y-1">
                    <p><strong>Total Selected:</strong></p>
                    <p>Volume: {{ formatVolume(totalSelectedVolume) }}</p>
                    <p>Weight: {{ formatWeight(totalSelectedWeight) }}</p>
                    
                    <!-- Visual Error Summary -->
                    <div v-if="hasAnyUnstickerizedPackages" class="p-2 bg-yellow-50 border border-yellow-200 rounded text-xs text-yellow-800 mt-2">
                      ⚠️ Some packages are missing stickers
                    </div>
                    <div v-if="hasMixedAssignmentTypes" class="p-2 bg-red-50 border border-red-200 rounded text-xs text-red-800 mt-2">
                      ⚠️ Mixed assignment types (Regular + Backhaul)
                    </div>
                    <div v-if="hasAnyRegionMismatch" class="p-2 bg-red-50 border border-red-200 rounded text-xs text-red-800 mt-2">
                      ⚠️ Region mismatch in assignments
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Available Driver-Truck Sets -->
            <div class="lg:col-span-2">
              <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
                <div class="flex justify-between items-center mb-4">
                  <h3 class="text-lg font-medium text-gray-900">
                    Available Driver-Truck Sets
                  </h3>
                  <div class="flex space-x-2">
                    <button 
                      v-for="type in assignmentTypeOptions" 
                      :key="type.value"
                      @click="setAssignmentTypeFilter(type.value)"
                      class="px-3 py-1 text-xs rounded border"
                      :class="assignmentTypeFilter === type.value 
                        ? 'bg-blue-100 text-blue-800 border-blue-300' 
                        : 'bg-gray-100 text-gray-600 border-gray-300'"
                    >
                      {{ type.label }}
                    </button>
                  </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div 
                    v-for="set in filteredDriverTruckSets" 
                    :key="set.id"
                    :class="[
                      'border rounded-lg p-4 hover:shadow-md transition-shadow cursor-pointer',
                      selectedSet?.id === set.id
                        ? 'border-blue-500 bg-blue-50 shadow-md'
                        : 'border-gray-200 bg-white'
                    ]"
                    @click="selectSet(set)"
                  >
                    <!-- Header with Driver & Status -->
                    <div class="flex justify-between items-start mb-3">
                      <div class="flex items-center">
                        <div class="flex-shrink-0 h-10 w-10 rounded-full flex items-center justify-center"
                             :class="set.available_for_backhaul ? 'bg-purple-100' : 'bg-gray-100'">
                          <span class="text-sm font-medium" 
                                :class="set.available_for_backhaul ? 'text-purple-800' : 'text-gray-600'">
                            {{ set.driver?.initials || getInitials(set.driver?.name) }}
                          </span>
                        </div>
                        <div class="ml-3">
                          <div class="text-sm font-medium text-gray-900">{{ set.driver?.name || 'Driver' }}</div>
                          
                          <!-- Manifest Status Badge -->
                          <div v-if="set.has_finalized_manifest" class="mt-1">
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800 border border-blue-300">
                              <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                              </svg>
                              Manifest Finalized
                            </span>
                          </div>
                          
                          <!-- Dispatch Status Badge -->
                          <div v-else-if="hasAssignedOrders(set)" class="mt-1">
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-yellow-100 text-yellow-800">
                              <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                              </svg>
                              {{ getAssignedOrdersCount(set) }} assigned
                            </span>
                          </div>
                        </div>
                      </div>
                      <div class="flex flex-col items-end space-y-1">
                        <span
                          v-if="set.available_for_backhaul"
                          class="px-2 py-1 rounded-full text-xs font-semibold bg-purple-100 text-purple-800 border border-purple-300"
                        >
                          Backhaul
                        </span>
                        <!-- Dispatch Button -->
                        <PrimaryButton
                          v-if="hasAssignedOrders(set)"
                          @click.stop="openDispatchModal(set)"
                          size="xs"
                          class="mt-1"
                        >
                          <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                          </svg>
                          Dispatch
                        </PrimaryButton>
                      </div>
                    </div>

                    <!-- Truck Info -->
                    <div class="mb-3">
                      <div class="text-sm font-medium text-gray-900">{{ set.truck?.license_plate || 'Truck' }}</div>
                      <div class="text-xs text-gray-500">{{ set.truck?.make }} {{ set.truck?.model }}</div>
                    </div>

                    <!-- Region Info -->
                    <div class="grid grid-cols-2 gap-2 text-sm mb-3">
                      <div>
                        <div class="text-gray-500 text-xs">Home Region</div>
                        <div class="font-medium">{{ set.region?.name || 'N/A' }}</div>
                      </div>
                      <div>
                        <div class="text-gray-500 text-xs">Current Region</div>
                        <div :class="set.available_for_backhaul ? 'text-purple-600 font-medium' : 'font-medium'">
                          {{ set.current_region?.name || set.region?.name || 'N/A' }}
                        </div>
                      </div>
                    </div>

                    <!-- Capacity - Volume & Weight -->
                    <div class="mb-4 space-y-3">
                      <!-- Volume Capacity -->
                      <div>
                        <div class="flex justify-between text-xs text-gray-600 mb-1">
                          <span>Volume Capacity</span>
                          <span>{{ getVolumePercentage(set) }}%</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                          <div 
                            class="h-2 rounded-full" 
                            :style="{ width: `${getVolumePercentage(set)}%` }"
                            :class="{
                              'bg-green-500': getVolumePercentage(set) <= 60,
                              'bg-yellow-500': getVolumePercentage(set) > 60 && getVolumePercentage(set) <= 85,
                              'bg-red-500': getVolumePercentage(set) > 85
                            }"
                          ></div>
                        </div>
                        <div class="text-xs text-gray-500 mt-1 text-right">
                          {{ formatVolume(set.current_volume || 0) }} / {{ formatVolume(set.truck?.volume_capacity || 0) }}
                        </div>
                      </div>

                      <!-- Weight Capacity -->
                      <div>
                        <div class="flex justify-between text-xs text-gray-600 mb-1">
                          <span>Weight Capacity</span>
                          <span>{{ getWeightPercentage(set) }}%</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                          <div 
                            class="h-2 rounded-full" 
                            :style="{ width: `${getWeightPercentage(set)}%` }"
                            :class="{
                              'bg-green-500': getWeightPercentage(set) <= 60,
                              'bg-yellow-500': getWeightPercentage(set) > 60 && getWeightPercentage(set) <= 85,
                              'bg-red-500': getWeightPercentage(set) > 85
                            }"
                          ></div>
                        </div>
                        <div class="text-xs text-gray-500 mt-1 text-right">
                          {{ formatWeight(set.current_weight || 0) }} / {{ formatWeight(set.truck?.weight_capacity || 0) }}
                        </div>
                      </div>
                    </div>

                    <!-- Available Capacity -->
                    <div class="text-xs text-gray-600 mb-3 space-y-1">
                      <div>Available Volume: {{ formatVolume(set.available_volume || 0) }}</div>
                      <div>Available Weight: {{ formatWeight(set.available_weight || 0) }}</div>
                    </div>

                    <!-- Assign Button -->
                    <PrimaryButton
                      @click.stop="openAssignmentModal(set)"
                      class="w-full"
                      :disabled="!canAssignToSet(set) || selectedDeliveries.length === 0 || set.has_finalized_manifest"
                    >
                      <template v-if="set.has_finalized_manifest">
                        <svg class="w-4 h-4 mr-1 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                        </svg>
                        Manifest Finalized - Cannot Assign
                      </template>
                      <template v-else-if="!canAssignToSet(set) && selectedDeliveries.length > 0">
                        Cannot Assign - Capacity Issues
                      </template>
                      <template v-else-if="selectedDeliveries.length === 0">
                        Assign Selected (0)
                      </template>
                      <template v-else>
                        Assign Selected ({{ selectedDeliveries.length }})
                      </template>
                    </PrimaryButton>
                    
                    <!-- Helper Text -->
                    <div v-if="set.has_finalized_manifest" class="mt-2 text-center">
                      <p class="text-xs text-blue-600">Ready for dispatch</p>
                    </div>
                  </div>
                </div>

                <!-- Empty State for Driver-Truck Sets -->
                <div v-if="filteredDriverTruckSets.length === 0" class="text-center py-8">
                  <div class="text-gray-400 mb-4">
                    <TruckIcon class="h-12 w-12 mx-auto" />
                  </div>
                  <p class="text-gray-500">No available driver-truck sets found</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </template>
    </div>

    <!-- Assignment Confirmation Modal -->
    <ConfirmationModal 
      :show="showAssignmentModal"
      @close="closeAssignmentModal"
      @confirmed="submitAssignment"
      title="Confirm Batch Assignment"
      confirm-text="Assign Deliveries"
      :confirm-disabled="!validationSummary.isValid"
      variant="primary"
      size="lg"
    >
      <div class="space-y-4">
        <!-- Validation Summary -->
        <div class="p-3 rounded-lg border" :class="{
          'border-green-200 bg-green-50 text-green-800': validationSummary.isValid,
          'border-red-200 bg-red-50 text-red-800': !validationSummary.isValid
        }">
          <div class="flex items-start">
            <div class="flex-shrink-0 mt-0.5">
              <svg v-if="validationSummary.isValid" class="h-5 w-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
              <svg v-else class="h-5 w-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z" />
              </svg>
            </div>
            <div class="ml-3 flex-1">
              <p class="font-medium text-sm">{{ validationSummary.message }}</p>
              <ul v-if="validationSummary.details && validationSummary.details.length > 0" class="mt-1 text-xs space-y-1">
                <li v-for="detail in validationSummary.details" :key="detail" class="flex items-start">
                  <span class="mr-2">•</span>
                  <span>{{ detail }}</span>
                </li>
              </ul>
            </div>
          </div>
        </div>

        <!-- Assignment Details Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <!-- Capacity Summary -->
          <div class="bg-gray-50 rounded-lg p-3 border border-gray-200">
            <h3 class="text-sm font-medium text-gray-900 mb-2 flex items-center">
              <svg class="h-4 w-4 mr-1.5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
              </svg>
              Capacity Check
            </h3>
            <div class="space-y-2 text-xs">
              <div class="flex justify-between">
                <span class="text-gray-600">Volume:</span>
                <span class="font-medium">{{ formatVolume(totalSelectedVolume) }}</span>
              </div>
              <div class="flex justify-between">
                <span class="text-gray-600">Available:</span>
                <span :class="totalSelectedVolume > validationSummary.availableVolume ? 'text-red-600' : 'text-green-600'">
                  {{ formatVolume(validationSummary.availableVolume || 0) }}
                </span>
              </div>
              <div class="border-t pt-2 mt-2"></div>
              <div class="flex justify-between">
                <span class="text-gray-600">Weight:</span>
                <span class="font-medium">{{ formatWeight(totalSelectedWeight) }}</span>
              </div>
              <div class="flex justify-between">
                <span class="text-gray-600">Available:</span>
                <span :class="totalSelectedWeight > validationSummary.availableWeight ? 'text-red-600' : 'text-green-600'">
                  {{ formatWeight(validationSummary.availableWeight || 0) }}
                </span>
              </div>
            </div>
          </div>

          <!-- Driver-Truck Set -->
          <div class="bg-gray-50 rounded-lg p-3 border border-gray-200">
            <h3 class="text-sm font-medium text-gray-900 mb-2 flex items-center">
              <svg class="h-4 w-4 mr-1.5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z" />
              </svg>
              Driver-Truck Set
            </h3>
            <div class="flex items-center space-x-3">
              <!-- Consistent with Available Driver-Truck Sets -->
              <div class="flex-shrink-0 h-10 w-10 rounded-full flex items-center justify-center"
                   :class="selectedSet?.available_for_backhaul ? 'bg-purple-100' : 'bg-gray-100'">
                <span class="text-sm font-medium" 
                      :class="selectedSet?.available_for_backhaul ? 'text-purple-800' : 'text-gray-600'">
                  {{ selectedSet?.driver?.initials || getInitials(selectedSet?.driver?.name) }}
                </span>
              </div>
              <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-gray-900 truncate">{{ selectedSet?.driver?.name ?? 'N/A' }}</p>
                <p class="text-xs text-gray-500 truncate">{{ selectedSet?.truck?.license_plate ?? 'N/A' }}</p>
                <div class="flex items-center space-x-2 mt-1">
                  <span class="text-xs text-gray-500">{{ selectedSet?.truck?.make }} {{ selectedSet?.truck?.model }}</span>
                  <span v-if="selectedSet?.available_for_backhaul"
                        class="px-1.5 py-0.5 rounded-full text-xs font-semibold bg-purple-100 text-purple-800 border border-purple-300">
                    Backhaul
                  </span>
                </div>
                <div class="mt-1">
                  <span class="text-xs px-1.5 py-0.5 rounded" 
                        :class="getCapacityStatusClass(selectedSet)">
                    {{ getCapacityPercentage(selectedSet) }}% capacity
                  </span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Selected Deliveries -->
        <div class="border rounded-lg">
          <div class="bg-gray-50 px-3 py-2 border-b">
            <h3 class="text-sm font-medium text-gray-900 flex items-center">
              <svg class="h-4 w-4 mr-1.5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
              </svg>
              Selected Deliveries ({{ selectedDeliveries.length }})
            </h3>
          </div>
          <div class="max-h-40 overflow-y-auto">
            <div v-for="delivery in selectedDeliveries" :key="delivery.id" 
                 class="px-3 py-2 border-b last:border-b-0 hover:bg-gray-50">
              <div class="flex justify-between items-start">
                <div class="flex-1 min-w-0">
                  <div class="flex items-center space-x-2">
                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-semibold bg-green-100 text-green-800">
                      Ref
                    </span>
                    <span class="font-medium text-sm text-gray-900 truncate">
                      {{ delivery.delivery_request?.reference_number || `DR-${String(delivery.delivery_request_id || '').padStart(6, '0')}` }}
                    </span>
                  </div>
                  <p class="text-xs text-gray-500 mt-0.5 truncate">
                    {{ delivery.delivery_request?.pick_up_region?.name ?? 'N/A' }} → 
                    {{ delivery.delivery_request?.drop_region?.name ?? 'N/A' }}
                  </p>
                  <div class="flex items-center space-x-3 mt-1 text-xs text-gray-400">
                    <span>Vol: {{ formatVolume(calculateTotalVolume(delivery.delivery_request?.packages || [])) }}</span>
                    <span>Wt: {{ formatWeight(calculateTotalWeight(delivery.delivery_request?.packages || [])) }}</span>
                  </div>
                </div>
                <div class="flex items-center space-x-1 ml-2">
                  <span v-if="hasUnstickerizedPackages(delivery)" 
                        class="inline-flex items-center px-1.5 py-0.5 rounded text-xs bg-yellow-100 text-yellow-800"
                        title="Missing stickers">
                    ⚠️
                  </span>
                  <span v-if="hasRegionMismatch(delivery)"
                        class="inline-flex items-center px-1.5 py-0.5 rounded text-xs bg-red-100 text-red-800"
                        title="Region mismatch">
                    ❌
                  </span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Estimated Timeline -->
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-3">
          <h3 class="text-sm font-medium text-blue-900 mb-2 flex items-center">
            <svg class="h-4 w-4 mr-1.5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            Estimated Timeline
          </h3>
          <div class="text-xs text-blue-700 space-y-1">
            <p>• Departure: Within 1 hour of assignment</p>
            <p>• Estimated arrival: Calculated based on route distance</p>
            <p>• Delivery completion: Within 24-48 hours</p>
          </div>
        </div>
      </div>
    </ConfirmationModal>

    <!-- Cancel Delivery Modal -->
    <Modal :show="!!deliveryToCancel" @close="cancelDeliveryToCancel" max-width="md">
      <div class="p-6">
        <h2 class="text-lg font-medium text-gray-900">Cancel Delivery Assignment</h2>
        <p class="mt-1 text-sm text-gray-600">
          Are you sure you want to cancel this delivery assignment?
        </p>
        
        <!-- Delivery Information -->
        <div class="mt-4 bg-red-50 border border-red-200 rounded-lg p-4">
          <div class="flex items-start">
            <div class="flex-shrink-0">
              <svg class="h-5 w-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z" />
              </svg>
            </div>
            <div class="ml-3">
              <h3 class="text-sm font-medium text-red-800">Delivery Information</h3>
              <div class="mt-2 text-sm text-red-700 space-y-1">
                <p><strong>Reference:</strong> {{ deliveryToCancel?.delivery_request?.reference_number || `DR-${String(deliveryToCancel?.delivery_request_id || '').padStart(6, '0')}` }}</p>
                <p><strong>Route:</strong> {{ deliveryToCancel?.delivery_request?.pick_up_region?.name }} → {{ deliveryToCancel?.delivery_request?.drop_off_region?.name }}</p>
                <p><strong>Driver:</strong> {{ deliveryToCancel?.driver_truck_assignment?.driver?.name || 'N/A' }}</p>
                <p><strong>Truck:</strong> {{ deliveryToCancel?.driver_truck_assignment?.truck?.license_plate || 'N/A' }}</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Reason Input -->
        <div class="mt-4">
          <label for="cancellation_reason" class="block text-sm font-medium text-gray-700">Reason for Cancellation</label>
          <textarea
            id="cancellation_reason"
            v-model="cancellationReason"
            rows="3"
            class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
            placeholder="Enter reason for cancellation..."
            required
          ></textarea>
          <p class="mt-1 text-xs text-gray-500">This reason will be recorded in the delivery history.</p>
        </div>

        <div class="mt-6 flex justify-end space-x-4">
          <SecondaryButton @click="cancelDeliveryToCancel">
            Cancel
          </SecondaryButton>
          <DangerButton 
            @click="cancelDelivery" 
            :disabled="!cancellationReason.trim()"
            :loading="loading"
          >
            Confirm Cancellation
          </DangerButton>
        </div>
      </div>
    </Modal>

    <!-- Dispatch Modal -->
    <DispatchModal 
      :show="showDispatchModal"
      :set="dispatchSet"
      @close="closeDispatchModal"
      @dispatched="handleDispatched"
    />

  </EmployeeLayout>
</template>

<script setup>
import { ref, reactive, computed, onMounted, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import EmployeeLayout from '@/Layouts/EmployeeLayout.vue'
import PrimaryButton from '@/Components/PrimaryButton.vue'
import SecondaryButton from '@/Components/SecondaryButton.vue'
import DangerButton from '@/Components/DangerButton.vue'
import SelectInput from '@/Components/SelectInput.vue'
import SearchInput from '@/Components/SearchInput.vue'
import FlashMessages from '@/Components/FlashMessages.vue'
import ConfirmationModal from '@/Components/ConfirmationModal.vue'
import DispatchModal from '@/Components/DispatchModal.vue'
import Modal from '@/Components/Modal.vue'
import StatusBadge from '@/Components/StatusBadge.vue'
import { TruckIcon } from '@heroicons/vue/24/outline'

// Props
const props = defineProps({
  deliveries: Object,
  driverTruckSets: {
    type: Array,
    default: () => []
  },
  batchSuggestions: {
    type: Array,
    default: () => []
  },
  regions: {
    type: Array,
    default: () => []
  },
  flash: Object,
  filters: Object
})

// Reactive state
const loading = ref(false)
const error = ref(null)
const refreshing = ref(false)
const showAssignmentModal = ref(false)
const deliveryToCancel = ref(null)
const cancellationReason = ref('')
const tabLoading = ref(false)
const showDispatchModal = ref(false)
const dispatchSet = ref(null)

// Use the activeTab from props or default to 'ready'
const activeTab = ref(props.filters?.activeTab || 'ready')

// Selection state
const selectedDeliveries = ref([])
const selectedSet = ref(null)
const assignmentTypeFilter = ref('all')

// Initialize filters from props
const filters = reactive({
  search: props.filters?.search || '',
  region_id: props.filters?.region_id || '',
  driver_id: props.filters?.driver_id || '',
  sticker_status: props.filters?.sticker_status || '',
  status: props.filters?.status || ''
})

// Computed
const regionOptions = computed(() => [
  { value: '', label: 'All Regions' },
  ...(props.regions?.map(region => ({
    value: region.id,
    label: region.name
  })) || [])
])

const stickerStatusOptions = computed(() => [
  { value: '', label: 'All Sticker Status' },
  { value: 'with_stickers', label: 'With Stickers' },
  { value: 'missing_stickers', label: 'Missing Stickers' }
])



const driverOptions = computed(() => {
  // Get unique drivers from ALL assigned deliveries, not just filtered ones
  const drivers = new Map();
  
  if (activeTab.value === 'assigned' && deliveries.value.data) {
    deliveries.value.data.forEach(delivery => {
      const driver = delivery.driver_truck_assignment?.driver;
      if (driver && driver.id) {
        // Use driver name as the label, not employee ID
        drivers.set(driver.id, {
          value: driver.id,
          label: driver.name, // Just show the driver name
          name: driver.name,
          employee_id: driver.employee_id || null
        });
      }
    });
  }
  
  return [
    { value: '', label: 'All Drivers' },
    ...Array.from(drivers.values())
  ];
})

const assignmentTypeOptions = computed(() => [
  { value: 'all', label: 'All Types' },
  { value: 'regular', label: 'Regular' },
  { value: 'backhaul', label: 'Backhaul' }
])

const hasFlashMessages = computed(() => {
  return props.flash?.success || props.flash?.error || props.flash?.warning
})

const deliveries = computed(() => props.deliveries || { data: [], meta: {} })
const allSelected = computed(() => {
  return filteredDeliveries.value.length > 0 && 
         filteredDeliveries.value.every(delivery => isDeliverySelected(delivery.id))
})

// SIMPLIFIED - Just return the backend-paginated data
const filteredDeliveries = computed(() => {
  return deliveries.value.data || []
})

const driverTruckSets = computed(() => {
  if (Array.isArray(props.driverTruckSets)) {
    return props.driverTruckSets
  }
  return []
})

const batchSuggestions = computed(() => {
  if (Array.isArray(props.batchSuggestions)) {
    return props.batchSuggestions
  }
  return []
})

const effectiveBatchSuggestions = computed(() => {
  return batchSuggestions.value.filter(suggestion => {
    // Only include suggestions where all deliveries are ready (not pending_payment)
    return suggestion.delivery_requests?.every(delivery => 
      delivery.status === 'ready'
    ) ?? false;
  });
})

const filteredDriverTruckSets = computed(() => {
  const sets = driverTruckSets.value
  if (!Array.isArray(sets) || sets.length === 0) {
    return []
  }
  
  return sets.map(set => {
    const driver = set.driver || {}
    const truck = set.truck || {}
    
    let initials = driver.initials || ''
    if (!initials && driver.name) {
      initials = getInitials(driver.name)
    }
    
    return {
      ...set,
      driver: {
        ...driver,
        name: driver.name || 'Driver',
        initials: initials,
        delivery_orders_count: driver.delivery_orders_count ?? driver.current_assignments ?? 0,
        current_assignments: driver.current_assignments ?? driver.delivery_orders_count ?? 0,
        canAcceptNewAssignment: driver.canAcceptNewAssignment ?? driver.available ?? false,
        available: driver.available ?? true
      },
      truck: {
        ...truck,
        volume_capacity: truck.volume_capacity ?? 0,
        weight_capacity: truck.weight_capacity ?? 0,
        license_plate: truck.license_plate || 'Truck',
        make: truck.make || '',
        model: truck.model || '',
        status: truck.status || 'unknown'
      },
      current_volume: set.current_volume ?? 0,
      current_weight: set.current_weight ?? 0,
      available_volume: set.available_volume ?? 0,
      available_weight: set.available_weight ?? 0,
      is_available: set.is_available ?? true,
      available_for_backhaul: set.available_for_backhaul ?? false,
      region: set.region ?? { name: 'N/A' },
      current_region: set.current_region ?? { name: 'N/A' },
      active_orders: set.active_orders ?? []
    }
  }).filter(set => {
    if (assignmentTypeFilter.value === 'all') return true
    if (assignmentTypeFilter.value === 'backhaul') return set.available_for_backhaul
    if (assignmentTypeFilter.value === 'regular') return !set.available_for_backhaul
    return true
  })
})

// Add this computed property to process the assigned deliveries with manifest status
const processedAssignedDeliveries = computed(() => {
  return filteredDeliveries.value.map(delivery => {
    if (!delivery.driver_truck_assignment) return delivery

    // Find the driver truck set that matches this assignment
    const matchingSet = driverTruckSets.value.find(set => 
      set.id === delivery.driver_truck_assignment.id
    )

    // If we found a matching set, add the manifest status to the delivery's assignment
    if (matchingSet) {
      return {
        ...delivery,
        driver_truck_assignment: {
          ...delivery.driver_truck_assignment,
          has_finalized_manifest: matchingSet.has_finalized_manifest,
          manifest_status: matchingSet.manifest_status
        }
      }
    }

    return delivery
  })
})

const totalSelectedVolume = computed(() => {
  return selectedDeliveries.value.reduce((total, delivery) => {
    const packages = delivery.delivery_request?.packages || []
    return total + calculateTotalVolume(packages)
  }, 0)
})

const totalSelectedWeight = computed(() => {
  return selectedDeliveries.value.reduce((total, delivery) => {
    const packages = delivery.delivery_request?.packages || []
    return total + calculateTotalWeight(packages)
  }, 0)
})

const hasAnyUnstickerizedPackages = computed(() => {
  return selectedDeliveries.value.some(delivery => hasUnstickerizedPackages(delivery))
})

const hasMixedAssignmentTypes = computed(() => {
  if (selectedDeliveries.value.length === 0) return false
  const hasBackhaul = selectedDeliveries.value.some(d => d.is_backhaul)
  const hasRegular = selectedDeliveries.value.some(d => !d.is_backhaul)
  return hasBackhaul && hasRegular
})

const hasAnyRegionMismatch = computed(() => {
  return selectedDeliveries.value.some(delivery => hasRegionMismatch(delivery))
})

const validationSummary = computed(() => {
  if (!selectedSet.value || selectedDeliveries.value.length === 0) {
    return {
      isValid: false,
      message: 'Please select deliveries and a driver-truck set',
      details: [],
      availableVolume: 0,
      availableWeight: 0
    }
  }

  const details = []
  
  const truckCapacity = selectedSet.value.truck || {}
  const currentVolume = selectedSet.value.current_volume || 0
  const currentWeight = selectedSet.value.current_weight || 0
  
  const availableVolume = (truckCapacity.volume_capacity || 0) - currentVolume
  const availableWeight = (truckCapacity.weight_capacity || 0) - currentWeight

  // Capacity checks
  if (totalSelectedVolume.value > availableVolume) {
    details.push(`Insufficient volume capacity: ${formatVolume(totalSelectedVolume.value)} selected vs ${formatVolume(availableVolume)} available`)
  }

  if (totalSelectedWeight.value > availableWeight) {
    details.push(`Insufficient weight capacity: ${formatWeight(totalSelectedWeight.value)} selected vs ${formatWeight(availableWeight)} available`)
  }

  // Region validation
  const homeRegionId = selectedSet.value.region?.id
  const currentRegionId = selectedSet.value.current_region?.id
  const isBackhaulSet = selectedSet.value.available_for_backhaul
  
  selectedDeliveries.value.forEach(delivery => {
    const pickupRegionId = delivery.delivery_request?.pick_up_region?.id
    const dropoffRegionId = delivery.delivery_request?.drop_off_region?.id
    
    if (isBackhaulSet) {
      // Backhaul validation
      if (pickupRegionId !== currentRegionId) {
        details.push(`Backhaul: Pickup must be from current region (${selectedSet.value.current_region?.name})`)
      }
      if (dropoffRegionId !== homeRegionId) {
        details.push(`Backhaul: Delivery must be to home region (${selectedSet.value.region?.name})`)
      }
    } else {
      // Regular assignment validation
      if (pickupRegionId !== homeRegionId) {
        details.push(`Regular: Pickup must be from home region (${selectedSet.value.region?.name})`)
      }
    }
  })

  // Mixed assignment types
  if (hasMixedAssignmentTypes.value) {
    details.push('Cannot mix regular and backhaul assignments in the same batch')
  }

  // Check for packages without stickers
  if (hasAnyUnstickerizedPackages.value) {
    details.push('Some packages are missing stickers')
  }

  const isValid = details.length === 0

  return {
    isValid,
    message: isValid ? 'Assignment is valid' : 'Assignment validation failed',
    details,
    availableVolume,
    availableWeight
  }
})

// Watch for tab changes and trigger filter update
watch(activeTab, (newTab, oldTab) => {
  if (newTab !== oldTab) {
    switchTab(newTab)
  }
})

// Methods
async function loadData() {
  loading.value = true
  error.value = null
  try {
    const payload = {
      ...filters,
      activeTab: activeTab.value
    }
    
    await router.reload({
      data: payload,
      preserveState: true,
      preserveScroll: true
    })
  } catch (err) {
    error.value = err.message || 'Failed to load deliveries'
  } finally {
    loading.value = false
  }
}

async function switchTab(tab) {
  if (tab === activeTab.value || tabLoading.value) return
  
  tabLoading.value = true
  activeTab.value = tab
  
  try {
    const payload = {
      ...filters,
      activeTab: tab
    }
    
    await router.visit(route('cargo-assignments.index'), {
      data: payload,
      preserveState: true,
      preserveScroll: true,
      replace: true,
      only: ['deliveries', 'filters'],
      onFinish: () => {
        tabLoading.value = false
      }
    })
  } catch (err) {
    error.value = 'Failed to switch tabs'
    tabLoading.value = false
  }
}

async function refreshData() {
  refreshing.value = true
  try {
    await router.reload({
      only: ['deliveries', 'driverTruckSets', 'batchSuggestions'],
      onSuccess: () => {
        error.value = null
      }
    })
  } catch (err) {
    error.value = 'Failed to refresh data'
  } finally {
    refreshing.value = false
  }
}

function handleFilterChange() {
  const payload = {
    ...filters,
    activeTab: activeTab.value
  }
  
  router.visit(route('cargo-assignments.index'), {
    data: payload,
    preserveState: true,
    preserveScroll: true,
    replace: true,
    only: ['deliveries', 'filters']
  })
}

// Debounced search
let searchTimeout = null
function handleDebouncedFilter() {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    handleFilterChange()
  }, 500)
}

function resetFilters() {
  filters.search = '';
  filters.region_id = '';
  filters.driver_id = '';
  filters.sticker_status = '';
  // REMOVED: filters.status = '';
  handleFilterChange();
}

function handlePageChange(page) {
  if (page >= 1 && page <= deliveries.value.meta.last_page) {
    const payload = {
      ...filters,
      activeTab: activeTab.value,
      page: page
    }
    
    // Remove empty filters
    Object.keys(payload).forEach(key => {
      if (payload[key] === '' || payload[key] === null || payload[key] === undefined) {
        delete payload[key];
      }
    });
    
    router.visit(route('cargo-assignments.index'), {
      data: payload,
      preserveState: true,
      preserveScroll: true,
      replace: true
    })
  }
}

// Selection methods
function toggleSelectAll() {
  if (allSelected.value) {
    // Deselect all
    selectedDeliveries.value = selectedDeliveries.value.filter(
      selected => !filteredDeliveries.value.some(d => d.id === selected.id)
    )
  } else {
    // Select all filtered deliveries that aren't already selected
    const newSelections = filteredDeliveries.value.filter(
      delivery => !selectedDeliveries.value.some(selected => selected.id === delivery.id)
    )
    selectedDeliveries.value.push(...newSelections)
  }
}

function toggleDeliverySelection(delivery) {
  const isSelected = selectedDeliveries.value.some(d => d.id === delivery.id)
  if (isSelected) {
    selectedDeliveries.value = selectedDeliveries.value.filter(d => d.id !== delivery.id)
  } else {
    selectedDeliveries.value.push(delivery)
  }
}

function isDeliverySelected(deliveryId) {
  return selectedDeliveries.value.some(d => d.id === deliveryId)
}

function removeDelivery(deliveryId) {
  selectedDeliveries.value = selectedDeliveries.value.filter(d => d.id !== deliveryId)
}

function clearSelection() {
  selectedDeliveries.value = []
  selectedSet.value = null
}

function selectSet(set) {
  selectedSet.value = set
}

function openAssignmentModal() {
  if (validationSummary.value.isValid) {
    showAssignmentModal.value = true
  }
}

function closeAssignmentModal() {
  showAssignmentModal.value = false
}

function submitAssignment() {
  const deliveryRequestIds = selectedDeliveries.value.map(d => d.delivery_request_id || d.id)
  
  // Ensure departure time is at least 1 minute in the future
  const estimatedDeparture = new Date(Date.now() + 60000); // Now + 1 minute
  
  router.post(route('cargo-assignments.assign.batch'), {
    delivery_request_ids: deliveryRequestIds,
    driver_truck_assignment_id: selectedSet.value.id,
    estimated_departure: estimatedDeparture.toISOString()
  }, {
    onStart: () => loading.value = true,
    onFinish: () => {
      loading.value = false
      showAssignmentModal.value = false
      clearSelection()
    },
    onError: (errors) => {
      console.error('Batch assignment failed:', errors)
      alert('Failed to assign deliveries. Please try again.')
    }
  })
}

function openDispatchModal(set) {
  dispatchSet.value = set
  showDispatchModal.value = true
}

function closeDispatchModal() {
  showDispatchModal.value = false
  dispatchSet.value = null
}

function handleDispatched() {
  // Refresh data after successful dispatch
  refreshData()
  closeDispatchModal()
}

function hasAssignedOrders(set) {
  return set.active_orders && set.active_orders.length > 0
}

function getAssignedOrdersCount(set) {
  return set.active_orders ? set.active_orders.length : 0
}

function confirmCancel(delivery) {
  // Safety check - don't allow cancellation if manifest is finalized
  if (delivery.driver_truck_assignment?.has_finalized_manifest) {
    alert('Cannot cancel assignment. Manifest has been finalized and this delivery is ready for dispatch.')
    return
  }
  
  deliveryToCancel.value = delivery
  cancellationReason.value = ''
}

function cancelDeliveryToCancel() {
  deliveryToCancel.value = null
  cancellationReason.value = ''
}

async function cancelDelivery() {
  if (!deliveryToCancel.value || !cancellationReason.value.trim()) return;

  try {
    await router.post(route('cargo-assignments.deliveries.cancel', deliveryToCancel.value.id), {
      reason: cancellationReason.value
    }, {
      onStart: () => loading.value = true,
      onFinish: () => {
        loading.value = false;
        deliveryToCancel.value = null;
        cancellationReason.value = '';
      },
      onSuccess: () => {
        // Refresh the data
        refreshData();
      },
      onError: (errors) => {
        console.error('Failed to cancel delivery:', errors);
        alert('Failed to cancel delivery assignment. Please try again.');
      }
    });
  } catch (error) {
    console.error('Error cancelling delivery:', error);
    alert('An error occurred while cancelling the delivery.');
  }
}

function viewDetails(deliveryId) {
  router.get(route('deliveries.show', deliveryId))
}

// Helper methods
function getSearchPlaceholder() {
  const placeholders = {
    ready: 'Search ready deliveries...',
    assigned: 'Search assigned deliveries...', 
    active: 'Search active deliveries...'
  }
  return placeholders[activeTab.value] || 'Search deliveries...'
}

function getTabLabel() {
  const labels = {
    ready: 'ready for assignment',
    assigned: 'assigned',
    active: 'active'
  }
  return labels[activeTab.value] || ''
}

function getSelectedRegionName(regionId) {
  const region = props.regions.find(r => r.id == (regionId || filters.region_id))
  return region?.name || 'Selected Region'
}

// Add a helper method to get driver name for display
function getSelectedDriverName(driverId) {
  if (!driverId) return ''
  const driverOption = driverOptions.value.find(opt => opt.value == driverId)
  return driverOption?.name || driverOption?.label || 'Selected Driver'
}

function getStickerStatusLabel(status) {
  const labels = {
    'with_stickers': 'With Stickers',
    'missing_stickers': 'Missing Stickers'
  }
  return labels[status] || status
}

function getStatusLabel(status) {
  const labels = {
    'ready': 'Ready',
    'pending_payment': 'Pending Payment'
  }
  return labels[status] || status
}



// Helper method to check if ALL packages have stickers
const hasAllStickers = (delivery) => {
  const packages = delivery.delivery_request?.packages || []
  if (packages.length === 0) return true
  return packages.every(pkg => pkg.sticker_printed_at)
}



function getDeliveryStatusLabel(status) {
  const labels = {
    'ready': 'Ready',
    'assigned': 'Assigned',
    'dispatched': 'Dispatched',
    'in_transit': 'In Transit'
  }
  return labels[status] || status
}

function getDeliveryStatusVariant(status) {
  const variants = {
    'ready': 'success',
    'assigned': 'info',
    'dispatched': 'primary',
    'in_transit': 'secondary'
  }
  return variants[status] || 'secondary'
}

function getInitials(name) {
  if (!name) return '??'
  return name.split(' ').map(n => n[0]).join('').toUpperCase().substring(0, 2)
}

function getVolumePercentage(set) {
  if (!set.truck || !set.truck.volume_capacity) return 0
  const currentVolume = set.current_volume || 0
  return Math.round((currentVolume / set.truck.volume_capacity) * 100)
}

function getWeightPercentage(set) {
  if (!set.truck || !set.truck.weight_capacity) return 0
  const currentWeight = set.current_weight || 0
  return Math.round((currentWeight / set.truck.weight_capacity) * 100)
}

function getCapacityStatusClass(set) {
  if (!set) return 'bg-gray-100 text-gray-800'
  const percentage = getCapacityPercentage(set)
  if (percentage >= 90) return 'bg-red-100 text-red-800'
  if (percentage >= 75) return 'bg-yellow-100 text-yellow-800'
  return 'bg-green-100 text-green-800'
}

function getCapacityPercentage(set) {
  if (!set || !set.truck) return 0
  const volumePercentage = set.truck.volume_capacity > 0 ? 
    Math.round((set.current_volume / set.truck.volume_capacity) * 100) : 0
  const weightPercentage = set.truck.weight_capacity > 0 ? 
    Math.round((set.current_weight / set.truck.weight_capacity) * 100) : 0
  return Math.max(volumePercentage, weightPercentage)
}

function formatDate(dateString) {
  if (!dateString) return 'N/A'
  return new Date(dateString).toLocaleDateString()
}

function formatDateTime(dateString) {
  if (!dateString) return 'N/A'
  return new Date(dateString).toLocaleString()
}

const formatVolume = (volume) => {
  const vol = Number(volume || 0)
  
  // If volume is very small, show in cm³
  if (vol < 0.001) {
    const volumeCm3 = vol * 1000000
    return `${volumeCm3.toFixed(0)} cm³`
  }
  
  // If volume is small but measurable in m³, show with more decimals
  if (vol < 0.01) {
    return `${vol.toFixed(6)} m³`
  }
  
  // Normal volume, show with 2 decimals
  return `${vol.toFixed(2)} m³`
}

const formatWeight = (weight) => {
  const weightNum = Number(weight) || 0
  
  if (weightNum === 0) return '0 kg'
  
  if (weightNum < 1) {
    return `${(weightNum * 1000).toFixed(0)} g`
  } else {
    return `${weightNum.toFixed(1)} kg`
  }
}

const calculateTotalVolume = (packages) => {
  if (!packages || !Array.isArray(packages)) return 0
  
  return packages.reduce((total, pkg) => {
    // If we have valid dimensions, calculate volume from them
    if (pkg.height && pkg.width && pkg.length) {
      const height = parseFloat(pkg.height) || 0
      const width = parseFloat(pkg.width) || 0
      const length = parseFloat(pkg.length) || 0
      
      // Calculate volume in cubic meters (cm³ to m³ conversion)
      const volumeM3 = (height * width * length) / 1000000
      return total + volumeM3
    }
    
    // Fallback to stored volume if dimensions aren't available
    return total + (Number(pkg.volume) || 0)
  }, 0)
}

function calculateTotalWeight(packages) {
  if (!packages || !Array.isArray(packages)) return 0
  return packages.reduce((total, pkg) => total + (Number(pkg.weight) || 0), 0)
}

// Helper functions for VISUAL highlighting only (not filtering)
const hasUnstickerizedPackages = (delivery) => {
  const packages = delivery.delivery_request?.packages || []
  return packages.some(pkg => !pkg.sticker_printed_at)
}

const hasRegionMismatch = (delivery) => {
  if (!selectedSet.value) return false
  
  const homeRegionId = selectedSet.value.region?.id
  const currentRegionId = selectedSet.value.current_region?.id
  const isBackhaulSet = selectedSet.value.available_for_backhaul
  
  const pickupRegionId = delivery.delivery_request?.pick_up_region?.id
  const dropoffRegionId = delivery.delivery_request?.drop_off_region?.id
  
  if (isBackhaulSet) {
    return pickupRegionId !== currentRegionId || dropoffRegionId !== homeRegionId
  } else {
    return pickupRegionId !== homeRegionId
  }
}



function prepareBatchAssignment(suggestion) {
  if (!suggestion || !suggestion.delivery_requests) {
    return
  }
  
  selectedDeliveries.value = suggestion.delivery_requests || []
  
  const suitableSets = suitableDriverTruckSets(suggestion)
  if (suitableSets.length > 0) {
    selectedSet.value = suitableSets[0]
  }
  
  openAssignmentModal()
}

function suitableDriverTruckSets(suggestion) {
  const sets = filteredDriverTruckSets.value
  if (!Array.isArray(sets) || sets.length === 0) {
    return []
  }
  return sets.filter(set => {
    const isInRegion = set.region?.id === suggestion.pickup_region?.id || 
                      (set.available_for_backhaul && set.current_region?.id === suggestion.pickup_region?.id)
    
    const truckCapacity = set.truck || {}
    const currentVolume = set.current_volume || 0
    const currentWeight = set.current_weight || 0
    const availableVolume = (truckCapacity.volume_capacity || 0) - currentVolume
    const availableWeight = (truckCapacity.weight_capacity || 0) - currentWeight
    const totalVolume = Number(suggestion.total_volume) || 0
    const totalWeight = Number(suggestion.total_weight) || 0
    const hasCapacity = availableVolume >= totalVolume && availableWeight >= totalWeight
    
    return set.is_available && isInRegion && hasCapacity
  })
}

function isBackhaulSuggestion(suggestion) {
  const homeRegionId = suggestion.pickup_region?.id
  const currentRegionId = suggestion.destination_region?.id
  return suggestion.delivery_requests?.every(delivery => {
    const pickupRegionId = delivery.delivery_request?.pick_up_region?.id
    const dropoffRegionId = delivery.delivery_request?.drop_off_region?.id
    return pickupRegionId === currentRegionId && dropoffRegionId === homeRegionId
  }) || false
}

function setAssignmentTypeFilter(type) {
  assignmentTypeFilter.value = type
}

function canAssignToSet(set) {
  // Block if set has finalized manifest
  if (set.has_finalized_manifest) return false
  
  if (!set.is_available) return false
  
  const truckCapacity = set.truck || {}
  const currentVolume = set.current_volume || 0
  const currentWeight = set.current_weight || 0
  const availableVolume = (truckCapacity.volume_capacity || 0) - currentVolume
  const availableWeight = (truckCapacity.weight_capacity || 0) - currentWeight
  
  return availableVolume >= totalSelectedVolume.value && 
         availableWeight >= totalSelectedWeight.value
}

// Lifecycle
onMounted(() => {
  // Set up periodic refresh for active deliveries
  setInterval(() => {
    if (activeTab.value === 'active') {
      refreshData()
    }
  }, 30000) // Refresh every 30 seconds
})
</script>