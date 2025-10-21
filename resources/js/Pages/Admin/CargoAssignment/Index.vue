<template>
  <EmployeeLayout>
    <template #header>
      <div class="flex justify-between items-center px-6">
        <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">Cargo Assignment Dashboard</h2>
        <div class="flex space-x-2">
          <SearchInput 
            v-model="searchTerm" 
            placeholder="Search deliveries..." 
            class="w-64"
            @search="applyFilters"
          />
          <SecondaryButton @click="refreshData" class="inline-flex items-center">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
            </svg>
            Refresh
          </SecondaryButton>
        </div>
      </div>
    </template>

    <!-- Status Messages -->
    <div v-if="hasFlashMessages" class="px-6 py-2">
      <FlashMessages :flash="flash" />
    </div>

    <!-- Filters Section -->
    <div class="px-6 py-4 bg-gray-50 dark:bg-gray-700 border-b border-gray-200 dark:border-gray-600">
      <div class="flex flex-row items-center gap-4">
        <div class="flex-1 flex flex-row items-center gap-4">
          <SelectInput
            v-model="statusFilter"
            :options="statusOptions"
            placeholder="Filter by status"
            class="flex-1 min-w-0"
          />
          <SelectInput
            v-model="regionFilter"
            :options="regionOptions"
            placeholder="Filter by region"
            class="flex-1 min-w-0"
          />
          <SelectInput
            v-model="backhaulFilter"
            :options="backhaulOptions"
            placeholder="Backhaul status"
            class="flex-1 min-w-0"
          />
        </div>
        <SecondaryButton @click="resetFilters" size="sm">
          Clear Filters
        </SecondaryButton>
      </div>
    </div>

    <div class="px-6 py-4">
      <!-- Delivery Orders Table -->
      <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg border border-gray-200 dark:border-gray-700 mb-6">
        <div class="p-4 bg-gray-50 dark:bg-gray-700 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
          <h3 class="font-medium text-gray-900 dark:text-gray-100">
            Delivery Orders
          </h3>
          <div class="text-sm text-gray-500 dark:text-gray-400">
            Showing {{ deliveries?.from ?? 0 }} to {{ deliveries?.to ?? 0 }} of {{ deliveries?.total ?? 0 }} entries
          </div>
        </div>
        
        <DataTable
          :columns="deliveryColumns"
          :data="transformedDeliveries"
          :loading="loading"
          selectable
          @selection-change="handleSelectionChange"
        >
          <template #status="{ row }">
            <div class="flex items-center space-x-2">
              <StatusBadge
                :status="row.status"
                :class="statusBadgeClass(row.status)"
              >
                {{ formatStatusText(row.status) }}
              </StatusBadge>
              <span 
                v-if="row.is_backhaul" 
                class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200"
              >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 mr-1" viewBox="0 0 20 20" fill="currentColor">
                  <path fill-rule="evenodd" d="M12.586 4.586a2 2 0 112.828 2.828l-3 3a2 2 0 01-2.828 0 1 1 0 00-1.414 1.414 4 4 0 005.656 0l3-3a4 4 0 00-5.656-5.656l-1.5 1.5a1 1 0 101.414 1.414l1.5-1.5zm-5 5a2 2 0 012.828 0 1 1 0 101.414-1.414 4 4 0 00-5.656 0l-3 3a4 4 0 105.656 5.656l1.5-1.5a1 1 0 10-1.414-1.414l-1.5 1.5a2 2 0 11-2.828-2.828l3-3z" clip-rule="evenodd" />
                </svg>
                Backhaul
              </span>
            </div>
          </template>
          <template #packages="{ row }">
            <div class="flex flex-col items-center">
              <span>{{ row.delivery_request?.packages?.length ?? 0 }}</span>
              <span v-if="hasUnstickerizedPackages(row)" class="text-yellow-500" title="Some packages are missing stickers">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                  <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                </svg>
              </span>
            </div>
          </template>
          <template #assignment_type="{ row }">
            <div class="flex items-center">
              <span 
                v-if="row.is_backhaul" 
                class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200"
              >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 mr-1" viewBox="0 0 20 20" fill="currentColor">
                  <path fill-rule="evenodd" d="M12.586 4.586a2 2 0 112.828 2.828l-3 3a2 2 0 01-2.828 0 1 1 0 00-1.414 1.414 4 4 0 005.656 0l3-3a4 4 0 00-5.656-5.656l-1.5 1.5a1 1 0 101.414 1.414l1.5-1.5zm-5 5a2 2 0 012.828 0 1 1 0 101.414-1.414 4 4 0 00-5.656 0l-3 3a4 4 0 105.656 5.656l1.5-1.5a1 1 0 10-1.414-1.414l-1.5 1.5a2 2 0 11-2.828-2.828l3-3z" clip-rule="evenodd" />
                </svg>
                Backhaul
              </span>
              <span 
                v-else 
                class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200"
              >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 mr-1" viewBox="0 0 20 20" fill="currentColor">
                  <path d="M8 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM15 16.5a1 1 0 11-3 0 1 1 0 013 0z" />
                  <path d="M3 4a1 1 0 00-1 1v10a1 1 0 001 1h1.05a2.5 2.5 0 014.9 0H10a1 1 0 001-1v-1a1 1 0 011-1h2a1 1 0 011 1v1a1 1 0 001 1h1.05a2.5 2.5 0 014.9 0H19a1 1 0 001-1V5a1 1 0 00-1-1H3z" />
                </svg>
                Regular
              </span>
            </div>
          </template>
          <template #actions="{ row }">
            <div class="flex space-x-1">
              <SecondaryButton 
                @click.stop="viewDetails(row.id)" 
                size="xs" 
                title="Details"
              >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor">
                  <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                </svg>
              </SecondaryButton>
              <DangerButton 
                v-if="row.status !== 'completed' && row.status !== 'cancelled'"
                @click.stop="confirmCancel(row)" 
                size="xs" 
                title="Cancel"
              >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor">
                  <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
                </svg>
              </DangerButton>
            </div>
          </template>
        </DataTable>
        
        <Pagination
          v-if="deliveries?.last_page > 1"
          :pagination="deliveries"
          @page-changed="handlePageChange"
        />
      </div>

      <!-- Assignment Section -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Selected Deliveries Card -->
        <div class="lg:col-span-1">
          <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg border border-gray-200 dark:border-gray-700">
            <div class="p-4 bg-gray-50 dark:bg-gray-700 border-b border-gray-200 dark:border-gray-700">
              <div class="flex justify-between items-center">
                <h3 class="font-medium text-gray-900 dark:text-gray-100">
                  Selected Deliveries ({{ selectedDeliveries?.length ?? 0 }})
                </h3>
                <button 
                  v-if="selectedDeliveries?.length > 0"
                  @click="clearSelection"
                  class="text-xs text-red-500 hover:text-red-700"
                >
                  Clear All
                </button>
              </div>
            </div>
            <div class="divide-y divide-gray-200 dark:divide-gray-700 max-h-96 overflow-y-auto">
              <template v-if="selectedDeliveries?.length > 0">
                <div 
                  v-for="delivery in selectedDeliveries" 
                  :key="delivery.id" 
                  class="p-4 hover:bg-gray-50 dark:hover:bg-gray-700/50"
                >
                  <div class="flex justify-between items-start">
                    <div>
                      <p class="text-sm font-medium text-gray-900 dark:text-gray-100">
                        DO-{{ delivery.id?.toString()?.padStart(6, '0') }}
                        <span 
                          v-if="delivery.is_backhaul" 
                          class="ml-1 inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-purple-100 text-purple-800"
                        >
                          Backhaul
                        </span>
                      </p>
                      <p class="text-xs text-gray-500 dark:text-gray-400">
                        {{ delivery.delivery_request?.pick_up_region?.name ?? 'N/A' }} → 
                        {{ delivery.delivery_request?.drop_off_region?.name ?? 'N/A' }}
                      </p>
                      <div class="mt-1 text-xs">
                        <p>Volume: {{ calculateTotalVolume(delivery.delivery_request?.packages ?? []) }} m³</p>
                        <p>Weight: {{ calculateTotalWeight(delivery.delivery_request?.packages ?? []) }} kg</p>
                        <p v-if="hasUnstickerizedPackages(delivery)" class="text-yellow-600 dark:text-yellow-400 flex items-center">
                          <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 mr-1" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                          </svg>
                          Missing stickers
                        </p>
                      </div>
                    </div>
                    <button 
                      @click="removeDelivery(delivery.id)"
                      class="text-red-500 hover:text-red-700"
                      title="Remove"
                    >
                      <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd"/>
                      </svg>
                    </button>
                  </div>
                </div>
              </template>
              <div v-else class="p-4 text-center text-gray-500 dark:text-gray-400">
                No deliveries selected
              </div>
            </div>
            <div v-if="selectedDeliveries?.length > 0" class="p-4 border-t border-gray-200 dark:border-gray-700">
              <div class="text-sm">
                <p class="font-medium">Total Selected:</p>
                <p>Volume: {{ totalSelectedVolume }} m³</p>
                <p>Weight: {{ totalSelectedWeight }} kg</p>
                <p v-if="hasAnyUnstickerizedPackages" class="text-yellow-600 dark:text-yellow-400 font-medium">
                  ⚠️ Some packages are missing stickers
                </p>
                <p v-if="hasMixedAssignmentTypes" class="text-red-600 dark:text-red-400 font-medium">
                  ⚠️ Mixed assignment types (Regular + Backhaul)
                </p>
                <p v-if="hasRegionMismatch" class="text-red-600 dark:text-red-400 font-medium">
                  ⚠️ Region mismatch in assignments
                </p>
              </div>
              <PrimaryButton 
                class="mt-4 w-full"
                :disabled="!canAssignSelected"
                @click="openAssignmentModal"
              >
                Assign Selected
              </PrimaryButton>
            </div>
          </div>
        </div>

        <!-- Driver-Truck Sets -->
        <div class="lg:col-span-2">
          <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg border border-gray-200 dark:border-gray-700">
            <div class="p-4 bg-gray-50 dark:bg-gray-700 border-b border-gray-200 dark:border-gray-700">
              <h3 class="font-medium text-gray-900 dark:text-gray-100">
                Available Driver-Truck Sets
                <div class="mt-2 flex space-x-2">
                  <button 
                    v-for="type in assignmentTypeOptions" 
                    :key="type.value"
                    @click="setAssignmentTypeFilter(type.value)"
                    class="px-2 py-1 text-xs rounded border"
                    :class="assignmentTypeFilter === type.value 
                      ? 'bg-blue-100 text-blue-800 border-blue-300 dark:bg-blue-900 dark:text-blue-200 dark:border-blue-700' 
                      : 'bg-gray-100 text-gray-600 border-gray-300 dark:bg-gray-700 dark:text-gray-400 dark:border-gray-600'"
                  >
                    {{ type.label }}
                  </button>
                </div>
              </h3>
            </div>
            <div class="p-4">
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <template v-if="filteredDriverTruckSets?.length > 0">
                  <div 
                    v-for="set in filteredDriverTruckSets" 
                    :key="set.id" 
                    class="border rounded-lg p-4 hover:border-blue-500 transition-colors cursor-pointer"
                    :class="{ 
                      'border-blue-500 bg-blue-50 dark:bg-blue-900/20': selectedSet?.id === set.id,
                      'border-purple-500 bg-purple-50 dark:bg-purple-900/20': set.available_for_backhaul,
                      'opacity-50 cursor-not-allowed': !set.is_available,
                      'cursor-pointer': set.is_available
                    }"
                    @click="set.is_available && selectSet(set)"
                  >
                    <div class="flex items-start space-x-3">
                      <!-- Driver Info -->
                      <div class="flex-1">
                        <div class="flex items-center space-x-2">
                          <div class="h-10 w-10 rounded-full bg-gray-200 dark:bg-gray-600 flex items-center justify-center">
                            <span class="text-gray-600 dark:text-gray-300 font-medium">{{ set.driver.initials ?? '' }}</span>
                          </div>
                          <div>
                            <p class="font-medium">{{ set.driver.name ?? 'N/A' }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ set.driver.employee_id ?? '' }}</p>
                            <span 
                              v-if="set.available_for_backhaul" 
                              class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200 mt-1"
                            >
                              <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 mr-1" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M12.586 4.586a2 2 0 112.828 2.828l-3 3a2 2 0 01-2.828 0 1 1 0 00-1.414 1.414 4 4 0 005.656 0l3-3a4 4 0 00-5.656-5.656l-1.5 1.5a1 1 0 101.414 1.414l1.5-1.5zm-5 5a2 2 0 012.828 0 1 1 0 101.414-1.414 4 4 0 00-5.656 0l-3 3a4 4 0 105.656 5.656l1.5-1.5a1 1 0 10-1.414-1.414l-1.5 1.5a2 2 0 11-2.828-2.828l3-3z" clip-rule="evenodd" />
                              </svg>
                              Backhaul Available
                            </span>
                          </div>
                        </div>
                        <div class="mt-2 text-xs">
                          <p>Current Assignments: {{ set.driver?.delivery_orders_count ?? 0 }}</p>
                          <p>Available: 
                            <span :class="set.driver?.canAcceptNewAssignment ? 'text-green-600' : 'text-red-600'">
                              {{ set.driver?.canAcceptNewAssignment ? 'Yes' : 'No' }}
                            </span>
                          </p>
                          <p v-if="set.available_for_backhaul" class="text-purple-600">
                            Current: {{ set.current_region?.name ?? 'N/A' }}
                          </p>
                          <p v-else class="text-blue-600">
                            Home: {{ set.region?.name ?? 'N/A' }}
                          </p>
                        </div>
                      </div>
                      <!-- Truck Info -->
                      <div class="flex-1">
                        <div class="flex items-center space-x-2">
                          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-indigo-500" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M8 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM15 16.5a1 1 0 11-3 0 1 1 0 013 0z" />
                            <path d="M3 4a1 1 0 00-1 1v10a1 1 0 001 1h1.05a2.5 2.5 0 014.9 0H10a1 1 0 001-1v-1a1 1 0 011-1h2a1 1 0 011 1v1a1 1 0 001 1h1.05a2.5 2.5 0 014.9 0H19a1 1 0 001-1V5a1 1 0 00-1-1H3z" />
                          </svg>
                          <div>
                            <p class="font-medium">{{ set.truck?.license_plate ?? 'N/A' }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ set.truck?.make ?? '' }} {{ set.truck?.model ?? '' }}</p>
                            <span 
                              v-if="set.truck?.status === 'available_for_backhaul'"
                              class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-purple-100 text-purple-800 mt-1"
                            >
                              Backhaul
                            </span>
                          </div>
                        </div>
                        <div class="mt-2 space-y-2">
                          <!-- Volume Capacity -->
                          <div>
                            <p class="text-xs">Volume: {{ (set.current_volume ?? 0).toFixed(2) }} / {{ set.truck?.volume_capacity ?? 0 }} m³</p>
                            <div class="w-full bg-gray-200 dark:bg-gray-600 rounded-full h-1.5">
                              <div 
                                class="bg-blue-600 h-1.5 rounded-full" 
                                :style="{ width: `${Math.min(100, ((set.current_volume ?? 0) / (set.truck?.volume_capacity || 1)) * 100)}%` }"
                              ></div>
                            </div>
                          </div>
                          <!-- Weight Capacity -->
                          <div>
                            <p class="text-xs">Weight: {{ (set.current_weight ?? 0).toFixed(2) }} / {{ set.truck?.weight_capacity ?? 0 }} kg</p>
                            <div class="w-full bg-gray-200 dark:bg-gray-600 rounded-full h-1.5">
                              <div 
                                class="bg-green-600 h-1.5 rounded-full" 
                                :style="{ width: `${Math.min(100, ((set.current_weight ?? 0) / (set.truck?.weight_capacity || 1)) * 100)}%` }"
                              ></div>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>

                    <!-- Backhaul Management Buttons -->
                    <div v-if="set.is_available && !set.available_for_backhaul" class="mt-3 pt-3 border-t border-gray-200 dark:border-gray-700">
                      <SecondaryButton 
                        size="xs" 
                        class="w-full"
                        @click.stop="enableBackhaul(set)"
                        :disabled="!canEnableBackhaul(set)"
                      >
                        Enable Backhaul
                      </SecondaryButton>
                    </div>

                    <!-- Dispatch Button -->
                    <PrimaryButton
                      class="mt-4 w-full"
                      :disabled="!set.is_available || dispatchingSetId === set.id"
                      @click.stop="openDispatchModal(set)"
                    >
                      <span v-if="dispatchingSetId === set.id">
                        <LoadingSpinner size="xs" class="mr-2" /> Dispatching...
                      </span>
                      <span v-else>
                        Dispatch
                      </span>
                    </PrimaryButton>
                  </div>
                </template>
                <div v-else class="p-4 text-center text-gray-500 dark:text-gray-400">
                  No available driver-truck sets found
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Batch Assignment Suggestions -->
    <div class="mt-8 bg-white dark:bg-gray-800 shadow-sm rounded-lg border border-gray-200 dark:border-gray-700">
      <div class="p-4 bg-gray-50 dark:bg-gray-700 border-b border-gray-200 dark:border-gray-700">
        <h3 class="font-medium text-gray-900 dark:text-gray-100">
          Batch Assignment Suggestions
        </h3>
      </div>
      <div class="p-4">
        <div v-if="effectiveBatchSuggestions.length > 0" class="space-y-4">
          <div v-for="suggestion in effectiveBatchSuggestions" :key="suggestion.destination_region?.id || suggestion.pickup_region?.id" 
               class="border rounded-lg p-4 hover:bg-gray-50 dark:hover:bg-gray-700/50">
            <div class="flex justify-between items-start">
              <div>
                <p class="font-medium">
                  From: {{ suggestion.pickup_region?.name ?? 'N/A' }} → To: {{ suggestion.destination_region?.name ?? 'N/A' }}
                </p>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                  {{ suggestion.delivery_requests?.length ?? 0 }} deliveries
                </p>
                <p class="text-sm mt-2">
                  Total Volume: {{ (suggestion.total_volume || 0).toFixed(2) }} m³ | 
                  Total Weight: {{ (suggestion.total_weight || 0).toFixed(2) }} kg
                </p>
                <p v-if="isBackhaulSuggestion(suggestion)" class="text-purple-600 dark:text-purple-400 text-sm mt-1">
                  💡 Backhaul Opportunity: Deliveries from current region to home region
                </p>
                <p v-if="hasUnstickerizedPackagesInSuggestion(suggestion)" class="text-yellow-600 dark:text-yellow-400 text-sm mt-1">
                  ⚠️ Some deliveries have packages without stickers
                </p>
              </div>
              <PrimaryButton 
                size="sm" 
                @click="prepareBatchAssignment(suggestion)"
                :disabled="!suitableDriverTruckSets(suggestion).length || hasUnstickerizedPackagesInSuggestion(suggestion)"
              >
                Assign Batch
              </PrimaryButton>
            </div>
            
            <div v-if="suitableDriverTruckSets(suggestion).length" class="mt-4">
              <p class="text-sm font-medium">Suitable Driver-Truck Sets:</p>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-2 mt-2">
                <div v-for="set in suitableDriverTruckSets(suggestion)" 
                     :key="set.id"
                     class="p-2 border rounded cursor-pointer"
                     :class="{ 
                       'border-blue-500': selectedSet?.id === set.id,
                       'border-purple-500': set.available_for_backhaul
                     }"
                     @click="selectSet(set)">
                  <p class="font-medium">{{ set.driver?.name ?? 'N/A' }}</p>
                  <p class="text-xs">{{ set.truck?.license_plate ?? 'N/A' }}</p>
                  <p class="text-xs">Available: {{ (set.available_volume ?? 0).toFixed(2) }}m³ {{ (set.available_weight ?? 0).toFixed(2) }}kg</p>
                  <p class="text-xs text-gray-500">Region: {{ set.region?.name ?? 'N/A' }}</p>
                  <span 
                    v-if="set.available_for_backhaul" 
                    class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-purple-100 text-purple-800 mt-1"
                  >
                    Backhaul
                  </span>
                </div>
              </div>
            </div>
            <div v-else class="mt-4 text-sm text-yellow-600">
              No suitable driver-truck sets available
            </div>
          </div>
        </div>
        <div v-else class="text-center text-gray-500 dark:text-gray-400">
          No batch assignment suggestions available
        </div>
      </div>
    </div>

    <!-- Assignment Confirmation Modal -->
    <ConfirmationModal 
      :show="showAssignmentModal"
      @close="closeAssignmentModal"
      @confirmed="submitAssignment"
      title="Confirm Assignment"
      confirm-text="Confirm Assignment"
      :confirm-disabled="!validationSummary.isValid"
    >
      <div class="space-y-4">
        <div class="p-4 rounded-lg" :class="{
          'bg-green-50 text-green-800': validationSummary.isValid,
          'bg-red-50 text-red-800': !validationSummary.isValid
        }">
          <p class="font-medium">{{ validationSummary.message }}</p>
          <ul v-if="validationSummary.details" class="mt-2 list-disc list-inside">
            <li v-for="detail in validationSummary.details" :key="detail">{{ detail }}</li>
          </ul>
        </div>

        <div class="grid grid-cols-2 gap-4">
          <div>
            <p class="text-sm font-medium">Total Selected Volume</p>
            <p class="text-lg">{{ totalSelectedVolume.toFixed(2) }} m³</p>
            <p class="text-xs">Available: {{ (validationSummary.availableVolume || 0).toFixed(2) }} m³</p>
          </div>
          <div>
            <p class="text-sm font-medium">Total Selected Weight</p>
            <p class="text-lg">{{ totalSelectedWeight.toFixed(2) }} kg</p>
            <p class="text-xs">Available: {{ (validationSummary.availableWeight || 0).toFixed(2) }} kg</p>
          </div>
        </div>

        <div class="border-t pt-4">
          <p class="text-sm font-medium mb-2">Selected Deliveries:</p>
          <div class="space-y-2 max-h-32 overflow-y-auto">
            <div v-for="delivery in selectedDeliveries" :key="delivery.id" class="flex justify-between text-sm">
              <span>DO-{{ delivery.id.toString().padStart(6, '0') }}</span>
              <span>{{ delivery.delivery_request?.pick_up_region?.name ?? 'N/A' }} → {{ delivery.delivery_request?.drop_off_region?.name ?? 'N/A' }}</span>
              <span v-if="delivery.is_backhaul" class="text-purple-600">Backhaul</span>
            </div>
          </div>
        </div>

        <div class="border-t pt-4">
          <p class="text-sm font-medium mb-2">Selected Driver-Truck Set:</p>
          <div class="flex items-center space-x-3">
            <div class="h-10 w-10 rounded-full bg-gray-200 dark:bg-gray-600 flex items-center justify-center">
              <span class="text-gray-600 dark:text-gray-300 font-medium">{{ selectedSet?.driver?.initials ?? '' }}</span>
            </div>
            <div>
              <p class="font-medium">{{ selectedSet?.driver?.name ?? 'N/A' }}</p>
              <p class="text-xs text-gray-500">{{ selectedSet?.truck?.license_plate ?? 'N/A' }}</p>
              <span 
                v-if="selectedSet?.available_for_backhaul" 
                class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-purple-100 text-purple-800 mt-1"
              >
                Backhaul Available
              </span>
            </div>
          </div>
        </div>
      </div>
    </ConfirmationModal>

    <!-- Dispatch Modal -->
    <DispatchModal 
      :show="showDispatchModal"
      :set="dispatchingSet"
      @close="closeDispatchModal"
      @dispatched="handleDispatched"
    />

    <!-- Cancel Delivery Modal -->
    <ConfirmationModal 
      :show="!!deliveryToCancel"
      @close="cancelDeliveryToCancel"
      @confirmed="cancelDelivery"
      title="Cancel Delivery Order"
      confirm-text="Cancel Delivery"
      variant="danger"
    >
      <p>Are you sure you want to cancel delivery order DO-{{ deliveryToCancel?.id?.toString()?.padStart(6, '0') }}?</p>
      <p class="text-sm text-gray-600 dark:text-gray-400 mt-2">This action cannot be undone.</p>
    </ConfirmationModal>
  </EmployeeLayout>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import EmployeeLayout from '@/Layouts/EmployeeLayout.vue'
import DataTable from '@/Components/DataTable.vue'
import Pagination from '@/Components/Pagination.vue'
import SelectInput from '@/Components/SelectInput.vue'
import SearchInput from '@/Components/SearchInput.vue'
import PrimaryButton from '@/Components/PrimaryButton.vue'
import SecondaryButton from '@/Components/SecondaryButton.vue'
import DangerButton from '@/Components/DangerButton.vue'
import StatusBadge from '@/Components/StatusBadge.vue'
import FlashMessages from '@/Components/FlashMessages.vue'
import LoadingSpinner from '@/Components/LoadingSpinner.vue'
import ConfirmationModal from '@/Components/ConfirmationModal.vue'
import DispatchModal from '@/Components/DispatchModal.vue'

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

// Refs
const searchTerm = ref(props.filters?.search || '')
const statusFilter = ref(props.filters?.status || '')
const regionFilter = ref(props.filters?.region || '')
const backhaulFilter = ref(props.filters?.backhaul || '')
const assignmentTypeFilter = ref('all')

const selectedDeliveries = ref([])
const selectedSet = ref(null)
const loading = ref(false)
const showAssignmentModal = ref(false)
const showDispatchModal = ref(false)
const dispatchingSet = ref(null)
const dispatchingSetId = ref(null)
const deliveryToCancel = ref(null)

// Options
const backhaulOptions = [
  { value: '', label: 'All Backhaul Status' },
  { value: 'backhaul', label: 'Backhaul Only' },
  { value: 'regular', label: 'Regular Only' }
]

const assignmentTypeOptions = [
  { value: 'all', label: 'All Types' },
  { value: 'regular', label: 'Regular' },
  { value: 'backhaul', label: 'Backhaul' }
]

const statusOptions = [
  { value: '', label: 'All Statuses' },
  { value: 'pending', label: 'Pending' },
  { value: 'assigned', label: 'Assigned' },
  { value: 'picked_up', label: 'Picked Up' },
  { value: 'in_transit', label: 'In Transit' },
  { value: 'delivered', label: 'Delivered' },
  { value: 'completed', label: 'Completed' },
  { value: 'cancelled', label: 'Cancelled' }
]

// Computed Properties
const regionOptions = computed(() => [
  { value: '', label: 'All Regions' },
  ...(props.regions?.map(region => ({ value: region.id, label: region.name })) || [])
])

const hasFlashMessages = computed(() => {
  return props.flash?.success || props.flash?.error || props.flash?.warning
})

const deliveries = computed(() => props.deliveries || { data: [] })

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
  const suggestions = batchSuggestions.value
  
  if (backhaulFilter.value === 'backhaul') {
    return suggestions.filter(suggestion => isBackhaulSuggestion(suggestion))
  } else if (backhaulFilter.value === 'regular') {
    return suggestions.filter(suggestion => !isBackhaulSuggestion(suggestion))
  }
  
  return suggestions
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
      initials = driver.name.split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase()
    }
    
    return {
      ...set,
      driver: {
        ...driver,
        delivery_orders_count: driver.delivery_orders_count ?? driver.current_assignments ?? 0,
        current_assignments: driver.current_assignments ?? driver.delivery_orders_count ?? 0,
        canAcceptNewAssignment: driver.canAcceptNewAssignment ?? driver.available ?? false,
        available: driver.available ?? true,
        initials: initials,
        employee_id: driver.employee_id ?? driver.employeeProfile?.employee_id ?? 'N/A',
        name: driver.name ?? 'N/A'
      },
      truck: {
        ...truck,
        volume_capacity: truck.volume_capacity ?? 0,
        weight_capacity: truck.weight_capacity ?? 0,
        license_plate: truck.license_plate ?? 'N/A',
        make: truck.make ?? '',
        model: truck.model ?? '',
        status: truck.status ?? 'unknown'
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

const transformedDeliveries = computed(() => {
  return (deliveries.value?.data || []).map(delivery => ({
    ...delivery,
    packages_count: delivery.delivery_request?.packages?.length || 0,
    assignment_type: delivery.is_backhaul ? 'Backhaul' : 'Regular'
  }))
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

const hasRegionMismatch = computed(() => {
  if (!selectedSet.value || selectedDeliveries.value.length === 0) return false
  
  const homeRegionId = selectedSet.value.region?.id
  const currentRegionId = selectedSet.value.current_region?.id
  const isBackhaulSet = selectedSet.value.available_for_backhaul
  
  return selectedDeliveries.value.some(delivery => {
    const pickupRegionId = delivery.delivery_request?.pick_up_region?.id
    const dropoffRegionId = delivery.delivery_request?.drop_off_region?.id
    
    if (isBackhaulSet) {
      // Backhaul: pickup must be from current region, dropoff to home region
      return pickupRegionId !== currentRegionId || dropoffRegionId !== homeRegionId
    } else {
      // Regular: pickup must be from home region
      return pickupRegionId !== homeRegionId
    }
  })
})

const canAssignSelected = computed(() => {
  return selectedSet.value && 
         selectedDeliveries.value.length > 0 && 
         !hasAnyUnstickerizedPackages.value &&
         !hasMixedAssignmentTypes.value &&
         !hasRegionMismatch.value
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
    details.push(`Insufficient volume capacity: ${totalSelectedVolume.value.toFixed(2)}m³ selected vs ${availableVolume.toFixed(2)}m³ available`)
  }

  if (totalSelectedWeight.value > availableWeight) {
    details.push(`Insufficient weight capacity: ${totalSelectedWeight.value.toFixed(2)}kg selected vs ${availableWeight.toFixed(2)}kg available`)
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

// Table columns
const deliveryColumns = [
  { 
    field: 'id', 
    header: 'DO #', 
    sortable: true,
    formatter: (value) => value ? `DO-${value.toString().padStart(6, '0')}` : 'N/A'
  },
  { 
    field: 'delivery_request.pick_up_region.name', 
    header: 'Pickup', 
    sortable: true 
  },
  { 
    field: 'delivery_request.drop_off_region.name', 
    header: 'Dropoff', 
    sortable: true 
  },
  { 
    field: 'status', 
    header: 'Status', 
    sortable: true 
  },
  { 
    field: 'packages_count', 
    header: 'Packages', 
    sortable: false 
  },
  { 
    field: 'assignment_type', 
    header: 'Type', 
    sortable: true 
  },
  { 
    field: 'estimated_departure', 
    header: 'Est. Departure', 
    sortable: true,
    formatter: (value) => value ? formatDate(value) : 'N/A'
  },
  { 
    field: 'actions', 
    header: 'Actions', 
    sortable: false 
  }
]

// Methods
const applyFilters = () => {
  const params = {}
  if (searchTerm.value) params.search = searchTerm.value
  if (statusFilter.value) params.status = statusFilter.value
  if (regionFilter.value) params.region_id = regionFilter.value
  if (backhaulFilter.value) params.backhaul = backhaulFilter.value

  router.get(route('cargo-assignments.index'), params, {
    preserveState: true,
    replace: true
  })
}

const resetFilters = () => {
  searchTerm.value = ''
  statusFilter.value = ''
  regionFilter.value = ''
  backhaulFilter.value = ''
  assignmentTypeFilter.value = 'all'
  applyFilters()
}

const refreshData = () => {
  loading.value = true
  router.reload({
    only: ['deliveries', 'driverTruckSets', 'batchSuggestions'],
    onFinish: () => {
      loading.value = false
    }
  })
}

const handlePageChange = (page) => {
  router.get(route('cargo-assignments.index'), {
    ...props.filters,
    page
  }, {
    preserveState: true,
    preserveScroll: true
  })
}

const handleSelectionChange = (selected) => {
  selectedDeliveries.value = selected
}

const selectSet = (set) => {
  selectedSet.value = set
}

const clearSelection = () => {
  selectedDeliveries.value = []
  selectedSet.value = null
}

const removeDelivery = (deliveryId) => {
  selectedDeliveries.value = selectedDeliveries.value.filter(d => d.id !== deliveryId)
}

const openAssignmentModal = () => {
  if (validationSummary.value.isValid) {
    showAssignmentModal.value = true
  }
}

const closeAssignmentModal = () => {
  showAssignmentModal.value = false
}

const submitAssignment = () => {
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

const openDispatchModal = (set) => {
  dispatchingSet.value = set
  dispatchingSetId.value = set.id
  showDispatchModal.value = true
}

const closeDispatchModal = () => {
  showDispatchModal.value = false
  dispatchingSet.value = null
  dispatchingSetId.value = null
}

const handleDispatched = () => {
  closeDispatchModal()
  refreshData()
}

const confirmCancel = (delivery) => {
  deliveryToCancel.value = delivery
}

const cancelDeliveryToCancel = () => {
  deliveryToCancel.value = null
}

const cancelDelivery = () => {
  if (!deliveryToCancel.value) return

  router.post(route('cargo-assignments.deliveries.cancel', deliveryToCancel.value.id), {
    onStart: () => loading.value = true,
    onFinish: () => {
      loading.value = false
      deliveryToCancel.value = null
    }
  })
}

const viewDetails = (deliveryId) => {
  router.get(route('cargo-assignments.show', deliveryId))
}

const calculateTotalVolume = (packages) => {
  if (!packages || !Array.isArray(packages)) return 0
  return packages.reduce((total, pkg) => total + (Number(pkg.volume) || 0), 0)
}

const calculateTotalWeight = (packages) => {
  if (!packages || !Array.isArray(packages)) return 0
  return packages.reduce((total, pkg) => total + (Number(pkg.weight) || 0), 0)
}

const hasUnstickerizedPackages = (delivery) => {
  const packages = delivery.delivery_request?.packages || []
  return packages.some(pkg => !pkg.sticker_printed_at)
}

const hasUnstickerizedPackagesInSuggestion = (suggestion) => {
  return suggestion.delivery_requests?.some(delivery => hasUnstickerizedPackages(delivery)) || false
}

const formatDate = (dateString) => {
  if (!dateString) return 'N/A'
  const date = new Date(dateString)
  return date.toLocaleDateString() + ' ' + date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
}

const statusBadgeClass = (status) => {
  const classes = {
    pending: 'bg-yellow-100 text-yellow-800',
    assigned: 'bg-blue-100 text-blue-800',
    picked_up: 'bg-indigo-100 text-indigo-800',
    in_transit: 'bg-purple-100 text-purple-800',
    delivered: 'bg-green-100 text-green-800',
    completed: 'bg-gray-100 text-gray-800',
    cancelled: 'bg-red-100 text-red-800'
  }
  return classes[status] || 'bg-gray-100 text-gray-800'
}

const formatStatusText = (status) => {
  const statusMap = {
    pending: 'Pending',
    assigned: 'Assigned',
    picked_up: 'Picked Up',
    in_transit: 'In Transit',
    delivered: 'Delivered',
    completed: 'Completed',
    cancelled: 'Cancelled'
  }
  return statusMap[status] || status
}

const prepareBatchAssignment = (suggestion) => {
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

const suitableDriverTruckSets = (suggestion) => {
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

const isBackhaulSuggestion = (suggestion) => {
  const homeRegionId = suggestion.pickup_region?.id
  const currentRegionId = suggestion.destination_region?.id
  return suggestion.delivery_requests?.every(delivery => {
    const pickupRegionId = delivery.delivery_request?.pick_up_region?.id
    const dropoffRegionId = delivery.delivery_request?.drop_off_region?.id
    return pickupRegionId === currentRegionId && dropoffRegionId === homeRegionId
  }) || false
}

const setAssignmentTypeFilter = (type) => {
  assignmentTypeFilter.value = type
}

const enableBackhaul = (set) => {
  if (!canEnableBackhaul(set)) return
  
  router.post(route('cargo-assignments.backhaul.enable', set.id), {}, {
    onStart: () => loading.value = true,
    onFinish: () => {
      loading.value = false
      refreshData()
    },
    onError: (errors) => {
      console.error('Failed to enable backhaul:', errors)
      alert('Failed to enable backhaul. Please try again.')
    }
  })
}

const canEnableBackhaul = (set) => {
  return set.is_available && 
         !set.available_for_backhaul && 
         (set.driver?.canAcceptNewAssignment ?? set.driver?.available) &&
         set.truck?.status === 'available'
}

// Watchers
watch([searchTerm, statusFilter, regionFilter, backhaulFilter], () => {
  applyFilters()
})

onMounted(() => {
  if (props.filters) {
    searchTerm.value = props.filters.search || ''
    statusFilter.value = props.filters.status || ''
    regionFilter.value = props.filters.region_id || ''
    backhaulFilter.value = props.filters.backhaul || ''
  }
})
</script>